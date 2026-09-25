<?php
require_once(__DIR__ . '/../../../config/database.php');

date_default_timezone_set('Asia/Bangkok');

header('Content-Type: application/json');

$pdo = getDatabaseConnection();
$pdo->exec("SET timezone TO 'Asia/Bangkok'");

// รับข้อมูลจาก POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['student_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ข้อมูลไม่ครบถ้วน'
    ]);
    exit;
}

$student_id = $data['student_id'];
$guardian_type = $data['guardian_type'] ?? '';
// Map English guardian types to Thai labels
$guardian_type_map = [
    'father' => 'พ่อ',
    'mother' => 'แม่',
    'relative' => 'ผู้ปกครอง/ผู้ดูแล',
    'other' => 'อื่นๆ',
];
$guardian_type_th = $guardian_type_map[$guardian_type] ?? $guardian_type;
$guardian_name = $data['guardian_name'] ?? '';
$other_details = $data['other_details'] ?? '';
$pickup_time = date('Y-m-d H:i:s');

try {
    // ตรวจสอบว่ามี record การเช็คอินอยู่หรือไม่
    $check_stmt = $pdo->prepare("
        SELECT id, check_out_time FROM attendance 
        WHERE student_id = :student_id 
        AND DATE(check_date) = CURRENT_DATE
    ");
    $check_stmt->execute(['student_id' => $student_id]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['check_out_time'] !== null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'นักเรียนคนนี้ได้บันทึกการรับเด็กกลับบ้านไปแล้ว'
        ]);
        exit;
    }

    // สร้าง leave_note ที่รวมข้อมูลผู้รับเด็ก
    $leave_note = "ผู้รับเด็ก: {$guardian_name}";
    if ($guardian_type_th) {
        if ($guardian_name) {
            $leave_note .= " ({$guardian_type_th})";
        } else {
            $leave_note = "ผู้รับเด็ก: ({$guardian_type_th})";
        }
    }
    if ($other_details) {
        $leave_note .= " - {$other_details}";
    }

    if ($existing) {
        // มี record เช็คอินอยู่แล้ว → อัปเดตข้อมูลการเช็คเอาท์
        $update_stmt = $pdo->prepare("
            UPDATE attendance 
            SET 
                check_out_time = :check_out_time,
                status_checkout = 'checked_out',
                leave_note = :leave_note,
                updated_at = NOW()
            WHERE id = :id
        ");
        $update_stmt->execute([
            'check_out_time' => $pickup_time,
            'leave_note' => $leave_note,
            'id' => $existing['id']
        ]);
    } else {
        // ไม่มี record เช็คอิน → สร้าง record ใหม่
        $insert_stmt = $pdo->prepare("
            INSERT INTO attendance 
            (student_id, check_date, status, check_out_time, status_checkout, leave_note, created_at, updated_at)
            VALUES 
            (:student_id, CURRENT_DATE, 'present', :check_out_time, 'checked_out', :leave_note, NOW(), NOW())
        ");
        $insert_stmt->execute([
            'student_id' => $student_id,
            'check_out_time' => $pickup_time,
            'leave_note' => $leave_note
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกการรับเด็กสำเร็จ',
        'data' => [
            'student_id' => $student_id,
            'guardian_name' => $leave_note,
            'pickup_time' => $pickup_time
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in save_guardian_pickup.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
