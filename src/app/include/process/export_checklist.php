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

// แก้ไข query ให้จัดการกับข้อมูล JSON
$query = "SELECT 
    c.studentid,
    c.prefix_th,
    c.firstname_th,
    c.lastname_th,
    c.nickname,
    c.child_group,
    c.classroom,
    h.created_at as check_date,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.hair::jsonb->'checked')
    ) as hair,
    h.hair_reason,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.eye::jsonb->'checked')
    ) as eye,
    h.eye_condition,
    h.eye_reason,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.mouth::jsonb->'checked')
    ) as mouth,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.teeth::jsonb->'checked')
    ) as teeth,
    h.teeth_count,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.ears::jsonb->'checked')
    ) as ears,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.nose::jsonb->'checked')
    ) as nose,
    h.nose_condition,
    h.nose_reason,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.nails::jsonb->'checked')
    ) as nails,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.skin::jsonb->'checked')
    ) as skin,
    h.skin_wound_detail,
    h.skin_rash_detail,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.hands_feet::jsonb->'checked')
    ) as hands_feet,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.arms_legs::jsonb->'checked')
    ) as arms_legs,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.body::jsonb->'checked')
    ) as body,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.symptoms::jsonb->'checked')
    ) as symptoms,
    h.fever_temp,
    h.cough_type,
    h.symptoms_reason,
    (
        SELECT string_agg(value, ', ')
        FROM jsonb_array_elements_text(h.medicine::jsonb->'checked')
    ) as medicine,
    h.medicine_detail,
    h.medicine_reason,
    h.illness_reason,
    h.accident_reason,
    h.teacher_note,
    h.teacher_signature
FROM children c
LEFT JOIN health_data h ON c.studentid = h.student_id";

$params = [];
$dateCondition = "";

// กำหนดเงื่อนไขตามประเภทการ export
switch($exportType) {
    case 'daily':
        $date = $_GET['date'] ?? date('Y-m-d');
        $dateCondition = " AND DATE(h.created_at) = ?";
        $params[] = $date;
        $filename = "checklist_daily_" . $date;
        break;

    case 'monthly':
        $month = $_GET['month'] ?? date('Y-m');
        $dateCondition = " AND TO_CHAR(h.created_at, 'YYYY-MM') = ?";
        $params[] = $month;
        $filename = "checklist_monthly_" . $month;
        break;

    case 'range':
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        if ($start_date && $end_date) {
            $dateCondition = " AND DATE(h.created_at) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $filename = "checklist_" . $start_date . "_to_" . $end_date;
        }
        break;
}

$query .= $dateCondition . " WHERE 1=1";

if ($child_group) {
    $query .= " AND c.child_group = ?";
    $params[] = $child_group;
}

if ($classroom) {
    $query .= " AND c.classroom = ?";
    $params[] = $classroom;
}

$query .= " ORDER BY c.child_group, c.classroom, c.studentid, h.created_at";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename .= ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // แก้ไขหัวข้อคอลัมน์ให้ครบถ้วน
    fputcsv($output, [
        'รหัสนักเรียน',
        'คำนำหน้า',
        'ชื่อ',
        'นามสกุล',
        'ชื่อเล่น',
        'กลุ่มเรียน',
        'ห้องเรียน',
        'วันที่ตรวจ',
        'ผม/ศีรษะ',
        'หมายเหตุผม/ศีรษะ',
        'ตา',
        'ลักษณะขี้ตา',
        'หมายเหตุตา',
        'ปากและคอ',
        'ฟัน',
        'จำนวนฟันผุ',
        'หู',
        'จมูก',
        'ลักษณะน้ำมูก',
        'หมายเหตุจมูก',
        'เล็บมือ',
        'ผิวหนัง',
        'รายละเอียดแผล',
        'รายละเอียดผื่น',
        'มือและเท้า',
        'แขนและขา',
        'ลำตัวและหลัง',
        'อาการผิดปกติ',
        'อุณหภูมิ',
        'ลักษณะการไอ',
        'หมายเหตุอาการ',
        'การใช้ยา',
        'รายละเอียดยา',
        'หมายเหตุยา',
        'การเจ็บป่วย',
        'อุบัติเหตุ/แมลงกัดต่อย',
        'บันทึกของครู',
        'ครูผู้ตรวจ'
    ], ',', '"', "\\");

    foreach ($results as $row) {
        $checkDate = !empty($row['check_date']) ? date('Y-m-d', strtotime($row['check_date'])) : '-';
        
        // แปลงค่า null เป็น '-'
        $exportRow = array_map(function($value) {
            return $value ?? '-';
        }, [
            $row['studentid'],
            $row['prefix_th'],
            $row['firstname_th'],
            $row['lastname_th'],
            $row['nickname'],
            $row['child_group'],
            $row['classroom'],
            $checkDate,
            $row['hair'],
            $row['hair_reason'],
            $row['eye'],
            $row['eye_condition'],
            $row['eye_reason'],
            $row['mouth'],
            $row['teeth'],
            $row['teeth_count'],
            $row['ears'],
            $row['nose'],
            $row['nose_condition'],
            $row['nose_reason'],
            $row['nails'],
            $row['skin'],
            $row['skin_wound_detail'],
            $row['skin_rash_detail'],
            $row['hands_feet'],
            $row['arms_legs'],
            $row['body'],
            $row['symptoms'],
            $row['fever_temp'],
            $row['cough_type'],
            $row['symptoms_reason'],
            $row['medicine'],
            $row['medicine_detail'],
            $row['medicine_reason'],
            $row['illness_reason'],
            $row['accident_reason'],
            $row['teacher_note'],
            $row['teacher_signature']
        ]);

        fputcsv($output, $exportRow, ',', '"', "\\");
    }

    fclose($output);
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

exit;
?> 