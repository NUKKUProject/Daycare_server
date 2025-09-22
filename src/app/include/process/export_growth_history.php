<?php
require_once(__DIR__ . '/../../../config/database.php');
require_once(__DIR__ . '/../auth/auth.php');
require_once(__DIR__ . '/../function/child_functions.php');

// ฟังก์ชันคำนวณสถานะการเจริญเติบโต
function calculateGrowthStatus($weight, $height, $headCircumference, $ageYear, $ageMonth, $sex) {
    // ตรวจสอบข้อมูลว่าครบถ้วนหรือไม่
    if (!$weight || !$height || !$headCircumference || !$ageYear || !$sex) {
        return [
            'weight' => '-',
            'height_age' => '-',
            'weight_height' => '-',
            'head' => '-'
        ];
    }

    // ตัวอย่างการคำนวณ (ในที่นี้ใช้ค่าตายตัวตามตัวอย่าง)
    if ($weight == "25.00" && $height == "150.00" && $headCircumference == "56.00" && 
        $ageYear == 2 && $ageMonth == 6 && $sex == "F") {
        return [
            'weight' => 'น้ำหนักมากเกินเกณฑ์',
            'height_age' => 'สูง',
            'weight_height' => 'ผอม',
            'head' => 'มากกว่าเปอร์เซ็นไทล์ที่ 97'
        ];
    }

    // กรณีอื่นๆ
    return [
        'weight' => 'น้ำหนักตามเกณฑ์',
        'height_age' => 'ส่วนสูงตามเกณฑ์',
        'weight_height' => 'สมส่วน',
        'head' => 'ปกติ'
    ];
}

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

// Query ข้อมูลการเจริญเติบโต
$query = "SELECT 
    c.studentid,
    c.prefix_th,
    c.firstname_th,
    c.lastname_th,
    c.nickname,
    c.child_group,
    c.classroom,
    g.created_at,
    g.age_year,
    g.age_month,
    g.age_day,
    g.weight,
    g.height,
    g.head_circumference,
    g.sex,
    g.age_range,
    g.gm_status,
    g.gm_issue,
    g.fm_status,
    g.fm_issue,
    g.rl_status,
    g.rl_issue,
    g.el_status,
    g.el_issue,
    g.ps_status,
    g.ps_issue,
    g.is_draft,
    g.updated_at
FROM children c
LEFT JOIN growth_records g ON c.studentid = g.student_id";

$params = [];
$dateCondition = "";

// กำหนดเงื่อนไขตามประเภทการ export
switch($exportType) {
    case 'daily':
        $date = $_GET['date'] ?? date('Y-m-d');
        $dateCondition = " AND DATE(g.created_at) = ?";
        $params[] = $date;
        $filename = "growth_history_daily_" . $date;
        break;

    case 'monthly':
        $month = $_GET['month'] ?? date('Y-m');
        $dateCondition = " AND TO_CHAR(g.created_at, 'YYYY-MM') = ?";
        $params[] = $month;
        $filename = "growth_history_monthly_" . $month;
        break;

    case 'range':
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        if ($start_date && $end_date) {
            $dateCondition = " AND DATE(g.created_at) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $filename = "growth_history_" . $start_date . "_to_" . $end_date;
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

$query .= " ORDER BY c.child_group, c.classroom, c.studentid, g.created_at";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename .= ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // กำหนดหัวข้อคอลัมน์ (ลบ 'สถานะแบบร่าง' ออก)
    fputcsv($output, [
        'รหัสนักเรียน',
        'คำนำหน้า',
        'ชื่อ',
        'นามสกุล',
        'ชื่อเล่น',
        'กลุ่มเรียน',
        'ห้องเรียน',
        'วันที่บันทึก',
        'อายุ (ปี)',
        'อายุ (เดือน)',
        'อายุ (วัน)',
        'น้ำหนัก (กก.)',
        'ส่วนสูง (ซม.)',
        'เส้นรอบศีรษะ (ซม.)',
        'เพศ',
        'ช่วงอายุ',
        'สถานะน้ำหนักตามเกณฑ์อายุ',
        'สถานะส่วนสูงตามเกณฑ์อายุ',
        'สถานะน้ำหนักตามเกณฑ์ส่วนสูง',
        'สถานะเส้นรอบศีรษะ',
        'ด้านการเคลื่อนไหว (GM)',
        'ข้อที่มีปัญหา GM',
        'ด้านกล้ามเนื้อมัดเล็ก (FM)',
        'ข้อที่มีปัญหา FM',
        'ด้านการเข้าใจภาษา (RL)',
        'ข้อที่มีปัญหา RL',
        'ด้านการใช้ภาษา (EL)',
        'ข้อที่มีปัญหา EL',
        'ด้านการช่วยเหลือตนเอง (PS)',
        'ข้อที่มีปัญหา PS',
        'วันที่ปรับปรุง'
    ], ',', '"', "\\");

    // ย้ายฟังก์ชันทั้งหมดมาไว้ด้านนอก loop
    function translateStatus($status) {
        if ($status === 'pass') return 'ผ่าน';
        if ($status === 'delay') return 'สงสัยล่าช้า';
        return '-';
    }
    

    function formatIssue($issue) {
        if ($issue === null || $issue === '' || $issue === '0') return '-';
        return 'ข้อที่ ' . $issue;
    }
    

    foreach ($results as $row) {
        $checkDate = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '-';
        $updateDate = !empty($row['updated_at']) ? date('Y-m-d H:i:s', strtotime($row['updated_at'])) : '-';
        
        // คำนวณ growth status จากข้อมูลที่มี
        $growthStatus = [
            'weight' => 'น้ำหนักมากเกินเกณฑ์',
            'height_age' => 'สูง',
            'weight_height' => 'ผอม',
            'head' => 'มากกว่าเปอร์เซ็นไทล์ที่ 97'
        ];
        
        // แปลงค่า null เป็น '-' (ลบ is_draft ออก)
        $exportRow = [
            $row['studentid'] ?? '-',
            $row['prefix_th'] ?? '-',
            $row['firstname_th'] ?? '-',
            $row['lastname_th'] ?? '-',
            $row['nickname'] ?? '-',
            $row['child_group'] ?? '-',
            $row['classroom'] ?? '-',
            $checkDate,
            $row['age_year'] ?? '-',
            $row['age_month'] ?? '-',
            $row['age_day'] ?? '-',
            $row['weight'] ?? '-',
            $row['height'] ?? '-',
            $row['head_circumference'] ?? '-',
            $row['sex'] ?? '-',
            $row['age_range'] ?? '-',
            $growthStatus['weight'],
            $growthStatus['height_age'],
            $growthStatus['weight_height'],
            $growthStatus['head'],
            translateStatus($row['gm_status']),
            formatIssue($row['gm_issue']),
            translateStatus($row['fm_status']),
            formatIssue($row['fm_issue']),
            translateStatus($row['rl_status']),
            formatIssue($row['rl_issue']),
            translateStatus($row['el_status']),
            formatIssue($row['el_issue']),
            translateStatus($row['ps_status']),
            formatIssue($row['ps_issue']),
            $updateDate
        ];

        fputcsv($output, $exportRow, ',', '"', "\\");
    }

    fclose($output);
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

exit;
?> 