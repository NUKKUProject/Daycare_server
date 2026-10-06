<?php
// ตรวจสอบและแปลงข้อมูลสุขภาพ (อาการ / การดูแล / ผู้ดูแล) ที่รับมาจากหน้าเว็บ
// ใช้ร่วมกันระหว่างการบันทึกตอนสแกนเข้าเรียนและการเพิ่ม/แก้ไขประวัติ
// รหัสอาการ/การดูแลที่รับได้ มาจากตั้งค่าการเช็คชื่อ (checkin_settings.php)

require_once(__DIR__ . '/checkin_settings.php');

/**
 * @param array $data ข้อมูล JSON ที่ส่งมาจากหน้าเว็บ
 * @param bool  $clear true = ล้างข้อมูลสุขภาพทั้งหมด (เช่นสถานะเป็นลา/ขาด)
 * @return array ค่าที่พร้อมนำไปบันทึกลงตาราง attendance
 */
function parse_health_payload(array $data, bool $clear = false): array
{
    $symptoms = [];
    $care_actions = [];
    $temperature = null;
    $other_symptoms = null;
    $care_other = null;
    $caretaker_name = null;

    if (!$clear) {
        $allowed = checkin_allowed_map();

        if (isset($data['temperature']) && $data['temperature'] !== '' && is_numeric($data['temperature'])) {
            $temperature = floatval($data['temperature']);
        }

        if (isset($data['symptoms']) && is_array($data['symptoms'])) {
            foreach ($data['symptoms'] as $code => $subs) {
                if (!isset($allowed['symptoms'][$code])) {
                    continue;
                }
                $subs = is_array($subs) ? $subs : [];
                $symptoms[$code] = array_values(array_intersect($allowed['symptoms'][$code], $subs));
            }
        }

        if (isset($data['care_actions']) && is_array($data['care_actions'])) {
            $care_actions = array_values(array_intersect($allowed['care'], $data['care_actions']));
        }

        $other_symptoms = !empty($data['other_symptoms']) ? trim($data['other_symptoms']) : null;

        // ช่องข้อความการดูแล เก็บเมื่อมีตัวเลือกที่เปิดให้พิมพ์ข้อความ (เช่น "อื่นๆ") ถูกเลือก
        $needsText = count(array_intersect($allowed['care_text'], $care_actions)) > 0;
        $care_other = $needsText && !empty($data['care_other'])
            ? mb_substr(trim($data['care_other']), 0, 200) : null;
        $caretaker_name = !empty($data['caretaker_name'])
            ? mb_substr(trim($data['caretaker_name']), 0, 150) : null;
    }

    return [
        'temperature' => $temperature,
        'other_symptoms' => $other_symptoms,
        'symptoms_json' => json_encode((object)$symptoms, JSON_UNESCAPED_UNICODE),
        'care_actions_json' => json_encode($care_actions, JSON_UNESCAPED_UNICODE),
        'care_other' => $care_other,
        'caretaker_name' => $caretaker_name,
        // คอลัมน์ boolean เดิม เติมจากอาการที่มีรหัสเดิม เพื่อให้หน้าเดิมที่อ่านค่านี้ใช้งานได้
        'has_runny_nose' => isset($symptoms['runny_nose']) ? 't' : 'f',
        'has_cough' => isset($symptoms['cough']) ? 't' : 'f',
        'has_rash' => isset($symptoms['rash']) ? 't' : 'f',
        'has_red_eyes' => 'f',
    ];
}
