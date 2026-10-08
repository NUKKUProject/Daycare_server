<?php
// รายชื่อเด็ก + ผลตรวจฟันที่ใช้ของรอบที่เลือก (ในรอบเดียวกัน ผลแพทย์มาก่อนผลครู)
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/tooth_exam_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();

    // ปีการศึกษาที่ตรวจ และรอบ (ไม่ส่งรอบมา = รอบล่าสุดของปีนั้น)
    $healthAcademicYear = $_GET['academic_year'] ?? (date('Y') + 543);
    $roundId = !empty($_GET['round_id']) ? (int) $_GET['round_id'] : null;
    if (!$roundId) {
        $rounds = tooth_get_rounds($pdo, (string) $healthAcademicYear);
        $roundId = $rounds ? (int) $rounds[0]['id'] : null;
    }

    $params = [':health_academic_year' => $healthAcademicYear];
    // ถ้าปีนี้ยังไม่มีรอบ จะไม่มีผลตรวจให้ join (เงื่อนไข false) ทุกคนเป็น "ยังไม่มีการบันทึก"
    $roundCond = $roundId ? ' AND x.round_id = :round_id' : ' AND FALSE';
    if ($roundId) {
        $params[':round_id'] = $roundId;
    }

    $sql = "SELECT h.id, h.academic_year, h.student_id AS health_student_id, h.doctor_name, h.updated_at,
       h.exam_type, h.round_id, h.examined_by,
       (SELECT COUNT(*) FROM health_tooth_external y WHERE y.student_id = c.studentid AND y.round_id = h.round_id AND y.exam_type = 'doctor') > 0 AS has_doctor,
       (SELECT COUNT(*) FROM health_tooth_external y WHERE y.student_id = c.studentid AND y.round_id = h.round_id AND y.exam_type = 'teacher') > 0 AS has_teacher,
       c.studentid AS student_id,
       c.prefix_th,
       c.firstname_th AS first_name_th,
       c.lastname_th AS last_name_th,
       c.nickname,
       c.child_group,
       c.classroom,
       c.academic_year AS academic_year,
       CASE WHEN h.id IS NOT NULL THEN 'recorded' ELSE 'not_recorded' END AS check_status
FROM children c
LEFT JOIN LATERAL (
    SELECT x.* FROM health_tooth_external x
    WHERE x.student_id = c.studentid AND x.academic_year = :health_academic_year" . $roundCond . "
    ORDER BY " . str_replace('exam_type', 'x.exam_type', TOOTH_TYPE_ORDER_SQL) . ", x.id DESC
    LIMIT 1
) h ON TRUE
WHERE 1=1 AND c.status = 'กำลังศึกษา'";

    // เงื่อนไขสำหรับ student_year
    if (!empty($_GET['student_year']) && $_GET['student_year'] !== 'all') {
        $sql .= " AND c.academic_year = :children_academic_year";
        $params[':children_academic_year'] = $_GET['student_year'];
    }

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

    // เงื่อนไขการค้นหา
    if (!empty($_GET['search'])) {
        $sql .= " AND (c.firstname_th LIKE :search OR c.lastname_th LIKE :search OR c.studentid LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
