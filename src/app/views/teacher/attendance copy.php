<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['admin', 'teacher']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>
<?php
$children = getChildrenData();

// รับค่า tab และ room จาก URL
$currentTab = $_GET['tab'] ?? 'all';  // ใช้ค่าเริ่มต้น 'all' หากไม่ได้รับค่า
// ดึงข้อมูลจากฟังก์ชัน
$data = getChildrenGroupedByTab($currentTab);
?>

<style>
    .attendance {
        padding: 0;
    }

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

    .scanner-inline-panel {
        width: 100%;
        max-width: 600px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #f8f9fa;
    }

    .scanner-inline-panel .zoom-controls {
        margin: 0 auto 1rem;
    }

    #qrScannerModal .modal-body {
        background: #f8fafc;
    }

    #qrScannerModal #video-container {
        min-height: 300px;
    }

    #qrScannerModal #reader,
    #qrScannerModal #reader__scan_region {
        min-height: 300px !important;
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
        min-height: 350px !important;
        border: none !important;
    }

    #reader video {
        border-radius: 10px;
        object-fit: cover;
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

    #reader img {
        border-radius: 10px;
    }

    #reader__scan_region {
        display: flex;
        justify-content: center;
        align-items: center;
        background: #000;
        border-radius: 10px;
        min-height: 350px;
    }

    #reader__dashboard_section {
        padding: 10px;
    }

    /* Attendance Table */
    .table-container {
        flex-grow: 1;
    }

    /* Page Title */
    h1 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 2rem;
        text-align: center;
    }

    .https {
        font-size: 1.3rem;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
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

    /* Checkout Button */
    .checkout-button {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        background: #4a90e2;
        color: white;
        border-radius: 25px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 2rem;
        border: none;
        cursor: pointer;
    }

    .checkout-button:hover {
        background: #357abd;
        transform: translateY(-2px);
    }

    /* Tabs */
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
        text-decoration: none;
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


    /* Student Section Styles */
    .student-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
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

    /* Table Styles */
    .student-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }

    .student-table th {
        background-color: #4a90e2;
        color: white;
        padding: 1rem;
        font-weight: 500;
        text-align: center;
    }

    .student-table td {
        padding: 0.8rem;
        border-bottom: 1px solid #eee;
        text-align: center;
        vertical-align: middle;
    }

    .student-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Badge Styles */
    .badge {
        padding: 0.5em 1em;
        font-weight: 500;
        font-size: 0.85em;
        border-radius: 20px;
    }

    /* Empty State */
    .text-muted {
        color: #6c757d;
        font-style: italic;
    }

    /* Icons */
    .bi {
        margin-right: 0.3rem;
    }
    
    <style>
  /* ===== Health Modal - Navy Blue Theme ===== */
  #healthModal .modal-content {
    border: none;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(10, 30, 80, 0.2);
  }

  #healthModal .modal-header {
    background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
    border: none;
    padding: 1.5rem 2rem;
    position: relative;
    overflow: hidden;
  }

  #healthModal .modal-header::before {
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

  #healthModal .modal-header::after {
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

  #healthModal .modal-title {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.15rem;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
  }

  #healthModal .modal-title .title-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
  }

  #healthModal .modal-title .title-icon-wrap i {
    animation: heartbeat 1.6s ease-in-out infinite;
    color: #f87171;
  }

  @keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.25); }
  }

  #healthModal .modal-header .header-subtitle {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    font-weight: 400;
    margin-top: 2px;
  }

  #healthModal .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.6;
    position: relative;
    z-index: 1;
    transition: opacity 0.2s;
  }

  #healthModal .btn-close:hover {
    opacity: 1;
  }

  /* ===== Modal Body ===== */
  #healthModal .modal-body {
    background: #f0f4f8;
    padding: 1.75rem 1.75rem;
  }

  /* ===== Student Info Card ===== */
  .health-student-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.1rem 1.4rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 16px rgba(15, 36, 96, 0.1);
    border-left: 5px solid #1e4db7;
    position: relative;
    overflow: hidden;
  }

  .health-student-card::after {
    content: "\F34A";
    font-family: "bootstrap-icons";
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 4rem;
    color: #1e4db710;
    pointer-events: none;
  }

  .health-student-card .avatar {
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

  .health-student-card .info h4 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 700;
    color: #0f2460;
  }

  .health-student-card .info small {
    color: #64748b;
    font-size: 0.78rem;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 5px;
    flex-wrap: wrap;
  }

  .health-student-card .info .badge-pill {
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

  /* ===== Form Card Wrapper ===== */
  .form-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.2rem 1.4rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(15, 36, 96, 0.07);
  }

  /* ===== Temperature Input ===== */
  .temp-input-group {
    position: relative;
  }

  .temp-input-group .form-control {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    padding: 0.72rem 4rem 0.72rem 1rem;
    font-size: 1.05rem;
    font-weight: 600;
    color: #0f2460;
    background: #f8faff;
    transition: all 0.3s ease;
    box-shadow: none;
  }

  .temp-input-group .form-control:focus {
    border-color: #1e4db7;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
    outline: none;
  }

  .temp-input-group .form-control::placeholder {
    color: #cbd5e1;
    font-weight: 400;
  }

  .temp-input-group .unit-badge {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 8px;
    pointer-events: none;
    letter-spacing: 0.5px;
  }

  /* ===== Symptoms Grid ===== */
  .symptoms-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.6rem;
  }

  .symptom-checkbox {
    position: relative;
    cursor: pointer;
    margin: 0;
  }

  .symptom-checkbox input[type="checkbox"] {
    display: none;
  }

  .symptom-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8faff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.65rem 0.9rem;
    transition: all 0.25s ease;
    user-select: none;
  }

  .symptom-item .symptom-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #eff3ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    color: #1e4db7;
    transition: all 0.25s ease;
    flex-shrink: 0;
  }

  .symptom-item .symptom-text {
    font-size: 0.84rem;
    font-weight: 600;
    color: #475569;
    transition: color 0.25s ease;
  }

  .symptom-item .check-mark {
    margin-left: auto;
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
    flex-shrink: 0;
  }

  .symptom-item .check-mark i {
    font-size: 0.68rem;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  /* Checked State */
  .symptom-checkbox input:checked + .symptom-item {
    border-color: #1e4db7;
    background: #eff3ff;
    box-shadow: 0 4px 14px rgba(30, 77, 183, 0.15);
  }

  .symptom-checkbox input:checked + .symptom-item .symptom-icon {
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    color: #fff;
    box-shadow: 0 4px 10px rgba(30, 77, 183, 0.3);
  }

  .symptom-checkbox input:checked + .symptom-item .symptom-text {
    color: #0f2460;
  }

  .symptom-checkbox input:checked + .symptom-item .check-mark {
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    border-color: transparent;
  }

  .symptom-checkbox input:checked + .symptom-item .check-mark i {
    opacity: 1;
  }

  .symptom-item:hover {
    border-color: #1e4db750;
    background: #f0f5ff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(30, 77, 183, 0.1);
  }

  /* ===== Other Symptoms Textarea ===== */
  #healthOtherSymptoms {
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

  #healthOtherSymptoms:focus {
    border-color: #1e4db7;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
    outline: none;
  }

  #healthOtherSymptoms::placeholder {
    color: #c4cdd9;
  }

  /* ===== Modal Footer ===== */
  #healthModal .modal-footer {
    background: #f0f4f8;
    border-top: 1px solid #e2e8f0;
    padding: 1rem 1.75rem;
    gap: 0.65rem;
  }

  #healthModal .btn-close-custom {
    border-radius: 12px;
    padding: 0.58rem 1.3rem;
    font-size: 0.88rem;
    font-weight: 600;
    border: 2px solid #e2e8f0;
    color: #64748b;
    background: #ffffff;
    transition: all 0.25s ease;
  }

  #healthModal .btn-close-custom:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
  }

  #healthModal .btn-save-custom {
    border-radius: 12px;
    padding: 0.58rem 1.5rem;
    font-size: 0.88rem;
    font-weight: 700;
    border: none;
    background: linear-gradient(135deg, #0f2460 0%, #1e4db7 100%);
    color: #ffffff;
    transition: all 0.25s ease;
    box-shadow: 0 4px 16px rgba(15, 36, 96, 0.35);
    letter-spacing: 0.2px;
  }

  #healthModal .btn-save-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15, 36, 96, 0.45);
    background: linear-gradient(135deg, #0a1a4f 0%, #1a43a8 100%);
  }

  #healthModal .btn-save-custom:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(15, 36, 96, 0.3);
  }
</style>

</style>

</style>

<body>
    <main class="main-content">
        <div class="container-fluid">           

            <div id="scanner-container">
                <h5 class="text-center text-primary">บันทึกการเช็คชื่อมาเรียน วันที่ <?php echo date('d/m/Y'); ?></h5>
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

                <div class="scanner-inline-panel">
                    <div id="scannerError" class="alert alert-warning" style="display:none;"></div>
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <i class="bi bi-camera-video-fill text-primary"></i>
                        <span id="scannerStatus" class="text-muted small">กำลังเปิดกล้อง...</span>
                    </div>
                    <div class="zoom-controls" id="zoomControls">
                        <span id="zoomStatusMsg" style="font-size:0.85rem;color:#6c757d;">
                            <i class="bi bi-search"></i> กำลังตรวจสอบกล้อง...
                        </span>
                        <div id="zoomInnerControls" style="display:none; justify-content:center; align-items:center;">
                            <button type="button" id="zoomOutBtn" title="ซูมออก" disabled>−</button>
                            <input type="range" id="zoomSlider" min="1" max="5" step="0.1" value="1">
                            <button type="button" id="zoomInBtn" title="ซูมเข้า" disabled>+</button>
                            <span class="zoom-value" id="zoomValueDisplay">1.0x</span>
                        </div>
                    </div>
                    <div id="video-container">
                        <div id="reader"></div>
                    </div>
                    <div class="d-flex justify-content-center mt-2">
                        <button type="button" class="btn btn-outline-primary" id="switchScannerCamera">
                            <i class="bi bi-arrow-repeat me-1"></i>สลับกล้อง
                        </button>
                    </div>
                    <div class="mt-3" style="width:100%;max-width:420px;">
                        <label for="scannerCameraSelect" class="form-label mb-1">
                            <i class="bi bi-camera-video me-1"></i>เลือกกล้อง
                        </label>
                        <select id="scannerCameraSelect" class="form-select" disabled>
                            <option value="">กำลังค้นหากล้อง...</option>
                        </select>
                    </div>
                </div>

                
                
                <!-- ตารางแสดงข้อมูลเช็คชื่อ -->
                <div class="table-responsive" style="width:100% ; max-height: 400px; overflow: scroll; ">
                    <table class="table table-striped" >
                        <thead>
                            <tr class="table-primary">
                                <th>ลำดับ</th>
                                <th>รหัสนักเรียน</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ห้องเรียน</th>
                                <th>วันเวลา</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table-body">
                            <?php if (count($children) > 0): ?>
                                <?php
                                $counter = 1; // เริ่มต้นตัวนับที่ 1
                                $hasAttendance = false; // ตัวแปรเพื่อตรวจสอบว่ามีข้อมูลการเช็คชื่อหรือไม่
                                foreach ($children as $record):
                                    if (!empty($record['check_date'])): // แสดงเฉพาะรายการที่มีการเช็คชื่อ
                                        $hasAttendance = true;
                                ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($record['studentid']); ?></td>
                                            <td><?php echo htmlspecialchars($record['prefix_th'] . ' ' . $record['firstname_th'] . ' ' . $record['lastname_th']); ?></td>
                                            <td><?php echo htmlspecialchars($record['classroom']); ?></td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($record['check_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['status']); ?></td>
                                        </tr>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                                <?php if (!$hasAttendance): // ถ้าไม่มีรายการที่มีการเช็คชื่อ 
                                ?>
                                    <tr>
                                        <td colspan="6">ไม่มีข้อมูลการเช็คชื่อในวันนี้</td>
                                    </tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">ไม่มีข้อมูลการเช็คชื่อในวันนี้</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                                <div class="table-responsive" style="max-height:350px; overflow:scroll; overflow-x:hidden;">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr class="table-primary">
                                                <th>รหัสประจำตัว</th>
                                                <th>ชื่อ</th>
                                                <th>นามสกุล</th>
                                                <th>ชื่อเล่น</th>
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
                                                        <td><?= htmlspecialchars($child['nickname']) ?></td>
                                                        <td><span class="badge bg-info"><?= htmlspecialchars($child['child_group']) ?></span></td>
                                                        <td><span class="badge bg-primary"><?= htmlspecialchars($child['classroom']) ?></span></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = $child['status'] === 'มาเรียน' ? 'bg-success' : 'bg-danger';
                                                            ?>
                                                            <span class="badge <?= $statusClass ?>">
                                                                <?= htmlspecialchars($child['status']) ?>
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
        </div>

    </main>

    <!-- Health Modal -->
    <div class="modal fade" id="healthModal" tabindex="-1" aria-labelledby="healthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">

        <!-- Header -->
        <div class="modal-header">
            <h5 class="modal-title" id="healthModalLabel">
            <div class="title-icon-wrap">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div>
                บันทึกข้อมูส่งเด็ก
                <div class="header-subtitle">Health Record System</div>
            </div>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">

            <!-- Student Info -->
            <div class="health-student-card" id="healthStudentInfo">
            <div class="avatar" id="healthStudentAvatar">-</div>
            <div class="info">
                <h4 id="healthStudentName">ชื่อ นักเรียน</h4>
                <small id="healthStudentDetail">
                <i class="bi bi-person-badge"></i>
                <span id="healthStudentId" class="badge-pill">-</span>
                <i class="bi bi-door-open ms-1"></i>
                <span id="healthStudentClassroom" class="badge-pill">-</span>
                </small>
            </div>
            </div>

            <!-- Form -->
            <form id="healthForm" onsubmit="return false;">
            <input type="hidden" id="healthStudentIdInput" value="">
            <input type="hidden" id="healthAttendanceIdInput" value="">

            <!-- Temperature -->
            <div class="form-card">
                <div class="section-label">
                <i class="bi bi-thermometer-half"></i> อุณหภูมิร่างกาย
                </div>
                <div class="temp-input-group">
                <input
                    type="number"
                    class="form-control"
                    id="healthTemperature"
                    step="0.1"
                    min="35.0"
                    max="42.0"
                    placeholder="37.0"
                >
                <span class="unit-badge">°C</span>
                </div>
            </div>

            <!-- Symptoms -->
            <div class="form-card">
                <div class="section-label">
                <i class="bi bi-exclamation-triangle"></i> อาการผิดปกติ
                <span class="ms-1 text-muted fw-normal"
                    style="text-transform: none; letter-spacing: 0; font-size: 0.76rem;">
                    (เลือกได้มากกว่า 1)
                </span>
                </div>
                <div class="symptoms-grid">

                <label class="symptom-checkbox" data-symptom="runny_nose">
                    <input type="checkbox" id="symptomRunnyNose">
                    <div class="symptom-item">
                    <div class="symptom-icon"><i class="bi bi-droplet-fill"></i></div>
                    <span class="symptom-text">มีน้ำมูก</span>
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>

                <label class="symptom-checkbox" data-symptom="cough">
                    <input type="checkbox" id="symptomCough">
                    <div class="symptom-item">
                    <div class="symptom-icon"><i class="bi bi-wind"></i></div>
                    <span class="symptom-text">ไอ</span>
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>

                <label class="symptom-checkbox" data-symptom="rash">
                    <input type="checkbox" id="symptomRash">
                    <div class="symptom-item">
                    <div class="symptom-icon"><i class="bi bi-circle-fill"></i></div>
                    <span class="symptom-text">มีผื่น</span>
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>

                <label class="symptom-checkbox" data-symptom="red_eyes">
                    <input type="checkbox" id="symptomRedEyes">
                    <div class="symptom-item">
                    <div class="symptom-icon"><i class="bi bi-eye-fill"></i></div>
                    <span class="symptom-text">ตาแดง</span>
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>

                </div>
            </div>

            <!-- Other Symptoms -->
            <div class="form-card mb-0">
                <div class="section-label">
                <i class="bi bi-pencil-square"></i> อาการอื่นๆ
                </div>
                <textarea
                class="form-control"
                id="healthOtherSymptoms"
                placeholder="เช่น ปวดหัว, คลื่นไส้, ท้องเสีย, ฯลฯ"
                ></textarea>
            </div>

            </form>
        </div>

        <!-- Footer -->
        <div class="modal-footer justify-content-end">
            <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" id="healthBtnClose">
            <i class="bi bi-x-circle me-1"></i> ปิด
            </button>
            <button type="button" class="btn btn-save-custom" id="healthBtnSave">
            <i class="bi bi-check-circle me-1"></i> บันทึกข้อมูลสุขภาพ
            </button>
        </div>

        </div>
    </div>
    </div>

    <!-- script สำหรับแสกน qrcode เช็คชื่อ -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+" crossorigin="anonymous"></script>
    <script>
        const attendanceTableBody = document.getElementById('attendance-table-body');
        const manualStudentIdInput = document.getElementById('manualStudentId');
        const manualAttendanceBtn = document.getElementById('manualAttendanceBtn');
        let isScanning = false; // ตัวแปรสำหรับเช็คสถานะการสแกน
        let lastScannedData = ''; // ตัวแปรเก็บข้อมูล QR ล่าสุดที่สแกน

        // ====== Health Check Modal Functions ======

        // Bootstrap modal instance (lazy init)
        let healthModalInstance = null;
        function getHealthModal() {
            if (!healthModalInstance) {
                const modalEl = document.getElementById('healthModal');
                healthModalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
            }
            return healthModalInstance;
        }

        function openHealthModal(studentData) {
            console.log(studentData);
            // Populate hidden fields
            document.getElementById('healthStudentIdInput').value = studentData.student_id;
            document.getElementById('healthAttendanceIdInput').value = studentData.attendance_id;

            // Populate visible info
            const name = studentData.name || 'ไม่ระบุชื่อ';
            document.getElementById('healthStudentName').textContent = name;
            document.getElementById('healthStudentId').textContent = studentData.student_id;
            document.getElementById('healthStudentClassroom').textContent = studentData.classroom || '-';
            const firstChar = (studentData.first_name || name).charAt(0).toUpperCase();
            document.getElementById('healthStudentAvatar').textContent = firstChar;

            // Reset form fields
            document.getElementById('healthTemperature').value = '';
            document.getElementById('symptomRunnyNose').checked = false;
            document.getElementById('symptomCough').checked = false;
            document.getElementById('symptomRash').checked = false;
            document.getElementById('symptomRedEyes').checked = false;
            document.getElementById('healthOtherSymptoms').value = '';
            document.querySelectorAll('.symptom-checkbox').forEach(el => el.classList.remove('checked'));

            // Show Bootstrap modal
            getHealthModal().show();
        }

        function closeHealthModal() {
            if (healthModalInstance) {
                healthModalInstance.hide();
            }
        }

        function submitHealthData() {
            const studentId = document.getElementById('healthStudentIdInput').value;
            const attendanceId = document.getElementById('healthAttendanceIdInput').value;
            const temperature = document.getElementById('healthTemperature').value;
            const hasRunnyNose = document.getElementById('symptomRunnyNose').checked ? 1 : 0;
            const hasCough = document.getElementById('symptomCough').checked ? 1 : 0;
            const hasRash = document.getElementById('symptomRash').checked ? 1 : 0;
            const hasRedEyes = document.getElementById('symptomRedEyes').checked ? 1 : 0;
            const otherSymptoms = document.getElementById('healthOtherSymptoms').value;

            // ถ้ากรอกอุณหภูมิ ให้ตรวจสอบว่าค่าถูกต้อง
            if (temperature !== '') {
                const tempVal = parseFloat(temperature);
                if (isNaN(tempVal) || tempVal < 35.0 || tempVal > 42.0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'อุณหภูมิไม่ถูกต้อง',
                        text: 'กรุณากรอกอุณหภูมิระหว่าง 35.0 - 42.0 °C',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }
            }

            const saveBtn = document.getElementById('healthBtnSave');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> กำลังบันทึก...';

            fetch('../../include/attendance/attendance-submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: studentId,
                    attendance_id: attendanceId,
                    temperature: temperature,
                    has_runny_nose: hasRunnyNose,
                    has_cough: hasCough,
                    has_rash: hasRash,
                    has_red_eyes: hasRedEyes,
                    other_symptoms: otherSymptoms
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    closeHealthModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกข้อมูลมาเรียนสำเร็จ',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    updateAttendanceTable();
                    const manualStudentIdInput = document.getElementById('manualStudentId').value="";
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }
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
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> บันทึกข้อมูลสุขภาพ';
            });
        }

        // ====== Symptom Checkbox Toggle ======
        document.addEventListener('click', function(e) {
            const label = e.target.closest('.symptom-checkbox');
            if (label) {
                const checkbox = label.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    label.classList.toggle('checked', checkbox.checked);
                }
            }
        });

        // ====== Event Listeners สำหรับ Health Modal ======
        document.getElementById('healthBtnSave').addEventListener('click', submitHealthData);
        document.getElementById('healthModal').addEventListener('hidden.bs.modal', () => {
            // เมื่อปิดหน้าต่างสุขภาพ ให้กลับไปสแกนคนถัดไปทันที
            isScanning = false;
            if (scannerState === 'paused') {
                setTimeout(resumeScanner, 150);
            }
        });

        // No overlay needed with Bootstrap modal; closing handled by modal's built‑in mechanisms.

        // กด Escape เพื่อปิด modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHealthModal();
            }
        });

        function submitAttendanceData(studentData, closeScannerOnSuccess = false) {
            let scannerHeldForHealth = false;
            fetch('../../include/attendance/attendance-check.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(studentData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        if (closeScannerOnSuccess) {
                            // คง Popup และ stream ไว้ แต่พักการสแกนระหว่างกรอกข้อมูลสุขภาพ
                            scannerHeldForHealth = true;
                        }
                        updateAttendanceTable();
                        setTimeout(() => {
                            openHealthModal(data.data);
                        }, 100);

                    } else if (data.status === 'warning') {
                        // แจ้งเตือนว่าบันทึกไปแล้ว ไม่เปิด Modal
                        Swal.fire({
                            icon: 'warning',
                            title: 'บันทึกแล้ว',
                            html: `<b>${data.data.name}</b><br>
                            มีการบันทึกการเข้าเรียนในวันนี้แล้ว`,
                            confirmButtonColor: '#1e4db7',
                            confirmButtonText: 'รับทราบ'
                        });

                    } else {
                        throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึก');
                    }
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
                        if (!scannerHeldForHealth && scannerState === 'paused') {
                            resumeScanner();
                        }
                    }, 500);
                });
        }

        function handleManualAttendanceSubmit() {
            const studentId = manualStudentIdInput.value.trim();

            if (!studentId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกเลขประจำตัวนักเรียน',
                    timer: 1500,
                    showConfirmButton: false
                });
                manualStudentIdInput.focus();
                return;
            }

            if (isScanning) return;

            isScanning = true;
            lastScannedData = studentId;

            submitAttendanceData({
                student_id: studentId,
                manual: true
            });
        }

        manualAttendanceBtn.addEventListener('click', handleManualAttendanceSubmit);
        manualStudentIdInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleManualAttendanceSubmit();
            }
        });

        // ====== Scan QR Code Handler ======
        const onScanSuccess = (decodedText, decodedResult) => {
            if (isScanning) return;

            isScanning = true;
            lastScannedData = decodedText;
            pauseScanner();

            try {
                const studentData = JSON.parse(decodedText);

                submitAttendanceData(studentData, true);

            } catch (error) {
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
                    resumeScanner();
                }, 1500);
            }
        };

        // ฟังก์ชันอัพเดทตารางแสดงผล
        function updateAttendanceTable() {
            fetch('../../include/attendance/get-attendance.php')
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = '';

                    if (data.message) {
                        tableBody.innerHTML = `<tr><td colspan="6" class="text-center">${data.message}</td></tr>`;
                        return;
                    }

                    data.forEach((record, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${record.student_id}</td>
                            <td>${record.prefix_th} ${record.firstname_th} ${record.lastname_th}</td>
                            <td>${record.classroom}</td>
                            <td>${record.timestamp}</td>
                            <td>
                                <span class="badge ${getBadgeClass(record.status)}">
                                    ${record.status}
                                </span>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                });
        }

        // ฟังก์ชันกำหนดสีของ badge
        function getBadgeClass(status) {
            switch (status) {
                case 'มาเรียน':
                    return 'bg-success';
                case 'มาสาย':
                    return 'bg-warning';
                case 'ขาดเรียน':
                    return 'bg-danger';
                default:
                    return 'bg-secondary';
            }
        }

        // ===== QR Scanner Lifecycle =====
        const html5QrCode = new Html5Qrcode('reader');
        const readerElem = document.getElementById('reader');
        const switchScannerCamera = document.getElementById('switchScannerCamera');
        const scannerCameraSelect = document.getElementById('scannerCameraSelect');
        const scannerStatus = document.getElementById('scannerStatus');
        const scannerError = document.getElementById('scannerError');

        let scannerState = 'idle';
        let scannerFacingMode = 'environment';
        let scannerTrack = null;
        let scannerGeneration = 0;
        let currentZoom = 1;
        let scannerCameras = [];
        let scannerCameraIndex = null;

        const zoomStatusMsg = document.getElementById('zoomStatusMsg');
        const zoomInnerControls = document.getElementById('zoomInnerControls');
        const zoomSlider = document.getElementById('zoomSlider');
        const zoomValueDisplay = document.getElementById('zoomValueDisplay');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');

        function setScannerStatus(message, type = 'muted') {
            scannerStatus.className = 'small text-' + type;
            scannerStatus.textContent = message;
        }

        function calculateQrBoxSize() {
            const containerWidth = readerElem.offsetWidth || 320;
            const viewportWidth = window.innerWidth;
            const ratio = viewportWidth <= 480 ? 0.75 : viewportWidth <= 768 ? 0.65 : 0.5;
            return Math.max(160, Math.min(containerWidth * ratio, 250));
        }

        function getScannerConfig() {
            const qrBoxSize = calculateQrBoxSize();
            return { fps: 15, qrbox: { width: qrBoxSize, height: qrBoxSize } };
        }

        async function getScannerCameraConfig() {
            if (typeof Html5Qrcode.getCameras !== 'function') {
                return { facingMode: scannerFacingMode };
            }

            try {
                scannerCameras = await Html5Qrcode.getCameras();
            } catch (error) {
                // บางเบราว์เซอร์ไม่ยอมให้ enumerate กล้องก่อน แต่ยังเปิดด้วย facingMode ได้
                console.warn('ไม่สามารถค้นหารายการกล้อง ใช้ facingMode แทน:', error);
                return { facingMode: scannerFacingMode };
            }
            if (!scannerCameras.length) {
                throw new Error('ไม่พบกล้องในอุปกรณ์นี้');
            }

            if (scannerCameraIndex === null || scannerCameraIndex >= scannerCameras.length) {
                const keyword = scannerFacingMode === 'environment'
                    ? /back|rear|environment|หลัง|หลัง/i
                    : /front|user|หน้า/i;
                const preferredIndex = scannerCameras.findIndex(camera => keyword.test(camera.label || ''));
                scannerCameraIndex = preferredIndex >= 0
                    ? preferredIndex
                    : (scannerFacingMode === 'environment' ? scannerCameras.length - 1 : 0);
            }

            renderScannerCameraList();

            return { deviceId: { exact: scannerCameras[scannerCameraIndex].id } };
        }

        function renderScannerCameraList() {
            if (!scannerCameraSelect) return;

            scannerCameraSelect.innerHTML = '';
            scannerCameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = camera.label || `กล้อง ${index + 1}`;
                scannerCameraSelect.appendChild(option);
            });
            scannerCameraSelect.value = String(scannerCameraIndex === null ? 0 : scannerCameraIndex);
            scannerCameraSelect.disabled = scannerCameras.length <= 1;
        }

        function resetZoomControls(message = 'กำลังตรวจสอบกล้อง...') {
            zoomStatusMsg.style.display = 'inline';
            zoomStatusMsg.innerHTML = '<i class="bi bi-search"></i> ' + message;
            zoomInnerControls.style.display = 'none';
            zoomInBtn.disabled = true;
            zoomOutBtn.disabled = true;
            currentZoom = 1;
            zoomValueDisplay.textContent = '1.0x';
        }

        function updateZoomDisplay(value) {
            zoomValueDisplay.textContent = Number(value).toFixed(1) + 'x';
        }

        function updateZoomButtons(value) {
            const current = Number(value);
            zoomOutBtn.disabled = current <= Number(zoomSlider.min);
            zoomInBtn.disabled = current >= Number(zoomSlider.max);
        }

        function initZoomControl() {
            const videoElem = document.querySelector('#reader video');
            const stream = videoElem && videoElem.srcObject;
            const tracks = stream && typeof stream.getVideoTracks === 'function'
                ? stream.getVideoTracks()
                : [];
            scannerTrack = tracks[0] || null;
            if (!scannerTrack) {
                resetZoomControls('ยังไม่ได้รับสัญญาณกล้อง');
                return;
            }

            try {
                const capabilities = scannerTrack.getCapabilities ? scannerTrack.getCapabilities() : {};
                const zoom = capabilities.zoom;
                if (!zoom || Number(zoom.max) <= Number(zoom.min)) {
                    resetZoomControls('กล้องนี้ไม่รองรับการซูม');
                    return;
                }

                const settings = scannerTrack.getSettings ? scannerTrack.getSettings() : {};
                zoomSlider.min = zoom.min;
                zoomSlider.max = zoom.max;
                zoomSlider.step = zoom.step || 0.1;
                currentZoom = settings.zoom || zoom.min;
                zoomSlider.value = currentZoom;
                updateZoomDisplay(currentZoom);
                updateZoomButtons(currentZoom);
                zoomStatusMsg.style.display = 'none';
                zoomInnerControls.style.display = 'flex';
            } catch (error) {
                console.warn('ไม่สามารถตรวจสอบการซูมได้:', error);
                resetZoomControls('ไม่สามารถตรวจสอบการซูมได้');
            }
        }

        async function applyZoom(value) {
            if (!scannerTrack || !scannerTrack.applyConstraints) return;
            try {
                await scannerTrack.applyConstraints({ advanced: [{ zoom: Number(value) }] });
                currentZoom = Number(value);
                updateZoomDisplay(currentZoom);
                updateZoomButtons(currentZoom);
            } catch (error) {
                console.warn('ไม่สามารถปรับซูมได้:', error);
            }
        }

        // ผูก event ของ zoom เพียงครั้งเดียว ไม่ผูกซ้ำตอน restart กล้อง
        zoomSlider.addEventListener('input', () => updateZoomDisplay(zoomSlider.value));
        zoomSlider.addEventListener('change', () => applyZoom(zoomSlider.value));
        zoomInBtn.addEventListener('click', () => {
            const value = Math.min(Number(zoomSlider.max), currentZoom + Number(zoomSlider.step || 0.1));
            zoomSlider.value = value;
            applyZoom(value);
        });
        zoomOutBtn.addEventListener('click', () => {
            const value = Math.max(Number(zoomSlider.min), currentZoom - Number(zoomSlider.step || 0.1));
            zoomSlider.value = value;
            applyZoom(value);
        });

        async function startScanner() {
            if (scannerState === 'starting' || scannerState === 'running' || scannerState === 'paused') return;

            scannerState = 'starting';
            const generation = ++scannerGeneration;
            scannerError.style.display = 'none';
            resetZoomControls();
            setScannerStatus('กำลังเปิดกล้อง...', 'info');
            switchScannerCamera.disabled = true;

            try {
                await new Promise(resolve => requestAnimationFrame(resolve));
                if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('การใช้กล้องต้องเปิดผ่าน HTTPS หรือ localhost');
                }
                const cameraConfig = await getScannerCameraConfig();
                await html5QrCode.start(cameraConfig, getScannerConfig(), onScanSuccess);

                // ถ้าผู้ใช้ปิด Modal ระหว่างที่กำลังเปิดกล้อง ให้ปิด stream ทันที
                if (generation !== scannerGeneration) {
                    await html5QrCode.stop().catch(() => {});
                    scannerState = 'idle';
                    return;
                }

                scannerState = 'running';
                setScannerStatus('กล้องกำลังทำงาน', 'success');
                switchScannerCamera.disabled = false;
                initZoomControl();
            } catch (error) {
                scannerState = 'idle';
                setScannerStatus('เปิดกล้องไม่สำเร็จ', 'danger');
                const reason = error && error.message ? ` (${error.message})` : '';
                scannerError.textContent = window.isSecureContext
                    ? 'ไม่สามารถเปิดกล้องได้ กรุณาอนุญาตสิทธิ์กล้องในเบราว์เซอร์' + reason
                    : 'การใช้กล้องต้องเปิดผ่าน HTTPS หรือ localhost' + reason;
                scannerError.style.display = 'block';
                resetZoomControls('ไม่พบกล้องหรือไม่ได้รับอนุญาต');
                console.error('Error starting QR scanner:', error);
            }
        }

        async function stopScanner() {
            if (scannerState === 'idle' || scannerState === 'stopping') return;

            scannerState = 'stopping';
            ++scannerGeneration;
            switchScannerCamera.disabled = true;
            try {
                if (html5QrCode) await html5QrCode.stop();
            } catch (error) {
                console.warn('หยุดกล้อง:', error);
            }
            try {
                if (html5QrCode) html5QrCode.clear();
            } catch (error) {
                console.warn('ล้างตัวสแกน:', error);
            }
            scannerTrack = null;
            scannerState = 'idle';
            resetZoomControls('กล้องหยุดทำงานแล้ว');
            setScannerStatus('กล้องยังไม่ทำงาน', 'muted');
        }

        function pauseScanner() {
            if (scannerState !== 'running') return;
            try {
                if (!html5QrCode || typeof html5QrCode.pause !== 'function') return;
                html5QrCode.pause(true);
                scannerState = 'paused';
            } catch (error) {
                console.warn('พักการสแกน:', error);
            }
        }

        function resumeScanner() {
            if (scannerState !== 'paused') return;
            try {
                if (!html5QrCode || typeof html5QrCode.resume !== 'function') return;
                html5QrCode.resume();
                scannerState = 'running';
            } catch (error) {
                console.warn('เริ่มการสแกนต่อ:', error);
            }
        }

        async function switchScannerCameraMode() {
            if (scannerState !== 'running' && scannerState !== 'paused') return;
            const previousMode = scannerFacingMode;
            const previousIndex = scannerCameraIndex;
            scannerFacingMode = previousMode === 'environment' ? 'user' : 'environment';
            if (scannerCameras.length > 1) {
                scannerCameraIndex = previousIndex === null
                    ? 0
                    : (previousIndex + 1) % scannerCameras.length;
            } else {
                scannerCameraIndex = null;
            }
            await stopScanner();
            await startScanner();
            if (scannerState === 'idle') {
                scannerFacingMode = previousMode;
                scannerCameraIndex = previousIndex;
                await startScanner();
            }
        }

        switchScannerCamera.addEventListener('click', switchScannerCameraMode);
        scannerCameraSelect.addEventListener('change', async () => {
            const selectedIndex = Number(scannerCameraSelect.value);
            if (!Number.isInteger(selectedIndex) || !scannerCameras[selectedIndex]) return;
            if (selectedIndex === scannerCameraIndex) return;

            scannerCameraIndex = selectedIndex;
            await stopScanner();
            await startScanner();
        });
        // เริ่มกล้องครั้งเดียวบนหน้า และไม่ restart เมื่อ scroll หรือ resize
        startScanner();
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && scannerState !== 'idle') {
                stopScanner();
            } else if (!document.hidden && scannerState === 'idle') {
                startScanner();
            }
        });
        window.addEventListener('pagehide', () => stopScanner());
        

        // อัพเดทตารางเมื่อโหลดหน้าเว็บ
        document.addEventListener('DOMContentLoaded', () => {
            updateAttendanceTable();
            // อัพเดททุก 30 วินาที
            setInterval(updateAttendanceTable, 30000);
        });
    </script>
</body>

</html>
