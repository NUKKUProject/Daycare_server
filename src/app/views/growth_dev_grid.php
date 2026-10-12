<?php
include __DIR__ . '/../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
include __DIR__ . '/partials/Header.php';
include __DIR__ . '/../include/auth/auth_navbar.php';
require_once __DIR__ . '/../include/function/pages_referen.php';
require_once __DIR__ . '/../include/function/child_functions.php';
require_once __DIR__ . '/../include/auth/auth_dashboard.php';

$groups = get_childgroup();
?>

<style>
    .tg-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); padding: .9rem 1.25rem; margin-bottom: .85rem; }
    .gs { display: inline-flex; width: 22px; height: 22px; border-radius: 50%; background: #1e4db7; color: #fff; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; }
    .page-title { font-size: 1.5rem; font-weight: 700; color: #0f2460; margin: 0; }
    .sec { display: grid; grid-template-columns: 210px minmax(0, 1fr); gap: 1.25rem; align-items: start; }
    @media (max-width: 992px) { .sec { grid-template-columns: 1fr; gap: .75rem; } }
    .sec-head { display: flex; gap: .65rem; align-items: flex-start; }
    .sec-head .gs { margin-top: 2px; }
    .sec-title { font-weight: 700; font-size: 1.02rem; color: #0f2460; line-height: 1.25; }
    .sec-sub { font-size: .8rem; color: #64748b; line-height: 1.3; }
    .fgrid { display: grid; gap: .75rem 1rem; }
    .fgrid.c4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .fgrid.c3 { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; }
    @media (max-width: 992px) { .fgrid.c4, .fgrid.c3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .fgrid.c4, .fgrid.c3 { grid-template-columns: 1fr; } }
    .fld > label { display: block; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .2rem; }
    .fld .help { min-height: 1.5rem; margin-top: .3rem; display: flex; flex-wrap: wrap; gap: .3rem; align-items: center; font-size: .75rem; color: #64748b; }
    .tg-divider { border: 0; border-top: 1px solid #e2e8f0; margin: .9rem 0; }

    .tg-progress { display: flex; align-items: center; gap: .75rem; margin-bottom: .6rem; flex-wrap: wrap; }
    .tg-progress .bar { flex: 1 1 200px; height: 10px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .tg-progress .bar > span { display: block; height: 100%; background: #22c55e; transition: width .25s ease; }
    .tg-legend { display: flex; flex-wrap: wrap; gap: .75rem; font-size: .75rem; color: #64748b; margin-bottom: .5rem; align-items: center; }
    .tg-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; }

    .tg-wrap { overflow: auto; max-height: 68vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    table.tg { border-collapse: separate; border-spacing: 0; width: 100%; font-size: .85rem; --h1: 28px; }
    .tg th { position: sticky; background: #f1f5f9; color: #475569; font-weight: 700; font-size: .75rem; padding: 5px 4px; border-bottom: 1px solid #cbd5e1; text-align: center; white-space: nowrap; z-index: 3; }
    .tg tr.h1 th { top: 0; height: var(--h1); font-size: .74rem; letter-spacing: .2px; border-bottom: 1px solid #fff; }
    .tg tr.h2 th { top: var(--h1); }
    .tg th.g-child { background: #e2e8f0; } .tg th.g-age { background: #dbeafe; }
    .tg th.g-d0 { background: #fde68a; } .tg th.g-d1 { background: #bbf7d0; } .tg th.g-d2 { background: #e9d5ff; }
    .tg th.g-d3 { background: #fecaca; } .tg th.g-d4 { background: #bae6fd; }
    .tg th.g-meas { background: #fef08a; } .tg td.gc-meas { background: #fefce8; }
    .tg input.n { width: 84px; min-width: 84px; text-align: center; }
    .tg th.g-sum { background: #fed7aa; } .tg th.g-state { background: #e2e8f0; }
    .tg td { padding: 3px; border-bottom: 1px solid #e2e8f0; text-align: center; background: #fff; }
    .tg .c-no { position: sticky; left: 0; z-index: 2; min-width: 36px; background: #fff; }
    .tg .c-nm { position: sticky; left: 36px; z-index: 2; min-width: 160px; text-align: left; padding: 3px 8px; background: #fff; border-right: 1px solid #cbd5e1; }
    .tg th.c-no, .tg th.c-nm { z-index: 5; }
    .tg .nick { font-weight: 700; color: #0f2460; line-height: 1.15; }
    .tg .full { font-size: .72rem; color: #64748b; line-height: 1.15; }
    .tg input.f, .tg select.f { height: 30px; font-size: .85rem; padding: 0 4px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; width: 100%; }
    .tg input.f:focus, .tg select.f:focus { outline: 2px solid #3b82f6; outline-offset: -1px; }
    .tg input.t { min-width: 150px; }
    .tg select.f { min-width: 84px; }
    .tg select.f.is-pass { background: #dcfce7; border-color: #16a34a; color: #15803d; font-weight: 700; }
    .tg select.f.is-delay { background: #fee2e2; border-color: #dc2626; color: #b91c1c; font-weight: 700; }
    .tg td.gc-child { background: #f1f5f9; } .tg td.gc-age { background: #eff6ff; }
    .tg td.gc-d0 { background: #fffbeb; } .tg td.gc-d1 { background: #f0fdf4; } .tg td.gc-d2 { background: #faf5ff; }
    .tg td.gc-d3 { background: #fef2f2; } .tg td.gc-d4 { background: #f0f9ff; }
    .tg td.gc-sum { background: #fff7ed; } .tg td.gc-state { background: #f8fafc; }
    /* แถวที่ยังไม่ได้กรอก = เทาอ่อนทั้งแถว */
    .tg tr.todo td { background: #fafafb !important; }
    /* แถวที่กรอกแล้วให้เห็นชัด: เขียว = บันทึกแล้ว  ฟ้า = พร้อมบันทึก  ส้มอ่อน = ติ๊กพัฒนาการไม่ครบ */
    .tg tr.saved td { background: #dcfce7 !important; }
    .tg tr.ready td { background: #dbeafe !important; }
    .tg tr.partial td { background: #ffedd5 !important; }
    .tg tr.saved input.f, .tg tr.ready input.f, .tg tr.partial input.f { background: #fff; }
    .tg tr.todo input.f, .tg tr.todo select.f { background: #fafafb; border-color: #eceef1; }
    .tg tbody tr:hover td { filter: brightness(.97); }
    .tg tr.saved .c-no, .tg tr.saved .c-nm { box-shadow: inset 5px 0 0 #22c55e; }
    .tg tr.ready .c-nm { box-shadow: inset 5px 0 0 #3b82f6; }
    .tg tr.partial .c-nm { box-shadow: inset 5px 0 0 #fb923c; }
    .tg tr.todo .c-nm { box-shadow: inset 5px 0 0 #cbd5e1; }
    .tg td.nw { white-space: nowrap; font-size: .8rem; }
    .tg td.rng { white-space: nowrap; font-size: .75rem; min-width: 190px; text-align: left; padding: 3px 8px; }
    .tg .st { font-size: .72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; white-space: nowrap; }
    .tg .st.saved { background: #dcfce7; color: #15803d; }
    .tg .st.ready { background: #dbeafe; color: #1d4ed8; }
    .tg .st.todo { background: #f1f5f9; color: #64748b; }
    .tg .st.partial { background: #ffedd5; color: #c2410c; }
    .tg .res { font-size: .72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; white-space: normal; display: inline-block; min-width: 150px; }
    .tg .res.normal { background: #dcfce7; color: #15803d; }
    .tg .res.delay { background: #fee2e2; color: #b91c1c; }
    .tg .cnt { font-weight: 700; font-size: .95rem; }
    .tg .rowact { border: 1.5px solid #16a34a; background: #f0fdf4; color: #15803d; border-radius: 8px; font-size: .8rem; font-weight: 700; padding: 5px 12px; cursor: pointer; white-space: nowrap; box-shadow: 0 1px 2px rgba(15, 36, 96, .12); }
    .tg .rowact:hover { background: #16a34a; color: #fff; }
    .tg .rowact:active { transform: scale(.96); }

    .tg-wrap.draggable { cursor: grab; }
    .tg-wrap.dragging { cursor: grabbing; user-select: none; }
    .tg-wrap.dragging * { cursor: grabbing !important; }
    .tg-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .tg-empty .big { font-size: 1.05rem; font-weight: 700; color: #334155; margin-bottom: .35rem; }
    .tg-hint { font-size: .78rem; color: #64748b; margin-top: .5rem; }

    .tg-dock { position: sticky; bottom: 0; z-index: 30; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 16px rgba(15, 36, 96, .08);
        border-radius: 12px 12px 0 0; padding: .6rem 1.25rem; margin: 0 -.25rem; display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .tg-dock .pills { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; font-size: .82rem; }
    .tg-pill { border-radius: 999px; padding: 2px 12px; font-weight: 700; font-size: .78rem; }
    .tg-pill.saved { background: #dcfce7; color: #15803d; } .tg-pill.ready { background: #dbeafe; color: #1d4ed8; }
    .tg-pill.todo { background: #f1f5f9; color: #64748b; } .tg-pill.partial { background: #ffedd5; color: #c2410c; }
    .tg-dock .acts { margin-left: auto; display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .btn-save-grid { background: #15803d; border-color: #15803d; color: #fff; font-weight: 700; min-width: 170px; }
    .btn-save-grid:hover:not(:disabled) { background: #166534; border-color: #166534; color: #fff; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h2 class="page-title">กรอกผลคัดกรองพัฒนาการทั้งห้อง</h2>
            <a href="growth_history.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>กลับหน้าประวัติการเจริญเติบโต</a>
        </div>

        <div class="tg-card">
            <!-- ส่วนที่ 1: ข้อมูลการประเมิน -->
            <div class="sec">
                <div class="sec-head">
                    <span class="gs">1</span>
                    <div>
                        <div class="sec-title">ข้อมูลการประเมิน</div>
                        <div class="sec-sub">ตั้งครั้งเดียว ใช้กับเด็กทุกคนที่บันทึก</div>
                    </div>
                </div>
                <div class="fgrid c4">
                    <div class="fld">
                        <label for="gDate">วันที่ประเมิน</label>
                        <input type="date" id="gDate" class="form-control">
                        <div class="help">อายุ ช่วงอายุเกณฑ์ และวันประเมินซ้ำคำนวณจากวันนี้</div>
                    </div>
                </div>
            </div>

            <hr class="tg-divider">

            <!-- ส่วนที่ 2: เลือกเด็ก -->
            <div class="sec">
                <div class="sec-head">
                    <span class="gs">2</span>
                    <div>
                        <div class="sec-title">เลือกเด็ก</div>
                        <div class="sec-sub">เลือกกลุ่มและห้อง รายชื่อทั้งห้องขึ้นเอง</div>
                    </div>
                </div>
                <div class="fgrid c3">
                    <div class="fld">
                        <label for="gGroup">กลุ่มเรียน</label>
                        <select id="gGroup" class="form-select">
                            <option value="">-- เลือก --</option>
                            <?php foreach ($groups as $g): if (!empty($g['child_group'])): ?>
                                <option value="<?= htmlspecialchars($g['child_group']) ?>"><?= htmlspecialchars($g['child_group']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="fld">
                        <label for="gRoom">ห้องเรียน</label>
                        <select id="gRoom" class="form-select"><option value="">-- เลือก --</option></select>
                    </div>
                    <div class="fld">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-outline-primary w-100" id="gLoad" title="โหลดรายชื่อใหม่"><i class="bi bi-arrow-clockwise"></i> โหลดใหม่</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3) ตาราง -->
        <div class="tg-card">
            <div class="sec-head mb-2">
                <span class="gs">3</span>
                <div>
                    <div class="sec-title">กรอกผลคัดกรอง</div>
                    <div class="sec-sub">เลือกผ่าน/ไม่ผ่านทั้ง 5 ด้านในตาราง แล้วกด "บันทึกทั้งห้อง" ที่แถบด้านล่าง</div>
                </div>
            </div>
            <div class="tg-progress" id="gProgress" style="display:none">
                <span id="gProgressText" class="fw-bold"></span>
                <div class="bar"><span id="gProgressBar" style="width:0%"></span></div>
            </div>
            <div class="tg-legend" id="gLegend" style="display:none">
                <span><i style="background:#22c55e"></i>บันทึกแล้ว</span><span><i style="background:#3b82f6"></i>พร้อมบันทึก (ครบ 5 ด้าน)</span>
                <span><i style="background:#fb923c"></i>กรอกยังไม่ครบ (ยังบันทึกไม่ได้)</span><span><i style="background:#cbd5e1"></i>ยังไม่ได้กรอก</span>
            </div>
            <div class="tg-wrap" id="gWrap">
                <div class="tg-empty">
                    <div class="big">เลือกกลุ่มเรียนและห้องเรียนด้านบน</div>
                    รายชื่อเด็กจะขึ้นให้อัตโนมัติ แล้วกรอกต่อในตารางได้เลย
                </div>
            </div>
            <div class="tg-hint">
                Tab ไปช่องถัดไป · Enter ลงแถวถัดไป · ช่วงอายุเกณฑ์เลือกจาก 0-2 ปี หรือ 2-5 ปี (ตั้งให้ตามอายุเด็ก แก้เองได้) · กรอกน้ำหนัก ส่วนสูง เส้นรอบศีรษะ ข้อที่ และรายละเอียดเพิ่มเติมได้ · พัฒนาการต้องเลือกผ่าน/ไม่ผ่านให้ครบ 5 ด้าน (หรือไม่เลือกเลยถ้าบันทึกเฉพาะการเจริญเติบโต) · ปุ่ม "ผ่านครบ" ติ๊กผ่านทั้ง 5 ด้านให้ · ผลคัดกรองและวันประเมินซ้ำ (+30 วัน เมื่อมีด้านไม่ผ่าน) ระบบคำนวณให้เอง
            </div>
        </div>

        <div class="tg-dock" id="gDock">
            <div class="pills" id="gSummary"><span class="text-muted">ยังไม่ได้โหลดรายชื่อ</span></div>
            <div class="acts">
                <button type="button" class="btn btn-outline-primary btn-sm" id="gFillPass" disabled title="ติ๊กผ่านครบ 5 ด้านให้เด็กที่ยังไม่มีข้อมูล"><i class="bi bi-magic me-1"></i>ผ่านครบให้ที่ยังว่าง</button>
                <button type="button" class="btn btn-save-grid" id="gSave" disabled><i class="bi bi-check-circle me-1"></i>บันทึกทั้งห้อง</button>
            </div>
        </div>
    </div>
</main>

<script>
    const API_LIST = '../include/function/get_dev_screening_class.php';
    const API_SAVE = '../include/process/save_dev_screening_bulk.php';
    const XHR = { 'X-Requested-With': 'XMLHttpRequest' };
    const DOMAINS = [
        ['gm', 'การเคลื่อนไหว', 'Gross Motor (GM)'],
        ['fm', 'กล้ามเนื้อมัดเล็กและสติปัญญา', 'Fine Motor (FM)'],
        ['rl', 'การเข้าใจภาษา', 'Receptive Language (RL)'],
        ['el', 'การใช้ภาษา', 'Expressive Language (EL)'],
        ['ps', 'การช่วยเหลือตัวเองและสังคม', 'Personal and Social (PS)']
    ];
    const RESULT_NORMAL = 'สมวัย (ผ่านครบ 5 ด้าน)';
    const RESULT_DELAY = 'สงสัยล่าช้ากระตุ้นพัฒนาการภายใน 14-30 วัน';
    const STATE_TEXT = { saved: 'บันทึกแล้ว', ready: 'พร้อมบันทึก', partial: 'ติ๊กพัฒนาการยังไม่ครบ 5 ด้าน', todo: 'ยังไม่ได้กรอก' };

    const PRE = Object.fromEntries(new URLSearchParams(location.search));
    let rows = [];
    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

    function todayStr() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }
    const thaiShort = (d) => (d ? new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) : '-');

    // อายุเต็ม ปี/เดือน/วัน ตรงกับฝั่งเซิร์ฟเวอร์ (DateTime::diff)
    function ageOf(birthday) {
        const exam = byId('gDate').value;
        if (!birthday || !exam) return null;
        const b = new Date(birthday + 'T00:00:00'), a = new Date(exam + 'T00:00:00');
        if (b > a) return null;
        let y = a.getFullYear() - b.getFullYear(), m = a.getMonth() - b.getMonth(), d = a.getDate() - b.getDate();
        if (d < 0) { m--; d += new Date(a.getFullYear(), a.getMonth(), 0).getDate(); }
        if (m < 0) { y--; m += 12; }
        return { y, m, d, months: y * 12 + m };
    }
    // ช่วงอายุเกณฑ์: ตัวเลือกเดิมของหน้าบันทึกกราฟการเจริญเติบโต (เริ่มต้นตามอายุเด็ก)
    const RANGES = [['0-2', '0-2 ปี'], ['2-5', '2-5 ปี']];
    const defaultRange = (months) => (months < 24 ? '0-2' : '2-5');
    function plus30(iso) {
        const d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + 30);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    const nv = (v) => (v === null || v === undefined ? '' : String(v));
    function toRow(c) {
        const age = ageOf(c.birthday);
        const r = {
            sid: c.studentid, nick: c.nickname || '', name: [c.prefix_th, c.firstname_th, c.lastname_th].filter(Boolean).join(' '),
            birthday: c.birthday, st: {}, note: {}, issue: {}, dirty: false,
            weight: nv(c.weight), height: nv(c.height), head: nv(c.head_circumference),
            range: RANGES.some(([v]) => v === c.age_range) ? c.age_range : (age ? defaultRange(age.months) : '0-2'),
            saved: c.growth_id !== null && c.growth_id !== undefined,
            noBirth: !c.birthday, rangeTouched: false
        };
        DOMAINS.forEach(([k]) => { r.st[k] = c[k + '_status'] || ''; r.note[k] = c[k + '_note'] || ''; r.issue[k] = nv(c[k + '_issue']); });
        return r;
    }
    const ticked = (r) => DOMAINS.filter(([k]) => r.st[k]).length;
    const passN = (r) => DOMAINS.filter(([k]) => r.st[k] === 'pass').length;
    const failN = (r) => DOMAINS.filter(([k]) => r.st[k] === 'delay').length;
    const complete = (r) => ticked(r) === DOMAINS.length;
    const hasMeasure = (r) => r.weight !== '' || r.height !== '' || r.head !== '';
    // พัฒนาการต้องติ๊กครบ 5 ด้านหรือไม่ติ๊กเลย  แถวที่แก้แล้วบันทึกได้เมื่อครบ 5 ด้าน หรือไม่ติ๊กเลยแต่มีน้ำหนัก/ส่วนสูง/รอบศีรษะ
    const isReady = (r) => r.dirty && !!ageOf(r.birthday) && (complete(r) || (ticked(r) === 0 && hasMeasure(r)));
    const rowState = (r) => (r.dirty
        ? (isReady(r) ? 'ready' : (ticked(r) ? 'partial' : 'todo'))
        : (r.saved ? 'saved' : 'todo'));

    function header() {
        return '<thead><tr class="h1">' +
            '<th class="g-child" colspan="3">เด็ก</th><th class="g-age" colspan="3">อายุ</th><th class="g-meas" colspan="3">การเจริญเติบโต</th>' +
            DOMAINS.map((d, i) => `<th class="g-d${i}" colspan="3" title="${esc(d[2])}">${esc(d[1])} (${d[0].toUpperCase()})</th>`).join('') +
            '<th class="g-sum" colspan="4">สรุป</th><th class="g-state" colspan="2">สถานะ</th></tr>' +
            '<tr class="h2"><th class="c-no">#</th><th class="c-nm">ชื่อ</th><th>วันเดือนปีเกิด</th>' +
            '<th>อายุ ณ วันที่ตรวจ</th><th>อายุ (เดือน)</th><th>ช่วงอายุเกณฑ์ที่ใช้</th>' +
            '<th>น้ำหนัก (กก.)</th><th>ส่วนสูง (ซม.)</th><th>เส้นรอบศีรษะ (ซม.)</th>' +
            DOMAINS.map(() => '<th>ผล</th><th>ข้อที่</th><th>รายละเอียดเพิ่มเติม</th>').join('') +
            '<th>ผ่าน (ด้าน)</th><th>ไม่ผ่าน (ด้าน)</th><th>ผลการคัดกรอง</th><th>ประเมินซ้ำภายใน 1 เดือน</th>' +
            '<th>สถานะ</th><th></th></tr></thead>';
    }

    function summaryCells(r) {
        const t = ticked(r), f = failN(r);
        const res = !complete(r) ? '<span class="text-muted small">-</span>'
            : (f === 0 ? `<span class="res normal">${RESULT_NORMAL}</span>` : `<span class="res delay">${RESULT_DELAY}</span>`);
        const re = complete(r) && f > 0 ? thaiShort(plus30(byId('gDate').value)) : '-';
        return { pass: t ? passN(r) : '-', fail: t ? f : '-', res, re };
    }

    function rowHtml(r, i) {
        const st = rowState(r), age = ageOf(r.birthday), s = summaryCells(r);
        const td = (cls, inner) => `<td class="${cls}">${inner}</td>`;
        const meas = (m, v, label) => td('gc-meas', `<input class="f n" type="number" min="0" step="0.1" data-m="${m}" value="${esc(v)}" aria-label="${label}">`);
        let h = `<tr class="${st}" data-i="${i}">` +
            `<td class="c-no gc-child">${i + 1}</td>` +
            `<td class="c-nm gc-child"><div class="nick">${r.nick ? esc(r.nick) : esc(r.name)}</div>${r.nick ? `<div class="full">${esc(r.name)}</div>` : ''}</td>` +
            td('gc-child nw', r.noBirth ? `<input class="f" type="date" data-b="1" max="${byId('gDate').value}" value="${esc(r.birthday || '')}" title="ยังไม่ได้ระบุวันเกิด เลือกวันเกิดได้" aria-label="วันเกิด" style="min-width:130px">` : esc(thaiShort(r.birthday))) +
            td('gc-age nw c-age', age ? `${age.y} ปี ${age.m} ด. ${age.d} ว.` : '<span class="text-danger">ยังไม่ระบุวันเกิด</span>') +
            td('gc-age c-mon', age ? age.months : '-') +
            td('gc-age', `<select class="f" data-r="1" aria-label="ช่วงอายุเกณฑ์ที่ใช้">${RANGES.map(([v, t]) => `<option value="${v}"${r.range === v ? ' selected' : ''}>${t}</option>`).join('')}</select>`) +
            meas('weight', r.weight, 'น้ำหนัก') + meas('height', r.height, 'ส่วนสูง') + meas('head', r.head, 'เส้นรอบศีรษะ');
        DOMAINS.forEach(([k], di) => {
            const v = r.st[k];
            h += td('gc-d' + di, `<select class="f ${v === 'pass' ? 'is-pass' : v === 'delay' ? 'is-delay' : ''}" data-k="${k}" aria-label="${esc(DOMAINS[di][1])}">` +
                `<option value="">-</option><option value="pass"${v === 'pass' ? ' selected' : ''}>ผ่าน</option><option value="delay"${v === 'delay' ? ' selected' : ''}>ไม่ผ่าน</option></select>`) +
                td('gc-d' + di, `<input class="f n" type="number" min="1" data-iss="${k}" value="${esc(r.issue[k])}" aria-label="ข้อที่">`) +
                td('gc-d' + di, `<input class="f t" type="text" maxlength="200" data-n="${k}" value="${esc(r.note[k])}" placeholder="รายละเอียดเพิ่มเติม">`);
        });
        h += td('gc-sum cnt c-pass', s.pass) + td('gc-sum cnt c-fail', s.fail) + td('gc-sum c-res', s.res) + td('gc-sum nw c-re', s.re) +
            td('gc-state', `<span class="st ${st}">${STATE_TEXT[st]}</span>`) +
            td('gc-state', '<button type="button" class="rowact" data-allpass="1" title="ติ๊กผ่านทั้ง 5 ด้าน">ผ่านครบ</button>') + '</tr>';
        return h;
    }

    function render() {
        const wrap = byId('gWrap');
        if (!rows.length) { wrap.innerHTML = '<div class="tg-empty"><div class="big">ไม่พบรายชื่อเด็กตามเงื่อนไข</div>ลองเปลี่ยนกลุ่มหรือห้อง</div>'; summary(); return; }
        const sc = { top: wrap.scrollTop, left: wrap.scrollLeft };
        wrap.innerHTML = '<table class="tg">' + header() + '<tbody>' + rows.map(rowHtml).join('') + '</tbody></table>';
        wrap.scrollTop = sc.top; wrap.scrollLeft = sc.left;
        summary();
    }

    // อัปเดตเฉพาะแถวที่แก้ (ไม่วาดตารางใหม่ เพื่อไม่ให้เสียโฟกัส)
    function refreshRow(i) {
        const tr = byId('gWrap').querySelector(`tr[data-i="${i}"]`);
        if (!tr) return;
        const r = rows[i], st = rowState(r), s = summaryCells(r);
        tr.className = st;
        tr.querySelector('.c-pass').textContent = s.pass; tr.querySelector('.c-fail').textContent = s.fail;
        tr.querySelector('.c-res').innerHTML = s.res; tr.querySelector('.c-re').textContent = s.re;
        const ag = ageOf(r.birthday);
        tr.querySelector('.c-age').innerHTML = ag ? `${ag.y} ปี ${ag.m} ด. ${ag.d} ว.` : '<span class="text-danger">ยังไม่ระบุวันเกิด</span>';
        tr.querySelector('.c-mon').textContent = ag ? ag.months : '-';
        const rs = tr.querySelector('select[data-r]'); if (rs) rs.value = r.range;
        const chip = tr.querySelector('.st'); chip.className = 'st ' + st; chip.textContent = STATE_TEXT[st];
        tr.querySelectorAll('select[data-k]').forEach((sel) => {
            sel.value = r.st[sel.dataset.k];
            sel.className = 'f ' + (sel.value === 'pass' ? 'is-pass' : sel.value === 'delay' ? 'is-delay' : '');
        });
        summary();
    }

    function summary() {
        const c = { saved: 0, ready: 0, partial: 0, todo: 0 };
        rows.forEach((r) => c[rowState(r)]++);
        byId('gSummary').innerHTML = rows.length
            ? `<span>${rows.length} คน</span><span class="tg-pill saved">บันทึกแล้ว ${c.saved}</span><span class="tg-pill ready">พร้อมบันทึก ${c.ready}</span>` +
              (c.partial ? `<span class="tg-pill partial">พัฒนาการยังไม่ครบ 5 ด้าน ${c.partial}</span>` : '') +
              `<span class="tg-pill todo">ยังไม่ได้กรอก ${c.todo}</span>`
            : '<span class="text-muted">ยังไม่ได้โหลดรายชื่อ</span>';
        byId('gProgress').style.display = byId('gLegend').style.display = rows.length ? '' : 'none';
        if (rows.length) {
            byId('gProgressText').textContent = `บันทึกแล้ว ${c.saved} / ${rows.length} คน`;
            byId('gProgressBar').style.width = Math.round(c.saved * 100 / rows.length) + '%';
        }
        const ready = rows.filter(isReady).length;
        const btn = byId('gSave');
        btn.disabled = !ready;
        btn.innerHTML = `<i class="bi bi-check-circle me-1"></i>บันทึกทั้งห้อง${ready ? ` (${ready} คน)` : ''}`;
        byId('gFillPass').disabled = !rows.length;
        window.__dirty = rows.some((r) => r.dirty);
    }

    async function confirmDiscard() {
        if (!window.__dirty) return true;
        const r = await Swal.fire({ icon: 'warning', title: 'มีข้อมูลที่ยังไม่บันทึก', text: 'โหลดรายชื่อใหม่จะทิ้งข้อมูลที่กรอกค้างไว้', showCancelButton: true, confirmButtonText: 'โหลดใหม่', cancelButtonText: 'กลับไปกรอกต่อ' });
        return r.isConfirmed;
    }

    // userClick = กดปุ่ม "โหลดใหม่" เอง (ถ้ายังไม่เลือกเงื่อนไข จะเตือน) นอกนั้นโหลดอัตโนมัติเมื่อเปลี่ยนตัวกรอง
    async function loadList(userClick) {
        const group = byId('gGroup').value, room = byId('gRoom').value, date = byId('gDate').value;
        if (!date) { if (userClick) Swal.fire({ icon: 'info', title: 'กรุณาเลือกวันที่ประเมิน', confirmButtonText: 'ตกลง' }); return; }
        if (!group || !room) {
            if (userClick) Swal.fire({ icon: 'info', title: 'เลือกกลุ่มเรียนและห้องเรียนก่อน', confirmButtonText: 'ตกลง' });
            return;
        }
        if (!(await confirmDiscard())) return;
        byId('gWrap').innerHTML = '<div class="tg-empty"><div class="spinner-border text-primary"></div></div>';
        try {
            const q = new URLSearchParams({ child_group: group, classroom: room, date: date });
            const res = await fetch(`${API_LIST}?${q}`, { headers: XHR });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'โหลดไม่สำเร็จ');
            rows = data.students.map(toRow);
            render();
        } catch (e) {
            rows = []; byId('gWrap').innerHTML = `<div class="tg-empty"><div class="big">โหลดไม่สำเร็จ</div>${esc(e.message)}</div>`; summary();
        }
    }

    async function saveAll() {
        const ready = rows.filter(isReady);
        const partial = rows.filter((r) => r.dirty && !isReady(r) && ticked(r)).length;
        if (!ready.length) { Swal.fire({ icon: 'warning', title: 'ยังไม่มีข้อมูลให้บันทึก', text: 'กรอกน้ำหนัก/ส่วนสูง/รอบศีรษะ หรือเลือกผ่าน/ไม่ผ่านให้ครบทั้ง 5 ด้านในแถวที่ต้องการบันทึก', confirmButtonText: 'ตกลง' }); return; }
        const ok = await Swal.fire({
            title: `บันทึก ${ready.length} คน?`,
            html: `<div>วันที่ประเมิน ${esc(thaiShort(byId('gDate').value))}</div>` +
                (partial ? `<div class="text-muted small mt-1">ข้าม ${partial} คนที่ติ๊กพัฒนาการไม่ครบ 5 ด้าน</div>` : ''),
            showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#15803d'
        });
        if (!ok.isConfirmed) return;
        const btn = byId('gSave'); btn.disabled = true;
        try {
            const body = {
                assess_date: byId('gDate').value,
                rows: ready.map((r) => {
                    const o = { student_id: r.sid, birthday: r.noBirth ? r.birthday : null, age_range: r.range, weight: r.weight, height: r.height, head_circumference: r.head };
                    DOMAINS.forEach(([k]) => { o[k] = { status: r.st[k], note: r.note[k], issue: r.issue[k] }; });
                    return o;
                })
            };
            const res = await fetch(API_SAVE, { method: 'POST', headers: { 'Content-Type': 'application/json', ...XHR }, body: JSON.stringify(body) });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'บันทึกไม่สำเร็จ');
            const errors = data.errors || [];
            const failed = new Set(errors.map((e) => String(e).split(':')[0]));
            ready.forEach((r) => { if (!failed.has(r.sid)) { r.saved = true; r.dirty = false; } });
            render();
            Swal.fire({ icon: errors.length ? 'warning' : 'success', title: `บันทึกสำเร็จ ${data.saved} คน`, timer: errors.length ? undefined : 1800, showConfirmButton: !!errors.length, confirmButtonText: 'ตกลง',
                text: errors.length ? errors.join('\n') : '' });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message, confirmButtonText: 'ตกลง' });
        } finally { summary(); }
    }

    function enableDragScroll(el) {
        el.classList.add('draggable');
        let down = false, sx = 0, sy = 0, sl = 0, st = 0;
        el.addEventListener('mousedown', (e) => {
            if (e.button !== 0 || e.target.closest('input, select, textarea, button, a, label')) return;
            down = true; sx = e.clientX; sy = e.clientY; sl = el.scrollLeft; st = el.scrollTop;
            el.classList.add('dragging');
        });
        window.addEventListener('mousemove', (e) => { if (!down) return; el.scrollLeft = sl - (e.clientX - sx); el.scrollTop = st - (e.clientY - sy); });
        window.addEventListener('mouseup', () => { if (down) { down = false; el.classList.remove('dragging'); } });
    }

    function loadRooms() {
        const group = byId('gGroup').value, sel = byId('gRoom');
        sel.innerHTML = '<option value="">-- เลือก --</option>';
        if (!group) return Promise.resolve();
        return fetch(`../include/function/get_classrooms.php?child_group=${encodeURIComponent(group)}`)
            .then((r) => r.json())
            .then((d) => { [...new Set((Array.isArray(d) ? d : []).map((c) => c.classroom_name))].forEach((n) => { const o = document.createElement('option'); o.value = o.textContent = n; sel.appendChild(o); }); })
            .catch(console.error);
    }

    function init() {
        const wrap = byId('gWrap');
        enableDragScroll(wrap);
        byId('gDate').value = PRE.date || todayStr();
        byId('gGroup').addEventListener('change', async () => { await loadRooms(); loadList(false); });
        byId('gRoom').addEventListener('change', () => loadList(false));
        byId('gLoad').addEventListener('click', () => loadList(true));
        byId('gSave').addEventListener('click', saveAll);
        // เปลี่ยนวันที่ประเมิน = โหลดใหม่ (ผลที่บันทึกไว้ของวันนั้นและอายุเปลี่ยนตาม)
        byId('gDate').addEventListener('change', () => { if (rows.length) loadList(false); });
        byId('gFillPass').addEventListener('click', () => {
            rows.forEach((r) => { if (!r.dirty && !ticked(r)) { DOMAINS.forEach(([k]) => { r.st[k] = 'pass'; }); r.dirty = true; } });
            render();
        });
        wrap.addEventListener('change', (e) => {
            const el = e.target, tr = el.closest('tr[data-i]');
            if (!tr || !(el.dataset.k || el.dataset.r || el.dataset.b)) return;
            const r = rows[+tr.dataset.i];
            if (el.dataset.b) {
                r.birthday = el.value;
                const ag = ageOf(r.birthday);
                if (ag && !r.rangeTouched) r.range = defaultRange(ag.months);
            } else if (el.dataset.r) { r.range = el.value; r.rangeTouched = true; } else r.st[el.dataset.k] = el.value;
            r.dirty = true;
            refreshRow(+tr.dataset.i);
        });
        wrap.addEventListener('input', (e) => {
            const el = e.target, tr = el.closest('tr[data-i]');
            if (!tr || !(el.dataset.n || el.dataset.m || el.dataset.iss)) return;
            const r = rows[+tr.dataset.i];
            if (el.dataset.n) r.note[el.dataset.n] = el.value;
            else if (el.dataset.iss) r.issue[el.dataset.iss] = el.value;
            else r[el.dataset.m] = el.value;
            r.dirty = true;
            refreshRow(+tr.dataset.i);
        });
        wrap.addEventListener('click', (e) => {
            const b = e.target.closest('button'); if (!b || !b.dataset.allpass) return;
            const r = rows[+b.closest('tr').dataset.i];
            DOMAINS.forEach(([k]) => { r.st[k] = 'pass'; }); r.dirty = true;
            refreshRow(+b.closest('tr').dataset.i);
        });
        wrap.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' || e.target.tagName !== 'INPUT') return;
            e.preventDefault();
            const td = e.target.closest('td'), tr = td.parentElement, idx = [...tr.children].indexOf(td);
            const next = tr.nextElementSibling && tr.nextElementSibling.children[idx]?.querySelector('input:not([disabled])');
            if (next) { next.focus(); next.select(); }
        });
        window.addEventListener('beforeunload', (e) => { if (window.__dirty) { e.preventDefault(); e.returnValue = ''; } });
        applyPreselect();
    }

    // มาจากหน้าประวัติการเจริญเติบโต: ใส่กลุ่ม/ห้องที่เลือกไว้ให้ แล้วโหลดรายชื่อต่อเลย
    async function applyPreselect() {
        if (PRE.child_group) { byId('gGroup').value = PRE.child_group; await loadRooms(); }
        if (PRE.classroom) byId('gRoom').value = PRE.classroom;
        loadList(false);
    }

    document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
