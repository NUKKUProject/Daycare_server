<?php
// รายชื่อเด็ก + ผลตรวจของรอบที่เลือก แยกตามผู้ตรวจ (ครู / แพทย์ / ข้อมูลเดิม) สำหรับหน้ากรอกทั้งห้องแบบตาราง
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/tooth_exam_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();

    $roundId = (int) ($_GET['round_id'] ?? 0);
    $group = trim($_GET['child_group'] ?? '');
    $classroom = trim($_GET['classroom'] ?? '');
    $studentYear = trim($_GET['student_year'] ?? '');

    $round = $roundId ? tooth_get_round($pdo, $roundId) : null;
    if (!$round) {
        throw new Exception('กรุณาเลือกรอบตรวจ');
    }
    // กันดึงทั้งศูนย์โดยไม่ตั้งใจ ต้องเลือกกลุ่มหรือห้องอย่างน้อยหนึ่งอย่าง
    if ($group === '' && $classroom === '') {
        throw new Exception('กรุณาเลือกกลุ่มเรียนหรือห้องเรียน');
    }

    $sql = "SELECT c.studentid, c.prefix_th, c.firstname_th, c.lastname_th, c.nickname,
                   c.child_group, c.classroom, c.birthday
            FROM children c
            WHERE c.status = 'กำลังศึกษา'";
    $params = [];
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
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ผลตรวจทุกประเภทของเด็กกลุ่มนี้ในรอบนี้ (เรียงเก่า -> ใหม่ ตัวหลังทับตัวก่อน = ใช้แถวล่าสุดของแต่ละประเภท)
    $records = [];
    if ($children) {
        $ids = array_column($children, 'studentid');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $recStmt = $pdo->prepare("SELECT id, student_id, exam_type, total_teeth, decayed_teeth, oral_components, teeth_status,
                   missing_teeth_detail, decayed_teeth_positions::text AS decayed_teeth_positions,
                   treatments::text AS treatments, other_treatment_detail, urgency, doctor_name, examined_by, updated_at
            FROM health_tooth_external
            WHERE round_id = ? AND student_id IN ($in)
            ORDER BY id");
        $recStmt->execute(array_merge([$roundId], $ids));
        $decode = function ($v, $default) {
            if ($v === null || $v === '') {
                return $default;
            }
            $d = json_decode($v, true);
            return is_array($d) ? $d : $default;
        };
        foreach ($recStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $r['decayed_teeth_positions'] = $decode($r['decayed_teeth_positions'], null);
            $r['treatments'] = $decode($r['treatments'], []);
            $records[$r['student_id']][$r['exam_type']] = $r;
        }
    }
    foreach ($children as &$c) {
        $c['records'] = $records[$c['studentid']] ?? new stdClass();
    }
    unset($c);

    echo json_encode([
        'status' => 'success',
        'round' => $round,
        'allowed_types' => tooth_allowed_types($_SESSION['role'] ?? ''),
        'data' => $children,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
