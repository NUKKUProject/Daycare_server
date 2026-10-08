-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ผลตรวจฟันที่แพทย์กรอกจำนวนฟันผุ / ตำแหน่ง / วิธีรักษา แต่ไม่ได้เลือกสภาพฟัน -> ตั้งเป็น "มีฟันผุ" (abnormal)
-- (ระบบใหม่เลือกให้เองตอนบันทึก  สคริปต์นี้แก้ข้อมูลเดิมที่บันทึกไว้ก่อนหน้า)
--
-- ดูตัวอย่างแถวที่จะถูกแก้ก่อนรันได้ด้วย: เปลี่ยน UPDATE ด้านล่างเป็น SELECT id, student_id ... ด้วยเงื่อนไข WHERE เดียวกัน

UPDATE health_tooth_external
SET teeth_status = 'abnormal'
WHERE (teeth_status IS NULL OR teeth_status = '')
  AND (
        COALESCE(decayed_teeth, 0) > 0
        OR COALESCE(NULLIF(treatments::text, 'null'), '[]') <> '[]'
        OR COALESCE((SELECT SUM(v::int) FROM jsonb_each_text(decayed_teeth_positions::jsonb) AS t(k, v) WHERE v ~ '^[0-9]+$'), 0) > 0
      );
