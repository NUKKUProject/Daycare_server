<?php
// รายการอาการ / การดูแล และเวลามาสาย ที่ใช้ร่วมกันทุกหน้า (include ไว้ภายในแท็ก <script>)
// ข้อมูลมาจากหน้า "ตั้งค่าการเช็คชื่อ" ของ admin (include/function/checkin_settings.php)
require_once __DIR__ . '/../../include/function/checkin_settings.php';

$__flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;

$__shape = function (array $opts): array {
    $symptoms = array_map(function ($s) {
        return [
            'code' => $s['code'],
            'label' => $s['label'],
            'icon' => $s['icon'] ?? '',
            'archived' => !empty($s['is_deleted']) || empty($s['is_active']),
            'subs' => array_map(function ($x) {
                return [
                    'code' => $x['code'],
                    'label' => $x['label'],
                    'archived' => !empty($x['is_deleted']) || empty($x['is_active']),
                ];
            }, $s['subs'] ?? []),
        ];
    }, $opts['symptoms']);

    $care = array_map(function ($c) {
        return [
            'code' => $c['code'],
            'label' => $c['label'],
            'icon' => $c['icon'] ?? '',
            'allowsText' => !empty($c['allows_text']),
            'archived' => !empty($c['is_deleted']) || empty($c['is_active']),
        ];
    }, $opts['care']);

    return ['symptoms' => $symptoms, 'care' => $care];
};

$__active = $__shape(checkin_load_options(true, false));
$__all = $__shape(checkin_load_options(false, true));
?>
// เวลาตัดสายมาสาย (HH:MM)
const CHECKIN_LATE_TIME = <?php echo json_encode(checkin_get_late_time()); ?>;

// ตัวเลือกที่เปิดใช้งาน (แสดงตอนเช็คชื่อ) subs = ตัวเลือกย่อยที่แสดงเมื่อติ๊กอาการหลัก
const SYMPTOM_OPTIONS = <?php echo json_encode($__active['symptoms'], $__flags); ?>;
const CARE_OPTIONS = <?php echo json_encode($__active['care'], $__flags); ?>;

// ตัวเลือกทั้งหมดรวมที่ปิดใช้งาน/ลบแล้ว ใช้อ่านชื่อในประวัติเก่า
const SYMPTOM_LOOKUP = <?php echo json_encode($__all['symptoms'], $__flags); ?>;
const CARE_LOOKUP = <?php echo json_encode($__all['care'], $__flags); ?>;
