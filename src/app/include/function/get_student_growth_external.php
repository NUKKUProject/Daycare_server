<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();

    $studentId = $_GET['student_id'] ?? null;
    $academicYear = $_GET['academic_year'] ?? null;

    if (!$studentId) {
        throw new Exception('Missing student_id parameter');
    }

    $sql = "
        SELECT h.id, h.academic_year, h.student_id, h.exam_date, h.doctor_name,
               h.check_round, h.recorded_by, h.is_doctor_checked,
               h.vital_signs, h.behavior, h.physical_measures, 
               h.development_assessment, h.physical_exam, h.neurological,
               h.recommendation, h.created_at, h.updated_at,
               c.prefix_th, c.firstname_th AS first_name_th, c.lastname_th AS last_name_th,
               c.nickname, c.child_group, c.classroom, c.birthday, c.sex
        FROM health_data_external h
        JOIN children c ON h.student_id = c.studentid
        WHERE h.student_id = :student_id
    ";

    $params = [':student_id' => $studentId];

    if ($academicYear) {
        $sql .= " AND h.academic_year = :academic_year";
        $params[':academic_year'] = $academicYear;
    }

    $sql .= " ORDER BY h.exam_date ASC, h.check_round ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลง JSON fields และคำนวณอายุ
    foreach ($records as &$record) {
        $record['physical_measures'] = !empty($record['physical_measures'])
            ? json_decode($record['physical_measures'], true) : null;
        $record['development_assessment'] = !empty($record['development_assessment'])
            ? json_decode($record['development_assessment'], true) : null;
        $record['vital_signs'] = !empty($record['vital_signs'])
            ? json_decode($record['vital_signs'], true) : null;
        $record['physical_exam'] = !empty($record['physical_exam'])
            ? json_decode($record['physical_exam'], true) : null;
        $record['neurological'] = !empty($record['neurological'])
            ? json_decode($record['neurological'], true) : null;
        $record['behavior'] = !empty($record['behavior'])
            ? json_decode($record['behavior'], true) : null;

        // คำนวณอายุ ณ วันที่ตรวจ
        if (!empty($record['birthday']) && !empty($record['exam_date'])) {
            $birth = new DateTime($record['birthday']);
            $exam = new DateTime($record['exam_date']);
            $age = $birth->diff($exam);
            $record['age_year'] = $age->y;
            $record['age_month'] = $age->m;
            $record['age_day'] = $age->d;
        } else {
            $record['age_year'] = null;
            $record['age_month'] = null;
            $record['age_day'] = null;
        }

        // คำนวณ BMI
        $measures = $record['physical_measures'];
        $weight = $measures ? floatval($measures['weight'] ?? 0) : 0;
        $height = $measures ? floatval($measures['height'] ?? 0) : 0;
        if ($weight > 0 && $height > 0) {
            $record['bmi'] = round($weight / (($height / 100) * ($height / 100)), 1);
        } else {
            $record['bmi'] = null;
        }
    }
    unset($record);

    $currentRecord = !empty($records) ? end($records) : null;

    echo json_encode([
        'status' => 'success',
        'current_record' => $currentRecord,
        'all_records' => $records
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
