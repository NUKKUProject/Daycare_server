<?php
require_once(__DIR__ . '/../../../config/database.php');
session_start();

// ล้าง output buffer และตั้งค่า header
ob_start();
header('Content-Type: application/json');

function sendResponse($status, $message) {
    ob_clean();
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $pdo = getDatabaseConnection();
    
    if (!isset($_POST['action']) || !isset($_POST['type'])) {
        throw new Exception('Missing required parameters');
    }

    $action = $_POST['action'];
    $type = $_POST['type'];
    $student_id = $_POST['student_id'] ?? null;

    if (!$student_id) {
        throw new Exception('Student ID is required');
    }

    error_log("Received data: " . print_r($_POST, true));

    function arrayToPostgresArray($arr) {
        if (empty($arr)) return '{}';
        return '{' . implode(',', array_map(fn($item) => '"' . str_replace('"', '\"', $item) . '"', $arr)) . '}';
    }

    switch ($action) {
        case 'add':
            error_log("Adding allergy data for type: " . $type);
            error_log("POST data: " . print_r($_POST, true));
            
            if ($type === 'drug') {
                if (empty($_POST['drug_name']) && empty($_POST['detection_method']) && empty($_POST['symptoms'])) {
                    echo json_encode(['status' => 'success', 'message' => 'ไม่มีการเปลี่ยนแปลงข้อมูล']);
                    break;
                }

                $stmt = $pdo->prepare("INSERT INTO drug_allergies (student_id, drug_name, detection_method, symptoms, has_allergy_card) VALUES (:student_id, :drug_name, :detection_method, :symptoms, :has_allergy_card)");
                error_log("Raw has_allergy_card value: " . (isset($_POST['has_allergy_card']) ? $_POST['has_allergy_card'] : 'not set'));
                
                // แปลงค่าเป็น boolean อย่างชัดเจน
                $hasAllergyCard = false;
                if (isset($_POST['has_allergy_card'])) {
                    $value = strtolower($_POST['has_allergy_card']);
                    if ($value === 'true' || $value === '1') {
                        $hasAllergyCard = true;
                    }
                }
                error_log("Converted has_allergy_card value: " . ($hasAllergyCard ? 'true' : 'false'));

                $params = [
                    'student_id' => $student_id,
                    'drug_name' => $_POST['drug_name'] ?? '',
                    'detection_method' => $_POST['detection_method'] ?? '',
                    'symptoms' => $_POST['symptoms'] ?? '',
                    'has_allergy_card' => $hasAllergyCard
                ];
                error_log("Drug params: " . print_r($params, true));
                $stmt->execute($params);
            } elseif ($type === 'food') {
                if (empty($_POST['food_name']) && empty($_POST['detection_method']) && 
                    empty($_POST['digestive_symptoms']) && empty($_POST['skin_symptoms']) && 
                    empty($_POST['respiratory_symptoms'])) {
                    echo json_encode(['status' => 'success', 'message' => 'ไม่มีการเปลี่ยนแปลงข้อมูล']);
                    break;
                }

                $stmt = $pdo->prepare("INSERT INTO food_allergies (student_id, food_name, detection_method, digestive_symptoms, skin_symptoms, respiratory_symptoms) VALUES (:student_id, :food_name, :detection_method, :digestive_symptoms::text[], :skin_symptoms::text[], :respiratory_symptoms::text[])");
                $params = [
                    'student_id' => $student_id,
                    'food_name' => $_POST['food_name'] ?? '',
                    'detection_method' => $_POST['detection_method'] ?? '',
                    'digestive_symptoms' => arrayToPostgresArray(json_decode($_POST['digestive_symptoms'] ?? '[]', true)),
                    'skin_symptoms' => arrayToPostgresArray(json_decode($_POST['skin_symptoms'] ?? '[]', true)),
                    'respiratory_symptoms' => arrayToPostgresArray(json_decode($_POST['respiratory_symptoms'] ?? '[]', true))
                ];
                error_log("Food params: " . print_r($params, true));
                $stmt->execute($params);
            }
            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลสำเร็จ']);
            break;

        case 'update':
            error_log("Updating allergy data for type: " . $type);
            error_log("POST data: " . print_r($_POST, true));

            if ($type === 'drug') {
                if (empty($_POST['drug_name']) && empty($_POST['detection_method']) && empty($_POST['symptoms'])) {
                    sendResponse('success', 'ไม่มีการเปลี่ยนแปลงข้อมูล');
                    break;
                }

                // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM drug_allergies WHERE student_id = :student_id");
                $checkStmt->execute(['student_id' => $student_id]);
                $exists = $checkStmt->fetchColumn() > 0;

                // ตรวจสอบและแปลงค่า has_allergy_card ให้ชัดเจน
                $hasAllergyCard = false;
                if (isset($_POST['has_allergy_card'])) {
                    $value = strtolower(trim($_POST['has_allergy_card']));
                    if ($value === '1' || $value === 'true') {
                        $hasAllergyCard = true;
                    } else if ($value === '0' || $value === 'false') {
                        $hasAllergyCard = false;
                    }
                }

                error_log("Has Allergy Card Input: " . ($_POST['has_allergy_card'] ?? 'not set'));
                error_log("Has Allergy Card Value to save: " . ($hasAllergyCard ? 'true' : 'false'));

                try {
                    if ($exists) {
                        // ถ้ามีข้อมูลอยู่แล้ว ให้ update
                        $stmt = $pdo->prepare("
                            UPDATE drug_allergies SET
                                drug_name = :drug_name,
                                detection_method = :detection_method,
                                symptoms = :symptoms,
                                has_allergy_card = :has_allergy_card::boolean,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE student_id = :student_id
                        ");
                    } else {
                        // ถ้ายังไม่มีข้อมูล ให้ insert
                        $stmt = $pdo->prepare("
                            INSERT INTO drug_allergies (
                                student_id,
                                drug_name,
                                detection_method,
                                symptoms,
                                has_allergy_card
                            ) VALUES (
                                :student_id,
                                :drug_name,
                                :detection_method,
                                :symptoms,
                                :has_allergy_card::boolean
                            )
                        ");
                    }

                    $params = [
                        'student_id' => $student_id,
                        'drug_name' => $_POST['drug_name'] ?? '',
                        'detection_method' => $_POST['detection_method'] ?? '',
                        'symptoms' => $_POST['symptoms'] ?? '',
                        'has_allergy_card' => $hasAllergyCard ? 'true' : 'false'
                    ];

                    error_log("SQL params: " . print_r($params, true));
                    $result = $stmt->execute($params);

                    if (!$result) {
                        $error = $stmt->errorInfo();
                        error_log("SQL Error: " . print_r($error, true));
                        throw new Exception("Database error: " . $error[2]);
                    }

                    // อัพเดทค่า has_drug_allergy_history ในตาราง children
                    $updateChildStmt = $pdo->prepare("
                        UPDATE children 
                        SET has_drug_allergy_history = true 
                        WHERE studentid = :student_id
                    ");
                    $updateChildStmt->execute(['student_id' => $student_id]);

                    // ตรวจสอบค่าที่บันทึกลงไปจริง
                    $checkStmt = $pdo->prepare("SELECT has_allergy_card FROM drug_allergies WHERE student_id = :student_id");
                    $checkStmt->execute(['student_id' => $student_id]);
                    $savedValue = $checkStmt->fetchColumn();
                    error_log("Saved has_allergy_card value: " . ($savedValue ? 'true' : 'false'));

                    sendResponse('success', 'บันทึกข้อมูลสำเร็จ');
                } catch (PDOException $e) {
                    error_log("PDO Error: " . $e->getMessage());
                    throw new Exception("Database error: " . $e->getMessage());
                }
            } else {
                if (empty($_POST['food_name']) && empty($_POST['detection_method']) && 
                    empty($_POST['digestive_symptoms']) && empty($_POST['skin_symptoms']) && 
                    empty($_POST['respiratory_symptoms'])) {
                    echo json_encode(['status' => 'success', 'message' => 'ไม่มีการเปลี่ยนแปลงข้อมูล']);
                    break;
                }

                $stmt = $pdo->prepare("
                    UPDATE food_allergies 
                    SET food_name = :food_name,
                        detection_method = :detection_method,
                        digestive_symptoms = :digestive_symptoms::text[],
                        skin_symptoms = :skin_symptoms::text[],
                        respiratory_symptoms = :respiratory_symptoms::text[],
                        updated_at = CURRENT_TIMESTAMP
                    WHERE student_id = :student_id
                ");

                $digestiveSymptoms = !empty($_POST['digestive_symptoms']) ? 
                    json_decode($_POST['digestive_symptoms'], true) : 
                    [];
                
                $skinSymptoms = !empty($_POST['skin_symptoms']) ? 
                    json_decode($_POST['skin_symptoms'], true) : 
                    [];
                
                $respiratorySymptoms = !empty($_POST['respiratory_symptoms']) ? 
                    json_decode($_POST['respiratory_symptoms'], true) : 
                    [];

                $success = $stmt->execute([
                    'student_id' => $student_id,
                    'food_name' => $_POST['food_name'],
                    'detection_method' => $_POST['detection_method'],
                    'digestive_symptoms' => '{' . implode(',', $digestiveSymptoms) . '}',
                    'skin_symptoms' => '{' . implode(',', $skinSymptoms) . '}',
                    'respiratory_symptoms' => '{' . implode(',', $respiratorySymptoms) . '}'
                ]);
            }

            if (!$success) {
                throw new Exception('Failed to update data');
            }

            // อัพเดทค่า has_food_allergy_history ในตาราง children
            $updateChildStmt = $pdo->prepare("
                UPDATE children 
                SET has_food_allergy_history = true 
                WHERE studentid = :student_id
            ");
            $updateChildStmt->execute(['student_id' => $student_id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'อัพเดทข้อมูลสำเร็จ'
            ]);
            break;

        case 'get':
            if ($type === 'both') {
                $drugStmt = $pdo->prepare("SELECT * FROM drug_allergies WHERE student_id = :student_id ORDER BY created_at DESC LIMIT 1");
                $foodStmt = $pdo->prepare("SELECT * FROM food_allergies WHERE student_id = :student_id ORDER BY created_at DESC LIMIT 1");
                
                $drugStmt->execute(['student_id' => $student_id]);
                $foodStmt->execute(['student_id' => $student_id]);
                
                $drugData = $drugStmt->fetch(PDO::FETCH_ASSOC);
                $foodData = $foodStmt->fetch(PDO::FETCH_ASSOC);

                error_log('Drug data: ' . print_r($drugData, true));
                error_log('Food data: ' . print_r($foodData, true));
                
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'drug' => $drugData ?: null,
                        'food' => $foodData ?: null
                    ]
                ]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM " . ($type === 'drug' ? 'drug_allergies' : 'food_allergies') . " WHERE student_id = :student_id ORDER BY created_at DESC");
                $stmt->execute(['student_id' => $student_id]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('Selected data: ' . print_r($data, true));
                echo json_encode(['status' => 'success', 'data' => $data]);
            }
            break;

        case 'delete':
            // ลบประวัติการแพ้ยา หรือแพ้อาหาร ตาม type
            if ($type === 'drug') {
                $stmt = $pdo->prepare("DELETE FROM drug_allergies WHERE student_id = :student_id");
                $stmt->execute(['student_id' => $student_id]);
                // อัพเดท children
                $updateChildStmt = $pdo->prepare("UPDATE children SET has_drug_allergy_history = false WHERE studentid = :student_id");
                $updateChildStmt->execute(['student_id' => $student_id]);
            } elseif ($type === 'food') {
                $stmt = $pdo->prepare("DELETE FROM food_allergies WHERE student_id = :student_id");
                $stmt->execute(['student_id' => $student_id]);
                // อัพเดท children
                $updateChildStmt = $pdo->prepare("UPDATE children SET has_food_allergy_history = false WHERE studentid = :student_id");
                $updateChildStmt->execute(['student_id' => $student_id]);
            }
            sendResponse('success', 'ลบข้อมูลประวัติการแพ้สำเร็จ');
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(400);
    sendResponse('error', $e->getMessage());
}
