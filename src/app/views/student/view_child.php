<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'student']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/function/child_functions.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';
require_once __DIR__ . '/../../include/function/vaccine_functions.php';
$is_admin = getUserRole() === 'admin';
$is_student = getUserRole() === 'student';
$is_teacher = getUserRole() === 'teacher';
// ดึงข้อมูลปีการศึกษาทั้งหมด
$academicYears = getAcademicYears();
// กำหนดค่า currentTab ตั้งแต่ต้น
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// แก้ไขการตรวจสอบ studentid ที่ส่งมา
$studentid = isset($_GET['studentid']) ? $_GET['studentid'] : null;

if (!$studentid) {
    echo "กรุณาระบุรหัสนักเรียน";
    exit;
}

// ดึงข้อมูลเด็กจากฐานข้อมูล
$child = getChildById($studentid);

if (!$child) {
    echo "ไม่พบข้อมูลนักเรียน";
    exit;
}

include __DIR__ . '/../../include/auth/auth_dashboard.php';

// เพิ่มการตรวจสอบที่ต้น view_child.php
if (getUserRole() === 'student') {
    // ตรวจสอบเฉพาะกรณีที่เป็น student
    $current_username = $_SESSION['username'];
    $stmt = $pdo->prepare("SELECT studentid FROM users WHERE username = :username AND role = 'student'");
    $stmt->execute(['username' => $current_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_studentid = $user ? $user['studentid'] : null;

    if ($studentid !== $user_studentid) {
        // ถ้าไม่ตรงกัน ให้ redirect กลับหน้า dashboard
        header('Location: /Daycare_system/project_kku/app/views/student/student_dashboard.php');
        exit;
    }
} elseif (getUserRole() === 'teacher') {
    // เพิ่มการตรวจสอบว่าเด็กอยู่ในความดูแลของครูคนนี้หรือไม่
    $pdo = getDatabaseConnection();
    $teacher_id = $_SESSION['user_id']; // สมมติว่ามี session user_id
    $stmt = $pdo->prepare("
        SELECT c.studentid 
        FROM children c
        JOIN teachers t ON 
            c.child_group = ANY(string_to_array(t.group_ids, ',')) AND
            c.classroom = ANY(string_to_array(t.classroom_ids, ','))
        WHERE t.teacher_id = :teacher_id AND c.studentid = :studentid
    ");
    $stmt->execute([
        'teacher_id' => $teacher_id,
        'studentid' => $studentid
    ]);

    if (!$stmt->fetch()) {
        // ถ้าไม่พบข้อมูล แสดงว่าครูคนนี้ไม่ได้ดูแลเด็กคนนี้
        header('Location: /Daycare_system/project_kku/app/views/teacher/teacher_dashboard.php');
        exit;
    }
}
// admin สามารถดูข้อมูลได้ทั้งหมด ไม่ต้องมีการตรวจสอบเพิ่มเติม


?>
<?php
// ตรวจสอบว่ามีสถานะถูกส่งมาหรือไม่
$status = isset($_GET['status']) ? $_GET['status'] : null;
$message = isset($_GET['message']) ? urldecode($_GET['message']) : null;
?>
<?php if (!empty($status) && !empty($message)): ?>
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

<link rel="stylesheet" href="../../../public/assets/css/view_child.css">

<main class="main-content">
    <!-- Profile Header Section -->
    <div class="profile-header">
        <div class="profile-header-content">
            <div class="d-flex align-items-center">
                <div class="profile-image-small">
                    <img src="<?= !empty($child['profile_image']) ?
                        htmlspecialchars($child['profile_image']) :
                        '../../../public/assets/images/avatar.png' ?>" alt="Profile" class="rounded-circle">
                </div>
                <div class="profile-info ms-3">
                    <h2><?= htmlspecialchars($child['prefix_th'] . $child['firstname_th'] . ' ' . $child['lastname_th']) ?>
                    </h2>
                    <p class="mb-0">รหัสนักเรียน: <?= htmlspecialchars($child['studentid']) ?></p>
                </div>
            </div>

            <div class="profile-actions">
                <?php if ($is_admin || $is_teacher): ?>
                    <!-- ปุ่ม Export ตามแท็บปัจจุบัน -->
                    <?php
                    $exportText = '';
                    $exportUrl = '';
                    switch($currentTab) {
                        case 'profile':
                            $exportText = 'Export ข้อมูลประวัติ';
                            $exportUrl = '../../include/export/export_child_profile.php';
                            break;
                        case 'vaccine':
                            $exportText = 'Export ข้อมูลวัคซีน';
                            $exportUrl = '../../include/export/export_child_vaccine.php';
                            break;
                        case 'attendance':
                            $exportText = 'Export ข้อมูลการเข้าเรียน';
                            $exportUrl = '../../include/export/export_child_attendance.php';
                            break;
                        case 'health':
                            $exportText = 'Export ข้อมูลสุขภาพ';
                            $exportUrl = '../../include/export/export_child_health.php';
                            break;
                    }
                    ?>
                    <form action="<?= $exportUrl ?>" method="post" class="d-inline">
                        <input type="hidden" name="studentid" value="<?= htmlspecialchars($studentid) ?>">
                        <button type="submit" class="btn btn-success btn-icon">
                            <i class="bi bi-file-earmark-excel"></i>
                            <span><?= $exportText ?></span>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                    <button onclick="generateQRCode('<?= htmlspecialchars($child['studentid']) ?>')" class="btn btn-light btn-icon">
                        <i class="bi bi-qr-code"></i>
                        <span>สร้าง QR Code</span>
                    </button>
                    <button id="editButton" class="btn btn-warning btn-icon">
                        <i class="bi bi-pencil"></i>
                        <span>แก้ไขข้อมูล</span>
                    </button>
                    <button onclick="confirmDelete('<?= htmlspecialchars($child['studentid']) ?>')" class="btn btn-danger btn-icon">
                        <i class="bi bi-trash"></i>
                        <span>ลบข้อมูล</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="profile-navigation">
        <?php
        $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
        ?>
        <ul class="nav nav-tabs profile-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'profile' ? 'active' : '' ?>"
                    href="?studentid=<?= htmlspecialchars($studentid) ?>&tab=profile">
                    <i class="bi bi-person-badge me-2"></i>
                    ประวัติประจำตัว
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'vaccine' ? 'active' : '' ?>"
                    href="?studentid=<?= htmlspecialchars($studentid) ?>&tab=vaccine">
                    <i class="bi bi-shield-check me-2"></i>
                    ประวัติการฉีดวัคซีน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'attendance' ? 'active' : '' ?>"
                    href="?studentid=<?= htmlspecialchars($studentid) ?>&tab=attendance">
                    <i class="bi bi-calendar-check me-2"></i>
                    ประวัติการมาเรียน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'health' ? 'active' : '' ?>"
                    href="?studentid=<?= htmlspecialchars($studentid) ?>&tab=health">
                    <i class="bi bi-heart-pulse me-2"></i>
                    ประวัติการตรวจร่างกาย
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'growth' ? 'active' : '' ?>" 
                href="?studentid=<?= htmlspecialchars($studentid) ?>&tab=growth">
                    <i class="bi bi-graph-up me-2"></i>
                    กราฟการเจริญเติบโต
                </a>
            </li>
        </ul>
    </div>


    <!-- Tab Contents -->
    <div class="tab-content">
        <?php if ($currentTab === 'profile'): ?>
            <!-- ข้อมูลประวัติประจำตัว -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="../../include/function/edit_child.php" method="POST" id="check" class="form-horizontal" enctype="multipart/form-data" novalidate>
                        <!-- เพิ่ม hidden field สำหรับ student_id -->
                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($child['studentid']) ?>">
                        
                        <!-- ส่วนรูปโปรไฟล์และข้อมูลพื้นฐาน -->
                        <div class="section-header">
                            <h5 class="mb-3"><i class="bi bi-person-circle me-2"></i>ข้อมูลพื้นฐาน</h5>
                        </div>
                                <div class="row mb-4">
                            <!-- รูปโปรไฟล์ -->
                                    <div class="col-md-3 text-center">
                                        <img id="profileImage"
                                            src="<?= !empty($child['profile_image']) ? htmlspecialchars($child['profile_image']) : '../../../public/assets/images/avatar.png' ?>"
                                            class="img-thumbnail mb-2" alt="Profile Image"
                                            style="width: 150px; height: 150px; object-fit: cover;">
                                        <?php if ($is_admin): ?>
                                            <input type="file" id="fileInput" name="profile_image" accept="image/*" onchange="handleImageSelect(this)" class="form-control">
                                            <input type="hidden" name="profile_image_data" id="profile_image_data">
                                        <?php endif; ?>
                                    </div>
                            <!-- ข้อมูลพื้นฐาน -->
                                    <div class="col-md-9">
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label class="form-label">รหัสนักเรียน:</label>
                                                <input type="text" class="form-control" name="student_id" 
                                                       value="<?= htmlspecialchars($child['studentid']) ?>" readonly>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">ปีการศึกษา</label>
                                                <select class="form-select" name="academic_year" required>
                                                    <?php foreach ($academicYears as $year): ?>
                                                        <option value="<?= htmlspecialchars($year['name']) ?>" <?= $child['academic_year'] == $year['name'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($year['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">กลุ่มเด็ก:</label>
                                                <select class="form-select" name="child_group" id="child_group" <?= !$is_admin ? 'disabled' : '' ?>>
                                                    <option value="เด็กกลาง" <?= ($child['child_group'] ?? '') === 'เด็กกลาง' ? 'selected' : '' ?>>เด็กกลาง</option>
                                                    <option value="เด็กโต" <?= ($child['child_group'] ?? '') === 'เด็กโต' ? 'selected' : '' ?>>เด็กโต</option>
                                                    <option value="เตรียมอนุบาล" <?= ($child['child_group'] ?? '') === 'เตรียมอนุบาล' ? 'selected' : '' ?>>เตรียมอนุบาล</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">ห้องเรียน:</label>
                                                <select class="form-select" name="classroom" id="classroom" <?= !$is_admin ? 'disabled' : '' ?>>
                                                    <option value="<?= htmlspecialchars($child['classroom']) ?>" selected>
                                                        <?= htmlspecialchars($child['classroom']) ?>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-3 col-form-label">ชื่อ-สกุล:</label>
                                            <div class="col-sm-3">
                                                <input class="form-control" name="prefix_th" id="TitleID_th_student"
                                                    type="text" value="<?= htmlspecialchars($child['prefix_th'] ?? '') ?>"
                                                    readonly>
                                            </div>
                                            <div class="col-sm-3">
                                                <input class="form-control" name="firstname_th" id="FirstName_th_student"
                                                    type="text"
                                                    value="<?= htmlspecialchars($child['firstname_th'] ?? '') ?>" readonly>
                                            </div>
                                            <div class="col-sm-3">
                                                <input class="form-control" name="lastname_th" id="LastName_th_student"
                                                    type="text" value="<?= htmlspecialchars($child['lastname_th'] ?? '') ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-3 col-form-label">ชื่อเล่น:</label>
                                            <div class="col-sm-9">
                                                <input class="form-control" name="nickname" id="Nickname" type="text"
                                                    value="<?= htmlspecialchars($child['nickname'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-3 col-form-label">Name-Surname:</label>
                                            <div class="col-sm-3">
                                                <input class="form-control" name="prefix_en" id="TitleID_eng_student"
                                                    type="text" value="<?= htmlspecialchars($child['prefix_en'] ?? '') ?>"
                                                    readonly>
                                            </div>
                                            <div class="col-sm-3">
                                                <input class="form-control" name="firstname_en" id="FirstName_eng_student"
                                                    type="text"
                                                    value="<?= htmlspecialchars($child['firstname_en'] ?? '') ?>" readonly>
                                            </div>
                                            <div class="col-sm-3">
                                                <input class="form-control" name="lastname_en" id="LastName_eng_student"
                                                    type="text" value="<?= htmlspecialchars($child['lastname_en'] ?? '') ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-3 col-form-label">วันเกิด:</label>
                                            <div class="col-sm-9">
                                                <?php
                                                // แปลงวันเกิดเป็น ค.ศ. สำหรับ input (ไม่ต้อง +543)
                                                $birthday = !empty($child['birthday']) ? date('d/m/Y', strtotime($child['birthday'])) : '';
                                                ?>
                                                <input class="form-control" name="birthday" id="Birthday" type="text" value="<?= htmlspecialchars($birthday) ?>" placeholder="วว/ดด/ปปปป" required>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- ส่วนข้อมูลผู้ปกครอง -->
                                    <div class="section-header">
                                            <h5 class="mb-3"><i class="bi bi-people-fill me-2"></i>ข้อมูลผู้ปกครอง</h5>
                                        </div>
                                        <div class="parent-relative-images mt-4">
    <h3>ผู้ปกครองและญาติ</h3>
    <div class="d-flex justify-content-around">
        <div class="text-center">
            <img src="<?= !empty($child['father_image']) ? htmlspecialchars($child['father_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Father" class="rounded-circle" style="width: 100px; height: 100px;">
            <p>พ่อ</p>
            <?php if ($is_admin): ?>
                <input type="file" name="father_image" class="form-control mt-2" accept="image/*">
            <?php endif; ?>
        </div>
        <div class="text-center">
            <img src="<?= !empty($child['mother_image']) ? htmlspecialchars($child['mother_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Mother" class="rounded-circle" style="width: 100px; height: 100px;">
            <p>แม่</p>
            <?php if ($is_admin): ?>
                <input type="file" name="mother_image" class="form-control mt-2" accept="image/*">
            <?php endif; ?>
        </div>
        <div class="text-center">
            <img src="<?= !empty($child['relative_image']) ? htmlspecialchars($child['relative_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Relative" class="rounded-circle" style="width: 100px; height: 100px;">
            <p>ญาติ</p>
            <?php if ($is_admin): ?>
                <input type="file" name="relative_image" class="form-control mt-2" accept="image/*">
            <?php endif; ?>
        </div>
    </div>
</div>
                                        <!-- ข้อมูลบิดา -->
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>ข้อมูลบิดา</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">ชื่อบิดา:</label>
                                                            <input type="text" class="form-control" name="father_first_name" 
                                                                value="<?= htmlspecialchars($child['father_first_name'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">นามสกุลบิดา:</label>
                                                            <input type="text" class="form-control" name="father_last_name" 
                                                                value="<?= htmlspecialchars($child['father_last_name'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="mb-3">
                                                            <label class="form-label">เบอร์โทรหลัก:</label>
                                                            <input type="tel" class="form-control" name="father_phone" 
                                                                value="<?= htmlspecialchars($child['father_phone'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="mb-3">
                                                            <label class="form-label">เบอร์โทรสำรอง:</label>
                                                            <input type="tel" class="form-control" name="father_phone_backup" 
                                                                value="<?= htmlspecialchars($child['father_phone_backup'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ข้อมูลมารดา -->
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>ข้อมูลมารดา</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">ชื่อมารดา:</label>
                                                            <input type="text" class="form-control" name="mother_first_name" 
                                                                value="<?= htmlspecialchars($child['mother_first_name'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">นามสกุลมารดา:</label>
                                                            <input type="text" class="form-control" name="mother_last_name" 
                                                                value="<?= htmlspecialchars($child['mother_last_name'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="mb-3">
                                                            <label class="form-label">เบอร์โทรหลัก:</label>
                                                            <input type="tel" class="form-control" name="mother_phone" 
                                                                value="<?= htmlspecialchars($child['mother_phone'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="mb-3">
                                                            <label class="form-label">เบอร์โทรสำรอง:</label>
                                                            <input type="tel" class="form-control" name="mother_phone_backup" 
                                                                value="<?= htmlspecialchars($child['mother_phone_backup'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- เพิ่มส่วนข้อมูลญาติ -->
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>ข้อมูลญาติ</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">ชื่อญาติ:</label>
                                                            <input type="text" class="form-control" name="relative_first_name" 
                                                                value="<?= htmlspecialchars($child['relative_first_name'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">นามสกุลญาติ:</label>
                                                            <input type="text" class="form-control" name="relative_last_name" 
                                                                value="<?= htmlspecialchars($child['relative_last_name'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="mb-3">
                                                            <label class="form-label">เบอร์โทรหลัก:</label>
                                                            <input type="tel" class="form-control" name="relative_phone" 
                                                                value="<?= htmlspecialchars($child['relative_phone'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="mb-3">
                                                            <label class="form-label">เบอร์โทรสำรอง:</label>
                                                            <input type="tel" class="form-control" name="relative_phone_backup" 
                                                                value="<?= htmlspecialchars($child['relative_phone_backup'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>

                                <hr>
                        <!-- ส่วนข้อมูลส่วนตัว -->
                        <div class="section-header">
                            <h5 class="mb-3"><i class="bi bi-person-vcard me-2"></i>ข้อมูลส่วนตัว</h5>
                        </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">เลขบัตรประชาชน:</label>
                                        <input class="form-control" name="id_card" id="Id_card" type="text"
                                            value="<?= htmlspecialchars($child['id_card'] ?? '') ?>" maxlength="20"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">สถานที่ออกบัตร:</label>
                                        <input class="form-control" name="issue_at" id="Issue_at" type="text"
                                            value="<?= htmlspecialchars($child['issue_at'] ?? '') ?>" maxlength="40"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">วันที่ออกบัตร:</label>
                                        <input class="form-control" name="issue_date" id="Issue_date" type="text"
                                            value="<?= htmlspecialchars($child['issue_date'] ?? '') ?>"
                                            placeholder="วว/ดด/ปปปป" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">วันหมดอายุ:</label>
                                        <input class="form-control" name="expiry_date" id="Expiry_date" type="text"
                                            value="<?= htmlspecialchars($child['expiry_date'] ?? '') ?>"
                                            placeholder="วว/ดด/ปปปป" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">เชื้อชาติ:</label>
                                        <input class="form-control" name="race" id="Race" type="text"
                                            value="<?= htmlspecialchars($child['race'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">สัญชาติ:</label>
                                        <input class="form-control" name="nationality" id="Nationality" type="text"
                                            value="<?= htmlspecialchars($child['nationality'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">ศาสนา:</label>
                                        <input class="form-control" name="religion" id="Religion" type="text"
                                            value="<?= htmlspecialchars($child['religion'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">อายุ:</label>
                                        <div class="input-group mb-1">
                                            <input class="form-control" name="age_years" id="age_years" type="number"
                                                value="<?= htmlspecialchars($child['age_years'] ?? '') ?>" required readonly>
                                            <span class="input-group-text">ปี</span>
                                        </div>
                                        <div class="input-group mb-1">
                                            <input class="form-control" name="age_months" id="age_months" type="number"
                                                value="<?= htmlspecialchars($child['age_months'] ?? '') ?>" required readonly>
                                            <span class="input-group-text">เดือน</span>
                                        </div>
                                        <div class="input-group mb-1">
                                            <input class="form-control" name="age_days" id="age_days" type="number"
                                                value="<?= htmlspecialchars($child['age_days'] ?? '') ?>" required readonly>
                                            <span class="input-group-text">วัน</span>
                                        </div>
                                        <input type="hidden" name="age_student" id="Age_student"
                                            value="<?= htmlspecialchars($child['age_student'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">วัน/เดือน/ปี เกิด:</label>
                                        <input class="form-control" name="birthday" id="Birthday" type="text"
                                            value="<?= htmlspecialchars($birthday) ?>" placeholder="วว/ดด/ปปปป" required>
                                    </div>
                                </div>
                                    <div class="col-md-4">
                                        <label class="form-label">สถานที่เกิด:</label>
                                        <input class="form-control" name="place_birth" id="Place_birth" type="text"
                                            value="<?= htmlspecialchars($child['place_birth'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">เพศ:</label>
                                        <select class="form-control" name="sex" id="Sex" required>
                                            <option value="ชาย" <?= $child['sex'] == 'ชาย' ? 'selected' : '' ?>>ชาย</option>
                                            <option value="หญิง" <?= $child['sex'] == 'หญิง' ? 'selected' : '' ?>>หญิง</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">ส่วนสูง:</label>
                                        <div class="input-group">
                                            <input class="form-control" name="height" id="Height" type="text"
                                                value="<?= htmlspecialchars($child['height'] ?? '') ?>" required>
                                            <span class="input-group-text">ซม.</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">น้ำหนัก:</label>
                                        <div class="input-group">
                                            <input class="form-control" name="weight" id="Weight" type="text"
                                                value="<?= htmlspecialchars($child['weight'] ?? '') ?>" required>
                                            <span class="input-group-text">กก.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">โรคประจำตัว:</label>
                                        <input class="form-control" name="congenital_disease" id="Congenital_disease"
                                            type="text" value="<?= htmlspecialchars($child['congenital_disease'] ?? '') ?>"
                                            required>
                                    </div>
                                </div>

                        <hr>
                        <!-- ส่วนข้อมูลสุขภาพ -->
                        <div class="section-header">
                            <h5 class="mb-3"><i class="bi bi-heart-pulse me-2"></i>ข้อมูลสุขภาพ</h5>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label">กรุ๊ปเลือด:</label>
                                <select class="form-select" name="blood_type">
                                    <option value="">เลือกกรุ๊ปเลือด</option>
                                    <option value="A" <?= ($child['blood_type'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= ($child['blood_type'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="O" <?= ($child['blood_type'] ?? '') === 'O' ? 'selected' : '' ?>>O</option>
                                    <option value="AB" <?= ($child['blood_type'] ?? '') === 'AB' ? 'selected' : '' ?>>AB</option>
                                </select>
                            </div>
                        </div>

                         <div class="section-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-3"><i class="bi bi-capsule me-2"></i>ข้อมูลการแพ้ยา</h5>
                                <?php if ($is_admin): ?>
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-allergy-btn"
                                            onclick="editAllergies('<?= htmlspecialchars($studentid) ?>', 'drug')"
                                            style="display: none;">
                                        <i class="bi bi-pencil-square"></i> แก้ไขข้อมูลแพ้ยา
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                        <!-- ส่วนของข้อมูลการแพ้ยา -->
                        <div class="section mb-4" id="drugAllergySection">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">ประวัติการแพ้ยา <span class="text-danger">*</span></label>
                                        <select class="form-select" name="has_drug_allergy_history" required disabled>
                                            <option value="0">ไม่มีประวัติการแพ้ยา</option>
                                            <option value="1">มีประวัติการแพ้ยา</option>
                                        </select>
                                    </div>

                                    <div class="drug-allergy-details" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">ชื่อยา <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="drug_name" required disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">วิธีที่ทราบว่าแพ้ <span class="text-danger">*</span></label>
                                            <select class="form-select" name="drug_detection_method" required disabled>
                                                <option value="">เลือกวิธี</option>
                                                <option value="symptoms_after_use">มีอาการแพ้หลังจากใช้ยา</option>
                                                <option value="skin_testing">การทดสอบทางผิวหนัง (skin testing)</option>
                                                <option value="blood_test">ทดสอบโดยการเจาะเลือด</option>
                                                <option value="repeat_use">ทดสอบโดยการใช้ยาซ้ำ</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">ลักษณะอาการแพ้ <span class="text-danger">*</span></label>
                                            <select class="form-select" name="drug_symptoms" required disabled>
                                                <option value="">เลือกอาการ</option>
                                                <option value="type1">ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก</option>
                                                <option value="type2">ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก และหายใจลำบาก</option>
                                                <option value="type3">ผื่นแดงลักษณะเป็นผื่นราบ และผื่นนูน กระจายอย่างสมมาตร</option>
                                                <option value="type4">ผิวแดงทั่วตัวและผื่นตุ่มหนองขนาดเล็กจำนวนมาก</option>
                                                <option value="type5">ผื่นที่เกิดขึ้นสามารถพบได้หลายแบบ</option>
                                                <option value="type6">ผื่นตุ่มน้ำ มีผิวหนังกำพร้าตายและหลุดลอก</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">บัตรแพ้ยา <span class="text-danger">*</span></label>
                                            <select class="form-select" name="has_drug_allergy_card" required disabled>
                                                <option value="">เลือกสถานะ</option>
                                                <option value="1">มีบัตรแพ้ยา</option>
                                                <option value="0">ไม่มีบัตรแพ้ยา</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <div class="section-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-3"><i class="bi bi-egg-fried me-2"></i>ข้อมูลการแพ้อาหาร</h5>
                                <?php if ($is_admin): ?>
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-allergy-btn"
                                            onclick="editAllergies('<?= htmlspecialchars($studentid) ?>', 'food')"
                                            style="display: none;">
                                        <i class="bi bi-pencil-square"></i> แก้ไขข้อมูลแพ้อาหาร
                                    </button>
                                <?php endif; ?>
                            </div>
                        <!-- ส่วนข้อมูลการแพ้อาหาร -->
                        <div class="section mb-4" id="foodAllergySection">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">ประวัติการแพ้อาหาร <span class="text-danger">*</span></label>
                                        <select class="form-select" name="has_food_allergy_history" required disabled>
                                            <option value="0">ไม่มีประวัติการแพ้อาหาร</option>
                                            <option value="1">มีประวัติการแพ้อาหาร</option>
                                        </select>
                                    </div>

                                    <div class="food-allergy-details" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">ชื่ออาหาร <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="food_name" required disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">วิธีที่ทราบว่าแพ้ <span class="text-danger">*</span></label>
                                            <select class="form-select" name="food_detection_method" required disabled>
                                                <option value="">เลือกวิธี</option>
                                                <option value="symptoms_after_eat">มีอาการแพ้หลังรับประทานอาหารชนิดนั้น</option>
                                                <option value="skin_testing">การทดสอบทางผิวหนัง (skin testing)</option>
                                                <option value="blood_test">ทดสอบโดยการเจาะเลือด</option>
                                                <option value="repeat_eat">ทดสอบโดยการรับประทานอาหารชนิดนั้นซ้ำ</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">อาการแพ้</label>
                                            <div class="ms-3">
                                                <h6 class="mb-2">อาการทางระบบทางเดินอาหาร:</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="digestive_symptoms[]" value="bloody_stool" disabled>
                                                    <label class="form-check-label">ถ่ายเป็นมูกเลือดเป็น ๆ หาย ๆ</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="digestive_symptoms[]" value="vomiting" disabled>
                                                    <label class="form-check-label">อาเจียน</label>
                                                </div>

                                                <h6 class="mb-2 mt-3">อาการทางผิวหนัง:</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skin_symptoms[]" value="urticaria" disabled>
                                                    <label class="form-check-label">ผื่นลมพิษทั่วตัว</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skin_symptoms[]" value="eye_swelling" disabled>
                                                    <label class="form-check-label">ตาบวม</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skin_symptoms[]" value="mouth_rash" disabled>
                                                    <label class="form-check-label">มีผื่นรอบปาก</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skin_symptoms[]" value="atopic_dermatitis" disabled>
                                                    <label class="form-check-label">มีผื่นคันเป็น ๆ หาย ๆ ขึ้นที่บริเวณแก้ม ด้านนอกแขน ข้อศอก ข้อมือ ที่เรียกว่า ผื่นภูมิแพ้ผิวหนัง</label>
                                                </div>

                                                <h6 class="mb-2 mt-3">อาการทางระบบทางเดินหายใจ:</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="respiratory_symptoms[]" value="wheezing" disabled>
                                                    <label class="form-check-label">ครืดคราดมีเสมหะ</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="respiratory_symptoms[]" value="runny_nose" disabled>
                                                    <label class="form-check-label">น้ำมูกใสไหล</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="respiratory_symptoms[]" value="nasal_congestion" disabled>
                                                    <label class="form-check-label">คัดจมูก</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="respiratory_symptoms[]" value="breathing_difficulty" disabled>
                                                    <label class="form-check-label">หายใจติดขัด มีเสียงวี้ด หายใจเหนื่อย</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


<!-- โหลด Flatpickr และภาษาไทย -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
function calculateAge(day, month, yearBE) {
    // แปลงปี พ.ศ. เป็น ค.ศ. ก่อนคำนวณ
    const year = parseInt(yearBE) - 543;
    const birthDate = new Date(year, parseInt(month) - 1, parseInt(day));
    const today = new Date();

    let years = today.getFullYear() - birthDate.getFullYear();
    let months = today.getMonth() - birthDate.getMonth();
    let days = today.getDate() - birthDate.getDate();

    if (days < 0) {
        const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        days += lastMonth.getDate();
        months--;
    }
    if (months < 0) {
        years--;
        months += 12;
    }
    if (today.getMonth() < birthDate.getMonth() ||
        (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) {
        years--;
    }

    document.getElementById("age_years").value = years;
    document.getElementById("age_months").value = months;
    document.getElementById("age_days").value = days;
    document.getElementById("Age_student").value = `${years} ปี ${months} เดือน ${days} วัน`;
}

flatpickr("#Birthday", {
    dateFormat: "d/m/Y",
    locale: "th",
    // สมมติ value ของ input เป็น yyyy-mm-dd (เก็บปี ค.ศ. จากฐานข้อมูล)
    defaultDate: (() => {
        const input = document.getElementById("Birthday").value;
        if (!input) return null;

        // input format ที่มาจาก DB เช่น 2020-02-02
        const [year, month, day] = input.split('-');

        // สร้างวันที่ในรูปแบบ ค.ศ. ให้ flatpickr
        return `${year}-${month}-${day}`;
    })(),

    onChange: function (selectedDates, dateStr, instance) {
        if (selectedDates.length > 0) {
            const date = selectedDates[0];
            const day = ("0" + date.getDate()).slice(-2);
            const month = ("0" + (date.getMonth() + 1)).slice(-2);
            const yearBE = date.getFullYear() + 543; // แปลงเป็นปี พ.ศ. แสดงใน input
            instance.input.value = `${day}/${month}/${yearBE}`;
        }
    },

    onReady: function (selectedDates, dateStr, instance) {
        if (selectedDates.length > 0) {
            const date = selectedDates[0];
            const day = ("0" + date.getDate()).slice(-2);
            const month = ("0" + (date.getMonth() + 1)).slice(-2);
            const yearBE = date.getFullYear() + 543;
            instance.input.value = `${day}/${month}/${yearBE}`;
        }
    }
});


</script>


<script>
document.getElementById('child_group').addEventListener('change', function() {
    const group = this.value;
    const classroomSelect = document.getElementById('classroom');
    classroomSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
    fetch('/app/include/function/get_classrooms.php?child_group=' + encodeURIComponent(group))
        .then(response => response.json())
        .then(data => {
            classroomSelect.innerHTML = '<option value=\"\">-- เลือกห้องเรียน --</option>';
            data.forEach(item => {
                // รองรับทั้งกรณีที่ return เป็น {classroom: ...} หรือ {classroom_name: ...}
                const value = item.classroom || item.classroom_name;
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                classroomSelect.appendChild(option);
            });
        });
});
</script>

<script>
// เพิ่มฟังก์ชันสำหรับควบคุมการแสดง/ซ่อนรายละเอียดการแพ้
function toggleAllergyDetails(type) {
    const historySelect = document.querySelector(`select[name="has_${type}_allergy_history"]`);
    const detailsDiv = document.querySelector(`.${type}-allergy-details`);
    
    if (historySelect.value === "1") {
        detailsDiv.style.display = "block";
    } else {
        detailsDiv.style.display = "none";
    }
}

// เพิ่ม event listeners สำหรับการเปลี่ยนแปลงค่า
document.addEventListener('DOMContentLoaded', function() {
    const drugHistorySelect = document.querySelector('select[name="has_drug_allergy_history"]');
    const foodHistorySelect = document.querySelector('select[name="has_food_allergy_history"]');
    
    if (drugHistorySelect) {
        drugHistorySelect.addEventListener('change', function() {
            toggleAllergyDetails('drug');
        });
    }
    
    if (foodHistorySelect) {
        foodHistorySelect.addEventListener('change', function() {
            toggleAllergyDetails('food');
        });
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const editButton = document.getElementById("editButton");
    const saveButton = document.getElementById("saveButton");
    const cancelButton = document.getElementById("cancelButton");
    const editAllergyButtons = document.querySelectorAll(".edit-allergy-btn");

    // เลือก input และ select ทั้งหมดในฟอร์ม รวมถึงส่วนแพ้ยา
    const formInputs = document.querySelectorAll("#check input, #check select, #check textarea");
    const allergyInputs = document.querySelectorAll('#drugAllergySection input, #drugAllergySection select, #foodAllergySection input, #foodAllergySection select');

    // รวม input ทั้งหมดเข้าด้วยกัน
    const allInputs = [...formInputs, ...allergyInputs];

    // ฟังก์ชันล็อค input
    function lockInputs() {
        allInputs.forEach(input => {
            input.setAttribute("readonly", true);
            input.setAttribute("disabled", true);
        });
        editAllergyButtons.forEach(button => button.style.display = "none");
    }

    // ฟังก์ชันปลดล็อค input
    function unlockInputs() {
        allInputs.forEach(input => {
            if (input.hasAttribute('name')) {
                input.removeAttribute("readonly");
                input.removeAttribute("disabled");
            }
        });
        editAllergyButtons.forEach(button => button.style.display = "inline-block");
    }

    // สถานะเริ่มต้น: input ทั้งหมดเป็น readonly และ disabled
    lockInputs();

    // เมื่อคลิกปุ่มแก้ไข
    if (editButton) {
        editButton.addEventListener("click", () => {
            unlockInputs();
            saveButton.style.display = "inline-block";
            cancelButton.style.display = "inline-block";
            editButton.style.display = "none";
        });
    }

    // เมื่อคลิกปุ่มยกเลิก
    if (cancelButton) {
        cancelButton.addEventListener("click", () => {
            lockInputs();
            saveButton.style.display = "none";
            cancelButton.style.display = "none";
            editButton.style.display = "inline-block";
        });
    }

    // โหลดข้อมูลการแพ้
    loadAllergiesData();
});

    // ฟังก์ชันโหลดข้อมูลการแพ้
    function loadAllergiesData() {
        
        const studentId = '<?php echo $studentid; ?>';
        
        // โหลดข้อมูลการแพ้ทั้งหมด
        Promise.all([
            fetch('../../include/process/manage_allergies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get',
                    type: 'drug',
                    student_id: studentId
                })  
            }).then(response => response.json()),
            fetch('../../include/process/manage_allergies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get',
                    type: 'food',
                    student_id: studentId
                })
            }).then(response => response.json())
        ])
        .then(([drugData, foodData]) => {
            console.log('Drug Data:', drugData);
            console.log('Food Data:', foodData);
            
            const allergiesData = {
                drug: drugData.status === 'success' ? drugData.data[0] : null,
                food: foodData.status === 'success' ? foodData.data[0] : null
            };
            displayAllergiesData(allergiesData);
        })
        .catch(error => {
            console.error('Error loading allergies data:', error);
        });
    }

function displayAllergiesData(data) {
    const drugAllergySection = document.getElementById('drugAllergySection');
    if (drugAllergySection) {
        if (data.drug && Object.keys(data.drug).length > 0) {
            drugAllergySection.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>พบข้อมูลการแพ้ยา</strong>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ชื่อยาที่แพ้:</strong> ${data.drug.drug_name || '-'}</p>
                                <p><strong>วิธีที่ทราบว่าแพ้:</strong> ${getDetectionMethodText(data.drug.detection_method)}</p>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>ลักษณะอาการ:</strong>
                                    <ul class="list-unstyled ms-3">
                                        <li><i class="bi bi-dot"></i>${getDrugSymptomsText(data.drug.symptoms)}</li>
                                    </ul>
                                </div>
                                <div class="mb-3">
                                    <strong>บัตรแพ้ยา:</strong> ${data.drug.has_allergy_card ? 'มี' : 'ไม่มี'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

        const foodAllergySection = document.getElementById('foodAllergySection');
        if (foodAllergySection) {
            if (data.food && Object.keys(data.food).length > 0) {
                let digestiveSymptoms = [];
                let skinSymptoms = [];
                let respiratorySymptoms = [];

                try {
                    if (data.food.digestive_symptoms) {
                        digestiveSymptoms = typeof data.food.digestive_symptoms === 'string' 
                            ? data.food.digestive_symptoms.replace(/{|}/g, '').split(',').filter(Boolean)
                            : data.food.digestive_symptoms;
                    }
                    if (data.food.skin_symptoms) {
                        skinSymptoms = typeof data.food.skin_symptoms === 'string'
                            ? data.food.skin_symptoms.replace(/{|}/g, '').split(',').filter(Boolean)
                            : data.food.skin_symptoms;
                    }
                    if (data.food.respiratory_symptoms) {
                        respiratorySymptoms = typeof data.food.respiratory_symptoms === 'string'
                            ? data.food.respiratory_symptoms.replace(/{|}/g, '').split(',').filter(Boolean)
                            : data.food.respiratory_symptoms;
                    }

                    foodAllergySection.innerHTML = `
                        <div class="card">
                            <div class="card-body">
                                <div class="alert alert-warning" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>พบข้อมูลการแพ้อาหาร</strong>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>ชื่ออาหารที่แพ้:</strong> ${data.food.food_name || '-'}</p>
                                        <p><strong>วิธีที่ทราบว่าแพ้:</strong> ${getDetectionMethodText(data.food.detection_method)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong>อาการที่พบ:</strong>
                                            ${digestiveSymptoms.length > 0 ? `
                                                <div class="mt-2">
                                                    <p class="mb-1 text-primary">
                                                        <i class="bi bi-activity me-1"></i>อาการทางระบบทางเดินอาหาร:
                                                    </p>
                                                    <ul class="list-unstyled ms-3">
                                                        ${digestiveSymptoms.map(symptom => `
                                                            <li>
                                                                <i class="bi bi-dot text-warning"></i>
                                                                <span class="fw-medium">${getAllergySymptomText(symptom).split('(')[0]}</span>
                                                                ${getAllergySymptomText(symptom).includes('(') ? 
                                                                    `<small class="text-muted">(${getAllergySymptomText(symptom).split('(')[1]}</small>` : 
                                                                    ''}
                                                            </li>
                                                        `).join('')}
                                                    </ul>
                                                </div>
                                            ` : ''}
                                            ${skinSymptoms.length > 0 ? `
                                                <div class="mt-2">
                                                    <p class="mb-1 text-primary">อาการทางผิวหนัง:</p>
                                                    <ul class="list-unstyled ms-3">
                                                        ${skinSymptoms.map(symptom => 
                                                            `<li><i class="bi bi-dot"></i>${getAllergySymptomText(symptom)}</li>`
                                                        ).join('')}
                                                    </ul>
                                                </div>
                                            ` : ''}
                                            ${respiratorySymptoms.length > 0 ? `
                                                <div class="mt-2">
                                                    <p class="mb-1 text-primary">อาการทางระบบทางเดินหายใจ:</p>
                                                    <ul class="list-unstyled ms-3">
                                                        ${respiratorySymptoms.map(symptom => 
                                                            `<li><i class="bi bi-dot"></i>${getAllergySymptomText(symptom)}</li>`
                                                        ).join('')}
                                                    </ul>
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } catch (error) {
                    console.error('Error displaying food allergy data:', error);
                }
            } else {
                // กรณีไม่มีข้อมูล คงเดิม
            }
        }
    }

    // ฟังก์ชันแปลงข้อความ
    function getDetectionMethodText(method) {
        const methods = {
            'symptoms_after_use': 'มีอาการแพ้หลังจากใช้ยา',
            'skin_testing': 'การทดสอบทางผิวหนัง',
            'blood_test': 'ทดสอบโดยการเจาะเลือด',
            'repeat_use': 'ทดสอบโดยการใช้ยาซ้ำ',
            'symptoms_after_eat': 'มีอาการแพ้หลังรับประทานอาหาร',
            'repeat_eat': 'ทดสอบโดยการรับประทานอาหารซ้ำ'
        };
        return methods[method] || method;
    }

    function getDrugSymptomsText(type) {
        const symptoms = {
            'type1': 'ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก',
            'type2': 'ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก และหายใจลำบาก',
            'type3': 'ผื่นแดงลักษณะเป็นผื่นราบ และผื่นนูน กระจายอย่างสมมาตร',
            'type4': 'ผิวแดงทั่วตัวและผื่นตุ่มหนองขนาดเล็กจำนวนมาก',
            'type5': 'ผื่นที่เกิดขึ้นสามารถพบได้หลายแบบ',
            'type6': 'ผื่นตุ่มน้ำ มีผิวหนังกำพร้าตายและหลุดลอก'
        };
        return symptoms[type] || type;
    }

    function getAllergySymptomText(symptom) {
        const symptoms = {
            // อาการทางระบบทางเดินอาหาร
            'bloody_stool': 'ถ่ายเป็นมูกเลือดเป็น ๆ หาย ๆ (อาการรุนแรงควรพบแพทย์)',
            'vomiting': 'อาเจียน (อาจมีอาการคลื่นไส้ร่วมด้วย)',
            'diarrhea': 'ท้องเสีย (ถ่ายเหลวมากกว่า 3 ครั้งต่อวัน)',
            'stomach_pain': 'ปวดท้อง (อาการปวดบิดเป็นพัก ๆ)',
            'nausea': 'คลื่นไส้ (รู้สึกไม่สบายท้อง)',
            
            // อาการทางผิวหนัง
            'urticaria': 'ผื่นลมพิษทั่วตัว (ผื่นนูนแดงคัน)',
            'eye_swelling': 'ตาบวม (อาจมีอาการคันร่วมด้วย)',
            'mouth_rash': 'มีผื่นรอบปาก (มักพบหลังรับประทานอาหารที่แพ้)',
            'atopic_dermatitis': 'ผื่นภูมิแพ้ผิวหนัง (มีผื่นคันเป็น ๆ หาย ๆ ขึ้นที่บริเวณแก้ม ด้านนอกแขน ข้อศอก ข้อมือ ที่เรียกว่า ผื่นภูมิแพ้ผิวหนัง)',
            'skin_rash': 'ผื่นแดงตามผิวหนัง (อาจมีอาการคันร่วมด้วย)',
            'itching': 'คันตามผิวหนัง (โดยไม่มีผื่น)',
            
            // อาการทางระบบทางเดินหายใจ
            'wheezing': 'หายใจมีเสียงวี้ด (อาการหายใจลำบาก มีเสียงดัง)',
            'runny_nose': 'น้ำมูกไหล (มักมีอาการคัดจมูกร่วมด้วย)',
            'nasal_congestion': 'คัดจมูก (รู้สึกอึดอัด หายใจไม่สะดวก)',
            'breathing_difficulty': 'หายใจลำบาก (มีเสียงวี้ด รู้สึกเหนื่อยง่าย หายใจไม่อิ่ม)',
            'coughing': 'ไอ (อาจเป็นไอแห้งหรือมีเสมหะ)',
            'throat_tightness': 'รู้สึกแน่นคอ (คล้ายมีอะไรมาจุกที่คอ)'
        };
        return symptoms[symptom] || symptom;
    }
</script>

<style>
.allergy-item {
    background-color: #fff;
    transition: all 0.3s ease;
}

.allergy-item:hover {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
}

.allergy-item ul {
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
}

.allergy-item li {
    margin-bottom: 0.25rem;
}

.card-header {
    background: linear-gradient(to right, #f8f9fa, white);
    border-bottom: none;
}

.card-header h5 {
    color: #2c3e50;
}

.card-header i {
    color: #3498db;
}
</style>
                        <hr>
                        <!-- ส่วนข้อมูลที่อยู่ -->
                        <div class="section-header">
                            <h5 class="mb-3"><i class="bi bi-house-door me-2"></i>ข้อมูลที่อยู่</h5>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">ที่อยู่:</label>
                                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($child['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">ตำบล/แขวง:</label>
                                <input type="text" class="form-control" name="district" 
                                       value="<?= htmlspecialchars($child['district'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">อำเภอ/เขต:</label>
                                <input type="text" class="form-control" name="amphoe" 
                                       value="<?= htmlspecialchars($child['amphoe'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">จังหวัด:</label>
                                <input type="text" class="form-control" name="province" 
                                       value="<?= htmlspecialchars($child['province'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">รหัสไปรษณีย์:</label>
                                <input type="text" class="form-control" name="zipcode" maxlength="5" 
                                       value="<?= htmlspecialchars($child['zipcode'] ?? '') ?>">
                            </div>
                        </div>

                        <hr>
                        <!-- ส่วนข้อมูลผู้ติดต่อฉุกเฉิน -->
                        <div class="section-header">
                            <h5 class="mb-3"><i class="bi bi-telephone-fill me-2"></i>ข้อมูลผู้ติดต่อฉุกเฉิน</h5>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">ชื่อผู้ติดต่อฉุกเฉิน:</label>
                                <input type="text" class="form-control" name="emergency_contact" 
                                       value="<?= htmlspecialchars($child['emergency_contact'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">เบอร์โทรฉุกเฉิน:</label>
                                <input type="tel" class="form-control" name="emergency_phone" 
                                       value="<?= htmlspecialchars($child['emergency_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ความสัมพันธ์:</label>
                                <input type="text" class="form-control" name="emergency_relation" 
                                       value="<?= htmlspecialchars($child['emergency_relation'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ปุ่มบันทึกและยกเลิก -->
                        <div class="text-end mt-4">
                            <button id="saveButton" type="submit" class="btn btn-success" style="display: none;">
                                <i class="bi bi-save me-2"></i>บันทึกข้อมูล
                                </button>
                            <button id="cancelButton" type="button" class="btn btn-secondary" style="display: none;">
                                <i class="bi bi-x-circle me-2"></i>ยกเลิก
                                </button>
                        </div>
                            </form>
                </div>
            </div>
        </div>

        <script>
function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // ตรวจสอบขนาดไฟล์
        if (file.size > 5 * 1024 * 1024) {
            alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                
                // ลดขนาดลงเหลือ 600px
                let width = img.width;
                let height = img.height;
                const maxSize = 600;

                if (width > height) {
                    if (width > maxSize) {
                        height *= maxSize / width;
                        width = maxSize;
                    }
                } else {
                    if (height > maxSize) {
                        width *= maxSize / height;
                        height = maxSize;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                // วาดรูปลงใน canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // ลดคุณภาพลงเหลือ 0.5
                const base64 = canvas.toDataURL('image/jpeg', 0.5);
                
                // เก็บข้อมูล base64
                document.getElementById('profile_image_data').value = base64;
                
                // แสดงตัวอย่างรูป
                document.getElementById('profileImage').src = base64;
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// เพิ่มการตรวจสอบก่อนส่งฟอร์ม
document.getElementById('addChildForm').onsubmit = function(e) {
    const imageData = document.getElementById('profile_image_data').value;
    if (imageData) {
        // ตรวจสอบขนาดข้อมูล base64
        const base64Size = Math.ceil((imageData.length * 3) / 4);
        if (base64Size > 5 * 1024 * 1024) {
            alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
            e.preventDefault();
            return false;
        }
    }
    return true;
};
</script>

    <?php elseif ($currentTab === 'vaccine'): ?>
        <!-- ข้อมูลประวัติการฉีดวัคซีน -->
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- ปุ่มเพิ่มรายการวัคซีน -->
                <?php if ($is_admin): ?>
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" onclick="addVaccineList()">
                            <i class="bi bi-plus-circle me-2"></i>เพิ่มรายการวัคซีน
                        </button>
                    </div>
                    <div class="mb-3">
                    <button type="button" class="btn btn-success" onclick="addAgeGroup()">
                        <i class="bi bi-plus-circle me-2"></i>เพิ่มช่วงอายุ
                    </button>
                    </div>
                <?php endif; ?>

                <!-- ตารางแสดงรายการวัคซีน -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="15%">อายุที่ควรได้รับ</th>
                                <th width="60%">วัคซีนที่ได้รับ</th>
                                <th width="15%">วันที่ได้รับวัคซีน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $age_groups = getVaccineAgeGroups();
                            foreach ($age_groups as $group): ?>
                                <tr>
                                <td width="15%">
                                        <?= htmlspecialchars($group['age_group']) ?>
                                        <?php if ($is_admin): ?>
                                            <div class="btn-group btn-group-sm mt-1">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editAgeGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['age_group']) ?>', <?= $group['display_order'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteAgeGroup(<?= $group['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $vaccines = getVaccinesByAgeGroup($group['id']);
                                        if (!empty($vaccines)):
                                            foreach ($vaccines as $vaccine): 
                                                $vaccine_record = getVaccineRecord($studentid, $vaccine['id']);
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><?= htmlspecialchars($vaccine['vaccine_name']) ?></span>
                                                <div class="btn-group">
                                                    <?php if ($is_admin): ?>
                                                        <button class="btn btn-sm btn-primary" onclick="editVaccineList(<?= $vaccine['id'] ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteVaccineList(<?= $vaccine['id'] ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($vaccines) && end($vaccines) !== $vaccine): ?>
                                                <hr class="my-2">
                                            <?php endif; ?>
                                        <?php 
                                            endforeach;
                                        else: ?>
                                            <div class="text-muted">
                                                <i class="bi bi-info-circle me-2"></i>ยังไม่มีรายการวัคซีนในกลุ่มอายุนี้
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($vaccines)):
                                            foreach ($vaccines as $vaccine): 
                                                $vaccine_record = getVaccineRecord($studentid, $vaccine['id']);
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php if (isset($vaccine_record) && $vaccine_record): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?= date('d/m/Y', strtotime($vaccine_record['vaccine_date'])) ?></span>
                                                        <div class="btn-group">
                                                            <button class="btn btn-sm btn-info" onclick="viewVaccineDetails(<?= $vaccine_record['id'] ?>)">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <?php if ($is_admin): ?>
                                                                <button class="btn btn-sm btn-warning" onclick="editVaccineRecord(<?= $vaccine_record['id'] ?>)">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger" onclick="deleteVaccineRecord(<?= $vaccine_record['id'] ?>)">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                                <?php elseif ($is_teacher): ?>
                                                                <button class="btn btn-sm btn-warning" onclick="editVaccineRecord(<?= $vaccine_record['id'] ?>)">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="text-muted me-2">
                                                            <i class="bi bi-dash-circle me-1"></i>ยังไม่ได้รับวัคซีน
                                                        </span>
                                                        <?php if ($is_admin || $is_teacher|| $is_student): ?>
                                                            <button class="btn btn-sm btn-success" onclick="addVaccineRecord(<?= $vaccine['id'] ?>)">
                                                                <i class="bi bi-plus-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($vaccines) && end($vaccines) !== $vaccine): ?>
                                                <hr class="my-2">
                                            <?php endif; ?>
                                        <?php 
                                            endforeach;
                                        endif; 
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal เพิ่มรายการวัคซีน -->
                <div class="modal fade" id="vaccineListModal" tabindex="-1" aria-labelledby="vaccineListModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="vaccineListModalLabel">
                                    <i class="bi bi-plus-circle me-2"></i>เพิ่มรายการวัคซีน
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="vaccineListForm" class="needs-validation" novalidate>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">
                                            <i class="bi bi-calendar-event me-2"></i>กลุ่มอายุ
                                        </label>
                                        <select class="form-select form-select-lg shadow-sm" id="ageGroup" name="ageGroup" required>
                                            <option value="">เลือกกลุ่มอายุ</option>
                                            <?php foreach (getVaccineAgeGroups() as $group): ?>
                                                <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['age_group']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">กรุณาเลือกกลุ่มอายุ</div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">
                                            <i class="bi bi-shield-fill-check me-2"></i>ชื่อวัคซีน
                                        </label>
                                        <div class="input-group input-group-lg shadow-sm">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-pencil"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="vaccineName" 
                                                   name="vaccine_name" 
                                                   placeholder="กรุณากรอกชื่อวัคซีน"
                                                   required>
                                        </div>
                                        <div class="form-text text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            ตัวอย่าง: ฉีดวัคซีนป้องกันวัณโรค (BCG)
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">
                                            <i class="bi bi-card-text me-2"></i>รายละเอียดวัคซีน(ถ้ามี)
                                        </label>
                                        <div class="input-group input-group-lg shadow-sm">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-info-circle"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="vaccineDescription" 
                                                   name="vaccine_description" 
                                                   placeholder="รายละเอียดเพิ่มเติมวัคซีน">
                                        </div>
                                        <div class="form-text text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            ตัวอย่าง: (เฉพาะรายที่แม่เป็นพาหะ)
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-2"></i>ปิด
                                </button>
                                <button type="button" class="btn btn-primary" onclick="saveVaccineList()">
                                    <i class="bi bi-save me-2"></i>บันทึก
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Modal เพิ่ม/แก้ไขประวัติวัคซีน -->
        <?php if ($is_admin || $is_teacher || $is_student): ?>
            <div class="modal fade" id="vaccineModal" tabindex="-1" aria-labelledby="vaccineModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="vaccineModalLabel">
                                <i class="bi bi-shield-fill-check me-2"></i>บันทึกการฉีดวัคซีน
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="vaccineForm" class="needs-validation" novalidate>
                                <input type="hidden" id="vaccineId" name="vaccine_id">
                                <input type="hidden" id="vaccineListId" name="vaccine_list_id">
                                <input type="hidden" name="student_id" value="<?= htmlspecialchars($studentid) ?>">

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>ข้อมูลพื้นฐาน</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">วันที่ฉีด</label>
                                                    <input type="date" class="form-control form-control-lg" id="vaccineDate" name="vaccine_date" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">ชื่อวัคซีน</label>
                                                    <input type="text" class="form-control form-control-lg" id="vaccineRecordName" name="vaccine_name" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">ครั้งที่</label>
                                                    <input type="number" class="form-control form-control-lg" id="vaccineNumber" name="vaccine_number" min="1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>สถานที่และผู้ให้บริการ</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">สถานที่ฉีด</label>
                                                    <input type="text" class="form-control form-control-lg" id="vaccineLocation" name="vaccine_location">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">ผู้ให้บริการ</label>
                                                    <input type="text" class="form-control form-control-lg" id="vaccineProvider" name="vaccine_provider">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-card-text me-2"></i>ข้อมูลเพิ่มเติม</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">Lot No.</label>
                                                        <input type="text" class="form-control form-control-lg" id="lotNumber" name="lot_number">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">วันนัดครั้งถัดไป</label>
                                                        <input type="date" class="form-control form-control-lg" id="nextAppointment" name="next_appointment">
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <label class="form-label fw-bold">หมายเหตุ</label>
                                                        <textarea class="form-control" id="vaccineNote" name="vaccine_note" rows="3"></textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold">
                                                            <i class="bi bi-image me-2"></i>รูปภาพ
                                                        </label>
                                                        <input type="file" class="form-control form-control-lg" id="vaccineImage" name="vaccine_image" accept="image/*">
                                                        <div class="form-text text-muted">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            อัพโหลดรูปภาพหลักฐานการฉีดวัคซีน (ถ้ามี)
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>ยกเลิก
                            </button>
                            <button type="button" class="btn btn-primary" onclick="saveVaccine()">
                                <i class="bi bi-save me-2"></i>บันทึก
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

<!-- Script สำหรับจัดการวัคซีน -->
<script> 
function addVaccineList() {
    // รีเซ็ตฟอร์ม
    document.getElementById('vaccineListForm').reset();
    
    // ดึงข้อมูลกลุ่มอายุมาแสดงใน dropdown
    fetch('../../include/process/get_age_groups.php')
        .then(response => response.json())
        .then(result => {
            if (result.age_groups) {
                const select = document.getElementById('ageGroup');
                select.innerHTML = '<option value="">เลือกกลุ่มอายุ</option>';
                result.age_groups.forEach(group => {
                    select.innerHTML += `<option value="${group.id}">${group.age_group}</option>`;
                });
                
                // เปิด Modal รายการวัคซีน
                new bootstrap.Modal(document.getElementById('vaccineListModal')).show();
            } else {
                throw new Error('ไม่สามารถโหลดข้อมูลกลุ่มอายุได้');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: error.message
            });
        });
}

function saveVaccineList() {
    const formData = new FormData(document.getElementById('vaccineListForm'));
    const data = {
        age_group_id: formData.get('ageGroup'), // ใช้ชื่อฟิลด์ age_group_id
        vaccine_name: formData.get('vaccine_name'),
        vaccine_description: formData.get('vaccine_description')
    };
    
    // ตรวจสอบข้อมูล
    if (!data.age_group_id) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณาเลือกกลุ่มอายุ',
            text: 'โปรดเลือกกลุ่มอายุที่ควรได้รับวัคซีน'
        });
        return;
    }

    if (!data.vaccine_name) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณากรอกชื่อวัคซีน',
            text: 'โปรดกรอกชื่อวัคซีนที่ต้องการเพิ่ม'
        });
        return;
    }

    // ส่งข้อมูลไปบันทึก
    fetch('../../include/process/save_list_vaccine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            // ปิด Modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('vaccineListModal'));
            modal.hide();
            
            // แสดงข้อความสำเร็จ
            Swal.fire({
                icon: 'success',
                title: 'บันทึกข้อมูลสำเร็จ',
                text: 'เพิ่มรายการวัคซีนเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload(); // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
            });
        } else {
            throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        }
    })
    .catch(error => {
        console.error('Error:', error); // แสดง error ใน console
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: error.message
        });
    });
}
                </script>
            </div>
        </div>

        <!-- Modal แสดงรายละเอียดวัคซีน -->
        <div class="modal fade" id="vaccineDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">รายละเอียดการรับวัคซีน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="vaccineDetailContent">
                        <!-- จะถูกเติมด้วย JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <script>
        // เพิ่ม JavaScript สำหรับจัดการวัคซีน
        document.getElementById('ageGroup').addEventListener('change', function() {
            loadVaccinesByAgeGroup(this.value);
        });

        function loadVaccinesByAgeGroup(ageGroupId) {
            fetch(`../../include/process/get_age_groups.php?age_group_id=${ageGroupId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('vaccineName');
                    select.innerHTML = '';
                    data.forEach(vaccine => {
                        const option = document.createElement('option');
                        option.value = vaccine.id;
                        option.textContent = vaccine.vaccine_name;
                        select.appendChild(option);
                    });
                });
        }

        function addVaccineRecord(vaccineListId) {
            // ปิด Modal อื่นๆ ก่อน
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            // โค้ดเดิม...
            document.getElementById('vaccineForm').reset();
            document.getElementById('vaccineId').value = '';
            document.getElementById('vaccineListId').value = vaccineListId;
            
            // ดึงข้อมูลและแสดง Modal
            fetch(`../../include/process/get_vaccinelist_detail.php?id=${vaccineListId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        // แก้ไขจาก vaccineName เป็น vaccineRecordName เพื่อไม่ให้ชนกับ ID ของฟอร์มรายการวัคซีน
                        document.getElementById('vaccineRecordName').value = result.data.vaccine_name;
                        new bootstrap.Modal(document.getElementById('vaccineModal')).show();
                    } else {
                        throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลวัคซีนได้');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message
                    });
                });
        }

        function editVaccineRecord(id) {
            // ดึงข้อมูลวัคซีนที่ต้องการแก้ไข
            fetch(`../../include/function/get_vaccine_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // กำหนดค่าให้กับฟอร์ม
                    document.getElementById('vaccineId').value = data.id;
                    document.getElementById('vaccineListId').value = data.vaccine_list_id;
                    document.getElementById('vaccineDate').value = data.vaccine_date;
                    document.getElementById('vaccineRecordName').value = data.vaccine_name || ''; // เพิ่มการแสดงชื่อวัคซีน
                    document.getElementById('vaccineNumber').value = data.vaccine_number || '';
                    document.getElementById('vaccineLocation').value = data.vaccine_location || '';
                    document.getElementById('vaccineProvider').value = data.vaccine_provider || '';
                    document.getElementById('lotNumber').value = data.lot_number || '';
                    document.getElementById('nextAppointment').value = data.next_appointment || '';
                    document.getElementById('vaccineNote').value = data.vaccine_note || '';

                    // แสดง Modal
                    const vaccineModal = new bootstrap.Modal(document.getElementById('vaccineModal'));
                    vaccineModal.show();

                    // อัพเดทชื่อ Modal
                    document.getElementById('vaccineModalLabel').innerHTML = `
                        <i class="bi bi-pencil-square me-2"></i>แก้ไขบันทึกการฉีดวัคซีน
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message || 'ไม่สามารถโหลดข้อมูลได้'
                    });
                });
        }

        function viewVaccineDetails(id) {
            fetch(`../../include/function/get_vaccine_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // สร้าง HTML สำหรับรูปภาพ (ถ้ามี)
                    const imageHtml = data.image_path ? `
                        <div class="text-center mb-4">
                            <div class="vaccine-image-container">
                                <img src="${data.image_path}" alt="หลักฐานการฉีดวัคซีน" 
                                     class="img-fluid rounded">
                            </div>
                        </div>
                    ` : '';

                    const modalContent = `
                        <div class="modal-body p-0">
                            ${imageHtml}
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-card-header">
                                            <div class="header-icon">
                                                <i class="bi bi-shield-fill-check"></i>
                                            </div>
                                            <h6 class="mb-0">ข้อมูลการฉีดวัคซีน</h6>
                                        </div>
                                        <div class="info-card-body">
                                            <ul class="info-list">
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-calendar3"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>วันที่ฉีด</label>
                                                        <span>${new Date(data.vaccine_date).toLocaleDateString('th-TH')}</span>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-bookmark-check"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>ชื่อวัคซีน</label>
                                                        <span>${data.vaccine_name}</span>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-123"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>ครั้งที่</label>
                                                        <span>${data.vaccine_number || '-'}</span>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-geo-alt"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>สถานที่ฉีด</label>
                                                        <span>${data.vaccine_location || '-'}</span>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-card-header">
                                            <div class="header-icon">
                                                <i class="bi bi-info-circle"></i>
                                            </div>
                                            <h6 class="mb-0">รายละเอียดเพิ่มเติม</h6>
                                        </div>
                                        <div class="info-card-body">
                                            <ul class="info-list">
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>ผู้ให้บริการ</label>
                                                        <span>${data.vaccine_provider || '-'}</span>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-upc"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>Lot No.</label>
                                                        <span>${data.lot_number || '-'}</span>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-calendar-check"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>วันนัดครั้งถัดไป</label>
                                                        <span>${data.next_appointment ? new Date(data.next_appointment).toLocaleDateString('th-TH') : '-'}</span>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="info-icon">
                                                        <i class="bi bi-chat-text"></i>
                                                    </div>
                                                    <div class="info-content">
                                                        <label>หมายเหตุ</label>
                                                        <span>${data.vaccine_note || '-'}</span>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: '<div class="modal-custom-title"><i class="bi bi-shield-fill-check"></i>รายละเอียดการฉีดวัคซีน</div>',
                        html: modalContent,
                        width: '800px',
                        padding: '2rem',
                        showCloseButton: true,
                        confirmButtonText: '<i class="bi bi-check-circle"></i>ปิด',
                        confirmButtonColor: '#3085d6',
                        customClass: {
                            container: 'vaccine-detail-modal',
                            popup: 'modal-custom-popup',
                            closeButton: 'modal-custom-close',
                            confirmButton: 'modal-custom-confirm',
                        }
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดข้อมูลได้',
                        confirmButtonText: 'ตกลง'
                    });
                });
        }

        // เพิ่ม CSS
        const style = document.createElement('style');
        style.textContent = `
            .vaccine-detail-modal {
                font-family: 'Sarabun', sans-serif;
            }

            .modal-custom-popup {
                border-radius: 20px;
                background: #ffffff;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }

            .modal-custom-title {
                display: flex;
                align-items: center;
                gap: 10px;
                color: #2563eb;
                font-size: 1.5rem;
                font-weight: 600;
            }

            .modal-custom-title i {
                font-size: 1.8rem;
            }

            .vaccine-image-container {
                max-width: 500px;
                margin: 0 auto;
                overflow: hidden;
                border-radius: 15px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }

            .vaccine-image-container img {
                width: 100%;
                height: auto;
                transition: transform 0.3s ease;
            }

            .vaccine-image-container:hover img {
                transform: scale(1.05);
            }

            .info-card {
                background: #ffffff;
                border-radius: 15px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                overflow: hidden;
                transition: transform 0.3s ease;
            }

            .info-card:hover {
                transform: translateY(-5px);
            }

            .info-card-header {
                background: linear-gradient(135deg, #2563eb, #3b82f6);
                color: white;
                padding: 1rem 1.5rem;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .header-icon {
                width: 32px;
                height: 32px;
                background: rgba(255,255,255,0.2);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .info-card-body {
                padding: 1.5rem;
            }

            .info-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .info-list li {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 12px 0;
                border-bottom: 1px dashed rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }

            .info-list li:last-child {
    border-bottom: none;
}

            .info-list li:hover {
                background: rgba(37,99,235,0.05);
                padding-left: 10px;
            }

            .info-icon {
                width: 36px;
                height: 36px;
                background: rgba(37,99,235,0.1);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #2563eb;
            }

            .info-content {
                flex: 1;
            }

            .info-content label {
                display: block;
                font-size: 0.875rem;
                color: #64748b;
                margin-bottom: 2px;
            }

            .info-content span {
                display: block;
                color: #1e293b;
                font-weight: 500;
            }

            .modal-custom-confirm {
                background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
                border-radius: 10px !important;
                padding: 12px 30px !important;
                font-weight: 500 !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                transition: all 0.3s ease !important;
            }

            .modal-custom-confirm:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(37,99,235,0.25) !important;
            }

            .modal-custom-close {
                color: #64748b !important;
                transition: all 0.3s ease;
            }

            .modal-custom-close:hover {
                color: #1e293b !important;
                transform: rotate(90deg);
            }

            @media (max-width: 768px) {
                .info-card {
                    margin-bottom: 1rem;
                }
                
                .info-card-header {
                    padding: 0.75rem 1rem;
                }
                
                .info-card-body {
                    padding: 1rem;
                }
            }
        `;
        document.head.appendChild(style);

        // เพิ่มฟังก์ชันลบบันทึกการฉีดวัคซีน
        function deleteVaccineRecord(id) {
            Swal.fire({
                title: 'ยืนยันการลบ',
                text: "คุณต้องการลบบันทึกการฉีดวัคซีนนี้ใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../include/process/delete_vaccine_record.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบข้อมูลสำเร็จ',
                                text: 'บันทึกการฉีดวัคซีนถูกลบเรียบร้อยแล้ว',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: error.message
                        });
                    });
                }
            });
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
</script>

</script>

<?php elseif ($currentTab === 'attendance'): ?>
    <!-- ข้อมูลประวัติการมาเรียน -->
    <div class="card shadow-sm">
             <div class="card-body">
        <!-- ฟอร์มค้นหา -->
        <form id="searchForm" method="GET" class="row g-3 mb-4">
            <input type="hidden" name="studentid" value="<?= htmlspecialchars($studentid) ?>">
            <input type="hidden" name="tab" value="attendance">

            <!-- เลือกประเภทการแสดงผล -->
                            <div class="col-md-3">
                <label for="displayType" class="form-label">ประเภทการแสดงผล</label>
                <select class="form-select" id="displayType" name="displayType">
                    <option value="all">แสดงทั้งหมด</option>
                    <option value="date">เลือกวันที่</option>
                    <option value="range">เลือกช่วงวันที่</option>
                </select>
                            </div>

            <!-- ส่วนเลือกวันที่เดียว -->
            <div id="singleDateSection" class="col-md-3" style="display: none;">
                <label for="date" class="form-label">วันที่</label>
                <input type="date" class="form-control" id="date" name="date" 
                    value="<?php echo isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); ?>">
                        </div>

            <!-- ส่วนเลือกช่วงวันที่ -->
            <div id="dateRangeSection" class="col-md-6" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <label for="startDate" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="startDate" name="startDate">
                    </div>
                    <div class="col-md-6">
                        <label for="endDate" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="endDate" name="endDate">
                    </div>
                </div>
            </div>

            <!-- ปุ่มค้นหา -->
            <div class="col-12">
                <button type="button" class="btn btn-primary" onclick="loadResults()">ค้นหา</button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">รีเซ็ต</button>
            </div>
        </form>

            <!-- ตารางแสดงผล -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive" id="resultTable">
                        <!-- ข้อมูลจะถูกเพิ่มโดย JavaScript -->
                    </div>
                </div>
            </div>

            <script>
            // ควบคุมการแสดงผลฟอร์มตามประเภทที่เลือก
            document.getElementById('displayType').addEventListener('change', function() {
                const singleDateSection = document.getElementById('singleDateSection');
                const dateRangeSection = document.getElementById('dateRangeSection');
                
                switch(this.value) {
                    case 'date':
                        singleDateSection.style.display = 'block';
                        dateRangeSection.style.display = 'none';
                        break;
                    case 'range':
                        singleDateSection.style.display = 'none';
                        dateRangeSection.style.display = 'block';
                        break;
                    default: // 'all'
                        singleDateSection.style.display = 'none';
                        dateRangeSection.style.display = 'none';
                }
            });
        </script>

            <script>
                // โหลดผลลัพธ์
                function loadResults() {
                    const formData = new FormData(document.getElementById('searchForm'));
                    const queryString = new URLSearchParams(formData).toString();

                    fetch(`../../include/function/get_student_attendance.php?${queryString}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            const table = document.getElementById('resultTable');

                            if (!Array.isArray(data) || data.length === 0) {
                                table.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูลในวันที่เลือก</div>';
                                return;
                            }

                            let html = `
                 <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                         <thead>
                             <tr>
                                                        <th>วันที่</th>
                                                        <th>สหัสนักเรียน</th>
                                                        <th>ชื่อ-นามสกุล</th>
                                                        <th>กลุ่มเรียน</th>
                                                        <th>ห้องเรียน</th>
                                                        <th>ผถานะมาเรียน</th>
                                                        <th>เวลามาเรียน</th>
                                                        <th>สถานะกลับบ้าน</th>
                                                        <th>เวลากลับบ้าน</th>
                                                        <th>การจัดการ</th>
                             </tr>
                         </thead>
                         <tbody>
                                        `;

                            data.forEach(record => {
                                // แปลงวันที่ให้อยู่ในรูปแบบไทย
                                const checkDate = new Date(record.check_date).toLocaleDateString('th-TH', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit',
                                    hour12: false
                                });

                                html += `
                                                <tr>
                                                    <td>${checkDate}</td>
                                                    <td>${record.student_id}</td>
                                                    <td>${record.prefix_th} ${record.firstname_th} ${record.lastname_th}</td>
                                                    <td>${record.child_group}</td>
                                                    <td>${record.classroom}</td>
                                                    <td>
                                                        <span class="badge bg-${record.status === 'present' ? 'success' :
                                        (record.status === 'absent' ? 'danger' :
                                            (record.status === 'leave' ? 'warning' : 'secondary'))
                                    }">
                                                            ${record.status === 'present' ? 'มาเรียน' :
                                        (record.status === 'absent' ? 'ไม่มาเรียน' :
                                            (record.status === 'leave' ? 'ลา' : record.status))}
                                                        </span>
                                                    </td>
                                                    <td>
                                                    ${record.check_date && new Date(record.check_date).getHours() !== 0 && new Date(record.check_date).getMinutes() !== 0
                                        ? new Date(record.check_date).toLocaleTimeString('th-TH', {
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) + ' น.'
                                        : '-'}
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-${record.status_checkout === 'checked_out' ? 'success' : 'secondary'}">
                                                            ${record.status_checkout === 'checked_out' ? 'กลับแล้ว' : 'ยังไม่กลับ'}
                                                        </span>
                                                    </td>
                                                    <td>${record.check_out_time ? 
                                        record.check_out_time.split(':').slice(0, 2).join(':') + ' น.' : '-'}</td>
                                                    <td>
                                                        <?php if ($is_admin || $is_teacher): ?>
                                                                        ${renderActionButtons(record)}
                                                        <?php else: ?>
                                                        <button type="button" class="btn btn-info btn-sm" onclick="viewAttendanceDetail(${record.id})">
                                                                <i class="fas fa-eye"></i> ดู
                                         </button>
                                                        <?php endif; ?>
                                     </td>
                                                </tr>
                                                `;
                            });
                            html += `
                                            </tbody>
                                        </table>
                                    </div>
                                    `;
                            table.innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('resultTable').innerHTML =
                                '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                        });
                }

                // เรียกใช้ฟังก์ชันเมื่อโหลดหน้า
                document.addEventListener('DOMContentLoaded', loadResults);

                // ฟังก์ชันรีเซ็ตฟอร์ม
                function resetForm() {
                    document.getElementById('searchForm').reset();
                    loadResults();
                }


                // รับสิทธิ์จาก PHP เข้ามาใช้ใน JS
const isAdmin = <?= json_encode($is_admin) ?>;
const isTeacher = <?= json_encode($is_teacher) ?>;

                function renderActionButtons(student) {
                    let buttons = '';

                    const hasAttendanceRecord = student.id && student.status !== null;

                    if (hasAttendanceRecord) {
                        if (student.is_recorded === false) {
                            if (student.status === 'present' && !student.check_date) {
                                buttons = `
                                    <button type="button" class="btn btn-success btn-sm" onclick="checkInAttendance(${student.id})">
                                        <i class="fas fa-check"></i> เช็คอิน
                                    </button>
                                `;
                            } else if (student.status === 'absent' || student.status === 'leave') {
                                buttons = `
                                    <button type="button" class="btn btn-warning btn-sm" onclick="editAttendance(${student.id})">
                                        <i class="fas fa-edit"></i> แก้ไขสถานะ
                                    </button>
                                `;

                                if (isAdmin) {
                                    buttons += `
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteAttendance(${student.id})">
                                            <i class="fas fa-trash"></i> ลบ
                                        </button>
                                    `;
                                }
                            }
                        } else {
                            buttons = `
                                <button type="button" class="btn btn-info btn-sm" onclick="viewAttendanceDetail(${student.id})">
                                    <i class="fas fa-eye"></i> ดู
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="editAttendance(${student.id})">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                            `;

                            if (isAdmin) {
                                buttons += `
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteAttendance(${student.id})">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                `;
                            }
                        }
                    } else {
                        buttons = `
                            <button type="button" class="btn btn-primary btn-sm" 
                                onclick="addAttendance({
                                    student_id: '${student.student_id}',
                                    prefix_th: '${student.prefix_th}',
                                    firstname_th: '${student.firstname_th}',
                                    lastname_th: '${student.lastname_th}'
                                })">
                                <i class="fas fa-plus"></i> เพิ่ม
                            </button>
                        `;
                    }

                    return buttons;
                }

                // ในส่วนของการแสดงสถานะในตาราง
                function getStatusText(status) {
                    if (!status) return 'ยังไม่บันทึก';
                    switch (status) {
                        case 'present': return 'มาเรียน';
                        case 'absent': return 'ไม่มาเรียน';
                        case 'leave': return 'ลา';
                        default: return status;
                    }
                }

                // แก้ไขฟังก์ชัน getStatusBadgeClass
                function getStatusBadgeClass(status) {
                    switch (status) {
                        case 'present':
                        case 'มาเรียน':
                            return 'bg-success';
                        case 'absent':
                        case 'ไม่มาเรียน':
                            return 'bg-danger';
                        case 'leave':
                        case 'ลา':
                            return 'bg-warning';
                        default:
                            return 'bg-secondary';
                    }
                }

                // เพิ่มฟังก์ชันดูรายละเอียด
                function viewAttendanceDetail(id) {
                    fetch(`../../include/function/get_attendance_detail.php?id=${id}`)
                        .then(response => response.json())
                        .then(response => {
                            if (response.status !== 'success') {
                                throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                            }

                            const data = response.data;
                            let statusText = getStatusText(data.status);
                            let statusClass = getStatusBadgeClass(data.status);

                            Swal.fire({
                                title: 'รายละเอียดการเข้าเรียน',
                                html: `
                                            <div class="text-start">
                                                <p><strong>รหัสนักเรียน:</strong> ${data.student_id}</p>
                                                <p><strong>ชื่อ-นามสกุล:</strong> ${data.prefix_th} ${data.firstname_th} ${data.lastname_th}</p>
                                                <p><strong>กลุ่มเรียน:</strong> ${data.child_group}</p>
                                                <p><strong>ห้องเรียน:</strong> ${data.classroom}</p>
                                                <p>
                                                    <strong>สถานะ:</strong> 
                                                    <span class="badge ${statusClass}">${statusText}</span>
                                                </p>
                                                <p><strong>วันที่:</strong> ${new Date(data.check_date).toLocaleDateString('th-TH', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}</p>
                                                <p><strong>เวลามา:</strong> ${data.check_date && new Date(data.check_date).getHours() !== 0 && new Date(data.check_date).getMinutes() !== 0
                                        ? new Date(data.check_date).toLocaleTimeString('th-TH', {
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) + ' น.'
                                        : '-'}</p>
                                                <p>
                                                    <strong>สถานะกลับบ้าน:</strong> 
                                                    <span class="badge ${data.status_checkout === 'checked_out' ? 'bg-success' : 'bg-secondary'}">
                                                        ${data.status_checkout === 'checked_out' ? 'กลับแล้ว' : 'ยังไม่กลับ'}
                                                    </span>
                                                </p>
                                                <p><strong>เวลากลับ:</strong> ${data.check_out_time ?
                                        data.check_out_time.split(':').slice(0, 2).join(':') + ' น.' : '-'}</p>
                                                ${data.status === 'leave' ? `<p><strong>หมายเหตุ:</strong> ${data.leave_note || '-'}</p>` : ''}
                                                <hr>
                                                <p><small><strong>บันทึกเมื่อ:</strong> ${new Date(data.created_at).toLocaleString('th-TH', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })} น.</small></p>
                                                <p><small><strong>แก้ไขล่าสุด:</strong> ${new Date(data.updated_at).toLocaleString('th-TH', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })} น.</small></p>
                        </div>
                                        `,
                                width: '600px',
                                confirmButtonText: 'ปิด',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: error.message,
                                confirmButtonText: 'ตกลง'
                            });
                        });
                }

                // ฟังก์ชันแก้ไขข้อมูล
                function editAttendance(id) {
                    const currentDate = document.getElementById('date').value;
                    fetch(`../../include/function/get_attendance_detail.php?id=${id}&date=${currentDate}`)
                        .then(response => response.json())
                        .then(response => {
                            if (response.status !== 'success') {
                                throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                            }
                            showAttendanceForm({
                                ...response.data,
                                attendance_date: currentDate
                            }, false);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: error.message,
                                confirmButtonText: 'ตกลง'
                            });
                        });
                }

                // ฟังก์ชันแสดงฟอร์ม
                function showAttendanceForm(data, isNewRecord) {
                    Swal.fire({
                        title: isNewRecord ? 'บันทึกการเข้าเรียน' : 'แก้ไขข้อมูลการเข้าเรียน',
                        html: `
                                    <form id="attendanceForm">
                                        <input type="hidden" name="id" value="${data.id || ''}">
                                        <input type="hidden" name="student_id" value="${data.student_id || ''}">
                                        <input type="hidden" name="attendance_date" value="${data.attendance_date}">
                                        <div class="mb-3">
                                            <label class="form-label">รหัสนักเรียน</label>
                                            <input type="text" class="form-control" value="${data.student_id || ''}" readonly>
                            </div>
                                        <div class="mb-3">
                                            <label class="form-label">ชื่อ-นามสกุล</label>
                                            <input type="text" class="form-control" value="${data.prefix_th || ''} ${data.firstname_th || ''} ${data.lastname_th || ''}" readonly>
                            </div>
                                        <div class="mb-3">
                                            <label class="form-label">วันที่</label>
                                            <input type="date" class="form-control" value="${data.attendance_date}" readonly>
                            </div>
                                        <div class="mb-3">
                                            <label class="form-label">สถานะการมาเรียน</label>
                                            <select class="form-select" name="status" id="attendanceStatus">
                                                <option value="present" ${data.status === 'present' ? 'selected' : ''}>มาเรียน</option>
                                                <option value="absent" ${data.status === 'absent' ? 'selected' : ''}>ไม่มาเรียน</option>
                                                <option value="leave" ${data.status === 'leave' ? 'selected' : ''}>ลา</option>
                                            </select>
                        </div>
                                        <div class="mb-3" id="timeDiv">
                                            <label class="form-label">เวลามาเรียน</label>
                                            <input type="time" class="form-control" name="check_date" value="${data.check_date || ''}">
                                        </div>
                                        <div class="mb-3" id="checkoutDiv">
                                            <label class="form-label">เวลากลับ</label>
                                            <input type="time" class="form-control" name="check_out_time" value="${data.check_out_time || ''}">
                                        </div>
                                        <div class="mb-3" id="leaveNoteDiv" style="display:none">
                                            <label class="form-label">หมายเหตุการลา</label>
                                            <textarea class="form-control" name="leave_note" rows="3">${data.leave_note || ''}</textarea>
                                        </div>
                                    </form>
                                `,
                        didOpen: () => {
                            const attendanceStatus = document.getElementById('attendanceStatus');
                            const timeDiv = document.getElementById('timeDiv');
                            const checkoutDiv = document.getElementById('checkoutDiv');
                            const leaveNoteDiv = document.getElementById('leaveNoteDiv');

                            function updateFieldsVisibility() {
                                const status = attendanceStatus.value;
                                timeDiv.style.display = status === 'present' ? 'block' : 'none';
                                checkoutDiv.style.display = status === 'present' ? 'block' : 'none';
                                leaveNoteDiv.style.display = status === 'leave' ? 'block' : 'none';
                            }

                            attendanceStatus.addEventListener('change', updateFieldsVisibility);
                            updateFieldsVisibility();
                        },
                        showCancelButton: true,
                        confirmButtonText: 'บันทึก',
                        cancelButtonText: 'ยกเลิก',
                        preConfirm: () => {
                            const form = document.getElementById('attendanceForm');
                            const formData = new FormData(form);
                            const data = Object.fromEntries(formData.entries());

                            // เลือก URL ตามประเภทการทำงาน
                            const url = isNewRecord ?
                                '../../include/process/save_attendance_record.php' :
                                '../../include/process/update_attendance_record.php';

                            return fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(data)
                            })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.status === 'success') {
                                        return result;
                                    }
                                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                                });
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                icon: 'success',
                                title: isNewRecord ? 'บันทึกข้อมูลสำเร็จ' : 'แก้ไขข้อมูลสำเร็จ',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                loadResults();
                            });
                        }
                    });
                }

            </script>

        <?php elseif ($currentTab === 'health'): ?>
            <!-- ข้อมูลประวัติการตรวจร่างกาย -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- ฟอร์มค้นหา -->
        <form id="searchHealthForm" method="GET" class="row g-3 mb-4">
            <input type="hidden" name="studentid" value="<?= htmlspecialchars($studentid) ?>">
            <input type="hidden" name="tab" value="health">

            <!-- เลือกประเภทการแสดงผล -->
            <div class="col-md-3">
                <label for="healthDisplayType" class="form-label">ประเภทการแสดงผล</label>
                <select class="form-select" id="healthDisplayType" name="displayType">
                    <option value="all">แสดงทั้งหมด</option>
                    <option value="date">เลือกวันที่</option>
                    <option value="range">เลือกช่วงวันที่</option>
                </select>
            </div>

            <!-- ส่วนเลือกวันที่เดียว -->
            <div id="healthSingleDateSection" class="col-md-3" style="display: none;">
                <label for="healthDate" class="form-label">วันที่</label>
                <input type="date" class="form-control" id="healthDate" name="date" 
                    value="<?php echo isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); ?>">
            </div>

            <!-- ส่วนเลือกช่วงวันที่ -->
            <div id="healthDateRangeSection" class="col-md-6" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <label for="healthStartDate" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="healthStartDate" name="startDate">
                    </div>
                    <div class="col-md-6">
                        <label for="healthEndDate" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="healthEndDate" name="endDate">
                    </div>
                </div>
            </div>

            <!-- ปุ่มค้นหา -->
            <div class="col-12">
                <button type="button" class="btn btn-primary" onclick="loadHealthResults()">ค้นหา</button>
                <button type="button" class="btn btn-secondary" onclick="resetHealthForm()">รีเซ็ต</button>
            </div>
        </form>

                    <!-- ตารางแสดงผล -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive" id="healthResultTable">
                                <!-- ข้อมูลจะถูกเพิ่มโดย JavaScript -->
                            </div>
                        </div>
                    </div>

                    <script>
            // ควบคุมการแสดงผลฟอร์มตามประเภทที่เลือก
            document.getElementById('healthDisplayType').addEventListener('change', function() {
                const singleDateSection = document.getElementById('healthSingleDateSection');
                const dateRangeSection = document.getElementById('healthDateRangeSection');
                
                switch(this.value) {
                    case 'date':
                        singleDateSection.style.display = 'block';
                        dateRangeSection.style.display = 'none';
                        break;
                    case 'range':
                        singleDateSection.style.display = 'none';
                        dateRangeSection.style.display = 'block';
                        break;
                    default: // 'all'
                        singleDateSection.style.display = 'none';
                        dateRangeSection.style.display = 'none';
                }
            });
        </script>
        
                    <script>
                        // โหลดผลลัพธ์
                        function loadHealthResults() {
                            const formData = new FormData(document.getElementById('searchHealthForm'));
                            const queryString = new URLSearchParams(formData).toString();

                            fetch(`../../include/function/get_student_health.php?${queryString}`)
                                .then(response => response.json())
                                .then(data => {
                                    const table = document.getElementById('healthResultTable');
                                    if (data.length === 0) {
                                        table.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูล</div>';
                                        return;
                                    }

                            let html = `
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>วันที่</th>
                                                    <th>รหัสนักเรียน</th>
                                                    <th>ชื่อ-นามสกุล</th>
                                                    <th>กลุ่มเรียน</th>
                                                    <th>ห้องเรียน</th>
                                                    <th>ผลการตรวจ</th>
                                                    <th>ครูผู้ตรวจ</th>
                                                    <th>การจัดการ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                        `;

                                    data.forEach(record => {
                                        // แปลงวันที่ให้อยู่ในรูปแบบไทย
                                        const createdDate = new Date(record.created_at).toLocaleDateString('th-TH', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            second: '2-digit',
                                            hour12: false
                                        }) + 'น.';
                                        html += `
                                                                                <tr>
                                                                                    <td>${createdDate}</td>
                                                                                    <td>${record.student_id}</td>
                                                                                    <td>${record.prefix_th} ${record.first_name_th} ${record.last_name_th}</td>
                                                                                    <td>${record.child_group}</td>
                                                                                    <td>${record.classroom}</td>
                                                                                    <td>
                                                                                        <button type="button" class="btn btn-info btn-sm" 
                                                                                                onclick="viewDetails(${record.id})">
                                                                                            ดูรายละเอียด
                                         </button>
                                     </td>
                                                                                    <td>${record.teacher_signature}</td>
                                                                                    <td>
                                                                                    <?php if ($is_admin): ?>
                                                                                        <button type="button" class="btn btn-warning btn-sm" 
                                                                                                onclick="editRecord(${record.id})">
                                                                                            แก้ไข
                                         </button>
                                                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                                                                onclick="deleteRecord(${record.id})">
                                                                                            ลบ
                                                                                        </button>
                                                                                    <?php elseif ($is_teacher): ?>
                                                                                        <button type="button" class="btn btn-warning btn-sm" 
                                                                                                onclick="editRecord(${record.id})">
                                                                                            แก้ไข
                                                                                        </button>
                                                                                    <?php elseif ($is_student): ?>
                                                                                        <div class="alert alert-info" role="alert">
                                                                                            คุณสามารถดูข้อมูลของเด็กได้เท่านั้น
                                                                                        </div>
                                                                                    <?php endif; ?>
                                     </td>
                                 </tr>
                                                                            `;
                                    });

                                    html += `
                         </tbody>
                     </table>
                                                                                `;
                                    table.innerHTML = html;
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    document.getElementById('resultTable').innerHTML =
                                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                                });
                        }

                        // เรียกใช้ฟังก์ชันเมื่อโหลดหน้า
                        document.addEventListener('DOMContentLoaded', () => {
                            loadResults();
                        });

                        // ส่งฟอร์มเมื่อมีการเปลี่ยนแปลงค่าใดๆ
                        document.querySelectorAll('#searchForm select, #searchForm input').forEach(element => {
                            element.addEventListener('change', () => loadResults());
                        });


                        // ฟังก์ชันดูรายละเอียด
                        function viewDetails(id) {
                            fetch(`../../include/function/get_health_detail.php?id=${id}`)
                                .then(response => response.json())
                                .then(response => {
                                    if (response.status !== 'success') {
                                        throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                                    }
                                    const data = response.data;

                                    // ฟังก์ชันสำหรับแสดงรายการที่ถูกเลือก
                                    const displayCheckedItems = (field) => {
                                        if (!data[field] || (!data[field].checked && !data[field].unchecked)) return '';

                                        const checkedItems = data[field].checked || [];
                                        if (checkedItems.length === 0) return 'ไม่พบข้อมูล';

                                        return checkedItems.map(item => `<li>- ${item}</li>`).join('');
                                    };

                                    // สร้าง HTML สำหรับแสดงรายละเอียด
                                    const modalContent = `
                                            <div class="container-fluid">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h5 class="mb-3">ข้อมูลทั่วไป</h5>
                                                        <p><strong>รหัสนักเรียน:</strong> ${data.student_id}</p>
                                                        <p><strong>ชื่อ-นามสกุล:</strong> ${data.prefix_th} ${data.first_name_th} ${data.last_name_th}</p>
                                                        <p><strong>กลุ่มเรียน:</strong> ${data.child_group}</p>
                                                        <p><strong>ห้องเรียน:</strong> ${data.classroom}</p>
                                                        <p><strong>วันที่ตรวจ:</strong> ${data.formatted_date}</p>
                                                        <p><strong>ครูผู้ตรวจ:</strong> ${data.teacher_signature}</p>
                 </div>
                                                    <div class="col-md-6">
                                                        <h5 class="mb-3">ผลการตรวจร่างกาย</h5>

                                                        <div class="mb-2">
                                                            <strong>ผม/ศีรษะ:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('hair')}</ul>
                                                            ${data.hair_reason ? `<p class="ms-3">หมายเหตุ: ${data.hair_reason}</p>` : ''}
             </div>

                                                        <div class="mb-2">
                                                            <strong>ตา:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('eye')}</ul>
                                                            ${data.eye_condition ? `<p class="ms-3">ลักษณะขี้ตา: ${data.eye_condition}</p>` : ''}
                                                            ${data.eye_reason ? `<p class="ms-3">หมายเหตุ: ${data.eye_reason}</p>` : ''}
         </div>

                                                        <div class="mb-2">
                                                            <strong>ปากและคอ:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('mouth')}</ul>
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>ฟัน:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('teeth')}</ul>
                                                            ${data.teeth_count ? `<p class="ms-3">จำนวนฟันผุ: ${data.teeth_count}</p>` : ''}
</div>

                                                        <div class="mb-2">
                                                            <strong>หู:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('ears')}</ul>
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>จมูก:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('nose')}</ul>
                                                            ${data.nose_condition ? `<p class="ms-3">ลักษณะน้ำมูก: ${data.nose_condition}</p>` : ''}
                                                            ${data.nose_reason ? `<p class="ms-3">หมายเหตุ: ${data.nose_reason}</p>` : ''}
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>เล็บ:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('nails')}</ul>
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>ผิวหนัง:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('skin')}</ul>
                                                            ${data.skin_wound_detail ? `<p class="ms-3">รายละเอียดแผล: ${data.skin_wound_detail}</p>` : ''}
                                                            ${data.skin_rash_detail ? `<p class="ms-3">รายละเอียดผื่น: ${data.skin_rash_detail}</p>` : ''}
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>มือและเท้า:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('hands_feet')}</ul>
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>แขนและขา:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('arms_legs')}</ul>
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>ลำตัวและหลัง:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('body')}</ul>
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>อาการผิดปกติ:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('symptoms')}</ul>
                                                            ${data.fever_temp ? `<p class="ms-3">อุณหภูมิ: ${data.fever_temp} °C</p>` : ''}
                                                            ${data.cough_type ? `<p class="ms-3">ลักษณะการไอ: ${data.cough_type}</p>` : ''}
                                                            ${data.symptoms_reason ? `<p class="ms-3">หมายเหตุ: ${data.symptoms_reason}</p>` : ''}
                                                        </div>

                                                        <div class="mb-2">
                                                            <strong>การใช้ยา:</strong>
                                                            <ul class="list-unstyled mb-0">${displayCheckedItems('medicine')}</ul>
                                                            ${data.medicine_detail ? `<p class="ms-3">รายละเอียดยา: ${data.medicine_detail}</p>` : ''}
                                                            ${data.medicine_reason ? `<p class="ms-3">หมายเหตุ: ${data.medicine_reason}</p>` : ''}
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mt-3">
                                                        <h5 class="mb-3">บันทึกเพิ่มเติม</h5>
                                                        ${data.illness_reason ? `<p><strong>การเจ็บป่วย:</strong> ${data.illness_reason}</p>` : ''}
                                                        ${data.accident_reason ? `<p><strong>อุบัติเหตุ/แมลงกัดต่อย:</strong> ${data.accident_reason}</p>` : ''}
                                                        ${data.teacher_note ? `<p><strong>บันทึกของครู:</strong> ${data.teacher_note}</p>` : ''}
                                                        ${data.teacher_signature ? `<p><strong>ลงชื่อครู:</strong> ${data.teacher_signature}</p>` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                        `;

                                    Swal.fire({
                                        title: 'รายละเอียดการตรวจร่างกาย',
                                        html: modalContent,
                                        width: '80%',
                                        confirmButtonText: 'ปิด',
                                        customClass: {
                                            container: 'health-detail-modal'
                                        }
                                    });
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด',
                                        text: 'ไม่สามารถโหลดข้อมูลได้',
                                        confirmButtonText: 'ตกลง'
                                    });
                                });
                        }

                        // เพิ่มฟังก์ชันสำหรับเก็บค่า checkbox
                        function getCheckboxValues(fieldName) {
                            const checkboxes = document.querySelectorAll(`input[name="${fieldName}"]`);
                            const checked = [];
                            const unchecked = [];

                            checkboxes.forEach(checkbox => {
                                if (checkbox.checked) {
                                    checked.push(checkbox.value);
                                } else {
                                    unchecked.push(`ไม่${checkbox.value}`);
                                }
                            });

                            return {
                                checked: checked,
                                unchecked: unchecked
                            };
                        }

                        // ฟังก์ชันแก้ไขข้อมูล
                        function editRecord(id) {
                            fetch(`../../include/function/get_health_detail.php?id=${id}`)
                                .then(response => response.json())
                                .then(response => {
                                    if (response.status !== 'success') {
                                        throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                                    }
                                    const data = response.data;

                                    // ฟังก์ชันสร้าง checkbox group
                                    const createCheckboxGroup = (field, options, checkedItems = []) => {
                                        return options.map(option => {
                                            // แยกค่า condition ออกจาก checkbox value
                                            let isChecked = false;
                                            if (Array.isArray(checkedItems)) {
                                                // ตรวจสอบว่ามีค่า condition อยู่ในข้อความหรือไม่
                                                isChecked = checkedItems.some(item => {
                                                    if (field === 'teeth' && option === 'ฟันผุ') {
                                                        return item.includes('ฟันผุ');
                                                    } else if (field === 'symptoms' && option === 'มีไข้') {
                                                        return item.includes('มีไข้');
                                                    } else if (field === 'symptoms' && option === 'ไอ') {
                                                        return item.includes('ไอ');
                                                    } else if (field === 'nose' && option === 'มีน้ำมูก') {
                                                        return item.includes('มีน้ำมูก');
                                                    } else if (field === 'eye' && option === 'มีขี้ตา') {
                                                        return item.includes('มีขี้ตา');
                                                    } else {
                                                        return item === option;
                                                    }
                                                });
                                            }

                                            return `
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input" type="checkbox" 
                                                                                                name="${field}" value="${option}" 
                                                                                                ${isChecked ? 'checked' : ''}>
                                                                                            <label class="form-check-label">${option}</label>
                                                        </div>
                                                                                    `;
                                        }).join('');
                                    };

                                    const modalContent = `
                                            <form id="editHealthForm">
                                                <input type="hidden" name="id" value="${data.id}">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h5 class="mb-3">ข้อมูลทั่วไป</h5>
                                                            <p><strong>รหัสนักเรียน:</strong> ${data.student_id}</p>
                                                            <p><strong>ชื่อ-นามสกุล:</strong> ${data.prefix_th} ${data.first_name_th} ${data.last_name_th}</p>
                                                            <p><strong>กลุ่มเรียน:</strong> ${data.child_group}</p>
                                                            <p><strong>ห้องเรียน:</strong> ${data.classroom}</p>

                                                <input type="hidden" name="student_id" value="${data.student_id}">
                                                <input type="hidden" name="prefix_th" value="${data.prefix_th}">
                                                <input type="hidden" name="first_name_th" value="${data.first_name_th}">
                                                <input type="hidden" name="last_name_th" value="${data.last_name_th}">
                                                <input type="hidden" name="child_group" value="${data.child_group}">
                                                <input type="hidden" name="classroom" value="${data.classroom}">
                                                        </div>
                                            <div class="col-md-6">
                                                <h5 class="mb-3">ผลการตรวจร่างกาย</h5>

                                                <!-- ผม/ศีรษะ -->
                                                <div class="mb-3">
                                                    <label class="form-label">ผม/ศีรษะ</label>
                                                    ${createCheckboxGroup('hair', ['สะอาด', 'ผมยาว', 'ไม่สะอาด', 'มีเหา'],
    data.hair?.checked || [])}
                                                    <textarea class="form-control mt-2" name="hair_reason" 
                                                        placeholder="หมายเหตุเพิ่มเติม">${data.hair_reason || ''}</textarea>
                                                </div>

                                                <!-- ตา -->
                                                <div class="mb-3">
                                                    <label class="form-label">ตา</label>
                                                    ${createCheckboxGroup('eye', ['ปกติ', 'ตาแดง', 'มีขี้ตา'],
    data.eye?.checked || [])}
                                                    <select class="form-select mt-2" name="eye_condition">
                                                        <option value="">เลือกลักษณะขี้ตา</option>
                                                            <option value="ขวาปกติ" ${data.eye_condition === 'ขวาปกติ' ? 'selected' : ''}>ขวาปกติ</option>
                                                            <option value="เหลือง/เขียว" ${data.eye_condition === 'เหลือง/เขียว' ? 'selected' : ''}>เหลือง/เขียว</option>
                                                </select>
                                                    <textarea class="form-control mt-2" name="eye_reason" 
                                                        placeholder="หมายเหตุเพิ่มเติม">${data.eye_reason || ''}</textarea>
                                                </div>

                                                <!-- เพิ่มฟิลด์อื่นๆ ในลักษณะเดียวกัน -->

                                        <!-- ปากและคอ -->
                                                <div class="mb-3">
                                            <label class="form-label">ปากและคอ</label>
                                            ${createCheckboxGroup('mouth', ['สะอาด', 'มีกลิ่นปาก', 'มีแผลในปาก', 'มีตุ่มในปาก'],
    data.mouth?.checked || [])}
                                                </div>

                                        <!-- ฟัน -->
                                                <div class="mb-3">
                                            <label class="form-label">ฟัน</label>
                                            ${createCheckboxGroup('teeth', ['สะอาด', 'ไม่สะอาด', 'ฟันผุ'],
    data.teeth?.checked || [])}
                                            <div class="mt-2" id="teethCountDiv" style="display: ${data.teeth_count ? 'block' : 'none'}">
                                                <label>จำนวนฟันผุ:</label>
                                                <input type="number" class="form-control" name="teeth_count" 
                                                    value="${data.teeth_count || ''}" min="1" max="32">
                                                </div>
                                        </div>

                                        <!-- หู -->
                                                <div class="mb-3">
                                            <label class="form-label">หู</label>
                                            ${createCheckboxGroup('ears', ['สะอาด', 'ไม่สะอาด', 'มีขี้หู'],
    data.ears?.checked || [])}
                                                    </div>

                                        <!-- จมูก -->
                                        <div class="mb-3">
                                            <label class="form-label">จมูก</label>
                                            ${createCheckboxGroup('nose', ['สะอาด', 'มีน้ำมูก'],
    data.nose?.checked || [])}
                                            <select class="form-select mt-2" name="nose_condition">
                                                <option value="">เลือกลักษณะน้ำมูก</option>
                                                <option value="ใส" ${data.nose_condition === 'ใส' ? 'selected' : ''}>ใส</option>
                                                <option value="เหลือง" ${data.nose_condition === 'เหลือง' ? 'selected' : ''}>เหลือง</option>
                                                <option value="เขียว" ${data.nose_condition === 'เขียว' ? 'selected' : ''}>เขียว</option>
                                            </select>
                                            <textarea class="form-control mt-2" name="nose_reason" 
                                                placeholder="หมายเหตุเพิ่มเติม">${data.nose_reason || ''}</textarea>
                                                    </div>

                                        <!-- เล็บ -->
                                        <div class="mb-3">
                                            <label class="form-label">เล็บ</label>
                                            ${createCheckboxGroup('nails', ['สะอาด', 'ไม่สะอาด', 'เล็บยาว'],
        data.nails?.checked || [])}
                                                    </div>

                                        <!-- ผิวหนัง -->
                                        <div class="mb-3">
                                            <label class="form-label">ผิวหนัง</label>
                                            ${createCheckboxGroup('skin', ['สะอาด', 'มีแผล', 'มีผื่น', 'มีขี้ไคล'],
            data.skin?.checked || [])}
                                            <div class="mt-2" id="skinWoundDiv" style="display: ${data.skin_wound_detail ? 'block' : 'none'}">
                                                <label>รายละเอียดแผล:</label>
                                                <textarea class="form-control" name="skin_wound_detail">${data.skin_wound_detail || ''}</textarea>
                                                    </div>
                                            <div class="mt-2" id="skinRashDiv" style="display: ${data.skin_rash_detail ? 'block' : 'none'}">
                                                <label>รายละเอียดผื่น:</label>
                                                <textarea class="form-control" name="skin_rash_detail">${data.skin_rash_detail || ''}</textarea>
                                                    </div>
                                                    </div>

                                        <!-- มือและเท้า -->
                                        <div class="mb-3">
                                            <label class="form-label">มือและเท้า</label>
                                            ${createCheckboxGroup('hands_feet', ['ปกติ', 'จุดหรือผื่น', 'ตุ่มใส', 'ตุ่มหนอง'],
                data.hands_feet?.checked || [])}
                                                    </div>

                                        <!-- แขนและขา -->
                                        <div class="mb-3">
                                            <label class="form-label">แขนและขา</label>
                                            ${createCheckboxGroup('arms_legs', ['ปกติ', 'จุดหรือผื่น', 'ตุ่มใส', 'ตุ่มหนอง'],
                    data.arms_legs?.checked || [])}
                                                    </div>

                                        <!-- ลำตัวและหลัง -->
                                        <div class="mb-3">
                                            <label class="form-label">ลำตัวและหลัง</label>
                                            ${createCheckboxGroup('body', ['ปกติ', 'จุดหรือผื่น', 'ตุ่มใส', 'ตุ่มหนอง'],
                        data.body?.checked || [])}
                                                    </div>

                                        <!-- อาการผิดปกติ -->
                                        <div class="mb-3">
                                            <label class="form-label">อาการผิดปกติ</label>
                                            ${createCheckboxGroup('symptoms', ['ไม่มี', 'มีไข้', 'ไอ'],
                            data.symptoms?.checked || [])}
                                            <div class="mt-2" id="feverDiv" style="display: ${data.fever_temp ? 'block' : 'none'}">
                                                <label>อุณหภูมิ:</label>
                                                <input type="number" class="form-control" name="fever_temp" 
                                                    value="${data.fever_temp || ''}" step="0.1" min="35" max="42">
                                                    </div>
                                            <div class="mt-2" id="coughDiv" style="display: ${data.cough_type ? 'block' : 'none'}">
                                                <label>ลักษณะการไอ:</label>
                                                <select class="form-select" name="cough_type">
                                                    <option value="">เลือกลักษณะการไอ</option>
                                                    <option value="ไอแห้ง" ${data.cough_type === 'ไอแห้ง' ? 'selected' : ''}>ไอแห้ง</option>
                                                    <option value="มีเสมหะ" ${data.cough_type === 'มีเสมหะ' ? 'selected' : ''}>มีเสมหะ</option>
                                                </select>
                                                    </div>
                                            <textarea class="form-control mt-2" name="symptoms_reason" 
                                                placeholder="หมายเหตุเพิ่มเติม">${data.symptoms_reason || ''}</textarea>
                                                    </div>

                                        <!-- การใช้ยา -->
                                        <div class="mb-3">
                                            <label class="form-label">การใช้ยา</label>
                                            ${createCheckboxGroup('medicine', ['ไม่มี', 'มียา'],
                                data.medicine?.checked || [])}
                                            <div class="mt-2" id="medicineDetailDiv" style="display: ${data.medicine_detail ? 'block' : 'none'}">
                                                <label>รายละเอียดยา:</label>
                                                <textarea class="form-control" name="medicine_detail">${data.medicine_detail || ''}</textarea>
                                                    </div>
                                            <textarea class="form-control mt-2" name="medicine_reason" 
                                                placeholder="หมายเหตุเพิ่มเติม">${data.medicine_reason || ''}</textarea>
                                                    </div>

                                                    </div>
                                            <div class="col-12 mt-3">
                                                <h5 class="mb-3">บันทึกเพิ่มเติม</h5>
                                                <div class="mb-3">
                                                    <label class="form-label">การเจ็บป่วย</label>
                                                    <textarea class="form-control" name="illness_reason">${data.illness_reason || ''}</textarea>
                                                    </div>
                                                <div class="mb-3">
                                                    <label class="form-label">อุบัติเหตุ/แมลงกัดต่อย</label>
                                                    <textarea class="form-control" name="accident_reason">${data.accident_reason || ''}</textarea>
                                                    </div>
                                                <div class="mb-3">
                                                    <label class="form-label">บันทึกของครู</label>
                                                    <textarea class="form-control" name="teacher_note">${data.teacher_note || ''}</textarea>
                                                    </div>
                                                <div class="mb-3">
                                                    <label class="form-label">ลงชื่อครู</label>
                                                    <input type="text" class="form-control" name="teacher_signature" 
                                                        value="${data.teacher_signature || ''}" required>
                                                    </div>
                                                    </div>
                                                    </div>
                            </form>
                                            `;

                                    Swal.fire({
                                        title: 'แก้ไขข้อมูลการตรวจร่างกาย',
                                        html: modalContent,
                                        width: '80%',
                                        showCancelButton: true,
                                        confirmButtonText: 'บันทึก',
                                        cancelButtonText: 'ยกเลิก',
                                        didOpen: () => {
                                            // จัดการ checkbox ตา
                                            document.querySelector('input[name="eye"][value="มีขี้ตา"]')?.addEventListener('change', function () {
                                                const eyeConditionDiv = document.getElementById('eyeConditionDiv');
                                                if (eyeConditionDiv) {
                                                    eyeConditionDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });

                                            // จัดการ checkbox จมูก
                                            document.querySelector('input[name="nose"][value="มีน้ำมูก"]')?.addEventListener('change', function () {
                                                const noseConditionDiv = document.getElementById('noseConditionDiv');
                                                if (noseConditionDiv) {
                                                    noseConditionDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });

                                            // จัดการ checkbox ฟันผุ
                                            document.querySelector('input[name="teeth"][value="ฟันผุ"]')?.addEventListener('change', function () {
                                                const teethCountDiv = document.getElementById('teethCountDiv');
                                                if (teethCountDiv) {
                                                    teethCountDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });

                                            // จัดการ checkbox ผิวหนัง
                                            document.querySelector('input[name="skin"][value="มีแผล"]')?.addEventListener('change', function () {
                                                const skinWoundDiv = document.getElementById('skinWoundDiv');
                                                if (skinWoundDiv) {
                                                    skinWoundDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });
                                            document.querySelector('input[name="skin"][value="มีผื่น"]')?.addEventListener('change', function () {
                                                const skinRashDiv = document.getElementById('skinRashDiv');
                                                if (skinRashDiv) {
                                                    skinRashDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });

                                            // จัดการ checkbox อาการผิดปกติ
                                            document.querySelector('input[name="symptoms"][value="มีไข้"]')?.addEventListener('change', function () {
                                                const feverDiv = document.getElementById('feverDiv');
                                                if (feverDiv) {
                                                    feverDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });
                                            document.querySelector('input[name="symptoms"][value="ไอ"]')?.addEventListener('change', function () {
                                                const coughDiv = document.getElementById('coughDiv');
                                                if (coughDiv) {
                                                    coughDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });

                                            // จัดการ checkbox การใช้ยา
                                            document.querySelector('input[name="medicine"][value="มียา"]')?.addEventListener('change', function () {
                                                const medicineDetailDiv = document.getElementById('medicineDetailDiv');
                                                if (medicineDetailDiv) {
                                                    medicineDetailDiv.style.display = this.checked ? 'block' : 'none';
                                                }
                                            });

                                            // เช็คสถานะ checkbox เริ่มต้นและแสดง/ซ่อน div ที่เกี่ยวข้อง
                                            const checkInitialState = (checkboxSelector, divId) => {
                                                const checkbox = document.querySelector(checkboxSelector);
                                                const div = document.getElementById(divId);
                                                if (checkbox && div && checkbox.checked) {
                                                    div.style.display = 'block';
                                                }
                                            };

                                            // เช็คสถานะเริ่มต้นของทุก checkbox
                                            checkInitialState('input[name="eye"][value="มีขี้ตา"]', 'eyeConditionDiv');
                                            checkInitialState('input[name="nose"][value="มีน้ำมูก"]', 'noseConditionDiv');
                                            checkInitialState('input[name="teeth"][value="ฟันผุ"]', 'teethCountDiv');
                                            checkInitialState('input[name="skin"][value="มีแผล"]', 'skinWoundDiv');
                                            checkInitialState('input[name="skin"][value="มีผื่น"]', 'skinRashDiv');
                                            checkInitialState('input[name="symptoms"][value="มีไข้"]', 'feverDiv');
                                            checkInitialState('input[name="symptoms"][value="ไอ"]', 'coughDiv');
                                            checkInitialState('input[name="medicine"][value="มียา"]', 'medicineDetailDiv');
                                        },
                                        preConfirm: () => {
                                            // เก็บข้อมูลจากฟอร์ม
                                            const form = document.getElementById('editHealthForm');
                                            const formData = new FormData(form);
                                            const data = {
                                                id: formData.get('id'),
                                                student_id: formData.get('student_id'),
                                                prefix_th: formData.get('prefix_th'),
                                                first_name_th: formData.get('first_name_th'),
                                                last_name_th: formData.get('last_name_th'),
                                                child_group: formData.get('child_group'),
                                                classroom: formData.get('classroom'),
                                                teacher_signature: formData.get('teacher_signature'),

                                                // เก็บข้อมูล checkbox fields
                                                hair: getCheckboxValues('hair'),
                                                eye: getCheckboxValues('eye'),
                                                mouth: getCheckboxValues('mouth'),
                                                teeth: getCheckboxValues('teeth'),
                                                ears: getCheckboxValues('ears'),
                                                nose: getCheckboxValues('nose'),
                                                nails: getCheckboxValues('nails'),
                                                skin: getCheckboxValues('skin'),
                                                hands_feet: getCheckboxValues('hands_feet'),
                                                arms_legs: getCheckboxValues('arms_legs'),
                                                body: getCheckboxValues('body'),
                                                symptoms: getCheckboxValues('symptoms'),
                                                medicine: getCheckboxValues('medicine'),

                                                // แก้ไขการส่งค่าตัวเลข
                                                teeth_count: formData.get('teeth_count') ? parseInt(formData.get('teeth_count')) : null,
                                                fever_temp: formData.get('fever_temp') ? parseFloat(formData.get('fever_temp')) : null,

                                                // เก็บข้อมูล condition และ detail
                                                eye_condition: formData.get('eye_condition') || null,
                                                nose_condition: formData.get('nose_condition') || null,
                                                cough_type: formData.get('cough_type') || null,
                                                skin_wound_detail: formData.get('skin_wound_detail') || null,
                                                skin_rash_detail: formData.get('skin_rash_detail') || null,
                                                medicine_detail: formData.get('medicine_detail') || null,

                                                // เก็บข้อมูล reason
                                                hair_reason: formData.get('hair_reason') || null,
                                                eye_reason: formData.get('eye_reason') || null,
                                                nose_reason: formData.get('nose_reason') || null,
                                                symptoms_reason: formData.get('symptoms_reason') || null,
                                                medicine_reason: formData.get('medicine_reason') || null,
                                                illness_reason: formData.get('illness_reason') || null,
                                                accident_reason: formData.get('accident_reason') || null,
                                                teacher_note: formData.get('teacher_note') || null
                                            };

                                            // ส่งข้อมูลไปอัพเดท
                                            return fetch('../../include/process/update_health_record.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                },
                                                body: JSON.stringify(data)
                                            })
                                                .then(response => response.json())
                                                .then(result => {
                                                    if (result.status === 'success') {
                                                        return result;
                                                    }
                                                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                                                });
                                        }
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'บันทึกข้อมูลสำเร็จ',
                                                showConfirmButton: false,
                                                timer: 1500
                                            }).then(() => {
                                                loadResults(); // โหลดข้อมูลใหม่
                                            });
                                        }
                                    }).catch(error => {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'เกิดข้อผิดพลาด',
                                            text: error.message,
                                            confirmButtonText: 'ตกลง'
                                        });
                                    });
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด',
                                        text: 'ไม่สามารถโหลดข้อมูลได้',
                                        confirmButtonText: 'ตกลง'
                                    });
                                });
                        }

                        // เพิ่มฟังก์ชันลบข้อมูล
                        function deleteRecord(id) {
                            console.log('Deleting record:', id); // เพิ่มบรรทัดนี้

                            Swal.fire({
                                title: 'ยืนยันการลบ',
                                text: "คุณต้องการลบข้อมูลนี้ใช่หรือไม่?",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'ใช่, ลบข้อมูล',
                                cancelButtonText: 'ยกเลิก'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    console.log('User confirmed deletion'); // เพิ่มบรรทัดนี้

                                    fetch('../../include/process/delete_health_record.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({ id: id })
                                    })
                                        .then(response => {
                                            console.log('Response:', response); // เพิ่มบรรทัดนี้
                                            return response.json();
                                        })
                                        .then(result => {
                                            console.log('Result:', result); // เพิ่มบรรทัดนี้
                                            if (result.status === 'success') {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'ลบข้อมูลสำเร็จ',
                                                    text: 'ข้อมูลได้ถูกลบเรียบร้อยแล้ว',
                                                    confirmButtonText: 'ตกลง'
                                                }).then(() => {
                                                    window.location.reload();
                                                });
                                            } else {
                                                throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error); // เพิ่มบรรทัดนี้
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'เกิดข้อผิดพลาด',
                                                text: error.message,
                                                confirmButtonText: 'ตกลง'
                                            });
                                        });
                                }
                            });
                        }
                             // โหลดข้อมูลเริ่มต้นเมื่อเปิดหน้า
                            loadHealthResults();
                    </script>
                                                    </div>
                                                    </div>
                                                    </div>

        <!-- เพิ่มเนื้อหาสำหรับ tab growth -->
<?php elseif ($currentTab === 'growth'): ?>
<div class="card shadow-sm">
    <div class="card-body">
        <!-- ฟอร์มค้นหา -->
        <form id="searchGrowthForm" method="GET" class="row g-3 mb-4">
            <input type="hidden" name="studentid" value="<?= htmlspecialchars($studentid) ?>">
            <input type="hidden" name="tab" value="growth">

            <!-- เลือกประเภทการแสดงผล -->
            <div class="col-md-3">
                <label for="growthDisplayType" class="form-label">ประเภทการแสดงผล</label>
                <select class="form-select" id="growthDisplayType" name="displayType">
                    <option value="all">แสดงทั้งหมด</option>
                    <option value="date">เลือกวันที่</option>
                    <option value="range">เลือกช่วงวันที่</option>
                </select>
                                                    </div>

            <!-- ส่วนเลือกวันที่เดียว -->
            <div id="growthSingleDateSection" class="col-md-3" style="display: none;">
                <label for="growthDate" class="form-label">วันที่</label>
                <input type="date" class="form-control" id="growthDate" name="date" 
                    value="<?php echo isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); ?>">
                                                    </div>

            <!-- ส่วนเลือกช่วงวันที่ -->
            <div id="growthDateRangeSection" class="col-md-6" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <label for="growthStartDate" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="growthStartDate" name="startDate">
                                                    </div>
                    <div class="col-md-6">
                        <label for="growthEndDate" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="growthEndDate" name="endDate">
                                                    </div>
                                                    </div>
                                                    </div>

            <!-- ปุ่มค้นหา -->
            <div class="col-12">
                <button type="button" class="btn btn-primary" onclick="loadGrowthResults()">ค้นหา</button>
                <button type="button" class="btn btn-secondary" onclick="resetGrowthForm()">รีเซ็ต</button>
                                                    </div>
        </form>

        <!-- ตารางแสดงผล -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive" id="growthResultTable">
                    <!-- ข้อมูลจะถูกเพิ่มโดย JavaScript -->
                                                    </div>
                                                    </div>
                                                    </div>

        <script>
            // จัดการการแสดง/ซ่อนส่วนเลือกวันที่
            document.getElementById('growthDisplayType').addEventListener('change', function() {
                const singleDateSection = document.getElementById('growthSingleDateSection');
                const dateRangeSection = document.getElementById('growthDateRangeSection');
                
                singleDateSection.style.display = this.value === 'date' ? 'block' : 'none';
                dateRangeSection.style.display = this.value === 'range' ? 'block' : 'none';
            });

            // โหลดผลลัพธ์
                function loadGrowthResults() {
                    const formData = new FormData(document.getElementById('searchGrowthForm'));
                    const queryString = new URLSearchParams(formData).toString();

                    fetch(`../../include/function/get_student_growth.php?${queryString}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                       .then(data => {
                            const table = document.getElementById('growthResultTable');
                            const records = data.all_records;

                            if (!Array.isArray(records) || records.length === 0) {
                                table.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูลในวันที่เลือก</div>';
            return;
        }

                            let html = `
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>วันที่</th>
                                                <th>รหัสนักเรียน</th>
                                                <th>ชื่อ-นามสกุล</th>
                                                <th>กลุ่มเรียน</th>
                                                <th>ห้องเรียน</th>
                                                <th>เวลาที่บันทึก</th>
                                                <th>ผลการประเมิน</th>
                                                <th>การจัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;

                            records.forEach(record => {
                                const checkDate = new Date(record.created_at).toLocaleDateString('th-TH', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit',
                                    hour12: false
                                });

                                // สร้างชื่อนักเรียนจากข้อมูลที่มี
                                let studentName = record.student_name || '';
                                if (!studentName) {
                                    const prefix = record.prefix_th || '';
                                    const firstname = record.firstname_th || '';
                                    const lastname = record.lastname_th || '';
                                    studentName = `${prefix} ${firstname} ${lastname}`.trim() || '-';
                                }

                                html += `
                                    <tr>
                                        <td>${checkDate}</td>
                                        <td>${record.student_id || '-'}</td>
                                        <td>${studentName}</td>
                                        <td>${record.child_group || '-'}</td>
                                        <td>${record.classroom || '-'}</td>
                                        <td>${record.created_at || '-'}</td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm" onclick="showGrowthDetails('${record.student_id}', '${record.id}')">
                                                <i class="bi bi-eye"></i> ดูผลการประเมิน
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="showGrowthOverview('${record.student_id}')">
                                                <i class="bi bi-graph-up"></i> ดูกราฟภาพรวม
                                            </button>
                                        </td>
                                        <td>
                                            <?php if ($is_admin || $is_teacher): ?>
                                                ${renderGrowthActionButtons(record)}
                                            <?php else: ?>
                                                <span class="text-muted">ดูข้อมูลเท่านั้น</span>
     <?php endif; ?>
                                        </td>
                                    </tr>
                                `;
                            });

                            html += `
                                        </tbody>
                                    </table>
 </div>
                            `;
                            table.innerHTML = html;
                        })

                                .catch(error => {
                                    console.error('Error:', error);
                                    document.getElementById('growthResultTable').innerHTML =
                                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                                });
                        }


            // รับสิทธิ์จาก PHP เข้ามาใช้ใน JS
            const studentid = <?= $studentid ?>;
            const isAdmin = <?= json_encode($is_admin) ?>;
            const isTeacher = <?= json_encode($is_teacher) ?>;

            function renderGrowthActionButtons(student) {
                let buttons = '';

                const hasGrowthRecord = student.id && student.status !== null;

                if (hasGrowthRecord) {
                    if (student.is_recorded === false) {
                        if (student.status === 'present' && !student.check_date) {
                            buttons = `
                                <button type="button" class="btn btn-success btn-sm" onclick="checkInAttendance(${student.id})">
                                    <i class="fas fa-check"></i> เช็คอิน
                                </button>
                            `;
                        } else if (student.status === 'absent' || student.status === 'leave') {
                            buttons = `
                                <button type="button" class="btn btn-warning btn-sm" onclick="editRecord(${student.id})">
                                    <i class="fas fa-edit"></i> แก้ไขสถานะ
                                </button>
                            `;

                            if (isAdmin) {
                                buttons += `
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteRecord(${student.id})">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                `;
                            }
                    }
                } else {
                        buttons = `
                            <button type="button" class="btn btn-warning btn-sm" onclick="editRecord(${student.id})">
                                <i class="fas fa-edit"></i> แก้ไข
                            </button>
                        `;

                        if (isAdmin) {
                            buttons += `
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteRecord(${student.id})">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                            `;
                        }
                    }
                } else {
                    buttons = `
                        <button type="button" class="btn btn-primary btn-sm" 
                            onclick="addAttendance({
                                student_id: '${student.student_id}',
                                prefix_th: '${student.prefix_th}',
                                firstname_th: '${student.firstname_th}',
                                lastname_th: '${student.lastname_th}'
                            })">
                            <i class="fas fa-plus"></i> เพิ่ม
                        </button>
                    `;
                }

                return buttons;
            }


// ฟังก์ชันแสดงรายละเอียดการเจริญเติบโต
    function showGrowthDetails(studentId, recordId) {
        console.log('Showing growth details for student:', studentId, 'record:', recordId);

        fetch(`../../include/function/get_student_growth_records.php?student_id=${studentId}&record_id=${recordId}`)
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
                const detailsHtml = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>ข้อมูลการวัด</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>อายุ</th>
                                    <td>${record.age_year || 0}ปี ${record.age_month || 0}เดือน ${record.age_day || 0}วัน</td>
                                </tr>
                                <tr>
                                    <th>น้ำหนัก</th>
                                    <td>${record.weight || 0} กก.</td>
                                </tr>
                                <tr>
                                    <th>ส่วนสูง</th>
                                    <td>${record.height || 0} ซม.</td>
                                </tr>
                                <tr>
                                    <th>เส้นรอบวงศีรษะ</th>
                                    <td>${record.head_circumference || 0} ซม.</td>
                                </tr>
                                <tr>
                                    <th>วันที่บันทึก</th>
                                    <td>${record.created_at ? new Date(record.created_at).toLocaleDateString('th-TH', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) : '-'}</td>
                                </tr>
                            </table>
</div>
                        <div class="col-md-6">
                            <h5>ผลการประเมิน</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>น้ำหนักตามเกณฑ์อายุ</th>
                                    <td>${record.growth_status.weight || '-'}</td>
                                </tr>
                                <tr>
                                    <th>ส่วนสูงตามเกณฑ์อายุ</th>
                                    <td>${record.growth_status.height_age || '-'}</td>
                                </tr>
                                <tr>
                                    <th>น้ำหนักตามเกณฑ์ส่วนสูง</th>
                                    <td>${record.growth_status.weight_height || '-'}</td>
                                </tr>
                                <tr>
                                    <th>เส้นรอบวงศีรษะ</th>
                                    <td>${record.growth_status.head || '-'}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-12 mt-4">
                        <h5>ผลการประเมินพัฒนาการ</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%">ด้านการเคลื่อนไหว (GM)</th>
                                <td>
                                    ${record.gm_status ? `
                                        <span class="badge ${record.gm_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.gm_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.gm_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.gm_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านกล้ามเนื้อมัดเล็กและสติปัญญา (FM)</th>
                                <td>
                                    ${record.fm_status ? `
                                        <span class="badge ${record.fm_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.fm_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.fm_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.fm_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านการเข้าใจภาษา (RL)</th>
                                <td>
                                    ${record.rl_status ? `
                                        <span class="badge ${record.rl_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.rl_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.rl_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.rl_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านการใช้ภาษา (EL)</th>
                                <td>
                                    ${record.el_status ? `
                                        <span class="badge ${record.el_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.el_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.el_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.el_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านการช่วยเหลือตัวเองและสังคม (PS)</th>
                                <td>
                                    ${record.ps_status ? `
                                        <span class="badge ${record.ps_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.ps_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.ps_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.ps_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                        </table>
                    </div>
                    </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}</h6>
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}</h6>
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ ${record.age_range || '0-2'} ปี เพศ${sex}</h6>
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sex}</h6>
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
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
                        didRender: () => {
                        try {
                            const charts = [
                                { id: 'weightChart', type: 'weight' },
                                { id: 'heightChart', type: 'height' },
                                { id: 'weightHeightChart', type: 'weight_height' },
                                { id: 'headChart', type: 'head' }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                if (chartElement) {
                                    console.log('Creating chart:', chart.id, 'with sex:', sex); // Debug log
                                const newChart = new Chart(chartElement, {
                                    type: 'line',
                                        data: prepareChartData(chart.type, allRecords, record.sex),
                                    options: getChartOptions(chart.type)
                                });

                                chartElement.onclick = () => {
                                        expandChart(chart.type, allRecords, record.student_name, record.sex);
                                };
                                chartElement.style.cursor = 'pointer';
                                }
                            });
                        } catch (error) {
                            console.error('Error creating charts:', error);
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
        fetch(`../../include/function/get_student_growth_records.php?student_id=${studentId}`)
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

// เพิ่มฟังก์ชันแสดงกราฟภาพรวม
    function showGrowthOverview(studentId) {
        fetch(`../../include/function/get_student_growth_records.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const allRecords = data.all_records;
                    const sex = allRecords[0].sex === 'M' ? 'ชาย' : 'หญิง';
                    if (allRecords.length === 0) {
                        Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลการเจริญเติบโต', 'info');
                        return;
                    }

                    const detailsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}</h6>
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}</h6>
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ ${allRecords.age_range || '0-2'} ปี เพศ${sex}</h6>
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sex}</h6>
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
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: getChartTitle(type)
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

    // เพิ่มฟังก์ชันสำหรับขยายกราฟ
    function expandChart(chartType, records, studentName) {
        const allRecords = data.all_records;
        const sex = allRecords[0].sex === 'M' ? 'ชาย' : 'หญิง';
        const modalHtml = `
            <div class="card">
                <div class="card-body">
                    <canvas id="expandedChart" style="height: 70vh;"></canvas>
                </div>
            </div>
        `;

        let title = '';
        switch(chartType) {
            case 'weight':
                title = `กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}`;
                break;
            case 'height':
                title = `กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}`;
                break;
            case 'weight_height':
                title = `กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ 0-2 ปี เพศ${sex}`;
                break;
            case 'head':
                title = `กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sex}`;
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
        fetch(`../../include/function/get_growth_record.php?record_id=${recordId}`)
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

                            fetch('../../include/process/save_growth_data.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success')
                                    .then(() => {
                                        loadGrowthResults();
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

                fetch('../../include/process/delete_growth_record.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('สำเร็จ', 'ลบข้อมูลเรียบร้อยแล้ว', 'success')
                        .then(() => {
                            loadGrowthResults(); // โหลดข้อมูลใหม่
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

            // ฟังก์ชันรีเซ็ตฟอร์ม
            function resetGrowthForm() {
                document.getElementById('searchGrowthForm').reset();
                loadGrowthResults();
            }

            // เรียกใช้ฟังก์ชันเมื่อโหลดหน้า
            document.addEventListener('DOMContentLoaded', () => {
                loadGrowthResults();
            });

            // ส่งฟอร์มเมื่อมีการเปลี่ยนแปลงค่าใดๆ
            document.querySelectorAll('#searchGrowthForm select, #searchGrowthForm input').forEach(element => {
                element.addEventListener('change', () => loadGrowthResults());
            });
</script>
    </div>
</div>
<?php endif; ?>
</div>
</div>

<!-- Modal แก้ไขข้อมูล -->
<?php if ($is_admin): ?>
    <!-- Modal ยืนยันการลบ -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
        aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">ยืนยันการลบข้อมูล</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    คุณต้องการลบข้อมูลของเด็กคนนี้หรือไม่? การลบข้อมูลจะไม่สามารถกู้คืนได้
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger"
                        onclick="confirmDelete('<?= htmlspecialchars($child['studentid']) ?>')">ยืนยันการลบ</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- เพิ่ม Script สำหรับการลบข้อมูล -->
<script>
    function confirmDelete(studentId) {
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: "คุณต้องการลบข้อมูลนี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // ส่งคำขอลบข้อมูล
                fetch('../../include/function/delete_child.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `studentid=${encodeURIComponent(studentId)}`
                })
                    .then(response => response.text())
                    .then(result => {
                        if (result.includes("success")) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบข้อมูลสำเร็จ',
                                text: 'ข้อมูลเด็กถูกลบเรียบร้อยแล้ว',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.href = '../../views/student/children_history.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: 'ไม่สามารถลบข้อมูลได้ กรุณาลองใหม่อีกครั้ง'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง'
                        });
                    });
            }
        });
    }
                </script>

<script>
    // ฟังก์ชันสำหรับแปลงวันที่เป็นรูปแบบไทย
    function formatThaiDate(dateString) {
        return new Date(dateString).toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // ฟังก์ชันสำหรับแปลงเวลา
    function formatTime(timeString) {
        if (!timeString) return '-';
        return new Date(timeString).toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }) + ' น.';
    }

    // แปลงวันที่และเวลาเมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function () {
        const dateCells = document.querySelectorAll('.date-cell');
        const timeCells = document.querySelectorAll('.time-cell');

        dateCells.forEach(cell => {
            const dateString = cell.getAttribute('data-date');
            if (dateString) {
                cell.textContent = formatThaiDate(dateString);
            }
        });

        timeCells.forEach(cell => {
            const timeString = cell.getAttribute('data-time');
            cell.textContent = formatTime(timeString);
        });
    });
</script>

<script>
    // เพิ่มฟังก์ชัน JavaScript สำหรับจัดการฟอร์มค้นหา
    function resetForm() {
        document.getElementById('searchForm').reset();
        document.getElementById('searchForm').submit();
    }

    function resetHealthForm() {
        document.getElementById('searchHealthForm').reset();
        document.getElementById('searchHealthForm').submit();
    }
</script>

<!-- เพิ่ม Modal สำหรับแสดง QR Code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrCodeModalLabel">
                    <i class="bi bi-qr-code me-2"></i>QR Code
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrCodeImage" class="mb-3"></div>
                <div id="studentInfo" class="student-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="downloadQRCode()">
                    <i class="bi bi-download me-2"></i>ดาวน์โหลด QR Code
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- เพิ่ม Script สำหรับจัดการ QR Code -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
function generateQRCode(studentId) {
    // เช็คว่ามี QR Code อยู่แล้วหรือไม่
    const existingQRCode = document.querySelector('.qr-code-container img');
    if (existingQRCode && existingQRCode.src) {
        // ถ้ามี QR Code อยู่แล้ว ให้แสดง QR Code เดิม
        document.getElementById('qrCodeImage').innerHTML = '';
        
        const img = document.createElement('img');
        img.src = existingQRCode.src;
        img.alt = 'QR Code';
        img.className = 'img-fluid';
        document.getElementById('qrCodeImage').appendChild(img);

        // แสดงข้อมูลนักเรียน
        document.getElementById('studentInfo').innerHTML = `
            <div class="qr-student-info">
                <h6 class="mb-2">${'<?= htmlspecialchars($child['prefix_th'] . $child['firstname_th'] . ' ' . $child['lastname_th']) ?>'}</h6>
                <p class="mb-1">รหัสนักเรียน: ${studentId}</p>
                <p class="mb-0">ห้องเรียน: ${'<?= htmlspecialchars($child['classroom']) ?>'}</p>
            </div>
        `;

        // แสดง Modal
        const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
        modal.show();
    } else {
        // ถ้าไม่มี QR Code ให้สร้างใหม่
        fetch('../../include/function/generate-qr.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ student_id: studentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // เคลียร์ค่าเก่า
                document.getElementById('qrCodeImage').innerHTML = '';
                
                // แสดง QR Code จาก URL ที่ได้
                const img = document.createElement('img');
                img.src = data.qr_code;
                img.alt = 'QR Code';
                img.className = 'img-fluid';
                document.getElementById('qrCodeImage').appendChild(img);

                // แสดงข้อมูลนักเรียน
                document.getElementById('studentInfo').innerHTML = `
                    <div class="qr-student-info">
                        <h6 class="mb-2">${data.student.prefix} ${data.student.first_name} ${data.student.last_name}</h6>
                        <p class="mb-1">รหัสนักเรียน: ${data.student.student_id}</p>
                        <p class="mb-0">ห้องเรียน: ${data.student.classroom}</p>
                    </div>
                `;

                // แสดง Modal
                const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
                modal.show();

                // รีเฟรชหน้าเพื่อแสดง QR Code ใหม่
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถสร้าง QR Code ได้'
            });
        });
    }
}

// แก้ไขฟังก์ชัน downloadQRCode ให้รองรับทั้ง canvas และ img
function downloadQRCode() {
    const qrImage = document.querySelector('#qrCodeImage img');
    if (qrImage) {
        // สร้าง canvas ชั่วคราวเพื่อแปลงรูปภาพ
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // กำหนดขนาด canvas ตามขนาดรูปภาพ
        canvas.width = qrImage.naturalWidth;
        canvas.height = qrImage.naturalHeight;
        
        // วาดรูปภาพลงใน canvas
        ctx.drawImage(qrImage, 0, 0);
        
        // สร้าง link สำหรับดาวน์โหลด
        const link = document.createElement('a');
        link.download = 'qrcode_<?= htmlspecialchars($child['studentid']) ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
}
</script>

<script>
function toggleAllergyEdit() {
    const displaySection = document.getElementById('allergyDisplaySection');
    const editSection = document.getElementById('allergyEditSection');
    
    if (displaySection.style.display !== 'none') {
        displaySection.style.display = 'none';
        editSection.style.display = 'block';
    } else {
        displaySection.style.display = 'block';
        editSection.style.display = 'none';
    }
}

// แก้ไขฟังก์ชันบันทึกฟอร์มหลัก
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('check');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const confirmResult = await Swal.fire({
            title: 'ยืนยันการบันทึก',
            text: "คุณต้องการบันทึกข้อมูลใช่หรือไม่?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, บันทึกข้อมูล',
            cancelButtonText: 'ยกเลิก'
        });

        if (confirmResult.isConfirmed) {
            try {
                // บันทึกข้อมูลหลักก่อน
                const formData = new FormData(form);
                // แก้ไขตรงนี้ - ใช้ student_id แทน studentid
                const studentId = formData.get('student_id');
                
                console.log('Student ID:', studentId); // เพิ่ม debug log

                const fileInput = document.getElementById('fileInput');
                const profileImage = document.getElementById('profileImage');
                
                if (!fileInput.files.length) {
                    formData.delete('profile_image');
                }
                if (!profileImage.dataset.changed) {
                    formData.append('keep_existing_image', '1');
                }

                // บันทึกข้อมูลหลักของเด็ก
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.text();
                console.log('Main form response:', result);

                if (!result.includes('success')) {
                    throw new Error('บันทึกข้อมูลไม่สำเร็จ');
                }

                // บันทึกข้อมูลแพ้ยา
                const drugForm = document.getElementById('drugAllergySection');
                if (drugForm) {
                    const drugName = drugForm.querySelector('input[name="drug_name"]')?.value;
                    const detectionMethod = drugForm.querySelector('select[name="drug_detection_method"]')?.value;
                    const symptoms = drugForm.querySelector('select[name="drug_symptoms"]')?.value;
                    const hasAllergyCard = drugForm.querySelector('select[name="has_drug_allergy_card"]')?.value;

                    console.log('Drug allergy data:', { drugName, detectionMethod, symptoms, hasAllergyCard });

                    if (drugName || detectionMethod || symptoms || hasAllergyCard) {
                        console.log('Sending drug allergy data for student:', studentId);
                        
                        // แปลงค่า hasAllergyCard เป็น boolean string
                        const hasAllergyCardValue = hasAllergyCard === '1' ? 'true' : 'false';
                        
                        const drugData = new URLSearchParams({
                            action: 'update', // เปลี่ยนเป็น update
                            type: 'drug',
                            student_id: studentId,
                            drug_name: drugName || '',
                            detection_method: detectionMethod || '',
                            symptoms: symptoms || '',
                            has_allergy_card: hasAllergyCardValue // ใช้ค่าที่แปลงแล้ว
                        });

                        console.log('Drug allergy data to send:', drugData.toString());
                        
                        const drugResponse = await fetch('../../include/process/manage_allergies.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: drugData
                        });

                        const drugResult = await drugResponse.json();
                        console.log('Drug allergy response:', drugResult);

                        if (drugResult.status === 'error') {
                            throw new Error(drugResult.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูลแพ้ยา');
                        }
                    }
                }

                // บันทึกข้อมูลแพ้อาหาร
                const foodForm = document.getElementById('foodAllergySection');
                if (foodForm) {
                    const foodName = foodForm.querySelector('input[name="food_name"]')?.value || '';
                    const detectionMethod = foodForm.querySelector('select[name="food_detection_method"]')?.value || '';
                    const digestiveSymptoms = Array.from(foodForm.querySelectorAll('input[name="digestive_symptoms[]"]:checked')).map(cb => cb.value);
                    const skinSymptoms = Array.from(foodForm.querySelectorAll('input[name="skin_symptoms[]"]:checked')).map(cb => cb.value);
                    const respiratorySymptoms = Array.from(foodForm.querySelectorAll('input[name="respiratory_symptoms[]"]:checked')).map(cb => cb.value);

                    // บันทึกข้อมูลแพ้อาหารถ้ามีการกรอกข้อมูล
                    if (foodName || detectionMethod || digestiveSymptoms.length || skinSymptoms.length || respiratorySymptoms.length) {
                        const foodData = new URLSearchParams({
                            action: 'add',
                            type: 'food',
                            student_id: studentId,
                            food_name: foodName,
                            detection_method: detectionMethod,
                            digestive_symptoms: JSON.stringify(digestiveSymptoms),
                            skin_symptoms: JSON.stringify(skinSymptoms),
                            respiratory_symptoms: JSON.stringify(respiratorySymptoms)
                        });

                        await fetch('../../include/process/manage_allergies.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: foodData
                        });
                    }
                }

                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });

            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
    });
});
</script>

<!-- เพิ่ม Script สำหรับจัดการข้อมูลแพ้ยา/แพ้อาหาร -->
<script>
function editAllergies(studentId, type) {
    // โหลดข้อมูลปัจจุบันก่อนแสดง modal
    fetch('../../include/process/manage_allergies.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get',
            type: type,
            student_id: studentId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(response => {
        if (response.status !== 'success') {
            throw new Error(response.message || 'Failed to load data');
        }

        const currentData = response.data?.[0] || {};
        let modalContent = '';

        if (type === 'drug') {
            modalContent = `
                 <form id="editDrugAllergyForm">
            <input type="hidden" name="student_id" value="${studentId}">
            <div class="mb-3">
                <label class="form-label">ชื่อยาที่แพ้ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="drug_name" value="${currentData.drug_name || ''}" required>
                <div class="invalid-feedback">กรุณากรอกชื่อยาที่แพ้</div>
            </div>
            <div class="mb-3">
                <label class="form-label">วิธีที่ทราบว่าแพ้ <span class="text-danger">*</span></label>
                <select class="form-select" name="detection_method" required>
                    <option value="">เลือกวิธี</option>
                    <option value="symptoms_after_use" ${currentData.detection_method === 'symptoms_after_use' ? 'selected' : ''}>มีอาการแพ้หลังใช้ยา</option>
                    <option value="skin_testing" ${currentData.detection_method === 'skin_testing' ? 'selected' : ''}>การทดสอบทางผิวหนัง (skin testing)</option>
                    <option value="blood_test" ${currentData.detection_method === 'blood_test' ? 'selected' : ''}>ทดสอบโดยการเจาะเลือด</option>
                    <option value="repeat_use" ${currentData.detection_method === 'repeat_use' ? 'selected' : ''}>ทดสอบโดยการใช้ยาซ้ำ</option>
                </select>
                <div class="invalid-feedback">กรุณาเลือกวิธีที่ทราบว่าแพ้</div>
            </div>
            <div class="mb-3">
                <label class="form-label">ลักษณะอาการแพ้ <span class="text-danger">*</span></label>
                <select class="form-select" name="symptoms" required>
                    <option value="">เลือกลักษณะอาการ</option>
                    <option value="type1" ${currentData.symptoms === 'type1' ? 'selected' : ''}>ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก และหายใจลำบาก</option>
                    <option value="type2" ${currentData.symptoms === 'type2' ? 'selected' : ''}>ผื่นแดงลักษณะเป็นผื่นราบ และผื่นนูน กระจายอย่างสมมาตร</option>
                    <option value="type3" ${currentData.symptoms === 'type3' ? 'selected' : ''}>ผิวแดงทั่วตัวและผื่นตุ่มหนองขนาดเล็กจำนวนมาก</option>
                    <option value="type4" ${currentData.symptoms === 'type4' ? 'selected' : ''}>ผื่นที่เกิดขึ้นสามารถพบได้หลายแบบ</option>
                    <option value="type5" ${currentData.symptoms === 'type5' ? 'selected' : ''}>ผื่นตุ่มน้ำ มีผิวหนังกำพร้าตายและหลุดลอก</option>
                </select>
                <div class="invalid-feedback">กรุณาเลือกลักษณะอาการแพ้</div>
            </div>
            <div class="mb-3">
                <label class="form-label">บัตรแพ้ยา <span class="text-danger">*</span></label>
                <select class="form-select" name="has_allergy_card" required>
                    <option value="1" ${currentData.has_allergy_card ? 'selected' : ''}>มีบัตรแพ้ยา</option>
                    <option value="0" ${!currentData.has_allergy_card ? 'selected' : ''}>ไม่มีบัตรแพ้ยา</option>
                </select>
                <div class="invalid-feedback">กรุณาเลือกสถานะบัตรแพ้ยา</div>
            </div>
           <button type="button" class="btn btn-danger deleteDrugAllergyBtn"
        data-student-id="${studentId}"
        data-drug-name="${currentData.drug_name}">
        ลบประวัติการแพ้ยา
</button>

        </form>
            `;
        } else if (type === 'food') {
            // แปลงข้อมูล array จาก PostgreSQL เป็น array JavaScript
            const digestiveSymptoms = currentData.digestive_symptoms ? 
                currentData.digestive_symptoms.replace(/{|}/g, '').split(',') : [];
            const skinSymptoms = currentData.skin_symptoms ? 
                currentData.skin_symptoms.replace(/{|}/g, '').split(',') : [];
            const respiratorySymptoms = currentData.respiratory_symptoms ? 
                currentData.respiratory_symptoms.replace(/{|}/g, '').split(',') : [];

            modalContent = `
                <form id="editFoodAllergyForm">
                    <input type="hidden" name="student_id" value="${studentId}">
                    <div class="mb-3">
                        <label class="form-label">ชื่ออาหารที่แพ้ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="food_name" value="${currentData.food_name || ''}" required>
                        <div class="invalid-feedback">กรุณากรอกชื่ออาหารที่แพ้</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วิธีที่ทราบว่าแพ้ <span class="text-danger">*</span></label>
                        <select class="form-select" name="detection_method" required>
                            <option value="">เลือกวิธี</option>
                            <option value="symptoms_after_eat" ${currentData.detection_method === 'symptoms_after_eat' ? 'selected' : ''}>
                                มีอาการแพ้หลังรับประทานอาหารชนิดนั้น
                            </option>
                            <option value="skin_testing" ${currentData.detection_method === 'skin_testing' ? 'selected' : ''}>
                                การทดสอบทางผิวหนัง (skin testing)
                            </option>
                            <option value="blood_test" ${currentData.detection_method === 'blood_test' ? 'selected' : ''}>
                                ทดสอบโดยการเจาะเลือด
                            </option>
                            <option value="repeat_eat" ${currentData.detection_method === 'repeat_eat' ? 'selected' : ''}>
                                ทดสอบโดยการรับประทานอาหารชนิดนั้นซ้ำ
                            </option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกวิธีที่ทราบว่าแพ้</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อาการแพ้</label>
                        <div class="ms-3">
                            <h6 class="mb-2">อาการทางระบบทางเดินอาหาร:</h6>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="digestive_symptoms[]" 
                                       value="bloody_stool" ${digestiveSymptoms.includes('bloody_stool') ? 'checked' : ''}>
                                <label class="form-check-label">ถ่ายเป็นมูกเลือด</label>
                                                    </div>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="digestive_symptoms[]" 
                                       value="vomiting" ${digestiveSymptoms.includes('vomiting') ? 'checked' : ''}>
                                <label class="form-check-label">อาเจียน</label>
                                                    </div>

                            <h6 class="mb-2 mt-3">อาการทางผิวหนัง:</h6>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="skin_symptoms[]" 
                                       value="urticaria" ${skinSymptoms.includes('urticaria') ? 'checked' : ''}>
                                <label class="form-check-label">ผื่นลมพิษทั่วตัว</label>
                                                    </div>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="skin_symptoms[]" 
                                       value="eye_swelling" ${skinSymptoms.includes('eye_swelling') ? 'checked' : ''}>
                                <label class="form-check-label">ตาบวม</label>
                                                    </div>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="skin_symptoms[]" 
                                       value="mouth_rash" ${skinSymptoms.includes('mouth_rash') ? 'checked' : ''}>
                                <label class="form-check-label">มีผื่นรอบปาก</label>
                                                    </div>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="skin_symptoms[]" 
                                       value="atopic_dermatitis" ${skinSymptoms.includes('atopic_dermatitis') ? 'checked' : ''}>
                                <label class="form-check-label">มีผื่นคันเป็น ๆ หาย ๆ ขึ้นที่บริเวณแก้ม ด้านนอกแขน ข้อศอก ข้อมือ ที่เรียกว่า ผื่นภูมิแพ้ผิวหนัง</label>
                                                    </div>

                            <h6 class="mb-2 mt-3">อาการทางระบบหายใจ:</h6>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="respiratory_symptoms[]" 
                                       value="cough" ${respiratorySymptoms.includes('cough') ? 'checked' : ''}>
                                <label class="form-check-label">ไอ</label>
                                                    </div>
                                                    <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="respiratory_symptoms[]" 
                                       value="wheezing" ${respiratorySymptoms.includes('wheezing') ? 'checked' : ''}>
                                <label class="form-check-label">หายใจมีเสียงวี้ด</label>
                                                    </div>
                        </div>
                    </div>
                    <button type="button" id="deleteFoodAllergyBtn" class="btn btn-danger mt-3">ลบประวัติการแพ้อาหาร</button>
                </form>
            `;          
    }

    // แสดง SweetAlert2 modal
        Swal.fire({
            title: `แก้ไขข้อมูลการแพ้${type === 'drug' ? 'ยา' : 'อาหาร'}`,
            html: modalContent,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                // เก็บข้อมูลจากฟอร์ม
                const form = type === 'drug' ? 
                    document.getElementById('editDrugAllergyForm') : 
                    document.getElementById('editFoodAllergyForm');
                const formData = new FormData(form);
                
                // แปลงข้อมูลเป็น URLSearchParams
                const data = new URLSearchParams();
                data.append('action', 'update');
                data.append('type', type);
                data.append('student_id', formData.get('student_id'));
          
                if (type === 'drug') {
                    // ส่งข้อมูลแพ้ยา
                    data.append('drug_name', formData.get('drug_name'));
                    data.append('detection_method', formData.get('detection_method'));
                    data.append('symptoms', formData.get('symptoms'));
                    data.append('has_allergy_card', formData.get('has_allergy_card'));
                } else {
                    // ส่งข้อมูลแพ้อาหาร
                    data.append('food_name', formData.get('food_name'));
                    data.append('detection_method', formData.get('detection_method'));

                    // รวบรวมค่า checkbox ที่ถูกเลือก
                    const digestiveSymptoms = Array.from(form.querySelectorAll('input[name="digestive_symptoms[]"]:checked'))
                        .map(input => input.value);
                    const skinSymptoms = Array.from(form.querySelectorAll('input[name="skin_symptoms[]"]:checked'))
                        .map(input => input.value);
                    const respiratorySymptoms = Array.from(form.querySelectorAll('input[name="respiratory_symptoms[]"]:checked'))
                        .map(input => input.value);

                    // แปลงเป็น JSON string ก่อนส่ง
                    data.append('digestive_symptoms', JSON.stringify(digestiveSymptoms));
                    data.append('skin_symptoms', JSON.stringify(skinSymptoms));
                    data.append('respiratory_symptoms', JSON.stringify(respiratorySymptoms));
                }

                
                // ส่งข้อมูลไปอัพเดท
                return fetch('../../include/process/manage_allergies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: data
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.status !== 'success') {
                        throw new Error(result.message || 'Failed to update data');
                    }
                    return result;
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    text: 'อัพเดทข้อมูลเรียบร้อยแล้ว',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // โหลดข้อมูลใหม่
                    loadAllergiesData();
                });
            }
        }).catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: error.message
            });
        });
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลดข้อมูลได้'
        });
    });
}


</script>

<script>
// เพิ่ม Event Handler สำหรับปุ่มลบประวัติการแพ้ยา
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('deleteDrugAllergyBtn')) {
        const studentId = e.target.getAttribute('data-student-id');
        const drugName = e.target.getAttribute('data-drug-name');
        
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: `คุณต้องการลบประวัติการแพ้ยา ${drugName} ใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const data = new URLSearchParams();
                data.append('action', 'delete');
                data.append('type', 'drug');
                data.append('student_id', studentId);
                data.append('drug_name', drugName);

                fetch('../../include/process/manage_allergies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: data
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบสำเร็จ',
                            text: 'ลบประวัติการแพ้ยาสำเร็จแล้ว',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            loadAllergiesData(); // โหลดใหม่หลังแสดง Swal สักครู่
                        });
                    } else {
                        throw new Error(result.message || 'Failed to delete data');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message
                    });
                });
            }
        });
    }
});

// เพิ่ม Event Handler สำหรับปุ่มลบประวัติการแพ้อาหาร
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'deleteFoodAllergyBtn') {
        const studentId = e.target.getAttribute('data-student-id');
        const foodName = e.target.getAttribute('data-food-name');
        
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: `คุณต้องการลบประวัติการแพ้อาหาร ${foodName} ใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const data = new URLSearchParams();
                data.append('action', 'delete');
                data.append('type', 'food');
                data.append('student_id', studentId);
                data.append('food_name', foodName);

                fetch('../../include/process/manage_allergies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: data
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบสำเร็จ',
                            text: 'ลบประวัติการแพ้อาหารสำเร็จแล้ว',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            loadAllergiesData();
                        });
                    } else {
                        throw new Error(result.message || 'Failed to delete data');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message
                    });
                });
            }
        });
    }
});

</script>

    <!-- เพิ่มในส่วน head -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-zoom/1.2.1/chartjs-plugin-zoom.min.js"></script>

</body>

</html>