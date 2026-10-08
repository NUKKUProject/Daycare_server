-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ผลตรวจฟันกรอกไม่ครบก็บันทึกได้ จึงต้องอนุญาตให้ช่องเหล่านี้ว่าง (NULL)
-- (ถ้าคอลัมน์ไหนเดิมอนุญาตอยู่แล้ว คำสั่งนี้ไม่มีผลเสีย)

ALTER TABLE health_tooth_external
    ALTER COLUMN total_teeth DROP NOT NULL,
    ALTER COLUMN decayed_teeth DROP NOT NULL,
    ALTER COLUMN teeth_status DROP NOT NULL,
    ALTER COLUMN urgency DROP NOT NULL;
