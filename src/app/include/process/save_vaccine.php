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
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($data['vaccine_list_id']) || !isset($data['vaccine_date']) || !isset($data['studentid'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    $pdo = getDatabaseConnection();

    // ดึงชื่อวัคซีนจากตาราง vaccine_list
    $stmt = $pdo->prepare("SELECT vaccine_name FROM vaccine_list WHERE id = :vaccine_list_id");
    $stmt->execute(['vaccine_list_id' => $data['vaccine_list_id']]);
    $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vaccine) {
        throw new Exception('ไม่พบข้อมูลวัคซีน');
    }

    // จัดการรูปภาพ
    $image_path = null;
    if (!empty($data['image'])) {
        try {
            $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image']));
            
            // ตรวจสอบขนาดไฟล์
            $maxSize = 5 * 1024 * 1024; // 5MB
            if (strlen($image_data) > $maxSize) {
                throw new Exception('ขนาดไฟล์เกิน 5MB');
            }

            // สร้างชื่อไฟล์ใหม่
            $filename = 'vaccine_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $data['studentid']) . '.jpg';
            
            // กำหนด path ที่ถูกต้อง
            $uploadDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/uploads/vaccines/';
            
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($uploadDir)) {
                if (!@mkdir($uploadDir, 0755, true)) {
                    error_log("Failed to create directory: " . $uploadDir);
                    throw new Exception("ไม่สามารถสร้างโฟลเดอร์สำหรับเก็บรูปภาพได้");
                }
            }

            // ตรวจสอบสิทธิ์การเขียน
            if (!is_writable($uploadDir)) {
                if (!@chmod($uploadDir, 0755)) {
                    error_log("Failed to change directory permissions: " . $uploadDir);
                    throw new Exception("ไม่มีสิทธิ์ในการเขียนไฟล์");
                }
            }

            $uploadPath = $uploadDir . $filename;

            // บันทึกไฟล์
            if (file_put_contents($uploadPath, $image_data)) {
                // ตั้งค่าสิทธิ์ไฟล์
                chmod($uploadPath, 0644);
                
                // บันทึก path สัมพัทธ์
                $image_path = '../../../public/uploads/vaccines/' . $filename;
            } else {
                error_log("Failed to save file to: " . $uploadPath);
                throw new Exception("ไม่สามารถบันทึกไฟล์รูปภาพได้");
            }
        } catch (Exception $e) {
            error_log("Error saving image: " . $e->getMessage());
            // ถ้าเกิดข้อผิดพลาดในการบันทึกรูป ให้เก็บ path เป็น null และดำเนินการต่อ
            $image_path = null;
        }
    }

    // เตรียมข้อมูลสำหรับบันทึก
    $params = [
        'vaccine_list_id' => $data['vaccine_list_id'],
        'student_id' => $data['studentid'],
        'vaccine_date' => $data['vaccine_date'],
        'vaccine_name' => $vaccine['vaccine_name'],
        'vaccine_number' => $data['vaccine_number'] ?? null,
        'vaccine_location' => $data['vaccine_location'] ?? null,
        'vaccine_provider' => $data['vaccine_provider'] ?? null,
        'lot_number' => $data['lot_number'] ?? null,
        'next_appointment' => $data['next_appointment'] ?: null,
        'vaccine_note' => $data['vaccine_note'] ?? null,
        'image_path' => $image_path
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
                image_path = :image_path,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $params['id'] = $data['id'];
    } else {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO vaccines (
                vaccine_list_id, student_id, vaccine_date, vaccine_name, vaccine_number,
                vaccine_location, vaccine_provider, lot_number,
                next_appointment, vaccine_note, image_path, created_at, updated_at
            ) VALUES (
                :vaccine_list_id, :student_id, :vaccine_date, :vaccine_name, :vaccine_number,
                :vaccine_location, :vaccine_provider, :lot_number,
                :next_appointment, :vaccine_note, :image_path, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
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