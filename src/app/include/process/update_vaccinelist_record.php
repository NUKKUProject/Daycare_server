<?php
require_once(__DIR__ . '/../../../config/database.php');
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('ไม่พบรหัสวัคซีน');
    }

    $pdo = getDatabaseConnection();

    // ดึงข้อมูลเดิม
    $stmt = $pdo->prepare("SELECT * FROM vaccine_list WHERE id = ?");
    $stmt->execute([$data['id']]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldData) {
        throw new Exception('ไม่พบข้อมูลวัคซีน');
    }

    // ใช้ข้อมูลเดิมถ้าไม่มีการส่งข้อมูลใหม่มา
    $age_group_id = $data['age_group_id'] ?? $oldData['age_group_id'];
    $vaccine_name = $data['vaccine_name'] ?? $oldData['vaccine_name'];
    $vaccine_description = $data['vaccine_description'] ?? $oldData['vaccine_description'];

    // ตรวจสอบว่ามีวัคซีนซ้ำในกลุ่มอายุเดียวกันหรือไม่ (ยกเว้นตัวเอง)
    if ($age_group_id != $oldData['age_group_id'] || $vaccine_name != $oldData['vaccine_name']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM vaccine_list 
            WHERE age_group_id = ? 
            AND vaccine_name = ? 
            AND id != ? 
            AND is_active = true
        ");
        $stmt->execute([$age_group_id, $vaccine_name, $data['id']]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('มีวัคซีนนี้ในกลุ่มอายุนี้แล้ว');
        }
    }

    // อัพเดทข้อมูลในฐานข้อมูล
    $stmt = $pdo->prepare("
        UPDATE vaccine_list 
        SET age_group_id = :age_group_id,
            vaccine_name = :vaccine_name,
            vaccine_description = :vaccine_description,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

    $result = $stmt->execute([
        'id' => $data['id'],
        'age_group_id' => $age_group_id,
        'vaccine_name' => $vaccine_name,
        'vaccine_description' => $vaccine_description
    ]);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'อัพเดทข้อมูลสำเร็จ']);
    } else {
        throw new Exception('ไม่สามารถอัพเดทข้อมูลได้');
    }

} catch (Exception $e) {
    error_log("Error in update_vaccinelist_record: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?> 