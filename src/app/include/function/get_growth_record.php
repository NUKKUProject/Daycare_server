<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    if (!isset($_GET['record_id'])) {
        throw new Exception('Missing record ID');
    }

    $pdo = getDatabaseConnection();
    $recordId = $_GET['record_id'];

    // ดึงข้อมูลการเจริญเติบโตและข้อมูลนักเรียน
    $sql = "
        SELECT gr.*, 
               CONCAT(c.prefix_th, c.firstname_th, ' ', c.lastname_th) as student_name,
               c.nickname,
               gr.age_range,
               gr.gm_status,
               gr.gm_issue,
               gr.fm_status,
               gr.fm_issue,
               gr.rl_status,
               gr.rl_issue,
               gr.el_status,
               gr.el_issue,
               gr.ps_status,
               gr.ps_issue
        FROM growth_records gr
        JOIN children c ON gr.student_id = c.studentid
        WHERE gr.id = :record_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':record_id' => $recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception('ไม่พบข้อมูลที่ต้องการ');
    }

    // คำนวณผลการประเมิน
    $weightStatus = evaluateGrowthStatus('weight', $record['weight']);
    $heightStatus = evaluateGrowthStatus('height_age', $record['height']);
    $weightHeightStatus = evaluateGrowthStatus('weight_height', $record['weight'], $record['height']);
    $headStatus = evaluateGrowthStatus('head', $record['head_circumference']);

    // จัดรูปแบบข้อมูลพัฒนาการ
    $developmentStatus = [
        'gm' => [
            'status' => $record['gm_status'],
            'issue' => $record['gm_issue']
        ],
        'fm' => [
            'status' => $record['fm_status'],
            'issue' => $record['fm_issue']
        ],
        'rl' => [
            'status' => $record['rl_status'],
            'issue' => $record['rl_issue']
        ],
        'el' => [
            'status' => $record['el_status'],
            'issue' => $record['el_issue']
        ],
        'ps' => [
            'status' => $record['ps_status'],
            'issue' => $record['ps_issue']
        ]
    ];

    // แปลงข้อความสถานะเป็นภาษาไทย
    $statusTranslation = [
        'pass' => 'ผ่าน',
        'delay' => 'สงสัยล่าช้า'
    ];

    // เพิ่มข้อความภาษาไทยสำหรับแต่ละด้าน
    $developmentNames = [
        'gm' => 'ด้านการเคลื่อนไหว',
        'fm' => 'ด้านกล้ามเนื้อมัดเล็กและสติปัญญา',
        'rl' => 'ด้านการเข้าใจภาษา',
        'el' => 'ด้านการใช้ภาษา',
        'ps' => 'ด้านการช่วยเหลือตัวเองและสังคม'
    ];

    // เพิ่มข้อมูลแปลเป็นภาษาไทยในผลลัพธ์
    foreach ($developmentStatus as $key => &$status) {
        $status['name'] = $developmentNames[$key];
        $status['status_text'] = isset($status['status']) ? $statusTranslation[$status['status']] : 'ไม่ระบุ';
    }

    echo json_encode([
        'status' => 'success',
        'record' => array_merge($record, [
            'development_status' => $developmentStatus
        ]),
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
}

// ฟังก์ชันประเมินการเจริญเติบโต
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