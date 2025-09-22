<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    if (!isset($_POST['record_id'])) {
        throw new Exception('Missing record ID');
    }

    $pdo = getDatabaseConnection();
    $recordId = $_POST['record_id'];

    // ตรวจสอบว่ามีข้อมูลอยู่จริง
    $checkSql = "SELECT id FROM growth_records WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$recordId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Record not found');
    }

    // ลบข้อมูล
    $sql = "DELETE FROM growth_records WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$recordId]);

    if (!$success) {
        throw new Exception('Failed to delete record');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'ลบข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 