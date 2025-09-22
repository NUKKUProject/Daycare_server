<?php
require_once(__DIR__ . '/../../../config/database.php');

// รับข้อมูล JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['student_id']) || !isset($data['qr_code_path'])) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // เริ่ม transaction
    $pdo->beginTransaction();

    // ลบไฟล์ QR Code
    $qrCodePath = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/uploads/qrcodes/' . basename($data['qr_code_path']);
    if (file_exists($qrCodePath)) {
        unlink($qrCodePath);
    }

    // อัพเดทฐานข้อมูล
    $stmt = $pdo->prepare("UPDATE children SET qr_code = NULL WHERE studentid = :studentid");
    $stmt->execute(['studentid' => $data['student_id']]);

    // ยืนยัน transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'ลบ QR Code เรียบร้อยแล้ว'
    ]);

} catch (Exception $e) {
    // ถ้าเกิดข้อผิดพลาดให้ rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการลบ QR Code: ' . $e->getMessage()
    ]);
}
?> 