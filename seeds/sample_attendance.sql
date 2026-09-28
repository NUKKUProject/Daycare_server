-- ตัวอย่างข้อมูลประวัติการมาเรียน 10 รายการ (วันทำการล่าสุด 10 วัน) ของเด็กรหัส 'test'
-- ถ้าต้องการใช้เด็กคนอื่น ให้แก้รหัสใน CTE "target" (ต้องมีเด็กรหัสนี้ในตาราง children ก่อน
-- ไม่เช่นนั้นจะไม่แทรกอะไร)
-- รันซ้ำได้ ข้ามวันที่เด็กคนนั้นมีบันทึกอยู่แล้ว
--
-- รัน: docker exec -i postgres psql -U postgres -d nu_daycare < seeds/sample_attendance.sql

WITH target AS (
    SELECT studentid
    FROM children
    WHERE studentid = 'test'
),
days AS (
    SELECT day, row_number() OVER (ORDER BY day DESC) AS n
    FROM (
        SELECT d::date AS day
        FROM generate_series(CURRENT_DATE - 20, CURRENT_DATE, interval '1 day') d
        WHERE EXTRACT(ISODOW FROM d) BETWEEN 1 AND 5
        ORDER BY d DESC
        LIMIT 10
    ) recent
),
pattern (n, status, in_time, out_time, note) AS (
    VALUES
        (1,  'present', TIME '08:05', TIME '15:40', NULL),
        (2,  'present', TIME '08:12', TIME '15:35', NULL),
        (3,  'late',    TIME '08:50', TIME '15:45', NULL),
        (4,  'present', TIME '07:58', TIME '15:30', NULL),
        (5,  'leave',   TIME '00:00', NULL,         'ลาป่วย มีไข้'),
        (6,  'present', TIME '08:10', TIME '15:50', NULL),
        (7,  'present', TIME '08:01', TIME '15:38', NULL),
        (8,  'late',    TIME '08:40', TIME '15:42', NULL),
        (9,  'absent',  TIME '00:00', NULL,         NULL),
        (10, 'present', TIME '08:08', TIME '15:36', NULL)
)
INSERT INTO attendance (student_id, check_date, status, leave_note, status_checkout, check_out_time)
SELECT t.studentid,
       d.day + p.in_time,
       p.status,
       p.note,
       CASE WHEN p.out_time IS NOT NULL THEN 'checked_out' END,
       p.out_time
FROM target t
CROSS JOIN days d
JOIN pattern p ON p.n = d.n
WHERE NOT EXISTS (
    SELECT 1 FROM attendance a
    WHERE a.student_id = t.studentid
      AND a.check_date::date = d.day
);
