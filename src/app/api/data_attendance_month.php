<?php 
include_once '../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection(); // << สำคัญ: รับ PDO จากฟังก์ชัน

    $sql = "
WITH months AS (
  SELECT generate_series(
    make_date(:year::int, 1, 1),
    make_date(:year::int, 12, 1),
    interval '1 month'
  )::date AS month_start
),
roster_group AS (
  SELECT classroom, child_group, COUNT(*)::int AS student_total
  FROM children
  GROUP BY classroom, child_group
),
att_last_per_day AS (
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
    date_trunc('month', al.d)::date AS month_start,

    COUNT(*)::int AS student_scan_in, -- นับเด็ก-วัน
    COUNT(*) FILTER (
      WHERE al.check_out_time IS NOT NULL OR al.status_checkout = 'checked_out'
    )::int AS student_scan_out
  FROM att_last_per_day al
  JOIN children c ON c.studentid = al.student_id
  GROUP BY c.classroom, c.child_group, date_trunc('month', al.d)
)
SELECT
  to_char(m.month_start, 'YYYY-MM') AS month,
  extract(month from m.month_start)::int AS month_no,
  CASE extract(month from m.month_start)::int
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

  EXTRACT(day FROM (m.month_start + INTERVAL '1 month - 1 day'))::int AS days_in_month,

  (
    SELECT COUNT(*)
    FROM generate_series(
      m.month_start,
      (m.month_start + INTERVAL '1 month - 1 day')::date,
      INTERVAL '1 day'
    ) d
    WHERE EXTRACT(dow FROM d) BETWEEN 1 AND 5
  )::int AS weekdays_in_month,


  rg.child_group,
  rg.classroom,
  rg.student_total,
  COALESCE(adg.student_scan_in, 0)  AS student_scan_in,
  COALESCE(adg.student_scan_out, 0) AS student_scan_out
FROM roster_group rg
CROSS JOIN months m
LEFT JOIN att_daily_group adg
  ON adg.classroom = rg.classroom
 AND adg.child_group = rg.child_group
 AND adg.month_start = m.month_start
ORDER BY rg.classroom, rg.child_group, m.month_start;";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['year' => $_GET['year'] ?? date('Y')]);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>