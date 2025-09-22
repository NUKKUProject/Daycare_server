<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
require_once __DIR__ . '/../../include/function/child_functions.php';

// รับค่าพารามิเตอร์การพิมพ์
$group = $_GET['group'] ?? 'all';
$classroom = $_GET['classroom'] ?? 'all';
$withQR = isset($_GET['with_qr']) ? (bool)$_GET['with_qr'] : true;
$withoutQR = isset($_GET['without_qr']) ? (bool)$_GET['without_qr'] : false;

// ดึงข้อมูลเด็กตามเงื่อนไข
$data = getChildrenGroupedByTab($group);

// กรองข้อมูลตามห้องเรียนและสถานะ QR Code
$filteredData = [];
foreach ($data as $groupData) {
    $filteredClassrooms = [];
    foreach ($groupData['classrooms'] as $classroomData) {
        if ($classroom === 'all' || $classroomData['classroom'] === $classroom) {
            $filteredChildren = [];
            foreach ($classroomData['children'] as $child) {
                $hasQR = !empty($child['qr_code']);
                if (($hasQR && $withQR) || (!$hasQR && $withoutQR)) {
                    $filteredChildren[] = $child;
                }
            }
            if (!empty($filteredChildren)) {
                $classroomData['children'] = $filteredChildren;
                $filteredClassrooms[] = $classroomData;
            }
        }
    }
    if (!empty($filteredClassrooms)) {
        $groupData['classrooms'] = $filteredClassrooms;
        $filteredData[] = $groupData;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์ QR Code</title>
    <link href="../../../public/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../public/assets/css/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }

        .print-header {
            background: linear-gradient(135deg, #0061f2 0%, #6900f2 100%);
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .print-header h1 {
            font-size: 1.5rem;
            margin: 0;
        }

        .print-header p {
            margin: 0.5rem 0 0;
            opacity: 0.8;
        }

        .group-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
        }

        .group-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .group-header h3 {
            color: #2c3e50;
            font-size: 1.25rem;
            margin: 0;
        }

        .classroom-section {
            margin-bottom: 2rem;
        }

        .classroom-header {
            background: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .classroom-header h4 {
            color: #495057;
            font-size: 1rem;
            margin: 0;
        }

        .qr-card {
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            background: white;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
        }

        .qr-card:hover {
            transform: translateY(-5px);
        }

        .qr-image-wrapper {
            background: #f8f9fa;
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }

        .qr-image-wrapper img {
            max-width: 150px;
            height: auto;
            border-radius: 0.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        .card-title {
            color: #2c3e50;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .card-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .no-qr-placeholder {
            background: #f8f9fa;
            padding: 2rem;
            text-align: center;
            border-radius: 0.5rem;
        }

        .no-qr-placeholder i {
            font-size: 3rem;
            color: #adb5bd;
        }

        .action-buttons {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .action-buttons .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-left: 0.5rem;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }

            .print-header, .action-buttons {
                display: none !important;
            }

            .group-section {
                box-shadow: none;
                padding: 0;
                margin-bottom: 1rem;
            }

            .group-header {
                border-bottom: 1px solid #dee2e6;
            }

            .classroom-section {
                margin-bottom: 1rem;
            }

            .qr-card {
                box-shadow: none;
                border: 1px solid #dee2e6;
                page-break-inside: avoid;
            }

            .qr-image-wrapper {
                background: none;
                padding: 0.5rem;
            }

            .qr-image-wrapper img {
                max-width: 120px;
            }

            .card-body {
                padding: 0.5rem;
            }

            .card-title {
                font-size: 12px;
            }

            .card-text {
                font-size: 10px;
            }

            .row {
                display: flex;
                flex-wrap: wrap;
            }

            .col-md-4 {
                width: 33.333333%;
                padding: 0.5rem;
            }

            * {
                color: black !important;
                background: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Header -->
        <div class="print-header no-print">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-qr-code me-2"></i>พิมพ์ QR Code</h1>
                    <p>กลุ่ม: <?= $group === 'all' ? 'ทั้งหมด' : ($group === 'big' ? 'เด็กโต' : ($group === 'medium' ? 'เด็กกลาง' : 'เตรียมอนุบาล')) ?></p>
                    <p>ห้อง: <?= $classroom === 'all' ? 'ทั้งหมด' : $classroom ?></p>
                </div>
                <div class="action-buttons">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>พิมพ์
                </button>
                <button class="btn btn-secondary" onclick="window.close()">
                    <i class="bi bi-x-circle me-2"></i>ปิด
                </button>
                </div>
            </div>
        </div>

        <?php foreach ($filteredData as $groupData): ?>
            <div class="group-section">
                <div class="group-header">
                    <h3>
                    <i class="bi bi-people-fill me-2"></i>
                    <?= htmlspecialchars($groupData['group']) ?>
                </h3>
                </div>
                
                <?php foreach ($groupData['classrooms'] as $classroomData): ?>
                    <div class="classroom-section">
                        <div class="classroom-header">
                            <h4>
                                <i class="bi bi-door-open me-2"></i>
                                ห้อง: <?= htmlspecialchars($classroomData['classroom']) ?>
                        </h4>
                        </div>
                        <div class="row g-4">
                            <?php foreach ($classroomData['children'] as $child): ?>
                                <div class="col-md-4">
                                    <div class="card qr-card">
                                        <div class="qr-image-wrapper text-center">
                                                <?php if (!empty($child['qr_code'])): ?>
                                                <img src="<?= htmlspecialchars($child['qr_code']) ?>" class="img-fluid" alt="QR Code">
                                                <?php else: ?>
                                                    <div class="no-qr-placeholder">
                                                        <i class="bi bi-qr-code-scan"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <div class="card-body text-center">
                                            <h5 class="card-title">
                                                <?= htmlspecialchars($child['prefix_th']) ?>
                                                <?= htmlspecialchars($child['firstname_th']) ?>
                                                <?= htmlspecialchars($child['lastname_th']) ?>
                                            </h5>
                                            <p class="card-text">
                                                <i class="bi bi-people-fill me-1"></i>
                                                <?= htmlspecialchars($child['child_group']) ?>
                                            </p>
                                            <p class="card-text">
                                                <i class="bi bi-person-badge me-1"></i>
                                                <?= htmlspecialchars($child['nickname']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="../../../public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 