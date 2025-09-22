<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ไม่พบรหัสวัคซีน');
    }

    $pdo = getDatabaseConnection();
    
    // ดึงข้อมูลวัคซีน
    $stmt = $pdo->prepare("
        SELECT v.*, vl.age_group_id, vag.age_group
        FROM vaccines v
        JOIN vaccine_list vl ON v.vaccine_list_id = vl.id
        JOIN vaccine_age_groups vag ON vl.age_group_id = vag.id
        WHERE v.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vaccine) {
        throw new Exception('ไม่พบข้อมูลวัคซีน');
    }

    // แปลง path รูปภาพให้เป็น URL ที่ถูกต้อง
    if ($vaccine['image_path']) {
        $vaccine['image_url'] = $vaccine['image_path'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $vaccine
    ]);

} catch (Exception $e) {
    error_log("Error in get_vaccine_detail: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 