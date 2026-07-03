<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../function/db_connection.php';

// ตรวจสอบการเข้าถึง
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDatabaseConnection();

// รับข้อมูลจาก POST
$action = $_POST['action'] ?? 'get';
$type = $_POST['type'] ?? 'drug';
$student_id = $_POST['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['status' => 'error', 'message' => 'Student ID is required']);
    exit;
}

// ตรวจสอบสิทธิ์การเข้าถึง
$user_role = $_SESSION['user_role'] ?? '';
if ($user_role !== 'admin' && $action !== 'get') {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
    exit;
}

if ($action === 'get') {
    // ดึงข้อมูลการแพ้
    $table = $type === 'drug' ? 'drug_allergies' : 'food_allergies';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE student_id = :student_id ORDER BY created_at DESC");
        $stmt->execute(['student_id' => $student_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'update') {
    // อัพเดทหรือเพิ่มข้อมูลการแพ้
    if ($type === 'drug') {
        handleDrugAllergy($pdo, $student_id);
    } elseif ($type === 'food') {
        handleFoodAllergy($pdo, $student_id);
    }
}

function handleDrugAllergy($pdo, $student_id) {
    $drug_name = $_POST['drug_name'] ?? '';
    $detection_method = $_POST['detection_method'] ?? '';
    $symptoms = $_POST['symptoms'] ?? '';
    $has_allergy_card = isset($_POST['has_allergy_card']) ? 1 : 0;
    
    try {
        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        $checkStmt = $pdo->prepare("SELECT id FROM drug_allergies WHERE student_id = :student_id");
        $checkStmt->execute(['student_id' => $student_id]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // อัพเดทข้อมูลที่มีอยู่
            $stmt = $pdo->prepare("
                UPDATE drug_allergies 
                SET drug_name = :drug_name, 
                    detection_method = :detection_method, 
                    symptoms = :symptoms, 
                    has_allergy_card = :has_allergy_card,
                    updated_at = NOW()
                WHERE student_id = :student_id
            ");
        } else {
            // เพิ่มข้อมูลใหม่
            $stmt = $pdo->prepare("
                INSERT INTO drug_allergies 
                (student_id, drug_name, detection_method, symptoms, has_allergy_card, created_at, updated_at)
                VALUES 
                (:student_id, :drug_name, :detection_method, :symptoms, :has_allergy_card, NOW(), NOW())
            ");
        }
        
        $stmt->execute([
            'student_id' => $student_id,
            'drug_name' => $drug_name,
            'detection_method' => $detection_method,
            'symptoms' => $symptoms,
            'has_allergy_card' => $has_allergy_card
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลการแพ้ยาสำเร็จ']);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleFoodAllergy($pdo, $student_id) {
    $food_name = $_POST['food_name'] ?? '';
    $detection_method = $_POST['detection_method'] ?? '';
    
    // แปลง array symptoms จาก JSON string เป็น PHP array
    $digestive_symptoms = json_decode($_POST['digestive_symptoms'] ?? '[]', true) ?? [];
    $skin_symptoms = json_decode($_POST['skin_symptoms'] ?? '[]', true) ?? [];
    $respiratory_symptoms = json_decode($_POST['respiratory_symptoms'] ?? '[]', true) ?? [];
    
    // ฟังก์ชันแปลง PHP array เป็น PostgreSQL array format {value1,value2}
    function arrayToPostgresArray($arr) {
        if (empty($arr)) return '{}';
        return '{' . implode(',', array_map(fn($item) => '"' . str_replace('"', '\"', $item) . '"', $arr)) . '}';
    }
    
    try {
        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        $checkStmt = $pdo->prepare("SELECT id FROM food_allergies WHERE student_id = :student_id");
        $checkStmt->execute(['student_id' => $student_id]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // อัพเดทข้อมูลที่มีอยู่
            $stmt = $pdo->prepare("
                UPDATE food_allergies 
                SET food_name = :food_name, 
                    detection_method = :detection_method, 
                    digestive_symptoms = :digestive_symptoms, 
                    skin_symptoms = :skin_symptoms,
                    respiratory_symptoms = :respiratory_symptoms,
                    updated_at = NOW()
                WHERE student_id = :student_id
            ");
        } else {
            // เพิ่มข้อมูลใหม่
            $stmt = $pdo->prepare("
                INSERT INTO food_allergies 
                (student_id, food_name, detection_method, digestive_symptoms, skin_symptoms, respiratory_symptoms, created_at, updated_at)
                VALUES 
                (:student_id, :food_name, :detection_method, :digestive_symptoms, :skin_symptoms, :respiratory_symptoms, NOW(), NOW())
            ");
        }
        
        $stmt->execute([
            'student_id' => $student_id,
            'food_name' => $food_name,
            'detection_method' => $detection_method,
            'digestive_symptoms' => arrayToPostgresArray($digestive_symptoms),
            'skin_symptoms' => arrayToPostgresArray($skin_symptoms),
            'respiratory_symptoms' => arrayToPostgresArray($respiratory_symptoms)
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลการแพ้อาหารสำเร็จ']);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>