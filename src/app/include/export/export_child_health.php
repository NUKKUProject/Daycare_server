<?php
require_once(__DIR__ . '/../../include/function/child_functions.php');


error_reporting(0);
ini_set('display_errors', 0);

try {
    $studentid = $_POST['studentid'] ?? null;
    if (!$studentid) {
        throw new Exception("ไม่พบรหัสนักเรียน");
    }

    $pdo = getDatabaseConnection();
    
    // ดึงข้อมูลการตรวจสุขภาพ
    $stmt = $pdo->prepare("
        SELECT 
            h.id,
            h.student_id,
            h.created_at as check_date,
            h.prefix_th,
            h.first_name_th,
            h.last_name_th,
            h.child_group,
            h.classroom,
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
            h.fever_temp,
            (
                SELECT string_agg(value, ', ')
                FROM jsonb_array_elements_text(h.symptoms::jsonb->'checked')
            ) as symptoms,
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
            h.teacher_signature,
            h.updated_at
        FROM health_data h
        WHERE h.student_id = :studentid
        GROUP BY 
            h.id,
            h.student_id,
            h.created_at,
            h.prefix_th,
            h.first_name_th,
            h.last_name_th,
            h.child_group,
            h.classroom,
            h.hair_reason,
            h.eye_condition,
            h.eye_reason,
            h.mouth::text,
            h.teeth::text,
            h.teeth_count,
            h.ears::text,
            h.nose::text,
            h.nose_condition,
            h.nose_reason,
            h.nails::text,
            h.skin::text,
            h.skin_wound_detail,
            h.skin_rash_detail,
            h.hands_feet::text,
            h.arms_legs::text,
            h.body::text,
            h.fever_temp,
            h.cough_type,
            h.symptoms_reason,
            h.medicine::text,
            h.medicine_detail,
            h.medicine_reason,
            h.illness_reason,
            h.accident_reason,
            h.teacher_note,
            h.teacher_signature,
            h.updated_at
        ORDER BY h.created_at DESC
    ");
    $stmt->execute(['studentid' => $studentid]);
    $health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'child_health_' . $studentid . '_' . date('Y-m-d_His') . '.csv';

    // เคลียร์ output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // ส่ง headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // เขียน headers
    $headers = [
        'รหัส',
        'รหัสนักเรียน',
        'วันที่ตรวจ',
        'คำนำหน้า',
        'ชื่อ',
        'นามสกุล',
        'กลุ่มเด็ก',
        'ห้องเรียน',
        'ผม',
        'หมายเหตุผม',
        'ตา',
        'ลักษณะขี้ตา',
        'หมายเหตุตา',
        'ปาก',
        'ฟัน',
        'จำนวนฟันผุ',
        'หู',
        'จมูก',
        'ลักษณะนํ้ามูก',
        'หมายเหตุจมูก',
        'เล็บ',
        'ผิวหนัง',
        'รายละเอียดแผล',
        'รายละเอียดผื่น',
        'มือและเท้า',
        'แขนและขา',
        'ร่างกาย',
        'อุณหภูมิ',
        'มีไข้',
        'มีอาการไอ',
        'ลักษณะการไอ',
        'หมายเหตุ',
        'ยา',
        'รายละเอียดยา',
        'สาเหตุการใช้ยา',
        'สาเหตุการเจ็บป่วย',
        'สาเหตุอุบัติเหตุ',
        'บันทึกของครู',
        'ลายเซ็นครู',
        'วันที่ปรับปรุง'
    ];
    fputcsv($output, $headers, ',', '"', '\\');

    // เขียนข้อมูลสุขภาพ
    foreach ($health_records as $record) {
        $row = [
            $record['id'],
            $record['student_id'],
            $record['check_date'],
            $record['prefix_th'],
            $record['first_name_th'],
            $record['last_name_th'],
            $record['child_group'],
            $record['classroom'],
            $record['hair'],          // ผม/ศีรษะ
            $record['hair_reason'],   // หมายเหตุผม
            $record['eye'],           // ตา
            $record['eye_condition'], // สภาพตา/มีขี้ตา
            $record['eye_reason'],    // หมายเหตุตา
            $record['mouth'],         // ปาก
            $record['teeth'],         // ฟัน
            $record['teeth_count'],   // จำนวนฟันผุ
            $record['ears'],          // หู
            $record['nose'],          // จมูก
            $record['nose_condition'], // สภาพจมูก/มีนํ้ามูก
            $record['nose_reason'],   // หมายเหตุจมูก
            $record['nails'],
            $record['skin'],
            $record['skin_wound_detail'],
            $record['skin_rash_detail'],
            $record['hands_feet'],
            $record['arms_legs'],
            $record['body'],
            $record['fever_temp'],    // อุณหภูมิ
            $record['symptoms'],      // มีไข้
            $record['cough_type'],     // ลักษณะการไอ
            $record['symptoms_reason'], // หมายเหตุ
            $record['medicine'],
            $record['medicine_detail'],
            $record['medicine_reason'],
            $record['illness_reason'],
            $record['accident_reason'],
            $record['teacher_note'],
            $record['teacher_signature'],
            $record['updated_at']
        ];
        fputcsv($output, $row, ',', '"', '\\');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('HTTP/1.1 500 Internal Server Error');
    echo "เกิดข้อผิดพลาดในการ export ข้อมูล: " . $e->getMessage();
}
?> 