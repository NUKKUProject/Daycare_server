<?php
// ตั้งค่าการเช็คชื่อ: เวลามาสาย + ตัวเลือกอาการ/การดูแล
// อ่านจากฐานข้อมูล (migrations/008) ถ้ายังไม่มีตาราง จะใช้ค่าเริ่มต้นด้านล่างแทน

require_once(__DIR__ . '/../../../config/database.php');

const CHECKIN_DEFAULT_LATE_TIME = '08:30';

function checkin_pdo(): ?PDO
{
    static $pdo = false;
    if ($pdo === false) {
        try {
            $pdo = getDatabaseConnection();
        } catch (Throwable $e) {
            error_log('checkin_pdo: ' . $e->getMessage());
            $pdo = null;
        }
    }
    return $pdo;
}

/** ตัวเลือกเริ่มต้น (ใช้เมื่อยังไม่มีตารางตั้งค่า) */
function checkin_default_options(): array
{
    $sub = fn($code, $label) => ['code' => $code, 'label' => $label, 'is_active' => true];
    $sym = function ($code, $label, $icon, $subs = []) {
        return ['code' => $code, 'label' => $label, 'icon' => $icon, 'is_active' => true, 'subs' => $subs];
    };
    $care = fn($code, $label, $icon, $text = false) => [
        'code' => $code, 'label' => $label, 'icon' => $icon, 'allows_text' => $text, 'is_active' => true,
    ];

    return [
        'symptoms' => [
            $sym('runny_nose', 'น้ำมูก', '🤧', [$sub('clear', 'ใส'), $sub('yellow', 'เหลือง'), $sub('green', 'เขียว')]),
            $sym('cough', 'ไอ', '😷', [$sub('dry', 'แห้ง'), $sub('phlegm', 'เสมหะ')]),
            $sym('heat_in', 'ร้อนใน', '🔥'),
            $sym('gum_swelling', 'เหงือกบวม', '🦷'),
            $sym('red_throat', 'คอแดง', '🗣️'),
            $sym('mouth_blisters', 'ตุ่มที่ปาก', '👄'),
            $sym('mosquito_bites', 'ตุ่มยุงกัด', '🦟'),
            $sym('hfmd', 'มือเท้าปาก', '🖐️'),
            $sym('wound', 'แผล', '🩹'),
            $sym('rash', 'ผื่น', '🔴'),
            $sym('eye_discharge', 'ขี้ตา', '👁️', [$sub('yellow', 'เหลือง'), $sub('green', 'เขียว')]),
        ],
        'care' => [
            $care('wash_hands', 'ล้างมือบ่อยๆ', '🧼'),
            $care('give_medicine', 'ป้อนยา', '💊'),
            $care('apply_medicine', 'ทายา', '🧴'),
            $care('pcn123', 'PCN123', '📋'),
            $care('other', 'อื่นๆ', '✏️', true),
        ],
    ];
}

/** เวลาตัดสายในรูปแบบ HH:MM */
function checkin_get_late_time(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $cached = CHECKIN_DEFAULT_LATE_TIME;
    $pdo = checkin_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'checkin_late_time'");
            $stmt->execute();
            $value = $stmt->fetchColumn();
            if ($value && preg_match('/^([01]\d|2[0-3]):[0-5]\d/', $value)) {
                $cached = substr($value, 0, 5);
            }
        } catch (Throwable $e) {
            // ยังไม่มีตาราง app_settings -> ใช้ค่าเริ่มต้น
        }
    }
    return $cached;
}

/** เวลา $time (HH:MM หรือ HH:MM:SS) เลยเวลาตัดสายหรือไม่ (ตรงกับเวลาตัดสายพอดีถือว่ายังไม่สาย) */
function checkin_is_late(string $time): bool
{
    $parts = explode(':', $time);
    $seconds = intval($parts[0]) * 3600 + intval($parts[1] ?? 0) * 60;

    $cutoff = explode(':', checkin_get_late_time());
    $cutoffSeconds = intval($cutoff[0]) * 3600 + intval($cutoff[1]) * 60;

    return $seconds > $cutoffSeconds;
}

/**
 * โหลดตัวเลือกอาการ/การดูแลจากฐานข้อมูล
 * @param bool $activeOnly     true = เฉพาะที่เปิดใช้งาน (สำหรับหน้าเช็คชื่อ)
 * @param bool $includeDeleted true = รวมที่ถูกลบแล้ว (สำหรับอ่านชื่อในประวัติเก่า)
 */
function checkin_load_options(bool $activeOnly = true, bool $includeDeleted = false): array
{
    $pdo = checkin_pdo();
    if ($pdo) {
        try {
            $sql = "SELECT id, option_type, code, parent_code, label, icon, allows_text, sort_order, is_active, is_deleted
                    FROM checkin_options WHERE 1=1";
            if (!$includeDeleted) {
                $sql .= " AND is_deleted = FALSE";
            }
            if ($activeOnly) {
                $sql .= " AND is_active = TRUE";
            }
            $sql .= " ORDER BY sort_order, id";
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if ($rows) {
                return checkin_group_rows($rows);
            }
            // ตารางมีแต่ยังไม่มีข้อมูลเลย (ไม่ได้รัน seed) -> ใช้ค่าเริ่มต้น
            $count = (int)$pdo->query("SELECT COUNT(*) FROM checkin_options")->fetchColumn();
            if ($count > 0) {
                return ['symptoms' => [], 'care' => []];
            }
        } catch (Throwable $e) {
            // ยังไม่มีตาราง -> ใช้ค่าเริ่มต้น
        }
    }
    return checkin_default_options();
}

function checkin_group_rows(array $rows): array
{
    $symptoms = [];
    $care = [];
    $subs = [];
    $bool = fn($v) => $v === true || $v === 't' || $v === 1 || $v === '1';

    foreach ($rows as $r) {
        $item = [
            'id' => (int)$r['id'],
            'code' => $r['code'],
            'label' => $r['label'],
            'icon' => $r['icon'] ?? '',
            'allows_text' => $bool($r['allows_text']),
            'sort_order' => (int)$r['sort_order'],
            'is_active' => $bool($r['is_active']),
            'is_deleted' => $bool($r['is_deleted']),
        ];
        if ($r['option_type'] === 'symptom' && $r['parent_code'] !== null) {
            $subs[$r['parent_code']][] = $item;
        } elseif ($r['option_type'] === 'symptom') {
            $symptoms[] = $item;
        } else {
            $care[] = $item;
        }
    }

    foreach ($symptoms as &$s) {
        $s['subs'] = $subs[$s['code']] ?? [];
    }
    unset($s);

    return ['symptoms' => $symptoms, 'care' => $care];
}

/**
 * รหัสที่รับได้ตอนบันทึก (รวมทั้งที่ปิดใช้งานและที่ลบแล้ว
 * เพื่อไม่ให้ข้อมูลเก่าหายเมื่อแก้ไขประวัติ)
 */
function checkin_allowed_map(): array
{
    $all = checkin_load_options(false, true);
    $symptoms = [];
    foreach ($all['symptoms'] as $s) {
        $symptoms[$s['code']] = array_map(fn($x) => $x['code'], $s['subs'] ?? []);
    }
    $care = [];
    $careText = [];
    foreach ($all['care'] as $c) {
        $care[] = $c['code'];
        if (!empty($c['allows_text'])) {
            $careText[] = $c['code'];
        }
    }
    return ['symptoms' => $symptoms, 'care' => $care, 'care_text' => $careText];
}
