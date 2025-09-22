<?php

require_once(__DIR__ . '../../../../../config/database.php');


// กำหนดชื่อไฟล์ก่อนส่ง header
$academic_year = $_POST['academic_year'] ?? '';
$filename = "รายงานผลตรวจสุขช่องปาก_ปีการศึกษา_" . $academic_year . ".csv";

header('Content-Type: text/csv; charset=utf-8');
// ใช้ filename*=UTF-8''... เพื่อรองรับชื่อไฟล์ภาษาไทย
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
// // เชื่อมต่อฐานข้อมูล
try {
    $pdo = getDatabaseConnection();
} catch (PDOException $e) {
    die('ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้: ' . $e->getMessage());
}


// รับค่าจาก URL parameters

$doctor = 'all';


// ดึงข้อมูลจากฐานข้อมูล
// ดึงข้อมูลจากฐานข้อมูล
if ($doctor === 'all') {
    // ไม่กรอง doctor_name
    $sql = "SELECT * FROM health_tooth_external 
            WHERE academic_year = :year";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':year' => $academic_year
    ]);
} else {
    // กรอง doctor_name ตามค่าที่รับมา
    $sql = "SELECT * FROM health_tooth_external 
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
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    $headers = [
        'รหัสนักเรียน',
        'คำนำหน้า',
        'ชื่อ',
        'นามสกุล',
        'ชื่อเล่น',
        'ห้องเรียน',
        'ปีเกิด',
        'เดือนเกิด',
        'วันเกิด',
        'จำนวนฟันทั้งหมด',
        'จำนวนฟันผุทั้งหมด',
        'ส่วนประกอบช่องปาก (เหงือก, ลิ้น, เพดาน)',
        'สถานะฟัน',
        'ฟันหน้าบน',
        'ฟันกรามบนขวา',
        'ฟันกรามล่างขวา',
        'ฟันหน้าล่าง',
        'ฟันกรามบนซ้าย',
        'ฟันกรามล่างซ้าย',
        'อุดฟัน',
        'รักษาคลองรากฟัน',
        'ครอบฟัน',
        'เคลือบฟลูออไรด์',
        'เคลือบหลุมร่องฟันที่ฟันกราม',
        'ถอนฟัน',
        'อื่น ๆ ได้แก่',
        'ความเร่งด่วนในการรักษา',
        'แพทย์ผู้ตรวจ',
        'วันที่ตรวจ'
    ];
    fputcsv($output, $headers, ',', '"', "\\");
    // แปลงค่าภาษาอังกฤษเป็นไทย
    $teeth_status_map = [
        'normal' => 'ปกติ',
        'abnormal' => 'มีฟันผุ',
        '' => '-',
    ];
    $urgency_map = [
        'urgent' => 'เร่งด่วน',
        'not_urgent' => 'ไม่เร่งด่วน',
        '' => '-',
    ];
    $treatment_map = [
        'filling' => 'อุดฟัน',
        'root_canal' => 'รักษาคลองรากฟัน',
        'crown' => 'ครอบฟัน',
        'fluoride' => 'เคลือบฟลูออไรด์',
        'fluoride_molar' => 'เคลือบหลุมร่องฟันที่ฟันกราม',
        'extraction' => 'ถอนฟัน',
        'other' => 'อื่น ๆ',
    ];

    foreach ($data as $student) {
        // ตำแหน่งฟันผุ (json)
        $positions = json_decode($student['decayed_teeth_positions'] ?? '{}', true);
        // การรักษา (array)
        $treatments = json_decode($student['treatments'] ?? '[]', true);
        // แปลง treatment เป็นคอลัมน์
        $treatment_cols = [
            'filling' => '',
            'root_canal' => '',
            'crown' => '',
            'fluoride' => '',
            'fluoride_molar' => '',
            'extraction' => '',
            'other' => '',
        ];
        foreach ($treatments as $t) {
            if (isset($treatment_cols[$t])) $treatment_cols[$t] = '✓';
        }

        // ถ้าเป็นปกติและมี missing_teeth_detail ให้แสดง ปกติ - ...
        $teeth_status_val = $teeth_status_map[$student['teeth_status']] ?? '-';
        if (($student['teeth_status'] ?? '') === 'normal' && !empty($student['missing_teeth_detail'])) {
            $teeth_status_val .= ' - ' . $student['missing_teeth_detail'];
        }
        $row = [
            $student['student_id'] ?? '-',
            $student['prefix_th'] ?? '-',
            $student['first_name'] ?? '-',
            $student['last_name'] ?? '-',
            $student['nickname'] ?? '-',
            $student['classroom'] ?? '-',
            $student['age_year'] ?? '-',
            $student['age_month'] ?? '-',
            $student['age_day'] ?? '-',
            $student['total_teeth'] ?? '-',
            $student['decayed_teeth'] ?? '-',
            $student['oral_components'] ?? '-',
            $teeth_status_val,
            $positions['upper_front_teeth'] ?? '-',
            $positions['upper_right_molar'] ?? '-',
            $positions['lower_right_molar'] ?? '-',
            $positions['lower_front_teeth'] ?? '-',
            $positions['upper_left_molar'] ?? '-',
            $positions['lower_left_molar'] ?? '-',
            $treatment_cols['filling'],
            $treatment_cols['root_canal'],
            $treatment_cols['crown'],
            $treatment_cols['fluoride'],
            $treatment_cols['fluoride_molar'],
            $treatment_cols['extraction'],
            $student['other_treatment_detail'] ?? '-',
            $urgency_map[$student['urgency']] ?? '-',
            $student['doctor_name'] ?? '-',
            isset($student['created_at']) ? substr($student['created_at'], 0, 10) : '-',
        ];
        // เพิ่ม padding tab หน้า-หลังทุก cell เพื่อป้องกัน ####
        $row = array_map(function ($v) {
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
