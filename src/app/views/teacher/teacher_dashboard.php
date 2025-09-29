<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['teacher']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/child_functions.php';
$children = getChildrenData();

// ใช้ user_id จาก session เป็น teacher_id
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id']; // ดึงค่า user_id มาเป็น teacher_id
} else {
    die('ไม่พบข้อมูลผู้สอน. กรุณาเข้าสู่ระบบอีกครั้ง.'); // กรณีที่ไม่มี user_id ใน session
}

// รับค่า tab และ room จาก URL
$currentTab = $_GET['tab'] ?? 'all'; // ใช้ค่าเริ่มต้น 'all' หากไม่ได้รับค่า

// ดึงข้อมูลจากฟังก์ชัน
$data = getChildrenDataByTeacher($teacher_id, $currentTab);
$teacherData = getTeacherById($teacher_id);  // ดึงข้อมูลคุณครู

require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/auth/auth_dashboard.php';

?>
<?php
// ตรวจสอบว่ามีสถานะถูกส่งมาหรือไม่
$status = isset($_GET['status']) ? $_GET['status'] : null;
$message = isset($_GET['message']) ? urldecode($_GET['message']) : null;
?>
<?php if ($status && $message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: '<?php echo $status === "success" ? "success" : "error"; ?>',
                title: '<?php echo $status === "success" ? "สำเร็จ" : "ไม่สำเร็จ"; ?>',
                text: '<?php echo htmlspecialchars($message); ?>',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                // ลบพารามิเตอร์ status และ message ออกจาก URL
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                url.searchParams.delete('message');
                history.pushState({}, '', url);
            });
        });
    </script>
<?php endif; ?>

<style>

    /* Card Styles */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    }

    /* Teacher Profile Card */
    .teacher-profile-card {
        background: linear-gradient(135deg, #26648E 0%, #1F4E6E 100%);
        color: white;
        margin-bottom: 2rem;
    }

    .teacher-profile-img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border: 4px solid rgba(255,255,255,0.3);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    /* Navigation Tabs */
    .nav-tabs {
        border: none;
        margin-bottom: 2rem;
        gap: 0.5rem;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #495057;
        padding: 0.8rem 1.5rem;
        border-radius: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }

    .nav-tabs .nav-link.active {
        background: #26648E;
        color: white;
    }

    /* Student Cards */
    .student-card {
        height: 100%;
        background: white;
    }

    .student-card .card-body {
        padding: 1.5rem;
    }

    .student-image {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 1rem;
        border: 3px solid #f8f9fa;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* Status Badges */
    .badge {
        padding: 0.5em 1em;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.85em;
    }

    /* Action Buttons */
    .btn-primary {
        background: #26648E;
        border: none;
        border-radius: 20px;
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: #1F4E6E;
        transform: translateY(-2px);
    }

    /* Section Headers */
    h3, h4 {
        color: #2c3e50;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    /* Empty State */
    .text-center {
        color:rgb(0, 0, 0);
        font-weight: 400;
    }

    /* Responsive Grid */
    @media (max-width: 768px) {
        .col-12 {
            margin-bottom: 1rem;
        }
        
        .teacher-profile-card {
            text-align: center;
        }
        
        .teacher-profile-img {
            margin-bottom: 1rem;
        }
    }

    /* Quick Action Buttons */
    .quick-actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .quick-action-btn {
        background: white;
        border: none;
        border-radius: 10px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* Group Headers */
    .group-header {
        background: #f8f9fa;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        border-left: 4px solid #26648E;
    }
</style>

<main class="main-content ">
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 justify-center">ข้อมูลของเด็กนักเรียนที่อยู่ในการดูแล</h1>
    </div>

    <div class="container mt-4">
        <?php if ($teacherData): ?>
            <div class="card teacher-profile-card">
                <div class="row g-0 align-items-center">
                    <div class="col-md-4 p-4 text-center">
                        <img src="<?= !empty($teacherData['teacher_image']) ? 
                            htmlspecialchars($teacherData['teacher_image']) : 
                            '../../../public/assets/images/avatar.png' ?>"
                            class="teacher-profile-img rounded-circle" 
                            alt="รูปคุณครู"
                            onerror="this.src='../../../public/assets/images/avatar.png'">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <h4 class="card-title mb-3">
                                <?= htmlspecialchars($teacherData['teacher_firstname']) . " " . 
                                    htmlspecialchars($teacherData['teacher_lastname']) ?>
                            </h4>
                            <p class="card-text mb-2">
                                <i class="bi bi-people-fill me-2"></i>
                                <strong>กลุ่มที่ดูแล:</strong> <?= htmlspecialchars($teacherData['teacher_group'] ?? '-') ?>
                            </p>
                            <p class="card-text mb-2">
                                <i class="bi bi-door-open-fill me-2"></i>
                                <strong>ห้องเรียนที่ดูแล:</strong> <?= htmlspecialchars($teacherData['teacher_classroom'] ?? '-') ?>
                            </p>
                            <p class="card-text">
                                <small class="text-light">
                                    <i class="bi bi-person-badge me-2"></i>
                                    Teacher ID: <?= htmlspecialchars($teacherData['teacher_id']) ?>
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="quick-actions">
                <a href="daily_checklist.php" class="quick-action-btn text-decoration-none text-dark">
                    <i class="bi bi-clipboard2-check"></i>
                    บันทึกการตรวจร่างกาย
                </a>
                <a href="nutrition_child.php" class="quick-action-btn text-decoration-none text-dark">
                    <i class="cil-restaurant"></i>
                    บันทึกโภชนาการ
                </a>
                <a href="growth_chart.php" class="quick-action-btn text-decoration-none text-dark">
                    <i class="bi bi-graph-up"></i>
                    บันทึกกราฟมาตรฐานการเจริญเติบโตของเด็ก
                </a>
                <a href="../admin/qr_codes_list.php" class="quick-action-btn text-decoration-none text-dark">
                    <i class="bi bi-qr-code"></i>
                    รายการคิวอาร์โค้ด
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">ไม่พบข้อมูลคุณครู</div>
        <?php endif; ?>


        <!-- ส่วนของแท็บ เลือกกลุ่มเรียน-->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'all' ? 'active' : '' ?>" href="?tab=all">ทั้งหมด</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'big' ? 'active' : '' ?>" href="?tab=big">เด็กโต</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'medium' ? 'active' : '' ?>" href="?tab=medium">เด็กกลาง</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'prep' ? 'active' : '' ?>" href="?tab=prep">เตรียมอนุบาล</a>
            </li>

        </ul>

        <!-- แสดงข้อมูลเด็กในรูปแบบ card -->
        <div class="container mt-4">
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $groupData): ?>
                    <h3 class="mb-3"><?= htmlspecialchars($groupData['group']) ?></h3>
                    <?php if (!empty($groupData['classrooms'])): ?>
                        <?php foreach ($groupData['classrooms'] as $classroomData): ?>
                            <h4 class="mb-2"><strong>ห้อง:</strong> <?= htmlspecialchars($classroomData['classroom']) ?></h4>
                            <div class="row">
                                <?php if (!empty($classroomData['children'])): ?>
                                    <?php foreach ($classroomData['children'] as $child): ?>
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-body text-center d-flex flex-column">
                                                    <img src="<?= !empty($child['profile_image']) ?
                                                        htmlspecialchars($child['profile_image']) :
                                                        '../../../public/assets/images/avatar.png' ?>"
                                                        alt="Profile Image" class="img-fluid rounded-circle mx-auto"
                                                        style="width: 100px; height: 100px; object-fit: cover; margin-bottom: 10px;"
                                                        onerror="this.src='../../../public/assets/images/avatar.png'">
                                                    <h5 class="card-title">
                                                        <?= htmlspecialchars($child['prefix_th']) ?>
                                                        <?= htmlspecialchars($child['firstname_th']) ?>
                                                        <?= htmlspecialchars($child['lastname_th']) ?>
                                                    </h5>
                                                    <p class="card-text"><strong>กลุ่ม:</strong> <?= htmlspecialchars($child['child_group']) ?></p>
                                                    <p class="card-text"><strong>ชื่อเล่น:</strong> <?= htmlspecialchars($child['nickname']) ?></p>
                                                    <p class="card-text"><strong>ห้องเรียน:</strong> <?= htmlspecialchars($child['classroom']) ?></p>
                                                    <!-- เพิ่มการแสดงสถานะ -->
                                                    <?php if (isset($child['status'])): ?>
                                                        <p class="card-text"><strong>สถานะ:</strong> 
                                                            <span class="badge bg-<?=
                                                                ($child['status'] === 'มาเรียน' ? 'success' :
                                                                    ($child['status'] === 'ไม่มาเรียน' ? 'danger' :
                                                                        ($child['status'] === 'ลา' ? 'warning' :
                                                                            'secondary'))) ?>">
                                                                <?= htmlspecialchars($child['status']) ?>
                                                            </span>
                                                        </p>
                                                    <?php endif; ?>
                                                    <a href="../student/view_child.php?studentid=<?= htmlspecialchars($child['studentid']) ?>"
                                                        class="btn btn-primary btn-sm mt-auto">ดูรายละเอียด</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <p class="text-center">ไม่มีข้อมูลในห้องนี้</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">ไม่มีข้อมูลในกลุ่มนี้</p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">ไม่พบข้อมูลเด็ก</p>
            <?php endif; ?>
        </div>

    </div>
    </div>

</main>
</body>

</html>