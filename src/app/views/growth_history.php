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
    .gh-filters { display: grid; gap: .75rem 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); align-items: end; }
    @media (max-width: 992px) { .gh-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .gh-filters { grid-template-columns: 1fr; } }
    .gh-filter-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .85rem; }
    .gh-top-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

    .gh-pills { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .75rem; }
    .gh-pill { border-radius: 999px; padding: 3px 14px; font-weight: 700; font-size: .8rem; background: #e2e8f0; color: #475569; }
    .gh-pill.ok { background: #dcfce7; color: #15803d; } .gh-pill.todo { background: #f1f5f9; color: #64748b; }

    .gh-class { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); margin-bottom: 1rem; overflow: hidden; }
    .gh-class-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem 1.1rem; background: linear-gradient(90deg, #1e4db7, #3b6fd4); color: #fff; }
    .gh-class-title { font-weight: 700; font-size: 1.02rem; display: flex; align-items: center; gap: .5rem; }
    .gh-class-count { font-size: .85rem; opacity: .95; }
    .gh-table-wrap { overflow-x: auto; }
    .gh-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .88rem; }
    .gh-table th { background: #f1f5f9; color: #475569; font-size: .76rem; font-weight: 700; padding: 8px 10px; text-align: center; white-space: nowrap; border-bottom: 1px solid #cbd5e1; position: sticky; top: 0; }
    .gh-table td { padding: 8px 10px; text-align: center; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
    .gh-table .left { text-align: left; }
    .gh-table .nw { white-space: nowrap; }
    .gh-table tbody tr:hover td { background: #f8fafc; }
    .gh-table tr.none td { color: #94a3b8; }
    .gh-table tr.has td:first-child { box-shadow: inset 4px 0 0 #22c55e; }
    .gh-table tr.none td:first-child { box-shadow: inset 4px 0 0 #cbd5e1; }
    .gh-name { font-weight: 700; color: #0f2460; line-height: 1.2; }
    .gh-table tr.none .gh-name { color: #64748b; }
    .gh-nick { font-size: .74rem; color: #64748b; }
    .gh-dash { color: #cbd5e1; }
    .gh-table small { color: #94a3b8; }
    .gh-chip { font-size: .74rem; font-weight: 700; padding: 2px 10px; border-radius: 999px; white-space: nowrap; }
    .gh-chip.ok { background: #dcfce7; color: #15803d; } .gh-chip.todo { background: #e5e7eb; color: #64748b; }

    .gh-btn { display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; padding: 4px 12px; border-radius: 999px; border: 1.5px solid transparent; font-size: .78rem; font-weight: 700; cursor: pointer; transition: background-color .15s, color .15s, box-shadow .15s; }
    .gh-btn:active { transform: scale(.96); }
    .gh-btn.info { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; } .gh-btn.info:hover { background: #0e7490; color: #fff; }
    .gh-btn.primary { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; } .gh-btn.primary:hover { background: #1d4ed8; color: #fff; }
    .gh-btn.add { background: #f0fdf4; border-color: #86efac; color: #15803d; } .gh-btn.add:hover { background: #15803d; color: #fff; }
    .gh-icon { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; cursor: pointer; transition: background-color .15s, color .15s; }
    .gh-icon.warn { color: #b45309; } .gh-icon.warn:hover { background: #f59e0b; border-color: #f59e0b; color: #fff; }
    .gh-icon.danger { color: #b91c1c; } .gh-icon.danger:hover { background: #dc2626; border-color: #dc2626; color: #fff; }

    .gh-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, .06); }
    .gh-empty > i { display: block; font-size: 2rem; color: #94a3b8; margin-bottom: .4rem; }
    .gh-empty .big { font-size: 1.05rem; font-weight: 700; color: #334155; margin-bottom: .25rem; }

    /* ===== Modal / popup (SweetAlert + Bootstrap) ===== */
    .swal2-popup { border-radius: 16px; padding: 1.25rem 1.5rem 1.4rem; font-family: inherit; box-shadow: 0 20px 60px rgba(15, 36, 96, .25); }
    .swal2-title { color: #0f2460; font-size: 1.3rem; font-weight: 700; padding: .25rem 2.5rem .75rem 0; text-align: left; border-bottom: 1px solid #e2e8f0; margin-bottom: .75rem; }
    .swal2-icon + .swal2-title, .swal2-icon ~ .swal2-title { text-align: center; border-bottom: 0; padding-right: 0; }
    .swal2-html-container { margin: .25rem 0 0; text-align: left; font-size: .92rem; }
    .swal2-close { color: #64748b; border-radius: 10px; width: 36px; height: 36px; font-size: 1.6rem; transition: background-color .15s, color .15s; }
    .swal2-close:hover { background: #fee2e2; color: #b91c1c; }
    .swal2-actions { gap: .5rem; margin-top: 1.1rem; }
    .swal2-styled.swal2-confirm, .swal2-styled.swal2-cancel, .swal2-styled.swal2-deny { border-radius: 10px; font-weight: 700; padding: .55rem 1.3rem; box-shadow: none; }
    .swal2-html-container .form-label { font-size: .8rem; font-weight: 700; color: #475569; margin-bottom: .2rem; }
    .swal2-html-container .form-control, .swal2-html-container .form-select { border-radius: 8px; font-size: .9rem; }
    .swal2-html-container .card { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: none; }
    .swal2-html-container .card-title { font-size: .85rem; font-weight: 700; color: #0f2460; }
    .swal2-html-container .table { font-size: .85rem; }
    .swal2-html-container .table th { background: #f1f5f9; color: #475569; font-weight: 700; }
    .modal-content { border: 0; border-radius: 16px; box-shadow: 0 20px 60px rgba(15, 36, 96, .25); }
    .modal-header { border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; }
    .modal-title { color: #0f2460; font-weight: 700; }
    .modal-footer { border-top: 1px solid #e2e8f0; }

    /* รายละเอียดการเจริญเติบโต (ปุ่ม "ดูผล") */
    .ghm { text-align: left; }
    .ghm-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .6rem; margin-bottom: .9rem; }
    .ghm-tile { background: #f1f5f9; border-radius: 12px; padding: .6rem .85rem; }
    .ghm-tile span { display: block; font-size: .72rem; font-weight: 700; color: #64748b; }
    .ghm-tile b { font-size: 1rem; color: #0f2460; }
    .ghm-tile small { font-weight: 400; color: #64748b; }
    .ghm-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: .9rem; margin-bottom: .9rem; }
    .ghm-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: .8rem 1rem; }
    .ghm-box h6 { font-size: .9rem; font-weight: 700; color: #0f2460; margin: 0 0 .5rem; display: flex; align-items: center; gap: .4rem; }
    .ghm-row { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; padding: .4rem 0; border-top: 1px solid #f1f5f9; }
    .ghm-row:first-of-type { border-top: 0; }
    .ghm-row-label { font-size: .84rem; color: #334155; display: flex; align-items: center; gap: .4rem; }
    .ghm-row-label i { color: #1e4db7; }
    .ghm-row-val { text-align: right; }
    .ghm-extra { font-size: .74rem; color: #64748b; margin-top: 2px; }
    .ghm-chip { display: inline-block; font-size: .76rem; font-weight: 700; padding: 2px 10px; border-radius: 999px; }
    .ghm-chip.ok { background: #dcfce7; color: #15803d; } .ghm-chip.bad { background: #fee2e2; color: #b91c1c; }
    .ghm-chip.warn { background: #fef3c7; color: #b45309; } .ghm-chip.muted { background: #f1f5f9; color: #94a3b8; }
    .ghm-result { margin-top: .6rem; padding: .55rem .8rem; border-radius: 10px; font-size: .84rem; line-height: 1.6; }
    .ghm-result.ok { background: #f0fdf4; color: #15803d; } .ghm-result.bad { background: #fef2f2; color: #b91c1c; }
    .ghm-charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 1.1rem; }
    .ghm-canvas { position: relative; height: 380px; }
    /* กราฟในป๊อปอัปอื่น (กราฟภาพรวม) ให้การ์ดสูงพอ ไม่บีบกราฟ */
    .swal2-html-container .col-md-6 > .card > .card-body { display: flex; flex-direction: column; height: 400px; padding: .9rem 1rem; }
    .swal2-html-container .col-md-6 > .card > .card-body canvas { flex: 1 1 0; min-height: 0; }
    .swal2-html-container .col-md-6 > .card .card-title { margin-bottom: .5rem; }
    .ghm-chart { border: 1px solid #e2e8f0; border-radius: 12px; padding: .8rem 1rem 1rem; }
    .ghm-chart h6 { font-size: .82rem; font-weight: 700; color: #0f2460; margin: 0 0 .4rem; }
    .ghm-hint { margin-top: .6rem; font-size: .76rem; color: #94a3b8; text-align: center; }
    .ghm-tabs { display: flex; gap: .4rem; border-bottom: 2px solid #e2e8f0; margin-bottom: .9rem; }
    .ghm-tab { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; border: 0; background: none; font-weight: 700; font-size: .9rem; color: #64748b; border-bottom: 3px solid transparent; margin-bottom: -2px; cursor: pointer; transition: color .15s, border-color .15s; }
    .ghm-tab:hover { color: #1e4db7; }
    .ghm-tab.active { color: #1e4db7; border-bottom-color: #1e4db7; }
    .ghm-loading { text-align: center; color: #64748b; padding: 2rem 0; }
</style>

    <main class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="gh-title">ประวัติการบันทึกการเจริญเติบโตของเด็ก</h2>
                <div class="gh-sub">ดูผลการวัดและผลประเมินรายวัน แยกตามห้องเรียน</div>
            </div>
            <div class="gh-top-actions">
                <?php if ($is_admin || $is_teacher): ?>
                <button type="button" class="btn btn-success btn-sm" onclick="openDevGrid()"><i class="bi bi-pencil-square me-1"></i>กรอกผลทั้งห้อง</button>
                <a href="growth_dev_view.php" class="btn btn-primary btn-sm"><i class="bi bi-table me-1"></i>ดูผลรายห้อง</a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportToExcel()"><i class="bi bi-file-excel me-1"></i>Export Excel</button>
            </div>
        </div>

        <!-- ตัวกรอง -->
        <div class="gh-card">
            <form id="searchForm" method="GET">
                <div class="gh-filters">
                    <div>
                        <label for="child_group" class="gh-label">กลุ่มเรียน</label>
                        <select name="child_group" id="child_group" class="form-select" onchange="loadClassrooms()">
                            <option value="">-- เลือกกลุ่มเรียน --</option>
                            <?php
                            $groups = get_childgroup();
                            foreach ($groups as $group) {
                                if (!empty($group['child_group'])) {
                                    $selected = (isset($_GET['child_group']) && $_GET['child_group'] == $group['child_group']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($group['child_group']) . "' $selected>" . htmlspecialchars($group['child_group']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="classroom" class="gh-label">ห้องเรียน</label>
                        <select name="classroom" id="classroom" class="form-select">
                            <option value="">-- เลือกห้องเรียน --</option>
                        </select>
                    </div>
                    <div>
                        <label for="date" class="gh-label">วันที่</label>
                        <input type="date" class="form-control" id="date" name="date"
                            value="<?php echo htmlspecialchars(isset($_GET['date']) ? $_GET['date'] : date('Y-m-d')); ?>">
                    </div>
                    <div>
                        <label for="search" class="gh-label">ค้นหาชื่อ</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="ชื่อ-นามสกุล / ชื่อเล่น / รหัส" autocomplete="off"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </div>
                <div class="gh-filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>ค้นหา</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()"><i class="bi bi-x-circle me-1"></i>รีเซ็ต</button>
                </div>
            </form>
        </div>

        <!-- ผลลัพธ์ -->
        <div class="gh-pills" id="ghSummary" style="display:none"></div>
        <div id="resultTable">
            <div class="gh-empty"><i class="bi bi-funnel"></i><div class="big">เลือกกลุ่มเรียน ห้องเรียน หรือค้นหาชื่อเด็ก</div>ผลการบันทึกจะแสดงที่นี่</div>
        </div>
    </div>
</main>
    <script>
    // เปิดหน้ากรอกคัดกรองพัฒนาการทั้งห้อง พร้อมกลุ่มเรียน/ห้องเรียนที่เลือกไว้
    function openDevGrid() {
        const q = new URLSearchParams({
            child_group: document.getElementById('child_group').value,
            classroom: document.getElementById('classroom').value
        });
        window.location.href = 'growth_dev_grid.php?' + q.toString();
    }

        // โหลดห้องเรียนเมื่อเลือกกลุ่มเรียน
    function loadClassrooms() {
        var childGroup = document.getElementById('child_group').value;

        if (!childGroup) {
            document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
            return;
        }

        fetch(`../include/function/get_classrooms.php?child_group=${childGroup}`)
            .then(response => response.json())
            .then(data => {
                var classroomSelect = document.getElementById('classroom');
                classroomSelect.innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';

                data.forEach(function(classroom) {
                    var option = document.createElement('option');
                    option.value = classroom.classroom_name;
                    option.textContent = classroom.classroom_name;
                    classroomSelect.appendChild(option);
                });

                <?php if (isset($_GET['classroom'])): ?>
                    classroomSelect.value = '<?php echo json_encode($_GET['classroom']); ?>';
                <?php endif; ?>
            })
            .catch(error => console.error('Error:', error));
    }

    // รีเซ็ตฟอร์ม
    // รีเซ็ตตัวกรอง (คงรายการกลุ่มเรียนไว้ ล้างเฉพาะค่าที่เลือก)
    function resetForm() {
        document.getElementById('searchForm').reset();
        document.getElementById('child_group').value = '';
        document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
        document.getElementById('date').value = new Date().toLocaleDateString('sv-SE');
        document.getElementById('search').value = '';
        loadResults();
    }

    const ghEsc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const ghNum = (v, unit) => (v === null || v === undefined || v === '' ? '<span class="gh-dash">-</span>' : `${ghEsc(parseFloat(v))} <small>${unit}</small>`);
    const GH_CAN_MANAGE = <?= ($is_admin || $is_teacher) ? 'true' : 'false' ?>;

    // โหลดผลลัพธ์
    function loadResults() {
        const formData = new FormData(document.getElementById('searchForm'));
        const searchValue = formData.get('search');
        const table = document.getElementById('resultTable');
        const summary = document.getElementById('ghSummary');

        if (!formData.get('child_group') && !formData.get('classroom') && !searchValue) {
            summary.style.display = 'none';
            table.innerHTML = '<div class="gh-empty"><i class="bi bi-funnel"></i><div class="big">เลือกกลุ่มเรียน ห้องเรียน หรือค้นหาชื่อเด็ก</div>ผลการบันทึกจะแสดงที่นี่</div>';
            return;
        }

        table.innerHTML = '<div class="gh-empty"><div class="spinner-border text-primary"></div></div>';
        fetch('../include/function/get_growth_history.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data)) throw new Error(data.message || 'โหลดข้อมูลไม่สำเร็จ');
                if (data.length === 0) {
                    summary.style.display = 'none';
                    table.innerHTML = '<div class="gh-empty"><i class="bi bi-inbox"></i><div class="big">ไม่พบข้อมูล</div>ลองเปลี่ยนกลุ่ม ห้อง วันที่ หรือคำค้นหา</div>';
                    return;
                }

                const recorded = data.filter(s => s.id != null).length;
                summary.innerHTML =
                    `<span class="gh-pill">ทั้งหมด ${data.length} คน</span>` +
                    `<span class="gh-pill ok">บันทึกแล้ว ${recorded}</span>` +
                    `<span class="gh-pill todo">ยังไม่มีการบันทึก ${data.length - recorded}</span>`;
                summary.style.display = '';

                let html = '';
                Object.values(groupStudentsByClass(data)).forEach(group => {
                    const done = group.students.filter(s => s.id != null).length;
                    html += `
                    <section class="gh-class">
                        <div class="gh-class-head">
                            <div class="gh-class-title"><i class="bi bi-people-fill"></i>${ghEsc(group.child_group)} · ห้อง ${ghEsc(group.classroom)}</div>
                            <div class="gh-class-count">บันทึกแล้ว <strong>${done}</strong> / ${group.students.length} คน</div>
                        </div>
                        <div class="gh-table-wrap">
                        <table class="gh-table">
                            <thead><tr>
                                <th>#</th><th>รหัส</th><th class="left">ชื่อ-นามสกุล</th><th>สถานะ</th><th>เวลาที่บันทึก</th><th>อายุ</th>
                                <th>น้ำหนัก</th><th>ส่วนสูง</th><th>รอบศีรษะ</th><th>ผลการประเมิน</th>${GH_CAN_MANAGE ? '<th>จัดการ</th>' : ''}
                            </tr></thead>
                            <tbody>`;

                    group.students.forEach((s, i) => {
                        const has = s.id != null;
                        const fullName = `${s.prefix_th || ''}${s.firstname_th || ''} ${s.lastname_th || ''}`.trim();
                        const time = has ? new Date(s.created_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.' : '-';
                        const age = has ? `${s.age_year} ปี ${s.age_month} ด. ${s.age_day} ว.` : '-';
                        const d = `data-sid="${ghEsc(s.studentid)}" data-rid="${has ? ghEsc(s.id) : ''}" data-name="${ghEsc(fullName)}"`;
                        html += `
                            <tr class="${has ? 'has' : 'none'}">
                                <td>${i + 1}</td>
                                <td class="nw">${ghEsc(s.studentid)}</td>
                                <td class="left"><div class="gh-name">${ghEsc(fullName)}</div>${s.nickname ? `<div class="gh-nick">ชื่อเล่น ${ghEsc(s.nickname)}</div>` : ''}</td>
                                <td><span class="gh-chip ${has ? 'ok' : 'todo'}">${has ? 'บันทึกแล้ว' : 'ยังไม่มีการบันทึก'}</span></td>
                                <td class="nw">${time}</td>
                                <td class="nw">${age}</td>
                                <td class="nw">${has ? ghNum(s.weight, 'กก.') : '<span class="gh-dash">-</span>'}</td>
                                <td class="nw">${has ? ghNum(s.height, 'ซม.') : '<span class="gh-dash">-</span>'}</td>
                                <td class="nw">${has ? ghNum(s.head_circumference, 'ซม.') : '<span class="gh-dash">-</span>'}</td>
                                <td class="nw">
                                    ${has ? `
                                        <button type="button" class="gh-btn info" data-act="detail" ${d}><i class="bi bi-eye"></i>ดูผล</button>
                                    ` : (GH_CAN_MANAGE ? `<button type="button" class="gh-btn add" data-act="add" ${d}><i class="bi bi-plus-circle"></i>เพิ่มข้อมูล</button>` : '<span class="gh-dash">-</span>')}
                                </td>
                                ${GH_CAN_MANAGE ? `<td class="nw">${has ? `
                                    <button type="button" class="gh-icon warn" data-act="edit" title="แก้ไข" aria-label="แก้ไข" ${d}><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="gh-icon danger" data-act="delete" title="ลบ" aria-label="ลบ" ${d}><i class="bi bi-trash"></i></button>
                                ` : '<span class="gh-dash">-</span>'}</td>` : ''}
                            </tr>`;
                    });

                    html += `</tbody></table></div></section>`;
                });
                table.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                summary.style.display = 'none';
                table.innerHTML = `<div class="gh-empty"><i class="bi bi-exclamation-triangle text-danger"></i><div class="big">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>${ghEsc(error.message)}</div>`;
            });
    }

    // ปุ่มในตาราง: ใช้ data-attribute แทนการฝังชื่อใน onclick (ชื่อที่มีอัญประกาศจะไม่ทำให้ปุ่มพัง)
    document.addEventListener('click', (e) => {
        const b = e.target.closest('#resultTable button[data-act]');
        if (!b) return;
        const { act, sid, rid, name } = b.dataset;
        if (act === 'detail') showGrowthDetails(sid, rid);
        else if (act === 'overview') showGrowthOverview(sid);
        else if (act === 'add') showAddGrowthModal(sid, name);
        else if (act === 'edit') editRecord(rid);
        else if (act === 'delete') deleteRecord(rid);
    });

    // ตัวกรอง: เปลี่ยนแล้วโหลดผลใหม่เอง กด Enter/ค้นหาก็โหลดโดยไม่ต้องรีเฟรชหน้า
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('searchForm');
        form.addEventListener('submit', (e) => { e.preventDefault(); loadResults(); });
        document.getElementById('child_group').addEventListener('change', () => loadResults());
        document.getElementById('classroom').addEventListener('change', () => loadResults());
        document.getElementById('date').addEventListener('change', () => loadResults());
        let t;
        document.getElementById('search').addEventListener('input', () => { clearTimeout(t); t = setTimeout(loadResults, 450); });
    });

    // เพิ่มฟังก์ชันจัดกลุ่มข้อมูล
    function groupStudentsByClass(students) {
        return students.reduce((groups, student) => {
            const key = `${student.child_group}-${student.classroom}`;
            if (!groups[key]) {
                groups[key] = {
                    child_group: student.child_group,
                    classroom: student.classroom,
                    students: []
                };
            }
            groups[key].students.push(student);
            return groups;
        }, {});
    }

    // เพิ่มฟังก์ชันเมื่อโหลดหน้าครั้งแรก
    document.addEventListener('DOMContentLoaded', () => {
        // ถ้ามี URL parameters ให้กรอกข้อมูลในฟอร์มและค้นหา
        const urlParams = new URLSearchParams(window.location.search);
        
        // ถ้ามีการเลือกกลุ่มเรียน ให้โหลดห้องเรียนก่อน
        if (urlParams.get('child_group')) {
            // กรอกข้อมูลกลุ่มเรียน
            document.getElementById('child_group').value = urlParams.get('child_group');
            
            // โหลดห้องเรียน
            loadClassrooms();
            
            // รอให้ห้องเรียนโหลดเสร็จก่อนเลือกห้องเรียนและโหลดผลลัพธ์
            setTimeout(() => {
                if (urlParams.get('classroom')) {
                    document.getElementById('classroom').value = urlParams.get('classroom');
                }
                loadResults();
            }, 500);
        } else {
            // ไม่ได้เลือกกลุ่ม แต่มีวันที่/คำค้นหาใน URL ก็โหลดผลเลย (ถ้าไม่มี จะคงข้อความแนะนำเริ่มต้นไว้)
            if (urlParams.get('search')) { document.getElementById('search').value = urlParams.get('search'); loadResults(); }
        }

        // กรอกข้อมูลอื่นๆ จาก URL parameters
        for (const [key, value] of urlParams) {
            if (key !== 'child_group' && key !== 'classroom') {
                const element = document.getElementById(key);
                if (element) {
                    element.value = value;
                }
            }
        }
    });

    // แก้ไขฟังก์ชัน showAllGrowthCharts
    function showAllGrowthCharts(studentId) {
        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const record = data.current_record;
                    const allRecords = data.all_records;
                    const studentName = record.student_name;
                    const sex = record.sex === 'ชาย' ? 'M' : 'F'; // แปลงค่าเพศ

                    // สร้าง HTML สำหรับแสดงกราฟทั้งหมด
                    const chartsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ ${record.age_range || '0-2'} ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // แสดง Modal พร้อมกราฟทั้งหมด
                    Swal.fire({
                        title: `กราฟการเจริญเติบโต - ${studentName}`,
                        html: chartsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        didRender: () => {
                            const charts = [
                                { id: 'weightChart', type: 'weight' },
                                { id: 'heightChart', type: 'height' },
                                { id: 'weightHeightChart', type: 'weight_height' },
                                { id: 'headChart', type: 'head' }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                if (chartElement) {
                                    const newChart = new Chart(chartElement, {
                                        type: 'line',
                                        data: prepareChartData(chart.type, allRecords, sex),
                                        options: getChartOptions(chart.type)
                                    });

                                    chartElement.onclick = () => {
                                        expandChart(chart.type, allRecords, studentName, sex);
                                    };
                                    chartElement.style.cursor = 'pointer';
                                }
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }

   // ฟังก์ชันแสดง Modal สำหรับเพิ่มข้อมูล
function showAddGrowthModal(studentId, studentName) {
    // ตรวจสอบการมีอยู่ขององค์ประกอบก่อนดึงค่า
    const childGroupElement = document.getElementById('child_group');
    const selectedDateElement = document.querySelector('input[name="date"]');

    if (!childGroupElement || !selectedDateElement) {
        console.error('ไม่พบองค์ประกอบที่ต้องการใน DOM');
        return;
    }

    const childGroup = childGroupElement.value;
    const selectedDate = selectedDateElement.value;

    // ดึงข้อมูลเพศของนักเรียนจาก API
    fetch(`../include/function/get_student_info.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.student) {
                const sex = data.student.sex;
                const childGroup = data.student.child_group;
                
                const modalHtml = `
                    <form id="growthForm" onsubmit="saveGrowthData(event)">
                        <input type="hidden" name="student_id" value="${studentId}">
                        <input type="hidden" name="age_range" id="age_range_hidden">
                        <input type="hidden" name="record_date" value="${selectedDate}">
                        <input type="hidden" name="child_group" value="${childGroup}">
                        <input type="hidden" name="sex" value="${sex}">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="alert alert-info">
                                    วันที่บันทึก: ${new Date(selectedDate).toLocaleDateString('th-TH', {
                                                year: 'numeric',
                                                month: 'long',
                                day: 'numeric'
                            })}
                                </div>
                                </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ช่วงอายุ</label>
                                <select class="form-select" id="age_range" onchange="updateAgeInputLimits(); document.getElementById('age_range_hidden').value = this.value;" required>
                                    <option value="">เลือกช่วงอายุ</option>
                                    <option value="0-2">0-2 ปี</option>
                                    <option value="2-5">2-5 ปี</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">อายุ</label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="age_year" min="0" max="12" required>
                                            <span class="input-group-text">ปี</span>
                                    </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="age_month" min="0" max="11" required>
                                            <span class="input-group-text">เดือน</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="age_day" min="0" max="31" required>
                                            <span class="input-group-text">วัน</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">น้ำหนัก (กิโลกรัม)</label>
                                <input type="number" class="form-control" name="weight" step="0.1" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ส่วนสูง (เซนติเมตร)</label>
                                <input type="number" class="form-control" name="height" step="0.1" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">เส้นรอบวงศีรษะ (เซนติเมตร)</label>
                                <input type="number" class="form-control" name="head_circumference" step="0.1" min="0" required>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>การประเมินพัฒนาการ</h5>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">การเคลื่อนไหว (GM)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="gm_status" id="gm_pass" value="pass" required>
                                    <label class="form-check-label" for="gm_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="gm_status" id="gm_delay" value="delay">
                                    <label class="form-check-label" for="gm_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="gm_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">กล้ามเนื้อมัดเล็กและสติปัญญา (FM)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="fm_status" id="fm_pass" value="pass" required>
                                    <label class="form-check-label" for="fm_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="fm_status" id="fm_delay" value="delay">
                                    <label class="form-check-label" for="fm_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="fm_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">ความเข้าใจภาษา (RL)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="rl_status" id="rl_pass" value="pass" required>
                                    <label class="form-check-label" for="rl_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="rl_status" id="rl_delay" value="delay">
                                    <label class="form-check-label" for="rl_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="rl_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">การใช้ภาษา (EL)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="el_status" id="el_pass" value="pass" required>
                                    <label class="form-check-label" for="el_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="el_status" id="el_delay" value="delay">
                                    <label class="form-check-label" for="el_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="el_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">การช่วยเหลือตัวเองและสังคม (PS)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="ps_status" id="ps_pass" value="pass" required>
                                    <label class="form-check-label" for="ps_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="ps_status" id="ps_delay" value="delay">
                                    <label class="form-check-label" for="ps_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="ps_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>
                        </div>
                    </form>
                `;

                Swal.fire({
                    title: `เพิ่มข้อมูลการเจริญเติบโต - ${studentName}`,
                    html: modalHtml,
                    width: '800px',
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    preConfirm: () => {
                        const form = document.getElementById('growthForm');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return false;
                        }

                        // สร้าง FormData จากฟอร์ม
                        const formData = new FormData(form);
                        const data = {};

                        // เก็บข้อมูลพื้นฐาน
                        formData.forEach((value, key) => {
                            data[key] = value;
                        });

                        // จัดการข้อมูลการประเมิน 5 ด้าน
                        ['gm', 'fm', 'rl', 'el', 'ps'].forEach(field => {
                            // ดึงค่า radio button ที่ถูกเลือก
                            const selectedRadio = document.querySelector(`input[name="${field}_status"]:checked`);
                            if (selectedRadio) {
                                data[`${field}_status`] = selectedRadio.value;
                                
                                // ถ้าเป็น delay ให้ดึงค่า issue ด้วย
                                if (selectedRadio.value === 'delay') {
                                    const issueInput = document.querySelector(`input[name="${field}_issue"]`);
                                    if (issueInput && issueInput.value) {
                                        data[`${field}_issue`] = issueInput.value;
                                    }
                                }
                            }
                        });

                        console.log('Prepared data:', data); // Debug log
                        return data;
                    }
                }).then((result) => {
                    // แก้ไขส่วนของการส่งข้อมูล
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        
                        // เพิ่มข้อมูลทั้งหมดจาก result.value
                        Object.entries(result.value).forEach(([key, value]) => {
                            if (value !== null && value !== undefined && value !== '') {
                                formData.append(key, value);
                            }
                        });
                        
                        // เพิ่มการส่งค่า age_range
                        formData.append('age_range', document.getElementById('age_range').value);

                        // Debug: แสดงข้อมูลที่จะส่ง
                        console.log('Sending data:');
                        for (let pair of formData.entries()) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }

                        fetch('../include/process/save_growth_data.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success')
                                .then(() => {
                                    loadResults();
                                });
                            } else {
                                throw new Error(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error saving data:', error);
                            Swal.fire('ผิดพลาด', error.message, 'error');
                        });

                    }
                });
            } else {
                throw new Error(data.message || 'ไม่สามารถดึงข้อมูลนักเรียนได้');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลนักเรียนได้', 'error');
        });
}

    // เพิ่มฟังก์ชันใหม่สำหรับจำกัดช่วงอายุ
    function updateAgeInputLimits() {
        const ageRange = document.getElementById('age_range').value;
        const form = document.getElementById('growthForm');
        
        const yearInput = form.querySelector('[name="age_year"]');
        const monthInput = form.querySelector('[name="age_month"]');
        const dayInput = form.querySelector('[name="age_day"]');
        
        // รีเซ็ตค่าเริ่มต้น
        yearInput.value = '';
        monthInput.value = '';
        dayInput.value = '';
        
        switch(ageRange) {
            case '0-2':
                yearInput.max = 2;
                yearInput.min = 0;
                break;
            case '0-5':
                yearInput.max = 5;
                yearInput.min = 0;
                break;
            case '2-5':
                yearInput.max = 5;
                yearInput.min = 2;
                break;
            case '5':
                yearInput.max = 5;
                yearInput.min = 0;
                break;
            default:
                yearInput.max = 6;
                yearInput.min = 0;
        }
        
        // เพิ่ม Event Listener สำหรับตรวจสอบค่า
        yearInput.addEventListener('change', function() {
            const year = parseInt(this.value);
            if (year === yearInput.max) {
                monthInput.value = '0';
                dayInput.value = '0';
                monthInput.disabled = true;
                dayInput.disabled = true;
            } else if (year === yearInput.min && yearInput.min > 0) {
                monthInput.value = '0';
                dayInput.value = '0';
                monthInput.disabled = true;
                dayInput.disabled = true;
            } else {
                monthInput.disabled = false;
                dayInput.disabled = false;
            }
        });
    }

    // เพิ่มฟังก์ชันแก้ไขข้อมูล
    function editRecord(recordId) {
        // ดึงข้อมูลเดิมก่อนแก้ไข
        fetch(`../include/function/get_growth_record.php?record_id=${recordId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const record = data.record;
                    const modalHtml = `
                        <form id="growthForm" onsubmit="saveGrowthData(event)">
                            <input type="hidden" name="record_id" value="${recordId}">
                            <input type="hidden" name="student_id" value="${record.student_id}">
                            <input type="hidden" name="child_group" value="${record.child_group}">
                            <input type="hidden" name="sex" value="${record.sex}">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ช่วงอายุ</label>
                                    <select class="form-select" id="age_range" name="age_range" onchange="updateAgeInputLimits();" required>
                                        <option value="">เลือกช่วงอายุ</option>
                                        <option value="0-2" ${record.age_range === '0-2' ? 'selected' : ''}>0-2 ปี</option>
                                        <option value="2-5" ${record.age_range === '2-5' ? 'selected' : ''}>2-5 ปี</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">อายุ</label>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="age_year" min="0" max="12" value="${record.age_year}" required>
                                                <span class="input-group-text">ปี</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="age_month" min="0" max="11" value="${record.age_month}" required>
                                                <span class="input-group-text">เดือน</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="age_day" min="0" max="31" value="${record.age_day}" required>
                                                <span class="input-group-text">วัน</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">น้ำหนัก (กิโลกรัม)</label>
                                    <input type="number" class="form-control" name="weight" step="0.1" min="0" value="${record.weight}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ส่วนสูง (เซนติเมตร)</label>
                                    <input type="number" class="form-control" name="height" step="0.1" min="0" value="${record.height}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">เส้นรอบวงศีรษะ (เซนติเมตร)</label>
                                    <input type="number" class="form-control" name="head_circumference" step="0.1" min="0" value="${record.head_circumference}" required>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5>การประเมินพัฒนาการ</h5>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">การเคลื่อนไหว (GM)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="gm_status" id="gm_pass" value="pass" ${record.gm_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="gm_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="gm_status" id="gm_delay" value="delay" ${record.gm_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="gm_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="gm_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.gm_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">กล้ามเนื้อมัดเล็กและสติปัญญา (FM)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="fm_status" id="fm_pass" value="pass" ${record.fm_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="fm_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="fm_status" id="fm_delay" value="delay" ${record.fm_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="fm_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="fm_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.fm_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">ความเข้าใจภาษา (RL)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="rl_status" id="rl_pass" value="pass" ${record.rl_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="rl_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="rl_status" id="rl_delay" value="delay" ${record.rl_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="rl_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="rl_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.rl_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">การใช้ภาษา (EL)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="el_status" id="el_pass" value="pass" ${record.el_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="el_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="el_status" id="el_delay" value="delay" ${record.el_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="el_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="el_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.el_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">การช่วยเหลือตัวเองและสังคม (PS)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="ps_status" id="ps_pass" value="pass" ${record.ps_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="ps_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="ps_status" id="ps_delay" value="delay" ${record.ps_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="ps_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="ps_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.ps_issue || ''}">
                                    </div>
                                </div>
                            </div>
                        </form>
                    `;

                    Swal.fire({
                    title: `แก้ไขข้อมูลการเจริญเติบโต - ${record.student_name}`,
                    html: modalHtml,
                    width: '800px',
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    didRender: () => {
                    try {
                        console.log('Setting initial values for record:', record);

                        // ตั้งค่าช่วงอายุ
                        setTimeout(() => {
                            const ageRangeSelect = document.getElementById('age_range');
                            if (ageRangeSelect) {
                                ageRangeSelect.value = record.age_range;
                                updateAgeInputLimits();
                            }

                            // ตั้งค่าอายุ (ปี, เดือน, วัน)
                            document.querySelector('input[name="age_year"]').value = record.age_year;
                            document.querySelector('input[name="age_month"]').value = record.age_month;
                            document.querySelector('input[name="age_day"]').value = record.age_day;

                            // ตั้งค่าสถานะการประเมินพัฒนาการ
                            const developmentFields = [
                                { name: 'gm', status: record.gm_status, issue: record.gm_issue },
                                { name: 'fm', status: record.fm_status, issue: record.fm_issue },
                                { name: 'rl', status: record.rl_status, issue: record.rl_issue },
                                { name: 'el', status: record.el_status, issue: record.el_issue },
                                { name: 'ps', status: record.ps_status, issue: record.ps_issue }
                            ];

                            developmentFields.forEach(field => {
                                if (field.status) {
                                    const radioBtn = document.querySelector(`input[name="${field.name}_status"][value="${field.status}"]`);
                                    if (radioBtn) {
                                        radioBtn.checked = true;
                                    }

                                    if (field.issue) {
                                        const issueInput = document.querySelector(`input[name="${field.name}_issue"]`);
                                        if (issueInput) {
                                            issueInput.value = field.issue;
                                        }
                                    }
                                }
                            });

                        }, 100); // ใช้ setTimeout เพื่อให้รอ UI โหลดเสร็จก่อน
                    } catch (error) {
                        console.error('Error setting initial values:', error);
                        }
                    },
                    preConfirm: () => {
                        const form = document.getElementById('growthForm');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return false;
                        }
                        const formData = new FormData(form);
                        const data = Object.fromEntries(formData);

                        // เพิ่มการจัดการค่า radio buttons
                        ['gm', 'fm', 'rl', 'el', 'ps'].forEach(field => {
                            const status = formData.get(`${field}_status`);
                            if (status) {
                                data[`${field}_status`] = status;
                            }
                        });

                        return data;
                    }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            
                            // เก็บข้อมูลที่ส่งมาจากฟอร์ม
                            Object.entries(result.value).forEach(([key, value]) => {
                                if (value !== null && value !== undefined && value !== '') {
                                    formData.append(key, value);
                                }
                            });

                            // ตรวจสอบและเพิ่มค่า record_id ถ้ายังไม่มี
                            if (!formData.has('record_id') && record.id) {
                                formData.append('record_id', record.id);
                            }

                            // ตรวจสอบและเพิ่มค่า age_range ถ้ายังไม่มี
                            if (!formData.has('age_range')) {
                                formData.append('age_range', document.getElementById('age_range').value);
                            }

                            // ตรวจสอบและเพิ่มค่าสถานะการประเมิน 5 ด้าน
                            ['gm', 'fm', 'rl', 'el', 'ps'].forEach(field => {
                                if (!formData.has(`${field}_status`)) {
                                    const selectedRadio = document.querySelector(`input[name="${field}_status"]:checked`);
                                    if (selectedRadio) {
                                        formData.append(`${field}_status`, selectedRadio.value);
                                        
                                        if (selectedRadio.value === 'delay') {
                                            const issueInput = document.querySelector(`input[name="${field}_issue"]`);
                                            if (issueInput && issueInput.value && !formData.has(`${field}_issue`)) {
                                                formData.append(`${field}_issue`, issueInput.value);
                                            }
                                        }
                                    }
                                }
                            });

                            // Debug: แสดงข้อมูลที่จะส่ง
                            console.log('Sending data:');
                            for (let pair of formData.entries()) {
                                console.log(pair[0] + ': ' + pair[1]);
                            }

                            fetch('../include/process/save_growth_data.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success')
                                    .then(() => {
                                        loadResults();
                                    });
                                } else {
                                    throw new Error(data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error saving data:', error);
                                Swal.fire('ผิดพลาด', error.message, 'error');
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }

    // เพิ่มฟังก์ชันลบข้อมูล
    function deleteRecord(recordId) {
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: 'คุณต้องการลบข้อมูลนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('record_id', recordId);

                fetch('../include/process/delete_growth_record.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('สำเร็จ', 'ลบข้อมูลเรียบร้อยแล้ว', 'success')
                        .then(() => {
                            loadResults(); // โหลดข้อมูลใหม่
                        });
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire('ผิดพลาด', error.message, 'error');
                });
            }
        });
    }

    // เพิ่มฟังก์ชันแสดงกราฟภาพรวม
    function showGrowthOverview(studentId) {
        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const allRecords = data.all_records;
                    if (allRecords.length === 0) {
                        Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลการเจริญเติบโต', 'info');
                        return;
                    }

                    const detailsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย</h6>
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย</h6>
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ 0-2 ปี เพศชาย</h6>
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศชาย</h6>
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>วันที่บันทึก</th>
                                                <th>อายุ</th>
                                                <th>น้ำหนัก</th>
                                                <th>ส่วนสูง</th>
                                                <th>เส้นรอบศีรษะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${allRecords.map(record => `
                                                <tr>
                                                    <td>${new Date(record.created_at).toLocaleDateString('th-TH')}</td>
                                                    <td>${record.age_year}ปี ${record.age_month}เดือน ${record.age_day}วัน</td>
                                                    <td>${record.weight} กก.</td>
                                                    <td>${record.height} ซม.</td>
                                                    <td>${record.head_circumference} ซม.</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: `กราฟภาพรวมการเจริญเติบโต - ${allRecords[0].student_name}`,
                        html: detailsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        didRender: () => {
                            const charts = [
                                {
                                    id: 'weightChart',
                                    type: 'weight'
                                },
                                {
                                    id: 'heightChart',
                                    type: 'height'
                                },
                                {
                                    id: 'weightHeightChart',
                                    type: 'weight_height'
                                },
                                {
                                    id: 'headChart',
                                    type: 'head'
                                }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                const newChart = new Chart(chartElement, {
                                    type: 'line',
                                    data: prepareChartData(chart.type, allRecords, allRecords[0].sex),
                                    options: getChartOptions(chart.type)
                                });

                                // เพิ่ม event listener สำหรับการคลิกที่กราฟ
                                chartElement.onclick = () => {
                                    expandChart(chart.type, allRecords, allRecords[0].student_name);
                                };
                                
                                // เพิ่ม style cursor เป็น pointer เมื่อ hover
                                chartElement.style.cursor = 'pointer';
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }

    // เพิ่มฟังก์ชันสำหรับขยายกราฟ
    function expandChart(chartType, records, studentName, sex) {
        let title = '';
        const sexText = sex === 'M' ? 'ชาย' : 'หญิง';
        
        switch(chartType) {
            case 'weight':
                title = `กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sexText}`;
                break;
            case 'height':
                title = `กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sexText}`;
                break;
            case 'weight_height':
                title = `กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ 0-2 ปี เพศ${sexText}`;
                break;
            case 'head':
                title = `กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sexText}`;
                break;
        }

        const modalHtml = `
            <div class="card">
                <div class="card-body">
                    <div style="position:relative;height:70vh;"><canvas id="expandedChart"></canvas></div>
                </div>
            </div>
        `;

        Swal.fire({
            title: `${title} - ${studentName}`,
            html: modalHtml,
            width: '90%',
            height: '90%',
            showCloseButton: true,
            showConfirmButton: false,
            didRender: () => {
                new Chart(document.getElementById('expandedChart'), {
                    type: 'line',
                    data: prepareChartData(chartType, records, sex),
                    options: getChartOptions(chartType)
                });
            }
            });
    }

    // เพิ่มฟังก์ชันสำหรับเตรียมข้อมูลกราฟ
    function prepareChartData(type, records) {
        const sortedRecords = [...records].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        const labels = sortedRecords.map(r => new Date(r.created_at).toLocaleDateString('th-TH'));
        
        switch(type) {
            case 'weight':
                // ข้อมูลเส้นอ้างอิงสำหรับน้ำหนักตามเกณฑ์อายุ
                const weightReferenceLines = {
                    plus2SD: [
                        {x: 0, y: 4.4}, {x: 6, y: 8.8}, {x: 12, y: 11.0},
                        {x: 18, y: 12.6}, {x: 24, y: 14.2}, {x: 30, y: 15.8},
                        {x: 36, y: 17.4}, {x: 42, y: 19.0}, {x: 48, y: 20.6},
                        {x: 54, y: 22.4}, {x: 60, y: 24.2}
                    ],
                    plus1_5SD: [
                        {x: 0, y: 4.2}, {x: 6, y: 8.4}, {x: 12, y: 10.4},
                        {x: 18, y: 11.8}, {x: 24, y: 13.4}, {x: 30, y: 14.8},
                        {x: 36, y: 16.2}, {x: 42, y: 17.6}, {x: 48, y: 19.0},
                        {x: 54, y: 20.6}, {x: 60, y: 22.2}
                    ],
                    median: [
                        {x: 0, y: 3.4}, {x: 6, y: 7.4}, {x: 12, y: 9.2},
                        {x: 18, y: 10.4}, {x: 24, y: 11.8}, {x: 30, y: 13.2},
                        {x: 36, y: 14.4}, {x: 42, y: 15.6}, {x: 48, y: 16.8},
                        {x: 54, y: 18.0}, {x: 60, y: 19.2}
                    ],
                    minus1_5SD: [
                        {x: 0, y: 2.8}, {x: 6, y: 6.4}, {x: 12, y: 8.0},
                        {x: 18, y: 9.2}, {x: 24, y: 10.4}, {x: 30, y: 11.6},
                        {x: 36, y: 12.8}, {x: 42, y: 13.8}, {x: 48, y: 14.8},
                        {x: 54, y: 15.8}, {x: 60, y: 16.8}
                    ],
                    minus2SD: [
                        {x: 0, y: 2.4}, {x: 6, y: 5.8}, {x: 12, y: 7.2},
                        {x: 18, y: 8.4}, {x: 24, y: 9.4}, {x: 30, y: 10.4},
                        {x: 36, y: 11.4}, {x: 42, y: 12.4}, {x: 48, y: 13.4},
                        {x: 54, y: 14.4}, {x: 60, y: 15.4}
                    ]
                };

                // แปลงข้อมูลน้ำหนักของเด็กให้อยู่ในรูปแบบที่ต้องการ
                const weightData = sortedRecords.map(r => ({
                    x: r.age_year * 12 + r.age_month, // แปลงอายุเป็นเดือน
                    y: r.weight
                }));

                return {
                    labels: labels,
                    datasets: [
                        {
                            label: '+2 SD (น้ำหนักมาก)',
                            data: weightReferenceLines.plus2SD,
                            borderColor: 'rgba(255, 0, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 0, 0, 0.1)'
                        },
                        {
                            label: '+1.5 SD (ค่อนข้างมาก)',
                            data: weightReferenceLines.plus1_5SD,
                            borderColor: 'rgba(7, 88, 44, 0.82)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(7, 88, 44, 0.1)'
                        },
                        {
                            label: 'Median (น้ำหนักตามเกณฑ์)',
                            data: weightReferenceLines.median,
                            borderColor: 'rgba(0, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(0, 255, 0, 0.1)'
                        },
                        {
                            label: '-1.5 SD (ค่อนข้างน้อย)',
                            data: weightReferenceLines.minus1_5SD,
                            borderColor: 'rgba(65, 172, 88, 0.45)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(65, 172, 88, 0.1)'
                        },
                        {
                            label: '-2 SD (น้ำหนักน้อย)',
                            data: weightReferenceLines.minus2SD,
                            borderColor: 'rgba(255, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: true,
                            backgroundColor: 'rgba(255, 255, 0, 0.1)'
                        },
                        {
                            label: 'น้ำหนักของเด็ก',
                            data: weightData,
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: false
                        }
                    ]
                };
            case 'height':
                // ข้อมูลเส้นอ้างอิงสำหรับส่วนสูงตามเกณฑ์อายุ
                const heightReferenceLines = {
                    plus2SD: [
                        {x: 0, y: 52}, {x: 6, y: 70}, {x: 12, y: 80},
                        {x: 18, y: 86}, {x: 24, y: 92}, {x: 30, y: 97},
                        {x: 36, y: 102}, {x: 42, y: 107}, {x: 48, y: 112},
                        {x: 54, y: 117}, {x: 60, y: 122}
                    ],
                    plus1_5SD: [
                        {x: 0, y: 51}, {x: 6, y: 68}, {x: 12, y: 78},
                        {x: 18, y: 84}, {x: 24, y: 90}, {x: 30, y: 95},
                        {x: 36, y: 100}, {x: 42, y: 105}, {x: 48, y: 110},
                        {x: 54, y: 115}, {x: 60, y: 120}
                    ],
                    median: [
                        {x: 0, y: 50}, {x: 6, y: 67}, {x: 12, y: 76},
                        {x: 18, y: 82}, {x: 24, y: 88}, {x: 30, y: 93},
                        {x: 36, y: 98}, {x: 42, y: 103}, {x: 48, y: 108},
                        {x: 54, y: 113}, {x: 60, y: 118}
                    ],
                    minus1_5SD: [
                        {x: 0, y: 48}, {x: 6, y: 65}, {x: 12, y: 74},
                        {x: 18, y: 80}, {x: 24, y: 86}, {x: 30, y: 91},
                        {x: 36, y: 96}, {x: 42, y: 101}, {x: 48, y: 106},
                        {x: 54, y: 111}, {x: 60, y: 116}
                    ],
                    minus2SD: [
                        {x: 0, y: 47}, {x: 6, y: 64}, {x: 12, y: 72},
                        {x: 18, y: 78}, {x: 24, y: 84}, {x: 30, y: 89},
                        {x: 36, y: 94}, {x: 42, y: 99}, {x: 48, y: 104},
                        {x: 54, y: 109}, {x: 60, y: 114}
                    ]
                };

                // แปลงข้อมูลส่วนสูงของเด็กให้อยู่ในรูปแบบที่ต้องการ
                const heightData = sortedRecords.map(r => ({
                    x: r.age_year * 12 + r.age_month, // แปลงอายุเป็นเดือน
                    y: r.height
                }));

                return {
                    labels: labels,
                    datasets: [
                        {
                            label: '+2 SD (สูง)',
                            data: heightReferenceLines.plus2SD,
                            borderColor: 'rgba(255, 0, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 0, 0, 0.1)'
                        },
                        {
                            label: '+1.5 SD (ค่อนข้างสูง)',
                            data: heightReferenceLines.plus1_5SD,
                            borderColor: 'rgba(7, 88, 44, 0.82)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(7, 88, 44, 0.1)'
                        },
                        {
                            label: 'Median (ส่วนสูงตามเกณฑ์)',
                            data: heightReferenceLines.median,
                            borderColor: 'rgba(0, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(0, 255, 0, 0.1)'
                        },
                        {
                            label: '-1.5 SD (ค่อนข้างเตี้ย)',
                            data: heightReferenceLines.minus1_5SD,
                            borderColor: 'rgba(65, 172, 88, 0.45)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(65, 172, 88, 0.1)'
                        },
                        {
                            label: '-2 SD (เตี้ย)',
                            data: heightReferenceLines.minus2SD,
                            borderColor: 'rgba(255, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: true,
                            backgroundColor: 'rgba(255, 255, 0, 0.1)'
                        },
                        {
                            label: 'ส่วนสูงของเด็ก',
                            data: heightData,
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: false
                        }
                    ]
                };
            case 'weight_height':
                // ข้อมูลเส้นอ้างอิงสำหรับน้ำหนักตามเกณฑ์ส่วนสูง
                const referenceLines = {
                    plus3SD: [
                            {x: 45, y: 3}, {x: 55, y: 6}, {x: 65, y: 9},
                            {x: 75, y: 12}, {x: 85, y: 15}, {x: 95, y: 18},
                            {x: 105, y: 21}, {x: 110, y: 24}
                        ],
                    plus2SD: [
                            {x: 45, y: 2.8}, {x: 55, y: 5.5}, {x: 65, y: 8.2},
                            {x: 75, y: 11}, {x: 85, y: 14}, {x: 95, y: 17},
                            {x: 105, y: 20}, {x: 110, y: 22}
                        ],
                    plus1_5SD: [  // เพิ่มเส้น +1.5 SD
                            {x: 45, y: 2.7}, {x: 55, y: 5.3}, {x: 65, y: 7.9},
                            {x: 75, y: 10.5}, {x: 85, y: 13.3}, {x: 95, y: 16},
                            {x: 105, y: 19}, {x: 110, y: 20.5}
                        ],
                    median: [
                            {x: 45, y: 2.5}, {x: 55, y: 5}, {x: 65, y: 7.5},
                            {x: 75, y: 10}, {x: 85, y: 12.5}, {x: 95, y: 15},
                            {x: 105, y: 17.5}, {x: 110, y: 19}
                        ],
                    minus1_5SD: [  // เพิ่มเส้น -1.5 SD
                            {x: 45, y: 2.3}, {x: 55, y: 4.7}, {x: 65, y: 7.1},
                            {x: 75, y: 9.5}, {x: 85, y: 11.7}, {x: 95, y: 14},
                            {x: 105, y: 16}, {x: 110, y: 17.5}
                        ],
                    minus2SD: [
                            {x: 45, y: 2.2}, {x: 55, y: 4.5}, {x: 65, y: 6.8},
                            {x: 75, y: 9}, {x: 85, y: 11}, {x: 95, y: 13},
                            {x: 105, y: 15}, {x: 110, y: 16}
                        ]
                };

                return {
                    labels: labels,
                    datasets: [
                        // เส้นข้อมูลอ้างอิง
                        {
                            label: '+3 SD (อ้วน)',
                            data: referenceLines.plus3SD,
                            borderColor: 'rgba(255, 0, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 0, 0, 0.1)'
                        },
                        {
                            label: '+2 SD (เริ่มอ้วน)',
                            data: referenceLines.plus2SD,
                            borderColor: 'rgba(255, 165, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 165, 0, 0.1)'
                        },
                        {
                            label: '+1.5 SD (ท้วม)',  // เพิ่มเส้น +1.5 SD
                            data: referenceLines.plus1_5SD,
                            borderColor: 'rgba(7, 88, 44, 0.82)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 200, 0, 0.1)'
                        },
                        {
                            label: 'Median (สมส่วน)',
                            data: referenceLines.median,
                            borderColor: 'rgba(0, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(0, 255, 0, 0.1)'
                        },
                        {
                            label: '-1.5 SD (ค่อนข้างผอม)',  // เพิ่มเส้น -1.5 SD
                            data: referenceLines.minus1_5SD,
                            borderColor: 'rgba(65, 172, 88, 0.45)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 255, 100, 0.1)'
                        },
                        {
                            label: '-2 SD (ผอม)',
                            data: referenceLines.minus2SD,
                            borderColor: 'rgba(255, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: true,
                            backgroundColor: 'rgba(255, 255, 0, 0.1)'
                        },
                        // ข้อมูลของเด็ก
                        {
                            label: 'น้ำหนักตามส่วนสูง',
                            data: sortedRecords.map(r => ({
                                x: r.height,
                                y: r.weight
                            })),
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: false
                        }
                    ]
                };
            case 'head':
                // ข้อมูลเส้นอ้างอิงสำหรับเส้นรอบศีรษะตามเกณฑ์อายุ
                const headReferenceLines = {
                    p97th: [ // เปอร์เซ็นไทล์ที่ 97
                        {x: 0, y: 36.6}, {x: 3, y: 41.5}, {x: 6, y: 44.0},
                        {x: 9, y: 45.8}, {x: 12, y: 47.0}, {x: 18, y: 48.4},
                        {x: 24, y: 49.2}, {x: 30, y: 49.8}, {x: 36, y: 50.2},
                        {x: 42, y: 50.4}, {x: 48, y: 50.6}, {x: 54, y: 50.8},
                        {x: 60, y: 51.0}
                    ],
                    p85th: [ // เปอร์เซ็นไทล์ที่ 85
                        {x: 0, y: 35.8}, {x: 3, y: 40.7}, {x: 6, y: 43.2},
                        {x: 9, y: 45.0}, {x: 12, y: 46.2}, {x: 18, y: 47.6},
                        {x: 24, y: 48.4}, {x: 30, y: 49.0}, {x: 36, y: 49.4},
                        {x: 42, y: 49.6}, {x: 48, y: 49.8}, {x: 54, y: 50.0},
                        {x: 60, y: 50.2}
                    ],
                    p50th: [ // เปอร์เซ็นไทล์ที่ 50
                        {x: 0, y: 35.0}, {x: 3, y: 40.0}, {x: 6, y: 42.5},
                        {x: 9, y: 44.3}, {x: 12, y: 45.5}, {x: 18, y: 46.9},
                        {x: 24, y: 47.7}, {x: 30, y: 48.3}, {x: 36, y: 48.7},
                        {x: 42, y: 48.9}, {x: 48, y: 49.1}, {x: 54, y: 49.3},
                        {x: 60, y: 49.5}
                    ],
                    p15th: [ // เปอร์เซ็นไทล์ที่ 15
                        {x: 0, y: 34.2}, {x: 3, y: 39.2}, {x: 6, y: 41.7},
                        {x: 9, y: 43.5}, {x: 12, y: 44.7}, {x: 18, y: 46.1},
                        {x: 24, y: 46.9}, {x: 30, y: 47.5}, {x: 36, y: 47.9},
                        {x: 42, y: 48.1}, {x: 48, y: 48.3}, {x: 54, y: 48.5},
                        {x: 60, y: 48.7}
                    ],
                    p3rd: [ // เปอร์เซ็นไทล์ที่ 3
                        {x: 0, y: 33.4}, {x: 3, y: 38.4}, {x: 6, y: 40.9},
                        {x: 9, y: 42.7}, {x: 12, y: 43.9}, {x: 18, y: 45.3},
                        {x: 24, y: 46.1}, {x: 30, y: 46.7}, {x: 36, y: 47.1},
                        {x: 42, y: 47.3}, {x: 48, y: 47.5}, {x: 54, y: 47.7},
                        {x: 60, y: 47.9}
                    ]
                };

                // แปลงข้อมูลเส้นรอบศีรษะของเด็กให้อยู่ในรูปแบบที่ต้องการ
                const headData = sortedRecords.map(r => ({
                    x: r.age_year * 12 + r.age_month, // แปลงอายุเป็นเดือน
                    y: r.head_circumference
                }));

                return {
                    datasets: [
                        {
                            label: 'มากกว่าเปอร์เซ็นไทล์ที่ 97',
                            data: headReferenceLines.p97th,
                            borderColor: 'rgba(255, 0, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 0, 0, 0.1)'
                        },
                        {
                            label: 'เปอร์เซ็นไทล์ที่ 85-97',
                            data: headReferenceLines.p85th,
                            borderColor: 'rgba(255, 165, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 165, 0, 0.1)'
                        },
                        {
                            label: 'เปอร์เซ็นไทล์ที่ 50-85',
                            data: headReferenceLines.p50th,
                            borderColor: 'rgba(0, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(0, 255, 0, 0.1)'
                        },
                        {
                            label: 'เปอร์เซ็นไทล์ที่ 15-50',
                            data: headReferenceLines.p15th,
                            borderColor: 'rgba(255, 255, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: '+1',
                            backgroundColor: 'rgba(255, 255, 0, 0.1)'
                        },
                        {
                            label: 'เปอร์เซ็นไทล์ที่ 3-15',
                            data: headReferenceLines.p3rd,
                            borderColor: 'rgba(255, 200, 0, 0.5)',
                            borderDash: [5, 5],
                            fill: true,
                            backgroundColor: 'rgba(255, 200, 0, 0.1)'
                        },
                        {
                            label: 'เส้นรอบวงศีรษะของเด็ก',
                            data: headData,
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: false
                        }
                    ]
                };
        }
    }

    // เพิ่มฟังก์ชันสำหรับตั้งค่ากราฟ
    function getChartOptions(type) {
        const options = {
            responsive: true,
            maintainAspectRatio: false,   // ให้กราฟสูงตามกรอบที่กำหนด ไม่บีบตามสัดส่วนความกว้าง
            layout: { padding: { top: 4, right: 8 } },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 14, padding: 10, font: { size: 11 } }
                },
                title: {
                    display: true,
                    text: getChartTitle(type),
                    font: { size: 13 },
                    padding: { bottom: 6 }
                }
            }
        };

        if (type === 'weight') {
            options.scales = {
                x: {
                    type: 'linear',
                    position: 'bottom',
                    title: {
                        display: true,
                        text: 'อายุ (เดือน)'
                    },
                    min: 0,
                    max: 60
                },
                y: {
                    title: {
                        display: true,
                        text: 'น้ำหนัก (กก.)'
                    },
                    min: 0,
                    max: 25
                }
            };
        }

        if (type === 'weight_height') {
                options.scales = {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: {
                            display: true,
                            text: 'ส่วนสูง (ซม.)'
                        },
                        min: 45,
                        max: 110
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'น้ำหนัก (กก.)'
                        },
                    min: 0,
                        max: 25
                    }
                };
        }

        if (type === 'height') {
            options.scales = {
                x: {
                    type: 'linear',
                    position: 'bottom',
                    title: {
                        display: true,
                        text: 'อายุ (เดือน)'
                    },
                    min: 0,
                    max: 60
                },
                y: {
                    title: {
                        display: true,
                        text: 'ส่วนสูง (ซม.)'
                    },
                    min: 45,
                    max: 125
                }
            };
        }

        if (type === 'head') {
            options.scales = {
                x: {
                    type: 'linear',
                    position: 'bottom',
                    title: {
                        display: true,
                        text: 'อายุ (เดือน)'
                    },
                    min: 0,
                    max: 60
                },
                y: {
                    title: {
                        display: true,
                        text: 'เส้นรอบวงศีรษะ (ซม.)'
                    },
                    min: 32,
                    max: 54
                }
            };
        }

        return options;
    }

    // เพิ่มฟังก์ชันสำหรับชื่อกราฟ
    function getChartTitle(type) {
        switch(type) {
            case 'weight':
                return 'น้ำหนักตามเกณฑ์อายุ';
            case 'height':
                return 'ส่วนสูงตามเกณฑ์อายุ';
            case 'weight_height':
                return 'น้ำหนักตามเกณฑ์ส่วนสูง';
            case 'head':
                return 'เส้นรอบวงศีรษะ';
            default:
                return '';
        }
    }

    function getDataPoints(type, records) {
        switch(type) {
            case 'weight':
            case 'height':
            case 'head':
                return records.map(r => ({
                    x: r.age_year * 12 + r.age_month,
                    y: r[type === 'weight' ? 'weight' : 
                       type === 'height' ? 'height' : 'head_circumference']
                }));
            case 'weight_height':
                return records.map(r => ({
                    x: r.height,
                    y: r.weight
                }));
        }
    }

    // ฟังก์ชันแสดงรายละเอียดการเจริญเติบโต
    function showGrowthDetails(studentId, recordId) {
        console.log('Showing growth details for student:', studentId, 'record:', recordId);

        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}&record_id=${recordId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Data received:', data);
                
                if (!data || !data.current_record) {
                    throw new Error('ไม่พบข้อมูล');
                }

                const record = data.current_record;
                const allRecords = data.all_records || [];
                
                // ตรวจสอบและแปลงค่าเพศให้ถูกต้อง
                const sex = record.sex === 'M' ? 'ชาย' : 'หญิง';
                console.log('Sex:', sex); // Debug log

                // ตรวจสอบว่ามีข้อมูลที่จำเป็นครบถ้วน
                if (!record.student_name || !record.growth_status) {
                    throw new Error('ข้อมูลไม่ครบถ้วน');
                }

                // สร้าง HTML สำหรับ modal
                const ghmEsc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const ghmVal = (v, unit) => (v === null || v === undefined || v === '' ? '-' : `${ghmEsc(parseFloat(v))}<small> ${unit}</small>`);
                const ghmStatusChip = (text) => {
                    if (!text || text === 'ไม่สามารถประเมินได้') return `<span class="ghm-chip muted">${text ? ghmEsc(text) : '-'}</span>`;
                    const good = /ตามเกณฑ์|สมส่วน|ปกติ|เปอร์เซ็นไทล์ที่ (15|50)/.test(text);
                    return `<span class="ghm-chip ${good ? 'ok' : 'warn'}">${ghmEsc(text)}</span>`;
                };
                const ghmDev = [
                    ['gm', 'ด้านการเคลื่อนไหว (GM)', 'bi-person-walking'],
                    ['fm', 'ด้านกล้ามเนื้อมัดเล็กและสติปัญญา (FM)', 'bi-hand-index'],
                    ['rl', 'ด้านการเข้าใจภาษา (RL)', 'bi-ear'],
                    ['el', 'ด้านการใช้ภาษา (EL)', 'bi-chat-dots'],
                    ['ps', 'ด้านการช่วยเหลือตัวเองและสังคม (PS)', 'bi-people']
                ].map(([k, label, icon]) => {
                    const st = record[k + '_status'];
                    const pill = st === 'pass' ? '<span class="ghm-chip ok">ผ่าน</span>'
                        : st ? '<span class="ghm-chip bad">ไม่ผ่าน</span>' : '<span class="ghm-chip muted">-</span>';
                    const extra = [record[k + '_issue'] ? `ข้อที่ ${ghmEsc(record[k + '_issue'])}` : '', record[k + '_note'] ? ghmEsc(record[k + '_note']) : ''].filter(Boolean).join(' · ');
                    return `<div class="ghm-row"><div class="ghm-row-label"><i class="bi ${icon}"></i>${label}</div><div class="ghm-row-val">${pill}${extra ? `<div class="ghm-extra">${extra}</div>` : ''}</div></div>`;
                }).join('');
                const ghmAllDev = ['gm', 'fm', 'rl', 'el', 'ps'].every(k => record[k + '_status']);
                const ghmFail = ['gm', 'fm', 'rl', 'el', 'ps'].some(k => record[k + '_status'] && record[k + '_status'] !== 'pass');
                const ghmResult = record.dev_result || (ghmAllDev ? (ghmFail ? 'delay' : 'normal') : null);
                const ghmDevResult = ghmResult
                    ? `<div class="ghm-result ${ghmResult === 'delay' ? 'bad' : 'ok'}"><strong>ผลการคัดกรอง:</strong> ${ghmResult === 'delay' ? 'สงสัยล่าช้ากระตุ้นพัฒนาการภายใน 14-30 วัน' : 'สมวัย (ผ่านครบ 5 ด้าน)'}` +
                      (ghmResult === 'delay' && record.recheck_date ? `<br><strong>ประเมินซ้ำภายใน 1 เดือน:</strong> ภายในวันที่ ${new Date(record.recheck_date).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })}` : '') + '</div>'
                    : '';

                const detailsHtml = `
                    <div class="ghm">
                        <div class="ghm-tabs">
                            <button type="button" class="ghm-tab active" data-tab="now"><i class="bi bi-clipboard2-pulse"></i>ผลครั้งนี้</button>
                            <button type="button" class="ghm-tab" data-tab="trend"><i class="bi bi-graph-up"></i>กราฟและประวัติ</button>
                        </div>
                        <div class="ghm-pane" data-pane="now">
                        <div class="ghm-tiles">
                            <div class="ghm-tile"><span>วันที่บันทึก</span><b>${record.created_at ? new Date(record.created_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : '-'}</b></div>
                            <div class="ghm-tile"><span>อายุ</span><b>${record.age_year || 0} ปี ${record.age_month || 0} เดือน ${record.age_day || 0} วัน</b></div>
                            <div class="ghm-tile"><span>น้ำหนัก</span><b>${ghmVal(record.weight, 'กก.')}</b></div>
                            <div class="ghm-tile"><span>ส่วนสูง</span><b>${ghmVal(record.height, 'ซม.')}</b></div>
                            <div class="ghm-tile"><span>เส้นรอบศีรษะ</span><b>${ghmVal(record.head_circumference, 'ซม.')}</b></div>
                        </div>

                        <div class="ghm-cols">
                            <section class="ghm-box">
                                <h6><i class="bi bi-clipboard-check"></i>ผลการประเมินการเจริญเติบโต</h6>
                                <div class="ghm-row"><div class="ghm-row-label">น้ำหนักตามเกณฑ์อายุ</div><div class="ghm-row-val">${ghmStatusChip(record.growth_status.weight)}</div></div>
                                <div class="ghm-row"><div class="ghm-row-label">ส่วนสูงตามเกณฑ์อายุ</div><div class="ghm-row-val">${ghmStatusChip(record.growth_status.height_age)}</div></div>
                                <div class="ghm-row"><div class="ghm-row-label">น้ำหนักตามเกณฑ์ส่วนสูง</div><div class="ghm-row-val">${ghmStatusChip(record.growth_status.weight_height)}</div></div>
                                <div class="ghm-row"><div class="ghm-row-label">เส้นรอบศีรษะ</div><div class="ghm-row-val">${ghmStatusChip(record.growth_status.head)}</div></div>
                            </section>
                            <section class="ghm-box">
                                <h6><i class="bi bi-puzzle"></i>ผลการประเมินพัฒนาการ</h6>
                                ${ghmDev}
                                ${ghmDevResult}
                            </section>
                        </div>

                        </div>
                        <div class="ghm-pane" data-pane="trend" style="display:none">
                            <div id="ghmTrend" class="ghm-loading"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดกราฟและประวัติ...</div>
                        </div>
                    </div>
                `;

                // แสดง Modal
                    Swal.fire({
                    title: `ข้อมูลการเจริญเติบโต - ${record.student_name}`,
                    html: detailsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                    allowOutsideClick: true,
                    didOpen: () => {
                        const popup = Swal.getHtmlContainer();
                        let trendLoaded = false;

                        // สลับแท็บ: "ผลครั้งนี้" แสดงทันที ส่วน "กราฟและประวัติ" โหลดข้อมูลทุกครั้งแล้ววาดกราฟตอนเปิดครั้งแรก
                        popup.querySelectorAll('.ghm-tab').forEach(tab => tab.addEventListener('click', () => {
                            popup.querySelectorAll('.ghm-tab').forEach(t => t.classList.toggle('active', t === tab));
                            popup.querySelectorAll('.ghm-pane').forEach(p => { p.style.display = p.dataset.pane === tab.dataset.tab ? '' : 'none'; });
                            if (tab.dataset.tab === 'trend' && !trendLoaded) {
                                trendLoaded = true;
                                loadTrend();
                            }
                        }));

                        function loadTrend() {
                            const box = document.getElementById('ghmTrend');
                            fetch(`../include/function/get_student_growth_records.php?student_id=${encodeURIComponent(studentId)}`)
                                .then(r => r.json())
                                .then(res => {
                                    if (res.status !== 'success' || !res.all_records || !res.all_records.length) throw new Error('ไม่พบข้อมูลการเจริญเติบโต');
                                    const everyRecord = res.all_records;
                                    const rows = [...everyRecord].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                                    box.className = '';
                                    box.innerHTML = `
                                        <div class="ghm-box mb-3">
                                            <h6><i class="bi bi-table"></i>ประวัติการบันทึกทั้งหมด (ล่าสุดอยู่บนสุด)</h6>
                                            <div class="table-responsive" style="max-height:240px;overflow:auto;">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead><tr><th>วันที่บันทึก</th><th>อายุ</th><th>น้ำหนัก</th><th>ส่วนสูง</th><th>เส้นรอบศีรษะ</th></tr></thead>
                                                    <tbody>${rows.map(x => `
                                                        <tr${String(x.id) === String(record.id) ? ' class="table-primary"' : ''}>
                                                            <td>${new Date(x.created_at).toLocaleDateString('th-TH')}</td>
                                                            <td>${x.age_year} ปี ${x.age_month} เดือน ${x.age_day} วัน</td>
                                                            <td>${ghmVal(x.weight, 'กก.')}</td>
                                                            <td>${ghmVal(x.height, 'ซม.')}</td>
                                                            <td>${ghmVal(x.head_circumference, 'ซม.')}</td>
                                                        </tr>`).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="ghm-hint" style="text-align:left;">แถวสีฟ้าคือครั้งที่กำลังดูอยู่</div>
                                        </div>
                                        <div class="ghm-charts">
                                            <div class="ghm-chart"><h6>น้ำหนักตามเกณฑ์อายุ (0-5 ปี) เพศ${sex}</h6><div class="ghm-canvas"><canvas id="weightChart"></canvas></div></div>
                                            <div class="ghm-chart"><h6>ความยาว/ส่วนสูงตามเกณฑ์อายุ (0-5 ปี) เพศ${sex}</h6><div class="ghm-canvas"><canvas id="heightChart"></canvas></div></div>
                                            <div class="ghm-chart"><h6>น้ำหนักตามเกณฑ์ความยาว/ส่วนสูง (${ghmEsc(record.age_range || '0-2')} ปี) เพศ${sex}</h6><div class="ghm-canvas"><canvas id="weightHeightChart"></canvas></div></div>
                                            <div class="ghm-chart"><h6>เส้นรอบวงศีรษะ (แรกเกิด - 5 ปี) เพศ${sex}</h6><div class="ghm-canvas"><canvas id="headChart"></canvas></div></div>
                                        </div>
                                        <div class="ghm-hint"><i class="bi bi-zoom-in"></i> กดที่กราฟเพื่อขยายดูเต็มจอ</div>`;

                                    [['weightChart', 'weight'], ['heightChart', 'height'], ['weightHeightChart', 'weight_height'], ['headChart', 'head']].forEach(([id, type]) => {
                                        const el = document.getElementById(id);
                                        if (!el) return;
                                        new Chart(el, { type: 'line', data: prepareChartData(type, everyRecord, record.sex), options: getChartOptions(type) });
                                        el.onclick = () => expandChart(type, everyRecord, record.student_name, record.sex);
                                        el.style.cursor = 'pointer';
                                    });
                                })
                                .catch(err => {
                                    box.className = 'ghm-loading';
                                    box.innerHTML = `<span class="text-danger">${ghmEsc(err.message || 'โหลดไม่สำเร็จ')}</span>`;
                                    trendLoaded = false;
                                });
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
        Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message || 'ไม่สามารถดึงข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            });
    }


     // เพิ่มฟังก์ชันแสดงกราฟทั้งหมด
     function showAllGrowthCharts(studentId) {
        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const allRecords = data.all_records;
                    const studentName = data.current_record.student_name;

                    // สร้าง HTML สำหรับแสดงกราฟทั้งหมด
                    const chartsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // แสดง Modal พร้อมกราฟทั้งหมด
                    Swal.fire({
                        title: `กราฟการเจริญเติบโต - ${studentName}`,
                        html: chartsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        didRender: () => {
                            const charts = [
                                {
                                    id: 'weightChart',
                                    type: 'weight'
                                },
                                {
                                    id: 'heightChart',
                                    type: 'height'
                                },
                                {
                                    id: 'weightHeightChart',
                                    type: 'weight_height'
                                },
                                {
                                    id: 'headChart',
                                    type: 'head'
                                }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                const newChart = new Chart(chartElement, {
                                    type: 'line',
                                    data: prepareChartData(chart.type, allRecords),
                                    options: getChartOptions(chart.type)
                                });

                                // เพิ่ม event listener สำหรับการคลิกที่กราฟ
                                chartElement.onclick = () => {
                                    expandChart(chart.type, allRecords, allRecords[0].student_name);
                                };
                                
                                // เพิ่ม style cursor เป็น pointer เมื่อ hover
                                chartElement.style.cursor = 'pointer';
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }


    // เพิ่มฟังก์ชันสำหรับขยายกราฟ
    function expandChart(chartType, records, studentName) {
        const modalHtml = `
            <div class="card">
                <div class="card-body">
                    <div style="position:relative;height:70vh;"><canvas id="expandedChart"></canvas></div>
                </div>
            </div>
        `;

        let title = '';
        switch(chartType) {
            case 'weight':
                title = 'กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย';
                break;
            case 'height':
                title = 'กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย';
                break;
            case 'weight_height':
                title = 'กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ 0-2 ปี เพศชาย';
                break;
            case 'head':
                title = 'กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศชาย';
                break;
        }

        Swal.fire({
            title: `${title} - ${studentName}`,
            html: modalHtml,
            width: '90%',
            height: '90%',
            showCloseButton: true,
            showConfirmButton: false,
            didRender: () => {
                new Chart(document.getElementById('expandedChart'), {
                    type: 'line',
                    data: prepareChartData(chartType, records),
                    options: {
                        ...getChartOptions(chartType),
                        maintainAspectRatio: false,
                        responsive: true,
                        interaction: {
                            mode: 'nearest',
                            intersect: false,
                            axis: 'x'
                        },
                        plugins: {
                            zoom: {
                                zoom: {
                                    wheel: {
                                        enabled: true,
                                    },
                                    pinch: {
                                        enabled: true
                                    },
                                    mode: 'xy'
                                },
                                pan: {
                                    enabled: true,
                                    mode: 'xy'
                                }
                            }
                        }
                    }
                });
            }
        });
    }
    
    // ฟังก์ชันสำหรับอัปเดตหัวข้อกราฟ
    function updateChartTitle(chartElementId, type, gender, childGroup) {
        const chartTitle = getChartTitle(type, gender, childGroup);
        const chartElement = document.getElementById(chartElementId);

        if (chartElement) {
            const titleElement = chartElement.querySelector('.chart-title');
            if (titleElement) {
                titleElement.textContent = chartTitle;
            } else {
                console.error('ไม่พบองค์ประกอบหัวข้อกราฟ');
            }
        } else {
            console.error('ไม่พบองค์ประกอบกราฟ');
        }
    }

    // ตัวอย่างการเรียกใช้ฟังก์ชัน
    updateChartTitle('headChart', 'head', 'หญิง', 'กลาง');

    function exportGrowthHistory() {
        // แสดง Modal
        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.show();
    }

    // เพิ่ม event listener สำหรับการเปลี่ยนประเภทการ export
    document.getElementById('exportType').addEventListener('change', function() {
        // ซ่อนทุกส่วนก่อน
        document.querySelectorAll('.export-option').forEach(el => el.style.display = 'none');
        
        // แสดงส่วนที่เลือก
        switch(this.value) {
            case 'daily':
                document.getElementById('dailyExport').style.display = 'block';
                break;
            case 'monthly':
                document.getElementById('monthlyExport').style.display = 'block';
                break;
            case 'range':
                document.getElementById('rangeExport').style.display = 'block';
                break;
        }
    });

    function confirmExport() {
        const exportType = document.getElementById('exportType').value;
        const childGroup = document.getElementById('child_group').value;
        const classroom = document.getElementById('classroom').value;
        
        let url = '../include/process/export_growth_history.php?';
        url += `type=${exportType}`;

        // เพิ่มพารามิเตอร์ตามประเภทการ export
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

        // เพิ่มพารามิเตอร์กลุ่มเรียนและห้องเรียน
        if (childGroup) url += `&child_group=${childGroup}`;
        if (classroom) url += `&classroom=${classroom}`;

        // ปิด Modal
        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();

        // ไปยังหน้า export
        window.location.href = url;
    }

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
    
    <!-- เพิ่มในส่วน head -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js" integrity="sha384-4mFQWIqZrcfYZkFPEgIoT//zTEU64gEoH2tGV72Koyhoa9Fxz43YgroFUGj4/RAx" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js" integrity="sha384-Cs3dgUx6+jDxxuqHvVH8Onpyj2LF1gKZurLDlhqzuJmUqVYMJ0THTWpxK5Z086Zm" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-zoom/1.2.1/chartjs-plugin-zoom.min.js" integrity="sha384-4ujnoFIaCz8xsHAu9NwdZDeFVVV0SHFY9KLc+MJIWK7KMSVimFAnmdhc9fXWhMXt" crossorigin="anonymous"></script>

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

    <style>
    .export-option {
        padding: 10px 0;
    }
    </style>
</body>
</html> 
