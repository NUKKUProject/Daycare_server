<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';

$academicYears = getAcademicYears();
$currentTop = isset($academicYears[0]['name']) ? (int) $academicYears[0]['name'] : (int) date('Y') + 543;
?>

<style>
    .rd-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .rd-badge { border-radius: 999px; padding: 2px 12px; font-size: .78rem; font-weight: 700; }
    .rd-open { background: #dcfce7; color: #15803d; }
    .rd-closed { background: #fee2e2; color: #b91c1c; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h2 class="mb-0">จัดการรอบตรวจสุขภาพช่องปาก</h2>
            <a href="checklist_name.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>กลับหน้ารายชื่อ</a>
        </div>

        <div class="rd-card">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" for="rYear">ปีการศึกษา</label>
                    <select id="rYear" class="form-select">
                        <option value="">ทุกปี</option>
                        <?php foreach (array_merge([$currentTop + 1], array_map(fn($y) => (int) $y['name'], $academicYears)) as $i => $y): ?>
                            <option value="<?= $y ?>" <?= $i === 1 ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="button" class="btn btn-primary" id="rNew"><i class="bi bi-plus-circle me-1"></i>เปิดรอบใหม่</button>
                </div>
            </div>
            <div class="small text-muted mt-3">
                1 รอบ = การตรวจหนึ่งครั้งของทั้งศูนย์ ·
                เมื่อปิดรอบ ผลตรวจของรอบนั้นจะถูกล็อก (แก้/ลบ/บันทึกเพิ่มไม่ได้) จนกว่าจะเปิดรอบอีกครั้ง
            </div>
        </div>

        <div class="rd-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ปีการศึกษา</th><th>รอบ</th><th>ช่วงวันที่</th><th class="text-center">เด็กที่ตรวจแล้ว (คน)</th><th>สถานะ</th><th></th>
                        </tr>
                    </thead>
                    <tbody id="rBody"><tr><td colspan="6" class="text-center text-muted py-4">กำลังโหลด...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    const API = './process/manage_tooth_rounds.php';
    const XHR = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' };
    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const thaiDate = (s) => (s ? new Date(s + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }) : '');

    async function call(action, body) {
        const res = await fetch(API, { method: 'POST', headers: XHR, body: JSON.stringify({ action, ...body }) });
        const data = await res.json();
        if (data.status !== 'success') throw new Error(data.message || 'เกิดข้อผิดพลาด');
        return data;
    }

    async function load() {
        const year = byId('rYear').value;
        const res = await fetch(`${API}?action=list&academic_year=${encodeURIComponent(year)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        const list = data.data || [];
        byId('rBody').innerHTML = list.length ? list.map((r) => {
            const range = r.start_date || r.end_date ? `${thaiDate(r.start_date)} – ${thaiDate(r.end_date)}` : '-';
            const open = r.status === 'open';
            return `<tr><td>${esc(r.academic_year)}</td><td>${esc(r.title)}</td><td>${range}</td>
                <td class="text-center">${r.child_count}</td>
                <td><span class="rd-badge ${open ? 'rd-open' : 'rd-closed'}">${open ? 'เปิดอยู่' : 'ปิดแล้ว'}</span></td>
                <td class="text-end"><button class="btn btn-sm ${open ? 'btn-outline-danger' : 'btn-outline-success'}" data-act="${open ? 'close' : 'reopen'}" data-id="${r.id}" data-title="${esc(r.title)}">
                    <i class="bi ${open ? 'bi-lock' : 'bi-unlock'} me-1"></i>${open ? 'ปิดรอบ' : 'เปิดรอบอีกครั้ง'}</button></td></tr>`;
        }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีรอบตรวจ กด "เปิดรอบใหม่"</td></tr>';
    }

    async function newRound() {
        const year = byId('rYear').value || '<?= $currentTop + 1 ?>';
        const r = await Swal.fire({
            title: 'เปิดรอบตรวจใหม่',
            html: `<div class="text-start">
                <label class="form-label mt-2">ปีการศึกษา</label><input id="swYear" class="form-control" value="${esc(year)}" inputmode="numeric">
                <label class="form-label mt-2">ชื่อรอบ (เว้นว่างได้ ระบบตั้งเป็น "ครั้งที่ N")</label><input id="swTitle" class="form-control" maxlength="150" placeholder="เช่น ครั้งที่ 2 ภาคเรียนที่ 2">
                <div class="row"><div class="col-6"><label class="form-label mt-2">วันเริ่ม</label><input id="swStart" type="date" class="form-control"></div>
                <div class="col-6"><label class="form-label mt-2">วันสิ้นสุด</label><input id="swEnd" type="date" class="form-control"></div></div></div>`,
            showCancelButton: true, confirmButtonText: 'เปิดรอบ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#15803d',
            preConfirm: () => ({ academic_year: byId('swYear').value.trim(), title: byId('swTitle').value.trim(), start_date: byId('swStart').value, end_date: byId('swEnd').value })
        });
        if (!r.isConfirmed) return;
        try { const d = await call('create', r.value); Swal.fire({ icon: 'success', title: d.message, timer: 1800, showConfirmButton: false }); byId('rYear').value = r.value.academic_year; load(); }
        catch (e) { Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message }); }
    }

    document.addEventListener('DOMContentLoaded', () => {
        byId('rYear').addEventListener('change', load);
        byId('rNew').addEventListener('click', newRound);
        byId('rBody').addEventListener('click', async (e) => {
            const b = e.target.closest('button[data-act]'); if (!b) return;
            const close = b.dataset.act === 'close';
            const ok = await Swal.fire({
                icon: close ? 'warning' : 'question', title: close ? `ปิด "${b.dataset.title}"?` : `เปิด "${b.dataset.title}" อีกครั้ง?`,
                text: close ? 'ผลตรวจของรอบนี้จะถูกล็อก แก้ไข ลบ หรือบันทึกเพิ่มไม่ได้' : 'ครูและแพทย์จะบันทึก/แก้ไขผลของรอบนี้ได้อีก',
                showCancelButton: true, confirmButtonText: close ? 'ปิดรอบ' : 'เปิดรอบ', cancelButtonText: 'ยกเลิก'
            });
            if (!ok.isConfirmed) return;
            try { await call(b.dataset.act, { id: +b.dataset.id }); load(); }
            catch (err) { Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err.message }); }
        });
        load();
    });
</script>
</body>
</html>
