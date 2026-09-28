-- สร้างตาราง login_issues สำหรับเก็บการแจ้งปัญหาการเข้าสู่ระบบ
CREATE TABLE IF NOT EXISTS login_issues (
  id SERIAL PRIMARY KEY,
  student_id VARCHAR(50),
  student_name VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'resolved')),
  ip_address VARCHAR(45),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_login_issues_status ON login_issues (status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_login_issues_ip ON login_issues (ip_address, created_at);

COMMENT ON TABLE login_issues IS 'ตารางเก็บการแจ้งปัญหาการเข้าสู่ระบบ';
