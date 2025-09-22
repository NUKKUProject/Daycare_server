<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received data for update: " . print_r($data, true));

    if (empty($data['id']) || empty($data['student_id'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    // ตรวจสอบค่า status ที่รับเข้ามา
    if (!in_array($data['status'], ['present', 'absent', 'leave'])) {
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

    // แปลงเวลา check_out_time
    $check_out_time = null;
    if (!empty($data['check_out_time'])) {
        $time_parts = explode(':', $data['check_out_time']);
        $hours = $time_parts[0];
        $minutes = isset($time_parts[1]) ? $time_parts[1] : '00';
        $check_out_time = sprintf('%02d:%02d:00', $hours, $minutes);
    }

    // SQL query สำหรับอัพเดท
    $sql = "UPDATE attendance SET 
            status = :status::varchar,
            check_date = CASE 
                WHEN :status IN ('present', 'late') 
                THEN :full_timestamp::timestamp
                ELSE :attendance_date::date
            END,
            check_out_time = CASE 
                WHEN :status IN ('present', 'late') AND :check_out_time::text IS NOT NULL 
                AND :check_out_time::text != ''
                THEN :check_out_time::time
                ELSE NULL 
            END,
            leave_note = CASE
                WHEN :status = 'leave' THEN :leave_note
                WHEN :status = 'late' THEN 'มาสาย'
                ELSE NULL
            END,
            status_checkout = CASE 
                WHEN :status IN ('present', 'late') AND :check_out_time::text IS NOT NULL 
                AND :check_out_time::text != ''
                THEN 'checked_out'::varchar
                WHEN :status IN ('present', 'late') THEN 'no_checked_out'::varchar
                ELSE NULL 
            END,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";

    // กำหนดค่าพารามิเตอร์
    $params = [
        ':id' => $data['id'],
        ':status' => $data['status'],
        ':full_timestamp' => $full_timestamp,
        ':check_out_time' => $check_out_time,
        ':leave_note' => $data['leave_note'] ?? null,
        ':attendance_date' => $data['attendance_date']
    ];

    // Debug logs
    error_log("Status after late check: " . $data['status']);
    error_log("Full timestamp: " . $full_timestamp);
    error_log("SQL: " . $sql);
    error_log("Params: " . print_r($params, true));

    // ทำการอัพเดท
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
        $error = $stmt->errorInfo();
        error_log("Database error: " . print_r($error, true));
        throw new Exception('ไม่สามารถอัพเดทข้อมูลได้: ' . $error[2]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'อัพเดทข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    error_log("Error in update_attendance_record: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 