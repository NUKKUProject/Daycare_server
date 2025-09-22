<?php
require_once(__DIR__ . '/../../include/function/child_functions.php');


error_reporting(0);
ini_set('display_errors', 0);

try {
    $studentid = $_POST['studentid'] ?? null;
    if (!$studentid) {
        throw new Exception("ไม่พบรหัสนักเรียน");
    }

    // ดึงข้อมูลการเข้าเรียน
    $pdo = getDatabaseConnection();
    
    // ดึงข้อมูลการเข้าเรียนย้อนหลัง 3 เดือน
    $stmt = $pdo->prepare("
        SELECT 
            a.check_date,
            CASE 
                WHEN a.status = 'present' THEN 'มาเรียน'
                WHEN a.status = 'late' THEN 'มาสาย'
                WHEN a.status = 'absent' THEN 'ขาดเรียน'
                WHEN a.status = 'leave' THEN 'ลา'
                ELSE 'ไม่ระบุ'
            END as status_text,
            CASE 
                WHEN a.status_checkout = 'checked_out' THEN 'กลับแล้ว'
                WHEN a.status_checkout = 'not_checked_out' THEN 'ยังไม่กลับ'
                ELSE 'ไม่ระบุ'
            END as checkout_status,
            a.check_out_time,
            CASE 
                WHEN a.status = 'leave' THEN a.leave_note
                ELSE ''
            END as leave_note,
            a.created_at as recorded_time
        FROM attendance a
        WHERE a.student_id = :studentid
        AND a.check_date >= CURRENT_DATE - INTERVAL '3 months'
        ORDER BY a.check_date DESC
    ");
    $stmt->execute(['studentid' => $studentid]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'child_attendance_' . $studentid . '_' . date('Y-m-d_His') . '.csv';

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
    $headers = ['วันที่', 'สถานะการมาเรียน', 'สถานะการกลับ', 'เวลากลับ', 'บันทึกการลา', 'เวลาที่บันทึก'];
    fputcsv($output, $headers, ',', '"', '\\');

    // เขียนข้อมูลการเข้าเรียน
    foreach ($attendance_records as $record) {
        $row = [
            $record['check_date'],
            $record['status_text'],
            $record['checkout_status'],
            $record['check_out_time'],
            $record['leave_note'],
            $record['recorded_time']
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