<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');

$pdo  = getDatabaseConnection();
$view = $_GET['view'] ?? 'month';

// filters
$childGroup = isset($_GET['child_group']) ? trim($_GET['child_group']) : null;
$classroom  = isset($_GET['classroom']) ? trim($_GET['classroom']) : null;
if ($childGroup === '') $childGroup = null;
if ($classroom === '')  $classroom  = null;

try {
    // 1) หา start/end ตาม view
    if ($view === 'week') {
        $startDate = $_GET['week_start'] ?? date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime($startDate . ' +6 days'));
    } else {
        $month     = $_GET['month'] ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate   = date('Y-m-t', strtotime($startDate));
    }

    // 2) ทำให้ช่วงไม่เกิน "วันนี้"
    $today = date('Y-m-d');
    if ($endDate > $today) $endDate = $today;

    // ถ้าช่วงอยู่ในอนาคตทั้งหมด
    if ($startDate > $endDate) {
        echo json_encode([
            'no_data' => true,
            'message' => 'ช่วงวันที่เลือกอยู่หลังวันปัจจุบัน',
            'total_students' => 0,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => 0,
            'expected_attendance' => 0,
            'present_total' => 0,
            'leave_total' => 0,
            'absent_total' => 0,
            'not_come_total' => 0,
            'attendance_rate' => 0,
            'daily_stats' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3) จำนวนนักเรียนทั้งหมด (ตาม filter)
    $sqlTotalStudents = "SELECT COUNT(*)::int FROM children WHERE 1=1";
    $paramsStudents = [];

    if ($childGroup !== null) {
        $sqlTotalStudents .= " AND child_group = :child_group";
        $paramsStudents[':child_group'] = $childGroup;
    }
    if ($classroom !== null) {
        $sqlTotalStudents .= " AND classroom = :classroom";
        $paramsStudents[':classroom'] = $classroom;
    }

    $stmtTotal = $pdo->prepare($sqlTotalStudents);
    $stmtTotal->execute($paramsStudents);
    $totalStudents = (int)$stmtTotal->fetchColumn();

    if ($totalStudents === 0) {
        echo json_encode([
            'no_data' => true,
            'message' => 'ไม่พบนักเรียนตามเงื่อนไขที่เลือก',
            'total_students' => 0,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => 0,
            'expected_attendance' => 0,
            'present_total' => 0,
            'leave_total' => 0,
            'absent_total' => 0,
            'not_come_total' => 0,
            'attendance_rate' => 0,
            'daily_stats' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4) daily_stats: สร้างวัน จ-ศ + ดึงสถานะล่าสุดต่อคนต่อวัน
    $params = [
        ':start_date' => $startDate,
        ':end_date'   => $endDate,
    ];

    $studentsWhere = "WHERE 1=1";
    if ($childGroup !== null) {
        $studentsWhere .= " AND child_group = :child_group";
        $params[':child_group'] = $childGroup;
    }
    if ($classroom !== null) {
        $studentsWhere .= " AND classroom = :classroom";
        $params[':classroom'] = $classroom;
    }

    $sqlDaily = "
        WITH students AS (
            SELECT studentid
            FROM children
            $studentsWhere
        ),
        calendar AS (
            SELECT gs::date AS d
            FROM generate_series(:start_date::date, :end_date::date, interval '1 day') gs
            WHERE EXTRACT(ISODOW FROM gs) BETWEEN 1 AND 5
        ),
        last_per_day AS (
            SELECT DISTINCT ON (a.student_id, a.check_date::date)
                a.student_id,
                a.check_date::date AS d,
                a.status
            FROM attendance a
            INNER JOIN students s ON s.studentid = a.student_id
            WHERE a.check_date::date BETWEEN :start_date::date AND :end_date::date
            ORDER BY a.student_id, a.check_date::date, a.check_date DESC
        ),
        agg AS (
            SELECT
                d,
                COUNT(*) FILTER (WHERE status IN ('present','late')) AS present_count,
                COUNT(*) FILTER (WHERE status = 'leave')            AS leave_count
            FROM last_per_day
            GROUP BY d
        )
        SELECT
            cal.d AS date,
            COALESCE(agg.present_count, 0) AS present_count,
            COALESCE(agg.leave_count, 0)   AS leave_count
        FROM calendar cal
        LEFT JOIN agg ON agg.d = cal.d
        ORDER BY cal.d;
    ";

    $stmt = $pdo->prepare($sqlDaily);
    $stmt->execute($params);
    $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5) คำนวณสรุป
    $totalDays = count($dailyStats);                 // จ-ศ เท่านั้น (ถึงวันนี้แล้ว)
    $expectedAttendance = $totalStudents * $totalDays;

    $presentTotal = 0;
    $leaveTotal   = 0;

    foreach ($dailyStats as &$r) {
        $r['present_count'] = (int)$r['present_count'];
        $r['leave_count']   = (int)$r['leave_count'];

        // ไม่มาเรียน(รวมลา) รายวัน = นักเรียนทั้งหมด - มาเรียน
        $r['not_come_count'] = max(0, $totalStudents - $r['present_count']);

        // ขาด(ไม่นับลา) รายวัน = นักเรียนทั้งหมด - มาเรียน - ลา
        $r['absent_count'] = max(0, $totalStudents - $r['present_count'] - $r['leave_count']);

        $presentTotal += $r['present_count'];
        $leaveTotal   += $r['leave_count'];
    }
    unset($r);

    $notComeTotal = max(0, $expectedAttendance - $presentTotal);                // รวมลา+ขาด
    $absentTotal  = max(0, $expectedAttendance - $presentTotal - $leaveTotal);  // ไม่รวมลา

    $attendanceRate = $expectedAttendance > 0
        ? round(($presentTotal * 100) / $expectedAttendance, 1)
        : 0;

    echo json_encode([
        'total_students'      => $totalStudents,
        'start_date'          => $startDate,
        'end_date'            => $endDate,
        'total_days'          => $totalDays,
        'expected_attendance' => $expectedAttendance,
        'present_total'       => $presentTotal,
        'leave_total'         => $leaveTotal,
        'absent_total'        => $absentTotal,
        'not_come_total'      => $notComeTotal,
        'attendance_rate'     => $attendanceRate,
        'daily_stats'         => $dailyStats
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}