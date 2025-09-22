<?php
require_once(__DIR__ . '../../../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // รับ ID จาก URL
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ไม่พบ ID ที่ต้องการดู');
    }

    // ดึงข้อมูลรายละเอียดจากตาราง health_data
    $stmt = $pdo->prepare("SELECT * FROM health_tooth_external WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('ไม่พบข้อมูล');
    }

    // ส่งผลลัพธ์
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 