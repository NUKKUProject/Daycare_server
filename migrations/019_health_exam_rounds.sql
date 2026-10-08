-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- รอบตรวจสุขภาพ (ร่างกาย / พัฒนาการ) ที่ admin เปิดก่อนครูกรอก
-- 1 รอบ = (academic_year, round_no)  ผลตรวจใน health_data_external ผูกกับรอบด้วย academic_year + check_round เดิม

CREATE TABLE IF NOT EXISTS health_exam_rounds (
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

-- ข้อมูลเดิม: สร้างรอบจากคู่ (ปีการศึกษา, ครั้งที่) ที่มีผลตรวจอยู่แล้ว  ปีล่าสุดเปิดไว้ ปีก่อนหน้าปิด
INSERT INTO health_exam_rounds (academic_year, round_no, title, status)
SELECT y.academic_year, y.check_round, 'ครั้งที่ ' || y.check_round,
       CASE WHEN y.academic_year = (SELECT MAX(academic_year::text) FROM health_data_external) THEN 'open' ELSE 'closed' END
FROM (SELECT DISTINCT academic_year::text AS academic_year, COALESCE(check_round, 1) AS check_round
      FROM health_data_external WHERE academic_year IS NOT NULL) y
ON CONFLICT (academic_year, round_no) DO NOTHING;

CREATE INDEX IF NOT EXISTS idx_health_data_external_year_round
    ON health_data_external (academic_year, check_round, student_id);
