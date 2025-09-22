-- สร้างตาราง academic_years
CREATE TABLE IF NOT EXISTS academic_years (
  id SERIAL PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- เพิ่ม comment ให้กับคอลัมน์
COMMENT ON COLUMN academic_years.name IS 'ชื่อปีการศึกษา (พ.ศ.)';
COMMENT ON COLUMN academic_years.is_active IS 'สถานะการใช้งาน (true=เปิดใช้งาน, false=ปิดใช้งาน)';

-- สร้าง unique constraint
ALTER TABLE academic_years ADD CONSTRAINT academic_years_name_key UNIQUE (name);

-- เพิ่มข้อมูลปีการศึกษาเริ่มต้น
INSERT INTO academic_years (name, is_active) VALUES
('2566', FALSE),
('2567', TRUE);

-- อัพเดทข้อมูลเด็กที่มีอยู่ในปีการศึกษา 2566
UPDATE children SET academic_year_id = 1 WHERE academic_year_id IS NULL; 