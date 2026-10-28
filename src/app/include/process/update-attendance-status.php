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

// เด็กที่มีการลาซึ่งครูอนุมัติแล้วและครอบคลุมวันนี้ ให้เป็นสถานะ 'leave' (ลา) แทนการขาดเรียน
// (เฉพาะแถวที่ยังไม่มีสถานะ เด็กที่สแกนมาเรียนแล้วไม่ถูกเปลี่ยน)
$stmt = $pdo->prepare("
    UPDATE attendance a
    SET status = 'leave',
        leave_note = CASE WHEN l.leave_type = 'sick'
                          THEN 'ลาป่วย' || COALESCE(': ' || NULLIF(l.illness_name, ''), '')
                          ELSE 'ลากิจ' || COALESCE(': ' || NULLIF(l.reason, ''), '') END,
        updated_at = NOW()
    FROM leave_requests l
    WHERE l.student_id = a.student_id
      AND l.status = 'approved'
      AND CURRENT_DATE BETWEEN l.start_date AND l.end_date
      AND DATE(a.check_date) = CURRENT_DATE
      AND a.status IS NULL
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