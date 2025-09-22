<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    // ตรวจสอบว่ามี id ถูกส่งมาหรือไม่
    if (!isset($_GET['id'])) {
        throw new Exception('ไม่พบรหัสวัคซีน');
    }

    $id = $_GET['id'];
    $pdo = getDatabaseConnection();

    // ดึงข้อมูลวัคซีน
    $stmt = $pdo->prepare("
        SELECT vl.*, vag.age_group 
        FROM vaccine_list vl
        JOIN vaccine_age_groups vag ON vl.age_group_id = vag.id
        WHERE vl.id = ? AND vl.is_active = true
    ");
    $stmt->execute([$id]);
    $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vaccine) {
        throw new Exception('ไม่พบข้อมูลวัคซีน');
    }

    // ดึงข้อมูลกลุ่มอายุทั้งหมด
    $stmt = $pdo->query("
        SELECT * 
        FROM vaccine_age_groups 
        ORDER BY display_order
    ");
    $age_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งข้อมูลกลับ
    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $vaccine['id'],
            'age_group_id' => $vaccine['age_group_id'],
            'age_group' => $vaccine['age_group'],
            'vaccine_name' => $vaccine['vaccine_name'],
            'vaccine_description' => $vaccine['vaccine_description'],
            'age_groups' => $age_groups
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_vaccinelist_detail: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 