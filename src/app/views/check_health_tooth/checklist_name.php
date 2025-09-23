<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
include __DIR__ . '../../partials/Header.php';
include __DIR__ . '..//../../include/auth/auth_navbar.php';
require_once '../../include/function/pages_referen.php';
require_once __DIR__ . '../../../include/function/child_functions.php';
$is_admin = getUserRole() === 'admin';
$is_teacher = getUserRole() === 'teacher';
$is_doctor = getUserRole() === 'doctor';
require_once __DIR__ . '../../../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../../include/function/children_history_functions.php';
require_once __DIR__ . '/../../include/function/get_doctors.php';

// ใช้ user_id จาก session เป็น teacher_id
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
} else {
    die('ไม่พบข้อมูลผู้สอน. กรุณาเข้าสู่ระบบอีกครั้ง.');
}


// ดึงข้อมูลปีการศึกษาทั้งหมด
$academicYears = getAcademicYears();
//ดึงข้อมูลแพทย์
$doctorListJson = getListDoctors();                // ได้ JSON string
$response = json_decode($doctorListJson, true);    // แปลงเป็น array
$doctors = $response['data'] ?? [];                // เอาเฉพาะ 'data'

?>
<style>
    .form-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
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

    .measurement-input {
        font-weight: 600;
        color: #667eea;
    }

    /* ต้องแยกออกมาแบบนี้ */
    .measurement-input::placeholder {
        color: #cccccc;
        opacity: 1;
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

    .swal2-popup-large {
        font-size: 14px !important;
    }

    .swal2-close-button-large {
        font-size: 24px !important;
    }



    /* Form groups */
    .form-group {
        padding: 10px;
        border-radius: 8px;
        background: rgba(254, 255, 255, 0.7);
        transition: background-color 0.3s ease;

    }

    .form-group:hover {
        background: rgba(248, 249, 250, 0.8);
    }

    /* Treatment items */
    .treatment-item {
        padding: 5px 10px;
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.7);
        margin-bottom: 8px !important;
    }

    .treatment-item:hover {
        background: rgba(0, 123, 255, 0.1);
    }

    /* Form check styling */
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }

    .form-check-input:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
    }

    /* Card hover effects */
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Section header */
    .section-header {
        font-size: 1.25rem;
        font-weight: bold;
        border-bottom: 2px solid #007bff;
        padding-bottom: 8px;
    }

    /* Input wrapper for better spacing */
    .input-wrapper {
        display: flex;
        align-items: center;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .form-group .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .form-group label {
            margin-bottom: 8px;
            min-width: auto !important;
        }
    }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <h2 class="mb-4">บันทึกการตรวจสุขภาพช่องปาก </h2>

        <!-- ฟอร์มค้นหา -->
        <div class="card search-card">
            <div class="card-body">
                <form id="searchForm" method="GET" class="row g-3">
                    <div class="col-md-2">
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

                    <div class="col-md-2">
                        <label for="classroom" class="form-label">ห้องเรียน</label>
                        <select name="classroom" id="classroom" class="form-select">
                            <option value="">-- เลือกห้องเรียน --</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date" class="form-label">ตรวจประจำปีการศึกษา</label>
                        <select name="academic_year" class="form-select">
                            <?php foreach ($academicYears as $index => $year): ?>
                                <option value="<?= $year['name'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="student_year" class="form-label">กลุ่มเด็ก</label>
                        <select name="student_year" id="student_year" class="form-select" onchange="filterData()">
                            <option value="all" <?= (isset($_GET['student_year']) && $_GET['student_year'] == 'all') ? 'selected' : '' ?>>
                                ทั้งหมด
                            </option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?= $year['name'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="search" class="form-label">ค้นหาชื่อ</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="ชื่อ-นามสกุล"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>

                    <!-- เพิ่มปุ่ม Export ไว้ข้างๆ ปุ่มค้นหาและรีเซ็ต -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">ค้นหา</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">รีเซ็ต</button>
                        <button type="button" class="btn btn-danger" onclick="exportToPdf()">
                            <i class="fas fa-file-pdf"></i> Export Pdf
                        </button>
                        <button type="button" class="btn btn-success mt-1" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
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
    </div>
</main>
<script>
    function formatDateThai(dateStr) {
        const monthsThai = [
            '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];

        const [year, month, day] = dateStr.split('-');
        const thaiYear = parseInt(year) + 543;
        const monthName = monthsThai[parseInt(month)];
        return `${parseInt(day)} ${monthName} ${thaiYear}`;
    }


    // เพิ่มฟังก์ชันเมื่อโหลดหน้า
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

    // แก้ไขฟังก์ชัน loadClassrooms()
    function loadClassrooms() {
        var childGroup = document.getElementById('child_group').value;

        if (!childGroup) {
            document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
            return;
        }


        fetch(`../../include/function/get_classrooms.php?child_group=${childGroup}`)
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

        // ถ้าไม่มีการเลือกกลุ่มเรียนและห้องเรียน และไม่มีการค้นหาชื่อ
        if (!formData.get('child_group') && !formData.get('classroom') && !searchValue) {
            const table = document.getElementById('resultTable');
            table.innerHTML = '<div class="alert alert-info">กรุณาเลือกกลุ่มเรียน, ห้องเรียน หรือค้นหาจากชื่อนักเรียน แล้วกดปุ่มค้นหา</div>';
            return;
        }

        const params = new URLSearchParams(formData);

        fetch(`./function/get_health_tooth.php?${params.toString()}`)
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

                // แสดงข้อมูลแต่ละกลุ่ม
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
                                                <th>ปีการศึกษา</th>
                                                <th>สถานะ</th>
                                                <th style="width:150px;">วันที่ตรวจ</th>
                                                <th>ผลการตรวจ</th>
                                                <th>จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    // แสดงข้อมูลนักเรียนในกลุ่ม
                    group.students.forEach(student => {
                        // ตรวจสอบสถานะจากการมี id
                        const hasRecord = student.id != null;

                        html += `
                            <tr>
                                <td>${student.student_id}</td>
                                <td>${student.prefix_th} ${student.first_name_th} ${student.last_name_th}</td>
                                <td>${student.nickname}</td>
                                <td>${student.academic_year}</td>
                               <td>
                                <span class="badge 
                                    ${student.doctor_name 
                                        ? 'bg-success' 
                                        : hasRecord 
                                            ? 'bg-warning text-dark' 
                                            : 'bg-secondary'}">
                                    ${student.doctor_name 
                                        ? 'หมอตรวจแล้ว' 
                                        : hasRecord 
                                            ? 'รอแพทย์ตรวจ' 
                                            : 'ยังไม่มีการบันทึก'}
                                </span>
                            </td>

                                
                                <td>
                                    ${hasRecord ? 
                                    new Date(student.updated_at).toLocaleDateString('th-TH', {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric'
                                    }) 
                                    : '-'
                                }

                                </td>

                                <td>
                                    <?php if ($is_admin || $is_teacher): ?>
                                        ${hasRecord ? `
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDetails('${student.id}')">
                                                <i class="bi bi-eye"></i> ดูรายละเอียด
                                            </button>
                                        ` : `
                                            <span class="badge bg-secondary">ยังไม่มีการบันทึก</span>
                                        `}
                                    <?php else: ?>
                                        ${hasRecord ? `
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDetails('${student.id}')">
                                                <i class="bi bi-eye"></i> ดูรายละเอียด
                                            </button>
                                        ` : `
                                            <span class="badge bg-warning text-dark">ยังไม่มีการบันทึก</span>
                                        `}
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($is_admin || $is_teacher || $is_doctor): ?>
                                        ${hasRecord ? `
                                            <button type="button" class="btn btn-warning btn-sm" onclick="editRecord('${student.id}')">
                                                <i class="bi bi-pencil"></i> แก้ไข
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteRecord('${student.id}')">
                                                <i class="bi bi-trash"></i> ลบ
                                            </button>
                                        ` : `
                                            <button type="button" class="btn btn-primary btn-sm" onclick="addNewRecord('${student.student_id}')">
                                                <i class="bi bi-plus-circle"></i> เพิ่มข้อมูล
                                            </button>
                                        `}
                                    <?php else: ?>
                                        ${hasRecord ? `
                                        ` : `
                                            <span class="text-muted">ยังไม่มีการบันทึก</span>
                                        `}
                                    <?php endif; ?>
                                </td>
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

    // เพิ่ม event listener สำหรับการ submit form
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault(); // ป้องกันการ refresh หน้า
        loadResults();
    });

    // ฟังก์ชันช่วยแสดงสถานะการประเมินพัฒนาการหน้า view
    function getDevelopmentStatus(status, score = '') {
        if (!status || status.length === 0) return 'ไม่ได้ประเมิน';

        if (status === 'pass') {
            return score ? `ผ่าน - ข้อที่ (${score})` : 'ผ่าน';
        } else if (status === 'delay') {
            return score ? `สงสัยล่าช้า - ข้อที่ (${score})` : 'สงสัยล่าช้า';
        } else {
            return score ? `ข้อที่ ${score}` : 'ไม่ผ่าน';
        }
    }


    // ฟังก์ชันดูรายละเอียด
    // ฟังก์ชันดูรายละเอียด (แบบอ่านอย่างเดียว)
    function viewDetails(id) {
        try {
            fetch(`./function/get_health_tooth_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {

                    if (!data || data.length === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่พบข้อมูลการตรวจสุขภาพสำหรับนักเรียนนี้',
                            confirmButtonText: 'ตกลง'
                        });
                        return;
                    }

                    const student = data.data;
                    // แปลงข้อมูลตำแหน่งฟันผุ
                    const decayedPositions = JSON.parse(student.decayed_teeth_positions || '{}');
                    const treatments = JSON.parse(student.treatments || '[]');

                    // แปลงชื่อการรักษา
                    const treatmentNames = {
                        'filling': 'อุดฟัน',
                        'fluoride': 'เคลือบฟลูออไรด์',
                        'root_canal': 'รักษาครองรากฟัน',
                        'fluoride_molar': 'เคลือบหลุมร่องฟันที่ฟันกราม',
                        'crown': 'ครอบฟัน',
                        'extraction': 'ถอนฟัน',
                        'other': 'อื่นๆ'
                    };

                    // แปลงชื่อตำแหน่งฟัน
                    const positionNames = {
                        'upper_right_molar': 'ฟันกรามบนขวา',
                        'upper_front_teeth': 'ฟันหน้าบน',
                        'upper_left_molar': 'ฟันกรามบนซ้าย',
                        'lower_right_molar': 'ฟันกรามล่างขวา',
                        'lower_front_teeth': 'ฟันหน้าล่าง',
                        'lower_left_molar': 'ฟันกรามล่างซ้าย'
                    };
                    const modalContent = `
                 <div class="dental-health-detail" style="text-align: left;">
                    
                    <!-- Header -->
                    <div class="alert alert-info mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                        <h4 class="mb-1"><i class="fas fa-tooth me-2"></i>การตรวจสุขภาพฟัน</h4>
                        <p class="mb-0">ปีการศึกษา ${student.academic_year} | แพทย์ผู้ตรวจ: ${student.doctor_name}</p>
                    </div>

                    <!-- ข้อมูলนักเรียน -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header" style="background-color: #e3f2fd;">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>ข้อมูลนักเรียน</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>รหัสนักเรียน:</strong> ${student.student_id}</p>
                                    <p><strong>ชื่อ-นามสกุล:</strong> ${student.prefix_th}${student.first_name} ${student.last_name}</p>
                                    <p><strong>ชื่อเล่น:</strong> ${student.nickname || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>ห้องเรียน:</strong> ${student.classroom}</p>
                                    <p><strong>อายุ:</strong> ${student.age_year} ปี ${student.age_month} เดือน ${student.age_day} วัน</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- สถานภาพฟันโดยรวม -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header" style="background-color: #f3e5f5;">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>สถานภาพฟันโดยรวม</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center d-flex justify-content-center g-3 mb-3">
                                <div class="col-md-3">
                                    <div class="stat-box p-3" style="background-color: #e8f5e8; border-radius: 8px;">
                                        <h4 class="text-success">${student.total_teeth}</h4>
                                        <p class="mb-0">จำนวนฟันทั้งหมด</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box p-3" style="background-color: ${student.decayed_teeth > 0 ? '#ffebee' : '#e8f5e8'}; border-radius: 8px;">
                                        <h4 class="${student.decayed_teeth > 0 ? 'text-danger' : 'text-success'}">${student.decayed_teeth}</h4>
                                        <p class="mb-0">จำนวนฟันผุทั้งหมด</p>
                                    </div>
                                </div>
                               
                            </div>
                             <!-- อวัยวะในช่องปาก -->
                    ${student.oral_components ? `
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header" style="background-color: #f1f8e9;">
                            <h6 class="mb-0">สว่นประกอบของช่องปาก (เหงือก/ลิ้น/เพดาน)</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">${student.oral_components}</p>
                        </div>
                    </div>
                    ` : ''}
                        </div>
                    </div>

                    <!-- ยังไม่มีฟันผุ -->
                   
                     ${student.missing_teeth_detail ? `
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header" style="background-color: #fff8e1;">
                            <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>ยังไม่มีฟันผุ</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><strong>ยังไม่มีฟันผุ:</strong> ${student.missing_teeth_detail}</p>
                        </div>
                    </div>
                    ` : ''}

                    <!-- แผนผังฟันผุ -->
                    ${Object.keys(decayedPositions).length > 0 ? `
                    <div class="card mb-3 shadow-sm">
    <div class="card-header" style="background-color: #fff3e0; border-radius: 12px 12px 0 0;">
        <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>ตำแหน่งฟันผุ</h5>
    </div>
    <div class="card-body">
        <div class="teeth-diagram" style="text-align: center; padding: 10px;">
            <!-- ฟันบน -->
            <div class="upper-teeth mb-4">
                <h6 class="text-muted mb-3"><i class="fas fa-arrow-up me-1"></i>ฟันด้านบน</h6>
                <div class="row g-2 justify-content-center">
                    <div class="col-lg-2 col-md-4 col-sm-4 col-4">
                        <div class="tooth-position" style="
                            padding: 10px 5px; 
                            border: 2px solid ${decayedPositions.upper_left_molar ? '#dc3545' : '#28a745'}; 
                            border-radius: 10px; 
                            background: ${decayedPositions.upper_left_molar ? '#ffebee' : '#f8fff8'};
                            min-height: 120px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            transition: all 0.3s ease;
                        ">
                            <i class="fas fa-tooth mb-2" style="
                                font-size: clamp(18px, 4vw, 24px); 
                                color: ${decayedPositions.upper_left_molar ? '#dc3545' : '#28a745'};
                            "></i>
                            <div style="font-size: clamp(12px, 2.5vw, 12px); font-weight: 600; text-align: center; line-height: 1.2;">
                                ฟันกราม<br>บนซ้าย
                            </div>
                            <div class="mt-1">
                                ${decayedPositions.upper_left_molar ? 
                                    `<span class="badge bg-danger" style="font-size: clamp(12px, 2vw, 10px);">ผุ ${decayedPositions.upper_left_molar} ซี่</span>` : 
                                    `<span class="badge bg-success" style="font-size: clamp(12px, 2vw, 10px);">ปกติ</span>`
                                }
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-4 col-4">
                        <div class="tooth-position" style="
                            padding: 10px 5px; 
                            border: 2px solid ${decayedPositions.upper_front_teeth ? '#dc3545' : '#28a745'}; 
                            border-radius: 10px; 
                            background: ${decayedPositions.upper_front_teeth ? '#ffebee' : '#f8fff8'};
                            min-height: 120px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            transition: all 0.3s ease;
                        ">
                            <i class="fas fa-tooth mb-2" style="
                                font-size: clamp(18px, 4vw, 24px); 
                                color: ${decayedPositions.upper_front_teeth ? '#dc3545' : '#28a745'};
                            "></i>
                            <div style="font-size: clamp(12px, 2.5vw, 12px); font-weight: 600; text-align: center; line-height: 1.2;">
                                ฟันหน้า<br>บน
                            </div>
                            <div class="mt-1">
                                ${decayedPositions.upper_front_teeth ? 
                                    `<span class="badge bg-danger" style="font-size: clamp(12px, 2vw, 10px);">ผุ ${decayedPositions.upper_front_teeth} ซี่</span>` : 
                                    `<span class="badge bg-success" style="font-size: clamp(12px, 2vw, 10px);">ปกติ</span>`
                                }
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-4 col-4">
                        <div class="tooth-position" style="
                            padding: 10px 5px; 
                            border: 2px solid ${decayedPositions.upper_right_molar ? '#dc3545' : '#28a745'}; 
                            border-radius: 10px; 
                            background: ${decayedPositions.upper_right_molar ? '#ffebee' : '#f8fff8'};
                            min-height: 120px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            transition: all 0.3s ease;
                        ">
                            <i class="fas fa-tooth mb-2" style="
                                font-size: clamp(18px, 4vw, 24px); 
                                color: ${decayedPositions.upper_right_molar ? '#dc3545' : '#28a745'};
                            "></i>
                            <div style="font-size: clamp(12px, 2.5vw, 12px); font-weight: 600; text-align: center; line-height: 1.2;">
                                ฟันกราม<br>บนขวา
                            </div>
                            <div class="mt-1">
                                ${decayedPositions.upper_right_molar ? 
                                    `<span class="badge bg-danger" style="font-size: clamp(12px, 2vw, 10px);">ผุ ${decayedPositions.upper_right_molar} ซี่</span>` : 
                                    `<span class="badge bg-success" style="font-size: clamp(12px, 2vw, 10px);">ปกติ</span>`
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- เส้นแบ่ง -->
            <div class="teeth-separator mb-4">
                <hr style="border: 2px solid #dee2e6; margin: 20px 0;">
            </div>
            
            <!-- ฟันล่าง -->
            <div class="lower-teeth">
                <h6 class="text-muted mb-3"><i class="fas fa-arrow-down me-1"></i>ฟันด้านล่าง</h6>
                <div class="row g-2 justify-content-center">
                    <div class="col-lg-2 col-md-4 col-sm-4 col-4">
                        <div class="tooth-position" style="
                            padding: 10px 5px; 
                            border: 2px solid ${decayedPositions.lower_left_molar ? '#dc3545' : '#28a745'}; 
                            border-radius: 10px; 
                            background: ${decayedPositions.lower_left_molar ? '#ffebee' : '#f8fff8'};
                            min-height: 120px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            transition: all 0.3s ease;
                        ">
                            <i class="fas fa-tooth mb-2" style="
                                font-size: clamp(18px, 4vw, 24px); 
                                color: ${decayedPositions.lower_left_molar ? '#dc3545' : '#28a745'};
                            "></i>
                            <div style="font-size: clamp(12px, 2.5vw, 12px); font-weight: 600; text-align: center; line-height: 1.2;">
                                ฟันกราม<br>ล่างซ้าย
                            </div>
                            <div class="mt-1">
                                ${decayedPositions.lower_left_molar ? 
                                    `<span class="badge bg-danger" style="font-size: clamp(12px, 2vw, 10px);">${decayedPositions.lower_left_molar} ซี่</span>` : 
                                    `<span class="badge bg-success" style="font-size: clamp(12px, 2vw, 10px);">ปกติ</span>`
                                }
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-4 col-4">
                        <div class="tooth-position" style="
                            padding: 10px 5px; 
                            border: 2px solid ${decayedPositions.lower_front_teeth ? '#dc3545' : '#28a745'}; 
                            border-radius: 10px; 
                            background: ${decayedPositions.lower_front_teeth ? '#ffebee' : '#f8fff8'};
                            min-height: 120px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            transition: all 0.3s ease;
                        ">
                            <i class="fas fa-tooth mb-2" style="
                                font-size: clamp(18px, 4vw, 24px); 
                                color: ${decayedPositions.lower_front_teeth ? '#dc3545' : '#28a745'};
                            "></i>
                            <div style="font-size: clamp(12px, 2.5vw, 12px); font-weight: 600; text-align: center; line-height: 1.2;">
                                ฟันหน้า<br>ล่าง
                            </div>
                            <div class="mt-1">
                                ${decayedPositions.lower_front_teeth ? 
                                    `<span class="badge bg-danger" style="font-size: clamp(12px, 2vw, 10px);">${decayedPositions.lower_front_teeth} ซี่</span>` : 
                                    `<span class="badge bg-success" style="font-size: clamp(12px, 2vw, 10px);">ปกติ</span>`
                                }
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-4 col-4">
                        <div class="tooth-position" style="
                            padding: 10px 5px; 
                            border: 2px solid ${decayedPositions.lower_right_molar ? '#dc3545' : '#28a745'}; 
                            border-radius: 10px; 
                            background: ${decayedPositions.lower_right_molar ? '#ffebee' : '#f8fff8'};
                            min-height: 120px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            transition: all 0.3s ease;
                        ">
                            <i class="fas fa-tooth mb-2" style="
                                font-size: clamp(18px, 4vw, 24px); 
                                color: ${decayedPositions.lower_right_molar ? '#dc3545' : '#28a745'};
                            "></i>
                            <div style="font-size: clamp(12px, 2.5vw, 12px); font-weight: 600; text-align: center; line-height: 1.2;">
                                ฟันกราม<br>ล่างขวา
                            </div>
                            <div class="mt-1">
                                ${decayedPositions.lower_right_molar ? 
                                    `<span class="badge bg-danger" style="font-size: clamp(12px, 2vw, 10px);">${decayedPositions.lower_right_molar} ซี่</span>` : 
                                    `<span class="badge bg-success" style="font-size: clamp(12px, 2vw, 10px);">ปกติ</span>`
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                    ` : ''}

                   

                    <!-- การรักษา -->
                    ${treatments.length > 0 ? `
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header" style="background-color: #e8f5e8;">
                            <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>แผนการรักษา</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                ${treatments.map(treatment => `
                                    <div class="col-md-6 mb-2">
                                        <div class="treatment-item p-2" style="background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #28a745;">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            ${treatmentNames[treatment] || treatment}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            ${student.other_treatment_detail ? `
                                <div class="mt-3">
                                    <strong>รายละเอียดการรักษาอื่นๆ:</strong>
                                    <p class="mb-0">${student.other_treatment_detail}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    ` : ''}

                    

                    <!-- Footer Info -->
                    <div class="card shadow-sm">
                        <div class="card-body text-center" style="background-color: #f8f9fa;">
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                บันทึกข้อมูลเมื่อ: ${new Date(student.created_at).toLocaleString('th-TH')}
                                <br>
                                <i class="fas fa-edit me-1"></i>
                                อัปเดตล่าสุด: ${new Date(student.updated_at).toLocaleString('th-TH')}
                            </small>
                        </div>
                    </div>
                </div>`;

                    // แสดง Modal
                    Swal.fire({
                        title: 'ข้อมูลการตรวจสุขภาพฟัน',
                        html: modalContent,
                        showConfirmButton: false,
                        showCloseButton: true,
                        width: '80%'

                    });
                })
                .catch(error => {
                    console.error('Error fetching health data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถดึงข้อมูลการตรวจสุขภาพฟันได้',
                    });
                });
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถดึงข้อมูลการตรวจสุขภาพฟันได้',
            });
        }
    }

    // ฟังก์ชันช่วยแสดงสถานะการตรวจร่างกาย
    function getPhysicalExamStatus(examData) {
        if (!examData || examData.length === 0) return 'ไม่ได้ตรวจ';

        if (examData.includes('normal')) {
            return 'ปกติ';
        } else if (examData.includes('abnormal')) {
            return 'ผิดปกติ';
        }
        return 'ไม่ระบุ';
    }


    // เพิ่มฟังก์ชันลบข้อมูล
    function deleteRecord(id) {
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: "คุณต้องการลบข้อมูลนี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('./process/delete_health_tooth.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    })
                    .then(response => {
                        return response.json();
                    })
                    .then(result => {
                        if (result.status === 'success') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end', // มุมขวาบน
                                icon: 'success', // success | error | warning | info | question
                                title: 'บันทึกสำเร็จ!',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            })
                            loadResults(); // โหลดข้อมูลใหม่

                        } else {
                            throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error); // เพิ่มบรรทัดนี้
                        Swal.fire({
                            toast: true,
                            position: 'top-end', // มุมขวาบน
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: error.message,
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });
                    });
            }
        });
    }
    // แก้ไขฟังก์ชัน addNewRecord
    function addNewRecord(studentId) {
        const year = document.querySelector('select[name="academic_year"]').value;
        fetch(`./function/get_student_data.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                // เข้าถึงข้อมูลนักเรียนจาก object student
                const student = data.student;

                const modalContent = `
                    <form id="healthCheckForm">
                        <input type="hidden" name="student_id" value="${student.studentid}">
                        <input type="hidden" name="prefix_th" value="${student.prefix_th}">
                        <input type="hidden" name="first_name_th" value="${student.firstname_th}">
                        <input type="hidden" name="last_name_th" value="${student.lastname_th}">
                        
                        <input type="hidden" name="class_room" value="${student.classroom}">
                        
                        <!-- ข้อมูลนักเรียน -->
                        <div class="student-info mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>วันที่:</strong></p> 
                                    <input type="date" name="" class="form-control dotted-input measurement-input text-center" value="<?php echo date('Y-m-d'); ?>" required>
              
                                </div>
                               
                                <div class="col-md-6">
                                    <p><strong>ประจำปีการศึกษา:</strong></p>
                                     <input type="text" class="form-control dotted-input measurement-input text-center" name="academic_year" value="${year}" placeholder="2568">
                                </div>                           
                            </div>
                        </div>

                     
                    <div class="card form-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i>ข้อมูลพื้นฐาน
                            </div>
            
                            <div class="row mt-2">
                                <div class="col-12">                    
                                        <div class="me-5 flex-grow-1 d-flex  justify-content-between align-items-center">
                                            <label class="form-label fw-bold" style="white-space: nowrap;">ทันตแพทย์ผู้ตรวจ ทพ./ทพ.หญิง</label>
                                            <?php if ($_SESSION['role'] == 'doctor') { ?>
                                            <input type="text" name="doctor_name" class="form-control dotted-input measurement-input text-center" placeholder="ชื่อแพทย์/พยาบาล" value="<?php echo htmlspecialchars(getFullName(), ENT_QUOTES, 'UTF-8'); ?>" required>
                                                                                    <?php } else { ?>
                                            <input type="text" name="doctor_name" class="form-control dotted-input measurement-input text-center" value="${student.doctor_name || ''}" readonly>
                                           <?php } ?>
                                           <label class="form-label mb-0" style="white-space: nowrap;">ได้ทำการตรวจสุขภาพช่องปาก</label>
                                        </div>                                                                       
                                </div>
                            </div>                          
                            <div class="row g-3 mt-3 align-items-end ">
                                <!-- ชื่อ-นามสกุล -->
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">ชื่อ-นามสกุล</label>
                                    <input type="text" class="form-control dotted-input measurement-input text-center"
                                        value="${student.prefix_th}${student.firstname_th} ${student.lastname_th}">
                                </div>

                                <!-- ชื่อเล่น -->
                                <div class="col-md-2 col-sm-5">
                                    <label class="form-label fw-bold">ชื่อเล่น</label>
                                    <input type="text" class="form-control dotted-input measurement-input text-center"
                                        name="nickname" value="${student.nickname || '-'}">
                                </div>

                                <!-- ห้องเรียน -->
                                <div class="col-md-2 col-sm-4">
                                    <label class="form-label fw-bold">ห้องเรียน</label>
                                    <input type="text" class="form-control dotted-input measurement-input form-control dotted-input measurement-input text-center"
                                        value="${student.classroom}">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">อายุ (ปี)</label>
                                    <input type="number" name="age_year" class="form-control dotted-input measurement-input text-center" min="0" placeholder="1">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">เดือน</label>
                                    <input type="number" name="age_month" class="form-control dotted-input measurement-input text-center" min="0" placeholder="10">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">วัน</label>
                                    <input type="number" name="age_day" class="form-control dotted-input measurement-input text-center" min="0" placeholder="13">
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                
                    <div class="card vital-signs-card mb-4">
                        <div class="card-body">
                            <div class="section-header mb-4">
                                <i class="bi bi-heart-pulse me-2"></i>การตรวจสุขภาพฟัน
                            </div>

                            <div class="container-fluid">
                                <!-- จำนวนฟัน -->
                                <div class="row mb-4 ">
                                    <div class="col-md-12 card border-2" style="border-color: #eae3ffff;">
                                        <div class="form-group d-flex flex-grow-4 align-items-center justify-content-around">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <label class="form-label fw-semibold mb-0 me-3 " style="min-width: 140px;">จำนวนฟันทั้งหมดกี่ซี่:</label>
                                                <div class="input-wrapper">
                                                    <input type="number" name="total_teeth" class="form-control dotted-input measurement-input text-center" 
                                                        style="max-width: 80px;" min="0" max="32" placeholder="1-32">
                                                    <small class="text-muted ms-2">ซี่</small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <label class="form-label fw-semibold mb-0 me-3" style="min-width: 140px;">จำนวนฟันผุทั้งหมดกี่ซี่:</label>
                                                <div class="input-wrapper">
                                                    <input type="number" name="decayed_teeth" class="form-control dotted-input measurement-input text-center" 
                                                        style="max-width: 80px;" min="0" placeholder="0-32">
                                                    <small class="text-muted ms-2">ซี่</small>
                                                </div>
                                            </div>
                                        
                                        </div>  
                                        <div class="form-group">
                                            <label class="form-label fw-semibold mb-2">ส่วนประกอบของช่องปาก (เหงือก/ลิ้น/เพดาน):</label> <small class="text-muted d-block mt-1 ms-1" id="oral_components_detail_count">
                                                        0 / 100 ตัวอักษร
                                                        </small>
                                            <input type="text" name="oral_components" class="form-control dotted-input measurement-input text-center" 
                                                placeholder="กรอกรายละเอียด..." maxlength="100"
                                                id="oral_components_detail_input">
                                        </div>
                                    </div>              
                                </div>

          
                                <div class="row mb-2">
                                    <div class="col-md-12 card border-2 " style="border-color: #eae3ffff;">
                                        <div class="form-group">                     
                                            <div class="form-check mb-3 d-flex align-items-center">
                                                    <input class="form-check-input " type="radio" name="teeth_status" id="normal_teeth" value="normal">
                                                    <label class="form-check-label fw-bold text-warning" for="normal_teeth">
                                                        <i class="bi bi-exclamation-triangle ms-2 me-1"></i>ยังไม่มีฟันผุ
                                                    </label> <small class="text-muted d-block mt-1 ms-1" id="missing_teeth_detail_count">
                                                        0 / 100 ตัวอักษร
                                                        </small>
                                                    
                                            </div> <input type="text" name="missing_teeth_detail"
                                                class="form-control dotted-input measurement-input text-center"
                                                placeholder="ระบุรายละเอียด"
                                                maxlength="100"
                                                id="missing_teeth_detail_input">
   
                                        </div>                   
                                    </div>              
                                </div> 

                                <div class="row mb-4 ">
                                    <div class="col-md-12 card border-2" style="border-color: #eae3ffff;">
                                        <div class="form-group">                                           
                                            <div class="form-check mb-3 d-flex align-items-center">
                                                        <input class="form-check-input" type="radio" name="teeth_status" id="abnormal_teeth" value="abnormal">
                                                        <label class="form-check-label fw-bold text-success" for="abnormal_teeth">
                                                            <i class="bi bi-check-circle ms-2 me-1"></i>มีฟันผุที่บริเวณ
                                                        </label>
                                            </div>
                                        </div>  
                                        <div class="row mb-4"> 
                                                <div class="col-md-6">
                                                    <div class="card h-100 border-2" style="border-color: #e3f2fd;">
                                                        <div class="card-body">         
                                                            <div class="ms-4">
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันหน้าบน:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="upper_front_teeth" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามบนขวา:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="upper_right_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามล่างขวา:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="lower_right_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="card h-100 border-2" style="border-color: #fff3e0;">
                                                        <div class="card-body">                                            
                                                            <div class="ms-4">
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันหน้าล่าง:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="lower_front_teeth" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามบนซ้าย:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="upper_left_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามล่างซ้าย:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="lower_left_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> 
                                    </div>             
                                </div> 

                                <!-- การรักษาที่จำเป็น -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title fw-bold mb-3">
                                                    <i class="bi bi-tools me-2"></i>จำเป็นต้องได้รับการรักษา ดังต่อไปนี้
                                                </h6>
                                                
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="filling" id="filling">
                                                                <label class="form-check-label" for="filling">อุดฟัน</label>
                                                            </div>
                                                        </div>
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="fluoride" id="fluoride">
                                                                <label class="form-check-label" for="fluoride">เคลือบฟลูออไรด์</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-4">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="root_canal" id="root_canal">
                                                                <label class="form-check-label" for="root_canal">รักษาคลองรากฟัน</label>
                                                            </div>
                                                        </div>
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="fluoride_molar" id="fluoride_molar">
                                                                <label class="form-check-label" for="fluoride_molar">เคลือบหลุมร่องฟันที่ฟันกราม</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-4">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="crown" id="crown">
                                                                <label class="form-check-label" for="crown">ครอบฟัน</label>
                                                            </div>
                                                        </div>
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="extraction" id="extraction">
                                                                <label class="form-check-label" for="extraction">ถอนฟัน</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check d-flex align-items-center">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="other" id="other_treatment_check">
                                                                <label class="form-check-label ms-2" for="other_treatment_check">อื่นๆ ได้แก่:</label>
                                                            </div>
                                                            <input type="text" name="other_treatment_detail" 
                                                                class="form-control dotted-input measurement-input mt-1" 
                                                                style="max-width: 100%;" placeholder="ระบุการรักษาอื่นๆ">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ความเร่งด่วน -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-warning">
                                            <div class="card-body">
                                                <h6 class="card-title fw-bold mb-3">
                                                    <i class="bi bi-clock me-2"></i>ความเร่งด่วนในการรักษา
                                                </h6>
                                        
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-check d-flex align-items-center">
                                                            <input class="form-check-input" type="radio" name="urgency" id="urgent" value="urgent">
                                                            <label class="form-check-label text-danger fw-semibold" for="urgent">
                                                                <i class="bi bi-exclamation-circle me-1 ms-2"></i>ควรได้รับการรักษาโดยด่วน
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check d-flex align-items-center">
                                                            <input class="form-check-input" type="radio" name="urgency" id="not_urgent" value="not_urgent">
                                                            <label class="form-check-label text-success fw-semibold" for="not_urgent">
                                                                <i class="bi bi-check-circle me-1 ms-2"></i>ไม่เร่งด่วน
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <div class="form-check d-flex align-items-center">
                                                            <input class="form-check-input" type="radio" name="urgency" id="preventable" value="preventable">
                                                            <label class="form-check-label text-info fw-semibold" for="preventable">
                                                                <i class="bi bi-shield-check me-1 ms-2"></i>สามารถผัดผ่อนได้ในระยะเวลาไม่นานนัก
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                           
                </form>
                
                `;

                // แสดง Modal
                Swal.fire({
                    title: 'แบบบันทึกตรวจสุขภาพฟัน',
                    html: modalContent,
                    width: '85%',
                    showCancelButton: true,
                    showCloseButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    didOpen: () => {
                        const input = document.getElementById('missing_teeth_detail_input');
                        const countSpan = document.getElementById('missing_teeth_detail_count');

                        const inputOral_components = document.getElementById('oral_components_detail_input');
                        const countSpanOral_components = document.getElementById('oral_components_detail_count');

                        // อัปเดตจำนวนอักษรที่ป้อนทันทีที่มีการ input
                        input.addEventListener('input', () => {
                            countSpan.textContent = `${input.value.length} / 100 ตัวอักษร`;
                        });
                        inputOral_components.addEventListener('input', () => {
                            countSpanOral_components.textContent = `${inputOral_components.value.length} / 100 ตัวอักษร`;
                        });
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveHealth();
                    }
                });
            });
    }



    // ฟังก์ชันสำหรับจัดการการส่งฟอร์ม
    function saveHealth() {
        const form = document.getElementById('healthCheckForm');
        const formData = collectFormData(form);
        // ส่งข้อมูลไปยัง API
        fetch('./process/save_health_tooth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end', // มุมขวาบน
                        icon: 'success', // success | error | warning | info | question
                        title: 'บันทึกสำเร็จ!',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    })
                    loadResults(); // โหลดข้อมูลใหม่

                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }
            })
            .catch(error => {
                Swal.fire({
                    toast: true,
                    position: 'top-end', // มุมขวาบน
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                });
            });
    }

    // ฟังก์ชันรวบรวมข้อมูลจากฟอร์ม
    function collectFormData(form) {
        const formData = {};

        formData.student_id = form.querySelector('input[name="student_id"]').value;
        formData.prefix_th = form.querySelector('input[name="prefix_th"]').value;
        formData.first_name_th = form.querySelector('input[name="first_name_th"]').value;
        formData.last_name_th = form.querySelector('input[name="last_name_th"]').value;
        formData.nickname = form.querySelector('input[name="nickname"]').value;

        formData.class_room = form.querySelector('input[name="class_room"]').value;
        formData.doctor_name = form.querySelector('input[name="doctor_name"]').value;

        formData.age_year = form.querySelector('input[name="age_year"]').value;
        formData.age_month = form.querySelector('input[name="age_month"]').value;
        formData.age_day = form.querySelector('input[name="age_day"]').value;
        formData.academic_year = form.querySelector('input[name="academic_year"]').value;


        // รวบรวมข้อมูลจาก input ต่างๆ
        formData.total_teeth = form.querySelector('input[name="total_teeth"]').value;
        formData.decayed_teeth = form.querySelector('input[name="decayed_teeth"]').value;
        formData.oral_components = form.querySelector('input[name="oral_components"]').value;
        formData.teeth_status = form.querySelector('input[name="teeth_status"]:checked')?.value || null;
        formData.missing_teeth_detail = form.querySelector('input[name="missing_teeth_detail"]').value;

        // รวบรวมข้อมูลตำแหน่งฟันผุ
        formData.decayed_teeth_positions = {
            upper_front_teeth: form.querySelector('input[name="upper_front_teeth"]').value,
            upper_right_molar: form.querySelector('input[name="upper_right_molar"]').value,
            lower_right_molar: form.querySelector('input[name="lower_right_molar"]').value,
            lower_front_teeth: form.querySelector('input[name="lower_front_teeth"]').value,
            upper_left_molar: form.querySelector('input[name="upper_left_molar"]').value,
            lower_left_molar: form.querySelector('input[name="lower_left_molar"]').value
        };

        // รวบรวมข้อมูลการรักษาที่เลือก
        formData.treatments = [];
        form.querySelectorAll('input[name="treatments[]"]:checked').forEach(checkbox => {
            formData.treatments.push(checkbox.value);
        });
        formData.other_treatment_detail = form.querySelector('input[name="other_treatment_detail"]').value;

        // รวบรวมข้อมูลความเร่งด่วน
        formData.urgency = form.querySelector('input[name="urgency"]:checked')?.value || null;

        return formData;
    }


    function exportToPdf() {
        Swal.fire({
            title: 'Export ข้อมูลการตรวจสุขถาพ',
            html: `
            <form id="exportForm" class="text-start">           
               
                <!-- ประจำปีการศึกษา -->
                <div class="mb-3">
                    <label class="form-label">ประจำปีการศึกษา</label>
                      <select name="academic_year" class="form-select">
                            <?php foreach ($academicYears as $index => $year): ?>
                                <option value="<?= $year['name'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                </div>

                <!-- เลือกแพทย์ -->
                <div class="mb-3">
                    <label class="form-label">เลือกแพทย์</label>
                    
                    <select name="doctor" class="form-control selectpicker border" title="-- เลือก --" id="swal-doctor-select" required>
                        <option value="all">ทั้งหมด</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= htmlspecialchars($doctor['username']) ?>">
                            <?= htmlspecialchars($doctor['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        `,
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'ยกเลิก',
            didOpen: () => {
                $('#swal-doctor-select').selectpicker({
                    liveSearch: true,
                    dropupAuto: false,
                    style: 'btn-default',
                    container: 'body' // เพิ่มบรรทัดนี้
                });
            },
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#exportForm');
                const formData = new FormData(form);
                savedFormData = formData;

                // ส่งค่าไป PHP เพื่อนับข้อมูลก่อน
                return fetch('./function/get_count_health_tooth_export.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.count === 0) {
                            Swal.showValidationMessage('ไม่พบข้อมูลสำหรับ export');
                        }
                        return data.count;
                    })
                    .catch(error => {
                        Swal.showValidationMessage("พบข้อผิดพลาด");
                    });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const count = result.value;

                Swal.fire({
                    title: 'ยืนยันการพิมพ์ PDF',
                    text: `พบข้อมูลจำนวน ${count} รายการ ต้องการพิมพ์หรือไม่?`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'พิมพ์ PDF',
                    cancelButtonText: 'ยกเลิก'
                }).then(async (confirmResult) => {
                    if (confirmResult.isConfirmed) {
                        // 🔁 แสดงหมุนทันที
                        Swal.fire({
                            title: 'กำลังโหลด...',
                            html: 'กำลังสร้างไฟล์ PDF กรุณารอสักครู่',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading(); // ✅ spinner ขึ้นแน่
                                fetchAndDownload(); // ✅ เรียกฟังก์ชันโหลด PDF
                            }
                        });
                    }
                });
            }
        })
    }
    async function fetchAndDownload() {
        const queryParams = new URLSearchParams();
        savedFormData.forEach((value, key) => {
            queryParams.append(key, value);
        });

        const exportUrl = `./process/export_health_tooth_external.php?${queryParams.toString()}`;

        try {
            const res = await fetch(exportUrl);
            if (!res.ok) throw new Error("ไม่สามารถโหลด PDF ได้");

            let filename = 'download.pdf';
            const disposition = res.headers.get('Content-Disposition') || '';

            const match = disposition.match(/filename\*=UTF-8''(.+)/);
            if (match && match[1]) {
                filename = decodeURIComponent(match[1]);
            } else {
                const match2 = disposition.match(/filename="?([^"]+)"?/);
                if (match2 && match2[1]) {
                    filename = match2[1];
                }
            }

            filename = filename.replace(/[/\\?%*:|"<>]/g, '_');

            const blob = await res.blob();
            const blobUrl = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();

            URL.revokeObjectURL(blobUrl);
            Swal.close(); // ✅ ปิดหมุน

        } catch (err) {
            Swal.close();
            Swal.fire('เกิดข้อผิดพลาด', err.message, 'error');
        }
    }


    // ฟังก์ชันโหลดห้องเรียนสำหรับ export
    function loadExportClassrooms() {
        const childGroup = document.getElementById('exportChildGroup').value;
        const classroomSelect = document.getElementById('exportClassroom');

        classroomSelect.innerHTML = '<option value="">ทั้งหมด</option>';

        if (!childGroup) return;

        fetch(`../../include/function/get_classrooms.php?child_group=${childGroup}`)
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

        fetch(`../../include/function/get_classrooms.php?child_group=${childGroup}`)
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


    function editRecord(studentId) {
        try {
            fetch(`./function/get_health_tooth_detail.php?id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่พบข้อมูลการตรวจสุขภาพสำหรับนักเรียนนี้',
                            confirmButtonText: 'ตกลง'
                        });
                        return;
                    }
                    const studentData = data.data;
                    // สร้างเนื้อหา Modal
                    const modalContent = `
                    <form id="healthCheckForm">
                        <input type="hidden" name="data_id" value="${studentData.id}">
                        <input type="hidden" name="student_id" value="${studentData.student_id}">
                        <input type="hidden" name="prefix_th" value="${studentData.prefix_th}">
                        <input type="hidden" name="first_name_th" value="${studentData.first_name}">
                        <input type="hidden" name="last_name_th" value="${studentData.last_name}">
                        <input type="hidden" name="class_room" value="${studentData.classroom}">
                        
                        <!-- ข้อมูลนักเรียน -->
                        <div class="student-info mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>วันที่:</strong></p> 
                                    <input type="date" name="" class="form-control dotted-input measurement-input text-center" value="<?php echo date('Y-m-d'); ?>" required>
              
                                </div>
                               
                                <div class="col-md-6">
                                    <p><strong>ประจำปีการศึกษา:</strong></p>
                                     <input type="text" class="form-control dotted-input measurement-input text-center" name="academic_year" value="${studentData.academic_year}" placeholder="2568" readonly>
                                </div>                           
                            </div>
                        </div>

                     
                    <div class="card form-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i>ข้อมูลพื้นฐาน
                            </div>
            
                            <div class="row mt-2">
                                <div class="col-12">                    
                                        <div class="me-5 flex-grow-1 d-flex  justify-content-between align-items-center">
                                            <label class="form-label fw-bold" style="white-space: nowrap;">ทันตแพทย์ผู้ตรวจ ทพ./ทพ.หญิง</label>
                                            <?php if ($_SESSION['role'] == 'doctor') { ?>
                                            <input type="text" name="doctor_name" class="form-control dotted-input measurement-input text-center" placeholder="ชื่อแพทย์/พยาบาล" value="<?php echo htmlspecialchars(getFullName(), ENT_QUOTES, 'UTF-8'); ?>" required>
                                                                                    <?php } else { ?>
                                            <input type="text" name"doctor_name" class="form-control dotted-input measurement-input text-center" value="${studentData.doctor_name || ''}" readonly>
                                           <?php } ?>
                                           <label class="form-label mb-0" style="white-space: nowrap;">ได้ทำการตรวจสุขภาพช่องปาก</label>
                                        </div>                                                                       
                                </div>
                            </div>                          
                            <div class="row g-3 mt-3 align-items-end ">
                                <!-- ชื่อ-นามสกุล -->
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">ชื่อ-นามสกุล</label>
                                    <input type="text" class="form-control dotted-input measurement-input text-center"
                                        value="${studentData.prefix_th}${studentData.first_name} ${studentData.last_name}">
                                </div>

                                <!-- ชื่อเล่น -->
                                <div class="col-md-2 col-sm-5">
                                    <label class="form-label fw-bold">ชื่อเล่น</label>
                                    <input type="text" class="form-control dotted-input measurement-input text-center"
                                        name="nickname" value="${studentData.nickname || '-'}">
                                </div>

                                <!-- ห้องเรียน -->
                                <div class="col-md-2 col-sm-4">
                                    <label class="form-label fw-bold">ห้องเรียน</label>
                                    <input type="text" class="form-control dotted-input measurement-input form-control dotted-input measurement-input text-center"
                                        value="${studentData.classroom}">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">อายุ (ปี)</label>
                                    <input type="number" name="age_year" class="form-control dotted-input measurement-input text-center" min="0" placeholder="1"  value="${studentData.age_year}">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">เดือน</label>
                                    <input type="number" name="age_month" class="form-control dotted-input measurement-input text-center" min="0" placeholder="10"  value="${studentData.age_month}">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">วัน</label>
                                    <input type="number" name="age_day" class="form-control dotted-input measurement-input text-center" min="0" placeholder="13"  value="${studentData.age_day}">
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                   <div class="card vital-signs-card mb-4">
                        <div class="card-body">
                            <div class="section-header mb-4">
                                <i class="bi bi-heart-pulse me-2"></i>การตรวจสุขภาพฟัน
                            </div>

                            <div class="container-fluid">
                                <!-- จำนวนฟัน -->
                                <div class="row mb-4 ">
                                    <div class="col-md-12 card border-2" style="border-color: #eae3ffff;">
                                        <div class="form-group d-flex flex-grow-4 align-items-center justify-content-around">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <label class="form-label fw-semibold mb-0 me-3 " style="min-width: 140px;">จำนวนฟันทั้งหมดกี่ซี่:</label>
                                                <div class="input-wrapper">
                                                    <input type="number" name="total_teeth" class="form-control dotted-input measurement-input text-center" 
                                                        style="max-width: 80px;" min="0" max="32" placeholder="1-32" value="${studentData.total_teeth || 0}">
                                                    <small class="text-muted ms-2">ซี่</small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <label class="form-label fw-semibold mb-0 me-3" style="min-width: 140px;">จำนวนฟันผุทั้งหมดกี่ซี่:</label>
                                                <div class="input-wrapper">
                                                    <input type="number" name="decayed_teeth" class="form-control dotted-input measurement-input text-center" 
                                                        style="max-width: 80px;" min="0" placeholder="0-32" value="${studentData.decayed_teeth || 0}">
                                                    <small class="text-muted ms-2">ซี่</small>
                                                </div>
                                            </div>
                                        
                                        </div>  
                                        <div class="form-group">
                                            <label class="form-label fw-semibold mb-2">ส่วนประกอบของช่องปาก (เหงือก/ลิ้น/เพดาน):</label>
                                            <input type="text" name="oral_components" class="form-control dotted-input measurement-input text-center" 
                                                placeholder="กรอกรายละเอียด..." value="${studentData.oral_components || ''}">
                                        </div>
                                    </div>              
                                </div>

          
                                <div class="row mb-2">
                                    <div class="col-md-12 card border-2 " style="border-color: #eae3ffff;">
                                        <div class="form-group">                     
                                            <div class="form-check mb-3 d-flex align-items-center">
                                                    <input class="form-check-input " type="radio" name="teeth_status" id="normal_teeth" value="normal">
                                                    <label class="form-check-label fw-bold text-warning" for="normal_teeth">
                                                        <i class="bi bi-exclamation-triangle ms-2 me-1"></i>ยังไม่มีฟันผุ
                                                    </label>
                                                    
                                            </div><input type="text" name="missing_teeth_detail" 
                                                        class="form-control dotted-input measurement-input d-inline ms-2" 
                                                            placeholder="ระบุรายละเอียด" value="${studentData.missing_teeth_detail || ''}">                  
                                        </div>                   
                                    </div>              
                                </div> 

                                <div class="row mb-4 ">
                                    <div class="col-md-12 card border-2" style="border-color: #eae3ffff;">
                                        <div class="form-group">                                           
                                            <div class="form-check mb-3 d-flex align-items-center">
                                                        <input class="form-check-input" type="radio" name="teeth_status" id="abnormal_teeth" value="abnormal">
                                                        <label class="form-check-label fw-bold text-success" for="abnormal_teeth">
                                                            <i class="bi bi-check-circle ms-2 me-1"></i>มีฟันผุที่บริเวณ
                                                        </label>
                                            </div>
                                        </div>  
                                        <div class="row mb-4"> 
                                                <div class="col-md-6">
                                                    <div class="card h-100 border-2" style="border-color: #e3f2fd;">
                                                        <div class="card-body">         
                                                            <div class="ms-4">
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันหน้าบน:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="upper_front_teeth" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามบนขวา:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="upper_right_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามล่างขวา:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="lower_right_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="card h-100 border-2" style="border-color: #fff3e0;">
                                                        <div class="card-body">                                            
                                                            <div class="ms-4">
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันหน้าล่าง:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="lower_front_teeth" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามบนซ้าย:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="upper_left_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">ฟันกรามล่างซ้าย:</small>
                                                                    </div>
                                                                    <div class="col-8">
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" name="lower_left_molar" 
                                                                                class="form-control dotted-input measurement-input text-center" 
                                                                                style="max-width: 60px;" min="0" placeholder="">
                                                                            <small class="text-muted ms-1">ซี่</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> 
                                    </div>             
                                </div> 

                                <!-- การรักษาที่จำเป็น -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title fw-bold mb-3">
                                                    <i class="bi bi-tools me-2"></i>จำเป็นต้องได้รับการรักษา ดังต่อไปนี้
                                                </h6>
                                                
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="filling" id="filling">
                                                                <label class="form-check-label" for="filling">อุดฟัน</label>
                                                            </div>
                                                        </div>
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="fluoride" id="fluoride">
                                                                <label class="form-check-label" for="fluoride">เคลือบฟลูออไรด์</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-4">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="root_canal" id="root_canal">
                                                                <label class="form-check-label" for="root_canal">รักษาคลองรากฟัน</label>
                                                            </div>
                                                        </div>
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="fluoride_molar" id="fluoride_molar">
                                                                <label class="form-check-label" for="fluoride_molar">เคลือบหลุมร่องฟันที่ฟันกราม</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-4">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="crown" id="crown">
                                                                <label class="form-check-label" for="crown">ครอบฟัน</label>
                                                            </div>
                                                        </div>
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="extraction" id="extraction">
                                                                <label class="form-check-label" for="extraction">ถอนฟัน</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="treatment-item mb-2">
                                                            <div class="form-check d-flex align-items-center">
                                                                <input class="form-check-input" type="checkbox" name="treatments[]" value="other" id="other_treatment_check">
                                                                <label class="form-check-label ms-2" for="other_treatment_check">อื่นๆ ได้แก่:</label>
                                                            </div>
                                                            <input type="text" name="other_treatment_detail" 
                                                                class="form-control dotted-input measurement-input mt-1" 
                                                                style="max-width: 100%;" placeholder="ระบุการรักษาอื่นๆ" value="${studentData.other_treatment_detail || ''}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ความเร่งด่วน -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-warning">
                                            <div class="card-body">
                                                <h6 class="card-title fw-bold mb-3">
                                                    <i class="bi bi-clock me-2"></i>ความเร่งด่วนในการรักษา
                                                </h6>
                                        
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-check d-flex align-items-center">
                                                            <input class="form-check-input" type="radio" name="urgency" id="urgent" value="urgent">
                                                            <label class="form-check-label text-danger fw-semibold" for="urgent">
                                                                <i class="bi bi-exclamation-circle me-1 ms-2"></i>ควรได้รับการรักษาโดยด่วน
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check d-flex align-items-center">
                                                            <input class="form-check-input" type="radio" name="urgency" id="not_urgent" value="not_urgent">
                                                            <label class="form-check-label text-success fw-semibold" for="not_urgent">
                                                                <i class="bi bi-check-circle me-1 ms-2"></i>ไม่เร่งด่วน
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <div class="form-check d-flex align-items-center">
                                                            <input class="form-check-input" type="radio" name="urgency" id="preventable" value="preventable">
                                                            <label class="form-check-label text-info fw-semibold" for="preventable">
                                                                <i class="bi bi-shield-check me-1 ms-2"></i>สามารถผัดผ่อนได้ในระยะเวลาไม่นานนัก
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                           
                </form>


                </form>`;
                    Swal.fire({
                        title: 'แก้ไขข้อมูลการตรวจสุขภาพ',
                        html: modalContent,
                        showCancelButton: true,
                        width: '85%',
                        confirmButtonText: 'บันทึก',
                        showCloseButton: true,
                        cancelButtonText: 'ยกเลิก',
                        didOpen: () => {
                            if (studentData.teeth_status) {
                                const radio = document.querySelector(`input[name="teeth_status"][value="${studentData.teeth_status}"]`);
                                if (radio) radio.checked = true;
                            }
                            if (studentData.decayed_teeth_positions) {
                                const positions = JSON.parse(studentData.decayed_teeth_positions);
                                document.querySelector('input[name="upper_front_teeth"]').value = positions.upper_front_teeth || 0;
                                document.querySelector('input[name="upper_left_molar"]').value = positions.upper_left_molar || 0;
                                document.querySelector('input[name="upper_right_molar"]').value = positions.upper_right_molar || 0;
                                document.querySelector('input[name="lower_front_teeth"]').value = positions.lower_front_teeth || 0;
                                document.querySelector('input[name="lower_left_molar"]').value = positions.lower_left_molar || 0;
                                document.querySelector('input[name="lower_right_molar"]').value = positions.lower_right_molar || 0;
                            }
                            if (studentData.treatments) {
                                document.querySelectorAll('input[name="treatments[]"]').forEach(cb => {
                                    cb.checked = false; // Reset all first
                                });

                                const treatments = JSON.parse(studentData.treatments);
                                treatments.forEach(treatment => {
                                    const checkbox = document.querySelector(`input[name="treatments[]"][value="${treatment}"]`);
                                    if (checkbox) checkbox.checked = true;
                                });
                            }

                            if (studentData.urgency) {
                                const selectedRadio = document.querySelector(`input[name="urgency"][value="${studentData.urgency}"]`);
                                if (selectedRadio) {
                                    selectedRadio.checked = true;
                                }
                            }
                        },
                        preConfirm: () => {
                            const form = document.getElementById('healthCheckForm');
                            const formData = collectFormData(form);
                            formData.data_id = form.querySelector('input[name="data_id"]').value || null;

                            return fetch('./process/save_edit_health_tooth.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify(formData)
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.status === 'success') {
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end', // มุมขวาบน
                                            icon: 'success', // success | error | warning | info | question
                                            title: 'บันทึกสำเร็จ!',
                                            showConfirmButton: false,
                                            timer: 3000,
                                            timerProgressBar: true
                                        })
                                        loadResults(); // โหลดข้อมูลใหม่

                                    } else {
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end', // มุมขวาบน
                                            icon: 'error',
                                            title: 'เกิดข้อผิดพลาด',
                                            text: error.message,
                                            timer: 3000,
                                            timerProgressBar: true,
                                            showConfirmButton: false,
                                        });
                                    }
                                })
                                .catch(error => {
                                    Swal.fire({
                                        toast: true,
                                        position: 'top-end', // มุมขวาบน
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด',
                                        text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
                                        timer: 3000,
                                        timerProgressBar: true,
                                        showConfirmButton: false,
                                    });
                                });
                        }
                    })
                })
        } catch (error) {
            console.error('Error fetching health data:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถดึงข้อมูลการตรวจสุขภาพได้',
            });
        }
    }

    function checkArrayInputs(name, values) {
        if (!Array.isArray(values)) {
            values = [values]; // แปลงเป็น array ถ้าเป็น string
        }
        values.forEach(val => {
            const selector = `input[name="${name}[]"][value="${val}"]`;
            const checkbox = document.querySelector(selector);
            if (checkbox) checkbox.checked = true;
        });
    }

       function exportToExcel() {
        Swal.fire({
            title: 'Export ข้อมูลการตรวจสุขภาพ',
            html: `
                <form id="exportFormExcel" class="text-start">                         
                    <!-- วันที่ -->
                    <div id="dailyField" class="mb-3">
                       <label for="date" class="form-label">ตรวจประจำปีการศึกษา</label>
                        <select name="academic_year" class="form-select">
                            <?php foreach ($academicYears as $index => $year): ?>
                                <option value="<?= $year['name'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>        
                    <input type="hidden" id="" name="doctor" value="all">       
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#exportFormExcel');
                const formData = new FormData(form);
                savedFormData = formData;

                // ส่งค่าไป PHP เพื่อนับข้อมูลก่อน
                return fetch('./function/get_count_health_tooth_export.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.count === 0) {
                            Swal.showValidationMessage('ไม่พบข้อมูลสำหรับ export');
                        }
                        return data.count;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(error.message);
                    });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const count = result.value;

                Swal.fire({
                    title: 'ยืนยันการ Export ข้อมูล',
                    text: `พบข้อมูลจำนวน ${count} รายการ ต้องการ Export หรือไม่?`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Export',
                    cancelButtonText: 'ยกเลิก'
                }).then(async (confirmResult) => {
                    if (confirmResult.isConfirmed) {
                        // 🔁 แสดงหมุนทันที
                        Swal.fire({
                            title: 'กำลังโหลด...',
                            html: 'กำลัง Export ไฟล์ Excel กรุณารอสักครู่',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading(); // ✅ spinner ขึ้นแน่
                                fetchAndDownloadExcel(); // 
                            }
                        });
                    }
                });
            }
        })
    }

    function fetchAndDownloadExcel() {
        fetch('./process/export_excel_health_tooth.php', {
                method: 'POST',
                body: savedFormData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // อ่านชื่อไฟล์จาก Content-Disposition header (รองรับ filename*=UTF-8''...)
                const disposition = response.headers.get('Content-Disposition') || '';
                let filename = 'health_check_data.csv';
                // รองรับ filename*=UTF-8''...
                let match = disposition.match(/filename\*=UTF-8''([^;\n]+)/);
                if (match && match[1]) {
                    filename = decodeURIComponent(match[1]);
                } else {
                    // fallback: filename="..."
                    match = disposition.match(/filename="?([^";]+)"?/);
                    if (match && match[1]) {
                        filename = match[1];
                    }
                }
                // ป้องกันอักขระต้องห้ามในชื่อไฟล์
                filename = filename.replace(/[\/\\?%*:|"<>]/g, '_');
                return response.blob().then(blob => ({
                    blob,
                    filename
                }));
            })
            .then(({
                blob,
                filename
            }) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);

                Swal.close();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Export ไฟล์ Excel สำเร็จ!',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            })
            .catch(error => {
                console.error('Error exporting data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถ Export ไฟล์ Excel ได้ กรุณาลองใหม่อีกครั้ง',
                });
            });
    }
</script>