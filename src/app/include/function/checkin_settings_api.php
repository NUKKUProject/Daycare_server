<?php
// API ตั้งค่าการเช็คชื่อ (เฉพาะ admin)
//   GET  ?action=get                       -> เวลามาสาย + ตัวเลือกทั้งหมด (รวมที่ปิดใช้งาน)
//   POST {action: save_late_time, late_time}
//   POST {action: save_option, option_type, id?, label, icon, allows_text, is_active, subs?}
//   POST {action: toggle_active, id, is_active}
//   POST {action: delete_option, id}
//   POST {action: move_option, id, direction: up|down}

require_once(__DIR__ . '/checkin_settings.php');

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    respond(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบเท่านั้น'], 403);
}

$pdo = checkin_pdo();
if (!$pdo) {
    respond(['status' => 'error', 'message' => 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ'], 500);
}

// ต้องรัน migration 008 ก่อน
$tablesReady = in_array(
    $pdo->query("SELECT to_regclass('public.checkin_options') IS NOT NULL AND to_regclass('public.app_settings') IS NOT NULL")->fetchColumn(),
    [true, 't', '1', 1],
    true
);
if (!$tablesReady) {
    respond([
        'status' => 'needs_migration',
        'message' => 'ยังไม่ได้รัน migrations/008_add_checkin_settings.sql ในฐานข้อมูล',
    ]);
}

function admin_snapshot(): array
{
    // ไม่ใช้ cache เวลาตัดสายของ checkin_get_late_time() เพราะเพิ่งแก้ไข
    $pdo = checkin_pdo();
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'checkin_late_time'");
    $stmt->execute();
    $late = $stmt->fetchColumn();
    $late = $late ? substr($late, 0, 5) : CHECKIN_DEFAULT_LATE_TIME;

    $options = checkin_load_options(false, false);
    return ['late_time' => $late, 'symptoms' => $options['symptoms'], 'care' => $options['care']];
}

function clean_text($value, int $max): string
{
    return mb_substr(trim((string)$value), 0, $max);
}

function new_code(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(4));
}

function reindex_main(PDO $pdo, string $type, array $orderedIds): void
{
    $upd = $pdo->prepare("UPDATE checkin_options SET sort_order = :o, updated_at = NOW() WHERE id = :id");
    foreach ($orderedIds as $i => $id) {
        $upd->execute([':o' => ($i + 1) * 10, ':id' => $id]);
    }
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $_GET['action'] ?? $input['action'] ?? '';

    switch ($action) {
        case 'get':
            respond(['status' => 'success', 'data' => admin_snapshot()]);

        case 'save_late_time':
            $time = trim($input['late_time'] ?? '');
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
                respond(['status' => 'error', 'message' => 'รูปแบบเวลาไม่ถูกต้อง (HH:MM)'], 422);
            }
            $stmt = $pdo->prepare("
                INSERT INTO app_settings (setting_key, setting_value, updated_at)
                VALUES ('checkin_late_time', :v, NOW())
                ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW()
            ");
            $stmt->execute([':v' => $time]);
            respond(['status' => 'success', 'message' => 'บันทึกเวลามาสายแล้ว', 'data' => admin_snapshot()]);

        case 'save_option':
            $type = $input['option_type'] ?? '';
            if (!in_array($type, ['symptom', 'care'], true)) {
                respond(['status' => 'error', 'message' => 'ประเภทตัวเลือกไม่ถูกต้อง'], 422);
            }
            $label = clean_text($input['label'] ?? '', 100);
            if ($label === '') {
                respond(['status' => 'error', 'message' => 'กรุณาระบุชื่อตัวเลือก'], 422);
            }
            $icon = clean_text($input['icon'] ?? '', 8);
            $allowsText = $type === 'care' && !empty($input['allows_text']);
            $isActive = !isset($input['is_active']) || !empty($input['is_active']);
            $id = isset($input['id']) && $input['id'] !== '' ? (int)$input['id'] : null;

            $pdo->beginTransaction();

            if ($id) {
                $find = $pdo->prepare("SELECT code FROM checkin_options WHERE id = :id AND option_type = :t AND parent_code IS NULL AND is_deleted = FALSE");
                $find->execute([':id' => $id, ':t' => $type]);
                $code = $find->fetchColumn();
                if (!$code) {
                    throw new Exception('ไม่พบตัวเลือกที่ต้องการแก้ไข');
                }
                $upd = $pdo->prepare("
                    UPDATE checkin_options
                    SET label = :label, icon = :icon, allows_text = :text, is_active = :active, updated_at = NOW()
                    WHERE id = :id
                ");
                $upd->bindValue(':label', $label);
                $upd->bindValue(':icon', $icon);
                $upd->bindValue(':text', $allowsText, PDO::PARAM_BOOL);
                $upd->bindValue(':active', $isActive, PDO::PARAM_BOOL);
                $upd->bindValue(':id', $id, PDO::PARAM_INT);
                $upd->execute();
            } else {
                $code = new_code($type === 'care' ? 'care' : 'sym');
                $max = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM checkin_options WHERE option_type = :t AND parent_code IS NULL");
                $max->execute([':t' => $type]);
                $sort = (int)$max->fetchColumn() + 10;

                $ins = $pdo->prepare("
                    INSERT INTO checkin_options (option_type, code, parent_code, label, icon, allows_text, sort_order, is_active)
                    VALUES (:t, :code, NULL, :label, :icon, :text, :sort, :active)
                    RETURNING id
                ");
                $ins->bindValue(':t', $type);
                $ins->bindValue(':code', $code);
                $ins->bindValue(':label', $label);
                $ins->bindValue(':icon', $icon);
                $ins->bindValue(':text', $allowsText, PDO::PARAM_BOOL);
                $ins->bindValue(':sort', $sort, PDO::PARAM_INT);
                $ins->bindValue(':active', $isActive, PDO::PARAM_BOOL);
                $ins->execute();
                $id = (int)$ins->fetchColumn();
            }

            // ตัวเลือกย่อย (เฉพาะอาการ)
            if ($type === 'symptom' && isset($input['subs']) && is_array($input['subs'])) {
                $existing = $pdo->prepare("SELECT code FROM checkin_options WHERE option_type = 'symptom' AND parent_code = :p AND is_deleted = FALSE");
                $existing->execute([':p' => $code]);
                $existingCodes = $existing->fetchAll(PDO::FETCH_COLUMN);

                $keep = [];
                $order = 0;
                foreach ($input['subs'] as $sub) {
                    $subLabel = clean_text($sub['label'] ?? '', 100);
                    if ($subLabel === '') {
                        continue;
                    }
                    $order += 10;
                    $subCode = $sub['code'] ?? '';
                    if ($subCode !== '' && in_array($subCode, $existingCodes, true) && !in_array($subCode, $keep, true)) {
                        $upd = $pdo->prepare("UPDATE checkin_options SET label = :l, sort_order = :o, updated_at = NOW() WHERE option_type = 'symptom' AND parent_code = :p AND code = :c");
                        $upd->execute([':l' => $subLabel, ':o' => $order, ':p' => $code, ':c' => $subCode]);
                    } else {
                        $subCode = new_code('sub');
                        $ins = $pdo->prepare("INSERT INTO checkin_options (option_type, code, parent_code, label, sort_order) VALUES ('symptom', :c, :p, :l, :o)");
                        $ins->execute([':c' => $subCode, ':p' => $code, ':l' => $subLabel, ':o' => $order]);
                    }
                    $keep[] = $subCode;
                }

                // ตัวเลือกย่อยที่ถูกเอาออก -> ลบแบบเก็บแถวไว้
                foreach ($existingCodes as $oldCode) {
                    if (!in_array($oldCode, $keep, true)) {
                        $del = $pdo->prepare("UPDATE checkin_options SET is_deleted = TRUE, updated_at = NOW() WHERE option_type = 'symptom' AND parent_code = :p AND code = :c");
                        $del->execute([':p' => $code, ':c' => $oldCode]);
                    }
                }
            }

            $pdo->commit();
            respond(['status' => 'success', 'message' => 'บันทึกตัวเลือกแล้ว', 'data' => admin_snapshot()]);

        case 'toggle_active':
            $stmt = $pdo->prepare("UPDATE checkin_options SET is_active = :a, updated_at = NOW() WHERE id = :id AND parent_code IS NULL AND is_deleted = FALSE");
            $stmt->bindValue(':a', !empty($input['is_active']), PDO::PARAM_BOOL);
            $stmt->bindValue(':id', (int)($input['id'] ?? 0), PDO::PARAM_INT);
            $stmt->execute();
            respond(['status' => 'success', 'data' => admin_snapshot()]);

        case 'delete_option':
            $id = (int)($input['id'] ?? 0);
            $find = $pdo->prepare("SELECT option_type, code FROM checkin_options WHERE id = :id AND parent_code IS NULL AND is_deleted = FALSE");
            $find->execute([':id' => $id]);
            $row = $find->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                respond(['status' => 'error', 'message' => 'ไม่พบตัวเลือกที่ต้องการลบ'], 404);
            }
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE checkin_options SET is_deleted = TRUE, updated_at = NOW() WHERE id = :id")->execute([':id' => $id]);
            if ($row['option_type'] === 'symptom') {
                $pdo->prepare("UPDATE checkin_options SET is_deleted = TRUE, updated_at = NOW() WHERE option_type = 'symptom' AND parent_code = :p")
                    ->execute([':p' => $row['code']]);
            }
            $pdo->commit();
            respond(['status' => 'success', 'message' => 'ลบตัวเลือกแล้ว', 'data' => admin_snapshot()]);

        case 'move_option':
            $id = (int)($input['id'] ?? 0);
            $dir = $input['direction'] ?? '';
            if (!in_array($dir, ['up', 'down'], true)) {
                respond(['status' => 'error', 'message' => 'ทิศทางไม่ถูกต้อง'], 422);
            }
            $find = $pdo->prepare("SELECT option_type FROM checkin_options WHERE id = :id AND parent_code IS NULL AND is_deleted = FALSE");
            $find->execute([':id' => $id]);
            $type = $find->fetchColumn();
            if (!$type) {
                respond(['status' => 'error', 'message' => 'ไม่พบตัวเลือก'], 404);
            }
            $list = $pdo->prepare("SELECT id FROM checkin_options WHERE option_type = :t AND parent_code IS NULL AND is_deleted = FALSE ORDER BY sort_order, id");
            $list->execute([':t' => $type]);
            $ids = array_map('intval', $list->fetchAll(PDO::FETCH_COLUMN));
            $pos = array_search($id, $ids, true);
            $swap = $dir === 'up' ? $pos - 1 : $pos + 1;
            if ($pos !== false && isset($ids[$swap])) {
                [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
                $pdo->beginTransaction();
                reindex_main($pdo, $type, $ids);
                $pdo->commit();
            }
            respond(['status' => 'success', 'data' => admin_snapshot()]);

        default:
            respond(['status' => 'error', 'message' => 'ไม่รู้จักคำสั่ง'], 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('checkin_settings_api: ' . $e->getMessage());
    respond(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
}
