<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Missing required parameter: id');
    }

    $pdo = getDatabaseConnection();

    // ดึงข้อมูลนักเรียนและข้อมูลอาหาร
    $sql = "SELECT 
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
                nr.meal_type,
                nr.meal_status,
                nr.nutrition_note,
                nr.recorded_by,
                TO_CHAR(nr.recorded_at, 'DD/MM/YYYY') as recorded_date,
                TO_CHAR(nr.recorded_at, 'HH24:MI') as recorded_time,
                t.first_name as staff_firstname,
                t.last_name as staff_lastname
            FROM nutrition_records nr
            JOIN children c ON nr.student_id = c.studentid
            LEFT JOIN teachers t ON nr.recorded_by = t.teacher_id
            WHERE nr.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception('Record not found');
    }

    // จัดรูปแบบข้อมูลสำหรับส่งกลับ
    $response = [
        'status' => 'success',
        'data' => [
            'student' => [
                'id' => $record['studentid'],
                'name' => $record['prefix_th'] . $record['firstname_th'] . ' ' . $record['lastname_th'],
                'nickname' => $record['nickname'],
                'child_group' => $record['child_group'],
                'classroom' => $record['classroom']
            ],
            'nutrition' => [
                'id' => $record['id'],
                'weight' => $record['weight'],
                'height' => $record['height'],
                'meal_type' => $record['meal_type'],
                'meal_status' => $record['meal_status'],
                'note' => $record['nutrition_note']
            ],
            'record_info' => [
                'date' => $record['recorded_date'],
                'time' => $record['recorded_time'],
                'recorded_by' => $record['recorded_by'],
                'staff_firstname' => $record['staff_firstname'],
                'staff_lastname' => $record['staff_lastname']
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 