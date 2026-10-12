<?php
// ประวัติการคัดกรองพัฒนาการของเด็ก 1 คน (ผู้ปกครองดูได้เฉพาะลูกตัวเอง แก้ไขไม่ได้)
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/dev_screening_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher', 'student']);

try {
    $studentId = $_GET['student_id'] ?? '';
    if ($studentId === '') {
        throw new Exception('Missing student_id parameter');
    }
    $pdo = getDatabaseConnection();
    devScreeningAssertCanView($pdo, $studentId);

    $stmt = $pdo->prepare("
        SELECT g.id, DATE(g.created_at) AS assess_date, c.birthday,
               g.age_year, g.age_month, g.age_day, g.age_months, g.age_range,
               g.gm_status, g.gm_note, g.fm_status, g.fm_note, g.rl_status, g.rl_note,
               g.el_status, g.el_note, g.ps_status, g.ps_note,
               g.gm_issue, g.fm_issue, g.rl_issue, g.el_issue, g.ps_issue,
               g.dev_result, g.recheck_date
        FROM growth_records g
        JOIN children c ON c.studentid = g.student_id
        WHERE g.student_id = :sid AND g.is_draft = false
          AND (g.gm_status IS NOT NULL OR g.fm_status IS NOT NULL OR g.rl_status IS NOT NULL
               OR g.el_status IS NOT NULL OR g.ps_status IS NOT NULL)
        ORDER BY g.created_at DESC, g.id DESC");
    $stmt->execute([':sid' => $studentId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // นับผ่าน/ไม่ผ่านจากสถานะ (รวมถึงแถวเก่าที่บันทึกก่อนมีคอลัมน์สรุป)
    foreach ($records as &$r) {
        $pass = 0;
        $fail = 0;
        foreach (DEV_SCREENING_DOMAINS as $d) {
            if ($r[$d . '_status'] === 'pass') {
                $pass++;
            } elseif ($r[$d . '_status'] !== null && $r[$d . '_status'] !== '') {
                $fail++;
            }
        }
        $r['pass_count'] = $pass;
        $r['fail_count'] = $fail;
        $complete = ($pass + $fail) === count(DEV_SCREENING_DOMAINS);
        if (!$r['dev_result'] && $complete) {
            $r['dev_result'] = $fail > 0 ? 'delay' : 'normal';
        }
        if (!$r['recheck_date'] && $r['dev_result'] === 'delay') {
            $r['recheck_date'] = (new DateTime($r['assess_date']))->modify('+30 days')->format('Y-m-d');
        }
        if ($r['age_months'] === null && $r['age_year'] !== null) {
            $r['age_months'] = $r['age_year'] * 12 + $r['age_month'];
        }
    }
    unset($r);
    echo json_encode(['status' => 'success', 'records' => $records]);
} catch (Exception $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
