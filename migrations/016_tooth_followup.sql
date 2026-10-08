-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ติดตามผลตรวจฟันกับผู้ปกครอง: เมื่อแพทย์พบฟันผุ ผู้ปกครองกดรับทราบ / นัดหมอแล้ว / พาไปรักษาแล้ว
-- จากแดชบอร์ด ข้อมูลเก็บไว้กับผลตรวจนั้น ฝั่งศูนย์ดูได้ในหน้ากรอกทั้งห้อง

ALTER TABLE health_tooth_external
    ADD COLUMN IF NOT EXISTS parent_ack_at        TIMESTAMP,
    ADD COLUMN IF NOT EXISTS followup_status      VARCHAR(20),
    ADD COLUMN IF NOT EXISTS followup_date        DATE,
    ADD COLUMN IF NOT EXISTS followup_note        TEXT,
    ADD COLUMN IF NOT EXISTS followup_updated_at  TIMESTAMP,
    ADD COLUMN IF NOT EXISTS followup_by          VARCHAR(100);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'health_tooth_followup_status_check') THEN
        ALTER TABLE health_tooth_external
            ADD CONSTRAINT health_tooth_followup_status_check
            CHECK (followup_status IS NULL OR followup_status IN ('acknowledged', 'scheduled', 'treated'));
    END IF;
END $$;

COMMENT ON COLUMN health_tooth_external.followup_status IS 'acknowledged = ผู้ปกครองรับทราบ, scheduled = นัดหมอแล้ว, treated = พาไปรักษาแล้ว';
