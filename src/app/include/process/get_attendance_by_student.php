<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    if (!isset($_GET['student_id'])) {
        throw new Exception('ไม่พบรหัสนักเรียน');
    }

    $student_id = $_GET['student_id'];
    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare("
        SELECT a.*,
            CASE 
                WHEN a.status_checkout = 'checked_out' THEN 'กลับแล้ว'
                WHEN a.status_checkout = 'no_checked_out' THEN 'ยังไม่กลับ'
                ELSE NULL 
            END as status_checkout_th,
            CASE 
                WHEN a.status = 'present' THEN 'มาเรียน'
                WHEN a.status = 'late' THEN 'มาสาย'
                WHEN a.status = 'absent' THEN 'ขาดเรียน'
                WHEN a.status = 'leave' THEN 'ลา'
                ELSE a.status 
            END as status_th
        FROM attendance a
        WHERE a.student_id = :student_id
        ORDER BY a.check_date DESC
    ");
    $stmt->execute(['student_id' => $student_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $present = 0;
    $absent = 0;
    $leave = 0;
    $late = 0;
    $total = count($records);

    foreach ($records as $r) {
        switch ($r['status']) {
            case 'present': $present++; break;
            case 'late': $late++; break;
            case 'absent': $absent++; break;
            case 'leave': $leave++; break;
        }
    }

    $present_total = $present + $late;
    $rate = $total > 0 ? round(($present_total / $total) * 100) : 0;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'records' => $records,
            'summary' => [
                'present' => $present_total,
                'absent' => $absent,
                'leave' => $leave,
                'total' => $total,
                'rate' => $rate
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_attendance_by_student: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
