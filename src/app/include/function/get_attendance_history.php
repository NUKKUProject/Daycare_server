<?php
require_once(__DIR__ . '/../../../config/database.php');


try {
    $pdo = getDatabaseConnection();

    $sql = "SELECT a.id, a.*, 
       c.studentid as student_id, 
       c.prefix_th, c.firstname_th, c.lastname_th, c.nickname,
       c.child_group, c.classroom, a.is_recorded,
       COALESCE(CASE 
            WHEN a.status_checkout = 'checked_out' THEN 'กลับแล้ว'
            WHEN a.status_checkout = 'no_checked_out' THEN 'ยังไม่กลับ'
            ELSE NULL 
        END, 'ยังไม่มีข้อมูล') as status_checkout_th,
       COALESCE(CASE 
            WHEN a.status = 'present' THEN 'มาเรียน'
            WHEN a.status = 'absent' THEN 'ไม่มาเรียน'
            WHEN a.status = 'leave' THEN 'ลา'
            ELSE 'ยังไม่บันทึก'
        END, 'ยังไม่บันทึก') as status_th,
       a.check_date as check_date,
       a.check_out_time as check_out_time
FROM children c
LEFT JOIN (
    SELECT * FROM attendance 
    WHERE DATE(check_date) = :date
) a ON c.studentid = a.student_id
WHERE 1=1

";


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

    if (!empty($_GET['date'])) {
        $params[':date'] = $_GET['date'];
    } else {
        $params[':date'] = date('Y-m-d');
    }

    if (!empty($_GET['student_id'])) {
        $sql .= " AND c.studentid = :student_id";
        $params[':student_id'] = $_GET['student_id'];
    }

    if (!empty($_GET['search'])) {
        $sql .= " AND (c.firstname_th LIKE :search OR c.lastname_th LIKE :search OR c.studentid LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
