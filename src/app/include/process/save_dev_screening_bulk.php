<?php
// บันทึกผลคัดกรองพัฒนาการ + น้ำหนัก/ส่วนสูง/เส้นรอบศีรษะ หลายคนพร้อมกัน (ครู/แอดมินเท่านั้น) ลงตาราง growth_records เดิม
// ระบบคำนวณอายุ สรุปผล และวันประเมินซ้ำเอง  ถ้าเด็กมีแถวของวันนั้นอยู่แล้ว จะเติมลงแถวเดิม
// พัฒนาการ 5 ด้านต้องติ๊กครบทั้งหมดหรือไม่ติ๊กเลย (ไม่ติ๊กเลย = บันทึกเฉพาะข้อมูลการเจริญเติบโต)
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../function/dev_screening_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher']);
date_default_timezone_set('Asia/Bangkok');

// ค่าตัวเลขที่ไม่บังคับ: ว่าง = null, ไม่ใช่ตัวเลขหรือติดลบ = ผิดพลาด
function devScreeningOptionalNumber($v, string $label, float $max): ?float
{
    if ($v === null || $v === '') {
        return null;
    }
    if (!is_numeric($v) || $v < 0 || $v > $max) {
        throw new InvalidArgumentException("$label ไม่ถูกต้อง");
    }
    return (float)$v;
}

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

        try {
            $weight = devScreeningOptionalNumber($row['weight'] ?? null, 'น้ำหนัก', 150);
            $height = devScreeningOptionalNumber($row['height'] ?? null, 'ส่วนสูง', 250);
            $head = devScreeningOptionalNumber($row['head_circumference'] ?? null, 'เส้นรอบศีรษะ', 100);
        } catch (InvalidArgumentException $e) {
            $errors[] = "$sid: " . $e->getMessage();
            continue;
        }

        $ageRange = $row['age_range'] ?? '';
        if (!in_array($ageRange, DEV_SCREENING_AGE_RANGES, true)) {
            $ageRange = devScreeningDefaultRange($age['months']);
        }

        // พัฒนาการ 5 ด้าน: ครบทั้งหมดหรือไม่มีเลย
        $statuses = [];
        $dev = [];
        foreach (DEV_SCREENING_DOMAINS as $d) {
            $st = $row[$d]['status'] ?? '';
            if ($st === 'pass' || $st === 'delay') {
                $statuses[$d] = $st;
            } elseif ($st !== '') {
                $errors[] = "$sid: ค่าผลพัฒนาการไม่ถูกต้อง";
                continue 2;
            }
            $note = trim((string)($row[$d]['note'] ?? ''));
            $issue = $row[$d]['issue'] ?? null;
            $issue = ($issue === null || $issue === '') ? null : (int)$issue;
            $dev[$d] = ['note' => $note !== '' ? $note : null, 'issue' => $issue && $issue > 0 ? $issue : null];
        }
        $hasDev = count($statuses) > 0;
        if ($hasDev && count($statuses) !== count(DEV_SCREENING_DOMAINS)) {
            $errors[] = "$sid: ยังไม่ได้ติ๊กผ่าน/ไม่ผ่านครบทั้ง 5 ด้าน";
            continue;
        }
        if (!$hasDev && $weight === null && $height === null && $head === null) {
            $errors[] = "$sid: ไม่มีข้อมูลที่จะบันทึก";
            continue;
        }

        // ค่าที่เขียนเสมอ (อายุ + ช่วงอายุเกณฑ์) กับค่าที่เขียนเมื่อมีข้อมูล
        $cols = [
            'age_year' => $age['year'], 'age_month' => $age['month'], 'age_day' => $age['day'],
            'age_months' => $age['months'], 'age_range' => $ageRange,
        ];
        if ($weight !== null) { $cols['weight'] = $weight; }
        if ($height !== null) { $cols['height'] = $height; }
        if ($head !== null) { $cols['head_circumference'] = $head; }
        if ($hasDev) {
            $sum = devScreeningSummary($statuses, $assessDate);
            foreach (DEV_SCREENING_DOMAINS as $d) {
                $cols["{$d}_status"] = $statuses[$d];
                $cols["{$d}_note"] = $dev[$d]['note'];
                $cols["{$d}_issue"] = $dev[$d]['issue'];
            }
            $cols['dev_pass_count'] = $sum['pass_count'];
            $cols['dev_fail_count'] = $sum['fail_count'];
            $cols['dev_result'] = $sum['result'];
            $cols['recheck_date'] = $sum['recheck_date'];
            $cols['dev_recorded_by'] = $recordedBy;
        }

        $findStmt->execute([':sid' => $sid, ':d' => $assessDate]);
        $existingId = $findStmt->fetchColumn();
        if ($existingId) {
            $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($cols)));
            $stmt = $pdo->prepare("UPDATE growth_records SET $set, updated_at = NOW() WHERE id = :id");
            $stmt->execute(array_combine(array_map(fn($c) => ":$c", array_keys($cols)), $cols) + [':id' => $existingId]);
        } else {
            $cols += [
                'student_id' => $sid, 'child_group' => $child['child_group'], 'sex' => $child['sex'],
                'created_at' => $assessDate . ' ' . date('H:i:s'), 'is_draft' => 'false',
            ];
            $names = implode(', ', array_keys($cols));
            $marks = implode(', ', array_map(fn($c) => ":$c", array_keys($cols)));
            $stmt = $pdo->prepare("INSERT INTO growth_records ($names) VALUES ($marks)");
            $stmt->execute(array_combine(array_map(fn($c) => ":$c", array_keys($cols)), $cols));
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
