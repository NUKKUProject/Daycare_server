<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // รับ ID จาก URL
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ไม่พบ ID ที่ต้องการดู');
    }

    // ดึงข้อมูลรายละเอียดจากตาราง health_data
    $stmt = $pdo->prepare("SELECT * FROM health_data WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('ไม่พบข้อมูล');
    }

    // แปลงข้อมูล JSON เป็น array
    $checkboxFields = [
        'hair', 'eye', 'mouth', 'teeth', 'ears', 'nose', 'nails',
        'skin', 'hands_feet', 'arms_legs', 'body', 'symptoms', 'medicine'
    ];

    foreach ($checkboxFields as $field) {
        if (isset($result[$field]) && !empty($result[$field])) {
            $result[$field] = json_decode($result[$field], true);
        } else {
            $result[$field] = ['checked' => [], 'unchecked' => []];
        }
    }

    // จัดการข้อมูลเพิ่มเติม
    $additionalFields = [
        'eye_condition', 'nose_condition', 'teeth_count', 'fever_temp', 
        'cough_type', 'skin_wound_detail', 'skin_rash_detail', 'medicine_detail'
    ];

    foreach ($additionalFields as $field) {
        if (!isset($result[$field])) {
            $result[$field] = null;
        }
    }

    // จัดการข้อมูล reason fields
    $reasonFields = [
        'hair_reason', 'eye_reason', 'nose_reason', 'symptoms_reason',
        'medicine_reason', 'illness_reason', 'accident_reason', 'teacher_note'
    ];

    foreach ($reasonFields as $field) {
        if (!isset($result[$field])) {
            $result[$field] = null;
        }
    }

    // เพิ่มข้อมูลเวลา
    $result['formatted_date'] = date('d/m/Y H:i:s', strtotime($result['created_at']));

    // ส่งผลลัพธ์
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 