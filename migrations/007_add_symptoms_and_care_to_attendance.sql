-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X) ไม่ใช่ Execute Statement (Ctrl+Enter)
-- เพราะ DBeaver แยกคำสั่งตามบรรทัดว่าง อาจรันเฉพาะ UPDATE ท้ายไฟล์ โดยยังไม่ได้เพิ่มคอลัมน์
--
-- อาการที่พบ / การดูแล-ช่วยเหลือ / ผู้ดูแล ตอนรับเด็กเข้าเรียน
-- คอลัมน์ boolean เดิม (has_runny_nose, has_cough, has_rash, has_red_eyes) ยังคงไว้
-- เพื่อให้หน้าประวัติเดิมใช้งานได้ต่อ

ALTER TABLE attendance
    ADD COLUMN IF NOT EXISTS symptoms JSONB NOT NULL DEFAULT '{}'::jsonb,
    ADD COLUMN IF NOT EXISTS care_actions JSONB NOT NULL DEFAULT '[]'::jsonb,
    ADD COLUMN IF NOT EXISTS care_other TEXT,
    ADD COLUMN IF NOT EXISTS caretaker_name VARCHAR(150);

COMMENT ON COLUMN attendance.symptoms IS
    'อาการที่พบ เก็บเป็น {"รหัสอาการ": [ตัวเลือกย่อย]} เช่น {"runny_nose":["clear","yellow"],"cough":["dry"],"rash":[]}';
COMMENT ON COLUMN attendance.care_actions IS
    'การดูแล/ช่วยเหลือ เก็บเป็น array ของรหัส เช่น ["wash_hands","give_medicine","pcn123"]';
COMMENT ON COLUMN attendance.care_other IS 'การดูแล/ช่วยเหลืออื่นๆ (ข้อความอิสระ)';
COMMENT ON COLUMN attendance.caretaker_name IS 'ชื่อผู้ดูแลที่ให้การดูแล/ช่วยเหลือ';

-- ข้อมูลเดิม: แปลง boolean เก่าให้อยู่ในรูป symptoms ใหม่ (ไม่มีตัวเลือกย่อย)
UPDATE attendance
SET symptoms = jsonb_strip_nulls(jsonb_build_object(
        'runny_nose', CASE WHEN has_runny_nose IS TRUE THEN '[]'::jsonb END,
        'cough',      CASE WHEN has_cough      IS TRUE THEN '[]'::jsonb END,
        'rash',       CASE WHEN has_rash       IS TRUE THEN '[]'::jsonb END,
        'red_eyes',   CASE WHEN has_red_eyes   IS TRUE THEN '[]'::jsonb END
    ))
WHERE symptoms = '{}'::jsonb
  AND (has_runny_nose IS TRUE OR has_cough IS TRUE OR has_rash IS TRUE OR has_red_eyes IS TRUE);
