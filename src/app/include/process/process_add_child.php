<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// เชื่อมต่อฐานข้อมูล
require_once(__DIR__ . '/../../../config/database.php');

try {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['student_id']) || empty(trim($_POST['student_id']))) {
        throw new Exception('กรุณากรอกรหัสนักเรียน');
    }

    if (!isset($_POST['academic_year']) || empty(trim($_POST['academic_year']))) {
        throw new Exception('กรุณาเลือกปีการศึกษา');
    }

    // เชื่อมต่อฐานข้อมูล
    $conn = getDatabaseConnection();

    // ตรวจสอบรหัสนักเรียนซ้ำ
    $checkStudent = $conn->prepare("SELECT studentid FROM children WHERE studentid = ?");
    $checkStudent->execute([$_POST['student_id']]);
    if ($checkStudent->rowCount() > 0) {
        throw new Exception('รหัสนักเรียนนี้มีในระบบแล้ว');
    }

    // ตรวจสอบเลขบัตรประชาชนซ้ำ (ถ้ามีการกรอก)
    if (!empty($_POST['id_card']) && $_POST['id_card'] !== '-') {
        $checkIdCard = $conn->prepare("SELECT id_card FROM children WHERE id_card = ?");
        $checkIdCard->execute([$_POST['id_card']]);
        if ($checkIdCard->rowCount() > 0) {
            throw new Exception('เลขบัตรประชาชนนี้มีในระบบแล้ว');
        }
    }

    // ฟังก์ชันสำหรับจัดการค่าว่าง
    function handleEmptyValue($value) {
        return (empty(trim($value)) || $value === '-') ? null : htmlspecialchars($value);
    }

    // รับค่าและทำความสะอาดข้อมูล
    $student_id = htmlspecialchars($_POST['student_id']);
    $academic_year = htmlspecialchars($_POST['academic_year']);
    $child_group = htmlspecialchars($_POST['child_group'] ?? '');
    $classroom = htmlspecialchars($_POST['classroom'] ?? '');
    $id_card = handleEmptyValue($_POST['id_card']);
    $nickname = htmlspecialchars($_POST['nickname'] ?? '');
    $prefix_th = htmlspecialchars($_POST['prefix_th'] ?? '');
    $first_name_th = htmlspecialchars($_POST['first_name_th'] ?? '');
    $last_name_th = htmlspecialchars($_POST['last_name_th'] ?? '');
    $prefix_en = htmlspecialchars($_POST['prefix_en'] ?? '');
    $first_name_en = htmlspecialchars($_POST['first_name_en'] ?? '');
    $last_name_en = htmlspecialchars($_POST['last_name_en'] ?? '');
    $father_first_name = handleEmptyValue($_POST['father_first_name']);
    $father_last_name = handleEmptyValue($_POST['father_last_name']);
    $father_phone = handleEmptyValue($_POST['father_phone']);
    $father_phone_backup = handleEmptyValue($_POST['father_phone_backup']);
    $mother_first_name = handleEmptyValue($_POST['mother_first_name']);
    $mother_last_name = handleEmptyValue($_POST['mother_last_name']);
    $mother_phone = handleEmptyValue($_POST['mother_phone']);
    $mother_phone_backup = handleEmptyValue($_POST['mother_phone_backup']);

    // จัดการการอัปโหลดรูปภาพ
    $profile_image = null;
    if (!empty($_POST['profile_image_data'])) {
        try {
            $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['profile_image_data']));
            
            // ตรวจสอบขนาดไฟล์
            $maxSize = 5 * 1024 * 1024; // 5MB
            if (strlen($image_data) > $maxSize) {
                throw new Exception('ขนาดไฟล์เกิน 5MB');
            }

            // สร้างชื่อไฟล์ใหม่
            $filename = 'profile_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $student_id) . '.jpg';
            
            // กำหนด path ที่ถูกต้อง
            $uploadDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/uploads/profiles/';
            $uploadPath = $uploadDir . $filename;

            // บันทึกไฟล์
            if (file_put_contents($uploadPath, $image_data)) {
                chmod($uploadPath, 0644);
                $profile_image = '../../../public/uploads/profiles/' . $filename;
            } else {
                throw new Exception("ไม่สามารถบันทึกไฟล์รูปภาพได้");
            }
        } catch (Exception $e) {
            error_log("Error saving image: " . $e->getMessage());
            $profile_image = null;
        }
    }

    // SQL สำหรับเพิ่มข้อมูล
    $sql = "INSERT INTO children (
        studentid, academic_year, id_card, prefix_th, firstname_th, lastname_th,
        prefix_en, firstname_en, lastname_en, nickname,
        child_group, classroom, profile_image,
        father_first_name, father_last_name, father_phone, father_phone_backup,
        mother_first_name, mother_last_name, mother_phone, mother_phone_backup
    ) VALUES (
        :studentid, :academic_year, :id_card, :prefix_th, :firstname_th, :lastname_th,
        :prefix_en, :firstname_en, :lastname_en, :nickname,
        :child_group, :classroom, :profile_image,
        :father_first_name, :father_last_name, :father_phone, :father_phone_backup,
        :mother_first_name, :mother_last_name, :mother_phone, :mother_phone_backup
    )";

    $stmt = $conn->prepare($sql);

    // ผูกค่าพารามิเตอร์
    $stmt->execute([
        ':studentid' => $student_id,
        ':academic_year' => $academic_year,
        ':id_card' => $id_card,
        ':prefix_th' => $prefix_th,
        ':firstname_th' => $first_name_th,
        ':lastname_th' => $last_name_th,
        ':prefix_en' => $prefix_en,
        ':firstname_en' => $first_name_en,
        ':lastname_en' => $last_name_en,
        ':nickname' => $nickname,
        ':child_group' => $child_group,
        ':classroom' => $classroom,
        ':profile_image' => $profile_image,
        ':father_first_name' => $father_first_name,
        ':father_last_name' => $father_last_name,
        ':father_phone' => $father_phone,
        ':father_phone_backup' => $father_phone_backup,
        ':mother_first_name' => $mother_first_name,
        ':mother_last_name' => $mother_last_name,
        ':mother_phone' => $mother_phone,
        ':mother_phone_backup' => $mother_phone_backup
    ]);

    echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลเด็กสำเร็จ']);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>
