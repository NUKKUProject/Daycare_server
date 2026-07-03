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
    $user_studentid = $_SESSION['username']; // สมมติว่ามี session studentid

    if ($studentid !== $user_studentid) {
        // ถ้าไม่ตรงกัน ให้ redirect กลับหน้า dashboard
        header('Location: ./student_dashboard.php');
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
?>
<link rel="stylesheet" href="../../../public/assets/css/view_child1.css">
<style>
.status-badge.status-late { background:#fef3c7;color:#d97706; }
.status-badge.status-leave { background:#fef3c7;color:#d97706; }
</style>

<!-- ===== Page Wrapper ===== -->
 <main class="main-content">
    <div class="page-wrapper">

  <!-- ===== Profile Hero ===== -->
  <div class="profile-hero">
    <div class="profile-avatar-wrap">
        <img src="<?= !empty($child['profile_image']) 
            ? htmlspecialchars($child['profile_image']) 
            : '../../../public/assets/images/avatar.png' ?>" 
            alt="Profile" class="profile-avatar" />
        <div class="profile-status-dot"></div>
    </div>

    <div class="profile-details">
        <div class="profile-name">
            <?= htmlspecialchars($child['prefix_th'] . $child['firstname_th'] . ' ' . $child['lastname_th']) ?>
        </div>
        <div style="font-size:0.82rem;color:var(--gray-400);margin-bottom:0.5rem;">
            <?= htmlspecialchars($child['prefix_en'] . ' ' . $child['firstname_en'] . ' ' . $child['lastname_en']) ?>
        </div>
        <div class="profile-meta">
            <span class="meta-chip">
                <i class="bi bi-person-badge"></i> 
                <?= htmlspecialchars($child['studentid']) ?>
            </span>
            <span class="meta-chip">
                <i class="bi bi-mortarboard"></i> 
                ปีการศึกษา <?= htmlspecialchars($child['academic_year']) ?>
            </span>
            <span class="meta-chip">
                <i class="bi bi-people"></i> 
                <?= htmlspecialchars($child['child_group']) ?>
            </span>
            <span class="meta-chip">
                <i class="bi bi-door-open"></i> 
                ห้อง <?= htmlspecialchars($child['classroom']) ?>
            </span>
        </div>
    </div>

    <!-- Profile Actions -->
    <div class="profile-actions">
        <?php if ($is_admin || $is_teacher): ?>
            <button class="btn-action btn-export" id="btnExport">
                <i class="bi bi-file-earmark-excel"></i><span>Export</span>
            </button>
        <?php endif; ?>
        <?php if ($is_admin): ?>
            <button class="btn-action btn-qr" onclick="generateQRCode('<?= htmlspecialchars($child['studentid']) ?>')">
                <i class="bi bi-qr-code"></i><span>QR Code</span>
            </button>
            <button class="btn-action btn-edit" id="btnEdit">
                <i class="bi bi-pencil"></i><span>แก้ไข</span>
            </button>
            <button class="btn-action btn-delete" id="btnDelete" onclick="confirmDelete('<?= htmlspecialchars($child['studentid']) ?>')">
                <i class="bi bi-trash"></i><span>ลบ</span>
            </button>
        <?php endif; ?>
    </div>
</div>

  <!-- Allergy Warning Banner -->
  <div class="allergy-banner" id="allergyBanner" style="display:none;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
      <strong>⚠️ แจ้งเตือน: พบประวัติการแพ้</strong><br/>
      <span id="allergyText">กำลังโหลดข้อมูล...</span>
    </div>
  </div>

  <!-- Edit Mode Bar -->
  <div class="edit-mode-bar" id="editModeBar">
    <i class="bi bi-pencil-square"></i>
    <span>กำลังแก้ไขข้อมูล — กรุณาบันทึกหรือยกเลิกเมื่อเสร็จสิ้น</span>
    <div style="margin-left:auto;display:flex;gap:0.5rem;">
      <button class="btn-action btn-save" id="btnSave" style="padding:5px 12px;">
        <i class="bi bi-check-lg"></i><span>บันทึก</span>
      </button>
      <button class="btn-action btn-cancel" id="btnCancel" style="padding:5px 12px;">
        <i class="bi bi-x-lg"></i><span>ยกเลิก</span>
      </button>
    </div>
  </div>

  <!-- ===== Tab Navigation ===== -->
  <div class="tab-nav-wrap">
    <button class="tab-btn active" data-tab="profile">
      <i class="bi bi-person-badge"></i>
      <span>ประวัติประจำตัว</span>
    </button>
    <button class="tab-btn" data-tab="vaccine">
      <i class="bi bi-shield-check"></i>
      <span>วัคซีน</span>
    </button>
    <button class="tab-btn" data-tab="attendance">
      <i class="bi bi-calendar-check"></i>
      <span>การมาเรียน</span>
    </button>
    <button class="tab-btn" data-tab="health">
      <i class="bi bi-heart-pulse"></i>
      <span>ตรวจร่างกาย</span>
    </button>
    <button class="tab-btn" data-tab="growth">
      <i class="bi bi-graph-up"></i>
      <span>การเจริญเติบโต</span>
    </button>
  </div>

  <!-- ===== TAB: PROFILE ===== -->
  <div id="tab-profile" class="tab-content-pane">
    <div class="content-card">
      <div class="content-card-header">
        <div class="section-title">
          <i class="bi bi-person-circle icon-primary" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
          ข้อมูลประวัติประจำตัว
        </div>
      </div>
      <div class="content-card-body">
        <form id="profileForm" method="POST" action="../../include/function/edit_child.php" enctype="multipart/form-data">

        <!-- ข้อมูลพื้นฐาน -->
        <div class="section-divider">
          <span class="section-divider-title"><i class="bi bi-person me-1"></i>ข้อมูลพื้นฐาน</span>
          <div class="section-divider-line"></div>
        </div>

        <div class="row g-3 align-items-start">
          <!-- รูปโปรไฟล์ -->
          <div class="col-md-2 text-center">
            <img id="profilePreview"
              src="<?= !empty($child['profile_image']) ? htmlspecialchars($child['profile_image']) : '../../../public/assets/images/avatar.png' ?>"
              class="rounded-3 mb-2"
              style="width:100%;max-width:120px;height:120px;object-fit:cover;border:2px solid var(--gray-200);"
              alt="Profile" />
            <div id="imageUploadWrap" style="display:none;">
              <label class="btn-action btn-cancel w-100 justify-content-center" style="cursor:pointer;font-size:0.75rem;padding:5px 8px;">
                <i class="bi bi-camera"></i> เปลี่ยนรูป
                <input type="file" name="profile_image" accept="image/*" style="display:none;" id="profileImageInput" />
              </label>
            </div>
          </div>

          <!-- ข้อมูลพื้นฐาน -->
          <div class="col-md-10">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">รหัสนักเรียน</label>
                <input type="text" class="form-control" name="studentid" value="<?= htmlspecialchars($child['studentid']) ?>" readonly />
              </div>
              <div class="col-md-3">
                <label class="form-label">ปีการศึกษา</label>
                <select class="form-select" name="academic_year" id="academic_year" <?= !$is_admin ? 'disabled' : '' ?>>
                  <?php foreach ($academicYears as $year): ?>
                    <option value="<?= htmlspecialchars($year['name']) ?>" <?= ($child['academic_year'] ?? '') == $year['name'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($year['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">กลุ่มเด็ก</label>
                <select class="form-select" name="child_group" id="child_group" <?= !$is_admin ? 'disabled' : '' ?>>
                  <option value="เด็กกลาง" <?= ($child['child_group'] ?? '') === 'เด็กกลาง' ? 'selected' : '' ?>>เด็กกลาง</option>
                  <option value="เด็กโต" <?= ($child['child_group'] ?? '') === 'เด็กโต' ? 'selected' : '' ?>>เด็กโต</option>
                  <option value="เตรียมอนุบาล" <?= ($child['child_group'] ?? '') === 'เตรียมอนุบาล' ? 'selected' : '' ?>>เตรียมอนุบาล</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">ห้องเรียน</label>
                <select class="form-select" name="classroom" id="classroom" <?= !$is_admin ? 'disabled' : '' ?>>
                  <option value="<?= htmlspecialchars($child['classroom']) ?>" selected>
                    <?= htmlspecialchars($child['classroom']) ?>
                  </option>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label">คำนำหน้า (ไทย)</label>
                <input type="text" class="form-control" name="prefix_th" value="<?= htmlspecialchars($child['prefix_th'] ?? '') ?>" readonly />
              </div>
              <div class="col-md-4">
                <label class="form-label">ชื่อ (ไทย)</label>
                <input type="text" class="form-control" name="firstname_th" value="<?= htmlspecialchars($child['firstname_th'] ?? '') ?>" readonly />
              </div>
              <div class="col-md-4">
                <label class="form-label">นามสกุล (ไทย)</label>
                <input type="text" class="form-control" name="lastname_th" value="<?= htmlspecialchars($child['lastname_th'] ?? '') ?>" readonly />
              </div>
              <div class="col-md-2">
                <label class="form-label">ชื่อเล่น</label>
                <input type="text" class="form-control" name="nickname" value="<?= htmlspecialchars($child['nickname'] ?? '') ?>" readonly />
              </div>

              <div class="col-md-2">
                <label class="form-label">คำนำหน้า (EN)</label>
                <input type="text" class="form-control" name="prefix_en" value="<?= htmlspecialchars($child['prefix_en'] ?? '') ?>" readonly />
              </div>
              <div class="col-md-4">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="firstname_en" value="<?= htmlspecialchars($child['firstname_en'] ?? '') ?>" readonly />
              </div>
              <div class="col-md-4">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="lastname_en" value="<?= htmlspecialchars($child['lastname_en'] ?? '') ?>" readonly />
              </div>
              <div class="col-md-2">
                <label class="form-label">วันเกิด</label>
                <?php
                $birthday = !empty($child['birthday']) ? date('Y-m-d', strtotime($child['birthday'])) : '';
                ?>
                <input type="date" class="form-control" name="birthday" value="<?= htmlspecialchars($birthday) ?>" readonly />
              </div>
            </div>
          </div>
        </div>

        <!-- ข้อมูลส่วนตัว -->
        <div class="section-divider">
          <span class="section-divider-title"><i class="bi bi-person-vcard me-1"></i>ข้อมูลส่วนตัว</span>
          <div class="section-divider-line"></div>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">เลขประจำตัวประชาชน</label>
            <input type="text" class="form-control" name="id_card" value="<?= htmlspecialchars($child['id_card'] ?? '') ?>" readonly maxlength="13" />
          </div>
           <div class="col-md-2">
             <label class="form-label">เพศ</label>
             <select class="form-select" name="sex" readonly>
               <option value="ชาย" <?= ($child['sex'] ?? '') === 'ชาย' ? 'selected' : '' ?>>ชาย</option>
               <option value="หญิง" <?= ($child['sex'] ?? '') === 'หญิง' ? 'selected' : '' ?>>หญิง</option>
               <option value="อื่นๆ" <?= ($child['sex'] ?? '') === 'อื่นๆ' ? 'selected' : '' ?>>อื่นๆ</option>
             </select>
           </div>
          <div class="col-md-2">
            <label class="form-label">เชื้อชาติ</label>
            <input type="text" class="form-control" name="race" value="<?= htmlspecialchars($child['race'] ?? '') ?>" readonly />
          </div>
          <div class="col-md-2">
            <label class="form-label">สัญชาติ</label>
            <input type="text" class="form-control" name="nationality" value="<?= htmlspecialchars($child['nationality'] ?? '') ?>" readonly />
          </div>
          <div class="col-md-2">
            <label class="form-label">ศาสนา</label>
            <input type="text" class="form-control" name="religion" value="<?= htmlspecialchars($child['religion'] ?? '') ?>" readonly />
          </div>
           <div class="col-md-2">
             <label class="form-label">กรุ๊ปเลือด</label>
             <select class="form-select" name="blood_type" readonly>
               <option value="">เลือกกรุ๊ปเลือด</option>
               <option value="A" <?= ($child['blood_type'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
               <option value="B" <?= ($child['blood_type'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
               <option value="O" <?= ($child['blood_type'] ?? '') === 'O' ? 'selected' : '' ?>>O</option>
               <option value="AB" <?= ($child['blood_type'] ?? '') === 'AB' ? 'selected' : '' ?>>AB</option>
             </select>
           </div>
          <div class="col-md-2">
            <label class="form-label">ส่วนสูง</label>
            <div class="input-group">
              <input type="text" class="form-control" name="height" value="<?= htmlspecialchars($child['height'] ?? '') ?>" readonly />
              <span class="input-group-text">ซม.</span>
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label">น้ำหนัก</label>
            <div class="input-group">
              <input type="text" class="form-control" name="weight" value="<?= htmlspecialchars($child['weight'] ?? '') ?>" readonly />
              <span class="input-group-text">กก.</span>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">โรคประจำตัว</label>
            <input type="text" class="form-control" name="congenital_disease" value="<?= htmlspecialchars($child['congenital_disease'] ?? '') ?>" readonly />
          </div>
        </div>

        <!-- ข้อมูลผู้ปกครอง -->
        <div class="section-divider">
          <span class="section-divider-title"><i class="bi bi-people me-1"></i>ข้อมูลผู้ปกครอง</span>
          <div class="section-divider-line"></div>
        </div>

        <div class="row g-3">
          <!-- บิดา -->
          <div class="col-md-4">
            <div class="parent-card">
              <div class="parent-card-header">
                <img src="<?= !empty($child['father_image']) ? htmlspecialchars($child['father_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Father" class="parent-avatar" />
                <div>
                  <div class="parent-card-title"><i class="bi bi-person me-1"></i>บิดา</div>
                  <div class="parent-card-subtitle">Father</div>
                </div>
              </div>
              <div class="parent-card-body">
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label">ชื่อ</label>
                    <input type="text" class="form-control form-control-sm" name="father_first_name" value="<?= htmlspecialchars($child['father_first_name'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">นามสกุล</label>
                    <input type="text" class="form-control form-control-sm" name="father_last_name" value="<?= htmlspecialchars($child['father_last_name'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">เบอร์หลัก</label>
                    <input type="tel" class="form-control form-control-sm" name="father_phone" value="<?= htmlspecialchars($child['father_phone'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">เบอร์สำรอง</label>
                    <input type="tel" class="form-control form-control-sm" name="father_phone_backup" value="<?= htmlspecialchars($child['father_phone_backup'] ?? '') ?>" readonly />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- มารดา -->
          <div class="col-md-4">
            <div class="parent-card">
              <div class="parent-card-header">
                <img src="<?= !empty($child['mother_image']) ? htmlspecialchars($child['mother_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Mother" class="parent-avatar" />
                <div>
                  <div class="parent-card-title"><i class="bi bi-person me-1"></i>มารดา</div>
                  <div class="parent-card-subtitle">Mother</div>
                </div>
              </div>
              <div class="parent-card-body">
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label">ชื่อ</label>
                    <input type="text" class="form-control form-control-sm" name="mother_first_name" value="<?= htmlspecialchars($child['mother_first_name'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">นามสกุล</label>
                    <input type="text" class="form-control form-control-sm" name="mother_last_name" value="<?= htmlspecialchars($child['mother_last_name'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">เบอร์หลัก</label>
                    <input type="tel" class="form-control form-control-sm" name="mother_phone" value="<?= htmlspecialchars($child['mother_phone'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">เบอร์สำรอง</label>
                    <input type="tel" class="form-control form-control-sm" name="mother_phone_backup" value="<?= htmlspecialchars($child['mother_phone_backup'] ?? '') ?>" readonly />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ญาติ -->
          <div class="col-md-4">
            <div class="parent-card">
              <div class="parent-card-header">
                <img src="<?= !empty($child['relative_image']) ? htmlspecialchars($child['relative_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Relative" class="parent-avatar" />
                <div>
                  <div class="parent-card-title"><i class="bi bi-person me-1"></i>ญาติ</div>
                  <div class="parent-card-subtitle">Relative</div>
                </div>
              </div>
              <div class="parent-card-body">
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label">ชื่อ</label>
                    <input type="text" class="form-control form-control-sm" name="relative_first_name" value="<?= htmlspecialchars($child['relative_first_name'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">นามสกุล</label>
                    <input type="text" class="form-control form-control-sm" name="relative_last_name" value="<?= htmlspecialchars($child['relative_last_name'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">เบอร์หลัก</label>
                    <input type="tel" class="form-control form-control-sm" name="relative_phone" value="<?= htmlspecialchars($child['relative_phone'] ?? '') ?>" readonly />
                  </div>
                  <div class="col-6">
                    <label class="form-label">เบอร์สำรอง</label>
                    <input type="tel" class="form-control form-control-sm" name="relative_phone_backup" value="<?= htmlspecialchars($child['relative_phone_backup'] ?? '') ?>" readonly />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ข้อมูลสุขภาพ / การแพ้ -->
        <div class="section-divider">
          <span class="section-divider-title"><i class="bi bi-heart-pulse me-1"></i>ข้อมูลสุขภาพและการแพ้</span>
          <div class="section-divider-line"></div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="allergy-card allergy-card-drug" id="drugAllergyCard">
              <div class="allergy-card-title" style="color:#b91c1c;">
                <i class="bi bi-capsule-pill"></i> การแพ้ยา
                <?php if ($is_admin): ?>
                <button type="button" class="icon-btn icon-btn-edit ms-auto" id="btnEditDrugAllergy" style="display:none;" title="แก้ไข">
                  <i class="bi bi-pencil"></i>
                </button>
                <?php endif; ?>
              </div>
              <div id="drugAllergyContent">
                <div style="font-size:0.85rem;color:var(--gray-500);">กำลังโหลดข้อมูล...</div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="allergy-card allergy-card-none" id="foodAllergyCard">
              <div class="allergy-card-title" style="color:#15803d;">
                <i class="bi bi-egg-fried"></i> การแพ้อาหาร
                <?php if ($is_admin): ?>
                <button type="button" class="icon-btn icon-btn-edit ms-auto" id="btnEditFoodAllergy" style="display:none;" title="แก้ไข">
                  <i class="bi bi-pencil"></i>
                </button>
                <?php endif; ?>
              </div>
              <div id="foodAllergyContent">
                <div style="font-size:0.85rem;color:var(--gray-500);">กำลังโหลดข้อมูล...</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ที่อยู่ -->
        <div class="section-divider">
          <span class="section-divider-title"><i class="bi bi-house-door me-1"></i>ที่อยู่</span>
          <div class="section-divider-line"></div>
        </div>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">ที่อยู่</label>
            <textarea class="form-control" name="address" rows="2" readonly><?= htmlspecialchars($child['address'] ?? '') ?></textarea>
          </div>
          <div class="col-md-3">
            <label class="form-label">ตำบล/แขวง</label>
            <input type="text" class="form-control" name="district" value="<?= htmlspecialchars($child['district'] ?? '') ?>" readonly />
          </div>
          <div class="col-md-3">
            <label class="form-label">อำเภอ/เขต</label>
            <input type="text" class="form-control" name="amphoe" value="<?= htmlspecialchars($child['amphoe'] ?? '') ?>" readonly />
          </div>
          <div class="col-md-3">
            <label class="form-label">จังหวัด</label>
            <input type="text" class="form-control" name="province" value="<?= htmlspecialchars($child['province'] ?? '') ?>" readonly />
          </div>
          <div class="col-md-3">
            <label class="form-label">รหัสไปรษณีย์</label>
            <input type="text" class="form-control" name="zipcode" value="<?= htmlspecialchars($child['zipcode'] ?? '') ?>" readonly maxlength="5" />
          </div>
        </div>

        <!-- ผู้ติดต่อฉุกเฉิน -->
        <div class="section-divider">
          <span class="section-divider-title"><i class="bi bi-telephone-fill me-1"></i>ผู้ติดต่อฉุกเฉิน</span>
          <div class="section-divider-line"></div>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">ชื่อผู้ติดต่อ</label>
            <input type="text" class="form-control" name="emergency_contact" value="<?= htmlspecialchars($child['emergency_contact'] ?? '') ?>" readonly />
          </div>
          <div class="col-md-4">
            <label class="form-label">เบอร์โทร</label>
            <input type="tel" class="form-control" name="emergency_phone" value="<?= htmlspecialchars($child['emergency_phone'] ?? '') ?>" readonly />
          </div>
          <div class="col-md-4">
            <label class="form-label">ความสัมพันธ์</label>
            <input type="text" class="form-control" name="emergency_relation" value="<?= htmlspecialchars($child['emergency_relation'] ?? '') ?>" readonly />
          </div>
        </div>

        </form>
      </div><!-- end card-body -->
    </div><!-- end content-card -->
  </div>


  

  <!-- ===== TAB: VACCINE ===== -->
  <div id="tab-vaccine" class="tab-content-pane" style="display:none;">
    <div class="content-card">
      <div class="content-card-header">
        <div class="section-title">
          <i class="bi bi-shield-check icon-success" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
          ประวัติการฉีดวัคซีน
        </div>
        <?php if ($is_admin): ?>
        <div class="d-flex gap-2">
          <button class="btn-action btn-save" id="btnAddVaccineList" style="padding:6px 12px;font-size:0.8rem;">
            <i class="bi bi-plus-lg"></i><span>เพิ่มรายการวัคซีน</span>
          </button>
          <button class="btn-action btn-cancel" id="btnAddAgeGroup" style="padding:6px 12px;font-size:0.8rem;">
            <i class="bi bi-plus-lg"></i><span>เพิ่มช่วงอายุ</span>
          </button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Summary Bar -->
      <div id="vaccineSummary" style="padding:1rem 1.5rem;background:var(--gray-50);border-bottom:1px solid var(--gray-100);display:flex;gap:1.5rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:0.5rem;">
          <div style="width:10px;height:10px;border-radius:50%;background:var(--success);"></div>
          <span style="font-size:0.82rem;color:var(--gray-600);">ได้รับแล้ว <strong style="color:var(--success);" id="receivedCount">0</strong> รายการ</span>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;">
          <div style="width:10px;height:10px;border-radius:50%;background:var(--gray-300);"></div>
          <span style="font-size:0.82rem;color:var(--gray-600);">ยังไม่ได้รับ <strong style="color:var(--gray-500);" id="pendingCount">0</strong> รายการ</span>
        </div>
        <div style="margin-left:auto;display:flex;align-items:center;gap:0.5rem;">
          <div style="font-size:0.82rem;color:var(--gray-500);">ความครอบคลุม</div>
          <div style="font-size:1rem;font-weight:800;color:var(--primary);" id="coveragePercent">0%</div>
        </div>
      </div>

      <div class="content-card-body" style="padding:0;">
        <div style="overflow-x:auto;">
          <table class="vaccine-table">
            <thead>
              <tr>
                <th style="width:140px;">อายุที่ควรได้รับ</th>
                <th>วัคซีน</th>
                <th style="width:180px;">วันที่ได้รับ</th>
                <th style="width:100px;">จัดการ</th>
              </tr>
            </thead>
            <tbody id="vaccineTableBody">
              <tr>
                <td colspan="4" style="text-align:center;padding:2rem;color:var(--gray-400);">
                  <i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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
              <input type="hidden" id="vaccineListId" name="vaccine_list_id">
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

    <!-- Modal เพิ่ม/แก้ไขช่วงอายุ -->
    <div class="modal fade" id="ageGroupModal" tabindex="-1" aria-labelledby="ageGroupModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="ageGroupModalLabel">
              <i class="bi bi-plus-circle me-2"></i>เพิ่มช่วงอายุ
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="ageGroupForm" class="needs-validation" novalidate>
              <input type="hidden" id="ageGroupId" name="id">
              <div class="mb-4">
                <label class="form-label fw-bold">
                  <i class="bi bi-calendar-event me-2"></i>ช่วงอายุ
                </label>
                <input type="text" 
                       class="form-control form-control-lg" 
                       id="ageGroupName" 
                       name="age_group" 
                       placeholder="กรุณากรอกช่วงอายุ"
                       required>
                <div class="invalid-feedback">กรุณากรอกช่วงอายุ</div>
                <div class="form-text text-muted">
                  <i class="bi bi-info-circle me-1"></i>
                  ตัวอย่าง: 2 เดือน, 4 เดือน, 9 เดือน
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-2"></i>ปิด
            </button>
            <button type="button" class="btn btn-success" onclick="saveAgeGroup()">
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
              <input type="hidden" id="vaccineListRecordId" name="vaccine_list_id">
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

    <!-- Modal ดูรายละเอียดการฉีดวัคซีน -->
    <div class="modal fade" id="vaccineDetailModal" tabindex="-1" aria-labelledby="vaccineDetailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title" id="vaccineDetailModalLabel">
              <i class="bi bi-eye me-2"></i>รายละเอียดการฉีดวัคซีน
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>ข้อมูลพื้นฐาน</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">ชื่อวัคซีน</div>
                      <div style="font-size:0.95rem;font-weight:600;" id="detailVaccineName">-</div>
                    </div>
                    <div class="mb-3">
                      <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">ครั้งที่</div>
                      <div style="font-size:0.95rem;" id="detailVaccineNumber">-</div>
                    </div>
                    <div class="mb-3">
                      <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">วันที่ฉีด</div>
                      <div style="font-size:0.95rem;" id="detailVaccineDate">-</div>
                    </div>
                    <div class="mb-3">
                      <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">ช่วงอายุ</div>
                      <div style="font-size:0.95rem;" id="detailAgeGroup">-</div>
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
                      <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">สถานที่ฉีด</div>
                      <div style="font-size:0.95rem;" id="detailLocation">-</div>
                    </div>
                    <div class="mb-3">
                      <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">ผู้ให้บริการ</div>
                      <div style="font-size:0.95rem;" id="detailProvider">-</div>
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
                        <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">Lot No.</div>
                        <div style="font-size:0.95rem;" id="detailLotNumber">-</div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">วันนัดครั้งถัดไป</div>
                        <div style="font-size:0.95rem;" id="detailNextAppointment">-</div>
                      </div>
                      <div class="col-12 mb-3">
                        <div style="font-size:0.75rem;color:var(--gray-500);margin-bottom:0.25rem;">หมายเหตุ</div>
                        <div style="font-size:0.95rem;" id="detailNote">-</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <?php if ($is_admin || $is_teacher): ?>
            <button type="button" class="btn btn-warning" id="detailEditBtn">
              <i class="bi bi-pencil me-2"></i>แก้ไข
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-2"></i>ปิด
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== TAB: ATTENDANCE ===== -->
  <div id="tab-attendance" class="tab-content-pane" style="display:none;">
    <div class="content-card">
      <div class="content-card-header">
        <div class="section-title">
          <i class="bi bi-calendar-check icon-info" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
          ประวัติการมาเรียน
        </div>
      </div>

      <!-- Summary Stats -->
      <div style="padding:1rem 1.5rem;background:var(--gray-50);border-bottom:1px solid var(--gray-100);">
        <div class="row g-3">
          <div class="col-6 col-md-3">
            <div style="text-align:center;padding:0.75rem;background:#fff;border-radius:var(--radius-md);border:1px solid var(--gray-200);">
              <div style="font-size:1.5rem;font-weight:800;color:var(--success);" id="att-stat-present">0</div>
              <div style="font-size:0.75rem;color:var(--gray-400);">วันที่มาเรียน</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div style="text-align:center;padding:0.75rem;background:#fff;border-radius:var(--radius-md);border:1px solid var(--gray-200);">
              <div style="font-size:1.5rem;font-weight:800;color:var(--danger);" id="att-stat-absent">0</div>
              <div style="font-size:0.75rem;color:var(--gray-400);">วันที่ขาดเรียน</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div style="text-align:center;padding:0.75rem;background:#fff;border-radius:var(--radius-md);border:1px solid var(--gray-200);">
              <div style="font-size:1.5rem;font-weight:800;color:var(--warning);" id="att-stat-leave">0</div>
              <div style="font-size:0.75rem;color:var(--gray-400);">วันที่ลา</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div style="text-align:center;padding:0.75rem;background:#fff;border-radius:var(--radius-md);border:1px solid var(--gray-200);">
              <div style="font-size:1.5rem;font-weight:800;color:var(--primary);" id="att-stat-rate">0%</div>
              <div style="font-size:0.75rem;color:var(--gray-400);">อัตราการมาเรียน</div>
            </div>
          </div>
        </div>
      </div>

      <div class="content-card-body" style="padding:0;">
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>วันที่</th>
                <th>สถานะมาเรียน</th>
                <th>เวลามา</th>
                <th>สถานะกลับบ้าน</th>
                <th>เวลากลับ</th>
                <th>จัดการ</th>
              </tr>
            </thead>
            <tbody id="attendanceTableBody">
              <tr>
                <td colspan="6" style="text-align:center;padding:2rem;color:var(--gray-400);">
                  <i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== TAB: HEALTH ===== -->
  <div id="tab-health" class="tab-content-pane" style="display:none;">
    <div class="content-card">
      <div class="content-card-header">
        <div class="section-title">
          <i class="bi bi-heart-pulse icon-danger" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
          ประวัติการตรวจร่างกาย
        </div>
      </div>

      <div class="content-card-body" style="padding:0;">
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>วันที่ตรวจ</th>
                <th>ผลการตรวจ (สรุป)</th>
                <th>ครูผู้ตรวจ</th>
                <th>จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="font-size:0.82rem;color:var(--gray-600);">3 มิ.ย. 2567 08:00 น.</td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <span class="status-badge" style="background:#dcfce7;color:#15803d;"><i class="bi bi-check"></i> ผม/ศีรษะ: สะอาด</span>
                    <span class="status-badge" style="background:#dcfce7;color:#15803d;"><i class="bi bi-check"></i> ตา: ปกติ</span>
                    <span class="status-badge" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-exclamation"></i> จมูก: มีน้ำมูก</span>
                  </div>
                </td>
                <td style="font-size:0.85rem;">ครูสมหวัง</td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="icon-btn icon-btn-view" title="ดูรายละเอียด"><i class="bi bi-eye"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== TAB: GROWTH ===== -->
  <div id="tab-growth" class="tab-content-pane" style="display:none;">

    <!-- Growth Stats -->
    <div class="growth-stats">
      <div class="growth-stat-card">
        <div class="growth-stat-value"><?= htmlspecialchars($child['weight'] ?? '0') ?></div>
        <div class="growth-stat-unit">กก.</div>
        <div class="growth-stat-label">น้ำหนัก</div>
        <span class="growth-stat-status" style="background:#dcfce7;color:#15803d;">สมส่วน</span>
      </div>
      <div class="growth-stat-card">
        <div class="growth-stat-value"><?= htmlspecialchars($child['height'] ?? '0') ?></div>
        <div class="growth-stat-unit">ซม.</div>
        <div class="growth-stat-label">ส่วนสูง</div>
        <span class="growth-stat-status" style="background:#dcfce7;color:#15803d;">ตามเกณฑ์</span>
      </div>
      <div class="growth-stat-card">
        <div class="growth-stat-value">50.2</div>
        <div class="growth-stat-unit">ซม.</div>
        <div class="growth-stat-label">เส้นรอบศีรษะ</div>
        <span class="growth-stat-status" style="background:#dcfce7;color:#15803d;">ปกติ</span>
      </div>
      <div class="growth-stat-card">
        <div class="growth-stat-value">15.9</div>
        <div class="growth-stat-unit">BMI</div>
        <div class="growth-stat-label">ดัชนีมวลกาย</div>
        <span class="growth-stat-status" style="background:#dcfce7;color:#15803d;">ปกติ</span>
      </div>
    </div>

    <!-- Development Assessment -->
    <div class="content-card mb-4">
      <div class="content-card-header">
        <div class="section-title">
          <i class="bi bi-clipboard2-pulse icon-warning" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
          ผลการประเมินพัฒนาการ (ล่าสุด)
        </div>
      </div>
      <div class="content-card-body" style="padding:0;">
        <table class="dev-table">
          <thead>
            <tr>
              <th>ด้านพัฒนาการ</th>
              <th>ผลการประเมิน</th>
              <th>หมายเหตุ</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><i class="bi bi-person-walking me-2" style="color:var(--primary);"></i>การเคลื่อนไหว (GM)</td>
              <td><span class="dev-pass"><i class="bi bi-check-circle-fill me-1"></i>ผ่าน</span></td>
              <td style="font-size:0.78rem;color:var(--gray-400);">-</td>
            </tr>
            <tr>
              <td><i class="bi bi-hand-index me-2" style="color:var(--info);"></i>กล้ามเนื้อมัดเล็กและสติปัญญา (FM)</td>
              <td><span class="dev-pass"><i class="bi bi-check-circle-fill me-1"></i>ผ่าน</span></td>
              <td style="font-size:0.78rem;color:var(--gray-400);">-</td>
            </tr>
            <tr>
              <td><i class="bi bi-ear me-2" style="color:var(--success);"></i>การเข้าใจภาษา (RL)</td>
              <td><span class="dev-delay"><i class="bi bi-exclamation-triangle-fill me-1"></i>สงสัยล่าช้า</span></td>
              <td style="font-size:0.78rem;color:var(--warning);">ข้อที่ 3</td>
            </tr>
            <tr>
              <td><i class="bi bi-chat-dots me-2" style="color:var(--warning);"></i>การใช้ภาษา (EL)</td>
              <td><span class="dev-pass"><i class="bi bi-check-circle-fill me-1"></i>ผ่าน</span></td>
              <td style="font-size:0.78rem;color:var(--gray-400);">-</td>
            </tr>
            <tr>
              <td><i class="bi bi-people me-2" style="color:var(--danger);"></i>การช่วยเหลือตัวเองและสังคม (PS)</td>
              <td><span class="dev-pass"><i class="bi bi-check-circle-fill me-1"></i>ผ่าน</span></td>
              <td style="font-size:0.78rem;color:var(--gray-400);">-</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Growth History Table -->
    <div class="content-card mb-4">
      <div class="content-card-header">
        <div class="section-title">
          <i class="bi bi-table icon-primary" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
          ประวัติการบันทึกการเจริญเติบโต
        </div>
      </div>

      <div class="content-card-body" style="padding:0;">
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>วันที่บันทึก</th>
                <th>อายุ</th>
                <th>น้ำหนัก</th>
                <th>ส่วนสูง</th>
                <th>เส้นรอบศีรษะ</th>
                <th>ผลประเมิน</th>
                <th>จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="font-size:0.82rem;color:var(--gray-600);">3 มิ.ย. 2567</td>
                <td style="font-size:0.82rem;">4 ปี 2 เดือน</td>
                <td><strong><?= htmlspecialchars($child['weight'] ?? '-') ?></strong> <span style="font-size:0.75rem;color:var(--gray-400);">กก.</span></td>
                <td><strong><?= htmlspecialchars($child['height'] ?? '-') ?></strong> <span style="font-size:0.75rem;color:var(--gray-400);">ซม.</span></td>
                <td><strong>50.2</strong> <span style="font-size:0.75rem;color:var(--gray-400);">ซม.</span></td>
                <td>
                  <button class="btn-action btn-save" style="padding:4px 10px;font-size:0.75rem;" onclick="showGrowthDetailModal()">
                    <i class="bi bi-bar-chart"></i><span>ดูผล</span>
                  </button>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="icon-btn icon-btn-edit" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Chart Grid -->
    <div class="chart-grid">
      <div class="chart-card">
        <div class="chart-card-header">
          <div class="chart-card-title"><i class="bi bi-bar-chart me-1"></i>น้ำหนักตามเกณฑ์อายุ</div>
          <button class="icon-btn icon-btn-view" title="ขยาย"><i class="bi bi-arrows-fullscreen"></i></button>
        </div>
        <div class="chart-card-body">
          <canvas id="chartWeight"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-card-header">
          <div class="chart-card-title"><i class="bi bi-bar-chart me-1"></i>ส่วนสูงตามเกณฑ์อายุ</div>
          <button class="icon-btn icon-btn-view" title="ขยาย"><i class="bi bi-arrows-fullscreen"></i></button>
        </div>
        <div class="chart-card-body">
          <canvas id="chartHeight"></canvas>
        </div>
      </div>
    </div>
  </div>

</div><!-- end page-wrapper -->

<!-- ===== Modal: Drug Allergy ===== -->
<div class="modal fade" id="drugAllergyModal" tabindex="-1" aria-labelledby="drugAllergyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="drugAllergyModalLabel">
          <i class="bi bi-capsule-pill me-2"></i>จัดการข้อมูลการแพ้ยา
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="drugAllergyForm">
          <input type="hidden" name="student_id" value="<?= htmlspecialchars($studentid) ?>">
          <input type="hidden" name="type" value="drug">
          <input type="hidden" name="action" value="save_multiple">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small">สามารถเพิ่มรายการยาที่แพ้ได้หลายรายการในครั้งเดียว</div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="addDrugAllergyRow()">
              <i class="bi bi-plus-circle me-1"></i>เพิ่มรายการ
            </button>
          </div>

          <div id="drugAllergyRows" class="d-flex flex-column gap-3"></div>
        </form>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-2"></i>ปิด
        </button>
        <button type="button" class="btn btn-danger" onclick="saveDrugAllergy()">
          <i class="bi bi-save me-2"></i>บันทึกข้อมูล
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal: Food Allergy ===== -->
<div class="modal fade" id="foodAllergyModal" tabindex="-1" aria-labelledby="foodAllergyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="foodAllergyModalLabel">
          <i class="bi bi-egg-fried me-2"></i>จัดการข้อมูลการแพ้อาหาร
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="foodAllergyForm">
          <input type="hidden" name="student_id" value="<?= htmlspecialchars($studentid) ?>">
          <input type="hidden" name="type" value="food">
          <input type="hidden" name="action" value="save_multiple">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small">สามารถเพิ่มรายการอาหารที่แพ้ได้หลายรายการในครั้งเดียว</div>
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="addFoodAllergyRow()">
              <i class="bi bi-plus-circle me-1"></i>เพิ่มรายการ
            </button>
          </div>

          <div id="foodAllergyRows" class="d-flex flex-column gap-3"></div>
        </form>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-2"></i>ปิด
        </button>
        <button type="button" class="btn btn-warning" onclick="saveFoodAllergy()">
          <i class="bi bi-save me-2"></i>บันทึกข้อมูล
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Toast Container ===== -->
<div class="toast-container" id="toastContainer"></div>

<!-- ===== Modal: Attendance Detail ===== -->
<div class="modal fade" id="attendanceDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0" style="border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);padding:1.25rem 1.75rem;">
        <h5 class="modal-title text-white fw-bold"><i class="bi bi-calendar-check me-2"></i>รายละเอียดการมาเรียน</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:1.75rem;background:#f0f2f5;">
        <div class="row g-3">

          <!-- TOP: Child Info + Status -->
          <div class="col-12">
            <div class="card border-0" style="border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
              <div class="card-body p-4">
                <div class="row align-items-center g-3">
                  <div class="col-md-8">
                    <div style="font-size:1.15rem;font-weight:700;color:#1a1a2e;" id="attDetailStudent">-</div>
                    <div style="font-size:0.82rem;color:#6c757d;margin-top:0.25rem;">
                      <span id="attDetailStudentId">-</span>
                      <span class="mx-2">|</span>
                      <span id="attDetailGroup">-</span>
                    </div>
                    <div style="font-size:0.82rem;color:#6c757d;margin-top:0.15rem;">
                      <i class="bi bi-calendar3 me-1" style="color:#e94560;"></i>วันที่: <span id="attDetailDate">-</span>
                    </div>
                  </div>
                  <div class="col-md-4 text-md-end">
                    <div style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#adb5bd;margin-bottom:0.25rem;">สถานะ</div>
                    <div id="attDetailStatus">-</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- BOTTOM Left: Check-in -->
          <div class="col-md-6">
            <div class="card border-0 h-100" style="border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#0f3460,#16213e);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-box-arrow-in-right text-white" style="font-size:0.9rem;"></i>
                  </div>
                  <h6 class="mb-0 fw-bold" style="color:#1a1a2e;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.3px;">เวลาเข้าเรียน</h6>
                </div>
                <div class="d-flex align-items-center gap-3 mb-2">
                  <div style="font-size:1.5rem;font-weight:800;color:#0f3460;" id="attDetailCheckin">-</div>
                </div>
                <div style="font-size:0.75rem;color:#adb5bd;">
                  <i class="bi bi-clock me-1"></i>บันทึกเมื่อ: <span id="attDetailCreatedAt">-</span>
                </div>
                <hr style="border-color:#e9ecef;margin:0.75rem 0;">
                <div style="font-size:0.85rem;">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="color:#6c757d;font-weight:500;">อุณหภูมิร่างกาย</span>
                    <strong style="color:#1a1a2e;font-size:1rem;"><span id="attDetailTemperature">-</span></strong>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span style="color:#6c757d;font-weight:500;">อาการป่วย</span>
                    <strong style="color:#1a1a2e;text-align:right;max-width:60%;"><span id="attDetailSymptoms">-</span></strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- BOTTOM Right: Check-out -->
          <div class="col-md-6">
            <div class="card border-0 h-100" style="border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#e94560,#c23152);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-box-arrow-right text-white" style="font-size:0.9rem;"></i>
                  </div>
                  <h6 class="mb-0 fw-bold" style="color:#1a1a2e;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.3px;">เวลากลับบ้าน</h6>
                </div>
                <div class="d-flex align-items-center gap-3 mb-2">
                  <div style="font-size:1.5rem;font-weight:800;color:#e94560;" id="attDetailCheckoutTime">-</div>
                </div>
                <div id="attDetailCheckoutStatus" style="font-size:0.82rem;"></div>
                <div id="attDetailPickupRow" style="margin-top:0.5rem;padding-top:0.5rem;border-top:1px solid #e9ecef;">
                  <div class="d-flex justify-content-between align-items-center">
                    <span style="color:#6c757d;font-weight:500;font-size:0.85rem;">ชื่อผู้มารับกลับ</span>
                    <strong style="color:#1a1a2e;font-size:0.9rem;"><span id="attDetailPickupBy">-</span></strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Leave Note -->
          <div class="col-12" id="attDetailLeaveNoteRow" style="display:none;">
            <div class="card border-0" style="border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border-left:4px solid #e94560;">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-calendar-x" style="color:#e94560;font-size:1.1rem;"></i>
                  <h6 class="mb-0 fw-bold" style="color:#1a1a2e;font-size:0.85rem;">หมายเหตุการลา</h6>
                </div>
                <div style="font-size:0.92rem;color:#495057;" id="attDetailLeaveNote">-</div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer border-0" style="background:#f0f2f5;padding:1rem 1.75rem;">
        <button type="button" class="btn px-4 fw-semibold" style="border-radius:8px;background:#1a1a2e;color:#fff;border:none;" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-2"></i>ปิด
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal: Growth Detail ===== -->
<div class="modal fade" id="growthDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-graph-up me-2 text-primary"></i>รายละเอียดการเจริญเติบโต</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div style="background:var(--gray-50);border-radius:var(--radius-md);padding:1rem;">
              <div style="font-size:0.8rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;margin-bottom:0.75rem;">ข้อมูลการวัด</div>
              <div class="info-row">
                <div class="info-row-icon"><i class="bi bi-calendar3"></i></div>
                <div class="info-row-label">วันที่บันทึก</div>
                <div class="info-row-value">3 มิถุนายน 2567</div>
              </div>
              <div class="info-row">
                <div class="info-row-icon"><i class="bi bi-clock"></i></div>
                <div class="info-row-label">อายุ</div>
                <div class="info-row-value">4 ปี 2 เดือน 18 วัน</div>
              </div>
              <div class="info-row">
                <div class="info-row-icon"><i class="bi bi-arrow-up-circle"></i></div>
                <div class="info-row-label">น้ำหนัก</div>
                <div class="info-row-value"><?= htmlspecialchars($child['weight'] ?? '-') ?> กก.</div>
              </div>
              <div class="info-row">
                <div class="info-row-icon"><i class="bi bi-arrow-up"></i></div>
                <div class="info-row-label">ส่วนสูง</div>
                <div class="info-row-value"><?= htmlspecialchars($child['height'] ?? '-') ?> ซม.</div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div style="background:var(--gray-50);border-radius:var(--radius-md);padding:1rem;">
              <div style="font-size:0.8rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;margin-bottom:0.75rem;">ผลการประเมิน</div>
              <div class="info-row">
                <div class="info-row-icon"><i class="bi bi-clipboard-check"></i></div>
                <div class="info-row-label">น้ำหนักตามอายุ</div>
                <div class="info-row-value" style="color:var(--success);font-weight:700;">สมส่วน</div>
              </div>
              <div class="info-row">
                <div class="info-row-icon"><i class="bi bi-clipboard-check"></i></div>
                <div class="info-row-label">ส่วนสูงตามอายุ</div>
                <div class="info-row-value" style="color:var(--success);font-weight:700;">ตามเกณฑ์</div>
              </div>
              <div class="info-row">
                <div class="info-row-icon"><i class="bi bi-clipboard-check"></i></div>
                <div class="info-row-label">เส้นรอบศีรษะ</div>
                <div class="info-row-value" style="color:var(--success);font-weight:700;">ปกติ</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-action btn-cancel" data-bs-dismiss="modal">
          <i class="bi bi-x-lg"></i><span>ปิด</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
(function () {
  'use strict';

  const studentId = '<?= addslashes(htmlspecialchars($studentid)) ?>';

  /* ── Tab Switching ── */
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabPanes = document.querySelectorAll('.tab-content-pane');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;

      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanes.forEach(p => p.style.display = 'none');

      btn.classList.add('active');
      const pane = document.getElementById('tab-' + target);
      if (pane) {
        pane.style.display = 'block';
      }
    });
  });

  /* ── Edit Mode Toggle ── */
  const btnEdit   = document.getElementById('btnEdit');
  const btnSave   = document.getElementById('btnSave');
  const btnCancel = document.getElementById('btnCancel');
  const editBar   = document.getElementById('editModeBar');
  const imageWrap = document.getElementById('imageUploadWrap');
  const editAllergyBtns = [
    document.getElementById('btnEditDrugAllergy'),
    document.getElementById('btnEditFoodAllergy')
  ];

  function getEditableFields() {
    return document.querySelectorAll(
      '#tab-profile input:not([type="file"]):not([name="studentid"]), #tab-profile select, #tab-profile textarea'
    );
  }

  function enterEditMode() {
    getEditableFields().forEach(el => {
      el.removeAttribute('readonly');
      el.removeAttribute('disabled');
    });
    editBar.classList.add('show');
    imageWrap.style.display = 'block';
    editAllergyBtns.forEach(b => { if (b) b.style.display = 'flex'; });
    btnEdit.style.display = 'none';
    showToast('info', 'โหมดแก้ไขเปิดใช้งานแล้ว');
  }

  function exitEditMode(save) {
    // ถ้าบันทึก ให้เรียก saveProfileData ก่อน แล้วค่อยปิด edit mode
    if (save) {
      saveProfileData();
    }
    
    getEditableFields().forEach(el => {
      if (el.tagName === 'SELECT') {
        el.setAttribute('disabled', true);
      } else {
        el.setAttribute('readonly', true);
      }
    });
    editBar.classList.remove('show');
    imageWrap.style.display = 'none';
    editAllergyBtns.forEach(b => { if (b) b.style.display = 'none'; });
    btnEdit.style.display = 'inline-flex';
    
    if (!save) {
      showToast('warning', 'ยกเลิกการแก้ไขแล้ว');
    }
  }

  function saveProfileData() {
    // ดึงค่าจากฟอร์มเก็บในตัวแปร
    const studentid = document.querySelector('[name="studentid"]').value;
    const academicYear = document.querySelector('[name="academic_year"]').value;
    const childGroup = document.querySelector('[name="child_group"]').value;
    const classroom = document.querySelector('[name="classroom"]').value;
    const prefixTh = document.querySelector('[name="prefix_th"]').value;
    const firstnameTh = document.querySelector('[name="firstname_th"]').value;
    const lastnameTh = document.querySelector('[name="lastname_th"]').value;
    const nickname = document.querySelector('[name="nickname"]').value;
    const prefixEn = document.querySelector('[name="prefix_en"]').value;
    const firstnameEn = document.querySelector('[name="firstname_en"]').value;
    const lastnameEn = document.querySelector('[name="lastname_en"]').value;
    const birthday = document.querySelector('[name="birthday"]').value;
    const idCard = document.querySelector('[name="id_card"]').value;
    const sex = document.querySelector('[name="sex"]').value;
    const race = document.querySelector('[name="race"]').value;
    const nationality = document.querySelector('[name="nationality"]').value;
    const religion = document.querySelector('[name="religion"]').value;
    const bloodType = document.querySelector('[name="blood_type"]').value;
    const height = document.querySelector('[name="height"]').value;
    const weight = document.querySelector('[name="weight"]').value;
    const congenitalDisease = document.querySelector('[name="congenital_disease"]').value;
    const fatherFirstName = document.querySelector('[name="father_first_name"]').value;
    const fatherLastName = document.querySelector('[name="father_last_name"]').value;
    const fatherPhone = document.querySelector('[name="father_phone"]').value;
    const fatherPhoneBackup = document.querySelector('[name="father_phone_backup"]').value;
    const motherFirstName = document.querySelector('[name="mother_first_name"]').value;
    const motherLastName = document.querySelector('[name="mother_last_name"]').value;
    const motherPhone = document.querySelector('[name="mother_phone"]').value;
    const motherPhoneBackup = document.querySelector('[name="mother_phone_backup"]').value;
    const relativeFirstName = document.querySelector('[name="relative_first_name"]').value;
    const relativeLastName = document.querySelector('[name="relative_last_name"]').value;
    const relativePhone = document.querySelector('[name="relative_phone"]').value;
    const relativePhoneBackup = document.querySelector('[name="relative_phone_backup"]').value;
    const address = document.querySelector('[name="address"]').value;
    const district = document.querySelector('[name="district"]').value;
    const amphoe = document.querySelector('[name="amphoe"]').value;
    const province = document.querySelector('[name="province"]').value;
    const zipcode = document.querySelector('[name="zipcode"]').value;
    const emergencyContact = document.querySelector('[name="emergency_contact"]').value;
    const emergencyPhone = document.querySelector('[name="emergency_phone"]').value;
    const emergencyRelation = document.querySelector('[name="emergency_relation"]').value;
    
    // สร้าง object ข้อมูล
    const data = {
      student_id: studentid,
      academic_year: academicYear,
      child_group: childGroup,
      classroom: classroom,
      prefix_th: prefixTh,
      firstname_th: firstnameTh,
      lastname_th: lastnameTh,
      nickname: nickname,
      prefix_en: prefixEn,
      firstname_en: firstnameEn,
      lastname_en: lastnameEn,
      birthday: birthday,
      id_card: idCard,
      sex: sex,
      race: race,
      nationality: nationality,
      religion: religion,
      blood_type: bloodType,
      height: height,
      weight: weight,
      congenital_disease: congenitalDisease,
      father_first_name: fatherFirstName,
      father_last_name: fatherLastName,
      father_phone: fatherPhone,
      father_phone_backup: fatherPhoneBackup,
      mother_first_name: motherFirstName,
      mother_last_name: motherLastName,
      mother_phone: motherPhone,
      mother_phone_backup: motherPhoneBackup,
      relative_first_name: relativeFirstName,
      relative_last_name: relativeLastName,
      relative_phone: relativePhone,
      relative_phone_backup: relativePhoneBackup,
      address: address,
      district: district,
      amphoe: amphoe,
      province: province,
      zipcode: zipcode,
      emergency_contact: emergencyContact,
      emergency_phone: emergencyPhone,
      emergency_relation: emergencyRelation
    };
    
    // Validation: ตรวจสอบข้อมูลที่จำเป็น
    const requiredFields = [
      { field: 'prefix_th', message: 'กรุณากรอกคำนำหน้า (ไทย)' },
      { field: 'firstname_th', message: 'กรุณากรอกชื่อ (ไทย)' },
      { field: 'lastname_th', message: 'กรุณากรอกนามสกุล (ไทย)' },
      { field: 'prefix_en', message: 'กรุณากรอกคำนำหน้า (EN)' },
      { field: 'firstname_en', message: 'กรุณากรอก First Name' },
      { field: 'lastname_en', message: 'กรุณากรอก Last Name' },
      { field: 'sex', message: 'กรุณาเลือกเพศ' },
      { field: 'emergency_contact', message: 'กรุณากรอกชื่อผู้ติดต่อฉุกเฉิน' },
      { field: 'emergency_phone', message: 'กรุณากรอกเบอร์ผู้ติดต่อฉุกเฉิน' }
    ];
    
    for (const item of requiredFields) {
      if (!data[item.field] || data[item.field].trim() === '') {
        showToast('error', item.message);
        document.querySelector(`[name="${item.field}"]`)?.focus();
        return false;
      }
    }
    
    // Validation: ตรวจสอบรูปแบบเบอร์โทร
    const phonePattern = /^[0-9]{9,10}$/;
    const phoneFields = ['father_phone', 'mother_phone', 'relative_phone', 'emergency_phone'];
    for (const field of phoneFields) {
      if (data[field] && !phonePattern.test(data[field].replace(/[-\s]/g, ''))) {
        showToast('error', 'กรุณากรอกเบอร์โทรให้ถูกต้อง (9-10 หลัก)');
        document.querySelector(`[name="${field}"]`)?.focus();
        return false;
      }
    }
    
    // Validation: ตรวจสอบเลขบัตรประชาชน
    if (data.id_card && !/^[0-9]{13}$/.test(data.id_card)) {
      showToast('error', 'กรุณากรอกเลขบัตรประชาชนให้ถูกต้อง (13 หลัก)');
      document.querySelector('[name="id_card"]')?.focus();
      return false;
    }
    
    // Validation: ตรวจสอบรหัสไปรษณีย์
    if (data.zipcode && !/^[0-9]{5}$/.test(data.zipcode)) {
      showToast('error', 'กรุณากรอกรหัสไปรษณีย์ให้ถูกต้อง (5 หลัก)');
      document.querySelector('[name="zipcode"]')?.focus();
      return false;
    }
    
    // แสดง loading state
    const btnSave = document.getElementById('btnSave');
    const originalText = btnSave.innerHTML;
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังบันทึก...';
    
    // Debug: แสดงข้อมูลที่จะส่งไปบันทึก
    console.log('=== Saving Profile Data ===');
    console.log('Data:', data);
    console.log('==========================');
    
    fetch('../../include/function/edit_child.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(r => {
      if (!r.ok) {
        throw new Error('HTTP error: ' + r.status);
      }
      return r.json();
    })
    .then(result => {
      console.log('Save response:', result);
      if (result.status === 'success') {
        showToast('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
        // รีเฟรชหน้าเพื่อแสดงข้อมูลที่อัพเดท
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showToast('error', result.message || 'ไม่สามารถบันทึกข้อมูลได้');
      }
    })
    .catch(error => {
      console.error('Error saving profile:', error);
      showToast('error', 'เกิดข้อผิดพลาดในการบันทึก: ' + error.message);
    })
    .finally(() => {
      // คืนค่าปุ่มบันทึก
      btnSave.disabled = false;
      btnSave.innerHTML = originalText;
    });
    
    return true;
  }

  if (btnEdit)   btnEdit.addEventListener('click', enterEditMode);
  if (btnSave)   btnSave.addEventListener('click', () => exitEditMode(true));
  if (btnCancel) btnCancel.addEventListener('click', () => exitEditMode(false));

  /* ── Profile Image Preview ── */
  const profileImageInput = document.getElementById('profileImageInput');
  if (profileImageInput) {
    profileImageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      if (file.size > 5 * 1024 * 1024) {
        showToast('error', 'ขนาดไฟล์ต้องไม่เกิน 5MB');
        return;
      }
      const reader = new FileReader();
      reader.onload = e => {
        document.getElementById('profilePreview').src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  /* ── Delete Confirmation ── */
  function confirmDelete(studentId) {
    if (confirm('คุณต้องการลบข้อมูลนักเรียนคนนี้หรือไม่?\nการลบจะไม่สามารถกู้คืนได้')) {
      showToast('success', 'ลบข้อมูลเรียบร้อยแล้ว (demo)');
    }
  }

  /* ── QR Code ── */
  function generateQRCode(studentId) {
    showToast('info', 'กำลังสร้าง QR Code...');
  }

  /* ── Growth Detail Modal ── */
  window.showGrowthDetailModal = function () {
    const modal = new bootstrap.Modal(document.getElementById('growthDetailModal'));
    modal.show();
  };

  /* ── Toast Notification ── */
  window.showToast = function (type, message) {
    const container = document.getElementById('toastContainer');
    const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill',
                    warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const colors = { success: '#22c55e', error: '#ef4444', warning: '#f59e0b', info: '#06b6d4' };

    const toast = document.createElement('div');
    toast.className = 'toast-item';
    toast.innerHTML = `
      <i class="bi ${icons[type] || icons.info}" style="color:${colors[type]};font-size:1rem;"></i>
      <span>${message}</span>
    `;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  };

  /* ── Allergy Modal Helpers ── */
  window.addDrugAllergyRow = function(item = {}) {
    const container = document.getElementById('drugAllergyRows');
    if (!container) return;

    const index = container.querySelectorAll('.drug-allergy-row').length;
    const row = document.createElement('div');
    row.className = 'drug-allergy-row border rounded p-3 bg-light';
    row.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>รายการที่ ${index + 1}</strong>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeDrugAllergyRow(this)">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-capsule me-2"></i>ชื่อยาที่แพ้</label>
          <input type="text" class="form-control" name="drug_items[${index}][drug_name]" value="${(item.drug_name || '').replace(/"/g, '&quot;')}" placeholder="ระบุชื่อยาที่แพ้">
        </div>
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-check-circle me-2"></i>วิธีที่ทราบว่าแพ้</label>
          <select class="form-select" name="drug_items[${index}][detection_method]">
            <option value="">เลือกวิธีที่ทราบ</option>
            <option value="symptoms_after_use" ${item.detection_method === 'symptoms_after_use' ? 'selected' : ''}>มีอาการแพ้หลังจากใช้ยา</option>
            <option value="skin_testing" ${item.detection_method === 'skin_testing' ? 'selected' : ''}>การทดสอบทางผิวหนัง</option>
            <option value="blood_test" ${item.detection_method === 'blood_test' ? 'selected' : ''}>ทดสอบโดยการเจาะเลือด</option>
            <option value="repeat_use" ${item.detection_method === 'repeat_use' ? 'selected' : ''}>ทดสอบโดยการใช้ยาซ้ำ</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>อาการที่เกิดขึ้น</label>
          <select class="form-select" name="drug_items[${index}][symptoms]">
            <option value="">เลือกอาการ</option>
            <option value="type1" ${item.symptoms === 'type1' ? 'selected' : ''}>ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก</option>
            <option value="type2" ${item.symptoms === 'type2' ? 'selected' : ''}>ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก และหายใจลำบาก</option>
            <option value="type3" ${item.symptoms === 'type3' ? 'selected' : ''}>ผื่นแดงลักษณะเป็นผื่นราบ และผื่นนูน กระจายอย่างสมมาตร</option>
            <option value="type4" ${item.symptoms === 'type4' ? 'selected' : ''}>ผิวแดงทั่วตัวและผื่นตุ่มหนองขนาดเล็กจำนวนมาก</option>
            <option value="type5" ${item.symptoms === 'type5' ? 'selected' : ''}>ผื่นที่เกิดขึ้นสามารถพบได้หลายแบบ</option>
            <option value="type6" ${item.symptoms === 'type6' ? 'selected' : ''}>ผื่นตุ่มน้ำ มีผิวหนังกำพร้าตายและหลุดลอก</option>
          </select>
        </div>
        <div class="col-md-12">
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="drug_items[${index}][has_allergy_card]" value="true" ${item.has_allergy_card ? 'checked' : ''}>
            <label class="form-check-label fw-bold"><i class="bi bi-card-checklist me-2"></i>มีบัตรแพ้ยา</label>
          </div>
        </div>
      </div>
    `;
    container.appendChild(row);
  };

  window.removeDrugAllergyRow = function(button) {
    button.closest('.drug-allergy-row')?.remove();
    const container = document.getElementById('drugAllergyRows');
    if (container && container.children.length === 0) {
      window.addDrugAllergyRow();
    }
  };

  function getDrugAllergyRowsFromForm() {
    return Array.from(document.querySelectorAll('.drug-allergy-row')).map(row => ({
      id: row.dataset.id || '',
      drug_name: row.querySelector('input[name$="[drug_name]"]')?.value?.trim() || '',
      detection_method: row.querySelector('select[name$="[detection_method]"]')?.value || '',
      symptoms: row.querySelector('select[name$="[symptoms]"]')?.value || '',
      has_allergy_card: row.querySelector('input[name$="[has_allergy_card]"]')?.checked ? 'true' : 'false'
    })).filter(item => item.drug_name || item.detection_method || item.symptoms || item.has_allergy_card === 'true');
  }

  function populateDrugAllergyRows(items = []) {
    const container = document.getElementById('drugAllergyRows');
    if (!container) return;

    container.innerHTML = '';
    if (!items || items.length === 0) {
      window.addDrugAllergyRow();
      return;
    }

    items.forEach(item => {
      const row = document.createElement('div');
      row.className = 'drug-allergy-row border rounded p-3 bg-light';
      row.dataset.id = item.id || '';
      row.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>รายการที่ ${container.querySelectorAll('.drug-allergy-row').length + 1}</strong>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeDrugAllergyRow(this)">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-capsule me-2"></i>ชื่อยาที่แพ้</label>
            <input type="text" class="form-control" name="drug_items[${container.querySelectorAll('.drug-allergy-row').length}][drug_name]" value="${(item.drug_name || '').replace(/"/g, '&quot;')}" placeholder="ระบุชื่อยาที่แพ้">
          </div>
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-check-circle me-2"></i>วิธีที่ทราบว่าแพ้</label>
            <select class="form-select" name="drug_items[${container.querySelectorAll('.drug-allergy-row').length}][detection_method]">
              <option value="">เลือกวิธีที่ทราบ</option>
              <option value="symptoms_after_use" ${item.detection_method === 'symptoms_after_use' ? 'selected' : ''}>มีอาการแพ้หลังจากใช้ยา</option>
              <option value="skin_testing" ${item.detection_method === 'skin_testing' ? 'selected' : ''}>การทดสอบทางผิวหนัง</option>
              <option value="blood_test" ${item.detection_method === 'blood_test' ? 'selected' : ''}>ทดสอบโดยการเจาะเลือด</option>
              <option value="repeat_use" ${item.detection_method === 'repeat_use' ? 'selected' : ''}>ทดสอบโดยการใช้ยาซ้ำ</option>
            </select>
          </div>
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>อาการที่เกิดขึ้น</label>
            <select class="form-select" name="drug_items[${container.querySelectorAll('.drug-allergy-row').length}][symptoms]">
              <option value="">เลือกอาการ</option>
              <option value="type1" ${item.symptoms === 'type1' ? 'selected' : ''}>ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก</option>
              <option value="type2" ${item.symptoms === 'type2' ? 'selected' : ''}>ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก และหายใจลำบาก</option>
              <option value="type3" ${item.symptoms === 'type3' ? 'selected' : ''}>ผื่นแดงลักษณะเป็นผื่นราบ และผื่นนูน กระจายอย่างสมมาตร</option>
              <option value="type4" ${item.symptoms === 'type4' ? 'selected' : ''}>ผิวแดงทั่วตัวและผื่นตุ่มหนองขนาดเล็กจำนวนมาก</option>
              <option value="type5" ${item.symptoms === 'type5' ? 'selected' : ''}>ผื่นที่เกิดขึ้นสามารถพบได้หลายแบบ</option>
              <option value="type6" ${item.symptoms === 'type6' ? 'selected' : ''}>ผื่นตุ่มน้ำ มีผิวหนังกำพร้าตายและหลุดลอก</option>
            </select>
          </div>
          <div class="col-md-12">
            <div class="form-check form-switch mt-2">
              <input class="form-check-input" type="checkbox" name="drug_items[${container.querySelectorAll('.drug-allergy-row').length}][has_allergy_card]" value="true" ${item.has_allergy_card ? 'checked' : ''}>
              <label class="form-check-label fw-bold"><i class="bi bi-card-checklist me-2"></i>มีบัตรแพ้ยา</label>
            </div>
          </div>
        </div>
      `;
      container.appendChild(row);
    });
  }

  window.addFoodAllergyRow = function(item = {}) {
    const container = document.getElementById('foodAllergyRows');
    if (!container) return;

    const index = container.querySelectorAll('.food-allergy-row').length;
    const row = document.createElement('div');
    row.className = 'food-allergy-row border rounded p-3 bg-light';
    row.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>รายการที่ ${index + 1}</strong>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeFoodAllergyRow(this)">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-egg me-2"></i>ชื่ออาหารที่แพ้</label>
          <input type="text" class="form-control" name="food_items[${index}][food_name]" value="${(item.food_name || '').replace(/"/g, '&quot;')}" placeholder="ระบุชื่ออาหารที่แพ้">
        </div>
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-check-circle me-2"></i>วิธีที่ทราบว่าแพ้</label>
          <select class="form-select" name="food_items[${index}][detection_method]">
            <option value="">เลือกวิธีที่ทราบ</option>
            <option value="symptoms_after_eat" ${item.detection_method === 'symptoms_after_eat' ? 'selected' : ''}>มีอาการแพ้หลังรับประทานอาหาร</option>
            <option value="repeat_eat" ${item.detection_method === 'repeat_eat' ? 'selected' : ''}>ทดสอบโดยการรับประทานอาหารซ้ำ</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-droplet me-2"></i>อาการทางเดินอาหาร</label>
          <div class="row g-2">
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][digestive_symptoms][]" value="bloody_stool" ${item.digestive_symptoms?.includes('bloody_stool') ? 'checked' : ''}><label class="form-check-label">ถ่ายเป็นมูกเลือดเป็น ๆ หาย ๆ</label></div></div>
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][digestive_symptoms][]" value="vomiting" ${item.digestive_symptoms?.includes('vomiting') ? 'checked' : ''}><label class="form-check-label">อาเจียน</label></div></div>
          </div>
        </div>
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-droplet-half me-2"></i>อาการทางผิวหนัง</label>
          <div class="row g-2">
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="urticaria" ${item.skin_symptoms?.includes('urticaria') ? 'checked' : ''}><label class="form-check-label">ผื่นลมพิษทั่วตัว</label></div></div>
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="eye_swelling" ${item.skin_symptoms?.includes('eye_swelling') ? 'checked' : ''}><label class="form-check-label">ตาบวม</label></div></div>
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="mouth_rash" ${item.skin_symptoms?.includes('mouth_rash') ? 'checked' : ''}><label class="form-check-label">มีผื่นรอบปาก</label></div></div>
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="atopic_dermatitis" ${item.skin_symptoms?.includes('atopic_dermatitis') ? 'checked' : ''}><label class="form-check-label">ผื่นภูมิแพ้ผิวหนัง</label></div></div>
          </div>
        </div>
        <div class="col-md-12">
          <label class="form-label fw-bold"><i class="bi bi-wind me-2"></i>อาการทางเดินหายใจ</label>
          <div class="row g-2">
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="wheezing" ${item.respiratory_symptoms?.includes('wheezing') ? 'checked' : ''}><label class="form-check-label">หายใจมีเสียงวี้ด</label></div></div>
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="runny_nose" ${item.respiratory_symptoms?.includes('runny_nose') ? 'checked' : ''}><label class="form-check-label">น้ำมูกไหล</label></div></div>
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="nasal_congestion" ${item.respiratory_symptoms?.includes('nasal_congestion') ? 'checked' : ''}><label class="form-check-label">คัดจมูก</label></div></div>
            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="breathing_difficulty" ${item.respiratory_symptoms?.includes('breathing_difficulty') ? 'checked' : ''}><label class="form-check-label">หายใจลำบาก</label></div></div>
          </div>
        </div>
      </div>
    `;
    container.appendChild(row);
  };

  window.removeFoodAllergyRow = function(button) {
    button.closest('.food-allergy-row')?.remove();
    const container = document.getElementById('foodAllergyRows');
    if (container && container.children.length === 0) {
      window.addFoodAllergyRow();
    }
  };

  function getFoodAllergyRowsFromForm() {
    return Array.from(document.querySelectorAll('.food-allergy-row')).map(row => ({
      id: row.dataset.id || '',
      food_name: row.querySelector('input[name$="[food_name]"]')?.value?.trim() || '',
      detection_method: row.querySelector('select[name$="[detection_method]"]')?.value || '',
      digestive_symptoms: Array.from(row.querySelectorAll('input[name$="[digestive_symptoms][]"]:checked')).map(cb => cb.value),
      skin_symptoms: Array.from(row.querySelectorAll('input[name$="[skin_symptoms][]"]:checked')).map(cb => cb.value),
      respiratory_symptoms: Array.from(row.querySelectorAll('input[name$="[respiratory_symptoms][]"]:checked')).map(cb => cb.value)
    })).filter(item => item.food_name || item.detection_method || item.digestive_symptoms.length || item.skin_symptoms.length || item.respiratory_symptoms.length);
  }

  function populateFoodAllergyRows(items = []) {
    const container = document.getElementById('foodAllergyRows');
    if (!container) return;

    container.innerHTML = '';
    if (!items || items.length === 0) {
      window.addFoodAllergyRow();
      return;
    }

    items.forEach(item => {
      const row = document.createElement('div');
      row.className = 'food-allergy-row border rounded p-3 bg-light';
      row.dataset.id = item.id || '';
      row.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>รายการที่ ${container.querySelectorAll('.food-allergy-row').length + 1}</strong>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeFoodAllergyRow(this)">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-egg me-2"></i>ชื่ออาหารที่แพ้</label>
            <input type="text" class="form-control" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][food_name]" value="${(item.food_name || '').replace(/"/g, '&quot;')}" placeholder="ระบุชื่ออาหารที่แพ้">
          </div>
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-check-circle me-2"></i>วิธีที่ทราบว่าแพ้</label>
            <select class="form-select" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][detection_method]">
              <option value="">เลือกวิธีที่ทราบ</option>
              <option value="symptoms_after_eat" ${item.detection_method === 'symptoms_after_eat' ? 'selected' : ''}>มีอาการแพ้หลังรับประทานอาหาร</option>
              <option value="repeat_eat" ${item.detection_method === 'repeat_eat' ? 'selected' : ''}>ทดสอบโดยการรับประทานอาหารซ้ำ</option>
            </select>
          </div>
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-droplet me-2"></i>อาการทางเดินอาหาร</label>
            <div class="row g-2">
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][digestive_symptoms][]" value="bloody_stool" ${item.digestive_symptoms?.includes('bloody_stool') ? 'checked' : ''}><label class="form-check-label">ถ่ายเป็นมูกเลือดเป็น ๆ หาย ๆ</label></div></div>
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][digestive_symptoms][]" value="vomiting" ${item.digestive_symptoms?.includes('vomiting') ? 'checked' : ''}><label class="form-check-label">อาเจียน</label></div></div>
            </div>
          </div>
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-droplet-half me-2"></i>อาการทางผิวหนัง</label>
            <div class="row g-2">
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][skin_symptoms][]" value="urticaria" ${item.skin_symptoms?.includes('urticaria') ? 'checked' : ''}><label class="form-check-label">ผื่นลมพิษทั่วตัว</label></div></div>
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][skin_symptoms][]" value="eye_swelling" ${item.skin_symptoms?.includes('eye_swelling') ? 'checked' : ''}><label class="form-check-label">ตาบวม</label></div></div>
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][skin_symptoms][]" value="mouth_rash" ${item.skin_symptoms?.includes('mouth_rash') ? 'checked' : ''}><label class="form-check-label">มีผื่นรอบปาก</label></div></div>
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][skin_symptoms][]" value="atopic_dermatitis" ${item.skin_symptoms?.includes('atopic_dermatitis') ? 'checked' : ''}><label class="form-check-label">ผื่นภูมิแพ้ผิวหนัง</label></div></div>
            </div>
          </div>
          <div class="col-md-12">
            <label class="form-label fw-bold"><i class="bi bi-wind me-2"></i>อาการทางเดินหายใจ</label>
            <div class="row g-2">
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][respiratory_symptoms][]" value="wheezing" ${item.respiratory_symptoms?.includes('wheezing') ? 'checked' : ''}><label class="form-check-label">หายใจมีเสียงวี้ด</label></div></div>
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][respiratory_symptoms][]" value="runny_nose" ${item.respiratory_symptoms?.includes('runny_nose') ? 'checked' : ''}><label class="form-check-label">น้ำมูกไหล</label></div></div>
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][respiratory_symptoms][]" value="nasal_congestion" ${item.respiratory_symptoms?.includes('nasal_congestion') ? 'checked' : ''}><label class="form-check-label">คัดจมูก</label></div></div>
              <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${container.querySelectorAll('.food-allergy-row').length}][respiratory_symptoms][]" value="breathing_difficulty" ${item.respiratory_symptoms?.includes('breathing_difficulty') ? 'checked' : ''}><label class="form-check-label">หายใจลำบาก</label></div></div>
            </div>
          </div>
        </div>
      `;
      container.appendChild(row);
    });
  }

  /* ── Open Drug Allergy Modal ── */
  window.openDrugAllergyModal = function() {
    fetch('../../include/process/manage_allergies.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'get', type: 'drug', student_id: studentId })
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        populateDrugAllergyRows(Array.isArray(result.data) ? result.data : []);
      } else {
        populateDrugAllergyRows([]);
      }
      const modal = new bootstrap.Modal(document.getElementById('drugAllergyModal'));
      modal.show();
    })
    .catch(error => {
      console.error('Error loading drug allergy data:', error);
      populateDrugAllergyRows([]);
      const modal = new bootstrap.Modal(document.getElementById('drugAllergyModal'));
      modal.show();
    });
  };

  /* ── Open Food Allergy Modal ── */
  window.openFoodAllergyModal = function() {
    fetch('../../include/process/manage_allergies.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'get', type: 'food', student_id: studentId })
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        populateFoodAllergyRows(Array.isArray(result.data) ? result.data : []);
      } else {
        populateFoodAllergyRows([]);
      }
      const modal = new bootstrap.Modal(document.getElementById('foodAllergyModal'));
      modal.show();
    })
    .catch(error => {
      console.error('Error loading food allergy data:', error);
      populateFoodAllergyRows([]);
      const modal = new bootstrap.Modal(document.getElementById('foodAllergyModal'));
      modal.show();
    });
  };

  /* ── Save Drug Allergy ── */
  window.saveDrugAllergy = function() {
    const rows = getDrugAllergyRowsFromForm();
    const payload = {
      action: 'save_multiple',
      type: 'drug',
      student_id: studentId,
      items: rows
    };

    fetch('../../include/process/manage_allergies.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'บันทึกข้อมูลการแพ้ยาเรียบร้อยแล้ว');
        bootstrap.Modal.getInstance(document.getElementById('drugAllergyModal')).hide();
        loadAllergiesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถบันทึกข้อมูลได้');
      }
    })
    .catch(error => {
      console.error('Error saving drug allergy:', error);
      showToast('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
    });
  };

  /* ── Save Food Allergy ── */
  window.saveFoodAllergy = function() {
    const rows = getFoodAllergyRowsFromForm();
    const payload = {
      action: 'save_multiple',
      type: 'food',
      student_id: studentId,
      items: rows
    };

    fetch('../../include/process/manage_allergies.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'บันทึกข้อมูลการแพ้อาหารเรียบร้อยแล้ว');
        bootstrap.Modal.getInstance(document.getElementById('foodAllergyModal')).hide();
        loadAllergiesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถบันทึกข้อมูลได้');
      }
    })
    .catch(error => {
      console.error('Error saving food allergy:', error);
      showToast('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
    });
  };

  /* ── Load Allergies Data ── */
  function loadAllergiesData() {
    Promise.all([
      fetch('../../include/process/manage_allergies.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'get', type: 'drug', student_id: studentId })
      }).then(r => r.json()),
      fetch('../../include/process/manage_allergies.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'get', type: 'food', student_id: studentId })
      }).then(r => r.json())
    ])
    .then(([drugData, foodData]) => {
      const drugAllergies = drugData.status === 'success' && Array.isArray(drugData.data) ? drugData.data : [];
      const foodAllergies = foodData.status === 'success' && Array.isArray(foodData.data) ? foodData.data : [];

      const drugContent = document.getElementById('drugAllergyContent');
      if (drugAllergies.length > 0) {
        drugContent.innerHTML = `
          <div style="font-size:0.8rem;color:var(--gray-500);margin-bottom:0.35rem;">ยาที่แพ้ (${drugAllergies.length} รายการ)</div>
          ${drugAllergies.map(item => `
            <div style="margin-bottom:0.45rem;">
              <span class="allergy-tag allergy-tag-drug"><i class="bi bi-exclamation-circle"></i> ${escapeHtml(item.drug_name || '-')}</span>
              <div style="font-size:0.78rem;color:var(--gray-500);margin-top:0.2rem;">วิธีที่ทราบ: ${escapeHtml(getDetectionMethodText(item.detection_method))}</div>
              <div style="font-size:0.78rem;color:var(--gray-500);">อาการ: ${escapeHtml(getDrugSymptomsText(item.symptoms))}</div>
              <div style="font-size:0.78rem;color:var(--gray-500);">บัตรแพ้ยา: ${item.has_allergy_card ? '<span style="color:var(--success);font-weight:600;">มี</span>' : '<span style="color:var(--danger);font-weight:600;">ไม่มี</span>'}</div>
            </div>
          `).join('')}
        `;
        document.getElementById('allergyBanner').style.display = 'flex';
        document.getElementById('allergyText').textContent = `มีประวัติการแพ้ยา ${drugAllergies.length} รายการ`;
      } else {
        drugContent.innerHTML = '<div style="font-size:0.85rem;color:var(--success);font-weight:600;"><i class="bi bi-check-circle me-1"></i>ไม่มีประวัติการแพ้ยา</div>';
      }

      const foodContent = document.getElementById('foodAllergyContent');
      if (foodAllergies.length > 0) {
        foodContent.innerHTML = `
          <div style="font-size:0.8rem;color:var(--gray-500);margin-bottom:0.35rem;">อาหารที่แพ้ (${foodAllergies.length} รายการ)</div>
          ${foodAllergies.map(item => {
            let digestiveSymptoms = [];
            let skinSymptoms = [];
            let respiratorySymptoms = [];

            try {
              if (item.digestive_symptoms) {
                digestiveSymptoms = typeof item.digestive_symptoms === 'string'
                  ? item.digestive_symptoms.replace(/{|}/g, '').split(',').filter(Boolean)
                  : item.digestive_symptoms;
              }
              if (item.skin_symptoms) {
                skinSymptoms = typeof item.skin_symptoms === 'string'
                  ? item.skin_symptoms.replace(/{|}/g, '').split(',').filter(Boolean)
                  : item.skin_symptoms;
              }
              if (item.respiratory_symptoms) {
                respiratorySymptoms = typeof item.respiratory_symptoms === 'string'
                  ? item.respiratory_symptoms.replace(/{|}/g, '').split(',').filter(Boolean)
                  : item.respiratory_symptoms;
              }
            } catch (e) {
              console.error('Error parsing food allergy symptoms:', e);
            }

            return `
              <div style="margin-bottom:0.45rem;">
                <span class="allergy-tag allergy-tag-food"><i class="bi bi-exclamation-circle"></i> ${escapeHtml(item.food_name || '-')}</span>
                <div style="font-size:0.78rem;color:var(--gray-500);margin-top:0.2rem;">วิธีที่ทราบ: ${escapeHtml(getDetectionMethodText(item.detection_method))}</div>
                ${digestiveSymptoms.length > 0 ? `<div style="font-size:0.78rem;color:var(--gray-500);">อาการทางเดินอาหาร: ${escapeHtml(digestiveSymptoms.map(s => getAllergySymptomText(s)).join(', '))}</div>` : ''}
                ${skinSymptoms.length > 0 ? `<div style="font-size:0.78rem;color:var(--gray-500);">อาการทางผิวหนัง: ${escapeHtml(skinSymptoms.map(s => getAllergySymptomText(s)).join(', '))}</div>` : ''}
                ${respiratorySymptoms.length > 0 ? `<div style="font-size:0.78rem;color:var(--gray-500);">อาการทางเดินหายใจ: ${escapeHtml(respiratorySymptoms.map(s => getAllergySymptomText(s)).join(', '))}</div>` : ''}
              </div>
            `;
          }).join('')}
        `;
        document.getElementById('allergyBanner').style.display = 'flex';
      } else {
        foodContent.innerHTML = '<div style="font-size:0.85rem;color:var(--success);font-weight:600;"><i class="bi bi-check-circle me-1"></i>ไม่มีประวัติการแพ้อาหาร</div>';
      }
    })
    .catch(error => {
      console.error('Error loading allergies:', error);
    });
  }

  function getDetectionMethodText(method) {
    const methods = {
      'symptoms_after_use': 'มีอาการแพ้หลังจากใช้ยา',
      'skin_testing': 'การทดสอบทางผิวหนัง',
      'blood_test': 'ทดสอบโดยการเจาะเลือด',
      'repeat_use': 'ทดสอบโดยการใช้ยาซ้ำ',
      'symptoms_after_eat': 'มีอาการแพ้หลังรับประทานอาหาร',
      'repeat_eat': 'ทดสอบโดยการรับประทานอาหารซ้ำ'
    };
    return methods[method] || method || '-';
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
    return symptoms[type] || type || '-';
  }

  function getAllergySymptomText(symptom) {
    const symptoms = {
      'bloody_stool': 'ถ่ายเป็นมูกเลือดเป็น ๆ หาย ๆ',
      'vomiting': 'อาเจียน',
      'urticaria': 'ผื่นลมพิษทั่วตัว',
      'eye_swelling': 'ตาบวม',
      'mouth_rash': 'มีผื่นรอบปาก',
      'atopic_dermatitis': 'ผื่นภูมิแพ้ผิวหนัง',
      'wheezing': 'หายใจมีเสียงวี้ด',
      'runny_nose': 'น้ำมูกไหล',
      'nasal_congestion': 'คัดจมูก',
      'breathing_difficulty': 'หายใจลำบาก'
    };
    return symptoms[symptom] || symptom || '-';
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /* ── Utility: Format date to Thai Buddhist era ── */
  function formatThaiDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear() + 543;
    return `${day}/${month}/${year}`;
  }

  function formatThaiDateFull(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    const dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    const monthNames = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    return dayNames[date.getDay()] + 'ที่ ' + date.getDate() + ' ' + monthNames[date.getMonth()] + ' ' + (date.getFullYear() + 543);
  }

  function formatTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    return String(date.getHours()).padStart(2, '0') + ':' + String(date.getMinutes()).padStart(2, '0') + ' น.';
  }

  /* ── Load Vaccines Data ── */
  function loadVaccinesData() {
    const tbody = document.getElementById('vaccineTableBody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...</td></tr>';

    fetch('../../include/process/get_vaccines_by_student.php?student_id=' + encodeURIComponent(studentId))
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          renderVaccineTable(result.data.age_groups);
          updateSummary(result.data.summary);
        } else {
          throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
        }
      })
      .catch(error => {
        console.error('Error loading vaccines:', error);
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--danger);"><i class="bi bi-exclamation-triangle me-2"></i>${error.message}</td></tr>`;
      });
  }

  function updateSummary(summary) {
    document.getElementById('receivedCount').textContent = summary.received_vaccines;
    document.getElementById('pendingCount').textContent = summary.pending_vaccines;
    document.getElementById('coveragePercent').textContent = summary.coverage_percent + '%';
  }

  function renderVaccineTable(ageGroups) {
    const tbody = document.getElementById('vaccineTableBody');
    let html = '';
    let lastAgeGroup = '';
    let hasAnyContent = false;

    ageGroups.forEach(group => {
      hasAnyContent = true;
      
      if (group.vaccines.length === 0) {
        // แสดงช่วงอายุที่ไม่มีรายการวัคซีน
        html += '<tr>';
        html += `<td><span class="age-group-cell">${group.age_group}</span><?php if ($is_admin): ?><div class="d-flex gap-1 mt-1"><button class="btn btn-sm btn-outline-primary" style="padding:1px 6px;font-size:0.65rem;" onclick="editAgeGroup(${group.id}, '${group.age_group.replace(/'/g, "\\'")}', ${group.display_order || 0})"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" style="padding:1px 6px;font-size:0.65rem;" onclick="deleteAgeGroup(${group.id})"><i class="bi bi-trash"></i></button></div><?php endif; ?></td>`;
        html += `<td><span class="vaccine-status-pending"><i class="bi bi-info-circle"></i> ยังไม่มีรายการวัคซีน</span></td>`;
        html += `<td><span class="vaccine-status-pending"><i class="bi bi-clock"></i> -</span></td>`;
        html += '<td><div class="d-flex gap-1 flex-nowrap">';
       
        html += '</div></td></tr>';
        return;
      }

      group.vaccines.forEach((vaccine, index) => {
        const ageGroupName = vaccine.vaccine_record_id ? '' : group.age_group;
        lastAgeGroup = group.age_group;

        html += '<tr>';
        html += `<td>${index === 0 ? `<span class="age-group-cell">${group.age_group}</span><?php if ($is_admin): ?><div class="d-flex gap-1 mt-1"><button class="btn btn-sm btn-outline-primary" style="padding:1px 6px;font-size:0.65rem;" onclick="editAgeGroup(${group.id}, '${group.age_group.replace(/'/g, "\\'")}', ${group.display_order || 0})"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" style="padding:1px 6px;font-size:0.65rem;" onclick="deleteAgeGroup(${group.id})"><i class="bi bi-trash"></i></button></div><?php endif; ?>` : ''}</td>`;
        html += `<td><div class="vaccine-name-cell">${vaccine.vaccine_name || '-'}`;
        <?php if ($is_admin): ?>
        html += `<button class="icon-btn icon-btn-edit me-2 ms-2" title="แก้ไขรายการวัคซีน" onclick="editVaccineList(${vaccine.id})"><i class="bi bi-pencil-square"></i></button>`;
        html += `<button class="icon-btn icon-btn-delete" title="ลบรายการวัคซีน" onclick="deleteVaccineList(${vaccine.id})"><i class="bi bi-trash"></i></button>`;
        <?php endif; ?>
        html += `</div></td>`;
        html += '<td>';
        if (vaccine.vaccine_record_id) {
          const date = new Date(vaccine.vaccine_date);
          const formattedDate = `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear() + 543}`;
          html += `<span class="vaccine-status-done"><i class="bi bi-check-circle-fill"></i> ${formattedDate}</span>`;
        } else {
          html += `<span class="vaccine-status-pending"><i class="bi bi-clock"></i> ยังไม่ได้รับ</span>`;
        }
        html += '</td>';
        html += '<td><div class="d-flex gap-1 flex-nowrap">';
        if (vaccine.vaccine_record_id) {
          html += `<button class="icon-btn icon-btn-view" title="ดูรายละเอียด" onclick="viewVaccineDetails(${vaccine.vaccine_record_id})"><i class="bi bi-eye"></i></button>`;
          <?php if ($is_admin || $is_teacher): ?>
          html += `<button class="icon-btn icon-btn-edit" title="แก้ไข" onclick="editVaccineRecord(${vaccine.vaccine_record_id})"><i class="bi bi-pencil"></i></button>`;
          <?php endif; ?>
          <?php if ($is_admin): ?>
          html += `<button class="icon-btn icon-btn-delete" title="ลบ" onclick="deleteVaccineRecord(${vaccine.vaccine_record_id})"><i class="bi bi-trash"></i></button>`;
          <?php endif; ?>
        } else {
          <?php if ($is_admin || $is_teacher || $is_student): ?>
          html += `<button class="icon-btn icon-btn-add" title="บันทึกการฉีด" onclick="addVaccineRecord(${vaccine.id})"><i class="bi bi-plus-lg"></i></button>`;
          <?php endif; ?>
        }
       
        html += '</div></td></tr>';
      });

      <?php if ($is_admin): ?>
      html += `<tr><td colspan="4" style="padding:0.5rem 1rem;background:var(--gray-50);border-bottom:1px solid var(--gray-200);">
   
      </td></tr>`;
      <?php endif; ?>
    });

    if (!hasAnyContent || ageGroups.length === 0) {
      html = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-info-circle me-2"></i>ยังไม่มีข้อมูลช่วงอายุและรายการวัคซีน</td></tr>';
    }

    tbody.innerHTML = html;
  }

  function renderAgeGroupActions(group, container) {
    <?php if ($is_admin): ?>
    const ageGroupActions = document.createElement('div');
    ageGroupActions.className = 'd-flex gap-1 mt-2';
    ageGroupActions.innerHTML = `
      <button class="icon-btn icon-btn-edit btn-sm" title="แก้ไขช่วงอายุ" onclick="editAgeGroup(${group.id}, '${group.age_group.replace(/'/g, "\\'")}', ${group.display_order || 0})"><i class="bi bi-pencil"></i></button>
      <button class="icon-btn icon-btn-delete btn-sm" title="ลบช่วงอายุ" onclick="deleteAgeGroup(${group.id})"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(ageGroupActions);
    <?php endif; ?>
  }

  /* ── Vaccine Functions ── */
  window.addVaccineRecord = function(vaccineListId) {
    // ตรวจสอบว่ามี vaccineListId หรือไม่
    if (!vaccineListId || vaccineListId === '' || vaccineListId === '0') {
      showToast('error', 'กรุณาเลือกรายการวัคซีน');
      return;
    }
    
    document.getElementById('vaccineForm').reset();
    document.getElementById('vaccineListId').value = vaccineListId;
    document.getElementById('vaccineId').value = ''; // เคลียร์ค่าสำหรับการเพิ่มใหม่
    document.getElementById('vaccineDate').value = new Date().toISOString().split('T')[0];

    // ดึงข้อมูลรายการวัคซีน
    fetch('../../include/process/get_vaccinelist_detail.php?id=' + vaccineListId)
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          document.getElementById('vaccineRecordName').value = result.data.vaccine_name || '';
          document.getElementById('vaccineListRecordId').value = result.data.vaccine_id;
          // ไม่ต้องกำหนดค่า vaccineId เพราะเป็นการเพิ่มข้อมูลใหม่
          new bootstrap.Modal(document.getElementById('vaccineModal')).show();
        } else {
          showToast('error', 'ไม่สามารถโหลดข้อมูลวัคซีนได้');
        }
      })
      .catch(error => {
        showToast('error', 'เกิดข้อผิดพลาด');
      });
  };

  window.editVaccineRecord = function(vaccineId) {
    fetch('../../include/process/get_vaccine_detail.php?id=' + vaccineId)
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          const data = result.data;
          document.getElementById('vaccineForm').reset();
          document.getElementById('vaccineId').value = data.id;
          document.getElementById('vaccineListRecordId').value = data.vaccine_list_id;
          document.getElementById('vaccineDate').value = data.vaccine_date;
          document.getElementById('vaccineRecordName').value = data.vaccine_name || '';
          document.getElementById('vaccineNumber').value = data.vaccine_number || '';
          document.getElementById('vaccineLocation').value = data.vaccine_location || '';
          document.getElementById('vaccineProvider').value = data.vaccine_provider || '';
          document.getElementById('lotNumber').value = data.lot_number || '';
          document.getElementById('nextAppointment').value = data.next_appointment || '';
          document.getElementById('vaccineNote').value = data.vaccine_note || '';
          new bootstrap.Modal(document.getElementById('vaccineModal')).show();
        } else {
          showToast('error', 'ไม่สามารถโหลดข้อมูลได้');
        }
      })
      .catch(error => {
        showToast('error', 'เกิดข้อผิดพลาด');
      });
  };

  window.viewVaccineDetails = function(vaccineId) {
    fetch('../../include/process/get_vaccine_detail.php?id=' + vaccineId)
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          const d = result.data;
          document.getElementById('detailVaccineName').textContent = d.vaccine_name || '-';
          document.getElementById('detailVaccineNumber').textContent = d.vaccine_number || '-';
          document.getElementById('detailVaccineDate').textContent = formatThaiDate(d.vaccine_date);
          document.getElementById('detailAgeGroup').textContent = d.age_group || '-';
          document.getElementById('detailLocation').textContent = d.vaccine_location || '-';
          document.getElementById('detailProvider').textContent = d.vaccine_provider || '-';
          document.getElementById('detailLotNumber').textContent = d.lot_number || '-';
          document.getElementById('detailNextAppointment').textContent = formatThaiDate(d.next_appointment);
          document.getElementById('detailNote').textContent = d.vaccine_note || '-';

          <?php if ($is_admin || $is_teacher): ?>
          const editBtn = document.getElementById('detailEditBtn');
          editBtn.style.display = 'inline-flex';
          editBtn.onclick = function() {
            bootstrap.Modal.getInstance(document.getElementById('vaccineDetailModal')).hide();
            editVaccineRecord(vaccineId);
          };
          <?php endif; ?>

          new bootstrap.Modal(document.getElementById('vaccineDetailModal')).show();
        } else {
          showToast('error', result.message || 'ไม่สามารถโหลดข้อมูลได้');
        }
      })
      .catch(error => {
        showToast('error', 'เกิดข้อผิดพลาด');
      });
  };

  window.saveVaccine = function() {
    const form = document.getElementById('vaccineForm');
    
    // ตรวจสอบ vaccine_list_id ว่ามีค่าหรือไม่
    const vaccineListRecordId = document.getElementById('vaccineListRecordId').value;
    if (!vaccineListRecordId || vaccineListRecordId.trim() === '') {
      showToast('error', 'กรุณาเลือกรายการวัคซีน');
      return;
    }
    
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // ตรวจสอบและแปลงชื่อฟิลด์ให้ตรงกับ API
    if (data.student_id && !data.studentid) {
      data.studentid = data.student_id;
    }
    
    // เพิ่มการส่งค่า id สำหรับการแก้ไขข้อมูล
    const vaccineId = document.getElementById('vaccineId').value;
    if (vaccineId && vaccineId.trim() !== '') {
      data.id = vaccineId;
    }

    fetch('../../include/process/save_vaccine.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'บันทึกข้อมูลสำเร็จ');
        bootstrap.Modal.getInstance(document.getElementById('vaccineModal')).hide();
        loadVaccinesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถบันทึกข้อมูลได้');
      }
    })
    .catch(error => {
      showToast('error', 'เกิดข้อผิดพลาด');
    });
  };

  window.deleteVaccineRecord = function(vaccineId) {
    if (!confirm('คุณต้องการลบข้อมูลการฉีดวัคซีนนี้หรือไม่?')) return;

    fetch('../../include/process/delete_vaccine_record.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: vaccineId })
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'ลบข้อมูลสำเร็จ');
        loadVaccinesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถลบข้อมูลได้');
      }
    })
    .catch(error => {
      showToast('error', 'เกิดข้อผิดพลาด');
    });
  };

  /* ── Age Group Functions ── */
  window.addAgeGroup = function() {
    document.getElementById('ageGroupForm').reset();
    document.getElementById('ageGroupId').value = '';
    document.getElementById('ageGroupModalLabel').innerHTML = '<i class="bi bi-plus-circle me-2"></i>เพิ่มช่วงอายุ';
    new bootstrap.Modal(document.getElementById('ageGroupModal')).show();
  };

  window.editAgeGroup = function(id, name, order) {
    document.getElementById('ageGroupForm').reset();
    document.getElementById('ageGroupId').value = id;
    document.getElementById('ageGroupName').value = name;
    document.getElementById('ageGroupModalLabel').innerHTML = '<i class="bi bi-pencil me-2"></i>แก้ไขช่วงอายุ';
    new bootstrap.Modal(document.getElementById('ageGroupModal')).show();
  };

  window.saveAgeGroup = function() {
    const form = document.getElementById('ageGroupForm');
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    const id = document.getElementById('ageGroupId').value;
    const ageGroupName = document.getElementById('ageGroupName').value.trim();
    
    if (!ageGroupName) {
      showToast('error', 'กรุณากรอกช่วงอายุ');
      return;
    }
    
    const data = {
      id: id || null,
      age_group: ageGroupName,
      display_order: id ? (document.getElementById('ageGroupOrder')?.value || 999) : 999
    };

    const url = id ? '../../include/process/update_vaccine_age_group.php' : '../../include/process/save_vaccine_age_group.php';

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'บันทึกข้อมูลสำเร็จ');
        bootstrap.Modal.getInstance(document.getElementById('ageGroupModal')).hide();
        loadVaccinesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถบันทึกข้อมูลได้');
      }
    })
    .catch(error => {
      console.error('Error saving age group:', error);
      showToast('error', 'เกิดข้อผิดพลาดในการบันทึก');
    });
  };

  window.deleteAgeGroup = function(id) {
    if (!confirm('คุณต้องการลบช่วงอายุนี้หรือไม่?')) return;

    fetch('../../include/process/delete_vaccine_age_group.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'ลบข้อมูลสำเร็จ');
        loadVaccinesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถลบข้อมูลได้');
      }
    })
    .catch(error => {
      showToast('error', 'เกิดข้อผิดพลาด');
    });
  };

  /* ── Vaccine List Functions ── */
  window.addVaccineList = function() {
    document.getElementById('vaccineListForm').reset();
    document.getElementById('vaccineListId').value = '';
    document.getElementById('vaccineListModalLabel').innerHTML = '<i class="bi bi-plus-circle me-2"></i>เพิ่มรายการวัคซีน';

    fetch('../../include/process/get_age_groups.php')
      .then(r => r.json())
      .then(result => {
        if (result.age_groups) {
          const select = document.getElementById('ageGroup');
          select.innerHTML = '<option value="">เลือกกลุ่มอายุ</option>';
          result.age_groups.forEach(group => {
            select.innerHTML += `<option value="${group.id}">${group.age_group}</option>`;
          });
          new bootstrap.Modal(document.getElementById('vaccineListModal')).show();
        } else {
          showToast('error', 'ไม่สามารถโหลดข้อมูลกลุ่มอายุได้');
        }
      })
      .catch(error => {
        showToast('error', 'เกิดข้อผิดพลาด');
      });
  };

  window.addVaccineListForGroup = function(ageGroupId) {
    document.getElementById('vaccineListForm').reset();
    document.getElementById('vaccineListId').value = '';
    document.getElementById('vaccineListModalLabel').innerHTML = '<i class="bi bi-plus-circle me-2"></i>เพิ่มรายการวัคซีน';

    fetch('../../include/process/get_age_groups.php')
      .then(r => r.json())
      .then(result => {
        if (result.age_groups) {
          const select = document.getElementById('ageGroup');
          select.innerHTML = '<option value="">เลือกกลุ่มอายุ</option>';
          result.age_groups.forEach(group => {
            select.innerHTML += `<option value="${group.id}" ${group.id === ageGroupId ? 'selected' : ''}>${group.age_group}</option>`;
          });
          new bootstrap.Modal(document.getElementById('vaccineListModal')).show();
        } else {
          showToast('error', 'ไม่สามารถโหลดข้อมูลกลุ่มอายุได้');
        }
      })
      .catch(error => {
        showToast('error', 'เกิดข้อผิดพลาด');
      });
  };

  window.editVaccineList = function(id) {
    fetch('../../include/process/get_vaccinelist_detail.php?id=' + id)
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          document.getElementById('vaccineListForm').reset();
          document.getElementById('vaccineListId').value = id;
          document.getElementById('vaccineName').value = result.data.vaccine_name || '';
          document.getElementById('vaccineDescription').value = result.data.vaccine_description || '';
          document.getElementById('vaccineListModalLabel').innerHTML = '<i class="bi bi-pencil me-2"></i>แก้ไขรายการวัคซีน';

          fetch('../../include/process/get_age_groups.php')
            .then(r => r.json())
            .then(ageResult => {
              if (ageResult.age_groups) {
                const select = document.getElementById('ageGroup');
                select.innerHTML = '<option value="">เลือกกลุ่มอายุ</option>';
                ageResult.age_groups.forEach(group => {
                  select.innerHTML += `<option value="${group.id}" ${group.id === result.data.age_group_id ? 'selected' : ''}>${group.age_group}</option>`;
                });
                new bootstrap.Modal(document.getElementById('vaccineListModal')).show();
              }
            });
        } else {
          showToast('error', 'ไม่สามารถโหลดข้อมูลได้');
        }
      })
      .catch(error => {
        showToast('error', 'เกิดข้อผิดพลาด');
      });
  };

  window.saveVaccineList = function() {
    const form = document.getElementById('vaccineListForm');
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    const id = document.getElementById('vaccineListId').value;
    const data = {
      id: id,
      age_group_id: document.getElementById('ageGroup').value,
      vaccine_name: document.getElementById('vaccineName').value,
      vaccine_description: document.getElementById('vaccineDescription').value
    };

    const url = id ? '../../include/process/update_vaccinelist_record.php' : '../../include/process/save_list_vaccine.php';

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'บันทึกข้อมูลสำเร็จ');
        bootstrap.Modal.getInstance(document.getElementById('vaccineListModal')).hide();
        loadVaccinesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถบันทึกข้อมูลได้');
      }
    })
    .catch(error => {
      showToast('error', 'เกิดข้อผิดพลาด');
    });
  };

  window.deleteVaccineList = function(id) {
    if (!confirm('คุณต้องการลบรายการวัคซีนนี้หรือไม่?')) return;

    fetch('../../include/process/delete_vaccinelist_record.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(result => {
      if (result.status === 'success') {
        showToast('success', 'ลบข้อมูลสำเร็จ');
        loadVaccinesData();
      } else {
        showToast('error', result.message || 'ไม่สามารถลบข้อมูลได้');
      }
    })
    .catch(error => {
      showToast('error', 'เกิดข้อผิดพลาด');
    });
  };

  /* ── Tab Switching - Load vaccines when tab is clicked ── */
  const vaccineTabBtn = document.querySelector('[data-tab="vaccine"]');
  if (vaccineTabBtn) {
    vaccineTabBtn.addEventListener('click', function() {
      loadVaccinesData();
    });
  }

  /* ── Tab Switching - Load attendance when tab is clicked ── */
  const attendanceTabBtn = document.querySelector('[data-tab="attendance"]');
  if (attendanceTabBtn) {
    attendanceTabBtn.addEventListener('click', function() {
      loadAttendanceData();
    });
  }

  /* ── Load Attendance Data ── */
  function loadAttendanceData() {
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...</td></tr>';

    fetch('../../include/process/get_attendance_by_student.php?student_id=' + encodeURIComponent(studentId))
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          renderAttendanceTable(result.data.records);
          updateAttendanceSummary(result.data.summary);
        } else {
          throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
        }
      })
      .catch(error => {
        console.error('Error loading attendance:', error);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--danger);"><i class="bi bi-exclamation-triangle me-2"></i>' + error.message + '</td></tr>';
      });
  }

  function updateAttendanceSummary(summary) {
    document.getElementById('att-stat-present').textContent = summary.present;
    document.getElementById('att-stat-absent').textContent = summary.absent;
    document.getElementById('att-stat-leave').textContent = summary.leave;
    document.getElementById('att-stat-rate').textContent = summary.rate + '%';
  }

  function renderAttendanceTable(records) {
    const tbody = document.getElementById('attendanceTableBody');
    if (!records || records.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-info-circle me-2"></i>ไม่มีข้อมูลการมาเรียน</td></tr>';
      return;
    }

    let html = '';
    records.forEach(r => {
      const date = new Date(r.check_date);
      const dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
      const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
      const thaiYear = date.getFullYear() + 543;
      const formattedDate = dayNames[date.getDay()] + ' ' + date.getDate() + ' ' + monthNames[date.getMonth()] + ' ' + thaiYear;

      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const timeStr = hours + ':' + minutes + ' น.';

      let statusClass = 'status-present';
      let statusIcon = 'bi-check-circle-fill';
      if (r.status === 'absent') {
        statusClass = 'status-absent';
        statusIcon = 'bi-x-circle-fill';
      } else if (r.status === 'leave') {
        statusClass = 'status-leave';
        statusIcon = 'bi-calendar-x-fill';
      } else if (r.status === 'late') {
        statusClass = 'status-late';
        statusIcon = 'bi-exclamation-circle-fill';
      }

      let checkoutClass = 'status-pending';
      let checkoutIcon = 'bi-question-circle';
      let checkoutText = '-';
      if (r.status_checkout === 'checked_out') {
        checkoutClass = 'status-checkout';
        checkoutIcon = 'bi-house-check';
        checkoutText = 'กลับแล้ว';
      } else if (r.status_checkout === 'no_checked_out') {
        checkoutClass = 'status-pending';
        checkoutIcon = 'bi-clock';
        checkoutText = 'ยังไม่กลับ';
      }

      const hasTime = r.status === 'present' || r.status === 'late';
      const displayCheckin = hasTime ? timeStr : '<span style="color:var(--gray-400);">-</span>';
      const displayCheckout = r.check_out_time
        ? (typeof r.check_out_time === 'string'
            ? r.check_out_time.substring(0, 5) + ' น.'
            : '-')
        : '<span style="color:var(--gray-400);">-</span>';

      const detailBtn = '<button class="icon-btn icon-btn-view" title="ดูรายละเอียด" onclick="viewAttendanceDetail(' + r.id + ')"><i class="bi bi-eye"></i></button>';

      html += '<tr>' +
        '<td style="font-size:0.82rem;color:var(--gray-600);">' + formattedDate + '</td>' +
        '<td><span class="status-badge ' + statusClass + '"><i class="bi ' + statusIcon + '"></i> ' + r.status_th + '</span></td>' +
        '<td style="font-size:0.82rem;">' + displayCheckin + '</td>' +
        '<td><span class="status-badge ' + checkoutClass + '"><i class="bi ' + checkoutIcon + '"></i> ' + checkoutText + '</span></td>' +
        '<td style="font-size:0.82rem;">' + displayCheckout + '</td>' +
        '<td><div class="d-flex gap-1">' + detailBtn + '</div></td>' +
        '</tr>';
    });

    tbody.innerHTML = html;
  }

  /* ── View Attendance Detail ── */
  window.viewAttendanceDetail = function(id) {
    fetch('../../include/function/get_attendance_detail.php?id=' + id)
      .then(r => r.json())
      .then(result => {
        if (result.status !== 'success') {
          showToast('error', result.message || 'ไม่สามารถโหลดข้อมูลได้');
          return;
        }
        const d = result.data;
        document.getElementById('attDetailStudent').textContent = (d.prefix_th || '') + ' ' + (d.firstname_th || '') + ' ' + (d.lastname_th || '');
        document.getElementById('attDetailStudentId').textContent = 'รหัส: ' + (d.student_id || '-');
        document.getElementById('attDetailGroup').textContent = 'กลุ่ม: ' + (d.child_group || '-') + ' | ห้อง: ' + (d.classroom || '-');
        document.getElementById('attDetailDate').textContent = formatThaiDateFull(d.check_date);
        document.getElementById('attDetailCheckin').textContent = d.check_date ? formatTime(d.check_date) : '-';
        document.getElementById('attDetailCheckoutTime').textContent = d.check_out_time ? formatTime(d.check_out_time) : '-';
        document.getElementById('attDetailCreatedAt').textContent = d.created_at ? formatThaiDateFull(d.created_at) + ' ' + formatTime(d.created_at) : '-';

        // Temperature
        document.getElementById('attDetailTemperature').textContent = d.temperature ? d.temperature + ' °C' : '-';

        // Symptoms
        const symptoms = [];
        if (d.has_runny_nose === true || d.has_runny_nose === 't' || d.has_runny_nose === '1') symptoms.push('น้ำมูกไหล');
        if (d.has_cough === true || d.has_cough === 't' || d.has_cough === '1') symptoms.push('ไอ');
        if (d.has_rash === true || d.has_rash === 't' || d.has_rash === '1') symptoms.push('ผื่น');
        if (d.has_red_eyes === true || d.has_red_eyes === 't' || d.has_red_eyes === '1') symptoms.push('ตาแดง');
        if (d.other_symptoms) symptoms.push(d.other_symptoms);
        document.getElementById('attDetailSymptoms').textContent = symptoms.length > 0 ? symptoms.join(', ') : 'ไม่มีอาการ';

        const statusColors = { present: 'success', late: 'warning', absent: 'danger', leave: 'warning' };
        const statusIcons = { present: 'bi-check-circle-fill', late: 'bi-exclamation-circle-fill', absent: 'bi-x-circle-fill', leave: 'bi-calendar-x-fill' };
        const icon = statusIcons[d.status] || 'bi-question-circle';
        document.getElementById('attDetailStatus').innerHTML = '<span class="status-badge status-' + d.status + '"><i class="bi ' + icon + '"></i> ' + (d.status_th || '-') + '</span>';

        const pickupRow = document.getElementById('attDetailPickupRow');
        if (d.status_checkout === 'checked_out') {
          document.getElementById('attDetailCheckoutStatus').innerHTML = '<span class="status-badge status-checkout"><i class="bi bi-house-check"></i> กลับแล้ว</span>';
          pickupRow.style.display = 'block';
          document.getElementById('attDetailPickupBy').textContent = d.picked_up_by || d.leave_note || '-';
        } else if (d.status_checkout === 'no_checked_out') {
          document.getElementById('attDetailCheckoutStatus').innerHTML = '<span class="status-badge status-pending"><i class="bi bi-clock"></i> ยังไม่กลับ</span>';
          pickupRow.style.display = 'none';
        } else {
          document.getElementById('attDetailCheckoutStatus').textContent = '-';
          pickupRow.style.display = 'none';
        }

        const leaveRow = document.getElementById('attDetailLeaveNoteRow');
        if (d.status === 'leave' && d.leave_note) {
          leaveRow.style.display = 'block';
          document.getElementById('attDetailLeaveNote').textContent = d.leave_note;
        } else {
          leaveRow.style.display = 'none';
        }

        new bootstrap.Modal(document.getElementById('attendanceDetailModal')).show();
      })
      .catch(error => {
        showToast('error', 'เกิดข้อผิดพลาด: ' + error.message);
      });
  };

  /* ── Add Vaccine List Button ── */
  const btnAddVaccineList = document.getElementById('btnAddVaccineList');
  if (btnAddVaccineList) {
    btnAddVaccineList.addEventListener('click', addVaccineList);
  }

  /* ── Add Age Group Button ── */
  const btnAddAgeGroup = document.getElementById('btnAddAgeGroup');
  if (btnAddAgeGroup) {
    btnAddAgeGroup.addEventListener('click', addAgeGroup);
  }

  /* ── Setup Allergy Button Events ── */
  document.addEventListener('DOMContentLoaded', function() {
    const btnEditDrugAllergy = document.getElementById('btnEditDrugAllergy');
    const btnEditFoodAllergy = document.getElementById('btnEditFoodAllergy');
    
    if (btnEditDrugAllergy) {
      btnEditDrugAllergy.addEventListener('click', function() {
        window.openDrugAllergyModal();
      });
    }
    
    if (btnEditFoodAllergy) {
      btnEditFoodAllergy.addEventListener('click', function() {
        window.openFoodAllergyModal();
      });
    }
  });

  /* ── Initialize ── */
  loadAllergiesData();

})();
</script>
</body>
</html>
