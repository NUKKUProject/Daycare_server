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

    // ดึง check_round ล่าสุดสำหรับ student_id และ academic_year นี้
    $checkRound = 1;
    if (!empty($data['student_id']) && !empty($data['academic_year'])) {
        $stmt = $pdo->prepare("SELECT MAX(check_round) as max_round FROM health_data_external WHERE student_id = :student_id AND academic_year = :academic_year");
        $stmt->execute([
            ':student_id' => $data['student_id'],
            ':academic_year' => $data['academic_year']
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['max_round']) {
            $checkRound = (int)$result['max_round'] + 1;
        }
    }

    // ดึง recorded_by จาก session (ถ้ามี)
    $recordedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

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
                check_round,
                recorded_by,
                is_doctor_checked,
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
                :check_round,
                :recorded_by,
                :is_doctor_checked,
                NOW(),
                NOW()          
            )";

    $stmt = $pdo->prepare($sql);

    // แปลงค่า is_doctor_checked เป็น boolean (0 หรือ 1)
    $isDoctorChecked = 0; // default เป็น 0 (false)
    if (isset($data['is_doctor_checked'])) {
        $isDoctorChecked = filter_var($data['is_doctor_checked'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    // bind parameters
    $stmt->execute([
        ':exam_date' => $data['exam_date'],
        ':academic_year' => $data['academic_year'],
        ':doctor_name' => $data['doctor_name'] ?? null,
        ':student_id' => $data['student_id'],
        ':prefix_th' => $data['prefix_th'],
        ':first_name' => $data['first_name_th'],
        ':last_name_th' => $data['last_name_th'],
        ':child_grop' => $data['child_grop'],
        ':classroom' => $data['classroom'],
        ':birth_date' => $data['birth_date'] ?? null,
        ':age_year' => $data['age_year'] ?? null,
        ':age_month' => $data['age_month'] ?? null,
        ':age_day' => $data['age_day'] ?? null,
        ':vital_signs' => json_encode($data['vital_signs']),
        ':behavior' => json_encode($data['behavior']),
        ':physical_measures' => json_encode($data['physical_measures']),
        ':development_assessment' => json_encode($data['development_assessment']),
        ':physical_exam' => json_encode($data['physical_exam']),
        ':neurological' => json_encode($data['neurological']),
        ':recommendation' => $data['recommendation'] ?? null,
        ':signature' => $data['signature'] ?? null,
        ':nickname' => $data['nickname'] ?? null,
        ':check_round' => $checkRound,
        ':recorded_by' => $recordedBy,
        ':is_doctor_checked' => $isDoctorChecked
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ',
        'check_round' => $checkRound
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>