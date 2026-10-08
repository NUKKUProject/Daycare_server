<?php
// ข้อมูลสำหรับตารางกรอกทั้งห้อง: รายชื่อเด็ก + ผลตรวจของรอบที่เลือก (ถ้ามี)
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/health_round_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();
    $round = health_get_round($pdo, (int) ($_GET['round_id'] ?? 0));
    if (!$round) {
        throw new Exception('ไม่พบรอบตรวจ');
    }

    $childGroup = trim($_GET['child_group'] ?? '');
    $classroom = trim($_GET['classroom'] ?? '');
    $studentYear = trim($_GET['student_year'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, child_group, classroom, birthday
            FROM children WHERE status = 'กำลังศึกษา'";
    $params = [];
    if ($childGroup !== '') {
        $sql .= ' AND child_group = :cg';
        $params[':cg'] = $childGroup;
    }
    if ($classroom !== '') {
        $sql .= ' AND classroom = :cr';
        $params[':cr'] = $classroom;
    }
    if ($studentYear !== '' && $studentYear !== 'all') {
        $sql .= ' AND academic_year = :sy';
        $params[':sy'] = $studentYear;
    }
    if ($search !== '') {
        $sql .= ' AND (firstname_th ILIKE :s OR lastname_th ILIKE :s OR nickname ILIKE :s OR studentid ILIKE :s)';
        $params[':s'] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY child_group, classroom, firstname_th, lastname_th';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ผลตรวจของรอบนี้ (ล่าสุดต่อเด็ก 1 แถว)
    $records = [];
    if ($children) {
        $ids = array_column($children, 'studentid');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $recStmt = $pdo->prepare("SELECT DISTINCT ON (student_id)
                   id, student_id, exam_date, measurement_date, birth_date, age_year, age_month, age_day, doctor_name,
                   vital_signs, behavior, physical_measures, development_assessment, physical_exam, neurological, recommendation
            FROM health_data_external
            WHERE academic_year::text = ? AND COALESCE(check_round, 1) = ? AND student_id IN ($in)
            ORDER BY student_id, id DESC");
        $recStmt->execute(array_merge([$round['academic_year'], (int) $round['round_no']], $ids));
        foreach ($recStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $dec = fn($v) => ($v === null || $v === '') ? [] : (json_decode($v, true) ?: []);
            $records[$r['student_id']] = [
                'id' => (int) $r['id'],
                'locked' => trim((string) $r['doctor_name']) !== '',
                'doctor_name' => $r['doctor_name'],
                'exam_date' => $r['exam_date'],
                'measurement_date' => $r['measurement_date'],
                'birth_date' => $r['birth_date'],
                'vital_signs' => $dec($r['vital_signs']),
                'behavior' => $dec($r['behavior']),
                'physical_measures' => $dec($r['physical_measures']),
                'development_assessment' => $dec($r['development_assessment']),
                'physical_exam' => $dec($r['physical_exam']),
                'neurological' => $dec($r['neurological']),
                'recommendation' => $r['recommendation'] ?? '',
            ];
        }
    }

    $rows = [];
    foreach ($children as $c) {
        $rows[] = [
            'student_id' => $c['studentid'],
            'name' => trim(($c['prefix_th'] ?? '') . ($c['firstname_th'] ?? '') . ' ' . ($c['lastname_th'] ?? '')),
            'nick' => $c['nickname'] ?? '',
            'child_group' => $c['child_group'] ?? '',
            'classroom' => $c['classroom'] ?? '',
            'birthday' => $c['birthday'] ?? null,
            'record' => $records[$c['studentid']] ?? null,
        ];
    }

    echo json_encode(['status' => 'success', 'round' => $round, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
