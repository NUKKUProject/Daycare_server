<?php
// บันทึกผลตรวจฟันหลายคนในครั้งเดียว (หน้ากรอกทั้งห้องแบบตาราง)
// ผูกกับรอบตรวจและประเภทผู้ตรวจ (ครูคัดกรอง / แพทย์) ถ้าเด็กมีผลของประเภทนี้ในรอบนี้แล้วจะอัปเดต ถ้าไม่มีจะเพิ่มใหม่
// ทำในธุรกรรมเดียว รอบที่ปิดแล้วบันทึกไม่ได้
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../function/tooth_exam_helpers.php';

header('Content-Type: application/json; charset=utf-8');

const TOOTH_POSITIONS = ['upper_front_teeth', 'upper_right_molar', 'lower_right_molar', 'lower_front_teeth', 'upper_left_molar', 'lower_left_molar'];
const TOOTH_TREATMENTS = ['filling', 'fluoride', 'root_canal', 'fluoride_molar', 'crown', 'extraction', 'other'];
const TOOTH_URGENCY = ['urgent', 'not_urgent', 'preventable'];

function respond(array $body, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function count_or_null($v): ?int
{
    return ($v === null || $v === '' || !is_numeric($v)) ? null : max(0, (int) $v);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respond(['status' => 'error', 'message' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง'], 400);
    }

    $doctorName = trim((string) ($data['doctor_name'] ?? ''));
    $rows = $data['rows'] ?? [];
    if (!is_array($rows) || !$rows) {
        respond(['status' => 'error', 'message' => 'ไม่มีข้อมูลให้บันทึก'], 400);
    }

    $examDate = DateTime::createFromFormat('Y-m-d', (string) ($data['exam_date'] ?? ''));
    if (!$examDate) {
        $examDate = new DateTime('today');
    }
    // วันที่ตรวจเก็บใน updated_at (ที่ระบบ export ใช้เป็นวันที่ตรวจ) ใช้เวลาปัจจุบันของวันนั้น
    $stamp = $examDate->format('Y-m-d') . ' ' . date('H:i:s');

    $pdo = getDatabaseConnection();

    $round = tooth_get_round($pdo, (int) ($data['round_id'] ?? 0));
    if (!$round) {
        respond(['status' => 'error', 'message' => 'กรุณาเลือกรอบตรวจ'], 400);
    }
    if ($round['status'] !== 'open') {
        respond(['status' => 'error', 'message' => 'รอบตรวจนี้ถูกปิดแล้ว ไม่สามารถบันทึกได้'], 400);
    }
    $academicYear = $round['academic_year'];
    $examType = tooth_resolve_exam_type($_SESSION['role'] ?? '', $data['exam_type'] ?? null);
    $by = tooth_current_user_label();
    // ชื่อแพทย์เก็บเฉพาะผลที่แพทย์ตรวจ ผลคัดกรองของครูไม่เก็บ (ผู้บันทึกอยู่ใน examined_by อยู่แล้ว)
    $doctorName = $examType === 'doctor' && $doctorName !== '' ? $doctorName : null;

    $pdo->beginTransaction();

    $childStmt = $pdo->prepare("SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, classroom, birthday
                                FROM children WHERE studentid = :sid");
    $update = $pdo->prepare("UPDATE health_tooth_external SET
            prefix_th = :prefix_th, first_name = :first_name, last_name = :last_name, nickname = :nickname,
            classroom = :classroom, doctor_name = :doctor_name,
            age_year = :age_year, age_month = :age_month, age_day = :age_day,
            total_teeth = :total_teeth, decayed_teeth = :decayed_teeth, oral_components = :oral_components,
            teeth_status = :teeth_status, missing_teeth_detail = :missing_teeth_detail,
            decayed_teeth_positions = :positions, treatments = :treatments,
            other_treatment_detail = :other_detail, urgency = :urgency, updated_at = :stamp,
            examined_by = :by, examined_at = :exam_day, exam_type = :t
        WHERE id = :id");

    $insert = $pdo->prepare("INSERT INTO health_tooth_external (
            student_id, prefix_th, first_name, last_name, nickname, classroom, doctor_name,
            age_year, age_month, age_day, academic_year,
            total_teeth, decayed_teeth, oral_components, teeth_status, missing_teeth_detail,
            decayed_teeth_positions, treatments, other_treatment_detail, urgency, updated_at,
            round_id, exam_type, examined_by, examined_at
        ) VALUES (
            :sid, :prefix_th, :first_name, :last_name, :nickname, :classroom, :doctor_name,
            :age_year, :age_month, :age_day, :academic_year,
            :total_teeth, :decayed_teeth, :oral_components, :teeth_status, :missing_teeth_detail,
            :positions, :treatments, :other_detail, :urgency, :stamp,
            :rid, :t, :by, :exam_day
        )");

    $saved = 0;
    $errors = [];

    foreach ($rows as $row) {
        $sid = trim((string) ($row['student_id'] ?? ''));
        $childStmt->execute([':sid' => $sid]);
        $child = $childStmt->fetch(PDO::FETCH_ASSOC);
        if (!$child) {
            $errors[] = ['student_id' => $sid, 'message' => 'ไม่พบข้อมูลนักเรียน'];
            continue;
        }

        $total = count_or_null($row['total_teeth'] ?? null);
        $decayed = count_or_null($row['decayed_teeth'] ?? null);
        // กรอกไม่ครบก็บันทึกได้ ช่องที่ไม่ได้กรอกเก็บเป็นว่าง (ต้องมีอย่างน้อยหนึ่งช่อง)
        $status = in_array($row['teeth_status'] ?? '', ['normal', 'abnormal'], true) ? $row['teeth_status'] : null;
        $positions = [];
        foreach (TOOTH_POSITIONS as $p) {
            $positions[$p] = count_or_null($row['positions'][$p] ?? null) ?? 0;
        }
        if ($status === 'normal') {
            $decayed = 0;
            $positions = array_fill_keys(TOOTH_POSITIONS, 0);
        }
        $urgency = in_array($row['urgency'] ?? '', TOOTH_URGENCY, true) ? $row['urgency'] : null;
        $treatments = array_values(array_intersect(TOOTH_TREATMENTS, is_array($row['treatments'] ?? null) ? $row['treatments'] : []));
        // ข้อความที่แพทย์พิมพ์ต้องไม่หาย แม้ไม่ได้ติ๊ก "อื่นๆ" ระบบติ๊กให้เอง
        $otherDetail = mb_substr(trim((string) ($row['other_treatment_detail'] ?? '')), 0, 200);
        if ($otherDetail !== '' && !in_array('other', $treatments, true)) {
            $treatments[] = 'other';
        }
        // กรอกจำนวนฟันผุ > 0 แต่ไม่ได้เลือกสภาพฟัน = มีฟันผุ
        if ($status === null && $decayed !== null && $decayed > 0) {
            $status = 'abnormal';
        }
        $oral = mb_substr(trim((string) ($row['oral_components'] ?? '')), 0, 100);
        $missing = mb_substr(trim((string) ($row['missing_teeth_detail'] ?? '')), 0, 100);

        $hasAny = $total !== null || $decayed !== null || $status !== null || $urgency !== null
            || array_sum($positions) > 0 || $treatments || $oral !== '' || $missing !== '';
        if (!$hasAny) {
            $errors[] = ['student_id' => $sid, 'message' => 'ยังไม่ได้กรอกข้อมูลใดๆ'];
            continue;
        }

        // อายุ ณ วันตรวจ คำนวณจากวันเกิด (ไม่มีวันเกิดเก็บเป็น 0)
        $ageY = $ageM = $ageD = 0;
        if (!empty($child['birthday'])) {
            $birth = new DateTime($child['birthday']);
            if ($birth <= $examDate) {
                $diff = $birth->diff($examDate);
                [$ageY, $ageM, $ageD] = [$diff->y, $diff->m, $diff->d];
            }
        }

        $params = [
            ':prefix_th' => $child['prefix_th'],
            ':first_name' => $child['firstname_th'],
            ':last_name' => $child['lastname_th'],
            ':nickname' => $child['nickname'],
            ':classroom' => $child['classroom'],
            ':doctor_name' => $doctorName,
            ':age_year' => $ageY,
            ':age_month' => $ageM,
            ':age_day' => $ageD,
            ':total_teeth' => $total,
            ':decayed_teeth' => $decayed,
            ':oral_components' => $oral,
            ':teeth_status' => $status,
            ':missing_teeth_detail' => $missing,
            ':positions' => json_encode($positions),
            ':treatments' => json_encode($treatments),
            ':other_detail' => $otherDetail,
            ':urgency' => $urgency,
            ':stamp' => $stamp,
            ':by' => $by,
            ':exam_day' => $examDate->format('Y-m-d'),
        ];

        // เด็ก 1 คน 1 รอบ มีแถวเดียว: ถ้ามีแล้วอัปเดตแถวเดิม (แพทย์อัปเดตผลที่ครูคัดกรองไว้) ไม่เพิ่มแถวซ้ำ
        $existing = tooth_find_existing($pdo, $sid, (int) $round['id']);
        if ($existing && $existing['exam_type'] === 'doctor' && $examType !== 'doctor') {
            $errors[] = ['student_id' => $sid, 'message' => 'แพทย์ตรวจแล้ว ครูแก้ไขไม่ได้'];
            continue;
        }
        if ($existing) {
            $update->execute($params + [':id' => $existing['id'], ':t' => $examType]);
        } else {
            $insert->execute($params + [':sid' => $sid, ':academic_year' => $academicYear, ':rid' => $round['id'], ':t' => $examType]);
        }
        $saved++;
    }

    $pdo->commit();
    respond(['status' => 'success', 'saved' => $saved, 'errors' => $errors,
        'message' => "บันทึก {$saved} คน" . ($errors ? ' (ข้าม ' . count($errors) . ' คน)' : '')]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('save_health_tooth_bulk: ' . $e->getMessage());
    respond(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ ไม่มีข้อมูลถูกบันทึก (' . $e->getMessage() . ')'], 500);
}
