<?php
require_once(__DIR__ . '/../../../config/database.php');
require_once(__DIR__ . '/../../../phpqrcode/qrlib.php');

header('Content-Type: application/json');

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// รับข้อมูลจาก request body
$data = json_decode(file_get_contents('php://input'), true);
$group = $data['group'] ?? 'all';
$classroom = $data['classroom'] ?? 'all';
$withoutQR = $data['without_qr'] ?? true;

try {
    $pdo = getDatabaseConnection();

    // สร้าง query สำหรับดึงข้อมูลนักเรียน
    $query = "SELECT * FROM public.children WHERE 1=1";
    $params = [];

    // เพิ่มเงื่อนไขตามกลุ่มเรียน
    if ($group !== 'all') {
        $childGroup = '';
        switch ($group) {
            case 'big':
                $childGroup = 'เด็กโต';
                break;
            case 'medium':
                $childGroup = 'เด็กกลาง';
                break;
            case 'prep':
                $childGroup = 'เตรียมอนุบาล';
                break;
        }
        $query .= " AND child_group = :child_group";
        $params[':child_group'] = $childGroup;
    }

    // เพิ่มเงื่อนไขตามห้องเรียน
    if ($classroom !== 'all') {
        $query .= " AND classroom = :classroom";
        $params[':classroom'] = $classroom;
    }

    // เพิ่มเงื่อนไขสำหรับเด็กที่ยังไม่มี QR Code
    if ($withoutQR) {
        $query .= " AND (qr_code IS NULL OR qr_code = '')";
    }

    // ดึงข้อมูลนักเรียน
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // กำหนดที่เก็บไฟล์ QR Code
    $uploadDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/uploads/qrcodes/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $count = 0;
    foreach ($children as $child) {
        // สร้างข้อมูลสำหรับ QR Code
        $studentData = [
            'student_id' => $child['studentid'],
            'prefix' => $child['prefix_th'],
            'first_name' => $child['firstname_th'],
            'last_name' => $child['lastname_th'],
            'nick_name' => $child['nickname'],
            'classroom' => $child['classroom'],
            'child_group' => $child['child_group']
        ];

        $qrContent = json_encode($studentData);
        
        // สร้างชื่อไฟล์
        $filename = 'qr_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $child['studentid']) . '.png';
        $uploadPath = $uploadDir . $filename;
        $webPath = '../../../public/uploads/qrcodes/' . $filename;

        // สร้าง QR Code
        QRcode::png($qrContent, $uploadPath, QR_ECLEVEL_L, 10);
        chmod($uploadPath, 0644);

        // บันทึกข้อมูล QR Code ลงฐานข้อมูล
        $updateStmt = $pdo->prepare("UPDATE children SET qr_code = :qr_code WHERE studentid = :studentid");
        $updateStmt->execute([
            'qr_code' => $webPath,
            'studentid' => $child['studentid']
        ]);

        $count++;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'สร้าง QR Code สำเร็จ',
        'count' => $count
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการสร้าง QR Code: ' . $e->getMessage()
    ]);
} 