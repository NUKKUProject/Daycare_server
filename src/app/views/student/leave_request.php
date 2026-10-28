<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['student']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>
<?php require_once __DIR__ . '/../../include/function/leave_functions.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>
<?php
// ผู้ปกครองล็อกอินด้วยรหัสนักเรียน แจ้งลาได้เฉพาะลูกตัวเอง
$studentid = $_SESSION['username'] ?? '';
$child = $studentid !== '' ? getChildById($studentid) : null;
$childName = $child ? trim(($child['prefix_th'] ?? '') . ($child['firstname_th'] ?? '') . ' ' . ($child['lastname_th'] ?? '')) : '';
$nick = $child['nickname'] ?? '';
?>

<style>
    /* โทนเดียวกับ Dashboard ผู้ปกครอง: #26648E / #1E4F6F / #ebf4fb  หน้าเดียวคอลัมน์เดียว แบ่งเป็นขั้นตอนสั้นๆ ให้อ่านง่าย */
    .lv-wrap { max-width: 720px; margin: 0 auto; padding-bottom: 2rem; }
    .lv-header { background: radial-gradient(circle at 92% 15%, rgba(255,255,255,.22) 0 8%, transparent 8.5%), linear-gradient(120deg, #1E4F6F 0%, #26648E 58%, #4f88aa 100%); border-radius: 1.5rem; color: #fff; padding: 1.1rem 1.6rem; margin-bottom: 1rem; box-shadow: 0 14px 32px rgba(38, 100, 142, .2); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .lv-header .t { font-size: .8rem; opacity: .8; }
    .lv-header .n { font-size: clamp(1.25rem, 4.5vw, 1.6rem); font-weight: 800; line-height: 1.15; }
    .lv-header .f { font-size: .82rem; opacity: .85; }
    .lv-header .ic { width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }

    .lv-card { background: #fff; border: 1px solid #d9e6ee; border-radius: 1.25rem; box-shadow: 0 12px 30px rgba(15, 23, 42, .05); padding: 1.4rem 1.5rem; margin-bottom: 1rem; }
    .lv-card-title { font-size: 1.05rem; font-weight: 800; color: #1E4F6F; margin: 0 0 1rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .lv-card-title .count { font-size: .78rem; font-weight: 700; color: #64748b; background: #ebf4fb; border-radius: 999px; padding: 2px 12px; }

    /* ขั้นตอนของฟอร์ม */
    .lv-step + .lv-step { margin-top: 1.4rem; }
    .lv-step-title { display: flex; align-items: center; gap: .55rem; font-size: .9rem; font-weight: 800; color: #1E4F6F; margin-bottom: .6rem; }
    .lv-step-title .no { width: 24px; height: 24px; border-radius: 50%; background: #26648E; color: #fff; font-size: .75rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lv-step-title .opt { font-size: .75rem; font-weight: 600; color: #94a3b8; }

    /* ประเภทการลา: การ์ดเตี้ย ไอคอนซ้ายข้อความขวา */
    .lv-types { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
    .lv-type { position: relative; }
    .lv-type input, .lv-chipopt input { position: absolute; opacity: 0; pointer-events: none; }
    .lv-type label { display: flex; align-items: center; gap: .7rem; padding: .7rem .9rem; border: 2px solid #d9e6ee; border-radius: 1rem; background: #fff; font-weight: 800; color: #475569; cursor: pointer; transition: border-color .15s, background-color .15s, color .15s; }
    .lv-type label i { width: 38px; height: 38px; border-radius: .8rem; background: #f1f5f9; color: #64748b; font-size: 1.15rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background-color .15s, color .15s; }
    .lv-type label small { display: block; font-weight: 500; font-size: .72rem; color: #94a3b8; }
    .lv-type input:checked + label { border-color: #26648E; background: #ebf4fb; color: #1E4F6F; }
    .lv-type input:checked + label i { background: #26648E; color: #fff; }
    .lv-type input:focus-visible + label, .lv-chipopt input:focus-visible + label { outline: 3px solid #93c5fd; outline-offset: 2px; }

    /* โรคที่ป่วย: ชิปกะทัดรัด เลือกแล้วเป็นสีธีมทึบ */
    .lv-chips { display: flex; flex-wrap: wrap; gap: .45rem; }
    .lv-chipopt { position: relative; }
    .lv-chipopt label { display: inline-block; padding: .4rem .95rem; border: 1.5px solid #d9e6ee; border-radius: 999px; font-size: .88rem; font-weight: 600; color: #475569; background: #fff; cursor: pointer; transition: border-color .15s, background-color .15s, color .15s; }
    .lv-chipopt label:hover { border-color: #26648E; }
    .lv-chipopt input:checked + label { background: #26648E; border-color: #26648E; color: #fff; }

    /* วันที่ */
    .lv-dates { display: flex; align-items: center; gap: .6rem; }
    .lv-dates .sep { color: #94a3b8; font-weight: 700; }
    .lv-dates .form-control { flex: 1 1 0; min-width: 0; }
    .lv-days { display: inline-block; margin-top: .5rem; font-size: .8rem; font-weight: 700; color: #1E4F6F; background: #ebf4fb; border-radius: 999px; padding: 2px 12px; }
    .lv-days:empty { display: none; }

    .lv-card .form-control, .lv-card .form-select { border-color: #cbd9e3; border-radius: .75rem; }
    .lv-card .form-control:focus { border-color: #26648E; box-shadow: 0 0 0 .2rem rgba(38, 100, 142, .2); }

    .lv-actions { margin-top: 1.5rem; }
    .lv-submit { width: 100%; background: #26648E; border-color: #26648E; color: #fff; font-weight: 800; padding: .75rem 1.5rem; border-radius: .9rem; box-shadow: 0 8px 18px rgba(38, 100, 142, .25); }
    .lv-submit:hover:not(:disabled) { background: #1E4F6F; border-color: #1E4F6F; color: #fff; }
    .lv-reset { display: block; margin: .6rem auto 0; border: 0; background: none; color: #94a3b8; font-size: .82rem; cursor: pointer; }
    .lv-reset:hover { color: #475569; text-decoration: underline; }

    /* ประวัติ */
    .lv-item { border: 1px solid #d9e6ee; border-left: 5px solid #26648E; border-radius: .9rem; padding: .75rem .95rem; margin-bottom: .6rem; }
    .lv-item.cancelled { opacity: .6; background: #f8fafc; border-left-color: #cbd5e1; }
    .lv-item.st-pending { border-left-color: #f59e0b; } .lv-item.st-approved { border-left-color: #22c55e; } .lv-item.st-rejected { border-left-color: #ef4444; }
    .lv-item .top { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; }
    .lv-item .when { font-weight: 800; color: #1E4F6F; }
    .lv-item .meta { font-size: .8rem; color: #64748b; margin-top: .3rem; }
    .lv-chip { font-size: .72rem; font-weight: 800; padding: 2px 10px; border-radius: 999px; white-space: nowrap; }
    .lv-chip.sick { background: #fee2e2; color: #b91c1c; } .lv-chip.personal { background: #dcebf3; color: #1E4F6F; }
    .lv-chip.pending { background: #fef3c7; color: #b45309; } .lv-chip.approved { background: #dcfce7; color: #15803d; }
    .lv-chip.rejected { background: #fee2e2; color: #b91c1c; } .lv-chip.cancelled { background: #e5e7eb; color: #64748b; }
    .lv-chip.past { background: #f1f5f9; color: #64748b; } .lv-chip.now { background: #fef3c7; color: #b45309; }
    .lv-decision { font-size: .78rem; margin-top: .35rem; padding: .35rem .6rem; border-radius: .6rem; background: #f1f5f9; color: #475569; }
    .lv-cancel { margin-left: auto; border: 1.5px solid #fecaca; background: #fff; color: #b91c1c; border-radius: 999px; font-size: .75rem; font-weight: 700; padding: 2px 12px; cursor: pointer; }
    .lv-cancel:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
    .lv-empty { text-align: center; color: #94a3b8; padding: 1rem .5rem; font-size: .9rem; }
</style>

<main class="main-content">
    <div class="container-fluid px-3 px-lg-4">
        <div class="lv-wrap">
            <div class="lv-header">
                <div>
                    <div class="t">แจ้งการลา</div>
                    <div class="n"><?= htmlspecialchars($nick !== '' ? $nick : $childName) ?></div>
                    <?php if ($nick !== ''): ?><div class="f"><?= htmlspecialchars($childName) ?></div><?php endif; ?>
                </div>
                <div class="ic"><i class="bi bi-calendar-x"></i></div>
            </div>

            <!-- ฟอร์มแจ้งลา: 4 ขั้นตอน -->
            <div class="lv-card">
                <h5 class="lv-card-title">แจ้งลาใหม่</h5>
                <form id="leaveForm" novalidate>
                    <div class="lv-step">
                        <div class="lv-step-title"><span class="no">1</span>ประเภทการลา</div>
                        <div class="lv-types">
                            <div class="lv-type">
                                <input type="radio" name="leave_type" id="typePersonal" value="personal">
                                <label for="typePersonal"><i class="bi bi-briefcase"></i><span>ลากิจ<small>ธุระส่วนตัว / ครอบครัว</small></span></label>
                            </div>
                            <div class="lv-type">
                                <input type="radio" name="leave_type" id="typeSick" value="sick">
                                <label for="typeSick"><i class="bi bi-thermometer-half"></i><span>ลาป่วย<small>เด็กไม่สบาย</small></span></label>
                            </div>
                        </div>
                    </div>

                    <!-- ลาป่วย: เลือกโรค -->
                    <div class="lv-step" id="sickBox" style="display:none">
                        <div class="lv-step-title"><span class="no">2</span>โรคที่ป่วย</div>
                        <div class="lv-chips">
                            <?php foreach (LEAVE_COMMON_DISEASES as $key => $label): ?>
                                <div class="lv-chipopt">
                                    <input type="radio" name="illness" id="ill_<?= $key ?>" value="<?= $key ?>">
                                    <label for="ill_<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                                </div>
                            <?php endforeach; ?>
                            <div class="lv-chipopt">
                                <input type="radio" name="illness" id="ill_other" value="other">
                                <label for="ill_other">อื่นๆ</label>
                            </div>
                        </div>
                        <input type="text" class="form-control mt-2" id="illnessOther" maxlength="150" placeholder="พิมพ์ชื่อโรคหรืออาการที่ป่วย" style="display:none">
                    </div>

                    <div class="lv-step">
                        <div class="lv-step-title"><span class="no" id="noDate">2</span>วันที่ลา</div>
                        <div class="lv-dates">
                            <input type="date" class="form-control" id="startDate" aria-label="ลาตั้งแต่วันที่">
                            <span class="sep">ถึง</span>
                            <input type="date" class="form-control" id="endDate" aria-label="ถึงวันที่">
                        </div>
                        <span class="lv-days" id="dayCount"></span>
                    </div>

                    <div class="lv-step">
                        <div class="lv-step-title"><span class="no" id="noReason">3</span><span id="reasonLabel">รายละเอียด / เหตุผล</span> <span class="opt" id="reasonHint">(ไม่บังคับ)</span></div>
                        <textarea class="form-control" id="reason" rows="2" maxlength="500" placeholder="เช่น มีไข้ ไอ น้ำมูก หรือธุระของครอบครัว"></textarea>
                    </div>

                    <div class="lv-actions">
                        <button type="submit" class="btn lv-submit" id="btnSubmit"><i class="bi bi-send me-1"></i>ส่งแจ้งลา</button>
                        <button type="reset" class="lv-reset" id="btnReset">ล้างฟอร์ม</button>
                    </div>
                </form>
            </div>

            <!-- ประวัติการลา -->
            <div class="lv-card">
                <h5 class="lv-card-title">ประวัติการลา <span class="count" id="leaveCount" style="display:none"></span></h5>
                <div id="leaveList"><div class="lv-empty"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลด...</div></div>
            </div>
        </div>
    </div>
</main>

<script>
    const API = '../../include/process/leave_request.php';
    const MAX_DAYS = <?= LEAVE_MAX_DAYS ?>;
    const BACKDATE_DAYS = <?= LEAVE_BACKDATE_DAYS ?>;
    const $ = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const thai = (s) => new Date(String(s).slice(0, 10) + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
    const TYPE_TEXT = { personal: 'ลากิจ', sick: 'ลาป่วย' };
    const STATUS_TEXT = { pending: 'รออนุมัติ', approved: 'อนุมัติแล้ว', rejected: 'ไม่อนุมัติ', cancelled: 'ยกเลิกแล้ว' };
    let todayStr = iso(new Date());

    function selectedType() { const r = document.querySelector('input[name="leave_type"]:checked'); return r ? r.value : ''; }
    function selectedIllness() { const r = document.querySelector('input[name="illness"]:checked'); return r ? r.value : ''; }

    function countDays() {
        const s = $('startDate').value, e = $('endDate').value;
        if (!s || !e) { $('dayCount').textContent = ''; return; }
        const n = Math.round((new Date(e + 'T00:00:00') - new Date(s + 'T00:00:00')) / 86400000) + 1;
        $('dayCount').textContent = n > 0 ? `รวม ${n} วัน` : 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มลา';
        $('dayCount').style.color = n > 0 && n <= MAX_DAYS ? '' : '#b91c1c';
    }

    // ประเภทการลา: ลาป่วยแสดงรายการโรค  ลากิจต้องระบุเหตุผล
    function syncType() {
        const t = selectedType();
        $('sickBox').style.display = t === 'sick' ? '' : 'none';
        $('reasonHint').textContent = t === 'personal' ? '(จำเป็น)' : '(ไม่บังคับ)';
        $('reason').placeholder = t === 'personal' ? 'ระบุเหตุผลการลากิจ' : 'เช่น มีไข้ ไอ น้ำมูก';
        // เลขขั้นตอน: ลาป่วยมีขั้น "โรคที่ป่วย" เพิ่ม
        $('noDate').textContent = t === 'sick' ? '3' : '2';
        $('noReason').textContent = t === 'sick' ? '4' : '3';
    }
    function syncIllness() {
        const other = selectedIllness() === 'other';
        $('illnessOther').style.display = other ? '' : 'none';
        if (other) $('illnessOther').focus();
    }

    function initDates() {
        const now = new Date();
        const min = new Date(now); min.setDate(min.getDate() - BACKDATE_DAYS);
        $('startDate').min = $('endDate').min = iso(min);
        $('startDate').value = $('endDate').value = iso(now);
        countDays();
    }

    function warn(text) { Swal.fire({ icon: 'warning', title: text, confirmButtonText: 'ตกลง' }); }

    async function submitLeave(e) {
        e.preventDefault();
        const type = selectedType();
        if (!type) return warn('กรุณาเลือกประเภทการลา');
        let illness = '', illnessOther = '';
        if (type === 'sick') {
            illness = selectedIllness();
            if (!illness) return warn('กรุณาเลือกโรคที่ป่วย');
            if (illness === 'other') { illnessOther = $('illnessOther').value.trim(); if (!illnessOther) { $('illnessOther').focus(); return warn('กรุณาระบุโรคที่ป่วย'); } }
        }
        const s = $('startDate').value, en = $('endDate').value, reason = $('reason').value.trim();
        if (!s || !en) return warn('กรุณาระบุวันที่ลาให้ครบ');
        if (en < s) return warn('วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มลา');
        if (type === 'personal' && !reason) { $('reason').focus(); return warn('กรุณาระบุเหตุผลการลากิจ'); }

        const btn = $('btnSubmit'); btn.disabled = true;
        try {
            const res = await fetch(API, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'create', leave_type: type, illness, illness_other: illnessOther, start_date: s, end_date: en, reason })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'ส่งแจ้งลาไม่สำเร็จ');
            Swal.fire({ icon: 'success', title: 'ส่งแจ้งลาเรียบร้อย', text: 'รอครูอนุมัติ ดูสถานะได้ที่ประวัติการลา', timer: 2200, showConfirmButton: false });
            $('leaveForm').reset(); syncType(); syncIllness(); initDates();
            loadList();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'ส่งแจ้งลาไม่สำเร็จ', text: err.message, confirmButtonText: 'ตกลง' });
        } finally { btn.disabled = false; }
    }

    async function loadList() {
        const box = $('leaveList');
        try {
            const res = await fetch(`${API}?action=list`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (res.status === 403) throw new Error('หมดเวลาการใช้งาน กรุณาเข้าสู่ระบบใหม่');
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'โหลดไม่สำเร็จ');
            todayStr = data.today || todayStr;
            $('leaveCount').style.display = data.rows.length ? '' : 'none';
            $('leaveCount').textContent = data.rows.length + ' รายการ';
            if (!data.rows.length) { box.innerHTML = '<div class="lv-empty"><i class="bi bi-inbox fs-3 d-block mb-1"></i>ยังไม่มีประวัติการลา</div>'; return; }
            box.innerHTML = data.rows.map((r) => {
                const cancelled = r.status === 'cancelled';
                const range = r.start_date === r.end_date ? thai(r.start_date) : `${thai(r.start_date)} - ${thai(r.end_date)}`;
                // ช่วงเวลา (กำลังลา/ผ่านมาแล้ว) แสดงเฉพาะที่อนุมัติแล้ว
                const when = r.status !== 'approved' ? '' : (r.end_date < todayStr ? '<span class="lv-chip past">ผ่านมาแล้ว</span>' : (r.start_date <= todayStr ? '<span class="lv-chip now">กำลังลา</span>' : ''));
                // ยกเลิกได้: รออนุมัติที่ยังไม่พ้นวันลา หรืออนุมัติแล้วแต่ยังไม่ถึงวันเริ่มลา
                const canCancel = (r.status === 'pending' && r.end_date >= todayStr) || (r.status === 'approved' && r.start_date > todayStr);
                const detail = [r.leave_type === 'sick' && r.illness_name ? 'โรค: ' + esc(r.illness_name) : '', r.reason ? esc(r.reason) : ''].filter(Boolean).join(' · ');
                const decision = (r.status === 'approved' || r.status === 'rejected') && (r.decided_by || r.decision_note)
                    ? `<div class="lv-decision">${r.status === 'rejected' ? 'เหตุผลที่ไม่อนุมัติ' : 'ครูแจ้ง'}: ${r.decision_note ? esc(r.decision_note) : '-'}${r.decided_by ? ` <span class="text-muted">(${esc(r.decided_by)})</span>` : ''}</div>` : '';
                return `<div class="lv-item st-${r.status} ${cancelled || r.status === 'rejected' ? 'cancelled' : ''}">
                    <div class="top">
                        <span class="when">${range}</span>
                        <span class="lv-chip ${r.leave_type}">${TYPE_TEXT[r.leave_type] || ''}</span>
                        <span class="lv-chip ${r.status}">${STATUS_TEXT[r.status] || ''}</span>
                        ${when}
                        ${canCancel ? `<button type="button" class="lv-cancel" data-id="${esc(r.id)}">ยกเลิก</button>` : ''}
                    </div>
                    ${detail ? `<div class="meta">${detail}</div>` : ''}
                    ${decision}
                </div>`;
            }).join('');
        } catch (err) {
            box.innerHTML = `<div class="lv-empty text-danger">${esc(err.message)}</div>`;
        }
    }

    async function cancelLeave(id) {
        const ok = await Swal.fire({ icon: 'question', title: 'ยกเลิกการลานี้?', showCancelButton: true, confirmButtonText: 'ยกเลิกการลา', cancelButtonText: 'ไม่ยกเลิก', confirmButtonColor: '#dc2626' });
        if (!ok.isConfirmed) return;
        try {
            const res = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ action: 'cancel', id: Number(id) }) });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'ยกเลิกไม่สำเร็จ');
            Swal.fire({ icon: 'success', title: 'ยกเลิกการลาแล้ว', timer: 1400, showConfirmButton: false });
            loadList();
        } catch (err) { Swal.fire({ icon: 'error', title: 'ยกเลิกไม่สำเร็จ', text: err.message, confirmButtonText: 'ตกลง' }); }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDates(); syncType(); syncIllness(); loadList();
        document.querySelectorAll('input[name="leave_type"]').forEach((r) => r.addEventListener('change', syncType));
        document.querySelectorAll('input[name="illness"]').forEach((r) => r.addEventListener('change', syncIllness));
        $('startDate').addEventListener('change', () => { if ($('endDate').value < $('startDate').value) $('endDate').value = $('startDate').value; countDays(); });
        $('endDate').addEventListener('change', countDays);
        $('leaveForm').addEventListener('submit', submitLeave);
        $('leaveForm').addEventListener('reset', () => setTimeout(() => { syncType(); syncIllness(); initDates(); }, 0));
        $('leaveList').addEventListener('click', (e) => { const b = e.target.closest('.lv-cancel'); if (b) cancelLeave(b.dataset.id); });
    });
</script>
</body>
</html>
