<?php

require_once(__DIR__ . '../../../../../config/database.php');


// กำหนดชื่อไฟล์ก่อนส่ง header
$academic_year = $_POST['academic_year'] ?? '';
$filename = "รายงานผลตรวจสุข_ปีการศึกษา_" . $academic_year . ".csv";

header('Content-Type: text/csv; charset=utf-8');
// ใช้ filename*=UTF-8''... เพื่อรองรับชื่อไฟล์ภาษาไทย
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
// เชื่อมต่อฐานข้อมูล
try {
    $pdo = getDatabaseConnection();
} catch (PDOException $e) {
    die('ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้: ' . $e->getMessage());
}


// รับค่าจาก URL parameters

$doctor = $_POST['doctor'] ?? '';


// ดึงข้อมูลจากฐานข้อมูล
if ($doctor === 'all') {
    // ไม่กรอง doctor_name
    $sql = "SELECT * FROM health_data_external 
            WHERE academic_year = :year";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':year' => $academic_year
    ]);
} else {
    // กรอง doctor_name ตามค่าที่รับมา
    $sql = "SELECT * FROM health_data_external 
            WHERE academic_year = :year AND doctor_name = :doctor";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':year' => $academic_year,
        ':doctor' => $doctor
    ]);
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
try {
    // header ถูกตั้งไว้แล้วด้านบน

    // เปิด output stream
    $output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

  $headers = [
    'รหัสนักเรียน',
    'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'ชื่อเล่น', 'กลุ่มเรียน', 'ห้องเรียน',
    'วันเกิด (ปีเดือนวัน)', 'ปีเกิด', 'เดือนเกิด', 'วันเกิด',
    'ความดันโลหิต', 'หายใจ', 'ชีพจร', 'อุณหภูมิ',
    // เพิ่มสำหรับ physical_measures
    'ส่วนสูง (cm)', 'น้ำหนัก (kg)', 'รอบศีรษะ (cm)', 'น้ำหนักต่ออายุ', 'ส่วนสูงต่ออายุ', 'น้ำหนักต่อส่วนสูง', 'เปอร์เซ็นไทลศีรษะ',
    // เพิ่มสำหรับ behavior
    'สถานะพฤติกรรม', 'รายละเอียดพฤติกรรม',
    // เพิ่มสำหรับ physical_exam (เลือกหลักๆ เพื่อไม่ให้ columns เยอะเกิน)
    'สภาพทั่วไป', 'ผิวหนัง', 'ศีรษะ', 'ใบหน้า', 'ตา', 'หูและการได้ยิน', 'จมูก', 'ปากและช่องปาก', 'คอ',
    'ทรวงอกและปอด', 'การหายใจ', 'ปอด', 'หัวใจ', 'เสียงหัวใจ', 'ชีพจร', 'ท้อง', 'อื่นๆ',
    // เพิ่มสำหรับ neurological
    'ปฏิกิริยาประสาท', 'การเคลื่อนไหว', 'รายละเอียดปฏิกิริยา', 'รายละเอียดการเคลื่อนไหว',
    // เพิ่มสำหรับ development_assessment (สรุปแค่สถานะ + score)
    'การเคลื่อนไหว(GM)', 'มัดเล็กและสติปัญญา (FM)', 'เข้าใจภาษา (RL)', 'ใช้ภาษา (EL)', 'ช่วยเหลือตนเองและสังคม (PS)',
    // เพิ่มอื่นๆ
    'วันที่ตรวจ', 'ปีการศึกษา', 'แพทย์ผู้ตรวจ', 'คำแนะนำ'
];
fputcsv($output, $headers, ',', '"', "\\"); // ตามที่คุณให้มา

// ฟังก์ชันช่วยดึงค่าจาก JSON และแปลง
function getValue($jsonData, $key, $default = '-') {
    $data = json_decode($jsonData, true);
    return $data[$key] ?? $default;
}

function flattenArray($jsonData, $key, $separator = ', ') {
    $data = getValue($jsonData, $key);
    // แปลงค่าอังกฤษเป็นไทย (เช่น normal => ปกติ)
    $map = [
        'normal' => 'ปกติ',
        'abnormal' => 'ผิดปกติ',
        // เพิ่ม mapping อื่นๆ ได้ที่นี่
    ];
    // ถ้าค่าเป็น abnormal ให้แสดง "ผิดปกติ - <detail>" ถ้ามี detail
    $detailKeys = [
        'general', 'skin', 'head', 'face', 'eyes', 'ears', 'nose', 'mouth', 'neck', 'breast', 'breathe', 'lungs', 'heart', 'heart_sound', 'pulse', 'abdomen', 'others'
    ];
    $detailKey = in_array($key, $detailKeys) ? $key . '_detail' : null;
    $detail = $detailKey ? getValue($jsonData, $detailKey, '') : '';
    if (is_array($data)) {
        $data = array_map(function($v) use ($map, $detail) {
            if ($v === 'abnormal' && $detail) {
                return 'ผิดปกติ - ' . $detail;
            }
            return $map[$v] ?? $v;
        }, $data);
        return implode($separator, $data);
    } else {
        if ($data === 'abnormal' && $detail) {
            return 'ผิดปกติ - ' . $detail;
        }
        return $map[$data] ?? $data;
    }
}

// ฟังก์ชันสำหรับ development_assessment (ย้ายออกนอก loop)
function getDev($dev) {
    if (!isset($dev['status']) || $dev['status'] === '' || is_null($dev['status'])) return 'ผ่าน';
    if ($dev['status'] === 'pass') {
        return 'ผ่าน';
    } elseif ($dev['status'] === 'delay') {
        $score = $dev['score'] ?? '-';
        return 'สงสัยล่าช้า - ข้อที่ ' . $score;
    } else {
        return $dev['status'] . (isset($dev['score']) ? ' (' . $dev['score'] . ')' : '');
    }
}

// Loop นักเรียนและเขียนแถว
foreach ($data as $student) {
    // แปลง JSON
    $vitalSigns = json_decode($student['vital_signs'] ?? '{}', true);
    $measures = json_decode($student['physical_measures'] ?? '{}', true);
    $behavior = json_decode($student['behavior'] ?? '{}', true);
    $physicalExam = json_decode($student['physical_exam'] ?? '{}', true);
    $neurological = json_decode($student['neurological'] ?? '{}', true);
    $development = json_decode($student['development_assessment'] ?? '{}', true);

    // จัดการวันเกิด (แยกปีเดือนวัน)
   


    // เขียนแถว CSV พร้อม padding ด้วย tab เพื่อป้องกัน #### ใน Excel
    $row = [
        $student['student_id'] ?? '-',
        $student['prefix_th'] ?? '-',
        $student['first_name'] ?? '-',
        $student['last_name_th'] ?? '-',
        $student['nickname'] ?? '-',
        $student['child_grop'] ?? '-',
        $student['classroom'] ?? '-',
        $student['birth_date'] ?? '-',
        $student['age_year'] ?? '-',
        $student['age_month'] ?? '-',
        $student['age_day'] ?? '-',
        $vitalSigns['bp'] ?? '-',
        $vitalSigns['respiration'] ?? '-',
        $vitalSigns['pulse'] ?? '-',
        $vitalSigns['temperature'] ?? '-',
        $measures['height'] ?? '-',
        $measures['weight'] ?? '-',
        $measures['head_circ'] ?? '-',
        flattenArray($student['physical_measures'], 'weight_for_age'),
        flattenArray($student['physical_measures'], 'height_for_age'),
        flattenArray($student['physical_measures'], 'weight_for_height'),
        flattenArray($student['physical_measures'], 'head_percentile'),
        // behavior ภาษาไทย
        ($behavior['status'] === 'has' ? 'ผิดปกติ' : ($behavior['status'] === 'normal' ? 'ปกติ' : '-')),
        $behavior['detail'] ?? '-',
        flattenArray($student['physical_exam'], 'general'),
        flattenArray($student['physical_exam'], 'skin'),
        flattenArray($student['physical_exam'], 'head'),
        flattenArray($student['physical_exam'], 'face'),
        flattenArray($student['physical_exam'], 'eyes'),
        flattenArray($student['physical_exam'], 'ears'),
        flattenArray($student['physical_exam'], 'nose'),
        flattenArray($student['physical_exam'], 'mouth'),
        flattenArray($student['physical_exam'], 'neck'),
        flattenArray($student['physical_exam'], 'breast'),
        flattenArray($student['physical_exam'], 'breathe'),
        flattenArray($student['physical_exam'], 'lungs'),
        flattenArray($student['physical_exam'], 'heart'),
        flattenArray($student['physical_exam'], 'heart_sound'),
        flattenArray($student['physical_exam'], 'pulse'),
        flattenArray($student['physical_exam'], 'abdomen'),
        flattenArray($student['physical_exam'], 'others'),
        flattenArray($student['neurological'], 'neuro'),
        flattenArray($student['neurological'], 'movement'),
        getValue($student['neurological'], 'neuro_detail'),
        getValue($student['neurological'], 'movement_detail'),
        getDev($development['gm'] ?? []),
        getDev($development['fm'] ?? []),
        getDev($development['rl'] ?? []),
        getDev($development['el'] ?? []),
        getDev($development['ps'] ?? []),
        $student['exam_date'] ?? '-',
        $student['academic_year'] ?? '-',
        $student['doctor_name'] ?? '-',
        $student['recommendation'] ?? 'ไม่มีคำแนะนำ'
    ];
    // เพิ่ม padding tab หน้า-หลังทุก cell เพื่อป้องกัน ####
    $row = array_map(function($v) {
        return "\t" . (string)$v . "\t";
    }, $row);
    fputcsv($output, $row, ',', '"', "\\");
}

    fclose($output);
} catch (Exception $e) {
    // จัดการข้อผิดพลาด    
    error_log('Error exporting CSV: ' . $e->getMessage());
    http_response_code(500);
    echo 'เกิดข้อผิดพลาดในการส่งออกข้อมูล';
}
?>