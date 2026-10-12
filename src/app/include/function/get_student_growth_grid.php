<?php
// ข้อมูลการเจริญเติบโต (น้ำหนัก/ส่วนสูง/รอบศีรษะ) ที่ครูกรอกในหน้า growth_dev_grid ของเด็ก 1 คน
// ส่งออกในรูปแบบเดียวกับ get_student_growth_external.php เพื่อให้แท็บการเจริญเติบโตของ view_child ใช้ร่วมกันได้
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
        SELECT g.id, DATE(g.created_at) AS exam_date, g.age_year, g.age_month, g.age_day,
               g.weight, g.height, g.head_circumference,
               g.gm_status, g.gm_issue, g.fm_status, g.fm_issue, g.rl_status, g.rl_issue,
               g.el_status, g.el_issue, g.ps_status, g.ps_issue,
               g.gm_note, g.fm_note, g.rl_note, g.el_note, g.ps_note, g.dev_result, g.recheck_date,
               c.sex, c.birthday
        FROM growth_records g
        JOIN children c ON c.studentid = g.student_id
        WHERE g.student_id = :sid AND g.is_draft = false
          AND (g.weight IS NOT NULL OR g.height IS NOT NULL OR g.head_circumference IS NOT NULL
               OR g.gm_status IS NOT NULL OR g.fm_status IS NOT NULL OR g.rl_status IS NOT NULL
               OR g.el_status IS NOT NULL OR g.ps_status IS NOT NULL)
        ORDER BY g.created_at ASC, g.id ASC");
    $stmt->execute([':sid' => $studentId]);

    $records = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $weight = $r['weight'] !== null ? (float)$r['weight'] : null;
        $height = $r['height'] !== null ? (float)$r['height'] : null;
        $dev = [];
        foreach (DEV_SCREENING_DOMAINS as $d) {
            $dev[$d] = ['status' => $r[$d . '_status'], 'score' => $r[$d . '_issue'], 'note' => $r[$d . '_note']];
        }
        // สรุปผลคัดกรอง: ใช้ค่าที่บันทึกไว้ ถ้าเป็นแถวเก่าที่ไม่มีให้คำนวณเมื่อติ๊กครบ 5 ด้าน
        $statuses = array_column($dev, 'status');
        $complete = count(array_filter($statuses)) === count(DEV_SCREENING_DOMAINS);
        $result = $r['dev_result'] ?: ($complete ? (in_array('delay', $statuses, true) ? 'delay' : 'normal') : null);
        $recheck = $r['recheck_date'] ?: ($result === 'delay' ? (new DateTime($r['exam_date']))->modify('+30 days')->format('Y-m-d') : null);
        $records[] = [
            'id' => $r['id'],
            'exam_date' => $r['exam_date'],
            'sex' => $r['sex'],
            'age_year' => $r['age_year'],
            'age_month' => $r['age_month'],
            'age_day' => $r['age_day'],
            'physical_measures' => [
                'weight' => $weight,
                'height' => $height,
                'head_circ' => $r['head_circumference'] !== null ? (float)$r['head_circumference'] : null,
            ],
            'bmi' => ($weight && $height) ? round($weight / (($height / 100) ** 2), 1) : null,
            'development_assessment' => $dev,
            'dev_result' => $result,
            'recheck_date' => $recheck,
        ];
    }

    // การ์ดตัวเลขล่าสุดใช้แถวล่าสุดที่มีการวัด (แถวที่มีแต่ผลพัฒนาการยังอยู่ในประวัติ)
    $withMeasure = array_values(array_filter($records, fn($x) => array_filter($x['physical_measures'], fn($v) => $v !== null)));

    echo json_encode([
        'status' => 'success',
        'current_record' => $withMeasure ? end($withMeasure) : null,
        'all_records' => $records,
    ]);
} catch (Exception $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
