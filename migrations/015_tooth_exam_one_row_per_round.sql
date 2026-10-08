-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- เด็ก 1 คน ใน 1 รอบตรวจ ให้มีผลตรวจแถวเดียว (ครูบันทึกก่อน แพทย์มาอัปเดตแถวเดิม)
-- ข้อมูลเดิมที่มีแถวซ้ำ (ผลครู + ผลแพทย์ของเด็กคนเดียวกันในรอบเดียวกัน) จะเหลือแถวเดียว:
--   เลือกผลของแพทย์ก่อน ถ้าไม่มีใช้ผลครู แล้วเอาแถวล่าสุด  แถวที่ซ้ำจะถูกลบ
-- ก่อนลบจะสำรองแถวที่ซ้ำไว้ในตาราง health_tooth_external_dup_backup (ลบทิ้งเองได้เมื่อตรวจสอบแล้ว)
--
-- ดูตัวอย่างแถวที่จะถูกลบก่อนรันได้ด้วย:
--   SELECT d.* FROM health_tooth_external d JOIN (
--     SELECT id, ROW_NUMBER() OVER (PARTITION BY student_id, round_id ORDER BY
--       CASE exam_type WHEN 'doctor' THEN 0 WHEN 'teacher' THEN 1 ELSE 2 END, id DESC) AS rn
--     FROM health_tooth_external WHERE round_id IS NOT NULL) r ON r.id = d.id WHERE r.rn > 1;

CREATE TABLE IF NOT EXISTS health_tooth_external_dup_backup AS
SELECT * FROM health_tooth_external WHERE FALSE;

WITH ranked AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY student_id, round_id ORDER BY
               CASE exam_type WHEN 'doctor' THEN 0 WHEN 'teacher' THEN 1 ELSE 2 END, id DESC) AS rn
    FROM health_tooth_external
    WHERE round_id IS NOT NULL
), dups AS (
    SELECT id FROM ranked WHERE rn > 1
), moved AS (
    INSERT INTO health_tooth_external_dup_backup
    SELECT h.* FROM health_tooth_external h JOIN dups USING (id)
    RETURNING id
)
DELETE FROM health_tooth_external WHERE id IN (SELECT id FROM moved);

-- กันซ้ำถาวร
CREATE UNIQUE INDEX IF NOT EXISTS uq_health_tooth_student_round
    ON health_tooth_external (student_id, round_id) WHERE round_id IS NOT NULL;
