<?php
require_once(__DIR__ . '/../../../config/database.php');
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('ไม่พบข้อมูลที่ต้องการลบ');
    }

    $pdo = getDatabaseConnection();

    // ดึงข้อมูลรูปภาพก่อนลบ
    $stmt = $pdo->prepare("SELECT image_path FROM vaccines WHERE id = :id");
    $stmt->execute(['id' => $data['id']]);
    $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);

    // ลบข้อมูลจากฐานข้อมูล
    $stmt = $pdo->prepare("DELETE FROM vaccines WHERE id = :id");
    $result = $stmt->execute(['id' => $data['id']]);

    if ($result) {
        // ลบไฟล์รูปภาพ (ถ้ามี)
        if ($vaccine && $vaccine['image_path']) {
            $imagePath = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/' . 
                        str_replace('../../../public/', '', $vaccine['image_path']);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ']);
    } else {
        throw new Exception('ไม่สามารถลบข้อมูลได้');
    }

} catch (Exception $e) {
    error_log("Error in delete_vaccine_record: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?> 