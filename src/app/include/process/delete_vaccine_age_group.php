<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('ไม่พบรหัสช่วงอายุที่ต้องการลบ');
    }

    // ตรวจสอบว่ามีวัคซีนที่เชื่อมโยงกับช่วงอายุนี้หรือไม่
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM vaccine_list 
        WHERE age_group_id = :id
    ");
    $stmt->execute(['id' => $data['id']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ไม่สามารถลบช่วงอายุนี้ได้ เนื่องจากมีวัคซีนที่เชื่อมโยงอยู่');
    }

    // ลบข้อมูลช่วงอายุ
    $stmt = $pdo->prepare("
        DELETE FROM vaccine_age_groups 
        WHERE id = :id
    ");
    
    $stmt->execute(['id' => $data['id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'ลบช่วงอายุสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 