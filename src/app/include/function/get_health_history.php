<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    $sql = "SELECT h.id, h.*, 
           c.studentid as student_id, 
           c.prefix_th, c.firstname_th as first_name_th, c.lastname_th as last_name_th,
           c.nickname,
           c.child_group, c.classroom,
           CASE 
                WHEN h.id IS NOT NULL THEN 'recorded'
                ELSE 'not_recorded'
           END as check_status,
           h.created_at as check_date,
           h.teacher_signature
    FROM children c
    LEFT JOIN (
        SELECT * FROM health_data 
        WHERE DATE(created_at) = :date
    ) h ON c.studentid = h.student_id
    WHERE 1=1";

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

    if (!empty($_GET['search'])) {
        $sql .= " AND (c.firstname_th LIKE :search OR c.lastname_th LIKE :search OR c.studentid LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลงข้อมูล JSON fields สำหรับข้อมูลที่มีการบันทึกแล้ว
    foreach ($results as &$result) {
        if ($result['check_status'] === 'recorded') {
            $jsonFields = ['hair', 'eye', 'mouth', 'teeth', 'ears', 'nose', 'nails', 
                          'skin', 'hands_feet', 'arms_legs', 'body', 'symptoms', 'medicine'];
            
            foreach ($jsonFields as $field) {
                if (isset($result[$field])) {
                    $result[$field] = json_decode($result[$field], true);
                }
            }
        }
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 