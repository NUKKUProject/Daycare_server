<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');
$pdo = getDatabaseConnection();

$examDate = $_GET['exam_date'] ?? null;

if (!$examDate) {
    echo json_encode(['doctors' => []]);
    exit;
}

// ดงรายชื่อแพทย์ทีู่้ตรวจในวันที่เลือก (ไม่้ำกัน เรียงตามตัวอักร)
$stmt = $pdo->prepare("
    SELECT DISTINCT doctor_name
    FROM health_data_external
    WHERE exam_date = :exam_date AND doctor_name IS NOT NULL AND doctor_name != ''
    ORDER BY doctor_name ASC
");
$stmt->execute([':exam_date' => $examDate]);
$doctors = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['doctors' => $doctors]);