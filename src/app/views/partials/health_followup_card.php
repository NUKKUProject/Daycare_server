<?php
// การ์ดสรุปบนแดชบอร์ดครู/admin: แสดงเฉพาะจำนวนเรื่องสุขภาพที่ต้องติดตามกับผู้ปกครอง (ทั้งศูนย์)
// กดตัวเลขแล้วไปหน้า "ติดตามสุขภาพ" (views/health_followup.php) พร้อมกรองตามสถานะนั้น
// ข้อมูลมาจากไลบรารีกลาง include/function/health_followup_functions.php (เพิ่มการตรวจชนิดใหม่ที่นั่น)
require_once __DIR__ . '/../../include/function/health_followup_functions.php';

$tfuItems = [];
try {
    $tfuItems = hf_fetch_items(getDatabaseConnection());
} catch (Exception $e) {
    error_log('health follow-up card: ' . $e->getMessage());
}

if ($tfuItems):
    $tfuCount = ['none' => 0, 'acknowledged' => 0, 'scheduled' => 0, 'treated' => 0];
    foreach ($tfuItems as $it) {
        $tfuCount[$it['status'] ?: 'none']++;
    }
    $tfuTiles = [
        ['none', 'ยังไม่ตอบ', 'bi-hourglass-split'],
        ['acknowledged', 'รับทราบแล้ว', 'bi-hand-thumbs-up'],
        ['scheduled', 'นัดหมอแล้ว', 'bi-calendar-event'],
        ['treated', 'พาไปรักษาแล้ว', 'bi-check2-circle'],
    ];
?>
<style>
    .tfu-card { background: #fff; border: 1px solid #d9e6ee; border-radius: 16px; box-shadow: 0 8px 24px rgba(15, 23, 42, .07); margin-bottom: 1.5rem; overflow: hidden; }
    .tfu-head { align-items: center; background: #f4f9fd; border-bottom: 1px solid #d9e6ee; display: flex; flex-wrap: wrap; gap: .75rem; padding: .9rem 1.25rem; }
    .tfu-head .ic { align-items: center; background: #dbeafe; border-radius: 12px; color: #1d4ed8; display: flex; font-size: 1.2rem; height: 42px; justify-content: center; width: 42px; }
    .tfu-head .t { color: #1E4F6F; font-size: 1.05rem; font-weight: 700; line-height: 1.2; }
    .tfu-head .s { color: #64748b; font-size: .8rem; }
    .tfu-all { background: #1E4F6F; border-radius: 999px; color: #fff; font-size: .8rem; font-weight: 700; margin-left: auto; padding: .3rem .9rem; text-decoration: none; white-space: nowrap; }
    .tfu-all:hover { background: #26648E; color: #fff; }
    .tfu-tiles { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(0, 1fr)); padding: 1rem 1.25rem 1.25rem; }
    @media (max-width: 768px) { .tfu-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .tfu-tile { align-items: center; background: #fff; border: 2px solid #e2e8f0; border-radius: 14px; color: inherit; display: flex; gap: .8rem; padding: .8rem 1rem; text-decoration: none; transition: border-color .12s, transform .12s, box-shadow .12s; }
    .tfu-tile:hover { box-shadow: 0 8px 18px rgba(38, 100, 142, .15); color: inherit; transform: translateY(-2px); }
    .tfu-tile .n { font-size: 1.9rem; font-weight: 700; line-height: 1; }
    .tfu-tile .l { color: #475569; font-size: .82rem; font-weight: 700; line-height: 1.2; }
    .tfu-tile i { font-size: 1.3rem; margin-left: auto; opacity: .7; }
    .tfu-tile.none { border-color: #fdba74; background: #fff7ed; } .tfu-tile.none .n, .tfu-tile.none i { color: #c2410c; }
    .tfu-tile.acknowledged .n, .tfu-tile.acknowledged i { color: #1d4ed8; }
    .tfu-tile.scheduled .n, .tfu-tile.scheduled i { color: #6d28d9; }
    .tfu-tile.treated .n, .tfu-tile.treated i { color: #15803d; }
</style>

<div class="tfu-card" id="healthFollowup">
    <div class="tfu-head">
        <span class="ic"><i class="bi bi-heart-pulse"></i></span>
        <div>
            <div class="t">ติดตามสุขภาพ — ผู้ปกครองแจ้งกลับ</div>
            <div class="s">เรื่องสุขภาพที่ต้องติดตามกับผู้ปกครอง ทั้งศูนย์ <?= count($tfuItems) ?> รายการ</div>
        </div>
        <a href="/app/views/health_followup.php" class="tfu-all">ดูทั้งหมด <i class="bi bi-chevron-right"></i></a>
    </div>
    <div class="tfu-tiles">
        <?php foreach ($tfuTiles as [$key, $label, $icon]): ?>
            <a class="tfu-tile <?= $key ?>" href="/app/views/health_followup.php?status=<?= $key ?>">
                <span><span class="n d-block"><?= $tfuCount[$key] ?></span><span class="l"><?= $label ?></span></span>
                <i class="bi <?= $icon ?>"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
