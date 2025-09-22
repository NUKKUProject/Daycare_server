-- เพิ่มปีการศึกษา 2568 ถ้ายังไม่มี
INSERT INTO academic_years (name, is_active) 
SELECT '2568', TRUE
WHERE NOT EXISTS (SELECT 1 FROM academic_years WHERE name = '2568');
 
-- อัพเดทปีการศึกษาของเด็กทุกคนให้เป็นปีการศึกษา 2568
UPDATE children 
SET academic_year = 2568; 