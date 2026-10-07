-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ผู้มาส่งเด็กตอนสแกนเข้า (father / mother / relative / other)
-- dropped_off_detail ใช้เก็บรายละเอียดเมื่อเลือก other

ALTER TABLE attendance
    ADD COLUMN IF NOT EXISTS dropped_off_by VARCHAR(20),
    ADD COLUMN IF NOT EXISTS dropped_off_detail TEXT;

COMMENT ON COLUMN attendance.dropped_off_by IS 'ผู้มาส่งเด็ก: father, mother, relative, other';
COMMENT ON COLUMN attendance.dropped_off_detail IS 'รายละเอียดผู้มาส่ง (กรณี other)';
