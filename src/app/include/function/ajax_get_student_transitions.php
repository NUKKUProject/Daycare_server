<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // รับพารามิเตอร์จาก POST
    $childGroup = $_POST['child_group'] ?? '';
    $classroom = $_POST['classroom'] ?? '';
    $academicYear = $_POST['academic_year'] ?? '';

    // Debug: แสดงค่าที่ได้รับ
    error_log("Received academic_year: " . $academicYear);

    // ตรวจสอบว่าเป็นปีการศึกษาในอนาคตหรือไม่
    if (!empty($academicYear)) {
        $currentYear = date('Y') + 543; // ปีปัจจุบันใน พ.ศ.
        $year1 = intval(explode('/', $academicYear)[0]);
        if ($year1 > $currentYear) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'message' => 'ไม่สามารถเลือกปีการศึกษาในอนาคตได้'
            ]);
            exit;
        }
    }

    // สร้าง query พื้นฐาน
    $sql = "
        SELECT DISTINCT
            c.id,
            c.studentid,
            c.prefix_th,
            c.firstname_th,
            c.lastname_th,
            c.nickname,
            c.child_group,
            c.classroom,
            c.status,
            c.academic_year as child_academic_year,
            t.id as transition_id,
            t.academic_year AS transition_academic_year,
            t.current_class_level,
            t.current_classroom,
            t.new_class_level,
            t.new_classroom,
            t.transition_type,
            t.effective_date,
            t.reason,
            COALESCE(ts.status_name, 'ยังไม่มีข้อมูล') as transition_status_text,
            ts.description as status_description
        FROM children c
        LEFT JOIN student_transitions t ON c.id = t.child_id
        LEFT JOIN transition_statuses ts ON t.status_id = ts.id
        WHERE c.status = 'กำลังศึกษา'
    ";

    $params = [];

    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($childGroup)) {
        $sql .= " AND c.child_group = :child_group";
        $params[':child_group'] = $childGroup;
    }

    if (!empty($classroom)) {
        $sql .= " AND c.classroom = :classroom";
        $params[':classroom'] = $classroom;
    }

    if (!empty($academicYear)) {
        // แปลงปีการศึกษาเป็นตัวเลขก่อนเปรียบเทียบ
        $year = intval(explode('/', $academicYear)[0]);
        error_log("Converted academic_year to integer: " . $year);
        
        // ใช้เงื่อนไขการเปรียบเทียบที่ชัดเจน
        $sql .= " AND c.academic_year = :academic_year";
        $params[':academic_year'] = $year;
    }

    // Debug: แสดง SQL query และ parameters
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r($params, true));

    // เพิ่มเงื่อนไขให้ดึงเฉพาะข้อมูลล่าสุดของแต่ละนักเรียน
    $sql .= " AND (t.id IS NULL OR t.id = (
        SELECT MAX(t2.id)
        FROM student_transitions t2
        WHERE t2.child_id = c.id
    ))";

    $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug: แสดงผลลัพธ์
        error_log("Query results count: " . count($results));
        if (count($results) > 0) {
            error_log("First result academic_year: " . $results[0]['child_academic_year']);
        }

        // ตรวจสอบว่ามีข้อมูลหรือไม่
        if (count($results) === 0) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'message' => 'ไม่พบข้อมูลนักเรียนในปีการศึกษา ' . $academicYear
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
} 