<?php
require_once(__DIR__ . '/../../../config/database.php');

header('Content-Type: application/json');

// ตรวจสอบว่ามี studentid ส่งมาหรือไม่
if (!isset($_GET['studentid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบรหัสนักเรียน'
    ]);
    exit;
}

$studentid = $_GET['studentid'];
$displayType = $_GET['displayType'] ?? 'range';
$singleDate = $_GET['singleDate'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

try {
    $pdo = getDatabaseConnection();
    
    // แก้ไข query ให้ใช้ created_at แทน measurement_date
    $query = "SELECT g.*, 
              c.firstname_th, c.lastname_th, c.nickname,
              g.age_year,
              g.age_month
              FROM growth_records g
              JOIN children c ON g.student_id = c.studentid
              WHERE g.student_id = :studentid";
    
    $params = ['studentid' => $studentid];

    // แก้ไขเงื่อนไขการกรองวันที่ให้ใช้ created_at
    if ($displayType === 'date' && !empty($singleDate)) {
        $query .= " AND DATE(g.created_at) = :measurement_date";
        $params['measurement_date'] = $singleDate;
    } elseif ($displayType === 'range' && !empty($startDate) && !empty($endDate)) {
        $query .= " AND g.created_at BETWEEN :start_date AND :end_date";
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
    }

    $query .= " ORDER BY g.created_at ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลงข้อมูลให้เหมาะสมกับการใช้งาน
    $formattedRecords = array_map(function($record) {
        return [
            'id' => $record['id'],
            'student_id' => $record['student_id'],
            'measurement_date' => $record['created_at'], // ใช้ created_at แทน measurement_date
            'weight' => floatval($record['weight']),
            'height' => floatval($record['height']),
            'head_circumference' => floatval($record['head_circumference']),
            'age_year' => intval($record['age_year']),
            'age_month' => intval($record['age_month']),
            'student_name' => $record['firstname_th'] . ' ' . $record['lastname_th'],
            'nickname' => $record['nickname']
        ];
    }, $records);

    echo json_encode([
        'status' => 'success',
        'records' => $formattedRecords
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?> 