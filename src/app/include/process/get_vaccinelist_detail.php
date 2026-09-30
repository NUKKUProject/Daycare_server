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

    // รองรับ vaccine_id สำหรับการแก้ไข (ถ้ามี)
    $vaccine_id = $id;

    // คำนวณ "ครั้งที่" ถัดไปให้อัตโนมัติ ถ้าส่ง student_id มาด้วย (กันลืมกรอกจนบันทึกไม่ได้ เพราะคอลัมน์นี้ NOT NULL)
    $next_vaccine_number = 1;
    if (!empty($_GET['student_id'])) {
        $numStmt = $pdo->prepare("
            SELECT COALESCE(MAX(vaccine_number), 0) + 1
            FROM vaccines
            WHERE vaccine_list_id = :vaccine_list_id AND student_id = :student_id
        ");
        $numStmt->execute([
            'vaccine_list_id' => $id,
            'student_id' => $_GET['student_id']
        ]);
        $next_vaccine_number = (int) $numStmt->fetchColumn();
    }

// ส่งข้อมูลกลับ
    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $vaccine['id'],
            'age_group_id' => $vaccine['age_group_id'],
            'age_group' => $vaccine['age_group'],
            'vaccine_name' => $vaccine['vaccine_name'],
            'vaccine_description' => $vaccine['vaccine_description'],
            'age_groups' => $age_groups,
            'vaccine_id' => $vaccine_id,
            'next_vaccine_number' => $next_vaccine_number
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