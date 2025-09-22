<?php
require_once(__DIR__ . '../../../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    $sql = "SELECT h.id, h.academic_year, h.student_id AS health_student_id, h.exam_date, h.doctor_name,
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
LEFT JOIN health_data_external h
    ON c.studentid = h.student_id 
    AND h.academic_year = :academic_year
WHERE c.academic_year = :academic_year::int ";

    $params = [];

    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($_GET['child_group'])) {
        $sql .= " AND c.child_group = :child_group";
        $params[':child_group'] = $_GET['child_group'];
    }

    if (!empty($_GET['classroom'])) {
        $sql .= " AND c.classroom = :classroom";
        $params[':classroom'] = $_GET['classroom'];
    }


    if (!empty($_GET['academic_year'])) {
        $params[':academic_year'] = $_GET['academic_year'];
    } else {
        // fallback กรณีไม่ได้ส่งปีการศึกษา (อาจใช้ปีปัจจุบันหรือค่า default)
        $params[':academic_year'] = date('Y') + 543; // สำหรับปี พ.ศ.
    }

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
