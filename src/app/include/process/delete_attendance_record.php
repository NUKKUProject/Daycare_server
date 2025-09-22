<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการ�บ');
    }

    // เตรียมคำสั่ง SQL สำหรับลบข้อมูล
    $sql = "DELETE FROM attendance WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    // ทำการลบข้อมูล
    if (!$stmt->execute(['id' => $data['id']])) {
        throw new Exception('ไม่สามารถลบข้อมูลได้');
    }

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