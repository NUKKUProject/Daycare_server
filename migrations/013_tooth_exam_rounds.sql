-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- รอบตรวจสุขภาพช่องปาก + ผู้ตรวจ (ครูคัดกรอง / แพทย์)
--   tooth_exam_rounds     รอบตรวจ (เช่น ปีการศึกษา 2569 ครั้งที่ 1) เปิด/ปิดโดย admin
--   health_tooth_external เพิ่ม round_id, exam_type (teacher / doctor / legacy), examined_by, examined_at
-- เด็ก 1 คน ใน 1 รอบ มีผลตรวจได้ตามประเภทผู้ตรวจ (ครู 1 ชุด + แพทย์ 1 ชุด) ไม่ทับกัน

CREATE TABLE IF NOT EXISTS tooth_exam_rounds (
    id            SERIAL PRIMARY KEY,
    academic_year VARCHAR(10) NOT NULL,
    round_no      INTEGER NOT NULL,
    title         VARCHAR(150) NOT NULL,
    start_date    DATE,
    end_date      DATE,
    status        VARCHAR(10) NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed')),
    created_by    VARCHAR(100),
    created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
    closed_at     TIMESTAMP,
    UNIQUE (academic_year, round_no)
);

ALTER TABLE health_tooth_external
    ADD COLUMN IF NOT EXISTS round_id    INTEGER REFERENCES tooth_exam_rounds (id),
    ADD COLUMN IF NOT EXISTS exam_type   VARCHAR(10) NOT NULL DEFAULT 'legacy',
    ADD COLUMN IF NOT EXISTS examined_by VARCHAR(100),
    ADD COLUMN IF NOT EXISTS examined_at DATE;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'health_tooth_exam_type_check') THEN
        ALTER TABLE health_tooth_external
            ADD CONSTRAINT health_tooth_exam_type_check CHECK (exam_type IN ('teacher', 'doctor', 'legacy'));
    END IF;
END $$;

-- ข้อมูลเดิม: ทุกปีการศึกษาที่มีผลตรวจ -> รอบที่ 1
-- ปีล่าสุดเปิดรอบไว้ ปีก่อนหน้าปิดรอบ (ล็อกไม่ให้แก้โดยไม่ตั้งใจ)
INSERT INTO tooth_exam_rounds (academic_year, round_no, title, status)
SELECT y.academic_year, 1, 'ครั้งที่ 1',
       CASE WHEN y.academic_year = (SELECT MAX(academic_year::text) FROM health_tooth_external) THEN 'open' ELSE 'closed' END
FROM (SELECT DISTINCT academic_year::text AS academic_year
      FROM health_tooth_external WHERE academic_year IS NOT NULL) y
ON CONFLICT (academic_year, round_no) DO NOTHING;

UPDATE health_tooth_external h
SET round_id = r.id
FROM tooth_exam_rounds r
WHERE h.round_id IS NULL AND r.round_no = 1 AND r.academic_year = h.academic_year::text;

-- ตามที่หน้ารายชื่อเดิมตีความอยู่แล้ว: มีชื่อแพทย์ = แพทย์ตรวจแล้ว  ไม่มีชื่อแพทย์ = รอแพทย์ตรวจ (ครูคัดกรอง)
UPDATE health_tooth_external
SET exam_type = CASE WHEN COALESCE(BTRIM(doctor_name), '') <> '' THEN 'doctor' ELSE 'teacher' END,
    examined_by = NULLIF(BTRIM(doctor_name), ''),
    examined_at = updated_at::date
WHERE exam_type = 'legacy';

CREATE INDEX IF NOT EXISTS idx_health_tooth_round_student
    ON health_tooth_external (round_id, student_id, exam_type);
