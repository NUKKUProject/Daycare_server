<?php
require_once(__DIR__ . '/../../../config/database.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = getDatabaseConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['student_id']) || !isset($data['attendance_id'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'ข้อมูลไม่ถูกต้อง'
    ]);
    exit;
}

try {
// Extract fields
$student_id = $data['student_id'];
$attendance_id = isset($data['attendance_id']) ? intval($data['attendance_id']) : null;
$temperature = isset($data['temperature']) && $data['temperature'] !== '' ? floatval($data['temperature']) : null;
$has_runny_nose = !empty($data['has_runny_nose']) ? 't' : 'f';
$has_cough = !empty($data['has_cough']) ? 't' : 'f';
$has_rash = !empty($data['has_rash']) ? 't' : 'f';
$has_red_eyes = !empty($data['has_red_eyes']) ? 't' : 'f';
$other_symptoms = !empty($data['other_symptoms']) ? $data['other_symptoms'] : null;

// Determine attendance status based on current time (same logic as attendance-submit.php)
function isLate($time) {
    $parts = explode(':', $time);
    $h = intval($parts[0]);
    $m = intval($parts[1]);
    $seconds = $h * 3600 + $m * 60;
    return $seconds > (8 * 3600 + 30 * 60);
}
$current_time = date('H:i');
$status = isLate($current_time) ? 'late' : 'present';

if ($attendance_id) {
    // Try to update existing attendance record with health data
    $update_sql = "
        UPDATE attendance SET
            temperature = :temperature,
            has_runny_nose = :has_runny_nose::boolean,
            has_cough = :has_cough::boolean,
            has_rash = :has_rash::boolean,
            has_red_eyes = :has_red_eyes::boolean,
            other_symptoms = :other_symptoms,
            health_checked = 't'::boolean,
            status = :status,
            check_date = CURRENT_TIMESTAMP
        WHERE id = :id AND student_id = :student_id
    ";
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([
        'temperature' => $temperature,
        'has_runny_nose' => $has_runny_nose,
        'has_cough' => $has_cough,
        'has_rash' => $has_rash,
        'has_red_eyes' => $has_red_eyes,
        'other_symptoms' => $other_symptoms,
        'status' => $status,
        'id' => $attendance_id,
        'student_id' => $student_id
    ]);
    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสุขภาพและการเช็คชื่อสำเร็จ',
        'attendance_id' => $attendance_id
    ]);
} else {
    // No attendance_id provided – create a new attendance record with health data
    $insert_sql = "
        INSERT INTO attendance (
            student_id, check_date, status,
            temperature, has_runny_nose, has_cough, has_rash, has_red_eyes, other_symptoms, health_checked
        ) VALUES (
            :student_id::varchar, CURRENT_TIMESTAMP, :status::varchar,
            :temperature, :has_runny_nose::boolean, :has_cough::boolean, :has_rash::boolean, :has_red_eyes::boolean, :other_symptoms, 't'::boolean
        ) RETURNING id
    ";
    $stmt = $pdo->prepare($insert_sql);
    $stmt->execute([
        'student_id' => $student_id,
        'status' => $status,
        'temperature' => $temperature,
        'has_runny_nose' => $has_runny_nose,
        'has_cough' => $has_cough,
        'has_rash' => $has_rash,
        'has_red_eyes' => $has_red_eyes,
        'other_symptoms' => $other_symptoms
    ]);
    $new_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกการเข้าเรียนและข้อมูลสุขภาพสำเร็จ',
        'attendance_id' => $new_id
    ]);
}

} catch (Exception $e) {
    error_log("Error in attendance-health-submit.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}