<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

try {
    if (!isset($_GET['record_id'])) {
        throw new Exception('Missing record ID');
    }

    $pdo = getDatabaseConnection();
    $recordId = $_GET['record_id'];

    // เพิ่มการดึงข้อมูลจากตาราง children ด้วย
    $sql = "
        SELECT gr.*, 
               CONCAT(c.prefix_th, c.firstname_th, ' ', c.lastname_th) as student_name,
               c.nickname as student_nickname,
               c.child_group, c.classroom
        FROM growth_records gr
        JOIN children c ON gr.student_id = c.studentid
        WHERE gr.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception('Record not found');
    }

    // ใช้ฟังก์ชันประเมินเดียวกับที่ใช้ในการบันทึก
    require_once(__DIR__ . '/save_growth_data.php');

    $growthStatus = [
        'weight' => evaluateGrowthStatus('weight', $record['weight']),
        'height_age' => evaluateGrowthStatus('height_age', $record['height']),
        'weight_height' => evaluateGrowthStatus('weight_height', $record['weight'], $record['height']),
        'head' => evaluateGrowthStatus('head', $record['head_circumference'])
    ];

    echo json_encode([
        'status' => 'success',
        'record' => $record,
        'growth_status' => $growthStatus
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 