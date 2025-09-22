<?php 
require_once __DIR__ . 'child_functions.php';
function getChildrenWithAttendance($group = null, $classroom = null, $date = null) {
    
    $pdo = getDatabaseConnection(); // เชื่อมต่อฐานข้อมูล
    // SQL Query
    $sql = "
        SELECT 
            c.id AS child_id,
            c.prefix_th,
            c.firstname_th AS child_firstname,
            c.lastname_th AS child_lastname,
            c.child_group,
            c.classroom,
            a.student_id,
            a.check_date,
            a.status,
            a.status_checkout
        FROM 
            children c
        LEFT JOIN 
            attendance a ON c.child_id = a.child_id
        WHERE 
            1=1
    ";

    // เพิ่มเงื่อนไขการกรองข้อมูล
    $params = [];
    if (!empty($group)) {
        $sql .= " AND c.child_group = ?";
        $params[] = $group;
    }

    if (!empty($classroom)) {
        $sql .= " AND c.classroom = ?";
        $params[] = $classroom;
    }

    if (!empty($date)) {
        $sql .= " AND a.check_date = ?";
        $params[] = $date;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>