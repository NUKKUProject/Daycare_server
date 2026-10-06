<?php
require_once(__DIR__ . '/../../../config/database.php');
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher', 'student'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
    exit;
}

try {
    // รองรับทั้งแบบ multipart (มีรูปแนบ) และ JSON (แบบเดิม)
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {
        if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
            throw new Exception('ไฟล์รูปมีขนาดใหญ่เกินกำหนดของเซิร์ฟเวอร์ กรุณาเลือกรูปที่เล็กลง');
        }
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
    }

    // รองรับทั้ง studentid และ student_id
    $student_id = $data['studentid'] ?? $data['student_id'] ?? null;
    
    // ตรวจสอบข้อมูลที่จำเป็น - ใช้ empty() เพื่อตรวจสอบค่าว่างด้วย
    if (empty($data['vaccine_list_id']) || empty($data['vaccine_date']) || empty($student_id)) {
        throw new Exception('ข้อมูลไม่ครบถ้วน กรุณากรอกรหัสวัคซีน วันที่ฉีด และรหัสนักเรียน');
    }

    $pdo = getDatabaseConnection();

    // ผู้ปกครอง (student) บันทึก/แก้ไขได้เฉพาะของเด็กตัวเอง
    if ($_SESSION['role'] === 'student') {
        if ($student_id !== ($_SESSION['username'] ?? '')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
            exit;
        }
        // ถ้าเป็นการแก้ไขรายการเดิม ต้องเช็คว่ารายการนั้นเป็นของเด็กตัวเองจริง (กันแก้ไขรายการของเด็กคนอื่นผ่าน id)
        if (!empty($data['id'])) {
            $ownerStmt = $pdo->prepare("SELECT student_id FROM vaccines WHERE id = :id");
            $ownerStmt->execute(['id' => $data['id']]);
            $ownerStudentId = $ownerStmt->fetchColumn();
            if ($ownerStudentId === false || $ownerStudentId !== ($_SESSION['username'] ?? '')) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
                exit;
            }
        }
    }

    // ดึงชื่อวัคซีนจากตาราง vaccine_list
    $stmt = $pdo->prepare("SELECT vaccine_name FROM vaccine_list WHERE id = :vaccine_list_id");
    $stmt->execute(['vaccine_list_id' => $data['vaccine_list_id']]);
    $vaccine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vaccine) {
        throw new Exception('ไม่พบข้อมูลวัคซีน');
    }

    // ===== รูปประกอบการฉีดวัคซีน =====
    $uploadDir = __DIR__ . '/../../../public/uploads/vaccines/';
    $imagePrefix = '../../../public/uploads/vaccines/';
    $newImagePath = null;
    $removeImage = !empty($data['remove_image']);
    $oldImagePath = null;

    if (!empty($data['id'])) {
        $oldStmt = $pdo->prepare("SELECT image_path FROM vaccines WHERE id = :id");
        $oldStmt->execute(['id' => $data['id']]);
        $oldImagePath = $oldStmt->fetchColumn() ?: null;
    }

    $deleteImageFile = function ($path) use ($uploadDir) {
        if (!$path) {
            return;
        }
        // ลบเฉพาะไฟล์ที่อยู่ในโฟลเดอร์รูปวัคซีนเท่านั้น
        $file = realpath($uploadDir . basename($path));
        $dir = realpath($uploadDir);
        if ($file && $dir && strpos($file, $dir) === 0 && is_file($file)) {
            @unlink($file);
        }
    };

    if (isset($_FILES['vaccine_image']) && $_FILES['vaccine_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['vaccine_image'];
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception('ไฟล์รูปมีขนาดใหญ่เกินกำหนด กรุณาเลือกรูปที่เล็กลง');
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('ไฟล์รูปต้องไม่เกิน 5 MB');
        }

        // ตรวจชนิดไฟล์จากเนื้อไฟล์จริง ไม่เชื่อชนิดที่เบราว์เซอร์ส่งมา
        $info = @getimagesize($file['tmp_name']);
        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$info || !isset($extMap[$info['mime']])) {
            throw new Exception('รองรับเฉพาะไฟล์รูป JPG, PNG หรือ WebP');
        }

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new Exception('ไม่สามารถสร้างโฟลเดอร์เก็บรูปได้');
        }

        $safeStudent = preg_replace('/[^A-Za-z0-9_-]/', '', $student_id);
        $fileName = 'vaccine_' . time() . '_' . $safeStudent . '_' . bin2hex(random_bytes(3)) . '.' . $extMap[$info['mime']];
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            throw new Exception('ไม่สามารถบันทึกไฟล์รูปได้');
        }
        $newImagePath = $imagePrefix . $fileName;
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

    // ส่วน SQL ของรูป: อัปโหลดใหม่ = เปลี่ยนรูป, ติ๊กลบ = ลบรูป, ไม่ทำอะไร = คงรูปเดิม
    $imageSet = '';
    if ($newImagePath !== null) {
        $imageSet = 'image_path = :image_path,';
    } elseif ($removeImage) {
        $imageSet = 'image_path = NULL,';
    }

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
                {$imageSet}
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $params['id'] = $data['id'];
        if ($newImagePath !== null) {
            $params['image_path'] = $newImagePath;
        }
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
        $params['image_path'] = $newImagePath;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
    } catch (Throwable $dbError) {
        // บันทึกฐานข้อมูลไม่สำเร็จ -> ลบรูปที่เพิ่งอัปโหลดทิ้ง ไม่ให้ไฟล์ค้าง
        $deleteImageFile($newImagePath);
        throw $dbError;
    }

    if ($result) {
        // เปลี่ยนรูปหรือลบรูป -> ลบไฟล์เก่าออกจากเครื่อง
        if ($oldImagePath && ($newImagePath !== null || $removeImage)) {
            $deleteImageFile($oldImagePath);
        }
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลสำเร็จ']);
    } else {
        $deleteImageFile($newImagePath);
        throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
    }

} catch (Exception $e) {
    error_log("Error in save_vaccine: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>