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
    .la-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .la-title { font-size: 1.5rem; font-weight: 700; color: #0f2460; margin: 0; }
    .la-sub { font-size: .85rem; color: #64748b; margin-top: .15rem; }
    .la-label { display: block; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .2rem; }
    .la-tabs { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .9rem; }
    .la-tab { border: 1.5px solid #e2e8f0; background: #fff; color: #475569; border-radius: 999px; padding: .35rem 1rem; font-weight: 700; font-size: .85rem; cursor: pointer; transition: all .15s; }
    .la-tab:hover { border-color: #1e4db7; color: #1e4db7; }
    .la-tab.active { background: #1e4db7; border-color: #1e4db7; color: #fff; }
    .la-filters { display: grid; gap: .75rem 1rem; grid-template-columns: repeat(5, minmax(0, 1fr)); align-items: end; }
    @media (max-width: 1200px) { .la-filters { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 768px) { .la-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .la-filters { grid-template-columns: 1fr; } }
    .la-pills { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .75rem; }
    .la-pill { border-radius: 999px; padding: 3px 14px; font-weight: 700; font-size: .8rem; background: #e2e8f0; color: #475569; }

    .la-tablecard { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); overflow: hidden; }
    .la-wrap { overflow: auto; max-height: 74vh; }
    .la-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .88rem; }
    .la-table th { background: #f1f5f9; color: #475569; font-size: .76rem; font-weight: 700; padding: 8px 10px; text-align: center; white-space: nowrap; border-bottom: 1px solid #cbd5e1; position: sticky; top: 0; z-index: 2; }
    .la-table td { padding: 9px 10px; border-bottom: 1px solid #eef2f6; vertical-align: middle; text-align: center; }
    .la-table .left { text-align: left; }
    .la-table .nw { white-space: nowrap; }
    .la-table tbody tr:hover td { background: #f8fafc; }
    .la-table tr.pending td:first-child { box-shadow: inset 4px 0 0 #f59e0b; }
    .la-table tr.approved td:first-child { box-shadow: inset 4px 0 0 #22c55e; }
    .la-table tr.rejected td:first-child { box-shadow: inset 4px 0 0 #ef4444; }
    .la-table tr.cancelled td:first-child { box-shadow: inset 4px 0 0 #cbd5e1; }
    .la-table tr.cancelled td { color: #94a3b8; }
    .la-name { font-weight: 700; color: #0f2460; line-height: 1.2; }
    .la-nick { font-size: .74rem; color: #64748b; }
    .la-detail { max-width: 260px; text-align: left; font-size: .82rem; color: #475569; }
    .la-days { font-size: .74rem; color: #64748b; }
    .la-chip { font-size: .74rem; font-weight: 700; padding: 2px 10px; border-radius: 999px; white-space: nowrap; }
    .la-chip.sick { background: #fee2e2; color: #b91c1c; } .la-chip.personal { background: #dbeafe; color: #1d4ed8; }
    .la-chip.pending { background: #fef3c7; color: #b45309; } .la-chip.approved { background: #dcfce7; color: #15803d; }
    .la-chip.rejected { background: #fee2e2; color: #b91c1c; } .la-chip.cancelled { background: #e5e7eb; color: #64748b; }
    .la-chip.now { background: #fef3c7; color: #b45309; }
    .la-btn { display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; padding: 4px 12px; border-radius: 999px; border: 1.5px solid transparent; font-size: .78rem; font-weight: 700; cursor: pointer; transition: background-color .15s, color .15s; }
    .la-btn:active { transform: scale(.96); }
    .la-btn.ok { background: #f0fdf4; border-color: #86efac; color: #15803d; } .la-btn.ok:hover { background: #15803d; color: #fff; }
    .la-btn.no { background: #fef2f2; border-color: #fecaca; color: #b91c1c; } .la-btn.no:hover { background: #dc2626; color: #fff; }
    .la-decision { font-size: .74rem; color: #64748b; text-align: left; }
    .la-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); }
    .la-empty > i { display: block; font-size: 2rem; color: #94a3b8; margin-bottom: .4rem; }
    .la-empty .big { font-size: 1.05rem; font-weight: 700; color: #334155; margin-bottom: .25rem; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="la-title">อนุมัติการลา</h2>
                <div class="la-sub">คำขอลาที่ผู้ปกครองแจ้ง เมื่ออนุมัติ การเช็กชื่อของวันที่ลาจะเป็นสถานะ "ลา" อัตโนมัติ (เด็กที่สแกนมาเรียนแล้วไม่ถูกเปลี่ยน)</div>
            </div>
        </div>

        <div class="la-tabs" id="laTabs">
            <button type="button" class="la-tab active" data-status="pending">รออนุมัติ</button>
            <button type="button" class="la-tab" data-status="approved">อนุมัติแล้ว</button>
            <button type="button" class="la-tab" data-status="rejected">ไม่อนุมัติ</button>
            <button type="button" class="la-tab" data-status="cancelled">ยกเลิกแล้ว</button>
            <button type="button" class="la-tab" data-status="all">ทั้งหมด</button>
        </div>

        <div class="la-card">
            <div class="la-filters">
                <div>
                    <label for="fGroup" class="la-label">กลุ่มเรียน</label>
                    <select id="fGroup" class="form-select">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($groups as $g): if (!empty($g['child_group'])): ?>
                            <option value="<?= htmlspecialchars($g['child_group']) ?>"><?= htmlspecialchars($g['child_group']) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="fRoom" class="la-label">ห้องเรียน</label>
                    <select id="fRoom" class="form-select"><option value="">-- ทั้งหมด --</option></select>
                </div>
                <div>
                    <label for="fSearch" class="la-label">ค้นหาชื่อ / รหัส</label>
                    <input type="text" id="fSearch" class="form-control" placeholder="ชื่อ ชื่อเล่น หรือรหัส" autocomplete="off">
                </div>
                <div>
                    <label for="fFrom" class="la-label">ลาตั้งแต่วันที่</label>
                    <input type="date" id="fFrom" class="form-control">
                </div>
                <div>
                    <label for="fTo" class="la-label">ถึงวันที่</label>
                    <input type="date" id="fTo" class="form-control">
                </div>
            </div>
        </div>

        <div class="la-pills" id="laSummary" style="display:none"></div>
        <div id="laList"></div>
    </div>
</main>

<script>
    const API = '../include/process/leave_manage.php';
    const XHR = { 'X-Requested-With': 'XMLHttpRequest' };
    const $ = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const thai = (s) => new Date(String(s).slice(0, 10) + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
    const thaiDt = (s) => new Date(String(s).replace(' ', 'T')).toLocaleString('th-TH', { day: 'numeric', month: 'short', year: '2-digit', hour: '2-digit', minute: '2-digit' });
    const TYPE_TEXT = { personal: 'ลากิจ', sick: 'ลาป่วย' };
    const STATUS_TEXT = { pending: 'รออนุมัติ', approved: 'อนุมัติแล้ว', rejected: 'ไม่อนุมัติ', cancelled: 'ยกเลิกแล้ว' };
    let status = 'pending';
    let todayStr = '';
    let debounceT;

    function dayCount(a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000) + 1; }

    async function load() {
        const box = $('laList');
        box.innerHTML = '<div class="la-empty"><div class="spinner-border text-primary"></div></div>';
        const q = new URLSearchParams({ status, child_group: $('fGroup').value, classroom: $('fRoom').value, search: $('fSearch').value.trim(), date_from: $('fFrom').value, date_to: $('fTo').value });
        try {
            const res = await fetch(`${API}?${q}`, { headers: XHR });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'โหลดไม่สำเร็จ');
            todayStr = data.today;
            render(data.rows);
        } catch (e) {
            $('laSummary').style.display = 'none';
            box.innerHTML = `<div class="la-empty"><i class="bi bi-exclamation-triangle text-danger"></i><div class="big">โหลดไม่สำเร็จ</div>${esc(e.message)}</div>`;
        }
    }

    function render(rows) {
        const box = $('laList'), sum = $('laSummary');
        if (!rows.length) {
            sum.style.display = 'none';
            box.innerHTML = `<div class="la-empty"><i class="bi bi-inbox"></i><div class="big">${status === 'pending' ? 'ไม่มีคำขอลาที่รออนุมัติ' : 'ไม่พบรายการ'}</div>ลองเปลี่ยนสถานะ ห้อง หรือช่วงวันที่</div>`;
            return;
        }
        sum.innerHTML = `<span class="la-pill">${rows.length} รายการ</span>`;
        sum.style.display = '';

        const body = rows.map((r) => {
            const name = `${r.prefix_th || ''}${r.firstname_th || ''} ${r.lastname_th || ''}`.trim();
            const range = r.start_date === r.end_date ? thai(r.start_date) : `${thai(r.start_date)} - ${thai(r.end_date)}`;
            const detail = [r.leave_type === 'sick' && r.illness_name ? '<b>โรค:</b> ' + esc(r.illness_name) : '', r.reason ? esc(r.reason) : ''].filter(Boolean).join('<br>') || '<span class="text-muted">-</span>';
            const now = r.status === 'approved' && r.start_date <= todayStr && r.end_date >= todayStr ? ' <span class="la-chip now">กำลังลา</span>' : '';
            const decided = r.decided_by || r.decision_note
                ? `<div class="la-decision">${r.decision_note ? esc(r.decision_note) + '<br>' : ''}${r.decided_by ? 'โดย ' + esc(r.decided_by) : ''}${r.decided_at ? ' · ' + thaiDt(r.decided_at) : ''}</div>` : '';
            const actions = r.status === 'pending'
                ? `<button type="button" class="la-btn ok" data-act="approve" data-id="${esc(r.id)}" data-name="${esc(name)}"><i class="bi bi-check-lg"></i>อนุมัติ</button>
                   <button type="button" class="la-btn no" data-act="reject" data-id="${esc(r.id)}" data-name="${esc(name)}"><i class="bi bi-x-lg"></i>ไม่อนุมัติ</button>`
                : decided || '<span class="text-muted">-</span>';
            return `<tr class="${esc(r.status)}">
                <td class="left"><div class="la-name">${esc(name)}</div><div class="la-nick">${r.nickname ? 'ชื่อเล่น ' + esc(r.nickname) + ' · ' : ''}${esc(r.child_group)} · ${esc(r.classroom)}</div></td>
                <td><span class="la-chip ${esc(r.leave_type)}">${TYPE_TEXT[r.leave_type] || ''}</span></td>
                <td class="nw"><div>${range}</div><div class="la-days">${dayCount(r.start_date, r.end_date)} วัน</div></td>
                <td class="la-detail">${detail}</td>
                <td class="nw"><div class="la-days">${esc(r.created_by || '')}</div><div class="la-days">${thaiDt(r.created_at)}</div></td>
                <td class="nw"><span class="la-chip ${esc(r.status)}">${STATUS_TEXT[r.status] || ''}</span>${now}</td>
                <td class="nw" style="min-width:190px">${actions}</td>
            </tr>`;
        }).join('');

        box.innerHTML = `<div class="la-tablecard"><div class="la-wrap"><table class="la-table">
            <thead><tr><th class="left">เด็ก</th><th>ประเภท</th><th>วันที่ลา</th><th class="left">โรค / เหตุผล</th><th>แจ้งเมื่อ</th><th>สถานะ</th><th>การดำเนินการ</th></tr></thead>
            <tbody>${body}</tbody></table></div></div>`;
    }

    async function decide(id, name, decision) {
        const approve = decision === 'approve';
        const res = await Swal.fire({
            icon: approve ? 'question' : 'warning',
            title: approve ? 'อนุมัติการลา?' : 'ไม่อนุมัติการลา?',
            html: `<div class="mb-2">${esc(name)}</div>` +
                `<textarea id="swNote" class="form-control" rows="3" maxlength="300" placeholder="${approve ? 'หมายเหตุถึงผู้ปกครอง (ไม่บังคับ)' : 'ระบุเหตุผลที่ไม่อนุมัติ (จำเป็น)'}"></textarea>`,
            showCancelButton: true, confirmButtonText: approve ? 'อนุมัติ' : 'ไม่อนุมัติ', cancelButtonText: 'ยกเลิก',
            confirmButtonColor: approve ? '#15803d' : '#dc2626',
            preConfirm: () => {
                const note = document.getElementById('swNote').value.trim();
                if (!approve && !note) { Swal.showValidationMessage('กรุณาระบุเหตุผลที่ไม่อนุมัติ'); return false; }
                return note;
            }
        });
        if (!res.isConfirmed) return;
        try {
            const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json', ...XHR }, body: JSON.stringify({ action: 'decide', id: Number(id), decision, note: res.value }) });
            const data = await r.json();
            if (data.status !== 'success') throw new Error(data.message || 'ดำเนินการไม่สำเร็จ');
            Swal.fire({ icon: 'success', title: data.message, text: approve && data.attendance_updated ? `ปรับการเช็กชื่อเป็น "ลา" แล้ว ${data.attendance_updated} วัน` : '', timer: 1800, showConfirmButton: false });
            load();
        } catch (e) { Swal.fire({ icon: 'error', title: 'ดำเนินการไม่สำเร็จ', text: e.message, confirmButtonText: 'ตกลง' }); load(); }
    }

    function loadRooms() {
        const group = $('fGroup').value, sel = $('fRoom');
        sel.innerHTML = '<option value="">-- ทั้งหมด --</option>';
        if (!group) return Promise.resolve();
        return fetch(`../include/function/get_classrooms.php?child_group=${encodeURIComponent(group)}`)
            .then((r) => r.json())
            .then((d) => { [...new Set((Array.isArray(d) ? d : []).map((c) => c.classroom_name))].forEach((n) => { const o = document.createElement('option'); o.value = o.textContent = n; sel.appendChild(o); }); })
            .catch(console.error);
    }

    document.addEventListener('DOMContentLoaded', () => {
        $('laTabs').addEventListener('click', (e) => {
            const b = e.target.closest('.la-tab'); if (!b) return;
            status = b.dataset.status;
            document.querySelectorAll('.la-tab').forEach((t) => t.classList.toggle('active', t === b));
            load();
        });
        $('fGroup').addEventListener('change', async () => { await loadRooms(); load(); });
        $('fRoom').addEventListener('change', load);
        $('fFrom').addEventListener('change', load);
        $('fTo').addEventListener('change', load);
        $('fSearch').addEventListener('input', () => { clearTimeout(debounceT); debounceT = setTimeout(load, 450); });
        $('laList').addEventListener('click', (e) => {
            const b = e.target.closest('button[data-act]'); if (!b) return;
            decide(b.dataset.id, b.dataset.name, b.dataset.act);
        });
        load();
    });
</script>
</body>
</html>
