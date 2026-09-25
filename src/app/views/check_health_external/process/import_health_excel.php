<?php
require_once(__DIR__ . '/../../../../vendor/autoload.php');
require_once(__DIR__ . '/../../../../config/database.php');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

header('Content-Type: application/json; charset=utf-8');

try {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบก่อนทำการ Import');
    }

    // ตรวจสอบไฟล์
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('กรุณาเลือกไฟล์ที่จะ Import');
    }

    $file = $_FILES['import_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls'])) {
        throw new Exception('รองรับเฉพาะไฟล์ .xlsx และ .xls เท่านั้น');
    }

    $pdo = getDatabaseConnection();
    $recordedBy = $_SESSION['user_id'];

    // โหลดไฟล์ Excel
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet = $spreadsheet->getSheetByName('ข้อมูล');
    if (!$sheet) {
        $sheet = $spreadsheet->getActiveSheet();
    }

    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    if ($highestRow < 2) {
        throw new Exception('ไม่พบข้อมูลในไฟล์ (ต้องมีอย่างน้อย 1 แถวข้อมูล)');
    }

    // เริ่ม transaction
    $pdo->beginTransaction();

    $successCount = 0;
    $errorRows = [];
    $totalRows = $highestRow - 1;

    // Map header names to column indices (for validation)
    $headerMap = [];
    for ($col = 1; $col <= Coordinate::columnIndexFromString($highestCol); $col++) {
        $headerName = trim($sheet->getCellByColumnAndRow($col, 1)->getCalculatedValue() ?? '');
        if ($headerName) {
            $headerMap[$headerName] = $col;
        }
    }

    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            $getCell = function ($colIdx) use ($sheet, $row) {
                $val = $sheet->getCellByColumnAndRow($colIdx, $row)->getCalculatedValue();
                return $val !== null ? trim((string)$val) : '';
            };

            // ── Required fields ──
            $studentId = $getCell(1);   // A: รหัสนักเรียน
            $examDate  = $getCell(4);   // D: วันที่ตรวจ
            $academicYear = $getCell(5); // E: ปีการศึกษา

            if (empty($studentId)) {
                $errorRows[] = "แถวที่ {$row}: ไม่พบรหัสนักเรียน";
                continue;
            }
            if (empty($examDate)) {
                $errorRows[] = "แถวที่ {$row} (รหัส {$studentId}): ไม่พบวันที่ตรวจ";
                continue;
            }
            if (empty($academicYear)) {
                $errorRows[] = "แถวที่ {$row} (รหัส {$studentId}): ไม่พบปีการศึกษา";
                continue;
            }

            // ตรวจสอบ student_id ในตาราง children
            $stmt = $pdo->prepare("SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, child_group, classroom, birthday FROM children WHERE studentid = :sid");
            $stmt->execute([':sid' => $studentId]);
            $child = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$child) {
                $errorRows[] = "แถวที่ {$row} (รหัส {$studentId}): ไม่พบรหัสนักเรียนในระบบ";
                continue;
            }

            // ตรวจสอบวันที่
            $examDateObj = date_create($examDate);
            if (!$examDateObj) {
                // ลอง format อื่น
                $examDateObj = date_create_from_format('Y-m-d', $examDate);
                if (!$examDateObj) {
                    $examDateObj = date_create_from_format('d/m/Y', $examDate);
                }
            }
            if (!$examDateObj) {
                $errorRows[] = "แถวที่ {$row} (รหัส {$studentId}): รูปแบบวันที่ไม่ถูกต้อง (ต้องการ YYYY-MM-DD)";
                continue;
            }
            $examDateFormatted = $examDateObj->format('Y-m-d');

            // ── Auto check_round ──
            $stmt = $pdo->prepare("SELECT MAX(check_round) as max_round FROM health_data_external WHERE student_id = :sid AND academic_year = :year");
            $stmt->execute([':sid' => $studentId, ':year' => $academicYear]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $checkRound = ($result && $result['max_round']) ? (int)$result['max_round'] + 1 : 1;

            // ── Map fields ──

            // Vital signs
            $vitalSigns = [
                'temperature'  => $getCell(6) !== '' ? $getCell(6) : null,
                'bp'           => $getCell(7) !== '' ? $getCell(7) : null,
                'bp_date'      => null,
            ];

            // Physical measures
            $physicalMeasures = [
                'height'           => $getCell(8) !== '' ? $getCell(8) : null,
                'weight'           => $getCell(9) !== '' ? $getCell(9) : null,
                'head_circ'        => $getCell(10) !== '' ? $getCell(10) : null,
                'weight_for_age'   => $getCell(11) !== '' ? [$getCell(11)] : [],
                'height_for_age'   => $getCell(12) !== '' ? [$getCell(12)] : [],
                'weight_for_height'=> $getCell(13) !== '' ? [$getCell(13)] : [],
                'head_percentile'  => $getCell(14) !== '' ? [$getCell(14)] : [],
            ];

            // Behavior
            $behaviorStatus = $getCell(15);
            $behaviorDetail = $getCell(16);
            $behavior = [
                'status' => $behaviorStatus === 'has' ? 'has' : ($behaviorStatus === 'none' ? 'none' : null),
                'detail' => $behaviorDetail !== '' ? $behaviorDetail : null,
            ];

            // Development assessment
            $devMap = function ($status, $score) {
                $s = '';
                if ($status === 'pass') $s = 'pass';
                elseif ($status === 'delay') $s = 'delay';
                return [
                    'status' => $s,
                    'score'  => $score !== '' ? $score : '',
                ];
            };
            $development = [
                'gm' => $devMap($getCell(17), $getCell(18)),
                'fm' => $devMap($getCell(19), $getCell(20)),
                'rl' => $devMap($getCell(21), $getCell(22)),
                'el' => $devMap($getCell(23), $getCell(24)),
                'ps' => $devMap($getCell(25), $getCell(26)),
            ];

            // Physical exam systems mapping
            // Each system: col (status), col+1 (detail)
            $examSystems = [
                'general'      => 27,
                'skin'         => 29,
                'head'         => 31,
                'face'         => 33,
                'eyes'         => 35,
                'ears'         => 37,
                'nose'         => 39,
                'mouth'        => 41,
                'neck'         => 43,
                'breast'       => 45,
                'breathe'      => 47,
                'lungs'        => 49,
                'heart'        => 51,
                'heart_sound'  => 53,
                'pulse'        => 55,
                'abdomen'      => 57,
                'others'       => 59,
            ];

            $physicalExam = [];
            foreach ($examSystems as $key => $colIdx) {
                $statusVal = $getCell($colIdx);
                $detailVal = $getCell($colIdx + 1);
                // รองรับทั้งค่าไทย (ปกติ/ผิดปกติ) และอังกฤษ (normal/abnormal)
                $normVal = $statusVal === 'ผิดปกติ' ? 'abnormal' : ($statusVal === 'ปกติ' ? 'normal' : $statusVal);
                $physicalExam[$key] = $normVal === 'abnormal' ? ['abnormal'] : ($normVal === 'normal' ? ['normal'] : []);
                $physicalExam[$key . '_detail'] = $detailVal !== '' ? $detailVal : null;
            }

            // Neurological
            $neuroStatus = $getCell(61);
            $neuroDetail = $getCell(62);
            $movementStatus = $getCell(63);
            $movementDetail = $getCell(64);
            $neuroNorm = $neuroStatus === 'ผิดปกติ' ? 'abnormal' : ($neuroStatus === 'ปกติ' ? 'normal' : $neuroStatus);
            $moveNorm = $movementStatus === 'ผิดปกติ' ? 'abnormal' : ($movementStatus === 'ปกติ' ? 'normal' : $movementStatus);
            $neurological = [
                'neuro'           => $neuroNorm === 'abnormal' ? ['abnormal'] : ($neuroNorm === 'normal' ? ['normal'] : []),
                'neuro_detail'    => $neuroDetail !== '' ? $neuroDetail : null,
                'movement'        => $moveNorm === 'abnormal' ? ['abnormal'] : ($moveNorm === 'normal' ? ['normal'] : []),
                'movement_detail' => $movementDetail !== '' ? $movementDetail : null,
            ];

            // Recommendation
            $recommendation = $getCell(65) !== '' ? $getCell(65) : null;

            // ── Insert ──
            $sql = "INSERT INTO health_data_external (
                exam_date, academic_year, doctor_name,
                student_id, prefix_th, first_name, last_name_th,
                child_grop, classroom,
                birth_date, age_year, age_month, age_day, nickname,
                vital_signs, behavior, physical_measures,
                development_assessment, physical_exam, neurological,
                recommendation,
                check_round, recorded_by, is_doctor_checked,
                created_at, updated_at
            ) VALUES (
                :exam_date, :academic_year, :doctor_name,
                :student_id, :prefix_th, :first_name, :last_name_th,
                :child_grop, :classroom,
                :birth_date, :age_year, :age_month, :age_day, :nickname,
                :vital_signs, :behavior, :physical_measures,
                :development_assessment, :physical_exam, :neurological,
                :recommendation,
                :check_round, :recorded_by, :is_doctor_checked,
                NOW(), NOW()
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':exam_date'               => $examDateFormatted,
                ':academic_year'           => $academicYear,
                ':doctor_name'             => null,
                ':student_id'              => $child['studentid'],
                ':prefix_th'               => $child['prefix_th'] ?? '',
                ':first_name'              => $child['firstname_th'] ?? '',
                ':last_name_th'            => $child['lastname_th'] ?? '',
                ':child_grop'              => $child['child_group'] ?? '',
                ':classroom'               => $child['classroom'] ?? '',
                ':birth_date'              => $child['birthday'] ?? null,
                ':age_year'                => null,
                ':age_month'               => null,
                ':age_day'                 => null,
                ':nickname'                => $child['nickname'] ?? '',
                ':vital_signs'             => json_encode($vitalSigns, JSON_UNESCAPED_UNICODE),
                ':behavior'                => json_encode($behavior, JSON_UNESCAPED_UNICODE),
                ':physical_measures'       => json_encode($physicalMeasures, JSON_UNESCAPED_UNICODE),
                ':development_assessment'  => json_encode($development, JSON_UNESCAPED_UNICODE),
                ':physical_exam'           => json_encode($physicalExam, JSON_UNESCAPED_UNICODE),
                ':neurological'            => json_encode($neurological, JSON_UNESCAPED_UNICODE),
                ':recommendation'          => $recommendation,
                ':check_round'             => $checkRound,
                ':recorded_by'             => $recordedBy,
                ':is_doctor_checked'       => 0,
            ]);

            $successCount++;
        } catch (Exception $e) {
            $errorRows[] = "แถวที่ {$row}: {$e->getMessage()}";
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    $pdo->commit();

    // หา check_round ที่บันทึกส่วนใหญ่ (รอบล่าสุดที่เพิ่งนำเข้า)
    $roundSummary = '';
    if ($successCount > 0) {
        $stmt = $pdo->prepare("
            SELECT academic_year, check_round, COUNT(*) as cnt
            FROM health_data_external
            WHERE recorded_by = :rb AND created_at >= NOW() - INTERVAL '5 minutes'
            GROUP BY academic_year, check_round
            ORDER BY cnt DESC
            LIMIT 1
        ");
        $stmt->execute([':rb' => $recordedBy]);
        $top = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($top) {
            $roundSummary = " (ตรวจประจำปี {$top['academic_year']} รอบที่ {$top['check_round']})";
        }
    }

    echo json_encode([
        'status'       => $successCount > 0 ? 'success' : 'error',
        'message'      => "Import สำเร็จ {$successCount} จาก {$totalRows} รายการ{$roundSummary}",
        'success_count' => $successCount,
        'error_count'   => count($errorRows),
        'error_rows'    => $errorRows,
        'total_rows'    => $totalRows,
        'round_summary' => $roundSummary,
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
