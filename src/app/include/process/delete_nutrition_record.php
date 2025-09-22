<?php
require_once(__DIR__ . '/../../../config/database.php');
require_once(__DIR__ . '/../auth/auth.php');

header('Content-Type: application/json');

// ตรวจสอบสิทธิ์การเข้าถึง
if (getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่มีสิทธิ์ในการลบข้อมูล'
    ]);
    exit;
}

try {
    // ตรวจสอบว่ามี ID ที่ต้องการลบหรือไม่
    if (!isset($_GET['id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการลบ');
    }

    $id = $_GET['id'];
    $pdo = getDatabaseConnection();

    // เริ่ม Transaction
    $pdo->beginTransaction();

    try {
        // ลบข้อมูลจากตาราง nutrition_records
        $sql = "DELETE FROM nutrition_records WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        // ตรวจสอบว่ามีการลบข้อมูลจริงหรือไม่
        if ($stmt->rowCount() === 0) {
            throw new Exception('ไม่พบข้อมูลที่ต้องการลบ');
        }

        // Commit Transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'ลบข้อมูลสำเร็จ'
        ]);

    } catch (Exception $e) {
        // Rollback กรณีเกิดข้อผิดพลาด
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 