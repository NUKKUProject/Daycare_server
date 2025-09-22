<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json');
try {
    // ตั้งค่า timezone เป็นประเทศไทย
    date_default_timezone_set('Asia/Bangkok');
    
    $pdo = getDatabaseConnection();
    
    // รับข้อมูล JSON และแปลงเป็น array
    
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON input");
    }

    // เตรียมคำสั่ง SQL
    $sql = "INSERT INTO health_data_external (
                exam_date,
                academic_year,
                doctor_name,
                student_id,
                prefix_th,
                first_name,
                last_name_th,
                child_grop,
                classroom,
                birth_date,
                age_year,
                age_month,
                age_day,
                vital_signs,
                behavior,
                physical_measures,
                development_assessment,
                physical_exam,
                neurological,
                recommendation,
                signature,
                nickname,
                created_at,
                updated_at               
            ) VALUES (
                :exam_date,
                :academic_year,
                :doctor_name,
                :student_id,
                :prefix_th,
                :first_name,
                :last_name_th,
                :child_grop,
                :classroom,
                :birth_date,
                :age_year,
                :age_month,
                :age_day,
                :vital_signs,
                :behavior,
                :physical_measures,
                :development_assessment,
                :physical_exam,
                :neurological,
                :recommendation,
                :signature,
                :nickname,
                NOW(),
                NOW()          
            )";

    $stmt = $pdo->prepare($sql);

    // bind parameters
    $stmt->execute([
        ':exam_date' => $data['exam_date'],
        ':academic_year' => $data['academic_year'],
        ':doctor_name' => $data['doctor_name'],
        ':student_id' => $data['student_id'],
        ':prefix_th' => $data['prefix_th'],
        ':first_name' => $data['first_name_th'],
        ':last_name_th' => $data['last_name_th'],
        ':child_grop' => $data['child_grop'],
        ':classroom' => $data['classroom'],
        ':birth_date' => $data['birth_date'],
        ':age_year' => $data['age_year'],
        ':age_month' => $data['age_month'],
        ':age_day' => $data['age_day'],
        ':vital_signs' => json_encode($data['vital_signs']),
        ':behavior' => json_encode($data['behavior']),
        ':physical_measures' => json_encode($data['physical_measures']),
        ':development_assessment' => json_encode($data['development_assessment']),
        ':physical_exam' => json_encode($data['physical_exam']),
        ':neurological' => json_encode($data['neurological']),
        ':recommendation' => $data['recommendation'],
        ':signature' => $data['signature'],
        ':nickname' => $data['nickname']
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ',
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 