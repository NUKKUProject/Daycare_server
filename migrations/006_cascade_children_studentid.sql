-- อนุญาตให้แก้ไขเลขประจำตัวนักเรียน (children.studentid) แล้วให้ตารางอื่นที่อ้างอิงอยู่
-- (attendance, drug_allergies, food_allergies, growth_records, nutrition_records, vaccines,
--  users, ฯลฯ) อัปเดตตามอัตโนมัติ แทนที่จะถูกฐานข้อมูลปฏิเสธ
--
-- สคริปต์นี้ค้นหา FOREIGN KEY ทุกตัวที่อ้างอิง children(studentid) แบบไดนามิก
-- (ไม่ยึดรายชื่อตายตัว เผื่อมีตารางที่ schema dump เดิมไม่ได้บันทึกไว้ เช่น health_data,
--  health_data_external, health_tooth_external) แล้วเพิ่ม ON UPDATE CASCADE ให้ทุกตัว
-- โดยคงพฤติกรรม ON DELETE เดิมไว้ (ถ้ามี) รันซ้ำได้ปลอดภัย (ข้าม constraint ที่มีอยู่แล้ว)

DO $$
DECLARE
    r RECORD;
    newdef TEXT;
BEGIN
    FOR r IN
        SELECT con.oid, con.conname, con.conrelid::regclass AS tbl, pg_get_constraintdef(con.oid) AS def
        FROM pg_constraint con
        JOIN pg_class refcl ON refcl.oid = con.confrelid
        WHERE con.contype = 'f'
          AND refcl.relname = 'children'
          AND EXISTS (
              SELECT 1
              FROM unnest(con.confkey) AS attnum
              JOIN pg_attribute a ON a.attrelid = con.confrelid AND a.attnum = attnum
              WHERE a.attname = 'studentid'
          )
          AND pg_get_constraintdef(con.oid) NOT ILIKE '%ON UPDATE CASCADE%'
    LOOP
        EXECUTE format('ALTER TABLE %s DROP CONSTRAINT %I', r.tbl, r.conname);

        newdef := r.def;
        IF newdef ILIKE '%ON DELETE%' THEN
            newdef := regexp_replace(newdef, '(ON DELETE)', 'ON UPDATE CASCADE \1', 'i');
        ELSE
            newdef := newdef || ' ON UPDATE CASCADE';
        END IF;

        EXECUTE format('ALTER TABLE %s ADD CONSTRAINT %I %s', r.tbl, r.conname, newdef);

        RAISE NOTICE 'Added ON UPDATE CASCADE to % on table %', r.conname, r.tbl;
    END LOOP;
END $$;
