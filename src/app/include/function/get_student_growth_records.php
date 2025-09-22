<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    if (!isset($_GET['student_id'])) {
        throw new Exception('Missing student ID');
    }

    $pdo = getDatabaseConnection();
    $studentId = $_GET['student_id'];

    // ดึงข้อมูลทั้งหมดของนักเรียน เรียงตามวันที่
    $sql = "
        SELECT gr.*, 
               CONCAT(c.prefix_th, c.firstname_th, ' ', c.lastname_th) as student_name,
               c.sex
        FROM growth_records gr
        JOIN children c ON gr.student_id = c.studentid
        WHERE gr.student_id = :student_id
        AND gr.is_draft = false
    ";

    // เพิ่มเงื่อนไขถ้ามี record_id
    if (isset($_GET['record_id'])) {
        $sql .= " AND gr.id = :record_id";
        $params = [
            ':student_id' => $studentId,
            ':record_id' => $_GET['record_id']
        ];
    } else {
        $params = [':student_id' => $studentId];
    }

    $sql .= " ORDER BY gr.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // คำนวณผลการประเมินสำหรับแต่ละรายการ
    foreach ($records as &$record) {
        $record['growth_status'] = evaluateGrowthStatus($record);
        $record['sex'] = $record['sex'] === 'ชาย' ? 'M' : 'F';
    }

    echo json_encode([
        'status' => 'success',
        'current_record' => end($records), // ข้อมูลล่าสุด
        'all_records' => $records // ข้อมูลทั้งหมด
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// ฟังก์ชันประเมินการเจริญเติบโต
function evaluateGrowthStatus($record) {
    $weight = floatval($record['weight']);
    $height = floatval($record['height']);
    $head = floatval($record['head_circumference']);

    return [
        'weight' => getWeightStatus($weight),
        'height_age' => getHeightStatus($height),
        'weight_height' => getWeightHeightStatus($weight, $height),
        'head' => getHeadStatus($head)
    ];
}

// ฟังก์ชันประเมินน้ำหนักตามเกณฑ์อายุ
function getWeightStatus($weight) {
    if (!$weight) return 'ไม่สามารถประเมินได้';
    if ($weight < 10) return 'น้ำหนักน้อยกว่าเกณฑ์';
    if ($weight < 12) return 'น้ำหนักค่อนข้างน้อย';
    if ($weight < 15) return 'น้ำหนักตามเกณฑ์';
    if ($weight < 17) return 'น้ำหนักค่อนข้างมาก';
    return 'น้ำหนักมากเกินเกณฑ์';
}

// ฟังก์ชันประเมินส่วนสูงตามเกณฑ์อายุ
function getHeightStatus($height) {
    if (!$height) return 'ไม่สามารถประเมินได้';
    if ($height < 90) return 'เตี้ย';
    if ($height < 95) return 'ค่อนข้างเตี้ย';
    if ($height < 110) return 'ส่วนสูงตามเกณฑ์';
    if ($height < 115) return 'ค่อนข้างสูง';
    return 'สูง';
}

// ฟังก์ชันประเมินน้ำหนักตามเกณฑ์ส่วนสูง
function getWeightHeightStatus($weight, $height) {
    if (!$weight || !$height) return 'ไม่สามารถประเมินได้';
    $bmi = $weight / (($height/100) * ($height/100));
    if ($bmi < 16) return 'ผอม';
    if ($bmi < 17) return 'ค่อนข้างผอม';
    if ($bmi < 23) return 'สมส่วน';
    if ($bmi < 25) return 'ท้วม';
    return 'อ้วน';
}

// ฟังก์ชันประเมินเส้นรอบศีรษะตามเกณฑ์อายุ
function getHeadStatus($head) {
    if (!$head) return 'ไม่สามารถประเมินได้';
    if ($head < 40) return 'น้อยกว่าเปอร์เซ็นไทล์ที่ 3';
    if ($head < 42) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 3 - 15';
    if ($head < 44) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 15 - 50';
    if ($head < 46) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 50 - 85';
    if ($head < 48) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 85 - 97';
    return 'มากกว่าเปอร์เซ็นไทล์ที่ 97';
}

function getStudentGrowthRecords($studentId, $recordId = null) {
    global $pdo;
    
    // ดึงข้อมูลการเจริญเติบโตและเพศของเด็ก
    $query = "
        SELECT gr.*, c.sex 
        FROM growth_records gr
        JOIN children c ON gr.student_id = c.studentid
        WHERE gr.student_id = :student_id
    ";
    
    if ($recordId) {
        $query .= " AND gr.id = :record_id";
    }
    
    $stmt = $pdo->prepare($query);
    $params = ['student_id' => $studentId];
    
    if ($recordId) {
        $params['record_id'] = $recordId;
    }
    
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
} 