<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    // ตรวจสอบข้อมูล
    if (empty($data['age_group']) || trim($data['age_group']) === '') {
        throw new Exception('กรุณาระบุช่วงอายุ');
    }

    // ตรวจสอบว่ามีช่วงอายุนี้อยู่แล้วหรือไม่
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccine_age_groups WHERE age_group = :age_group");
    $stmt->execute(['age_group' => trim($data['age_group'])]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ช่วงอายุนี้มีอยู่แล้วในระบบ');
    }

    // หา display_order ล่าสุด
    $stmt = $pdo->query("SELECT COALESCE(MAX(display_order), 0) + 1 FROM vaccine_age_groups");
    $nextDisplayOrder = $stmt->fetchColumn();

    // เพิ่มข้อมูลช่วงอายุใหม่
    $stmt = $pdo->prepare("
        INSERT INTO vaccine_age_groups (age_group, display_order) 
        VALUES (:age_group, :display_order)
    ");
    
    $stmt->execute([
        'age_group' => trim($data['age_group']),
        'display_order' => $nextDisplayOrder
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'เพิ่มช่วงอายุสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 