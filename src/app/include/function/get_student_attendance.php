<?php
require_once(__DIR__ . '/../../../config/database.php');

$studentid = $_GET['studentid'] ?? null;
$displayType = $_GET['displayType'] ?? 'all';
$date = $_GET['date'] ?? null;
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;

try {
    $pdo = getDatabaseConnection();

    // สร้าง query พื้นฐาน
    $sql = "SELECT a.*,
     c.studentid as student_id,
     c.prefix_th,
     c.firstname_th,
     c.lastname_th,
     c.child_group,
     c.classroom,
     CASE 
         WHEN a.status_checkout = 'checked_out' THEN 'กลับแล้ว'
         WHEN a.status_checkout = 'no_checked_out' THEN 'ยังไม่กลับ'
         ELSE NULL 
     END as status_checkout_th,
     CASE 
         WHEN a.status = 'present' THEN 'มาเรียน'
         WHEN a.status = 'absent' THEN 'ไม่มาเรียน'
         WHEN a.status = 'leave' THEN 'ลา'
         ELSE a.status 
     END as status_th
     FROM attendance a
     JOIN children c ON a.student_id = c.studentid
     WHERE a.student_id = :studentid";

    $params = ['studentid' => $studentid];

    // เพิ่มเงื่อนไขตามประเภทการแสดงผล
    switch ($displayType) {
        case 'date':
            if ($date) {
                $sql .= " AND DATE(a.check_date) = :date";
                $params['date'] = $date;
            }
            break;
        case 'range':
            if ($startDate && $endDate) {
                $sql .= " AND DATE(a.check_date) BETWEEN :start_date AND :end_date";
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }
            break;
        // case 'all' ไม่ต้องเพิ่มเงื่อนไข
    }

    // เรียงลำดับตามวันที่ล่าสุด
    $sql .= " ORDER BY a.check_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งผลลัพธ์กลับเป็น JSON
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