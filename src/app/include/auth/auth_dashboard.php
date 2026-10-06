<?php
function isCurrentPage($path)
{
    $current_url = $_SERVER['REQUEST_URI'];

    // ตรวจสอบกรณีพิเศษสำหรับหน้า Dashboard
    if ($path === 'dashboard') {
        if (getUserRole() === 'admin' && strpos($current_url, 'admin_dashboard.php') !== false) {
            return true;
        } else if (getUserRole() === 'student' && strpos($current_url, 'student_dashboard.php') !== false) {
            return true;
        } else if (getUserRole() === 'teacher' && strpos($current_url, 'teacher_dashboard.php') !== false) {
            return true;
        } else if (getUserRole() === 'doctor' && strpos($current_url, 'doctor_dashboard.php') !== false) {
            return true;
        }
        return false;
    }

    return strpos($current_url, $path) !== false;
}
?>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->


        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar-open collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column sidebar">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo isCurrentPage('dashboard') ? 'active' : ''; ?>"
                            aria-current="page" href="<?php
                                                        echo isLoggedIn()
                                                            ? (getUserRole() === 'admin'
                                                                ? '/app/views/admin/admin_dashboard.php'
                                                                : (getUserRole() === 'doctor'
                                                                    ? '/app/views/doctor/doctor_dashboard.php'
                                                                    : (getUserRole() === 'student'
                                                                        ? '/app/views/student/student_dashboard.php'
                                                                        : '/app/views/teacher/teacher_dashboard.php')))
                                                            : 'login.php';
                                                        ?>">
                            <i class="bi bi-house-door" style="font-size: 27px;"></i>
                            Dashboard
                        </a>
                    </li>
                     

                    <!-- เมนูสำหรับ Admin (แสดงเฉพาะสำหรับ admin) -->
                    <?php if (getUserRole() === 'admin'): ?>
                        <?php
                        // เมนูของ admin จัดเป็นกลุ่ม (กดที่หัวกลุ่มเพื่อพับ/กาง)
                        $adminMenu = [
                            [
                                'title' => 'งานประจำวัน',
                                'icon' => 'bi bi-calendar-check',
                                'items' => [
                                    ['label' => 'แสกนเช็คชื่อมาเรียน', 'href' => '/app/views/teacher/attendance.php', 'match' => 'attendance.php', 'icon' => 'bi bi-qr-code-scan'],
                                    ['label' => 'แสกนเช็คชื่อกลับบ้าน', 'href' => '/app/views/teacher/scan_checkout.php', 'match' => 'scan_checkout.php', 'icon' => 'bi bi-qr-code-scan'],
                                    ['label' => 'สมุดสื่อสารประจำวัน', 'href' => '/app/views/teacher/daily_notebook.php', 'match' => 'daily_notebook.php', 'icon' => 'bi bi-journal-text'],
                                    ['label' => 'เมนูอาหารรายวัน', 'href' => '/app/views/teacher/daily_menu.php', 'match' => 'daily_menu.php', 'icon' => 'cil-restaurant'],
                                ],
                            ],
                            [
                                'title' => 'ข้อมูลเด็ก',
                                'icon' => 'bi bi-people-fill',
                                'items' => [
                                    ['label' => 'ข้อมูลของเด็ก', 'href' => '/app/views/student/children_history.php', 'match' => 'children_history.php', 'icon' => 'bi bi-people-fill'],
                                    ['label' => 'เลื่อนชั้น', 'href' => '/app/views/admin/children_transition.php', 'match' => 'children_transition.php', 'icon' => 'bi bi-arrow-up-circle', 'sub' => true],
                                    ['label' => 'สำเร็จการศึกษา', 'href' => '/app/views/admin/children_graduation.php', 'match' => 'children_graduation.php', 'icon' => 'bi bi-mortarboard-fill', 'sub' => true],
                                    ['label' => 'จัดการ QR Code', 'href' => '/app/views/admin/qr_codes_list.php', 'match' => 'qr_codes_list.php', 'icon' => 'bi bi-qr-code'],
                                ],
                            ],
                            [
                                'title' => 'ประวัติและรายงาน',
                                'icon' => 'bi bi-clock-history',
                                'items' => [
                                    ['label' => 'ประวัติการมาเรียน', 'href' => '/app/views/attendance_history.php', 'match' => 'attendance_history.php', 'icon' => 'bi bi-archive-fill'],
                                    ['label' => 'ตรวจร่างกายประจำวัน', 'href' => '/app/views/checklist_history.php', 'match' => 'checklist_history.php', 'icon' => 'fa-solid fa-stethoscope'],
                                    ['label' => 'ตรวจสุขภาพโดยกุมารแพทย์', 'href' => '/app/views/check_health_external/checklist_name.php', 'match' => 'check_health_external', 'icon' => 'fa-solid fa-user-doctor'],
                                    ['label' => 'ตรวจสุขภาพช่องปาก', 'href' => '/app/views/check_health_tooth/checklist_name.php', 'match' => 'check_health_tooth', 'icon' => 'fa-solid fa-tooth'],
                                    ['label' => 'กราฟการเจริญเติบโต', 'href' => '/app/views/growth_history.php', 'match' => 'growth_history.php', 'icon' => 'bi bi-graph-up'],
                                ],
                            ],
                            [
                                'title' => 'จัดการระบบ',
                                'icon' => 'bi bi-sliders',
                                'items' => [
                                    ['label' => 'จัดการโปรไฟล์ครู/ผู้ใช้', 'href' => '/app/views/admin/profile_management.php', 'match' => 'profile_management.php', 'icon' => 'bi bi-person-lines-fill'],
                                    ['label' => 'บัญชีรออนุมัติ', 'href' => '/app/views/admin/wait_appove_account.php', 'match' => 'wait_appove_account.php', 'icon' => 'bi bi-person-check'],
                                    ['label' => 'แจ้งปัญหาการเข้าสู่ระบบ', 'href' => '/app/views/admin/login_issues.php', 'match' => 'login_issues.php', 'icon' => 'bi bi-exclamation-triangle'],
                                    ['label' => 'ตั้งค่าการเช็คชื่อ', 'href' => '/app/views/admin/checkin_settings.php', 'match' => 'checkin_settings.php', 'icon' => 'bi bi-gear-fill'],
                                ],
                            ],
                        ];

                        // กลุ่มที่มีหน้าปัจจุบันอยู่จะกางไว้เสมอ ถ้าไม่อยู่ในกลุ่มใดเลย (เช่นหน้า Dashboard) ให้กางกลุ่มแรก
                        $adminActiveGroup = null;
                        foreach ($adminMenu as $gi => $group) {
                            foreach ($group['items'] as $item) {
                                if (isCurrentPage($item['match'])) {
                                    $adminActiveGroup = $gi;
                                    break 2;
                                }
                            }
                        }
                        ?>
                        <style>
                            .nav-group { margin: .5rem 1rem 0; list-style: none; }
                            .nav-group-toggle {
                                width: 100%; display: flex; align-items: center; gap: .6rem; background: none; border: none;
                                color: rgba(255, 255, 255, .75); font-size: .9rem; font-weight: 700; letter-spacing: .03em;
                                padding: .55rem .6rem; border-radius: 8px; text-align: left;
                            }
                            .nav-group-toggle:hover { background: rgba(255, 255, 255, .08); color: #fff; }
                            .nav-group-toggle .chev { margin-left: auto; font-size: .75rem; transition: transform .2s ease; }
                            .nav-group.open > .nav-group-toggle .chev { transform: rotate(180deg); }
                            .nav-group.has-active > .nav-group-toggle { color: #fff; }
                            .nav-group-items { list-style: none; padding: 0; margin: .15rem 0 0; display: none; }
                            .nav-group.open > .nav-group-items { display: block; }
                            .sidebar .nav-group-items .nav-item { margin: .12rem 0; }
                            .sidebar .nav-group-items .nav-link { padding: .6rem .8rem; font-size: 1.02rem; }
                            .sidebar .nav-group-items .nav-sub .nav-link { padding-left: 2.1rem; font-size: .95rem; }
                            .sidebar .nav-group-items .nav-link.active::before { left: -.4rem; }
                        </style>

                        <?php foreach ($adminMenu as $gi => $group): ?>
                            <?php $isOpen = $adminActiveGroup === $gi || ($adminActiveGroup === null && $gi === 0); ?>
                            <li class="nav-group <?php echo $isOpen ? 'open' : ''; ?> <?php echo $adminActiveGroup === $gi ? 'has-active' : ''; ?>" data-group="g<?php echo $gi; ?>">
                                <button type="button" class="nav-group-toggle" aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>">
                                    <i class="<?php echo htmlspecialchars($group['icon']); ?>"></i>
                                    <span><?php echo htmlspecialchars($group['title']); ?></span>
                                    <i class="bi bi-chevron-down chev"></i>
                                </button>
                                <ul class="nav-group-items">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <li class="nav-item <?php echo !empty($item['sub']) ? 'nav-sub' : ''; ?>">
                                            <a class="nav-link <?php echo isCurrentPage($item['match']) ? 'active' : ''; ?>"
                                                href="<?php echo htmlspecialchars($item['href']); ?>">
                                                <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                                <?php echo htmlspecialchars($item['label']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php endforeach; ?>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var KEY = 'adminNavOpen';
                                var saved = {};
                                try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { saved = {}; }

                                document.querySelectorAll('.nav-group').forEach(function (g) {
                                    var id = g.dataset.group;
                                    var btn = g.querySelector('.nav-group-toggle');
                                    // จำสถานะที่ผู้ใช้เคยพับ/กางไว้ (กลุ่มที่มีหน้าปัจจุบันกางเสมอ)
                                    if (!g.classList.contains('has-active') && saved[id] !== undefined) {
                                        g.classList.toggle('open', saved[id] === true);
                                        btn.setAttribute('aria-expanded', saved[id] === true ? 'true' : 'false');
                                    }
                                    btn.addEventListener('click', function () {
                                        var open = g.classList.toggle('open');
                                        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                                        saved[id] = open;
                                        try { localStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) { /* ไม่เป็นไร */ }
                                    });
                                });
                            });
                        </script>
                    <?php endif; ?>

                    <?php if (getUserRole() === 'teacher'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('attendance.php') ? 'active' : ''; ?>"
                                href="/app/views/teacher/attendance.php">
                                <i class="bi bi-qr-code-scan" style="font-size: 23px;"></i>
                                แสกนเช็คชื่อ<br>มาเรียน
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('scan_checkout.php') ? 'active' : ''; ?>"
                                href="/app/views/teacher/scan_checkout.php">
                                <i class="bi bi-qr-code-scan" style="font-size: 23px;"></i>
                                แสกนเช็คชื่อ<br>กลับบ้าน
                            </a>
                        </li>

                        <li class="nav-item <?php echo isCurrentPage('qr_codes_list.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/admin/qr_codes_list.php">
                                <i class="bi bi-qr-code" style="font-size: 23px;"></i>
                                จัดการ QR Code
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('attendance_history.php') ? 'active' : ''; ?>"
                                href="/app/views/attendance_history.php">
                                <i class="bi bi-archive-fill" style="font-size: 23px;"></i>
                                บันทึกประวัติการเช็คชื่อมาเรียน
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('checklist_history.php') ? 'active' : ''; ?>"
                                href="/app/views/checklist_history.php">
                                <i class="fa-solid fa-stethoscope" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจร่างกาย
                            </a>
                        </li>
                        <li class="nav-item <?php echo isCurrentPage('check_health_external') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_external/checklist_name.php">
                                <i class="fa-solid fa-user-doctor" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจสุขภาพ
                            </a>
                        </li>
                        <li class="nav-item <?php echo isCurrentPage('check_health_tooth') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_tooth/checklist_name.php">
                                <i class="fa-solid fa-tooth" style="font-size: 23px;"></i>
                                บันทึกการตรวจสุขภาพช่องปาก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('growth_history.php') ? 'active' : ''; ?>"
                                href="/app/views/growth_history.php">
                                <i class="bi bi-graph-up" style="font-size: 23px;"></i>
                                บันทึกกราฟการเจริญเติบโตของเด็ก
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('daily_notebook.php') ? 'active' : ''; ?>"
                                href="/app/views/teacher/daily_notebook.php">
                                <i class="bi bi-journal-text" style="font-size: 23px;"></i>
                                สมุดสื่อสารประจำวัน
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('daily_menu.php') ? 'active' : ''; ?>"
                                href="/app/views/teacher/daily_menu.php">
                                <i class="cil-restaurant" style="font-size: 23px;"></i>
                                เมนูอาหารรายวัน
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (getUserRole() === 'doctor'): ?>
                        <li class="nav-item <?php echo isCurrentPage('check_health_external/checklist_name.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_external/checklist_name.php">
                                <i class="fa-solid fa-user-doctor" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจสุขภาพ
                            </a>
                        </li>
                        <li class="nav-item <?php echo isCurrentPage('check_health_tooth/checklist_name.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_tooth/checklist_name.php">
                                <i class="fa-solid fa-tooth" style="font-size: 23px;"></i>
                                บันทึกการตรวจสุขภาพช่องปาก
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- เมนูสำหรับ student (แสดงเฉพาะสำหรับ ผู้ปกครอง) -->
                    <?php if (getUserRole() === 'student'): ?>
                        <?php
                        // ดึง studentid จาก username ปัจจุบัน
                        $studentid = $_SESSION['username'] ?? '';
                    
                        ?>

                        <li class="nav-item">
                            <a href="/app/views/student/view_child.php?studentid=<?= htmlspecialchars($studentid) ?>"
                                class="nav-link">
                                <i class="bi bi-person-fill" style="font-size: 23px;"></i>
                                ดูประวัติประจำตัว
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/app/views/student/daily_notebook.php"
                                class="nav-link <?php echo isCurrentPage('daily_notebook.php') ? 'active' : ''; ?>">
                                <i class="bi bi-journal-text" style="font-size: 23px;"></i>
                                สมุดสื่อสารประจำวัน
                            </a>
                        </li>

                        <!-- <li class="nav-item">
                            <a class="nav-link" href="/app/views/attendance_history.php">
                                <i class="bi bi-archive-fill" style="font-size: 23px;"></i>
                                ประวัติการเช็คชื่อมาเรียน
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="/app/views/checklist_history.php">
                                <i class="fa-solid fa-stethoscope" style="font-size: 23px;"></i>
                                ประวัติการตรวจร่างกาย
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('nutrition_history.php') ? 'active' : ''; ?>" 
                               href="/app/views/nutrition_history.php">
                                <i class="cil-restaurant" style="font-size: 23px;"></i>
                                บันทึกประวัติโภชนาการและพัฒนาการ
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('growth_chart.php') ? 'active' : ''; ?>" 
                               href="/app/views/teacher/growth_chart.php">
                                <i class="bi bi-graph-up" style="font-size: 23px;"></i>
                                บันทึกกราฟการเจริญเติบโตของเด็ก
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-bar-chart-2" aria-hidden="true">
                                    <line x1="18" y1="20" x2="18" y2="10"></line>
                                    <line x1="12" y1="20" x2="12" y2="4"></line>
                                    <line x1="6" y1="20" x2="6" y2="14"></line>
                                </svg>
                                กิจกรรมของเด็ก
                            </a>
                        </li> -->
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>
</div>
