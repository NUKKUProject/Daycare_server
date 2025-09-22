<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

// เพิ่มการตั้งค่า timezone ที่ต้นไฟล์
date_default_timezone_set('Asia/Bangkok');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $pdo = getDatabaseConnection();
    
    // ตรวจสอบข้อมูลที่จำเป็น
    $requiredFields = [
        'student_id', 'age_year', 'age_month', 'age_day', 
        'weight', 'height', 'head_circumference', 'age_range', 
        'child_group', 'sex'
    ];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // ดึงข้อมูลจาก POST
    $studentId = $_POST['student_id'];
    $isDraft = isset($_POST['is_draft']) ? $_POST['is_draft'] : '0';
    $ageYear = $_POST['age_year'];
    $ageMonth = $_POST['age_month'];
    $ageDay = $_POST['age_day'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $headCircumference = $_POST['head_circumference'];
    $ageRange = $_POST['age_range'];
    $childGroup = $_POST['child_group'];
    $sex = $_POST['sex'];
    // รับข้อมูลพัฒนาการทั้ง 5 ด้าน
    $gm_pass = isset($_POST['gm_pass']) ? 1 : 0;
    $gm_delay = isset($_POST['gm_delay']) ? 1 : 0;
    $gm_issue = !empty($_POST['gm_issue']) ? (int)$_POST['gm_issue'] : null;

    $fm_pass = isset($_POST['fm_pass']) ? 1 : 0;
    $fm_delay = isset($_POST['fm_delay']) ? 1 : 0;
    $fm_issue = !empty($_POST['fm_issue']) ? (int)$_POST['fm_issue'] : null;

    $rl_pass = isset($_POST['rl_pass']) ? 1 : 0;
    $rl_delay = isset($_POST['rl_delay']) ? 1 : 0;
    $rl_issue = !empty($_POST['rl_issue']) ? (int)$_POST['rl_issue'] : null;

    $el_pass = isset($_POST['el_pass']) ? 1 : 0;
    $el_delay = isset($_POST['el_delay']) ? 1 : 0;
    $el_issue = !empty($_POST['el_issue']) ? (int)$_POST['el_issue'] : null;

    $ps_pass = isset($_POST['ps_pass']) ? 1 : 0;
    $ps_delay = isset($_POST['ps_delay']) ? 1 : 0;
    $ps_issue = !empty($_POST['ps_issue']) ? (int)$_POST['ps_issue'] : null;
    $recordDate = $_POST['record_date'] ?? date('Y-m-d');
    $now = new DateTime('now', new DateTimeZone('Asia/Bangkok'));
    $currentTime = $now->format('H:i:s');
    $recordDateTime = $recordDate . ' ' . $currentTime;

    // คำนวณอายุเป็นเดือน
    $totalAgeInMonths = ($ageYear * 12) + $ageMonth + ($ageDay / 30);

    // ประเมินการเจริญเติบโต
    $weightStatus = evaluateGrowthStatus('weight', $weight);
    $heightStatus = evaluateGrowthStatus('height_age', $height);
    $weightHeightStatus = evaluateGrowthStatus('weight_height', $weight, $height);
    $headStatus = evaluateGrowthStatus('head', $headCircumference);

    // ตรวจสอบการบันทึกซ้ำเฉพาะกรณีเพิ่มข้อมูลใหม่
    if (!isset($_POST['record_id'])) {
        $checkSql = "SELECT id FROM growth_records 
                     WHERE student_id = :student_id 
                     AND DATE(created_at) = :record_date
                     AND is_draft = false";
        
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'student_id' => $studentId,
            'record_date' => $recordDate
        ]);

        if ($checkStmt->rowCount() > 0 && $isDraft == '0') {
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้ เนื่องจากมีการบันทึกข้อมูลแล้วในวันที่เลือก');
        }
    }

    // ตรวจสอบการบันทึกซ้ำในวันเดียวกัน
    if (isset($_POST['record_id'])) {
        // กรณีแก้ไขข้อมูล (UPDATE)
        $sql = "UPDATE growth_records SET 
            student_id = :student_id,
            age_year = :age_year,
            age_month = :age_month,
            age_day = :age_day,
            weight = :weight,
            height = :height,
            head_circumference = :head_circumference,
            age_range = :age_range,
            child_group = :child_group,
            sex = :sex,
            gm_status = :gm_status,
            gm_issue = :gm_issue,
            fm_status = :fm_status,
            fm_issue = :fm_issue,
            rl_status = :rl_status,
            rl_issue = :rl_issue,
            el_status = :el_status,
            el_issue = :el_issue,
            ps_status = :ps_status,
            ps_issue = :ps_issue,
            updated_at = :updated_at,
            is_draft = :is_draft
            WHERE id = :record_id";

        $params = [
            'student_id' => $studentId,
            'age_year' => $ageYear,
            'age_month' => $ageMonth,
            'age_day' => $ageDay,
            'weight' => $weight,
            'height' => $height,
            'head_circumference' => $headCircumference,
            'age_range' => $ageRange,
            'child_group' => $childGroup,
            'sex' => $sex,
            'gm_status' => $_POST['gm_status'] ?? null,
            'gm_issue' => !empty($_POST['gm_issue']) ? $_POST['gm_issue'] : null,
            'fm_status' => $_POST['fm_status'] ?? null,
            'fm_issue' => !empty($_POST['fm_issue']) ? $_POST['fm_issue'] : null,
            'rl_status' => $_POST['rl_status'] ?? null,
            'rl_issue' => !empty($_POST['rl_issue']) ? $_POST['rl_issue'] : null,
            'el_status' => $_POST['el_status'] ?? null,
            'el_issue' => !empty($_POST['el_issue']) ? $_POST['el_issue'] : null,
            'ps_status' => $_POST['ps_status'] ?? null,
            'ps_issue' => !empty($_POST['ps_issue']) ? $_POST['ps_issue'] : null,
            'updated_at' => $recordDateTime,
            'is_draft' => $isDraft,
            'record_id' => $_POST['record_id']
        ];

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        // Debug log
        error_log("Updating growth record with params: " . print_r($params, true));
    } else {
        // กรณีเพิ่มข้อมูลใหม่ (INSERT)
        $sql = "INSERT INTO growth_records (
            student_id, age_year, age_month, age_day,
            weight, height, head_circumference,
            age_range, child_group, sex, created_at,
            gm_status, gm_issue,
            fm_status, fm_issue,
            rl_status, rl_issue,
            el_status, el_issue,
            ps_status, ps_issue,
            is_draft
        ) VALUES (
            :student_id, :age_year, :age_month, :age_day,
            :weight, :height, :head_circumference,
            :age_range, :child_group, :sex, :created_at,
            :gm_status, :gm_issue,
            :fm_status, :fm_issue,
            :rl_status, :rl_issue,
            :el_status, :el_issue,
            :ps_status, :ps_issue,
            :is_draft
        )";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            'student_id' => $studentId,
            'age_year' => $ageYear,
            'age_month' => $ageMonth,
            'age_day' => $ageDay,
            'weight' => $weight,
            'height' => $height,
            'head_circumference' => $headCircumference,
            'age_range' => $ageRange,
            'child_group' => $childGroup,
            'sex' => $sex,
            'created_at' => $recordDateTime,
            'gm_status' => $_POST['gm_status'] ?? null,
            'gm_issue' => !empty($_POST['gm_issue']) ? $_POST['gm_issue'] : null,
            'fm_status' => $_POST['fm_status'] ?? null,
            'fm_issue' => !empty($_POST['fm_issue']) ? $_POST['fm_issue'] : null,
            'rl_status' => $_POST['rl_status'] ?? null,
            'rl_issue' => !empty($_POST['rl_issue']) ? $_POST['rl_issue'] : null,
            'el_status' => $_POST['el_status'] ?? null,
            'el_issue' => !empty($_POST['el_issue']) ? $_POST['el_issue'] : null,
            'ps_status' => $_POST['ps_status'] ?? null,
            'ps_issue' => !empty($_POST['ps_issue']) ? $_POST['ps_issue'] : null,
            'is_draft' => $isDraft
        ]);
    }

    if (!$success) {
        throw new Exception('Failed to save data');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ',
        'growth_status' => [
            'weight' => $weightStatus,
            'height' => $heightStatus,
            'weight_height' => $weightHeightStatus,
            'head' => $headStatus
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}

// ฟังก์ชันประเมินการเจริญเติบโต (ใช้ฟังก์ชันเดียวกับ frontend)
function evaluateGrowthStatus($type, $value1, $value2 = null) {
    if (!$value1) return 'ไม่สามารถประเมินได้';
    
    $value1 = floatval($value1);
    
    switch($type) {
        case 'weight':
            if ($value1 < 10) return 'น้ำหนักน้อยกว่าเกณฑ์';
            if ($value1 < 12) return 'น้ำหนักค่อนข้างน้อย';
            if ($value1 < 15) return 'น้ำหนักตามเกณฑ์';
            if ($value1 < 17) return 'น้ำหนักค่อนข้างมาก';
            return 'น้ำหนักมากเกินเกณฑ์';
        
        case 'height_age':
            if ($value1 < 90) return 'เตี้ย';
            if ($value1 < 95) return 'ค่อนข้างเตี้ย';
            if ($value1 < 110) return 'ส่วนสูงตามเกณฑ์';
            if ($value1 < 115) return 'ค่อนข้างสูง';
            return 'สูง';
            
        case 'weight_height':
            if (!$value2) return 'ไม่สามารถประเมินได้';
            $value2 = floatval($value2);
            $bmi = $value1 / (($value2/100) * ($value2/100));
            if ($bmi < 16) return 'ผอม';
            if ($bmi < 17) return 'ค่อนข้างผอม';
            if ($bmi < 23) return 'สมส่วน';
            if ($bmi < 25) return 'ท้วม';
            return 'อ้วน';
        
        case 'head':
            if ($value1 < 40) return 'น้อยกว่าเปอร์เซ็นไทล์ที่ 3';
            if ($value1 < 42) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 3 - 15';
            if ($value1 < 44) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 15 - 50';
            if ($value1 < 46) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 50 - 85';
            if ($value1 < 48) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 85 - 97';
            return 'มากกว่าเปอร์เซ็นไทล์ที่ 97';
    }
    return 'ไม่สามารถประเมินได้';
}

// แปลงค่า checkbox เป็น status
function getStatus($pass, $delay) {
    if ($pass == '1') return 'pass';
    if ($delay == '1') return 'delay';
    return null;
} 