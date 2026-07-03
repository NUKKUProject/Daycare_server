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
$first_name = $data['first_name'] ?? '';

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
        $check_stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = :student_id AND DATE(check_date) = CURRENT_DATE");
        $check_stmt->execute(['student_id' => $data['student_id']]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

        // ตรวจสอบเวลาปัจจุบันเพื่อกำหนดสถานะ (late / present) – แต่ไม่บันทึกลงฐานข้อมูล
        $current_time = date('H:i');
        $status = isLate($current_time) ? 'late' : 'present';

        // ดึงข้อมูลนักเรียนเพิ่มเติม (เพื่อให้ข้อมูลตอบกลับครบถ้วน)
        $student_info_stmt = $pdo->prepare("SELECT prefix_th, firstname_th, lastname_th, nickname, classroom FROM children WHERE studentid = :student_id");
        $student_info_stmt->execute(['student_id' => $data['student_id']]);
        $student_info = $student_info_stmt->fetch(PDO::FETCH_ASSOC);

        $full_name = $name;
        $classroom = '';
        $nickname = '';
        if ($student_info) {
            $full_name = trim(($student_info['prefix_th'] ?? '') . ' ' . ($student_info['firstname_th'] ?? '') . ' ' . ($student_info['lastname_th'] ?? ''));
            $classroom = $student_info['classroom'] ?? '';
            $nickname = $student_info['nickname'] ?? '';
            $first_name = $student_info['firstname_th'] ?? '';
        }

        if ($existing) {
            // มีการบันทึกแล้ว – ส่งผลลัพธ์ว่าได้บันทึกไว้แล้ว
            echo json_encode([
                'status' => 'warning',
                'message' => 'มีการบันทึกการเข้าเรียนแล้วในวันนี้',
                'data' => [
                    'student_id' => $data['student_id'],
                    'name' => $full_name,
                    'first_name' => $first_name,
                    'nickname' => $nickname,
                    'classroom' => $classroom,
                    'attendance_status' => $status,
                    'time' => $current_time,
                    'is_recorded' => true
                ]
            ]);
        } else {
            // ยังไม่มีการบันทึก – ส่งผลลัพธ์ว่าไม่มีบันทึกในวันนี้
            echo json_encode([
                'status' => 'success',
                'message' => 'ยังไม่มีการบันทึกการเข้าเรียนในวันนี้',
                'data' => [
                    'student_id' => $data['student_id'],
                    'name' => $full_name,
                    'first_name' => $first_name,
                    'nickname' => $nickname,
                    'classroom' => $classroom,
                    'attendance_status' => $status,
                    'time' => $current_time,
                    'is_recorded' => false
                ]
            ]);
        }

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