<?php
require_once(__DIR__ . '/../../../config/database.php');
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher', 'student'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        throw new Exception('ไม่พบรหัสประวัติวัคซีน');
    }

    $pdo = getDatabaseConnection();

    // ดึงข้อมูลรูปภาพ (และเจ้าของรายการ) ก่อนลบ
    $stmt = $pdo->prepare("SELECT image_path, student_id FROM vaccines WHERE id = ?");
    $stmt->execute([$data['id']]);
    $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);

    // ผู้ปกครอง (student) ลบได้เฉพาะรายการของเด็กตัวเอง
    if ($_SESSION['role'] === 'student') {
        if (!$vaccine || $vaccine['student_id'] !== ($_SESSION['username'] ?? '')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
            exit;
        }
    }

    // ลบข้อมูลจากฐานข้อมูล
    $stmt = $pdo->prepare("DELETE FROM vaccines WHERE id = ?");
    $result = $stmt->execute([$data['id']]);

    if ($result) {
        // ลบไฟล์รูปภาพ (ถ้ามี)
        if ($vaccine && $vaccine['image_path']) {
            $image_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/' . str_replace('../../../public/', '', $vaccine['image_path']);
            if (file_exists($image_path)) {
                unlink($image_path);
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