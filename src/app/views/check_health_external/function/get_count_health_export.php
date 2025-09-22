<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');
$pdo = getDatabaseConnection();

$academicYear = $_POST['academic_year'] ?? null;
$doctorName = $_POST['doctor'] ?? null;

if (!$academicYear) {
    echo json_encode(['count' => 0]);
    exit;
}

if ($doctorName === 'all') {
    // กรณี doctorName = 'all' ดึงข้อมูลทั้งหมดที่ปีตรงกับเงื่อนไข
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM health_data_external
        WHERE academic_year = :year
    ");
    $stmt->execute([':year' => $academicYear]);
} else {
    // กรณี doctorName ไม่ใช่ 'all' กรองตามชื่อแพทย์
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM health_data_external
        WHERE academic_year = :year AND doctor_name = :doctor
    ");
    $stmt->execute([
        ':year' => $academicYear,
        ':doctor' => $doctorName
    ]);
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode(['count' => (int)$row['total']]);