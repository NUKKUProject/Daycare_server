<?php

require_once(__DIR__ . '../../../../../config/database.php');

// รับค่าจาก POST
$export_type = $_POST['export_type'] ?? 'academic_year';
$academic_year = $_POST['academic_year'] ?? '';
$exam_date = $_POST['exam_date'] ?? '';
$doctor = $_POST['doctor'] ?? 'all';

// ตรวจสอบข้อมูลนำเข้า
if ($export_type === 'academic_year' && empty($academic_year)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'กรุณาระบุปีการศึกษา';
    exit;
}

if ($export_type === 'exam_date' && empty($exam_date)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'กรุณาระบุวันที่ตรวจ';
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $pdo = getDatabaseConnection();
    
    // Query ข้อมูลตามประเภทที่เลือก (เฉพาะข้อมูลที่มี doctor_name ไม่เป็นค่าว่าง)
    if ($export_type === 'exam_date') {
        // Export ตามวันที่ตรวจ
        $sql = "SELECT * FROM health_data_external 
                WHERE exam_date = :exam_date 
                AND doctor_name IS NOT NULL 
                AND doctor_name != '' 
                ORDER BY student_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':exam_date' => $exam_date]);
    } else {
        // Export ตามปีการศึกษา
        $sql = "SELECT * FROM health_data_external 
                WHERE academic_year = :year 
                AND doctor_name IS NOT NULL 
                AND doctor_name != '' 
                ORDER BY exam_date, student_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':year' => $academic_year]);
    }
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบว่ามีข้อมูลหรือไม่
    if (empty($data)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'ไม่พบข้อมูลสำหรับ export';
        exit;
    }
    
    // กำหนดชื่อไฟล์
    if ($export_type === 'exam_date') {
        $dateFormatted = date('Y-m-d', strtotime($exam_date));
        $filename = "รายงานผลตรวจสุขภาพ_วันที่_" . $dateFormatted . ".csv";
    } else {
        $filename = "รายงานผลตรวจสุขภาพ_ปีการศึกษา_" . $academic_year . ".csv";
    }
    
    // ส่ง headers
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // เปิด output stream
    $output = fopen('php://output', 'w');
    
    // เขียน UTF-8 BOM เพื่อให้ Excel อ่านภาษาไทยได้ถูกต้อง
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // กำหนด headers ของตาราง
    $headers = [
        'รหัสนักเรียน',
        'คำนำหน้า',
        'ชื่อ',
        'นามสกุล',
        'ชื่อเล่น',
        'กลุ่มเรียน',
        'ห้องเรียน',
        'วันเกิด',
        'อายุ (ปี)',
        'อายุ (เดือน)',
        'อายุ (วัน)',
        'วันที่ตรวจ',
        'ปีการศึกษา',
        'ตรวจรอบที่',
        'แพทย์ผู้ตรวจ',
        'อุณหภูมิร่างกาย',
        'ความดันโลหิต',
        'ชีพจร',
        'อัตราการหายใจ',
        'ส่วนสูง (cm)',
        'น้ำหนัก (kg)',
        'เส้นรอบศีรษะ (cm)',
        'น้ำหนักตามเกณฑ์อายุ',
        'ส่วนสูงตามเกณฑ์อายุ',
        'น้ำหนักตามเกณฑ์ส่วนสูง',
        'เปอร์เซ็นไทล์ศีรษะ',
        'สถานะพฤติกรรม',
        'รายละเอียดพฤติกรรม',
        'สภาพทั่วไป',
        'ผิวหนัง',
        'ศีรษะ',
        'ใบหน้า',
        'ตา',
        'หูและการได้ยิน',
        'จมูก',
        'ปากและช่องปาก',
        'คอ',
        'ทรวงอกและปอด',
        'การหายใจ',
        'ปอด',
        'หัวใจ',
        'เสียงหัวใจ',
        'ชีพจร (ร่างกาย)',
        'ช่องท้อง',
        'อื่นๆ',
        'ปฏิกิริยารีเฟล็กซ์',
        'การเคลื่อนไหว',
        'รายละเอียดปฏิกิริยา',
        'รายละเอียดการเคลื่อนไหว',
        'การเคลื่อนไหว (GM)',
        'มัดเล็กและสติปัญญา (FM)',
        'เข้าใจภาษา (RL)',
        'ใช้ภาษา (EL)',
        'ช่วยเหลือตนเองและสังคม (PS)',
        'คำแนะนำ'
    ];
    
    // เขียน headers
    fputcsv($output, $headers, ',', '"', "\\");
    
    // ฟังก์ชันช่วยดึงค่าจาก JSON
    function getValue($jsonData, $key, $default = '-') {
        if (empty($jsonData)) return $default;
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) return $default;
        return $data[$key] ?? $default;
    }
    
    // ฟังก์ชันแปลง array เป็น string
    function flattenArray($jsonData, $key, $separator = ', ') {
        if (empty($jsonData)) return '-';
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) return '-';
        
        $value = $data[$key] ?? null;
        
        // แปลงค่าอังกฤษเป็นไทย
        $map = [
            'normal' => 'ปกติ',
            'abnormal' => 'ผิดปกติ',
            'has' => 'มี',
            'none' => 'ไม่มี'
        ];
        
        // รายการที่มี detail
        $detailKeys = [
            'general', 'skin', 'head', 'face', 'eyes', 'ears', 'nose', 'mouth', 
            'neck', 'breast', 'breathe', 'lungs', 'heart', 'heart_sound', 
            'pulse', 'abdomen', 'others'
        ];
        
        $detailKey = in_array($key, $detailKeys) ? $key . '_detail' : null;
        $detail = $detailKey ? getValue($jsonData, $detailKey, '') : '';
        
        if (is_array($value)) {
            $mapped = array_map(function($v) use ($map, $detail) {
                if ($v === 'abnormal' && !empty($detail)) {
                    return 'ผิดปกติ - ' . $detail;
                }
                return $map[$v] ?? $v;
            }, $value);
            return implode($separator, $mapped);
        } else {
            if ($value === 'abnormal' && !empty($detail)) {
                return 'ผิดปกติ - ' . $detail;
            }
            return $map[$value] ?? ($value ?: '-');
        }
    }
    
    // ฟังก์ชันสำหรับ development_assessment
    function getDev($dev) {
        if (!is_array($dev) || empty($dev)) return '-';
        if (!isset($dev['status']) || $dev['status'] === '' || is_null($dev['status'])) return 'ผ่าน';
        if ($dev['status'] === 'pass') {
            return 'ผ่าน';
        } elseif ($dev['status'] === 'delay') {
            $score = $dev['score'] ?? '-';
            return 'สงสัยล่าช้า (ข้อที่ ' . $score . ')';
        } else {
            return $dev['status'] . (isset($dev['score']) ? ' (' . $dev['score'] . ')' : '');
        }
    }
    
    // ฟังก์ชันแปลงวันที่เป็นภาษาไทย
    function formatDateThai($dateStr) {
        if (empty($dateStr)) return '-';
        $months = [
            '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
            '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
            '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
        ];
        $date = date_create($dateStr);
        if (!$date) return $dateStr;
        $day = date_format($date, 'j');
        $month = $months[date_format($date, 'm')] ?? '';
        $year = (int)date_format($date, 'Y') + 543;
        return $day . ' ' . $month . ' ' . $year;
    }
    
    // Loop เขียนข้อมูล
    foreach ($data as $student) {
        // แปลง JSON
        $vitalSigns = !empty($student['vital_signs']) ? json_decode($student['vital_signs'], true) : [];
        $measures = !empty($student['physical_measures']) ? json_decode($student['physical_measures'], true) : [];
        $behavior = !empty($student['behavior']) ? json_decode($student['behavior'], true) : [];
        $physicalExam = !empty($student['physical_exam']) ? json_decode($student['physical_exam'], true) : [];
        $neurological = !empty($student['neurological']) ? json_decode($student['neurological'], true) : [];
        $development = !empty($student['development_assessment']) ? json_decode($student['development_assessment'], true) : [];
        
        // สถานะพฤติกรรม
        $behaviorStatus = '-';
        if (isset($behavior['status'])) {
            if ($behavior['status'] === 'has') {
                $behaviorStatus = 'มีพฤติกรรมผิดปกติ';
            } elseif ($behavior['status'] === 'none') {
                $behaviorStatus = 'ไม่มีพฤติกรรมผิดปกติ';
            } elseif ($behavior['status'] === 'normal') {
                $behaviorStatus = 'ปกติ';
            }
        }
        
        // เขียนแถวข้อมูล
        $row = [
            $student['student_id'] ?? '-',
            $student['prefix_th'] ?? '-',
            $student['first_name'] ?? '-',
            $student['last_name_th'] ?? '-',
            $student['nickname'] ?? '-',
            $student['child_grop'] ?? '-',
            $student['classroom'] ?? '-',
            formatDateThai($student['birth_date'] ?? ''),
            $student['age_year'] ?? '-',
            $student['age_month'] ?? '-',
            $student['age_day'] ?? '-',
            formatDateThai($student['exam_date'] ?? ''),
            $student['academic_year'] ?? '-',
            $student['check_round'] ?? '-',
            $student['doctor_name'] ?? '-',
            ($vitalSigns['temperature'] ?? '-') . ' °C',
            $vitalSigns['bp'] ?? '-',
            $vitalSigns['pulse'] ?? '-',
            $vitalSigns['respiration'] ?? '-',
            $measures['height'] ?? '-',
            $measures['weight'] ?? '-',
            $measures['head_circ'] ?? '-',
            flattenArray($student['physical_measures'] ?? '', 'weight_for_age'),
            flattenArray($student['physical_measures'] ?? '', 'height_for_age'),
            flattenArray($student['physical_measures'] ?? '', 'weight_for_height'),
            flattenArray($student['physical_measures'] ?? '', 'head_percentile'),
            $behaviorStatus,
            $behavior['detail'] ?? '-',
            flattenArray($student['physical_exam'] ?? '', 'general'),
            flattenArray($student['physical_exam'] ?? '', 'skin'),
            flattenArray($student['physical_exam'] ?? '', 'head'),
            flattenArray($student['physical_exam'] ?? '', 'face'),
            flattenArray($student['physical_exam'] ?? '', 'eyes'),
            flattenArray($student['physical_exam'] ?? '', 'ears'),
            flattenArray($student['physical_exam'] ?? '', 'nose'),
            flattenArray($student['physical_exam'] ?? '', 'mouth'),
            flattenArray($student['physical_exam'] ?? '', 'neck'),
            flattenArray($student['physical_exam'] ?? '', 'breast'),
            flattenArray($student['physical_exam'] ?? '', 'breathe'),
            flattenArray($student['physical_exam'] ?? '', 'lungs'),
            flattenArray($student['physical_exam'] ?? '', 'heart'),
            flattenArray($student['physical_exam'] ?? '', 'heart_sound'),
            flattenArray($student['physical_exam'] ?? '', 'pulse'),
            flattenArray($student['physical_exam'] ?? '', 'abdomen'),
            flattenArray($student['physical_exam'] ?? '', 'others'),
            flattenArray($student['neurological'] ?? '', 'neuro'),
            flattenArray($student['neurological'] ?? '', 'movement'),
            getValue($student['neurological'] ?? '', 'neuro_detail'),
            getValue($student['neurological'] ?? '', 'movement_detail'),
            getDev($development['gm'] ?? []),
            getDev($development['fm'] ?? []),
            getDev($development['rl'] ?? []),
            getDev($development['el'] ?? []),
            getDev($development['ps'] ?? []),
            $student['recommendation'] ?? '-'
        ];
        
        // เขียนข้อมูลลง CSV
        fputcsv($output, $row, ',', '"', "\\");
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    error_log('Database error in export_excel_health.php: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
    }
} catch (Exception $e) {
    error_log('Error in export_excel_health.php: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'เกิดข้อผิดพลาดในการส่งออกข้อมูล';
    }
}
exit;
?>