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
                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('children_history.php') ? 'active' : ''; ?>"
                                href="/app/views/student/children_history.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-file" aria-hidden="true">
                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                    <polyline points="13 2 13 9 20 9"></polyline>
                                </svg>
                                ข้อมูลของเด็ก
                            </a>
                            <ul class="nav flex-column ml-3">
                                <li class="nav-item <?php echo isCurrentPage('children_transition.php') ? 'active' : ''; ?>">
                                    <a class="nav-link" href="/app/views/admin/children_transition.php">
                                        <i class="cil-swap-vertical" style="font-size: 23px;"></i>
                                        การจัดการเลื่อนชั้น
                                    </a>
                                </li>
                                <li class="nav-item <?php echo isCurrentPage('children_graduation.php') ? 'active' : ''; ?>">
                                    <a class="nav-link" href="/app/views/admin/children_graduation.php">
                                        <i class="fas fa-graduation-cap" style="font-size: 23px;"></i>

                                        จัดการเลื่อนสำเร็จการศึกษา
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('attendance.php') ? 'active' : ''; ?>"
                                href="/app/views/teacher/attendance.php">
                                <i class="bi bi-qr-code-scan" style="font-size: 23px;"></i>
                                แสกนเช็คชื่อ
                            </a>
                        </li>

                        <li class="nav-item <?php echo isCurrentPage('attendance_history.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/attendance_history.php">
                                <i class="bi bi-archive-fill" style="font-size: 23px;"></i>
                                บันทึกประวัติการเช็คชื่อมาเรียน
                            </a>
                        </li>

                        <li class="nav-item <?php echo isCurrentPage('checklist_history.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/checklist_history.php">
                                <i class="bi bi-person-arms-up" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจร่างกายประจำวัน
                            </a>
                        </li>

                        <li class="nav-item <?php echo isCurrentPage('checklist_history.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_external/checklist_name.php">
                                <i class="fa-solid fa-user-doctor" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจสุขภาพ
                            </a>
                        </li>
                         <li class="nav-item <?php echo isCurrentPage('checklist_history.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_tooth/checklist_name.php">
                                <i class="fa-solid fa-tooth" style="font-size: 23px;"></i>
                                บันทึกการตรวจสุขภาพช่องปาก
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
                            <a class="nav-link <?php echo isCurrentPage('growth_history.php') ? 'active' : ''; ?>"
                                href="/app/views/growth_history.php">
                                <i class="bi bi-graph-up" style="font-size: 23px;"></i>
                                บันทึกกราฟการเจริญเติบโตของเด็ก
                            </a>
                        </li>

                        <li class="nav-item <?php echo isCurrentPage('profile_management.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/admin/profile_management.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-bar-chart-2" aria-hidden="true">
                                    <line x1="18" y1="20" x2="18" y2="10"></line>
                                    <line x1="12" y1="20" x2="12" y2="4"></line>
                                    <line x1="6" y1="20" x2="6" y2="14"></line>
                                </svg>
                                จัดการโปรไฟล์
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link <?php echo isCurrentPage('wait_appove_account.php') ? 'active' : ''; ?>"
                                href="/app/views/admin/wait_appove_account.php">
                                <i class="bi bi-people me-2"></i>
                                จัดการบัญชีรออนุมัติ
                            </a>
                        </li>

                    <?php endif; ?>

                    <?php if (getUserRole() === 'teacher'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('attendance.php') ? 'active' : ''; ?>"
                                href="/app/views/teacher/attendance.php">
                                <i class="bi bi-qr-code-scan" style="font-size: 23px;"></i>
                                แสกนเช็คชื่อ
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
                                <i class="bi bi-person-arms-up" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจร่างกาย
                            </a>
                        </li>
                        <li class="nav-item <?php echo isCurrentPage('checklist_history.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_external/checklist_name.php">
                                <i class="fa-solid fa-user-doctor" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจสุขภาพ
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
                            <a class="nav-link <?php echo isCurrentPage('growth_history.php') ? 'active' : ''; ?>"
                                href="/app/views/growth_history.php">
                                <i class="bi bi-graph-up" style="font-size: 23px;"></i>
                                บันทึกกราฟการเจริญเติบโตของเด็ก
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (getUserRole() === 'doctor'): ?>
                        <li class="nav-item <?php echo isCurrentPage('checklist_history.php') ? 'active' : ''; ?>">
                            <a class="nav-link" href="/app/views/check_health_external/checklist_name.php">
                                <i class="fa-solid fa-user-doctor" style="font-size: 23px;"></i>
                                บันทึกประวัติการตรวจสุขภาพ
                            </a>
                        </li>
                        <li class="nav-item <?php echo isCurrentPage('checklist_history.php') ? 'active' : ''; ?>">
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

                        <!-- <li class="nav-item">
                            <a class="nav-link" href="/app/views/attendance_history.php">
                                <i class="bi bi-archive-fill" style="font-size: 23px;"></i>
                                ประวัติการเช็คชื่อมาเรียน
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="/app/views/checklist_history.php">
                                <i class="bi bi-person-arms-up" style="font-size: 23px;"></i>
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