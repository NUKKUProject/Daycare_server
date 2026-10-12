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
<body>

    <main class="main-content">
<div class="checklist-container">
        <h2 class="mb-4">ประวัติการบันทึกกราฟการเจริญเติบโตของเด็ก</h2>
    <!-- ฟอร์มค้นหา -->
    <div class="card search-card">
        <div class="card-body">
            <form id="searchForm" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="child_group" class="form-label">กลุ่มเรียน</label>
                    <select name="child_group" id="child_group" class="form-select" onchange="loadClassrooms()">
                        <option value="">-- เลือกกลุ่มเรียน --</option>
                        <?php
                        $groups = get_childgroup();
                        foreach ($groups as $group) {
                            if (!empty($group['child_group'])) {
                                $selected = (isset($_GET['child_group']) && $_GET['child_group'] == $group['child_group']) ? 'selected' : '';
                                echo "<option value='" . $group['child_group'] . "' $selected>" . $group['child_group'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="classroom" class="form-label">ห้องเรียน</label>
                    <select name="classroom" id="classroom" class="form-select">
                        <option value="">-- เลือกห้องเรียน --</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date" class="form-label">วันที่</label>
                    <input type="date" class="form-control" id="date" name="date"
                        value="<?php echo htmlspecialchars(isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));?>">
                </div>

                <div class="col-md-3">
                    <label for="search" class="form-label">ค้นหาชื่อ</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="ชื่อ-นามสกุล"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>

                <div class="col-12">
                    <div class="search-buttons">
                        <button type="submit" class="btn btn-search">
                            <i class="bi bi-search"></i>
                            ค้นหา
                        </button>
                        <button type="button" class="btn btn-reset" onclick="resetForm()">
                            <i class="bi bi-x-circle"></i>
                            รีเซ็ต
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportToExcel()">
                            <i class="bi bi-file-excel"></i> Export Excel
                        </button>
                        <?php if ($is_admin || $is_teacher): ?>
                        <button type="button" class="btn btn-warning" onclick="openDevGrid()">
                            <i class="bi bi-clipboard2-pulse"></i> กรอกคัดกรองพัฒนาการทั้งห้อง
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- ตารางแสดงผล -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive" id="resultTable">
                <!-- ข้อมูลจะถูกเพิ่มโดย JavaScript -->
            </div>
        </div>
    </div>

</main>
<script src="../../public/assets/js/growth_standard_charts.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/growth_standard_charts.js') ?: time() ?>"></script>
    <script>
    // เปิดหน้ากรอกคัดกรองพัฒนาการทั้งห้อง พร้อมกลุ่มเรียน/ห้องเรียนที่เลือกไว้
    function openDevGrid() {
        const q = new URLSearchParams({
            child_group: document.getElementById('child_group').value,
            classroom: document.getElementById('classroom').value
        });
        window.location.href = 'growth_dev_grid.php?' + q.toString();
    }

        // โหลดห้องเรียนเมื่อเลือกกลุ่มเรียน
    function loadClassrooms() {
        var childGroup = document.getElementById('child_group').value;

        if (!childGroup) {
            document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
            return;
        }

        fetch(`../include/function/get_classrooms.php?child_group=${childGroup}`)
            .then(response => response.json())
            .then(data => {
                var classroomSelect = document.getElementById('classroom');
                classroomSelect.innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';

                data.forEach(function(classroom) {
                    var option = document.createElement('option');
                    option.value = classroom.classroom_name;
                    option.textContent = classroom.classroom_name;
                    classroomSelect.appendChild(option);
                });

                <?php if (isset($_GET['classroom'])): ?>
                    classroomSelect.value = '<?php echo json_encode($_GET['classroom']); ?>';
                <?php endif; ?>
            })
            .catch(error => console.error('Error:', error));
    }

    // รีเซ็ตฟอร์ม
    function resetForm() {
        document.getElementById('searchForm').reset();
        document.getElementById('child_group').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
        document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
        loadResults(); // โหลดผลลัพธ์ใหม่
    }

    // โหลดผลลัพธ์
    function loadResults() {
        const formData = new FormData(document.getElementById('searchForm'));
        const searchValue = formData.get('search');
        
        if (!formData.get('child_group') && !formData.get('classroom') && !searchValue) {
            const table = document.getElementById('resultTable');
            table.innerHTML = '<div class="alert alert-info">กรุณาเลือกกลุ่มเรียน, ห้องเรียน หรือค้นหาจากชื่อนักเรียน แล้วกดปุ่มค้นหา</div>';
            return;
        }

        // เปลี่ยนจาก GET เป็น POST
        fetch('../include/function/get_growth_history.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const table = document.getElementById('resultTable');
            if (data.length === 0) {
                table.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูล</div>';
                return;
            }

            // จัดกลุ่มข้อมูลตามกลุ่มเรียนและห้องเรียน
            const groupedData = groupStudentsByClass(data);

            let html = '';
            Object.entries(groupedData).forEach(([key, group]) => {
                html += `
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">กลุ่มเรียน: ${group.child_group} | ห้องเรียน: ${group.classroom}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>รหัสนักเรียน</th>
                                            <th>ชื่อ-นามสกุล</th>
                                            <th>ชื่อเล่น</th>
                                            <th>สถานะ</th>
                                            <th>เวลาที่บันทึก</th>
                                            <th>ผลการประเมิน</th>
                                            <?php if ($is_admin || $is_teacher): ?>
                                            <th>จัดการ</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

                group.students.forEach(student => {
                    const hasRecord = student.id != null;
                    const age = hasRecord ? 
                        `${student.age_year}ปี ${student.age_month}เดือน ${student.age_day}วัน` : '-';
                    const recordTime = hasRecord ? 
                        new Date(student.created_at).toLocaleTimeString('th-TH', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) + ' น.' : '-';

                    html += `
                                                <tr>
                            <td>${student.studentid}</td>
                            <td>${student.prefix_th}${student.firstname_th} ${student.lastname_th}</td>
                            <td>${student.nickname}</td>
                            <td>
                                <span class="badge ${hasRecord ? 'bg-success' : 'bg-secondary'}">
                                    ${hasRecord ? 'บันทึกแล้ว' : 'ยังไม่มีการบันทึก'}
                                </span>
                            </td>
                            <td>${recordTime}</td>
                            <td>
                                ${hasRecord ? `
                                    <button type="button" class="btn btn-info btn-sm" onclick="showGrowthDetails('${student.studentid}', '${student.id}')">
                                        <i class="bi bi-eye"></i> ดูผลการประเมิน
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="showGrowthOverview('${student.studentid}')">
                                        <i class="bi bi-graph-up"></i> ดูกราฟภาพรวม
                                    </button>
                                ` : `
                                    <button type="button" class="btn btn-success btn-sm" onclick="showAddGrowthModal('${student.studentid}', '${student.prefix_th}${student.firstname_th} ${student.lastname_th}')">
                                        <i class="bi bi-plus-circle"></i> เพิ่มข้อมูล
                                    </button>
                                `}
                            </td>
                            <?php if ($is_admin || $is_teacher): ?>
                            <td>
                                ${hasRecord ? `
                                    <button type="button" class="btn btn-warning btn-sm" onclick="editRecord('${student.id}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteRecord('${student.id}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                ` : '-'}
                            </td>
                            <?php endif; ?>
                        </tr>
                    `;
                });

                html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });

            table.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            table.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        });
    }

    // เพิ่มฟังก์ชันจัดกลุ่มข้อมูล
    function groupStudentsByClass(students) {
        return students.reduce((groups, student) => {
            const key = `${student.child_group}-${student.classroom}`;
            if (!groups[key]) {
                groups[key] = {
                    child_group: student.child_group,
                    classroom: student.classroom,
                    students: []
                };
            }
            groups[key].students.push(student);
            return groups;
        }, {});
    }

    // เพิ่มฟังก์ชันเมื่อโหลดหน้าครั้งแรก
    document.addEventListener('DOMContentLoaded', () => {
        // ถ้ามี URL parameters ให้กรอกข้อมูลในฟอร์มและค้นหา
        const urlParams = new URLSearchParams(window.location.search);
        
        // ถ้ามีการเลือกกลุ่มเรียน ให้โหลดห้องเรียนก่อน
        if (urlParams.get('child_group')) {
            // กรอกข้อมูลกลุ่มเรียน
            document.getElementById('child_group').value = urlParams.get('child_group');
            
            // โหลดห้องเรียน
            loadClassrooms();
            
            // รอให้ห้องเรียนโหลดเสร็จก่อนเลือกห้องเรียนและโหลดผลลัพธ์
            setTimeout(() => {
                if (urlParams.get('classroom')) {
                    document.getElementById('classroom').value = urlParams.get('classroom');
                }
                loadResults();
            }, 500);
        } else {
            // แสดงข้อความแนะนำเมื่อโหลดหน้าครั้งแรก
            const table = document.getElementById('resultTable');
            table.innerHTML = '<div class="alert alert-info">กรุณาเลือกกลุ่มเรียน, ห้องเรียน หรือค้นหาจากชื่อนักเรียน แล้วกดปุ่มค้นหา</div>';
        }

        // กรอกข้อมูลอื่นๆ จาก URL parameters
        for (const [key, value] of urlParams) {
            if (key !== 'child_group' && key !== 'classroom') {
                const element = document.getElementById(key);
                if (element) {
                    element.value = value;
                }
            }
        }
    });

    // แก้ไขฟังก์ชัน showAllGrowthCharts
    function showAllGrowthCharts(studentId) {
        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const record = data.current_record;
                    const allRecords = data.all_records;
                    const studentName = record.student_name;
                    const sex = record.sex === 'ชาย' ? 'M' : 'F'; // แปลงค่าเพศ

                    // สร้าง HTML สำหรับแสดงกราฟทั้งหมด
                    const chartsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ ${record.age_range || '0-2'} ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sex === 'M' ? 'ชาย' : 'หญิง'}</h6>
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // แสดง Modal พร้อมกราฟทั้งหมด
                    Swal.fire({
                        title: `กราฟการเจริญเติบโต - ${studentName}`,
                        html: chartsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        didRender: () => {
                            const charts = [
                                { id: 'weightChart', type: 'weight' },
                                { id: 'heightChart', type: 'height' },
                                { id: 'weightHeightChart', type: 'weight_height' },
                                { id: 'headChart', type: 'head' }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                if (chartElement) {
                                    const newChart = new Chart(chartElement, {
                                        type: 'line',
                                        data: prepareChartData(chart.type, allRecords, sex),
                                        options: getChartOptions(chart.type)
                                    });

                                    chartElement.onclick = () => {
                                        expandChart(chart.type, allRecords, studentName, sex);
                                    };
                                    chartElement.style.cursor = 'pointer';
                                }
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }

   // ฟังก์ชันแสดง Modal สำหรับเพิ่มข้อมูล
function showAddGrowthModal(studentId, studentName) {
    // ตรวจสอบการมีอยู่ขององค์ประกอบก่อนดึงค่า
    const childGroupElement = document.getElementById('child_group');
    const selectedDateElement = document.querySelector('input[name="date"]');

    if (!childGroupElement || !selectedDateElement) {
        console.error('ไม่พบองค์ประกอบที่ต้องการใน DOM');
        return;
    }

    const childGroup = childGroupElement.value;
    const selectedDate = selectedDateElement.value;

    // ดึงข้อมูลเพศของนักเรียนจาก API
    fetch(`../include/function/get_student_info.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.student) {
                const sex = data.student.sex;
                const childGroup = data.student.child_group;
                
                const modalHtml = `
                    <form id="growthForm" onsubmit="saveGrowthData(event)">
                        <input type="hidden" name="student_id" value="${studentId}">
                        <input type="hidden" name="age_range" id="age_range_hidden">
                        <input type="hidden" name="record_date" value="${selectedDate}">
                        <input type="hidden" name="child_group" value="${childGroup}">
                        <input type="hidden" name="sex" value="${sex}">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="alert alert-info">
                                    วันที่บันทึก: ${new Date(selectedDate).toLocaleDateString('th-TH', {
                                                year: 'numeric',
                                                month: 'long',
                                day: 'numeric'
                            })}
                                </div>
                                </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ช่วงอายุ</label>
                                <select class="form-select" id="age_range" onchange="updateAgeInputLimits(); document.getElementById('age_range_hidden').value = this.value;" required>
                                    <option value="">เลือกช่วงอายุ</option>
                                    <option value="0-2">0-2 ปี</option>
                                    <option value="2-5">2-5 ปี</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">อายุ</label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="age_year" min="0" max="12" required>
                                            <span class="input-group-text">ปี</span>
                                    </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="age_month" min="0" max="11" required>
                                            <span class="input-group-text">เดือน</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="age_day" min="0" max="31" required>
                                            <span class="input-group-text">วัน</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">น้ำหนัก (กิโลกรัม)</label>
                                <input type="number" class="form-control" name="weight" step="0.1" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ส่วนสูง (เซนติเมตร)</label>
                                <input type="number" class="form-control" name="height" step="0.1" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">เส้นรอบวงศีรษะ (เซนติเมตร)</label>
                                <input type="number" class="form-control" name="head_circumference" step="0.1" min="0" required>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>การประเมินพัฒนาการ</h5>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">การเคลื่อนไหว (GM)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="gm_status" id="gm_pass" value="pass" required>
                                    <label class="form-check-label" for="gm_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="gm_status" id="gm_delay" value="delay">
                                    <label class="form-check-label" for="gm_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="gm_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">กล้ามเนื้อมัดเล็กและสติปัญญา (FM)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="fm_status" id="fm_pass" value="pass" required>
                                    <label class="form-check-label" for="fm_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="fm_status" id="fm_delay" value="delay">
                                    <label class="form-check-label" for="fm_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="fm_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">ความเข้าใจภาษา (RL)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="rl_status" id="rl_pass" value="pass" required>
                                    <label class="form-check-label" for="rl_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="rl_status" id="rl_delay" value="delay">
                                    <label class="form-check-label" for="rl_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="rl_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">การใช้ภาษา (EL)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="el_status" id="el_pass" value="pass" required>
                                    <label class="form-check-label" for="el_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="el_status" id="el_delay" value="delay">
                                    <label class="form-check-label" for="el_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="el_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">การช่วยเหลือตัวเองและสังคม (PS)</label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="ps_status" id="ps_pass" value="pass" required>
                                    <label class="form-check-label" for="ps_pass">ผ่าน</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="ps_status" id="ps_delay" value="delay">
                                    <label class="form-check-label" for="ps_delay">สงสัยล่าช้า</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="number" class="form-control form-control-sm" name="ps_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                </div>
                            </div>
                        </div>
                    </form>
                `;

                Swal.fire({
                    title: `เพิ่มข้อมูลการเจริญเติบโต - ${studentName}`,
                    html: modalHtml,
                    width: '800px',
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    preConfirm: () => {
                        const form = document.getElementById('growthForm');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return false;
                        }

                        // สร้าง FormData จากฟอร์ม
                        const formData = new FormData(form);
                        const data = {};

                        // เก็บข้อมูลพื้นฐาน
                        formData.forEach((value, key) => {
                            data[key] = value;
                        });

                        // จัดการข้อมูลการประเมิน 5 ด้าน
                        ['gm', 'fm', 'rl', 'el', 'ps'].forEach(field => {
                            // ดึงค่า radio button ที่ถูกเลือก
                            const selectedRadio = document.querySelector(`input[name="${field}_status"]:checked`);
                            if (selectedRadio) {
                                data[`${field}_status`] = selectedRadio.value;
                                
                                // ถ้าเป็น delay ให้ดึงค่า issue ด้วย
                                if (selectedRadio.value === 'delay') {
                                    const issueInput = document.querySelector(`input[name="${field}_issue"]`);
                                    if (issueInput && issueInput.value) {
                                        data[`${field}_issue`] = issueInput.value;
                                    }
                                }
                            }
                        });

                        console.log('Prepared data:', data); // Debug log
                        return data;
                    }
                }).then((result) => {
                    // แก้ไขส่วนของการส่งข้อมูล
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        
                        // เพิ่มข้อมูลทั้งหมดจาก result.value
                        Object.entries(result.value).forEach(([key, value]) => {
                            if (value !== null && value !== undefined && value !== '') {
                                formData.append(key, value);
                            }
                        });
                        
                        // เพิ่มการส่งค่า age_range
                        formData.append('age_range', document.getElementById('age_range').value);

                        // Debug: แสดงข้อมูลที่จะส่ง
                        console.log('Sending data:');
                        for (let pair of formData.entries()) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }

                        fetch('../include/process/save_growth_data.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success')
                                .then(() => {
                                    loadResults();
                                });
                            } else {
                                throw new Error(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error saving data:', error);
                            Swal.fire('ผิดพลาด', error.message, 'error');
                        });

                    }
                });
            } else {
                throw new Error(data.message || 'ไม่สามารถดึงข้อมูลนักเรียนได้');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลนักเรียนได้', 'error');
        });
}

    // เพิ่มฟังก์ชันใหม่สำหรับจำกัดช่วงอายุ
    function updateAgeInputLimits() {
        const ageRange = document.getElementById('age_range').value;
        const form = document.getElementById('growthForm');
        
        const yearInput = form.querySelector('[name="age_year"]');
        const monthInput = form.querySelector('[name="age_month"]');
        const dayInput = form.querySelector('[name="age_day"]');
        
        // รีเซ็ตค่าเริ่มต้น
        yearInput.value = '';
        monthInput.value = '';
        dayInput.value = '';
        
        switch(ageRange) {
            case '0-2':
                yearInput.max = 2;
                yearInput.min = 0;
                break;
            case '0-5':
                yearInput.max = 5;
                yearInput.min = 0;
                break;
            case '2-5':
                yearInput.max = 5;
                yearInput.min = 2;
                break;
            case '5':
                yearInput.max = 5;
                yearInput.min = 0;
                break;
            default:
                yearInput.max = 6;
                yearInput.min = 0;
        }
        
        // เพิ่ม Event Listener สำหรับตรวจสอบค่า
        yearInput.addEventListener('change', function() {
            const year = parseInt(this.value);
            if (year === yearInput.max) {
                monthInput.value = '0';
                dayInput.value = '0';
                monthInput.disabled = true;
                dayInput.disabled = true;
            } else if (year === yearInput.min && yearInput.min > 0) {
                monthInput.value = '0';
                dayInput.value = '0';
                monthInput.disabled = true;
                dayInput.disabled = true;
            } else {
                monthInput.disabled = false;
                dayInput.disabled = false;
            }
        });
    }

    // เพิ่มฟังก์ชันแก้ไขข้อมูล
    function editRecord(recordId) {
        // ดึงข้อมูลเดิมก่อนแก้ไข
        fetch(`../include/function/get_growth_record.php?record_id=${recordId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const record = data.record;
                    const modalHtml = `
                        <form id="growthForm" onsubmit="saveGrowthData(event)">
                            <input type="hidden" name="record_id" value="${recordId}">
                            <input type="hidden" name="student_id" value="${record.student_id}">
                            <input type="hidden" name="child_group" value="${record.child_group}">
                            <input type="hidden" name="sex" value="${record.sex}">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ช่วงอายุ</label>
                                    <select class="form-select" id="age_range" name="age_range" onchange="updateAgeInputLimits();" required>
                                        <option value="">เลือกช่วงอายุ</option>
                                        <option value="0-2" ${record.age_range === '0-2' ? 'selected' : ''}>0-2 ปี</option>
                                        <option value="2-5" ${record.age_range === '2-5' ? 'selected' : ''}>2-5 ปี</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">อายุ</label>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="age_year" min="0" max="12" value="${record.age_year}" required>
                                                <span class="input-group-text">ปี</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="age_month" min="0" max="11" value="${record.age_month}" required>
                                                <span class="input-group-text">เดือน</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="age_day" min="0" max="31" value="${record.age_day}" required>
                                                <span class="input-group-text">วัน</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">น้ำหนัก (กิโลกรัม)</label>
                                    <input type="number" class="form-control" name="weight" step="0.1" min="0" value="${record.weight}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ส่วนสูง (เซนติเมตร)</label>
                                    <input type="number" class="form-control" name="height" step="0.1" min="0" value="${record.height}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">เส้นรอบวงศีรษะ (เซนติเมตร)</label>
                                    <input type="number" class="form-control" name="head_circumference" step="0.1" min="0" value="${record.head_circumference}" required>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5>การประเมินพัฒนาการ</h5>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">การเคลื่อนไหว (GM)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="gm_status" id="gm_pass" value="pass" ${record.gm_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="gm_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="gm_status" id="gm_delay" value="delay" ${record.gm_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="gm_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="gm_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.gm_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">กล้ามเนื้อมัดเล็กและสติปัญญา (FM)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="fm_status" id="fm_pass" value="pass" ${record.fm_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="fm_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="fm_status" id="fm_delay" value="delay" ${record.fm_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="fm_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="fm_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.fm_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">ความเข้าใจภาษา (RL)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="rl_status" id="rl_pass" value="pass" ${record.rl_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="rl_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="rl_status" id="rl_delay" value="delay" ${record.rl_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="rl_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="rl_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.rl_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">การใช้ภาษา (EL)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="el_status" id="el_pass" value="pass" ${record.el_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="el_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="el_status" id="el_delay" value="delay" ${record.el_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="el_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="el_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.el_issue || ''}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">การช่วยเหลือตัวเองและสังคม (PS)</label>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="ps_status" id="ps_pass" value="pass" ${record.ps_status === 'pass' ? 'checked' : ''} >
                                        <label class="form-check-label" for="ps_pass">ผ่าน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" name="ps_status" id="ps_delay" value="delay" ${record.ps_status === 'delay' ? 'checked' : ''}>
                                        <label class="form-check-label" for="ps_delay">สงสัยล่าช้า</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="number" class="form-control form-control-sm" name="ps_issue" style="width: 80px;" min="1" placeholder="ข้อที่" value="${record.ps_issue || ''}">
                                    </div>
                                </div>
                            </div>
                        </form>
                    `;

                    Swal.fire({
                    title: `แก้ไขข้อมูลการเจริญเติบโต - ${record.student_name}`,
                    html: modalHtml,
                    width: '800px',
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    didRender: () => {
                    try {
                        console.log('Setting initial values for record:', record);

                        // ตั้งค่าช่วงอายุ
                        setTimeout(() => {
                            const ageRangeSelect = document.getElementById('age_range');
                            if (ageRangeSelect) {
                                ageRangeSelect.value = record.age_range;
                                updateAgeInputLimits();
                            }

                            // ตั้งค่าอายุ (ปี, เดือน, วัน)
                            document.querySelector('input[name="age_year"]').value = record.age_year;
                            document.querySelector('input[name="age_month"]').value = record.age_month;
                            document.querySelector('input[name="age_day"]').value = record.age_day;

                            // ตั้งค่าสถานะการประเมินพัฒนาการ
                            const developmentFields = [
                                { name: 'gm', status: record.gm_status, issue: record.gm_issue },
                                { name: 'fm', status: record.fm_status, issue: record.fm_issue },
                                { name: 'rl', status: record.rl_status, issue: record.rl_issue },
                                { name: 'el', status: record.el_status, issue: record.el_issue },
                                { name: 'ps', status: record.ps_status, issue: record.ps_issue }
                            ];

                            developmentFields.forEach(field => {
                                if (field.status) {
                                    const radioBtn = document.querySelector(`input[name="${field.name}_status"][value="${field.status}"]`);
                                    if (radioBtn) {
                                        radioBtn.checked = true;
                                    }

                                    if (field.issue) {
                                        const issueInput = document.querySelector(`input[name="${field.name}_issue"]`);
                                        if (issueInput) {
                                            issueInput.value = field.issue;
                                        }
                                    }
                                }
                            });

                        }, 100); // ใช้ setTimeout เพื่อให้รอ UI โหลดเสร็จก่อน
                    } catch (error) {
                        console.error('Error setting initial values:', error);
                        }
                    },
                    preConfirm: () => {
                        const form = document.getElementById('growthForm');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return false;
                        }
                        const formData = new FormData(form);
                        const data = Object.fromEntries(formData);

                        // เพิ่มการจัดการค่า radio buttons
                        ['gm', 'fm', 'rl', 'el', 'ps'].forEach(field => {
                            const status = formData.get(`${field}_status`);
                            if (status) {
                                data[`${field}_status`] = status;
                            }
                        });

                        return data;
                    }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            
                            // เก็บข้อมูลที่ส่งมาจากฟอร์ม
                            Object.entries(result.value).forEach(([key, value]) => {
                                if (value !== null && value !== undefined && value !== '') {
                                    formData.append(key, value);
                                }
                            });

                            // ตรวจสอบและเพิ่มค่า record_id ถ้ายังไม่มี
                            if (!formData.has('record_id') && record.id) {
                                formData.append('record_id', record.id);
                            }

                            // ตรวจสอบและเพิ่มค่า age_range ถ้ายังไม่มี
                            if (!formData.has('age_range')) {
                                formData.append('age_range', document.getElementById('age_range').value);
                            }

                            // ตรวจสอบและเพิ่มค่าสถานะการประเมิน 5 ด้าน
                            ['gm', 'fm', 'rl', 'el', 'ps'].forEach(field => {
                                if (!formData.has(`${field}_status`)) {
                                    const selectedRadio = document.querySelector(`input[name="${field}_status"]:checked`);
                                    if (selectedRadio) {
                                        formData.append(`${field}_status`, selectedRadio.value);
                                        
                                        if (selectedRadio.value === 'delay') {
                                            const issueInput = document.querySelector(`input[name="${field}_issue"]`);
                                            if (issueInput && issueInput.value && !formData.has(`${field}_issue`)) {
                                                formData.append(`${field}_issue`, issueInput.value);
                                            }
                                        }
                                    }
                                }
                            });

                            // Debug: แสดงข้อมูลที่จะส่ง
                            console.log('Sending data:');
                            for (let pair of formData.entries()) {
                                console.log(pair[0] + ': ' + pair[1]);
                            }

                            fetch('../include/process/save_growth_data.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success')
                                    .then(() => {
                                        loadResults();
                                    });
                                } else {
                                    throw new Error(data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error saving data:', error);
                                Swal.fire('ผิดพลาด', error.message, 'error');
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }

    // เพิ่มฟังก์ชันลบข้อมูล
    function deleteRecord(recordId) {
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: 'คุณต้องการลบข้อมูลนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('record_id', recordId);

                fetch('../include/process/delete_growth_record.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('สำเร็จ', 'ลบข้อมูลเรียบร้อยแล้ว', 'success')
                        .then(() => {
                            loadResults(); // โหลดข้อมูลใหม่
                        });
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire('ผิดพลาด', error.message, 'error');
                });
            }
        });
    }

    // เพิ่มฟังก์ชันแสดงกราฟภาพรวม
    function showGrowthOverview(studentId) {
        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const allRecords = data.all_records;
                    if (allRecords.length === 0) {
                        Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลการเจริญเติบโต', 'info');
                        return;
                    }

                    const detailsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย</h6>
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย</h6>
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ 0-2 ปี เพศชาย</h6>
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศชาย</h6>
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>วันที่บันทึก</th>
                                                <th>อายุ</th>
                                                <th>น้ำหนัก</th>
                                                <th>ส่วนสูง</th>
                                                <th>เส้นรอบศีรษะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${allRecords.map(record => `
                                                <tr>
                                                    <td>${new Date(record.created_at).toLocaleDateString('th-TH')}</td>
                                                    <td>${record.age_year}ปี ${record.age_month}เดือน ${record.age_day}วัน</td>
                                                    <td>${record.weight} กก.</td>
                                                    <td>${record.height} ซม.</td>
                                                    <td>${record.head_circumference} ซม.</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: `กราฟภาพรวมการเจริญเติบโต - ${allRecords[0].student_name}`,
                        html: detailsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        didRender: () => {
                            const charts = [
                                {
                                    id: 'weightChart',
                                    type: 'weight'
                                },
                                {
                                    id: 'heightChart',
                                    type: 'height'
                                },
                                {
                                    id: 'weightHeightChart',
                                    type: 'weight_height'
                                },
                                {
                                    id: 'headChart',
                                    type: 'head'
                                }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                const newChart = new Chart(chartElement, {
                                    type: 'line',
                                    data: prepareChartData(chart.type, allRecords, allRecords[0].sex),
                                    options: getChartOptions(chart.type)
                                });

                                // เพิ่ม event listener สำหรับการคลิกที่กราฟ
                                chartElement.onclick = () => {
                                    expandChart(chart.type, allRecords, allRecords[0].student_name);
                                };
                                
                                // เพิ่ม style cursor เป็น pointer เมื่อ hover
                                chartElement.style.cursor = 'pointer';
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }

    // เพิ่มฟังก์ชันสำหรับขยายกราฟ
    function expandChart(chartType, records, studentName, sex) {
        let title = '';
        const sexText = sex === 'M' ? 'ชาย' : 'หญิง';
        
        switch(chartType) {
            case 'weight':
                title = `กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sexText}`;
                break;
            case 'height':
                title = `กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sexText}`;
                break;
            case 'weight_height':
                title = `กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ 0-2 ปี เพศ${sexText}`;
                break;
            case 'head':
                title = `กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sexText}`;
                break;
        }

        const modalHtml = `
            <div class="card">
                <div class="card-body">
                    <canvas id="expandedChart" style="height: 70vh;"></canvas>
                </div>
            </div>
        `;

        Swal.fire({
            title: `${title} - ${studentName}`,
            html: modalHtml,
            width: '90%',
            height: '90%',
            showCloseButton: true,
            showConfirmButton: false,
            didRender: () => {
                new Chart(document.getElementById('expandedChart'), {
                    type: 'line',
                    data: prepareChartData(chartType, records, sex),
                    options: getChartOptions(chartType)
                });
            }
            });
    }

    function getDataPoints(type, records) {
        switch(type) {
            case 'weight':
            case 'height':
            case 'head':
                return records.map(r => ({
                    x: r.age_year * 12 + r.age_month,
                    y: r[type === 'weight' ? 'weight' : 
                       type === 'height' ? 'height' : 'head_circumference']
                }));
            case 'weight_height':
                return records.map(r => ({
                    x: r.height,
                    y: r.weight
                }));
        }
    }

    // ฟังก์ชันแสดงรายละเอียดการเจริญเติบโต
    function showGrowthDetails(studentId, recordId) {
        console.log('Showing growth details for student:', studentId, 'record:', recordId);

        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}&record_id=${recordId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Data received:', data);
                
                if (!data || !data.current_record) {
                    throw new Error('ไม่พบข้อมูล');
                }

                const record = data.current_record;
                const allRecords = data.all_records || [];
                
                // ตรวจสอบและแปลงค่าเพศให้ถูกต้อง
                const sex = record.sex === 'M' ? 'ชาย' : 'หญิง';
                console.log('Sex:', sex); // Debug log

                // ตรวจสอบว่ามีข้อมูลที่จำเป็นครบถ้วน
                if (!record.student_name || !record.growth_status) {
                    throw new Error('ข้อมูลไม่ครบถ้วน');
                }

                // สร้าง HTML สำหรับ modal
                const detailsHtml = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>ข้อมูลการวัด</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>อายุ</th>
                                    <td>${record.age_year || 0}ปี ${record.age_month || 0}เดือน ${record.age_day || 0}วัน</td>
                                </tr>
                                <tr>
                                    <th>น้ำหนัก</th>
                                    <td>${record.weight || 0} กก.</td>
                                </tr>
                                <tr>
                                    <th>ส่วนสูง</th>
                                    <td>${record.height || 0} ซม.</td>
                                </tr>
                                <tr>
                                    <th>เส้นรอบวงศีรษะ</th>
                                    <td>${record.head_circumference || 0} ซม.</td>
                                </tr>
                                <tr>
                                    <th>วันที่บันทึก</th>
                                    <td>${record.created_at ? new Date(record.created_at).toLocaleDateString('th-TH', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) : '-'}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>ผลการประเมิน</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>น้ำหนักตามเกณฑ์อายุ</th>
                                    <td>${record.growth_status.weight || '-'}</td>
                                </tr>
                                <tr>
                                    <th>ส่วนสูงตามเกณฑ์อายุ</th>
                                    <td>${record.growth_status.height_age || '-'}</td>
                                </tr>
                                <tr>
                                    <th>น้ำหนักตามเกณฑ์ส่วนสูง</th>
                                    <td>${record.growth_status.weight_height || '-'}</td>
                                </tr>
                                <tr>
                                    <th>เส้นรอบวงศีรษะ</th>
                                    <td>${record.growth_status.head || '-'}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-12 mt-4">
                        <h5>ผลการประเมินพัฒนาการ</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%">ด้านการเคลื่อนไหว (GM)</th>
                                <td>
                                    ${record.gm_status ? `
                                        <span class="badge ${record.gm_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.gm_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.gm_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.gm_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านกล้ามเนื้อมัดเล็กและสติปัญญา (FM)</th>
                                <td>
                                    ${record.fm_status ? `
                                        <span class="badge ${record.fm_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.fm_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.fm_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.fm_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านการเข้าใจภาษา (RL)</th>
                                <td>
                                    ${record.rl_status ? `
                                        <span class="badge ${record.rl_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.rl_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.rl_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.rl_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านการใช้ภาษา (EL)</th>
                                <td>
                                    ${record.el_status ? `
                                        <span class="badge ${record.el_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.el_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.el_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.el_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                            <tr>
                                <th>ด้านการช่วยเหลือตัวเองและสังคม (PS)</th>
                                <td>
                                    ${record.ps_status ? `
                                        <span class="badge ${record.ps_status === 'pass' ? 'bg-success' : 'bg-warning'}">
                                            ${record.ps_status === 'pass' ? 'ผ่าน' : 'สงสัยล่าช้า'}
                                        </span>
                                        ${record.ps_issue ? `<br>ข้อที่สงสัยล่าช้า: ${record.ps_issue}` : ''}
                                    ` : '-'}
                                </td>
                            </tr>
                        </table>
                    </div>
                    </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}</h6>
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศ${sex}</h6>
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ ${record.age_range || '0-2'} ปี เพศ${sex}</h6>
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                    <h6 class="card-title">กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศ${sex}</h6>
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                // แสดง Modal
                    Swal.fire({
                    title: `ข้อมูลการเจริญเติบโต - ${record.student_name}`,
                    html: detailsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                    allowOutsideClick: true,
                        didRender: () => {
                        try {
                            const charts = [
                                { id: 'weightChart', type: 'weight' },
                                { id: 'heightChart', type: 'height' },
                                { id: 'weightHeightChart', type: 'weight_height' },
                                { id: 'headChart', type: 'head' }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                if (chartElement) {
                                    console.log('Creating chart:', chart.id, 'with sex:', sex); // Debug log
                                const newChart = new Chart(chartElement, {
                                    type: 'line',
                                        data: prepareChartData(chart.type, allRecords, record.sex),
                                    options: getChartOptions(chart.type)
                                });

                                chartElement.onclick = () => {
                                        expandChart(chart.type, allRecords, record.student_name, record.sex);
                                };
                                chartElement.style.cursor = 'pointer';
                                }
                            });
                        } catch (error) {
                            console.error('Error creating charts:', error);
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
        Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message || 'ไม่สามารถดึงข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            });
    }


     // เพิ่มฟังก์ชันแสดงกราฟทั้งหมด
     function showAllGrowthCharts(studentId) {
        fetch(`../include/function/get_student_growth_records.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const allRecords = data.all_records;
                    const studentName = data.current_record.student_name;

                    // สร้าง HTML สำหรับแสดงกราฟทั้งหมด
                    const chartsHtml = `
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="heightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="weightHeightChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <canvas id="headChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // แสดง Modal พร้อมกราฟทั้งหมด
                    Swal.fire({
                        title: `กราฟการเจริญเติบโต - ${studentName}`,
                        html: chartsHtml,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        didRender: () => {
                            const charts = [
                                {
                                    id: 'weightChart',
                                    type: 'weight'
                                },
                                {
                                    id: 'heightChart',
                                    type: 'height'
                                },
                                {
                                    id: 'weightHeightChart',
                                    type: 'weight_height'
                                },
                                {
                                    id: 'headChart',
                                    type: 'head'
                                }
                            ];

                            charts.forEach(chart => {
                                const chartElement = document.getElementById(chart.id);
                                const newChart = new Chart(chartElement, {
                                    type: 'line',
                                    data: prepareChartData(chart.type, allRecords),
                                    options: getChartOptions(chart.type)
                                });

                                // เพิ่ม event listener สำหรับการคลิกที่กราฟ
                                chartElement.onclick = () => {
                                    expandChart(chart.type, allRecords, allRecords[0].student_name);
                                };
                                
                                // เพิ่ม style cursor เป็น pointer เมื่อ hover
                                chartElement.style.cursor = 'pointer';
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            });
    }


    // เพิ่มฟังก์ชันสำหรับขยายกราฟ
    function expandChart(chartType, records, studentName) {
        const modalHtml = `
            <div class="card">
                <div class="card-body">
                    <canvas id="expandedChart" style="height: 70vh;"></canvas>
                </div>
            </div>
        `;

        let title = '';
        switch(chartType) {
            case 'weight':
                title = 'กราฟแสดงน้ำหนักตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย';
                break;
            case 'height':
                title = 'กราฟแสดงความยาว/ส่วนสูงตามเกณฑ์อายุของเด็กอายุ 0-5 ปี เพศชาย';
                break;
            case 'weight_height':
                title = 'กราฟแสดงน้ำหนักตามเกณฑ์ความยาว/ส่วนสูงของเด็กอายุ 0-2 ปี เพศชาย';
                break;
            case 'head':
                title = 'กราฟแสดงเส้นรอบวงศีรษะเด็กแรกเกิด - 5 ปี เพศชาย';
                break;
        }

        Swal.fire({
            title: `${title} - ${studentName}`,
            html: modalHtml,
            width: '90%',
            height: '90%',
            showCloseButton: true,
            showConfirmButton: false,
            didRender: () => {
                new Chart(document.getElementById('expandedChart'), {
                    type: 'line',
                    data: prepareChartData(chartType, records),
                    options: {
                        ...getChartOptions(chartType),
                        maintainAspectRatio: false,
                        responsive: true,
                        interaction: {
                            mode: 'nearest',
                            intersect: false,
                            axis: 'x'
                        },
                        plugins: {
                            zoom: {
                                zoom: {
                                    wheel: {
                                        enabled: true,
                                    },
                                    pinch: {
                                        enabled: true
                                    },
                                    mode: 'xy'
                                },
                                pan: {
                                    enabled: true,
                                    mode: 'xy'
                                }
                            }
                        }
                    }
                });
            }
        });
    }
    
    // ฟังก์ชันสำหรับอัปเดตหัวข้อกราฟ
    function updateChartTitle(chartElementId, type, gender, childGroup) {
        const chartTitle = getChartTitle(type, gender, childGroup);
        const chartElement = document.getElementById(chartElementId);

        if (chartElement) {
            const titleElement = chartElement.querySelector('.chart-title');
            if (titleElement) {
                titleElement.textContent = chartTitle;
            } else {
                console.error('ไม่พบองค์ประกอบหัวข้อกราฟ');
            }
        } else {
            console.error('ไม่พบองค์ประกอบกราฟ');
        }
    }

    // ตัวอย่างการเรียกใช้ฟังก์ชัน
    updateChartTitle('headChart', 'head', 'หญิง', 'กลาง');

    function exportGrowthHistory() {
        // แสดง Modal
        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.show();
    }

    // เพิ่ม event listener สำหรับการเปลี่ยนประเภทการ export
    document.getElementById('exportType').addEventListener('change', function() {
        // ซ่อนทุกส่วนก่อน
        document.querySelectorAll('.export-option').forEach(el => el.style.display = 'none');
        
        // แสดงส่วนที่เลือก
        switch(this.value) {
            case 'daily':
                document.getElementById('dailyExport').style.display = 'block';
                break;
            case 'monthly':
                document.getElementById('monthlyExport').style.display = 'block';
                break;
            case 'range':
                document.getElementById('rangeExport').style.display = 'block';
                break;
        }
    });

    function confirmExport() {
        const exportType = document.getElementById('exportType').value;
        const childGroup = document.getElementById('child_group').value;
        const classroom = document.getElementById('classroom').value;
        
        let url = '../include/process/export_growth_history.php?';
        url += `type=${exportType}`;

        // เพิ่มพารามิเตอร์ตามประเภทการ export
        switch(exportType) {
            case 'daily':
                const date = document.getElementById('exportDate').value;
                url += `&date=${date}`;
                break;
            case 'monthly':
                const month = document.getElementById('exportMonth').value;
                url += `&month=${month}`;
                break;
            case 'range':
                const startDate = document.getElementById('exportStartDate').value;
                const endDate = document.getElementById('exportEndDate').value;
                if (!startDate || !endDate) {
                    alert('กรุณาระบุวันที่เริ่มต้นและสิ้นสุด');
                    return;
                }
                url += `&start_date=${startDate}&end_date=${endDate}`;
                break;
        }

        // เพิ่มพารามิเตอร์กลุ่มเรียนและห้องเรียน
        if (childGroup) url += `&child_group=${childGroup}`;
        if (classroom) url += `&classroom=${classroom}`;

        // ปิด Modal
        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();

        // ไปยังหน้า export
        window.location.href = url;
    }

    function exportToExcel() {
        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.show();
    }

    function toggleDateFields() {
        const exportType = document.getElementById('exportType').value;
        document.getElementById('dailyField').style.display = 'none';
        document.getElementById('monthlyField').style.display = 'none';
        document.getElementById('rangeFields').style.display = 'none';

        switch(exportType) {
            case 'daily':
                document.getElementById('dailyField').style.display = 'block';
                break;
            case 'monthly':
                document.getElementById('monthlyField').style.display = 'block';
                break;
            case 'range':
                document.getElementById('rangeFields').style.display = 'block';
                break;
        }
    }

    function loadExportClassrooms() {
        const childGroup = document.getElementById('exportChildGroup').value;
        const classroomSelect = document.getElementById('exportClassroom');
        
        classroomSelect.innerHTML = '<option value="">ทั้งหมด</option>';
        
        if (childGroup) {
            fetch(`../include/function/get_classrooms.php?child_group=${childGroup}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(classroom => {
                        const option = document.createElement('option');
                        option.value = classroom.classroom_name;
                        option.textContent = classroom.classroom_name;
                        classroomSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    }

    function confirmExport() {
        const exportType = document.getElementById('exportType').value;
        const childGroup = document.getElementById('exportChildGroup').value;
        const classroom = document.getElementById('exportClassroom').value;
        
        let url = '../include/process/export_growth_history.php?';
        url += `type=${exportType}`;

        switch(exportType) {
            case 'daily':
                const date = document.getElementById('exportDate').value;
                url += `&date=${date}`;
                break;
            case 'monthly':
                const month = document.getElementById('exportMonth').value;
                url += `&month=${month}`;
                break;
            case 'range':
                const startDate = document.getElementById('exportStartDate').value;
                const endDate = document.getElementById('exportEndDate').value;
                if (!startDate || !endDate) {
                    alert('กรุณาระบุวันที่เริ่มต้นและสิ้นสุด');
                    return;
                }
                url += `&start_date=${startDate}&end_date=${endDate}`;
                break;
        }

        if (childGroup) url += `&child_group=${childGroup}`;
        if (classroom) url += `&classroom=${classroom}`;

        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
        window.location.href = url;
    }
    </script>
    
    <!-- เพิ่มในส่วน head -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js" integrity="sha384-4mFQWIqZrcfYZkFPEgIoT//zTEU64gEoH2tGV72Koyhoa9Fxz43YgroFUGj4/RAx" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js" integrity="sha384-Cs3dgUx6+jDxxuqHvVH8Onpyj2LF1gKZurLDlhqzuJmUqVYMJ0THTWpxK5Z086Zm" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-zoom/1.2.1/chartjs-plugin-zoom.min.js" integrity="sha384-4ujnoFIaCz8xsHAu9NwdZDeFVVV0SHFY9KLc+MJIWK7KMSVimFAnmdhc9fXWhMXt" crossorigin="anonymous"></script>

    <!-- เพิ่ม HTML ส่วน Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export ข้อมูลการเจริญเติบโต</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
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
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <!-- เดือน -->
                        <div id="monthlyField" class="mb-3" style="display:none">
                            <label class="form-label">เดือน</label>
                            <input type="month" class="form-control" id="exportMonth" 
                                value="<?php echo date('Y-m'); ?>">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="confirmExport()">Export</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .export-option {
        padding: 10px 0;
    }
    </style>
</body>
</html> 
