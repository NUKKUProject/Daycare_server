-- วิธีรัน: เลือกทั้งไฟล์แล้วสั่ง Execute Script (DBeaver: Alt+X)
--
-- ซิงค์ธง has_drug_allergy_history / has_food_allergy_history กับรายการแพ้จริง
-- (drug_allergies / food_allergies) ธงเดิมถูกล้างเป็น false ทุกครั้งที่บันทึกแท็บประวัติประจำตัว

UPDATE children c
SET has_drug_allergy_history = EXISTS (SELECT 1 FROM drug_allergies d WHERE d.student_id = c.studentid),
    has_food_allergy_history = EXISTS (SELECT 1 FROM food_allergies f WHERE f.student_id = c.studentid);
