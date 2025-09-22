<?php
require_once(__DIR__ . '/../../../config/database.php'); // เชื่อมต่อไฟล์ database.php
$pdo = getDatabaseConnection();

try {
    // ดึงข้อมูลการเช็คชื่อทั้งหมดในวันนี้
    $stmt = $pdo->prepare("
    SELECT 
        a.id,
        a.student_id,
        c.prefix_th,
        c.firstname_th,
        c.lastname_th,
        c.nickname,
        c.classroom,
        TO_CHAR(a.check_date, 'YYYY-MM-DD HH24:MI:SS') AS timestamp,
        CASE 
            WHEN a.status = 'present' THEN 'มาเรียน'
            WHEN a.status = 'late' THEN 'มาสาย'
            WHEN a.status = 'absent' THEN 'ขาดเรียน'
            ELSE a.status
        END AS status
    FROM 
        attendance a
    LEFT JOIN 
        children c ON a.student_id = c.studentid
    WHERE 
        DATE(a.check_date) = CURRENT_DATE
    ORDER BY 
        a.check_date DESC
    ");

    $stmt->execute();
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($attendanceRecords) {
        echo json_encode($attendanceRecords);
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