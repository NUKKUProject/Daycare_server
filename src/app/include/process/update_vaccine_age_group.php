<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id']) || empty($data['age_group'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    // ตรวจสอบว่ามีช่วงอายุนี้อยู่แล้วหรือไม่ (ยกเว้นรายการปัจจุบัน)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM vaccine_age_groups 
        WHERE age_group = :age_group 
        AND id != :id
    ");
    $stmt->execute([
        'age_group' => $data['age_group'],
        'id' => $data['id']
    ]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ช่วงอายุนี้มีอยู่แล้วในระบบ');
    }

    // อัพเดทข้อมูลช่วงอายุ
    $stmt = $pdo->prepare("
        UPDATE vaccine_age_groups 
        SET age_group = :age_group,
            display_order = :display_order
        WHERE id = :id
    ");
    
    $stmt->execute([
        'id' => $data['id'],
        'age_group' => $data['age_group'],
        'display_order' => $data['display_order']
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'อัพเดทช่วงอายุสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 