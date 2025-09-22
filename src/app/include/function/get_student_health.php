<?php
require_once(__DIR__ . '/../../../config/database.php');

$studentid = $_GET['studentid'] ?? null;
$displayType = $_GET['displayType'] ?? 'all';
$date = $_GET['date'] ?? null;
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;

try {
    $pdo = getDatabaseConnection();
    
    $sql = "SELECT h.*, c.prefix_th, c.firstname_th as first_name_th, c.lastname_th as last_name_th, 
            c.child_group, c.classroom
            FROM health_data h
            JOIN children c ON h.student_id = c.studentid
            WHERE h.student_id = :studentid";
    
    $params = ['studentid' => $studentid];

    // เพิ่มเงื่อนไขตามประเภทการแสดงผล
    switch ($displayType) {
        case 'date':
            if ($date) {
                $sql .= " AND DATE(h.created_at) = :date";
                $params['date'] = $date;
            }
            break;
        case 'range':
            if ($startDate && $endDate) {
                $sql .= " AND DATE(h.created_at) BETWEEN :start_date AND :end_date";
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }
            break;
        // case 'all' ไม่ต้องเพิ่มเงื่อนไข
    }
    
    $sql .= " ORDER BY h.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($results ?: []);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'data' => []
    ]);
} 