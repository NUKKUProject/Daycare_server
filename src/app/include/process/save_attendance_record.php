<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received data: " . print_r($data, true));
    
    if (empty($data['student_id'])) {
        throw new Exception('ไม่พบรหัสนักเรียน');
    }

    // ตรวจสอบค่า status ที่รับเข้ามา
    if (!in_array($data['status'], ['present', 'absent', 'leave', 'late'])) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }

    // เพิ่มฟังก์ชันตรวจสอบเวลา
    function isLate($time) {
        // แปลงเวลาเป็น timestamp
        $time_parts = explode(':', $time);
        $hours = intval($time_parts[0]);
        $minutes = isset($time_parts[1]) ? intval($time_parts[1]) : 0;
        
        // เวลาที่มา
        $check_time = $hours * 3600 + $minutes * 60;
        
        // เวลาที่กำหนด (8:30)
        $cutoff_time = 8 * 3600 + 30 * 60;
        
        error_log("Check time (seconds): " . $check_time);
        error_log("Cutoff time (seconds): " . $cutoff_time);
        
        return $check_time > $cutoff_time;
    }

    // ตรวจสอบและกำหนดค่าเริ่มต้น
    $check_date = null;
    $full_timestamp = null;

    if (!empty($data['check_date'])) {
        // แปลงเวลาให้อยู่ในรูปแบบ HH:mm:ss
        $time_parts = explode(':', $data['check_date']);
        $hours = $time_parts[0];
        $minutes = isset($time_parts[1]) ? $time_parts[1] : '00';
        $seconds = isset($time_parts[2]) ? $time_parts[2] : '00';
        $check_date = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        
        // สร้าง timestamp แบบสมบูรณ์
        $full_timestamp = date('Y-m-d H:i:s', strtotime($data['attendance_date'] . ' ' . $check_date));
        
        // ตรวจสอบสถานะมาสาย
        if ($data['status'] === 'present') {
            $is_late = isLate($data['check_date']);
            error_log("Is late check: " . ($is_late ? 'true' : 'false'));
            if ($is_late) {
                $data['status'] = 'late';
                error_log("Status changed to late");
            }
        }
    }

    $check_out_time = null;
    if (!empty($data['check_out_time'])) {
        $time_parts = explode(':', $data['check_out_time']);
        $hours = $time_parts[0];
        $minutes = isset($time_parts[1]) ? $time_parts[1] : '00';
        $check_out_time = sprintf('%02d:%02d:00', $hours, $minutes);
    }

    $leave_note = !empty($data['leave_note']) ? $data['leave_note'] : null;
    $attendance_date = !empty($data['attendance_date']) ? $data['attendance_date'] : date('Y-m-d');

    // ตรวจสอบสถานะมาสาย
    if ($data['status'] === 'present' && $check_date && isLate($check_date)) {
        $data['status'] = 'late';
    }

    // ตรวจสอบว่ามีข้อมูลของนักเรียนในวันที่เลือกหรือไม่
    $checkSql = "SELECT id FROM attendance 
                WHERE student_id = :student_id 
                AND DATE(check_date) = :attendance_date::date";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        ':student_id' => $data['student_id'],
        ':attendance_date' => $attendance_date
    ]);
    $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRecord) {
        // ถ้ามีข้อมูลอยู่แล้ว ให้แจ้งเตือน
        throw new Exception('มีข้อมูลการเข้าเรียนของนักเรียนในวันที่เลือกแล้ว กรุณาใช้การแก้ไขแทน');
    }

    // SQL query
    $sql = "INSERT INTO attendance (
            student_id, status, check_date, check_out_time, 
            leave_note, status_checkout, is_recorded, created_at, updated_at
        ) VALUES (
            :student_id,
            :status::varchar,
            CASE 
                WHEN :status IN ('present', 'late') 
                THEN :full_timestamp::timestamp
                ELSE :attendance_date::date
            END,
            CASE 
                WHEN :status IN ('present', 'late') AND :check_out_time::text IS NOT NULL 
                AND :check_out_time::text != ''
                THEN :check_out_time::time
                ELSE NULL 
            END,
            CASE
                WHEN :status = 'leave' THEN :leave_note
                WHEN :status = 'late' THEN 'มาสาย'
                ELSE NULL
            END,
            CASE 
                WHEN :status IN ('present', 'late') AND :check_out_time::text IS NOT NULL 
                AND :check_out_time::text != ''
                THEN 'checked_out'::varchar
                WHEN :status IN ('present', 'late') THEN 'no_checked_out'::varchar
                ELSE NULL 
            END,
            TRUE,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        ) RETURNING id, check_date, status";

    // กำหนดค่าพารามิเตอร์
    $params = [
        ':student_id' => $data['student_id'],
        ':status' => $data['status'],
        ':full_timestamp' => $full_timestamp,
        ':check_out_time' => $check_out_time,
        ':leave_note' => $leave_note,
        ':attendance_date' => $attendance_date
    ];

    // Debug logs
    error_log("Status: " . $data['status']);
    error_log("Full timestamp: " . $full_timestamp);
    error_log("Attendance date: " . $attendance_date);
    error_log("Raw check_date: " . $data['check_date']);
    error_log("Formatted check_date: " . $check_date);
    error_log("Full timestamp before insert: " . $full_timestamp);
    error_log("SQL: " . $sql);
    error_log("Params: " . print_r($params, true));

    // ทำการบันทึก
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
        $error = $stmt->errorInfo();
        error_log("Database error: " . print_r($error, true));
        throw new Exception('ไม่สามารถบันทึกข้อมูลได้: ' . $error[2]);
    }

    // ตรวจสอบค่าที่บันทึก
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Inserted record: " . print_r($result, true));

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    error_log("Error in save_attendance_record: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
