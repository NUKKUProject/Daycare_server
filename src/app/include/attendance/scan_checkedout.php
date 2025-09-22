<?php
require_once(__DIR__ . '/../../../config/database.php');// เชื่อมต่อไฟล์ database.php

$pdo = getDatabaseConnection();

// เพิ่ม error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// รับข้อมูลจาก QR Code และฟอร์ม
$data = json_decode(file_get_contents('php://input'), true);
error_log("Received data: " . print_r($data, true));

if ($data && isset($data['student_id'])) {
    try {
        $student_id = $data['student_id'];
        $picked_up_by = $data['picked_up_by'] ?? null;
        $picked_up_detail = $data['picked_up_detail'] ?? null;
        
        // ตรวจสอบว่ามีข้อมูลในตาราง attendance หรือไม่
        $check_stmt = $pdo->prepare("
            SELECT id, status_checkout 
            FROM attendance 
            WHERE student_id = :student_id 
            AND DATE(check_date) = CURRENT_DATE
        ");
        $check_stmt->execute(['student_id' => $student_id]);
        $attendance = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attendance) {
            throw new Exception("ไม่พบข้อมูลการเช็คชื่อของวันนี้");
        }

        if ($attendance['status_checkout'] === 'checked_out') {
            throw new Exception("มีการบันทึกการรับกลับบ้านแล้ว");
        }
        
        // อัพเดทข้อมูลในตาราง attendance
        $stmt = $pdo->prepare("
            UPDATE attendance 
            SET 
                status_checkout = 'checked_out',
                check_out_time = CURRENT_TIMESTAMP,
                picked_up_by = :picked_up_by,
                picked_up_detail = :picked_up_detail
            WHERE 
                student_id = :student_id 
                AND DATE(check_date) = CURRENT_DATE
            RETURNING id
        ");

        $params = [
            'student_id' => $student_id,
            'picked_up_by' => $picked_up_by,
            'picked_up_detail' => $picked_up_detail
        ];

        error_log("Executing update with params: " . print_r($params, true));
        
        $result = $stmt->execute($params);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || !$updated) {
            throw new Exception("ไม่สามารถอัพเดทข้อมูลได้");
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกการเช็คเอาท์สำเร็จ',
            'data' => [
                'student_id' => $student_id,
                'picked_up_by' => $picked_up_by,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        error_log("Error in scan_checkedout.php: " . $e->getMessage());
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
