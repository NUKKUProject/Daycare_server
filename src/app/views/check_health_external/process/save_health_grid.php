<?php
// บันทึกผลตรวจสุขภาพทั้งห้อง (ตารางกรอกหลายคน) ผูกกับรอบตรวจ
// - เด็กแต่ละคนมีผลตรวจ 1 แถวต่อรอบ (บันทึกซ้ำ = อัปเดต)
// - รอบที่ปิดแล้วบันทึกไม่ได้  ผลที่แพทย์ตรวจแล้วแก้ผ่านตารางนี้ไม่ได้
// - บันทึกเฉพาะแถวที่ส่งมา (กรอกบางส่วนได้) แต่ละแถวบันทึกแยกกัน แถวที่ผิดพลาดไม่กระทบแถวอื่น
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../function/health_round_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();
    $payload = json_decode(file_get_contents('php://input'), true);
    $records = $payload['records'] ?? null;
    if (!is_array($records) || !$records) {
        throw new Exception('ไม่พบข้อมูลที่จะบันทึก');
    }
    $round = health_require_open_round($pdo, (int) ($payload['round_id'] ?? 0));
    $year = (string) $round['academic_year'];
    $roundNo = (int) $round['round_no'];
    $recordedBy = $_SESSION['user_id'] ?? null;

    $json = fn($v) => json_encode(is_array($v) ? $v : [], JSON_UNESCAPED_UNICODE);
    $dec = fn($v) => ($v === null || $v === '') ? [] : (json_decode($v, true) ?: []);
    $intOrNull = fn($v) => ($v === null || $v === '' || !is_numeric($v)) ? null : (int) $v;
    $dateOrNull = fn($v) => (is_string($v) && DateTime::createFromFormat('Y-m-d', $v)) ? $v : null;

    $saved = [];
    $errors = [];

    foreach ($records as $rec) {
        $sid = (string) ($rec['student_id'] ?? '');
        try {
            if ($sid === '') {
                throw new Exception('ไม่พบรหัสนักเรียน');
            }
            $child = $pdo->prepare('SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, child_group, classroom, birthday FROM children WHERE studentid = :s');
            $child->execute([':s' => $sid]);
            $child = $child->fetch(PDO::FETCH_ASSOC);
            if (!$child) {
                throw new Exception('ไม่พบนักเรียนในระบบ');
            }

            $examDate = $dateOrNull($rec['exam_date'] ?? null) ?: date('Y-m-d');
            $measureDate = $dateOrNull($rec['measurement_date'] ?? null) ?: $examDate;
            $birth = $dateOrNull($rec['birth_date'] ?? null) ?: ($child['birthday'] ?? null);

            $find = $pdo->prepare('SELECT * FROM health_data_external WHERE student_id = :s AND academic_year::text = :y AND COALESCE(check_round, 1) = :n ORDER BY id DESC LIMIT 1');
            $find->execute([':s' => $sid, ':y' => $year, ':n' => $roundNo]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);

            $pdo->beginTransaction();
            if ($existing) {
                if (trim((string) $existing['doctor_name']) !== '') {
                    throw new Exception('แพทย์ตรวจแล้ว แก้ไขในตารางนี้ไม่ได้');
                }
                // รวมกับข้อมูลเดิม: ช่องที่ตารางไม่มี (เช่น รายการตรวจอื่นที่กรอกในฟอร์มรายคน) จะไม่หาย
                $merge = fn($col, $new) => $json(array_merge($dec($existing[$col]), is_array($new) ? $new : []));
                $upd = $pdo->prepare('UPDATE health_data_external SET
                        exam_date = :exam_date, measurement_date = :measurement_date, birth_date = :birth_date,
                        age_year = :age_year, age_month = :age_month, age_day = :age_day,
                        vital_signs = :vital_signs, behavior = :behavior, physical_measures = :physical_measures,
                        development_assessment = :development_assessment, physical_exam = :physical_exam, neurological = :neurological,
                        recommendation = :recommendation, updated_at = NOW()
                    WHERE id = :id');
                $upd->execute([
                    ':exam_date' => $examDate, ':measurement_date' => $measureDate, ':birth_date' => $birth,
                    ':age_year' => $intOrNull($rec['age_year'] ?? null), ':age_month' => $intOrNull($rec['age_month'] ?? null), ':age_day' => $intOrNull($rec['age_day'] ?? null),
                    ':vital_signs' => $merge('vital_signs', $rec['vital_signs'] ?? []),
                    ':behavior' => $merge('behavior', $rec['behavior'] ?? []),
                    ':physical_measures' => $merge('physical_measures', $rec['physical_measures'] ?? []),
                    ':development_assessment' => $merge('development_assessment', $rec['development_assessment'] ?? []),
                    ':physical_exam' => $merge('physical_exam', $rec['physical_exam'] ?? []),
                    ':neurological' => $merge('neurological', $rec['neurological'] ?? []),
                    ':recommendation' => $rec['recommendation'] ?? null,
                    ':id' => $existing['id'],
                ]);
                $id = (int) $existing['id'];
            } else {
                $ins = $pdo->prepare('INSERT INTO health_data_external (
                        exam_date, measurement_date, academic_year, doctor_name, student_id, prefix_th, first_name, last_name_th,
                        child_grop, classroom, birth_date, age_year, age_month, age_day, nickname,
                        vital_signs, behavior, physical_measures, development_assessment, physical_exam, neurological,
                        recommendation, check_round, recorded_by, is_doctor_checked, created_at, updated_at
                    ) VALUES (
                        :exam_date, :measurement_date, :academic_year, NULL, :student_id, :prefix_th, :first_name, :last_name_th,
                        :child_grop, :classroom, :birth_date, :age_year, :age_month, :age_day, :nickname,
                        :vital_signs, :behavior, :physical_measures, :development_assessment, :physical_exam, :neurological,
                        :recommendation, :check_round, :recorded_by, FALSE, NOW(), NOW()
                    ) RETURNING id');
                $ins->execute([
                    ':exam_date' => $examDate, ':measurement_date' => $measureDate, ':academic_year' => $year,
                    ':student_id' => $child['studentid'], ':prefix_th' => $child['prefix_th'] ?? '', ':first_name' => $child['firstname_th'] ?? '',
                    ':last_name_th' => $child['lastname_th'] ?? '', ':child_grop' => $child['child_group'] ?? '', ':classroom' => $child['classroom'] ?? '',
                    ':birth_date' => $birth,
                    ':age_year' => $intOrNull($rec['age_year'] ?? null), ':age_month' => $intOrNull($rec['age_month'] ?? null), ':age_day' => $intOrNull($rec['age_day'] ?? null),
                    ':nickname' => $child['nickname'] ?? '',
                    ':vital_signs' => $json($rec['vital_signs'] ?? []), ':behavior' => $json($rec['behavior'] ?? []),
                    ':physical_measures' => $json($rec['physical_measures'] ?? []), ':development_assessment' => $json($rec['development_assessment'] ?? []),
                    ':physical_exam' => $json($rec['physical_exam'] ?? []), ':neurological' => $json($rec['neurological'] ?? []),
                    ':recommendation' => $rec['recommendation'] ?? null,
                    ':check_round' => $roundNo, ':recorded_by' => $recordedBy,
                ]);
                $id = (int) $ins->fetchColumn();
            }
            $pdo->commit();
            $saved[] = ['student_id' => $sid, 'id' => $id];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = ['student_id' => $sid, 'message' => $e->getMessage()];
        }
    }

    echo json_encode([
        'status' => $saved ? 'success' : 'error',
        'message' => $saved ? '' : ($errors[0]['message'] ?? 'บันทึกไม่สำเร็จ'),
        'saved' => $saved, 'errors' => $errors, 'total' => count($records),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
