<?php
session_start();
require_once(__DIR__ . '/../../../config/database.php');


// ปิดการแสดง error
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // ตรวจสอบว่ามี user_id ใน session หรือไม่
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบใหม่');
    }

    // รับข้อมูล JSON จาก request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['drafts']) || empty($data['drafts'])) {
        throw new Exception('ไม่พบข้อมูลที่ต้องการบันทึก');
    }

    // เชื่อมต่อฐานข้อมูล
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();

    try {
        // เตรียม SQL query
        $sql = "INSERT INTO nutrition_records 
                (student_id, weight, height, meal_type, meal_status, nutrition_note, recorded_by) 
                VALUES 
                (:student_id, :weight, :height, :meal_type, :meal_status, :nutrition_note, :recorded_by)";

        $stmt = $pdo->prepare($sql);
        
        // บันทึกข้อมูลทีละรายการ
        foreach ($data['drafts'] as $draft) {
            $success = $stmt->execute([
                'student_id' => $draft['student_id'],
                'weight' => $draft['weight'],
                'height' => $draft['height'],
                'meal_type' => $draft['meal_type'],
                'meal_status' => $draft['meal_status'],
                'nutrition_note' => $draft['nutrition_note'] ?? null,
                'recorded_by' => $_SESSION['user_id']
            ]);

            if (!$success) {
                throw new Exception('Failed to save nutrition record');
            }
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกข้อมูลสำเร็จ'
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