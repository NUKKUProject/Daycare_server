<?php
require_once(__DIR__ . '/../../../config/database.php');
session_start();

// ตรวจสอบว่าเป็น admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
    exit;
}

function saveListVaccine($data) {
    try {
        if (!isset($data['age_group_id']) || !isset($data['vaccine_name'])) {
            throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
        }

        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีชื่อวัคซีนนี้ในกลุ่มอายุนี้แล้วหรือไม่
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM vaccine_list 
            WHERE age_group_id = ? AND vaccine_name = ? AND is_active = true
        ");
        $stmt->execute([$data['age_group_id'], $data['vaccine_name']]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('มีวัคซีนนี้ในกลุ่มอายุนี้แล้ว');
        }

        // เพิ่มข้อมูลวัคซีนใหม่
        $stmt = $pdo->prepare("
            INSERT INTO vaccine_list (
                age_group_id,
                vaccine_name,
                vaccine_description,
                is_active,
                created_at,
                updated_at
            ) VALUES (
                :age_group_id,
                :vaccine_name,
                :vaccine_description,
                true,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            )
        ");

        $result = $stmt->execute([
            'age_group_id' => $data['age_group_id'],
            'vaccine_name' => $data['vaccine_name'],
            'vaccine_description' => $data['vaccine_description'] ?? ''
        ]);

        if ($result) {
            return [
                'status' => 'success',
                'message' => 'บันทึกรายการวัคซีนสำเร็จ'
            ];
        } else {
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
        }

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// รับข้อมูลจาก AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode(saveListVaccine($data));
}
?> 