-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ผลการคัดกรองพัฒนาการเด็ก 5 ด้าน (GM / FM / RL / EL / PS) ที่ครู/แอดมินกรอกแบบหลายคนในหน้า growth_history
-- 1 แถว = เด็ก 1 คน ต่อ 1 วันที่ประเมิน  ผู้ปกครองดูได้ในแท็บการเจริญเติบโตของ view_child (อ่านอย่างเดียว)
-- จำนวนด้านที่ผ่าน/ไม่ผ่าน ผลคัดกรอง และวันประเมินซ้ำ ระบบคำนวณจากการติ๊กตอนบันทึก

CREATE TABLE IF NOT EXISTS development_screenings (
    id              SERIAL PRIMARY KEY,
    student_id      VARCHAR(50) NOT NULL REFERENCES children(studentid) ON UPDATE CASCADE ON DELETE CASCADE,
    assess_date     DATE NOT NULL,
    birthday        DATE,
    age_year        INTEGER,
    age_month       INTEGER,
    age_day         INTEGER,
    age_months      INTEGER,                 -- อายุ ณ วันประเมิน (เดือนเต็ม)
    age_range       VARCHAR(100),            -- ช่วงอายุเกณฑ์ที่ใช้
    gm_status       VARCHAR(4) NOT NULL CHECK (gm_status IN ('pass', 'fail')),
    gm_note         TEXT,
    fm_status       VARCHAR(4) NOT NULL CHECK (fm_status IN ('pass', 'fail')),
    fm_note         TEXT,
    rl_status       VARCHAR(4) NOT NULL CHECK (rl_status IN ('pass', 'fail')),
    rl_note         TEXT,
    el_status       VARCHAR(4) NOT NULL CHECK (el_status IN ('pass', 'fail')),
    el_note         TEXT,
    ps_status       VARCHAR(4) NOT NULL CHECK (ps_status IN ('pass', 'fail')),
    ps_note         TEXT,
    pass_count      INTEGER NOT NULL,
    fail_count      INTEGER NOT NULL,
    result          VARCHAR(10) NOT NULL CHECK (result IN ('normal', 'delay')),
    recheck_date    DATE,                    -- ถ้ามีด้านไม่ผ่าน = assess_date + 30 วัน
    recorded_by     VARCHAR(100),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (student_id, assess_date)
);

CREATE INDEX IF NOT EXISTS idx_development_screenings_date ON development_screenings (assess_date);
