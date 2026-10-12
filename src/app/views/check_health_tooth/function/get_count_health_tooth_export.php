<?php
require_once(__DIR__ . '../../../../../config/database.php');
require_once __DIR__ . '/tooth_exam_helpers.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = getDatabaseConnection();

// นับผลตรวจตามที่เลือก: ตรวจประจำปี (ทั้งปีการศึกษา) หรือ รอบตรวจ (รอบเดียว) 1 เด็ก 1 รายการ
$doctorName = $_POST['doctor'] ?? 'all';
$scope = tooth_export_scope($pdo, $_POST);

if (!$scope) {
    echo json_encode(['count' => 0]);
    exit;
}

if ($doctorName === 'all' || $doctorName === '') {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM (' . tooth_effective_sql($scope['withRound']) . ') t');
    $stmt->execute($scope['params']);
} else {
    // กรองตามชื่อแพทย์ (นับเฉพาะผลที่แพทย์ตรวจ)
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM (' . tooth_effective_sql($scope['withRound'], true) . ') t WHERE doctor_name = :doctor');
    $stmt->execute($scope['params'] + [':doctor' => $doctorName]);
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode(['count' => (int) $row['total']]);
