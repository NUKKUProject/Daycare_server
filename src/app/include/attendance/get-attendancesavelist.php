<?php
require_once(__DIR__ . '/../../../config/database.php');// เชื่อมต่อไฟล์ database.php
$pdo = getDatabaseConnection();

try {
    // ดึงข้อมูลการเช็คชื่อทั้งหมดในวันนี้
    $today = date('Y-m-d');
    $stmt = $pdo->prepare('
        SELECT 
            c.id,
            c.student_id,
            c.status,
            COALESCE(a.prefix_th, \'\') AS prefix_th, 
            COALESCE(a.firstname_th, \'\') AS firstname_th, 
            COALESCE(a.lastname_th, \'\') AS lastname_th,
            COALESCE(a.classroom, \'ไม่ระบุห้องเรียน\') AS classroom,
            c.check_date AS timestamp
        FROM 
            attendance c
        LEFT JOIN 
            children a 
        ON c.student_id = a.studentid
        WHERE c.student_id IS NOT NULL
        ORDER BY 
            c.id
    ');
    $stmt->execute();

    // ส่งข้อมูลกลับในรูปแบบ JSON
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($attendanceRecords) {
        echo json_encode($attendanceRecords, JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['message' => 'No attendance records found for today']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
?>