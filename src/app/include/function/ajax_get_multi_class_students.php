<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../../config/database.php'); // ปรับ path ให้ถูกต้อง

try {
    $pdo = getDatabaseConnection();

    // อ่านข้อมูล JSON POST body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['academic_year']) || empty($input['academic_year'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่มีข้อมูลปีการศึกษาที่ส่งมา'
        ]);
        exit;
    }

    $academicYear = $input['academic_year'];
    $childGroup = isset($input['child_group']) ? $input['child_group'] : null;
    $classrooms = isset($input['classroom']) ? (is_array($input['classroom']) ? $input['classroom'] : [$input['classroom']]) : null;

    // สร้าง SQL query
    $sql = "
        SELECT 
            c.id,
            c.studentid,
            c.prefix_th,
            c.firstname_th,
            c.lastname_th,
            c.nickname,
            c.child_group,
            c.classroom,
            c.status,
            c.academic_year,
            ay.name as academic_year_name
        FROM children c
        INNER JOIN academic_years ay ON c.academic_year = ay.name::integer
        WHERE c.status = 'กำลังศึกษา'
          AND ay.name = :academic_year
    ";

    $params = [':academic_year' => $academicYear];

    // เพิ่มเงื่อนไขกรองตาม child_group ถ้ามี
    if ($childGroup) {
        $sql .= " AND c.child_group = :child_group";
        $params[':child_group'] = $childGroup;
    }

    // เพิ่มเงื่อนไขกรองตาม classroom ถ้ามี
    if ($classrooms && !empty($classrooms)) {
        $placeholders = [];
        foreach ($classrooms as $index => $classroom) {
            $paramName = ":classroom" . $index;
            $placeholders[] = $paramName;
            $params[$paramName] = $classroom;
        }
        $sql .= " AND c.classroom IN (" . implode(',', $placeholders) . ")";
    }

    $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $students
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
