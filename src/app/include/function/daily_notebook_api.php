<?php
// API สมุดสื่อสารประจำวัน (ผู้ปกครอง <-> ครู)
//
//   GET  ?action=classrooms                                  รายชื่อห้องเรียน (staff)
//   GET  ?action=menus&date=YYYY-MM-DD[&classroom=]          เมนูอาหารของวันนั้น (staff)
//   GET  ?action=menu_suggest                                เมนูที่เคยกรอก ใช้เป็นตัวช่วยพิมพ์ (staff)
//   POST {action: save_menus, date, classrooms[], menus{morning_snack,lunch,afternoon_snack}}   (staff)
//   GET  ?action=roster&date=&child_group=&classroom=        รายชื่อเด็ก + สถานะการกรอกสมุด (staff)
//   GET  ?action=report&student_id=&date=                    สมุดของเด็ก 1 คน (staff / ผู้ปกครองเฉพาะลูกตัวเอง)
//   POST {action: save_report, student_id, date, side: teacher|parent, ...fields}
//        staff บันทึกได้ทั้งสองฝั่ง  ผู้ปกครองบันทึกได้เฉพาะฝั่ง parent ของลูกตัวเองและเฉพาะวันนี้

require_once(__DIR__ . '/../../../config/database.php');

date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const NB_MOODS = ['happy', 'scared', 'cry', 'angry', 'normal'];
const NB_MEAL_SLOTS = ['morning_snack', 'lunch', 'afternoon_snack'];

// ชนิดของแต่ละฟิลด์ที่รับได้ในแต่ละฝั่ง
const NB_PARENT_FIELDS = [
    'parent_mood' => 'mood',
    'parent_message' => 'text',
    'drop_off_time' => 'time',
    'home_morning_milk_ml' => 'int',
    'home_morning_food' => 'text',
    'home_evening_milk_ml' => 'int',
    'home_evening_food' => 'text',
    'home_sleep_hours' => 'decimal',
    'home_bedtime' => 'time',
    'home_wake_time' => 'time',
    'home_stopped_diaper' => 'bool',
    'home_stopped_bottle' => 'bool',
];

const NB_TEACHER_FIELDS = [
    'teacher_mood' => 'mood',
    'teacher_message' => 'text',
    'center_morning_milk_ml' => 'int',
    'center_morning_snack_amount' => 'text',
    'center_lunch_amount' => 'text',
    'center_afternoon_milk_ml' => 'int',
    'center_afternoon_snack_amount' => 'text',
    'center_nap_hours' => 'decimal',
    'center_urine_count' => 'int',
    'center_stool_count' => 'int',
    'center_stopped_diaper' => 'bool',
    'center_stopped_bottle' => 'bool',
    'activities' => 'text',
];

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $message, int $code = 400): void
{
    respond(['status' => 'error', 'message' => $message], $code);
}

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'teacher', 'student'], true)) {
    fail('กรุณาเข้าสู่ระบบ', 401);
}
$isStaff = in_array($role, ['admin', 'teacher'], true);

function require_staff(bool $isStaff): void
{
    if (!$isStaff) {
        fail('ไม่มีสิทธิ์ในการดำเนินการ', 403);
    }
}

function valid_date($d): bool
{
    if (!is_string($d) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) {
        return false;
    }
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

function clean_value(string $type, $raw)
{
    switch ($type) {
        case 'mood':
            return in_array($raw, NB_MOODS, true) ? $raw : null;
        case 'text':
            $t = mb_substr(trim((string)($raw ?? '')), 0, 2000);
            return $t === '' ? null : $t;
        case 'time':
            return is_string($raw) && preg_match('/^([01]\d|2[0-3]):[0-5]\d/', $raw) ? substr($raw, 0, 5) : null;
        case 'int':
            return is_numeric($raw) ? (string)max(0, min(5000, (int)round((float)$raw))) : null;
        case 'decimal':
            return is_numeric($raw) ? (string)max(0, min(24, round((float)$raw, 1))) : null;
        case 'bool':
            return !empty($raw) && $raw !== 'false' && $raw !== '0' ? 't' : 'f';
    }
    return null;
}

$pdo = getDatabaseConnection();

$ready = in_array(
    $pdo->query("SELECT to_regclass('public.daily_reports') IS NOT NULL AND to_regclass('public.daily_menus') IS NOT NULL")->fetchColumn(),
    [true, 't', '1', 1],
    true
);
if (!$ready) {
    respond([
        'status' => 'needs_migration',
        'message' => 'ยังไม่ได้รัน migrations/010_add_daily_notebook.sql ในฐานข้อมูล',
    ]);
}

function menus_for(PDO $pdo, string $date, array $classrooms): array
{
    if (!$classrooms) {
        return [];
    }
    $in = implode(',', array_fill(0, count($classrooms), '?'));
    $stmt = $pdo->prepare("SELECT classroom, meal_slot, menu_text FROM daily_menus WHERE menu_date = ? AND classroom IN ($in)");
    $stmt->execute(array_merge([$date], array_values($classrooms)));
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[$row['classroom']][$row['meal_slot']] = $row['menu_text'];
    }
    return $map;
}

try {
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    }
    $action = $_GET['action'] ?? $input['action'] ?? '';

    switch ($action) {
        case 'classrooms':
            require_staff($isStaff);
            $rows = $pdo->query("SELECT classroom_name, child_group FROM classrooms WHERE status = 'active' ORDER BY child_group, classroom_name")->fetchAll(PDO::FETCH_ASSOC);
            respond(['status' => 'success', 'data' => $rows]);

        case 'menus':
            require_staff($isStaff);
            $date = $_GET['date'] ?? '';
            if (!valid_date($date)) {
                fail('วันที่ไม่ถูกต้อง');
            }
            $sql = "SELECT classroom, meal_slot, menu_text FROM daily_menus WHERE menu_date = :d";
            $params = [':d' => $date];
            if (!empty($_GET['classroom'])) {
                $sql .= " AND classroom = :c";
                $params[':c'] = $_GET['classroom'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $map = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[$row['classroom']][$row['meal_slot']] = $row['menu_text'];
            }
            respond(['status' => 'success', 'data' => $map]);

        case 'menu_suggest':
            require_staff($isStaff);
            $rows = $pdo->query("
                SELECT meal_slot, menu_text, COUNT(*) AS c
                FROM daily_menus
                WHERE menu_date >= CURRENT_DATE - INTERVAL '180 days'
                GROUP BY meal_slot, menu_text
                ORDER BY c DESC, menu_text
            ")->fetchAll(PDO::FETCH_ASSOC);
            $out = ['morning_snack' => [], 'lunch' => [], 'afternoon_snack' => []];
            foreach ($rows as $r) {
                if (count($out[$r['meal_slot']]) < 30) {
                    $out[$r['meal_slot']][] = $r['menu_text'];
                }
            }
            respond(['status' => 'success', 'data' => $out]);

        case 'save_menus':
            require_staff($isStaff);
            $date = $input['date'] ?? '';
            $classrooms = array_values(array_unique(array_filter((array)($input['classrooms'] ?? []), 'is_string')));
            $menus = (array)($input['menus'] ?? []);
            if (!valid_date($date)) {
                fail('วันที่ไม่ถูกต้อง');
            }
            if (!$classrooms) {
                fail('กรุณาเลือกห้องเรียนอย่างน้อย 1 ห้อง');
            }

            // รับเฉพาะห้องที่มีอยู่จริง
            $in = implode(',', array_fill(0, count($classrooms), '?'));
            $exists = $pdo->prepare("SELECT classroom_name FROM classrooms WHERE classroom_name IN ($in)");
            $exists->execute($classrooms);
            $valid = $exists->fetchAll(PDO::FETCH_COLUMN);
            if (!$valid) {
                fail('ไม่พบห้องเรียนที่เลือก');
            }

            $upsert = $pdo->prepare("
                INSERT INTO daily_menus (menu_date, classroom, meal_slot, menu_text, created_by)
                VALUES (:d, :c, :s, :t, :u)
                ON CONFLICT (menu_date, classroom, meal_slot)
                DO UPDATE SET menu_text = EXCLUDED.menu_text, updated_at = NOW(), created_by = EXCLUDED.created_by
            ");
            $delete = $pdo->prepare("DELETE FROM daily_menus WHERE menu_date = :d AND classroom = :c AND meal_slot = :s");

            $pdo->beginTransaction();
            foreach ($valid as $room) {
                foreach (NB_MEAL_SLOTS as $slot) {
                    $text = mb_substr(trim((string)($menus[$slot] ?? '')), 0, 255);
                    if ($text === '') {
                        $delete->execute([':d' => $date, ':c' => $room, ':s' => $slot]);
                    } else {
                        $upsert->execute([':d' => $date, ':c' => $room, ':s' => $slot, ':t' => $text, ':u' => $username]);
                    }
                }
            }
            $pdo->commit();
            respond(['status' => 'success', 'message' => 'บันทึกเมนูอาหารแล้ว (' . count($valid) . ' ห้อง)']);

        case 'roster':
            require_staff($isStaff);
            $date = $_GET['date'] ?? '';
            if (!valid_date($date)) {
                fail('วันที่ไม่ถูกต้อง');
            }
            $sql = "
                SELECT c.studentid, c.prefix_th, c.firstname_th, c.lastname_th, c.nickname, c.profile_image,
                       c.classroom, c.child_group,
                       (r.parent_updated_at IS NOT NULL) AS parent_filled,
                       (r.teacher_updated_at IS NOT NULL) AS teacher_filled,
                       r.parent_mood, r.teacher_mood,
                       a.status AS att_status
                FROM children c
                LEFT JOIN daily_reports r ON r.student_id = c.studentid AND r.report_date = :d
                LEFT JOIN LATERAL (
                    SELECT status FROM attendance
                    WHERE student_id = c.studentid AND DATE(check_date) = :d2
                    ORDER BY check_date DESC LIMIT 1
                ) a ON TRUE
                WHERE 1 = 1";
            $params = [':d' => $date, ':d2' => $date];
            if (!empty($_GET['child_group'])) {
                $sql .= " AND c.child_group = :g";
                $params[':g'] = $_GET['child_group'];
            }
            if (!empty($_GET['classroom'])) {
                $sql .= " AND c.classroom = :c";
                $params[':c'] = $_GET['classroom'];
            }
            $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rooms = array_values(array_unique(array_column($rows, 'classroom')));
            respond(['status' => 'success', 'data' => ['children' => $rows, 'menus' => menus_for($pdo, $date, $rooms)]]);

        case 'report':
            $sid = $_GET['student_id'] ?? '';
            $date = $_GET['date'] ?? '';
            if ($sid === '' || !valid_date($date)) {
                fail('ข้อมูลไม่ครบถ้วน');
            }
            if (!$isStaff && $sid !== $username) {
                fail('ไม่มีสิทธิ์ในการดำเนินการ', 403);
            }

            $childStmt = $pdo->prepare("SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, profile_image, classroom, child_group FROM children WHERE studentid = :s");
            $childStmt->execute([':s' => $sid]);
            $child = $childStmt->fetch(PDO::FETCH_ASSOC);
            if (!$child) {
                fail('ไม่พบข้อมูลนักเรียน', 404);
            }

            $repStmt = $pdo->prepare("SELECT * FROM daily_reports WHERE student_id = :s AND report_date = :d");
            $repStmt->execute([':s' => $sid, ':d' => $date]);
            $report = $repStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            // เวลาเช็คชื่อเข้า ใช้เป็นค่าเริ่มต้นของ "ส่งเด็กเวลา"
            $attStmt = $pdo->prepare("
                SELECT status, TO_CHAR(check_date, 'HH24:MI') AS checkin_time
                FROM attendance WHERE student_id = :s AND DATE(check_date) = :d
                ORDER BY check_date ASC LIMIT 1
            ");
            $attStmt->execute([':s' => $sid, ':d' => $date]);
            $att = $attStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($att && $att['checkin_time'] === '00:00') {
                $att['checkin_time'] = null;
            }

            $menus = menus_for($pdo, $date, [$child['classroom']]);
            respond(['status' => 'success', 'data' => [
                'child' => $child,
                'report' => $report,
                'menu' => $menus[$child['classroom']] ?? new stdClass(),
                'attendance' => $att,
            ]]);

        case 'save_report':
            $sid = (string)($input['student_id'] ?? '');
            $date = $input['date'] ?? '';
            $side = $input['side'] ?? '';
            if ($sid === '' || !valid_date($date) || !in_array($side, ['teacher', 'parent'], true)) {
                fail('ข้อมูลไม่ครบถ้วน');
            }

            if (!$isStaff) {
                // ผู้ปกครอง: เฉพาะลูกตัวเอง ฝั่งผู้ปกครอง และเฉพาะวันนี้
                if ($sid !== $username) {
                    fail('ไม่มีสิทธิ์ในการดำเนินการ', 403);
                }
                if ($side !== 'parent') {
                    fail('ไม่มีสิทธิ์ในการดำเนินการ', 403);
                }
                if ($date !== date('Y-m-d')) {
                    fail('ผู้ปกครองแก้ไขได้เฉพาะสมุดของวันนี้');
                }
            }

            $childCheck = $pdo->prepare("SELECT 1 FROM children WHERE studentid = :s");
            $childCheck->execute([':s' => $sid]);
            if (!$childCheck->fetchColumn()) {
                fail('ไม่พบข้อมูลนักเรียน', 404);
            }

            $fields = $side === 'parent' ? NB_PARENT_FIELDS : NB_TEACHER_FIELDS;
            $byCol = $side === 'parent' ? 'parent_updated_by' : 'teacher_updated_by';
            $atCol = $side === 'parent' ? 'parent_updated_at' : 'teacher_updated_at';

            $cols = [];
            $placeholders = [];
            $updates = [];
            $params = [':sid' => $sid, ':d' => $date, ':u' => $username];
            $cast = ['time' => 'TIME', 'int' => 'INTEGER', 'decimal' => 'NUMERIC', 'bool' => 'BOOLEAN'];

            foreach ($fields as $col => $type) {
                if (!array_key_exists($col, $input)) {
                    continue; // ไม่ได้ส่งมา = ไม่แก้
                }
                $cols[] = $col;
                $ph = ':p_' . $col;
                $placeholders[] = isset($cast[$type]) ? "CAST($ph AS {$cast[$type]})" : $ph;
                $updates[] = "$col = EXCLUDED.$col";
                $params[$ph] = clean_value($type, $input[$col]);
            }
            if (!$cols) {
                fail('ไม่มีข้อมูลที่ต้องบันทึก');
            }

            $sql = "INSERT INTO daily_reports (student_id, report_date, " . implode(', ', $cols) . ", $byCol, $atCol)
                    VALUES (:sid, :d, " . implode(', ', $placeholders) . ", :u, NOW())
                    ON CONFLICT (student_id, report_date) DO UPDATE SET "
                . implode(', ', $updates) . ", $byCol = EXCLUDED.$byCol, $atCol = NOW(), updated_at = NOW()";
            $pdo->prepare($sql)->execute($params);

            respond(['status' => 'success', 'message' => 'บันทึกสมุดสื่อสารแล้ว']);

        default:
            fail('ไม่รู้จักคำสั่ง');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('daily_notebook_api: ' . $e->getMessage());
    fail('เกิดข้อผิดพลาด: ' . $e->getMessage(), 500);
}
