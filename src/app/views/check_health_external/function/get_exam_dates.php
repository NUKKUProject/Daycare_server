<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');
$pdo = getDatabaseConnection();

// ดึงรายการวันที่ที่มีการตรวจจริงจากฐานข้อมูล (ไม่้ำกัน เรียงจากล่าสุดไปเก่าสุด)
$stmt = $pdo->prepare("
    SELECT DISTINCT exam_date
    FROM health_data_external
    WHERE exam_date IS NOT NULL
    ORDER BY exam_date DESC
");
$stmt->execute();
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['dates' => $dates]);