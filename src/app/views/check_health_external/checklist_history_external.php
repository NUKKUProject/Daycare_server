<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'student']);
include __DIR__ . '../../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once '../../include/function/pages_referen.php';


$is_admin = getUserRole() === 'admin';
$is_student = getUserRole() === 'student';
$is_teacher = getUserRole() === 'teacher';
require_once __DIR__ . '/../../include/auth/auth_dashboard.php';






// ใช้ user_id จาก session เป็น teacher_id
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
} else {
    die('ไม่พบข้อมูลผู้สอน. กรุณาเข้าสู่ระบบอีกครั้ง.');
}
?>

<style>
    /* CSS เดิมของคุณ - เก็บไว้ทั้งหมด */
    body {
        font-family: 'Sarabun', sans-serif;
        background-color: #f8f9fa;
        line-height: 1.6;
    }

    .form-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .header-section {
        background: linear-gradient(135deg, #26648E 0%, #26648E 100%);
        color: white;
        padding: 2rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    .header-logo {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        backdrop-filter: blur(10px);
    }

    .section-header {
        background: linear-gradient(45deg, #4CAF50, #45a049);
        color: white;
        padding: 0.75rem 1.25rem;
        margin: -1.25rem -1.25rem 1.25rem -1.25rem;
        border-radius: 0.375rem 0.375rem 0 0;
        font-weight: 600;
    }

    .form-section {
        background: #f8f9ff;
        border-radius: 10px;
        border-left: 4px solid #667eea;
    }

    .dotted-input {
        border: none !important;
        border-bottom: 2px dotted #6c757d !important;
        border-radius: 0 !important;
        background: transparent !important;
        padding: 0.25rem 0.5rem !important;
        box-shadow: none !important;
    }

    .dotted-input:focus {
        border-bottom-color: #667eea !important;
        box-shadow: 0 2px 0 #667eea !important;
    }

    .custom-table {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }

    .custom-table th {
        font-weight: 600;
        text-align: center;
        padding: 0.75rem 0.5rem;
        font-size: 0.9rem;
        border: 1px solid #dee2e6;
    }

    .custom-table td {
        text-align: center;
        padding: 0.75rem 0.5rem;
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .custom-checkbox {
        transform: scale(1.3);
        accent-color: #667eea;
    }

    .temperature-badge {
        background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        display: inline-flex;
        align-items: center;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .vital-signs-card {
        border: none;
        border-radius: 15px;
        background: linear-gradient(135deg, #667eea15, #764ba215);
        border-left: 5px solid #667eea;
    }

    .behavior-card {
        border: none;
        border-radius: 15px;
        background: linear-gradient(135deg, #4CAF5015, #45a04915);
        border-left: 5px solid #4CAF50;
    }

    .measurement-input {
        text-align: center;
        font-weight: 600;
        color: #667eea;
    }

    .btn-custom {
        background-color: rgb(21, 158, 71);
        border: none;
        padding: 0.75rem 2rem;
        font-weight: 600;
        border-radius: 25px;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-custom:hover {
        transform: translateY(-2px);
        background-color: rgba(21, 158, 71, 0.8);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .print-section {
        background: #f8f9fa;
        border-top: 3px solid #667eea;
        padding: 1.5rem;
        text-align: center;
    }

    @media print {
        body {
            background: white !important;
        }

        .form-container {
            box-shadow: none !important;
            border: 1px solid #000 !important;
        }

        .btn-custom,
        .print-section {
            display: none !important;
        }

        .header-section {
            background: #667eea !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
    }

    .input-group-text {
        background: linear-gradient(45deg, #26648E, rgb(77, 106, 125));
        color: white;
        border: none;
        font-weight: 500;
    }

    .badge-custom {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }

    /* 🆕 CSS เพิ่มเติมสำหรับหน้า 2 - ไม่กระทบของเดิม */

    /* ตารางประเมินพัฒนาการ */
    .development-table {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        font-size: 12px;
    }

    .development-table th {
        font-weight: 600;
        text-align: center;
        padding: 0.5rem 0.25rem;
        vertical-align: middle;
        border: 1px solid #fff;
        border-right: 1px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
    }

    .development-table td {
        text-align: center;
        padding: 0.5rem 0.25rem;
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .development-table .category-header {
        writing-mode: vertical-rl;
        text-orientation: mixed;
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
        font-weight: 700;
        width: 80px;
    }

    /* การตรวจร่างกาย */
    .examination-section {
        background: #fff9f9;
        border-left: 4px solid #dc3545;
        border-radius: 10px;
    }

    .examination-item {
        display: flex;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px dotted #dee2e6;
    }

    .examination-item:last-child {
        border-bottom: none;
    }

    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-right: 1rem;
        min-width: 250px;
    }

    .dotted-line {
        border-bottom: 1px dotted #6c757d;
        flex-grow: 1;
        min-height: 1.5rem;
        background: transparent;
        border: none;
        border-bottom: 1px dotted #6c757d !important;
    }

    /* ระบบประสาท */
    .neuro-section {
        background: #f0f8ff;
        border-left: 4px solid #0d6efd;
        border-radius: 10px;
    }

    /* คำแนะนำ */
    .recommendations-section {
        background: #fff8e1;
        border-left: 4px solid #ff9800;
        border-radius: 10px;
    }

    /* ลายเซ็น */
    .signature-section {
        background: #f8f9fa;
        border-top: 3px solid #667eea;
        padding: 2rem;
        text-align: center;
    }

    .signature-box {
        text-align: center;
        padding: 1rem;
    }

    .signature-line {
        border-bottom: 2px solid #333;
        margin-bottom: 0.5rem;
        height: 3rem;
        position: relative;
    }

    /* Responsive สำหรับหน้า 2 */
    @media (max-width: 768px) {
        .development-table {
            font-size: 10px;
        }

        .development-table th,
        .development-table td {
            padding: 0.25rem;
        }

        .examination-item {
            flex-direction: column;
            align-items: flex-start;
            padding: 1rem 0;
        }

        .checkbox-container {
            margin-bottom: 0.5rem;
            min-width: auto;
            width: 100%;
        }
    }

    /* Print สำหรับหน้า 2 */
    @media print {
        .development-table {
            font-size: 10px;
        }

        .development-table th,
        .development-table td {
            padding: 0.25rem;
        }

        .signature-section {
            page-break-inside: avoid;
        }
    }
</style>
</head>

<body>
    <style>
        .attendance-container {
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
    </style>

    <main class="main-content">
        <div class="attendance-container">
            <div>
                <!-- Header Section -->
                <div class="header-section">
                    <h2 class="mb-2">แบบบันทึกการตรวจสุขภาพเด็ก โดยกุมารแพทย์</h2>
                    <p class="mb-0">ณ ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย</p>
                </div>
                <!-- Basic Information -->
                <form action="">
                    <div class="card form-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i>ข้อมูลพื้นฐาน
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">วัน/เดือน/ปี</label>
                                    <input type="date" class="form-control dotted-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ประจำปีการศึกษา</label>
                                    <input type="text" class="form-control dotted-input" placeholder="2567">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-12">
                                    <label class="form-label fw-bold">ข้าพเจ้า นพ./พญ.</label>
                                    <input type="text" class="form-control dotted-input" placeholder="ชื่อแพทย์/พยาบาล">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">ได้ตรวจร่างกาย ดช./ดญ.</label>
                                    <input type="text" class="form-control dotted-input" placeholder="ชื่อ-นามสกุลเด็ก">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">ห้องเรียน</label>
                                    <input type="text" class="form-control dotted-input" placeholder="ห้อง">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">วัน/เดือน/ปีเกิด</label>
                                    <input type="date" class="form-control dotted-input">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">อายุ (ปี)</label>
                                    <input type="number" class="form-control dotted-input measurement-input" min="0" max="10">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">เดือน</label>
                                    <input type="number" class="form-control dotted-input measurement-input" min="0" max="11">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">วัน</label>
                                    <input type="number" class="form-control dotted-input measurement-input" min="0" max="31">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vital Signs -->
                    <div class="card vital-signs-card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-heart-pulse me-2"></i>สัญญาณชีพ
                            </div>

                            <div class="temperature-badge mb-3">
                                <i class="bi bi-thermometer-half me-2"></i>
                                การวัดอุณหภูมิร่างกาย
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-thermometer-half"></i></span>
                                        <input type="number" class="form-control measurement-input" step="0.1" placeholder="36.5">
                                        <span class="input-group-text">°C</span>
                                    </div>
                                    <small class="text-muted">อุณหภูมิร่างกาย</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-activity"></i></span>
                                        <input type="number" class="form-control measurement-input" placeholder="80">
                                        <span class="input-group-text">ครั้ง/นาที</span>
                                    </div>
                                    <small class="text-muted">ชีพจร</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lungs"></i></span>
                                        <input type="number" class="form-control measurement-input" placeholder="20">
                                        <span class="input-group-text">ครั้ง/นาที</span>
                                    </div>
                                    <small class="text-muted">อัตราการหายใจ</small>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-heart"></i></span>
                                        <input type="text" class="form-control measurement-input" placeholder="120/80">
                                        <span class="input-group-text">mmHg</span>
                                    </div>
                                    <small class="text-muted">ความดันโลหิต</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">วันที่ตรวจ</label>
                                    <input type="date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Behavior Assessment -->
                    <div class="card behavior-card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-emoji-smile me-2"></i>การประเมินพฤติกรรมของเด็ก
                            </div>

                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>สังเกตพฤติกรรมเด็ก:</strong> ความร่วมมือ ก้าวร้าว ไม่สบาย ไม่สามารถควบคุมอารมณ์ และอื่นๆ
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check form-check-inline me-4">
                                        <input class="form-check-input custom-checkbox" type="radio" name="behavior" id="behaviorNone" value="none">
                                        <label class="form-check-label fw-bold text-success" for="behaviorNone">
                                            <i class="bi bi-check-circle me-1"></i>ไม่มี
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input custom-checkbox" type="radio" name="behavior" id="behaviorHas" value="has">
                                        <label class="form-check-label fw-bold text-warning" for="behaviorHas">
                                            <i class="bi bi-exclamation-triangle me-1"></i>มี
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3" id="behaviorDetail" style="display: none;">
                                <label class="form-label fw-bold">ระบุพฤติกรรมที่พบ:</label>
                                <textarea class="form-control" rows="3" placeholder="อธิบายพฤติกรรมที่สังเกตพบ..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Measurements -->
                    <div class="card form-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-rulers me-2"></i>การตรวจร่างกาย
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-arrow-up"></i></span>
                                        <input type="number" class="form-control measurement-input" step="0.1" placeholder="120.5" id="height">
                                        <span class="input-group-text">เซนติเมตร</span>
                                    </div>
                                    <label class="form-label fw-bold mt-1">ส่วนสูง</label>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                                        <input type="number" class="form-control measurement-input" step="0.1" placeholder="25.5" id="weight">
                                        <span class="input-group-text">กิโลกรัม</span>
                                    </div>
                                    <label class="form-label fw-bold mt-1">น้ำหนัก</label>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-circle"></i></span>
                                        <input type="number" class="form-control measurement-input" step="0.1" placeholder="52.0">
                                        <span class="input-group-text">เซนติเมตร</span>
                                    </div>
                                    <label class="form-label fw-bold mt-1">รอบศีรษะ</label>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">BMI</span>
                                        <input type="number" class="form-control measurement-input" step="0.1" readonly id="bmi">
                                        <span class="input-group-text">kg/m²</span>
                                    </div>
                                    <label class="form-label fw-bold mt-1">ดัชนีมวลกาย (คำนวณอัตโนมัติ)</label>
                                </div>
                            </div>

                            <!-- Weight for Age Table -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <span class="badge badge-custom">น้ำหนักตามอายุ</span>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table custom-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="background-color:#e3f2fd;">น้อยกว่าเกณฑ์</th>
                                                <th style="background-color:#e3f2fd;">ค่อนข้างน้อย</th>
                                                <th style="background-color:#e3f2fd;">ตามเกณฑ์</th>
                                                <th style="background-color:#e3f2fd;">ค่อนข้างมาก</th>
                                                <th style="background-color:#e3f2fd;">มากกว่าเกณฑ์</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightAge[]" value="น้อยกว่าเกณฑ์"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightAge[]" value="ค่อนข้างน้อย"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightAge[]" value="ตามเกณฑ์"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightAge[]" value="ค่อนข้างมาก"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightAge[]" value="มากกว่าเกณฑ์"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Height for Age Table -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <span class="badge badge-custom">ส่วนสูงตามอายุ</span>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table custom-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="background-color:#e3f2fd;">เตี้ย</th>
                                                <th style="background-color:#e3f2fd;">ค่อนข้างเตี้ย</th>
                                                <th style="background-color:#e3f2fd;">ตามเกณฑ์</th>
                                                <th style="background-color:#e3f2fd;">ค่อนข้างสูง</th>
                                                <th style="background-color:#e3f2fd;">สูง</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="checkbox" class="custom-checkbox" name="heightAge[]" value="เตี้ย"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="heightAge[]" value="ค่อนข้างเตี้ย"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="heightAge[]" value="ตามเกณฑ์"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="heightAge[]" value="ค่อนข้างสูง"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="heightAge[]" value="สูง"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Weight for Height Table -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <span class="badge badge-custom">น้ำหนักตามส่วนสูง</span>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table custom-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="background-color:#e3f2fd;">ผอม</th>
                                                <th style="background-color:#e3f2fd;">ค่อนข้างผอม</th>
                                                <th style="background-color:#e3f2fd;">สมส่วน</th>
                                                <th style="background-color:#e3f2fd;">ท้วม</th>
                                                <th style="background-color:#e3f2fd;">เริ่มอ้วน</th>
                                                <th style="background-color:#e3f2fd;">อ้วน</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightHeight[]" value="ผอม"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightHeight[]" value="ค่อนข้างผอม"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightHeight[]" value="สมส่วน"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightHeight[]" value="ท้วม"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightHeight[]" value="เริ่มอ้วน"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="weightHeight[]" value="อ้วน"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Head Circumference Percentile -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <span class="badge badge-custom">รอบศีรษะ (เปอร์เซ็นไทล์)</span>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table custom-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="background-color:#e3f2fd;">น้อยกว่า 3<br>เปอร์เซ็นไทล์</th>
                                                <th style="background-color:#e3f2fd;">3-15<br>เปอร์เซ็นไทล์</th>
                                                <th style="background-color:#e3f2fd;">15-50<br>เปอร์เซ็นไทล์</th>
                                                <th style="background-color:#e3f2fd;">50-85<br>เปอร์เซ็นไทล์</th>
                                                <th style="background-color:#e3f2fd;">85-97<br>เปอร์เซ็นไทล์</th>
                                                <th style="background-color:#e3f2fd;">มากกว่า 97<br>เปอร์เซ็นไทล์</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="checkbox" class="custom-checkbox" name="headCirc[]" value="น้อยกว่า 3"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="headCirc[]" value="3-15"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="headCirc[]" value="15-50"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="headCirc[]" value="50-85"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="headCirc[]" value="85-97"></td>
                                                <td><input type="checkbox" class="custom-checkbox" name="headCirc[]" value="มากกว่า 97"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Development Assessment Table -->
                    <div class="card form-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-graph-up me-2"></i>ประเมินพัฒนาการทั้ง 5 ด้าน
                            </div>

                            <div class="table-responsive">
                                <table class="table development-table mb-0">
                                    <thead>
                                        <tr>
                                            <th colspan="3" style="width:20%; background-color:#e3f2fd;">การเคลื่อนไหว(GM)</th>
                                            <th colspan="3" style="width:20%; background-color:#fce4ec;">มือเก็บและสิ่งปลีกย่อย(FM)</th>
                                            <th colspan="3" style="width:20%; background-color:#e8f5e9;">เข้าใจภาษา(RL)</th>
                                            <th colspan="3" style="width:20%; background-color:#fff3e0;">ใช้ภาษา(EL)</th>
                                            <th colspan="3" style="width:20%; background-color:#f3e5f5;">ช่วยเหลือตนเองและสังคม(PS)</th>
                                        </tr>
                                        <tr>
                                            <th style="background-color:#e3f2fd;">ช้า</th>
                                            <th style="background-color:#e3f2fd;">ปกติ</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#fce4ec;">ช้า</th>
                                            <th style="background-color:#fce4ec;">ปกติ</th>
                                            <th style="background-color:#fce4ec;">ผ่าน</th>
                                            <th style="background-color:#e8f5e9;">ช้า</th>
                                            <th style="background-color:#e8f5e9;">ปกติ</th>
                                            <th style="background-color:#e8f5e9;">ผ่าน</th>
                                            <th style="background-color:#fff3e0;">ช้า</th>
                                            <th style="background-color:#fff3e0;">ปกติ</th>
                                            <th style="background-color:#fff3e0;">ผ่าน</th>
                                            <th style="background-color:#f3e5f5;">ช้า</th>
                                            <th style="background-color:#f3e5f5;">ปกติ</th>
                                            <th style="background-color:#f3e5f5;">ผ่าน</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <!-- GM -->
                                            <td><input type="checkbox" class="custom-checkbox" name="gm_assessment" value="slow"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="gm_assessment" value="normal"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="gm_assessment" value="pass"></td>
                                            <!-- FM -->
                                            <td><input type="checkbox" class="custom-checkbox" name="fm_assessment" value="slow"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="fm_assessment" value="normal"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="fm_assessment" value="pass"></td>
                                            <!-- RL -->
                                            <td><input type="checkbox" class="custom-checkbox" name="rl_assessment" value="slow"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="rl_assessment" value="normal"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="rl_assessment" value="pass"></td>
                                            <!-- EL -->
                                            <td><input type="checkbox" class="custom-checkbox" name="el_assessment" value="slow"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="el_assessment" value="normal"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="el_assessment" value="pass"></td>
                                            <!-- PS -->
                                            <td><input type="checkbox" class="custom-checkbox" name="ps_assessment" value="slow"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="ps_assessment" value="normal"></td>
                                            <td><input type="checkbox" class="custom-checkbox" name="ps_assessment" value="pass"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Examination -->
                    <div class="card examination-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-stethoscope me-2"></i>ผลการตรวจร่างกาย
                            </div>

                            <!-- ✅ ฟอร์มตรวจร่างกายทั้งหมด โดยใช้ Bootstrap เท่านั้น -->

                            <!-- รายการตรวจร่างกายทั้งหมด -->

                            <!-- หัวใจ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">หัวใจ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="heart_normal" name="heart[]" value="normal">
                                        <label class="form-check-label" for="heart_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="heart_abnormal" name="heart[]" value="abnormal">
                                        <label class="form-check-label" for="heart_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ตับ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ตับ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="liver_normal" name="liver[]" value="normal">
                                        <label class="form-check-label" for="liver_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="liver_abnormal" name="liver[]" value="abnormal">
                                        <label class="form-check-label" for="liver_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ม้าม -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ม้าม</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="spleen_normal" name="spleen[]" value="normal">
                                        <label class="form-check-label" for="spleen_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="spleen_abnormal" name="spleen[]" value="abnormal">
                                        <label class="form-check-label" for="spleen_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ต่อมน้ำเหลือง -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ต่อมน้ำเหลือง</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lymph_normal" name="lymph[]" value="normal">
                                        <label class="form-check-label" for="lymph_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lymph_abnormal" name="lymph[]" value="abnormal">
                                        <label class="form-check-label" for="lymph_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ตา -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ตา</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="eyes_normal" name="eyes[]" value="normal">
                                        <label class="form-check-label" for="eyes_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="eyes_abnormal" name="eyes[]" value="abnormal">
                                        <label class="form-check-label" for="eyes_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- หูและการได้ยิน -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">หูและการได้ยิน</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="ears_normal" name="ears[]" value="normal">
                                        <label class="form-check-label" for="ears_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="ears_abnormal" name="ears[]" value="abnormal">
                                        <label class="form-check-label" for="ears_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- จมูก -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">จมูก</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="nose_normal" name="nose[]" value="normal">
                                        <label class="form-check-label" for="nose_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="nose_abnormal" name="nose[]" value="abnormal">
                                        <label class="form-check-label" for="nose_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ปอดและหลอดลม -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ปอดและหลอดลม</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="respiratory_normal" name="respiratory[]" value="normal">
                                        <label class="form-check-label" for="respiratory_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="respiratory_abnormal" name="respiratory[]" value="abnormal">
                                        <label class="form-check-label" for="respiratory_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ท้องและปอด -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ท้องและปอด</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="digestive_normal" name="digestive[]" value="normal">
                                        <label class="form-check-label" for="digestive_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="digestive_abnormal" name="digestive[]" value="abnormal">
                                        <label class="form-check-label" for="digestive_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- การผายใส -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">การผายใส</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="genital_normal" name="genital[]" value="normal">
                                        <label class="form-check-label" for="genital_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="genital_abnormal" name="genital[]" value="abnormal">
                                        <label class="form-check-label" for="genital_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ฟัน -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ฟัน</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="dental_normal" name="dental[]" value="normal">
                                        <label class="form-check-label" for="dental_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="dental_abnormal" name="dental[]" value="abnormal">
                                        <label class="form-check-label" for="dental_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ผิวหนัง -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ผิวหนัง</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="skin_normal" name="skin[]" value="normal">
                                        <label class="form-check-label" for="skin_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="skin_abnormal" name="skin[]" value="abnormal">
                                        <label class="form-check-label" for="skin_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- แขนขา -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">แขนขา</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="extremities_normal" name="extremities[]" value="normal">
                                        <label class="form-check-label" for="extremities_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="extremities_abnormal" name="extremities[]" value="abnormal">
                                        <label class="form-check-label" for="extremities_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- อื่น ๆ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">อื่น ๆ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="others_normal" name="others[]" value="normal">
                                        <label class="form-check-label" for="others_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="others_abnormal" name="others[]" value="abnormal">
                                        <label class="form-check-label" for="others_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Neurological Examination -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-brain me-2"></i>ระบบประสาท (Neurological Examination)
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">ปฏิกิริยาขั้นพื้นฐาน</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="checkbox" id="neuro_normal" name="neuro[]" value="normal">
                                        <label class="form-check-label" for="neuro_normal">ปกติ</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="checkbox" id="neuro_abnormal" name="neuro[]" value="abnormal">
                                        <label class="form-check-label" for="neuro_abnormal">ผิดปกติ ระบุ</label>
                                    </div>

                                    <input type="text" class="form-control dotted-line" placeholder="ระบุรายละเอียดหากผิดปกติ" style="max-width: 30rem;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">การเคลื่อนไหวร่างกาย กล้ามเนื้อและเส้นประสาท</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="checkbox" id="movement_normal" name="movement[]" value="normal">
                                        <label class="form-check-label" for="movement_normal">ปกติ</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="checkbox" id="movement_abnormal" name="movement[]" value="abnormal">
                                        <label class="form-check-label" for="movement_abnormal">ผิดปกติ ระบุ</label>
                                    </div>

                                    <input type="text" class="form-control" placeholder="ระบุรายละเอียดหากผิดปกติ" style="max-width: 30rem;">
                                </div>
                            </div>

                        </div>
                    </div>


                    <!-- Recommendations Section -->
                    <div class="card recommendations-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-clipboard-check me-2"></i>คำแนะนำ
                            </div>

                            <div class="mb-3">
                                <textarea class="form-control" rows="6" placeholder="กรอกคำแนะนำและข้อสังเกต..." style="border: none; border-bottom: 1px dotted #6c757d; background: transparent; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="signature-section">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <p class="fw-bold mb-1">ลายเซ็น</p>
                                    <p class="small text-muted">แพทย์ผู้ตรวจ</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Print Section -->
                    <div class="print-section">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <button type="button" class="btn btn-custom w-100" onclick="saveForm()">
                                    <i class="bi bi-floppy me-2"></i>บันทึกข้อมูล
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary w-100" onclick="printForm()">
                                    <i class="bi bi-printer me-2"></i>พิมพ์แบบฟอร์ม
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-secondary w-100" onclick="resetForm()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>ล้างข้อมูล
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Calculate BMI automatically
        function calculateBMI() {
            const height = parseFloat(document.getElementById('height').value);
            const weight = parseFloat(document.getElementById('weight').value);
            const bmiField = document.getElementById('bmi');

            if (height && weight) {
                const heightInM = height / 100;
                const bmi = (weight / (heightInM * heightInM)).toFixed(1);
                bmiField.value = bmi;

                // Add BMI status badge
                let status = '';
                let badgeClass = '';
                if (bmi < 18.5) {
                    status = 'น้ำหนักน้อย';
                    badgeClass = 'bg-warning';
                } else if (bmi < 25) {
                    status = 'ปกติ';
                    badgeClass = 'bg-success';
                } else if (bmi < 30) {
                    status = 'เริ่มอ้วน';
                    badgeClass = 'bg-warning';
                } else {
                    status = 'อ้วน';
                    badgeClass = 'bg-danger';
                }

                // Show BMI status
                showBMIStatus(status, badgeClass);
            }
        }

        function showBMIStatus(status, badgeClass) {
            // Remove existing badge
            const existingBadge = document.querySelector('.bmi-status');
            if (existingBadge) {
                existingBadge.remove();
            }

            // Add new badge
            const bmiField = document.getElementById('bmi');
            const badge = document.createElement('span');
            badge.className = `badge ${badgeClass} bmi-status ms-2`;
            badge.textContent = status;
            bmiField.parentNode.appendChild(badge);
        }

        // Show/hide behavior detail
        document.querySelectorAll('input[name="behavior"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const detailDiv = document.getElementById('behaviorDetail');
                if (this.value === 'has') {
                    detailDiv.style.display = 'block';
                } else {
                    detailDiv.style.display = 'none';
                }
            });
        });

        // Ensure only one checkbox is selected per row
        function setupSingleSelection() {
            const tables = ['weightAge', 'heightAge', 'weightHeight', 'headCirc'];

            tables.forEach(tableName => {
                const checkboxes = document.querySelectorAll(`input[name="${tableName}[]"]`);
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            checkboxes.forEach(cb => {
                                if (cb !== this) cb.checked = false;
                            });
                        }
                    });
                });
            });
        }

        // Form functions
        function saveForm() {
            // Validate required fields
            const requiredFields = ['height', 'weight'];
            let isValid = true;

            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (isValid) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>สำเร็จ!</strong> บันทึกข้อมูลการตรวจสุขภาพเรียบร้อยแล้ว
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.querySelector('.form-container .p-4').insertBefore(alert, document.querySelector('.form-container .p-4').firstChild);

                // Auto hide after 3 seconds
                setTimeout(() => {
                    if (alert) alert.remove();
                }, 3000);
            } else {
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>ข้อผิดพลาด!</strong> กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.querySelector('.form-container .p-4').insertBefore(alert, document.querySelector('.form-container .p-4').firstChild);
            }
        }

        function printForm() {
            window.print();
        }

        function resetForm() {
            if (confirm('คุณต้องการล้างข้อมูลทั้งหมดใช่หรือไม่?')) {
                document.querySelectorAll('input').forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
                document.querySelectorAll('textarea').forEach(textarea => {
                    textarea.value = '';
                });
                document.getElementById('behaviorDetail').style.display = 'none';

                // Remove BMI status badge
                const bmiStatus = document.querySelector('.bmi-status');
                if (bmiStatus) bmiStatus.remove();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Setup event listeners
            document.getElementById('height').addEventListener('input', calculateBMI);
            document.getElementById('weight').addEventListener('input', calculateBMI);

            // Setup single selection for tables
            setupSingleSelection();

            // Auto-fill current date
            const today = new Date().toISOString().split('T')[0];
            const dateInputs = document.querySelectorAll('input[type="date"]');
            if (dateInputs.length > 0) {
                dateInputs[0].value = today; // Set first date input to today
            }
        });
    </script>
</body>

</html>