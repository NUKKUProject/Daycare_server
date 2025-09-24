<?php
require_once(__DIR__ . '/../../../config/database.php'); // เชื่อมต่อไฟล์ database.php

// เพิ่ม error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = getDatabaseConnection();

// ฟังก์ชันตรวจสอบเวลามาสาย
function isLate($time) {
    // แปลงเวลาเป็นวินาที
    $time_parts = explode(':', $time);
    $hours = intval($time_parts[0]);
    $minutes = intval($time_parts[1]);
    $check_time = $hours * 3600 + $minutes * 60;
    
    // เวลาที่กำหนด (8:30)
    $cutoff_time = 8 * 3600 + 30 * 60;
    
    error_log("Check time: {$hours}:{$minutes} ({$check_time} seconds)");
    error_log("Cutoff time: 8:30 ({$cutoff_time} seconds)");
    
    return $check_time > $cutoff_time;
}

// รับข้อมูลจาก QR Code
$data = json_decode(file_get_contents('php://input'), true);
$name = trim(
    ($data['prefix'] ?? '') . ' ' .
    ($data['first_name'] ?? '') . ' ' .
    ($data['last_name'] ?? '')
);

error_log("Received data: " . print_r($data, true));


if ($data && isset($data['student_id'])) {
    
    try {
        // ตรวจสอบว่ามีนักเรียนคนนี้ในระบบหรือไม่
        $check_student = $pdo->prepare("SELECT studentid FROM children WHERE studentid = :student_id");
        $check_student->execute(['student_id' => $data['student_id']]);
        
        if (!$check_student->fetch()) {
            throw new Exception("ไม่พบข้อมูลนักเรียนในระบบ");
        }


        // ตรวจสอบว่ามีการบันทึกไปแล้วหรือไม่
        $check_stmt = $pdo->prepare("
            SELECT id 
            FROM attendance 
            WHERE student_id = :student_id 
            AND DATE(check_date) = CURRENT_DATE
        ");
        $check_stmt->execute(['student_id' => $data['student_id']]);
        
        if ($check_stmt->fetch()) {
            throw new Exception("มีการบันทึกการเข้าเรียนแล้วในวันนี้");
        }

        // ตรวจสอบเวลาปัจจุบัน
        $current_time = date('H:i');
        $status = isLate($current_time) ? 'late' : 'present';
        
        error_log("Current time: {$current_time}, Status: {$status}");

        // บันทึกการเช็คชื่อ
        $stmt = $pdo->prepare("
            INSERT INTO attendance (
                student_id, 
                check_date, 
                status,
                leave_note,
                status_checkout
            ) VALUES (
                :student_id::varchar, 
                CURRENT_TIMESTAMP, 
                :status::varchar,
                CASE 
                    WHEN :status::varchar = 'late' THEN 'มาสาย'::varchar 
                    ELSE NULL 
                END,
                'no_checked_out'::varchar
            ) RETURNING id
        ");
        
        $params = [
            'student_id' => $data['student_id'],
            'status' => $status
        ];

        error_log("Executing insert with params: " . print_r($params, true));
        
        $result = $stmt->execute($params);
        $inserted = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || !$inserted) {
            throw new Exception("ไม่สามารถบันทึกข้อมูลได้");
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกการเข้าเรียนสำเร็จ',
            'data' => [
                'id' => $inserted['id'],
                'student_id' => $data['student_id'],
                'name' => $name ?? 'ไม่ระบุชื่อ',
                'attendance_status' => $status,
                'time' => $current_time,
                'attendance_id' => $inserted['id'],
                'is_recorded' => true
            ]
        ]);

    } catch (Exception $e) {
        error_log("Error in attendance-submit.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid or missing data received");
    echo json_encode([
        'status' => 'error',
        'message' => 'ข้อมูลไม่ถูกต้องหรือไม่ครบถ้วน'
    ]);
}

?>