<?php
require_once(__DIR__ . '/../../../config/database.php');

header('Content-Type: application/json');

$view = $_GET['view'] ?? 'month';
$pdo = getDatabaseConnection();

try {
    if ($view === 'week') {
        $weekStart = $_GET['week_start'] ?? date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE(check_date) as date,
                COUNT(*) as total_count,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_count
            FROM attendance 
            WHERE DATE(check_date) BETWEEN ? AND ?
            GROUP BY DATE(check_date)
            ORDER BY date
        ");
        $stmt->execute([$weekStart, $weekEnd]);
    } else {
        $month = $_GET['month'] ?? date('Y-m');
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE(check_date) as date,
                COUNT(*) as total_count,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_count
            FROM attendance 
            WHERE TO_CHAR(check_date, 'YYYY-MM') = ?
            GROUP BY DATE(check_date)
            ORDER BY date
        ");
        $stmt->execute([$month]);
    }
    
    $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dailyStats)) {
        // ส่งข้อมูลว่างกลับไปแทนที่จะส่ง error
        echo json_encode([
            'total_days' => 0,
            'present_count' => 0,
            'absent_count' => 0,
            'leave_count' => 0,
            'attendance_rate' => 0,
            'daily_stats' => [],
            'no_data' => true, // เพิ่มฟิลด์เพื่อระบุว่าไม่มีข้อมูล
            'message' => 'ไม่พบข้อมูลในช่วงเวลาที่เลือก'
        ]);
        exit;
    }
    
    $monthlyStats = [
        'total_days' => count($dailyStats),
        'present_count' => array_sum(array_column($dailyStats, 'present_count')),
        'absent_count' => array_sum(array_column($dailyStats, 'absent_count')),
        'leave_count' => array_sum(array_column($dailyStats, 'leave_count')),
        'daily_stats' => $dailyStats
    ];
    
    $totalStudents = array_sum(array_column($dailyStats, 'total_count'));
    $monthlyStats['attendance_rate'] = $totalStudents > 0 
        ? round(($monthlyStats['present_count'] * 100) / $totalStudents, 1)
        : 0;
    
    echo json_encode($monthlyStats);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?> 