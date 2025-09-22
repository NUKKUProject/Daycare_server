<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['admin']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>
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

<main class="main-content ">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">แดชบอร์ดข้อมูล</h1>
        </div>


        <!-- แท็บหลัก -->
        <ul class="nav nav-tabs dashboard-tabs mb-4" id="mainTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab">ภาพรวมทั้งหมด</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="staff-tab" data-bs-toggle="tab" href="#staff" role="tab">บุคลากร</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="students-tab" data-bs-toggle="tab" href="#students" role="tab">นักเรียน</a>
            </li>
        </ul>

        <!-- เนื้อหาแท็บ -->
        <div class="tab-content" id="mainTabContent">
            <!-- แท็บภาพรวม -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row">
                    <!-- การ์ดสรุปข้อมูล -->
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">จำนวนนักเรียนทั้งหมด</h5>
                                <h2 class="card-text"><?= $totalStudents ?> คน</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">จำนวนบุคลากร</h5>
                                <h2 class="card-text"><?= $totalStaff ?> คน</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">จำนวนห้องเรียน</h5>
                                <h2 class="card-text"><?= count($studentsByGroup) ?> ห้อง</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- กราฟแสดงภาพรวม -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title-graph">สัดส่วนนักเรียนแต่ละระดับชั้น</h5>
                            <canvas id="studentsPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- แท็บบุคลากร -->
            <div class="tab-pane fade" id="staff" role="tabpanel">
                <!-- แท็บย่อยของบุคลากร -->
                <ul class="nav nav-pills dashboard-pills mb-3" id="staffSubTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="staff-info-tab" data-bs-toggle="pill" href="#staff-info" role="tab">ข้อมูลทั่วไป</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="staff-performance-tab" data-bs-toggle="pill" href="#staff-performance" role="tab">ประสิทธิภาพการทำงาน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="staff-attendance-tab" data-bs-toggle="pill" href="#staff-attendance" role="tab">การลงเวลาทำงาน</a>
                    </li>
                </ul>

                <div class="tab-content" id="staffSubTabContent">
                    <!-- เนื้อหาแท็บย่อยบุคลากร -->
                    <div class="tab-pane fade show active" id="staff-info" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">สัดส่วนตำแหน่งบุคลากร</h5>
                                        <canvas id="staffPositionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">อายุการทำงานเฉลี่ย</h5>
                                        <canvas id="staffExperienceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- เพิ่มเนื้อหาแท็บย่อยอื่นๆ ของบุคลากร -->
                </div>
            </div>
            <!-- แท็บนักเรียน -->
            <div class="tab-pane fade" id="students" role="tabpanel">
                <!-- แท็บย่อยของนักเรียน -->
                <ul class="nav nav-pills dashboard-pills mb-3" id="studentsSubTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="students-info-tab" data-bs-toggle="pill" href="#students-info" role="tab">ข้อมูลทั่วไป</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="students-attendance-tab" data-bs-toggle="pill" href="#students-attendance" role="tab">การมาเรียน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="students-activities-tab" data-bs-toggle="pill" href="#students-activities" role="tab">กิจกรรม</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="students-nutrition-tab" data-bs-toggle="pill" href="#students-nutrition" role="tab">โภชนาการ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="students-health-tab" data-bs-toggle="pill" href="#students-health" role="tab">สุขภาพร่างกาย</a>
                    </li>
                </ul>

                <div class="tab-content" id="studentsSubTabContent">
                    <!-- เนื้อหาแท็บย่อยนักเรียนข้อมูลทั่วไป -->
                    <div class="tab-pane fade show active" id="students-info" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">จำนวนนักเรียนแยกตามอายุ</h5>
                                        <canvas id="studentsAgeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">สถิติการเข้าร่วมกิจกรรม</h5>
                                        <canvas id="studentsActivityChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- เนื้อหาแท็บย่อยนักเรียนการมาเรียน -->
                <div class="tab-pane fade" id="students-attendance" role="tabpanel">
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title-graph">อัตราการมาเรียน</h5>
                                        <div class="d-flex gap-2 align-items-center">
                                            <select class="form-select form-select-sm" id="viewType" style="width: auto;">
                                                <option value="month">รายเดือน</option>
                                                <option value="week">รายสัปดาห์</option>
                                            </select>
                                            <input type="month" class="form-control form-control-sm" id="attendanceMonth"
                                                value="<?= date('Y-m') ?>"
                                                max="<?= date('Y-m') ?>"
                                                style="width: auto;">
                                            <select class="form-select form-select-sm" id="weekSelect" style="width: auto; display: none;">
                                                <option value="">เลือกสัปดาห์</option>
                                            </select>
                                            <button class="btn btn-primary btn-sm" id="fetchDataBtn">
                                                <i class="bi bi-search"></i> ดูข้อมูล
                                            </button>
                                        </div>
                                    </div>
                                    <div id="attendanceStatsContainer">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-3">
                                                <div class="card bg-primary text-white">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2">จำนวนวันเรียนทั้งหมด</h6>
                                                        <h3 class="card-title mb-0" id="totalDays">0</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2">มาเรียน</h6>
                                                        <h3 class="card-title mb-0" id="presentCount">0</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-danger text-white">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2">ขาดเรียน</h6>
                                                        <h3 class="card-title mb-0" id="absentCount">0</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-warning text-white">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2">ลา</h6>
                                                        <h3 class="card-title mb-0" id="leaveCount">0</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-info text-white">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2">อัตราการมาเรียน</h6>
                                                        <h3 class="card-title mb-0" id="attendanceRate">0%</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <canvas id="attendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</main>


<!-- เพิ่ม Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตัวแปรสำหรับกราฟทั้งหมด
        let attendanceChart, studentsPieChart, staffPositionChart;

        // ตัวแปรสำหรับการดึงข้อมูลการมาเรียน
        const attendanceMonthPicker = document.getElementById('attendanceMonth');
        const viewTypeSelect = document.getElementById('viewType');
        const weekSelect = document.getElementById('weekSelect');
        const fetchDataBtn = document.getElementById('fetchDataBtn');

        // สร้างกราฟหลัก
        function initializeMainCharts() {
            // กราฟวงกลมแสดงสัดส่วนนักเรียน
            studentsPieChart = new Chart(document.getElementById('studentsPieChart'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_keys($studentsByGroup)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($studentsByGroup)) ?>,
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // กราฟตำแหน่งบุคลากร
            staffPositionChart = new Chart(document.getElementById('staffPositionChart'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($staffByPosition)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($staffByPosition)) ?>,
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)',
                            'rgb(75, 192, 192)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // ฟังก์ชันสร้างตัวเลือกสัปดาห์
        function generateWeekOptions(yearMonth) {
            const [year, month] = yearMonth.split('-');
            const date = new Date(year, month - 1, 1);
            const weeks = [];

            while (date.getMonth() === month - 1) {
                const weekStart = new Date(date);
                const weekEnd = new Date(date);
                weekEnd.setDate(weekEnd.getDate() + 6);

                weeks.push({
                    start: new Date(weekStart),
                    end: new Date(weekEnd),
                    value: `${weekStart.toISOString().split('T')[0]}`,
                    label: `สัปดาห์ที่ ${weeks.length + 1} (${weekStart.getDate()}-${
                        weekEnd.getMonth() === weekStart.getMonth() ? 
                        weekEnd.getDate() : 
                        weekEnd.getDate() + '/' + (weekEnd.getMonth() + 1)
                    })`
                });

                date.setDate(date.getDate() + 7);
            }

            weekSelect.innerHTML = '<option value="">เลือกสัปดาห์</option>' +
                weeks.map(week => `<option value="${week.value}">${week.label}</option>`).join('');
        }

        // ฟังก์ชันดึงข้อมูล
        async function fetchAttendanceData() {
            const params = {
                view: viewTypeSelect.value
            };

            if (viewTypeSelect.value === 'week' && !weekSelect.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาเลือกสัปดาห์',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            params[viewTypeSelect.value === 'week' ? 'week_start' : 'month'] =
                viewTypeSelect.value === 'week' ? weekSelect.value : attendanceMonthPicker.value;

            try {
                const queryString = new URLSearchParams(params).toString();
                const response = await fetch(`../../include/function/get_attendance_stats.php?${queryString}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const data = await response.json();
                updateAttendanceStats(data);
            } catch (error) {
                console.error('Error fetching attendance data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถดึงข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            }
        }

        // ฟังก์ชันอัพเดทการแสดงผลข้อมูล
        function updateAttendanceStats(data) {
            try {
                // อัพเดทตัวเลขสถิติ
                document.getElementById('totalDays').textContent = data.total_days || 0;
                document.getElementById('presentCount').textContent = data.present_count || 0;
                document.getElementById('absentCount').textContent = data.absent_count || 0;
                document.getElementById('attendanceRate').textContent = `${data.attendance_rate || 0}%`;

                const chartCanvas = document.getElementById('attendanceChart');
                const noDataDiv = document.getElementById('noDataMessage') || (() => {
                    const div = document.createElement('div');
                    div.id = 'noDataMessage';
                    div.className = 'alert alert-info text-center';
                    document.getElementById('attendanceStatsContainer').appendChild(div);
                    return div;
                })();

                // ถ้าไม่มีข้อมูล
                if (data.no_data) {
                    chartCanvas.style.display = 'none';
                    noDataDiv.style.display = 'block';
                    noDataDiv.textContent = data.message;
                    return;
                }

                // มีข้อมูล
                chartCanvas.style.display = 'block';
                noDataDiv.style.display = 'none';

                // อัพเดทกราฟ
                if (attendanceChart) {
                    attendanceChart.destroy();
                }

                attendanceChart = new Chart(chartCanvas, {
                    type: 'bar',
                    data: {
                        labels: data.daily_stats.map(item => {
                            const date = new Date(item.date);
                            return date.toLocaleDateString('th-TH', {
                                day: 'numeric',
                                month: 'short'
                            });
                        }),
                        datasets: [{
                            label: 'มาเรียน',
                            data: data.daily_stats.map(item => item.present_count),
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgb(75, 192, 192)',
                            borderWidth: 1
                        }, {
                            label: 'ขาดเรียน',
                            data: data.daily_stats.map(item => item.absent_count),
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        }, {
                            label: 'ลา',
                            data: data.daily_stats.map(item => item.leave_count),
                            backgroundColor: 'rgba(255, 205, 86, 0.5)',
                            borderColor: 'rgb(255, 205, 86)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'จำนวนนักเรียน (คน)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'วันที่'
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'สถิติการมาเรียนรายวัน'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error updating attendance stats:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถแสดงข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            }
        }

        // Event listeners
        viewTypeSelect.addEventListener('change', function() {
            const isWeekView = this.value === 'week';
            weekSelect.style.display = isWeekView ? 'block' : 'none';
            if (isWeekView) {
                generateWeekOptions(attendanceMonthPicker.value);
            }
        });

        attendanceMonthPicker.addEventListener('change', function() {
            if (viewTypeSelect.value === 'week') {
                generateWeekOptions(this.value);
            }
        });

        fetchDataBtn.addEventListener('click', fetchAttendanceData);

        // เริ่มต้นสร้างกราฟหลัก
        initializeMainCharts();
    });
</script>

</body>

</html>