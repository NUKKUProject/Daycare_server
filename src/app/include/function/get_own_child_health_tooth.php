<?php
require_once __DIR__ . '/../auth/auth.php';
checkUserRole(['student']);
require_once __DIR__ . '/../../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $studentid = $_SESSION['username'] ?? '';
    if ($studentid === '') {
        throw new Exception('ไม่พบข้อมูลผู้ใช้');
    }

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT id, academic_year, doctor_name, age_year, age_month, age_day,
               total_teeth, decayed_teeth, oral_components, teeth_status,
               missing_teeth_detail, decayed_teeth_positions, treatments,
               other_treatment_detail, urgency, updated_at, created_at
        FROM health_tooth_external
        WHERE student_id = :student_id
        ORDER BY academic_year DESC, created_at DESC
    ");
    $stmt->execute(['student_id' => $studentid]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $records], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
