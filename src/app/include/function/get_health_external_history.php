<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $studentid = $_GET['studentid'] ?? null;
    if (!$studentid) {
        throw new Exception('ไม่พบรหัสนักเรียน');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // ผู้ปกครอง (student) เห็นเฉพาะรายการที่แพทย์ตรวจแล้ว (มีชื่อแพทย์)
    $doctorFilter = (($_SESSION['role'] ?? '') === 'student')
        ? "AND doctor_name IS NOT NULL AND TRIM(doctor_name) <> ''"
        : '';

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT id, academic_year, exam_date, doctor_name, check_round,
               vital_signs, behavior, physical_measures,
               development_assessment, physical_exam, neurological,
               recommendation, measurement_date, created_at
        FROM health_data_external
        WHERE student_id = :student_id
          $doctorFilter
        ORDER BY exam_date DESC, check_round DESC
    ");
    $stmt->execute(['student_id' => $studentid]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $records], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
