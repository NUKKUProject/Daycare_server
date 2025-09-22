-- สร้างตาราง sso_roles สำหรับเก็บข้อมูลการกำหนดสิทธิ์ผู้ใช้ SSO
CREATE TABLE IF NOT EXISTS sso_roles (
  id SERIAL PRIMARY KEY,
  citizen_id VARCHAR(13) NOT NULL,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  position VARCHAR(100) NOT NULL,
  role VARCHAR(10) NOT NULL CHECK (role IN ('admin', 'teacher')),
  assigned_by VARCHAR(13) NOT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(10) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
  CONSTRAINT unique_citizen_id UNIQUE (citizen_id)
);

-- เพิ่ม comment ให้กับตารางและคอลัมน์
COMMENT ON TABLE sso_roles IS 'ตารางเก็บข้อมูลการกำหนดสิทธิ์ผู้ใช้ SSO';
COMMENT ON COLUMN sso_roles.citizen_id IS 'รหัสประจำตัวประชาชน';
COMMENT ON COLUMN sso_roles.firstname IS 'ชื่อ';
COMMENT ON COLUMN sso_roles.lastname IS 'นามสกุล';
COMMENT ON COLUMN sso_roles.position IS 'ตำแหน่ง';
COMMENT ON COLUMN sso_roles.role IS 'สิทธิ์การใช้งาน';
COMMENT ON COLUMN sso_roles.assigned_by IS 'รหัสประจำตัวประชาชนของผู้ที่กำหนดสิทธิ์';
COMMENT ON COLUMN sso_roles.assigned_at IS 'วันที่กำหนดสิทธิ์';
COMMENT ON COLUMN sso_roles.status IS 'สถานะการใช้งาน';

-- เพิ่ม foreign key constraint
ALTER TABLE sso_roles
ADD CONSTRAINT fk_sso_roles_assigned_by 
FOREIGN KEY (assigned_by) 
REFERENCES sso_roles (citizen_id) 
ON DELETE RESTRICT ON UPDATE CASCADE; 