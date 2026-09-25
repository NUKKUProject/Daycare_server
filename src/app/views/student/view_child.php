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
// กำหนดแท็บที่เปิดอยู่และป้องกันค่าที่ไม่อยู่ในรายการ
$allowedTabs = ['profile', 'vaccine', 'attendance', 'health', 'growth'];
$currentTab = $_GET['tab'] ?? 'profile';
if (!in_array($currentTab, $allowedTabs, true)) {
    $currentTab = 'profile';
}

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

/* ── Detail View (checklist_history style) ── */
.detail-container {
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}
.detail-header {
    background: linear-gradient(120deg, #2C3E50, #3498DB);
    color: white;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}
.detail-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: linear-gradient(120deg, rgba(255,255,255,0.1), transparent);
    transform: skewX(-30deg);
}
.detail-header h5 {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: white;
    position: relative;
}
.student-info p { margin-bottom: 0.35rem; font-size: 0.95rem; position: relative; z-index: 1; }
.student-info p i { margin-right: 0.5rem; width: 1.2rem; text-align: center; }
.detail-body { padding: 2rem; }
.detail-section {
    background: #F8FAFC;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #ECF0F1;
    transition: all 0.3s ease;
}
.detail-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}
.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
}
.detail-icon {
    color: #3498DB;
    font-size: 1.2rem;
    padding: 0.8rem;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 10px;
}
.detail-content { flex: 1; }
.detail-content h6 {
    color: #2C3E50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.detail-list { list-style: none; padding: 0; margin: 0; }
.detail-list li {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.5rem 0;
    color: #34495E;
}
.detail-list li::before {
    content: "•";
    color: #3498DB;
    font-size: 1.5rem;
    line-height: 0;
}
.detail-note {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(52, 152, 219, 0.05);
    border-left: 3px solid #3498DB;
    border-radius: 0 10px 10px 0;
    color: #34495E;
    font-style: italic;
}
.detail-footer {
    margin-top: 2rem;
    padding: 2rem;
    background: #F8FAFC;
    border-radius: 15px;
}
.detail-signature {
    text-align: right;
    font-style: italic;
    color: #34495E;
}
.detail-signature span {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
  /* ───────────────────────────────────────────
       MODAL SHELL
    ─────────────────────────────────────────── */
    #healthDetailModal .modal-content {
      border: none;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
    }

    #healthDetailModal .modal-header {
      background: linear-gradient(135deg, #1a2a4a 0%, #2563eb 100%);
      padding: 1.4rem 1.75rem;
      border: none;
    }

    #healthDetailModal .modal-footer {
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      padding: 1rem 1.75rem;
    }

    /* ───────────────────────────────────────────
       SCROLLABLE BODY
    ─────────────────────────────────────────── */
    #healthDetailModal .modal-body {
      padding: 0;
      background: #f1f5f9;
      max-height: 72vh;
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }

    #healthDetailModal .modal-body::-webkit-scrollbar { width: 6px; }
    #healthDetailModal .modal-body::-webkit-scrollbar-track { background: transparent; }
    #healthDetailModal .modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

    /* ───────────────────────────────────────────
       DETAIL CONTAINER
    ─────────────────────────────────────────── */
    .hd-container { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; }

    /* ── Student info card ── */
    .hd-info-card {
      background: #fff;
      border-radius: 16px;
      padding: 1.25rem 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 0.6rem 1.25rem;
    }

    .hd-info-item {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      font-size: 0.875rem;
      color: #374151;
    }

    .hd-info-item i {
      color: #2563eb;
      font-size: 1rem;
      margin-top: 1px;
      flex-shrink: 0;
    }

    .hd-info-item strong { color: #1e293b; }

    /* ── Section wrapper ── */
    .hd-section {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .hd-section-title {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.85rem 1.25rem;
      background: linear-gradient(90deg, #f0f7ff, #e8f0fe);
      border-bottom: 1px solid #dbeafe;
      font-size: 0.9rem;
      font-weight: 700;
      color: #1e40af;
      margin: 0;
    }

    .hd-section-title i { font-size: 1rem; }

    /* ── Grid of check items ── */
    .hd-items-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1px;
      background: #f1f5f9;
    }

    .hd-item {
      background: #fff;
      padding: 1rem 1.25rem;
      display: flex;
      gap: 0.85rem;
      align-items: flex-start;
      transition: background 0.15s;
    }

    .hd-item:hover { background: #f8faff; }

    /* ── Icon badge ── */
    .hd-item-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #dbeafe, #ede9fe);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .hd-item-icon i { font-size: 1rem; color: #2563eb; }

    /* ── Item body ── */
    .hd-item-body { flex: 1; min-width: 0; }

    .hd-item-title {
      font-size: 0.78rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 0.3rem;
    }

    /* ── Tag pills for checked items ── */
    .hd-tags { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 0.4rem; }

    .hd-tag {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
      border-radius: 99px;
      padding: 0.18rem 0.65rem;
      font-size: 0.78rem;
      font-weight: 500;
    }

    .hd-tag i { font-size: 0.7rem; }

    .hd-tag.hd-tag-empty {
      background: #f8fafc;
      color: #94a3b8;
      border-color: #e2e8f0;
    }

    /* ── Note chips ── */
    .hd-notes { display: flex; flex-direction: column; gap: 0.3rem; margin-top: 0.35rem; }

    .hd-note {
      display: flex;
      align-items: flex-start;
      gap: 0.4rem;
      background: #fefce8;
      border: 1px solid #fde68a;
      border-radius: 8px;
      padding: 0.3rem 0.6rem;
      font-size: 0.78rem;
      color: #78350f;
    }

    .hd-note i { color: #d97706; font-size: 0.8rem; margin-top: 1px; flex-shrink: 0; }

    /* ── Footer signature ── */
    .hd-footer-sig {
      background: #fff;
      border-radius: 16px;
      padding: 1rem 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 0.875rem;
      color: #374151;
      border-left: 4px solid #2563eb;
    }

    .hd-footer-sig i { color: #2563eb; font-size: 1.1rem; }

    /* ───────────────────────────────────────────
       RESPONSIVE TWEAKS
    ─────────────────────────────────────────── */
    @media (max-width: 576px) {
      .hd-container { padding: 1rem; gap: 1rem; }
      .hd-info-card { grid-template-columns: 1fr; }
      .hd-items-grid { grid-template-columns: 1fr; }
    }
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
    
</div>

  <!-- Allergy Warning Banner -->
  <div class="allergy-banner" id="allergyBanner" style="display:none;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div class="allergy-banner-content">
      <strong>แจ้งเตือน: พบประวัติการแพ้</strong>
      <span id="allergyText">กำลังโหลดข้อมูล...</span>
    </div>
  </div>

  <!-- ===== Tab Navigation ===== -->
    <div class="tab-nav-wrap">
      <button class="tab-btn <?= $currentTab === 'profile' ? 'active' : '' ?>" data-tab="profile">
        <i class="bi bi-person-badge"></i>
        <span>ประวัติประจำตัว</span>
    </button>
    <button class="tab-btn <?= $currentTab === 'vaccine' ? 'active' : '' ?>" data-tab="vaccine">
      <i class="fa-solid fa-syringe"></i>
      <span>วัคซีน</span>
    </button>
    <button class="tab-btn <?= $currentTab === 'attendance' ? 'active' : '' ?>" data-tab="attendance">
      <i class="bi bi-calendar-check"></i>
      <span>การมาเรียน</span>
    </button>
    <button class="tab-btn <?= $currentTab === 'health' ? 'active' : '' ?>" data-tab="health">
      <i class="fa-solid fa-stethoscope"></i>
      <span>ตรวจร่างกาย</span>
    </button>
    <button class="tab-btn <?= $currentTab === 'growth' ? 'active' : '' ?>" data-tab="growth">
      <i class="bi bi-graph-up"></i>
      <span>การเจริญเติบโต</span>
    </button>
  </div>

  <!-- ===== TAB: PROFILE ===== -->
  <div id="tab-profile" class="tab-content-pane" style="<?= $currentTab === 'profile' ? '' : 'display:none;' ?>">
    <div class="content-card">
      <div class="content-card-header">
        <div class="section-title">
          <i class="bi bi-person-circle icon-primary" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
          ข้อมูลประวัติประจำตัว
        </div>
        <div class="profile-actions">
        <?php if ($is_admin || $is_teacher): ?>
            <button class="btn-action btn-edit" id="btnEdit">
                <i class="bi bi-pencil"></i><span>แก้ไข</span>
            </button>
            <button class="btn-action btn-save" id="btnSave" style="display:none;">
                <i class="bi bi-check-lg"></i><span>บันทึก</span>
            </button>
            <button class="btn-action btn-cancel" id="btnCancel" style="display:none;">
                <i class="bi bi-x-lg"></i><span>ยกเลิก</span>
            </button>      
        <?php endif; ?>
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
              style="width:100%;max-width:200px;height:20กดคำ0px;object-fit:cover;border:2px solid var(--gray-200);"
              alt="Profile" />
            <div id="imageUploadWrap" style="display:none;">
              <div class="d-flex gap-2 justify-content-center flex-wrap">
                <button type="button" class="btn-action btn-cancel" id="takeProfilePhoto"
                        style="font-size:0.75rem;padding:5px 8px;">
                  <i class="bi bi-camera-fill"></i> ถ่ายรูป
                </button>
                <button type="button" class="btn-action btn-cancel" id="uploadProfilePhoto"
                        style="font-size:0.75rem;padding:5px 8px;">
                  <i class="bi bi-upload"></i> อัปโหลดรูป
                </button>
              </div>
              <input type="file" name="profile_image" accept="image/*" style="display:none;"
                     id="profileImageInput" />
              <small class="d-block text-muted mt-1">รองรับ JPG, PNG, GIF, WEBP ขนาดไม่เกิน 5MB</small>
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
                <select class="form-select" name="academic_year" id="academic_year" disabled>
                  <?php foreach ($academicYears as $year): ?>
                    <option value="<?= htmlspecialchars($year['name']) ?>" <?= ($child['academic_year'] ?? '') == $year['name'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($year['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">กลุ่มเด็ก</label>
                <select class="form-select" name="child_group" id="child_group" disabled>
                  <option value="เด็กกลาง" <?= ($child['child_group'] ?? '') === 'เด็กกลาง' ? 'selected' : '' ?>>เด็กกลาง</option>
                  <option value="เด็กโต" <?= ($child['child_group'] ?? '') === 'เด็กโต' ? 'selected' : '' ?>>เด็กโต</option>
                  <option value="เตรียมอนุบาล" <?= ($child['child_group'] ?? '') === 'เตรียมอนุบาล' ? 'selected' : '' ?>>เตรียมอนุบาล</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">ห้องเรียน</label>
                <select class="form-select" name="classroom" id="classroom" disabled>
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
             <select class="form-select" name="sex" disabled>
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
             <select class="form-select" name="blood_type" disabled>
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
                <div class="parent-photo-editor text-center">
                  <img id="fatherImagePreview" src="<?= !empty($child['father_image']) ? htmlspecialchars($child['father_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Father" class="parent-avatar" style="width:80px;height:80px;" />
                  <div id="fatherImageUploadWrap" class="mt-1" style="display:none;">
                    <button type="button" class="btn-action btn-cancel" data-parent-camera="father" style="font-size:0.7rem;padding:3px 6px;" title="ถ่ายรูปบิดา"><i class="bi bi-camera-fill"></i> ถ่ายรูป</button>
                    <button type="button" class="btn-action btn-cancel" data-parent-upload="father" style="font-size:0.7rem;padding:3px 6px;" title="อัปโหลดรูปบิดา"><i class="bi bi-upload"></i> อัปโหลด</button>
                    <input type="file" name="father_image" id="fatherImageInput" accept="image/*" style="display:none;">
                  </div>
                </div>
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
                <div class="parent-photo-editor text-center">
                  <img id="motherImagePreview" src="<?= !empty($child['mother_image']) ? htmlspecialchars($child['mother_image']) : '../../../public/assets/images/avatar.png' ?>" alt="Mother" class="parent-avatar" style="width:80px;height:80px;" />
                  <div id="motherImageUploadWrap" class="mt-1" style="display:none;">
                    <button type="button" class="btn-action btn-cancel" data-parent-camera="mother" style="font-size:0.7rem;padding:3px 6px;" title="ถ่ายรูปมารดา"><i class="bi bi-camera-fill"></i> ถ่ายรูป</button>
                    <button type="button" class="btn-action btn-cancel" data-parent-upload="mother" style="font-size:0.7rem;padding:3px 6px;" title="อัปโหลดรูปมารดา"><i class="bi bi-upload"></i> อัปโหลด</button>
                    <input type="file" name="mother_image" id="motherImageInput" accept="image/*" style="display:none;">
                  </div>
                </div>
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

          <!-- ผู้ปกครอง/ผู้ดูแล -->
          <div class="col-md-4">
            <div class="parent-card">
              <div class="parent-card-header">
                <img src="<?= !empty($child['relative_image']) ? htmlspecialchars($child['relative_image']) : '../../../public/assets/images/avatar.png' ?>" alt="ผู้ปกครอง/ผู้ดูแล" class="parent-avatar" style="width:80px;height:80px;" />
                <div>
                  <div class="parent-card-title"><i class="bi bi-person me-1"></i>ผู้ปกครอง/ผู้ดูแล</div>
                  <div class="parent-card-subtitle">Guardian</div>
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
          <span class="section-divider-title"><i class="fa-solid fa-stethoscope me-1"></i>ข้อมูลสุขภาพและการแพ้</span>
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

        <!-- Bottom Profile Actions -->
        </form> <div class="profile-actions-bottom" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200); text-align: center;">
          <?php if ($is_admin || $is_teacher): ?>
              <button class="btn-action btn-edit" id="btnEditBottom">
                  <i class="bi bi-pencil"></i><span>แก้ไข</span>
              </button>
              <button class="btn-action btn-save" id="btnSaveBottom" style="display:none;">
                  <i class="bi bi-check-lg"></i><span>บันทึก</span>
              </button>
              <button class="btn-action btn-cancel" id="btnCancelBottom" style="display:none;">
                  <i class="bi bi-x-lg"></i><span>ยกเลิก</span>
              </button>      
          <?php endif; ?>
        </div>
      </div><!-- end card-body -->
    </div><!-- end content-card -->
  </div>

  <!-- Modal ถ่ายรูปโปรไฟล์ด้วยกล้องจริง -->
  <div class="modal fade" id="profileCameraModal" tabindex="-1" aria-labelledby="profileCameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="profileCameraModalLabel">
            <i class="bi bi-camera-fill me-2"></i>ถ่ายรูปนักเรียน
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="ปิด"></button>
        </div>
          <div class="modal-body text-center">
            <video id="profileCameraVideo" class="w-100 rounded-3 bg-dark" autoplay playsinline
                   style="max-height:55vh;object-fit:cover;"></video>
            <canvas id="profileCameraCanvas" class="d-none"></canvas>
            <div id="profileCameraError" class="alert alert-warning mt-3 mb-0" style="display:none;"></div>
            <div id="profileCameraZoomWrap" class="mt-3" style="display:none;">
              <label for="profileCameraZoom" class="form-label mb-1">
                <i class="bi bi-zoom-in me-1"></i>ซูม <span id="profileCameraZoomValue">1.0x</span>
              </label>
              <input type="range" class="form-range" id="profileCameraZoom" step="0.1">
            </div>
            <div class="small text-muted mt-2">กรุณาอนุญาตให้เว็บไซต์เข้าถึงกล้อง</div>
          </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="button" class="btn btn-outline-primary" id="switchProfileCamera">
            <i class="bi bi-arrow-repeat me-1"></i>สลับกล้อง
          </button>
          <button type="button" class="btn btn-primary" id="captureProfilePhoto">
            <i class="bi bi-camera-fill me-1"></i>ถ่ายภาพ
          </button>
        </div>
      </div>
    </div>
  </div>

  

  <!-- ===== TAB: VACCINE ===== -->
  <div id="tab-vaccine" class="tab-content-pane" style="<?= $currentTab === 'vaccine' ? '' : 'display:none;' ?>">
    <div class="content-card">
      <div class="content-card-header">
        <div class="section-title">
          <i class="fa-solid fa-syringe icon-success" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
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
                  <i class="fa-solid fa-syringe me-2"></i>ชื่อวัคซีน
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
              <i class="fa-solid fa-syringe me-2"></i>บันทึกการฉีดวัคซีน
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
  <div id="tab-attendance" class="tab-content-pane" style="<?= $currentTab === 'attendance' ? '' : 'display:none;' ?>">
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
  <div id="tab-health" class="tab-content-pane" style="<?= $currentTab === 'health' ? '' : 'display:none;' ?>">
    <div class="content-card">
      <div class="content-card-header">
        <div class="section-title">
          <i class="fa-solid fa-stethoscope icon-danger" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;"></i>
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
            <tbody id="healthTableBody">
              <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>คลิกแท็บเพื่อโหลดข้อมูล</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== TAB: GROWTH ===== -->
  <div id="tab-growth" class="tab-content-pane" style="<?= $currentTab === 'growth' ? '' : 'display:none;' ?>">

    <!-- Growth Stats -->
    <div class="growth-stats">
      <div class="growth-stat-card">
        <div class="growth-stat-value" id="growth-weight-val">-</div>
        <div class="growth-stat-unit">กก.</div>
        <div class="growth-stat-label">น้ำหนัก</div>
        <span class="growth-stat-status" id="growth-weight-status">รอข้อมูล</span>
      </div>
      <div class="growth-stat-card">
        <div class="growth-stat-value" id="growth-height-val">-</div>
        <div class="growth-stat-unit">ซม.</div>
        <div class="growth-stat-label">ส่วนสูง</div>
        <span class="growth-stat-status" id="growth-height-status">รอข้อมูล</span>
      </div>
      <div class="growth-stat-card">
        <div class="growth-stat-value" id="growth-head-val">-</div>
        <div class="growth-stat-unit">ซม.</div>
        <div class="growth-stat-label">เส้นรอบศีรษะ</div>
        <span class="growth-stat-status" id="growth-head-status">รอข้อมูล</span>
      </div>
      <div class="growth-stat-card">
        <div class="growth-stat-value" id="growth-bmi-val">-</div>
        <div class="growth-stat-unit">BMI</div>
        <div class="growth-stat-label">ดัชนีมวลกาย</div>
        <span class="growth-stat-status" id="growth-bmi-status">รอข้อมูล</span>
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
          <tbody id="growth-dev-body">
            <tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>รอข้อมูล</td></tr>
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
                <th>วันที่ตรวจ</th>
                <th>อายุ</th>
                <th>น้ำหนัก</th>
                <th>ส่วนสูง</th>
                <th>เส้นรอบศีรษะ</th>
                <th>BMI</th>
                <th>ผลประเมิน</th>
              </tr>
            </thead>
            <tbody id="growth-history-body">
              <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>คลิกแท็บเพื่อโหลดข้อมูล</td></tr>
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
        </div>
        <div class="chart-card-body">
          <canvas id="chartWeight"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-card-header">
          <div class="chart-card-title"><i class="bi bi-bar-chart me-1"></i>ส่วนสูงตามเกณฑ์อายุ</div>
        </div>
        <div class="chart-card-body">
          <canvas id="chartHeight"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-card-header">
          <div class="chart-card-title"><i class="bi bi-bar-chart me-1"></i>BMI ตามเกณฑ์อายุ</div>
        </div>
        <div class="chart-card-body">
          <canvas id="chartBMI"></canvas>
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
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#0f2460 0%,#1a3a8f 60%,#1e4db7 100%);padding:1.25rem 1.75rem;">
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
        <button type="button" class="btn px-4 fw-semibold" style="border-radius:8px;background:linear-gradient(135deg,#0f2460 0%,#1a3a8f 60%,#1e4db7 100%);color:#fff;border:none;" data-bs-dismiss="modal">
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
      <div class="modal-body" id="growthDetailContent">
        <div style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลด...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-action btn-cancel" data-bs-dismiss="modal">
          <i class="bi bi-x-lg"></i><span>ปิด</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal: Health Detail ===== -->
<div class="modal fade" id="healthDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header border-0">
        <h5 class="modal-title text-white fw-bold d-flex align-items-center gap-2">
          <i class="fa-solid fa-stethoscope"></i> รายละเอียดการตรวจร่างกาย
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="healthDetailContent"></div>
      </div>

      <div class="modal-footer">
        <button type="button"
          class="btn px-4 fw-semibold"
          style="border-radius:10px;background:linear-gradient(135deg,#1a2a4a,#2563eb);color:#fff;border:none;"
          data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-2"></i>ปิด
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha384-bs/nf9FbdNouRbMiFcrcZfLXYPKiPaGVGplVbv7dLGECccEXDW+S3zjqSKR5ZEaD" crossorigin="anonymous"></script>

<script>
(function () {
  'use strict';

  const studentId = '<?= addslashes(htmlspecialchars($studentid)) ?>';

  /* ── Child group / classroom ── */
  const childGroupSelect = document.getElementById('child_group');
  const classroomSelect = document.getElementById('classroom');
  let classroomRequestId = 0;
  let profileEditMode = false;

  async function loadClassroomsByGroup() {
    if (!childGroupSelect || !classroomSelect) return;

    const childGroup = childGroupSelect.value;
    const requestId = ++classroomRequestId;
    const previouslySelected = classroomSelect.value;

    classroomSelect.innerHTML = '<option value="">กำลังโหลดห้องเรียน...</option>';
    classroomSelect.disabled = true;

    try {
      const response = await fetch(
        `../../include/function/get_classrooms.php?child_group=${encodeURIComponent(childGroup)}`
      );
      if (!response.ok) {
        throw new Error('ไม่สามารถโหลดข้อมูลห้องเรียนได้');
      }

      const classrooms = await response.json();
      // ป้องกันผลลัพธ์จาก request เก่าทับข้อมูล เมื่อเปลี่ยนกลุ่มอย่างรวดเร็ว
      if (requestId !== classroomRequestId) return;
      if (!Array.isArray(classrooms)) {
        throw new Error('รูปแบบข้อมูลห้องเรียนไม่ถูกต้อง');
      }

      classroomSelect.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = classrooms.length ? '-- เลือกห้องเรียน --' : '-- ไม่พบห้องเรียน --';
      classroomSelect.appendChild(placeholder);

      classrooms.forEach((classroom) => {
        const option = document.createElement('option');
        option.value = classroom.classroom_name;
        option.textContent = classroom.classroom_name;
        if (classroom.classroom_name === previouslySelected) {
          option.selected = true;
        }
        classroomSelect.appendChild(option);
      });
      classroomSelect.disabled = !profileEditMode;
    } catch (error) {
      if (requestId !== classroomRequestId) return;
      classroomSelect.innerHTML = '<option value="">-- โหลดห้องเรียนไม่สำเร็จ --</option>';
      classroomSelect.disabled = !profileEditMode;
      console.error('Failed to load classrooms:', error);
      if (typeof showToast === 'function') {
        showToast('error', error.message || 'ไม่สามารถโหลดห้องเรียนได้');
      }
    }
  }

  if (childGroupSelect) {
    childGroupSelect.addEventListener('change', loadClassroomsByGroup);
  }

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
  const btnEditBottom   = document.getElementById('btnEditBottom');
  const btnSaveBottom   = document.getElementById('btnSaveBottom');
  const btnCancelBottom = document.getElementById('btnCancelBottom');
  const imageWrap = document.getElementById('imageUploadWrap');
  const parentImageWraps = [
    document.getElementById('fatherImageUploadWrap'),
    document.getElementById('motherImageUploadWrap')
  ];
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
    profileEditMode = true;
    getEditableFields().forEach(el => {
      el.removeAttribute('readonly');
      el.removeAttribute('disabled');
    });
    imageWrap.style.display = 'block';
    parentImageWraps.forEach(wrap => { if (wrap) wrap.style.display = 'block'; });
    editAllergyBtns.forEach(b => { if (b) b.style.display = 'flex'; });
    
    // Hide top edit buttons, show top save/cancel
    if (btnEdit) btnEdit.style.display = 'none';
    if (btnSave) btnSave.style.display = 'inline-flex';
    if (btnCancel) btnCancel.style.display = 'inline-flex';
    
    // Hide bottom edit buttons, show bottom save/cancel
    if (btnEditBottom) btnEditBottom.style.display = 'none';
    if (btnSaveBottom) btnSaveBottom.style.display = 'inline-flex';
    if (btnCancelBottom) btnCancelBottom.style.display = 'inline-flex';
    
    showToast('info', 'โหมดแก้ไขเปิดใช้งานแล้ว');
  }

  function exitEditMode(save) {
    profileEditMode = false;
    // ทำให้ผลลัพธ์จากการโหลดห้องเรียนที่ค้างอยู่ไม่ปลดล็อกช่องภายหลัง
    classroomRequestId++;
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
    imageWrap.style.display = 'none';
    parentImageWraps.forEach(wrap => { if (wrap) wrap.style.display = 'none'; });
    editAllergyBtns.forEach(b => { if (b) b.style.display = 'none'; });
    
    // Show top edit buttons, hide top save/cancel
    if (btnEdit) btnEdit.style.display = 'inline-flex';
    if (btnSave) btnSave.style.display = 'none';
    if (btnCancel) btnCancel.style.display = 'none';
    
    // Show bottom edit buttons, hide bottom save/cancel
    if (btnEditBottom) btnEditBottom.style.display = 'inline-flex';
    if (btnSaveBottom) btnSaveBottom.style.display = 'none';
    if (btnCancelBottom) btnCancelBottom.style.display = 'none';
    
    if (!save) {
      const preview = document.getElementById('profilePreview');
      const imageInput = document.getElementById('profileImageInput');
      if (preview && preview.dataset.originalSrc) {
        preview.src = preview.dataset.originalSrc;
      }
      if (imageInput) imageInput.value = '';
      ['father', 'mother'].forEach(type => {
        const parentPreview = document.getElementById(type + 'ImagePreview');
        const parentInput = document.getElementById(type + 'ImageInput');
        if (parentPreview && parentPreview.dataset.originalSrc) {
          parentPreview.src = parentPreview.dataset.originalSrc;
        }
        if (parentInput) parentInput.value = '';
      });
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
    
    // ใช้ FormData เพื่อส่งไฟล์รูปไปพร้อมกับข้อมูลฟอร์ม
    const profileForm = document.getElementById('profileForm');
    const formData = new FormData(profileForm);
    // ฟอร์มใช้ชื่อ studentid เพื่อแสดงผล แต่ endpoint รับ student_id
    formData.set('student_id', studentid);

    fetch('../../include/function/edit_child.php', {
      method: 'POST',
      body: formData
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
  
  if (btnEditBottom)   btnEditBottom.addEventListener('click', enterEditMode);
  if (btnSaveBottom)   btnSaveBottom.addEventListener('click', () => exitEditMode(true));
  if (btnCancelBottom) btnCancelBottom.addEventListener('click', () => exitEditMode(false));

  /* ── Profile Image Preview ── */
  const profileImageInput = document.getElementById('profileImageInput');
  const profilePreview = document.getElementById('profilePreview');
  const takeProfilePhoto = document.getElementById('takeProfilePhoto');
  const uploadProfilePhoto = document.getElementById('uploadProfilePhoto');
  const parentImageTargets = {};
  ['father', 'mother'].forEach(type => {
    const preview = document.getElementById(type + 'ImagePreview');
    const input = document.getElementById(type + 'ImageInput');
    if (preview) preview.dataset.originalSrc = preview.src;
    if (preview && input) parentImageTargets[type] = { preview, input };
  });

  if (profilePreview) {
    profilePreview.dataset.originalSrc = profilePreview.src;
  }

  const cameraModalElement = document.getElementById('profileCameraModal');
  const cameraVideo = document.getElementById('profileCameraVideo');
  const cameraCanvas = document.getElementById('profileCameraCanvas');
  const cameraError = document.getElementById('profileCameraError');
  const captureProfilePhoto = document.getElementById('captureProfilePhoto');
  const switchProfileCamera = document.getElementById('switchProfileCamera');
  const cameraZoomWrap = document.getElementById('profileCameraZoomWrap');
  const cameraZoom = document.getElementById('profileCameraZoom');
  const cameraZoomValue = document.getElementById('profileCameraZoomValue');
  const cameraModal = cameraModalElement && window.bootstrap
    ? bootstrap.Modal.getOrCreateInstance(cameraModalElement)
    : null;
  let profileCameraStream = null;
  let profileCameraTrack = null;
  let profileCameraFacing = 'user';
  let activeCameraTarget = { preview: profilePreview, input: profileImageInput, type: 'profile' };

  function showProfileImagePreview(file, previewElement) {
    if (!file) return false;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      showToast('error', 'กรุณาเลือกไฟล์รูปภาพ JPG, PNG, GIF หรือ WEBP');
      return false;
    }

    if (file.size > 5 * 1024 * 1024) {
      showToast('error', 'ขนาดไฟล์ต้องไม่เกิน 5MB');
      return false;
    }

    const reader = new FileReader();
    reader.onload = e => {
      if (previewElement) previewElement.src = e.target.result;
    };
    reader.readAsDataURL(file);
    return true;
  }

  function stopProfileCamera() {
    if (profileCameraStream) {
      profileCameraStream.getTracks().forEach(track => track.stop());
      profileCameraStream = null;
    }
    profileCameraTrack = null;
    if (cameraVideo) cameraVideo.srcObject = null;
    if (cameraZoomWrap) cameraZoomWrap.style.display = 'none';
    if (switchProfileCamera) switchProfileCamera.disabled = true;
  }

  function configureProfileCameraZoom() {
    if (!profileCameraTrack || !cameraZoom || !cameraZoomWrap) return;

    const capabilities = typeof profileCameraTrack.getCapabilities === 'function'
      ? profileCameraTrack.getCapabilities()
      : {};
    const zoom = capabilities.zoom;

    // แสดงเฉพาะกล้องที่รองรับการซูมจริง
    if (!zoom || Number(zoom.max) <= Number(zoom.min)) {
      cameraZoomWrap.style.display = 'none';
      return;
    }

    const settings = typeof profileCameraTrack.getSettings === 'function'
      ? profileCameraTrack.getSettings()
      : {};
    cameraZoom.min = zoom.min;
    cameraZoom.max = zoom.max;
    cameraZoom.step = zoom.step || 0.1;
    cameraZoom.value = settings.zoom || zoom.min;
    if (cameraZoomValue) {
      cameraZoomValue.textContent = Number(cameraZoom.value).toFixed(1) + 'x';
    }
    cameraZoomWrap.style.display = 'block';
  }

  async function startProfileCamera() {
    if (!cameraVideo || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('เบราว์เซอร์นี้ไม่รองรับการเปิดกล้อง หรือเว็บไซต์ไม่ได้เปิดผ่าน HTTPS');
    }

    profileCameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: profileCameraFacing } },
      audio: false
    });
    profileCameraTrack = profileCameraStream.getVideoTracks()[0] || null;
    cameraVideo.srcObject = profileCameraStream;
    await cameraVideo.play();
    configureProfileCameraZoom();
    if (switchProfileCamera) switchProfileCamera.disabled = false;
  }

  // เปิดกล้องจริงผ่าน getUserMedia แทนการเปิดตัวเลือกไฟล์
  async function openCameraForImage(target) {
    if (!cameraModal || !target || !target.input) {
      showToast('error', 'ไม่สามารถเปิดหน้าต่างกล้องได้');
      return;
    }

    activeCameraTarget = target;
    if (cameraError) {
      cameraError.style.display = 'none';
      cameraError.textContent = '';
    }
    if (captureProfilePhoto) captureProfilePhoto.disabled = true;
    if (switchProfileCamera) switchProfileCamera.disabled = true;
    cameraModal.show();

    try {
      await startProfileCamera();
      if (captureProfilePhoto) captureProfilePhoto.disabled = false;
    } catch (error) {
      if (cameraError) {
        cameraError.textContent = window.isSecureContext
          ? 'ไม่สามารถเปิดกล้องได้ กรุณาตรวจสอบสิทธิ์การใช้งานกล้อง'
          : 'การใช้กล้องต้องเปิดเว็บไซต์ผ่าน HTTPS หรือ localhost';
        cameraError.style.display = 'block';
      }
      stopProfileCamera();
    }
  }

  if (takeProfilePhoto && profileImageInput) {
    takeProfilePhoto.addEventListener('click', () => {
      openCameraForImage({ preview: profilePreview, input: profileImageInput, type: 'profile' });
    });
  }

  document.querySelectorAll('[data-parent-camera]').forEach(button => {
    button.addEventListener('click', () => {
      const target = parentImageTargets[button.dataset.parentCamera];
      openCameraForImage({ ...target, type: button.dataset.parentCamera });
    });
  });

  if (switchProfileCamera && profileImageInput) {
    switchProfileCamera.addEventListener('click', async () => {
      const previousFacing = profileCameraFacing;
      profileCameraFacing = previousFacing === 'user' ? 'environment' : 'user';
      switchProfileCamera.disabled = true;
      stopProfileCamera();

      try {
        await startProfileCamera();
      } catch (error) {
        profileCameraFacing = previousFacing;
        try {
          await startProfileCamera();
        } catch (restoreError) {
          if (cameraError) {
            cameraError.textContent = 'ไม่สามารถสลับกล้องได้ กรุณาตรวจสอบว่ามีกล้องอีกตัวและอนุญาตสิทธิ์แล้ว';
            cameraError.style.display = 'block';
          }
        }
      }
    });
  }

  if (cameraZoom) {
    cameraZoom.addEventListener('input', async () => {
      if (!profileCameraTrack || typeof profileCameraTrack.applyConstraints !== 'function') return;
      const value = Number(cameraZoom.value);
      if (cameraZoomValue) cameraZoomValue.textContent = value.toFixed(1) + 'x';
      try {
        await profileCameraTrack.applyConstraints({ advanced: [{ zoom: value }] });
      } catch (error) {
        showToast('warning', 'กล้องไม่รองรับระดับซูมนี้');
      }
    });
  }

  if (captureProfilePhoto) {
    captureProfilePhoto.addEventListener('click', () => {
      if (!cameraVideo || !cameraCanvas || !cameraVideo.videoWidth || !activeCameraTarget.input) return;

      cameraCanvas.width = cameraVideo.videoWidth;
      cameraCanvas.height = cameraVideo.videoHeight;
      cameraCanvas.getContext('2d').drawImage(
        cameraVideo, 0, 0, cameraCanvas.width, cameraCanvas.height
      );

      cameraCanvas.toBlob(blob => {
        if (!blob) return;
        const fileName = activeCameraTarget.type + '_camera.jpg';
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        if (showProfileImagePreview(file, activeCameraTarget.preview)) {
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          activeCameraTarget.input.files = dataTransfer.files;
          cameraModal?.hide();
        }
      }, 'image/jpeg', 0.9);
    });
  }

  if (cameraModalElement) {
    cameraModalElement.addEventListener('hidden.bs.modal', stopProfileCamera);
  }

  if (uploadProfilePhoto && profileImageInput) {
    uploadProfilePhoto.addEventListener('click', () => {
      profileImageInput.removeAttribute('capture');
      profileImageInput.click();
    });
  }

  document.querySelectorAll('[data-parent-upload]').forEach(button => {
    button.addEventListener('click', () => {
      const target = parentImageTargets[button.dataset.parentUpload];
      if (target && target.input) target.input.click();
    });
  });

  if (profileImageInput) {
    profileImageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!showProfileImagePreview(file, profilePreview)) this.value = '';
    });
  }

  Object.values(parentImageTargets).forEach(target => {
    target.input.addEventListener('change', function () {
      const file = this.files[0];
      if (!showProfileImagePreview(file, target.preview)) this.value = '';
    });
  });

  /* ── Delete Confirmation ── */
  // function confirmDelete(studentId) {
  //   if (confirm('คุณต้องการลบข้อมูลนักเรียนคนนี้หรือไม่?\nการลบจะไม่สามารถกู้คืนได้')) {
  //     showToast('success', 'ลบข้อมูลเรียบร้อยแล้ว (demo)');
  //   }
  // }

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
      const allergyBanner = document.getElementById('allergyBanner');
      const allergyText = document.getElementById('allergyText');

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
      } else {
        foodContent.innerHTML = '<div style="font-size:0.85rem;color:var(--success);font-weight:600;"><i class="bi bi-check-circle me-1"></i>ไม่มีประวัติการแพ้อาหาร</div>';
      }

      const allergyMessages = [];
      if (drugAllergies.length > 0) {
        allergyMessages.push(`มีประวัติการแพ้ยา ${drugAllergies.length} รายการ`);
      }
      if (foodAllergies.length > 0) {
        allergyMessages.push(`มีประวัติการแพ้อาหาร ${foodAllergies.length} รายการ`);
      }

      if (allergyMessages.length > 0) {
        allergyText.textContent = allergyMessages.join(' • ');
        allergyBanner.style.display = 'flex';
      } else {
        allergyText.textContent = '';
        allergyBanner.style.display = 'none';
      }

      const foodCard = document.getElementById('foodAllergyCard');
      if (foodCard) {
        foodCard.classList.toggle('allergy-card-food', foodAllergies.length > 0);
        foodCard.classList.toggle('allergy-card-none', foodAllergies.length === 0);
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

  /* ── Tab Switching - Load health when tab is clicked ── */
  let healthDataLoaded = false;
  const healthTabBtn = document.querySelector('[data-tab="health"]');
  if (healthTabBtn) {
    healthTabBtn.addEventListener('click', function() {
      if (!healthDataLoaded) {
        loadHealthData();
        healthDataLoaded = true;
      }
    });
  }

  /* ── Tab Switching - Load growth when tab is clicked ── */
  let growthDataLoaded = false;
  const growthTabBtn = document.querySelector('[data-tab="growth"]');
  if (growthTabBtn) {
    growthTabBtn.addEventListener('click', function() {
      if (!growthDataLoaded) {
        loadGrowthData();
        growthDataLoaded = true;
      }
    });
  }

  // เปิดแท็บที่ส่งมาจาก Dashboard และโหลดข้อมูลของแท็บนั้นทันที
  const initialTab = <?= json_encode($currentTab) ?>;
  if (initialTab !== 'profile') {
    const initialTabBtn = document.querySelector(`[data-tab="${initialTab}"]`);
    if (initialTabBtn) {
      initialTabBtn.click();
    }
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

  /* ── Load Health Data ── */
  function loadHealthData() {
    const tbody = document.getElementById('healthTableBody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...</td></tr>';

    fetch('../../include/function/get_student_health.php?studentid=' + encodeURIComponent(studentId))
      .then(r => r.json())
      .then(result => {
        if (!result || result.error) {
          throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
        }
        renderHealthTable(result);
      })
      .catch(error => {
        console.error('Error loading health:', error);
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--danger);"><i class="bi bi-exclamation-triangle me-2"></i>' + error.message + '</td></tr>';
      });
  }

  function renderHealthTable(records) {
    const tbody = document.getElementById('healthTableBody');
    if (!records || records.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-info-circle me-2"></i>ไม่มีข้อมูลการตรวจร่างกาย</td></tr>';
      return;
    }

    const bodyPartLabels = {
      hair: 'ผม/ศีรษะ', eye: 'ตา', mouth: 'ช่องปากและคอ', teeth: 'ฟัน',
      ears: 'หู', nose: 'จมูก', nails: 'เล็บมือ', skin: 'ผิวหนัง',
      hands_feet: 'ฝ่ามือและฝ่าเท้า', arms_legs: 'แขนและขา', body: 'ลำตัวและหลัง',
      symptoms: 'อาการผิดปกติ', medicine: 'มียา'
    };
    const normalValues = ['สะอาด', 'ปกติ', 'ไม่มี'];
    const dateFields = ['hair', 'eye', 'mouth', 'teeth', 'ears', 'nose', 'nails', 'skin', 'hands_feet', 'arms_legs', 'body', 'symptoms', 'medicine'];

    let html = '';
    records.forEach(r => {
      const dateStr = r.created_at || '';
      const parts = dateStr.split(' ');
      const datePart = parts[0] || '';
      const timePart = parts[1] || '00:00:00';
      const [y, m, d] = datePart.split('-').map(Number);
      const [h, min] = timePart.split(':').map(Number);
      const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
      const dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
      const dateObj = new Date(y, m - 1, d);
      const thaiYear = y + 543;
      const formattedDate = dayNames[dateObj.getDay()] + ' ' + d + ' ' + monthNames[m - 1] + ' ' + thaiYear + ' ' + String(h).padStart(2, '0') + ':' + String(min).padStart(2, '0') + ' น.';

      // Build summary badges
      const MAX_BADGES = 3;
      let badgeItems = [];
      dateFields.forEach(field => {
        let data;
        try {
          data = typeof r[field] === 'string' ? JSON.parse(r[field]) : r[field];
        } catch (e) {
          return;
        }
        if (!data || !data.checked || data.checked.length === 0) return;

        const label = bodyPartLabels[field] || field;
        const hasIssue = data.checked.some(v => !normalValues.includes(v));
        if (hasIssue) {
          data.checked.forEach(v => {
            if (!normalValues.includes(v)) {
              badgeItems.push('<span class="status-badge" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-exclamation"></i> ' + label + ': ' + v + '</span>');
            }
          });
        } else {
          badgeItems.push('<span class="status-badge" style="background:#dcfce7;color:#15803d;"><i class="bi bi-check"></i> ' + label + ': ปกติ</span>');
        }
      });

      let badgesHtml = '';
      if (badgeItems.length === 0) {
        badgesHtml = '<span style="font-size:0.82rem;color:var(--gray-400);">ไม่มีข้อมูล</span>';
      } else {
        const visible = badgeItems.slice(0, MAX_BADGES);
        const remaining = badgeItems.length - MAX_BADGES;
        badgesHtml = visible.join('');
        if (remaining > 0) {
          badgesHtml += '<span class="status-badge" style="background:#e5e7eb;color:#374151;font-size:0.75rem;cursor:pointer;" title="คลิกดูรายละเอียด">+' + remaining + '</span>';
        }
      }

      const teacher = r.teacher_signature || '-';
      const detailBtn = '<button class="icon-btn icon-btn-view" title="ดูรายละเอียด" onclick="viewHealthDetail(' + r.id + ')"><i class="bi bi-eye"></i></button>';

      html += '<tr>' +
        '<td style="font-size:0.82rem;color:var(--gray-600);">' + formattedDate + '</td>' +
        '<td><div class="d-flex flex-wrap gap-1">' + badgesHtml + '</div></td>' +
        '<td style="font-size:0.85rem;">' + teacher + '</td>' +
        '<td><div class="d-flex gap-1">' + detailBtn + '</div></td>' +
        '</tr>';
    });

    tbody.innerHTML = html;
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

  /* ── View Health Detail ── */
 function renderTags(field, d) {
      if (!d[field] || !d[field].checked) return emptyTag();
      const items = d[field].checked;
      if (!items || items.length === 0) return emptyTag();
      return '<div class="hd-tags">' +
        items.map(function (item) {
          return '<span class="hd-tag"><i class="bi bi-check-circle-fill"></i>' + item + '</span>';
        }).join('') +
        '</div>';
    }

    function emptyTag() {
      return '<div class="hd-tags"><span class="hd-tag hd-tag-empty"><i class="bi bi-dash-circle"></i>ไม่พบข้อมูล</span></div>';
    }

    /**
     * Renders a note row with a label and value.
     * Returns empty string when value is falsy.
     */
    function renderNote(label, value) {
      if (!value) return '';
      return '<div class="hd-note"><i class="bi bi-info-circle-fill"></i><span><strong>' + label + ':</strong> ' + value + '</span></div>';
    }

    /**
     * Builds a single check-item card.
     */
    function renderItem(iconClass, title, tagsHtml, notesHtml) {
      return [
        '<div class="hd-item">',
          '<div class="hd-item-icon"><i class="bi ' + iconClass + '"></i></div>',
          '<div class="hd-item-body">',
            '<div class="hd-item-title">' + title + '</div>',
            tagsHtml,
            notesHtml ? '<div class="hd-notes">' + notesHtml + '</div>' : '',
          '</div>',
        '</div>'
      ].join('');
    }

    /* ─────────────────────────────────────────
       MAIN BUILD FUNCTION
    ───────────────────────────────────────── */
    function buildModal(d) {
      var html = '<div class="hd-container">';

      /* ── Student info card ── */
      html += '<div class="hd-info-card">';
      html += '<div class="hd-info-item"><i class="bi bi-person-badge"></i><span><strong>รหัสนักเรียน:</strong> ' + (d.student_id || '-') + '</span></div>';
      html += '<div class="hd-info-item"><i class="bi bi-person-vcard"></i><span><strong>ชื่อ-นามสกุล:</strong> ' + [(d.prefix_th || ''), (d.first_name_th || ''), (d.last_name_th || '')].join(' ').trim() + '</span></div>';
      html += '<div class="hd-info-item"><i class="bi bi-people"></i><span><strong>กลุ่มเรียน:</strong> ' + (d.child_group || '-') + '</span></div>';
      html += '<div class="hd-info-item"><i class="bi bi-door-open"></i><span><strong>ห้องเรียน:</strong> ' + (d.classroom || '-') + '</span></div>';
      html += '<div class="hd-info-item"><i class="bi bi-calendar-check"></i><span><strong>วันที่ตรวจ:</strong> ' + (d.formatted_date || '-') + '</span></div>';
      html += '<div class="hd-info-item"><i class="bi bi-person-check"></i><span><strong>ครูผู้ตรวจ:</strong> ' + (d.teacher_signature || '-') + '</span></div>';
      html += '</div>';

      /* ── Physical examination section ── */
      html += '<div class="hd-section">';
      html += '<h6 class="hd-section-title"><i class="fa-solid fa-stethoscope"></i> ผลการตรวจร่างกาย</h6>';
      html += '<div class="hd-items-grid">';

      html += renderItem('bi-person-lines-fill', 'ผม / ศีรษะ',
        renderTags('hair', d),
        renderNote('รายละเอียดอื่นๆ', d.hair_reason)
      );

      html += renderItem('bi-eye', 'ตา',
        renderTags('eye', d),
        renderNote('ลักษณะขี้ตา', d.eye_condition) +
        renderNote('รายละเอียดอื่นๆ', d.eye_reason)
      );

      html += renderItem('bi-emoji-smile', 'ปากและคอ',
        renderTags('mouth', d),
        ''
      );

      html += renderItem('bi-emoji-smile', 'ฟัน',
        renderTags('teeth', d),
        renderNote('จำนวนฟันผุ', d.teeth_count)
      );

      html += renderItem('bi-ear', 'หู',
        renderTags('ears', d),
        ''
      );

      html += renderItem('bi-emoji-neutral', 'จมูก',
        renderTags('nose', d),
        renderNote('ลักษณะน้ำมูก', d.nose_condition) +
        renderNote('รายละเอียดอื่นๆ', d.nose_reason)
      );

      html += renderItem('bi-hand-index', 'เล็บ',
        renderTags('nails', d),
        ''
      );

      html += renderItem('bi-bandaid', 'ผิวหนัง',
        renderTags('skin', d),
        renderNote('รายละเอียดแผล', d.skin_wound_detail) +
        renderNote('รายละเอียดผื่น', d.skin_rash_detail)
      );

      html += renderItem('bi-thermometer-half', 'อาการผิดปกติ',
        renderTags('symptoms', d),
        renderNote('อุณหภูมิ', d.fever_temp ? d.fever_temp + ' °C' : '') +
        renderNote('ลักษณะการไอ', d.cough_type) +
        renderNote('รายละเอียดอื่นๆ', d.symptoms_reason)
      );

      html += renderItem('bi-capsule', 'การใช้ยา',
        renderTags('medicine', d),
        renderNote('รายละเอียดยา', d.medicine_detail) +
        renderNote('รายละเอียดอื่นๆ', d.medicine_reason)
      );

      html += '</div></div>'; /* close hd-items-grid + hd-section */

      /* ── Additional notes section (only when data exists) ── */
      var hasExtra = d.illness_reason || d.accident_reason || d.teacher_note;
      if (hasExtra) {
        html += '<div class="hd-section">';
        html += '<h6 class="hd-section-title"><i class="bi bi-journal-text"></i> บันทึกเพิ่มเติม</h6>';
        html += '<div class="hd-items-grid">';

        if (d.illness_reason) {
          html += renderItem('bi-hospital', 'การเจ็บป่วย', '', renderNote('รายละเอียด', d.illness_reason));
        }
        if (d.accident_reason) {
          html += renderItem('bi-bandaid', 'อุบัติเหตุ / แมลงกัดต่อย', '', renderNote('รายละเอียด', d.accident_reason));
        }
        if (d.teacher_note) {
          html += renderItem('bi-pencil-square', 'บันทึกของครู', '', renderNote('บันทึก', d.teacher_note));
        }

        html += '</div></div>';
      }

      /* ── Signature footer ── */
      html += '<div class="hd-footer-sig">';
      html += '<i class="bi bi-person-check-fill"></i>';
      html += '<span><strong>ลงชื่อครูผู้ตรวจ:</strong> ' + (d.teacher_signature || '-') + '</span>';
      html += '</div>';

      html += '</div>'; /* close hd-container */
      return html;
    }

     window.viewHealthDetail = function (id) {
      fetch('../../include/function/get_health_detail.php?id=' + id)
        .then(function (r) { return r.json(); })
        .then(function (result) {
          if (result.status !== 'success') {
            showToast('error', result.message || 'ไม่สามารถโหลดข้อมูลได้');
            return;
          }
          document.getElementById('healthDetailContent').innerHTML = buildModal(result.data);
          new bootstrap.Modal(document.getElementById('healthDetailModal')).show();
        })
        .catch(function (error) {
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

  /* ── Load Growth Data ── */
  let growthCharts = { weight: null, height: null };

  function loadGrowthData() {
    const tbody = document.getElementById('growth-history-body');
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...</td></tr>';
    }

    fetch('../../include/function/get_student_growth_external.php?student_id=' + encodeURIComponent(studentId))
      .then(r => r.json())
      .then(result => {
        if (result.status !== 'success') {
          throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
        }
        renderGrowthData(result);
      })
      .catch(error => {
        console.error('Error loading growth:', error);
        const tbody2 = document.getElementById('growth-history-body');
        if (tbody2) {
          tbody2.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--danger);"><i class="bi bi-exclamation-triangle me-2"></i>' + error.message + '</td></tr>';
        }
        const devBody = document.getElementById('growth-dev-body');
        if (devBody) {
          devBody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:1rem;color:var(--danger);"><i class="bi bi-exclamation-triangle me-2"></i>' + error.message + '</td></tr>';
        }
      });
  }

  function renderGrowthData(result) {
    const current = result.current_record;
    const records = result.all_records || [];

    const studentSex = current ? (current.sex === 'ชาย' ? 'M' : 'F') : 'M';
    renderGrowthStats(current);
    renderGrowthDev(current);
    renderGrowthHistory(records);
    renderGrowthCharts(records, studentSex);
  }

  function renderGrowthStats(record) {
    if (!record) {
      ['weight', 'height', 'head', 'bmi'].forEach(id => {
        const el = document.getElementById('growth-' + id + '-val');
        if (el) el.textContent = '-';
        const st = document.getElementById('growth-' + id + '-status');
        if (st) { st.textContent = 'ไม่มีข้อมูล'; st.style.background = '#f3f4f6'; st.style.color = '#9ca3af'; }
      });
      return;
    }

    const measures = record.physical_measures || {};

    // Water weight
    const weight = measures.weight || '-';
    setGrowthStat('weight', weight + '', 'กก.', measures.weight_for_age || []);

    // Height
    const height = measures.height || '-';
    setGrowthStat('height', height + '', 'ซม.', measures.height_for_age || []);

    // Head circumference
    const headCirc = measures.head_circ || '-';
    setGrowthStat('head', headCirc + '', 'ซม.', measures.head_percentile || []);

    // BMI
    const bmi = record.bmi || '-';
    const bmiStatus = measures.weight_for_height || [];
    setGrowthStat('bmi', bmi + '', 'BMI', bmiStatus);
  }

  function setGrowthStat(id, value, unit, statusArr) {
    const valEl = document.getElementById('growth-' + id + '-val');
    const statusEl = document.getElementById('growth-' + id + '-status');
    if (valEl) valEl.textContent = value;
    if (statusEl) {
      if (statusArr && statusArr.length > 0) {
        const label = statusArr.join(', ');
        statusEl.textContent = label;
        const isGood = label.includes('สมส่วน') || label.includes('ตามเกณฑ์') || label.includes('ปกติ');
        statusEl.style.background = isGood ? '#dcfce7' : '#fef3c7';
        statusEl.style.color = isGood ? '#15803d' : '#d97706';
      } else {
        statusEl.textContent = 'ไม่มีข้อมูล';
        statusEl.style.background = '#f3f4f6';
        statusEl.style.color = '#9ca3af';
      }
    }
  }

  function renderGrowthDev(record) {
    const tbody = document.getElementById('growth-dev-body');
    if (!tbody) return;

    if (!record || !record.development_assessment) {
      tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-info-circle me-2"></i>ไม่มีข้อมูลการประเมินพัฒนาการ</td></tr>';
      return;
    }

    const dev = record.development_assessment;
    const devItems = [
      { code: 'gm', label: 'การเคลื่อนไหว (GM)', icon: 'bi-person-walking', color: 'var(--primary)' },
      { code: 'fm', label: 'กล้ามเนื้อมัดเล็กและสติปัญญา (FM)', icon: 'bi-hand-index', color: 'var(--info)' },
      { code: 'rl', label: 'การเข้าใจภาษา (RL)', icon: 'bi-ear', color: 'var(--success)' },
      { code: 'el', label: 'การใช้ภาษา (EL)', icon: 'bi-chat-dots', color: 'var(--warning)' },
      { code: 'ps', label: 'การช่วยเหลือตัวเองและสังคม (PS)', icon: 'bi-people', color: 'var(--danger)' }
    ];

    let html = '';
    devItems.forEach(item => {
      const data = dev[item.code] || {};
      const status = data.status || '';
      const score = data.score || '';
      let statusHtml = '';
      let noteHtml = '-';

      if (status === 'pass') {
        statusHtml = '<span class="dev-pass"><i class="bi bi-check-circle-fill me-1"></i>ผ่าน</span>';
        if (score) noteHtml = 'ข้อที่ ' + score;
      } else if (status === 'fail') {
        statusHtml = '<span class="dev-delay"><i class="bi bi-exclamation-triangle-fill me-1"></i>สงสัยล่าช้า</span>';
        if (score) noteHtml = 'ข้อที่ ' + score;
      } else {
        statusHtml = '<span style="color:var(--gray-400);">-</span>';
      }

      html += '<tr>' +
        '<td><i class="bi ' + item.icon + ' me-2" style="color:' + item.color + ';"></i>' + item.label + '</td>' +
        '<td>' + statusHtml + '</td>' +
        '<td style="font-size:0.78rem;color:var(--gray-400);">' + noteHtml + '</td>' +
      '</tr>';
    });
    tbody.innerHTML = html;
  }

  function renderGrowthHistory(records) {
    const tbody = document.getElementById('growth-history-body');
    if (!tbody) return;

    if (!records || records.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--gray-400);"><i class="bi bi-info-circle me-2"></i>ไม่มีประวัติการบันทึกการเจริญเติบโต</td></tr>';
      return;
    }

    let html = '';
    records.forEach((r, idx) => {
      const measures = r.physical_measures || {};
      const examDate = r.exam_date || '';
      const dateStr = formatThaiDateShort(examDate);
      const ageStr = formatAge(r.age_year, r.age_month, r.age_day);
      const weight = measures.weight || '-';
      const height = measures.height || '-';
      const headCirc = measures.head_circ || '-';
      const bmi = r.bmi || '-';

      // Build status labels
      let statusParts = [];
      if (measures.weight_for_age && measures.weight_for_age.length > 0)
        statusParts.push('นน.' + measures.weight_for_age.join('/'));
      if (measures.height_for_age && measures.height_for_age.length > 0)
        statusParts.push('สส.' + measures.height_for_age.join('/'));
      if (measures.weight_for_height && measures.weight_for_height.length > 0)
        statusParts.push('นน/สส.' + measures.weight_for_height.join('/'));
      const statusHtml = statusParts.length > 0
        ? statusParts.join('<br>')
        : '<span style="color:var(--gray-400);">-</span>';

      html += '<tr>' +
        '<td style="font-size:0.82rem;color:var(--gray-600);">' + dateStr + '</td>' +
        '<td style="font-size:0.82rem;">' + ageStr + '</td>' +
        '<td><strong>' + weight + '</strong> <span style="font-size:0.75rem;color:var(--gray-400);">กก.</span></td>' +
        '<td><strong>' + height + '</strong> <span style="font-size:0.75rem;color:var(--gray-400);">ซม.</span></td>' +
        '<td><strong>' + headCirc + '</strong> <span style="font-size:0.75rem;color:var(--gray-400);">ซม.</span></td>' +
        '<td><strong>' + bmi + '</strong></td>' +
        '<td>' +
          '<button class="btn-action btn-save" style="padding:4px 10px;font-size:0.75rem;" onclick="showGrowthDetailModal(' + idx + ')">' +
            '<i class="bi bi-bar-chart"></i><span>ดูผล</span>' +
          '</button>' +
        '</td>' +
      '</tr>';
    });
    tbody.innerHTML = html;

    // Store records data for modal access
    window._growthRecords = records;
  }

  function renderGrowthCharts(records, sex) {
    if (!records || records.length < 1) return;

    // Destroy existing charts
    Object.values(growthCharts).forEach(c => { if (c) { c.destroy(); } });
    growthCharts = { weight: null, height: null, bmi: null };

    // Build child data series (age in months)
    const childDataWeight = records
      .filter(r => r.physical_measures && r.physical_measures.weight)
      .map(r => ({
        x: (r.age_year || 0) * 12 + (r.age_month || 0),
        y: parseFloat(r.physical_measures.weight)
      }));

    const childDataHeight = records
      .filter(r => r.physical_measures && r.physical_measures.height)
      .map(r => ({
        x: (r.age_year || 0) * 12 + (r.age_month || 0),
        y: parseFloat(r.physical_measures.height)
      }));

    const childDataBMI = records
      .filter(r => r.bmi)
      .map(r => ({
        x: (r.age_year || 0) * 12 + (r.age_month || 0),
        y: parseFloat(r.bmi)
      }));

    // Fetch reference data and build charts
    fetchRefData('weight', sex, chartData => buildChart('chartWeight', 'น้ำหนักตามเกณฑ์อายุ', 'น้ำหนัก (กก.)', chartData, childDataWeight, '#3b82f6', 2.5, 35));
    fetchRefData('height', sex, chartData => buildChart('chartHeight', 'ส่วนสูงตามเกณฑ์อายุ', 'ส่วนสูง (ซม.)', chartData, childDataHeight, '#22c55e', 40, 130));
    fetchRefData('bmi', sex, chartData => buildChart('chartBMI', 'BMI ตามเกณฑ์อายุ', 'BMI (กก./ม.²)', chartData, childDataBMI, '#f59e0b', 10, 25));
  }

  function fetchRefData(indicator, sex, callback) {
    fetch('../../include/function/get_growth_reference.php?indicator=' + indicator + '&sex=' + sex)
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          callback(result);
        }
      })
      .catch(error => console.error('Failed to load growth reference data (' + indicator + '):', error));
  }

  function buildChart(canvasId, title, yLabel, refData, childPoints, childColor, yMin, yMax) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    // Build reference datasets
    const refDatasets = [
      {
        label: '+2 SD',
        data: refData.ages.map((age, i) => ({ x: age, y: refData.plus2sd[i] })),
        borderColor: 'rgba(239,68,68,0.6)',
        borderDash: [6, 4],
        borderWidth: 1.5,
        pointRadius: 0,
        fill: '+1',
        backgroundColor: 'rgba(239,68,68,0.05)'
      },
      {
        label: '+1 SD',
        data: refData.ages.map((age, i) => ({ x: age, y: refData.plus1sd[i] })),
        borderColor: 'rgba(249,115,22,0.6)',
        borderDash: [6, 4],
        borderWidth: 1.5,
        pointRadius: 0,
        fill: '+1',
        backgroundColor: 'rgba(249,115,22,0.05)'
      },
      {
        label: 'Median',
        data: refData.ages.map((age, i) => ({ x: age, y: refData.median[i] })),
        borderColor: 'rgba(34,197,94,0.7)',
        borderDash: [6, 4],
        borderWidth: 1.5,
        pointRadius: 0,
        fill: '+1',
        backgroundColor: 'rgba(34,197,94,0.08)'
      },
      {
        label: '-1 SD',
        data: refData.ages.map((age, i) => ({ x: age, y: refData.minus1sd[i] })),
        borderColor: 'rgba(249,115,22,0.6)',
        borderDash: [6, 4],
        borderWidth: 1.5,
        pointRadius: 0,
        fill: '+1',
        backgroundColor: 'rgba(249,115,22,0.05)'
      },
      {
        label: '-2 SD',
        data: refData.ages.map((age, i) => ({ x: age, y: refData.minus2sd[i] })),
        borderColor: 'rgba(239,68,68,0.6)',
        borderDash: [6, 4],
        borderWidth: 1.5,
        pointRadius: 0,
        fill: false,
        backgroundColor: 'rgba(239,68,68,0.05)'
      },
      // Child data
      {
        label: 'ข้อมูลของเด็ก',
        data: childPoints,
        borderColor: childColor,
        backgroundColor: childColor,
        borderWidth: 2.5,
        pointRadius: 5,
        pointHoverRadius: 8,
        pointBackgroundColor: childColor,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        fill: false,
        tension: 0.3,
        order: 1
      }
    ];

    growthCharts[canvasId.replace('chart', '').toLowerCase()] = new Chart(ctx, {
      type: 'line',
      data: { datasets: refDatasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'nearest' },
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 14, padding: 12, font: { size: 11 } }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + (canvasId === 'chartBMI' ? '' : '');
              }
            }
          }
        },
        scales: {
          x: {
            type: 'linear',
            title: { display: true, text: 'อายุ (เดือน)' },
            min: 0,
            max: 60,
            ticks: { stepSize: 6 }
          },
          y: {
            title: { display: true, text: yLabel },
            min: yMin,
            max: yMax
          }
        }
      }
    });
  }

  function formatThaiDateShort(dateStr) {
    if (!dateStr) return '-';
    const monthsThai = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const [y, m, d] = dateStr.split('-').map(Number);
    return parseInt(d) + ' ' + monthsThai[m - 1] + ' ' + (y + 543);
  }

  function formatThaiDateLong(dateStr) {
    if (!dateStr) return '-';
    const monthsThai = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const [y, m, d] = dateStr.split('-').map(Number);
    return parseInt(d) + ' ' + monthsThai[m - 1] + ' ' + (y + 543);
  }

  function formatAge(year, month, day) {
    let parts = [];
    if (year) parts.push(year + ' ปี');
    if (month) parts.push(month + ' เดือน');
    if (day) parts.push(day + ' วัน');
    return parts.length > 0 ? parts.join(' ') : '-';
  }

  /* ── Growth Detail Modal ── */
  window.showGrowthDetailModal = function (index) {
    const records = window._growthRecords || [];
    const record = records[index];
    if (!record) {
      document.getElementById('growthDetailContent').innerHTML = '<div style="text-align:center;padding:2rem;color:var(--gray-400);">ไม่พบข้อมูล</div>';
      new bootstrap.Modal(document.getElementById('growthDetailModal')).show();
      return;
    }

    const measures = record.physical_measures || {};
    const dev = record.development_assessment || {};
    const examDate = record.exam_date || '';
    const ageStr = formatAge(record.age_year, record.age_month, record.age_day);

    const weight = measures.weight || '-';
    const height = measures.height || '-';
    const headCirc = measures.head_circ || '-';
    const bmi = record.bmi || '-';

    function statusBadge(arr) {
      if (!arr || arr.length === 0) return '<span style="color:var(--gray-400);">-</span>';
      const label = arr.join(', ');
      const isGood = label.includes('สมส่วน') || label.includes('ตามเกณฑ์') || label.includes('ปกติ');
      return '<span style="color:' + (isGood ? 'var(--success)' : 'var(--warning)') + ';font-weight:700;">' + label + '</span>';
    }

    function devStatusHtml(status) {
      if (status === 'pass') return '<span style="color:var(--success);font-weight:700;">ผ่าน</span>';
      if (status === 'fail') return '<span style="color:var(--danger);font-weight:700;">สงสัยล่าช้า</span>';
      return '<span style="color:var(--gray-400);">-</span>';
    }

    const content = `
      <div class="row g-3">
        <div class="col-md-6">
          <div style="background:var(--gray-50);border-radius:var(--radius-md);padding:1rem;">
            <div style="font-size:0.8rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;margin-bottom:0.75rem;">ข้อมูลการวัด</div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-calendar3"></i></div>
              <div class="info-row-label">วันที่ตรวจ</div>
              <div class="info-row-value">${formatThaiDateLong(examDate)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-clock"></i></div>
              <div class="info-row-label">อายุ</div>
              <div class="info-row-value">${ageStr}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-arrow-up-circle"></i></div>
              <div class="info-row-label">น้ำหนัก</div>
              <div class="info-row-value">${weight} กก.</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-arrow-up"></i></div>
              <div class="info-row-label">ส่วนสูง</div>
              <div class="info-row-value">${height} ซม.</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-activity"></i></div>
              <div class="info-row-label">เส้นรอบศีรษะ</div>
              <div class="info-row-value">${headCirc} ซม.</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-calculator"></i></div>
              <div class="info-row-label">BMI</div>
              <div class="info-row-value">${bmi}</div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div style="background:var(--gray-50);border-radius:var(--radius-md);padding:1rem;">
            <div style="font-size:0.8rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;margin-bottom:0.75rem;">ผลการประเมิน</div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-clipboard-check"></i></div>
              <div class="info-row-label">น้ำหนักตามอายุ</div>
              <div class="info-row-value">${statusBadge(measures.weight_for_age)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-clipboard-check"></i></div>
              <div class="info-row-label">ส่วนสูงตามอายุ</div>
              <div class="info-row-value">${statusBadge(measures.height_for_age)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-clipboard-check"></i></div>
              <div class="info-row-label">น้ำหนักตามส่วนสูง</div>
              <div class="info-row-value">${statusBadge(measures.weight_for_height)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-clipboard-check"></i></div>
              <div class="info-row-label">เส้นรอบศีรษะ</div>
              <div class="info-row-value">${statusBadge(measures.head_percentile)}</div>
            </div>
            <hr style="margin:0.75rem 0;">
            <div style="font-size:0.8rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;margin-bottom:0.5rem;">พัฒนาการ</div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-person-walking"></i></div>
              <div class="info-row-label">GM</div>
              <div class="info-row-value">${devStatusHtml((dev.gm || {}).status)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-hand-index"></i></div>
              <div class="info-row-label">FM</div>
              <div class="info-row-value">${devStatusHtml((dev.fm || {}).status)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-ear"></i></div>
              <div class="info-row-label">RL</div>
              <div class="info-row-value">${devStatusHtml((dev.rl || {}).status)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-chat-dots"></i></div>
              <div class="info-row-label">EL</div>
              <div class="info-row-value">${devStatusHtml((dev.el || {}).status)}</div>
            </div>
            <div class="info-row">
              <div class="info-row-icon"><i class="bi bi-people"></i></div>
              <div class="info-row-label">PS</div>
              <div class="info-row-value">${devStatusHtml((dev.ps || {}).status)}</div>
            </div>
          </div>
        </div>
      </div>
      ${record.recommendation ? '<div class="row g-3 mt-2"><div class="col-12"><div style="background:#fef3c7;border-radius:var(--radius-md);padding:0.75rem 1rem;"><strong>คำแนะนำ:</strong> ' + record.recommendation + '</div></div></div>' : ''}
    `;

    document.getElementById('growthDetailContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('growthDetailModal')).show();
  };

  /* ── Initialize ── */
  loadAllergiesData();

})();
</script>
</body>
</html>
