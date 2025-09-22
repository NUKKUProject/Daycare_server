<?php
// ตั้งค่า error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เพิ่มขนาดการอัปโหลด
ini_set('upload_max_filesize', '12M');
ini_set('post_max_size', '13M');
ini_set('memory_limit', '256M');

// เริ่ม output buffering และ session
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../config/database.php');// รวมไฟล์สำหรับเชื่อมต่อฐานข้อมูล

function sendJsonResponse($status, $message, $code = 200) {
    ob_clean(); // ล้าง buffer
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit();
}

// ฟังก์ชันสำหรับจัดการค่าว่างหรือค่า null
function handleEmptyValue($value, $default = null) {
    return (empty(trim($value)) || $value === '-') ? $default : $value;
}

try {
    // ตรวจสอบสิทธิ์
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendJsonResponse('error', 'Unauthorized access', 403);
    }

    // ฟังก์ชันสำหรับอัปเดตหรือแทรกข้อมูลเด็ก
    function updateChildById(
        $id,
        $student_id,
        $academic_year,
        $prefix_th,
        $first_name_th,
        $last_name_th,
        $prefix_en,
        $first_name_en,
        $last_name_en,
        $id_card,
        $issue_at,
        $issue_date,
        $expiry_date,
        $race,
        $nationality,
        $religion,
        $age_student,
        $birthday,
        $place_birth,
        $height,
        $weight,
        $sex,
        $congenital_disease,
        $classroom,
        $child_group,
        $nickname,
        $father_first_name,
        $father_last_name,
        $father_phone,
        $mother_first_name,
        $mother_last_name,
        $mother_phone,
        $profile_image,
        $father_image,
        $mother_image,
        $relative_image,
        $blood_type,
        $allergic_food,
        $allergic_medicine,
        $address,
        $district,
        $amphoe,
        $province,
        $zipcode,
        $emergency_contact,
        $emergency_phone,
        $emergency_relation,
        $relative_first_name,
        $relative_last_name,
        $relative_phone,
        $relative_phone_backup,
        $age_years,
        $age_months,
        $age_days,
        $has_drug_allergy_history,
        $has_food_allergy_history
    ) {
        try {
            $pdo = getDatabaseConnection();
            
            // Debug log เพิ่มเติม
            error_log("Starting updateChildById with data:");
            error_log("Student ID: " . $student_id);
            error_log("Academic Year: " . $academic_year);
            error_log("Race: " . $race);
            error_log("Age: " . $age_student);

            // เปิดใช้ PDO exceptions
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ตรวจสอบว่าเด็กมีอยู่ในฐานข้อมูลแล้วหรือไม่
            $query = "SELECT COUNT(*) FROM children WHERE studentid = :studentid";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':studentid', $student_id, PDO::PARAM_STR);
            $stmt->execute();
            $exists = $stmt->fetchColumn();
            
            error_log("Student exists check: " . ($exists ? "Yes" : "No"));

            // สร้าง query
            if ($exists > 0) {
                $query = "UPDATE children SET 
                        studentid = :studentid,
                        academic_year = :academic_year,
                        prefix_th = :prefix_th,
                        firstname_th = :firstname_th,
                        lastname_th = :lastname_th,
                        prefix_en = :prefix_en,
                        firstname_en = :firstname_en,
                        lastname_en = :lastname_en,
                        id_card = :id_card,
                        issue_at = :issue_at,
                        issue_date = :issue_date,
                        expiry_date = :expiry_date,
                        race = :race,
                        nationality = :nationality,
                        religion = :religion,
                        age_student = :age_student,
                        age_years = :age_years,
                        age_months = :age_months,
                        age_days = :age_days,
                        birthday = :birthday,
                        place_birth = :place_birth,
                        height = :height,
                        weight = :weight,
                        sex = :sex,
                        congenital_disease = :congenital_disease,
                        classroom = :classroom,
                        child_group = :child_group,
                        nickname = :nickname,
                        father_first_name = :father_first_name,
                        father_last_name = :father_last_name,
                        father_phone = :father_phone,
                        mother_first_name = :mother_first_name,
                        mother_last_name = :mother_last_name,
                        mother_phone = :mother_phone,
                        profile_image = :profile_image,
                        father_image = :father_image,
                        mother_image = :mother_image,
                        relative_image = :relative_image,
                        blood_type = :blood_type,
                        allergic_food = :allergic_food,
                        allergic_medicine = :allergic_medicine,
                        address = :address,
                        district = :district,
                        amphoe = :amphoe,
                        province = :province,
                        zipcode = :zipcode,
                        emergency_contact = :emergency_contact,
                        emergency_phone = :emergency_phone,
                        emergency_relation = :emergency_relation,
                        relative_first_name = :relative_first_name,
                        relative_last_name = :relative_last_name,
                        relative_phone = :relative_phone,
                        relative_phone_backup = :relative_phone_backup,
                        has_drug_allergy_history = :has_drug_allergy_history,
                        has_food_allergy_history = :has_food_allergy_history,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE studentid = :studentid";
                
                error_log("Using UPDATE query");
            } else {
                $query = "INSERT INTO children (
                        studentid, academic_year,
                        prefix_th, firstname_th, lastname_th, prefix_en, 
                        firstname_en, lastname_en, id_card, issue_at, issue_date, 
                        expiry_date, race, nationality, religion, age_student, age_years, age_months, age_days,
                        birthday, place_birth, height, weight, sex, congenital_disease, classroom, child_group, nickname, father_first_name, 
                        father_last_name, father_phone, mother_first_name, mother_last_name, 
                        mother_phone, profile_image, father_image, mother_image, relative_image, blood_type,
                        allergic_food, allergic_medicine, address, district, amphoe, province, 
                        zipcode, emergency_contact, emergency_phone, emergency_relation,
                        relative_first_name, relative_last_name,
                        relative_phone, relative_phone_backup,
                        has_drug_allergy_history,
                        has_food_allergy_history,
                        created_at) 
                    VALUES (
                        :studentid, :academic_year,
                        :prefix_th, :firstname_th, :lastname_th, :prefix_en, 
                        :firstname_en, :lastname_en, :id_card, :issue_at, :issue_date, 
                        :expiry_date, :race, :nationality, :religion, :age_student, :age_years, :age_months, :age_days,
                        :birthday, :place_birth, :height, :weight, :sex, :congenital_disease, :classroom, :child_group, :nickname, :father_first_name, 
                        :father_last_name, :father_phone, :mother_first_name, :mother_last_name, 
                        :mother_phone, :profile_image, :father_image, :mother_image, :relative_image, :blood_type,
                        :allergic_food, :allergic_medicine, :address, :district, :amphoe, :province, 
                        :zipcode, :emergency_contact, :emergency_phone, :emergency_relation,
                        :relative_first_name, :relative_last_name,
                        :relative_phone, :relative_phone_backup,
                        :has_drug_allergy_history,
                        :has_food_allergy_history,
                        CURRENT_TIMESTAMP)";
                
                error_log("Using INSERT query");
            }

            $stmt = $pdo->prepare($query);
            
            // Debug parameters ที่จะ bind
            error_log("Parameters to be bound:");
            error_log(print_r([
                'studentid' => $student_id,
                'academic_year' => $academic_year,
                'prefix_th' => $prefix_th,
                'firstname_th' => $first_name_th,
                'lastname_th' => $last_name_th,
                'prefix_en' => $prefix_en,
                'firstname_en' => $first_name_en,
                'lastname_en' => $last_name_en,
                'id_card' => $id_card,
                'issue_at' => $issue_at,
                'issue_date' => $issue_date,
                'expiry_date' => $expiry_date,
                'race' => $race,
                'nationality' => $nationality,
                'religion' => $religion,
                'age_student' => $age_student,
                'age_years' => $age_years,
                'age_months' => $age_months,
                'age_days' => $age_days,
                'birthday' => $birthday,
                'place_birth' => $place_birth,
                'height' => $height,
                'weight' => $weight,
                'sex' => $sex,
                'congenital_disease' => $congenital_disease,
                'classroom' => $classroom,
                'child_group' => $child_group,
                'nickname' => $nickname,
                'father_first_name' => $father_first_name,
                'father_last_name' => $father_last_name,
                'father_phone' => $father_phone,
                'mother_first_name' => $mother_first_name,
                'mother_last_name' => $mother_last_name,
                'mother_phone' => $mother_phone,
                'profile_image' => $profile_image,
                'father_image' => $father_image,
                'mother_image' => $mother_image,
                'relative_image' => $relative_image,
                'blood_type' => $blood_type,
                'allergic_food' => $allergic_food,
                'allergic_medicine' => $allergic_medicine,
                'address' => $address,
                'district' => $district,
                'amphoe' => $amphoe,
                'province' => $province,
                'zipcode' => $zipcode,
                'emergency_contact' => $emergency_contact,
                'emergency_phone' => $emergency_phone,
                'emergency_relation' => $emergency_relation,
                'relative_first_name' => $relative_first_name,
                'relative_last_name' => $relative_last_name,
                'relative_phone' => $relative_phone,
                'relative_phone_backup' => $relative_phone_backup,
                'has_drug_allergy_history' => $has_drug_allergy_history,
                'has_food_allergy_history' => $has_food_allergy_history
            ], true));

            // Bind parameters
            $stmt->bindValue(':studentid', $student_id, PDO::PARAM_STR);
            $stmt->bindValue(':academic_year', 
                $academic_year !== null ? (int)$academic_year : null, 
                $academic_year !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':prefix_th', $prefix_th, PDO::PARAM_STR);
            $stmt->bindValue(':firstname_th', $first_name_th, PDO::PARAM_STR);
            $stmt->bindValue(':lastname_th', $last_name_th, PDO::PARAM_STR);
            $stmt->bindValue(':prefix_en', $prefix_en, PDO::PARAM_STR);
            $stmt->bindValue(':firstname_en', $first_name_en, PDO::PARAM_STR);
            $stmt->bindValue(':lastname_en', $last_name_en, PDO::PARAM_STR);
            $stmt->bindValue(':id_card', 
                (!empty($id_card) && $id_card !== '-') ? $id_card : null, 
                (!empty($id_card) && $id_card !== '-') ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':issue_at', $issue_at, PDO::PARAM_STR);
            $stmt->bindValue(':issue_date', $issue_date, PDO::PARAM_NULL);
            $stmt->bindValue(':expiry_date', $expiry_date, PDO::PARAM_NULL);
            $stmt->bindValue(':race', $race ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':nationality', $nationality ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':religion', $religion ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':age_student', 
                !empty($age_student) || $age_student === '0' ? (int)$age_student : null,
                !empty($age_student) || $age_student === '0' ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':age_years', 
                !empty($age_years) || $age_years === '0' ? (int)$age_years : null,
                !empty($age_years) || $age_years === '0' ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':age_months', 
                !empty($age_months) || $age_months === '0' ? (int)$age_months : null,
                !empty($age_months) || $age_months === '0' ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':age_days', 
                !empty($age_days) || $age_days === '0' ? (int)$age_days : null,
                !empty($age_days) || $age_days === '0' ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':birthday',
                (!empty($birthday) && $birthday !== '-') ? $birthday : null,
                (!empty($birthday) && $birthday !== '-') ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $stmt->bindValue(':place_birth', $place_birth, PDO::PARAM_STR);
            $stmt->bindValue(':height', 
                (!empty($height) || $height === 0) ? $height : null, 
                (!empty($height) || $height === 0) ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':weight', 
                (!empty($weight) || $weight === 0) ? $weight : null, 
                (!empty($weight) || $weight === 0) ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':sex', $sex, PDO::PARAM_STR);
            $stmt->bindValue(':congenital_disease', $congenital_disease, PDO::PARAM_STR);
            $stmt->bindValue(':classroom', $classroom, PDO::PARAM_STR);
            $stmt->bindValue(':child_group', $child_group, PDO::PARAM_STR);
            $stmt->bindValue(':nickname', $nickname, PDO::PARAM_STR);
            $stmt->bindValue(':father_first_name', $father_first_name, PDO::PARAM_STR);
            $stmt->bindValue(':father_last_name', $father_last_name, PDO::PARAM_STR);
            $stmt->bindValue(':father_phone', $father_phone, PDO::PARAM_STR);
            $stmt->bindValue(':mother_first_name', $mother_first_name, PDO::PARAM_STR);
            $stmt->bindValue(':mother_last_name', $mother_last_name, PDO::PARAM_STR);
            $stmt->bindValue(':mother_phone', $mother_phone, PDO::PARAM_STR);
            $stmt->bindValue(':profile_image', $profile_image, PDO::PARAM_STR);
            $stmt->bindValue(':father_image', $father_image, PDO::PARAM_STR);
            $stmt->bindValue(':mother_image', $mother_image, PDO::PARAM_STR);
            $stmt->bindValue(':relative_image', $relative_image, PDO::PARAM_STR);
            $stmt->bindValue(':blood_type', $blood_type ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':allergic_food', $allergic_food ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':allergic_medicine', $allergic_medicine ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':address', $address ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':district', $district ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':amphoe', $amphoe ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':province', $province ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':zipcode', $zipcode ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':emergency_contact', $emergency_contact ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':emergency_phone', $emergency_phone ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':emergency_relation', $emergency_relation ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':relative_first_name', $relative_first_name, PDO::PARAM_STR);
            $stmt->bindValue(':relative_last_name', $relative_last_name, PDO::PARAM_STR);
            $stmt->bindValue(':relative_phone', $relative_phone, PDO::PARAM_STR);
            $stmt->bindValue(':relative_phone_backup', $relative_phone_backup, PDO::PARAM_STR);
            $stmt->bindValue(':has_drug_allergy_history', $has_drug_allergy_history, PDO::PARAM_BOOL);
            $stmt->bindValue(':has_food_allergy_history', $has_food_allergy_history, PDO::PARAM_BOOL);

            // Execute และตรวจสอบผล
            try {
                $result = $stmt->execute();
                error_log("Query execution result: " . ($result ? "Success" : "Failed"));
                
                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("SQL Error Info: " . print_r($errorInfo, true));
                    throw new Exception("Database error: " . $errorInfo[2]);
                }

                // ตรวจสอบว่ามีการอัพเดทจริงๆ
                if ($exists > 0) {
                    $rowCount = $stmt->rowCount();
                    error_log("Rows affected: " . $rowCount);
                }

                // ดึงข้อมูลการแพ้ยา
                $drugQuery = "SELECT * FROM drug_allergies WHERE student_id = :student_id";
                $drugStmt = $pdo->prepare($drugQuery);
                $drugStmt->execute(['student_id' => $student_id]);
                $drugAllergies = $drugStmt->fetchAll(PDO::FETCH_ASSOC);

                // ดึงข้อมูลการแพ้อาหาร
                $foodQuery = "SELECT * FROM food_allergies WHERE student_id = :student_id";
                $foodStmt = $pdo->prepare($foodQuery);
                $foodStmt->execute(['student_id' => $student_id]);
                $foodAllergies = $foodStmt->fetchAll(PDO::FETCH_ASSOC);

                // เพิ่มข้อมูลการแพ้ยาและอาหารเข้าไปในข้อมูลที่จะส่งกลับ
                $result = [
                    'basic_info' => [
                        'id' => $id,
                        'student_id' => $student_id,
                        'prefix_th' => $prefix_th,
                        'first_name_th' => $first_name_th,
                        'last_name_th' => $last_name_th,
                        'prefix_en' => $prefix_en,
                        'first_name_en' => $first_name_en,
                        'last_name_en' => $last_name_en,
                        'id_card' => $id_card,
                        'issue_at' => $issue_at,
                        'issue_date' => $issue_date,
                        'expiry_date' => $expiry_date,
                        'race' => $race,
                        'nationality' => $nationality,
                        'religion' => $religion,
                        'age_student' => $age_student,
                        'age_years' => $age_years,
                        'age_months' => $age_months,
                        'age_days' => $age_days,
                        'birthday' => $birthday,
                        'place_birth' => $place_birth,
                        'height' => $height,
                        'weight' => $weight,
                        'sex' => $sex,
                        'congenital_disease' => $congenital_disease,
                        'classroom' => $classroom,
                        'child_group' => $child_group,
                        'nickname' => $nickname,
                        'father_first_name' => $father_first_name,
                        'father_last_name' => $father_last_name,
                        'father_phone' => $father_phone,
                        'mother_first_name' => $mother_first_name,
                        'mother_last_name' => $mother_last_name,
                        'mother_phone' => $mother_phone,
                        'profile_image' => $profile_image,
                        'father_image' => $father_image,
                        'mother_image' => $mother_image,
                        'relative_image' => $relative_image,
                        'blood_type' => $blood_type,
                        'allergic_food' => $allergic_food,
                        'allergic_medicine' => $allergic_medicine,
                        'address' => $address,
                        'district' => $district,
                        'amphoe' => $amphoe,
                        'province' => $province,
                        'zipcode' => $zipcode,
                        'emergency_contact' => $emergency_contact,
                        'emergency_phone' => $emergency_phone,
                        'emergency_relation' => $emergency_relation,
                        'relative_first_name' => $relative_first_name,
                        'relative_last_name' => $relative_last_name,
                        'relative_phone' => $relative_phone,
                        'relative_phone_backup' => $relative_phone_backup,
                        'has_drug_allergy_history' => $has_drug_allergy_history,
                        'has_food_allergy_history' => $has_food_allergy_history
                    ],
                    'drug_allergies' => $drugAllergies,
                    'food_allergies' => $foodAllergies
                ];

                return $result;

            } catch (PDOException $e) {
                error_log("PDO Error: " . $e->getMessage());
                error_log("SQL Query: " . $query);
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error in updateChildById: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    // ดึงข้อมูลของเด็กจาก ID (เพื่อแสดงในฟอร์ม)
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];
        $child = getChildById($id);

        if (!$child) {
            echo "ไม่พบข้อมูลเด็ก";
            exit();
        }
    }

    // จัดการข้อมูลที่รับจากฟอร์ม
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("Received POST request with data: " . print_r($_POST, true));
        
        try {
            // รับข้อมูลพื้นฐาน
            $student_id = $_POST['student_id'] ?? null;
            if (!$student_id) {
                throw new Exception("ไม่พบรหัสนักเรียน");
            }

            // ดึงข้อมูลเดิมจากฐานข้อมูล
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("SELECT * FROM children WHERE studentid = ?");
            $stmt->execute([$student_id]);
            $existingData = $stmt->fetch(PDO::FETCH_ASSOC);

            // รับค่าจากฟอร์มหรือใช้ค่าเดิมถ้าไม่มีการส่งค่ามา
            $updateData = [
                'id' => $existingData['id'] ?? null,
                'student_id' => $student_id,
                'academic_year' => !empty($_POST['academic_year']) ? (int)$_POST['academic_year'] : ($existingData['academic_year'] ?? null),
                'prefix_th' => $_POST['prefix_th'] ?? $existingData['prefix_th'] ?? '',
                'first_name_th' => trim($_POST['firstname_th'] ?? $existingData['firstname_th'] ?? ''),
                'last_name_th' => trim($_POST['lastname_th'] ?? $existingData['lastname_th'] ?? ''),
                'prefix_en' => $_POST['prefix_en'] ?? $existingData['prefix_en'] ?? '',
                'first_name_en' => trim($_POST['firstname_en'] ?? $existingData['firstname_en'] ?? ''),
                'last_name_en' => trim($_POST['lastname_en'] ?? $existingData['lastname_en'] ?? ''),
                'nickname' => $_POST['nickname'] ?? $existingData['nickname'] ?? '',
                'id_card' => !empty($_POST['id_card']) ? $_POST['id_card'] : ($existingData['id_card'] ?? null),
                'issue_at' => $_POST['issue_at'] ?? $existingData['issue_at'] ?? null,
                'issue_date' => !empty($_POST['issue_date']) ? $_POST['issue_date'] : ($existingData['issue_date'] ?? null),
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : ($existingData['expiry_date'] ?? null),
                'race' => $_POST['race'] ?? $existingData['race'] ?? '',
                'nationality' => $_POST['nationality'] ?? $existingData['nationality'] ?? '',
                'religion' => $_POST['religion'] ?? $existingData['religion'] ?? '',
                'age_student' => !empty($_POST['age_student']) ? (int)$_POST['age_student'] : ($existingData['age_student'] ?? null),
                'age_years' => !empty($_POST['age_years']) ? (int)$_POST['age_years'] : ($existingData['age_years'] ?? null),
                'age_months' => !empty($_POST['age_months']) ? (int)$_POST['age_months'] : ($existingData['age_months'] ?? null),
                'age_days' => !empty($_POST['age_days']) ? (int)$_POST['age_days'] : ($existingData['age_days'] ?? null),
                'birthday' => !empty($_POST['birthday']) ? $_POST['birthday'] : ($existingData['birthday'] ?? null),
                'height' => !empty($_POST['height']) ? (float)$_POST['height'] : ($existingData['height'] ?? null),
                'weight' => !empty($_POST['weight']) ? (float)$_POST['weight'] : ($existingData['weight'] ?? null),
                'sex' => $_POST['sex'] ?? $existingData['sex'] ?? '',
                'classroom' => $_POST['classroom'] ?? $existingData['classroom'] ?? '',
                'child_group' => $_POST['child_group'] ?? $existingData['child_group'] ?? '',
                'father_first_name' => $_POST['father_first_name'] ?? $existingData['father_first_name'] ?? '',
                'father_last_name' => $_POST['father_last_name'] ?? $existingData['father_last_name'] ?? '',
                'father_phone' => $_POST['father_phone'] ?? $existingData['father_phone'] ?? '',
                'mother_first_name' => $_POST['mother_first_name'] ?? $existingData['mother_first_name'] ?? '',
                'mother_last_name' => $_POST['mother_last_name'] ?? $existingData['mother_last_name'] ?? '',
                'mother_phone' => $_POST['mother_phone'] ?? $existingData['mother_phone'] ?? '',
                'congenital_disease' => $_POST['congenital_disease'] ?? $existingData['congenital_disease'] ?? '',
                'blood_type' => $_POST['blood_type'] ?? $existingData['blood_type'] ?? null,
                'address' => $_POST['address'] ?? $existingData['address'] ?? null,
                'district' => $_POST['district'] ?? $existingData['district'] ?? null,
                'amphoe' => $_POST['amphoe'] ?? $existingData['amphoe'] ?? null,
                'province' => $_POST['province'] ?? $existingData['province'] ?? null,
                'zipcode' => $_POST['zipcode'] ?? $existingData['zipcode'] ?? null,
                'emergency_contact' => $_POST['emergency_contact'] ?? $existingData['emergency_contact'] ?? null,
                'emergency_phone' => $_POST['emergency_phone'] ?? $existingData['emergency_phone'] ?? null,
                'emergency_relation' => $_POST['emergency_relation'] ?? $existingData['emergency_relation'] ?? null,
                'relative_first_name' => $_POST['relative_first_name'] ?? null,
                'relative_last_name' => $_POST['relative_last_name'] ?? null,
                'relative_phone' => $_POST['relative_phone'] ?? null,
                'relative_phone_backup' => $_POST['relative_phone_backup'] ?? null
            ];

            // จัดการรูปโปรไฟล์ของเด็ก
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../../public/uploads/profiles/';
                $fileName = $student_id . '_profile_' . uniqid() . '_' . basename($_FILES['profile_image']['name']);
                $uploadFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                    $updateData['profile_image'] = '/public/uploads/profiles/' . $fileName;
                }
            } else {
                // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้ใช้รูปเดิม
                $updateData['profile_image'] = $existingData['profile_image'] ?? null;
            }

            // จัดการรูปภาพพ่อ
            if (isset($_FILES['father_image']) && $_FILES['father_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../../public/uploads/parents/';
                $fileName = $student_id . '_father_' . uniqid() . '_' . basename($_FILES['father_image']['name']);
                $uploadFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['father_image']['tmp_name'], $uploadFile)) {
                    $updateData['father_image'] = '/public/uploads/parents/' . $fileName;
                }
            } else {
                // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้ใช้รูปเดิม
                $updateData['father_image'] = $existingData['father_image'] ?? null;
            }

            // จัดการรูปภาพแม่
            if (isset($_FILES['mother_image']) && $_FILES['mother_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../../public/uploads/parents/';
                $fileName = $student_id . '_mother_' . uniqid() . '_' . basename($_FILES['mother_image']['name']);
                $uploadFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['mother_image']['tmp_name'], $uploadFile)) {
                    $updateData['mother_image'] = '/public/uploads/parents/' . $fileName;
                }
            } else {
                // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้ใช้รูปเดิม
                $updateData['mother_image'] = $existingData['mother_image'] ?? null;
            }

            // จัดการรูปภาพญาติ
            if (isset($_FILES['relative_image']) && $_FILES['relative_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../../public/uploads/parents/';
                $fileName = $student_id . '_relative_' . uniqid() . '_' . basename($_FILES['relative_image']['name']);
                $uploadFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['relative_image']['tmp_name'], $uploadFile)) {
                    $updateData['relative_image'] = '/public/uploads/parents/' . $fileName;
                }
            } else {
                // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้ใช้รูปเดิม
                $updateData['relative_image'] = $existingData['relative_image'] ?? null;
            }

            // Debug log
            error_log("Profile image path: " . $updateData['profile_image']);
            error_log("Father image path: " . $updateData['father_image']);
            error_log("Mother image path: " . $updateData['mother_image']);
            error_log("Relative image path: " . $updateData['relative_image']);

            error_log("Updating student with ID: " . $student_id);
            error_log("Current values in database:");
            error_log(print_r($existingData, true));
            error_log("New values to update:");
            error_log(print_r([
                'race' => $updateData['race'],
                'age_student' => $updateData['age_student'],
                // ... other fields ...
            ], true));

            // แปลงวันที่จาก พ.ศ. เป็น ค.ศ. ก่อนบันทึก
            if (!empty($_POST['birthday'])) {
                $date = DateTime::createFromFormat('d/m/Y', $_POST['birthday']);
                if ($date) {
                    // แปลงปี พ.ศ. เป็น ค.ศ.
                    $year = $date->format('Y') - 543;
                    $date->setDate($year, $date->format('m'), $date->format('d'));
                    $updateData['birthday'] = $date->format('Y-m-d');
                }
            } else {
                $updateData['birthday'] = null;
            }

            // เรียกใช้ฟังก์ชันอัปเดต
            $result = updateChildById(
                $updateData['id'],
                $updateData['student_id'],
                $updateData['academic_year'],
                $updateData['prefix_th'],
                $updateData['first_name_th'],
                $updateData['last_name_th'],
                $updateData['prefix_en'],
                $updateData['first_name_en'],
                $updateData['last_name_en'],
                $updateData['id_card'],
                $updateData['issue_at'],
                $updateData['issue_date'],
                $updateData['expiry_date'],
                $updateData['race'],
                $updateData['nationality'],
                $updateData['religion'],
                $updateData['age_student'],
                $updateData['birthday'],
                $updateData['place_birth'],
                $updateData['height'],
                $updateData['weight'],
                $updateData['sex'],
                $updateData['congenital_disease'],
                $updateData['classroom'],
                $updateData['child_group'],
                $updateData['nickname'],
                $updateData['father_first_name'],
                $updateData['father_last_name'],
                $updateData['father_phone'],
                $updateData['mother_first_name'],
                $updateData['mother_last_name'],
                $updateData['mother_phone'],
                $updateData['profile_image'],
                $updateData['father_image'],
                $updateData['mother_image'],
                $updateData['relative_image'],
                $updateData['blood_type'],
                null, // allergic_food
                null, // allergic_medicine
                $updateData['address'],
                $updateData['district'],
                $updateData['amphoe'],
                $updateData['province'],
                $updateData['zipcode'],
                $updateData['emergency_contact'],
                $updateData['emergency_phone'],
                $updateData['emergency_relation'],
                $updateData['relative_first_name'],
                $updateData['relative_last_name'],
                $updateData['relative_phone'],
                $updateData['relative_phone_backup'],
                $updateData['age_years'],
                $updateData['age_months'],
                $updateData['age_days'],
                $updateData['has_drug_allergy_history'],
                $updateData['has_food_allergy_history']
            );

            if ($result === true) {
                header('Location: ../../views/student/view_child.php?studentid=' . urlencode($student_id) . 
                       '&status=success&message=' . urlencode('บันทึกข้อมูลสำเร็จ'));
                exit();
            } else {
                throw new Exception("ไม่สามารถบันทึกข้อมูลได้");
            }

        } catch (Exception $e) {
            error_log("Error processing form: " . $e->getMessage());
            header('Location: ../../views/student/view_child.php?studentid=' . urlencode($student_id) . 
                   '&status=error&message=' . urlencode($e->getMessage()));
            exit();
        }
    }

    // ส่งผลลัพธ์
    sendJsonResponse('success', 'บันทึกข้อมูลสำเร็จ');

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    sendJsonResponse('error', $e->getMessage(), 500);
}
exit();

?>