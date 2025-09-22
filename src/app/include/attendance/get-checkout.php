<?php
require_once(__DIR__ . '/../../../config/database.php');// เชื่อมต่อไฟล์ database.php
$pdo = getDatabaseConnection();

try {
    // ดึงข้อมูลการเช็คชื่อทั้งหมดในวันนี้
    $stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.student_id,
        COALESCE(a.prefix_th, '') AS prefix_th, 
        COALESCE(a.firstname_th, '') AS firstname_th, 
        COALESCE(a.lastname_th, '') AS lastname_th,
        COALESCE(a.classroom, 'ไม่ระบุห้องเรียน') AS classroom,
        COALESCE(TO_CHAR(c.check_date, 'YYYY-MM-DD HH24:MI:SS'), 'ยังไม่มีการบันทึก') AS check_date,
        COALESCE(TO_CHAR(c.check_out_time, 'HH24:MI:SS'), 'ยังไม่มีการบันทึก') AS check_out_time,
        CASE 
            WHEN c.status_checkout IS NULL THEN 'ยังไม่มีการบันทึก'
            WHEN c.status_checkout = 'checked_out' THEN 'กลับบ้านแล้ว'
            WHEN c.status_checkout = 'no_checked_out' THEN 'ยังไม่กลับบ้าน'
            ELSE c.status_checkout 
        END AS status_checkout,
        CASE 
            WHEN c.picked_up_by = 'father' THEN 'รับโดยบิดา'
            WHEN c.picked_up_by = 'mother' THEN 'รับโดยมารดา'
            WHEN c.picked_up_by = 'relative' THEN 'รับโดยญาติ'
            WHEN c.picked_up_by = 'other' THEN 'รับโดยบุคคลอื่น'
            ELSE COALESCE(c.picked_up_by, 'ไม่ระบุ')
        END AS picked_up_by,
        COALESCE(c.picked_up_detail, '') AS picked_up_detail
    FROM 
        attendance c
    LEFT JOIN 
        children a ON c.student_id = a.studentid
    WHERE 
        DATE(c.check_date) = CURRENT_DATE
    ORDER BY 
        c.check_out_time DESC NULLS LAST,
        c.id DESC
    ");

    $stmt->execute();
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($attendanceRecords) {
        // แปลงข้อมูลเวลาให้อยู่ในรูปแบบที่ต้องการ
        foreach ($attendanceRecords as &$record) {
            if ($record['check_out_time'] !== 'ยังไม่มีการบันทึก') {
                $timestamp = strtotime($record['check_out_time']);
                $record['timestamp'] = date('Y-m-d H:i:s', $timestamp);
            } else {
                $record['timestamp'] = $record['check_out_time'];
            }
        }
        echo json_encode($attendanceRecords, JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['message' => 'ยังไม่มีการบันทึกในวันนี้']);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'error' => true, 
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?>