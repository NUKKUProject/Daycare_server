<?php
// บันทึกผลตรวจฟันหลายคนในครั้งเดียว (หน้ากรอกทั้งห้องแบบตาราง)
// ถ้าเด็กมีผลตรวจของปีการศึกษานั้นอยู่แล้วจะอัปเดตแถวล่าสุด ถ้าไม่มีจะเพิ่มใหม่ ทำในธุรกรรมเดียว
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';

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

    $academicYear = trim((string) ($data['academic_year'] ?? ''));
    $doctorName = trim((string) ($data['doctor_name'] ?? ''));
    $rows = $data['rows'] ?? [];
    if ($academicYear === '' || !is_array($rows) || !$rows) {
        respond(['status' => 'error', 'message' => 'ไม่มีข้อมูลให้บันทึก'], 400);
    }

    $examDate = DateTime::createFromFormat('Y-m-d', (string) ($data['exam_date'] ?? ''));
    if (!$examDate) {
        $examDate = new DateTime('today');
    }
    // วันที่ตรวจเก็บใน updated_at (ที่ระบบ export ใช้เป็นวันที่ตรวจ) ใช้เวลาปัจจุบันของวันนั้น
    $stamp = $examDate->format('Y-m-d') . ' ' . date('H:i:s');

    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();

    $childStmt = $pdo->prepare("SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, classroom, birthday
                                FROM children WHERE studentid = :sid");
    $findStmt = $pdo->prepare("SELECT id FROM health_tooth_external
                               WHERE student_id = :sid AND academic_year = :yr ORDER BY id DESC LIMIT 1");

    $update = $pdo->prepare("UPDATE health_tooth_external SET
            prefix_th = :prefix_th, first_name = :first_name, last_name = :last_name, nickname = :nickname,
            classroom = :classroom, doctor_name = :doctor_name,
            age_year = :age_year, age_month = :age_month, age_day = :age_day,
            total_teeth = :total_teeth, decayed_teeth = :decayed_teeth, oral_components = :oral_components,
            teeth_status = :teeth_status, missing_teeth_detail = :missing_teeth_detail,
            decayed_teeth_positions = :positions, treatments = :treatments,
            other_treatment_detail = :other_detail, urgency = :urgency, updated_at = :stamp
        WHERE id = :id");

    $insert = $pdo->prepare("INSERT INTO health_tooth_external (
            student_id, prefix_th, first_name, last_name, nickname, classroom, doctor_name,
            age_year, age_month, age_day, academic_year,
            total_teeth, decayed_teeth, oral_components, teeth_status, missing_teeth_detail,
            decayed_teeth_positions, treatments, other_treatment_detail, urgency, updated_at
        ) VALUES (
            :sid, :prefix_th, :first_name, :last_name, :nickname, :classroom, :doctor_name,
            :age_year, :age_month, :age_day, :academic_year,
            :total_teeth, :decayed_teeth, :oral_components, :teeth_status, :missing_teeth_detail,
            :positions, :treatments, :other_detail, :urgency, :stamp
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
        $status = (string) ($row['teeth_status'] ?? '');
        if ($total === null || $decayed === null || !in_array($status, ['normal', 'abnormal'], true)) {
            $errors[] = ['student_id' => $sid, 'message' => 'กรอกจำนวนฟันและสภาพฟันไม่ครบ'];
            continue;
        }

        $positions = [];
        foreach (TOOTH_POSITIONS as $p) {
            $positions[$p] = count_or_null($row['positions'][$p] ?? null) ?? 0;
        }
        if ($status === 'normal') {
            $decayed = 0;
            $positions = array_fill_keys(TOOTH_POSITIONS, 0);
        } elseif (array_sum($positions) !== $decayed) {
            $errors[] = ['student_id' => $sid, 'message' => 'ยอดรวมตำแหน่งฟันผุไม่ตรงกับจำนวนฟันผุ'];
            continue;
        }

        $urgency = in_array($row['urgency'] ?? '', TOOTH_URGENCY, true) ? $row['urgency'] : null;
        if ($status === 'abnormal' && $urgency === null) {
            $errors[] = ['student_id' => $sid, 'message' => 'ยังไม่ได้เลือกความเร่งด่วน'];
            continue;
        }

        $treatments = array_values(array_intersect(TOOTH_TREATMENTS, is_array($row['treatments'] ?? null) ? $row['treatments'] : []));
        $otherDetail = in_array('other', $treatments, true) ? mb_substr(trim((string) ($row['other_treatment_detail'] ?? '')), 0, 200) : '';

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
            ':oral_components' => mb_substr(trim((string) ($row['oral_components'] ?? '')), 0, 100),
            ':teeth_status' => $status,
            ':missing_teeth_detail' => mb_substr(trim((string) ($row['missing_teeth_detail'] ?? '')), 0, 100),
            ':positions' => json_encode($positions),
            ':treatments' => json_encode($treatments),
            ':other_detail' => $otherDetail,
            ':urgency' => $urgency,
            ':stamp' => $stamp,
        ];

        $findStmt->execute([':sid' => $sid, ':yr' => $academicYear]);
        $existingId = $findStmt->fetchColumn();
        if ($existingId) {
            $update->execute($params + [':id' => $existingId]);
        } else {
            $insert->execute($params + [':sid' => $sid, ':academic_year' => $academicYear]);
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
