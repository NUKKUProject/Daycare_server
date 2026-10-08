-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ศูนย์ (admin / ครู) แจ้งกลับแทนผู้ปกครองได้ (เช่น ศูนย์พาไปรักษาเอง) จึงต้องรู้ว่าใครเป็นผู้บันทึก
--   parent = ผู้ปกครอง, center = ศูนย์ (admin / ครู / แพทย์)

ALTER TABLE health_tooth_external
    ADD COLUMN IF NOT EXISTS followup_by_role VARCHAR(20);

UPDATE health_tooth_external SET followup_by_role = 'parent'
WHERE followup_status IS NOT NULL AND followup_by_role IS NULL;
