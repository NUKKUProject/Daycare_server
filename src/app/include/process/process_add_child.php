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

    foreach ([
        'child_group' => 'กรุณาเลือกกลุ่มเด็ก',
        'classroom' => 'กรุณาเลือกห้องเรียน',
        'firstname_th' => 'กรุณากรอกชื่อ (ไทย)',
        'lastname_th' => 'กรุณากรอกนามสกุล (ไทย)'
    ] as $field => $message) {
        $value = $_POST[$field] ?? $_POST[str_replace('name', '_name', $field)] ?? '';
        if (empty(trim((string) $value))) {
            throw new Exception($message);
        }
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
        $value = is_string($value) ? trim($value) : $value;
        return (empty($value) || $value === '-') ? null : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    function uploadChildImage($field, $studentId, $folder, $prefix) {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES[$field]['type'], $allowedTypes, true) || $_FILES[$field]['size'] > 5 * 1024 * 1024) {
            throw new Exception('ไฟล์รูปภาพไม่ถูกต้องหรือมีขนาดเกิน 5MB');
        }

        $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $studentId) . '_' . $prefix . '_' . uniqid() . '.' . $extension;
        $uploadDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/public/uploads/' . $folder . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new Exception('ไม่สามารถสร้างโฟลเดอร์อัปโหลดรูปภาพได้');
        }
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $filename)) {
            throw new Exception('ไม่สามารถบันทึกไฟล์รูปภาพได้');
        }

        return '../../../public/uploads/' . $folder . '/' . $filename;
    }

    function toPostgresTextArray($values) {
        $values = is_array($values) ? array_values(array_filter($values, static function ($value) {
            return $value !== null && $value !== '';
        })) : [];
        $escaped = array_map(static function ($value) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value) . '"';
        }, $values);
        return '{' . implode(',', $escaped) . '}';
    }

    // รับค่าและทำความสะอาดข้อมูล
    $student_id = htmlspecialchars($_POST['student_id']);
    $academic_year = htmlspecialchars($_POST['academic_year']);
    $child_group = htmlspecialchars($_POST['child_group'] ?? '');
    $classroom = htmlspecialchars($_POST['classroom'] ?? '');
    $id_card = handleEmptyValue($_POST['id_card'] ?? null);
    $nickname = handleEmptyValue($_POST['nickname'] ?? null);
    $prefix_th = handleEmptyValue($_POST['prefix_th'] ?? null);
    $first_name_th = handleEmptyValue($_POST['firstname_th'] ?? $_POST['first_name_th'] ?? null);
    $last_name_th = handleEmptyValue($_POST['lastname_th'] ?? $_POST['last_name_th'] ?? null);
    $prefix_en = handleEmptyValue($_POST['prefix_en'] ?? null);
    $first_name_en = handleEmptyValue($_POST['firstname_en'] ?? $_POST['first_name_en'] ?? null);
    $last_name_en = handleEmptyValue($_POST['lastname_en'] ?? $_POST['last_name_en'] ?? null);
    $birthday = handleEmptyValue($_POST['birthday'] ?? null);
    $race = handleEmptyValue($_POST['race'] ?? null);
    $nationality = handleEmptyValue($_POST['nationality'] ?? null);
    $religion = handleEmptyValue($_POST['religion'] ?? null);
    $height = handleEmptyValue($_POST['height'] ?? null);
    $weight = handleEmptyValue($_POST['weight'] ?? null);
    $sex = handleEmptyValue($_POST['sex'] ?? null);
    $congenital_disease = handleEmptyValue($_POST['congenital_disease'] ?? null);
    $blood_type = handleEmptyValue($_POST['blood_type'] ?? null);
    $father_first_name = handleEmptyValue($_POST['father_first_name'] ?? null);
    $father_last_name = handleEmptyValue($_POST['father_last_name'] ?? null);
    $father_phone = handleEmptyValue($_POST['father_phone'] ?? null);
    $father_phone_backup = handleEmptyValue($_POST['father_phone_backup'] ?? null);
    $mother_first_name = handleEmptyValue($_POST['mother_first_name'] ?? null);
    $mother_last_name = handleEmptyValue($_POST['mother_last_name'] ?? null);
    $mother_phone = handleEmptyValue($_POST['mother_phone'] ?? null);
    $mother_phone_backup = handleEmptyValue($_POST['mother_phone_backup'] ?? null);
    $relative_first_name = handleEmptyValue($_POST['relative_first_name'] ?? null);
    $relative_last_name = handleEmptyValue($_POST['relative_last_name'] ?? null);
    $relative_phone = handleEmptyValue($_POST['relative_phone'] ?? null);
    $relative_phone_backup = handleEmptyValue($_POST['relative_phone_backup'] ?? null);
    $address = handleEmptyValue($_POST['address'] ?? null);
    $district = handleEmptyValue($_POST['district'] ?? null);
    $amphoe = handleEmptyValue($_POST['amphoe'] ?? null);
    $province = handleEmptyValue($_POST['province'] ?? null);
    $zipcode = handleEmptyValue($_POST['zipcode'] ?? null);
    $emergency_contact = handleEmptyValue($_POST['emergency_contact'] ?? null);
    $emergency_phone = handleEmptyValue($_POST['emergency_phone'] ?? null);
    $emergency_relation = handleEmptyValue($_POST['emergency_relation'] ?? null);
    $allergic_food = handleEmptyValue($_POST['allergic_food'] ?? null);
    $allergic_medicine = handleEmptyValue($_POST['allergic_medicine'] ?? null);
    $drugItems = is_array($_POST['drug_items'] ?? null) ? $_POST['drug_items'] : [];
    $foodItems = is_array($_POST['food_items'] ?? null) ? $_POST['food_items'] : [];
    $has_food_allergy_history = !empty($allergic_food);
    $has_drug_allergy_history = !empty($allergic_medicine);

    // จัดการการอัปโหลดรูปภาพจากฟอร์มเต็มรูปแบบ
    $profile_image = uploadChildImage('profile_image', $student_id, 'profiles', 'profile');
    $father_image = uploadChildImage('father_image', $student_id, 'parents', 'father');
    $mother_image = uploadChildImage('mother_image', $student_id, 'parents', 'mother');
    $relative_image = uploadChildImage('relative_image', $student_id, 'parents', 'relative');

    // รองรับรูปแบบเดิมที่ส่งรูปโปรไฟล์เป็น base64
    if (!$profile_image && !empty($_POST['profile_image_data'])) {
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
        prefix_en, firstname_en, lastname_en, nickname, birthday, race, nationality,
        religion, height, weight, sex, congenital_disease, child_group, classroom,
        profile_image, father_first_name, father_last_name, father_phone, father_phone_backup,
        father_image, mother_first_name, mother_last_name, mother_phone, mother_phone_backup,
        mother_image, relative_first_name, relative_last_name, relative_phone, relative_phone_backup,
        relative_image, blood_type, allergic_food, allergic_medicine, address, district, amphoe,
        province, zipcode, emergency_contact, emergency_phone, emergency_relation,
        has_drug_allergy_history, has_food_allergy_history
    ) VALUES (
        :studentid, :academic_year, :id_card, :prefix_th, :firstname_th, :lastname_th,
        :prefix_en, :firstname_en, :lastname_en, :nickname, :birthday, :race, :nationality,
        :religion, :height, :weight, :sex, :congenital_disease, :child_group, :classroom,
        :profile_image, :father_first_name, :father_last_name, :father_phone, :father_phone_backup,
        :father_image, :mother_first_name, :mother_last_name, :mother_phone, :mother_phone_backup,
        :mother_image, :relative_first_name, :relative_last_name, :relative_phone, :relative_phone_backup,
        :relative_image, :blood_type, :allergic_food, :allergic_medicine, :address, :district, :amphoe,
        :province, :zipcode, :emergency_contact, :emergency_phone, :emergency_relation,
        :has_drug_allergy_history, :has_food_allergy_history
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
        ':birthday' => $birthday,
        ':race' => $race,
        ':nationality' => $nationality,
        ':religion' => $religion,
        ':height' => $height,
        ':weight' => $weight,
        ':sex' => $sex,
        ':congenital_disease' => $congenital_disease,
        ':child_group' => $child_group,
        ':classroom' => $classroom,
        ':profile_image' => $profile_image,
        ':father_first_name' => $father_first_name,
        ':father_last_name' => $father_last_name,
        ':father_phone' => $father_phone,
        ':father_phone_backup' => $father_phone_backup,
        ':father_image' => $father_image,
        ':mother_first_name' => $mother_first_name,
        ':mother_last_name' => $mother_last_name,
        ':mother_phone' => $mother_phone,
        ':mother_phone_backup' => $mother_phone_backup,
        ':mother_image' => $mother_image,
        ':relative_first_name' => $relative_first_name,
        ':relative_last_name' => $relative_last_name,
        ':relative_phone' => $relative_phone,
        ':relative_phone_backup' => $relative_phone_backup,
        ':relative_image' => $relative_image,
        ':blood_type' => $blood_type,
        ':allergic_food' => $allergic_food,
        ':allergic_medicine' => $allergic_medicine,
        ':address' => $address,
        ':district' => $district,
        ':amphoe' => $amphoe,
        ':province' => $province,
        ':zipcode' => $zipcode,
        ':emergency_contact' => $emergency_contact,
        ':emergency_phone' => $emergency_phone,
        ':emergency_relation' => $emergency_relation,
        ':has_drug_allergy_history' => $has_drug_allergy_history ? 'true' : 'false',
        ':has_food_allergy_history' => $has_food_allergy_history ? 'true' : 'false'
    ]);

    // บันทึกรายละเอียดการแพ้ให้เหมือนกับหน้าประวัติประจำตัว
    $drugInsert = $conn->prepare("INSERT INTO drug_allergies
        (student_id, drug_name, detection_method, symptoms, has_allergy_card)
        VALUES (:student_id, :drug_name, :detection_method, :symptoms, :has_allergy_card)");
    foreach ($drugItems as $item) {
        if (!is_array($item) || (empty($item['drug_name']) && empty($item['detection_method']) && empty($item['symptoms']))) {
            continue;
        }
        $drugInsert->execute([
            ':student_id' => $student_id,
            ':drug_name' => $item['drug_name'] ?? '',
            ':detection_method' => $item['detection_method'] ?? '',
            ':symptoms' => $item['symptoms'] ?? '',
            ':has_allergy_card' => !empty($item['has_allergy_card']) ? 'true' : 'false'
        ]);
        $has_drug_allergy_history = true;
    }

    $foodInsert = $conn->prepare("INSERT INTO food_allergies
        (student_id, food_name, detection_method, digestive_symptoms, skin_symptoms, respiratory_symptoms)
        VALUES (:student_id, :food_name, :detection_method, CAST(:digestive_symptoms AS text[]), CAST(:skin_symptoms AS text[]), CAST(:respiratory_symptoms AS text[]))");
    foreach ($foodItems as $item) {
        if (!is_array($item)) {
            continue;
        }
        $digestive = $item['digestive_symptoms'] ?? [];
        $skin = $item['skin_symptoms'] ?? [];
        $respiratory = $item['respiratory_symptoms'] ?? [];
        if (empty($item['food_name']) && empty($item['detection_method']) && empty($digestive) && empty($skin) && empty($respiratory)) {
            continue;
        }
        $foodInsert->execute([
            ':student_id' => $student_id,
            ':food_name' => $item['food_name'] ?? '',
            ':detection_method' => $item['detection_method'] ?? '',
            ':digestive_symptoms' => toPostgresTextArray($digestive),
            ':skin_symptoms' => toPostgresTextArray($skin),
            ':respiratory_symptoms' => toPostgresTextArray($respiratory)
        ]);
        $has_food_allergy_history = true;
    }

    $flagUpdate = $conn->prepare("UPDATE children SET has_drug_allergy_history = :drug_flag, has_food_allergy_history = :food_flag WHERE studentid = :student_id");
    $flagUpdate->execute([
        ':drug_flag' => $has_drug_allergy_history ? 'true' : 'false',
        ':food_flag' => $has_food_allergy_history ? 'true' : 'false',
        ':student_id' => $student_id
    ]);

    echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลเด็กสำเร็จ']);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>
