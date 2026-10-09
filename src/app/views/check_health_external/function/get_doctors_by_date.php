<?php
require_once(__DIR__ . '../../../../../config/database.php');
require_once __DIR__ . '/health_round_helpers.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = getDatabaseConnection();

// กรองตามรอบตรวจ (round_id) หรือวันที่ตรวจ (exam_date ใช้กับ modal เดิมอีกตัว)
$roundId = (int) ($_GET['round_id'] ?? 0);
$examDate = $_GET['exam_date'] ?? null;
if ($roundId) {
    $cond = health_round_condition($pdo, $roundId);
} elseif ($examDate) {
    $cond = ['exam_date = :exam_date', [':exam_date' => $examDate]];
} else {
    $cond = null;
}

if (!$cond) {
    echo json_encode(['doctors' => []]);
    exit;
}

// รายชื่อแพทย์ที่ตรวจในรอบ/วันที่เลือก (ไม่ซ้ำกัน เรียงตามตัวอักษร)
$stmt = $pdo->prepare("
    SELECT DISTINCT doctor_name
    FROM health_data_external
    WHERE {$cond[0]} AND doctor_name IS NOT NULL AND doctor_name != ''
    ORDER BY doctor_name ASC
");
$stmt->execute($cond[1]);
$doctors = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['doctors' => $doctors], JSON_UNESCAPED_UNICODE);
