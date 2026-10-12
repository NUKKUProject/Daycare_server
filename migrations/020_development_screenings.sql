-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- คัดกรองพัฒนาการ 5 ด้าน (กรอกหลายคนในหน้า growth_history) เก็บใน growth_records เดิม
-- ใช้คอลัมน์เดิม gm/fm/rl/el/ps_status ('pass' = ผ่าน, 'delay' = ไม่ผ่าน) และ created_at เป็นวันที่ประเมิน
-- ที่เพิ่มคือรายละเอียดรายด้าน อายุเป็นเดือน ช่วงอายุเกณฑ์ จำนวนผ่าน/ไม่ผ่าน ผลคัดกรอง และวันประเมินซ้ำ
-- (ระบบคำนวณจำนวน ผลคัดกรอง และวันประเมินซ้ำจากการติ๊กตอนบันทึก)

ALTER TABLE growth_records
    ADD COLUMN IF NOT EXISTS gm_note        TEXT,
    ADD COLUMN IF NOT EXISTS fm_note        TEXT,
    ADD COLUMN IF NOT EXISTS rl_note        TEXT,
    ADD COLUMN IF NOT EXISTS el_note        TEXT,
    ADD COLUMN IF NOT EXISTS ps_note        TEXT,
    ADD COLUMN IF NOT EXISTS age_months     INTEGER,        -- อายุ ณ วันประเมิน (เดือนเต็ม)
    ADD COLUMN IF NOT EXISTS dev_age_range  VARCHAR(100),   -- ช่วงอายุเกณฑ์พัฒนาการที่ใช้ (แยกจาก age_range ของกราฟน้ำหนัก/ส่วนสูง)
    ADD COLUMN IF NOT EXISTS dev_pass_count INTEGER,
    ADD COLUMN IF NOT EXISTS dev_fail_count INTEGER,
    ADD COLUMN IF NOT EXISTS dev_result     VARCHAR(10) CHECK (dev_result IN ('normal', 'delay')),
    ADD COLUMN IF NOT EXISTS recheck_date   DATE,           -- ถ้ามีด้านไม่ผ่าน = วันประเมิน + 30 วัน
    ADD COLUMN IF NOT EXISTS dev_recorded_by VARCHAR(100);

-- (ถ้าเคยรันไฟล์ 020 เวอร์ชันก่อนหน้าที่สร้างตาราง development_screenings ไว้ ตารางนั้นไม่ได้ใช้แล้ว ลบได้)
-- DROP TABLE IF EXISTS development_screenings;
