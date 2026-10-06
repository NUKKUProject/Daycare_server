-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- สมุดสื่อสารประจำวัน (ผู้ปกครอง <-> ครู)
--   daily_menus   เมนูอาหารรายวัน กรอกครั้งเดียวต่อห้องต่อวัน
--   daily_reports สมุดรายวัน 1 เด็ก 1 วัน แยกฝั่งผู้ปกครอง (ที่บ้าน) และฝั่งครู (ที่ศูนย์)

CREATE TABLE IF NOT EXISTS daily_menus (
    id          SERIAL PRIMARY KEY,
    menu_date   DATE NOT NULL,
    classroom   VARCHAR(50) NOT NULL,
    -- morning_snack = อาหารว่างเช้า, lunch = อาหารกลางวัน, afternoon_snack = อาหารว่างบ่าย
    meal_slot   VARCHAR(20) NOT NULL CHECK (meal_slot IN ('morning_snack', 'lunch', 'afternoon_snack')),
    menu_text   VARCHAR(255) NOT NULL,
    created_by  VARCHAR(50),
    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (menu_date, classroom, meal_slot)
);

CREATE INDEX IF NOT EXISTS idx_daily_menus_date ON daily_menus (menu_date);

CREATE TABLE IF NOT EXISTS daily_reports (
    id          SERIAL PRIMARY KEY,
    student_id  VARCHAR(50) NOT NULL
                REFERENCES children (studentid) ON UPDATE CASCADE ON DELETE CASCADE,
    report_date DATE NOT NULL,

    -- ===== ฝั่งผู้ปกครอง (ที่บ้าน) =====
    -- อารมณ์: happy, scared, cry, angry, normal
    parent_mood             VARCHAR(10),
    parent_message          TEXT,
    drop_off_time           TIME,
    home_morning_milk_ml    INTEGER,
    home_morning_food       TEXT,
    home_evening_milk_ml    INTEGER,
    home_evening_food       TEXT,
    home_sleep_hours        NUMERIC(4, 1),
    home_bedtime            TIME,
    home_wake_time          TIME,
    home_stopped_diaper     BOOLEAN NOT NULL DEFAULT FALSE,
    home_stopped_bottle     BOOLEAN NOT NULL DEFAULT FALSE,
    parent_updated_by       VARCHAR(50),
    parent_updated_at       TIMESTAMP,

    -- ===== ฝั่งครู (ที่ศูนย์) =====
    teacher_mood                  VARCHAR(10),
    teacher_message               TEXT,
    center_morning_milk_ml        INTEGER,
    center_morning_snack_amount   TEXT,
    center_lunch_amount           TEXT,
    center_afternoon_milk_ml      INTEGER,
    center_afternoon_snack_amount TEXT,
    center_nap_hours              NUMERIC(4, 1),
    center_urine_count            INTEGER,
    center_stool_count            INTEGER,
    center_stopped_diaper         BOOLEAN NOT NULL DEFAULT FALSE,
    center_stopped_bottle         BOOLEAN NOT NULL DEFAULT FALSE,
    activities                    TEXT,
    teacher_updated_by            VARCHAR(50),
    teacher_updated_at            TIMESTAMP,

    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (student_id, report_date)
);

CREATE INDEX IF NOT EXISTS idx_daily_reports_date ON daily_reports (report_date);
