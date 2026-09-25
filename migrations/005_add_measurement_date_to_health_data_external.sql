-- วันที่ชั่งน้ำหนักและวัดส่วนสูงของการตรวจสุขภาพภายนอก
ALTER TABLE health_data_external
    ADD COLUMN IF NOT EXISTS measurement_date DATE;

-- รายการเดิมไม่มีวันที่แยก จึงใช้วันที่ตรวจเป็นค่าเริ่มต้น
UPDATE health_data_external
SET measurement_date = exam_date
WHERE measurement_date IS NULL;

CREATE INDEX IF NOT EXISTS idx_health_data_external_measurement_date
    ON health_data_external(measurement_date);
