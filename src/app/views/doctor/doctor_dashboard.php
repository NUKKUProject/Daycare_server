<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['doctor']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?> 
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>


<?php
$children = getChildrenData();

// เพิ่มการเรียกใช้ฟังก์ชันที่จำเป็น
require_once __DIR__ . '/../../include/function/dashboard_functions.php';

// ดึงข้อมูลสำหรับ Dashboard
$totalStudents = getTotalStudents() ?? 0;
$totalStaff = getTotalStaff() ?? 0;
$attendanceRate = getAttendanceRate() ?? 0;
$totalActivities = getTotalActivities() ?? 0;

// ดึงข้อมูลสำหรับกราฟ
$monthlyAttendance = getMonthlyAttendance() ?? [];
$studentsByGroup = getStudentsByGroup() ?? [];
$staffByPosition = getStaffByPosition() ?? [];
?>

<style>
    .nav-tabs .nav-link {
        color: #495057;
        border: none;
        border-bottom: 2px solid transparent;
    }

    .nav-tabs .nav-link.active {
        color: #26648E;
        border-bottom: 2px solid #26648E;
        background: none;
    }

    .nav-pills .nav-link {
        color: #495057;
        border-radius: 20px;
        padding: 8px 20px;
        margin: 0 5px;
    }

    .nav-pills .nav-link.active {
        background-color: #26648E;
        color: white;
    }

    .card {
        border: none;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-title {
        font-weight: 600;
    }

    .card-title-graph {
        color: rgba(31, 102, 153, 0.91);
        font-weight: 600;
    }

    canvas {
        max-height: 300px;
    }
</style>

<main class="main-content d-flex justify-content-center align-items-center">

   
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <!-- การ์ดหลัก -->
                <div class="card border-0 shadow-lg welcome-card">
                    <div class="card-body p-5 text-center position-relative">

                        <!-- ไอคอนหลัก -->
                        <div class="mb-4">
                            <div class="welcome-icon-circle mx-auto mb-3">
                                <i class="fas fa-stethoscope fa-2x text-white"></i>
                            </div>
                        </div>

                        <!-- ข้อความต้อนรับ -->
                        <h2 class="display-5 fw-bold text-primary mb-3">
                            <i class="fas fa-heart text-danger me-2"></i>
                            ยินดีต้อนรับ
                        </h2>

                        <!-- ชื่อผู้ใช้ -->
                        <div class="mb-4">
                            <div class="user-badge d-inline-block">
                                <i class="fas fa-user-circle me-2"></i>
                                <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </div>
                        </div>

                        <!-- ชื่อระบบ -->
                        <div class="system-info mb-4">
                            <p class="text-muted fs-5 mb-0">
                                ระบบตรวจสุขภาพเด็ก
                            </p>
                            <p class="text-muted fs-5 mb-0">
                                ณ ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<style>
    .welcome-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px !important;
        position: relative;
        overflow: hidden;
    }

    .welcome-card .card-body {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 2;
    }

    .welcome-icon-circle {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 2s infinite;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
    }

    .user-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 25px;
        border-radius: 50px;
        font-size: 1.2rem;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        transform: translateY(0);
        transition: all 0.3s ease;
    }

    .user-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
    }

    .system-info .badge {
        font-size: 1rem !important;
        padding: 10px 20px !important;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    }


    /* Responsive adjustments */
    @media (max-width: 1023px) {
        .welcome-card .card-body {
            padding: 2rem !important;
        }

        .display-5 {
            font-size: 2rem !important;
        }

        .user-badge {
            font-size: 1rem;
            padding: 10px 20px;
        }

        .welcome-footer .col-md-6 {
            text-align: center !important;
        }

        .justify-content-md-end,
        .justify-content-md-start {
            justify-content: center !important;
        }
    }
</style>



</body>

</html>