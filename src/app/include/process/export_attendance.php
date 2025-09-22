<?php
require_once(__DIR__ . '/../../../config/database.php');
require_once(__DIR__ . '/../auth/auth.php');
require_once(__DIR__ . '/../function/child_functions.php');

// เชื่อมต่อฐานข้อมูล
try {
    $pdo = getDatabaseConnection();
} catch (PDOException $e) {
    die('ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้: ' . $e->getMessage());
}

// ตรวจสอบสิทธิ์การเข้าถึง
checkUserRole(['admin', 'teacher']);

// รับค่าจาก URL parameters
$exportType = $_GET['type'] ?? 'daily';
$child_group = $_GET['child_group'] ?? '';
$classroom = $_GET['classroom'] ?? '';
$search = $_GET['search'] ?? '';

// สร้าง query พื้นฐาน
$query = "SELECT 
    c.studentid,
    c.prefix_th,
    c.firstname_th,
    c.lastname_th,
    c.nickname,
    c.child_group,
    c.classroom,
    a.status,
    a.check_date,
    a.check_out_time,
    a.status_checkout,
    a.leave_note
FROM children c
LEFT JOIN attendance a ON c.studentid = a.student_id";

$params = [];
$dateCondition = "";

// กำหนดเงื่อนไขตามประเภทการ export
switch($exportType) {
    case 'daily':
        $date = $_GET['date'] ?? date('Y-m-d');
        $dateCondition = " AND DATE(a.check_date) = ?";
        $params[] = $date;
        $filename = "attendance_daily_" . $date;
        break;

    case 'monthly':
        $month = $_GET['month'] ?? date('Y-m');
        $dateCondition = " AND TO_CHAR(a.check_date, 'YYYY-MM') = ?";
        $params[] = $month;
        $filename = "attendance_monthly_" . $month;
        break;

    case 'range':
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        if ($start_date && $end_date) {
            $dateCondition = " AND DATE(a.check_date) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $filename = "attendance_" . $start_date . "_to_" . $end_date;
        }
        break;
}

$query .= $dateCondition . " WHERE 1=1";

// เพิ่มเงื่อนไขการกรองข้อมูล
if ($child_group) {
    $query .= " AND c.child_group = ?";
    $params[] = $child_group;
}

if ($classroom) {
    $query .= " AND c.classroom = ?";
    $params[] = $classroom;
}

$query .= " ORDER BY c.child_group, c.classroom, c.studentid, a.check_date";

try {
    // เตรียมและ execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // กำหนดชื่อไฟล์
    $filename .= ".csv";

    // ตั้งค่า headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // เปิด output stream
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // เขียนหัวข้อคอลัมน์
    fputcsv($output, [
        'รหัสนักเรียน',
        'คำนำหน้า',
        'ชื่อ',
        'นามสกุล',
        'ชื่อเล่น',
        'กลุ่มเรียน',
        'ห้องเรียน',
        'วันที่',
        'สถานะการมาเรียน',
        'เวลามาเรียน',
        'สถานะการกลับบ้าน',
        'เวลากลับบ้าน',
        'หมายเหตุ'
    ], ',', '"', "\\");

    // เขียนข้อมูล
    foreach ($results as $row) {
        $status = match($row['status']) {
            'present' => 'มาเรียน',
            'absent' => 'ไม่มาเรียน',
            'leave' => 'ลา',
            'late' => 'มาสาย',
            default => 'ไม่ระบุ'
        };

        $statusCheckout = $row['status_checkout'] === 'checked_out' ? 'กลับแล้ว' : 'ยังไม่กลับ';
        
        $checkDate = $row['check_date'] ? date('H:i', strtotime($row['check_date'])) : '-';
        $checkOutTime = $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '-';
        $attendanceDate = $row['check_date'] ? date('Y-m-d', strtotime($row['check_date'])) : '-';

        fputcsv($output, [
            $row['studentid'],
            $row['prefix_th'],
            $row['firstname_th'],
            $row['lastname_th'],
            $row['nickname'],
            $row['child_group'],
            $row['classroom'],
            $attendanceDate,
            $status,
            $checkDate,
            $statusCheckout,
            $checkOutTime,
            $row['leave_note'] ?? '-'
        ], ',', '"', "\\");
    }

    fclose($output);
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

exit;
?> 