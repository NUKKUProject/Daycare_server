<?php
// บันทึกผลคัดกรองพัฒนาการหลายคนพร้อมกัน (ครู/แอดมินเท่านั้น) ลงตาราง growth_records เดิม
// ระบบคำนวณอายุ สรุปผล และวันประเมินซ้ำเอง  ถ้าเด็กมีแถวของวันนั้นอยู่แล้ว (เช่น บันทึกน้ำหนัก/ส่วนสูงไว้) จะเติมลงแถวเดิม
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../function/dev_screening_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher']);
date_default_timezone_set('Asia/Bangkok');

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

    $childSql = "SELECT c.birthday, c.child_group, c.sex FROM children c WHERE c.studentid = :sid";
    if ($isTeacher) {
        $childSql .= devScreeningTeacherScopeSql();
    }
    $childStmt = $pdo->prepare($childSql);

    $findStmt = $pdo->prepare("SELECT id FROM growth_records
        WHERE student_id = :sid AND DATE(created_at) = :d AND is_draft = false
        ORDER BY id DESC LIMIT 1");

    $devCols = "age_year = :age_year, age_month = :age_month, age_day = :age_day,
        age_months = :age_months, dev_age_range = :age_range,
        gm_status = :gm_status, gm_note = :gm_note, fm_status = :fm_status, fm_note = :fm_note,
        rl_status = :rl_status, rl_note = :rl_note, el_status = :el_status, el_note = :el_note,
        ps_status = :ps_status, ps_note = :ps_note,
        dev_pass_count = :pass_count, dev_fail_count = :fail_count, dev_result = :result,
        recheck_date = :recheck_date, dev_recorded_by = :recorded_by";

    $updateStmt = $pdo->prepare("UPDATE growth_records SET $devCols, updated_at = NOW() WHERE id = :id");

    $insertStmt = $pdo->prepare("
        INSERT INTO growth_records
            (student_id, child_group, sex, created_at, is_draft,
             age_year, age_month, age_day, age_months, dev_age_range,
             gm_status, gm_note, fm_status, fm_note, rl_status, rl_note, el_status, el_note, ps_status, ps_note,
             dev_pass_count, dev_fail_count, dev_result, recheck_date, dev_recorded_by)
        VALUES
            (:student_id, :child_group, :sex, :created_at, false,
             :age_year, :age_month, :age_day, :age_months, :age_range,
             :gm_status, :gm_note, :fm_status, :fm_note, :rl_status, :rl_note, :el_status, :el_note, :ps_status, :ps_note,
             :pass_count, :fail_count, :result, :recheck_date, :recorded_by)");

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
            if ($st !== 'pass' && $st !== 'delay') {
                $errors[] = "$sid: ยังไม่ได้ติ๊กผ่าน/ไม่ผ่านครบทั้ง 5 ด้าน";
                continue 2;
            }
            $statuses[$d] = $st;
            $note = trim((string)($row[$d]['note'] ?? ''));
            $bind[":{$d}_status"] = $st;
            $bind[":{$d}_note"] = $note !== '' ? $note : null;
        }

        $sum = devScreeningSummary($statuses, $assessDate);
        $bind += [
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
        ];

        $findStmt->execute([':sid' => $sid, ':d' => $assessDate]);
        $existingId = $findStmt->fetchColumn();
        if ($existingId) {
            $updateStmt->execute($bind + [':id' => $existingId]);
        } else {
            $insertStmt->execute($bind + [
                ':student_id' => $sid,
                ':child_group' => $child['child_group'],
                ':sex' => $child['sex'],
                ':created_at' => $assessDate . ' ' . date('H:i:s'),
            ]);
        }
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
