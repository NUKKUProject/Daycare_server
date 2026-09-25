<?php
require_once(__DIR__ . '/../../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');

try {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบก่อนทำการบันทึก');
    }

    $json = file_get_contents('php://input');
    $records = json_decode($json, true);

    if (!is_array($records) || empty($records)) {
        throw new Exception('ไม่พบข้อมูลที่จะบันทึก');
    }

    $pdo = getDatabaseConnection();
    $recordedBy = $_SESSION['user_id'];

    $pdo->beginTransaction();

    $successCount = 0;
    $errors = [];

    foreach ($records as $rec) {
        try {
            $studentId    = $rec['student_id'] ?? '';
            $academicYear = $rec['academic_year'] ?? '';
            $examDate     = $rec['exam_date'] ?? '';
            $measurementDate = $rec['measurement_date'] ?? null;
            $checkRound   = (int)($rec['check_round'] ?? 1);
            $existingId   = $rec['existing_id'] ?? null;

            if (empty($studentId)) {
                $errors[] = ['student_id' => $studentId, 'message' => 'ไม่พบรหัสนักเรียน'];
                continue;
            }
            if (empty($academicYear)) {
                $errors[] = ['student_id' => $studentId, 'message' => 'ไม่พบปีการศึกษา'];
                continue;
            }
            if (empty($examDate)) {
                $errors[] = ['student_id' => $studentId, 'message' => 'ไม่พบวันที่ตรวจ'];
                continue;
            }

            // ดึงข้อมูลเด็ก
            $stmt = $pdo->prepare("
                SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, child_group, classroom, birthday
                FROM children WHERE studentid = :sid
            ");
            $stmt->execute([':sid' => $studentId]);
            $child = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$child) {
                $errors[] = ['student_id' => $studentId, 'message' => 'ไม่พบรหัสนักเรียนในระบบ'];
                continue;
            }

            // ถ้ามี existing_id และยังไม่มีหมอตรวจ → UPDATE
            if ($existingId) {
                $stmtChk = $pdo->prepare("SELECT is_doctor_checked, doctor_name FROM health_data_external WHERE id = :id");
                $stmtChk->execute([':id' => $existingId]);
                $chk = $stmtChk->fetch(PDO::FETCH_ASSOC);

                if ($chk && empty($chk['doctor_name'])) {
                    $sql = "UPDATE health_data_external SET
                        exam_date = :exam_date,
                        measurement_date = :measurement_date,
                        birth_date = :birth_date,
                        age_year = :age_year,
                        age_month = :age_month,
                        age_day = :age_day,
                        vital_signs = :vital_signs,
                        behavior = :behavior,
                        physical_measures = :physical_measures,
                        development_assessment = :development_assessment,
                        physical_exam = :physical_exam,
                        neurological = :neurological,
                        recommendation = :recommendation,
                        updated_at = NOW()
                    WHERE id = :id";
                    $stmtUpd = $pdo->prepare($sql);
                    $stmtUpd->execute([
                        ':exam_date'             => $examDate,
                        ':measurement_date'      => $measurementDate ?: $examDate,
                        ':birth_date'            => $rec['birth_date'] ?? $child['birthday'] ?? null,
                        ':age_year'              => $rec['age_year'] ?? null,
                        ':age_month'             => $rec['age_month'] ?? null,
                        ':age_day'               => $rec['age_day'] ?? null,
                        ':vital_signs'           => json_encode($rec['vital_signs'] ?? [], JSON_UNESCAPED_UNICODE),
                        ':behavior'              => json_encode($rec['behavior'] ?? [], JSON_UNESCAPED_UNICODE),
                        ':physical_measures'     => json_encode($rec['physical_measures'] ?? [], JSON_UNESCAPED_UNICODE),
                        ':development_assessment'=> json_encode($rec['development_assessment'] ?? [], JSON_UNESCAPED_UNICODE),
                        ':physical_exam'         => json_encode($rec['physical_exam'] ?? [], JSON_UNESCAPED_UNICODE),
                        ':neurological'          => json_encode($rec['neurological'] ?? [], JSON_UNESCAPED_UNICODE),
                        ':recommendation'        => $rec['recommendation'] ?? null,
                        ':id'                    => $existingId,
                    ]);
                    $successCount++;
                    continue;
                }
            }

            // INSERT ใหม่ (รอบใหม่)
            $sql = "INSERT INTO health_data_external (
                exam_date, measurement_date, academic_year, doctor_name,
                student_id, prefix_th, first_name, last_name_th,
                child_grop, classroom,
                birth_date, age_year, age_month, age_day, nickname,
                vital_signs, behavior, physical_measures,
                development_assessment, physical_exam, neurological,
                recommendation,
                check_round, recorded_by, is_doctor_checked,
                created_at, updated_at
            ) VALUES (
                :exam_date, :measurement_date, :academic_year, :doctor_name,
                :student_id, :prefix_th, :first_name, :last_name_th,
                :child_grop, :classroom,
                :birth_date, :age_year, :age_month, :age_day, :nickname,
                :vital_signs, :behavior, :physical_measures,
                :development_assessment, :physical_exam, :neurological,
                :recommendation,
                :check_round, :recorded_by, :is_doctor_checked,
                NOW(), NOW()
            )";

            $stmtIns = $pdo->prepare($sql);
            $stmtIns->execute([
                ':exam_date'               => $examDate,
                ':measurement_date'        => $measurementDate ?: $examDate,
                ':academic_year'           => $academicYear,
                ':doctor_name'             => null,
                ':student_id'              => $child['studentid'],
                ':prefix_th'               => $child['prefix_th'] ?? '',
                ':first_name'              => $child['firstname_th'] ?? '',
                ':last_name_th'            => $child['lastname_th'] ?? '',
                ':child_grop'              => $child['child_group'] ?? '',
                ':classroom'               => $child['classroom'] ?? '',
                ':birth_date'              => $rec['birth_date'] ?? $child['birthday'] ?? null,
                ':age_year'                => $rec['age_year'] ?? null,
                ':age_month'               => $rec['age_month'] ?? null,
                ':age_day'                 => $rec['age_day'] ?? null,
                ':nickname'                => $child['nickname'] ?? '',
                ':vital_signs'             => json_encode($rec['vital_signs'] ?? [], JSON_UNESCAPED_UNICODE),
                ':behavior'                => json_encode($rec['behavior'] ?? [], JSON_UNESCAPED_UNICODE),
                ':physical_measures'       => json_encode($rec['physical_measures'] ?? [], JSON_UNESCAPED_UNICODE),
                ':development_assessment'  => json_encode($rec['development_assessment'] ?? [], JSON_UNESCAPED_UNICODE),
                ':physical_exam'           => json_encode($rec['physical_exam'] ?? [], JSON_UNESCAPED_UNICODE),
                ':neurological'            => json_encode($rec['neurological'] ?? [], JSON_UNESCAPED_UNICODE),
                ':recommendation'          => $rec['recommendation'] ?? null,
                ':check_round'             => $checkRound,
                ':recorded_by'             => $recordedBy,
                ':is_doctor_checked'       => 0,
            ]);

            $successCount++;

        } catch (Exception $e) {
            $errors[] = ['student_id' => $rec['student_id'] ?? '', 'message' => $e->getMessage()];
        }
    }

    $pdo->commit();

    echo json_encode([
        'status'        => $successCount > 0 ? 'success' : 'error',
        'success_count' => $successCount,
        'error_count'   => count($errors),
        'errors'        => $errors,
        'total'         => count($records),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(200);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
