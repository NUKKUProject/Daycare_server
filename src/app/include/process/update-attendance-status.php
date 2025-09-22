<?php
require_once(__DIR__ . '/../../../config/database.php');
$pdo = getDatabaseConnection();

// สร้างเรคอร์ดเริ่มต้นสำหรับเด็กทุกคนในวันปัจจุบัน
$stmt = $pdo->prepare("
    INSERT INTO attendance (student_id, check_date, status)
    SELECT studentid, CURRENT_DATE, NULL
    FROM children
    WHERE studentid NOT IN (
        SELECT student_id FROM attendance WHERE DATE(check_date) = CURRENT_DATE
    )
");
$stmt->execute();

// อัปเดตสถานะเป็น 'absent' สำหรับเด็กที่ไม่มีการบันทึกการเช็คชื่อ
$stmt = $pdo->prepare("
    UPDATE attendance
    SET status = 'absent'
    WHERE DATE(check_date) = CURRENT_DATE
    AND status IS NULL
");
$stmt->execute();

?>