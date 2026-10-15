<?php
// ประวัติการบันทึกการเจริญเติบโตทุกครั้ง (ตามห้อง/คำค้นหา และช่วงวันที่) สำหรับหน้า growth_history
// ครูเห็นเฉพาะเด็กในห้องที่ดูแล ผู้ปกครองเห็นเฉพาะลูกตัวเอง
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/dev_screening_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher', 'student']);

const GROWTH_HISTORY_LIMIT = 1000;

try {
    $pdo = getDatabaseConnection();
    $childGroup = $_GET['child_group'] ?? '';
    $classroom = $_GET['classroom'] ?? '';
    $search = trim($_GET['search'] ?? '');
    $from = $_GET['date_from'] ?? '';
    $to = $_GET['date_to'] ?? '';

    $isStudent = getUserRole() === 'student';
    if (!$isStudent && $childGroup === '' && $classroom === '' && $search === '') {
        throw new Exception('กรุณาเลือกกลุ่มเรียน ห้องเรียน หรือค้นหาชื่อเด็ก');
    }
    foreach ([$from, $to] as $d) {
        if ($d !== '' && !DateTime::createFromFormat('Y-m-d', $d)) {
            throw new Exception('รูปแบบวันที่ไม่ถูกต้อง');
        }
    }

    $sql = "SELECT g.id, g.student_id, DATE(g.created_at) AS record_date, g.created_at,
                   g.age_year, g.age_month, g.age_day, g.weight, g.height, g.head_circumference,
                   g.gm_status, g.fm_status, g.rl_status, g.el_status, g.ps_status,
                   g.dev_result, g.dev_recorded_by,
                   c.prefix_th, c.firstname_th, c.lastname_th, c.nickname, c.child_group, c.classroom
            FROM growth_records g
            JOIN children c ON c.studentid = g.student_id
            WHERE g.is_draft = false";
    $params = [];

    if ($isStudent) {
        $sql .= " AND g.student_id = :own";
        $params[':own'] = $_SESSION['username'] ?? '';
    } else {
        if (getUserRole() === 'teacher') {
            $sql .= devScreeningTeacherScopeSql();
            $params[':teacher_id'] = $_SESSION['user_id'];
        }
        if ($childGroup !== '') {
            $sql .= " AND c.child_group = :child_group";
            $params[':child_group'] = $childGroup;
        }
        if ($classroom !== '') {
            $sql .= " AND c.classroom = :classroom";
            $params[':classroom'] = $classroom;
        }
        if ($search !== '') {
            $sql .= " AND (c.firstname_th ILIKE :search OR c.lastname_th ILIKE :search
                           OR c.nickname ILIKE :search OR c.studentid ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
    }
    if ($from !== '') {
        $sql .= " AND DATE(g.created_at) >= :from";
        $params[':from'] = $from;
    }
    if ($to !== '') {
        $sql .= " AND DATE(g.created_at) <= :to";
        $params[':to'] = $to;
    }
    $sql .= " ORDER BY g.created_at DESC, g.id DESC LIMIT " . (GROWTH_HISTORY_LIMIT + 1);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $truncated = count($rows) > GROWTH_HISTORY_LIMIT;
    if ($truncated) {
        array_pop($rows);
    }

    echo json_encode(['status' => 'success', 'rows' => $rows, 'truncated' => $truncated, 'limit' => GROWTH_HISTORY_LIMIT]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
