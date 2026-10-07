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

    .nickname-text {
        display: inline-block;
        background: #1e4db7;
        color: #ffffff;
        font-weight: 700;
        padding: 3px 12px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* มือถือ: ล็อกคอลัมน์ชื่อเล่นไว้ด้านซ้ายตอนเลื่อนตารางไปทางขวา */
    @media (max-width: 768px) {
        table th.nick-col,
        table td.nick-col {
            position: sticky;
            left: 0;
        }

        table td.nick-col {
            z-index: 1;
            background-color: #fff;
        }

        table th.nick-col {
            z-index: 3;
        }

        /* เงาจางๆ ขอบขวา บอกว่าคอลัมน์นี้ถูกล็อก (ไม่ทับสไตล์เดิมของเซลล์) */
        table th.nick-col::after,
        table td.nick-col::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            right: -6px;
            width: 6px;
            pointer-events: none;
            background: linear-gradient(to right, rgba(15, 36, 96, 0.12), transparent);
        }

        /* เงาด้านซ้าย */
        table th.nick-col::before,
        table td.nick-col::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: -6px;
            width: 6px;
            pointer-events: none;
            background: linear-gradient(to left, rgba(15, 36, 96, 0.12), transparent);
        }
    }

    /* Sticky table header while scrolling */
    .sticky-head thead th {
        position: sticky;
        top: 0;
        z-index: 2;
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
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 16px rgba(15, 36, 96, 0.1);
    border-left: 5px solid #1e4db7;
    position: relative;
    overflow: hidden;
  }

  .health-student-card .student-head {
    display: flex;
    align-items: center;
    gap: 1.1rem;
  }

  .health-student-card .info small {
    gap: 6px;
  }

  .health-student-card .avatar {
    width: 96px;
    height: 96px;
    border-radius: 18px;
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: #fff;
    font-size: 1.3rem;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 6px 16px rgba(30, 77, 183, 0.35);
  }

  .health-student-card .avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .health-student-card .info {
    min-width: 0;
    flex: 1;
  }

  .health-student-card .info .nickname {
    font-size: clamp(1.6rem, 7vw, 2.1rem);
    font-weight: 800;
    line-height: 1.15;
    color: #0f2460;
    margin: 0 0 2px;
    white-space: nowrap;
  }

  .health-student-card .info h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 500;
    color: #64748b;
  }

  @media (max-width: 576px) {
    #healthModal .modal-body {
      padding: 1rem;
    }

    .health-student-card {
      padding: 0.9rem 1rem;
      gap: 0.75rem;
    }

    .health-student-card .student-head {
      gap: 0.8rem;
    }

    .health-student-card .avatar {
      width: 80px;
      height: 80px;
    }
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

  .health-student-card .info .badge-pill.late {
    background: #fff7e0;
    color: #b45309;
    border-color: #fcd34d;
  }

  .health-student-card .info .badge-pill.ontime {
    background: #ecfdf3;
    color: #15803d;
    border-color: #86efac;
  }

  .health-history-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .health-history-list:empty {
    display: none;
  }

  .health-history-list:not(:empty) {
    border-top: 1px dashed #dbe3f0;
    padding-top: 0.9rem;
  }

  .health-history-item {
    font-size: 0.78rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .health-history-item.disease {
    background: #eff3ff;
    color: #1e4db7;
  }

  .health-allergy-alert {
    margin-top: 4px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 8px 12px;
  }

  .health-allergy-alert-title {
    font-size: 0.76rem;
    font-weight: 700;
    color: #b91c1c;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
  }

  .health-allergy-alert-item {
    font-size: 0.78rem;
    font-weight: 600;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 2px 0;
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

  .temp-quick {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-top: 0.6rem;
  }
  .btn-temp-normal {
    border: 2px solid #1e4db7;
    background: #eff3ff;
    color: #0f2460;
    font-weight: 700;
    font-size: 1.05rem;
    border-radius: 12px;
    padding: 0.55rem 0.5rem;
    transition: all 0.2s ease;
  }
  .btn-temp-normal.active { background: #1e4db7; color: #fff; }

  /* ===== Section status (สีตามสถานะการกรอก) ===== */
  #healthModal .form-card[data-sec] {
    border-left: 6px solid #cbd5e1;
    transition: border-color 0.25s ease, background-color 0.25s ease;
  }
  #healthModal .form-card[data-state="required"],
  #healthModal .form-card[data-state="todo"]     { border-left-color: #f59e0b; background: #fffbeb; }
  #healthModal .form-card[data-state="done"]     { border-left-color: #16a34a; }
  #healthModal .form-card[data-state="none"]     { border-left-color: #cbd5e1; }
  .sec-chip {
    margin-left: auto;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: none;
    padding: 2px 10px;
    border-radius: 999px;
    white-space: nowrap;
  }
  .sec-chip i { color: inherit; font-size: 0.8rem; }
  [data-state="required"] .sec-chip,
  [data-state="todo"] .sec-chip     { background: #fef3c7; color: #b45309; }
  [data-state="required"] .sec-chip { background: #fde68a; }
  [data-state="done"] .sec-chip     { background: #dcfce7; color: #15803d; }
  [data-state="none"] .sec-chip     { background: #f1f5f9; color: #64748b; }
  @keyframes secFlash {
    0%, 100% { box-shadow: 0 2px 10px rgba(15, 36, 96, 0.07); }
    50% { box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.45); }
  }
  .sec-flash { animation: secFlash 0.5s ease 3; }

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
    grid-template-columns: repeat(4, 1fr);
    gap: 0.6rem;
  }

  @media (max-width: 576px) {
    .symptoms-grid {
      grid-template-columns: repeat(2, 1fr);
    }
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

  /* ===== Symptom sub-options & care ===== */
  .symptoms-grid {
    align-items: start;
  }

  /* อีโมจิเป็นสีเต็ม จึงคงพื้นอ่อนไว้แม้ติ๊กแล้ว */
  .symptom-item .symptom-icon.emoji,
  .symptom-checkbox input:checked + .symptom-item .symptom-icon.emoji {
    background: #eff3ff;
    box-shadow: none;
    font-size: 1.25rem;
    line-height: 1;
    font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif;
  }

  .symptom-checkbox input:checked + .symptom-item .symptom-icon.emoji {
    background: #ffffff;
    outline: 2px solid #1e4db7;
  }

  .symptom-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
  }

  .symptom-group .symptom-checkbox {
    display: block;
  }

  .sub-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    padding: 0 0.2rem;
  }

  .sub-options[hidden] {
    display: none;
  }

  .sub-chip {
    position: relative;
    margin: 0;
    cursor: pointer;
  }

  .sub-chip input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .sub-chip span {
    display: inline-block;
    padding: 0.2rem 0.75rem;
    border-radius: 20px;
    border: 2px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 600;
    transition: all 0.2s ease;
    user-select: none;
  }

  .sub-chip input:checked + span {
    border-color: #1e4db7;
    background: linear-gradient(135deg, #0f2460, #1e4db7);
    color: #ffffff;
  }

  .sub-chip input:focus-visible + span {
    box-shadow: 0 0 0 3px rgba(30, 77, 183, 0.25);
  }

  .care-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.6rem;
  }

  @media (max-width: 576px) {
    .care-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  #healthCareOther,
  #healthCaretakerName {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    padding: 0.65rem 1rem;
    font-size: 0.9rem;
    color: #334155;
    background: #f8faff;
    box-shadow: none;
  }

  #healthCareOther:focus,
  #healthCaretakerName:focus {
    border-color: #1e4db7;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
    outline: none;
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

  /* ===== Guardian Cards ===== */
  #healthModal .guardian-grid {
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
    width: 96px;
    max-width: 100%;
    height: auto;
    aspect-ratio: 1 / 1;
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
    font-size: 0.95rem;
    color: #0f2460;
    font-weight: 600;
    line-height: 1.3;
    margin-top: -2px;
    word-break: break-word;
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

  #healthModal .guardian-card-inner .check-mark i,
  #healthModal .other-card-inner .check-mark i { display: block; line-height: 1; }
  #healthModal .guardian-card-inner .check-mark i::before,
  #healthModal .other-card-inner .check-mark i::before { display: block; vertical-align: 0; line-height: 1; }

  @media (max-width: 576px) {
    #healthModal .guardian-grid { grid-template-columns: 1fr; gap: 0.5rem; }
    #healthModal .guardian-card-inner {
      display: grid;
      grid-template-columns: 84px 1fr 20px;
      grid-template-areas: "img label check" "img name check";
      column-gap: 0.75rem;
      row-gap: 0;
      align-items: center;
      padding: 0.5rem 0.75rem;
      text-align: left;
    }
    #healthModal .guardian-card-inner .g-avatar { grid-area: img; width: 84px; }
    #healthModal .guardian-card-inner .g-label { grid-area: label; font-size: 1.15rem; font-weight: 700; color: #0f2460; align-self: end; }
    #healthModal .guardian-card-inner .g-name { grid-area: name; font-size: 1rem; font-weight: 600; color: #334155; margin-top: 0; align-self: start; }
    #healthModal .guardian-card-inner .check-mark { grid-area: check; margin-top: 0; align-self: center; justify-self: center; }
    #healthModal .guardian-card-inner:hover { transform: none; }
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

                <!-- กล้องพร้อมกรอบ -->
                <div class="scanner-inline-panel">
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
                <div class="table-responsive sticky-head" style="width:100% ; max-height: 400px; overflow: scroll; ">
                    <table class="table table-striped" >
                        <thead>
                            <tr class="table-primary">
                                <th>ลำดับ</th>
                                <th>รหัสนักเรียน</th>
                                <th class="nick-col">ชื่อเล่น</th>
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
                                            <td class="nick-col" style="white-space:nowrap;"><span class="nickname-text"><?php echo htmlspecialchars($record['nickname'] ?? ''); ?></span></td>
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
                                        <td colspan="7">ไม่มีข้อมูลการเช็คชื่อในวันนี้</td>
                                    </tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">ไม่มีข้อมูลการเช็คชื่อในวันนี้</td>
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
                                <div class="table-responsive sticky-head" style="max-height:350px; overflow-y:auto; overflow-x:auto; -webkit-overflow-scrolling:touch;">
                                    <table class="table table-striped" style="min-width:760px; white-space:nowrap;">
                                        <thead>
                                            <tr class="table-primary">
                                                <th>รหัสประจำตัว</th>
                                                <th class="nick-col">ชื่อเล่น</th>
                                                <th>ชื่อ</th>
                                                <th>นามสกุล</th>
                                                <th>ห้องเรียน</th>
                                                <th>สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($classroomData['children'])): ?>
                                                <?php foreach ($classroomData['children'] as $child): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($child['studentid']) ?></td>
                                                        <td class="nick-col"><span class="nickname-text"><?= htmlspecialchars($child['nickname']) ?></span></td>
                                                        <td><?= htmlspecialchars($child['prefix_th']) ?> <?= htmlspecialchars($child['firstname_th']) ?></td>
                                                        <td><?= htmlspecialchars($child['lastname_th']) ?></td>
                                                        <td><span class="badge bg-info"><?= htmlspecialchars($child['classroom']) ?></span></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = $child['status'] === 'มาเรียน' ? 'bg-success'
                                                                : ($child['status'] === 'มาสาย' ? 'bg-warning text-dark' : 'bg-danger');
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

        <!-- Header -->
        <div class="modal-header">
            <h5 class="modal-title" id="healthModalLabel">
            <div class="title-icon-wrap">
                <i class="bi bi-heart-fill"></i>
            </div>
            <div>
                บันทึกข้อมูลส่งเด็ก
                <div class="header-subtitle">Health Record System</div>
            </div>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">

            <!-- Student Info -->
            <div class="health-student-card" id="healthStudentInfo">
            <div class="student-head">
                <div class="avatar" id="healthStudentAvatar">-</div>
                <div class="info">
                    <div class="nickname" id="healthStudentNickname" style="display:none;"></div>
                    <h4 id="healthStudentName">ชื่อ นักเรียน</h4>
                    <small id="healthStudentDetail">
                    <i class="bi bi-person-badge"></i>
                    <span id="healthStudentId" class="badge-pill">-</span>
                    <i class="bi bi-door-open ms-1"></i>
                    <span id="healthStudentClassroom" class="badge-pill">-</span>
                    <span id="healthLateBadge" class="badge-pill"></span>
                    </small>
                </div>
            </div>
            <div id="healthHistoryList" class="health-history-list"></div>
            </div>

            <!-- Form -->
            <form id="healthForm" onsubmit="return false;">
            <input type="hidden" id="healthStudentIdInput" value="">
            <input type="hidden" id="healthAttendanceIdInput" value="">

            <!-- Drop-off person -->
            <div data-sec="dropoff" class="form-card">
                <div class="section-label">
                <i class="bi bi-person-heart"></i> ผู้มาส่งเด็ก
                </div>
                <div class="guardian-grid mb-3">
                    <label class="guardian-select-card">
                    <input type="radio" name="dropoff" value="father">
                    <div class="guardian-card-inner">
                        <img id="dropFatherImg" src="" alt="รูปพ่อ" class="g-avatar" onerror="this.src=DROP_DEFAULT_AVATAR">
                        <span class="g-label">พ่อ</span>
                        <span class="g-name" id="dropFatherName">-</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                    </label>
                    <label class="guardian-select-card">
                    <input type="radio" name="dropoff" value="mother">
                    <div class="guardian-card-inner">
                        <img id="dropMotherImg" src="" alt="รูปแม่" class="g-avatar" onerror="this.src=DROP_DEFAULT_AVATAR">
                        <span class="g-label">แม่</span>
                        <span class="g-name" id="dropMotherName">-</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                    </label>
                    <label class="guardian-select-card">
                    <input type="radio" name="dropoff" value="relative">
                    <div class="guardian-card-inner">
                        <img id="dropRelativeImg" src="" alt="รูปผู้ปกครอง/ผู้ดูแล" class="g-avatar" onerror="this.src=DROP_DEFAULT_AVATAR">
                        <span class="g-label">ผู้ปกครอง/ผู้ดูแล</span>
                        <span class="g-name" id="dropRelativeName">-</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                    </label>
                </div>
                <label class="guardian-select-card w-100">
                    <input type="radio" name="dropoff" value="other">
                    <div class="other-card-inner">
                    <div class="other-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <span class="other-text">อื่นๆ (โปรดระบุ)</span>
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>
                <input type="text" class="form-control mt-3" id="dropOffDetail" maxlength="200"
                    placeholder="ระบุชื่อ-นามสกุลและความสัมพันธ์ของผู้มาส่ง" style="display:none;">
            </div>

            <!-- Temperature -->
            <div data-sec="temp" class="form-card">
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
                <div class="temp-quick" id="healthTempQuick">
                <button type="button" class="btn-temp-normal" data-temp="36.5">36.5</button>
                <button type="button" class="btn-temp-normal" data-temp="36.8">36.8</button>
                <button type="button" class="btn-temp-normal" data-temp="37.2">37.2</button>
                </div>
            </div>

            <!-- Symptoms -->
            <div data-sec="symptoms" class="form-card">
                <div class="section-label">
                <i class="bi bi-exclamation-triangle"></i> อาการผิดปกติ
                <span class="ms-1 text-muted fw-normal"
                    style="text-transform: none; letter-spacing: 0; font-size: 0.76rem;">
                    (เลือกได้มากกว่า 1)
                </span>
                </div>
                <div class="symptoms-grid" id="symptomsGrid"></div>
            </div>

            <!-- Other Symptoms -->
            <div data-sec="other" class="form-card">
                <div class="section-label">
                <i class="bi bi-pencil-square"></i> อาการอื่นๆ
                </div>
                <textarea
                class="form-control"
                id="healthOtherSymptoms"
                placeholder="เช่น ปวดหัว, คลื่นไส้, ท้องเสีย, ฯลฯ"
                ></textarea>
            </div>

            <!-- Care / Help -->
            <div data-sec="care" class="form-card">
                <div class="section-label">
                <i class="bi bi-bandaid"></i> การดูแล/ช่วยเหลือ
                <span class="ms-1 text-muted fw-normal"
                    style="text-transform: none; letter-spacing: 0; font-size: 0.76rem;">
                    (เลือกได้มากกว่า 1)
                </span>
                </div>
                <div class="care-grid" id="careGrid"></div>
                <input type="text" class="form-control mt-3" id="healthCareOther" maxlength="200"
                    placeholder="ระบุการดูแล/ช่วยเหลืออื่นๆ" style="display:none;">
            </div>

            <!-- Caretaker -->
            <div data-sec="caretaker" class="form-card mb-0">
                <div class="section-label">
                <i class="bi bi-person-check"></i> ผู้ดูแลชื่อ
                </div>
                <input type="text" class="form-control" id="healthCaretakerName" maxlength="150"
                    placeholder="ชื่อผู้ดูแลที่ให้การดูแล/ช่วยเหลือ" autocomplete="off">
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

        <?php include __DIR__ . '/../partials/health_options.js.php'; ?>

        // ชื่อ/ไอคอนมาจากหน้าตั้งค่า จึงต้อง escape ก่อนใส่ลง HTML
        const escHtml = (str) => String(str ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        function renderHealthOptions() {
            const grid = document.getElementById('symptomsGrid');
            grid.innerHTML = SYMPTOM_OPTIONS.length === 0
                ? '<div class="text-muted small">ยังไม่มีตัวเลือกอาการ (ตั้งค่าได้ที่หน้า "ตั้งค่าการเช็คชื่อ")</div>'
                : SYMPTOM_OPTIONS.map(s => `
                <div class="symptom-group">
                    <label class="symptom-checkbox">
                        <input type="checkbox" data-symptom="${escHtml(s.code)}">
                        <div class="symptom-item">
                            <div class="symptom-icon emoji" aria-hidden="true">${escHtml(s.icon) || '🔹'}</div>
                            <span class="symptom-text">${escHtml(s.label)}</span>
                            <div class="check-mark"><i class="bi bi-check"></i></div>
                        </div>
                    </label>
                    ${s.subs && s.subs.length ? `<div class="sub-options" data-sub-of="${escHtml(s.code)}" hidden>
                        ${s.subs.map(sub => `<label class="sub-chip"><input type="checkbox" data-sub-of="${escHtml(s.code)}" value="${escHtml(sub.code)}"><span>${escHtml(sub.label)}</span></label>`).join('')}
                    </div>` : ''}
                </div>
            `).join('');

            document.getElementById('careGrid').innerHTML = CARE_OPTIONS.length === 0
                ? '<div class="text-muted small">ยังไม่มีตัวเลือกการดูแล</div>'
                : CARE_OPTIONS.map(c => `
                <label class="symptom-checkbox">
                    <input type="checkbox" data-care="${escHtml(c.code)}" data-allows-text="${c.allowsText ? 1 : 0}">
                    <div class="symptom-item">
                        <div class="symptom-icon emoji" aria-hidden="true">${escHtml(c.icon) || '🔹'}</div>
                        <span class="symptom-text">${escHtml(c.label)}</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>
            `).join('');
        }

        // ตัวเลือกการดูแลที่ถูกติ๊กอยู่ มีตัวที่เปิดให้พิมพ์ข้อความเพิ่ม (เช่น "อื่นๆ") หรือไม่
        function careNeedsText() {
            return !!document.querySelector('input[data-care][data-allows-text="1"]:checked');
        }

        function syncSubOptions(code) {
            const parent = document.querySelector(`input[data-symptom="${code}"]`);
            const subs = document.querySelector(`.sub-options[data-sub-of="${code}"]`);
            if (!parent || !subs) return;
            subs.hidden = !parent.checked;
            if (!parent.checked) {
                subs.querySelectorAll('input').forEach(i => i.checked = false);
            }
        }

        function collectHealthChoices() {
            const symptoms = {};
            document.querySelectorAll('input[data-symptom]:checked').forEach(el => {
                const code = el.dataset.symptom;
                symptoms[code] = Array.from(
                    document.querySelectorAll(`input[data-sub-of="${code}"]:checked`)
                ).map(i => i.value);
            });
            const careActions = Array.from(document.querySelectorAll('input[data-care]:checked'))
                .map(el => el.dataset.care);
            return { symptoms, careActions };
        }

        const DROP_DEFAULT_AVATAR = '../../../public/assets/images/avatar.png';
        const CARETAKER_STORAGE_KEY = 'healthCaretakerName';
        function loadCaretakerName() {
            try { return localStorage.getItem(CARETAKER_STORAGE_KEY) || ''; } catch (e) { return ''; }
        }
        function saveCaretakerName(name) {
            try { localStorage.setItem(CARETAKER_STORAGE_KEY, name); } catch (e) {}
        }

        renderHealthOptions();

        document.getElementById('healthModal').addEventListener('change', (e) => {
            const el = e.target;
            if (el.name === 'dropoff') {
                const detailEl = document.getElementById('dropOffDetail');
                detailEl.style.display = el.value === 'other' ? '' : 'none';
                if (el.value === 'other') detailEl.focus(); else detailEl.value = '';
                return;
            }
            if (el.dataset.symptom) {
                syncSubOptions(el.dataset.symptom);
            } else if (el.dataset.care !== undefined && el.dataset.allowsText === '1') {
                const otherInput = document.getElementById('healthCareOther');
                const need = careNeedsText();
                otherInput.style.display = need ? '' : 'none';
                if (need) otherInput.focus(); else otherInput.value = '';
            }
        });

        // Bootstrap modal instance (lazy init)
        let healthModalInstance = null;
        let currentScanTime = ''; // เวลา (HH:MM) ที่สแกนเด็กคนนี้ ใช้ตัดสินมาสาย
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
            const nicknameEl = document.getElementById('healthStudentNickname');
            const nickname = (studentData.nickname || '').trim();
            nicknameEl.textContent = nickname ? 'น้อง' + nickname : '';
            nicknameEl.style.display = nickname ? '' : 'none';
            document.getElementById('healthStudentId').textContent = studentData.student_id;
            document.getElementById('healthStudentClassroom').textContent = studentData.classroom || '-';

            // สถานะมาสาย คำนวณจากเวลาที่สแกน เทียบกับเวลาตัดสายที่ admin ตั้งไว้
            currentScanTime = studentData.time || '';
            const lateBadge = document.getElementById('healthLateBadge');
            const isLateNow = studentData.attendance_status === 'late';
            lateBadge.textContent = isLateNow
                ? `⏰ มาสาย (หลัง ${CHECKIN_LATE_TIME} น.)`
                : `✅ ตรงเวลา (ก่อน ${CHECKIN_LATE_TIME} น.)`;
            lateBadge.className = 'badge-pill ' + (isLateNow ? 'late' : 'ontime');

            const isEmpty = (val) => !val || val === '-' || val.trim() === '';
            const avatarEl = document.getElementById('healthStudentAvatar');
            avatarEl.innerHTML = '';
            if (!isEmpty(studentData.profile_image)) {
                const img = document.createElement('img');
                img.src = studentData.profile_image;
                img.alt = 'รูปนักเรียน';
                img.onerror = () => {
                    avatarEl.innerHTML = '';
                    avatarEl.textContent = (studentData.first_name || name).charAt(0).toUpperCase();
                };
                avatarEl.appendChild(img);
            } else {
                avatarEl.textContent = (studentData.first_name || name).charAt(0).toUpperCase();
            }

            // ประวัติโรคประจำตัว / แพ้ยา / แพ้อาหาร
            const historyList = document.getElementById('healthHistoryList');
            const escapeHtml = (str) => String(str).replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));

            let historyHtml = '';

            if (!isEmpty(studentData.congenital_disease)) {
                historyHtml += `
                    <div class="health-history-item disease">
                        <i class="bi bi-file-medical-fill"></i> โรคประจำตัว: ${escapeHtml(studentData.congenital_disease)}
                    </div>
                `;
            }

            const allergyItems = [
                { value: studentData.allergic_medicine, icon: 'bi-shield-fill-exclamation', label: 'แพ้ยา' },
                { value: studentData.allergic_food, icon: 'bi-basket2-fill', label: 'แพ้อาหาร' }
            ].filter(item => !isEmpty(item.value));

            if (allergyItems.length > 0) {
                historyHtml += `
                    <div class="health-allergy-alert">
                        <div class="health-allergy-alert-title"><i class="bi bi-exclamation-triangle-fill"></i> ข้อควรระวังด้านสุขภาพ</div>
                        ${allergyItems.map(item => `
                            <div class="health-allergy-alert-item">
                                <i class="bi ${item.icon}"></i> ${item.label}: ${escapeHtml(item.value)}
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            historyList.innerHTML = historyHtml;

            // Reset form fields
            document.getElementById('healthTemperature').value = '';
            document.querySelectorAll('#healthForm input[type="checkbox"]').forEach(el => el.checked = false);
            document.querySelectorAll('.sub-options').forEach(el => el.hidden = true);
            document.getElementById('healthOtherSymptoms').value = '';
            const careOtherInput = document.getElementById('healthCareOther');
            careOtherInput.value = '';
            careOtherInput.style.display = 'none';
            document.getElementById('healthCaretakerName').value = loadCaretakerName();

            // ผู้มาส่ง: โหลดรูป/ชื่อผู้ปกครองจากข้อมูลเด็ก
            document.querySelectorAll('input[name="dropoff"]').forEach(r => r.checked = false);
            const dropDetail = document.getElementById('dropOffDetail');
            dropDetail.value = '';
            dropDetail.style.display = 'none';
            ['Father', 'Mother', 'Relative'].forEach(k => {
                document.getElementById('drop' + k + 'Img').src = DROP_DEFAULT_AVATAR;
                document.getElementById('drop' + k + 'Name').textContent = '-';
            });
            fetch(`../../include/attendance/get_student_guardians.php?student_id=${encodeURIComponent(studentData.student_id)}`)
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success') return;
                    const g = res.data;
                    const full = (f, l) => [f, l].filter(Boolean).join(' ') || '-';
                    [['Father', 'father'], ['Mother', 'mother'], ['Relative', 'relative']].forEach(([k, key]) => {
                        document.getElementById('drop' + k + 'Img').src = g[key + '_image'] || DROP_DEFAULT_AVATAR;
                        document.getElementById('drop' + k + 'Name').textContent = full(g[key + '_first_name'], g[key + '_last_name']);
                    });
                })
                .catch(err => console.error('Error loading guardians:', err));

            updateSectionStatus();

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
            const otherSymptoms = document.getElementById('healthOtherSymptoms').value;
            const { symptoms, careActions } = collectHealthChoices();
            const careOther = careNeedsText()
                ? document.getElementById('healthCareOther').value.trim()
                : '';
            const caretakerName = document.getElementById('healthCaretakerName').value.trim();
            const dropSel = document.querySelector('input[name="dropoff"]:checked');
            const droppedOffBy = dropSel ? dropSel.value : '';
            const droppedOffDetail = document.getElementById('dropOffDetail').value.trim();

            if (!droppedOffBy) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกผู้มาส่งเด็ก', confirmButtonColor: '#1e4db7', confirmButtonText: 'ตกลง' }).then(() => flashSection('dropoff'));
                return;
            }
            if (droppedOffBy === 'other' && !droppedOffDetail) {
                Swal.fire({ icon: 'warning', title: 'กรุณาระบุผู้มาส่ง', confirmButtonColor: '#1e4db7', confirmButtonText: 'ตกลง' }).then(() => { flashSection('dropoff'); document.getElementById('dropOffDetail').focus(); });
                return;
            }

            // ถ้ามีการดูแล/ช่วยเหลือ ต้องระบุชื่อผู้ดูแล
            if (careActions.length > 0 && !caretakerName) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาระบุชื่อผู้ดูแล',
                    text: 'เลือกการดูแล/ช่วยเหลือแล้ว กรุณากรอกชื่อผู้ดูแล',
                    confirmButtonColor: '#1e4db7',
                    confirmButtonText: 'ตกลง'
                }).then(() => { flashSection('caretaker'); document.getElementById('healthCaretakerName').focus(); });
                return;
            }

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
                    symptoms: symptoms,
                    other_symptoms: otherSymptoms,
                    care_actions: careActions,
                    care_other: careOther,
                    caretaker_name: caretakerName,
                    dropped_off_by: droppedOffBy,
                    dropped_off_detail: droppedOffDetail,
                    scan_time: currentScanTime
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    saveCaretakerName(caretakerName);
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

        // ====== Event Listeners สำหรับ Health Modal ======
        document.getElementById('healthBtnSave').addEventListener('click', submitHealthData);
        // ปุ่มลัดอุณหภูมิ: กดซ้ำที่ปุ่มเดิมเพื่อล้างค่า
        document.getElementById('healthTempQuick').addEventListener('click', (e) => {
            const btn = e.target.closest('[data-temp]');
            if (!btn) return;
            const input = document.getElementById('healthTemperature');
            input.value = input.value === btn.dataset.temp ? '' : btn.dataset.temp;
            updateSectionStatus();
        });

        // ===== สีและป้ายสถานะของแต่ละหัวข้อ =====
        const SEC_CHIP = {
            required: ['bi-exclamation-triangle-fill', 'ต้องกรอก'],
            todo: ['bi-exclamation-triangle-fill', 'ยังไม่กรอก'],
            done: ['bi-check-circle-fill', 'เรียบร้อย'],
            none: ['bi-dash-circle', 'ไม่มี'],
        };
        function setSectionState(sec, state, extra) {
            const card = document.querySelector(`#healthModal [data-sec="${sec}"]`);
            if (!card) return;
            card.dataset.state = state;
            let chip = card.querySelector('.sec-chip');
            if (!chip) {
                chip = document.createElement('span');
                chip.className = 'sec-chip';
                card.querySelector('.section-label').appendChild(chip);
            }
            const [icon, text] = SEC_CHIP[state];
            chip.innerHTML = `<i class="bi ${icon}"></i> ${extra || text}`;
        }
        function updateSectionStatus() {
            const { symptoms, careActions } = collectHealthChoices();
            const symCount = Object.keys(symptoms).length;
            const caretaker = document.getElementById('healthCaretakerName').value.trim();

            setSectionState('dropoff', document.querySelector('input[name="dropoff"]:checked') ? 'done' : 'required');
            setSectionState('temp', document.getElementById('healthTemperature').value !== '' ? 'done' : 'todo');
            setSectionState('symptoms', symCount ? 'done' : 'none', symCount ? `เลือก ${symCount}` : 'ไม่มีอาการ');
            setSectionState('other', document.getElementById('healthOtherSymptoms').value.trim() ? 'done' : 'none');
            setSectionState('care', careActions.length ? 'done' : 'none');
            setSectionState('caretaker', caretaker ? 'done' : (careActions.length ? 'required' : 'none'));

            document.querySelectorAll('#healthTempQuick [data-temp]').forEach(b => {
                b.classList.toggle('active', b.dataset.temp === document.getElementById('healthTemperature').value);
            });
        }
        function flashSection(sec) {
            const card = document.querySelector(`#healthModal [data-sec="${sec}"]`);
            if (!card) return;
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.remove('sec-flash');
            void card.offsetWidth;
            card.classList.add('sec-flash');
        }
        ['input', 'change'].forEach(evt =>
            document.getElementById('healthForm').addEventListener(evt, updateSectionStatus));

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
                        tableBody.innerHTML = `<tr><td colspan="7" class="text-center">${data.message}</td></tr>`;
                        return;
                    }

                    data.forEach((record, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${record.student_id}</td>
                            <td class="nick-col" style="white-space:nowrap;"><span class="nickname-text">${record.nickname || ''}</span></td>
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
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
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
        const switchScannerCamera = document.getElementById('switchScannerCamera');
        const scannerCameraSelect = document.getElementById('scannerCameraSelect');
        let scannerFacingMode = 'environment';
        let scannerCameras = [];
        let scannerCameraIndex = null;
        let scannerState = 'idle';
        let scannerRestarting = false;

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

        async function getScannerCameraConfig() {
            if (typeof Html5Qrcode.getCameras !== 'function') {
                return { facingMode: scannerFacingMode };
            }

            try {
                scannerCameras = await Html5Qrcode.getCameras();
            } catch (error) {
                console.warn('ไม่สามารถค้นหารายการกล้อง ใช้ facingMode แทน:', error);
                return { facingMode: scannerFacingMode };
            }

            if (!scannerCameras.length) {
                throw new Error('ไม่พบกล้องในอุปกรณ์นี้');
            }

            if (scannerCameraIndex === null || scannerCameraIndex >= scannerCameras.length) {
                const keyword = scannerFacingMode === 'environment'
                    ? /back|rear|environment|หลัง/i
                    : /front|user|หน้า/i;
                const preferredIndex = scannerCameras.findIndex(camera => keyword.test(camera.label || ''));
                scannerCameraIndex = preferredIndex >= 0
                    ? preferredIndex
                    : (scannerFacingMode === 'environment' ? scannerCameras.length - 1 : 0);
            }

            scannerCameraSelect.innerHTML = '';
            scannerCameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = camera.label || `กล้อง ${index + 1}`;
                scannerCameraSelect.appendChild(option);
            });
            scannerCameraSelect.value = String(scannerCameraIndex === null ? 0 : scannerCameraIndex);
            scannerCameraSelect.disabled = scannerCameras.length <= 1;
            switchScannerCamera.disabled = scannerCameras.length <= 1;

            return { deviceId: { exact: scannerCameras[scannerCameraIndex].id } };
        }

        const qrBoxSize = calculateQrBoxSize();

        const config = {
            fps: 25,
            qrbox: {
                width: qrBoxSize,
                height: qrBoxSize
            }
        };

        async function startScannerWithConfig(scannerConfig) {
            if (scannerState === 'starting' || scannerState === 'running') return;
            scannerState = 'starting';
            try {
                const cameraConfig = await getScannerCameraConfig();
                await html5QrCode.start(cameraConfig, scannerConfig, onScanSuccess);
                scannerState = 'running';
                initZoomControl();
            } catch (error) {
                scannerState = 'idle';
                console.error('Error starting QR scanner:', error);
            }
        }

        async function stopScannerForRestart() {
            if (scannerState === 'idle') return;
            scannerState = 'stopping';
            try {
                await html5QrCode.stop();
            } catch (error) {
                console.warn('หยุดกล้อง:', error);
            }
            try {
                html5QrCode.clear();
            } catch (error) {
                console.warn('ล้างตัวสแกน:', error);
            }
            scannerState = 'idle';
        }

        async function restartScanner(scannerConfig) {
            if (scannerRestarting) return;
            scannerRestarting = true;
            try {
                await stopScannerForRestart();
                await startScannerWithConfig(scannerConfig);
            } finally {
                scannerRestarting = false;
            }
        }

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

        switchScannerCamera.addEventListener('click', async () => {
            if (scannerState !== 'running' || scannerCameras.length < 2) return;
            scannerFacingMode = scannerFacingMode === 'environment' ? 'user' : 'environment';
            scannerCameraIndex = (scannerCameraIndex + 1) % scannerCameras.length;
            await restartScanner(config);
        });

        scannerCameraSelect.addEventListener('change', async () => {
            const selectedIndex = Number(scannerCameraSelect.value);
            if (!Number.isInteger(selectedIndex) || !scannerCameras[selectedIndex]) return;
            if (selectedIndex === scannerCameraIndex) return;
            scannerCameraIndex = selectedIndex;
            await restartScanner(config);
        });

        // เริ่มกล้องครั้งเดียวบนหน้า และไม่ restart เมื่อ scroll หรือ resize
        startScannerWithConfig(config);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopScannerForRestart();
            } else if (scannerState === 'idle') {
                startScannerWithConfig(config);
            }
        });
        window.addEventListener('pagehide', () => stopScannerForRestart());
        

        // อัพเดทตารางเมื่อโหลดหน้าเว็บ
        document.addEventListener('DOMContentLoaded', () => {
            updateAttendanceTable();
            // อัพเดททุก 30 วินาที
            setInterval(updateAttendanceTable, 30000);
        });
    </script>
</body>

</html>
