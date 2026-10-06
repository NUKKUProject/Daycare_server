// รายการอาการ / การดูแล ที่ใช้ร่วมกันทุกหน้า (include ไว้ภายในแท็ก <script>)
// รหัส (code) ต้องตรงกับ include/function/health_data.php

// อาการที่พบ (subs = ตัวเลือกย่อยที่แสดงเมื่อติ๊กอาการหลัก)
const SYMPTOM_OPTIONS = [
    { code: 'runny_nose', label: 'น้ำมูก', icon: '🤧', subs: [
        { code: 'clear', label: 'ใส' }, { code: 'yellow', label: 'เหลือง' }, { code: 'green', label: 'เขียว' }
    ] },
    { code: 'cough', label: 'ไอ', icon: '😷', subs: [
        { code: 'dry', label: 'แห้ง' }, { code: 'phlegm', label: 'เสมหะ' }
    ] },
    { code: 'heat_in', label: 'ร้อนใน', icon: '🔥' },
    { code: 'gum_swelling', label: 'เหงือกบวม', icon: '🦷' },
    { code: 'red_throat', label: 'คอแดง', icon: '🗣️' },
    { code: 'mouth_blisters', label: 'ตุ่มที่ปาก', icon: '👄' },
    { code: 'mosquito_bites', label: 'ตุ่มยุงกัด', icon: '🦟' },
    { code: 'hfmd', label: 'มือเท้าปาก', icon: '🖐️' },
    { code: 'wound', label: 'แผล', icon: '🩹' },
    { code: 'rash', label: 'ผื่น', icon: '🔴' },
    { code: 'eye_discharge', label: 'ขี้ตา', icon: '👁️', subs: [
        { code: 'yellow', label: 'เหลือง' }, { code: 'green', label: 'เขียว' }
    ] }
];

// การดูแล/ช่วยเหลือ
const CARE_OPTIONS = [
    { code: 'wash_hands', label: 'ล้างมือบ่อยๆ', icon: '🧼' },
    { code: 'give_medicine', label: 'ป้อนยา', icon: '💊' },
    { code: 'apply_medicine', label: 'ทายา', icon: '🧴' },
    { code: 'pcn123', label: 'PCN123', icon: '📋' },
    { code: 'other', label: 'อื่นๆ', icon: '✏️' }
];
