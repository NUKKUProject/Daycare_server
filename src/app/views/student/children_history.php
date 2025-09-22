<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
// require_once __DIR__ . '/../../include/function/child_functions.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';

// รับค่า academic_year จาก URL
$currentAcademicYear = $_GET['academic_year'] ?? null;
$currentTab = $_GET['tab'] ?? 'all';

// ดึงข้อมูลปีการศึกษาทั้งหมด
$academicYears = getAcademicYears();

// ถ้ามีการเลือกปีการศึกษา ให้ดึงข้อมูลเด็กในปีการศึกษานั้น
if ($currentAcademicYear) {
    $data = getChildrenByGroupAndYear($currentTab, $currentAcademicYear);
} else {
    $data = [];
}

require_once __DIR__ . '/../../include/function/pages_referen.php';
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
                text: '<?php echo $message; ?>',
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

<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>

<link href="../../../public/assets/css/children_style.css" rel="stylesheet">

<style>
/* Main Layout */
.main-content {
    background-color: #f8f9fa;
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #26648E 0%, #1F4E6E 100%);
    padding: 2rem;
    border-radius: 15px;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.page-header h1 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
}

.page-header p {
    opacity: 0.8;
    margin-top: 0.5rem;
}

/* Action Buttons */
.children-action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-icon {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.2rem;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    background-color: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    background-color: rgba(255, 255, 255, 0.2);
}

/* Tabs */
.children-tabs {
    background: white;
    padding: 1rem;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
    border: none;
    gap: 0.5rem;
}

.children-tabs .nav-link {
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    color: #495057;
    font-weight: 500;
    transition: all 0.3s ease;
}

.children-tabs .nav-link:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.children-tabs .nav-link.active {
    background: #26648E;
    color: white;
}

/* Group Headers */
.group-header {
    font-size: 1.5rem;
    color: #26648E;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: 10px;
    margin: 2rem 0 1rem;
    border-left: 4px solid #26648E;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.classroom-header {
    font-size: 1.2rem;
    color: #495057;
    margin: 1rem 0;
    padding-left: 1rem;
    border-left: 3px solid #6c757d;
}

/* Child Cards */
.child-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: none;
}

.child-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.profile-image-wrapper {
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #f8f9fa;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.profile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.child-info {
    text-align: center;
}

.child-name {
    color: #26648E;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.child-details {
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.view-details-btn {
    width: 100%;
    border-radius: 25px;
    padding: 0.6rem;
    margin-top: 1rem;
    transition: all 0.3s ease;
}

.view-details-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
    }

    .children-action-buttons {
        flex-direction: column;
    }

    .btn-icon {
        width: 100%;
        justify-content: center;
    }

    .children-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
    }

    .children-tabs .nav-link {
        white-space: nowrap;
    }
}

/* Alert Styles */
.alert {
    border-radius: 10px;
    padding: 1rem;
    margin: 1rem 0;
    border: none;
    background-color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.alert-info {
    border-left: 4px solid #0dcaf0;
    color: #055160;
}

/* Add Child Modal */
.child-form-modal .modal-content {
    border-radius: 15px;
    border: none;
}

.child-form-modal .modal-header {
    background: #26648E;
    color: white;
    border-radius: 15px 15px 0 0;
}

.child-form-modal .modal-footer {
    border-top: none;
    padding: 1rem;
}

/* Export Modal Styles */
#exportModal .modal-content {
    border-radius: 15px;
    border: none;
}

#exportModal .modal-header {
    background: #198754;
    color: white;
    border-radius: 15px 15px 0 0;
}

#exportModal .form-check {
    margin-bottom: 0.5rem;
}

#exportModal .modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1rem;
}

#exportModal .form-select {
    border-radius: 8px;
}

#exportModal .form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

/* เพิ่มสไตล์สำหรับการ์ดปีการศึกษา */
.academic-year-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.academic-year-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.academic-year-icon {
    font-size: 3rem;
    color: #26648E;
}

.academic-year-title {
    color: #26648E;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.academic-year-status {
    margin-bottom: 1rem;
}

.view-year-btn {
    width: 100%;
    border-radius: 25px;
    padding: 0.6rem;
    transition: all 0.3s ease;
}

.view-year-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
</style>

<main class="main-content">
    <div class="child_history mt-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-1">ข้อมูลเด็กในระบบ</h1>
                <p class="mb-0">จัดการข้อมูลเด็กทั้งหมดในระบบ</p>
            </div>
            <div class="action-buttons children-action-buttons">
                <button class="btn btn-primary btn-icon" data-bs-toggle="modal" data-bs-target="#addChildModal">
                    <i class="bi bi-plus-circle"></i>
                    <span>เพิ่มข้อมูลเด็ก</span>
                </button>
                <button class="btn btn-success btn-icon" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="bi bi-file-earmark-excel"></i>
                    <span>Export ข้อมูล</span>
                </button>
                <a href="../admin/qr_codes_list.php" class="btn btn-primary btn-icon">
                    <i class="bi bi-qr-code"></i>
                    <span>รายการคิวอาร์โค้ด</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Academic Year Cards -->
    <?php if (!$currentAcademicYear): ?>
    <div class="row g-4">
        <?php foreach ($academicYears as $year): ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card academic-year-card h-100">
                    <div class="card-body text-center">
                        <div class="academic-year-icon mb-3">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <h3 class="academic-year-title"><?= htmlspecialchars($year['name']) ?></h3>
                        <p class="academic-year-status">
                            <span class="badge <?= $year['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $year['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                            </span>
                        </p>
                        <a href="?academic_year=<?= htmlspecialchars($year['name']) ?>" 
                           class="btn btn-primary view-year-btn">
                            <i class="bi bi-folder2-open me-2"></i>ดูข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Back Button -->
    <div class="mb-4">
        <a href="children_history.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>กลับไปหน้ารายการปีการศึกษา
        </a>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs children-tabs">
        <li class="nav-item">
            <a class="nav-link <?= $currentTab === 'all' ? 'active' : '' ?>" 
               href="?academic_year=<?= htmlspecialchars($currentAcademicYear) ?>&tab=all">
                <i class="bi bi-grid-3x3-gap me-2"></i>ทั้งหมด
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentTab === 'medium' ? 'active' : '' ?>" 
               href="?academic_year=<?= htmlspecialchars($currentAcademicYear) ?>&tab=medium">
                <i class="bi bi-people me-2"></i>เด็กกลาง
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentTab === 'big' ? 'active' : '' ?>" 
               href="?academic_year=<?= htmlspecialchars($currentAcademicYear) ?>&tab=big">
                <i class="bi bi-people me-2"></i>เด็กโต
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentTab === 'prep' ? 'active' : '' ?>" 
               href="?academic_year=<?= htmlspecialchars($currentAcademicYear) ?>&tab=prep">
                <i class="bi bi-people me-2"></i>เตรียมอนุบาล
            </a>
        </li>
    </ul>

    <!-- Classroom Tabs -->
    <?php if ($currentTab !== 'all'): ?>
    <div class="classroom-tabs mt-3">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?= !isset($_GET['classroom']) ? 'active' : '' ?>" 
                   href="?academic_year=<?= htmlspecialchars($currentAcademicYear) ?>&tab=<?= $currentTab ?>">
                    <i class="bi bi-grid me-2"></i>ทุกห้อง
                </a>
            </li>
            <?php
            // ดึงข้อมูลห้องเรียนตามกลุ่มที่เลือก
            $classrooms = getClassroomsByGroup($currentTab);
            foreach ($classrooms as $classroom): ?>
                <li class="nav-item">
                    <a class="nav-link <?= isset($_GET['classroom']) && $_GET['classroom'] === $classroom['classroom'] ? 'active' : '' ?>" 
                       href="?academic_year=<?= htmlspecialchars($currentAcademicYear) ?>&tab=<?= $currentTab ?>&classroom=<?= htmlspecialchars($classroom['classroom']) ?>">
                        <i class="bi bi-door-open me-2"></i><?= htmlspecialchars($classroom['classroom']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Children Cards -->
    <div class="container-fluid px-0">
        <?php foreach ($data as $groupData): ?>
            <h3 class="group-header"><?= htmlspecialchars($groupData['group']) ?></h3>
            <?php if (!empty($groupData['classrooms'])): ?>
                <?php foreach ($groupData['classrooms'] as $classroomData): ?>
                    <?php if (!isset($_GET['classroom']) || $_GET['classroom'] === $classroomData['classroom']): ?>
                        <h4 class="classroom-header">ห้อง: <?= htmlspecialchars($classroomData['classroom']) ?></h4>
                        <div class="row g-4">
                            <?php if (!empty($classroomData['children'])): ?>
                                <?php foreach ($classroomData['children'] as $child): ?>
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                        <div class="child-card card h-100">
                                            <div class="card-body">
                                                <div class="profile-image-wrapper">
                                                    <img src="<?= !empty($child['profile_image']) ?
                                                        htmlspecialchars($child['profile_image']) :
                                                        '../../../public/assets/images/avatar.png' ?>"
                                                        class="profile-image"
                                                        alt="Profile Image"
                                                        onerror="this.src='../../../public/assets/images/avatar.png'">
                                                </div>
                                                <div class="child-info">
                                                    <h5 class="child-name">
                                                        <?= htmlspecialchars($child['prefix_th']) ?>
                                                        <?= htmlspecialchars($child['firstname_th']) ?>
                                                        <?= htmlspecialchars($child['lastname_th']) ?>
                                                    </h5>
                                                    <p class="child-details"><strong>ชื่อเล่น:</strong> <?= htmlspecialchars($child['nickname']) ?></p>
                                                    <p class="child-details"><strong>กลุ่ม:</strong> <?= htmlspecialchars($child['child_group']) ?></p>
                                                    <p class="child-details"><strong>ห้องเรียน:</strong> <?= htmlspecialchars($child['classroom']) ?></p>
                                                    <a href="view_child.php?studentid=<?= htmlspecialchars($child['studentid']) ?>"
                                                       class="btn btn-primary view-details-btn">
                                                       <i class="bi bi-eye me-2"></i>ดูรายละเอียด
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle me-2"></i>ไม่มีข้อมูลในห้องนี้
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle me-2"></i>ไม่มีข้อมูลในกลุ่มนี้
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div>
</main>

    <!-- ฟอร์ม Modal สำหรับเพิ่มข้อมูลเด็ก -->
                    <div class="child-form-modal modal fade" id="addChildModal" tabindex="-1" aria-labelledby="addChildModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addChildModalLabel">เพิ่มข้อมูลเด็ก</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <!-- ใช้ PHP include -->
                                <?php include '../../include/form/form_addchild.php'; ?>
                            </div>
                        </div>
                    </div>

    <!-- เพิ่ม Modal สำหรับ Export ข้อมูล -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export ข้อมูลเด็ก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../../include/export/export_children.php" method="post">
                    <div class="modal-body">
                        <!-- เลือกกลุ่มเด็ก -->
                        <div class="mb-3">
                            <label class="form-label">กลุ่มเด็ก:</label>
                            <select name="child_group" class="form-select" required>
                                <option value="all">ทั้งหมด</option>
                                <option value="medium">เด็กกลาง</option>
                                <option value="big">เด็กโต</option>
                                <option value="prep">เตรียมอนุบาล</option>
                            </select>
                        </div>
                        
                        <!-- ข้อมูลพื้นฐานที่จำเป็น -->
                        <input type="hidden" name="fields[]" value="student_id">
                        <input type="hidden" name="fields[]" value="name">
                        <input type="hidden" name="fields[]" value="nickname">
                        <input type="hidden" name="fields[]" value="classroom">
                        
                        <!-- เลือกรูปแบบไฟล์ -->
                        <div class="mb-3">
                            <label class="form-label">รูปแบบไฟล์:</label>
                            <select name="format" class="form-select" disabled>
                                <option value="csv">CSV</option>
                            </select>
                            <small class="text-muted">ขณะนี้รองรับเฉพาะการ export เป็น CSV</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-download me-2"></i>Export
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>