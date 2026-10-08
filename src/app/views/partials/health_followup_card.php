<?php
// การ์ดบนแดชบอร์ดครู/admin: เรื่องสุขภาพที่ต้องติดตามกับผู้ปกครอง และผู้ปกครองแจ้งกลับแล้วหรือยัง (ทั้งศูนย์)
//
// ออกแบบให้เพิ่มการตรวจสุขภาพชนิดอื่นได้ในภายหลัง: แต่ละชนิดมีฟังก์ชัน "provider" ที่คืนรายการในรูปแบบเดียวกัน
// (ดูตัวอย่างจาก tfuProviderDental) แล้วเพิ่มชื่อฟังก์ชันเข้า $tfuProviders การ์ดจะรวมแสดงให้เอง
// รูปแบบรายการ: type, type_label, student_id, nickname, name, classroom, detail, urgency (urgent|preventable|not_urgent|''),
//   count_text, checked_at (วันที่ตรวจ), status ('' = ยังไม่ตอบ | acknowledged | scheduled | treated),
//   status_date, note, replied_at, link
require_once __DIR__ . '/../../../config/database.php';

/** ผลตรวจฟันของแพทย์ล่าสุดของเด็กแต่ละคนที่พบฟันผุ */
function tfuProviderDental(PDO $pdo): array
{
    $stmt = $pdo->query("
        WITH latest AS (
            SELECT DISTINCT ON (h.student_id)
                   h.student_id, h.round_id, h.decayed_teeth, h.teeth_status, h.urgency, h.examined_at, h.updated_at,
                   h.followup_status, h.followup_date, h.followup_note, h.followup_updated_at, h.followup_by_role
            FROM health_tooth_external h
            LEFT JOIN tooth_exam_rounds r ON r.id = h.round_id
            WHERE h.exam_type = 'doctor'
            ORDER BY h.student_id, r.academic_year DESC NULLS LAST, r.round_no DESC NULLS LAST, h.id DESC
        )
        SELECT l.*, c.nickname, c.prefix_th, c.firstname_th, c.lastname_th, c.classroom
        FROM latest l
        JOIN children c ON c.studentid = l.student_id
        WHERE c.status = 'กำลังศึกษา' AND (COALESCE(l.decayed_teeth, 0) > 0 OR l.teeth_status = 'abnormal')
    ");
    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $n = (int) $r['decayed_teeth'];
        $items[] = [
            'type' => 'dental', 'type_label' => 'ช่องปาก',
            'student_id' => $r['student_id'], 'nickname' => $r['nickname'],
            'name' => trim(($r['prefix_th'] ?? '') . ($r['firstname_th'] ?? '') . ' ' . ($r['lastname_th'] ?? '')),
            'classroom' => $r['classroom'], 'detail' => 'พบฟันผุ', 'urgency' => $r['urgency'] ?? '',
            'count_text' => $n > 0 ? $n . ' ซี่' : '', 'checked_at' => $r['examined_at'] ?: substr((string) $r['updated_at'], 0, 10),
            'status' => $r['followup_status'] ?: '', 'status_date' => $r['followup_date'], 'note' => $r['followup_note'],
            'replied_at' => $r['followup_updated_at'], 'by' => $r['followup_by_role'] ?? '',
            'link' => '../check_health_tooth/checklist_grid.php?round_id=' . (int) $r['round_id'] . '&search=' . rawurlencode($r['student_id']),
        ];
    }
    return $items;
}

// เพิ่มการตรวจสุขภาพชนิดอื่นที่นี่ เช่น 'tfuProviderGrowth'
$tfuProviders = ['tfuProviderDental'];

$tfuItems = [];
try {
    $tfuPdo = getDatabaseConnection();
    foreach ($tfuProviders as $provider) {
        try {
            $tfuItems = array_merge($tfuItems, $provider($tfuPdo));
        } catch (Exception $e) {
            error_log('health follow-up provider ' . $provider . ': ' . $e->getMessage());   // ยังไม่ได้รัน migration ก็ไม่ให้แดชบอร์ดพัง
        }
    }
} catch (Exception $e) {
    error_log('health follow-up card: ' . $e->getMessage());
}

if (!function_exists('tfuThaiDate')) {
    function tfuThaiDate(?string $d, bool $withTime = false): string
    {
        if (!$d) {
            return '';
        }
        $t = strtotime($d);
        if (!$t) {
            return '';
        }
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $s = (int) date('j', $t) . ' ' . $months[(int) date('n', $t)] . ' ' . substr((string) ((int) date('Y', $t) + 543), -2);
        return $withTime ? $s . ' ' . date('H:i', $t) . ' น.' : $s;
    }
}

if ($tfuItems):
    $tfuCount = ['none' => 0, 'acknowledged' => 0, 'scheduled' => 0, 'treated' => 0];
    $tfuPending = [];
    $tfuReplied = [];
    foreach ($tfuItems as $it) {
        $st = $it['status'] ?: 'none';
        $tfuCount[$st] = ($tfuCount[$st] ?? 0) + 1;
        if ($st === 'none') {
            $tfuPending[] = $it;
        } else {
            $tfuReplied[] = $it;
        }
    }
    // ยังไม่ตอบ: ด่วนก่อน แล้วรอนานสุดก่อน / ตอบแล้ว: ล่าสุดก่อน
    $urgRank = ['urgent' => 0, 'preventable' => 1, 'not_urgent' => 2];
    usort($tfuPending, fn($a, $b) => [$urgRank[$a['urgency']] ?? 3, (string) $a['checked_at']] <=> [$urgRank[$b['urgency']] ?? 3, (string) $b['checked_at']]);
    usort($tfuReplied, fn($a, $b) => strcmp((string) $b['replied_at'], (string) $a['replied_at']));
    $tfuUrgLabel = ['urgent' => 'ด่วน', 'preventable' => 'ผัดผ่อนได้', 'not_urgent' => 'ไม่เร่งด่วน'];
    $tfuStatusLabel = ['acknowledged' => ['รับทราบแล้ว', 'ack'], 'scheduled' => ['นัดหมอ', 'sch'], 'treated' => ['พาไปรักษาแล้ว', 'trt']];
    $tfuName = fn(array $it): string => $it['nickname'] ? 'น้อง' . $it['nickname'] : $it['name'];
?>
<style>
    .tfu-card { background: #fff; border: 1px solid #d9e6ee; border-radius: 16px; box-shadow: 0 8px 24px rgba(15, 23, 42, .07); margin-bottom: 1.5rem; overflow: hidden; }
    .tfu-head { align-items: center; background: #f4f9fd; border-bottom: 1px solid #d9e6ee; display: flex; flex-wrap: wrap; gap: .75rem; padding: .9rem 1.25rem; }
    .tfu-head .ic { align-items: center; background: #dbeafe; border-radius: 12px; color: #1d4ed8; display: flex; font-size: 1.2rem; height: 42px; justify-content: center; width: 42px; }
    .tfu-head .t { color: #1E4F6F; font-size: 1.05rem; font-weight: 700; line-height: 1.2; }
    .tfu-head .s { color: #64748b; font-size: .8rem; }
    .tfu-stats { display: flex; flex-wrap: wrap; gap: .4rem; margin-left: auto; }
    .tfu-pill { border-radius: 999px; font-size: .78rem; font-weight: 700; padding: .2rem .75rem; white-space: nowrap; }
    .tfu-pill.none { background: #ffedd5; color: #c2410c; } .tfu-pill.ack { background: #dbeafe; color: #1d4ed8; }
    .tfu-pill.sch { background: #ede9fe; color: #6d28d9; } .tfu-pill.trt { background: #dcfce7; color: #15803d; }
    .tfu-tag { background: #f1f5f9; border-radius: 6px; color: #475569; font-size: .68rem; font-weight: 700; margin-right: .3rem; padding: 0 .4rem; vertical-align: 1px; }
    .tfu-body { display: grid; gap: 1.25rem; grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 1rem 1.25rem 1.25rem; }
    @media (max-width: 992px) { .tfu-body { grid-template-columns: minmax(0, 1fr); } }
    .tfu-col h6 { color: #1E4F6F; font-size: .9rem; font-weight: 700; margin: 0 0 .6rem; }
    .tfu-list { display: flex; flex-direction: column; gap: .45rem; }
    .tfu-item { align-items: center; background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #cbd5e1; border-radius: 10px; color: inherit; display: flex; gap: .7rem; padding: .5rem .8rem; text-decoration: none; transition: background-color .12s, box-shadow .12s; }
    .tfu-item:hover { background: #f4f9fd; box-shadow: 0 4px 12px rgba(38, 100, 142, .12); color: inherit; }
    .tfu-item.urgent { border-left-color: #dc2626; } .tfu-item.preventable { border-left-color: #f59e0b; }
    .tfu-item.ack { border-left-color: #3b82f6; } .tfu-item.sch { border-left-color: #8b5cf6; } .tfu-item.trt { border-left-color: #22c55e; }
    .tfu-item .nm { flex: 1; min-width: 0; }
    .tfu-item .nm b { color: #0f2460; display: block; line-height: 1.2; }
    .tfu-item .nm small { color: #64748b; display: block; line-height: 1.3; overflow-wrap: anywhere; }
    .tfu-item .rt { flex-shrink: 0; text-align: right; }
    .tfu-item .rt small { color: #64748b; display: block; font-size: .74rem; }
    .tfu-more { color: #64748b; font-size: .8rem; margin-top: .4rem; }
    .tfu-empty { background: #f0fdf4; border: 1px dashed #86efac; border-radius: 10px; color: #15803d; font-weight: 700; padding: .8rem; text-align: center; }
</style>

<div class="tfu-card" id="healthFollowup">
    <div class="tfu-head">
        <span class="ic"><i class="bi bi-heart-pulse"></i></span>
        <div>
            <div class="t">ติดตามสุขภาพ — ผู้ปกครองแจ้งกลับ</div>
            <div class="s">เรื่องสุขภาพที่ต้องติดตามกับผู้ปกครอง ทั้งศูนย์ <?= count($tfuItems) ?> รายการ</div>
        </div>
        <div class="tfu-stats">
            <span class="tfu-pill none">ยังไม่ตอบ <?= $tfuCount['none'] ?></span>
            <span class="tfu-pill ack">รับทราบ <?= $tfuCount['acknowledged'] ?></span>
            <span class="tfu-pill sch">นัดหมอ <?= $tfuCount['scheduled'] ?></span>
            <span class="tfu-pill trt">พาไปรักษาแล้ว <?= $tfuCount['treated'] ?></span>
        </div>
    </div>
    <div class="tfu-body">
        <div class="tfu-col">
            <h6>ยังไม่ตอบกลับ (<?= count($tfuPending) ?>) — เรียงด่วนและรอนานก่อน</h6>
            <div class="tfu-list">
                <?php if (!$tfuPending): ?>
                    <div class="tfu-empty"><i class="bi bi-check-circle-fill me-1"></i>ผู้ปกครองตอบกลับครบทุกคนแล้ว</div>
                <?php endif; ?>
                <?php foreach (array_slice($tfuPending, 0, 6) as $it): ?>
                    <a class="tfu-item <?= htmlspecialchars($it['urgency']) ?>" href="<?= htmlspecialchars($it['link']) ?>">
                        <span class="nm"><b><?= htmlspecialchars($tfuName($it)) ?></b>
                            <small><span class="tfu-tag"><?= htmlspecialchars($it['type_label']) ?></span>ห้อง <?= htmlspecialchars($it['classroom'] ?? '-') ?> · ตรวจ <?= htmlspecialchars(tfuThaiDate($it['checked_at'])) ?></small></span>
                        <span class="rt">
                            <?php if ($it['count_text'] !== ''): ?><b class="text-danger"><?= htmlspecialchars($it['count_text']) ?></b><?php endif; ?>
                            <small><?= htmlspecialchars($tfuUrgLabel[$it['urgency']] ?? $it['detail']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (count($tfuPending) > 6): ?>
                <div class="tfu-more">และอีก <?= count($tfuPending) - 6 ?> รายการ — ดูทั้งหมดได้ที่หน้ากรอกผลตรวจ (ตัวกรอง "มีฟันผุที่ผู้ปกครองยังไม่ตอบกลับ")</div>
            <?php endif; ?>
        </div>

        <div class="tfu-col">
            <h6>ผู้ปกครองแจ้งกลับล่าสุด</h6>
            <div class="tfu-list">
                <?php if (!$tfuReplied): ?>
                    <div class="tfu-empty" style="background:#f8fafc;border-color:#cbd5e1;color:#64748b;">ยังไม่มีผู้ปกครองแจ้งกลับ</div>
                <?php endif; ?>
                <?php foreach (array_slice($tfuReplied, 0, 6) as $it):
                    [$stText, $stCls] = $tfuStatusLabel[$it['status']] ?? ['แจ้งกลับ', 'ack']; ?>
                    <a class="tfu-item <?= $stCls ?>" href="<?= htmlspecialchars($it['link']) ?>">
                        <span class="nm"><b><?= htmlspecialchars($tfuName($it)) ?> <span class="tfu-pill <?= $stCls ?> ms-1"><?= htmlspecialchars($stText) ?><?= $it['status'] !== 'acknowledged' && $it['status_date'] ? ' ' . htmlspecialchars(tfuThaiDate($it['status_date'])) : '' ?></span></b>
                            <small><span class="tfu-tag"><?= htmlspecialchars($it['type_label']) ?></span><?= ($it['by'] ?? '') === 'center' ? '<span class="tfu-tag">ศูนย์บันทึก</span>' : '' ?>ห้อง <?= htmlspecialchars($it['classroom'] ?? '-') ?><?= !empty($it['note']) ? ' · “' . htmlspecialchars($it['note']) . '”' : '' ?></small></span>
                        <span class="rt"><small><?= htmlspecialchars(tfuThaiDate($it['replied_at'], true)) ?></small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
