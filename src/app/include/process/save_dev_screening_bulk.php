<?php
// บันทึกผลคัดกรองพัฒนาการหลายคนพร้อมกัน (ครู/แอดมินเท่านั้น) ระบบคำนวณอายุ สรุปผล และวันประเมินซ้ำเอง
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../function/dev_screening_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher']);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $assessDate = $input['assess_date'] ?? '';
    $rows = $input['rows'] ?? [];
    if (!DateTime::createFromFormat('Y-m-d', $assessDate)) {
        throw new Exception('กรุณาเลือกวันที่ประเมิน');
    }
    if (!is_array($rows) || !$rows) {
        throw new Exception('ไม่มีข้อมูลที่จะบันทึก');
    }

    $pdo = getDatabaseConnection();
    $isTeacher = getUserRole() === 'teacher';
    $recordedBy = function_exists('getFullName') ? getFullName() : ($_SESSION['username'] ?? '');

    $childSql = "SELECT c.birthday FROM children c WHERE c.studentid = :sid";
    if ($isTeacher) {
        $childSql .= devScreeningTeacherScopeSql();
    }
    $childStmt = $pdo->prepare($childSql);

    $upsert = $pdo->prepare("
        INSERT INTO development_screenings
            (student_id, assess_date, birthday, age_year, age_month, age_day, age_months, age_range,
             gm_status, gm_note, fm_status, fm_note, rl_status, rl_note, el_status, el_note, ps_status, ps_note,
             pass_count, fail_count, result, recheck_date, recorded_by)
        VALUES
            (:student_id, :assess_date, :birthday, :age_year, :age_month, :age_day, :age_months, :age_range,
             :gm_status, :gm_note, :fm_status, :fm_note, :rl_status, :rl_note, :el_status, :el_note, :ps_status, :ps_note,
             :pass_count, :fail_count, :result, :recheck_date, :recorded_by)
        ON CONFLICT (student_id, assess_date) DO UPDATE SET
            birthday = EXCLUDED.birthday, age_year = EXCLUDED.age_year, age_month = EXCLUDED.age_month,
            age_day = EXCLUDED.age_day, age_months = EXCLUDED.age_months, age_range = EXCLUDED.age_range,
            gm_status = EXCLUDED.gm_status, gm_note = EXCLUDED.gm_note,
            fm_status = EXCLUDED.fm_status, fm_note = EXCLUDED.fm_note,
            rl_status = EXCLUDED.rl_status, rl_note = EXCLUDED.rl_note,
            el_status = EXCLUDED.el_status, el_note = EXCLUDED.el_note,
            ps_status = EXCLUDED.ps_status, ps_note = EXCLUDED.ps_note,
            pass_count = EXCLUDED.pass_count, fail_count = EXCLUDED.fail_count, result = EXCLUDED.result,
            recheck_date = EXCLUDED.recheck_date, recorded_by = EXCLUDED.recorded_by, updated_at = NOW()
    ");

    $saved = 0;
    $errors = [];
    $pdo->beginTransaction();
    foreach ($rows as $row) {
        $sid = (string)($row['student_id'] ?? '');
        $params = [':sid' => $sid];
        if ($isTeacher) {
            $params[':teacher_id'] = $_SESSION['user_id'];
        }
        $childStmt->execute($params);
        $child = $childStmt->fetch(PDO::FETCH_ASSOC);
        if (!$child) {
            $errors[] = "$sid: ไม่พบเด็ก หรือไม่มีสิทธิ์บันทึก";
            continue;
        }
        $age = $child['birthday'] ? devScreeningAge($child['birthday'], $assessDate) : null;
        if (!$age) {
            $errors[] = "$sid: วันเกิดไม่ถูกต้องหรือวันที่ประเมินก่อนวันเกิด";
            continue;
        }

        $statuses = [];
        $bind = [];
        foreach (DEV_SCREENING_DOMAINS as $d) {
            $st = $row[$d]['status'] ?? '';
            if ($st !== 'pass' && $st !== 'fail') {
                $errors[] = "$sid: ยังไม่ได้ติ๊กผ่าน/ไม่ผ่านครบทั้ง 5 ด้าน";
                continue 2;
            }
            $statuses[$d] = $st;
            $note = trim((string)($row[$d]['note'] ?? ''));
            $bind[":{$d}_status"] = $st;
            $bind[":{$d}_note"] = $note !== '' ? $note : null;
        }

        $sum = devScreeningSummary($statuses, $assessDate);
        $upsert->execute($bind + [
            ':student_id' => $sid,
            ':assess_date' => $assessDate,
            ':birthday' => $child['birthday'],
            ':age_year' => $age['year'],
            ':age_month' => $age['month'],
            ':age_day' => $age['day'],
            ':age_months' => $age['months'],
            ':age_range' => devScreeningAgeRange($age['months']),
            ':pass_count' => $sum['pass_count'],
            ':fail_count' => $sum['fail_count'],
            ':result' => $sum['result'],
            ':recheck_date' => $sum['recheck_date'],
            ':recorded_by' => $recordedBy,
        ]);
        $saved++;
    }
    $pdo->commit();

    echo json_encode(['status' => 'success', 'saved' => $saved, 'errors' => $errors]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
