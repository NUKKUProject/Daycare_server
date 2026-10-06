-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ตั้งค่าการเช็คชื่อ: เวลามาสาย + ตัวเลือกอาการ/การดูแลที่ admin เพิ่ม ลบ แก้ไขได้
-- ถ้ายังไม่ได้รันไฟล์นี้ ระบบจะใช้ค่าเริ่มต้น (08:30 และรายการเดิม) ไปก่อน

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('checkin_late_time', '08:30')
ON CONFLICT (setting_key) DO NOTHING;

CREATE TABLE IF NOT EXISTS checkin_options (
    id          SERIAL PRIMARY KEY,
    option_type VARCHAR(20) NOT NULL CHECK (option_type IN ('symptom', 'care')),
    code        VARCHAR(50) NOT NULL,
    -- NULL = ตัวเลือกหลัก, มีค่า = ตัวเลือกย่อยของอาการที่ code ตรงกัน
    parent_code VARCHAR(50),
    label       VARCHAR(100) NOT NULL,
    icon        VARCHAR(16),
    -- ใช้กับการดูแล: ถ้า TRUE ตอนเลือกจะมีช่องให้พิมพ์ข้อความเพิ่ม (เช่น "อื่นๆ")
    allows_text BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    -- is_active = FALSE: ซ่อนจากหน้าเช็คชื่อ แต่ข้อมูลเก่ายังแสดงชื่อได้
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    -- is_deleted = TRUE: ลบจากหน้าตั้งค่า (เก็บแถวไว้เพื่อให้ประวัติเก่าอ่านชื่อได้)
    is_deleted  BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS ux_checkin_options_code
    ON checkin_options (option_type, COALESCE(parent_code, ''), code);

CREATE INDEX IF NOT EXISTS idx_checkin_options_order
    ON checkin_options (option_type, sort_order);

-- ข้อมูลเริ่มต้น: อาการที่พบ
INSERT INTO checkin_options (option_type, code, parent_code, label, icon, sort_order) VALUES
    ('symptom', 'runny_nose',     NULL, 'น้ำมูก',     '🤧', 10),
    ('symptom', 'cough',          NULL, 'ไอ',         '😷', 20),
    ('symptom', 'heat_in',        NULL, 'ร้อนใน',     '🔥', 30),
    ('symptom', 'gum_swelling',   NULL, 'เหงือกบวม',  '🦷', 40),
    ('symptom', 'red_throat',     NULL, 'คอแดง',      '🗣️', 50),
    ('symptom', 'mouth_blisters', NULL, 'ตุ่มที่ปาก', '👄', 60),
    ('symptom', 'mosquito_bites', NULL, 'ตุ่มยุงกัด', '🦟', 70),
    ('symptom', 'hfmd',           NULL, 'มือเท้าปาก', '🖐️', 80),
    ('symptom', 'wound',          NULL, 'แผล',        '🩹', 90),
    ('symptom', 'rash',           NULL, 'ผื่น',       '🔴', 100),
    ('symptom', 'eye_discharge',  NULL, 'ขี้ตา',      '👁️', 110)
ON CONFLICT DO NOTHING;

-- ตัวเลือกย่อยของอาการ
INSERT INTO checkin_options (option_type, code, parent_code, label, sort_order) VALUES
    ('symptom', 'clear',  'runny_nose',    'ใส',    10),
    ('symptom', 'yellow', 'runny_nose',    'เหลือง', 20),
    ('symptom', 'green',  'runny_nose',    'เขียว',  30),
    ('symptom', 'dry',    'cough',         'แห้ง',   10),
    ('symptom', 'phlegm', 'cough',         'เสมหะ',  20),
    ('symptom', 'yellow', 'eye_discharge', 'เหลือง', 10),
    ('symptom', 'green',  'eye_discharge', 'เขียว',  20)
ON CONFLICT DO NOTHING;

-- ข้อมูลเริ่มต้น: การดูแล/ช่วยเหลือ
INSERT INTO checkin_options (option_type, code, parent_code, label, icon, allows_text, sort_order) VALUES
    ('care', 'wash_hands',     NULL, 'ล้างมือบ่อยๆ', '🧼', FALSE, 10),
    ('care', 'give_medicine',  NULL, 'ป้อนยา',        '💊', FALSE, 20),
    ('care', 'apply_medicine', NULL, 'ทายา',          '🧴', FALSE, 30),
    ('care', 'pcn123',         NULL, 'PCN123',        '📋', FALSE, 40),
    ('care', 'other',          NULL, 'อื่นๆ',         '✏️', TRUE,  50)
ON CONFLICT DO NOTHING;
