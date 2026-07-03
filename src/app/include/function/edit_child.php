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
    // ตรวจสอบสิทธิ์ - อนุญาตให้ admin, teacher, student ใช้งานได้
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse('error', 'Unauthorized access', 403);
    }
    
    $allowedRoles = ['admin', 'teacher', 'student'];
    if (!in_array($_SESSION['role'], $allowedRoles)) {
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
        $father_phone_backup,
        $mother_first_name,
        $mother_last_name,
        $mother_phone,
        $mother_phone_backup,
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
                        father_phone_backup = :father_phone_backup,
                        mother_first_name = :mother_first_name,
                        mother_last_name = :mother_last_name,
                        mother_phone = :mother_phone,
                        mother_phone_backup = :mother_phone_backup,
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
                        father_last_name, father_phone, father_phone_backup, mother_first_name, mother_last_name, 
                        mother_phone, mother_phone_backup, profile_image, father_image, mother_image, relative_image, blood_type,
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
                        :father_last_name, :father_phone, :father_phone_backup, :mother_first_name, :mother_last_name, 
                        :mother_phone, :mother_phone_backup, :profile_image, :father_image, :mother_image, :relative_image, :blood_type,
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
                'father_phone_backup' => $father_phone_backup,
                'mother_first_name' => $mother_first_name,
                'mother_last_name' => $mother_last_name,
                'mother_phone' => $mother_phone,
                'mother_phone_backup' => $mother_phone_backup,
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
            $stmt->bindValue(':father_phone_backup', $father_phone_backup, PDO::PARAM_STR);
            $stmt->bindValue(':mother_first_name', $mother_first_name, PDO::PARAM_STR);
            $stmt->bindValue(':mother_last_name', $mother_last_name, PDO::PARAM_STR);
            $stmt->bindValue(':mother_phone', $mother_phone, PDO::PARAM_STR);
            $stmt->bindValue(':mother_phone_backup', $mother_phone_backup, PDO::PARAM_STR);
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
                        'father_phone_backup' => $father_phone_backup,
                        'mother_first_name' => $mother_first_name,
                        'mother_last_name' => $mother_last_name,
                        'mother_phone' => $mother_phone,
                        'mother_phone_backup' => $mother_phone_backup,
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
        // ตรวจสอบว่าเป็น JSON request หรือไม่
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // รับ JSON input
            $jsonInput = file_get_contents('php://input');
            $inputData = json_decode($jsonInput, true);
            error_log("Received JSON request: " . $jsonInput);
        } else {
            // รับจาก POST ปกติ (FormData)
            $inputData = $_POST;
            error_log("Received POST request with data: " . print_r($_POST, true));
        }
        
        try {
            // รับข้อมูลพื้นฐาน
            $student_id = $inputData['student_id'] ?? null;
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
                'academic_year' => !empty($inputData['academic_year']) ? (int)$inputData['academic_year'] : ($existingData['academic_year'] ?? null),
                'prefix_th' => $inputData['prefix_th'] ?? $existingData['prefix_th'] ?? '',
                'first_name_th' => trim($inputData['firstname_th'] ?? $existingData['firstname_th'] ?? ''),
                'last_name_th' => trim($inputData['lastname_th'] ?? $existingData['lastname_th'] ?? ''),
                'prefix_en' => $inputData['prefix_en'] ?? $existingData['prefix_en'] ?? '',
                'first_name_en' => trim($inputData['firstname_en'] ?? $existingData['firstname_en'] ?? ''),
                'last_name_en' => trim($inputData['lastname_en'] ?? $existingData['lastname_en'] ?? ''),
                'nickname' => $inputData['nickname'] ?? $existingData['nickname'] ?? '',
                'id_card' => !empty($inputData['id_card']) ? $inputData['id_card'] : ($existingData['id_card'] ?? null),
                'race' => $inputData['race'] ?? $existingData['race'] ?? '',
                'nationality' => $inputData['nationality'] ?? $existingData['nationality'] ?? '',
                'religion' => $inputData['religion'] ?? $existingData['religion'] ?? '',
                'birthday' => !empty($inputData['birthday']) ? $inputData['birthday'] : ($existingData['birthday'] ?? null),
                'height' => !empty($inputData['height']) ? (float)$inputData['height'] : ($existingData['height'] ?? null),
                'weight' => !empty($inputData['weight']) ? (float)$inputData['weight'] : ($existingData['weight'] ?? null),
                'sex' => $inputData['sex'] ?? $existingData['sex'] ?? '',
                'classroom' => $inputData['classroom'] ?? $existingData['classroom'] ?? '',
                'child_group' => $inputData['child_group'] ?? $existingData['child_group'] ?? '',
                'father_first_name' => $inputData['father_first_name'] ?? $existingData['father_first_name'] ?? '',
                'father_last_name' => $inputData['father_last_name'] ?? $existingData['father_last_name'] ?? '',
                'father_phone' => $inputData['father_phone'] ?? $existingData['father_phone'] ?? '',
                'father_phone_backup' => $inputData['father_phone_backup'] ?? $existingData['father_phone_backup'] ?? '',
                'mother_first_name' => $inputData['mother_first_name'] ?? $existingData['mother_first_name'] ?? '',
                'mother_last_name' => $inputData['mother_last_name'] ?? $existingData['mother_last_name'] ?? '',
                'mother_phone' => $inputData['mother_phone'] ?? $existingData['mother_phone'] ?? '',
                'mother_phone_backup' => $inputData['mother_phone_backup'] ?? $existingData['mother_phone_backup'] ?? '',
                'congenital_disease' => $inputData['congenital_disease'] ?? $existingData['congenital_disease'] ?? '',
                'blood_type' => $inputData['blood_type'] ?? $existingData['blood_type'] ?? null,
                'address' => $inputData['address'] ?? $existingData['address'] ?? null,
                'district' => $inputData['district'] ?? $existingData['district'] ?? null,
                'amphoe' => $inputData['amphoe'] ?? $existingData['amphoe'] ?? null,
                'province' => $inputData['province'] ?? $existingData['province'] ?? null,
                'zipcode' => $inputData['zipcode'] ?? $existingData['zipcode'] ?? null,
                'emergency_contact' => $inputData['emergency_contact'] ?? $existingData['emergency_contact'] ?? null,
                'emergency_phone' => $inputData['emergency_phone'] ?? $existingData['emergency_phone'] ?? null,
                'emergency_relation' => $inputData['emergency_relation'] ?? $existingData['emergency_relation'] ?? null,
                'relative_first_name' => $inputData['relative_first_name'] ?? $existingData['relative_first_name'] ?? '',
                'relative_last_name' => $inputData['relative_last_name'] ?? $existingData['relative_last_name'] ?? '',
                'relative_phone' => $inputData['relative_phone'] ?? $existingData['relative_phone'] ?? '',
                'relative_phone_backup' => $inputData['relative_phone_backup'] ?? $existingData['relative_phone_backup'] ?? ''
            ];

            // จัดการรูปโปรไฟล์ของเด็ก
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    
                // 1. กำหนดไดเรกทอรีอัปโหลดอย่างปลอดภัย (ใช้พาธสมบูรณ์)
                $uploadDir = __DIR__ . '/../../../public/uploads/profiles/';
                
                // ตรวจสอบว่าไดเรกทอรีมีอยู่และเขียนได้
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    die('Directory does not exist or is not writable.');
                }
                
                // 2. สร้างชื่อไฟล์ที่ปลอดภัย
                $originalFileName = $_FILES['profile_image']['name'];
                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
                
                // ทำความสะอาดนามสกุลไฟล์
                $fileExtension = preg_replace('/[^a-zA-Z0-9]/', '', $fileExtension);
                
                // จำกัดนามสกุลไฟล์ที่อนุญาต
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                    die('File extension not allowed.');
                }
                
                // สร้างชื่อไฟล์ใหม่อย่างปลอดภัย
                $fileName = $student_id . '_profile_' . uniqid() . '.' . $fileExtension;
                
                // 3. สร้างพาธปลายทาง
                $uploadFile = $uploadDir . $fileName;
                
                // 4. ตรวจสอบพาธจริงเพื่อป้องกัน Path Traversal
                $realUploadDir = realpath($uploadDir);
                $realUploadFile = realpath(dirname($uploadFile)) . '/' . basename($uploadFile);
                
                // ตรวจสอบว่าพาธจริงอยู่ในไดเรกทอรีที่อนุญาต
                if (strpos($realUploadFile, $realUploadDir) !== 0) {
                    die('Security violation: Invalid file path.');
                }
                
                
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES['profile_image']['type'], $allowedMimeTypes)) {
                    die('File type not allowed.');
                }
                
                // 6. ทำการย้ายไฟล์
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                    $updateData['profile_image'] = '/public/uploads/profiles/' . $fileName;
                    echo 'File uploaded successfully.';
                } else {
                    echo 'Failed to upload file.';
                }
            }
            else {
                // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้ใช้รูปเดิม
                $updateData['profile_image'] = $existingData['profile_image'] ?? null;
            }

            // จัดการรูปภาพพ่อ
            if (isset($_FILES['father_image']) && $_FILES['father_image']['error'] === UPLOAD_ERR_OK) {
                // กำหนดไดเรกทอรีอัปโหลดอย่างปลอดภัย (ใช้พาธสมบูรณ์)
                $uploadDir = __DIR__ . '/../../../public/uploads/parents/';

                // ตรวจสอบว่าไดเรกทอรีมีอยู่และเขียนได้
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    die('Directory does not exist or is not writable.');
                }

                // สร้างชื่อไฟล์ที่ปลอดภัย
                $originalFileName = $_FILES['father_image']['name'];
                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

                // ทำความสะอาดนามสกุลไฟล์
                $fileExtension = preg_replace('/[^a-zA-Z0-9]/', '', $fileExtension);

                // จำกัดนามสกุลไฟล์ที่อนุญาต
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                    die('File extension not allowed.');
                }

                // สร้างชื่อไฟล์ใหม่อย่างปลอดภัย
                $fileName = $student_id . '_father_' . uniqid() . '.' . $fileExtension;

                // สร้างพาธปลายทาง
                $uploadFile = $uploadDir . $fileName;

                // ตรวจสอบพาธจริงเพื่อป้องกัน Path Traversal
                $realUploadDir = realpath($uploadDir);
                $realUploadFile = realpath(dirname($uploadFile)) . '/' . basename($uploadFile);

                // ตรวจสอบว่าพาธจริงอยู่ในไดเรกทอรีที่อนุญาต
                if (strpos($realUploadFile, $realUploadDir) !== 0) {
                    die('Security violation: Invalid file path.');
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES['father_image']['type'], $allowedMimeTypes)) {
                    die('File type not allowed.');
                }

                // ทำการย้ายไฟล์
                if (move_uploaded_file($_FILES['father_image']['tmp_name'], $uploadFile)) {
                    $updateData['father_image'] = '/public/uploads/parents/' . $fileName;
                    echo 'File uploaded successfully.';
                } else {
                    echo 'Failed to upload file.';
                }
            } else {
                // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้ใช้รูปเดิม
                $updateData['father_image'] = $existingData['father_image'] ?? null;
            }

            // จัดการรูปภาพแม่
            if (isset($_FILES['mother_image']) && $_FILES['mother_image']['error'] === UPLOAD_ERR_OK) {
                // กำหนดไดเรกทอรีอัปโหลดอย่างปลอดภัย (ใช้พาธสมบูรณ์)
                $uploadDir = __DIR__ . '/../../../public/uploads/parents/';

                // ตรวจสอบว่าไดเรกทอรีมีอยู่และเขียนได้
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    die('Directory does not exist or is not writable.');
                }

                // สร้างชื่อไฟล์ที่ปลอดภัย
                $originalFileName = $_FILES['mother_image']['name'];
                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

                // ทำความสะอาดนามสกุลไฟล์
                $fileExtension = preg_replace('/[^a-zA-Z0-9]/', '', $fileExtension);

                // จำกัดนามสกุลไฟล์ที่อนุญาต
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                    die('File extension not allowed.');
                }

                // สร้างชื่อไฟล์ใหม่อย่างปลอดภัย
                $fileName = $student_id . '_mother_' . uniqid() . '.' . $fileExtension;

                // สร้างพาธปลายทาง
                $uploadFile = $uploadDir . $fileName;

                // ตรวจสอบพาธจริงเพื่อป้องกัน Path Traversal
                $realUploadDir = realpath($uploadDir);
                $realUploadFile = realpath(dirname($uploadFile)) . '/' . basename($uploadFile);

                // ตรวจสอบว่าพาธจริงอยู่ในไดเรกทอรีที่อนุญาต
                if (strpos($realUploadFile, $realUploadDir) !== 0) {
                    die('Security violation: Invalid file path.');
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES['mother_image']['type'], $allowedMimeTypes)) {
                    die('File type not allowed.');
                }

                // ทำการย้ายไฟล์
                if (move_uploaded_file($_FILES['mother_image']['tmp_name'], $uploadFile)) {
                    $updateData['mother_image'] = '/public/uploads/parents/' . $fileName;
                } else {
                    echo 'Failed to upload mother image.';
                }
            } else {
                // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้ใช้รูปเดิม
                $updateData['mother_image'] = $existingData['mother_image'] ?? null;
            }

            // จัดการรูปภาพญาติ
            if (isset($_FILES['relative_image']) && $_FILES['relative_image']['error'] === UPLOAD_ERR_OK) {
                // กำหนดไดเรกทอรีอัปโหลดอย่างปลอดภัย (ใช้พาธสมบูรณ์)
                $uploadDir = __DIR__ . '/../../../public/uploads/parents/';

                // ตรวจสอบว่าไดเรกทอรีมีอยู่และเขียนได้
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    die('Directory does not exist or is not writable.');
                }

                // สร้างชื่อไฟล์ที่ปลอดภัย
                $originalFileName = $_FILES['relative_image']['name'];
                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

                // ทำความสะอาดนามสกุลไฟล์
                $fileExtension = preg_replace('/[^a-zA-Z0-9]/', '', $fileExtension);

                // จำกัดนามสกุลไฟล์ที่อนุญาต
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                    die('File extension not allowed.');
                }

                // สร้างชื่อไฟล์ใหม่อย่างปลอดภัย
                $fileName = $student_id . '_relative_' . uniqid() . '.' . $fileExtension;

                // สร้างพาธปลายทาง
                $uploadFile = $uploadDir . $fileName;

                // ตรวจสอบพาธจริงเพื่อป้องกัน Path Traversal
                $realUploadDir = realpath($uploadDir);
                $realUploadFile = realpath(dirname($uploadFile)) . '/' . basename($uploadFile);

                // ตรวจสอบว่าพาธจริงอยู่ในไดเรกทอรีที่อนุญาต
                if (strpos($realUploadFile, $realUploadDir) !== 0) {
                    die('Security violation: Invalid file path.');
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES['relative_image']['type'], $allowedMimeTypes)) {
                    die('File type not allowed.');
                }

                // ทำการย้ายไฟล์
                if (move_uploaded_file($_FILES['relative_image']['tmp_name'], $uploadFile)) {
                    $updateData['relative_image'] = '/public/uploads/parents/' . $fileName;
                } else {
                    echo 'Failed to upload relative image.';
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
            if (!empty($inputData['birthday'])) {
                // ลองแปลงจากรูปแบบ Y-m-d ก่อน (รูปแบบใหม่ที่ส่งมาจาก JS)
                $date = DateTime::createFromFormat('Y-m-d', $inputData['birthday']);
                if (!$date) {
                    // ถ้าไม่ใช่ ลองแปลงจากรูปแบบ d/m/Y (รูปแบบเดิม)
                    $date = DateTime::createFromFormat('d/m/Y', $inputData['birthday']);
                    if ($date) {
                        // แปลงปี พ.ศ. เป็น ค.ศ.
                        $year = $date->format('Y') - 543;
                        $date->setDate($year, $date->format('m'), $date->format('d'));
                    }
                }
                if ($date) {
                    $updateData['birthday'] = $date->format('Y-m-d');
                } else {
                    $updateData['birthday'] = $inputData['birthday'];
                }
            } else {
                $updateData['birthday'] = $existingData['birthday'] ?? null;
            }

            // เพิ่มค่าที่ขาดหายไป
            $updateData['age_years'] = $updateData['age_years'] ?? 0;
            $updateData['age_months'] = $updateData['age_months'] ?? 0;
            $updateData['age_days'] = $updateData['age_days'] ?? 0;
            $updateData['has_drug_allergy_history'] = $updateData['has_drug_allergy_history'] ?? false;
            $updateData['has_food_allergy_history'] = $updateData['has_food_allergy_history'] ?? false;

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
                $updateData['father_phone_backup'],
                $updateData['mother_first_name'],
                $updateData['mother_last_name'],
                $updateData['mother_phone'],
                $updateData['mother_phone_backup'],
                $updateData['profile_image'] ?? $existingData['profile_image'] ?? null,
                $updateData['father_image'] ?? $existingData['father_image'] ?? null,
                $updateData['mother_image'] ?? $existingData['mother_image'] ?? null,
                $updateData['relative_image'] ?? $existingData['relative_image'] ?? null,
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

            // updateChildById คืนค่า array เมื่อสำเร็จ
            if ($result !== false) {
                sendJsonResponse('success', 'บันทึกข้อมูลสำเร็จ');
            } else {
                throw new Exception("ไม่สามารถบันทึกข้อมูลได้");
            }

        } catch (Exception $e) {
            error_log("Error processing form: " . $e->getMessage());
            sendJsonResponse('error', $e->getMessage());
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