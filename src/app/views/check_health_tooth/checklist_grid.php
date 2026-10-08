<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/function/child_functions.php';
require_once __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';
require_once __DIR__ . '/function/tooth_exam_helpers.php';

$academicYears = getAcademicYears();
$groups = get_childgroup();
$defaultDoctor = getFullName();
$allowedTypes = tooth_allowed_types($_SESSION['role'] ?? '');
$typeLabels = ['teacher' => 'ครูคัดกรอง', 'doctor' => 'แพทย์ตรวจ'];
?>

<style>
    .tg-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); padding: .9rem 1.25rem; margin-bottom: .85rem; }
    .tg-card label, .tg-ctx label { font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .15rem; }
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
    .fgrid.c3 { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.6fr) auto; }
    @media (max-width: 992px) { .fgrid.c4, .fgrid.c3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .fgrid.c4, .fgrid.c3 { grid-template-columns: 1fr; } }
    .fld > label { display: block; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .2rem; }
    .fld .help { min-height: 1.5rem; margin-top: .3rem; display: flex; flex-wrap: wrap; gap: .3rem; align-items: center; font-size: .75rem; color: #64748b; }
    .fld .as-field { height: 38px; display: flex; align-items: center; gap: .4rem; }
    .ctx-title { font-size: .75rem; font-weight: 700; color: #64748b; margin-bottom: .35rem; }
    .tg-divider { border: 0; border-top: 1px solid #e2e8f0; margin: .9rem 0; }
    .chip { border-radius: 999px; padding: 2px 10px; font-weight: 700; font-size: .78rem; background: #f1f5f9; color: #475569; white-space: nowrap; }
    .chip.open { background: #dcfce7; color: #15803d; }
    .chip.closed { background: #fee2e2; color: #b91c1c; }
    .chip.teacher { background: #fef3c7; color: #b45309; }
    .chip.doctor { background: #dbeafe; color: #1d4ed8; }
    .tg-closed { background: #fee2e2; color: #b91c1c; border-radius: 8px; padding: .5rem .9rem; font-weight: 700; margin-bottom: .6rem; }

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
    .tg th.g-child { background: #e2e8f0; } .tg th.g-count { background: #dbeafe; } .tg th.g-pos { background: #fde68a; }
    .tg th.g-result { background: #fecaca; } .tg th.g-treat { background: #bbf7d0; } .tg th.g-note { background: #e9d5ff; } .tg th.g-state { background: #e2e8f0; }
    .tg td { padding: 3px; border-bottom: 1px solid #e2e8f0; text-align: center; background: #fff; }
    .tg .c-no { position: sticky; left: 0; z-index: 2; min-width: 36px; background: #fff; }
    .tg .c-nm { position: sticky; left: 36px; z-index: 2; min-width: 160px; text-align: left; padding: 3px 8px; background: #fff; border-right: 1px solid #cbd5e1; }
    .tg th.c-no, .tg th.c-nm { z-index: 5; }
    .tg .nick { font-weight: 700; color: #0f2460; line-height: 1.15; }
    .tg .full { font-size: .72rem; color: #64748b; line-height: 1.15; }
    .tg input.f, .tg select.f { height: 30px; font-size: .85rem; padding: 0 4px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; width: 100%; }
    .tg input.f:focus, .tg select.f:focus { outline: 2px solid #3b82f6; outline-offset: -1px; }
    .tg input.f:disabled, .tg select.f:disabled { background: #f1f5f9; color: #64748b; }
    .tg input.n { width: 46px; text-align: center; }
    .tg input.t { min-width: 120px; }
    .tg select.f { min-width: 92px; }
    .tg td.pos.bad input { background: #fef3c7; border-color: #f59e0b; }
    .tg .diff { background: #fef9c3 !important; border-color: #eab308 !important; }
    .tg tr.saved td { background: #f0fdf4; }
    .tg tr.ready td { background: #eff6ff; }
    .tg tr.prefill td { background: #faf5ff; }
    .tg tr.saved .c-no, .tg tr.saved .c-nm { background: #f0fdf4; box-shadow: inset 4px 0 0 #22c55e; }
    .tg tr.ready .c-nm { background: #eff6ff; box-shadow: inset 4px 0 0 #3b82f6; }
    .tg tr.prefill .c-no, .tg tr.prefill .c-nm { background: #faf5ff; box-shadow: inset 4px 0 0 #8b5cf6; }
    .tg tr.todo .c-nm { box-shadow: inset 4px 0 0 #f59e0b; }
    .tg th.th-treat { white-space: normal; min-width: 92px; max-width: 120px; line-height: 1.25; }
    .tg .tgl { display: block; width: 100%; height: 32px; border: 1px solid #94a3b8; border-radius: 6px; background: #fff; cursor: pointer; padding: 0; line-height: 1; font-size: 1rem; }
    .tg .tgl:hover:not(:disabled) { border-color: #3b82f6; background: #f0f7ff; }
    .tg .tgl.on { background: #dbeafe; border-color: #3b82f6; color: #1d4ed8; font-weight: 700; }
    .tg .tgl:disabled { cursor: default; opacity: .6; }
    .tg .st { font-size: .72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; white-space: nowrap; }
    .tg .st.saved { background: #dcfce7; color: #15803d; }
    .tg .st.ready { background: #dbeafe; color: #1d4ed8; }
    .tg .st.todo { background: #fef3c7; color: #b45309; }
    .tg .st.prefill { background: #ede9fe; color: #6d28d9; }
    .tg .ex { font-size: .72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; white-space: nowrap; background: #f1f5f9; color: #64748b; }
    .tg .ex.doctor { background: #dcfce7; color: #15803d; }
    .tg .ex.teacher { background: #fef3c7; color: #b45309; }
    .tg .rowact { border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; font-size: .72rem; padding: 2px 8px; cursor: pointer; white-space: nowrap; }
    .tg .rowact:hover { background: #f1f5f9; }
    .tg-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .tg-empty .big { font-size: 1.05rem; font-weight: 700; color: #334155; margin-bottom: .35rem; }
    .tg-hint { font-size: .78rem; color: #64748b; margin-top: .5rem; }

    .tg-dock { position: sticky; bottom: 0; z-index: 30; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 16px rgba(15, 36, 96, .08);
        border-radius: 12px 12px 0 0; padding: .6rem 1.25rem; margin: 0 -.25rem; display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .tg-dock .pills { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; font-size: .82rem; }
    .tg-pill { border-radius: 999px; padding: 2px 12px; font-weight: 700; font-size: .78rem; }
    .tg-pill.saved { background: #dcfce7; color: #15803d; } .tg-pill.ready { background: #dbeafe; color: #1d4ed8; }
    .tg-pill.todo { background: #fef3c7; color: #b45309; } .tg-pill.prefill { background: #ede9fe; color: #6d28d9; }
    .tg-dock .acts { margin-left: auto; display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .btn-save-grid { background: #15803d; border-color: #15803d; color: #fff; font-weight: 700; min-width: 170px; }
    .btn-save-grid:hover:not(:disabled) { background: #166534; border-color: #166534; color: #fff; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h2 class="page-title">กรอกผลตรวจสุขภาพช่องปากทั้งห้อง</h2>
            <a href="checklist_name.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>กลับหน้าเลือกรอบ</a>
        </div>

        <div class="tg-card">
            <!-- ส่วนที่ 1: ข้อมูลการตรวจ -->
            <div class="sec">
                <div class="sec-head">
                    <span class="gs">1</span>
                    <div>
                        <div class="sec-title">ข้อมูลการตรวจ</div>
                        <div class="sec-sub">ตั้งครั้งเดียว ใช้กับเด็กทุกคนที่บันทึก</div>
                    </div>
                </div>
                <div class="fgrid c4">
                    <div class="fld">
                        <label for="gRound">รอบตรวจ</label>
                        <select id="gRound" class="form-select" aria-label="รอบตรวจ"></select>
                        <div class="help" id="gRoundChips"></div>
                    </div>
                    <div class="fld">
                        <label for="gDate">วันที่ตรวจ</label>
                        <input type="date" id="gDate" class="form-control">
                        <div class="help"></div>
                    </div>
                    <div class="fld" id="gDoctorWrap">
                        <label for="gDoctor">ชื่อแพทย์ผู้ตรวจ</label>
                        <input type="text" id="gDoctor" class="form-control" value="<?= htmlspecialchars($defaultDoctor) ?>" maxlength="100">
                        <div class="help"></div>
                    </div>
                    <div class="fld">
                        <label for="gType">บันทึกในฐานะ</label>
                        <?php if (count($allowedTypes) === 1): ?>
                            <input type="hidden" id="gType" value="<?= $allowedTypes[0] ?>">
                            <div class="as-field"><span class="chip <?= $allowedTypes[0] ?>"><?= $typeLabels[$allowedTypes[0]] ?></span></div>
                            <div class="help">ตามสิทธิ์ที่เข้าสู่ระบบ</div>
                        <?php else: ?>
                            <select id="gType" class="form-select">
                                <?php foreach ($allowedTypes as $t): ?>
                                    <option value="<?= $t ?>"><?= $typeLabels[$t] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help">ผู้ดูแลระบบเลือกได้</div>
                        <?php endif; ?>
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
                        <div class="sec-sub">เลือกแล้วรายชื่อขึ้นเอง</div>
                    </div>
                </div>
                <div>
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
                            <select id="gRoom" class="form-select"><option value="">ทุกห้องในกลุ่ม</option></select>
                        </div>
                        <div class="fld">
                            <label for="gSearch">หรือค้นหาชื่อ / รหัส</label>
                            <input type="text" id="gSearch" class="form-control" placeholder="พิมพ์ชื่อ ชื่อเล่น หรือรหัส" autocomplete="off">
                        </div>
                        <div class="fld">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" id="gLoad" title="โหลดรายชื่อใหม่"><i class="bi bi-arrow-clockwise"></i> โหลดใหม่</button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a class="small text-decoration-none" data-bs-toggle="collapse" href="#gMore" role="button" aria-expanded="false"><i class="bi bi-sliders"></i> ตัวกรองเพิ่มเติม</a>
                        <div class="collapse mt-2" id="gMore">
                            <div class="fgrid c4">
                                <div class="fld">
                                    <label for="gStudentYear">เฉพาะเด็กที่เข้าเรียนปีการศึกษา</label>
                                    <select id="gStudentYear" class="form-select">
                                        <option value="all">ทั้งหมด</option>
                                        <?php foreach ($academicYears as $y): ?>
                                            <option value="<?= htmlspecialchars($y['name']) ?>"><?= htmlspecialchars($y['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3) ตาราง -->
        <div class="tg-card">
            <div class="sec-head mb-2">
                <span class="gs">3</span>
                <div>
                    <div class="sec-title">กรอกผลตรวจ</div>
                    <div class="sec-sub">กรอกในตาราง แล้วกด "บันทึกทั้งห้อง" ที่แถบด้านล่าง</div>
                </div>
            </div>
            <div id="gClosed" class="tg-closed" style="display:none"><i class="bi bi-lock-fill me-1"></i>รอบตรวจนี้ถูกปิดแล้ว ดูข้อมูลได้อย่างเดียว (ให้ผู้ดูแลระบบเปิดรอบอีกครั้งถ้าต้องแก้)</div>
            <div class="tg-progress" id="gProgress" style="display:none">
                <span id="gProgressText" class="fw-bold"></span>
                <div class="bar"><span id="gProgressBar" style="width:0%"></span></div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="gExtra">
                    <label class="form-check-label small" for="gExtra">แสดงช่องหมายเหตุ (ช่องปาก / รายละเอียด)</label>
                </div>
            </div>
            <div class="tg-legend" id="gLegend" style="display:none">
                <span><i style="background:#22c55e"></i>บันทึกแล้ว</span><span><i style="background:#3b82f6"></i>พร้อมบันทึก</span>
                <span><i style="background:#f59e0b"></i>ยังไม่ครบ</span><span id="gLegendPrefill"><i style="background:#8b5cf6"></i>ผลครู รอแพทย์ยืนยัน</span>
                <span><i style="background:#eab308"></i>ช่องที่แพทย์แก้ต่างจากครู</span>
            </div>
            <div class="tg-wrap" id="gWrap">
                <div class="tg-empty">
                    <div class="big">เลือกกลุ่มหรือห้องเรียนด้านบน</div>
                    รายชื่อเด็กจะขึ้นให้อัตโนมัติ แล้วกรอกต่อในตารางได้เลย
                </div>
            </div>
            <div class="tg-hint">
                Tab ไปช่องถัดไป · Enter ลงแถวถัดไป · เลือก "ไม่มีฟันผุ" ระบบเติมฟันผุและตำแหน่งเป็น 0 ให้ · ช่องตำแหน่งขึ้นเหลืองเมื่อยอดรวมไม่เท่ากับจำนวนฟันผุ · อายุคำนวณจากวันเกิดให้
            </div>
        </div>

        <div class="tg-dock" id="gDock">
            <div class="pills" id="gSummary"><span class="text-muted">ยังไม่ได้โหลดรายชื่อ</span></div>
            <div class="acts">
                <button type="button" class="btn btn-outline-primary btn-sm" id="gFillNormal" disabled title="เติมค่าปกติ (ไม่มีฟันผุ) ให้เด็กที่ยังไม่มีข้อมูล"><i class="bi bi-magic me-1"></i>เติมค่าปกติให้ที่ยังว่าง</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="gConfirmAll" disabled style="display:none"><i class="bi bi-check2-all me-1"></i>ยืนยันตามผลครูทั้งหมด</button>
                <button type="button" class="btn btn-save-grid" id="gSave" disabled><i class="bi bi-check-circle me-1"></i>บันทึกทั้งห้อง</button>
            </div>
        </div>
    </div>
</main>

<script>
    const API_LIST = './function/get_tooth_grid.php';
    const API_SAVE = './process/save_health_tooth_bulk.php';
    const API_ROUNDS = './process/manage_tooth_rounds.php';
    const XHR = { 'X-Requested-With': 'XMLHttpRequest' };
    const POS = [
        ['upper_front_teeth', 'หน้าบน'], ['upper_right_molar', 'กรามขวาบน'], ['lower_right_molar', 'กรามขวาล่าง'],
        ['lower_front_teeth', 'หน้าล่าง'], ['upper_left_molar', 'กรามซ้ายบน'], ['lower_left_molar', 'กรามซ้ายล่าง']
    ];
    const TR = [
        ['filling', 'อุด', 'อุดฟัน'], ['fluoride', 'ฟล', 'เคลือบฟลูออไรด์'], ['root_canal', 'รา', 'รักษาคลองรากฟัน'],
        ['fluoride_molar', 'กร', 'เคลือบหลุมร่องฟันที่ฟันกราม'], ['crown', 'คร', 'ครอบฟัน'], ['extraction', 'ถอ', 'ถอนฟัน'], ['other', 'อื่', 'อื่นๆ']
    ];
    const URG = [['urgent', 'ด่วน'], ['not_urgent', 'ไม่เร่งด่วน'], ['preventable', 'ผัดผ่อนได้']];
    const EXAM_TEXT = { doctor: 'แพทย์ตรวจแล้ว', teacher: 'ครูคัดกรอง', legacy: 'ข้อมูลเดิม' };

    const PRE = Object.fromEntries(new URLSearchParams(location.search));
    let rows = [];
    let rounds = [];
    let roundClosed = false;
    let loadedRoundId = null;
    let showExtra = false;
    try { showExtra = localStorage.getItem('toothGridExtra') === '1'; } catch (e) { /* ใช้งานต่อได้แม้ไม่มี localStorage */ }
    const byId = (id) => document.getElementById(id);
    const mode = () => byId('gType').value;
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const num = (v) => (v === null || v === undefined || v === '' ? null : Math.max(0, parseInt(v, 10)));
    const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

    function todayStr() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    function ageText(birthday) {
        const exam = byId('gDate').value;
        if (!birthday || !exam) return '-';
        const b = new Date(birthday + 'T00:00:00'), e = new Date(exam + 'T00:00:00');
        if (b > e) return '-';
        let y = e.getFullYear() - b.getFullYear(), m = e.getMonth() - b.getMonth();
        if (e.getDate() < b.getDate()) m--;
        if (m < 0) { y--; m += 12; }
        return `${y} ปี ${m} ด.`;
    }

    function recToVals(rec) {
        const p = rec.decayed_teeth_positions || {};
        return {
            tt: num(rec.total_teeth), dc: num(rec.decayed_teeth), p: POS.map(([k]) => num(p[k]) ?? 0),
            s: rec.teeth_status || '', u: rec.urgency || '', t: [...(rec.treatments || [])],
            oral: rec.oral_components || '', miss: rec.missing_teeth_detail || '', other: rec.other_treatment_detail || ''
        };
    }
    const emptyVals = () => ({ tt: null, dc: null, p: POS.map(() => null), s: '', u: '', t: [], oral: '', miss: '', other: '' });

    function toRow(c) {
        const recs = c.records || {};
        const m = mode();
        const own = recs[m];
        const source = recs.teacher || recs.legacy || null;
        const ref = m === 'doctor' && source ? recToVals(source) : null;
        const base = own ? recToVals(own) : (m === 'doctor' && source ? recToVals(source) : emptyVals());
        return Object.assign(base, {
            sid: c.studentid, nick: c.nickname || '', name: [c.prefix_th, c.firstname_th, c.lastname_th].filter(Boolean).join(' '),
            birthday: c.birthday, exam: recs.doctor ? 'doctor' : recs.teacher ? 'teacher' : recs.legacy ? 'legacy' : '',
            saved: !!own, prefill: !own && !!ref, ref: ref, dirty: false
        });
    }

    const posSum = (r) => r.p.reduce((a, b) => a + (b || 0), 0);
    function complete(r) {
        if (r.tt === null || !r.s) return false;
        if (r.s === 'normal') return r.dc === 0;
        return r.dc !== null && r.dc > 0 && posSum(r) === r.dc && !!r.u;
    }
    const rowState = (r) => (r.dirty ? (complete(r) ? 'ready' : 'todo') : (r.saved ? 'saved' : (r.prefill ? 'prefill' : 'todo')));
    const STATE_TEXT = { saved: 'บันทึกแล้ว', ready: 'พร้อมบันทึก', todo: 'ยังไม่ครบ', prefill: 'ผลครู · รอยืนยัน' };

    function diffOf(r, key, idx) {
        if (!r.ref) return false;
        if (key === 'p') return (r.p[idx] ?? 0) !== (r.ref.p[idx] ?? 0);
        if (key === 't') return (r.t.includes(idx)) !== (r.ref.t.includes(idx));
        return (r[key] ?? '') !== (r.ref[key] ?? '');
    }
    const dcls = (r, key, idx) => (diffOf(r, key, idx) && !r.prefill ? ' diff' : '');

    // ช่องรักษาอื่นๆ (ระบุ) แสดงเมื่อเปิดช่องหมายเหตุ หรือมีเด็กที่เลือก "อื่น"
    const showOtherCol = () => showExtra || rows.some((r) => r.t.includes('other'));

    function header() {
        const nTreat = TR.length + (showOtherCol() ? 1 : 0);
        return '<thead><tr class="h1">' +
            '<th class="g-child" colspan="3">เด็ก</th><th class="g-count" colspan="2">จำนวนฟัน (ซี่)</th><th class="g-pos" colspan="6">ฟันผุแต่ละตำแหน่ง (ซี่)</th>' +
            '<th class="g-result" colspan="2">ผลตรวจ</th><th class="g-treat" colspan="' + nTreat + '">การรักษา</th>' +
            (showExtra ? '<th class="g-note" colspan="2">หมายเหตุ</th>' : '') + '<th class="g-state" colspan="3">สถานะ</th></tr>' +
            '<tr class="h2"><th class="c-no">#</th><th class="c-nm">ชื่อ</th><th>อายุ</th><th>ทั้งหมด</th><th>ผุ</th>' +
            POS.map((p) => `<th>${p[1]}</th>`).join('') + '<th>สภาพฟัน</th><th>ความเร่งด่วน</th>' +
            TR.map((t) => `<th class="th-treat" title="${esc(t[2])}">${esc(t[2])}</th>`).join('') +
            (showOtherCol() ? '<th>อื่นๆ (ระบุ)</th>' : '') +
            (showExtra ? '<th>ช่องปาก</th><th>รายละเอียด</th>' : '') +
            '<th>ผลของรอบนี้</th><th>การกรอก</th><th></th></tr></thead>';
    }

    function rowHtml(r, i) {
        const st = rowState(r), bad = r.s === 'abnormal' && r.dc !== null && posSum(r) !== r.dc;
        const v = (x) => (x === null ? '' : x), d = roundClosed ? ' disabled' : '';
        let h = `<tr class="${st}" data-i="${i}"><td class="c-no">${i + 1}</td>` +
            `<td class="c-nm"><div class="nick">${r.nick ? esc(r.nick) : esc(r.name)}</div>${r.nick ? `<div class="full">${esc(r.name)}</div>` : ''}</td>` +
            `<td>${esc(ageText(r.birthday))}</td>` +
            `<td><input class="f n${dcls(r, 'tt')}" type="number" min="0" max="32" data-f="tt" value="${v(r.tt)}"${d} aria-label="ฟันทั้งหมด"></td>` +
            `<td><input class="f n${dcls(r, 'dc')}" type="number" min="0" max="32" data-f="dc" value="${v(r.dc)}"${d} aria-label="ฟันผุ"></td>`;
        r.p.forEach((x, k) => { h += `<td class="pos${bad ? ' bad' : ''}"><input class="f n${dcls(r, 'p', k)}" type="number" min="0" data-p="${k}" value="${v(x)}"${d} aria-label="${POS[k][1]}"></td>`; });
        h += `<td><select class="f${dcls(r, 's')}" data-f="s" data-key="s"${d}><option value="">-</option><option value="normal"${r.s === 'normal' ? ' selected' : ''}>ไม่มีฟันผุ</option><option value="abnormal"${r.s === 'abnormal' ? ' selected' : ''}>มีฟันผุ</option></select></td>`;
        h += `<td><select class="f${dcls(r, 'u')}" data-f="u"${d}><option value="">-</option>${URG.map((u) => `<option value="${u[0]}"${r.u === u[0] ? ' selected' : ''}>${u[1]}</option>`).join('')}</select></td>`;
        TR.forEach((t) => { const on = r.t.includes(t[0]); h += `<td><button type="button" class="tgl${on ? ' on' : ''}${dcls(r, 't', t[0])}" data-t="${t[0]}" title="${esc(t[2])}" aria-label="${esc(t[2])}" aria-pressed="${on}"${d}>${on ? '✓' : ''}</button></td>`; });
        if (showOtherCol()) h += `<td><input class="f t${dcls(r, 'other')}" type="text" maxlength="200" data-f="other" value="${esc(r.other)}"${r.t.includes('other') && !roundClosed ? '' : ' disabled'}></td>`;
        if (showExtra) {
            h += `<td><input class="f t${dcls(r, 'oral')}" type="text" maxlength="100" data-f="oral" value="${esc(r.oral)}"${d}></td>` +
                `<td><input class="f t${dcls(r, 'miss')}" type="text" maxlength="100" data-f="miss" value="${esc(r.miss)}"${d}></td>`;
        }
        h += `<td>${r.exam ? `<span class="ex ${r.exam}">${EXAM_TEXT[r.exam]}</span>` : '<span class="ex">ยังไม่มี</span>'}</td>` +
            `<td><span class="st ${st}">${STATE_TEXT[st]}</span></td>` +
            `<td>${roundClosed ? '' : (r.prefill ? '<button type="button" class="rowact" data-confirm="1" title="ยืนยันตามผลของครู">ยืนยัน</button> ' : '') + '<button type="button" class="rowact" data-normal="1" title="เติมค่าปกติ: ไม่มีฟันผุ (ถ้ายังไม่กรอกจำนวนฟันทั้งหมด จะใส่ 20 ซี่)">ปกติ</button>'}</td></tr>`;
        return h;
    }

    function render(keepFocus) {
        const wrap = byId('gWrap');
        if (!rows.length) { wrap.innerHTML = '<div class="tg-empty"><div class="big">ไม่พบรายชื่อเด็กตามเงื่อนไข</div>ลองเปลี่ยนกลุ่ม ห้อง หรือคำค้นหา</div>'; summary(); return; }
        const sc = { top: wrap.scrollTop, left: wrap.scrollLeft };
        wrap.innerHTML = '<table class="tg">' + header() + '<tbody>' + rows.map(rowHtml).join('') + '</tbody></table>';
        wrap.scrollTop = sc.top; wrap.scrollLeft = sc.left;
        if (keepFocus) {
            const el = wrap.querySelector(`tr[data-i="${keepFocus.i}"] [data-key="${keepFocus.key}"]`);
            if (el) el.focus();
        }
        summary();
    }

    function refreshRow(i) {
        const tr = byId('gWrap').querySelector(`tr[data-i="${i}"]`);
        if (!tr) return;
        const r = rows[i], st = rowState(r), bad = r.s === 'abnormal' && r.dc !== null && posSum(r) !== r.dc;
        tr.className = st;
        const chip = tr.querySelector('.st'); chip.className = 'st ' + st; chip.textContent = STATE_TEXT[st];
        tr.querySelectorAll('td.pos').forEach((td) => td.classList.toggle('bad', bad));
        const other = tr.querySelector('[data-f="other"]'); if (other) other.disabled = !r.t.includes('other') || roundClosed;
        tr.querySelectorAll('[data-f],[data-p]').forEach((el) => {
            const d = el.dataset.p !== undefined ? diffOf(r, 'p', +el.dataset.p) : diffOf(r, el.dataset.f);
            el.classList.toggle('diff', d && !r.prefill);
        });
        const confirmBtn = tr.querySelector('[data-confirm]'); if (confirmBtn && !r.prefill) confirmBtn.remove();
        summary();
    }

    function summary() {
        const c = { saved: 0, ready: 0, todo: 0, prefill: 0 };
        rows.forEach((r) => c[rowState(r)]++);
        const doctor = mode() === 'doctor';
        byId('gSummary').innerHTML = rows.length
            ? `<span>${rows.length} คน</span><span class="tg-pill saved">บันทึกแล้ว ${c.saved}</span><span class="tg-pill ready">พร้อมบันทึก ${c.ready}</span>` +
              (doctor ? `<span class="tg-pill prefill">ผลครูรอยืนยัน ${c.prefill}</span>` : '') + `<span class="tg-pill todo">ยังไม่ครบ ${c.todo}</span>`
            : '<span class="text-muted">ยังไม่ได้โหลดรายชื่อ</span>';

        const pr = byId('gProgress'), lg = byId('gLegend');
        pr.style.display = lg.style.display = rows.length ? '' : 'none';
        byId('gLegendPrefill').style.display = doctor ? '' : 'none';
        if (rows.length) {
            const done = c.saved + c.ready;
            byId('gProgressText').textContent = `ตรวจแล้ว ${done} / ${rows.length} คน`;
            byId('gProgressBar').style.width = Math.round(done * 100 / rows.length) + '%';
        }

        const anyDirty = rows.some((r) => r.dirty);
        const ready = rows.filter((r) => r.dirty && complete(r)).length;
        const saveBtn = byId('gSave');
        saveBtn.disabled = !anyDirty || roundClosed;
        saveBtn.innerHTML = `<i class="bi bi-check-circle me-1"></i>บันทึกทั้งห้อง${ready ? ` (${ready} คน)` : ''}`;
        byId('gFillNormal').disabled = !rows.length || roundClosed;
        const ca = byId('gConfirmAll');
        ca.style.display = doctor ? '' : 'none';
        ca.disabled = roundClosed || !rows.some((r) => r.prefill && complete(r));
        window.__dirty = anyDirty;
    }

    function setNormal(r) {
        r.tt = r.tt === null ? 20 : r.tt; r.dc = 0; r.p = POS.map(() => 0); r.s = 'normal'; r.u = ''; r.t = []; r.other = ''; r.dirty = true; r.prefill = false;
    }

    function roundChips() {
        const r = rounds.find((x) => String(x.id) === String(byId('gRound').value));
        byId('gRoundChips').innerHTML = r
            ? `<span class="chip ${r.status === 'open' ? 'open' : 'closed'}">${r.status === 'open' ? 'เปิดอยู่' : 'ปิดแล้ว'}</span>` +
              `<span class="chip teacher">ครู ${r.teacher_count} คน</span><span class="chip doctor">แพทย์ ${r.doctor_count} คน</span>`
            : '';
    }

    async function loadRounds() {
        const sel = byId('gRound');
        const keep = sel.value;
        try {
            const res = await fetch(`${API_ROUNDS}?action=list`, { headers: XHR });
            const data = await res.json();
            rounds = data.data || [];
            sel.innerHTML = rounds.length
                ? rounds.map((r) => `<option value="${r.id}">${esc(r.academic_year)} · ${esc(r.title)}${r.status === 'closed' ? ' (ปิดแล้ว)' : ''}</option>`).join('')
                : '<option value="">ยังไม่มีรอบ — ให้ admin เปิดรอบ</option>';
            const firstOpen = rounds.find((r) => r.status === 'open');
            if (firstOpen) sel.value = firstOpen.id;
            if (PRE.round_id && rounds.some((r) => String(r.id) === PRE.round_id) && !keep) sel.value = PRE.round_id;
            if (keep && rounds.some((r) => String(r.id) === keep)) sel.value = keep;
        } catch (e) { sel.innerHTML = '<option value="">โหลดรอบไม่สำเร็จ</option>'; }
        roundChips();
    }

    async function confirmDiscard() {
        if (!window.__dirty) return true;
        const r = await Swal.fire({ icon: 'warning', title: 'มีข้อมูลที่ยังไม่บันทึก', text: 'โหลดรายชื่อใหม่จะทิ้งข้อมูลที่กรอกค้างไว้', showCancelButton: true, confirmButtonText: 'โหลดใหม่', cancelButtonText: 'กลับไปกรอกต่อ' });
        return r.isConfirmed;
    }

    // userClick = กดปุ่ม "โหลดใหม่" เอง (ถ้ายังไม่เลือกเงื่อนไข จะเตือน) นอกนั้นโหลดอัตโนมัติเมื่อเปลี่ยนตัวกรอง
    async function loadList(userClick) {
        const group = byId('gGroup').value, room = byId('gRoom').value, round = byId('gRound').value, search = byId('gSearch').value.trim();
        if (!round) { if (userClick) Swal.fire({ icon: 'info', title: 'ยังไม่มีรอบตรวจ', text: 'ให้ผู้ดูแลระบบเปิดรอบจากหน้าเลือกรอบก่อน', confirmButtonText: 'ตกลง' }); return; }
        if (!group && !room && !search) {
            if (userClick) Swal.fire({ icon: 'info', title: 'เลือกกลุ่ม/ห้องเรียน หรือค้นหาชื่อเด็กก่อน', confirmButtonText: 'ตกลง' });
            return;
        }
        if (!(await confirmDiscard())) return;
        byId('gWrap').innerHTML = '<div class="tg-empty"><div class="spinner-border text-primary"></div></div>';
        try {
            const q = new URLSearchParams({ round_id: round, student_year: byId('gStudentYear').value, child_group: group, classroom: room, search: search });
            const res = await fetch(`${API_LIST}?${q}`, { headers: XHR });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'โหลดไม่สำเร็จ');
            roundClosed = data.round.status !== 'open';
            loadedRoundId = data.round.id;
            byId('gClosed').style.display = roundClosed ? '' : 'none';
            rows = data.data.map(toRow);
            render();
        } catch (e) {
            rows = []; byId('gWrap').innerHTML = `<div class="tg-empty"><div class="big">โหลดไม่สำเร็จ</div>${esc(e.message)}</div>`; summary();
        }
    }

    async function saveAll() {
        const dirty = rows.filter((r) => r.dirty);
        const ready = dirty.filter(complete), notReady = dirty.filter((r) => !complete(r));
        if (!ready.length) { Swal.fire({ icon: 'warning', title: 'ยังไม่มีแถวที่พร้อมบันทึก', text: 'กรอกให้ครบทุกช่องที่จำเป็นของแถวที่แก้ไข (แถวสีเหลืองยังไม่ครบ)', confirmButtonText: 'ตกลง' }); return; }
        const isDoctor = mode() === 'doctor';
        if (isDoctor && !byId('gDoctor').value.trim()) { Swal.fire({ icon: 'warning', title: 'กรุณาระบุชื่อแพทย์ผู้ตรวจ', confirmButtonText: 'ตกลง' }); byId('gDoctor').focus(); return; }
        const who = mode() === 'doctor' ? 'ผลตรวจของแพทย์' : 'ผลคัดกรองของครู';
        const ok = await Swal.fire({
            icon: 'question', title: `บันทึก ${ready.length} คน?`,
            html: `บันทึกเป็น <b>${who}</b> ของ "${esc(byId('gRound').selectedOptions[0]?.textContent || '')}"<br>` +
                (notReady.length ? `ข้าม <b>${notReady.length}</b> คนที่กรอกยังไม่ครบ (ข้อมูลที่กรอกไว้ยังอยู่ในตาราง)<br>` : '') +
                'ถ้าเด็กมีผลของผู้ตรวจประเภทเดียวกันในรอบนี้อยู่แล้ว จะถูกอัปเดตทับ (ผลของอีกประเภทไม่ถูกแตะ)',
            showCancelButton: true, confirmButtonText: 'ยืนยันบันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#15803d'
        });
        if (!ok.isConfirmed) return;
        const btn = byId('gSave'); btn.disabled = true;
        try {
            const body = {
                round_id: loadedRoundId, exam_type: mode(), exam_date: byId('gDate').value, doctor_name: isDoctor ? byId('gDoctor').value.trim() : '',
                rows: ready.map((r) => ({
                    student_id: r.sid, total_teeth: r.tt, decayed_teeth: r.dc, teeth_status: r.s, urgency: r.u || null,
                    positions: Object.fromEntries(POS.map(([k], i) => [k, r.p[i] || 0])), treatments: r.t,
                    other_treatment_detail: r.other, oral_components: r.oral, missing_teeth_detail: r.miss
                }))
            };
            const res = await fetch(API_SAVE, { method: 'POST', headers: { 'Content-Type': 'application/json', ...XHR }, body: JSON.stringify(body) });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'บันทึกไม่สำเร็จ');
            const failed = new Set((data.errors || []).map((e) => e.student_id));
            ready.forEach((r) => { if (!failed.has(r.sid)) { r.saved = true; r.dirty = false; r.prefill = false; r.exam = r.exam === 'doctor' || mode() === 'doctor' ? 'doctor' : (r.exam || 'teacher'); } });
            render();
            Swal.fire({ icon: failed.size ? 'warning' : 'success', title: data.message, timer: failed.size ? undefined : 1800, showConfirmButton: !!failed.size, confirmButtonText: 'ตกลง',
                text: failed.size ? data.errors.map((e) => `${e.student_id}: ${e.message}`).join('\n') : '' });
            loadRounds().then(roundChips);   // อัปเดตจำนวนครู/แพทย์ในป้ายรอบ
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message, confirmButtonText: 'ตกลง' });
        } finally { summary(); }
    }

    function onInput(e) {
        const el = e.target, tr = el.closest('tr[data-i]');
        if (!tr) return;
        const i = +tr.dataset.i, r = rows[i];
        if (el.dataset.p !== undefined) r.p[+el.dataset.p] = num(el.value);
        else if (el.dataset.f === 'tt' || el.dataset.f === 'dc') r[el.dataset.f] = num(el.value);
        else if (el.dataset.f) r[el.dataset.f] = el.value;
        else return;
        r.dirty = true; r.prefill = false;
        if (el.dataset.f === 's') {
            if (el.value === 'normal') { r.dc = 0; r.p = POS.map(() => 0); r.u = ''; }
            render({ i, key: 's' });
        } else {
            refreshRow(i);
        }
    }

    // ชื่อผู้ตรวจแสดงและบันทึกเฉพาะเมื่อบันทึกในฐานะแพทย์
    function syncExaminerField() {
        byId('gDoctorWrap').style.display = mode() === 'doctor' ? '' : 'none';
    }

    function init() {
        byId('gDate').value = todayStr();
        byId('gExtra').checked = showExtra;
        byId('gExtra').addEventListener('change', (e) => {
            showExtra = e.target.checked;
            try { localStorage.setItem('toothGridExtra', showExtra ? '1' : '0'); } catch (err) { /* ไม่เป็นไร */ }
            if (rows.length) render();
        });
        // เปลี่ยนตัวกรอง = โหลดรายชื่อใหม่เอง
        byId('gGroup').addEventListener('change', async () => { await loadRooms(); loadList(false); });
        byId('gRoom').addEventListener('change', () => loadList(false));
        byId('gStudentYear').addEventListener('change', () => loadList(false));
        byId('gRound').addEventListener('change', () => { roundChips(); loadList(false); });
        byId('gType').addEventListener('change', () => { syncExaminerField(); if (rows.length) loadList(false); });
        syncExaminerField();
        byId('gSearch').addEventListener('input', debounce(() => loadList(false), 450));
        byId('gLoad').addEventListener('click', () => loadList(true));
        byId('gSave').addEventListener('click', saveAll);
        byId('gFillNormal').addEventListener('click', () => { rows.forEach((r) => { if (!r.saved && !r.dirty && !r.prefill) setNormal(r); }); render(); });
        byId('gConfirmAll').addEventListener('click', () => { rows.forEach((r) => { if (r.prefill && complete(r)) { r.dirty = true; r.prefill = false; } }); render(); });
        byId('gDate').addEventListener('change', () => { if (rows.length) render(); });
        const wrap = byId('gWrap');
        wrap.addEventListener('input', onInput);
        wrap.addEventListener('change', (e) => { if (e.target.tagName === 'SELECT') onInput(e); });
        wrap.addEventListener('click', (e) => {
            const b = e.target.closest('button'); if (!b) return;
            const i = +b.closest('tr').dataset.i, r = rows[i];
            if (b.dataset.t) { const k = r.t.indexOf(b.dataset.t); k > -1 ? r.t.splice(k, 1) : r.t.push(b.dataset.t); r.dirty = true; r.prefill = false; render(); }
            if (b.dataset.normal) { setNormal(r); render(); }
            if (b.dataset.confirm) { r.dirty = true; r.prefill = false; render(); }
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

    // มาจากหน้าเลือกรอบ: ใส่รอบ/ตัวกรองที่เลือกไว้ให้ แล้วโหลดรายชื่อต่อเลย
    async function applyPreselect() {
        if (PRE.student_year && [...byId('gStudentYear').options].some((o) => o.value === PRE.student_year)) byId('gStudentYear').value = PRE.student_year;
        if (PRE.child_group) { byId('gGroup').value = PRE.child_group; await loadRooms(); }
        if (PRE.classroom) byId('gRoom').value = PRE.classroom;
        if (PRE.search) byId('gSearch').value = PRE.search;
        await loadRounds();
        loadList(false);
    }

    function loadRooms() {
        const group = byId('gGroup').value, sel = byId('gRoom');
        sel.innerHTML = '<option value="">ทุกห้องในกลุ่ม</option>';
        if (!group) return Promise.resolve();
        return fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(group)}`)
            .then((r) => r.json())
            .then((d) => { [...new Set((Array.isArray(d) ? d : []).map((c) => c.classroom_name))].forEach((n) => { const o = document.createElement('option'); o.value = o.textContent = n; sel.appendChild(o); }); })
            .catch(console.error);
    }

    document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
