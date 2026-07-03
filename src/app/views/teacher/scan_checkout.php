<?php include __DIR__ .'/../../include/auth/auth.php'; ?>
<?php checkUserRole(['admin', 'teacher']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ .'/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include//function/pages_referen.php'; ?>
<?php require_once __DIR__. '/../../include/function/child_functions.php'; ?>
<?php include __DIR__ .'/../../include/auth/auth_dashboard.php'; ?>
<?php
$children = getChildrenData();

// รับค่า tab และ room จาก URL
$currentTab = $_GET['tab'] ?? 'all';  // ใช้ค่าเริ่มต้น 'all' หากไม่ได้รับค่า
// ดึงข้อมูลจากฟังก์ชัน
$data = getChildrenGroupedByTab($currentTab);
?>
<style>
/* Main Layout */
.main-content {
    background-color: #f8f9fa;
}

/* Scanner Section */
#scanner-container {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);

        padding: 1rem;
    }

    #video-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 600px;
        min-height: 350px;
        /* กำหนดความสูงขั้นต่ำ */
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    #reader {
        display: flex;
        width: 100% !important;
        height: auto !important;
        min-height: 300px !important;
        border: none !important;
    }

    #reader video {
        border-radius: 10px;
        object-fit: cover;
    }

    #reader img {
        border-radius: 10px;
    }

    #reader__scan_region {
        display: flex;
        justify-content: center;
        align-items: center;
        background: #000;
        border-radius: 10px;
        min-height: 300px;
    }

    /* Zoom Controls */
    .zoom-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .zoom-controls button {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 50%;
        background: #4a90e2;
        color: white;
        font-size: 1.2rem;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        line-height: 1;
    }

    .zoom-controls button:hover {
        background: #357abd;
        transform: scale(1.1);
    }

    .zoom-controls button:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    .zoom-controls input[type="range"] {
        width: 120px;
        height: 4px;
        -webkit-appearance: none;
        appearance: none;
        background: #ddd;
        border-radius: 2px;
        outline: none;
    }

    .zoom-controls input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #4a90e2;
        cursor: pointer;
        border: none;
    }

    .zoom-controls input[type="range"]::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #4a90e2;
        cursor: pointer;
        border: none;
    }

    .zoom-value {
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
        min-width: 35px;
        text-align: center;
    }

/* Back Button */
.back-button {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background: #6c757d;
    color: white;
    border-radius: 25px;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    border: none;
    cursor: pointer;
}

.back-button:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.back-button .nav-link {
    color: white !important;
    text-decoration: none;
}

/* Page Title */
h1 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 2rem;
    text-align: center;
}

/* Tables */
.table-container {
    flex-grow: 1;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

table th {
    background-color: #4a90e2;
    color: white;
    padding: 1rem;
    font-weight: 500;
    text-align: center;
}

table td {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    text-align: center;
}

tbody tr:hover {
    background-color: #f8f9fa;
}

/* Student List Section */
.student-section {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    margin-top: 2rem;
}

.section-title {
    color: #2c3e50;
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #4a90e2;
}

/* Group Styles */
.group-section {
    margin-bottom: 2rem;
}

.group-header {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.group-header h3 {
    color: #2c3e50;
    margin: 0;
    font-size: 1.4rem;
}

/* Classroom Styles */
.classroom-section {
    margin-bottom: 2rem;
    padding: 0 1rem;
}

.classroom-title {
    color: #4a90e2;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

/* Badge Styles */
.badge {
    padding: 0.5em 1em;
    font-weight: 500;
    font-size: 0.85em;
    border-radius: 20px;
}

/* Tab Styles */
.nav-tabs {
    border: none;
    margin-bottom: 1.5rem;
    gap: 0.5rem;
    display: flex;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-tabs .nav-link i {
    font-size: 1.1em;
}

.nav-tabs .nav-link:hover {
    background: #e9ecef;
    color: #495057;
}

.nav-tabs .nav-link.active {
    background: #4a90e2;
    color: white;
}

/* Toast */
.toast {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.toast-header {
    background: #4a90e2;
    color: white;
    border-radius: 10px 10px 0 0;
}

 /* Responsive Design */
    @media (max-width: 480px) {
        #scanner-container {
            flex-direction: column;
            gap: 15px;
            padding: 0.5rem;
        }

        #video-container {
            width: 100%;
            min-height: 300px;
        }

        #reader {
            min-height: 300px !important;
        }
    }

    @media (min-width: 481px) and (max-width: 768px) {
        #scanner-container {
            flex-direction: column;
            gap: 18px;
        }

        #video-container {
            width: 100%;
            height: 180px;
        }

        #reader {
            min-height: 350px !important;
        }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
        #scanner-container {
            flex-direction: column;
            gap: 18px;
        }
        #video-container {
            width: 100%;
            max-width: 550px;
            height: 180px;
        }

        #reader {
            min-height: 380px !important;
        }
    }

    @media (min-width: 1025px) {
        #video-container {
            width: 100%;
            max-width: 600px;
            min-height: 400px;
        }

        #reader {
            min-height: 400px !important;
        }
    }

    @media (min-width: 1440px) {
        #video-container {
            width: 100%;
            max-width: 600px;
            min-height: 400px;
        }

        #reader {
            min-height: 400px !important;
        }
    }
    .selected-guardian {
  border: 3px solid #007bff !important;  /* Bootstrap primary */
  box-shadow: 0 0 10px rgba(0,123,255,0.2);
  transition: border 0.2s, box-shadow 0.2s;
}
.form-check-input[type="radio"] {
  display: none;     /* ซ่อนปุ่ม radio */
}
.manual-attendance-form {
        width: 100%;
        max-width: 600px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
    }

    .manual-attendance-form .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .manual-attendance-form .input-group {
        gap: 0.5rem;
    }

    .manual-attendance-form input {
        border-radius: 10px !important;
    }

    .manual-attendance-form button {
        border-radius: 10px !important;
        white-space: nowrap;
    }

    /* ===== Guardian Modal - Navy Blue Theme ===== */
  #guardianModal .modal-content {
    border: none;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(10, 30, 80, 0.2);
  }

  #guardianModal .modal-header {
    background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
    border: none;
    padding: 1.5rem 2rem;
    position: relative;
    overflow: hidden;
  }

  #guardianModal .modal-header::before {
    content: "";
    position: absolute;
    top: -40px;
    right: -40px;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    pointer-events: none;
  }

  #guardianModal .modal-header::after {
    content: "";
    position: absolute;
    bottom: -50px;
    right: 60px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.04);
    pointer-events: none;
  }

  #guardianModal .modal-title {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.15rem;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
  }

  #guardianModal .modal-title .title-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #fff;
  }

  #guardianModal .modal-header .header-subtitle {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    font-weight: 400;
    margin-top: 2px;
  }

  #guardianModal .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.6;
    position: relative;
    z-index: 1;
    transition: opacity 0.2s;
  }

  #guardianModal .btn-close:hover {
    opacity: 1;
  }

  /* ===== Modal Body ===== */
  #guardianModal .modal-body {
    background: #f0f4f8;
    padding: 1.75rem;
  }

  /* ===== Student Info Card ===== */
  .guardian-student-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.1rem 1.4rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 16px rgba(15, 36, 96, 0.1);
    border-left: 5px solid #1e4db7;
  }

  .guardian-student-card .avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.3rem;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 6px 16px rgba(30, 77, 183, 0.35);
  }

  .guardian-student-card .info h4 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 700;
    color: #0f2460;
  }

  .guardian-student-card .info small {
    color: #64748b;
    font-size: 0.78rem;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 5px;
    flex-wrap: wrap;
  }

  .guardian-student-card .info .badge-pill {
    background: #eff3ff;
    color: #1e4db7;
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 0.74rem;
    font-weight: 600;
    border: 1px solid #c7d7f8;
  }

  /* ===== Section Label ===== */
  .section-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: #94a3b8;
    margin-bottom: 0.65rem;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .section-label i {
    color: #1e4db7;
    font-size: 0.9rem;
  }

  /* ===== Form Card ===== */
  .form-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.2rem 1.4rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(15, 36, 96, 0.07);
  }

  /* ===== Guardian Cards ===== */
  .guardian-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
  }

  .guardian-select-card {
    position: relative;
    cursor: pointer;
    margin: 0;
  }

  .guardian-select-card input[type="radio"] {
    display: none;
  }

  .guardian-card-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
    background: #f8faff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem 0.5rem 0.75rem;
    transition: all 0.25s ease;
    user-select: none;
    text-align: center;
  }

  .guardian-card-inner .g-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e2e8f0;
    transition: all 0.25s ease;
  }

  .guardian-card-inner .g-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    transition: color 0.25s ease;
    line-height: 1.3;
  }

  .guardian-card-inner .g-name {
    font-size: 0.72rem;
    color: #94a3b8;
    font-weight: 400;
    margin-top: -4px;
  }

  .guardian-card-inner .check-mark {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
    flex-shrink: 0;
    margin-top: 2px;
  }

  .guardian-card-inner .check-mark i {
    font-size: 0.68rem;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  /* Checked State */
  .guardian-select-card input:checked + .guardian-card-inner {
    border-color: #1e4db7;
    background: #eff3ff;
    box-shadow: 0 4px 14px rgba(30, 77, 183, 0.15);
  }

  .guardian-select-card input:checked + .guardian-card-inner .g-avatar {
    border-color: #1e4db7;
    box-shadow: 0 4px 12px rgba(30, 77, 183, 0.25);
  }

  .guardian-select-card input:checked + .guardian-card-inner .g-label {
    color: #0f2460;
    font-weight: 700;
  }

  .guardian-select-card input:checked + .guardian-card-inner .check-mark {
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    border-color: transparent;
  }

  .guardian-select-card input:checked + .guardian-card-inner .check-mark i {
    opacity: 1;
  }

  .guardian-card-inner:hover {
    border-color: #1e4db750;
    background: #f0f5ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 77, 183, 0.1);
  }

  /* ===== Other Option Card ===== */
  .other-card-inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #f8faff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.8rem 1.1rem;
    transition: all 0.25s ease;
    cursor: pointer;
    user-select: none;
  }

  .other-card-inner .other-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #eff3ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #1e4db7;
    flex-shrink: 0;
    transition: all 0.25s ease;
  }

  .other-card-inner .other-text {
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    transition: color 0.25s ease;
  }

  .other-card-inner .check-mark {
    margin-left: auto;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
    flex-shrink: 0;
  }

  .other-card-inner .check-mark i {
    font-size: 0.68rem;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  .guardian-select-card input:checked + .other-card-inner {
    border-color: #1e4db7;
    background: #eff3ff;
    box-shadow: 0 4px 14px rgba(30, 77, 183, 0.15);
  }

  .guardian-select-card input:checked + .other-card-inner .other-icon {
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    color: #fff;
  }

  .guardian-select-card input:checked + .other-card-inner .other-text {
    color: #0f2460;
  }

  .guardian-select-card input:checked + .other-card-inner .check-mark {
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    border-color: transparent;
  }

  .guardian-select-card input:checked + .other-card-inner .check-mark i {
    opacity: 1;
  }

  /* ===== Other Details Textarea ===== */
  #otherGuardianDetails {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    padding: 0.72rem 1rem;
    font-size: 0.88rem;
    color: #334155;
    resize: none;
    min-height: 78px;
    transition: all 0.3s ease;
    background: #f8faff;
    box-shadow: none;
  }

  #otherGuardianDetails:focus {
    border-color: #1e4db7;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
    outline: none;
  }

  #otherGuardianDetails::placeholder {
    color: #c4cdd9;
  }

  /* ===== Modal Footer ===== */
  #guardianModal .modal-footer {
    background: #f0f4f8;
    border-top: 1px solid #e2e8f0;
    padding: 1rem 1.75rem;
    gap: 0.65rem;
  }

  #guardianModal .btn-close-custom {
    border-radius: 12px;
    padding: 0.58rem 1.3rem;
    font-size: 0.88rem;
    font-weight: 600;
    border: 2px solid #e2e8f0;
    color: #64748b;
    background: #ffffff;
    transition: all 0.25s ease;
  }

  #guardianModal .btn-close-custom:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
  }

  #guardianModal .btn-save-custom {
    border-radius: 12px;
    padding: 0.58rem 1.5rem;
    font-size: 0.88rem;
    font-weight: 700;
    border: none;
    background: linear-gradient(135deg, #0f2460 0%, #1e4db7 100%);
    color: #ffffff;
    transition: all 0.25s ease;
    box-shadow: 0 4px 16px rgba(15, 36, 96, 0.35);
  }

  #guardianModal .btn-save-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15, 36, 96, 0.45);
  }

  #guardianModal .btn-save-custom:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(15, 36, 96, 0.3);
  }
</style>

<body>
    <main class="main-content">
        <div class="container-fluid mt-4">

           
            <div id="scanner-container"> 
                <h3 class="text-center">บันทึกการเช็คชื่อกลับบ้าน วันที่ <?php echo date('d/m/Y'); ?></h3>

                 <div class="manual-attendance-form">
                    <label for="manualStudentId" class="form-label">
                        <i class="bi bi-keyboard"></i> กรอกเลขประจำตัวนักเรียนกรณีไม่มี QR Code
                    </label>
                    <div class="input-group">
                        <input type="text" id="manualStudentId" class="form-control" placeholder="กรอกเลขประจำตัวนักเรียน">
                        <button type="button" class="btn btn-primary" id="manualAttendanceBtn">
                            <i class="bi bi-check2-circle"></i> บันทึก
                        </button>
                    </div>
                </div>

                <script>
                    // Function to check existing checkout record
                    function checkExistingCheckout(studentId) {
                        // The checkout-check script resides in include/attendance relative to this view
                        return fetch(`../../include/attendance/checkout-check.php?student_id=${studentId}`)
                            .then(res => {
                                // Some endpoints may return empty body; handle gracefully
                                if (!res.ok) return {};
                                return res.text().then(text => {
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        // Not valid JSON – treat as empty result
                                        return {};
                                    }
                                });
                            })
                            .then(data => {
                                if (data && data.status === 'success') {
                                    Swal.fire({
                                        icon: 'info',
                                        title: 'ข้อมูลการกลับบ้าน',
                                        html: `นักเรียนนี้ได้บันทึกการกลับบ้านแล้ว เวลา: <b>${data.time}</b>`
                                    });
                                }
                            })
                            .catch(err => console.error('Error checking checkout:', err));
                    }

                    document.getElementById('manualAttendanceBtn').addEventListener('click', async function() {
                        const studentId = document.getElementById('manualStudentId').value.trim();
                        if (!studentId) {
                            Swal.fire({icon: 'warning', title: 'กรุณากรอกเลขประจำตัว'});
                            return;
                        }
                        // Check existing checkout first
                        await checkExistingCheckout(studentId);
                        // Fetch guardian data
                        try {
                            const res = await fetch(`../../include/attendance/get_student_guardians.php?student_id=${studentId}`);
                            const result = await res.json();
                            if (result.status === 'error') throw new Error(result.message || 'ไม่พบข้อมูลนักเรียน');
                            const guardians = result.data;
                            const hasGuardian = guardians.father_first_name || guardians.mother_first_name || guardians.relative_first_name;
                            if (!hasGuardian) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'ไม่พบข้อมูลผู้ปกครอง',
                                    text: 'นักเรียนคนนี้ไม่มีข้อมูลผู้ปกครองในระบบ กรุณาติดต่อผู้ดูแล',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                                return;
                            }
                            // Prepare minimal student data for modal
                            const studentData = result;
                            showPickupModal(guardians, studentData.data);
                        } catch (e) {
                            Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message});
                        }
                    });
                </script>


                <div id="video-container">
                    <div id="reader"></div>
                </div>

                <!-- ปุ่มซูมกล้อง -->
                <div class="zoom-controls" id="zoomControls">
                    <span id="zoomStatusMsg" style="font-size:0.85rem;color:#6c757d;">
                        <i class="bi bi-search"></i> กำลังตรวจสอบกล้อง...
                    </span>
                    <div id="zoomInnerControls" style="display:none; justify-content:center; align-items:center;">
                        <button type="button" id="zoomOutBtn" title="ซูมออก" disabled>−</button>
                        <input type="range" id="zoomSlider" min="1" max="5" step="0.1" value="1">
                        <button type="button" id="zoomInBtn" title="ซูมเข้า">+</button>
                        <span class="zoom-value" id="zoomValueDisplay">1.0x</span>
                    </div>
                </div>

                <div class="table-responsive" style="width:100% ; max-height: 400px; overflow: scroll; ">
                    <table class="table table-striped" >
                        <thead>
                            <tr class="table-primary">
                                <th>ลำดับ</th>
                                <th>รหัสนักเรียน</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ห้องเรียน</th>
                                <th>เวลา</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table-body">
                            <!-- ข้อมูลจะถูกเพิ่มด้วย JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Toast Notification -->
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div id="scanToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto">การแสกนสำเร็จ</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        การเช็คชื่อกลับได้ถูกบันทึกสำเร็จ
                    </div>
                </div>
            </div>

            <div class="student-section">
                <h2 class="section-title">รายชื่อเด็กในระบบ</h2>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'all' ? 'active' : '' ?>" href="?tab=all">
                            <i class="bi bi-people-fill"></i> ทั้งหมด
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'big' ? 'active' : '' ?>" href="?tab=big">
                            <i class="bi bi-person-check-fill"></i> เด็กโต
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'medium' ? 'active' : '' ?>" href="?tab=medium">
                            <i class="bi bi-person"></i> เด็กกลาง
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'prep' ? 'active' : '' ?>" href="?tab=prep">
                            <i class="bi bi-person-heart"></i> เตรียมอนุบาล
                        </a>
                    </li>
                </ul>

            <!-- Student Groups -->
            <?php foreach ($data as $groupData): ?>
                <div class="group-section">
                    <div class="group-header">
                        <h3><i class="bi bi-bookmark-star-fill"></i> <?= htmlspecialchars($groupData['group']) ?></h3>
                    </div>
                    <?php foreach ($groupData['classrooms'] as $classroomData): ?>
                        <div class="classroom-section">
                            <h4 class="classroom-title">
                                <i class="bi bi-door-open-fill"></i> ห้อง: <?= htmlspecialchars($classroomData['classroom']) ?>
                            </h4>
                            <div class="table-responsive">
                                <table class="student-table">
                                    <thead>
                                        <tr>
                                            <th>รหัสประจำตัว</th>
                                            <th>ชื่อ</th>
                                            <th>นามสกุล</th>
                                            <th>กลุ่ม</th>
                                            <th>ห้องเรียน</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($classroomData['children'])): ?>
                                            <?php foreach ($classroomData['children'] as $child): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($child['studentid']) ?></td>
                                                    <td><?= htmlspecialchars($child['prefix_th']) ?> <?= htmlspecialchars($child['firstname_th']) ?></td>
                                                    <td><?= htmlspecialchars($child['lastname_th']) ?></td>
                                                    <td><span class="badge bg-info"><?= htmlspecialchars($child['child_group']) ?></span></td>
                                                    <td><span class="badge bg-primary"><?= htmlspecialchars($child['classroom']) ?></span></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = $child['status_checkout'] === 'กลับบ้านแล้ว' ? 'bg-success' : 'bg-warning';
                                                        ?>
                                                        <span class="badge <?= $statusClass ?>">
                                                            <?= htmlspecialchars($child['status_checkout']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    <i class="bi bi-inbox"></i> ไม่มีข้อมูลในห้องนี้
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>   
    </main>
    <!-- Guardian Modal -->
    <div class="modal fade" id="guardianModal" tabindex="-1" aria-labelledby="guardianModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="guardianModalLabel">
                <div class="title-icon-wrap">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    บันทึกการรับเด็กกลับบ้าน
                    <div class="header-subtitle">Guardian Pickup Record</div>
                </div>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">

                <!-- Student Info -->
                <div class="guardian-student-card">
                <div class="avatar" id="guardianStudentAvatar">-</div>
                <div class="info">
                    <h4 id="guardianStudentName">ชื่อ นักเรียน</h4>
                    <small>
                    <i class="bi bi-person-badge"></i>
                    <span id="guardianStudentId" class="badge-pill">-</span>
                    <i class="bi bi-door-open ms-1"></i>
                    <span id="healthStudentClassroom" class="badge-pill">-</span>
                    <i class="bi bi-clock ms-1"></i>
                    <span id="guardianStudentTime" class="badge-pill text-success">-</span>
                    </small>
                </div>
                </div>

                <!-- Guardian Selection -->
                <div class="form-card">
                <div class="section-label">
                    <i class="bi bi-person-check"></i> เลือกผู้รับเด็กกลับบ้าน
                </div>

                <!-- Father / Mother / Relative -->
                <div class="guardian-grid mb-3">

                    <label class="guardian-select-card">
                    <input type="radio" name="guardian" value="father" id="fatherRadio">
                    <div class="guardian-card-inner">
                        <img id="fatherImg" src="" alt="รูปพ่อ" class="g-avatar" onerror="this.src=defaultAvatar">
                        <span class="g-label">พ่อ</span>
                        <span class="g-name" id="fatherName">-</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                    </label>

                    <label class="guardian-select-card">
                    <input type="radio" name="guardian" value="mother" id="motherRadio">
                    <div class="guardian-card-inner">
                        <img id="motherImg" src="" alt="รูปแม่" class="g-avatar" onerror="this.src=defaultAvatar">
                        <span class="g-label">แม่</span>
                        <span class="g-name" id="motherName">-</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                    </label>

                    <label class="guardian-select-card">
                    <input type="radio" name="guardian" value="relative" id="relativeRadio">
                    <div class="guardian-card-inner">
                        <img id="relativeImg" src="" alt="รูปญาติ" class="g-avatar" onerror="this.src=defaultAvatar">
                        <span class="g-label">ญาติ</span>
                        <span class="g-name" id="relativeName">-</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                    </label>

                </div>

                <!-- Other -->
                <label class="guardian-select-card w-100">
                    <input type="radio" name="guardian" value="other" id="otherRadio">
                    <div class="other-card-inner">
                    <div class="other-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <span class="other-text">อื่นๆ (โปรดระบุ)</span>
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>

                <!-- Other Detail Input -->
                <div id="otherDetails" class="mt-3" style="display:none;">
                    <textarea
                    class="form-control"
                    id="otherGuardianDetails"
                    placeholder="กรุณาระบุรายละเอียดผู้รับเด็ก เช่น ชื่อ-นามสกุล และความสัมพันธ์"
                    ></textarea>
                </div>

                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" id="guardianBtnClose">
                <i class="bi bi-x-circle me-1"></i> ปิด
                </button>
                <button type="button" class="btn btn-save-custom" id="guardianBtnSave">
                <i class="bi bi-check-circle me-1"></i> บันทึกข้อมูล
                </button>
            </div>

            </div>
        </div>
    </div>
    <!-- script สำหรับแสกน qrcode เช็คชื่อ -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script>
         // เริ่มต้นการสแกน

        const attendanceTableBody = document.getElementById('attendance-table-body');

        let isScanning = false;  // ตัวแปรสำหรับเช็คสถานะการสแกน
        let lastScannedData = ''; // ตัวแปรเก็บข้อมูล QR ล่าสุดที่สแกน

        // กด Escape เพื่อปิด modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHealthModal();
            }
        });


        // ใช้ Html5Qrcode สแกน QR จากวิดีโอ
        const onScanSuccess = (decodedText, decodedResult) => {
            if (isScanning || decodedText === lastScannedData) return;

            isScanning = true;
            lastScannedData = decodedText;
            
            let studentData;
            try {
                studentData = JSON.parse(decodedText);
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'QR Code ไม่ถูกต้อง',
                    text: 'กรุณาสแกน QR Code อีกครั้ง',
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => {
                    isScanning = false;
                    lastScannedData = ''; // รีเซ็ตให้สแกน QR เดิมซ้ำได้
                }, 1500);
                return;
            }
            
            // ดึงข้อมูลนักเรียนและผู้ปกครอง
            fetch(`../../include/attendance/get_student_guardians.php?student_id=${studentData.student_id}`)
                .then(response => response.json())
                .then(async result => {
                    if (result.status === 'error') {
                        throw new Error(result.message || 'ไม่พบข้อมูลนักเรียน');
                    }
                    const guardians = result.data;
                    // ตรวจสอบว่ามีข้อมูลผู้ปกครองหรือไม่ (อย่างน้อยต้องมีชื่อใดชื่อหนึ่ง)
                    const hasGuardian = guardians.father_first_name || guardians.mother_first_name || guardians.relative_first_name;
                    if (!hasGuardian) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'ไม่พบข้อมูลผู้ปกครอง',
                            text: 'นักเรียนคนนี้ไม่มีข้อมูลผู้ปกครองในระบบ กรุณาติดต่อผู้ดูแล',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return;
                    }
                    // ตรวจสอบการเช็คเอาท์ที่บันทึกไว้แล้ว
                    await checkExistingCheckout(studentData.student_id);
                    // แสดง modal ให้เลือกผู้รับเด็ก
                    showPickupModal(guardians, studentData);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message
                    });
                })
                .finally(() => {
                    setTimeout(() => {
                        isScanning = false;
                        lastScannedData = ''; // รีเซ็ตให้สแกน QR เดิมซ้ำได้เมื่อเกิด error
                    }, 3000);
                });
        };

        // เริ่มต้นการสแกน
        const html5QrCode = new Html5Qrcode("reader");
        const readerElem = document.getElementById('reader');

        // ฟังก์ชันคำนวณขนาด qrbox ตามหน้าจอ
        function calculateQrBoxSize() {
            const containerWidth = readerElem.offsetWidth;
            const viewportWidth = window.innerWidth;
            let qrBoxSize;

            // ทำให้ qrbox เล็กลงเพ่อให้ scanner โฟกัสพื้นที่แคบๆ
            // ส่งผลให้ QR ขนาดเล็กกินพื้นที่สัดส่วนมากขึ้น -> อ่านง่ายขึ้น
            if (viewportWidth <= 480) {
                // หน้าจอเล็ก (มือถือ)
                qrBoxSize = Math.min(containerWidth * 0.75, 200);
            } else if (viewportWidth <= 768) {
                // หน้าจอขนาดกลาง (แท็บเล็ต)
                qrBoxSize = Math.min(containerWidth * 0.65, 220);
            } else if (viewportWidth <= 1024) {
                // หน้าจอขนาดใหญ่ (แล็ปท็อป)
                qrBoxSize = Math.min(containerWidth * 0.5, 240);
            } else {
                // หน้าจอขนาดใหญ่มาก (เดสก์ท็อป)
                qrBoxSize = Math.min(containerWidth * 0.45, 250);
            }

            return Math.max(160, qrBoxSize); // ขนาดต่ำสุด 160px
        }

        const qrBoxSize = calculateQrBoxSize();

        const config = {
            fps: 25,
            qrbox: {
                width: qrBoxSize,
                height: qrBoxSize
            }
        };

        // Start the QR scanner. When the scanner has successfully started we can safely
        // initialise the zoom controls because the video element and its MediaStream are
        // now available. This avoids the race condition where `initZoomControl` runs
        // before the camera permission is granted or before the video track is attached,
        // which caused the zoom UI to remain hidden intermittently.
        html5QrCode.start({
                facingMode: "environment"
            },
            config,
            onScanSuccess
        ).then(() => {
            // Initialise zoom controls only after the scanner is ready.
            try {
                initZoomControl();
            } catch (e) {
                console.warn('Zoom init error (non-blocking):', e);
            }
        }).catch(err => {
            console.error('Error starting QR scanner:', err);
        });

        // --- Zoom Control Logic ---
        let currentZoom = 1;
        const MIN_ZOOM = 1;
        const MAX_ZOOM = 5;

        const zoomControls = document.getElementById('zoomControls');
        const zoomStatusMsg = document.getElementById('zoomStatusMsg');
        const zoomInnerControls = document.getElementById('zoomInnerControls');
        const zoomSlider = document.getElementById('zoomSlider');
        const zoomValueDisplay = document.getElementById('zoomValueDisplay');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');

        async function initZoomControl() {
            try {
                const videoElem = document.querySelector('#reader video');
                if (!videoElem) {
                    zoomStatusMsg.innerHTML = '<i class="bi bi-camera-video-off"></i> ไม่พบ Video element';
                    return;
                }

                const stream = videoElem.srcObject;
                if (!stream) {
                    zoomStatusMsg.innerHTML = '<i class="bi bi-camera-video-off"></i> ยังไม่ได้รับสัญญาณกล้อง';
                    return;
                }

                const videoTrack = stream.getVideoTracks()[0];
                if (!videoTrack) {
                    zoomStatusMsg.innerHTML = '<i class="bi bi-camera-video-off"></i> ไม่พบ Video Track';
                    return;
                }

                const capabilities = videoTrack.getCapabilities();
                if (!capabilities.zoom) {
                    zoomStatusMsg.innerHTML = '<i class="bi bi-camera"></i> กล้องนี้ไม่รองรับการซูม (Digital Zoom)';
                    return;
                }

                // --- ซูมได้ ---
                zoomStatusMsg.style.display = 'none';
                zoomInnerControls.style.display = 'flex';

                const zoomMin = capabilities.zoom.min || 1;
                const zoomMax = capabilities.zoom.max || 5;
                const zoomStep = capabilities.zoom.step || 0.1;

                zoomSlider.min = zoomMin;
                zoomSlider.max = zoomMax;
                zoomSlider.step = zoomStep;
                zoomSlider.value = 1;

                try {
                    const settings = videoTrack.getSettings();
                    if (settings.zoom) {
                        currentZoom = settings.zoom;
                        zoomSlider.value = currentZoom;
                        updateZoomDisplay(currentZoom);
                    }
                } catch (e) {}

                async function applyZoom(zoomValue) {
                    try {
                        await videoTrack.applyConstraints({
                            advanced: [{ zoom: zoomValue }]
                        });
                        currentZoom = zoomValue;
                        updateZoomDisplay(zoomValue);
                        updateZoomButtons(zoomValue);
                    } catch (err) {
                        console.error('ไม่สามารถปรับซูมได้:', err);
                    }
                }

                zoomSlider.addEventListener('input', function() {
                    const val = parseFloat(this.value);
                    updateZoomDisplay(val);
                });

                zoomSlider.addEventListener('change', function() {
                    const val = parseFloat(this.value);
                    applyZoom(val);
                });

                zoomInBtn.addEventListener('click', function() {
                    const newVal = Math.min(parseFloat(zoomSlider.max), currentZoom + parseFloat(zoomSlider.step || 0.1));
                    zoomSlider.value = newVal;
                    applyZoom(newVal);
                });

                zoomOutBtn.addEventListener('click', function() {
                    const newVal = Math.max(parseFloat(zoomSlider.min), currentZoom - parseFloat(zoomSlider.step || 0.1));
                    zoomSlider.value = newVal;
                    applyZoom(newVal);
                });

                updateZoomButtons(currentZoom);

            } catch (err) {
                console.warn('ไม่สามารถตั้งค่าซูมได้:', err);
                zoomStatusMsg.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ไม่สามารถตรวจสอบการซูมได้';
            }
        }

        function updateZoomDisplay(value) {
            zoomValueDisplay.textContent = parseFloat(value).toFixed(1) + 'x';
        }

        function updateZoomButtons(value) {
            const val = parseFloat(value);
            const min = parseFloat(zoomSlider.min);
            const max = parseFloat(zoomSlider.max);
            zoomOutBtn.disabled = val <= min;
            zoomInBtn.disabled = val >= max;
        }
        // --- End Zoom Control Logic ---

                // ฟังก์ชันปรับขนาด qrbox เมื่อขนาดหน้าจอเปลี่ยน
                function updateQrScannerSize() {
                    const newQrBoxSize = calculateQrBoxSize();
                    // Stop current scanner, then restart with new size and re‑init zoom controls
                    html5QrCode.stop()
                        .then(() => {
                            const newConfig = {
                                fps: 25,
                                qrbox: { width: newQrBoxSize, height: newQrBoxSize }
                            };
                            return html5QrCode.start({ facingMode: "environment" }, newConfig, onScanSuccess);
                        })
                        .then(() => {
                            try { initZoomControl(); } catch (e) { console.warn('Zoom init after resize error (non‑blocking):', e); }
                        })
                        .catch(err => {
                            console.error('Error (re)starting QR scanner:', err);
                        });
                }

                // Listen for viewport size changes – debounce to avoid rapid restarts
                let resizeTimer;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        updateQrScannerSize();
                    }, 300);
                });

                // -----------------------------------------------------------------
                // Re‑initialize scanner on page scroll (mobile browsers may detach video)
                // -----------------------------------------------------------------
                function ensureScannerRunning() {
                    const videoElem = document.querySelector('#reader video');
                    if (!videoElem) {
                        const currentQrBoxSize = calculateQrBoxSize();
                        const restartConfig = {
                            fps: 25,
                            qrbox: { width: currentQrBoxSize, height: currentQrBoxSize }
                        };
                        html5QrCode.start({ facingMode: "environment" }, restartConfig, onScanSuccess)
                            .then(() => {
                                try { initZoomControl(); } catch (e) { console.warn('Zoom init after scroll error:', e); }
                            })
                            .catch(err => console.error('Error restarting QR scanner on scroll:', err));
                    }
                }
                let scrollTimer;
                window.addEventListener('scroll', () => {
                    clearTimeout(scrollTimer);
                    scrollTimer = setTimeout(() => {
                        ensureScannerRunning();
                    }, 200);
                });

         // ฟังก์ชันเปิด Modal พร้อมข้อมูล
    function openGuardianModal(studentData, guardianData, timeStr) {
        // Student Info
        const defaultAvatar = '../../../public/assets/images/avatar.png';
        const nameParts = [studentData.prefix, studentData.first_name, studentData.last_name]
            .filter(Boolean).join(' ');
        document.getElementById('guardianStudentName').textContent = nameParts || '-';
        document.getElementById('guardianStudentId').textContent  = studentData.student_id || '-';
        document.getElementById('guardianStudentTime').textContent = timeStr || '-';
        document.getElementById('healthStudentClassroom').textContent = studentData.classroom || '-';
        // Avatar Initial
        const initial = (studentData.first_name || name).charAt(0).toUpperCase();
        document.getElementById('guardianStudentAvatar').textContent = initial;

        // Guardian Images & Names
        document.getElementById('fatherImg').src    = guardianData.father_image   || defaultAvatar;
        document.getElementById('motherImg').src    = guardianData.mother_image   || defaultAvatar;
        document.getElementById('relativeImg').src  = guardianData.relative_image || defaultAvatar;

        document.getElementById('fatherName').textContent   = guardianData.father_first_name   || '-';
        document.getElementById('motherName').textContent   = guardianData.mother_first_name   || '-';
        document.getElementById('relativeName').textContent = guardianData.relative_first_name || '-';

        // Reset Form
        document.querySelectorAll('input[name="guardian"]').forEach(r => r.checked = false);
        document.getElementById('otherDetails').style.display = 'none';
        document.getElementById('otherGuardianDetails').value = '';

        // Open Modal
        const modal = new bootstrap.Modal(document.getElementById('guardianModal'));
        modal.show();
    }

       
        // เพิ่มฟังก์ชันแสดง Modal
        function showPickupModal(guardianData, studentData) {

            // default avatar path
            const defaultAvatar = '../../../public/assets/images/avatar.png';
            // format current time for display
            const now = new Date();
            const timeStr = now.toLocaleString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            // เรียกฟังก์ชันเปิด Modal
            openGuardianModal(studentData, guardianData, timeStr);
        }

        // ฟังก์ชันอัปเดตตารางการเช็คชื่อ
        function updateAttendanceTable() {
            fetch('../../include/attendance/get-checkout.php')  // ดึงข้อมูลการเช็คชื่อจากฐานข้อมูล
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch attendance data');
                    }
                    return response.json();  // แปลงข้อมูลเป็น JSON
                })
                .then(data => {
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = ''; // เคลียร์ข้อมูลก่อน

                    if (data.message) {
                        // ถ้าไม่มีข้อมูลการเช็คชื่อ
                        tableBody.innerHTML = `<tr><td colspan="6">${data.message}</td></tr>`;
                        return;
                    }

                    // ถ้ามีข้อมูล
                    data.forEach((record, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                    <td>${index + 1}</td> <!-- เพิ่มเลขลำดับ -->
                    <td>${record.student_id}</td>
                    <td>${record.prefix_th} ${record.firstname_th} ${record.lastname_th}</td>
                    <td><span class="badge bg-primary">${record.classroom}</span></td>
                    <td><span class="badge bg-secondary">${record.timestamp}</span></td>
                    <td>
                        ${record.status_checkout && 
                        record.status_checkout !== 'ยังไม่กลับบ้าน' && 
                        record.status_checkout !== null && 
                        record.status_checkout !== undefined ? 
                            `<span class="badge bg-success">${record.status_checkout}</span>` : 
                            `<span class="badge bg-secondary">ยังไม่กลับบ้าน</span>`
                        }
                    </td>
                `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching attendance:', error);
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = '<tr><td colspan="6">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>';
                });
        }

        // เรียก updateAttendanceTable() ทันทีที่ DOM โหลดเสร็จ
        document.addEventListener('DOMContentLoaded', () => {
            // Load attendance data once on page load. Refresh will be triggered manually after a successful guardian save.
            updateAttendanceTable();
        });

        
        // เพิ่ม CSS สำหรับ Modal
        const style = document.createElement('style');
        style.textContent = `
            .guardian-images .card {
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .guardian-images .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .guardian-img {
                height: 200px;
                object-fit: cover;
            }
            .form-check {
                padding: 10px;
                text-align: center;
            }
        `;
        document.head.appendChild(style);

    document.addEventListener('DOMContentLoaded', () => {
    console.log('guardian.js loaded'); // debug
    const btnSave = document.getElementById('guardianBtnSave');
    if (!btnSave) {
        console.error('guardianBtnSave not found');
        return; // safety guard
    }

    btnSave.addEventListener('click', async function (e) {
        console.log('guardianBtnSave clicked');
        // 1️⃣ Validate selection
        const selectedGuardian = document.querySelector('input[name="guardian"]:checked');
        if (!selectedGuardian) {
            showToast('กรุณาเลือกผู้รับเด็กก่อนบันทึก', 'danger');
            return;
        }

        // 2️⃣ UI feedback – disable button & show spinner
        btnSave.disabled = true;
        const originalHtml = btnSave.innerHTML;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึก…';

        try {
            // 3️⃣ Send data to server (adjust endpoint as needed)
            console.log('Attempting to save guardian, id:', selectedGuardian.value);
            // Gather student ID from the modal display (element populated when modal opens)
            const studentIdElem = document.getElementById('guardianStudentId');
            const studentId = studentIdElem ? studentIdElem.textContent.trim() : '';
            // Prepare payload with guardian details
            let guardianName = '';
            // Determine name based on selected guardian type
            switch (selectedGuardian.value) {
                case 'father':
                    guardianName = document.getElementById('fatherName')?.textContent.trim() || '';
                    break;
                case 'mother':
                    guardianName = document.getElementById('motherName')?.textContent.trim() || '';
                    break;
                case 'relative':
                    guardianName = document.getElementById('relativeName')?.textContent.trim() || '';
                    break;
                case 'other':
                    guardianName = document.getElementById('otherGuardianDetails')?.value.trim() || '';
                    break;
                default:
                    guardianName = '';
            }

            const payload = {
                student_id: studentId,
                guardian_type: selectedGuardian.value,
                guardian_name: guardianName,
                other_details: document.getElementById('otherGuardianDetails')?.value.trim() || ''
            };
            const response = await fetch('../../include/attendance/save_guardian_pickup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error(`Server responded ${response.status}`);
            }

            const result = await response.json();
            if (result.status === 'success') {
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('guardianModal'));
                modal.hide();

                // แสดงข้อความสำเร็จ
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    text: `บันทึกการรับเด็กโดย: ${result.data['guardian_name']}`,
                    timer: 2000,
                    showConfirmButton: false
                });

                // อัปเดตตาราง
                const studentId = document.getElementById('manualStudentId').value="";
                updateAttendanceTable();

            } else {
                throw new Error(result.message || 'Unknown error');
            }
        } catch (err) {
            console.error(err);
            showToast('บันทึกข้อมูลไม่สำเร็จ: ' + err.message, 'danger');
        } finally {
            // 4️⃣ Restore button state
            btnSave.disabled = false;
            btnSave.innerHTML = originalHtml;
        }
    });
});

/**
 * Simple toast helper using Bootstrap 5 toast component.
 * type: 'success', 'danger', 'info', etc. (maps to bg- classes)
 */
function showToast(message, type = 'info') {
    // Create toast container if not exists
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.position = 'fixed';
        container.style.top = '1rem';
        container.style.right = '1rem';
        container.style.zIndex = 1055;
        document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;
    toastEl.role = 'alert';
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
    container.appendChild(toastEl);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });
    bsToast.show();
}
    </script>
</body>

</html>