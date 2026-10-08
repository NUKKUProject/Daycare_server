<?php
include __DIR__ . '/../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
include __DIR__ . '/partials/Header.php';
include __DIR__ . '/../include/auth/auth_navbar.php';
require_once __DIR__ . '/../include/function/pages_referen.php';
require_once __DIR__ . '/../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../include/function/health_followup_functions.php';

$items = [];
$hfErrors = [];
try {
    $items = hf_fetch_items(getDatabaseConnection(), $hfErrors);
} catch (Exception $e) {
    error_log('health_followup page: ' . $e->getMessage());
    $hfErrors['connection'] = $e->getMessage();
}
$types = array_map(fn($t) => ['label' => $t['label'], 'icon' => $t['icon']], hf_types());
?>

<style>
    .hf-wrap { max-width: 1500px; margin: 0 auto; }
    .hf-hero { align-items: center; background: linear-gradient(120deg, #1E4F6F 0%, #26648E 60%, #4f88aa 100%); border-radius: 1.25rem; box-shadow: 0 12px 28px rgba(38, 100, 142, .2);
        color: #fff; display: flex; gap: 1rem; margin-bottom: 1.1rem; padding: 1.25rem 1.5rem; }
    .hf-hero .ic { align-items: center; background: rgba(255, 255, 255, .18); border: 1px solid rgba(255, 255, 255, .3); border-radius: 1rem; display: flex; font-size: 1.6rem; height: 56px; justify-content: center; width: 56px; flex-shrink: 0; }
    .hf-hero h2 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .15rem; }
    .hf-hero p { color: rgba(255, 255, 255, .85); font-size: .9rem; margin: 0; }
    .hf-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 4px 16px rgba(15, 23, 42, .05); margin-bottom: 1rem; padding: 1rem 1.25rem; }

    .hf-stats { display: grid; gap: .8rem; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 1rem; }
    @media (max-width: 768px) { .hf-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .hf-stat { align-items: center; background: #fff; border: 2px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 4px 14px rgba(15, 23, 42, .05); cursor: pointer; display: flex; gap: .8rem; padding: .85rem 1rem; text-align: left; transition: border-color .12s, transform .12s, box-shadow .12s; }
    .hf-stat:hover { box-shadow: 0 10px 22px rgba(38, 100, 142, .15); transform: translateY(-2px); }
    .hf-stat .ico { align-items: center; border-radius: .85rem; display: flex; flex-shrink: 0; font-size: 1.3rem; height: 46px; justify-content: center; width: 46px; }
    .hf-stat .n { color: #0f2460; font-size: 1.8rem; font-weight: 700; line-height: 1; }
    .hf-stat .l { color: #64748b; font-size: .82rem; font-weight: 700; }
    .hf-stat.none .ico { background: #ffedd5; color: #c2410c; } .hf-stat.acknowledged .ico { background: #dbeafe; color: #1d4ed8; }
    .hf-stat.scheduled .ico { background: #ede9fe; color: #6d28d9; } .hf-stat.treated .ico { background: #dcfce7; color: #15803d; }
    .hf-stat.active.none { border-color: #fb923c; background: #fff7ed; } .hf-stat.active.acknowledged { border-color: #60a5fa; background: #eff6ff; }
    .hf-stat.active.scheduled { border-color: #a78bfa; background: #f5f3ff; } .hf-stat.active.treated { border-color: #4ade80; background: #f0fdf4; }

    .hf-toolbar { display: grid; gap: .75rem; grid-template-columns: minmax(0, 1.6fr) repeat(3, minmax(0, 1fr)) auto; align-items: end; }
    @media (max-width: 992px) { .hf-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .hf-toolbar label { color: #475569; font-size: .75rem; font-weight: 700; margin-bottom: .2rem; }
    .hf-search { position: relative; }
    .hf-search i { color: #94a3b8; left: .8rem; pointer-events: none; position: absolute; top: 50%; transform: translateY(-50%); }
    .hf-search input { padding-left: 2.2rem; }
    .hf-tabs { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .9rem; }
    .hf-tab { background: #fff; border: 2px solid #e2e8f0; border-radius: 999px; color: #475569; font-weight: 700; padding: .25rem .95rem; transition: all .12s; }
    .hf-tab:hover { border-color: #26648E; }
    .hf-tab.on { background: #1E4F6F; border-color: #1E4F6F; color: #fff; }
    .hf-tab .c { background: rgba(100, 116, 139, .15); border-radius: 999px; font-size: .75rem; margin-left: .3rem; padding: 0 .45rem; }
    .hf-tab.on .c { background: rgba(255, 255, 255, .25); }
    .hf-count { color: #64748b; font-size: .85rem; margin: 0 .25rem .5rem; }

    table.hf { border-collapse: separate; border-spacing: 0 .55rem; width: 100%; }
    .hf thead th { background: transparent; color: #64748b; font-size: .75rem; font-weight: 700; padding: 0 .9rem .1rem; text-align: left; white-space: nowrap; }
    .hf tbody td { background: #fff; border-bottom: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0; padding: .75rem .9rem; vertical-align: middle; }
    .hf tbody td:first-child { border-left: 5px solid #cbd5e1; border-radius: .9rem 0 0 .9rem; border-bottom-left-radius: .9rem; border-top-left-radius: .9rem; }
    .hf tbody td:last-child { border-radius: 0 .9rem .9rem 0; border-right: 1px solid #e2e8f0; }
    .hf tbody tr { box-shadow: 0 3px 10px rgba(15, 23, 42, .05); transition: transform .12s, box-shadow .12s; }
    .hf tbody tr:hover { box-shadow: 0 10px 22px rgba(38, 100, 142, .14); transform: translateY(-1px); }
    .hf tbody tr.urgent td:first-child { border-left-color: #dc2626; } .hf tbody tr.preventable td:first-child { border-left-color: #f59e0b; }
    .hf tbody tr.done td:first-child { border-left-color: #22c55e; }
    .hf-who { align-items: center; display: flex; gap: .7rem; min-width: 190px; }
    .hf-av { align-items: center; border-radius: 50%; color: #fff; display: flex; flex-shrink: 0; font-size: 1.05rem; font-weight: 700; height: 42px; justify-content: center; width: 42px; }
    .hf .nick { color: #0f2460; font-weight: 700; line-height: 1.2; }
    .hf .full { color: #64748b; font-size: .75rem; line-height: 1.2; }
    .hf-room { background: #eef6ff; border-radius: 6px; color: #1d4ed8; font-size: .75rem; font-weight: 700; padding: 1px 8px; white-space: nowrap; }
    .hf-tag { background: #f1f5f9; border-radius: 6px; color: #475569; font-size: .72rem; font-weight: 700; padding: 1px 8px; white-space: nowrap; }
    .hf-find { font-weight: 700; color: #0f2460; }
    .hf-urg { border-radius: 999px; display: inline-block; font-size: .72rem; font-weight: 700; margin-top: .2rem; padding: 1px 9px; }
    .hf-urg.urgent { background: #fee2e2; color: #b91c1c; } .hf-urg.preventable { background: #ffedd5; color: #c2410c; } .hf-urg.not_urgent { background: #f1f5f9; color: #475569; }
    .hf-chip { align-items: center; border-radius: 999px; display: inline-flex; font-size: .8rem; font-weight: 700; gap: .3rem; padding: 3px 11px; white-space: nowrap; }
    .hf-chip.none { background: #ffedd5; color: #c2410c; } .hf-chip.acknowledged { background: #dbeafe; color: #1d4ed8; }
    .hf-chip.scheduled { background: #ede9fe; color: #6d28d9; } .hf-chip.treated { background: #dcfce7; color: #15803d; }
    .hf-sub { color: #64748b; font-size: .74rem; margin-top: .25rem; }
    .hf-note { color: #475569; font-size: .82rem; max-width: 260px; overflow-wrap: anywhere; }
    .hf-act { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: flex-end; }
    .hf-btn { background: #16a34a; border: 0; border-radius: .6rem; box-shadow: 0 2px 6px rgba(22, 163, 74, .3); color: #fff; font-size: .8rem; font-weight: 700; padding: .35rem .9rem; white-space: nowrap; }
    .hf-btn:hover { background: #15803d; }
    .hf-btn.edit { background: #fff; border: 1.5px solid #16a34a; box-shadow: none; color: #15803d; }
    .hf-btn.edit:hover { background: #16a34a; color: #fff; }
    .hf-link { background: #fff; border: 1.5px solid #cbd5e1; border-radius: .6rem; color: #475569; font-size: .8rem; font-weight: 700; padding: .3rem .8rem; text-decoration: none; white-space: nowrap; }
    .hf-link:hover { background: #f1f5f9; color: #1E4F6F; }
    .hf-empty { color: #64748b; padding: 3rem 1rem; text-align: center; }
    .hf-empty i { color: #86efac; display: block; font-size: 2.6rem; margin-bottom: .5rem; }
    .hf-empty.neutral i { color: #cbd5e1; }

    @media (max-width: 992px) {
        .hf thead { display: none; }
        .hf, .hf tbody, .hf tr, .hf td { display: block; width: 100%; }
        .hf tbody tr { background: #fff; border-radius: .9rem; margin-bottom: .6rem; overflow: hidden; }
        .hf tbody td, .hf tbody td:first-child, .hf tbody td:last-child { border: 0; border-radius: 0; padding: .35rem .9rem; }
        .hf tbody tr td:first-child { border-left: 5px solid #cbd5e1; padding-top: .7rem; }
        .hf tbody tr.urgent td:first-child { border-left-color: #dc2626; } .hf tbody tr.preventable td:first-child { border-left-color: #f59e0b; } .hf tbody tr.done td:first-child { border-left-color: #22c55e; }
        .hf-act { justify-content: flex-start; padding-bottom: .5rem; }
    }

    .fo-popup { border-radius: 1.1rem !important; padding: 1.3rem 1.4rem 1.2rem !important; }
    .fo-popup .swal2-html-container { margin: 0 !important; padding: 0 !important; overflow: visible; }
    .fo-popup .swal2-actions { margin: 1.1rem 0 0 !important; gap: .6rem; width: 100%; }
    .fo-opts { display: grid; gap: .5rem; grid-template-columns: repeat(3, 1fr); }
    .fo-opt { background: #fff; border: 2px solid #cbd5e1; border-radius: .8rem; color: #1E4F6F; font-weight: 700; padding: .6rem .3rem; text-align: center; }
    .fo-opt i { display: block; font-size: 1.3rem; margin-bottom: .15rem; }
    .fo-opt:hover { background: #f4f9fd; border-color: #26648E; }
    .fo-opt.on { background: #f0fdf4; border-color: #16a34a; color: #15803d; }
    .fo-lb { color: #334155; display: block; font-size: .85rem; font-weight: 700; margin-bottom: .3rem; }
    .fo-confirm { background: #15803d; border: 0; border-radius: .7rem; color: #fff; font-weight: 700; padding: .6rem 1.3rem; }
    .fo-cancel { background: #fff; border: 2px solid #cbd5e1; border-radius: .7rem; color: #475569; font-weight: 700; padding: .55rem 1.1rem; }
</style>

<main class="main-content">
    <div class="container-fluid px-4 hf-wrap">
        <div class="hf-hero">
            <span class="ic"><i class="bi bi-heart-pulse"></i></span>
            <div>
                <h2>ติดตามสุขภาพ</h2>
                <p>เรื่องสุขภาพที่ต้องติดตามกับผู้ปกครอง ทั้งศูนย์ — ดูว่าผู้ปกครองแจ้งกลับแล้วหรือยัง และบันทึกแทนผู้ปกครองได้</p>
            </div>
        </div>

        <?php if ($hfErrors && ($_SESSION['role'] ?? '') === 'admin'): ?>
            <div class="alert alert-danger">
                <b>โหลดข้อมูลบางส่วนไม่สำเร็จ</b> (แสดงเฉพาะผู้ดูแลระบบ)
                <?php foreach ($hfErrors as $k => $msg): ?><div class="small"><code><?= htmlspecialchars($k) ?></code>: <?= htmlspecialchars($msg) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="hf-stats" id="hfStats"></div>

        <div class="hf-card">
            <div class="hf-tabs" id="hfTabs"></div>
            <div class="hf-toolbar">
                <div><label for="hfSearch">ค้นหา</label>
                    <div class="hf-search"><i class="bi bi-search"></i><input id="hfSearch" type="text" class="form-control" placeholder="ชื่อ ชื่อเล่น หรือรหัสนักเรียน" autocomplete="off"></div></div>
                <div><label for="hfStatus">สถานะการตอบ</label>
                    <select id="hfStatus" class="form-select">
                        <option value="none">ยังไม่ตอบ</option><option value="all">ทั้งหมด</option><option value="acknowledged">รับทราบแล้ว</option>
                        <option value="scheduled">นัดหมอแล้ว</option><option value="treated">พาไปรักษาแล้ว</option>
                    </select></div>
                <div><label for="hfGroup">กลุ่มเรียน</label><select id="hfGroup" class="form-select"><option value="">ทุกกลุ่ม</option></select></div>
                <div><label for="hfRoom">ห้องเรียน</label><select id="hfRoom" class="form-select"><option value="">ทุกห้อง</option></select></div>
                <div><button type="button" class="btn btn-outline-secondary w-100" id="hfReset" title="ล้างตัวกรอง"><i class="bi bi-arrow-counterclockwise"></i></button></div>
            </div>
        </div>

        <div class="hf-count" id="hfCount"></div>
        <div id="hfTable"></div>
    </div>
</main>

<script>
    const ITEMS = <?= json_encode($items, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const TYPES = <?= json_encode($types, JSON_UNESCAPED_UNICODE) ?>;
    const API = '../include/function/health_followup_api.php';
    const STATUS_TEXT = { none: 'ยังไม่ตอบ', acknowledged: 'รับทราบแล้ว', scheduled: 'นัดหมอแล้ว', treated: 'พาไปรักษาแล้ว' };
    const STATUS_ICON = { none: 'bi-hourglass-split', acknowledged: 'bi-hand-thumbs-up', scheduled: 'bi-calendar-event', treated: 'bi-check2-circle' };
    const URG = { urgent: 'ควรรักษาโดยด่วน', preventable: 'ผัดผ่อนได้', not_urgent: 'ไม่เร่งด่วน' };
    const URG_RANK = { urgent: 0, preventable: 1, not_urgent: 2 };
    const AV_COLORS = ['#26648E', '#2f8f83', '#b97b36', '#687ba8', '#a94949', '#4b8c75', '#6d28d9', '#0e7490'];
    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const thaiDate = (d, time) => {
        if (!d) return '';
        const dt = new Date(String(d).replace(' ', 'T'));
        if (isNaN(dt)) return '';
        const s = dt.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
        return time ? s + ' ' + dt.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.' : s;
    };
    const st = (it) => it.status || 'none';
    const avColor = (id) => AV_COLORS[[...String(id)].reduce((a, c) => a + c.charCodeAt(0), 0) % AV_COLORS.length];
    let typeFilter = 'all';

    function matches(it, ignoreStatus) {
        if (typeFilter !== 'all' && it.source_type !== typeFilter) return false;
        if (byId('hfGroup').value && it.child_group !== byId('hfGroup').value) return false;
        if (byId('hfRoom').value && it.classroom !== byId('hfRoom').value) return false;
        const q = byId('hfSearch').value.trim().toLowerCase();
        if (q && !`${it.nickname || ''} ${it.name} ${it.student_id}`.toLowerCase().includes(q)) return false;
        if (!ignoreStatus && byId('hfStatus').value !== 'all' && st(it) !== byId('hfStatus').value) return false;
        return true;
    }

    function renderStats() {
        const base = ITEMS.filter((it) => matches(it, true));
        const c = { none: 0, acknowledged: 0, scheduled: 0, treated: 0 };
        base.forEach((it) => c[st(it)]++);
        const cur = byId('hfStatus').value;
        byId('hfStats').innerHTML = ['none', 'acknowledged', 'scheduled', 'treated'].map((k) =>
            `<button type="button" class="hf-stat ${k}${cur === k ? ' active' : ''}" data-k="${k}">
                <span class="ico"><i class="bi ${STATUS_ICON[k]}"></i></span>
                <span><span class="n d-block">${c[k]}</span><span class="l">${STATUS_TEXT[k]}</span></span></button>`).join('');
    }

    function renderTabs() {
        const keys = Object.keys(TYPES);
        const count = (k) => ITEMS.filter((it) => k === 'all' || it.source_type === k).length;
        byId('hfTabs').innerHTML = [['all', 'ทั้งหมด', 'bi bi-grid']].concat(keys.map((k) => [k, TYPES[k].label, TYPES[k].icon])).map(([k, l, ic]) =>
            `<button type="button" class="hf-tab${typeFilter === k ? ' on' : ''}" data-k="${k}"><i class="${esc(ic)} me-1"></i>${esc(l)}<span class="c">${count(k)}</span></button>`).join('');
    }

    function renderTable() {
        const list = ITEMS.filter((it) => matches(it, false)).sort((a, b) => {
            if (st(a) === 'none' && st(b) === 'none') return (URG_RANK[a.urgency] ?? 3) - (URG_RANK[b.urgency] ?? 3) || String(a.checked_at).localeCompare(String(b.checked_at));
            if (st(a) === 'none') return -1;
            if (st(b) === 'none') return 1;
            return String(b.replied_at).localeCompare(String(a.replied_at));
        });
        byId('hfCount').textContent = list.length ? `แสดง ${list.length} รายการ` : '';
        if (!list.length) {
            const pendingDone = ITEMS.length && byId('hfStatus').value === 'none' && !byId('hfSearch').value && !byId('hfGroup').value && !byId('hfRoom').value && typeFilter === 'all';
            byId('hfTable').innerHTML = pendingDone
                ? '<div class="hf-card hf-empty"><i class="bi bi-check-circle-fill"></i><div class="fw-bold">ผู้ปกครองตอบกลับครบทุกเรื่องแล้ว</div><div class="small">ไม่มีเรื่องที่ค้างตอบ</div></div>'
                : `<div class="hf-card hf-empty neutral"><i class="bi bi-inbox"></i><div class="fw-bold">${ITEMS.length ? 'ไม่พบรายการตามเงื่อนไข' : 'ยังไม่มีเรื่องสุขภาพที่ต้องติดตาม'}</div><div class="small">${ITEMS.length ? 'ลองเปลี่ยนสถานะหรือล้างตัวกรอง' : 'เมื่อทันตแพทย์ตรวจพบฟันผุ เรื่องจะขึ้นที่นี่'}</div></div>`;
            return;
        }
        byId('hfTable').innerHTML = `<table class="hf"><thead><tr><th>เด็ก</th><th>ประเภท / สิ่งที่พบ</th><th>ตรวจเมื่อ</th><th>การตอบกลับ</th><th>หมายเหตุ</th><th></th></tr></thead><tbody>` +
            list.map((it) => {
                const s = st(it);
                const when = it.status_date && s !== 'acknowledged' ? ' ' + thaiDate(it.status_date) : '';
                const who = it.by === 'center' ? 'ศูนย์บันทึก' : (it.by === 'parent' ? 'ผู้ปกครอง' : '');
                const rowCls = s === 'treated' ? 'done' : (s === 'none' && it.urgency === 'urgent' ? 'urgent' : (s === 'none' && it.urgency === 'preventable' ? 'preventable' : ''));
                return `<tr class="${rowCls}">
                    <td><div class="hf-who"><span class="hf-av" style="background:${avColor(it.student_id)}">${esc(String(it.nickname || it.name).replace(/^น้อง/, '').charAt(0) || '?')}</span>
                        <div><div class="nick">${esc(it.nickname || it.name)}</div>${it.nickname ? `<div class="full">${esc(it.name)}</div>` : ''}<span class="hf-room">ห้อง ${esc(it.classroom || '-')}</span></div></div></td>
                    <td><span class="hf-tag">${esc(it.type_label)}</span> <span class="hf-find">${esc(it.detail)}${it.count_text ? ` <span class="text-danger">${esc(it.count_text)}</span>` : ''}</span>
                        ${it.urgency ? `<div><span class="hf-urg ${it.urgency}">${URG[it.urgency] || ''}</span></div>` : ''}</td>
                    <td>${thaiDate(it.checked_at)}</td>
                    <td><span class="hf-chip ${s}"><i class="bi ${STATUS_ICON[s]}"></i>${STATUS_TEXT[s]}${when}</span>${s !== 'none' ? `<div class="hf-sub">${thaiDate(it.replied_at, true)}${who ? ' · ' + who : ''}</div>` : ''}</td>
                    <td class="hf-note">${esc(it.note || '')}</td>
                    <td><div class="hf-act"><button type="button" class="hf-btn${s === 'none' ? '' : ' edit'}" data-sid="${esc(it.source_id)}" data-type="${esc(it.source_type)}"><i class="bi ${s === 'none' ? 'bi-pencil-square' : 'bi-pencil'} me-1"></i>${s === 'none' ? 'บันทึกการติดตาม' : 'แก้ไข'}</button>
                        <a class="hf-link" href="${esc(it.link)}"><i class="bi bi-box-arrow-up-right me-1"></i>ดูผลตรวจ</a></div></td>
                </tr>`;
            }).join('') + '</tbody></table>';
    }

    function renderAll() { renderStats(); renderTabs(); renderTable(); }

    function fillFilters() {
        const groups = [...new Set(ITEMS.map((i) => i.child_group).filter(Boolean))].sort();
        byId('hfGroup').innerHTML = '<option value="">ทุกกลุ่ม</option>' + groups.map((g) => `<option>${esc(g)}</option>`).join('');
        fillRooms();
    }
    function fillRooms() {
        const g = byId('hfGroup').value;
        const rooms = [...new Set(ITEMS.filter((i) => !g || i.child_group === g).map((i) => i.classroom).filter(Boolean))].sort();
        byId('hfRoom').innerHTML = '<option value="">ทุกห้อง</option>' + rooms.map((r) => `<option>${esc(r)}</option>`).join('');
    }

    // บันทึก/แก้ไขการติดตามแทนผู้ปกครอง
    async function openFollowup(it) {
        let status = it.status || 'treated';
        const todayDate = new Date(); const today = `${todayDate.getFullYear()}-${String(todayDate.getMonth() + 1).padStart(2, '0')}-${String(todayDate.getDate()).padStart(2, '0')}`;
        const OPT = [['acknowledged', 'รับทราบ', 'bi-hand-thumbs-up'], ['scheduled', 'นัดหมอแล้ว', 'bi-calendar-event'], ['treated', 'พาไปรักษาแล้ว', 'bi-check2-circle']];
        const res = await Swal.fire({
            width: 520,
            html: `<div style="text-align:left">
                <div style="font-weight:700;font-size:1.15rem;color:#1E4F6F">บันทึกการติดตามแทนผู้ปกครอง</div>
                <div class="text-muted small mb-3">${esc(it.nickname || it.name)} · ${esc(it.type_label)} · ใช้เมื่อศูนย์ดำเนินการแทน เช่น ศูนย์พาไปรักษาเอง</div>
                <div class="fo-opts">${OPT.map(([k, t, ic]) => `<button type="button" class="fo-opt${k === status ? ' on' : ''}" data-k="${k}"><i class="bi ${ic}"></i>${t}</button>`).join('')}</div>
                <div id="foDateWrap" class="mt-3"><label class="fo-lb" for="foDate">วันที่</label>
                    <input id="foDate" type="date" class="form-control" value="${esc((it.status_date || today).slice(0, 10))}"></div>
                <div class="mt-3"><label class="fo-lb" for="foNote">หมายเหตุ <span class="text-muted fw-normal">(ไม่บังคับ)</span></label>
                    <textarea id="foNote" class="form-control" rows="3" maxlength="300" placeholder="เช่น ศูนย์พาไปโรงพยาบาล... อุดฟันเรียบร้อย">${esc(it.note || '')}</textarea></div></div>`,
            showCancelButton: true, buttonsStyling: false, reverseButtons: true,
            confirmButtonText: '<i class="bi bi-check-circle me-1"></i>บันทึก', cancelButtonText: 'ยกเลิก',
            customClass: { popup: 'fo-popup', confirmButton: 'fo-confirm', cancelButton: 'fo-cancel' },
            didOpen: () => {
                const sync = () => { byId('foDateWrap').style.display = status === 'acknowledged' ? 'none' : ''; };
                document.querySelectorAll('.fo-opt').forEach((b) => b.addEventListener('click', () => {
                    status = b.dataset.k;
                    document.querySelectorAll('.fo-opt').forEach((x) => x.classList.toggle('on', x === b));
                    sync();
                }));
                sync();
            },
            preConfirm: () => {
                const date = status === 'acknowledged' ? '' : byId('foDate').value;
                if (status !== 'acknowledged' && !date) { Swal.showValidationMessage('กรุณาระบุวันที่'); return false; }
                return { date, note: byId('foNote').value.trim() };
            }
        });
        if (!res.isConfirmed) return;
        try {
            const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ type: it.source_type, id: it.source_id, status, date: res.value.date, note: res.value.note }) });
            const data = await r.json();
            if (data.status !== 'success') throw new Error(data.message || 'บันทึกไม่สำเร็จ');
            Object.assign(it, { status, status_date: res.value.date || null, note: res.value.note || null, replied_at: new Date().toISOString(), by: 'center' });
            renderAll();
            Swal.fire({ icon: 'success', title: data.message, timer: 1400, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message, confirmButtonText: 'ตกลง' });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        fillFilters();
        // มาจากการ์ดบนแดชบอร์ด: ?status=none|acknowledged|scheduled|treated
        const qs = new URLSearchParams(location.search).get('status');
        if (qs && [...byId('hfStatus').options].some((o) => o.value === qs)) byId('hfStatus').value = qs;
        renderAll();
        byId('hfStats').addEventListener('click', (e) => { const b = e.target.closest('[data-k]'); if (b) { byId('hfStatus').value = b.dataset.k; renderAll(); } });
        byId('hfTabs').addEventListener('click', (e) => { const b = e.target.closest('[data-k]'); if (b) { typeFilter = b.dataset.k; renderAll(); } });
        byId('hfStatus').addEventListener('change', renderAll);
        byId('hfGroup').addEventListener('change', () => { fillRooms(); renderAll(); });
        byId('hfRoom').addEventListener('change', renderAll);
        byId('hfReset').addEventListener('click', () => { byId('hfSearch').value = ''; byId('hfGroup').value = ''; fillRooms(); byId('hfStatus').value = 'none'; typeFilter = 'all'; renderAll(); });
        let t; byId('hfSearch').addEventListener('input', () => { clearTimeout(t); t = setTimeout(renderAll, 250); });
        byId('hfTable').addEventListener('click', (e) => {
            const b = e.target.closest('.hf-btn'); if (!b) return;
            const it = ITEMS.find((x) => x.source_type === b.dataset.type && String(x.source_id) === b.dataset.sid);
            if (it) openFollowup(it);
        });
    });
</script>
</body>
</html>
