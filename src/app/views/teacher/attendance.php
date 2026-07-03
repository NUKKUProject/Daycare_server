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
        <div class="container-fluid mt-4">           

            <div id="scanner-container">
                <h3 class="text-center">บันทึกการเช็คชื่อมาเรียน วันที่ <?php echo date('d/m/Y'); ?></h3>
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

                <!-- กล้องพร้อมกรอบ -->

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
                        <input type="range" id="zoomSlider" min="1" max="5" step="0.1" value="2">
                        <button type="button" id="zoomInBtn" title="ซูมเข้า">+</button>
                        <span class="zoom-value" id="zoomValueDisplay">1.0x</span>
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
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
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

        // No overlay needed with Bootstrap modal; closing handled by modal's built‑in mechanisms.

        // กด Escape เพื่อปิด modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHealthModal();
            }
        });

        function submitAttendanceData(studentData) {
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
                    }, 3000);
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
                student_id: studentId
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
            if (isScanning || decodedText === lastScannedData) return;

            isScanning = true;
            lastScannedData = decodedText;

            try {
                const studentData = JSON.parse(decodedText);

                submitAttendanceData(studentData);

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

        // Start the QR scanner first. Once it has successfully started we initialise the zoom controls.
        // This prevents a race condition where `initZoomControl` runs before the video stream is ready,
        // which caused the zoom UI to be hidden intermittently.
        html5QrCode.start({
                facingMode: "environment",
            },
            config,
            onScanSuccess
        ).then(() => {
            try {
                initZoomControl();
            } catch (e) {
                console.warn('Zoom init error (non-blocking):', e);
            }
        }).catch(err => {
            console.error('Error starting QR scanner:', err);
        });

        // -----------------------------------------------------------------
        // Re‑initialize scanner on page scroll
        // -----------------------------------------------------------------
        // Some mobile browsers pause or detach the video element when the
        // page is scrolled, causing the camera feed to disappear.  We listen
        // for scroll events and, if the <video> element inside #reader is no
        // longer present, we restart the scanner with the current QR‑box
        // size and re‑apply the zoom controls.
        function ensureScannerRunning() {
            const videoElem = document.querySelector('#reader video');
            if (!videoElem) {
                // Video element missing – restart scanner
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

        // Debounce scroll handling to avoid excessive restarts.
        let scrollTimer;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                ensureScannerRunning();
            }, 200);
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
                // ดึง video element ที่ html5-qrcode สร้างไว้
                const videoElem = document.querySelector('#reader video');
                if (!videoElem) {
                    zoomStatusMsg.innerHTML = '<i class="bi bi-camera-video-off"></i> ไม่พบ Video element';
                    return;
                }

                // ดึง MediaStream จาก video element
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

                // ตรวจสอบว่ากล้องรองรับการซูมหรือไม่
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

                // กำหนดค่า slider ตามความสามารถของกล้อง
                zoomSlider.min = zoomMin;
                zoomSlider.max = zoomMax;
                zoomSlider.step = zoomStep;
                zoomSlider.value = 1;

                // อัปเดตค่า zoom จริงจากกล้อง (ถ้ามี)
                try {
                    const settings = videoTrack.getSettings();
                    if (settings.zoom) {
                        currentZoom = settings.zoom;
                        zoomSlider.value = currentZoom;
                        updateZoomDisplay(currentZoom);
                    }
                } catch (e) {
                    // ไม่เป็นไร ใช้ค่าเริ่มต้น 1
                }

                // ฟังก์ชันปรับซูม
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

                // Event listeners
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

                // อัปเดตปุ่มครั้งแรก
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
            // Stop the current scanner, then restart with the new qrbox size.
            // After restarting we must re‑initialise the zoom controls because the
            // underlying <video> element (and its MediaStreamTrack) is recreated.
            html5QrCode.stop()
                .then(() => {
                    const newConfig = {
                        fps: 25,
                        qrbox: {
                            width: newQrBoxSize,
                            height: newQrBoxSize
                        }
                    };
                    return html5QrCode.start({ facingMode: "environment" }, newConfig, onScanSuccess);
                })
                .then(() => {
                    // Re‑attach zoom controls to the new video track.
                    try {
                        initZoomControl();
                    } catch (e) {
                        console.warn('Zoom init after resize error (non‑blocking):', e);
                    }
                })
                .catch(err => {
                    console.error('Error (re)starting QR scanner:', err);
                });
        }

        // Listen for viewport size changes and adjust the QR scanner + zoom.
        // Debounce to avoid rapid restarts during continuous resize.
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                updateQrScannerSize();
            }, 300);
        });
        

        // อัพเดทตารางเมื่อโหลดหน้าเว็บ
        document.addEventListener('DOMContentLoaded', () => {
            updateAttendanceTable();
            // อัพเดททุก 30 วินาที
            setInterval(updateAttendanceTable, 30000);
        });
    </script>
</body>

</html>