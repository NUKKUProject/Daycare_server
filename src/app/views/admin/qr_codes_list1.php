<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../../include/function/child_functions.php';

// รับค่า tab และ room จาก URL
$currentTab = $_GET['tab'] ?? 'all';
$data = getChildrenGroupedByTab($currentTab);
?>

<main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">รายการ QR Code</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="#">หน้าหลัก</a></li>
                            <li class="breadcrumb-item active">รายการ QR Code</li>
                        </ol>
                    </nav>
                </div>
                <div class="action-buttons d-flex gap-2">
                    <button class="btn btn-primary" onclick="showBulkGenerateModal()">
                        <i class="bi bi-qr-code me-2"></i>สร้าง QR Code จำนวนมาก
                    </button>
                    <button class="btn btn-light" onclick="showPrintOptions()">
                        <i class="bi bi-printer me-2"></i>พิมพ์
                    </button>
                    <button class="btn btn-success" onclick="download()">
                        <i class="bi bi-download"></i> ดาวน์โหลด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Download Options Modal -->
    <div class="modal fade" id="download" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ตัวเลือกการโหลด Qr Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="downloadForm">
                        <div class="mb-3">
                            <label class="form-label">กลุ่มเรียน:</label>
                            <select class="form-select" id="downloadGroup" name="downloadGroup">
                                <option value="all">ทั้งหมด</option>
                                <option value="เด็กโต">เด็กโต</option>
                                <option value="เด็กกลาง">เด็กกลาง</option>
                                <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ห้องเรียน:</label>
                            <select class="form-select" id="downloadClassroom" name="downloadClassroom">
                                <option value="all">ทั้งหมด</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="btnDownload()"><i class="bi bi-download"></i> ดาวน์โหลด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Options Modal -->
    <div class="modal fade" id="printOptionsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ตัวเลือกการพิมพ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="printOptionsForm">
                        <div class="mb-3">
                            <label class="form-label">กลุ่มเรียน:</label>
                            <select class="form-select" id="printGroup" name="printGroup">
                                <option value="all">ทั้งหมด</option>
                                <option value="big">เด็กโต</option>
                                <option value="medium">เด็กกลาง</option>
                                <option value="prep">เตรียมอนุบาล</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ห้องเรียน:</label>
                            <select class="form-select" id="printClassroom" name="printClassroom">
                                <option value="all">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">แสดงเฉพาะ:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="printWithQR" name="printWithQR" checked>
                                <label class="form-check-label" for="printWithQR">
                                    เด็กที่มี QR Code
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="printWithoutQR" name="printWithoutQR">
                                <label class="form-check-label" for="printWithoutQR">
                                    เด็กที่ยังไม่มี QR Code
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="printSelectedQR()">พิมพ์</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Generate QR Code Modal -->
    <div class="modal fade" id="bulkGenerateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">สร้าง QR Code จำนวนมาก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bulkGenerateForm">
                        <div class="mb-3">
                            <label class="form-label">กลุ่มเรียน:</label>
                            <select class="form-select" id="bulkGroup" name="bulkGroup">
                                <option value="all">ทั้งหมด</option>
                                <option value="big">เด็กโต</option>
                                <option value="medium">เด็กกลาง</option>
                                <option value="prep">เตรียมอนุบาล</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ห้องเรียน:</label>
                            <select class="form-select" id="bulkClassroom" name="bulkClassroom">
                                <option value="all">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สร้างเฉพาะ:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bulkWithoutQR" name="bulkWithoutQR" checked>
                                <label class="form-check-label" for="bulkWithoutQR">
                                    เด็กที่ยังไม่มี QR Code
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="bulkGenerateQR()">สร้าง QR Code</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentTab === 'all' ? 'active' : '' ?>" href="?tab=all">
                                <i class="bi bi-grid-3x3-gap"></i> ทั้งหมด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentTab === 'big' ? 'active' : '' ?>" href="?tab=big">
                                <i class="bi bi-people"></i> เด็กโต
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentTab === 'medium' ? 'active' : '' ?>" href="?tab=medium">
                                <i class="bi bi-people"></i> เด็กกลาง
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentTab === 'prep' ? 'active' : '' ?>" href="?tab=prep">
                                <i class="bi bi-people"></i> เตรียมอนุบาล
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <div class="search-wrapper">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text"
                            class="form-control search-input"
                            id="searchInput"
                            placeholder="ค้นหาชื่อหรือรหัสนักเรียน...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Codes Grid with Loading State -->
    <div class="qr-codes-container" id="qrCodesGrid">
        <!-- Loading Skeleton -->
        <div class="loading-skeleton" style="display: none;">
            <!-- Add skeleton layout here -->
        </div>

        <!-- Actual Content -->
        <?php foreach ($data as $groupData): ?>
            <div class="group-section mb-4">
                <div class="group-header">
                    <h3 class="h5 mb-0">
                        <i class="bi bi-people-fill me-2"></i>
                        <?= htmlspecialchars($groupData['group']) ?>
                    </h3>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php foreach ($groupData['classrooms'] as $classroomData): ?>
                            <div class="classroom-section mb-4">
                                <h4 class="h6 mb-3">
                                    <i class="bi bi-door-open me-2"></i>ห้อง: <?= htmlspecialchars($classroomData['classroom']) ?>
                                </h4>
                                <div class="row g-4">
                                    <?php
                                    $hasQRCode = [];
                                    $noQRCode = [];

                                    // แยกเด็กที่มีและไม่มี QR Code
                                    foreach ($classroomData['children'] as $child) {
                                        if (!empty($child['qr_code'])) {
                                            $hasQRCode[] = $child;
                                        } else {
                                            $noQRCode[] = $child;
                                        }
                                    }
                                    ?>

                                    <!-- แสดงเด็กที่มี QR Code -->
                                    <?php if (!empty($hasQRCode)): ?>
                                        <div class="col-12">
                                            <h5 class="text-success mb-3">
                                                <i class="bi bi-check-circle me-2"></i>มี QR Code
                                            </h5>
                                        </div>
                                        <?php foreach ($hasQRCode as $child): ?>
                                            <div class="col-md-3">
                                                <div class="card h-100 qr-card">
                                                    <div class="card-body text-center">
                                                        <div class="qr-image-wrapper mb-3">
                                                            <img src="<?= htmlspecialchars($child['qr_code']) ?>"
                                                                class="img-fluid rounded"
                                                                alt="QR Code">
                                                        </div>
                                                        <h5 class="card-title h6">
                                                            <?= htmlspecialchars($child['prefix_th']) ?>
                                                            <?= htmlspecialchars($child['firstname_th']) ?>
                                                            <?= htmlspecialchars($child['lastname_th']) ?>
                                                        </h5>
                                                        <p class="card-text small text-muted">
                                                            <i class="bi bi-people-fill me-2"></i>
                                                            กลุ่มเรียน: <?= htmlspecialchars($child['child_group']) ?>
                                                        </p>
                                                        <p class="card-text small text-muted">
                                                            <i class="bi bi-door-open me-2"></i>
                                                            ห้องเรียน: <?= htmlspecialchars($child['classroom']) ?>
                                                        </p>
                                                        <p class="card-text small text-muted">
                                                            <i class="bi bi-person-badge me-1"></i>
                                                            ชื่อเล่น: <?= htmlspecialchars($child['nickname']) ?>
                                                        </p>
                                                        <div class="btn-group w-100">
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                onclick="viewQRCode('<?= htmlspecialchars($child['qr_code']) ?>', '<?= htmlspecialchars($child['studentid']) ?>')">
                                                                <i class="bi bi-eye"></i> ดู
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-success"
                                                                onclick="downloadQRCode('<?= htmlspecialchars($child['qr_code']) ?>', '<?= htmlspecialchars($child['prefix_th']) . '_' . htmlspecialchars($child['firstname_th']) . '_' . htmlspecialchars($child['lastname_th']) ?>')">
                                                                <i class="bi bi-download"></i> ดาวน์โหลด
                                                            </button>
                                                            <?php if (getUserRole() === 'admin'): ?>
                                                                <button class="btn btn-sm btn-outline-danger"
                                                                    onclick="deleteQRCode('<?= htmlspecialchars($child['studentid']) ?>', '<?= htmlspecialchars($child['qr_code']) ?>')">
                                                                    <i class="bi bi-trash"></i> ลบ
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- แสดงเด็กที่ไม่มี QR Code -->
                                    <?php if (!empty($noQRCode)): ?>
                                        <div class="col-12 mt-4">
                                            <h5 class="text-danger mb-3">
                                                <i class="bi bi-exclamation-circle me-2"></i>ยังไม่มี QR Code
                                            </h5>
                                        </div>
                                        <?php foreach ($noQRCode as $child): ?>
                                            <div class="col-md-3">
                                                <div class="card h-100 qr-card">
                                                    <div class="card-body text-center">
                                                        <div class="qr-image-wrapper mb-3">
                                                            <div class="no-qr-placeholder">
                                                                <i class="bi bi-qr-code-scan text-muted" style="font-size: 3rem;"></i>
                                                            </div>
                                                        </div>
                                                        <h5 class="card-title h6">
                                                            <?= htmlspecialchars($child['prefix_th']) ?>
                                                            <?= htmlspecialchars($child['firstname_th']) ?>
                                                            <?= htmlspecialchars($child['lastname_th']) ?>
                                                        </h5>
                                                        <p class="card-text small text-muted">
                                                            <i class="bi bi-people-fill me-2"></i>
                                                            กลุ่มเรียน: <?= htmlspecialchars($child['child_group']) ?>
                                                        </p>
                                                        <p class="card-text small text-muted">
                                                            <i class="bi bi-door-open me-2"></i>
                                                            ห้องเรียน: <?= htmlspecialchars($child['classroom']) ?>
                                                        </p>
                                                        <p class="card-text small text-muted">
                                                            <i class="bi bi-person-badge me-1"></i>
                                                            ชื่อเล่น: <?= htmlspecialchars($child['nickname']) ?>
                                                        </p>
                                                        <div class="btn-group w-100">
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                onclick="generateQRCode('<?= htmlspecialchars($child['studentid']) ?>')">
                                                                <i class="bi bi-plus-circle"></i> สร้าง QR Code
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- ปรับปรุง Modal -->
<div class="modal fade qr-modal" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-qr-code me-2"></i>QR Code
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="qr-preview mb-3">
                    <img id="modalQRCode" src="" class="img-fluid rounded shadow" alt="QR Code">
                </div>
                <div class="student-info mb-3">
                    <!-- Student info will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Modern UI Styles */
    .main-content {
        background-color: #f8f9fa;
        min-height: 100vh;
        padding-bottom: 2rem;
    }

    /* Header Styles */
    .page-header {
        background: linear-gradient(135deg, #0061f2 0%, #6900f2 100%);
        padding: 2rem 0;
        margin-bottom: 2rem;
        color: white;
        border-radius: 0 0 0.5rem 0.5rem;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
    }

    .breadcrumb-item.active {
        color: rgba(255, 255, 255, 0.6);
    }

    /* Card Styles */
    .filter-card {
        border: none;
        border-radius: 1rem;
        background: white;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
        margin-bottom: 1.5rem;
    }

    .qr-card {
        border-radius: 1rem;
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
    }

    .qr-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 1rem 3rem rgba(31, 45, 61, 0.125);
    }

    .qr-image-wrapper {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin: -1px;
        position: relative;
        overflow: hidden;
    }

    .qr-image-wrapper::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 100%);
        pointer-events: none;
    }

    .qr-image-wrapper img {
        max-width: 140px;
        transition: transform 0.3s ease;
    }

    .qr-card:hover .qr-image-wrapper img {
        transform: scale(1.05);
    }

    /* Button Styles */
    .action-buttons .btn {
        padding: 0.5rem 1rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        transition: all 0.2s;
    }

    .btn-outline-primary {
        border-width: 2px;
    }

    .btn-outline-primary:hover {
        background: linear-gradient(45deg, #0061f2, #6900f2);
        border-color: transparent;
    }

    /* Navigation Pills */
    .nav-pills {
        background: white;
        padding: 0.5rem;
        border-radius: 50rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .nav-pills .nav-link {
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        transition: all 0.2s;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(45deg, #0061f2, #6900f2);
        box-shadow: 0 4px 10px rgba(0, 97, 242, 0.3);
    }

    /* Search Input */
    .search-wrapper {
        position: relative;
    }

    .search-input {
        padding-left: 2.5rem;
        border-radius: 50rem;
        border: 2px solid #e0e5ec;
        box-shadow: none;
        transition: all 0.2s;
    }

    .search-input:focus {
        border-color: #0061f2;
        box-shadow: 0 0 0 0.2rem rgba(0, 97, 242, 0.15);
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    /* Group Headers */
    .group-header {
        background: linear-gradient(to right, #f8f9fa, white);
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #0061f2;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .qr-card {
        animation: fadeIn 0.5s ease forwards;
    }

    /* Loading Skeleton */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    /* Modal Styles */
    .qr-modal .modal-content {
        border: none;
        border-radius: 1rem;
        overflow: hidden;
    }

    .qr-modal .modal-header {
        background: linear-gradient(45deg, #0061f2, #6900f2);
        color: white;
        border: none;
    }

    .qr-modal .modal-body {
        padding: 2rem;
    }

    /* Print Styles */
    @media print {
        .qr-card {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .action-buttons,
        .nav-pills,
        .search-wrapper {
            display: none !important;
        }

        /* ปรับขนาดและการแสดงผลสำหรับการพิมพ์ */
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            background: none !important;
        }

        .page-header,
        .filter-card {
            display: none !important;
        }

        .qr-card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            transform: none !important;
            margin-bottom: 1rem !important;
        }

        .qr-image-wrapper {
            background: none !important;
            padding: 0.5rem !important;
        }

        .qr-image-wrapper img {
            max-width: 120px !important;
            height: auto !important;
            display: block !important;
            margin: 0 auto !important;
        }

        .card-body {
            padding: 0.5rem !important;
        }

        .btn-group {
            display: none !important;
        }

        /* จัดการ Grid Layout สำหรับการพิมพ์ */
        .row {
            display: flex !important;
            flex-wrap: wrap !important;
        }

        .col-md-3 {
            width: 25% !important;
            float: left !important;
            padding: 0.5rem !important;
        }

        /* ปรับขนาดตัวอักษร */
        .card-title {
            font-size: 12px !important;
            margin-bottom: 0.25rem !important;
        }

        .card-text {
            font-size: 10px !important;
            margin-bottom: 0.25rem !important;
        }

        /* ซ่อนเอฟเฟกต์ที่ไม่จำเป็น */
        .qr-image-wrapper::after {
            display: none !important;
        }

        /* ปรับสีให้เหมาะกับการพิมพ์ */
        * {
            color: black !important;
            background: none !important;
        }
    }

    .no-qr-placeholder {
        background: #f8f9fa;
        padding: 2rem;
        border-radius: 0.5rem;
        margin: -1px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 200px;
    }
</style>

<script>
    // ฟังก์ชันค้นหา
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const searchText = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.qr-card');

        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const cardParent = card.closest('.col-md-3');
            if (text.includes(searchText)) {
                cardParent.style.display = '';
            } else {
                cardParent.style.display = 'none';
            }
        });
    });

    // ฟังก์ชันดู QR Code
    function viewQRCode(src, studentId) {
        document.getElementById('modalQRCode').src = src;
        const modal = new bootstrap.Modal(document.getElementById('qrModal'));
        modal.show();
    }

    // ฟังก์ชันดาวน์โหลด QR Code
    function downloadQRCode(src, studentName) {
        const link = document.createElement('a');
        link.href = src;
        link.download = `qrcode_${studentName}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ฟังก์ชันแสดงตัวเลือกการพิมพ์
    function showPrintOptions() {
        const modal = new bootstrap.Modal(document.getElementById('printOptionsModal'));
        modal.show();
    }

    // ฟังก์ชันโหลดห้องเรียนตามกลุ่มที่เลือก
    document.getElementById('printGroup').addEventListener('change', function() {
        const group = this.value;
        const classroomSelect = document.getElementById('printClassroom');

        // ล้างตัวเลือกห้องเรียนเก่า
        classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';

        if (group !== 'all') {
            // แสดง loading state
            classroomSelect.disabled = true;
            classroomSelect.innerHTML = '<option value="">กำลังโหลด...</option>';

            // แปลงค่า group ให้ตรงกับฐานข้อมูล
            let childGroup = group;
            if (group === 'big') childGroup = 'เด็กโต';
            else if (group === 'medium') childGroup = 'เด็กกลาง';
            else if (group === 'prep') childGroup = 'เตรียมอนุบาล';

            // ดึงข้อมูลห้องเรียนจากฐานข้อมูล
            fetch(`../../include/function/get_classrooms.php?child_group=${childGroup}`)
                .then(response => response.json())
                .then(classrooms => {
                    // ล้างตัวเลือกห้องเรียน
                    classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';

                    // เพิ่มห้องเรียนที่ดึงมาได้
                    classrooms.forEach(classroom => {
                        const option = document.createElement('option');
                        option.value = classroom.classroom_name;
                        option.textContent = classroom.classroom_name;
                        classroomSelect.appendChild(option);
                    });

                    // เปิดใช้งาน select
                    classroomSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching classrooms:', error);
                    classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';
                    classroomSelect.disabled = false;
                });
        } else {
            classroomSelect.disabled = false;
        }
    });

    // ฟังก์ชันพิมพ์ QR Code ที่เลือก
    function printSelectedQR() {
        const group = document.getElementById('printGroup').value;
        const classroom = document.getElementById('printClassroom').value;
        const printWithQR = document.getElementById('printWithQR').checked;
        const printWithoutQR = document.getElementById('printWithoutQR').checked;

        // สร้าง URL พร้อมพารามิเตอร์
        let url = `print_qr_codes.php?group=${group}&classroom=${classroom}`;
        if (printWithQR) url += '&with_qr=1';
        if (printWithoutQR) url += '&without_qr=1';

        // เปิดหน้าพิมพ์ในหน้าต่างใหม่
        window.open(url, '_blank');

        // ปิด modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('printOptionsModal'));
        modal.hide();
    }

    // ฟังก์ชันพิมพ์
    function printCurrentView() {
        window.print();
    }

    // ฟังก์ชัน download
    function download() {
        const modal = new bootstrap.Modal(document.getElementById('download'));
        modal.show();
        // ฟังก์ชันโหลดห้องเรียนตามกลุ่มที่เลือก
        document.getElementById('downloadGroup').addEventListener('change', function() {
            const group = this.value;
            const classroomSelect = document.getElementById('downloadClassroom');

            // ล้างตัวเลือกห้องเรียนเก่า
            classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';

            if (group !== 'all') {
                // แสดง loading state
                classroomSelect.disabled = true;
                classroomSelect.innerHTML = '<option value="">กำลังโหลด...</option>';

                // แปลงค่า group ให้ตรงกับฐานข้อมูล
                let childGroup = group;
                if (group === 'big') childGroup = 'เด็กโต';
                else if (group === 'medium') childGroup = 'เด็กกลาง';
                else if (group === 'prep') childGroup = 'เตรียมอนุบาล';

                // ดึงข้อมูลห้องเรียนจากฐานข้อมูล
                fetch(`../../include/function/get_classrooms.php?child_group=${childGroup}`)
                    .then(response => response.json())
                    .then(classrooms => {
                        // ล้างตัวเลือกห้องเรียน
                        classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';

                        // เพิ่มห้องเรียนที่ดึงมาได้
                        classrooms.forEach(classroom => {
                            const option = document.createElement('option');
                            option.value = classroom.classroom_name;
                            option.textContent = classroom.classroom_name;
                            classroomSelect.appendChild(option);
                        });

                        // เปิดใช้งาน select
                        classroomSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error fetching classrooms:', error);
                        classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';
                        classroomSelect.disabled = false;
                    });
            } else {
                classroomSelect.disabled = false;
            }
        });
    }

    function btnDownload() {
        const group = document.getElementById('downloadGroup').value;
        const classroom = document.getElementById('downloadClassroom').value;
        const payload = {
            group: group,
            classroom: classroom
        };

        fetch('./download_qr_group_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.download_url) {
                    window.location.href = data.download_url; // หรือสร้าง <a> แล้ว click
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            });
    }

    // ฟังก์ชันลบ QR Code
    function deleteQRCode(studentId, qrCodePath) {
        Swal.fire({
            title: 'ยืนยันการลบ QR Code',
            text: "คุณต้องการลบ QR Code นี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // ส่งคำขอลบไปยัง API
                fetch('../../include/process/delete_qr_code.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: studentId,
                            qr_code_path: qrCodePath
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบสำเร็จ',
                                text: 'ลบ QR Code เรียบร้อยแล้ว',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
                                location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'เกิดข้อผิดพลาดในการลบ QR Code');
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

    // ฟังก์ชันสร้าง QR Code
    function generateQRCode(studentId) {
        Swal.fire({
            title: 'ยืนยันการสร้าง QR Code',
            text: "คุณต้องการสร้าง QR Code สำหรับนักเรียนคนนี้ใช่หรือไม่?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, สร้าง',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // ส่งคำขอสร้าง QR Code ไปยัง API
                fetch('../../include/function/generate-qr.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: studentId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สร้างสำเร็จ',
                                text: 'สร้าง QR Code เรียบร้อยแล้ว',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
                                location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'เกิดข้อผิดพลาดในการสร้าง QR Code');
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

    // ฟังก์ชันแสดง Modal สร้าง QR Code จำนวนมาก
    function showBulkGenerateModal() {
        const modal = new bootstrap.Modal(document.getElementById('bulkGenerateModal'));
        modal.show();
    }

    // ฟังก์ชันโหลดห้องเรียนตามกลุ่มที่เลือก (สำหรับการสร้างจำนวนมาก)
    document.getElementById('bulkGroup').addEventListener('change', function() {
        const group = this.value;
        const classroomSelect = document.getElementById('bulkClassroom');

        // ล้างตัวเลือกห้องเรียนเก่า
        classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';

        if (group !== 'all') {
            // แสดง loading state
            classroomSelect.disabled = true;
            classroomSelect.innerHTML = '<option value="">กำลังโหลด...</option>';

            // แปลงค่า group ให้ตรงกับฐานข้อมูล
            let childGroup = group;
            if (group === 'big') childGroup = 'เด็กโต';
            else if (group === 'medium') childGroup = 'เด็กกลาง';
            else if (group === 'prep') childGroup = 'เตรียมอนุบาล';

            // ดึงข้อมูลห้องเรียนจากฐานข้อมูล
            fetch(`../../include/function/get_classrooms.php?child_group=${childGroup}`)
                .then(response => response.json())
                .then(classrooms => {
                    // ล้างตัวเลือกห้องเรียน
                    classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';

                    // เพิ่มห้องเรียนที่ดึงมาได้
                    classrooms.forEach(classroom => {
                        const option = document.createElement('option');
                        option.value = classroom.classroom_name;
                        option.textContent = classroom.classroom_name;
                        classroomSelect.appendChild(option);
                    });

                    // เปิดใช้งาน select
                    classroomSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching classrooms:', error);
                    classroomSelect.innerHTML = '<option value="all">ทั้งหมด</option>';
                    classroomSelect.disabled = false;
                });
        } else {
            classroomSelect.disabled = false;
        }
    });

    // ฟังก์ชันสร้าง QR Code จำนวนมาก
    function bulkGenerateQR() {
        const group = document.getElementById('bulkGroup').value;
        const classroom = document.getElementById('bulkClassroom').value;
        const withoutQR = document.getElementById('bulkWithoutQR').checked;

        // แสดง loading state
        Swal.fire({
            title: 'กำลังสร้าง QR Code',
            text: 'กรุณารอสักครู่...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // ส่งคำขอสร้าง QR Code ไปยัง API
        fetch('../../include/function/generate-bulk-qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    group: group,
                    classroom: classroom,
                    without_qr: withoutQR
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สร้างสำเร็จ',
                        text: `สร้าง QR Code จำนวน ${data.count} รายการเรียบร้อยแล้ว`,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดในการสร้าง QR Code');
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
</script>