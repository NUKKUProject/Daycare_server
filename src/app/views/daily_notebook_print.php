<?php
// หน้าพิมพ์สมุดสื่อสารประจำวัน
//   ?student_id=XXX&date=YYYY-MM-DD            พิมพ์ของเด็ก 1 คน (ผู้ปกครองเปิดได้เฉพาะลูกตัวเอง)
//   ?classroom=ห้อง&date=YYYY-MM-DD            พิมพ์ทั้งห้อง 1 คน 1 หน้า (admin/ครูเท่านั้น)
include __DIR__ . '/../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'student']);
include __DIR__ . '/partials/Header.php';
$role = getUserRole();
$isStaff = in_array($role, ['admin', 'teacher'], true);
$studentId = $isStaff ? ($_GET['student_id'] ?? '') : ($_SESSION['username'] ?? '');
$classroom = $isStaff ? ($_GET['classroom'] ?? '') : '';
?>
<body class="print-body">
<style>
    body.print-body { background: #e5e9f0; font-family: 'Kanit', sans-serif; }

    .toolbar {
        position: sticky; top: 0; z-index: 10; background: #0f2460; color: #fff; padding: .6rem 1rem;
        display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; justify-content: space-between;
    }
    .toolbar .btn { border-radius: 10px; font-weight: 700; }
    .toolbar .info { font-size: .9rem; opacity: .85; }

    #sheets { padding: 1rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; }

    .sheet {
        width: 210mm; min-height: 297mm; background: #fff; color: #1a1a1a; padding: 10mm 11mm;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .15); font-size: 10.5pt; line-height: 1.4; position: relative;
    }

    .sh-top { display: flex; justify-content: space-between; align-items: flex-end; gap: 10px; flex-wrap: wrap; margin-bottom: 6px; }
    .sh-top .date b, .slot { font-weight: 600; }
    .sh-title { font-size: 15pt; font-weight: 700; color: #0f2460; }
    .sh-child { font-size: 10.5pt; color: #444; }

    .slot { display: inline-block; min-width: 70px; border-bottom: 1px dotted #333; text-align: center; padding: 0 6px; }
    .slot.w { min-width: 110px; }

    .legend { display: flex; flex-wrap: wrap; gap: 4px 14px; font-size: 9.5pt; margin: 6px 0 10px; }
    .legend span { display: inline-flex; align-items: center; gap: 4px; }
    .face { font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; }

    .top-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px; }
    .pill {
        display: inline-block; background: #fdd88b; border-radius: 20px; padding: 3px 18px; font-weight: 700; color: #4a3200;
    }
    .mood-row { display: flex; align-items: center; gap: 8px; margin: 6px 0; }
    .mood-row .lbl { font-size: 9.5pt; color: #555; }
    .mood-face { font-size: 22pt; line-height: 1; opacity: .25; }
    .mood-face.sel { opacity: 1; border: 2px solid #0f2460; border-radius: 50%; padding: 2px; }
    .msg-lines {
        min-height: 4.4em; white-space: pre-wrap; word-break: break-word;
        background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 1.45em, #888 1.45em, #888 calc(1.45em + 1px));
        background-size: 100% 1.5em; line-height: 1.5em;
    }

    table.form { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.form th {
        background: #fdd88b; text-align: center; font-size: 14pt; padding: 6px; border: 1.5px solid #7a5a00; color: #4a3200;
    }
    table.form td { vertical-align: top; border: 1.5px solid #7a5a00; padding: 0; }
    .blk { padding: 5px 10px; border-bottom: 1.5px solid #7a5a00; }
    .blk:last-child { border-bottom: none; }
    .blk .h { font-weight: 700; }
    .blk ul { list-style: none; padding-left: 14px; margin: 2px 0 0; }
    .blk li::before { content: "•"; margin-right: 6px; }
    .menu-note { display: block; font-size: 9.5pt; color: #8a5a00; }
    .box { display: inline-block; width: 13px; height: 13px; border: 1.2px solid #333; margin-right: 5px; vertical-align: -2px; text-align: center; line-height: 11px; font-size: 11px; font-weight: 700; }
    .full-text { min-height: 2.6em; white-space: pre-wrap; word-break: break-word; border-bottom: 1px dotted #333; }

    .state { text-align: center; padding: 3rem; color: #555; }

    @page { size: A4 portrait; margin: 0; }
    @media print {
        body.print-body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .no-print { display: none !important; }
        #sheets { padding: 0; gap: 0; display: block; }
        .sheet { box-shadow: none; page-break-after: always; break-after: page; margin: 0; width: 210mm; min-height: 297mm; }
        .sheet:last-child { page-break-after: auto; break-after: auto; }
    }
    @media (max-width: 900px) { .sheet { width: 100%; min-height: auto; } }
</style>

<div class="toolbar no-print">
    <div class="info" id="toolInfo"><i class="bi bi-printer me-1"></i> กำลังเตรียมข้อมูล...</div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm" onclick="history.length > 1 ? history.back() : window.close()"><i class="bi bi-arrow-left me-1"></i>กลับ</button>
        <button type="button" class="btn btn-warning btn-sm" id="btnPrint"><i class="bi bi-printer-fill me-1"></i>พิมพ์</button>
    </div>
</div>

<div id="sheets"><div class="state">กำลังโหลด...</div></div>

<script>
    const API = '../include/function/daily_notebook_api.php';
    const STUDENT_ID = <?php echo json_encode($studentId); ?>;
    const CLASSROOM = <?php echo json_encode($classroom); ?>;
    const params = new URLSearchParams(location.search);
    const DATE = /^\d{4}-\d{2}-\d{2}$/.test(params.get('date') || '') ? params.get('date') : null;

    const MOODS = [
        { key: 'happy', face: '😊', text: 'มีความสุข' },
        { key: 'scared', face: '😨', text: 'อารมณ์กลัว' },
        { key: 'cry', face: '😢', text: 'ร้องไห้' },
        { key: 'angry', face: '😠', text: 'โกรธ หงุดหงิด ไม่พอใจ' },
        { key: 'normal', face: '😐', text: 'อารมณ์ปกติ เฉยๆ' }
    ];

    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
    const hhmm = (v) => (v ? String(v).slice(0, 5) : '');
    const isTrue = (v) => v === true || v === 't';

    function todayStr() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    async function api(action, p = {}) {
        const res = await fetch(`${API}?action=${action}&` + new URLSearchParams(p).toString());
        let data;
        try { data = await res.json(); } catch (e) { throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง'); }
        if (data.status !== 'success') throw new Error(data.message || 'เกิดข้อผิดพลาด');
        return data.data;
    }

    // ช่องเติมข้อความแบบเส้นจุด (ว่างไว้ให้เขียนมือได้)
    const slot = (v, unit = '', wide = false) => `<span class="slot${wide ? ' w' : ''}">${v === null || v === undefined || v === '' ? '&nbsp;' : esc(v)}</span>${unit ? ' ' + unit : ''}`;
    const box = (on) => `<span class="box">${on ? '✓' : ''}</span>`;

    function moodRow(sel) {
        return `<div class="mood-row"><span class="lbl">อารมณ์</span>${MOODS.map((m) =>
            `<span class="mood-face face ${m.key === sel ? 'sel' : ''}">${m.face}</span>`).join('')}</div>`;
    }

    function dateParts(d) {
        const m = String(d).match(/^(\d{4})-(\d{2})-(\d{2})/);
        const dt = new Date(+m[1], +m[2] - 1, +m[3]);
        return {
            day: dt.getDate(),
            month: dt.toLocaleDateString('th-TH', { month: 'long' }),
            year: dt.toLocaleDateString('th-TH', { year: 'numeric' })
        };
    }

    function sheetHtml(data, date) {
        const c = data.child, r = data.report || {}, menu = data.menu || {}, att = data.attendance;
        const dp = dateParts(date);
        const nick = (c.nickname || '').trim();
        const name = [c.prefix_th, c.firstname_th, c.lastname_th].filter(Boolean).join(' ');
        const dropOff = hhmm(r.drop_off_time) || (att && att.checkin_time) || '';
        const menuLine = (t) => (t ? `<span class="menu-note">เมนู: ${esc(t)}</span>` : '');

        return `
        <div class="sheet">
            <div class="sh-top">
                <div>
                    <div class="sh-title">สมุดสื่อสารประจำวัน</div>
                    <div class="sh-child">${nick ? 'น้อง' + esc(nick) + ' · ' : ''}${esc(name)}${c.classroom ? ' · ห้อง ' + esc(c.classroom) : ''}</div>
                </div>
                <div class="date">วันที่ ${slot(dp.day)} เดือน ${slot(dp.month, '', true)} พ.ศ. ${slot(dp.year)}</div>
            </div>

            <div class="legend">
                ${MOODS.map((m) => `<span><span class="face" style="font-size:13pt;">${m.face}</span> หมายถึง ${m.text}</span>`).join('')}
            </div>

            <div class="top-cols">
                <div>
                    <span class="pill">สื่อสารจากผู้ปกครอง</span>
                    ${moodRow(r.parent_mood)}
                    <div>ส่งเด็กเวลา ${slot(dropOff, 'น.')}</div>
                    <div class="msg-lines mt-1">${esc(r.parent_message || '')}</div>
                </div>
                <div>
                    <span class="pill">ข้อมูลจากคุณครู</span>
                    ${moodRow(r.teacher_mood)}
                    <div class="msg-lines mt-1" style="margin-top:30px;">${esc(r.teacher_message || '')}</div>
                </div>
            </div>

            <table class="form">
                <thead><tr><th>ที่บ้าน</th><th>ที่ศูนย์</th></tr></thead>
                <tbody><tr>
                    <td>
                        <div class="blk">
                            <div class="h">ช่วงเช้าก่อนมาศูนย์</div>
                            <ul>
                                <li>ดื่มนม ${slot(r.home_morning_milk_ml, 'มล.')}</li>
                                <li>อาหารเช้า (ปริมาณ/คุณภาพ)<div class="full-text">${esc(r.home_morning_food || '')}</div></li>
                            </ul>
                        </div>
                        <div class="blk">
                            <div class="h">ช่วงเย็นและกลางคืน</div>
                            <ul>
                                <li>ดื่มนม ${slot(r.home_evening_milk_ml, 'มล.')}</li>
                                <li>อาหารเย็น (ปริมาณ/คุณภาพ)<div class="full-text">${esc(r.home_evening_food || '')}</div></li>
                            </ul>
                        </div>
                        <div class="blk">
                            <ul>
                                <li>กลางคืนนอนหลับ ${slot(r.home_sleep_hours, 'ชม.')}</li>
                                <li>เข้านอนเวลา ${slot(hhmm(r.home_bedtime), 'น.')}</li>
                                <li>ตื่นนอนเวลา ${slot(hhmm(r.home_wake_time), 'น.')}</li>
                            </ul>
                            <div style="margin:4px 0 0 14px;">${box(isTrue(r.home_stopped_diaper))}เลิกใส่แพมเพิร์ส</div>
                            <div style="margin:2px 0 0 14px;">${box(isTrue(r.home_stopped_bottle))}เลิกดื่มนมขวด</div>
                        </div>
                    </td>
                    <td>
                        <div class="blk">
                            <div class="h">ช่วงเช้า</div>
                            <ul>
                                <li>ดื่มนม ${slot(r.center_morning_milk_ml, 'มล.')}</li>
                                <li>อาหารว่างเช้า${menuLine(menu.morning_snack)}<div class="full-text">${esc(r.center_morning_snack_amount || '')}</div></li>
                            </ul>
                        </div>
                        <div class="blk">
                            <ul>
                                <li>อาหารกลางวัน (ปริมาณ/คุณภาพ)${menuLine(menu.lunch)}<div class="full-text">${esc(r.center_lunch_amount || '')}</div></li>
                            </ul>
                        </div>
                        <div class="blk">
                            <div class="h">ช่วงบ่าย</div>
                            <ul>
                                <li>ดื่มนม ${slot(r.center_afternoon_milk_ml, 'มล.')}</li>
                                <li>อาหารว่างบ่าย${menuLine(menu.afternoon_snack)}<div class="full-text">${esc(r.center_afternoon_snack_amount || '')}</div></li>
                            </ul>
                        </div>
                        <div class="blk">
                            <ul>
                                <li>นอนกลางวัน ${slot(r.center_nap_hours, 'ชม.')}</li>
                                <li>ปัสสาวะ ${slot(r.center_urine_count, 'ครั้ง')}</li>
                                <li>อุจจาระ ${slot(r.center_stool_count, 'ครั้ง')}</li>
                            </ul>
                            <div style="margin:4px 0 0 14px;">${box(isTrue(r.center_stopped_diaper))}เลิกใส่แพมเพิร์ส</div>
                            <div style="margin:2px 0 0 14px;">${box(isTrue(r.center_stopped_bottle))}เลิกดื่มนมขวด</div>
                        </div>
                        <div class="blk">
                            <div class="h">กิจกรรมและการมีส่วนร่วม :</div>
                            <div class="full-text" style="min-height:4em;">${esc(r.activities || '')}</div>
                        </div>
                    </td>
                </tr></tbody>
            </table>
        </div>`;
    }

    async function loadOne(sid, date) {
        return api('report', { student_id: sid, date });
    }

    async function init() {
        const date = DATE || todayStr();
        const holder = byId('sheets');
        try {
            let list;
            if (CLASSROOM) {
                // พิมพ์ทั้งห้อง: ดึงรายชื่อแล้วโหลดสมุดของเด็กทีละกลุ่ม
                const roster = (await api('roster', { date, classroom: CLASSROOM })).children;
                if (!roster.length) throw new Error('ไม่พบเด็กในห้องนี้');
                list = [];
                for (let i = 0; i < roster.length; i += 5) {
                    const chunk = await Promise.all(roster.slice(i, i + 5).map((c) => loadOne(c.studentid, date)));
                    list.push(...chunk);
                    byId('toolInfo').innerHTML = `<i class="bi bi-hourglass-split me-1"></i> กำลังโหลด ${list.length}/${roster.length}`;
                }
            } else {
                if (!STUDENT_ID) throw new Error('ไม่ได้ระบุนักเรียน');
                list = [await loadOne(STUDENT_ID, date)];
            }
            holder.innerHTML = list.map((d) => sheetHtml(d, date)).join('');
            byId('toolInfo').innerHTML = `<i class="bi bi-printer me-1"></i> พร้อมพิมพ์ ${list.length} หน้า (A4 แนวตั้ง) · ตั้งค่าการพิมพ์: ขนาดกระดาษ A4, ขอบ "ไม่มี", เปิด "พิมพ์พื้นหลัง"`;
        } catch (e) {
            holder.innerHTML = `<div class="state">⚠️ ${esc(e.message)}</div>`;
            byId('toolInfo').textContent = 'ไม่สามารถเตรียมหน้าพิมพ์ได้';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        byId('btnPrint').addEventListener('click', () => window.print());
        init();
    });
</script>
</body>
</html>
