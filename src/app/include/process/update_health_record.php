<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการแก้ไข');
    }

    // เตรียมคำสั่ง SQL สำหรับอัพเดทข้อมูล
    $sql = "UPDATE health_data SET 
        student_id = :student_id,
        prefix_th = :prefix_th,
        first_name_th = :first_name_th,
        last_name_th = :last_name_th,
        child_group = :child_group,
        classroom = :classroom,
        hair = :hair,
        eye = :eye,
        mouth = :mouth,
        teeth = :teeth,
        ears = :ears,
        nose = :nose,
        nails = :nails,
        skin = :skin,
        hands_feet = :hands_feet,
        arms_legs = :arms_legs,
        body = :body,
        symptoms = :symptoms,
        medicine = :medicine,
        eye_condition = :eye_condition,
        nose_condition = :nose_condition,
        teeth_count = :teeth_count,
        fever_temp = :fever_temp,
        cough_type = :cough_type,
        skin_wound_detail = :skin_wound_detail,
        skin_rash_detail = :skin_rash_detail,
        medicine_detail = :medicine_detail,
        hair_reason = :hair_reason,
        eye_reason = :eye_reason,
        nose_reason = :nose_reason,
        symptoms_reason = :symptoms_reason,
        medicine_reason = :medicine_reason,
        illness_reason = :illness_reason,
        accident_reason = :accident_reason,
        teacher_note = :teacher_note,
        teacher_signature = :teacher_signature,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    
    // แปลงข้อมูล checkbox เป็น JSON
    $checkboxFields = ['hair', 'eye', 'mouth', 'teeth', 'ears', 'nose', 'nails', 
                      'skin', 'hands_feet', 'arms_legs', 'body', 'symptoms', 'medicine'];
    
    foreach ($checkboxFields as $field) {
        if (isset($data[$field]) && is_array($data[$field])) {
            $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
        }
    }

    // จัดการค่าตัวเลข - แปลงค่าว่างเป็น null
    $teeth_count = !empty($data['teeth_count']) ? $data['teeth_count'] : null;
    $fever_temp = !empty($data['fever_temp']) ? $data['fever_temp'] : null;

    // Bind parameters
    $params = [
        ':id' => $data['id'],
        ':student_id' => $data['student_id'],
        ':prefix_th' => $data['prefix_th'],
        ':first_name_th' => $data['first_name_th'],
        ':last_name_th' => $data['last_name_th'],
        ':child_group' => $data['child_group'],
        ':classroom' => $data['classroom'],
        ':hair' => $data['hair'],
        ':eye' => $data['eye'],
        ':mouth' => $data['mouth'],
        ':teeth' => $data['teeth'],
        ':ears' => $data['ears'],
        ':nose' => $data['nose'],
        ':nails' => $data['nails'],
        ':skin' => $data['skin'],
        ':hands_feet' => $data['hands_feet'],
        ':arms_legs' => $data['arms_legs'],
        ':body' => $data['body'],
        ':symptoms' => $data['symptoms'],
        ':medicine' => $data['medicine'],
        ':eye_condition' => $data['eye_condition'] ?: null,
        ':nose_condition' => $data['nose_condition'] ?: null,
        ':teeth_count' => $teeth_count,
        ':fever_temp' => $fever_temp,
        ':cough_type' => $data['cough_type'] ?: null,
        ':skin_wound_detail' => $data['skin_wound_detail'] ?: null,
        ':skin_rash_detail' => $data['skin_rash_detail'] ?: null,
        ':medicine_detail' => $data['medicine_detail'] ?: null,
        ':hair_reason' => $data['hair_reason'] ?: null,
        ':eye_reason' => $data['eye_reason'] ?: null,
        ':nose_reason' => $data['nose_reason'] ?: null,
        ':symptoms_reason' => $data['symptoms_reason'] ?: null,
        ':medicine_reason' => $data['medicine_reason'] ?: null,
        ':illness_reason' => $data['illness_reason'] ?: null,
        ':accident_reason' => $data['accident_reason'] ?: null,
        ':teacher_note' => $data['teacher_note'] ?: null,
        ':teacher_signature' => $data['teacher_signature']
    ];

    if (!$stmt->execute($params)) {
        throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 