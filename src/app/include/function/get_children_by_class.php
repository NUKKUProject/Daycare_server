<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    // Debug: แสดงค่า request parameters
    error_log("Request parameters: " . print_r($_GET, true));

    // เช็คว่ามีพารามิเตอร์ที่จำเป็นครบไหม
    if (empty($_GET['teacher_id']) || empty($_GET['child_group']) || empty($_GET['classroom'])) {
        throw new Exception('Missing required parameters');
    }

    $pdo = getDatabaseConnection();
    
    // รับพารามิเตอร์
    $teacherId = $_GET['teacher_id'];
    $childGroup = $_GET['child_group'];
    $classroom = $_GET['classroom'];
    $gender = $_GET['gender'] ?? '';

    // Debug: แสดงค่าพารามิเตอร์ที่ได้
    error_log("Processed parameters: " . print_r([
        'teacherId' => $teacherId,
        'childGroup' => $childGroup,
        'classroom' => $classroom,
        'gender' => $gender
    ], true));

    // สร้าง query พื้นฐาน
    $sql = "SELECT DISTINCT ON (c.studentid) 
        c.studentid, 
        c.prefix_th, 
        c.firstname_th, 
        c.lastname_th,
        c.nickname, 
        c.child_group, 
        c.classroom, 
        t.group_ids AS group_name,
        t.classroom_ids AS classroom_name,
        t.first_name AS teacher_first_name, 
        t.last_name AS teacher_last_name,
        CASE 
            WHEN a.status IS NULL THEN 'ยังไม่มาเรียน'
            WHEN a.status = 'present' THEN 'มาเรียน'
            WHEN a.status = 'absent' THEN 'ไม่มาเรียน'
            ELSE a.status
        END AS status
    FROM children c
    LEFT JOIN teachers t 
        ON c.classroom = ANY(string_to_array(t.classroom_ids, ',')::text[])
        AND c.child_group = ANY(string_to_array(t.group_ids, ',')::text[])
    LEFT JOIN public.attendance a
        ON c.studentid = a.student_id
        AND DATE(a.check_date) = CURRENT_DATE
    WHERE t.teacher_id = :teacher_id
        AND c.child_group = :child_group
        AND c.classroom = :classroom
        AND (a.status IS NOT NULL AND a.status != 'absent')";

    // เพิ่มเงื่อนไขการกรองตามเพศ
    if ($gender) {
        $sql .= " AND c.sex = :sex";
    }

    // เพิ่ม ORDER BY
    $sql .= " ORDER BY c.studentid, t.teacher_id";

    // Debug: แสดง SQL และ parameters
    error_log("SQL Query: " . $sql);
    error_log("SQL Parameters: " . print_r([
        ':teacher_id' => $teacherId,
        ':child_group' => $childGroup,
        ':classroom' => $classroom
    ], true));

    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $params = [
        ':teacher_id' => $teacherId,
        ':child_group' => $childGroup,
        ':classroom' => $classroom
    ];

    if ($gender) {
        $params[':sex'] = $gender;
    }

    $stmt->execute($params);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: แสดงจำนวนข้อมูลที่ได้
    error_log("Found " . count($children) . " children");

    echo json_encode([
        'status' => 'success',
        'children' => $children,
        'debug' => [
            'sql' => $sql,
            'params' => $params,
            'count' => count($children)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_children_by_class.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 