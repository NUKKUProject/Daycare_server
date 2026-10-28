-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- การลาของเด็ก: ผู้ปกครองแจ้งลาจากเมนู "การลา" แล้วครู/แอดมินอนุมัติหรือไม่อนุมัติ
-- leave_type: personal = ลากิจ, sick = ลาป่วย  (ลาป่วยระบุโรคจากรายการที่พบบ่อย หรือ "อื่นๆ" แล้วพิมพ์ชื่อโรคเอง)
-- status: pending = รออนุมัติ, approved = อนุมัติแล้ว, rejected = ไม่อนุมัติ, cancelled = ผู้ปกครองยกเลิก
-- เมื่ออนุมัติ ระบบเปลี่ยนการเช็กชื่อของวันที่ลาเป็นสถานะ "ลา" (attendance.status = 'leave')

CREATE TABLE IF NOT EXISTS leave_requests (
    id            SERIAL PRIMARY KEY,
    student_id    VARCHAR(50) NOT NULL REFERENCES children(studentid) ON UPDATE CASCADE ON DELETE CASCADE,
    leave_type    VARCHAR(10) NOT NULL CHECK (leave_type IN ('personal', 'sick')),
    illness       VARCHAR(30),                 -- รหัสโรคที่เลือก (ลาป่วย) หรือ 'other'
    illness_name  VARCHAR(150),                -- ชื่อโรคที่แสดง (จากรายการ หรือที่พิมพ์เองเมื่อเลือกอื่นๆ)
    start_date    DATE NOT NULL,
    end_date      DATE NOT NULL,
    reason        TEXT,
    status        VARCHAR(12) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),
    created_by    VARCHAR(100),
    created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
    cancelled_at  TIMESTAMP,
    decided_by    VARCHAR(100),                -- ผู้อนุมัติ/ไม่อนุมัติ
    decided_at    TIMESTAMP,
    decision_note TEXT,
    CHECK (end_date >= start_date),
    CHECK (leave_type <> 'sick' OR illness_name IS NOT NULL)
);

-- ถ้าเคยรันไฟล์ 021 เวอร์ชันก่อนหน้า (สถานะ submitted/cancelled) ให้ปรับตารางเดิมให้ตรงกับเวอร์ชันนี้
ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS decided_by VARCHAR(100);
ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS decided_at TIMESTAMP;
ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS decision_note TEXT;
ALTER TABLE leave_requests DROP CONSTRAINT IF EXISTS leave_requests_status_check;
UPDATE leave_requests SET status = 'pending' WHERE status = 'submitted';
ALTER TABLE leave_requests ALTER COLUMN status SET DEFAULT 'pending';
ALTER TABLE leave_requests ADD CONSTRAINT leave_requests_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled'));

CREATE INDEX IF NOT EXISTS idx_leave_requests_student ON leave_requests (student_id, start_date DESC);
CREATE INDEX IF NOT EXISTS idx_leave_requests_dates ON leave_requests (start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_leave_requests_status ON leave_requests (status);
