<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();

    // สร้าง subquery สำหรับดึงข้อมูลล่าสุดของแต่ละนักเรียน
    $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    
    $sql = "WITH latest_records AS (
        SELECT 
            c.studentid,
            c.prefix_th,
            c.firstname_th,
            c.lastname_th,
            c.nickname,
            c.child_group,
            c.classroom,
            nr.id,
            nr.weight,
            nr.height,
            MAX(CASE WHEN nr.meal_type = 'breakfast' THEN nr.meal_status END) as breakfast,
            MAX(CASE WHEN nr.meal_type = 'morning_snack' THEN nr.meal_status END) as morning_snack,
            MAX(CASE WHEN nr.meal_type = 'lunch' THEN nr.meal_status END) as lunch,
            MAX(CASE WHEN nr.meal_type = 'afternoon_snack' THEN nr.meal_status END) as afternoon_snack,
            STRING_AGG(DISTINCT NULLIF(nr.nutrition_note, ''), ', ') as note
        FROM children c
        LEFT JOIN nutrition_records nr ON c.studentid = nr.student_id 
            AND DATE(nr.recorded_at) = :date
        WHERE 1=1";

    $params = [':date' => $date];

    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($_POST['child_group'])) {
        $sql .= " AND c.child_group = :child_group";
        $params[':child_group'] = $_POST['child_group'];
    }

    if (!empty($_POST['classroom'])) {
        $sql .= " AND c.classroom = :classroom";
        $params[':classroom'] = $_POST['classroom'];
    }

    if (!empty($_POST['search'])) {
        $sql .= " AND (
            c.firstname_th ILIKE :search 
            OR c.lastname_th ILIKE :search 
            OR c.nickname ILIKE :search
        )";
        $params[':search'] = '%' . $_POST['search'] . '%';
    }

    $sql .= " GROUP BY 
        c.studentid,
        c.prefix_th,
        c.firstname_th,
        c.lastname_th,
        c.nickname,
        c.child_group,
        c.classroom,
        nr.id,
        nr.weight,
        nr.height
    ORDER BY c.child_group, c.classroom, c.firstname_th
    ) SELECT * FROM latest_records";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>