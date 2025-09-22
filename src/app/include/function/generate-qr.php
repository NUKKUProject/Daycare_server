<?php
require_once(__DIR__ . '/../../../config/database.php');
require_once(__DIR__ . '/../../../phpqrcode/qrlib.php');

// รับข้อมูล JSON จาก request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['student_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสนักเรียน']);
    exit;
}

$studentId = $data['student_id'];
try {
    $pdo = getDatabaseConnection();

    // ดึงข้อมูลนักเรียน
    $stmt = $pdo->prepare('SELECT * FROM public.children WHERE studentid = :studentid');
    $stmt->execute(['studentid' => $studentId]);
    $student = $stmt->fetch();

    if ($student) {
        // สร้างข้อมูลสำหรับ QR Code
        $studentData = [
            'student_id' => $student['studentid'],
            'prefix' => $student['prefix_th'],
            'first_name' => $student['firstname_th'],
            'last_name' => $student['lastname_th'],
            'nick_name' => $student['nickname'],
            'classroom' => $student['classroom'],
            'child_group' => $student['child_group']
        ];

        $qrContent = json_encode($studentData);
        
        // กำหนดที่เก็บไฟล์ QR Code
        $uploadDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/uploads/qrcodes/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // สร้างชื่อไฟล์
        $filename = 'qr_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $studentId) . '.png';
        $uploadPath = $uploadDir . $filename;
        $webPath = '../../../public/uploads/qrcodes/' . $filename;

        // สร้าง QR Code
        QRcode::png($qrContent, $uploadPath, QR_ECLEVEL_L, 10);
        chmod($uploadPath, 0644);

        // บันทึกข้อมูล QR Code ลงฐานข้อมูล
        $stmt = $pdo->prepare("UPDATE children SET qr_code = :qr_code WHERE studentid = :studentid");
        $stmt->execute([
            'qr_code' => $webPath,
            'studentid' => $studentId
        ]);

        // ส่งข้อมูลกลับ
        echo json_encode([
            'status' => 'success',
            'qr_code' => $webPath,
            'student' => $studentData
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลนักเรียน']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการสร้าง QR Code']);
}
?>