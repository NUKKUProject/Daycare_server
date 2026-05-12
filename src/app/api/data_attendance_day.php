<?php
include_once '../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();
    $year = (int)($_GET['year'] ?? date('Y'));

    $sql = "
WITH days AS (
  SELECT gs.d::date AS d
  FROM generate_series(
    make_date(:year::int, 1, 1),
    make_date(:year::int, 12, 31),
    interval '1 day'
  ) AS gs(d)
  WHERE EXTRACT(dow FROM gs.d) BETWEEN 1 AND 5
),
roster_group AS (
  SELECT classroom, child_group, COUNT(*)::int AS student_total
  FROM children
  GROUP BY classroom, child_group
),
att_last_per_day AS (
  -- ล่าสุดต่อ นักเรียน/วัน กันการบันทึกซ้ำในวันเดียว
  SELECT DISTINCT ON (a.student_id, a.check_date::date)
    a.student_id,
    a.check_date::date AS d,
    a.check_out_time,
    a.status_checkout
  FROM attendance a
  WHERE a.check_date >= make_date(:year::int, 1, 1)
    AND a.check_date <  make_date((:year::int + 1), 1, 1)
  ORDER BY a.student_id, a.check_date::date, a.check_date DESC
),
att_daily_group AS (
  SELECT
    c.classroom,
    c.child_group,
    al.d,
    COUNT(*)::int AS student_scan_in, -- เด็ก-วัน
    COUNT(*) FILTER (
      WHERE al.check_out_time IS NOT NULL OR al.status_checkout = 'checked_out'
    )::int AS student_scan_out
  FROM att_last_per_day al
  JOIN children c ON c.studentid = al.student_id
  GROUP BY c.classroom, c.child_group, al.d
)
SELECT
  dd.d,
  EXTRACT(month FROM dd.d)::int AS month_no,
  EXTRACT(day   FROM dd.d)::int AS day_no,
  CASE EXTRACT(month FROM dd.d)::int
    WHEN 1 THEN 'มกราคม'
    WHEN 2 THEN 'กุมภาพันธ์'
    WHEN 3 THEN 'มีนาคม'
    WHEN 4 THEN 'เมษายน'
    WHEN 5 THEN 'พฤษภาคม'
    WHEN 6 THEN 'มิถุนายน'
    WHEN 7 THEN 'กรกฎาคม'
    WHEN 8 THEN 'สิงหาคม'
    WHEN 9 THEN 'กันยายน'
    WHEN 10 THEN 'ตุลาคม'
    WHEN 11 THEN 'พฤศจิกายน'
    WHEN 12 THEN 'ธันวาคม'
  END AS month_name_th,

  rg.classroom,
  rg.child_group,
  rg.student_total,
  COALESCE(adg.student_scan_in, 0)  AS student_scan_in,
  COALESCE(adg.student_scan_out, 0) AS student_scan_out
FROM roster_group rg
CROSS JOIN days dd
LEFT JOIN att_daily_group adg
  ON adg.classroom = rg.classroom
 AND adg.child_group = rg.child_group
 AND adg.d = dd.d
ORDER BY dd.d, rg.classroom, rg.child_group;
";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['year' => $year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดรูป JSON: เดือน -> วัน -> list ของ classroom/child_group
    $data = [];
    foreach ($rows as $r) {
        $month = $r['month_name_th'];        // "มกราคม"
        $day   = (string)$r['day_no'];       // "1","2",...

        if (!isset($data[$month])) $data[$month] = [];
        if (!isset($data[$month][$day])) $data[$month][$day] = [];

        $data[$month][$day][] = [
            'classroom'       => $r['classroom'],
            'child_group'     => $r['child_group'],
            'student_total'   => (int)$r['student_total'],
            'student_scan_in'    => (int)$r['student_scan_in'],
            'student_scan_out'    => (int)$r['student_scan_out'],
        ];
    }

    echo json_encode([
        'success' => true,
        'ปี' => $year,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}