<?php
session_start();
require_once(__DIR__ . '/../../../config/database.php');

// ปิดการแสดง error
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    // ตรวจสอบ method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // ตรวจสอบ session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบใหม่');
    }

    // รับข้อมูล JSON จาก request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // ตรวจสอบความถูกต้องของข้อมูล
    if (!$data) {
        throw new Exception('ไม่พบข้อมูลที่ต้องการบันทึก');
    }

    // ตรวจสอบข้อมูลที่จำเป็น
    $required_fields = ['student_id', 'weight', 'height', 'meal_type', 'meal_status'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("กรุณากรอก {$field}");
        }
    }

    // เชื่อมต่อฐานข้อมูล
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();

    try {
        // เตรียม SQL query
        $sql = "INSERT INTO nutrition_records (
                    student_id, 
                    weight, 
                    height, 
                    meal_type, 
                    meal_status, 
                    nutrition_note, 
                    recorded_by,
                    recorded_at
                ) VALUES (
                    :student_id,
                    :weight,
                    :height,
                    :meal_type,
                    :meal_status,
                    :nutrition_note,
                    :recorded_by,
                    NOW()
                )";

        $stmt = $pdo->prepare($sql);
        
        // บันทึกข้อมูล
        $success = $stmt->execute([
            'student_id' => $data['student_id'],
            'weight' => $data['weight'],
            'height' => $data['height'],
            'meal_type' => $data['meal_type'],
            'meal_status' => $data['meal_status'],
            'nutrition_note' => $data['nutrition_note'] ?? null,
            'recorded_by' => $_SESSION['user_id']
        ]);

        if (!$success) {
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
        }

        // ดึง ID ของรายการที่เพิ่งบันทึก
        $recordId = $pdo->lastInsertId();

        $pdo->commit();

        // ส่งผลลัพธ์กลับ
        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกข้อมูลสำเร็จ',
            'data' => [
                'id' => $recordId
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 