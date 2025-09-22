<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    // รับข้อมูล JSON จาก request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // ตรวจสอบว่ามี id หรือไม่
    if (!isset($data['id'])) {
        throw new Exception('ไม่พบรหัสวัคซีน');
    }

    $pdo = getDatabaseConnection();
    
    // ตรวจสอบว่ามีการใช้งานวัคซีนนี้ในประวัติการฉีดหรือไม่
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM vaccines 
        WHERE vaccine_list_id = ?
    ");
    $stmt->execute([$data['id']]);
    
    if ($stmt->fetchColumn() > 0) {
        // ถ้ามีการใช้งาน ให้ทำ soft delete
        $stmt = $pdo->prepare("
            UPDATE vaccine_list 
            SET is_active = false,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
    } else {
        // ถ้าไม่มีการใช้งาน สามารถลบออกจากฐานข้อมูลได้เลย
        $stmt = $pdo->prepare("
            DELETE FROM vaccine_list 
            WHERE id = ?
        ");
    }

    $result = $stmt->execute([$data['id']]);

    if (!$result) {
        throw new Exception('ไม่สามารถลบข้อมูลได้');
    }

    // ส่งผลลัพธ์กลับ
    echo json_encode([
        'status' => 'success',
        'message' => 'ลบข้อมูลวัคซีนสำเร็จ'
    ]);

} catch (Exception $e) {
    error_log("Error in delete_vaccinelist_record: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 