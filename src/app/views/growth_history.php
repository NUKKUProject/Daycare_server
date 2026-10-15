<?php
include __DIR__ . '/../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'student']);
include __DIR__ . '/partials/Header.php';
include __DIR__ . '/../include/auth/auth_navbar.php';
require_once '../include/function/pages_referen.php';
require_once __DIR__ . '/../include/function/child_functions.php';
$is_admin = getUserRole() === 'admin';
$is_student = getUserRole() === 'student';
$is_teacher = getUserRole() === 'teacher';
require_once __DIR__ . '/../include/auth/auth_dashboard.php';

// ใช้ user_id จาก session เป็น teacher_id
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
} else {
    die('ไม่พบข้อมูลผู้สอน. กรุณาเข้าสู่ระบบอีกครั้ง.');
}
?>
<body>

<style>
    .gh-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .gh-title { font-size: 1.5rem; font-weight: 700; color: #0f2460; margin: 0; }
    .gh-sub { font-size: .85rem; color: #64748b; margin-top: .15rem; }
    .gh-label { display: block; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .2rem; }
    .gh-filters { display: grid; gap: .75rem 1rem; grid-template-columns: repeat(5, minmax(0, 1fr)); align-items: end; }
    @media (max-width: 1200px) { .gh-filters { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 768px) { .gh-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .gh-filters { grid-template-columns: 1fr; } }
    .gh-filter-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .85rem; }
    .gh-top-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

    .gh-pills { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .75rem; align-items: center; }
    .gh-pill { border-radius: 999px; padding: 3px 14px; font-weight: 700; font-size: .8rem; background: #e2e8f0; color: #475569; }
    .gh-pill.ok { background: #dcfce7; color: #15803d; } .gh-pill.bad { background: #fee2e2; color: #b91c1c; }
    .gh-note { font-size: .8rem; color: #b45309; background: #fef3c7; border-radius: 8px; padding: 3px 12px; }

    .gh-tablecard { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); overflow: hidden; }
    .gh-table-wrap { overflow: auto; max-height: 72vh; }
    .gh-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .88rem; }
    .gh-table th { background: #f1f5f9; color: #475569; font-size: .76rem; font-weight: 700; padding: 8px 10px; text-align: center; white-space: nowrap; border-bottom: 1px solid #cbd5e1; position: sticky; top: 0; z-index: 2; }
    .gh-table td { padding: 8px 10px; text-align: center; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
    .gh-table .left { text-align: left; }
    .gh-table .nw { white-space: nowrap; }
    .gh-table tbody tr:hover td { background: #f8fafc; }
    .gh-table tr.daystart td { border-top: 2px solid #cbd5e1; }
    .gh-name { font-weight: 700; color: #0f2460; line-height: 1.2; }
    .gh-nick { font-size: .74rem; color: #64748b; }
    .gh-dash { color: #cbd5e1; }
    .gh-table small { color: #94a3b8; }
    .gh-chip { font-size: .74rem; font-weight: 700; padding: 2px 10px; border-radius: 999px; white-space: nowrap; }
    .gh-chip.ok { background: #dcfce7; color: #15803d; } .gh-chip.bad { background: #fee2e2; color: #b91c1c; } .gh-chip.muted { background: #f1f5f9; color: #94a3b8; }
    .gh-icon { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; cursor: pointer; color: inherit; text-decoration: none; transition: background-color .15s, color .15s; }
    .gh-icon.warn { color: #b45309; } .gh-icon.warn:hover { background: #f59e0b; border-color: #f59e0b; color: #fff; }
    .gh-icon.info { color: #1d4ed8; } .gh-icon.info:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
    .gh-icon.danger { color: #b91c1c; } .gh-icon.danger:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
    .gh-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); }
    .gh-empty > i { display: block; font-size: 2rem; color: #94a3b8; margin-bottom: .4rem; }
    .gh-empty .big { font-size: 1.05rem; font-weight: 700; color: #334155; margin-bottom: .25rem; }

    .swal2-popup { border-radius: 16px; font-family: inherit; }
    .modal-content { border: 0; border-radius: 16px; box-shadow: 0 20px 60px rgba(15, 36, 96, .25); }
    .modal-header { border-bottom: 1px solid #e2e8f0; }
    .modal-title { color: #0f2460; font-weight: 700; }
    .modal-footer { border-top: 1px solid #e2e8f0; }
    .export-option { padding: 10px 0; }
    .gh-btn { display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; padding: 4px 14px; border-radius: 999px; border: 1.5px solid transparent; font-size: .8rem; font-weight: 700; cursor: pointer; transition: background-color .15s, color .15s, box-shadow .15s; }
    .gh-btn:active { transform: scale(.96); }
    .gh-btn.primary { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; } .gh-btn.primary:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; box-shadow: 0 2px 8px rgba(29, 78, 216, .3); }

    /* modal ข้อมูลทั้งหมดของเด็ก */
    .ghm-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .6rem; margin-bottom: .9rem; }
    .ghm-tile { background: #f1f5f9; border-radius: 12px; padding: .6rem .85rem; }
    .ghm-tile span { display: block; font-size: .72rem; font-weight: 700; color: #64748b; }
    .ghm-tile b { font-size: 1rem; color: #0f2460; }
    .ghm-tile small { font-weight: 400; color: #64748b; }
    .ghm-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: .8rem 1rem; }
    .ghm-box h6 { font-size: .9rem; font-weight: 700; color: #0f2460; margin: 0 0 .5rem; display: flex; align-items: center; gap: .4rem; }
    .ghm-charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 1.1rem; }
    .ghm-chart { border: 1px solid #e2e8f0; border-radius: 12px; padding: .8rem 1rem 1rem; }
    .ghm-chart h6 { font-size: .82rem; font-weight: 700; color: #0f2460; margin: 0 0 .4rem; }
    .ghm-canvas { position: relative; height: 380px; }
    .ghm-empty { height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: .85rem; }
    #childModal .modal-body { background: #fff; }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="gh-title">ประวัติการบันทึกการเจริญเติบโตของเด็ก</h2>
                <div class="gh-sub">รายชื่อเด็กที่มีประวัติการบันทึก กด "ดูกราฟ" เพื่อดูข้อมูลทั้งหมดของเด็กคนนั้น</div>
            </div>
            <div class="gh-top-actions">
                <?php if ($is_admin || $is_teacher): ?>
                <a href="growth_dev_grid.php" class="btn btn-success btn-sm"><i class="bi bi-pencil-square me-1"></i>กรอกผลทั้งห้อง</a>
                <a href="growth_dev_view.php" class="btn btn-primary btn-sm"><i class="bi bi-table me-1"></i>ดูผลและกราฟรายห้อง</a>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportToExcel()"><i class="bi bi-file-excel me-1"></i>Export Excel</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- ตัวกรอง -->
        <div class="gh-card">
            <form id="searchForm">
                <div class="gh-filters">
                    <?php if (!$is_student): ?>
                    <div>
                        <label for="child_group" class="gh-label">กลุ่มเรียน</label>
                        <select id="child_group" class="form-select">
                            <option value="">-- ทั้งหมด --</option>
                            <?php foreach (get_childgroup() as $g): if (!empty($g['child_group'])): ?>
                                <option value="<?= htmlspecialchars($g['child_group']) ?>"<?= (isset($_GET['child_group']) && $_GET['child_group'] == $g['child_group']) ? ' selected' : '' ?>><?= htmlspecialchars($g['child_group']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="classroom" class="gh-label">ห้องเรียน</label>
                        <select id="classroom" class="form-select"><option value="">-- ทั้งหมด --</option></select>
                    </div>
                    <div>
                        <label for="search" class="gh-label">ค้นหาชื่อ / รหัส</label>
                        <input type="text" class="form-control" id="search" placeholder="ชื่อ ชื่อเล่น หรือรหัส" autocomplete="off"
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                    <?php endif; ?>
                    <div>
                        <label for="date_from" class="gh-label">ตั้งแต่วันที่</label>
                        <input type="date" class="form-control" id="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="date_to" class="gh-label">ถึงวันที่</label>
                        <input type="date" class="form-control" id="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>
                </div>
                <div class="gh-filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>ค้นหา</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()"><i class="bi bi-x-circle me-1"></i>รีเซ็ต</button>
                </div>
            </form>
        </div>

        <div class="gh-pills" id="ghSummary" style="display:none"></div>
        <div id="resultTable">
            <div class="gh-empty"><i class="bi bi-clock-history"></i><div class="big">เลือกกลุ่มเรียน ห้องเรียน หรือค้นหาชื่อเด็ก</div>ประวัติการบันทึกจะแสดงที่นี่</div>
        </div>
    </div>
</main>

<!-- ===== Modal: ข้อมูลทั้งหมดของเด็ก (ประวัติ + กราฟ) ===== -->
<div class="modal fade" id="childModal" tabindex="-1" aria-labelledby="childModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-graph-up me-2 text-primary"></i><span id="childModalTitle">ข้อมูลการเจริญเติบโต</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="childModalBody"></div>
        </div>
    </div>
</div>

<script src="../../public/assets/js/growth_standard_charts.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/growth_standard_charts.js') ?: time() ?>"></script>

<script>
    const GH_CAN_MANAGE = <?= ($is_admin || $is_teacher) ? 'true' : 'false' ?>;
    const GH_IS_STUDENT = <?= $is_student ? 'true' : 'false' ?>;
    const ghEsc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const ghNum = (v, unit) => (v === null || v === undefined || v === '' ? '<span class="gh-dash">-</span>' : `${ghEsc(parseFloat(v))} <small>${unit}</small>`);
    const ghDate = (d) => new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
    const el = (id) => document.getElementById(id);

    function loadClassrooms() {
        const group = el('child_group').value, sel = el('classroom');
        sel.innerHTML = '<option value="">-- ทั้งหมด --</option>';
        if (!group) return Promise.resolve();
        return fetch(`../include/function/get_classrooms.php?child_group=${encodeURIComponent(group)}`)
            .then(r => r.json())
            .then(data => {
                [...new Set((Array.isArray(data) ? data : []).map(c => c.classroom_name))].forEach(n => {
                    const o = document.createElement('option'); o.value = o.textContent = n; sel.appendChild(o);
                });
            })
            .catch(console.error);
    }

    function resetForm() {
        el('searchForm').reset();
        if (!GH_IS_STUDENT) { el('child_group').value = ''; el('classroom').innerHTML = '<option value="">-- ทั้งหมด --</option>'; el('search').value = ''; }
        el('date_from').value = ''; el('date_to').value = '';
        loadResults();
    }

    // สรุปผลพัฒนาการของแถว: ใช้ค่าที่บันทึกไว้ ถ้าเป็นแถวเก่าให้คำนวณจากผลรายด้านเมื่อติ๊กครบ 5 ด้าน
    function devChip(r) {
        const st = ['gm', 'fm', 'rl', 'el', 'ps'].map(k => r[k + '_status']).filter(Boolean);
        const result = r.dev_result || (st.length === 5 ? (st.some(v => v !== 'pass') ? 'delay' : 'normal') : null);
        if (result === 'normal') return '<span class="gh-chip ok">สมวัย</span>';
        if (result === 'delay') return '<span class="gh-chip bad">สงสัยล่าช้า</span>';
        return st.length ? '<span class="gh-chip muted">ติ๊กไม่ครบ</span>' : '<span class="gh-dash">-</span>';
    }
    const fullNameOf = (r) => `${r.prefix_th || ''}${r.firstname_th || ''} ${r.lastname_th || ''}`.trim();

    // รายชื่อเด็ก: 1 แถวต่อ 1 คน (สรุปครั้งล่าสุด) กด "ดูกราฟ" เพื่อดูข้อมูลทั้งหมดของเด็กคนนั้น
    function loadResults() {
        const table = el('resultTable'), summary = el('ghSummary');
        const q = new URLSearchParams({ date_from: el('date_from').value, date_to: el('date_to').value });
        if (!GH_IS_STUDENT) {
            q.set('child_group', el('child_group').value); q.set('classroom', el('classroom').value); q.set('search', el('search').value.trim());
            if (!q.get('child_group') && !q.get('classroom') && !q.get('search')) {
                summary.style.display = 'none';
                table.innerHTML = '<div class="gh-empty"><i class="bi bi-people"></i><div class="big">เลือกกลุ่มเรียน ห้องเรียน หรือค้นหาชื่อเด็ก</div>รายชื่อเด็กที่มีประวัติการบันทึกจะแสดงที่นี่</div>';
                return;
            }
        }

        table.innerHTML = '<div class="gh-empty"><div class="spinner-border text-primary"></div></div>';
        fetch(`../include/function/get_growth_history_list.php?${q}`)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') throw new Error(res.message || 'โหลดข้อมูลไม่สำเร็จ');
                if (!res.rows.length) {
                    summary.style.display = 'none';
                    table.innerHTML = '<div class="gh-empty"><i class="bi bi-inbox"></i><div class="big">ไม่พบประวัติการบันทึก</div>ลองเปลี่ยนห้อง ช่วงวันที่ หรือคำค้นหา</div>';
                    return;
                }
                // รายการเรียงล่าสุดก่อนอยู่แล้ว: แถวแรกของเด็กแต่ละคนคือครั้งล่าสุด
                const kids = new Map();
                res.rows.forEach(r => {
                    if (!kids.has(r.student_id)) kids.set(r.student_id, { latest: r, count: 0 });
                    kids.get(r.student_id).count++;
                });
                const list = [...kids.values()].sort((a, b) => fullNameOf(a.latest).localeCompare(fullNameOf(b.latest), 'th'));

                summary.innerHTML = `<span class="gh-pill">${list.length} คน</span><span class="gh-pill">${res.rows.length} รายการบันทึก</span>` +
                    (res.truncated ? `<span class="gh-note">แสดง ${res.limit} รายการล่าสุด จำนวนครั้งของบางคนอาจไม่ครบ ลองจำกัดช่วงวันที่</span>` : '');
                summary.style.display = '';

                const body = list.map(({ latest: r, count }, i) => `<tr>
                        <td>${i + 1}</td>
                        <td class="left"><div class="gh-name">${ghEsc(fullNameOf(r))}</div><div class="gh-nick">${r.nickname ? 'ชื่อเล่น ' + ghEsc(r.nickname) + ' · ' : ''}${ghEsc(r.child_group)} · ${ghEsc(r.classroom)}</div></td>
                        <td>${count}</td>
                        <td class="nw">${ghDate(r.record_date)}</td>
                        <td class="nw">${ghNum(r.weight, 'กก.')}</td>
                        <td class="nw">${ghNum(r.height, 'ซม.')}</td>
                        <td class="nw">${ghNum(r.head_circumference, 'ซม.')}</td>
                        <td>${devChip(r)}</td>
                        <td class="nw"><button type="button" class="gh-btn primary" data-act="open" data-sid="${ghEsc(r.student_id)}"><i class="bi bi-graph-up"></i>ดูกราฟ</button></td>
                    </tr>`).join('');

                table.innerHTML = `<div class="gh-tablecard"><div class="gh-table-wrap"><table class="gh-table">
                    <thead><tr><th>#</th><th class="left">ชื่อ-นามสกุล</th><th>บันทึกแล้ว (ครั้ง)</th><th>ครั้งล่าสุด</th><th>น้ำหนักล่าสุด</th><th>ส่วนสูงล่าสุด</th><th>รอบศีรษะล่าสุด</th><th>ผลพัฒนาการล่าสุด</th><th>ข้อมูลทั้งหมด</th></tr></thead>
                    <tbody>${body}</tbody></table></div></div>`;
            })
            .catch(err => {
                summary.style.display = 'none';
                table.innerHTML = `<div class="gh-empty"><i class="bi bi-exclamation-triangle text-danger"></i><div class="big">โหลดข้อมูลไม่สำเร็จ</div>${ghEsc(err.message)}</div>`;
            });
    }

    // ===== Modal ข้อมูลทั้งหมดของเด็ก 1 คน: สรุปล่าสุด + ประวัติทุกครั้ง + กราฟ 4 แบบ =====
    let childCharts = [];
    let currentChildId = null;
    const childModal = () => bootstrap.Modal.getOrCreateInstance(el('childModal'));

    function destroyChildCharts() { childCharts.forEach(c => c.destroy()); childCharts = []; }

    function openChild(studentId) {
        currentChildId = studentId;
        destroyChildCharts();
        el('childModalTitle').textContent = 'ข้อมูลการเจริญเติบโต';
        el('childModalBody').innerHTML = '<div class="gh-empty" style="box-shadow:none"><div class="spinner-border text-primary"></div></div>';
        childModal().show();

        fetch(`../include/function/get_growth_history_list.php?student_id=${encodeURIComponent(studentId)}`)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success' || !res.rows.length) throw new Error(res.message || 'ไม่พบข้อมูลของเด็กคนนี้');
                renderChild(res.rows);
            })
            .catch(err => { el('childModalBody').innerHTML = `<div class="gh-empty" style="box-shadow:none"><div class="big text-danger">โหลดไม่สำเร็จ</div>${ghEsc(err.message)}</div>`; });
    }

    function renderChild(rows) {
        const latest = rows[0];
        const name = fullNameOf(latest);
        const sexText = latest.sex === 'ชาย' || latest.sex === 'M' ? 'ชาย' : 'หญิง';
        el('childModalTitle').textContent = `ข้อมูลการเจริญเติบโต - ${name}`;

        const tiles = `<div class="ghm-tiles">
            <div class="ghm-tile"><span>กลุ่มเรียน / ห้อง</span><b>${ghEsc(latest.child_group)} · ${ghEsc(latest.classroom)}</b></div>
            <div class="ghm-tile"><span>บันทึกแล้ว</span><b>${rows.length} ครั้ง</b></div>
            <div class="ghm-tile"><span>ครั้งล่าสุด</span><b>${ghDate(latest.record_date)}</b></div>
            <div class="ghm-tile"><span>น้ำหนักล่าสุด</span><b>${ghNum(latest.weight, 'กก.')}</b></div>
            <div class="ghm-tile"><span>ส่วนสูงล่าสุด</span><b>${ghNum(latest.height, 'ซม.')}</b></div>
            <div class="ghm-tile"><span>รอบศีรษะล่าสุด</span><b>${ghNum(latest.head_circumference, 'ซม.')}</b></div>
        </div>`;

        const histRows = rows.map(r => `<tr>
                <td class="nw">${ghDate(r.record_date)}</td>
                <td class="nw">${ghEsc(r.age_year)} ปี ${ghEsc(r.age_month)} ด. ${ghEsc(r.age_day)} ว.</td>
                <td class="nw">${ghNum(r.weight, 'กก.')}</td>
                <td class="nw">${ghNum(r.height, 'ซม.')}</td>
                <td class="nw">${ghNum(r.head_circumference, 'ซม.')}</td>
                <td>${devChip(r)}</td>
                <td class="nw">${r.dev_recorded_by ? ghEsc(r.dev_recorded_by) : '<span class="gh-dash">-</span>'}</td>
                ${GH_CAN_MANAGE ? `<td class="nw">
                    <a class="gh-icon warn" href="growth_dev_grid.php?${new URLSearchParams({ child_group: r.child_group, classroom: r.classroom, date: r.record_date })}" title="แก้ไขที่หน้ากรอกผล" aria-label="แก้ไข"><i class="bi bi-pencil"></i></a>
                    <button type="button" class="gh-icon danger" data-act="delete" data-rid="${ghEsc(r.id)}" data-name="${ghEsc(name)}" title="ลบ" aria-label="ลบ"><i class="bi bi-trash"></i></button>
                </td>` : ''}
            </tr>`).join('');

        const history = `<div class="ghm-box mb-3">
            <h6><i class="bi bi-table"></i>ประวัติการบันทึกทุกครั้ง (ล่าสุดอยู่บนสุด)</h6>
            <div class="gh-table-wrap" style="max-height:260px;"><table class="gh-table">
                <thead><tr><th>วันที่บันทึก</th><th>อายุ</th><th>น้ำหนัก</th><th>ส่วนสูง</th><th>รอบศีรษะ</th><th>ผลพัฒนาการ</th><th>ผู้บันทึก</th>${GH_CAN_MANAGE ? '<th>จัดการ</th>' : ''}</tr></thead>
                <tbody>${histRows}</tbody></table></div>
        </div>`;

        const range = (latest.age_year || 0) * 12 + (latest.age_month || 0) < 24 ? '0-2' : '2-5';
        const charts = `<div class="ghm-charts">
            <div class="ghm-chart"><h6>น้ำหนักตามเกณฑ์อายุ (0-5 ปี) เพศ${sexText}</h6><div class="ghm-canvas"><canvas id="weightChart"></canvas></div></div>
            <div class="ghm-chart"><h6>ความยาว/ส่วนสูงตามเกณฑ์อายุ (0-5 ปี) เพศ${sexText}</h6><div class="ghm-canvas"><canvas id="heightChart"></canvas></div></div>
            <div class="ghm-chart"><h6>น้ำหนักตามเกณฑ์ความยาว/ส่วนสูง (${range} ปี) เพศ${sexText}</h6><div class="ghm-canvas"><canvas id="weightHeightChart"></canvas></div></div>
            <div class="ghm-chart"><h6>เส้นรอบวงศีรษะ (แรกเกิด - 5 ปี) เพศ${sexText}</h6><div class="ghm-canvas"><canvas id="headChart"></canvas></div></div>
        </div>`;

        el('childModalBody').innerHTML = tiles + history + charts;

        // กราฟใช้ข้อมูลทุกครั้ง (กราฟเรียงตามเวลาเอง) เฉพาะครั้งที่มีค่าที่กราฟนั้นต้องใช้
        const has = (r, ...ks) => ks.every(k => r[k] !== null && r[k] !== undefined && r[k] !== '' && parseFloat(r[k]) > 0);
        const sets = {
            weight: rows.filter(r => has(r, 'weight')), height: rows.filter(r => has(r, 'height')),
            weight_height: rows.filter(r => has(r, 'weight', 'height')), head: rows.filter(r => has(r, 'head_circumference'))
        };
        destroyChildCharts();
        [['weightChart', 'weight'], ['heightChart', 'height'], ['weightHeightChart', 'weight_height'], ['headChart', 'head']].forEach(([id, type]) => {
            const canvas = el(id);
            if (!canvas) return;
            if (!sets[type].length) { canvas.parentElement.innerHTML = '<div class="ghm-empty">ยังไม่มีข้อมูลสำหรับกราฟนี้</div>'; return; }
            childCharts.push(new Chart(canvas, { type: 'line', data: prepareChartData(type, sets[type], latest.sex), options: getChartOptions(type) }));
        });
    }

    function deleteRecord(recordId, name) {
        Swal.fire({
            title: 'ลบรายการนี้?',
            html: `ลบการบันทึกของ <b>${ghEsc(name)}</b> ออกจากประวัติ ลบแล้วกู้คืนไม่ได้`,
            icon: 'warning', showCancelButton: true, confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#dc2626'
        }).then(result => {
            if (!result.isConfirmed) return;
            const fd = new FormData(); fd.append('record_id', recordId);
            fetch('../include/process/delete_growth_record.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success') throw new Error(res.message || 'ลบไม่สำเร็จ');
                    Swal.fire({ icon: 'success', title: 'ลบเรียบร้อย', timer: 1200, showConfirmButton: false });
                    loadResults();
                    if (currentChildId) openChild(currentChildId);
                })
                .catch(err => Swal.fire('ผิดพลาด', err.message, 'error'));
        });
    }

    document.addEventListener('click', (e) => {
        const open = e.target.closest('#resultTable button[data-act="open"]');
        if (open) { openChild(open.dataset.sid); return; }
        const del = e.target.closest('#childModalBody button[data-act="delete"]');
        if (del) deleteRecord(del.dataset.rid, del.dataset.name);
    });

    document.addEventListener('DOMContentLoaded', async () => {
        el('childModal').addEventListener('hidden.bs.modal', () => { destroyChildCharts(); currentChildId = null; });
        el('searchForm').addEventListener('submit', (e) => { e.preventDefault(); loadResults(); });
        el('date_from').addEventListener('change', loadResults);
        el('date_to').addEventListener('change', loadResults);
        if (!GH_IS_STUDENT) {
            el('child_group').addEventListener('change', async () => { await loadClassrooms(); loadResults(); });
            el('classroom').addEventListener('change', loadResults);
            let t; el('search').addEventListener('input', () => { clearTimeout(t); t = setTimeout(loadResults, 450); });
            const p = new URLSearchParams(location.search);
            if (p.get('child_group')) { await loadClassrooms(); if (p.get('classroom')) el('classroom').value = p.get('classroom'); }
        }
        const p2 = new URLSearchParams(location.search);
        if (GH_IS_STUDENT || p2.get('child_group') || p2.get('search')) loadResults();
    });

    function exportToExcel() {
        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.show();
    }

    function toggleDateFields() {
        const exportType = document.getElementById('exportType').value;
        document.getElementById('dailyField').style.display = 'none';
        document.getElementById('monthlyField').style.display = 'none';
        document.getElementById('rangeFields').style.display = 'none';

        switch(exportType) {
            case 'daily':
                document.getElementById('dailyField').style.display = 'block';
                break;
            case 'monthly':
                document.getElementById('monthlyField').style.display = 'block';
                break;
            case 'range':
                document.getElementById('rangeFields').style.display = 'block';
                break;
        }
    }

    function loadExportClassrooms() {
        const childGroup = document.getElementById('exportChildGroup').value;
        const classroomSelect = document.getElementById('exportClassroom');
        
        classroomSelect.innerHTML = '<option value="">ทั้งหมด</option>';
        
        if (childGroup) {
            fetch(`../include/function/get_classrooms.php?child_group=${childGroup}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(classroom => {
                        const option = document.createElement('option');
                        option.value = classroom.classroom_name;
                        option.textContent = classroom.classroom_name;
                        classroomSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    }

    function confirmExport() {
        const exportType = document.getElementById('exportType').value;
        const childGroup = document.getElementById('exportChildGroup').value;
        const classroom = document.getElementById('exportClassroom').value;
        
        let url = '../include/process/export_growth_history.php?';
        url += `type=${exportType}`;

        switch(exportType) {
            case 'daily':
                const date = document.getElementById('exportDate').value;
                url += `&date=${date}`;
                break;
            case 'monthly':
                const month = document.getElementById('exportMonth').value;
                url += `&month=${month}`;
                break;
            case 'range':
                const startDate = document.getElementById('exportStartDate').value;
                const endDate = document.getElementById('exportEndDate').value;
                if (!startDate || !endDate) {
                    alert('กรุณาระบุวันที่เริ่มต้นและสิ้นสุด');
                    return;
                }
                url += `&start_date=${startDate}&end_date=${endDate}`;
                break;
        }

        if (childGroup) url += `&child_group=${childGroup}`;
        if (classroom) url += `&classroom=${classroom}`;

        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
        window.location.href = url;
    }
</script>

    <!-- เพิ่ม HTML ส่วน Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export ข้อมูลการเจริญเติบโต</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="mb-3">
                            <label class="form-label">รูปแบบการ Export</label>
                            <select class="form-select" id="exportType" onchange="toggleDateFields()">
                                <option value="daily">รายวัน</option>
                                <option value="monthly">รายเดือน</option>
                                <option value="range">ช่วงวันที่</option>
                            </select>
                        </div>
                        
                        <!-- วันที่ -->
                        <div id="dailyField" class="mb-3">
                            <label class="form-label">วันที่</label>
                            <input type="date" class="form-control" id="exportDate" 
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <!-- เดือน -->
                        <div id="monthlyField" class="mb-3" style="display:none">
                            <label class="form-label">เดือน</label>
                            <input type="month" class="form-control" id="exportMonth" 
                                value="<?php echo date('Y-m'); ?>">
                        </div>
                        
                        <!-- ช่วงวันที่ -->
                        <div id="rangeFields" class="mb-3" style="display:none">
                            <label class="form-label">ตั้งแต่วันที่</label>
                            <input type="date" class="form-control mb-2" id="exportStartDate">
                            <label class="form-label">ถึงวันที่</label>
                            <input type="date" class="form-control" id="exportEndDate">
                        </div>

                        <!-- กลุ่มเรียน -->
                        <div class="mb-3">
                            <label class="form-label">กลุ่มเรียน</label>
                            <select class="form-select" id="exportChildGroup" onchange="loadExportClassrooms()">
                                <option value="">ทั้งหมด</option>
                                <?php
                                $groups = get_childgroup();
                                foreach ($groups as $group) {
                                    if (!empty($group['child_group'])) {
                                        echo "<option value='" . $group['child_group'] . "'>" . $group['child_group'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- ห้องเรียน -->
                        <div class="mb-3">
                            <label class="form-label">ห้องเรียน</label>
                            <select class="form-select" id="exportClassroom">
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="confirmExport()">Export</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
