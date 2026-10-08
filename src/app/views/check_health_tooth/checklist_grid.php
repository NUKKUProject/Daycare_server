<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/function/child_functions.php';
require_once __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';

$academicYears = getAcademicYears();
$currentTop = isset($academicYears[0]['name']) ? (int) $academicYears[0]['name'] : null;
$examYears = [];
if ($currentTop) {
    $examYears[] = $currentTop + 1;
    foreach ($academicYears as $y) {
        $examYears[] = (int) $y['name'];
    }
}
$groups = get_childgroup();
$defaultDoctor = getFullName();
?>

<style>
    .tg-bar label { font-size: .8rem; font-weight: 700; color: #475569; margin-bottom: .2rem; }
    .tg-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .tg-summary { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: .6rem; font-size: .85rem; }
    .tg-pill { border-radius: 999px; padding: 2px 12px; font-weight: 700; font-size: .8rem; }
    .tg-pill.saved { background: #dcfce7; color: #15803d; }
    .tg-pill.ready { background: #dbeafe; color: #1d4ed8; }
    .tg-pill.todo { background: #fef3c7; color: #b45309; }

    .tg-wrap { overflow: auto; max-height: 70vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    table.tg { border-collapse: separate; border-spacing: 0; min-width: 1500px; width: 100%; font-size: .85rem; }
    .tg th { position: sticky; top: 0; z-index: 3; background: #f1f5f9; color: #475569; font-weight: 700; font-size: .75rem;
        padding: 6px 4px; border-bottom: 1px solid #cbd5e1; text-align: center; white-space: nowrap; }
    .tg td { padding: 3px; border-bottom: 1px solid #e2e8f0; text-align: center; background: #fff; }
    .tg .c-no { position: sticky; left: 0; z-index: 2; min-width: 38px; background: #fff; }
    .tg .c-nm { position: sticky; left: 38px; z-index: 2; min-width: 170px; text-align: left; padding: 3px 8px; background: #fff; border-right: 1px solid #cbd5e1; }
    .tg th.c-no, .tg th.c-nm { z-index: 4; background: #f1f5f9; }
    .tg .nick { font-weight: 700; color: #0f2460; line-height: 1.15; }
    .tg .full { font-size: .72rem; color: #64748b; line-height: 1.15; }
    .tg input.f, .tg select.f { height: 30px; font-size: .85rem; padding: 0 4px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; width: 100%; }
    .tg input.f:focus, .tg select.f:focus { outline: 2px solid #3b82f6; outline-offset: -1px; }
    .tg input.n { width: 46px; text-align: center; }
    .tg input.t { min-width: 130px; }
    .tg select.f { min-width: 92px; }
    .tg td.pos.bad input { background: #fef3c7; border-color: #f59e0b; }
    .tg tr td { transition: background-color .15s; }
    .tg tr.saved td { background: #f0fdf4; }
    .tg tr.ready td { background: #eff6ff; }
    .tg tr.saved .c-no, .tg tr.saved .c-nm { background: #f0fdf4; box-shadow: inset 4px 0 0 #22c55e; }
    .tg tr.ready .c-nm { background: #eff6ff; box-shadow: inset 4px 0 0 #3b82f6; }
    .tg tr.todo .c-nm { box-shadow: inset 4px 0 0 #f59e0b; }
    .tg .tgl { width: 24px; height: 24px; border: 1px solid #94a3b8; border-radius: 5px; background: #fff; cursor: pointer; padding: 0; line-height: 1; font-size: .8rem; }
    .tg .tgl.on { background: #dbeafe; border-color: #3b82f6; color: #1d4ed8; font-weight: 700; }
    .tg .st { font-size: .72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; white-space: nowrap; }
    .tg .st.saved { background: #dcfce7; color: #15803d; }
    .tg .st.ready { background: #dbeafe; color: #1d4ed8; }
    .tg .st.todo { background: #fef3c7; color: #b45309; }
    .tg .rowact { border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; font-size: .72rem; padding: 2px 8px; cursor: pointer; white-space: nowrap; }
    .tg .rowact:hover { background: #f1f5f9; }
    .tg-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .tg-hint { font-size: .78rem; color: #64748b; margin-top: .5rem; }
    .tg-actions { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .btn-save-grid { background: #15803d; border-color: #15803d; color: #fff; font-weight: 700; }
    .btn-save-grid:hover { background: #166534; border-color: #166534; color: #fff; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h2 class="mb-0">กรอกผลตรวจสุขภาพช่องปากทั้งห้อง</h2>
            <a href="checklist_name.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>กลับหน้ารายชื่อ</a>
        </div>

        <div class="tg-card tg-bar">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label for="gGroup">กลุ่มเรียน</label>
                    <select id="gGroup" class="form-select">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($groups as $g): if (!empty($g['child_group'])): ?>
                            <option value="<?= htmlspecialchars($g['child_group']) ?>"><?= htmlspecialchars($g['child_group']) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="gRoom">ห้องเรียน</label>
                    <select id="gRoom" class="form-select"><option value="">ทุกห้อง</option></select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="gYear">ตรวจประจำปีการศึกษา</label>
                    <select id="gYear" class="form-select">
                        <?php foreach ($examYears as $i => $y): ?>
                            <option value="<?= $y ?>" <?= $i === 1 ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="gStudentYear">ปีการศึกษาของเด็ก</label>
                    <select id="gStudentYear" class="form-select">
                        <option value="all">ทั้งหมด</option>
                        <?php foreach ($academicYears as $y): ?>
                            <option value="<?= htmlspecialchars($y['name']) ?>"><?= htmlspecialchars($y['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="gDate">วันที่ตรวจ</label>
                    <input type="date" id="gDate" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label for="gDoctor">แพทย์/พยาบาล</label>
                    <input type="text" id="gDoctor" class="form-control" value="<?= htmlspecialchars($defaultDoctor) ?>" maxlength="100">
                </div>
                <div class="col-12 tg-actions">
                    <button type="button" class="btn btn-primary" id="gLoad"><i class="bi bi-search me-1"></i>โหลดรายชื่อ</button>
                    <button type="button" class="btn btn-outline-primary" id="gFillNormal" disabled><i class="bi bi-magic me-1"></i>เติมค่าปกติให้แถวที่ยังว่าง</button>
                    <button type="button" class="btn btn-save-grid ms-auto" id="gSave" disabled><i class="bi bi-check-circle me-1"></i>บันทึกทั้งห้อง</button>
                </div>
            </div>
        </div>

        <div class="tg-card">
            <div class="tg-summary" id="gSummary"></div>
            <div class="tg-wrap" id="gWrap"><div class="tg-empty">เลือกกลุ่มหรือห้องเรียน แล้วกด "โหลดรายชื่อ"</div></div>
            <div class="tg-hint">
                Tab ไปช่องถัดไป · Enter ลงแถวถัดไปในคอลัมน์เดียวกัน · เลือก "ไม่มีฟันผุ" ระบบเติมฟันผุและตำแหน่งเป็น 0 ให้ ·
                ช่องตำแหน่งขึ้นเหลืองเมื่อยอดรวมไม่เท่ากับจำนวนฟันผุ · อายุคำนวณจากวันเกิด ณ วันที่ตรวจ ·
                ตัวย่อการรักษา: อุด = อุดฟัน, ฟล = เคลือบฟลูออไรด์, รา = รักษาคลองรากฟัน, กร = เคลือบหลุมร่องฟันที่ฟันกราม, คร = ครอบฟัน, ถอ = ถอนฟัน, อื่ = อื่นๆ
            </div>
        </div>
    </div>
</main>

<script>
    const API_LIST = './function/get_tooth_grid.php';
    const API_SAVE = './process/save_health_tooth_bulk.php';
    const POS = [
        ['upper_front_teeth', 'หน้าบน'], ['upper_right_molar', 'กรามขวาบน'], ['lower_right_molar', 'กรามขวาล่าง'],
        ['lower_front_teeth', 'หน้าล่าง'], ['upper_left_molar', 'กรามซ้ายบน'], ['lower_left_molar', 'กรามซ้ายล่าง']
    ];
    const TR = [
        ['filling', 'อุด', 'อุดฟัน'], ['fluoride', 'ฟล', 'เคลือบฟลูออไรด์'], ['root_canal', 'รา', 'รักษาคลองรากฟัน'],
        ['fluoride_molar', 'กร', 'เคลือบหลุมร่องฟันที่ฟันกราม'], ['crown', 'คร', 'ครอบฟัน'], ['extraction', 'ถอ', 'ถอนฟัน'], ['other', 'อื่', 'อื่นๆ']
    ];
    const URG = [['urgent', 'ด่วน'], ['not_urgent', 'ไม่เร่งด่วน'], ['preventable', 'ผัดผ่อนได้']];

    let rows = [];
    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const num = (v) => (v === null || v === undefined || v === '' ? null : Math.max(0, parseInt(v, 10)));

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

    function toRow(r) {
        const saved = !!r.record_id;
        const p = r.decayed_teeth_positions || {};
        return {
            sid: r.studentid, nick: r.nickname || '', name: [r.prefix_th, r.firstname_th, r.lastname_th].filter(Boolean).join(' '),
            room: r.classroom, birthday: r.birthday,
            tt: saved ? num(r.total_teeth) : null, dc: saved ? num(r.decayed_teeth) : null,
            p: POS.map(([k]) => (saved ? num(p[k]) ?? 0 : null)),
            s: saved ? (r.teeth_status || '') : '', u: saved ? (r.urgency || '') : '',
            t: saved ? (r.treatments || []) : [], oral: saved ? (r.oral_components || '') : '',
            miss: saved ? (r.missing_teeth_detail || '') : '', other: saved ? (r.other_treatment_detail || '') : '',
            saved: saved, dirty: false
        };
    }

    const posSum = (r) => r.p.reduce((a, b) => a + (b || 0), 0);
    function complete(r) {
        if (r.tt === null || !r.s) return false;
        if (r.s === 'normal') return r.dc === 0;
        return r.dc !== null && r.dc > 0 && posSum(r) === r.dc && !!r.u;
    }
    const rowState = (r) => (r.dirty ? (complete(r) ? 'ready' : 'todo') : (r.saved ? 'saved' : 'todo'));
    const STATE_TEXT = { saved: 'บันทึกแล้ว', ready: 'พร้อมบันทึก', todo: 'ยังไม่ครบ' };

    function header() {
        return '<thead><tr><th class="c-no">#</th><th class="c-nm">ชื่อ</th><th>อายุ</th><th>ฟันทั้งหมด</th><th>ฟันผุ</th>' +
            POS.map((p) => `<th>${p[1]}</th>`).join('') + '<th>สภาพฟัน</th><th>ความเร่งด่วน</th>' +
            TR.map((t) => `<th title="${esc(t[2])}">${t[1]}</th>`).join('') +
            '<th>รักษาอื่นๆ (ระบุ)</th><th>ช่องปาก</th><th>รายละเอียดอื่น</th><th>สถานะ</th><th></th></tr></thead>';
    }

    function rowHtml(r, i) {
        const st = rowState(r), bad = r.s === 'abnormal' && r.dc !== null && posSum(r) !== r.dc;
        const v = (x) => (x === null ? '' : x);
        let h = `<tr class="${st}" data-i="${i}"><td class="c-no">${i + 1}</td>` +
            `<td class="c-nm"><div class="nick">${r.nick ? esc(r.nick) : esc(r.name)}</div>${r.nick ? `<div class="full">${esc(r.name)}</div>` : ''}</td>` +
            `<td>${esc(ageText(r.birthday))}</td>` +
            `<td><input class="f n" type="number" min="0" max="32" data-f="tt" value="${v(r.tt)}"></td>` +
            `<td><input class="f n" type="number" min="0" max="32" data-f="dc" value="${v(r.dc)}"></td>`;
        r.p.forEach((x, k) => { h += `<td class="pos${bad ? ' bad' : ''}"><input class="f n" type="number" min="0" data-p="${k}" value="${v(x)}"></td>`; });
        h += `<td><select class="f" data-f="s" data-key="s"><option value="">-</option><option value="normal"${r.s === 'normal' ? ' selected' : ''}>ไม่มีฟันผุ</option><option value="abnormal"${r.s === 'abnormal' ? ' selected' : ''}>มีฟันผุ</option></select></td>`;
        h += `<td><select class="f" data-f="u"><option value="">-</option>${URG.map((u) => `<option value="${u[0]}"${r.u === u[0] ? ' selected' : ''}>${u[1]}</option>`).join('')}</select></td>`;
        TR.forEach((t) => { const on = r.t.includes(t[0]); h += `<td><button type="button" class="tgl${on ? ' on' : ''}" data-t="${t[0]}" title="${esc(t[2])}" aria-label="${esc(t[2])}" aria-pressed="${on}">${on ? '✓' : ''}</button></td>`; });
        h += `<td><input class="f t" type="text" maxlength="200" data-f="other" value="${esc(r.other)}"${r.t.includes('other') ? '' : ' disabled'}></td>` +
            `<td><input class="f t" type="text" maxlength="100" data-f="oral" value="${esc(r.oral)}"></td>` +
            `<td><input class="f t" type="text" maxlength="100" data-f="miss" value="${esc(r.miss)}"></td>` +
            `<td><span class="st ${st}">${STATE_TEXT[st]}</span></td>` +
            `<td><button type="button" class="rowact" data-normal="1" title="เติมค่าปกติ: ไม่มีฟันผุ (ถ้ายังไม่กรอกจำนวนฟันทั้งหมด จะใส่ 20 ซี่)">ปกติ</button></td></tr>`;
        return h;
    }

    function render(keepFocus) {
        const wrap = byId('gWrap');
        if (!rows.length) { wrap.innerHTML = '<div class="tg-empty">ไม่พบรายชื่อเด็กตามเงื่อนไข</div>'; summary(); return; }
        const sc = { top: wrap.scrollTop, left: wrap.scrollLeft };
        wrap.innerHTML = '<table class="tg">' + header() + '<tbody>' + rows.map(rowHtml).join('') + '</tbody></table>';
        wrap.scrollTop = sc.top; wrap.scrollLeft = sc.left;
        if (keepFocus) {
            const el = wrap.querySelector(`tr[data-i="${keepFocus.i}"] [data-key="${keepFocus.key}"]`);
            if (el) el.focus();
        }
        summary();
    }

    // อัปเดตเฉพาะแถวเดียว เพื่อไม่ให้ช่องที่กำลังพิมพ์หลุดโฟกัส
    function refreshRow(i) {
        const tr = byId('gWrap').querySelector(`tr[data-i="${i}"]`);
        if (!tr) return;
        const r = rows[i], st = rowState(r), bad = r.s === 'abnormal' && r.dc !== null && posSum(r) !== r.dc;
        tr.className = st;
        const chip = tr.querySelector('.st'); chip.className = 'st ' + st; chip.textContent = STATE_TEXT[st];
        tr.querySelectorAll('td.pos').forEach((td) => td.classList.toggle('bad', bad));
        tr.querySelector('[data-f="other"]').disabled = !r.t.includes('other');
        summary();
    }

    function summary() {
        const c = { saved: 0, ready: 0, todo: 0 };
        rows.forEach((r) => c[rowState(r)]++);
        byId('gSummary').innerHTML = rows.length
            ? `<span>ทั้งหมด ${rows.length} คน</span><span class="tg-pill saved">บันทึกแล้ว ${c.saved}</span><span class="tg-pill ready">พร้อมบันทึก ${c.ready}</span><span class="tg-pill todo">ยังไม่ครบ ${c.todo}</span>`
            : '';
        const anyDirty = rows.some((r) => r.dirty);
        byId('gSave').disabled = !anyDirty;
        byId('gFillNormal').disabled = !rows.length;
        window.__dirty = anyDirty;
    }

    function setNormal(r) {
        r.tt = r.tt === null ? 20 : r.tt; r.dc = 0; r.p = POS.map(() => 0); r.s = 'normal'; r.u = ''; r.t = []; r.other = ''; r.dirty = true;
    }

    async function loadList() {
        const group = byId('gGroup').value, room = byId('gRoom').value;
        if (!group && !room) { Swal.fire({ icon: 'info', title: 'เลือกกลุ่มหรือห้องเรียนก่อน', confirmButtonText: 'ตกลง' }); return; }
        if (window.__dirty && !(await Swal.fire({ icon: 'warning', title: 'มีข้อมูลที่ยังไม่บันทึก', text: 'โหลดรายชื่อใหม่จะทิ้งข้อมูลที่กรอกค้างไว้', showCancelButton: true, confirmButtonText: 'โหลดใหม่', cancelButtonText: 'กลับไปกรอกต่อ' })).isConfirmed) return;
        byId('gWrap').innerHTML = '<div class="tg-empty"><div class="spinner-border text-primary"></div></div>';
        try {
            const q = new URLSearchParams({ academic_year: byId('gYear').value, student_year: byId('gStudentYear').value, child_group: group, classroom: room });
            const res = await fetch(`${API_LIST}?${q}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'โหลดไม่สำเร็จ');
            rows = data.data.map(toRow);
            render();
        } catch (e) {
            rows = []; byId('gWrap').innerHTML = `<div class="tg-empty">${esc(e.message)}</div>`; summary();
        }
    }

    async function saveAll() {
        const dirty = rows.filter((r) => r.dirty);
        const ready = dirty.filter(complete), notReady = dirty.filter((r) => !complete(r));
        if (!ready.length) { Swal.fire({ icon: 'warning', title: 'ยังไม่มีแถวที่พร้อมบันทึก', text: 'กรอกให้ครบทุกช่องที่จำเป็นของแถวที่แก้ไข', confirmButtonText: 'ตกลง' }); return; }
        if (!byId('gDoctor').value.trim()) { Swal.fire({ icon: 'warning', title: 'กรุณาระบุชื่อแพทย์/พยาบาล', confirmButtonText: 'ตกลง' }); byId('gDoctor').focus(); return; }
        const ok = await Swal.fire({
            icon: 'question', title: `บันทึก ${ready.length} คน?`,
            html: (notReady.length ? `ข้าม <b>${notReady.length}</b> คนที่กรอกยังไม่ครบ (ข้อมูลที่กรอกไว้ยังอยู่ในตาราง)<br>` : '') + 'เด็กที่เคยมีผลตรวจของปีนี้อยู่แล้วจะถูกอัปเดตทับ',
            showCancelButton: true, confirmButtonText: 'ยืนยันบันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#15803d'
        });
        if (!ok.isConfirmed) return;
        const btn = byId('gSave'); btn.disabled = true;
        try {
            const body = {
                academic_year: byId('gYear').value, exam_date: byId('gDate').value, doctor_name: byId('gDoctor').value.trim(),
                rows: ready.map((r) => ({
                    student_id: r.sid, total_teeth: r.tt, decayed_teeth: r.dc, teeth_status: r.s, urgency: r.u || null,
                    positions: Object.fromEntries(POS.map(([k], i) => [k, r.p[i] || 0])), treatments: r.t,
                    other_treatment_detail: r.other, oral_components: r.oral, missing_teeth_detail: r.miss
                }))
            };
            const res = await fetch(API_SAVE, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'บันทึกไม่สำเร็จ');
            const failed = new Set((data.errors || []).map((e) => e.student_id));
            ready.forEach((r) => { if (!failed.has(r.sid)) { r.saved = true; r.dirty = false; } });
            render();
            Swal.fire({ icon: failed.size ? 'warning' : 'success', title: data.message, timer: failed.size ? undefined : 1800, showConfirmButton: !!failed.size, confirmButtonText: 'ตกลง',
                text: failed.size ? data.errors.map((e) => `${e.student_id}: ${e.message}`).join('\n') : '' });
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
        r.dirty = true;
        if (el.dataset.f === 's') {
            if (el.value === 'normal') { r.dc = 0; r.p = POS.map(() => 0); r.u = ''; }
            render({ i, key: 's' });   // ช่องอื่นในแถวเปลี่ยนค่า จึงวาดใหม่
        } else {
            refreshRow(i);
        }
    }

    function init() {
        byId('gDate').value = todayStr();
        byId('gGroup').addEventListener('change', loadRooms);
        byId('gLoad').addEventListener('click', loadList);
        byId('gSave').addEventListener('click', saveAll);
        byId('gFillNormal').addEventListener('click', () => { rows.forEach((r) => { if (!r.saved && !r.dirty) setNormal(r); }); render(); });
        byId('gDate').addEventListener('change', () => { if (rows.length) render(); });
        const wrap = byId('gWrap');
        wrap.addEventListener('input', onInput);
        wrap.addEventListener('change', (e) => { if (e.target.tagName === 'SELECT') onInput(e); });
        wrap.addEventListener('click', (e) => {
            const b = e.target.closest('button'); if (!b) return;
            const i = +b.closest('tr').dataset.i, r = rows[i];
            if (b.dataset.t) { const k = r.t.indexOf(b.dataset.t); k > -1 ? r.t.splice(k, 1) : r.t.push(b.dataset.t); r.dirty = true; render(); }
            if (b.dataset.normal) { setNormal(r); render(); }
        });
        wrap.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' || e.target.tagName !== 'INPUT') return;
            e.preventDefault();
            const td = e.target.closest('td'), tr = td.parentElement, idx = [...tr.children].indexOf(td);
            const next = tr.nextElementSibling && tr.nextElementSibling.children[idx]?.querySelector('input:not([disabled])');
            if (next) { next.focus(); next.select(); }
        });
        window.addEventListener('beforeunload', (e) => { if (window.__dirty) { e.preventDefault(); e.returnValue = ''; } });
    }

    function loadRooms() {
        const group = byId('gGroup').value, sel = byId('gRoom');
        sel.innerHTML = '<option value="">ทุกห้อง</option>';
        if (!group) return;
        fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(group)}`)
            .then((r) => r.json())
            .then((d) => { [...new Set((Array.isArray(d) ? d : []).map((c) => c.classroom_name))].forEach((n) => { const o = document.createElement('option'); o.value = o.textContent = n; sel.appendChild(o); }); })
            .catch(console.error);
    }

    document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
