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
        ? "AND h.doctor_name IS NOT NULL AND TRIM(h.doctor_name) <> ''"
        : '';

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT h.id, h.academic_year, h.doctor_name, h.age_year, h.age_month, h.age_day,
               h.total_teeth, h.decayed_teeth, h.oral_components, h.teeth_status,
               h.missing_teeth_detail, h.decayed_teeth_positions, h.treatments,
               h.other_treatment_detail, h.urgency, h.updated_at, h.created_at, r.round_no
        FROM health_tooth_external h
        LEFT JOIN tooth_exam_rounds r ON r.id = h.round_id
        WHERE h.student_id = :student_id
          $doctorFilter
        ORDER BY h.academic_year DESC, r.round_no DESC NULLS LAST, h.created_at DESC
    ");
    $stmt->execute(['student_id' => $studentid]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $records], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
