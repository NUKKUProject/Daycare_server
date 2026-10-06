<?php
include __DIR__ . '/../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'student']);
include __DIR__ . '/partials/Header.php';
include __DIR__ . '/../include/auth/auth_navbar.php';
require_once '../include/function/pages_referen.php';
require_once __DIR__ . '/../include/function/child_functions.php';
$is_admin = getUserRole() === 'admin';
$is_student = getUserRole() === 'student';
$is_teacher = getUserRole() === 'teacher';
require_once __DIR__ . '/../include/auth/auth_dashboard.php';
// ใช้ user_id จาก session เป็น teacher_id
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
} else {
    die('ไม่พบข้อมูลผู้สอน. กรุณาเข้าสู่ระบบอีกครั้ง.');
}
?>

<style>
    /* ===== หน้าประวัติการมาเรียน ===== */
    .attendance-container {
        padding: 1.5rem;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    }

    .ah-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }

    .ah-header h2 {
        margin: 0;
        font-size: 1.5rem;
        color: #0f2460;
        font-weight: 800;
    }

    .ah-header .sub {
        color: #64748b;
        font-size: 0.85rem;
    }

    /* ฟอร์มค้นหา */
    .filter-card {
        background: #f8faff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem;
        margin-bottom: 1.25rem;
    }

    .filter-card .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.3rem;
    }

    .filter-card .form-control,
    .filter-card .form-select {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
    }

    .filter-card .form-control:focus,
    .filter-card .form-select:focus {
        border-color: #1e4db7;
        box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
    }

    .date-nav {
        display: flex;
        gap: 0.4rem;
    }

    /* ทุกช่องในแถวตัวกรองสูงเท่ากัน */
    .filter-card .form-control,
    .filter-card .form-select,
    .date-nav .btn {
        height: 42px;
    }

    .date-nav {
        align-items: stretch;
    }

    .date-nav input[type="date"] {
        flex: 1 1 auto;
        min-width: 0;
    }

    .date-nav .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        flex-shrink: 0;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        background: #fff;
        color: #475569;
        padding: 0 0.8rem;
        font-weight: 600;
    }

    .date-nav .btn:hover {
        border-color: #1e4db7;
        color: #1e4db7;
    }

    .search-wrap {
        position: relative;
    }

    .search-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    .search-wrap input {
        padding-left: 2.3rem;
    }

    /* การ์ดสรุปสถานะ (กดเพื่อกรอง) */
    .stat-row {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 0.6rem;
        margin-bottom: 1.25rem;
    }

    .stat-card {
        border: 2px solid #e2e8f0;
        background: #fff;
        border-radius: 14px;
        padding: 0.65rem 0.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 36, 96, 0.1);
    }

    .stat-card .num {
        display: block;
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.1;
        color: #0f2460;
    }

    .stat-card .lbl {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
    }

    .stat-card.active {
        border-color: #1e4db7;
        background: #eff3ff;
        box-shadow: 0 4px 14px rgba(30, 77, 183, 0.18);
    }

    .stat-card[data-status="present"] .num { color: #198754; }
    .stat-card[data-status="late"] .num { color: #b45309; }
    .stat-card[data-status="absent"] .num { color: #dc3545; }
    .stat-card[data-status="leave"] .num { color: #0d6efd; }
    .stat-card[data-status="none"] .num { color: #6c757d; }

    @media (max-width: 768px) {
        .stat-row {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* การ์ดแต่ละห้อง */
    .class-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 1.25rem;
        background: #fff;
    }

    .class-card .class-head {
        background: linear-gradient(135deg, #0f2460 0%, #1e4db7 100%);
        color: #fff;
        padding: 0.7rem 1rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .class-card .class-head h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    .class-card .class-head .mini {
        font-size: 0.78rem;
        opacity: 0.9;
    }

    .history-scroll {
        max-height: 520px;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }

    .history-table {
        margin: 0;
        min-width: 820px;
        white-space: nowrap;
    }

    .history-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #eff3ff;
        color: #0f2460;
        font-size: 0.82rem;
        font-weight: 700;
        border-bottom: 2px solid #c7d7f8;
        text-align: center;
    }

    .history-table td {
        vertical-align: middle;
        text-align: center;
        font-size: 0.9rem;
    }

    .history-table td.name-col {
        text-align: left;
    }

    .history-table tr.st-present td:first-child { box-shadow: inset 4px 0 0 #198754; }
    .history-table tr.st-late td:first-child { box-shadow: inset 4px 0 0 #f59e0b; }
    .history-table tr.st-absent td:first-child { box-shadow: inset 4px 0 0 #dc3545; }
    .history-table tr.st-leave td:first-child { box-shadow: inset 4px 0 0 #0d6efd; }
    .history-table tr.st-none td:first-child { box-shadow: inset 4px 0 0 #cbd5e1; }

    .nickname-text {
        display: inline-block;
        background: #1e4db7;
        color: #fff;
        font-weight: 700;
        padding: 2px 12px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* มือถือ: ล็อกคอลัมน์ชื่อเล่นไว้ด้านซ้ายตอนเลื่อนตารางไปทางขวา */
    @media (max-width: 768px) {
        /* ใช้ separate เพื่อให้เส้นขอบของเซลล์ที่ล็อกไม่หาย */
        .history-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .history-table th.nick-col,
        .history-table td.nick-col {
            position: sticky;
            left: 0;
        }

        .history-table td.nick-col {
            z-index: 1;
            background-color: #fff;
        }

        .history-table th.nick-col {
            z-index: 3;
            background-color: #eff3ff;
        }

        /* เงาจางๆ ขอบขวา บอกว่าคอลัมน์นี้ถูกล็อก (ไม่ทับสไตล์เดิมของเซลล์) */
        .history-table th.nick-col::after,
        .history-table td.nick-col::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            right: -6px;
            width: 6px;
            pointer-events: none;
            background: linear-gradient(to right, rgba(15, 36, 96, 0.12), transparent);
        }
    }

    .action-btns {
        display: inline-flex;
        gap: 0.3rem;
    }

    .action-btns .btn {
        width: 34px;
        height: 34px;
        padding: 0;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .action-btns .btn.add {
        width: auto;
        padding: 0 0.8rem;
        gap: 0.3rem;
    }

    .state-box {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #64748b;
    }

    .state-box .emoji {
        font-size: 2.4rem;
        display: block;
        margin-bottom: 0.4rem;
    }

    /* ===== Modal แก้ไข (ธีมเดียวกับหน้าเช็คชื่อ) ===== */
    #attendanceEditModal .modal-content {
        border: none;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 70px rgba(10, 30, 80, 0.2);
    }

    #attendanceEditModal .modal-header {
        background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
        border: none;
        padding: 1.5rem 2rem;
    }

    #attendanceEditModal .modal-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.15rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #attendanceEditModal .title-icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    #attendanceEditModal .header-subtitle {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        font-weight: 400;
        margin-top: 2px;
    }

    #attendanceEditModal .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.6;
    }

    #attendanceEditModal .modal-body {
        background: #f0f4f8;
        padding: 1.75rem;
    }

    #attendanceEditModal .modal-footer {
        background: #f0f4f8;
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.75rem;
        gap: 0.65rem;
    }

    .ae-student-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.1rem 1.4rem;
        display: flex;
        align-items: center;
        gap: 1.1rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 16px rgba(15, 36, 96, 0.1);
        border-left: 5px solid #1e4db7;
    }

    .ae-student-card .avatar {
        width: 96px;
        height: 96px;
        border-radius: 18px;
        background: linear-gradient(135deg, #0f2460, #1e4db7);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #fff;
        font-size: 1.8rem;
        font-weight: 700;
        flex-shrink: 0;
        box-shadow: 0 6px 16px rgba(30, 77, 183, 0.35);
    }

    .ae-student-card .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ae-student-card .info {
        min-width: 0;
        flex: 1;
    }

    .ae-student-card .nickname {
        font-size: clamp(1.6rem, 7vw, 2.1rem);
        font-weight: 800;
        line-height: 1.15;
        color: #0f2460;
        margin: 0 0 2px;
        white-space: nowrap;
    }

    .ae-student-card .fullname {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 500;
        color: #64748b;
    }

    .ae-student-card .pills {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }

    .badge-pill {
        background: #eff3ff;
        color: #1e4db7;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 0.74rem;
        font-weight: 600;
        border: 1px solid #c7d7f8;
    }

    .ae-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.2rem 1.4rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 10px rgba(15, 36, 96, 0.07);
    }

    .ae-card .section-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.7px;
        color: #94a3b8;
        margin-bottom: 0.65rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ae-card .section-label i {
        color: #1e4db7;
        font-size: 0.9rem;
    }

    .ae-card .form-control {
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        padding: 0.65rem 1rem;
        background: #f8faff;
        box-shadow: none;
        color: #334155;
    }

    .ae-card .form-control:focus {
        border-color: #1e4db7;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
        outline: none;
    }

    .temp-input-group {
        position: relative;
    }

    .temp-input-group .form-control {
        padding-right: 4rem;
        font-size: 1.05rem;
        font-weight: 600;
        color: #0f2460;
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
    }

    .status-choices {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.6rem;
    }

    .status-choice {
        margin: 0;
        position: relative;
        cursor: pointer;
    }

    .status-choice input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .status-choice span {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0.75rem 0.5rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        background: #f8faff;
        font-weight: 700;
        color: #475569;
        transition: all 0.2s ease;
        user-select: none;
    }

    .status-choice input:checked + span {
        border-color: #1e4db7;
        background: #eff3ff;
        color: #0f2460;
        box-shadow: 0 4px 14px rgba(30, 77, 183, 0.15);
    }

    .status-choice input:focus-visible + span {
        box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.25);
    }

    .symptoms-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.6rem;
        align-items: start;
    }

    .care-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.6rem;
    }

    @media (max-width: 576px) {
        .symptoms-grid,
        .care-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        #attendanceEditModal .modal-body {
            padding: 1rem;
        }

        #attendanceEditModal .modal-header {
            padding: 1.1rem 1.25rem;
        }

        .ae-student-card {
            padding: 0.9rem 1rem;
            gap: 0.8rem;
        }

        .ae-student-card .avatar {
            width: 80px;
            height: 80px;
        }

        .status-choices {
            grid-template-columns: 1fr;
        }
    }

    .symptom-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .symptom-checkbox {
        position: relative;
        cursor: pointer;
        margin: 0;
        display: block;
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
        font-size: 1.25rem;
        line-height: 1;
        font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif;
        flex-shrink: 0;
    }

    .symptom-item .symptom-text {
        font-size: 0.84rem;
        font-weight: 600;
        color: #475569;
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
        margin: 0;
    }

    .symptom-checkbox input:checked + .symptom-item {
        border-color: #1e4db7;
        background: #eff3ff;
        box-shadow: 0 4px 14px rgba(30, 77, 183, 0.15);
    }

    .symptom-checkbox input:checked + .symptom-item .symptom-icon {
        background: #fff;
        outline: 2px solid #1e4db7;
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
        background: #fff;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 600;
        user-select: none;
    }

    .sub-chip input:checked + span {
        border-color: #1e4db7;
        background: linear-gradient(135deg, #0f2460, #1e4db7);
        color: #fff;
    }

    #attendanceEditModal .btn-close-custom {
        border-radius: 12px;
        padding: 0.58rem 1.3rem;
        font-size: 0.88rem;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        color: #64748b;
        background: #fff;
    }

    #attendanceEditModal .btn-close-custom:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #334155;
    }

    #attendanceEditModal .btn-save-custom {
        border-radius: 12px;
        padding: 0.58rem 1.5rem;
        font-size: 0.88rem;
        font-weight: 700;
        border: none;
        background: linear-gradient(135deg, #0f2460 0%, #1e4db7 100%);
        color: #fff;
        box-shadow: 0 4px 16px rgba(15, 36, 96, 0.35);
    }

    #attendanceEditModal .btn-save-custom:hover {
        background: linear-gradient(135deg, #0a1a4f 0%, #1a43a8 100%);
    }

    #attendanceEditModal .btn-save-custom:disabled {
        opacity: 0.7;
    }
</style>

<main class="main-content">
    <div class="attendance-container">
        <div class="ah-header">
            <div>
                <h2>ประวัติการมาเรียนของเด็ก</h2>
                <div class="sub">เลือกวันที่และห้องเรียน ระบบจะแสดงผลให้อัตโนมัติ กดการ์ดสรุปเพื่อกรองตามสถานะ</div>
            </div>
            <?php if (!$is_student): ?>
                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            <?php endif; ?>
        </div>

        <!-- ฟอร์มค้นหา -->
        <div class="filter-card">
            <form id="searchForm" method="GET" class="row g-3 align-items-end" onsubmit="return false;">
                <div class="col-6 col-lg-2 <?= $is_student ? 'd-none' : '' ?>">
                    <label for="child_group" class="form-label">กลุ่มเรียน</label>
                    <select name="child_group" id="child_group" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <?php
                        $groups = get_childgroup();
                        foreach ($groups as $group) {
                            if (!empty($group['child_group'])) {
                                $g = htmlspecialchars($group['child_group']);
                                echo "<option value='" . $g . "'>" . $g . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-6 col-lg-2 <?= $is_student ? 'd-none' : '' ?>">
                    <label for="classroom" class="form-label">ห้องเรียน</label>
                    <select name="classroom" id="classroom" class="form-select">
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <label for="date" class="form-label">วันที่</label>
                    <div class="date-nav">
                        <button type="button" class="btn" id="datePrev" title="วันก่อนหน้า"><i class="fas fa-chevron-left"></i></button>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                        <button type="button" class="btn" id="dateNext" title="วันถัดไป"><i class="fas fa-chevron-right"></i></button>
                        <button type="button" class="btn" id="dateToday" title="กลับมาวันนี้">วันนี้</button>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4 <?= $is_student ? 'd-none' : '' ?>">
                    <label for="search" class="form-label">ค้นหา</label>
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="search" name="search" placeholder="ชื่อเล่น, ชื่อ-นามสกุล, รหัส" autocomplete="off">
                    </div>
                </div>

                <div class="col-12 <?= $is_student ? 'd-none' : '' ?>">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="resetBtn">
                        <i class="fas fa-rotate-left me-1"></i> ล้างตัวกรอง
                    </button>
                </div>
            </form>
        </div>

        <!-- สรุป + ผลลัพธ์ -->
        <div id="summaryArea"></div>
        <div id="resultArea"></div>
    </div>
</main>

<!-- Modal เพิ่ม/แก้ไขข้อมูลการเข้าเรียน -->
<div class="modal fade" id="attendanceEditModal" tabindex="-1" aria-labelledby="attendanceEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <div class="title-icon-wrap"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <span id="attendanceEditTitle">แก้ไขข้อมูลการเข้าเรียน</span>
                        <div class="header-subtitle">Attendance Record</div>
                    </div>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="ae-student-card">
                    <div class="avatar" id="aeAvatar">-</div>
                    <div class="info">
                        <div class="nickname" id="aeNickname" style="display:none;"></div>
                        <h4 class="fullname" id="aeFullname">-</h4>
                        <div class="pills">
                            <span class="badge-pill"><i class="bi bi-person-badge me-1"></i><span id="aeStudentId">-</span></span>
                            <span class="badge-pill"><i class="bi bi-door-open me-1"></i><span id="aeClassroom">-</span></span>
                            <span class="badge-pill"><i class="bi bi-calendar3 me-1"></i><span id="aeDate">-</span></span>
                        </div>
                    </div>
                </div>

                <form id="attendanceEditForm" onsubmit="return false;">
                    <input type="hidden" id="aeRecordId">
                    <input type="hidden" id="aeStudentIdInput">
                    <input type="hidden" id="aeDateInput">

                    <!-- สถานะ -->
                    <div class="ae-card">
                        <div class="section-label"><i class="bi bi-check2-square"></i> สถานะการมาเรียน</div>
                        <div class="status-choices">
                            <label class="status-choice"><input type="radio" name="aeStatus" value="present"><span>✅ มาเรียน</span></label>
                            <label class="status-choice"><input type="radio" name="aeStatus" value="leave"><span>📝 ลา</span></label>
                            <label class="status-choice"><input type="radio" name="aeStatus" value="absent"><span>❌ ไม่มาเรียน</span></label>
                        </div>
                        <div class="form-text mt-2" id="aeLateHint">ถ้าเวลามาหลัง <span id="aeLateTime">08:30</span> น. ระบบจะบันทึกเป็น "มาสาย" ให้อัตโนมัติ</div>
                    </div>

                    <!-- เวลา -->
                    <div class="ae-card" id="aeTimeCard">
                        <div class="section-label"><i class="bi bi-clock"></i> เวลา</div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold" for="aeCheckIn">เวลามาเรียน</label>
                                <input type="time" class="form-control" id="aeCheckIn">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold" for="aeCheckOut">เวลากลับบ้าน</label>
                                <input type="time" class="form-control" id="aeCheckOut">
                            </div>
                        </div>
                    </div>

                    <!-- หมายเหตุการลา -->
                    <div class="ae-card" id="aeLeaveCard" style="display:none;">
                        <div class="section-label"><i class="bi bi-journal-text"></i> หมายเหตุการลา</div>
                        <textarea class="form-control" id="aeLeaveNote" rows="3" placeholder="ระบุเหตุผลการลา"></textarea>
                    </div>

                    <!-- ข้อมูลสุขภาพ (เฉพาะมาเรียน) -->
                    <div id="aeHealthSection">
                        <div class="ae-card">
                            <div class="section-label"><i class="bi bi-thermometer-half"></i> อุณหภูมิร่างกาย</div>
                            <div class="temp-input-group">
                                <input type="number" class="form-control" id="aeTemperature" step="0.1" min="35.0" max="42.0" placeholder="37.0">
                                <span class="unit-badge">°C</span>
                            </div>
                        </div>

                        <div class="ae-card">
                            <div class="section-label">
                                <i class="bi bi-exclamation-triangle"></i> อาการผิดปกติ
                                <span class="ms-1 text-muted fw-normal" style="font-size:0.76rem;">(เลือกได้มากกว่า 1)</span>
                            </div>
                            <div class="symptoms-grid" id="aeSymptomsGrid"></div>
                        </div>

                        <div class="ae-card">
                            <div class="section-label"><i class="bi bi-pencil-square"></i> อาการอื่นๆ</div>
                            <textarea class="form-control" id="aeOtherSymptoms" rows="2" placeholder="เช่น ปวดหัว, คลื่นไส้, ท้องเสีย, ฯลฯ"></textarea>
                        </div>

                        <div class="ae-card">
                            <div class="section-label">
                                <i class="bi bi-bandaid"></i> การดูแล/ช่วยเหลือ
                                <span class="ms-1 text-muted fw-normal" style="font-size:0.76rem;">(เลือกได้มากกว่า 1)</span>
                            </div>
                            <div class="care-grid" id="aeCareGrid"></div>
                            <input type="text" class="form-control mt-3" id="aeCareOther" maxlength="200" placeholder="ระบุการดูแล/ช่วยเหลืออื่นๆ" style="display:none;">
                        </div>

                        <div class="ae-card mb-0">
                            <div class="section-label"><i class="bi bi-person-check"></i> ผู้ดูแลชื่อ</div>
                            <input type="text" class="form-control" id="aeCaretaker" maxlength="150" placeholder="ชื่อผู้ดูแลที่ให้การดูแล/ช่วยเหลือ" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> ปิด
                </button>
                <button type="button" class="btn btn-save-custom" id="aeSaveBtn">
                    <i class="bi bi-check-circle me-1"></i> บันทึก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const CAN_EDIT = <?php echo json_encode($is_admin || $is_teacher); ?>;
    const IS_STUDENT = <?php echo json_encode($is_student); ?>;
    const STUDENT_SELF_ID = <?php echo json_encode($is_student ? ($_SESSION['username'] ?? '') : ''); ?>;

    <?php include __DIR__ . '/partials/health_options.js.php'; ?>

    const STATUS_META = {
        present: { text: 'มาเรียน', badge: 'bg-success' },
        late: { text: 'มาสาย', badge: 'bg-warning text-dark' },
        absent: { text: 'ไม่มาเรียน', badge: 'bg-danger' },
        leave: { text: 'ลา', badge: 'bg-primary' },
        none: { text: 'ยังไม่บันทึก', badge: 'bg-secondary' }
    };
    const STATUS_ORDER = ['present', 'late', 'absent', 'leave', 'none'];

    let records = [];
    let statusFilter = 'all';
    let editModalInstance = null;
    let editingIsNew = false;

    const byId = (id) => document.getElementById(id);

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function debounce(fn, ms) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), ms);
        };
    }

    function statusKey(record) {
        return record.status && STATUS_META[record.status] ? record.status : 'none';
    }

    // ดึงเวลา HH:MM จากข้อความเวลา/วันที่-เวลา (ไม่พึ่ง new Date เพื่อให้ใช้ได้ทุกเบราว์เซอร์)
    function timeParts(value) {
        const m = String(value || '').match(/(?:^|[ T])(\d{2}):(\d{2})(?::\d{2})?/);
        return m ? { h: m[1], m: m[2] } : null;
    }

    function formatTime(value) {
        const t = timeParts(value);
        if (!t || (t.h === '00' && t.m === '00')) return '-';
        return `${t.h}:${t.m} น.`;
    }

    function inputTime(value) {
        const t = timeParts(value);
        if (!t || (t.h === '00' && t.m === '00')) return '';
        return `${t.h}:${t.m}`;
    }

    function fullName(r) {
        return [r.prefix_th, r.firstname_th, r.lastname_th].filter(Boolean).join(' ');
    }

    function formatThaiDate(dateStr) {
        const m = String(dateStr || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return '-';
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        return d.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function shiftDate(days) {
        const input = byId('date');
        const base = input.value ? new Date(input.value + 'T00:00:00') : new Date();
        base.setDate(base.getDate() + days);
        const y = base.getFullYear();
        const mo = String(base.getMonth() + 1).padStart(2, '0');
        const d = String(base.getDate()).padStart(2, '0');
        input.value = `${y}-${mo}-${d}`;
        loadResults();
    }

    function todayString() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    // ===== โหลดห้องเรียนตามกลุ่ม =====
    async function loadClassrooms(selected = '') {
        const group = byId('child_group').value;
        const select = byId('classroom');
        select.innerHTML = '<option value="">ทั้งหมด</option>';
        if (!group) return;

        try {
            const res = await fetch(`../include/function/get_classrooms.php?child_group=${encodeURIComponent(group)}`);
            const data = await res.json();
            (Array.isArray(data) ? data : []).forEach((c) => {
                const option = document.createElement('option');
                option.value = c.classroom_name;
                option.textContent = c.classroom_name;
                select.appendChild(option);
            });
            if (selected) select.value = selected;
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // ===== โหลดผลลัพธ์ =====
    async function loadResults() {
        const area = byId('resultArea');
        area.innerHTML = '<div class="state-box"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">กำลังโหลดข้อมูล...</div></div>';

        const params = new URLSearchParams();
        if (IS_STUDENT) {
            params.set('student_id', STUDENT_SELF_ID);
            params.set('date', byId('date').value || todayString());
        } else {
            ['child_group', 'classroom', 'date', 'search'].forEach((key) => {
                const value = byId(key).value.trim();
                if (value) params.set(key, value);
            });
        }

        try {
            const res = await fetch(`../include/function/get_attendance_history.php?${params.toString()}`);
            const data = await res.json();
            if (!Array.isArray(data)) {
                throw new Error(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
            records = data;
            renderAll();
        } catch (error) {
            console.error('Error:', error);
            records = [];
            byId('summaryArea').innerHTML = '';
            area.innerHTML = `<div class="state-box"><span class="emoji">⚠️</span>${esc(error.message)}<div class="mt-3"><button class="btn btn-outline-primary btn-sm" onclick="loadResults()">ลองใหม่</button></div></div>`;
        }
    }

    function countStatuses(list) {
        const counts = { present: 0, late: 0, absent: 0, leave: 0, none: 0 };
        list.forEach((r) => { counts[statusKey(r)]++; });
        return counts;
    }

    function renderSummary() {
        const counts = countStatuses(records);
        const cards = [{ key: 'all', text: 'ทั้งหมด', num: records.length }]
            .concat(STATUS_ORDER.map((k) => ({ key: k, text: STATUS_META[k].text, num: counts[k] })));

        byId('summaryArea').innerHTML = `<div class="stat-row">${cards.map((c) => `
            <button type="button" class="stat-card ${statusFilter === c.key ? 'active' : ''}" data-status="${c.key}">
                <span class="num">${c.num}</span>
                <span class="lbl">${c.text}</span>
            </button>`).join('')}</div>`;
    }

    function renderActionButtons(r, idx) {
        if (!CAN_EDIT) return '<span class="text-muted small">ดูข้อมูลเท่านั้น</span>';
        if (r.id) {
            return `<div class="action-btns">
                <button type="button" class="btn btn-info text-white" data-action="view" data-id="${r.id}" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>
                <button type="button" class="btn btn-warning" data-action="edit" data-id="${r.id}" title="แก้ไข"><i class="fas fa-edit"></i></button>
                <button type="button" class="btn btn-danger" data-action="delete" data-id="${r.id}" title="ลบ"><i class="fas fa-trash"></i></button>
            </div>`;
        }
        return `<div class="action-btns"><button type="button" class="btn btn-primary add" data-action="add" data-idx="${idx}"><i class="fas fa-plus"></i> บันทึก</button></div>`;
    }

    function renderResults() {
        const area = byId('resultArea');
        const list = statusFilter === 'all' ? records : records.filter((r) => statusKey(r) === statusFilter);

        if (list.length === 0) {
            area.innerHTML = `<div class="state-box"><span class="emoji">📭</span>${
                records.length === 0 ? 'ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่เลือก' : 'ไม่มีรายการในสถานะนี้'}</div>`;
            return;
        }

        // จัดกลุ่มตามกลุ่มเรียน + ห้องเรียน
        const groups = new Map();
        list.forEach((r) => {
            const key = `${r.child_group}|${r.classroom}`;
            if (!groups.has(key)) groups.set(key, { child_group: r.child_group, classroom: r.classroom, rows: [] });
            groups.get(key).rows.push(r);
        });

        let html = '';
        groups.forEach((g) => {
            const counts = countStatuses(g.rows);
            const mini = STATUS_ORDER.filter((k) => counts[k] > 0)
                .map((k) => `${STATUS_META[k].text} ${counts[k]}`).join(' · ');

            html += `
            <div class="class-card">
                <div class="class-head">
                    <h5><i class="bi bi-door-open-fill me-1"></i> ${esc(g.child_group || '-')} | ห้อง ${esc(g.classroom || '-')}</h5>
                    <span class="mini">${g.rows.length} คน${mini ? ' — ' + mini : ''}</span>
                </div>
                <div class="history-scroll">
                    <table class="table table-hover history-table">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th class="nick-col">ชื่อเล่น</th>
                                <th class="text-start">ชื่อ-นามสกุล</th>
                                <th>สถานะ</th>
                                <th>เวลามา</th>
                                <th>กลับบ้าน</th>
                                <th>เวลากลับ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${g.rows.map((r) => {
                                const key = statusKey(r);
                                const idx = records.indexOf(r);
                                const home = r.status_checkout === 'checked_out';
                                return `
                                <tr class="st-${key}">
                                    <td>${esc(r.student_id)}</td>
                                    <td class="nick-col">${r.nickname ? `<span class="nickname-text">${esc(r.nickname)}</span>` : '-'}</td>
                                    <td class="name-col">${esc(fullName(r))}</td>
                                    <td><span class="badge ${STATUS_META[key].badge}">${STATUS_META[key].text}</span></td>
                                    <td>${key === 'present' || key === 'late' ? formatTime(r.check_date) : '-'}</td>
                                    <td>${key === 'present' || key === 'late'
                                        ? `<span class="badge ${home ? 'bg-success' : 'bg-secondary'}">${home ? 'กลับแล้ว' : 'ยังไม่กลับ'}</span>` : '-'}</td>
                                    <td>${formatTime(r.check_out_time)}</td>
                                    <td>${renderActionButtons(r, idx)}</td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
        });
        area.innerHTML = html;
    }

    function renderAll() {
        renderSummary();
        renderResults();
    }

    // ===== เหตุการณ์ในตาราง/การ์ดสรุป =====
    document.addEventListener('click', (e) => {
        const card = e.target.closest('.stat-card');
        if (card) {
            statusFilter = card.dataset.status;
            renderAll();
            return;
        }

        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const { action, id, idx } = btn.dataset;
        if (action === 'view') viewAttendanceDetail(id);
        else if (action === 'edit') editAttendance(id);
        else if (action === 'delete') deleteAttendance(id);
        else if (action === 'add') addAttendance(records[Number(idx)]);
    });

    // ===== ข้อมูลสุขภาพ: อาการ / การดูแล =====
    // วาดตัวเลือกอาการ/การดูแลตามที่ admin ตั้งไว้
    // ถ้าข้อมูลเดิมใช้ตัวเลือกที่ถูกปิด/ลบไปแล้ว จะแสดงเพิ่มให้ (ติดป้าย "เลิกใช้") เพื่อไม่ให้ข้อมูลหายตอนแก้ไข
    function renderHealthOptions(record = {}) {
        let usedSymptoms = parseJsonField(record.symptoms, {});
        if (Array.isArray(usedSymptoms)) usedSymptoms = {};
        let usedCare = parseJsonField(record.care_actions, []);
        if (!Array.isArray(usedCare)) usedCare = [];

        const symptoms = SYMPTOM_LOOKUP.filter((s) => !s.archived || usedSymptoms[s.code] !== undefined);
        const care = CARE_LOOKUP.filter((c) => !c.archived || usedCare.includes(c.code));

        byId('aeSymptomsGrid').innerHTML = symptoms.length === 0
            ? '<div class="text-muted small">ยังไม่มีตัวเลือกอาการ</div>'
            : symptoms.map((s) => {
                const usedSubs = Array.isArray(usedSymptoms[s.code]) ? usedSymptoms[s.code] : [];
                const subs = (s.subs || []).filter((x) => !x.archived || usedSubs.includes(x.code));
                return `
            <div class="symptom-group">
                <label class="symptom-checkbox">
                    <input type="checkbox" data-symptom="${esc(s.code)}">
                    <div class="symptom-item">
                        <div class="symptom-icon" aria-hidden="true">${esc(s.icon) || '🔹'}</div>
                        <span class="symptom-text">${esc(s.label)}${s.archived ? ' <small class="text-muted">(เลิกใช้)</small>' : ''}</span>
                        <div class="check-mark"><i class="bi bi-check"></i></div>
                    </div>
                </label>
                ${subs.length ? `<div class="sub-options" data-sub-of="${esc(s.code)}" hidden>
                    ${subs.map((sub) => `<label class="sub-chip"><input type="checkbox" data-sub-of="${esc(s.code)}" value="${esc(sub.code)}"><span>${esc(sub.label)}</span></label>`).join('')}
                </div>` : ''}
            </div>`;
            }).join('');

        byId('aeCareGrid').innerHTML = care.length === 0
            ? '<div class="text-muted small">ยังไม่มีตัวเลือกการดูแล</div>'
            : care.map((c) => `
            <label class="symptom-checkbox">
                <input type="checkbox" data-care="${esc(c.code)}" data-allows-text="${c.allowsText ? 1 : 0}">
                <div class="symptom-item">
                    <div class="symptom-icon" aria-hidden="true">${esc(c.icon) || '🔹'}</div>
                    <span class="symptom-text">${esc(c.label)}${c.archived ? ' <small class="text-muted">(เลิกใช้)</small>' : ''}</span>
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                </div>
            </label>`).join('');
    }

    // ตัวเลือกการดูแลที่ถูกติ๊กอยู่ มีตัวที่เปิดให้พิมพ์ข้อความเพิ่ม (เช่น "อื่นๆ") หรือไม่
    function careNeedsText() {
        return !!document.querySelector('#aeCareGrid input[data-care][data-allows-text="1"]:checked');
    }

    function syncSubOptions(code) {
        const parent = document.querySelector(`#aeSymptomsGrid input[data-symptom="${code}"]`);
        const subs = document.querySelector(`#aeSymptomsGrid .sub-options[data-sub-of="${code}"]`);
        if (!parent || !subs) return;
        subs.hidden = !parent.checked;
        if (!parent.checked) subs.querySelectorAll('input').forEach((i) => { i.checked = false; });
    }

    function syncCareOther() {
        const input = byId('aeCareOther');
        const need = careNeedsText();
        input.style.display = need ? '' : 'none';
        if (!need) input.value = '';
    }

    function collectHealth() {
        const symptoms = {};
        document.querySelectorAll('#aeSymptomsGrid input[data-symptom]:checked').forEach((el) => {
            const code = el.dataset.symptom;
            symptoms[code] = Array.from(
                document.querySelectorAll(`#aeSymptomsGrid input[data-sub-of="${code}"]:checked`)
            ).map((i) => i.value);
        });
        const careActions = Array.from(document.querySelectorAll('#aeCareGrid input[data-care]:checked'))
            .map((el) => el.dataset.care);
        return { symptoms, careActions };
    }

    function parseJsonField(value, fallback) {
        if (value && typeof value === 'object') return value;
        try {
            const parsed = JSON.parse(value);
            return parsed && typeof parsed === 'object' ? parsed : fallback;
        } catch (e) {
            return fallback;
        }
    }

    const isTrue = (v) => v === true || v === 't' || v === '1' || v === 1;

    function applyHealthToForm(data) {
        document.querySelectorAll('#aeHealthSection input[type="checkbox"]').forEach((el) => { el.checked = false; });
        document.querySelectorAll('#aeSymptomsGrid .sub-options').forEach((el) => { el.hidden = true; });

        let symptoms = parseJsonField(data.symptoms, {});
        if (Array.isArray(symptoms)) symptoms = {};

        // ข้อมูลเก่าที่ยังไม่มี symptoms ให้ดึงจากช่อง boolean เดิม
        if (Object.keys(symptoms).length === 0) {
            if (isTrue(data.has_runny_nose)) symptoms.runny_nose = [];
            if (isTrue(data.has_cough)) symptoms.cough = [];
            if (isTrue(data.has_rash)) symptoms.rash = [];
        }

        Object.entries(symptoms).forEach(([code, subs]) => {
            const parent = document.querySelector(`#aeSymptomsGrid input[data-symptom="${code}"]`);
            if (!parent) return;
            parent.checked = true;
            syncSubOptions(code);
            (Array.isArray(subs) ? subs : []).forEach((sub) => {
                const chip = document.querySelector(`#aeSymptomsGrid input[data-sub-of="${code}"][value="${sub}"]`);
                if (chip) chip.checked = true;
            });
        });

        let careActions = parseJsonField(data.care_actions, []);
        if (!Array.isArray(careActions)) careActions = [];
        careActions.forEach((code) => {
            const el = document.querySelector(`#aeCareGrid input[data-care="${code}"]`);
            if (el) el.checked = true;
        });
        syncCareOther();
        byId('aeCareOther').value = data.care_other || '';

        // "ตาแดง" ของข้อมูลเก่าไม่มีในรายการใหม่ จึงเก็บไว้ในช่องอาการอื่นๆ ไม่ให้หาย
        let other = data.other_symptoms || '';
        if (isTrue(data.has_red_eyes) && !other.includes('ตาแดง')) {
            other = other ? `ตาแดง, ${other}` : 'ตาแดง';
        }
        byId('aeOtherSymptoms').value = other;
        byId('aeTemperature').value = data.temperature ?? '';
        byId('aeCaretaker').value = data.caretaker_name || '';
    }

    function updateStatusVisibility() {
        const status = document.querySelector('input[name="aeStatus"]:checked')?.value;
        const present = status === 'present';
        byId('aeTimeCard').style.display = present ? '' : 'none';
        byId('aeHealthSection').style.display = present ? '' : 'none';
        byId('aeLeaveCard').style.display = status === 'leave' ? '' : 'none';
        byId('aeLateHint').style.display = present ? '' : 'none';
    }

    function getEditModal() {
        if (!editModalInstance) {
            editModalInstance = new bootstrap.Modal(byId('attendanceEditModal'), { backdrop: 'static', keyboard: true });
        }
        return editModalInstance;
    }

    function openEditModal(data, isNew) {
        editingIsNew = isNew;
        byId('attendanceEditTitle').textContent = isNew ? 'บันทึกการเข้าเรียน' : 'แก้ไขข้อมูลการเข้าเรียน';

        byId('aeRecordId').value = data.id || '';
        byId('aeStudentIdInput').value = data.student_id || '';
        byId('aeDateInput').value = data.attendance_date;

        const name = fullName(data) || 'ไม่ระบุชื่อ';
        const nickname = (data.nickname || '').trim();
        const nickEl = byId('aeNickname');
        nickEl.textContent = nickname ? 'น้อง' + nickname : '';
        nickEl.style.display = nickname ? '' : 'none';
        byId('aeFullname').textContent = name;
        byId('aeStudentId').textContent = data.student_id || '-';
        byId('aeClassroom').textContent = data.classroom || '-';
        byId('aeDate').textContent = formatThaiDate(data.attendance_date);

        const initial = (data.firstname_th || nickname || '-').charAt(0).toUpperCase();
        const avatar = byId('aeAvatar');
        avatar.innerHTML = '';
        if (data.profile_image) {
            const img = document.createElement('img');
            img.src = data.profile_image;
            img.alt = 'รูปนักเรียน';
            img.onerror = () => { avatar.innerHTML = ''; avatar.textContent = initial; };
            avatar.appendChild(img);
        } else {
            avatar.textContent = initial;
        }

        // 'late' แสดงเป็นมาเรียน (ระบบคำนวณมาสายจากเวลาเอง)
        const status = data.status === 'late' ? 'present' : (data.status || 'present');
        const radio = document.querySelector(`input[name="aeStatus"][value="${status}"]`);
        (radio || document.querySelector('input[name="aeStatus"][value="present"]')).checked = true;

        byId('aeCheckIn').value = isNew ? inputTime(data.check_date) : inputTime(data.check_date);
        byId('aeCheckOut').value = inputTime(data.check_out_time);
        byId('aeLeaveNote').value = data.status === 'leave' ? (data.leave_note || '') : '';

        renderHealthOptions(data);
        applyHealthToForm(data);
        updateStatusVisibility();
        getEditModal().show();
    }

    function addAttendance(record) {
        const now = new Date();
        openEditModal({
            id: null,
            student_id: record.student_id,
            prefix_th: record.prefix_th,
            firstname_th: record.firstname_th,
            lastname_th: record.lastname_th,
            nickname: record.nickname,
            profile_image: record.profile_image,
            classroom: record.classroom,
            status: 'present',
            check_date: `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:00`,
            attendance_date: byId('date').value || todayString()
        }, true);
    }

    async function editAttendance(id) {
        const date = byId('date').value || todayString();
        try {
            const res = await fetch(`../include/function/get_attendance_detail.php?id=${encodeURIComponent(id)}&date=${date}`);
            const response = await res.json();
            if (response.status !== 'success') {
                throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
            }
            openEditModal({ ...response.data, attendance_date: date }, false);
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error.message, confirmButtonText: 'ตกลง' });
        }
    }

    async function saveAttendanceEdit() {
        const status = document.querySelector('input[name="aeStatus"]:checked')?.value;
        const studentId = byId('aeStudentIdInput').value;
        const checkIn = byId('aeCheckIn').value;
        const checkOut = byId('aeCheckOut').value;
        const leaveNote = byId('aeLeaveNote').value.trim();
        const temperature = byId('aeTemperature').value;
        const caretaker = byId('aeCaretaker').value.trim();
        const { symptoms, careActions } = collectHealth();
        const careOther = careNeedsText() ? byId('aeCareOther').value.trim() : '';

        const warn = (text, focusId) => {
            Swal.fire({ icon: 'warning', title: text, confirmButtonColor: '#1e4db7', confirmButtonText: 'ตกลง' });
            if (focusId) setTimeout(() => byId(focusId)?.focus(), 300);
        };

        if (!studentId) return warn('ไม่พบรหัสนักเรียน');
        if (!status) return warn('กรุณาเลือกสถานะการมาเรียน');
        if (status === 'present' && !checkIn) return warn('กรุณาระบุเวลามาเรียน', 'aeCheckIn');
        if (status === 'present' && checkOut && checkIn && checkOut < checkIn) {
            return warn('เวลากลับบ้านต้องไม่ก่อนเวลามาเรียน', 'aeCheckOut');
        }
        if (status === 'leave' && !leaveNote) return warn('กรุณาระบุหมายเหตุการลา', 'aeLeaveNote');
        if (status === 'present') {
            if (temperature !== '') {
                const t = parseFloat(temperature);
                if (isNaN(t) || t < 35 || t > 42) return warn('กรุณากรอกอุณหภูมิระหว่าง 35.0 - 42.0 °C', 'aeTemperature');
            }
            if (careActions.length > 0 && !caretaker) return warn('กรุณาระบุชื่อผู้ดูแล', 'aeCaretaker');
        }

        const payload = {
            id: byId('aeRecordId').value,
            student_id: studentId,
            attendance_date: byId('aeDateInput').value,
            status: status,
            check_date: status === 'present' ? checkIn : '',
            check_out_time: status === 'present' ? checkOut : '',
            leave_note: status === 'leave' ? leaveNote : '',
            temperature: status === 'present' ? temperature : '',
            symptoms: status === 'present' ? symptoms : {},
            other_symptoms: status === 'present' ? byId('aeOtherSymptoms').value.trim() : '',
            care_actions: status === 'present' ? careActions : [],
            care_other: status === 'present' ? careOther : '',
            caretaker_name: status === 'present' ? caretaker : ''
        };

        const url = editingIsNew
            ? '../include/process/save_attendance_record.php'
            : '../include/process/update_attendance_record.php';

        const btn = byId('aeSaveBtn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.status !== 'success') {
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
            getEditModal().hide();
            Swal.fire({
                icon: 'success',
                title: editingIsNew ? 'บันทึกข้อมูลสำเร็จ' : 'แก้ไขข้อมูลสำเร็จ',
                showConfirmButton: false,
                timer: 1500
            });
            loadResults();
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error.message, confirmButtonText: 'ตกลง' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    // ===== ลบ =====
    function deleteAttendance(id) {
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: 'คุณต้องการลบข้อมูลนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ใช่, ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch('../include/process/delete_attendance_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then((response) => response.json())
                .then((res) => {
                    if (res.status !== 'success') throw new Error(res.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                    Swal.fire({ icon: 'success', title: 'ลบข้อมูลสำเร็จ', showConfirmButton: false, timer: 1500 });
                    loadResults();
                })
                .catch((error) => {
                    console.error('Error:', error);
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error.message, confirmButtonText: 'ตกลง' });
                });
        });
    }

    // ===== ดูรายละเอียด =====
    function symptomsSummary(data) {
        let symptoms = parseJsonField(data.symptoms, {});
        if (Array.isArray(symptoms)) symptoms = {};
        const items = [];

        const entries = Object.entries(symptoms);
        if (entries.length > 0) {
            entries.forEach(([code, subs]) => {
                const opt = SYMPTOM_LOOKUP.find((s) => s.code === code);
                if (!opt) return;
                const subLabels = (Array.isArray(subs) ? subs : []).map((sc) => {
                    const sub = (opt.subs || []).find((x) => x.code === sc);
                    return sub ? sub.label : sc;
                });
                items.push(subLabels.length ? `${opt.label} (${subLabels.join(', ')})` : opt.label);
            });
        } else {
            if (isTrue(data.has_runny_nose)) items.push('น้ำมูก');
            if (isTrue(data.has_cough)) items.push('ไอ');
            if (isTrue(data.has_rash)) items.push('ผื่น');
        }
        if (isTrue(data.has_red_eyes)) items.push('ตาแดง');
        if (data.other_symptoms) items.push(data.other_symptoms);
        return items.length ? items.join(', ') : 'ไม่มีอาการ';
    }

    function careSummary(data) {
        let actions = parseJsonField(data.care_actions, []);
        if (!Array.isArray(actions)) actions = [];
        const labels = actions.map((code) => {
            const opt = CARE_LOOKUP.find((c) => c.code === code);
            if (!opt) return code;
            return opt.allowsText && data.care_other ? `${opt.label}: ${data.care_other}` : opt.label;
        });
        return labels.length ? labels.join(', ') : '-';
    }

    async function viewAttendanceDetail(id) {
        try {
            const res = await fetch(`../include/function/get_attendance_detail.php?id=${encodeURIComponent(id)}`);
            const response = await res.json();
            if (response.status !== 'success') {
                throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
            }
            const data = response.data;
            const key = data.status && STATUS_META[data.status] ? data.status : 'none';
            const present = key === 'present' || key === 'late';
            const nickname = (data.nickname || '').trim();
            const pickupName = data.status_checkout === 'checked_out' ? (data.leave_note || '-') : '-';

            const row = (label, value) => `<div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:4px;"><span style="color:#64748b;">${label}</span><strong style="color:#0f2460;text-align:right;">${value}</strong></div>`;

            Swal.fire({
                title: '<span style="font-size:1.25rem;font-weight:800;color:#0f2460;">รายละเอียดการเข้าเรียน</span>',
                html: `
                <div style="text-align:left;font-size:0.9rem;">
                    <div style="background:#f0f4f8;border-radius:12px;padding:1rem;margin-bottom:0.75rem;display:flex;justify-content:space-between;gap:10px;align-items:center;">
                        <div>
                            ${nickname ? `<div style="font-size:1.4rem;font-weight:800;color:#0f2460;line-height:1.2;">น้อง${esc(nickname)}</div>` : ''}
                            <div style="font-weight:600;color:#475569;">${esc(fullName(data))}</div>
                            <div style="font-size:0.78rem;color:#64748b;">${esc(data.student_id)} | ${esc(data.child_group)} | ${esc(data.classroom)}</div>
                            <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">${formatThaiDate(data.check_date)}</div>
                        </div>
                        <span class="badge ${STATUS_META[key].badge}" style="font-size:0.85rem;">${STATUS_META[key].text}</span>
                    </div>
                    ${present ? `
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:0.75rem;margin-bottom:0.75rem;">
                        <div style="background:#f0f4f8;border-radius:12px;padding:1rem;">
                            <div style="font-weight:700;color:#0f2460;margin-bottom:6px;">🏫 เข้าเรียน</div>
                            ${row('เวลา', formatTime(data.check_date))}
                            ${row('อุณหภูมิ', data.temperature ? esc(data.temperature) + ' °C' : '-')}
                            ${row('อาการ', esc(symptomsSummary(data)))}
                            ${row('การดูแล', esc(careSummary(data)))}
                            ${row('ผู้ดูแล', esc(data.caretaker_name || '-'))}
                        </div>
                        <div style="background:#f0f4f8;border-radius:12px;padding:1rem;">
                            <div style="font-weight:700;color:#0f2460;margin-bottom:6px;">🏠 กลับบ้าน</div>
                            ${row('เวลา', formatTime(data.check_out_time))}
                            ${row('สถานะ', data.status_checkout === 'checked_out' ? 'กลับแล้ว' : 'ยังไม่กลับ')}
                            ${row('ผู้รับ', esc(pickupName))}
                        </div>
                    </div>` : ''}
                    ${key === 'leave' ? `<div style="background:#eff3ff;border-left:4px solid #1e4db7;border-radius:10px;padding:0.75rem 1rem;"><strong>หมายเหตุการลา:</strong> ${esc(data.leave_note || '-')}</div>` : ''}
                </div>`,
                width: 640,
                confirmButtonText: '<i class="bi bi-x-circle me-1"></i> ปิด',
                confirmButtonColor: '#0f2460'
            });
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error.message, confirmButtonText: 'ตกลง' });
        }
    }

    // ===== เริ่มต้นหน้า =====
    document.addEventListener('DOMContentLoaded', async () => {
        renderHealthOptions();
        byId('aeLateTime').textContent = CHECKIN_LATE_TIME;

        byId('aeSymptomsGrid').addEventListener('change', (e) => {
            if (e.target.dataset.symptom) syncSubOptions(e.target.dataset.symptom);
        });
        byId('aeCareGrid').addEventListener('change', (e) => {
            if (e.target.dataset.allowsText === '1') {
                syncCareOther();
                if (e.target.checked) byId('aeCareOther').focus();
            }
        });
        document.querySelectorAll('input[name="aeStatus"]').forEach((r) => r.addEventListener('change', updateStatusVisibility));
        byId('aeSaveBtn').addEventListener('click', saveAttendanceEdit);

        // ตัวกรอง: เปลี่ยนแล้วค้นหาอัตโนมัติ
        byId('date').addEventListener('change', loadResults);
        byId('datePrev').addEventListener('click', () => shiftDate(-1));
        byId('dateNext').addEventListener('click', () => shiftDate(1));
        byId('dateToday').addEventListener('click', () => { byId('date').value = todayString(); loadResults(); });

        if (IS_STUDENT) {
            loadResults();
            return;
        }

        byId('child_group').addEventListener('change', async () => { await loadClassrooms(); loadResults(); });
        byId('classroom').addEventListener('change', loadResults);
        byId('search').addEventListener('input', debounce(loadResults, 350));
        byId('resetBtn').addEventListener('click', async () => {
            byId('child_group').value = '';
            byId('classroom').innerHTML = '<option value="">ทั้งหมด</option>';
            byId('date').value = todayString();
            byId('search').value = '';
            statusFilter = 'all';
            window.history.replaceState({}, '', window.location.pathname);
            loadResults();
        });

        // รับค่าตัวกรองจาก URL (ถ้ามี)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('child_group')) {
            byId('child_group').value = urlParams.get('child_group');
            await loadClassrooms(urlParams.get('classroom') || '');
        }
        if (urlParams.get('date')) byId('date').value = urlParams.get('date');
        if (urlParams.get('search')) byId('search').value = urlParams.get('search');

        loadResults();
    });

    // เพิ่มฟังก์ชันสำหรับ Export
    function exportToExcel() {
        Swal.fire({
            title: 'Export ข้อมูลการเข้าเรียน',
            html: `
                <form id="exportForm" class="text-start">
                    <div class="mb-3">
                        <label class="form-label">รูปแบบการ Export</label>
                        <select class="form-select" id="exportType" onchange="toggleDateFields()">
                            <option value="daily">รายวัน</option>
                            <option value="monthly">รายเดือน</option>
                            <option value="range">ช่วงวันที่</option>
                        </select>
                    </div>
                    
                    <!-- วันที่ -->
                    <div id="dailyField" class="mb-3">
                        <label class="form-label">วันที่</label>
                        <input type="date" class="form-control" id="exportDate" 
                            value="${document.getElementById('date').value}">
                    </div>
                    
                    <!-- เดือน -->
                    <div id="monthlyField" class="mb-3" style="display:none">
                        <label class="form-label">เดือน</label>
                        <input type="month" class="form-control" id="exportMonth" 
                            value="${new Date().toISOString().slice(0, 7)}">
                    </div>
                    
                    <!-- ช่วงวันที่ -->
                    <div id="rangeFields" class="mb-3" style="display:none">
                        <label class="form-label">ตั้งแต่วันที่</label>
                        <input type="date" class="form-control mb-2" id="exportStartDate">
                        <label class="form-label">ถึงวันที่</label>
                        <input type="date" class="form-control" id="exportEndDate">
                    </div>

                    <!-- กลุ่มเรียน -->
                    <div class="mb-3">
                        <label class="form-label">กลุ่มเรียน</label>
                        <select class="form-select" id="exportChildGroup" onchange="loadExportClassrooms()">
                            <option value="">ทั้งหมด</option>
                            <?php
                            $groups = get_childgroup();
                            foreach ($groups as $group) {
                                if (!empty($group['child_group'])) {
                                    echo "<option value='" . $group['child_group'] . "'>" . $group['child_group'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- ห้องเรียน -->
                    <div class="mb-3">
                        <label class="form-label">ห้องเรียน</label>
                        <select class="form-select" id="exportClassroom">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'ยกเลิก',
            didOpen: () => {
                // โหลดห้องเรียนถ้ามีการเลือกกลุ่มเรียนไว้
                const currentGroup = document.getElementById('child_group').value;
                if (currentGroup) {
                    document.getElementById('exportChildGroup').value = currentGroup;
                    loadExportClassrooms();
                }
            },
            preConfirm: () => {
                const exportType = document.getElementById('exportType').value;
                let dateValidation = true;
                let dateParams = {};

                switch(exportType) {
                    case 'daily':
                        const dailyDate = document.getElementById('exportDate').value;
                        if (!dailyDate) {
                            Swal.showValidationMessage('กรุณาเลือกวันที่');
                            return false;
                        }
                        dateParams = { date: dailyDate };
                        break;

                    case 'monthly':
                        const monthDate = document.getElementById('exportMonth').value;
                        if (!monthDate) {
                            Swal.showValidationMessage('กรุณาเลือกเดือน');
                            return false;
                        }
                        dateParams = { month: monthDate };
                        break;

                    case 'range':
                        const startDate = document.getElementById('exportStartDate').value;
                        const endDate = document.getElementById('exportEndDate').value;
                        if (!startDate || !endDate) {
                            Swal.showValidationMessage('กรุณาเลือกช่วงวันที่ให้ครบ');
                            return false;
                        }
                        if (startDate > endDate) {
                            Swal.showValidationMessage('วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด');
                            return false;
                        }
                        dateParams = { start_date: startDate, end_date: endDate };
                        break;
                }

                return {
                    type: exportType,
                    ...dateParams,
                    child_group: document.getElementById('exportChildGroup').value,
                    classroom: document.getElementById('exportClassroom').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const params = new URLSearchParams(result.value);
                const exportUrl = `../include/process/export_attendance.php?${params.toString()}`;
                window.open(exportUrl, '_blank');
            }
        });
    }

    // ฟังก์ชันสลับการแสดงฟิลด์วันที่
    function toggleDateFields() {
        const exportType = document.getElementById('exportType').value;
        document.getElementById('dailyField').style.display = exportType === 'daily' ? 'block' : 'none';
        document.getElementById('monthlyField').style.display = exportType === 'monthly' ? 'block' : 'none';
        document.getElementById('rangeFields').style.display = exportType === 'range' ? 'block' : 'none';
    }

    // ฟังก์ชันโหลดห้องเรียนสำหรับ export
    function loadExportClassrooms() {
        const childGroup = document.getElementById('exportChildGroup').value;
        const classroomSelect = document.getElementById('exportClassroom');
        
        classroomSelect.innerHTML = '<option value="">ทั้งหมด</option>';
        
        if (!childGroup) return;

        fetch(`../include/function/get_classrooms.php?child_group=${childGroup}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(function(classroom) {
                    const option = document.createElement('option');
                    option.value = classroom.classroom_name;
                    option.textContent = classroom.classroom_name;
                    classroomSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error:', error));
    }
</script>
