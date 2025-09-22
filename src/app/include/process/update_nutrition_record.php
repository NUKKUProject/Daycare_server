<?php
require_once(__DIR__ . '/../../../config/database.php');
require_once(__DIR__ . '/../auth/auth.php');

header('Content-Type: application/json');

// ตรวจสอบสิทธิ์การเข้าถึง
if (getUserRole() !== 'admin' && getUserRole() !== 'teacher') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่มีสิทธิ์ในการแก้ไขข้อมูล'
    ]);
    exit;
}

try {
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($data['id']) || !isset($data['weight']) || !isset($data['height']) || 
        !isset($data['meal_type']) || !isset($data['meal_status'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    $pdo = getDatabaseConnection();

    // เริ่ม Transaction
    $pdo->beginTransaction();

    try {
        // อัพเดทข้อมูลในตาราง nutrition_records
        $sql = "UPDATE nutrition_records 
                SET weight = :weight,
                    height = :height,
                    meal_type = :meal_type,
                    meal_status = :meal_status,
                    nutrition_note = :note
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $data['id'],
            ':weight' => $data['weight'],
            ':height' => $data['height'],
            ':meal_type' => $data['meal_type'],
            ':meal_status' => $data['meal_status'],
            ':note' => $data['note'] ?? null
        ]);

        // ตรวจสอบว่ามีการอัพเดทข้อมูลจริงหรือไม่
        if ($stmt->rowCount() === 0) {
            throw new Exception('ไม่พบข้อมูลที่ต้องการแก้ไข');
        }

        // Commit Transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'อัพเดทข้อมูลสำเร็จ'
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