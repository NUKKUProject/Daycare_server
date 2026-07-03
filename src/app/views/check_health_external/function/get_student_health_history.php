<?php
require_once(__DIR__ . '../../../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    $studentId = $_GET['student_id'] ?? null;
    $academicYear = $_GET['academic_year'] ?? null;

    if (!$studentId || !$academicYear) {
        throw new Exception("Missing required parameters: student_id and academic_year");
    }

    $sql = "SELECT h.id, h.academic_year, h.student_id, h.exam_date, h.doctor_name,
           h.check_round, h.recorded_by, h.is_doctor_checked,
           h.vital_signs, h.behavior, h.physical_measures, 
           h.development_assessment, h.physical_exam, h.neurological,
           h.recommendation, h.created_at, h.updated_at,
           c.prefix_th, c.firstname_th AS first_name_th, c.lastname_th AS last_name_th,
           c.nickname, c.child_group, c.classroom
    FROM health_data_external h
    JOIN children c ON h.student_id = c.studentid
    WHERE h.student_id = :student_id 
    AND h.academic_year = :academic_year
    ORDER BY h.check_round ASC, h.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $studentId,
        ':academic_year' => $academicYear
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>