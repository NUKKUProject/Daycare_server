<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับค่า ID จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการลบ');
    }

    $id = $data['id'];

    // เตรียมคำสั่ง SQL สำหรับลบข้อมูล
    $sql = "DELETE FROM health_data WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    // ทำการลบข้อมูล
    if (!$stmt->execute(['id' => $id])) {
        throw new Exception('ไม่สามารถลบข้อมูลได้');
    }

    // ส่งผลลัพธ์กลับ
    echo json_encode([
        'status' => 'success',
        'message' => 'ลบข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 