<?php
require_once(__DIR__ . '../../../../../config/database.php');
require_once __DIR__ . '/tooth_exam_helpers.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = getDatabaseConnection();

// นับผลตรวจในรอบที่เลือก (1 เด็ก 1 รายการ)
$roundId = (int) ($_POST['round_id'] ?? 0);
$doctorName = $_POST['doctor'] ?? 'all';
$round = $roundId ? tooth_get_round($pdo, $roundId) : null;

if (!$round) {
    echo json_encode(['count' => 0]);
    exit;
}

$params = [':year' => $round['academic_year'], ':round' => $round['id']];
if ($doctorName === 'all' || $doctorName === '') {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM (' . tooth_effective_sql(true) . ') t');
    $stmt->execute($params);
} else {
    // กรองตามชื่อแพทย์ (นับเฉพาะผลที่แพทย์ตรวจ)
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM (' . tooth_effective_sql(true, true) . ') t WHERE doctor_name = :doctor');
    $stmt->execute($params + [':doctor' => $doctorName]);
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode(['count' => (int) $row['total']]);
