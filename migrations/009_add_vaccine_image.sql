-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- รูปประกอบการฉีดวัคซีน: เก็บพาธไฟล์ใน public/uploads/vaccines/
-- (ถ้าฐานข้อมูลมีคอลัมน์นี้อยู่แล้ว คำสั่งนี้จะไม่ทำอะไร)
ALTER TABLE vaccines
    ADD COLUMN IF NOT EXISTS image_path VARCHAR(255);

COMMENT ON COLUMN vaccines.image_path IS 'พาธรูปประกอบการฉีดวัคซีน เช่น ../../../public/uploads/vaccines/vaccine_xxx.jpg';
