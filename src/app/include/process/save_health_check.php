<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    // ตั้งค่า timezone เป็นประเทศไทย
    date_default_timezone_set('Asia/Bangkok');
    
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON และแปลงเป็น array
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!$data || empty($data['student_id']) || empty($data['teacher_signature'])) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    // ใช้วันที่ที่ส่งมาและเพิ่มเวลาปัจจุบัน
    if (isset($data['created_at'])) {
        $data['created_at'] = $data['created_at'] . ' ' . date('H:i:s.u');
    } else {
        throw new Exception('กรุณาระบุวันที่');
    }

    // แปลงข้อมูล arrays เป็น JSON strings
    $jsonFields = [
        'hair', 'eye', 'mouth', 'teeth', 'ears', 'nose', 
        'nails', 'skin', 'hands_feet', 'arms_legs', 'body', 
        'symptoms', 'medicine'
    ];

    foreach ($jsonFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = json_encode($data[$field]);
        }
    }

    // สร้าง SQL query
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO health_data (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 