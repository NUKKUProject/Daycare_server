<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';

$currentAcademicYear = isset($_GET['academic_year']) ? htmlspecialchars($_GET['academic_year']) : null;
$currentTab = isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'all';
$allowedTabs = ['all', 'medium', 'big', 'prep'];
if (!in_array($currentTab, $allowedTabs, true)) {
    $currentTab = 'all';
}

$academicYears = getAcademicYears();

// ดึงข้อมูลเด็กทั้งหมดในปีการศึกษาที่เลือก เพื่อส่งให้ JavaScript
$allChildrenForJS = [];
if ($currentAcademicYear) {
    $rawData = getChildrenByGroupAndYear('all', $currentAcademicYear);
    foreach ($rawData as $groupData) {
        foreach ($groupData['classrooms'] as $classroomData) {
            foreach ($classroomData['children'] as $child) {
                $allChildrenForJS[] = [
                    'id'     => $child['studentid'],
                    'prefix' => $child['prefix_th'],
                    'first'  => $child['firstname_th'],
                    'last'   => $child['lastname_th'],
                    'nick'   => $child['nickname'],
                    'group'  => $child['child_group'],
                    'room'   => $child['classroom'],
                    'gender' => isset($child['gender']) ? $child['gender'] : 'male',
                    'img'    => isset($child['profile_image']) ? $child['profile_image'] : '',
                ];
            }
        }
    }
}

require_once __DIR__ . '/../../include/function/pages_referen.php';
// ตรวจสอบว่ามีสถานะถูกส่งมาหรือไม่
$status = isset($_GET['status']) ? $_GET['status'] : null;
$message = isset($_GET['message']) ? urldecode($_GET['message']) : null;
// รับค่าห้องเรียนที่เลือก (ถ้ามี)
$selectedClassroom = $_GET['classroom'] ?? null;
?>

<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>

<link href="../../../public/assets/css/children_style.css" rel="stylesheet">
<link href="../../../public/assets/css/children_ui.css" rel="stylesheet">

<style>

</style>

<main class="main-content">
<div class="app-wrapper mt-4">

    <!-- ===== Page Header ===== -->
    <div class="page-header">
        <div class="page-header-inner">
            <div>
                <div class="page-title">📋 ข้อมูลเด็กในระบบ</div>
                <div class="page-subtitle">
                    <?php if ($currentAcademicYear): ?>
                        ปีการศึกษา <?= htmlspecialchars($currentAcademicYear) ?> · จัดการและค้นหาข้อมูลเด็กทั้งหมด
                    <?php else: ?>
                        เลือกปีการศึกษาเพื่อดูข้อมูลเด็ก
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-header btn-header-outline"
                        data-bs-toggle="modal" data-bs-target="#addChildModal">
                    ➕ เพิ่มข้อมูลเด็ก
                </button>
                <button class="btn-header btn-header-outline"
                        data-bs-toggle="modal" data-bs-target="#exportModal">
                    📊 Export CSV
                </button>
                <a href="../admin/qr_codes_list.php" class="btn-header btn-header-solid">
                    📷 QR Codes
                </a>
            </div>
        </div>
    </div>

    <?php if (!$currentAcademicYear): ?>
    <!-- ===== Academic Year Selection ===== -->
    <div class="year-cards-grid">
        <?php if (!empty($academicYears)): ?>
            <?php foreach ($academicYears as $year): ?>
                <a href="?academic_year=<?= htmlspecialchars($year['name']) ?>" class="year-card">
                    <span class="year-card-icon">📅</span>
                    <div class="year-card-title"><?= htmlspecialchars($year['name']) ?></div>
                    <div>
                        <span class="year-badge <?= $year['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $year['is_active'] ? '✓ เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                        </span>
                    </div>
                    <span class="year-card-btn">เปิดดูข้อมูล →</span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column:1/-1; text-align:center; padding:3rem; color:#718096;">
                <div style="font-size:3rem; margin-bottom:1rem;">📭</div>
                <div>ยังไม่มีปีการศึกษาในระบบ</div>
            </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ===== Children View ===== -->

    <!-- Back Button -->
    <a href="children_history.php" class="back-btn">← กลับไปหน้าปีการศึกษา</a>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-card s-all">
            <div class="stat-icon">👦</div>
            <div>
                <div class="stat-value" id="statAll">0</div>
                <div class="stat-label">เด็กทั้งหมด</div>
            </div>
        </div>
        <div class="stat-card s-medium">
            <div class="stat-icon">🌟</div>
            <div>
                <div class="stat-value" id="statMedium">0</div>
                <div class="stat-label">เด็กกลาง</div>
            </div>
        </div>
        <div class="stat-card s-big">
            <div class="stat-icon">🎒</div>
            <div>
                <div class="stat-value" id="statBig">0</div>
                <div class="stat-label">เด็กโต</div>
            </div>
        </div>
        <div class="stat-card s-prep">
            <div class="stat-icon">🌱</div>
            <div>
                <div class="stat-value" id="statPrep">0</div>
                <div class="stat-label">เตรียมอนุบาล</div>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="control-panel">
        <!-- Search -->
        <div class="search-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" class="search-input" id="searchInput"
                   placeholder="ค้นหาชื่อ, ชื่อเล่น, รหัสนักเรียน..."
                   autocomplete="off">
            <button class="search-clear" id="searchClear">✕</button>
        </div>

        <!-- Group Filter -->
        <div class="filter-tabs" id="groupFilter">
            <button class="filter-tab active" data-group="all">ทั้งหมด</button>
            <button class="filter-tab" data-group="เด็กกลาง">เด็กกลาง</button>
            <button class="filter-tab" data-group="เด็กโต">เด็กโต</button>
            <button class="filter-tab" data-group="เตรียมอนุบาล">เตรียมอนุบาล</button>
        </div>

        <!-- Sort -->
        <select class="sort-select" id="sortSelect">
            <option value="name-asc">ชื่อ ก→ฮ</option>
            <option value="name-desc">ชื่อ ฮ→ก</option>
            <option value="id-asc">รหัส น้อย→มาก</option>
            <option value="id-desc">รหัส มาก→น้อย</option>
        </select>

        <!-- View Toggle -->
        <div class="view-toggle">
            <button class="view-btn active" id="viewCard" title="Card View">⊞</button>
            <button class="view-btn" id="viewTable" title="Table View">☰</button>
        </div>
    </div>

    <!-- Results Info + Classroom Pills -->
    <div class="results-info">
        <div class="results-count">
            แสดง <strong id="visibleCount">0</strong> จาก
            <strong id="totalCount">0</strong> คน
        </div>
        <div class="classroom-pills" id="classroomPills">
            <!-- สร้างโดย JavaScript -->
        </div>
    </div>

    <!-- No Results -->
    <div class="no-results" id="noResults">
        <span class="no-results-icon">🔍</span>
        <div class="no-results-text">ไม่พบข้อมูลที่ค้นหา กรุณาลองคำค้นหาอื่น</div>
    </div>

    <!-- Card View -->
    <div id="cardView"></div>

    <!-- Table View -->
    <div id="tableView" style="display:none;">
        <div class="table-wrapper">
            <table id="childrenTable">
                <thead>
                    <tr>
                        <th data-col="id">รหัส <span class="sort-arrow">↕</span></th>
                        <th data-col="name">ชื่อ-นามสกุล <span class="sort-arrow">↕</span></th>
                        <th data-col="group">กลุ่ม <span class="sort-arrow">↕</span></th>
                        <th data-col="room">ห้องเรียน <span class="sort-arrow">↕</span></th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

</div><!-- /app-wrapper -->
</main>

<!-- Modal: เพิ่มข้อมูลเด็ก -->
<div class="modal fade" id="addChildModal" tabindex="-1" aria-labelledby="addChildModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:15px; border:none;">
            <div class="modal-header" style="background:#26648E; color:white; border-radius:15px 15px 0 0;">
                <h5 class="modal-title" id="addChildModalLabel">เพิ่มข้อมูลเด็ก</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <?php include '../../include/form/form_addchild.php'; ?>
        </div>
    </div>
</div>

<!-- Modal: Export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:15px; border:none;">
            <div class="modal-header" style="background:#198754; color:white; border-radius:15px 15px 0 0;">
                <h5 class="modal-title" id="exportModalLabel">Export ข้อมูลเด็ก</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../include/export/export_children.php" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">กลุ่มเด็ก</label>
                        <select name="child_group" class="form-select" required>
                            <option value="all">ทั้งหมด</option>
                            <option value="medium">เด็กกลาง</option>
                            <option value="big">เด็กโต</option>
                            <option value="prep">เตรียมอนุบาล</option>
                        </select>
                    </div>
                    <input type="hidden" name="fields[]" value="student_id">
                    <input type="hidden" name="fields[]" value="name">
                    <input type="hidden" name="fields[]" value="nickname">
                    <input type="hidden" name="fields[]" value="classroom">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">รูปแบบไฟล์</label>
                        <select name="format" class="form-select" disabled>
                            <option value="csv">CSV</option>
                        </select>
                        <small class="text-muted">รองรับเฉพาะ CSV ในขณะนี้</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">📥 Export</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== DATA จาก PHP ===== -->
<?php if ($currentAcademicYear): ?>
<script>
    const CHILDREN = <?= json_encode($allChildrenForJS, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script>
    (function () {
        'use strict';

        /* ===== STATE ===== */
        const state = { search: '', group: 'all', room: 'all', view: 'card', sort: 'name-asc' };

        /* ===== DOM ===== */
        const searchInput    = document.getElementById('searchInput');
        const searchClear    = document.getElementById('searchClear');
        const groupFilter    = document.getElementById('groupFilter');
        const sortSelect     = document.getElementById('sortSelect');
        const viewCardBtn    = document.getElementById('viewCard');
        const viewTableBtn   = document.getElementById('viewTable');
        const cardView       = document.getElementById('cardView');
        const tableView      = document.getElementById('tableView');
        const tableBody      = document.getElementById('tableBody');
        const classroomPills = document.getElementById('classroomPills');
        const visibleCount   = document.getElementById('visibleCount');
        const totalCount     = document.getElementById('totalCount');
        const noResults      = document.getElementById('noResults');

        /* ===== AVATAR PLACEHOLDER ===== */
        function avatarPlaceholder(gender) {
            const fill = gender === 'female' ? '%23f9a8d4' : '%2393c5fd';
            return `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' fill='${fill}'/%3E%3Ccircle cx='40' cy='30' r='16' fill='%23fff' opacity='.7'/%3E%3Cellipse cx='40' cy='72' rx='24' ry='18' fill='%23fff' opacity='.7'/%3E%3C/svg%3E`;
        }

        function getImgSrc(child) {
            return (child.img && child.img.trim() !== '')
                ? child.img
                : avatarPlaceholder(child.gender);
        }

        /* ===== HIGHLIGHT ===== */
        function highlight(text, term) {
            if (!term || !text) return text || '';
            const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return String(text).replace(new RegExp(`(${escaped})`, 'gi'),
                '<mark class="highlight">$1</mark>');
        }

        /* ===== SORT ===== */
        function sortChildren(arr) {
            return [...arr].sort((a, b) => {
                switch (state.sort) {
                    case 'name-asc':  return (a.first||'').localeCompare(b.first||'', 'th');
                    case 'name-desc': return (b.first||'').localeCompare(a.first||'', 'th');
                    case 'id-asc':    return (a.id||'').localeCompare(b.id||'');
                    case 'id-desc':   return (b.id||'').localeCompare(a.id||'');
                    default:          return 0;
                }
            });
        }

        /* ===== FILTER ===== */
        function filterChildren() {
            const term = state.search.toLowerCase().trim();
            return CHILDREN.filter(c => {
                const matchGroup  = state.group === 'all' || c.group === state.group;
                const matchRoom   = state.room  === 'all' || c.room  === state.room;
                const fullName    = `${c.prefix||''}${c.first||''} ${c.last||''}`;
                const matchSearch = !term ||
                    (c.first||'').toLowerCase().includes(term) ||
                    (c.last||'').toLowerCase().includes(term)  ||
                    (c.nick||'').toLowerCase().includes(term)  ||
                    (c.id||'').toLowerCase().includes(term)    ||
                    fullName.toLowerCase().includes(term);
                return matchGroup && matchRoom && matchSearch;
            });
        }

        /* ===== BUILD CLASSROOM PILLS ===== */
        // Updated to accept a list of children so pills reflect the current group filter
        function buildClassroomPills(children = CHILDREN) {
            const rooms = [...new Set(children.map(c => c.room))].sort();
            let html = `<button class="classroom-pill active" data-room="all">ทุกห้อง</button>`;
            rooms.forEach(r => {
                html += `<button class="classroom-pill" data-room="${r}">ห้อง ${r}</button>`;
            });
            classroomPills.innerHTML = html;

            classroomPills.addEventListener('click', function (e) {
                const pill = e.target.closest('.classroom-pill');
                if (!pill) return;
                document.querySelectorAll('.classroom-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                state.room = pill.dataset.room;
                render();
            });
        }

        /* ===== BUILD CARD ===== */
        function buildCard(child) {
            const term     = state.search;
            const fullName = `${child.prefix||''}${child.first||''} ${child.last||''}`;
            const imgSrc   = getImgSrc(child);

            return `
            <div class="child-card">
                <div class="card-body">
                    <div class="avatar-wrapper">
                        <img src="${imgSrc}"
                            alt="${fullName}"
                            onerror="this.src='${avatarPlaceholder(child.gender)}'">
                    </div>
                    <div class="child-name-text">${highlight(fullName, term)}</div>
                    <div class="child-nickname-text">น้อง${highlight(child.nick||'', term)}</div>
                    <div class="child-nickname-text">${highlight(child.id||'')}</div>
                    <div class="child-tags">
                        <span class="tag tag-group">${child.group||''}</span>
                        <span class="tag tag-room">ห้อง ${child.room||''}</span>
                    </div>
                    <a href="view_child.php?studentid=${child.id||''}" class="card-action-btn">
                        👁 ดูรายละเอียด
                    </a>
                </div>
            </div>`;
        }

        /* ===== BUILD TABLE ROW ===== */
        function buildRow(child) {
            const term     = state.search;
            const fullName = `${child.prefix||''}${child.first||''} ${child.last||''}`;
            const imgSrc   = getImgSrc(child);

            return `
            <tr>
                <td><code style="font-size:0.8rem;color:#718096;">${child.id||''}</code></td>
                <td>
                    <div class="table-name-cell">
                        <img src="${imgSrc}" class="table-avatar" alt="${fullName}"
                            onerror="this.src='${avatarPlaceholder(child.gender)}'">
                        <div>
                            <div class="table-name">${highlight(fullName, term)}</div>
                            <div class="table-nickname-sub">น้อง${highlight(child.nick||'', term)}</div>
                        </div>
                    </div>
                </td>
                <td><span class="tbl-badge tbl-badge-group">${child.group||''}</span></td>
                <td><span class="tbl-badge tbl-badge-room">ห้อง ${child.room||''}</span></td>
                <td>
                    <a href="view_child.php?studentid=${child.id||''}" class="action-link">
                        👁 ดูข้อมูล
                    </a>
                </td>
            </tr>`;
        }

        /* ===== RENDER CARD VIEW ===== */
        function renderCardView(filtered) {
            // จัดกลุ่ม group → room → children
            const structure = {};
            filtered.forEach(c => {
                if (!structure[c.group]) structure[c.group] = {};
                if (!structure[c.group][c.room]) structure[c.group][c.room] = [];
                structure[c.group][c.room].push(c);
            });

            const groupMeta = {
                'เด็กกลาง':    { icon: '🌟' },
                'เด็กโต':      { icon: '🎒' },
                'เตรียมอนุบาล':{ icon: '🌱' },
            };

            let html = '';
            Object.keys(structure).forEach(groupName => {
                const rooms    = structure[groupName];
                const meta     = groupMeta[groupName] || { icon: '👦' };
                const groupTotal = Object.values(rooms).reduce((s, arr) => s + arr.length, 0);

                html += `
                <div class="group-section">
                    <div class="group-header-bar">
                        <span>${meta.icon}</span>
                        <span class="group-header-title">${groupName}</span>
                        <span class="group-count">${groupTotal} คน</span>
                    </div>`;

                Object.keys(rooms).sort().forEach(roomName => {
                    const children = sortChildren(rooms[roomName]);
                    html += `
                    <div class="classroom-section">
                        <div class="classroom-label">ห้อง ${roomName}</div>
                        <div class="cards-grid">
                            ${children.map(c => buildCard(c)).join('')}
                        </div>
                    </div>`;
                });

                html += `</div>`;
            });

            cardView.innerHTML = html || '';
        }

        /* ===== RENDER TABLE VIEW ===== */
        function renderTableView(filtered) {
            const sorted = sortChildren(filtered);
            tableBody.innerHTML = sorted.map(c => buildRow(c)).join('');
        }

        /* ===== UPDATE STATS ===== */
        function updateStats(filtered) {
            const counts = { 'เด็กกลาง': 0, 'เด็กโต': 0, 'เตรียมอนุบาล': 0 };
            filtered.forEach(c => {
                if (counts[c.group] !== undefined) counts[c.group]++;
            });

            document.getElementById('statAll').textContent    = filtered.length;
            document.getElementById('statMedium').textContent = counts['เด็กกลาง'];
            document.getElementById('statBig').textContent    = counts['เด็กโต'];
            document.getElementById('statPrep').textContent   = counts['เตรียมอนุบาล'];

            visibleCount.textContent = filtered.length;
            totalCount.textContent   = CHILDREN.length;
        }

        /* ===== MAIN RENDER ===== */
        function render() {
            const filtered = filterChildren();
            updateStats(filtered);

            if (state.view === 'card') {
                renderCardView(filtered);
            } else {
                renderTableView(filtered);
            }

            noResults.classList.toggle('visible', filtered.length === 0);
        }

        /* ===== EVENTS ===== */
        searchInput.addEventListener('input', function () {
            state.search = this.value;
            searchClear.style.display = this.value ? 'block' : 'none';
            render();
        });

        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            state.search = '';
            this.style.display = 'none';
            searchInput.focus();
            render();
        });

        groupFilter.addEventListener('click', function (e) {
            const tab = e.target.closest('.filter-tab');
            if (!tab) return;
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            // Update group and reset room filter to "all"
            state.group = tab.dataset.group;
            state.room = 'all';
            // Rebuild classroom pills based on the new group filter
            const filteredForPills = filterChildren();
            buildClassroomPills(filteredForPills);
            render();
        });

        sortSelect.addEventListener('change', function () {
            state.sort = this.value;
            render();
        });

        viewCardBtn.addEventListener('click', function () {
            state.view = 'card';
            viewCardBtn.classList.add('active');
            viewTableBtn.classList.remove('active');
            cardView.style.display  = '';
            tableView.style.display = 'none';
            render();
        });

        viewTableBtn.addEventListener('click', function () {
            state.view = 'table';
            viewTableBtn.classList.add('active');
            viewCardBtn.classList.remove('active');
            tableView.style.display = '';
            cardView.style.display  = 'none';
            render();
        });

        // Table column sort
        const thead = document.querySelector('#childrenTable thead');
        if (thead) {
            thead.addEventListener('click', function (e) {
                const th = e.target.closest('th[data-col]');
                if (!th) return;
                const col   = th.dataset.col;
                const isAsc = state.sort === `${col}-asc`;
                state.sort  = isAsc ? `${col}-desc` : `${col}-asc`;
                sortSelect.value = state.sort;

                document.querySelectorAll('thead th').forEach(t => {
                    t.classList.remove('sorted');
                    const arrow = t.querySelector('.sort-arrow');
                    if (arrow) arrow.textContent = '↕';
                });
                th.classList.add('sorted');
                const arrow = th.querySelector('.sort-arrow');
                if (arrow) arrow.textContent = isAsc ? '↓' : '↑';

                render();
            });
        }

        /* ===== INIT ===== */
        buildClassroomPills(); // initial pills for all children
        render();

    })();
</script>
<?php endif; ?>

</body>
</html>