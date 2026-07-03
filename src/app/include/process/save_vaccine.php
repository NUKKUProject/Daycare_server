<?php
require_once(__DIR__ . '/../../../config/database.php');
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // รองรับทั้ง studentid และ student_id
    $student_id = $data['studentid'] ?? $data['student_id'] ?? null;
    
    // ตรวจสอบข้อมูลที่จำเป็น - ใช้ empty() เพื่อตรวจสอบค่าว่างด้วย
    if (empty($data['vaccine_list_id']) || empty($data['vaccine_date']) || empty($student_id)) {
        throw new Exception('ข้อมูลไม่ครบถ้วน กรุณากรอกรหัสวัคซีน วันที่ฉีด และรหัสนักเรียน');
    }

    $pdo = getDatabaseConnection();

    // ดึงชื่อวัคซีนจากตาราง vaccine_list
    $stmt = $pdo->prepare("SELECT vaccine_name FROM vaccine_list WHERE id = :vaccine_list_id");
    $stmt->execute(['vaccine_list_id' => $data['vaccine_list_id']]);
    $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vaccine) {
        throw new Exception('ไม่พบข้อมูลวัคซีน');
    }

// เตรียมข้อมูลสำหรับบันทึก
    $params = [
        'vaccine_list_id' => $data['vaccine_list_id'],
        'student_id' => $student_id,
        'vaccine_date' => $data['vaccine_date'],
        'vaccine_name' => $vaccine['vaccine_name'],
        'vaccine_number' => $data['vaccine_number'] ?? null,
        'vaccine_location' => $data['vaccine_location'] ?? null,
        'vaccine_provider' => $data['vaccine_provider'] ?? null,
        'lot_number' => $data['lot_number'] ?? null,
        'next_appointment' => $data['next_appointment'] ?: null,
        'vaccine_note' => $data['vaccine_note'] ?? null
    ];

    if (isset($data['id']) && !empty($data['id'])) {
        // อัพเดทข้อมูล
        $sql = "UPDATE vaccines SET 
                vaccine_list_id = :vaccine_list_id,
                student_id = :student_id,
                vaccine_date = :vaccine_date,
                vaccine_name = :vaccine_name,
                vaccine_number = :vaccine_number,
                vaccine_location = :vaccine_location,
                vaccine_provider = :vaccine_provider,
                lot_number = :lot_number,
                next_appointment = :next_appointment,
                vaccine_note = :vaccine_note,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $params['id'] = $data['id'];
    } else {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO vaccines (
                vaccine_list_id, student_id, vaccine_date, vaccine_name, vaccine_number,
                vaccine_location, vaccine_provider, lot_number,
                next_appointment, vaccine_note, created_at, updated_at
            ) VALUES (
                :vaccine_list_id, :student_id, :vaccine_date, :vaccine_name, :vaccine_number,
                :vaccine_location, :vaccine_provider, :lot_number,
                :next_appointment, :vaccine_note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )";
    }

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลสำเร็จ']);
    } else {
        throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
    }

} catch (Exception $e) {
    error_log("Error in save_vaccine: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>