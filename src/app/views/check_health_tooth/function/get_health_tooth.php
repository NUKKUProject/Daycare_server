<?php
require_once(__DIR__ . '../../../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    $sql = "SELECT h.id, h.academic_year, h.student_id AS health_student_id, h.doctor_name,h.updated_at,
       c.studentid AS student_id, 
       c.prefix_th, 
       c.firstname_th AS first_name_th, 
       c.lastname_th AS last_name_th,
       c.nickname,
       c.child_group, 
       c.classroom,
       c.academic_year AS academic_year,
       CASE 
            WHEN h.id IS NOT NULL THEN 'recorded'
            ELSE 'not_recorded'
       END AS check_status
FROM children c
LEFT JOIN health_tooth_external h
    ON c.studentid = h.student_id 
    AND h.academic_year = :health_academic_year
WHERE 1=1 ";

    $params = [];

    // กำหนดค่าเริ่มต้นสำหรับปีการศึกษา
    $healthAcademicYear = $_GET['academic_year'] ?? (date('Y') + 543);
    $params[':health_academic_year'] = $healthAcademicYear;

    // เงื่อนไขสำหรับ student_year
    if (!empty($_GET['student_year']) && $_GET['student_year'] !== 'all') {
        $sql .= " AND c.academic_year = :children_academic_year";
        $params[':children_academic_year'] = $_GET['student_year'];
    }
    // ถ้าส่ง 'all' มาหรือไม่ส่งมา จะไม่กรองปีการศึกษา (แสดงทั้งหมด)

    // เงื่อนไขสำหรับ child_group
    if (!empty($_GET['child_group'])) {
        $sql .= " AND c.child_group = :child_group";
        $params[':child_group'] = $_GET['child_group'];
    }

    // เงื่อนไขสำหรับ classroom - ถ้าไม่เลือกจะแสดงทุกห้องในกลุ่มนั้น
    if (!empty($_GET['classroom'])) {
        $sql .= " AND c.classroom = :classroom";
        $params[':classroom'] = $_GET['classroom'];
    }
    // ถ้าไม่ได้เลือก classroom จะไม่เพิ่มเงื่อนไข = แสดงทุกห้องในกลุ่ม

    // เงื่อนไขการค้นหา
    if (!empty($_GET['search'])) {
        $sql .= " AND (c.firstname_th LIKE :search OR c.lastname_th LIKE :search OR c.studentid LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
