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
    .lv-wrap { max-width: 1100px; margin: 0 auto; }
    .lv-header { background: radial-gradient(circle at 92% 15%, rgba(255,255,255,.22) 0 8%, transparent 8.5%), radial-gradient(circle at 78% 95%, rgba(255,255,255,.12) 0 12%, transparent 12.5%), linear-gradient(120deg, #1E4F6F 0%, #26648E 58%, #4f88aa 100%); border-radius: 1.75rem; color: #fff; padding: 1.5rem 2rem; margin-bottom: 1.25rem; box-shadow: 0 18px 40px rgba(38, 100, 142, .2); position: relative; overflow: hidden; }
    .lv-header .t { font-size: .85rem; opacity: .75; }
    .lv-header .n { font-size: clamp(1.3rem, 5vw, 1.8rem); font-weight: 800; line-height: 1.15; }
    .lv-header .f { font-size: .85rem; opacity: .85; }
    .lv-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1rem; align-items: start; }
    @media (max-width: 992px) { .lv-grid { grid-template-columns: 1fr; } }
    .lv-card { background: #fff; border: 1px solid #d9e6ee; border-radius: 1.25rem; box-shadow: 0 12px 30px rgba(15, 23, 42, .06); padding: 1.25rem 1.4rem; }
    .lv-card h5 { font-size: 1.1rem; font-weight: 800; color: #1E4F6F; margin: 0 0 .75rem; padding-bottom: .7rem; border-bottom: 1px solid #d9e6ee; display: flex; align-items: center; gap: .55rem; }
    .lv-card h5 i { width: 34px; height: 34px; border-radius: .7rem; background: #ebf4fb; color: #26648E !important; display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; }
    .lv-card .form-control:focus, .lv-card .form-select:focus { border-color: #26648E; box-shadow: 0 0 0 .2rem rgba(38, 100, 142, .2); }
    .lv-card .form-control, .lv-card .form-select { border-color: #cbd9e3; border-radius: .7rem; }
    .lv-label { display: block; font-size: .8rem; font-weight: 700; color: #2c3e50; margin: .8rem 0 .3rem; }
    .lv-types { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
    .lv-type { position: relative; }
    .lv-type input { position: absolute; opacity: 0; pointer-events: none; }
    .lv-type label { display: flex; flex-direction: column; align-items: center; gap: .2rem; padding: .85rem .5rem; border: 2px solid #e2e8f0; border-radius: 14px; background: #fff; font-weight: 800; color: #475569; cursor: pointer; text-align: center; transition: border-color .15s, background-color .15s, color .15s; }
    .lv-type label i { font-size: 1.5rem; }
    .lv-type label small { font-weight: 400; font-size: .72rem; color: #94a3b8; }
    .lv-type input:checked + label { border-color: #26648E; background: #ebf4fb; color: #1E4F6F; box-shadow: 0 6px 16px rgba(38, 100, 142, .16); }
    .lv-type input:focus-visible + label { outline: 3px solid #93c5fd; }
    .lv-illness { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    @media (max-width: 480px) { .lv-illness { grid-template-columns: 1fr; } }
    .lv-ill { position: relative; }
    .lv-ill input { position: absolute; opacity: 0; pointer-events: none; }
    .lv-ill label { display: block; padding: .55rem .8rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: .9rem; font-weight: 600; color: #475569; cursor: pointer; transition: border-color .15s, background-color .15s; }
    .lv-ill input:checked + label { border-color: #dc2626; background: #fef2f2; color: #b91c1c; }
    .lv-ill input:focus-visible + label { outline: 3px solid #fca5a5; }
    .lv-ill.wide { grid-column: 1 / -1; }
    .lv-actions { margin-top: 1.1rem; display: flex; gap: .5rem; }
    .lv-submit { background: #26648E; border-color: #26648E; color: #fff; font-weight: 800; padding: .6rem 1.5rem; border-radius: .8rem; box-shadow: 0 6px 16px rgba(38, 100, 142, .25); }
    .lv-submit:hover:not(:disabled) { background: #1E4F6F; border-color: #1E4F6F; color: #fff; }
    .lv-days { font-size: .82rem; color: #64748b; margin-top: .35rem; }
    .lv-item { border: 1px solid #d9e6ee; border-left: 5px solid #26648E; border-radius: .9rem; padding: .7rem .9rem; margin-bottom: .6rem; }
    .lv-item.cancelled { opacity: .6; background: #f8fafc; border-left-color: #cbd5e1; }
    .lv-item.st-pending { border-left-color: #f59e0b; } .lv-item.st-approved { border-left-color: #22c55e; } .lv-item.st-rejected { border-left-color: #ef4444; }
    .lv-item .top { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; }
    .lv-item .when { font-weight: 800; color: #1E4F6F; }
    .lv-item .meta { font-size: .8rem; color: #64748b; margin-top: .25rem; }
    .lv-chip { font-size: .72rem; font-weight: 800; padding: 2px 10px; border-radius: 999px; white-space: nowrap; }
    .lv-chip.sick { background: #fee2e2; color: #b91c1c; } .lv-chip.personal { background: #dcebf3; color: #1E4F6F; }
    .lv-chip.pending { background: #fef3c7; color: #b45309; } .lv-chip.approved { background: #dcfce7; color: #15803d; }
    .lv-chip.rejected { background: #fee2e2; color: #b91c1c; } .lv-chip.cancelled { background: #e5e7eb; color: #64748b; }
    .lv-decision { font-size: .78rem; margin-top: .3rem; padding: .3rem .6rem; border-radius: 8px; background: #f1f5f9; color: #475569; }
    .lv-chip.past { background: #f1f5f9; color: #64748b; } .lv-chip.now { background: #fef3c7; color: #b45309; }
    .lv-cancel { margin-left: auto; border: 1.5px solid #fecaca; background: #fff; color: #b91c1c; border-radius: 999px; font-size: .75rem; font-weight: 700; padding: 2px 12px; cursor: pointer; }
    .lv-cancel:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
    .lv-empty { text-align: center; color: #94a3b8; padding: 1.5rem .5rem; }
</style>

<main class="main-content">
    <div class="container-fluid px-3 px-lg-4">
        <div class="lv-wrap">
            <div class="lv-header">
                <div class="t">แจ้งการลา</div>
                <div class="n"><?= htmlspecialchars($nick !== '' ? $nick : $childName) ?></div>
                <?php if ($nick !== ''): ?><div class="f"><?= htmlspecialchars($childName) ?></div><?php endif; ?>
            </div>

            <div class="lv-grid">
                <!-- ฟอร์มแจ้งลา -->
                <div class="lv-card">
                    <h5><i class="bi bi-calendar-x text-primary"></i>แจ้งลาใหม่</h5>
                    <form id="leaveForm" novalidate>
                        <span class="lv-label" style="margin-top:0">ประเภทการลา</span>
                        <div class="lv-types">
                            <div class="lv-type">
                                <input type="radio" name="leave_type" id="typePersonal" value="personal">
                                <label for="typePersonal"><i class="bi bi-briefcase"></i>ลากิจ<small>ธุระส่วนตัว / ครอบครัว</small></label>
                            </div>
                            <div class="lv-type">
                                <input type="radio" name="leave_type" id="typeSick" value="sick">
                                <label for="typeSick"><i class="bi bi-thermometer-half"></i>ลาป่วย<small>เด็กไม่สบาย</small></label>
                            </div>
                        </div>

                        <!-- ลาป่วย: เลือกโรค -->
                        <div id="sickBox" style="display:none">
                            <span class="lv-label">โรคที่ป่วย</span>
                            <div class="lv-illness">
                                <?php foreach (LEAVE_COMMON_DISEASES as $key => $label): ?>
                                    <div class="lv-ill">
                                        <input type="radio" name="illness" id="ill_<?= $key ?>" value="<?= $key ?>">
                                        <label for="ill_<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="lv-ill wide">
                                    <input type="radio" name="illness" id="ill_other" value="other">
                                    <label for="ill_other">อื่นๆ (ระบุโรค)</label>
                                </div>
                            </div>
                            <input type="text" class="form-control mt-2" id="illnessOther" maxlength="150" placeholder="พิมพ์ชื่อโรคหรืออาการที่ป่วย" style="display:none">
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="lv-label" for="startDate">ลาตั้งแต่วันที่</label>
                                <input type="date" class="form-control" id="startDate">
                            </div>
                            <div class="col-6">
                                <label class="lv-label" for="endDate">ถึงวันที่</label>
                                <input type="date" class="form-control" id="endDate">
                            </div>
                        </div>
                        <div class="lv-days" id="dayCount"></div>

                        <label class="lv-label" for="reason"><span id="reasonLabel">รายละเอียด / เหตุผล</span> <span class="text-muted fw-normal" id="reasonHint">(ไม่บังคับ)</span></label>
                        <textarea class="form-control" id="reason" rows="3" maxlength="500" placeholder="เช่น มีไข้ ไอ น้ำมูก หรือธุระของครอบครัว"></textarea>

                        <div class="lv-actions">
                            <button type="submit" class="btn lv-submit" id="btnSubmit"><i class="bi bi-send me-1"></i>ส่งแจ้งลา</button>
                            <button type="reset" class="btn btn-outline-secondary" id="btnReset">ล้างฟอร์ม</button>
                        </div>
                    </form>
                </div>

                <!-- ประวัติการลา -->
                <div class="lv-card">
                    <h5><i class="bi bi-clock-history text-primary"></i>ประวัติการลา</h5>
                    <div id="leaveList"><div class="lv-empty"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลด...</div></div>
                </div>
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
        $('dayCount').style.color = n > 0 && n <= MAX_DAYS ? '#64748b' : '#b91c1c';
    }

    // ประเภทการลา: ลาป่วยแสดงรายการโรค  ลากิจต้องระบุเหตุผล
    function syncType() {
        const t = selectedType();
        $('sickBox').style.display = t === 'sick' ? '' : 'none';
        $('reasonHint').textContent = t === 'personal' ? '(จำเป็น)' : '(ไม่บังคับ)';
        $('reason').placeholder = t === 'personal' ? 'ระบุเหตุผลการลากิจ' : 'เช่น มีไข้ ไอ น้ำมูก';
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
