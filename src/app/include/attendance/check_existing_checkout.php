<?php
require_once(__DIR__ . '/../../../config/database.php');

header('Content-Type: application/json');

$pdo = getDatabaseConnection();

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่ได้รับรหัสนักเรียน'
    ]);
    exit;
}

try {
    // ตรวจสอบว่ามีการเช็คเอาท์ในวันนี้หรือไม่
    $stmt = $pdo->prepare("
        SELECT id, check_out_time, status_checkout, leave_note 
        FROM attendance 
        WHERE student_id = :student_id 
        AND DATE(check_date) = CURRENT_DATE
        AND check_out_time IS NOT NULL
    ");
    $stmt->execute(['student_id' => $student_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo json_encode([
            'status' => 'exists',
            'data' => $existing
        ]);
    } else {
        echo json_encode([
            'status' => 'not_exists',
            'data' => null
        ]);
    }
} catch (Exception $e) {
    error_log("Error in check_existing_checkout.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
