<?php
// กรอกผลตรวจสุขภาพทั้งห้องเป็นตารางเดียว (ผูกกับรอบตรวจที่ admin เปิดไว้)
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/function/child_functions.php';
require_once __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';

$groups = get_childgroup();
$academicYears = getAcademicYears();
$roundId = (int) ($_GET['round_id'] ?? 0);
$isDoctor = getUserRole() === 'doctor';
$doctorName = $isDoctor ? getFullName() : '';
?>

<style>
    .hg-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); padding: .9rem 1.25rem; margin-bottom: .85rem; }
    .hg-card label { font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .15rem; }
    .hg-title { font-size: 1.5rem; font-weight: 700; color: #0f2460; margin: 0; }
    .hg-chip { border-radius: 999px; padding: 2px 12px; font-size: .78rem; font-weight: 700; background: #f1f5f9; color: #475569; display: inline-block; }
    .hg-chip.open { background: #dcfce7; color: #15803d; }
    .hg-chip.closed { background: #fee2e2; color: #b91c1c; }
    .hg-help { font-size: .8rem; color: #64748b; }
    .hg-closed { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: .6rem 1rem; margin-bottom: .85rem; font-weight: 600; }
    .hg-progress { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .hg-bar { flex: 1 1 200px; height: 10px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
    .hg-bar span { display: block; height: 100%; background: #16a34a; transition: width .2s; }

    .hg-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); overflow: auto; max-height: calc(100vh - 330px); min-height: 260px; cursor: default; }
    table.hg { border-collapse: separate; border-spacing: 0; font-size: .84rem; min-width: 100%; }
    .hg th, .hg td { border-bottom: 1px solid #e8edf3; border-right: 1px solid #eef2f6; padding: 4px 5px; white-space: nowrap; vertical-align: middle; background: #fff; }
    .hg thead th { position: sticky; top: 0; z-index: 3; text-align: center; font-size: .76rem; font-weight: 700; color: #fff; background: #1e4db7; }
    .hg thead tr.g1 th { top: 0; font-size: .8rem; }
    .hg thead tr.g2 th { top: 31px; }
    .hg th.gh-info { background: #475569; } .hg th.gh-body { background: #0f766e; } .hg th.gh-beh { background: #b45309; }
    .hg th.gh-dev { background: #7c3aed; } .hg th.gh-exam { background: #0369a1; } .hg th.gh-st { background: #334155; }
    .hg .c-no, .hg .c-nm { position: sticky; z-index: 2; background: #fff; }
    .hg .c-no { left: 0; width: 38px; min-width: 38px; text-align: center; color: #64748b; }
    .hg .c-nm { left: 38px; min-width: 150px; max-width: 190px; border-right: 2px solid #cbd5e1; }
    .hg thead .c-no, .hg thead .c-nm { z-index: 5; background: #1e4db7; color: #fff; }
    .hg .nick { font-weight: 700; color: #0f2460; } .hg .full { font-size: .72rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; }
    .hg .age { font-size: .72rem; color: #64748b; min-width: 70px; }
    .hg input.f, .hg select.f { border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 3px 5px; font-size: .84rem; background: #fff; height: 32px; }
    .hg input.f:focus, .hg select.f:focus { outline: none; border-color: #1e4db7; box-shadow: 0 0 0 3px rgba(30, 77, 183, .15); }
    .hg input.n { width: 62px; text-align: center; } .hg input.d { width: 128px; } .hg input.t { width: 120px; } .hg input.sc { width: 52px; text-align: center; margin-top: 2px; }
    .hg select.f { min-width: 92px; } .hg select.s-pass, .hg select.s-normal, .hg select.s-none { background: #f0fdf4; border-color: #86efac; }
    .hg select.s-delay, .hg select.s-abnormal, .hg select.s-has { background: #fef2f2; border-color: #fca5a5; }
    .hg .dtl { margin-top: 2px; width: 120px; }
    .hg .hide { display: none; }
    .hg.hide-exam .ex { display: none; }
    .hg tr.is-dirty td { background: #f5f9ff; } .hg tr.is-dirty .c-no, .hg tr.is-dirty .c-nm { background: #eaf2ff; }
    .hg tr.is-saved td { background: #f6fdf8; } .hg tr.is-saved .c-no, .hg tr.is-saved .c-nm { background: #ecfbf1; }
    .hg tr.is-locked td, .hg tr.is-skip td { background: #f1f5f9; opacity: .75; }
    .hg tr.is-err td { background: #fff1f2; }
    .hg .st { border-radius: 999px; padding: 2px 10px; font-size: .74rem; font-weight: 700; background: #e2e8f0; color: #475569; display: inline-block; }
    .hg .st.dirty { background: #dbeafe; color: #1d4ed8; } .hg .st.saved { background: #dcfce7; color: #15803d; }
    .hg .st.locked { background: #e2e8f0; color: #334155; } .hg .st.err { background: #fee2e2; color: #b91c1c; }
    .hg .rowact { border: 1.5px solid #94a3b8; background: #fff; border-radius: 7px; padding: 3px 9px; font-size: .75rem; font-weight: 700; cursor: pointer; color: #334155; }
    .hg .rowact:hover:not(:disabled) { border-color: #16a34a; color: #16a34a; background: #f0fdf4; }
    .hg .rowact.skip.on { background: #475569; color: #fff; border-color: #475569; }
    .hg .fillcol { border: 0; background: rgba(255, 255, 255, .25); color: #fff; border-radius: 5px; font-size: .7rem; padding: 0 5px; margin-left: 3px; cursor: pointer; }
    .hg .fillcol:hover { background: rgba(255, 255, 255, .45); }
    .hg-empty { padding: 3rem 1rem; text-align: center; color: #64748b; }

    .hg-dock { position: sticky; bottom: 0; z-index: 30; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 16px rgba(15, 36, 96, .08);
        padding: .7rem 1.25rem; display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; border-radius: 12px 12px 0 0; }
    .hg-dock .pills { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; font-size: .82rem; }
    .hg-dock .acts { margin-left: auto; display: flex; gap: .5rem; align-items: center; }
    .btn-save-grid { background: #15803d; border-color: #15803d; color: #fff; font-weight: 700; padding: .5rem 1.4rem; }
    .btn-save-grid:hover:not(:disabled) { background: #166534; color: #fff; }

    .gs { display: inline-flex; width: 22px; height: 22px; border-radius: 50%; background: #1e4db7; color: #fff; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; }
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
    .hg-divider { border: 0; border-top: 1px solid #e2e8f0; margin: .9rem 0; }
    .hg-card .chip { border-radius: 999px; padding: 2px 10px; font-weight: 700; font-size: .78rem; background: #f1f5f9; color: #475569; white-space: nowrap; }
    .hg-card .chip.open { background: #dcfce7; color: #15803d; } .hg-card .chip.closed { background: #fee2e2; color: #b91c1c; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h2 class="hg-title">กรอกผลตรวจสุขภาพทั้งห้อง</h2>
            <a href="checklist_name.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>กลับหน้าเลือกรอบ</a>
        </div>

        <div class="hg-card">
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
                        <div class="help"><button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="gApplyDate"><i class="bi bi-calendar-check me-1"></i>ใช้กับทั้งห้อง</button></div>
                    </div>
                    <div class="fld">
                        <label for="gMDate">วันที่ชั่งน้ำหนัก / วัดส่วนสูง</label>
                        <input type="date" id="gMDate" class="form-control">
                        <div class="help"><button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="gApplyMDate"><i class="bi bi-calendar-check me-1"></i>ใช้กับทั้งห้อง</button></div>
                    </div>
                    <?php if ($isDoctor): ?>
                        <div class="fld">
                            <label>ผู้ตรวจ</label>
                            <div class="form-control-plaintext fw-bold"><?= htmlspecialchars($doctorName) ?></div>
                            <div class="help">บันทึกแล้วลงชื่อแพทย์ให้อัตโนมัติ</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="hg-divider">

            <!-- ส่วนที่ 2: เลือกเด็ก -->
            <div class="sec">
                <div class="sec-head">
                    <span class="gs">2</span>
                    <div>
                        <div class="sec-title">เลือกเด็ก</div>
                        <div class="sec-sub">เลือกแล้วรายชื่อขึ้นเอง</div>
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
                        <select id="gRoom" class="form-select"><option value="">ทุกห้องในกลุ่ม</option></select>
                    </div>
                    <div class="fld">
                        <label for="gSearch">หรือค้นหาชื่อ / รหัส</label>
                        <input type="search" id="gSearch" class="form-control" placeholder="พิมพ์ชื่อ ชื่อเล่น หรือรหัส" autocomplete="off">
                    </div>
                    <div class="fld">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-outline-primary w-100" id="gReload" title="โหลดรายชื่อใหม่"><i class="bi bi-arrow-clockwise"></i> โหลดใหม่</button>
                    </div>
                    <div class="mt-2" style="grid-column: 1 / -1">
                        <div class="mt-2" id="gMore">
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
        <div class="hg-card">
            <div class="sec-head mb-2">
                <span class="gs">3</span>
                <div>
                    <div class="sec-title">กรอกผลตรวจ</div>
                    <div class="sec-sub">กรอกในตาราง แล้วกด "บันทึก" ที่แถบด้านล่าง · กรอกบางส่วนก็บันทึกได้ · Enter ลงช่องเดียวกันของคนถัดไป</div>
                </div>
            </div>
            <div id="gClosed" class="hg-closed" style="display:none"><i class="bi bi-lock-fill me-1"></i>รอบตรวจนี้ถูกปิดแล้ว ดูข้อมูลได้อย่างเดียว (ให้ผู้ดูแลระบบเปิดรอบอีกครั้งถ้าต้องแก้)</div>
            <div class="hg-progress mb-2" id="gProgress" style="display:none">
                <span id="gProgressText" class="fw-bold"></span>
                <div class="hg-bar"><span id="gProgressBar" style="width:0%"></span></div>
            </div>
            <div class="hg-wrap" id="gWrap"><div class="hg-empty"><div class="fs-5 fw-bold">เลือกกลุ่มหรือห้องเรียนด้านบน</div>รายชื่อเด็กจะขึ้นให้อัตโนมัติ แล้วกรอกต่อในตารางได้เลย</div></div>
        </div>

        <div class="hg-dock" id="gDock">
            <div class="pills" id="gPills"></div>
            <div class="acts">
                <span class="hg-help" id="gSaveHint"></span>
                <button type="button" class="btn btn-outline-primary btn-sm" id="gFillNormal" title="เติมค่าปกติ (พฤติกรรม none, พัฒนาการ pass, ตรวจร่างกาย normal) เฉพาะช่องที่ยังว่างของทุกคน"><i class="bi bi-magic me-1"></i>เติมปกติให้ที่ยังว่าง</button>
                <button type="button" class="btn btn-save-grid" id="gSave" disabled><i class="bi bi-check-circle me-1"></i>บันทึก</button>
            </div>
        </div>
    </div>
</main>

<script>
    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const XHR = { 'X-Requested-With': 'XMLHttpRequest' };
    const IS_DOCTOR = <?= $isDoctor ? 'true' : 'false' ?>;
    const DOCTOR_NAME = <?= json_encode($doctorName, JSON_UNESCAPED_UNICODE) ?>;

    const DEV = [['gm', 'GM'], ['fm', 'FM'], ['rl', 'RL'], ['el', 'EL'], ['ps', 'PS']];
    const EXAM = [['general', 'สภาพทั่วไป'], ['skin', 'ผิวหนัง'], ['head', 'ศีรษะ'], ['face', 'ใบหน้า'], ['eyes', 'ตา'], ['ears', 'หู'], ['nose', 'จมูก'], ['mouth', 'ปาก'],
        ['neck', 'คอ'], ['breast', 'ทรวงอก/ปอด'], ['breathe', 'การหายใจ'], ['lungs', 'ปอด'], ['heart', 'หัวใจ'], ['heart_sound', 'เสียงหัวใจ'], ['pulse', 'ชีพจร'],
        ['abdomen', 'ท้อง'], ['others', 'อื่นๆ'], ['neuro', 'ระบบประสาท'], ['movement', 'การเคลื่อนไหว']];
    const NEURO = ['neuro', 'movement'];
    const WFA = ['น้อยกว่าเกณฑ์', 'ค่อนข้างน้อย', 'ตามเกณฑ์', 'ค่อนข้างมาก', 'มากกว่าเกณฑ์'];
    const HFA = ['เตี้ย', 'ค่อนข้างเตี้ย', 'ตามเกณฑ์', 'ค่อนข้างสูง', 'สูง'];
    const WFH = ['ผอม', 'ค่อนข้างผอม', 'สมส่วน', 'ท้วม', 'เริ่มอ้วน', 'อ้วน'];

    let rounds = [], round = null, rows = [], loadSeq = 0, saving = false;
    const closed = () => !round || round.status !== 'open';
    const today = () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
    const one = (v) => (Array.isArray(v) ? (v[0] ?? '') : (v ?? ''));

    function toRow(c) {
        const rec = c.record, vs = rec?.vital_signs || {}, pm = rec?.physical_measures || {}, dv = rec?.development_assessment || {};
        const pe = rec?.physical_exam || {}, nl = rec?.neurological || {};
        const r = {
            sid: c.student_id, name: c.name, nick: c.nick, birthday: c.birthday,
            id: rec ? rec.id : null, locked: !!rec?.locked && !IS_DOCTOR, doctor: rec?.doctor_name || '', skip: false, dirty: false, err: '',
            date: vs.bp_date || rec?.exam_date || byId('gDate').value || today(), mdate: rec ? (rec.measurement_date || rec.exam_date || '') : (byId('gMDate').value || today()),
            temp: vs.temperature ?? '', bp: vs.bp ?? '', h: pm.height ?? '', w: pm.weight ?? '',
            wfa: one(pm.weight_for_age), hfa: one(pm.height_for_age), wfh: one(pm.weight_for_height),
            beh: rec?.behavior?.status ?? '', behd: rec?.behavior?.detail ?? '', dev: {}, ex: {}, rec: rec?.recommendation ?? '',
        };
        DEV.forEach(([k]) => { r.dev[k] = { s: dv[k]?.status ?? '', sc: dv[k]?.score ?? '' }; });
        EXAM.forEach(([k]) => {
            const src = NEURO.includes(k) ? nl : pe;
            r.ex[k] = { s: one(src[k]), d: src[k + '_detail'] ?? '' };
        });
        return r;
    }

    const hasData = (r) => !!(r.temp || r.bp || r.h || r.w || r.wfa || r.hfa || r.wfh || r.beh || r.rec ||
        DEV.some(([k]) => r.dev[k].s || r.dev[k].sc) || EXAM.some(([k]) => r.ex[k].s || r.ex[k].d));
    const editable = (r) => !closed() && !r.locked && !r.skip;
    const toSave = (r) => editable(r) && r.dirty && hasData(r);

    function ageParts(r) {
        if (!r.birthday || !r.date) return null;
        const b = new Date(r.birthday + 'T00:00:00'), e = new Date(r.date + 'T00:00:00');
        if (isNaN(b) || isNaN(e) || e < b) return null;
        let y = e.getFullYear() - b.getFullYear(), m = e.getMonth() - b.getMonth(), d = e.getDate() - b.getDate();
        if (d < 0) { m -= 1; d += new Date(e.getFullYear(), e.getMonth(), 0).getDate(); }
        if (m < 0) { y -= 1; m += 12; }
        return { y, m, d };
    }
    const ageText = (r) => { const a = ageParts(r); return a ? `${a.y} ปี ${a.m} ด. ${a.d} ว.` : '-'; };

    function rowState(r) {
        if (r.err) return ['is-err', 'err', r.err];
        if (r.locked) return ['is-locked', 'locked', '🔒 แพทย์ตรวจแล้ว'];
        if (r.skip) return ['is-skip', '', 'ไม่ตรวจ (ข้าม)'];
        if (toSave(r)) return ['is-dirty', 'dirty', 'พร้อมบันทึก'];
        if (r.dirty && !hasData(r)) return ['', '', 'ยังไม่ได้กรอก'];
        if (r.id) return ['is-saved', 'saved', r.doctor ? 'แพทย์ตรวจแล้ว' : (IS_DOCTOR ? 'รอแพทย์ตรวจ' : 'บันทึกแล้ว (รอแพทย์)')];
        return ['', '', 'ยังไม่ได้กรอก'];
    }

    function opts(list, cur) {
        const all = cur && !list.includes(cur) ? [...list, cur] : list;
        return '<option value=""></option>' + all.map((o) => `<option value="${esc(o)}"${o === cur ? ' selected' : ''}>${esc(o)}</option>`).join('');
    }

    function rowHtml(r, i) {
        const dis = editable(r) ? '' : ' disabled';
        const [cls, chip, text] = rowState(r);
        const num = (f, v, ph, step) => `<td><input class="f n" type="number" step="${step || '0.1'}" min="0" data-f="${f}" data-col="${f}" value="${esc(v)}" placeholder="${ph}"${dis}></td>`;
        let h = `<tr class="${cls}" data-i="${i}"><td class="c-no">${i + 1}</td>` +
            `<td class="c-nm"><div class="nick">${esc(r.nick || r.name)}</div>${r.nick ? `<div class="full">${esc(r.name)}</div>` : ''}</td>` +
            `<td><input class="f d" type="date" data-f="date" data-col="date" value="${esc(r.date)}"${dis}><div class="age" data-age>${esc(ageText(r))}</div></td>` +
            `<td><input class="f d" type="date" data-f="mdate" data-col="mdate" value="${esc(r.mdate)}"${dis}></td>` +
            num('w', r.w, 'กก.') + num('h', r.h, 'ซม.') + num('temp', r.temp, '37.0') +
            `<td><input class="f n" style="width:78px" type="text" data-f="bp" data-col="bp" value="${esc(r.bp)}" placeholder="100/60"${dis}></td>` +
            `<td><select class="f" data-f="wfa" data-col="wfa"${dis}>${opts(WFA, r.wfa)}</select></td>` +
            `<td><select class="f" data-f="hfa" data-col="hfa"${dis}>${opts(HFA, r.hfa)}</select></td>` +
            `<td><select class="f" data-f="wfh" data-col="wfh"${dis}>${opts(WFH, r.wfh)}</select></td>` +
            `<td><select class="f${r.beh ? ' s-' + r.beh : ''}" data-f="beh" data-col="beh"${dis}><option value=""></option><option value="none"${r.beh === 'none' ? ' selected' : ''}>ไม่มี</option><option value="has"${r.beh === 'has' ? ' selected' : ''}>มี</option></select>` +
                `<input class="f dtl${r.beh === 'has' || r.behd ? '' : ' hide'}" type="text" data-f="behd" data-col="behd" value="${esc(r.behd)}" placeholder="ระบุ"${dis}></td>`;
        DEV.forEach(([k]) => {
            const d = r.dev[k];
            h += `<td><select class="f${d.s ? ' s-' + d.s : ''}" data-f="dev" data-k="${k}" data-col="dev_${k}"${dis}><option value=""></option><option value="pass"${d.s === 'pass' ? ' selected' : ''}>ผ่าน</option><option value="delay"${d.s === 'delay' ? ' selected' : ''}>สงสัยล่าช้า</option></select>` +
                `<input class="f sc${d.s === 'delay' ? '' : ' hide'}" type="text" data-f="devsc" data-k="${k}" data-col="devsc_${k}" value="${esc(d.sc)}" placeholder="ข้อที่"${dis}></td>`;
        });
        EXAM.forEach(([k]) => {
            const e = r.ex[k];
            h += `<td class="ex"><select class="f${e.s ? ' s-' + e.s : ''}" data-f="ex" data-k="${k}" data-col="ex_${k}"${dis}><option value=""></option><option value="normal"${e.s === 'normal' ? ' selected' : ''}>ปกติ</option><option value="abnormal"${e.s === 'abnormal' ? ' selected' : ''}>ผิดปกติ</option></select>` +
                `<input class="f dtl${e.s === 'abnormal' || e.d ? '' : ' hide'}" type="text" data-f="exd" data-k="${k}" data-col="exd_${k}" value="${esc(e.d)}" placeholder="รายละเอียด"${dis}></td>`;
        });
        h += `<td><input class="f t" style="width:170px" type="text" data-f="rec" data-col="rec" value="${esc(r.rec)}" placeholder="คำแนะนำ"${dis}></td>` +
            `<td><span class="st ${chip}" data-st>${esc(text)}</span></td>` +
            `<td>${closed() || r.locked ? '' : `<button type="button" class="rowact" data-normal="1" title="พฤติกรรม none, พัฒนาการ pass, ตรวจร่างกาย normal">ปกติ</button> <button type="button" class="rowact skip${r.skip ? ' on' : ''}" data-skip="1" title="เด็กไม่มาตรวจ / ข้ามคนนี้">${r.skip ? 'ยกเลิกข้าม' : 'ข้าม'}</button>`}</td></tr>`;
        return h;
    }

    function header() {
        const fill = (kind, key, title) => (closed() ? '' : `<button type="button" class="fillcol" data-fill="${kind}" data-k="${key}" title="เติมค่าปกติให้ช่องว่างของทุกคน">${title}</button>`);
        return '<thead><tr class="g1"><th class="c-no" rowspan="2">#</th><th class="c-nm" rowspan="2">เด็ก</th><th class="gh-info" rowspan="2">วันที่ตรวจ / อายุ</th><th class="gh-body" rowspan="2">วันที่ชั่ง/วัด</th>' +
            '<th class="gh-body" colspan="7">การเจริญเติบโต</th><th class="gh-beh">ปัญหาด้านพฤติกรรม</th><th class="gh-dev" colspan="5">พัฒนาการ</th>' +
            `<th class="gh-exam ex" colspan="${EXAM.length}">ตรวจร่างกาย</th><th class="gh-info" rowspan="2">คำแนะนำ</th><th class="gh-st" rowspan="2">สถานะ</th><th class="gh-st" rowspan="2"></th></tr>` +
            '<tr class="g2"><th class="gh-body">น้ำหนัก</th><th class="gh-body">ส่วนสูง</th><th class="gh-body">อุณหภูมิ</th><th class="gh-body">BP</th>' +
            '<th class="gh-body">น้ำหนัก/อายุ</th><th class="gh-body">ส่วนสูง/อายุ</th><th class="gh-body">น้ำหนัก/ส่วนสูง</th>' +
            `<th class="gh-beh">ไม่มี/มี ${fill('beh', 'beh', '✓')}</th>` +
            DEV.map(([k, l]) => `<th class="gh-dev">${l} ${fill('dev', k, '✓')}</th>`).join('') +
            EXAM.map(([k, l]) => `<th class="gh-exam ex">${l} ${fill('ex', k, '✓')}</th>`).join('') + '</tr></thead>';
    }

    function render() {
        const wrap = byId('gWrap');
        if (!rows.length) { wrap.innerHTML = '<div class="hg-empty"><div class="fs-5 fw-bold">ไม่พบรายชื่อเด็กตามเงื่อนไข</div>ลองเปลี่ยนกลุ่ม ห้อง หรือคำค้นหา</div>'; summary(); return; }
        const keep = { top: wrap.scrollTop, left: wrap.scrollLeft };
        wrap.innerHTML = `<table class="hg">${header()}<tbody>${rows.map(rowHtml).join('')}</tbody></table>`;
        wrap.scrollTop = keep.top; wrap.scrollLeft = keep.left;
        summary();
    }

    function refreshRow(i) {
        const tr = byId('gWrap').querySelector(`tr[data-i="${i}"]`);
        if (!tr) return;
        const r = rows[i], [cls, chip, text] = rowState(r);
        tr.className = cls;
        const st = tr.querySelector('[data-st]'); st.className = 'st ' + chip; st.textContent = text;
        const age = tr.querySelector('[data-age]'); if (age) age.textContent = ageText(r);
        summary();
    }

    function replaceRow(i) {
        const tr = byId('gWrap').querySelector(`tr[data-i="${i}"]`);
        if (tr) { tr.outerHTML = rowHtml(rows[i], i); }
        summary();
    }

    function summary() {
        const total = rows.length, locked = rows.filter((r) => r.locked).length, skipped = rows.filter((r) => r.skip).length;
        const saved = rows.filter((r) => r.id && !toSave(r)).length, pending = rows.filter(toSave).length;
        const target = total - skipped;
        const done = rows.filter((r) => !r.skip && (r.locked || (r.id && !toSave(r)))).length;
        byId('gProgress').style.display = total ? '' : 'none';
        byId('gProgressText').textContent = `บันทึกแล้ว ${done} / ${target} คน`;
        byId('gProgressBar').style.width = (target ? Math.round((done / target) * 100) : 0) + '%';
        byId('gPills').innerHTML = `<span class="hg-chip">ทั้งหมด ${total}</span><span class="hg-chip open">บันทึกแล้ว ${saved}</span>` +
            `<span class="hg-chip" style="background:#dbeafe;color:#1d4ed8">รอบันทึก ${pending}</span>` +
            (locked ? `<span class="hg-chip">🔒 แพทย์ตรวจแล้ว ${locked}</span>` : '') + (skipped ? `<span class="hg-chip">ข้าม ${skipped}</span>` : '');
        const btn = byId('gSave');
        btn.disabled = saving || closed() || !pending;
        btn.innerHTML = `<i class="fas fa-save me-1"></i>${pending ? `บันทึก ${pending} คน` : 'บันทึก'}`;
        byId('gSaveHint').textContent = closed() ? '' : (pending ? '' : 'กรอกข้อมูลแล้วกดบันทึก');
    }

    // ---- editing ----
    function applyInput(el) {
        const tr = el.closest('tr'); if (!tr) return false;
        const i = +tr.dataset.i, r = rows[i], f = el.dataset.f, k = el.dataset.k;
        if (!editable(r)) return false;
        const v = el.value.trim();
        if (f === 'dev') r.dev[k].s = v;
        else if (f === 'devsc') r.dev[k].sc = v;
        else if (f === 'ex') r.ex[k].s = v;
        else if (f === 'exd') r.ex[k].d = v;
        else r[f] = v;
        r.dirty = true; r.err = '';
        if (f === 'dev' || f === 'ex' || f === 'beh') el.className = 'f' + (v ? ' s-' + v : '');
        if (f === 'beh') tr.querySelector('[data-f="behd"]')?.classList.toggle('hide', v !== 'has');
        if (f === 'dev') tr.querySelector(`[data-f="devsc"][data-k="${k}"]`)?.classList.toggle('hide', v !== 'delay');
        if (f === 'ex') { const d = tr.querySelector(`[data-f="exd"][data-k="${k}"]`); if (d) d.classList.toggle('hide', v !== 'abnormal' && !d.value); }
        refreshRow(i);
        return true;
    }

    function fillNormal(r, onlyBlank) {
        if (!editable(r)) return false;
        let changed = false;
        const set = (cur, val) => { if (onlyBlank && cur) return cur; if (cur !== val) changed = true; return val; };
        r.beh = set(r.beh, 'none');
        DEV.forEach(([k]) => { r.dev[k].s = set(r.dev[k].s, 'pass'); });
        EXAM.forEach(([k]) => { r.ex[k].s = set(r.ex[k].s, 'normal'); });
        if (changed) { r.dirty = true; r.err = ''; }
        return changed;
    }

    function fillColumn(kind, key) {
        let n = 0;
        rows.forEach((r) => {
            if (!editable(r)) return;
            if (kind === 'beh' && !r.beh) { r.beh = 'none'; r.dirty = true; n++; }
            else if (kind === 'dev' && !r.dev[key].s) { r.dev[key].s = 'pass'; r.dirty = true; n++; }
            else if (kind === 'ex' && !r.ex[key].s) { r.ex[key].s = 'normal'; r.dirty = true; n++; }
        });
        render();
        toast(n ? `เติมให้ ${n} คน` : 'ไม่มีช่องว่างให้เติม');
    }

    function toast(title) { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title, showConfirmButton: false, timer: 1400 }); }

    // ---- load ----
    const dirtyCount = () => rows.filter(toSave).length;

    async function confirmDiscard() {
        if (!dirtyCount()) return true;
        const r = await Swal.fire({ icon: 'warning', title: 'มีข้อมูลที่ยังไม่ได้บันทึก', text: `${dirtyCount()} คน จะหายไปถ้าโหลดใหม่`, showCancelButton: true, confirmButtonText: 'โหลดใหม่', cancelButtonText: 'กลับไปบันทึก' });
        return r.isConfirmed;
    }

    async function load() {
        const roundId = byId('gRound').value;
        if (!roundId) { byId('gWrap').innerHTML = '<div class="hg-empty">ยังไม่มีรอบตรวจ ให้ผู้ดูแลระบบเปิดรอบตรวจก่อน</div>'; summary(); return; }
        if (!byId('gGroup').value && !byId('gRoom').value && !byId('gSearch').value.trim()) {
            rows = []; summary();
            byId('gWrap').innerHTML = '<div class="hg-empty"><div class="fs-5 fw-bold">เลือกกลุ่มหรือห้องเรียนด้านบน</div>รายชื่อเด็กจะขึ้นให้อัตโนมัติ แล้วกรอกต่อในตารางได้เลย</div>';
            return;
        }
        const seq = ++loadSeq;
        const q =new URLSearchParams({ round_id: roundId, child_group: byId('gGroup').value, classroom: byId('gRoom').value, student_year: byId('gStudentYear').value, search: byId('gSearch').value.trim() });
        byId('gWrap').innerHTML = '<div class="hg-empty">กำลังโหลด...</div>';
        try {
            const res = await (await fetch('./function/get_health_grid.php?' + q, { headers: XHR })).json();
            if (seq !== loadSeq) return;
            if (res.status !== 'success') throw new Error(res.message);
            round = res.round;
            rows = res.data.map(toRow);
            byId('gClosed').style.display = closed() ? '' : 'none';
            ['gApplyDate', 'gApplyMDate', 'gFillNormal', 'gDate', 'gMDate'].forEach((id) => { byId(id).disabled = closed(); });
            render();
        } catch (e) {
            byId('gWrap').innerHTML = `<div class="hg-empty text-danger">โหลดไม่สำเร็จ: ${esc(e.message)}</div>`;
        }
    }

    async function loadClassrooms(keep) {
        const g = byId('gGroup').value, sel = byId('gRoom');
        sel.innerHTML = '<option value="">ทุกห้อง</option>';
        if (!g) return;
        try {
            const data = await (await fetch('../../include/function/get_classrooms.php?child_group=' + encodeURIComponent(g))).json();
            data.forEach((c) => { const o = document.createElement('option'); o.value = o.textContent = c.classroom_name; sel.appendChild(o); });
            if (keep) sel.value = keep;
        } catch (e) { /* ใช้ "ทุกห้อง" ต่อไป */ }
    }

    async function loadRounds(selectedId) {
        const res = await (await fetch('./process/manage_health_rounds.php?action=list', { headers: XHR })).json();
        rounds = (res.data || []).filter((r) => !IS_DOCTOR || r.status === 'open');   // แพทย์เห็นเฉพาะรอบที่เปิดอยู่
        const sel = byId('gRound');
        sel.innerHTML = rounds.map((r) => `<option value="${r.id}">${esc(r.academic_year)} · ${esc(r.title)}${r.status === 'open' ? '' : ' (ปิดแล้ว)'}</option>`).join('');
        const pick = rounds.find((r) => String(r.id) === String(selectedId)) || rounds.find((r) => r.status === 'open') || rounds[0];
        if (pick) sel.value = pick.id;
        roundChips();
    }

    function roundChips() {
        const r = rounds.find((x) => String(x.id) === String(byId('gRound').value));
        byId('gRoundChips').innerHTML = r
            ? `<span class="chip ${r.status === 'open' ? 'open' : 'closed'}">${r.status === 'open' ? 'เปิดอยู่' : 'ปิดแล้ว'}</span><span class="chip">ตรวจแล้ว ${r.doctor_count ?? 0} คน</span>`
            : '';
    }

    // ---- save ----
    function buildRecord(r) {
        const a = ageParts(r);
        const pe = {}, nl = {}, dev = {};
        EXAM.forEach(([k]) => {
            const t = NEURO.includes(k) ? nl : pe, e = r.ex[k];
            t[k] = e.s ? [e.s] : []; t[k + '_detail'] = e.d;
        });
        DEV.forEach(([k]) => { dev[k] = { status: r.dev[k].s, score: r.dev[k].s === 'delay' ? r.dev[k].sc : '' }; });
        return {
            student_id: r.sid, exam_date: r.date, measurement_date: r.mdate || r.date, birth_date: r.birthday,
            age_year: a ? a.y : null, age_month: a ? a.m : null, age_day: a ? a.d : null,
            vital_signs: { temperature: r.temp, bp: r.bp, bp_date: r.date }, behavior: { status: r.beh, detail: r.beh === 'has' ? r.behd : '' },
            physical_measures: { height: r.h, weight: r.w, weight_for_age: r.wfa, height_for_age: r.hfa, weight_for_height: r.wfh },
            development_assessment: dev, physical_exam: pe, neurological: nl, recommendation: r.rec,
        };
    }

    async function save() {
        const list = rows.filter(toSave);
        if (!list.length || saving) return;
        const ok = await Swal.fire({ icon: 'question', title: `บันทึกผลตรวจ ${list.length} คน?`, text: `รอบ: ${round.title} (ปีการศึกษา ${round.academic_year})`, showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#15803d' });
        if (!ok.isConfirmed) return;
        saving = true; summary();
        Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await (await fetch('./process/save_health_grid.php', {
                method: 'POST', headers: { ...XHR, 'Content-Type': 'application/json' },
                body: JSON.stringify({ round_id: round.id, records: list.map(buildRecord) }),
            })).json();
            const savedIds = new Map((res.saved || []).map((s) => [s.student_id, s.id]));
            const errs = new Map((res.errors || []).map((e) => [e.student_id, e.message]));
            rows.forEach((r) => {
                if (savedIds.has(r.sid)) { r.id = savedIds.get(r.sid); r.dirty = false; r.err = ''; if (IS_DOCTOR) r.doctor = res.doctor_name || DOCTOR_NAME; }
                if (errs.has(r.sid)) r.err = errs.get(r.sid);
            });
            render();
            if (res.status !== 'success' && !savedIds.size) throw new Error(res.message || 'บันทึกไม่สำเร็จ');
            const bad = (res.errors || []);
            Swal.fire({
                icon: bad.length ? 'warning' : 'success', title: bad.length ? 'บันทึกได้บางส่วน' : 'บันทึกเรียบร้อย',
                html: `สำเร็จ ${savedIds.size} คน` + (bad.length ? `<br><small class="text-danger">ไม่สำเร็จ ${bad.length} คน (ดูแถวสีแดงในตาราง)</small>` : ''),
                timer: bad.length ? undefined : 1800, showConfirmButton: !!bad.length,
            });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: e.message });
        } finally { saving = false; summary(); }
    }

    // ---- wiring ----
    document.addEventListener('DOMContentLoaded', async () => {
        const url = new URLSearchParams(location.search);
        byId('gDate').value = today();
        byId('gMDate').value = today();
        await loadRounds(url.get('round_id'));
        if (url.get('child_group')) byId('gGroup').value = url.get('child_group');
        await loadClassrooms(url.get('classroom'));
        if (url.get('search')) byId('gSearch').value = url.get('search');

        const reload = async () => { if (await confirmDiscard()) { rows.forEach((r) => { r.dirty = false; }); load(); } };
        byId('gRound').addEventListener('change', async () => { roundChips(); if (await confirmDiscard()) { rows.forEach((r) => { r.dirty = false; }); load(); } else { byId('gRound').value = round ? round.id : ''; } });
        byId('gGroup').addEventListener('change', async () => { if (!await confirmDiscard()) { return; } rows.forEach((r) => { r.dirty = false; }); await loadClassrooms(); load(); });
        byId('gRoom').addEventListener('change', reload);
        byId('gStudentYear').addEventListener('change', reload);
        byId('gReload').addEventListener('click', reload);
        let timer; byId('gSearch').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(reload, 450); });

        byId('gApplyDate').addEventListener('click', () => {
            const d = byId('gDate').value; if (!d) { Swal.fire('กรุณาเลือกวันที่ตรวจ', '', 'info'); return; }
            let n = 0; rows.forEach((r) => { if (editable(r)) { r.date = d; if (r.id) r.dirty = true; n++; } });
            render(); toast(`ตั้งวันที่ให้ ${n} คน`);
        });
        byId('gApplyMDate').addEventListener('click', () => {
            const d = byId('gMDate').value; if (!d) { Swal.fire('กรุณาเลือกวันที่ชั่งน้ำหนัก / วัดส่วนสูง', '', 'info'); return; }
            let n = 0; rows.forEach((r) => { if (editable(r)) { r.mdate = d; if (r.id) r.dirty = true; n++; } });
            render(); toast(`ตั้งวันที่ชั่ง/วัดให้ ${n} คน`);
        });
        byId('gFillNormal').addEventListener('click', () => {
            let n = 0; rows.forEach((r) => { if (fillNormal(r, true)) n++; });
            render(); toast(n ? `เติมค่าปกติให้ ${n} คน` : 'ไม่มีช่องว่างให้เติม');
        });
        byId('gSave').addEventListener('click', save);
        const wrap = byId('gWrap');
        const onChange = (e) => { const el = e.target; if (el.dataset && el.dataset.f) applyInput(el); };
        wrap.addEventListener('input', (e) => { if (e.target.matches('input')) onChange(e); });
        wrap.addEventListener('change', (e) => { if (e.target.matches('select, input[type=date]')) onChange(e); });
        wrap.addEventListener('click', (e) => {
            const fc = e.target.closest('[data-fill]'); if (fc) { fillColumn(fc.dataset.fill, fc.dataset.k); return; }
            const tr = e.target.closest('tr[data-i]'); if (!tr) return;
            const i = +tr.dataset.i, r = rows[i];
            if (e.target.closest('[data-normal]')) { fillNormal(r, false); replaceRow(i); }
            else if (e.target.closest('[data-skip]')) { r.skip = !r.skip; replaceRow(i); }
        });
        // Enter = ช่องเดียวกันของคนถัดไป (Shift+Enter = คนก่อนหน้า)
        wrap.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' || !e.target.dataset?.col) return;
            e.preventDefault();
            const all = [...wrap.querySelectorAll(`[data-col="${e.target.dataset.col}"]:not(:disabled):not(.hide)`)];
            const next = all[all.indexOf(e.target) + (e.shiftKey ? -1 : 1)];
            if (next) { next.focus(); if (next.select) next.select(); next.scrollIntoView({ block: 'nearest', inline: 'nearest' }); }
        });
        window.addEventListener('beforeunload', (e) => { if (dirtyCount()) { e.preventDefault(); e.returnValue = ''; } });

        load();
    });
</script>
</body>
</html>
