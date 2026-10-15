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
    .sec-head { display: flex; gap: .65rem; align-items: flex-start; }
    .sec-head .gs { margin-top: 2px; }
    .sec-title { font-weight: 700; font-size: 1.02rem; color: #0f2460; line-height: 1.25; }
    .sec-sub { font-size: .8rem; color: #64748b; line-height: 1.3; }
    .fgrid { display: grid; gap: .75rem 1rem; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr) auto; }
    @media (max-width: 992px) { .fgrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .fgrid { grid-template-columns: 1fr; } }
    .fld > label { display: block; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .2rem; }

    .tg-pills { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; font-size: .85rem; margin-bottom: .6rem; }
    .tg-pill { border-radius: 999px; padding: 2px 12px; font-weight: 700; font-size: .78rem; background: #f1f5f9; color: #64748b; }
    .tg-pill.ok { background: #dcfce7; color: #15803d; } .tg-pill.bad { background: #fee2e2; color: #b91c1c; }
    .tg-pill.rec { background: #dbeafe; color: #1d4ed8; }

    .tg-wrap { overflow: auto; max-height: 72vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    table.tg { border-collapse: separate; border-spacing: 0; width: 100%; font-size: .85rem; --h1: 28px; }
    .tg th { position: sticky; background: #f1f5f9; color: #475569; font-weight: 700; font-size: .75rem; padding: 5px 6px; border-bottom: 1px solid #cbd5e1; text-align: center; white-space: nowrap; z-index: 3; }
    .tg tr.h1 th { top: 0; height: var(--h1); font-size: .74rem; border-bottom: 1px solid #fff; }
    .tg tr.h2 th { top: var(--h1); }
    .tg th.g-child { background: #e2e8f0; } .tg th.g-age { background: #dbeafe; } .tg th.g-meas { background: #fef08a; }
    .tg th.g-d0 { background: #fde68a; } .tg th.g-d1 { background: #bbf7d0; } .tg th.g-d2 { background: #e9d5ff; }
    .tg th.g-d3 { background: #fecaca; } .tg th.g-d4 { background: #bae6fd; }
    .tg th.g-sum { background: #fed7aa; } .tg th.g-state { background: #e2e8f0; }
    .tg td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; text-align: center; background: #fff; vertical-align: middle; }
    .tg .c-no { position: sticky; left: 0; z-index: 2; min-width: 36px; background: #fff; }
    .tg .c-nm { position: sticky; left: 36px; z-index: 2; min-width: 160px; text-align: left; padding: 5px 8px; background: #fff; border-right: 1px solid #cbd5e1; }
    .tg th.c-no, .tg th.c-nm { z-index: 5; }
    .tg .nick { font-weight: 700; color: #0f2460; line-height: 1.15; }
    .tg .full { font-size: .72rem; color: #64748b; line-height: 1.15; }
    .tg td.nw { white-space: nowrap; font-size: .8rem; }
    .tg td.note { text-align: left; min-width: 140px; max-width: 220px; font-size: .78rem; color: #475569; }
    .tg span.pass { color: #15803d; font-weight: 700; } .tg span.delay { color: #b91c1c; font-weight: 700; }
    .tg .btn-chart { display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap; padding: 4px 12px; border-radius: 999px; border: 1.5px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: .78rem; font-weight: 700; cursor: pointer; transition: background-color .15s, color .15s, border-color .15s, box-shadow .15s; }
    .tg .btn-chart:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; box-shadow: 0 2px 8px rgba(29, 78, 216, .3); }
    .tg .btn-chart:active { transform: scale(.96); }
    .tg td.c-chart { min-width: 110px; }
    .tg .cnt { font-weight: 700; font-size: .95rem; }
    .tg .res { font-size: .72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; white-space: normal; display: inline-block; min-width: 150px; }
    .tg .res.normal { background: #dcfce7; color: #15803d; } .tg .res.delay { background: #fee2e2; color: #b91c1c; }
    .tg .st { font-size: .72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; white-space: nowrap; }
    .tg .st.saved { background: #dcfce7; color: #15803d; } .tg .st.todo { background: #e5e7eb; color: #64748b; }
    /* สีแถว: ยังไม่ได้บันทึก = เทาอ่อน  ผลสมวัย = เขียวอ่อน  สงสัยล่าช้า = แดงอ่อน  มีแต่ข้อมูลการเจริญเติบโต = ฟ้าอ่อน */
    .tg tr.todo td { background: #fafafb; color: #94a3b8; }
    .tg tr.normal td { background: #f0fdf4; }
    .tg tr.delay td { background: #fef2f2; }
    .tg tr.growth td { background: #eff6ff; }
    .tg tr.todo .c-nm { box-shadow: inset 5px 0 0 #cbd5e1; }
    .tg tr.normal .c-nm { box-shadow: inset 5px 0 0 #22c55e; }
    .tg tr.delay .c-nm { box-shadow: inset 5px 0 0 #ef4444; }
    .tg tr.growth .c-nm { box-shadow: inset 5px 0 0 #3b82f6; }
    .tg tbody tr:hover td { filter: brightness(.97); }
    .tg-legend { display: flex; flex-wrap: wrap; gap: .75rem; font-size: .75rem; color: #64748b; margin-bottom: .5rem; align-items: center; }
    .tg-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; }
    .tg-wrap.draggable { cursor: grab; } .tg-wrap.dragging { cursor: grabbing; user-select: none; }
    .tg-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .tg-empty .big { font-size: 1.05rem; font-weight: 700; color: #334155; margin-bottom: .35rem; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h2 class="page-title">ผลคัดกรองพัฒนาการและการเจริญเติบโตของเด็กทั้งห้อง</h2>
            <div class="d-flex gap-2">
                <a href="growth_dev_grid.php" id="gGoEntry" class="btn btn-success btn-sm"><i class="bi bi-pencil-square me-1"></i>ไปหน้ากรอกผล</a>
                <a href="growth_history.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock-history me-1"></i>ประวัติการเจริญเติบโต</a>
            </div>
        </div>

        <div class="tg-card">
            <div class="sec-head mb-2">
                <span class="gs">1</span>
                <div>
                    <div class="sec-title">เลือกห้องและวันที่ประเมิน</div>
                    <div class="sec-sub">แสดงผลที่บันทึกไว้ของวันที่เลือก (ดูอย่างเดียว ไปแก้ที่หน้ากรอกผล)</div>
                </div>
            </div>
            <div class="fgrid">
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
                    <label for="gDate">วันที่ประเมิน</label>
                    <input type="date" id="gDate" class="form-control">
                </div>
                <div class="fld">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-outline-primary w-100" id="gLoad"><i class="bi bi-arrow-clockwise"></i> โหลดใหม่</button>
                </div>
            </div>
        </div>

        <div class="tg-card">
            <div class="sec-head mb-2">
                <span class="gs">2</span>
                <div>
                    <div class="sec-title">ผลที่บันทึก</div>
                    <div class="sec-sub" id="gSub">เลือกกลุ่มเรียน ห้องเรียน และวันที่ประเมิน</div>
                </div>
            </div>
            <div class="tg-pills" id="gPills" style="display:none"></div>
            <div class="tg-legend" id="gLegend" style="display:none">
                <span><i style="background:#22c55e"></i>สมวัย</span><span><i style="background:#ef4444"></i>สงสัยล่าช้า</span>
                <span><i style="background:#3b82f6"></i>มีแต่ข้อมูลการเจริญเติบโต (ยังไม่ได้ประเมินพัฒนาการ)</span><span><i style="background:#cbd5e1"></i>ยังไม่ได้บันทึก</span>
            </div>
            <div class="tg-wrap" id="gWrap">
                <div class="tg-empty">
                    <div class="big">เลือกกลุ่มเรียนและห้องเรียนด้านบน</div>
                    ผลที่ครูบันทึกของทั้งห้องจะขึ้นให้อัตโนมัติ
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const API_LIST = '../include/function/get_dev_screening_class.php';
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
    const PRE = Object.fromEntries(new URLSearchParams(location.search));
    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const thaiShort = (d) => (d ? new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) : '-');
    const num = (v) => (v === null || v === undefined || v === '' ? '-' : esc(parseFloat(v)));

    function todayStr() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    // สรุปของแต่ละคนจากข้อมูลที่บันทึกไว้ (ค่าที่ระบบเก็บ ถ้าแถวเก่าไม่มีให้คำนวณจากผลรายด้าน)
    function info(s) {
        const statuses = DOMAINS.map(([k]) => s[k + '_status']).filter(Boolean);
        const pass = statuses.filter((v) => v === 'pass').length;
        const fail = statuses.length - pass;
        const hasDev = statuses.length > 0;
        const complete = statuses.length === DOMAINS.length;
        const result = s.dev_result || (complete ? (fail ? 'delay' : 'normal') : null);
        const hasMeasure = [s.weight, s.height, s.head_circumference].some((v) => v !== null && v !== undefined && v !== '');
        let recheck = s.recheck_date;
        if (!recheck && result === 'delay' && s.assessDate) {
            const d = new Date(s.assessDate + 'T00:00:00'); d.setDate(d.getDate() + 30);
            recheck = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        }
        const saved = s.growth_id !== null && s.growth_id !== undefined;
        const cls = !saved ? 'todo' : (result === 'delay' ? 'delay' : (result === 'normal' ? 'normal' : 'growth'));
        return { pass, fail, hasDev, result, recheck, saved, cls, hasMeasure };
    }

    function header() {
        return '<thead><tr class="h1">' +
            '<th class="g-child" colspan="3">เด็ก</th><th class="g-age" colspan="3">อายุ</th><th class="g-meas" colspan="3">การเจริญเติบโต</th>' +
            DOMAINS.map((d, i) => `<th class="g-d${i}" colspan="3" title="${esc(d[2])}">${esc(d[1])} (${d[0].toUpperCase()})</th>`).join('') +
            '<th class="g-sum" colspan="4">สรุป</th><th class="g-state" colspan="3">การบันทึก</th></tr>' +
            '<tr class="h2"><th class="c-no">#</th><th class="c-nm">ชื่อ</th><th>วันเดือนปีเกิด</th>' +
            '<th>อายุ ณ วันที่ตรวจ</th><th>อายุ (เดือน)</th><th>ช่วงอายุเกณฑ์ที่ใช้</th>' +
            '<th>น้ำหนัก (กก.)</th><th>ส่วนสูง (ซม.)</th><th>เส้นรอบศีรษะ (ซม.)</th>' +
            DOMAINS.map(() => '<th>ผล</th><th>ข้อที่</th><th>รายละเอียดเพิ่มเติม</th>').join('') +
            '<th>ผ่าน (ด้าน)</th><th>ไม่ผ่าน (ด้าน)</th><th>ผลการคัดกรอง</th><th>ประเมินซ้ำภายใน 1 เดือน</th>' +
            '<th>สถานะ</th><th>ผู้บันทึก</th><th>กราฟ</th></tr></thead>';
    }

    function rowHtml(s, i) {
        const f = info(s);
        const td = (cls, inner) => `<td class="${cls}">${inner}</td>`;
        const age = f.saved && s.age_year !== null ? `${s.age_year} ปี ${s.age_month} ด. ${s.age_day} ว.` : '-';
        const range = s.age_range ? esc(s.age_range) + ' ปี' : '-';
        let h = `<tr class="${f.cls}">` +
            `<td class="c-no">${i + 1}</td>` +
            `<td class="c-nm"><div class="nick">${s.nickname ? esc(s.nickname) : esc([s.prefix_th, s.firstname_th, s.lastname_th].filter(Boolean).join(' '))}</div>` +
            (s.nickname ? `<div class="full">${esc([s.prefix_th, s.firstname_th, s.lastname_th].filter(Boolean).join(' '))}</div>` : '') + '</td>' +
            td('nw', s.birthday ? esc(thaiShort(s.birthday)) : '<span class="text-danger">ไม่ระบุ</span>') +
            td('nw', age) + td('', f.saved && s.age_months !== null ? esc(s.age_months) : '-') + td('nw', f.saved ? range : '-') +
            td('', f.saved ? num(s.weight) : '-') + td('', f.saved ? num(s.height) : '-') + td('', f.saved ? num(s.head_circumference) : '-');
        DOMAINS.forEach(([k]) => {
            const st = s[k + '_status'];
            h += td('', st === 'pass' ? '<span class="pass">ผ่าน</span>' : st === 'delay' ? '<span class="delay">ไม่ผ่าน</span>' : '-') +
                td('', s[k + '_issue'] ? esc(s[k + '_issue']) : '-') +
                td('note', s[k + '_note'] ? esc(s[k + '_note']) : '-');
        });
        h += td('cnt', f.hasDev ? f.pass : '-') + td('cnt', f.hasDev ? f.fail : '-') +
            td('', f.result === 'delay' ? `<span class="res delay">${RESULT_DELAY}</span>` : f.result === 'normal' ? `<span class="res normal">${RESULT_NORMAL}</span>` : '-') +
            td('nw', f.result === 'delay' && f.recheck ? esc(thaiShort(f.recheck)) : '-') +
            td('', `<span class="st ${f.saved ? 'saved' : 'todo'}">${f.saved ? 'บันทึกแล้ว' : 'ยังไม่ได้บันทึก'}</span>`) +
            td('nw', f.saved && s.dev_recorded_by ? esc(s.dev_recorded_by) : '-') +
            td('c-chart', `<button type="button" class="btn-chart" data-chart="${esc(s.studentid)}" data-name="${esc(s.nickname || s.firstname_th)}"><i class="bi bi-graph-up-arrow"></i>ดูกราฟ</button>`) + '</tr>';
        return h;
    }

    function render(students, date) {
        const wrap = byId('gWrap');
        if (!students.length) {
            wrap.innerHTML = '<div class="tg-empty"><div class="big">ไม่พบรายชื่อเด็กในห้องนี้</div></div>';
            byId('gPills').style.display = byId('gLegend').style.display = 'none';
            return;
        }
        students.forEach((s) => { s.assessDate = date; });
        const infos = students.map(info);
        const count = (fn) => infos.filter(fn).length;
        byId('gPills').innerHTML =
            `<span>${students.length} คน</span>` +
            `<span class="tg-pill rec">บันทึกแล้ว ${count((x) => x.saved)}</span>` +
            `<span class="tg-pill">ยังไม่ได้บันทึก ${count((x) => !x.saved)}</span>` +
            `<span class="tg-pill ok">สมวัย ${count((x) => x.result === 'normal')}</span>` +
            `<span class="tg-pill bad">สงสัยล่าช้า ${count((x) => x.result === 'delay')}</span>`;
        byId('gPills').style.display = byId('gLegend').style.display = '';
        wrap.innerHTML = '<table class="tg">' + header() + '<tbody>' + students.map(rowHtml).join('') + '</tbody></table>';
    }

    async function load() {
        const group = byId('gGroup').value, room = byId('gRoom').value, date = byId('gDate').value;
        // ลิงก์ไปหน้ากรอกผล พร้อมตัวเลือกเดิม
        byId('gGoEntry').href = 'growth_dev_grid.php?' + new URLSearchParams({ child_group: group, classroom: room, date: date });
        if (!group || !room || !date) return;
        byId('gSub').textContent = `${group} · ${room} · วันที่ประเมิน ${thaiShort(date)}`;
        byId('gWrap').innerHTML = '<div class="tg-empty"><div class="spinner-border text-primary"></div></div>';
        try {
            const res = await fetch(`${API_LIST}?${new URLSearchParams({ child_group: group, classroom: room, date: date })}`, { headers: XHR });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'โหลดไม่สำเร็จ');
            render(data.students, date);
        } catch (e) {
            byId('gWrap').innerHTML = `<div class="tg-empty"><div class="big">โหลดไม่สำเร็จ</div>${esc(e.message)}</div>`;
        }
    }

    // ===== กราฟการเจริญเติบโตรายคน (popup): น้ำหนัก/ส่วนสูง/BMI ตามเกณฑ์อายุ =====
    const CHART_DEFS = [
        { key: 'weight', id: 'vcWeight', title: 'น้ำหนักตามเกณฑ์อายุ', y: 'น้ำหนัก (กก.)', color: '#3b82f6', min: 2.5, max: 35, get: (r) => r.physical_measures.weight },
        { key: 'height', id: 'vcHeight', title: 'ส่วนสูงตามเกณฑ์อายุ', y: 'ส่วนสูง (ซม.)', color: '#22c55e', min: 40, max: 130, get: (r) => r.physical_measures.height },
        { key: 'bmi', id: 'vcBmi', title: 'BMI ตามเกณฑ์อายุ', y: 'BMI (กก./ม.²)', color: '#f59e0b', min: 10, max: 25, get: (r) => r.bmi }
    ];
    let popupCharts = [];

    function drawChart(def, ref, points) {
        const band = (label, arr, color, fill) => ({ label, data: ref.ages.map((a, i) => ({ x: a, y: arr[i] })), borderColor: color, borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, fill, backgroundColor: color.replace(/[\d.]+\)$/, '0.06)') });
        const chart = new Chart(byId(def.id), {
            type: 'line',
            data: { datasets: [
                band('+2 SD', ref.plus2sd, 'rgba(239,68,68,0.6)', '+1'), band('+1 SD', ref.plus1sd, 'rgba(249,115,22,0.6)', '+1'),
                band('Median', ref.median, 'rgba(34,197,94,0.7)', '+1'), band('-1 SD', ref.minus1sd, 'rgba(249,115,22,0.6)', '+1'),
                band('-2 SD', ref.minus2sd, 'rgba(239,68,68,0.6)', false),
                { label: 'ข้อมูลของเด็ก', data: points, borderColor: def.color, backgroundColor: def.color, borderWidth: 2.5, pointRadius: 5, pointHoverRadius: 8, pointBorderColor: '#fff', pointBorderWidth: 2, fill: false, tension: 0.3, order: 1 }
            ] },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'nearest' },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 14, font: { size: 11 } } }, title: { display: true, text: def.title } },
                scales: { x: { type: 'linear', title: { display: true, text: 'อายุ (เดือน)' }, min: 0, max: 60, ticks: { stepSize: 6 } },
                          y: { title: { display: true, text: def.y }, min: def.min, max: def.max } }
            }
        });
        popupCharts.push(chart);
    }

    async function showChartsFor(studentId, name) {
        try {
            const res = await fetch(`../include/function/get_student_growth_grid.php?student_id=${encodeURIComponent(studentId)}`, { headers: XHR });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'โหลดข้อมูลไม่สำเร็จ');
            const records = (data.all_records || []).filter((r) => r.physical_measures && (r.physical_measures.weight || r.physical_measures.height));
            if (!records.length) { Swal.fire({ icon: 'info', title: 'ยังไม่มีข้อมูลน้ำหนัก/ส่วนสูงของเด็กคนนี้', confirmButtonText: 'ตกลง' }); return; }
            const sex = (data.current_record || records[0]).sex === 'ชาย' ? 'M' : 'F';
            const monthsOf = (r) => (r.age_year || 0) * 12 + (r.age_month || 0);
            const over = records.some((r) => monthsOf(r) > 60);

            popupCharts.forEach((c) => c.destroy()); popupCharts = [];
            Swal.fire({
                title: `กราฟการเจริญเติบโต - ${esc(name)}`,
                width: '95%', showCloseButton: true, showConfirmButton: false,
                html: (over ? '<div class="alert alert-warning py-1 small">มีครั้งที่อายุเกิน 60 เดือน จุดนั้นอยู่นอกช่วงของกราฟ (0-60 เดือน)</div>' : '') +
                    '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1rem;">' +
                    CHART_DEFS.map((d) => `<div style="position:relative;height:340px;"><canvas id="${d.id}"></canvas></div>`).join('') + '</div>',
                didOpen: () => {
                    CHART_DEFS.forEach(async (def) => {
                        const points = records.filter((r) => def.get(r)).map((r) => ({ x: monthsOf(r), y: parseFloat(def.get(r)) }));
                        try {
                            const rr = await fetch(`../include/function/get_growth_reference.php?indicator=${def.key}&sex=${sex}`);
                            const ref = await rr.json();
                            if (ref.status === 'success') drawChart(def, ref, points);
                        } catch (e) { console.error(e); }
                    });
                },
                willClose: () => { popupCharts.forEach((c) => c.destroy()); popupCharts = []; }
            });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message, confirmButtonText: 'ตกลง' });
        }
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

    function enableDragScroll(el) {
        el.classList.add('draggable');
        let down = false, sx = 0, sy = 0, sl = 0, st = 0;
        el.addEventListener('mousedown', (e) => {
            if (e.button !== 0 || e.target.closest('a, button')) return;
            down = true; sx = e.clientX; sy = e.clientY; sl = el.scrollLeft; st = el.scrollTop;
            el.classList.add('dragging');
        });
        window.addEventListener('mousemove', (e) => { if (!down) return; el.scrollLeft = sl - (e.clientX - sx); el.scrollTop = st - (e.clientY - sy); });
        window.addEventListener('mouseup', () => { if (down) { down = false; el.classList.remove('dragging'); } });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        enableDragScroll(byId('gWrap'));
        byId('gWrap').addEventListener('click', (e) => {
            const b = e.target.closest('button[data-chart]');
            if (b) showChartsFor(b.dataset.chart, b.dataset.name);
        });
        byId('gDate').value = PRE.date || todayStr();
        byId('gGroup').addEventListener('change', async () => { await loadRooms(); load(); });
        byId('gRoom').addEventListener('change', load);
        byId('gDate').addEventListener('change', load);
        byId('gLoad').addEventListener('click', load);
        if (PRE.child_group) { byId('gGroup').value = PRE.child_group; await loadRooms(); }
        if (PRE.classroom) byId('gRoom').value = PRE.classroom;
        load();
    });
</script>
</body>
</html>
