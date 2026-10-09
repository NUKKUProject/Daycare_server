<?php
// ศูนย์กลาง "ติดตามสุขภาพ": รวมเรื่องสุขภาพที่ต้องติดตามกับผู้ปกครองจากการตรวจหลายชนิด
//
// เพิ่มการตรวจชนิดใหม่:
//   1) เขียนฟังก์ชัน provider (ดูตัวอย่าง hf_provider_dental) คืนรายการตามรูปแบบด้านล่าง
//      โดย LEFT JOIN health_followups เพื่อดึงการตอบกลับ (source_type + source_id)
//   2) เพิ่มชนิดเข้า hf_types() และเพิ่มการตรวจสิทธิ์ใน hf_verify_source()
// รูปแบบรายการ: source_type, source_id, type_label, student_id, nickname, name, classroom, child_group, detail,
//   urgency (urgent|preventable|not_urgent|''), count_text, checked_at, status ('' = ยังไม่ตอบ | acknowledged | scheduled | treated),
//   status_date, note, replied_at, by (parent|center|''), link (หน้าดูผลตรวจ)
require_once __DIR__ . '/../../../config/database.php';

const HF_STATUSES = ['acknowledged', 'scheduled', 'treated'];

/** ชนิดการตรวจที่ติดตามได้ */
function hf_types(): array
{
    return [
        'dental' => ['label' => 'ช่องปาก', 'icon' => 'fa-solid fa-tooth', 'provider' => 'hf_provider_dental'],
        'external' => ['label' => 'ตรวจร่างกายกุมารแพทย์', 'icon' => 'fa-solid fa-user-doctor', 'provider' => 'hf_provider_external'],
    ];
}

/** รายการที่ผิดปกติของผลตรวจร่างกายกุมารแพทย์ 1 ครั้ง (ว่าง = ปกติ) ใช้ร่วมกันทั้งแดชบอร์ดและรายการติดตาม */
function hf_external_findings(array $r): array
{
    $decode = fn($v) => is_array($j = json_decode((string) $v, true)) ? $j : [];
    $isAbn = fn($v) => is_array($v) ? in_array('abnormal', $v, true) : $v === 'abnormal';
    $devLabels = ['gm' => 'GM', 'fm' => 'FM', 'rl' => 'RL', 'el' => 'EL', 'ps' => 'PS'];
    $examLabels = ['general' => 'สภาพทั่วไป', 'skin' => 'ผิวหนัง', 'head' => 'ศีรษะ', 'face' => 'ใบหน้า', 'eyes' => 'ตา', 'ears' => 'หู', 'nose' => 'จมูก', 'mouth' => 'ปาก',
        'neck' => 'คอ', 'breast' => 'ทรวงอก/ปอด', 'breathe' => 'การหายใจ', 'lungs' => 'ปอด', 'heart' => 'หัวใจ', 'heart_sound' => 'เสียงหัวใจ', 'pulse' => 'ชีพจร',
        'abdomen' => 'ท้อง', 'others' => 'อื่นๆ', 'neuro' => 'ระบบประสาท', 'movement' => 'การเคลื่อนไหว'];
    $dev = $decode($r['development_assessment'] ?? null);
    $pe = $decode($r['physical_exam'] ?? null) + $decode($r['neurological'] ?? null);
    $beh = $decode($r['behavior'] ?? null);
    $parts = [];
    $delayed = array_filter($devLabels, fn($k) => in_array($dev[$k]['status'] ?? '', ['delay', 'fail'], true), ARRAY_FILTER_USE_KEY);
    if ($delayed) {
        $parts[] = 'พัฒนาการสงสัยล่าช้า ' . implode(', ', $delayed);
    }
    $abn = array_filter($examLabels, fn($k) => $isAbn($pe[$k] ?? null), ARRAY_FILTER_USE_KEY);
    if ($abn) {
        $parts[] = 'ตรวจร่างกายผิดปกติ ' . implode(', ', $abn);
    }
    if (($beh['status'] ?? '') === 'has') {
        $parts[] = 'ปัญหาด้านพฤติกรรม' . (!empty($beh['detail']) ? ' (' . $beh['detail'] . ')' : '');
    }
    return $parts;
}

/** ผลตรวจร่างกายกุมารแพทย์ล่าสุดของเด็กแต่ละคนที่ผิดปกติ */
function hf_provider_external(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT DISTINCT ON (e.student_id) e.id, e.student_id, e.exam_date, e.updated_at,
               (SELECT hr.id FROM health_exam_rounds hr WHERE hr.academic_year = e.academic_year::text AND hr.round_no = COALESCE(e.check_round, 1) LIMIT 1) AS round_id,
               e.behavior, e.development_assessment, e.physical_exam, e.neurological,
               c.nickname, c.prefix_th, c.firstname_th, c.lastname_th, c.classroom, c.child_group,
               f.status AS f_status, f.followup_date AS f_date, f.note AS f_note, f.updated_at AS f_updated, f.by_role AS f_by
        FROM health_data_external e
        JOIN children c ON c.studentid = e.student_id
        LEFT JOIN health_followups f ON f.source_type = 'external' AND f.source_id = e.id
        WHERE c.status = 'กำลังศึกษา' AND COALESCE(TRIM(e.doctor_name), '') <> ''
        ORDER BY e.student_id, e.exam_date DESC NULLS LAST, e.id DESC
    ");
    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $parts = hf_external_findings($r);
        if (!$parts) {
            continue;
        }
        $items[] = [
            'source_type' => 'external', 'source_id' => (int) $r['id'], 'type_label' => 'ตรวจร่างกายกุมารแพทย์',
            'student_id' => $r['student_id'], 'nickname' => $r['nickname'],
            'name' => trim(($r['prefix_th'] ?? '') . ($r['firstname_th'] ?? '') . ' ' . ($r['lastname_th'] ?? '')),
            'classroom' => $r['classroom'], 'child_group' => $r['child_group'],
            'detail' => implode(' · ', $parts), 'urgency' => '', 'count_text' => '',
            'checked_at' => $r['exam_date'] ?: substr((string) $r['updated_at'], 0, 10),
            'status' => $r['f_status'] ?: '', 'status_date' => $r['f_date'], 'note' => $r['f_note'],
            'replied_at' => $r['f_updated'], 'by' => $r['f_by'] ?? '',
            'link' => '/app/views/check_health_external/checklist_grid.php?' . ($r['round_id'] ? 'round_id=' . (int) $r['round_id'] . '&' : '') . 'search=' . rawurlencode($r['student_id']),
        ];
    }
    return $items;
}

/** ผลตรวจฟันของแพทย์ล่าสุดของเด็กแต่ละคนที่พบฟันผุ */
function hf_provider_dental(PDO $pdo): array
{
    $stmt = $pdo->query("
        WITH latest AS (
            SELECT DISTINCT ON (h.student_id)
                   h.id, h.student_id, h.round_id, h.decayed_teeth, h.teeth_status, h.urgency, h.examined_at, h.updated_at, h.treatments::text AS treatments
            FROM health_tooth_external h
            LEFT JOIN tooth_exam_rounds r ON r.id = h.round_id
            WHERE h.exam_type = 'doctor'
            ORDER BY h.student_id, r.academic_year DESC NULLS LAST, r.round_no DESC NULLS LAST, h.id DESC
        )
        SELECT l.*, c.nickname, c.prefix_th, c.firstname_th, c.lastname_th, c.classroom, c.child_group,
               f.status AS f_status, f.followup_date AS f_date, f.note AS f_note, f.updated_at AS f_updated, f.by_role AS f_by
        FROM latest l
        JOIN children c ON c.studentid = l.student_id
        LEFT JOIN health_followups f ON f.source_type = 'dental' AND f.source_id = l.id
        WHERE c.status = 'กำลังศึกษา' AND (COALESCE(l.decayed_teeth, 0) > 0 OR l.teeth_status = 'abnormal' OR COALESCE(NULLIF(l.treatments, 'null'), '[]') <> '[]')
    ");
    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $n = (int) $r['decayed_teeth'];
        $items[] = [
            'source_type' => 'dental', 'source_id' => (int) $r['id'], 'type_label' => 'ช่องปาก',
            'student_id' => $r['student_id'], 'nickname' => $r['nickname'],
            'name' => trim(($r['prefix_th'] ?? '') . ($r['firstname_th'] ?? '') . ' ' . ($r['lastname_th'] ?? '')),
            'classroom' => $r['classroom'], 'child_group' => $r['child_group'],
            'detail' => 'พบฟันผุ', 'urgency' => $r['urgency'] ?? '', 'count_text' => $n > 0 ? $n . ' ซี่' : '',
            'checked_at' => $r['examined_at'] ?: substr((string) $r['updated_at'], 0, 10),
            'status' => $r['f_status'] ?: '', 'status_date' => $r['f_date'], 'note' => $r['f_note'],
            'replied_at' => $r['f_updated'], 'by' => $r['f_by'] ?? '',
            'link' => '/app/views/check_health_tooth/checklist_grid.php?round_id=' . (int) $r['round_id'] . '&search=' . rawurlencode($r['student_id']),
        ];
    }
    return $items;
}

/** รายการติดตามทั้งหมดของทั้งศูนย์ (รวมทุกชนิด) ชนิดที่ดึงไม่ได้จะถูกข้ามและบันทึก log */
function hf_fetch_items(PDO $pdo, ?array &$errors = null): array
{
    $all = [];
    foreach (hf_types() as $type => $def) {
        try {
            $all = array_merge($all, $def['provider']($pdo));
        } catch (Exception $e) {
            error_log("health follow-up provider {$type}: " . $e->getMessage());   // ยังไม่ได้รัน migration ก็ไม่ให้หน้าพัง
            if ($errors !== null) {
                $errors[$type] = $e->getMessage();
            }
        }
    }
    return $all;
}

/** จำนวนเรื่องที่ผู้ปกครองยังไม่ตอบ (ใช้แสดงตัวเลขบนเมนู) */
function hf_pending_count(): int
{
    static $count = null;
    if ($count !== null) {
        return $count;
    }
    try {
        $count = 0;
        foreach (hf_fetch_items(getDatabaseConnection()) as $it) {
            if ($it['status'] === '') {
                $count++;
            }
        }
    } catch (Exception $e) {
        $count = 0;
    }
    return $count;
}

/** ตรวจว่าผู้ใช้แจ้งกลับเรื่องนี้ได้หรือไม่ ผู้ปกครอง (studentId ระบุมา) ได้เฉพาะของลูกตัวเอง ศูนย์ได้ทุกคน */
function hf_verify_source(PDO $pdo, string $type, int $sourceId, ?string $studentId): ?string
{
    if ($type === 'dental') {
        $sql = "SELECT student_id FROM health_tooth_external WHERE id = :id AND exam_type = 'doctor'" . ($studentId !== null ? ' AND student_id = :s' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($studentId !== null ? [':id' => $sourceId, ':s' => $studentId] : [':id' => $sourceId]);
        $sid = $stmt->fetchColumn();
        return $sid !== false ? (string) $sid : null;
    }
    if ($type === 'external') {
        $sql = "SELECT student_id FROM health_data_external WHERE id = :id AND COALESCE(TRIM(doctor_name), '') <> ''" . ($studentId !== null ? ' AND student_id = :s' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($studentId !== null ? [':id' => $sourceId, ':s' => $studentId] : [':id' => $sourceId]);
        $sid = $stmt->fetchColumn();
        return $sid !== false ? (string) $sid : null;
    }
    return null;
}

/** บันทึก/อัปเดตการตอบกลับ (1 ผลตรวจ 1 แถว) */
function hf_save(PDO $pdo, string $type, int $sourceId, string $studentId, string $status, ?string $date, ?string $note, string $user, string $by): void
{
    $stmt = $pdo->prepare("
        INSERT INTO health_followups (source_type, source_id, student_id, status, followup_date, note, by_user, by_role, ack_at, updated_at)
        VALUES (:t, :sid, :stu, :st, :d, :n, :u, :by, NOW(), NOW())
        ON CONFLICT (source_type, source_id) DO UPDATE SET
            status = EXCLUDED.status, followup_date = EXCLUDED.followup_date, note = EXCLUDED.note,
            by_user = EXCLUDED.by_user, by_role = EXCLUDED.by_role, updated_at = NOW(),
            ack_at = COALESCE(health_followups.ack_at, NOW())
    ");
    $stmt->execute([':t' => $type, ':sid' => $sourceId, ':stu' => $studentId, ':st' => $status, ':d' => $date, ':n' => $note, ':u' => $user, ':by' => $by]);
}
