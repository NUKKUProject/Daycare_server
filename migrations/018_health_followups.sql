-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ตารางกลางสำหรับ "ติดตามสุขภาพ" การตอบกลับของผู้ปกครอง/ศูนย์ ต่อผลตรวจ 1 รายการ
-- รองรับการตรวจสุขภาพหลายชนิด: source_type ระบุชนิด (ตอนนี้มี 'dental' = ตรวจฟัน) source_id คือ id ของผลตรวจในตารางของชนิดนั้น
-- ย้ายข้อมูลการตอบกลับเรื่องฟันที่มีอยู่ (คอลัมน์ followup_* ในตาราง health_tooth_external) เข้ามาที่นี่
-- คอลัมน์เดิมไม่ถูกลบ (เก็บไว้เผื่อย้อนกลับ แต่ระบบใหม่ไม่ใช้แล้ว)

CREATE TABLE IF NOT EXISTS health_followups (
    id             SERIAL PRIMARY KEY,
    source_type    VARCHAR(30)  NOT NULL,
    source_id      INTEGER      NOT NULL,
    student_id     VARCHAR(50)  NOT NULL,
    status         VARCHAR(20)  NOT NULL CHECK (status IN ('acknowledged', 'scheduled', 'treated')),
    followup_date  DATE,
    note           TEXT,
    by_user        VARCHAR(100),
    by_role        VARCHAR(20)  NOT NULL DEFAULT 'parent',   -- parent = ผู้ปกครอง, center = ศูนย์ (admin / ครู / แพทย์)
    ack_at         TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT NOW(),
    UNIQUE (source_type, source_id)
);

CREATE INDEX IF NOT EXISTS idx_health_followups_student ON health_followups (student_id);

INSERT INTO health_followups (source_type, source_id, student_id, status, followup_date, note, by_user, by_role, ack_at, updated_at)
SELECT 'dental', h.id, h.student_id, h.followup_status, h.followup_date, h.followup_note, h.followup_by,
       COALESCE(to_jsonb(h)->>'followup_by_role', 'parent'), h.parent_ack_at, COALESCE(h.followup_updated_at, NOW())
FROM health_tooth_external h
WHERE h.followup_status IS NOT NULL
ON CONFLICT (source_type, source_id) DO NOTHING;
