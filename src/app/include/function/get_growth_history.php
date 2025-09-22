<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();

    // รับพารามิเตอร์จาก POST
    $childGroup = $_POST['child_group'] ?? '';
    $classroom = $_POST['classroom'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $search = $_POST['search'] ?? '';

    // สร้าง query พื้นฐาน
    $sql = "
        SELECT 
            c.studentid,
            c.prefix_th,
            c.firstname_th,
            c.lastname_th,
            c.nickname,
            c.child_group,
            c.classroom,
            gr.id,
            gr.weight,
            gr.height,
            gr.head_circumference,
            gr.age_year,
            gr.age_month,
            gr.age_day,
            gr.created_at
        FROM children c
        LEFT JOIN (
            SELECT *
            FROM growth_records
            WHERE DATE(created_at) = :date
            AND is_draft = false
        ) gr ON c.studentid = gr.student_id
        WHERE 1=1
    ";

    $params = [':date' => $date];

    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($childGroup)) {
        $sql .= " AND c.child_group = :child_group";
        $params[':child_group'] = $childGroup;
    }

    if (!empty($classroom)) {
        $sql .= " AND c.classroom = :classroom";
        $params[':classroom'] = $classroom;
    }

    if (!empty($search)) {
        $sql .= " AND (
            c.firstname_th LIKE :search 
            OR c.lastname_th LIKE :search 
            OR c.nickname LIKE :search
            OR c.studentid LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY c.child_group, c.classroom, c.firstname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เพิ่มการประเมินการเจริญเติบโตสำหรับแต่ละรายการที่มีข้อมูล
    foreach ($results as &$record) {
        if (isset($record['id'])) {
            // คำนวณผลการประเมินโดยตรง ไม่ใช้ฟังก์ชันจาก save_growth_data.php
            $weight = floatval($record['weight']);
            $height = floatval($record['height']);
            $head = floatval($record['head_circumference']);

            $record['growth_status'] = [
                'weight' => getWeightStatus($weight),
                'height_age' => getHeightStatus($height),
                'weight_height' => getWeightHeightStatus($weight, $height),
                'head' => getHeadStatus($head)
            ];
        }
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
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