<?php
// รายชื่อเด็กพร้อมผลตรวจฟันเดิมของปีการศึกษานั้น (ถ้ามี) สำหรับหน้ากรอกทั้งห้องแบบตาราง
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();

    $examYear = trim($_GET['academic_year'] ?? '');
    $group = trim($_GET['child_group'] ?? '');
    $classroom = trim($_GET['classroom'] ?? '');
    $studentYear = trim($_GET['student_year'] ?? '');

    if ($examYear === '') {
        throw new Exception('กรุณาเลือกปีการศึกษาที่ตรวจ');
    }
    // กันดึงทั้งศูนย์โดยไม่ตั้งใจ ต้องเลือกกลุ่มหรือห้องอย่างน้อยหนึ่งอย่าง
    if ($group === '' && $classroom === '') {
        throw new Exception('กรุณาเลือกกลุ่มเรียนหรือห้องเรียน');
    }

    $sql = "
        SELECT c.studentid, c.prefix_th, c.firstname_th, c.lastname_th, c.nickname,
               c.child_group, c.classroom, c.birthday,
               h.id AS record_id, h.total_teeth, h.decayed_teeth, h.oral_components, h.teeth_status,
               h.missing_teeth_detail, h.decayed_teeth_positions::text AS decayed_teeth_positions,
               h.treatments::text AS treatments, h.other_treatment_detail, h.urgency, h.doctor_name
        FROM children c
        LEFT JOIN LATERAL (
            SELECT * FROM health_tooth_external
            WHERE student_id = c.studentid AND academic_year = :exam_year
            ORDER BY id DESC LIMIT 1
        ) h ON TRUE
        WHERE c.status = 'กำลังศึกษา'";
    $params = [':exam_year' => $examYear];

    if ($studentYear !== '' && $studentYear !== 'all') {
        $sql .= ' AND c.academic_year = :student_year';
        $params[':student_year'] = $studentYear;
    }
    if ($group !== '') {
        $sql .= ' AND c.child_group = :grp';
        $params[':grp'] = $group;
    }
    if ($classroom !== '') {
        $sql .= ' AND c.classroom = :room';
        $params[':room'] = $classroom;
    }
    $sql .= ' ORDER BY c.child_group, c.classroom, c.firstname_th, c.lastname_th';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $decode = function ($v, $default) {
        if ($v === null || $v === '') {
            return $default;
        }
        $d = json_decode($v, true);
        return is_array($d) ? $d : $default;
    };
    foreach ($rows as &$r) {
        $r['decayed_teeth_positions'] = $decode($r['decayed_teeth_positions'], null);
        $r['treatments'] = $decode($r['treatments'], []);
    }
    unset($r);

    echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
