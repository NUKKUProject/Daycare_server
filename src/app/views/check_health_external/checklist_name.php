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

    .custom-table {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        font-size: 14px;
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
        font-weight: 600;
        color: #667eea;
    }

    /* ต้องแยกออกมาแบบนี้ */
    .measurement-input::placeholder {
        color: #cccccc;
        opacity: 1;
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
        font-size: 14px;
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

    .score-input {
        width: 20px;
        font-size: 14px
    }

    .student-detail-view {
        text-align: left;
        font-size: 14px;
    }

    .student-detail-view .card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .student-detail-view .card-title {
        font-size: 16px;
        margin-bottom: 15px;
        padding-bottom: 8px;
    }

    .student-detail-view p {
        margin-bottom: 8px;
    }

    .swal2-popup-large {
        font-size: 14px !important;
    }

    .swal2-close-button-large {
        font-size: 24px !important;
    }

    .title-student-info {
        color: #17a2b8 !important;
        border-bottom: 2px solid #17a2b8 !important;
    }

    .title-vital-signs {
        color: #dc3545 !important;
        border-bottom: 2px solid #dc3545 !important;
    }

    .title-measurements {
        color: #28a745 !important;
        border-bottom: 2px solid #28a745 !important;
    }

    .title-behavior {
        color: #ffc107 !important;
        border-bottom: 2px solid #ffc107 !important;
    }

    .title-development {
        color: #8000ff !important;
        /* สีม่วง */
        border-bottom: 2px solid rgb(128, 0, 255) !important;
    }

    .title-physical-exam {
        color: #007bff !important;
        border-bottom: 2px solid #007bff !important;
    }

    .title-neurological {
        color: #6c757d !important;
        border-bottom: 2px solid #6c757d !important;
    }

    .title-recommendation {
        color: #343a40 !important;
        border-bottom: 2px solid #343a40 !important;
    }
</style>

<main class="main-content">
    <div class="container-fluid px-4">
        <h2 class="mb-4">บันทึกการตรวจสุขภาพเด็ก</h2>

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
                        <label for="date" class="form-label">ประจำปีการศึกษา</label>
                        <select name="academic_year" class="form-select">
                            <?php foreach ($academicYears as $index => $year): ?>
                                <option value="<?= $year['name'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="search" class="form-label">ค้นหาชื่อ</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="ชื่อ-นามสกุล"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>

                    <!-- เพิ่มปุ่ม Export ไว้ข้างๆ ปุ่มค้นหาและรีเซ็ต -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary mt-1">ค้นหา</button>
                        <button type="button" class="btn btn-secondary mt-1" onclick="resetForm()">รีเซ็ต</button>
                        <button type="button" class="btn btn-danger mt-1" onclick="exportToPdf()">
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
                    classroomSelect.value = '<?php echo $_GET['classroom']; ?>';
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

        fetch(`./function/get_health_external.php?${params.toString()}`)
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
                                    new Date(student.exam_date).toLocaleDateString('th-TH', {
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
            fetch(`./function/get_health_external_detail.php?id=${id}`)
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
                    const vitalSigns = JSON.parse(student.vital_signs);
                    const physicalExam = JSON.parse(student.physical_exam);
                    const neurological = JSON.parse(student.neurological);
                    const behavior = JSON.parse(student.behavior);
                    const measures = JSON.parse(student.physical_measures);
                    const development = JSON.parse(student.development_assessment);
                    const modalContent = `
                <div class="student-detail-view">
                    <!-- ข้อมูลนักเรียน -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title title-student-info">
                            <i class="bi bi-person-circle me-2"></i>ข้อมูลนักเรียน</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ชื่อ-นามสกุล:</strong> ${student.prefix_th}${student.first_name} ${student.last_name_th}</p>
                                    <p><strong>ชื่อเล่น:</strong> ${student.nickname || '-'}</p>
                                    <p><strong>ห้องเรียน:</strong> ${student.classroom}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>วันที่ตรวจ:</strong> ${student.exam_date ? new Date(student.exam_date).toLocaleDateString('th-TH', {day: 'numeric',month: 'long',year: 'numeric'}) : ''}</p>
                                    <p><strong>ปีการศึกษา:</strong> ${student.academic_year}</p>
                                    <p><strong>แพทย์ผู้ตรวจ:</strong> ${student.doctor_name || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- สัญญาณชีพ -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title title-vital-signs">
                             <i class="bi bi-heart-pulse me-2"></i>สัญญาณชีพ</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <p><strong>อุณหภูมิ:</strong> ${vitalSigns.temperature || '-'} °C</p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>ชีพจร:</strong> ${vitalSigns.pulse || '-'} ครั้ง/นาที</p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>หายใจ:</strong> ${vitalSigns.respiration || '-'} ครั้ง/นาที</p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>ความดันโลหิต:</strong> ${vitalSigns.bp || '-'} ครั้ง/นาที</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลการวัด -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title title-measurements">
                            <i class="bi bi-rulers me-2"></i>ข้อมูลการวัด</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>ส่วนสูง:</strong> ${measures.height || '-'} ซม.</p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>น้ำหนัก:</strong> ${measures.weight || '-'} กก.</p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>รอบศีรษะ:</strong> ${measures.head_circ || '-'} ซม.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>น้ำหนัก/อายุ:</strong> ${measures.weight_for_age || '-'}</p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>ส่วนสูง/อายุ:</strong> ${measures.height_for_age || '-'}</p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>น้ำหนัก/ส่วนสูง:</strong> ${measures.weight_for_height || '-'}</p>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-4">
                                    <p><strong>เปอร์เซ็นไทล: </strong> ${measures.head_percentile || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- พฤติกรรม -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title title-behavior">
                            <i class="bi bi-emoji-smile me-2"></i>พฤติกรรม</h5>
                            <p><strong>สถานะ:</strong> ${behavior.status === 'none' ? 'ปกติ' : 'มีพฤติกรรมผิดปกติ'}</p>
                            ${behavior.status === 'has' ? `<p><strong>รายละเอียด:</strong> ${behavior.detail || '-'}</p>` : ''}
                        </div>
                    </div>

                    <!-- การประเมินพัฒนาการทั้ง 5 ด้าน -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title title-development">
                                <i class="bi bi-graph-up me-2"></i>การประเมินพัฒนาการทั้ง 5 ด้าน
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>การเคลื่อนไหว (GM):</strong> ${getDevelopmentStatus(development.gm.status, development.gm.score)}</p>
                                    <p><strong>มัดเล็กและสติปัญญา (FM):</strong> ${getDevelopmentStatus(development.fm.status, development.fm.score)}</p>
                                    <p><strong>เข้าใจภาษา (RL):</strong> ${getDevelopmentStatus(development.rl.status, development.rl.score)}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>ใช้ภาษา (EL):</strong> ${getDevelopmentStatus(development.el.status, development.el.score)}</p>
                                    <p><strong>ช่วยเหลือตนเองและสังคม (PS):</strong> ${getDevelopmentStatus(development.ps.status, development.ps.score)}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- การตรวจร่างกาย -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title title-physical-exam">
                            <i class="fa-solid fa-stethoscope"></i> การตรวจร่างกาย</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p>
                                        <strong>สภาพทั่วไป:</strong> 
                                            ${getPhysicalExamStatus(physicalExam.general)} 
                                            ${physicalExam.general_detail ? ` - ${physicalExam.general_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>ผิวหนัง:</strong> 
                                            ${getPhysicalExamStatus(physicalExam.skin)}
                                            ${physicalExam.skin_detail ? ` - ${physicalExam.skin_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>ศีรษะ:</strong> 
                                            ${getPhysicalExamStatus(physicalExam.head)}
                                            ${physicalExam.head_detail ? ` - ${physicalExam.head_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>ใบหน้า:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.face)}
                                        ${physicalExam.face_detail ? ` - ${physicalExam.face_detail}` : ''}                                       
                                    </p>
                                    <p>
                                        <strong>ตา:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.eyes)}
                                        ${physicalExam.eyes_detail ? ` - ${physicalExam.eyes_detail}` : ''} 
                                    </p>
                                    <p>
                                        <strong>หูและการได้ยิน:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.ears)}
                                        ${physicalExam.ears_detail ? ` - ${physicalExam.ears_detail}` : ''} 
                                    </p>
                                    <p>
                                        <strong>จมูก:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.nose)}
                                        ${physicalExam.nose_detail ? ` - ${physicalExam.nose_detail}` : ''} 
                                    </p>
                                    <p>
                                        <strong>ปากและช่องปาก:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.mouth)}
                                        ${physicalExam.mouth_detail ? ` - ${physicalExam.mouth_detail}` : ''}
                                    </p>
                                </div>
                                <div class="col-md-6">                    
                                    <p>
                                        <strong>คอ:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.neck)}
                                        ${physicalExam.neck_detail ? ` - ${physicalExam.neck_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>ทรวงอกและปอด:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.breast)}
                                        ${physicalExam.breast_detail ? ` - ${physicalExam.breast_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>การหายใจ:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.breathe)}
                                        ${physicalExam.breathe_detail ? ` - ${physicalExam.breathe_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>ปอด:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.lungs)}
                                        ${physicalExam.lungs_detail ? ` - ${physicalExam.lungs_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>หัวใจ:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.heart)}
                                        ${physicalExam.heart_detail ? ` - ${physicalExam.heart_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>เสียงหัวใจ:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.heart_sound)}
                                        ${physicalExam.heart_sound_detail ? ` - ${physicalExam.heart_sound_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>ชีพจร:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.pulse)}
                                        ${physicalExam.pulse_detail ? ` - ${physicalExam.pulse_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>ท้อง:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.abdomen)}
                                        ${physicalExam.abdomen_detail ? ` - ${physicalExam.abdomen_detail}` : ''}
                                    </p>
                                    <p>
                                        <strong>อื่นๆ:</strong> 
                                        ${getPhysicalExamStatus(physicalExam.others)}
                                        ${physicalExam.others_detail ? ` - ${physicalExam.others_detail}` : ''}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ระบบประสาท -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title title-neurological">
                             <i class="fa-solid fa-brain"></i> ระบบประสาท</h5>
                            <p>
                                <strong>ปฏิกิริยาขั้นพื้นฐาน:</strong> 
                                ${getPhysicalExamStatus(neurological.neuro)}
                                ${neurological.neuro_detail ? ` - ${neurological.neuro_detail}` : ''}
                            </p>
                            <p>
                                <strong>การเคลื่อนไหว:</strong> 
                                ${getPhysicalExamStatus(neurological.movement)}
                                ${neurological.movement_detail ? ` - ${neurological.movement_detail}` : ''}
                            </p>
                        </div>
                    </div>

                    <!-- คำแนะนำ -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title title-recommendation">
                            <i class="bi bi-clipboard-check me-2"></i>คำแนะนำ</h5>
                            <p>${student.recommendation || 'ไม่มีคำแนะนำ'}</p>
                        </div>
                    </div>
                </div>`;

                    // แสดง Modal
                    Swal.fire({
                        title: 'ข้อมูลการตรวจสุขภาพ',
                        html: modalContent,
                        showConfirmButton: false,
                        showCloseButton: true,
                        width: '80%',
                        customClass: {
                            popup: 'swal2-popup-large',
                            closeButton: 'swal2-close-button-large'
                        }
                    });
                })
                .catch(error => {
                    console.error('Error fetching health data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถดึงข้อมูลการตรวจสุขภาพได้',
                    });
                });
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถดึงข้อมูลการตรวจสุขภาพได้',
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
                fetch('./process/delete_health_record.php', {
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
                        <input type="hidden" name="child_group" value="${student.child_group}">
                        <input type="hidden" name="class_room" value="${student.classroom}">
                        
                        <!-- ข้อมูลนักเรียน -->
                        <div class="student-info mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>วันที่:</strong></p> 
                                    <input type="date" name="exam_date" class="form-control dotted-input measurement-input text-center" value="<?php echo date('Y-m-d'); ?>" required>
              
                                </div>
                               
                                <div class="col-md-6">
                                    <p><strong>ประจำปีการศึกษา:</strong></p>
                                     <input type="text" class="form-control dotted-input measurement-input text-center" value="${year}" placeholder="2567">
                                </div>                           
                            </div>
                        </div>

                        <!-- ส่วนที่เหลือของฟอร์มยังคงเหมือนเดิม -->
                    <div class="card form-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i>ข้อมูลพื้นฐาน
                            </div>
            
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div class="me-3 flex-grow-1">
                                            <label class="form-label fw-bold">ข้าพเจ้า นพ./พญ.</label>
                                            <?php if ($_SESSION['role'] == 'doctor') { ?>
                                            <input type="text" name="doctor_name" class="form-control dotted-input measurement-input text-center" placeholder="ชื่อแพทย์/พยาบาล" value="<?php echo getFullName(); ?>" required>
                                                                                    <?php } else { ?>
                                            <input type="text" name="doctor_name" class="form-control dotted-input measurement-input text-center" value="${student.doctor_name || ''}" readonly>
                                           <?php } ?>
                                        </div>
                                    <div class="text-end">
                                        <label class="form-label mb-0">จากภาควิชากุมารเวชศาสตร์ คณะแพทย์ศาสตร์ มหาวิทยาลัยขอนแก่น</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mt-5 align-items-end ">
                                <!-- ได้ตรวจร่างกาย -->
                                <div class="col-md-2">
                                    <label class="form-label mb-0">ได้ตรวจร่างกาย</label>
                                </div>

                                <!-- ชื่อ-นามสกุล -->
                                <div class="col-md-6">
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
                            </div>
   

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold ">วัน/เดือน/ปีเกิด</label>
                                    <input type="date" name="birth_date" class="form-control dotted-input measurement-input text-center">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">อายุ (ปี)</label>
                                    <input type="number" name="age_year" class="form-control dotted-input measurement-input text-center" min="0" placeholder="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">เดือน</label>
                                    <input type="number" name="age_month" class="form-control dotted-input measurement-input text-center" min="0" placeholder="10">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">วัน</label>
                                    <input type="number" name="age_day" class="form-control dotted-input measurement-input text-center" min="0" placeholder="13">
                                </div>
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
                                        <input type="number" name="temperature" class="form-control measurement-input text-center" step="0.1" placeholder="36.5">
                                        <span class="input-group-text">°C</span>
                                    </div>
                                    <small class="d-block text-start text-muted">อุณหภูมิร่างกาย</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-activity"></i></span>
                                        <input type="number" name="pulse" class="form-control measurement-input text-center" placeholder="80">
                                        <span class="input-group-text">ครั้ง/นาที</span>
                                    </div>
                                    <small class="d-block text-start text-muted">ชีพจร</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lungs"></i></span>
                                        <input type="number" name="respiration" class="form-control measurement-input text-center" placeholder="20">
                                        <span class="input-group-text">ครั้ง/นาที</span>
                                    </div>
                                    <small class="d-block text-start text-muted">อัตราการหายใจ</small>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-heart"></i></span>
                                        <input type="text" name="bp" class="form-control measurement-input text-center" placeholder="120/80">
                                        <span class="input-group-text">ครั้ง/นาที</span>
                                    </div>
                                    <small class="d-block text-start text-muted">ความดันโลหิต</small>
                                </div>
                                 <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="date" name="bp_date" class="form-control measurement-input text-center">                               
                                    </div>
                                    <small class="text-muted d-block text-start">วันที่ตรวจ</small>
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
                                        <input class="custom-checkbox custom-checkbox" type="radio" name="behavior" id="behaviorNone" value="none">
                                        <label class="form-check-label fw-bold text-success" for="behaviorNone">
                                            <i class="bi bi-check-circle me-1"></i>ไม่มี
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="custom-checkbox custom-checkbox" type="radio" name="behavior" id="behaviorHas" value="has">
                                        <label class="form-check-label fw-bold text-warning" for="behaviorHas">
                                            <i class="bi bi-exclamation-triangle me-1"></i>มี
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3" id="behaviorDetail" style="display: none;">
                                <label class="form-label fw-bold">ระบุพฤติกรรมที่พบ:</label>
                                <textarea class="form-control" rows="3" name="behavior_detail" placeholder="อธิบายพฤติกรรมที่สังเกตพบ..."></textarea>
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
                                    <div class="input-group input-group">
                                        <span class="input-group-text"><i class="bi bi-arrow-up"></i></span>
                                        <input type="number" class="form-control measurement-input text-center" step="0.1" name="height" placeholder="120.5" id="height">
                                        <span class="input-group-text">เซนติเมตร</span>
                                    </div>
                                    <small class="form-label fw-bold mt-1 d-block text-start">ส่วนสูง</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group">
                                        <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                                        <input type="number" name="weight" class="form-control measurement-input text-center" step="0.1" placeholder="25.5" id="weight">
                                        <span class="input-group-text">กิโลกรัม</span>
                                    </div>
                                    <small class="form-label fw-bold mt-1 d-block text-start">น้ำหนัก</small>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-circle"></i></span>
                                        <input type="number" name="head_circ" class="form-control measurement-input text-center" step="0.1" placeholder="52.0">
                                        <span class="input-group-text">เซนติเมตร</span>
                                    </div>
                                    <small class="form-label fw-bold mt-1 d-block text-start">รอบศีรษะ</small>
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
                                                <th style="background-color:#e3f2fd;width:20%;">น้อยกว่าเกณฑ์</th>
                                                <th style="background-color:#e3f2fd;width:20%;">ค่อนข้างน้อย</th>
                                                <th style="background-color:#e3f2fd;width:20%;">ตามเกณฑ์</th>
                                                <th style="background-color:#e3f2fd;width:20%;">ค่อนข้างมาก</th>
                                                <th style="background-color:#e3f2fd;width:20%;">มากกว่าเกณฑ์</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="น้อยกว่าเกณฑ์"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="ค่อนข้างน้อย"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="ตามเกณฑ์"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="ค่อนข้างมาก"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="มากกว่าเกณฑ์"></td>
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
                                                <th style="background-color:#e3f2fd;width:20%;">เตี้ย</th>
                                                <th style="background-color:#e3f2fd;width:20%;">ค่อนข้างเตี้ย</th>
                                                <th style="background-color:#e3f2fd;width:20%;">ตามเกณฑ์</th>
                                                <th style="background-color:#e3f2fd;width:20%;">ค่อนข้างสูง</th>
                                                <th style="background-color:#e3f2fd;width:20%;">สูง</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="เตี้ย"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="ค่อนข้างเตี้ย"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="ตามเกณฑ์"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="ค่อนข้างสูง"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="สูง"></td>
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
                                                <th style="background-color:#e3f2fd">สมส่วน</th>
                                                <th style="background-color:#e3f2fd;">ท้วม</th>
                                                <th style="background-color:#e3f2fd;">เริ่มอ้วน</th>
                                                <th style="background-color:#e3f2fd;">อ้วน</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="ผอม"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="ค่อนข้างผอม"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="สมส่วน"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="ท้วม"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="เริ่มอ้วน"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="อ้วน"></td>
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
                                                <th style="background-color:#e3f2fd;">ความเสี่ยง</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="น้อยกว่า 3"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="3-15"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="15-50"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="50-85"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="85-97"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="มากกว่า 97"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="ความเสี่ยง"></td>
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
                                            <th colspan="3" style="width:20%; background-color:#fce4ec;">มัดเล็กและสติปัญญา(FM)</th>
                                            <th colspan="3" style="width:20%; background-color:#e8f5e9;">เข้าใจภาษา(RL)</th>
                                            <th colspan="3" style="width:20%; background-color:#fff3e0;">ใช้ภาษา(EL)</th>
                                            <th colspan="3" style="width:20%; background-color:#f3e5f5;">ช่วยเหลือตนเองและสังคม(PS)</th>
                                        </tr>
                                        <tr>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <!-- GM -->
                                            <td><input type="radio" class="custom-checkbox" name="gm_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="gm_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="gm_no"></td>
                                            <!-- FM -->
                                            <td><input type="radio" class="custom-checkbox" name="fm_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="fm_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="fm_no[]" ></td>
                                            <!-- RL -->
                                            <td><input type="radio" class="custom-checkbox" name="rl_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="rl_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="rl_no[]"></td>
                                            <!-- EL -->
                                            <td><input type="radio" class="custom-checkbox" name="el_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="el_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="el_no[]" ></td>
                                            <!-- PS -->
                                            <td><input type="radio" class="custom-checkbox" name="ps_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="ps_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="ps_no[]"></td>
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

                            <!-- สภาพทั่วไป -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-flex d-block text-start">สภาพทั่วไป</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="general_normal" name="general[]" value="normal">
                                        <label class="form-check-label" for="general_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="general_abnormal" name="general[]" value="abnormal">
                                        <label class="form-check-label" for="general_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="general_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                             <!-- ผิวหนัง -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">ผิวหนัง</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="skin_normal" name="skin[]" value="normal">
                                        <label class="form-check-label" for="skin_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="skin_abnormal" name="skin[]" value="abnormal">
                                        <label class="form-check-label" for="skin_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="skin_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ศีรษะ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-flex d-block text-start">ศีรษะ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="head_normal" name="head[]" value="normal">
                                        <label class="form-check-label" for="head_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="head_abnormal" name="head[]" value="abnormal">
                                        <label class="form-check-label" for="head_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="head_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ใบหน้า -->
                            <div class="mb-3 ms-5">                       
                                    <label class="form-label fw-bold d-flex d-block text-start">ใบหน้า</label>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div class="form-check">
                                            <input class="custom-checkbox" type="radio" id="face_normal" name="face[]" value="normal">
                                            <label class="form-check-label" for="face_normal">ปกติ</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="custom-checkbox" type="radio" id="face_abnormal" name="face[]" value="abnormal">
                                            <label class="form-check-label" for="face_abnormal">ผิดปกติ ระบุ</label>
                                        </div>
                                        <input type="text" class="form-control measurement-input" name="face_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ตา -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ตา</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="eyes_normal" name="eyes[]" value="normal">
                                        <label class="form-check-label" for="eyes_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="eyes_abnormal" name="eyes[]" value="abnormal">
                                        <label class="form-check-label" for="eyes_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="eyes_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- หูและการได้ยิน -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">หูและการได้ยิน</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="ears_normal" name="ears[]" value="normal">
                                        <label class="form-check-label" for="ears_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="ears_abnormal" name="ears[]" value="abnormal">
                                        <label class="form-check-label" for="ears_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="ears_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- จมูก -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">จมูก</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="nose_normal" name="nose[]" value="normal">
                                        <label class="form-check-label" for="nose_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="nose_abnormal" name="nose[]" value="abnormal">
                                        <label class="form-check-label" for="nose_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="nose_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>                      

                            <!-- ปากและช่องปาก -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ปากและช่องปาก</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="mouth_normal" name="mouth[]" value="normal">
                                        <label class="form-check-label" for="mouth_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="mouth_abnormal" name="mouth[]" value="abnormal">
                                        <label class="form-check-label" for="mouth_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="mouth_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- คอ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">คอ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neck_normal" name="neck[]" value="normal">
                                        <label class="form-check-label" for="neck_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neck_abnormal" name="neck[]" value="abnormal">
                                        <label class="form-check-label" for="neck_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="neck_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ทรวงอกและปอด -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">ทรวงอกและปอด</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breast_normal" name="breast[]" value="normal">
                                        <label class="form-check-label" for="breast_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breast_abnormal" name="breast[]" value="abnormal">
                                        <label class="form-check-label" for="breast_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="breast_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" >
                                </div>
                            </div>

                            <!-- การหายใจ -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">การหายใจ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breathe_normal" name="breathe[]" value="normal">
                                        <label class="form-check-label" for="breathe_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breathe_abnormal" name="breathe[]" value="abnormal">
                                        <label class="form-check-label" for="breathe_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="breathe_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ปอด -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ปอด</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="lungs_normal" name="lungs[]" value="normal">
                                        <label class="form-check-label" for="lungs_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="lungs_abnormal" name="lungs[]" value="abnormal">
                                        <label class="form-check-label" for="lungs_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="lungs_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- หัวใจ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-flex d-block text-start">หัวใจ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_normal" name="heart[]" value="normal">
                                        <label class="form-check-label" for="heart_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_abnormal" name="heart[]" value="abnormal">
                                        <label class="form-check-label" for="heart_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="heart_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- เสียงหัวใจ -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-flex d-block text-start">เสียงหัวใจ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_sound_normal" name="heart_sound[]" value="normal">
                                        <label class="form-check-label" for="heart_sound_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_sound_sound_abnormal" name="heart_sound[]" value="abnormal">
                                        <label class="form-check-label" for="hheart_sound_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="heart_sound_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ชีพจร -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ชีพจร</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="pulse_normal" name="pulse[]" value="normal">
                                        <label class="form-check-label" for="pulse_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="pulse_abnormal" name="pulse[]" value="abnormal">
                                        <label class="form-check-label" for="pulse_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="pulse_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ท้อง -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">ท้อง</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="abdomen_normal" name="abdomen[]" value="normal">
                                        <label class="form-check-label" for="abdomen_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="abdomen_abnormal" name="abdomen[]" value="abnormal">
                                        <label class="form-check-label" for="abdomen_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="abdomen_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- อื่น ๆ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">อื่น ๆ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="others_normal" name="others[]" value="normal">
                                        <label class="form-check-label" for="others_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="others_abnormal" name="others[]" value="abnormal">
                                        <label class="form-check-label" for="others_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="others_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;">
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
                                <label class="form-label fw-bold d-block text-start">ปฏิกิริยาขั้นพื้นฐาน</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neuro_normal" name="neuro[]" value="normal">
                                        <label class="form-check-label" for="neuro_normal">ปกติ</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neuro_abnormal" name="neuro[]" value="abnormal">
                                        <label class="form-check-label" for="neuro_abnormal">ผิดปกติ ระบุ</label>
                                    </div>

                                    <input type="text" class="form-control dotted-line" placeholder="ระบุรายละเอียดหากผิดปกติ" name="neuro_detail" style="max-width: 30rem;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">การเคลื่อนไหวร่างกาย กล้ามเนื้อและเส้นประสาท</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="movement_normal" name="movement[]" value="normal">
                                        <label class="form-check-label" for="movement_normal">ปกติ</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="movement_abnormal" name="movement[]" value="abnormal">
                                        <label class="form-check-label" for="movement_abnormal">ผิดปกติ ระบุ</label>
                                    </div>

                                    <input type="text" class="form-control" placeholder="ระบุรายละเอียดหากผิดปกติ" name="movement_detail" style="max-width: 30rem;">
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
                                <textarea class="form-control" rows="6" name="recommendation" placeholder="กรอกคำแนะนำและข้อสังเกต..." style="border: none; border-bottom: 1px dotted #6c757d; background: transparent; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>                   
                </form>
                
                `;

                // แสดง Modal
                Swal.fire({
                    title: 'แบบบันทึกการตรวจสุขภาพ โดยกุมารแพทย์',
                    html: modalContent,
                    width: '85%',
                    showCancelButton: true,
                    showCloseButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    didOpen: () => {
                        setupFormEventListeners(); // ถ้ามีการจับ event
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveHealth();
                    }
                });
            });
    }


    function setupFormEventListeners() {
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

    }

    // ฟังก์ชันสำหรับจัดการการส่งฟอร์ม
    function saveHealth() {
        const form = document.getElementById('healthCheckForm');
        const formData = collectFormData(form);
        // ส่งข้อมูลไปยัง API
        fetch('./process/save_health_data.php', {
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
        const formData = new FormData(form);

        const data = {
            data_id: document.querySelector('[name="data_id"]')?.value || null,
            exam_date: document.querySelector('[name="exam_date"]')?.value || null,
            academic_year: document.querySelector('[name="academic_year"]')?.value || null,
            doctor_name: document.querySelector('[name="doctor_name"]')?.value || null,
            student_id: document.querySelector('[name="student_id"]')?.value || null,
            prefix_th: document.querySelector('[name="prefix_th"]')?.value || null,
            first_name_th: document.querySelector('[name="first_name_th"]')?.value || null,
            last_name_th: document.querySelector('[name="last_name_th"]')?.value || null,
            child_grop: document.querySelector('[name="child_group"]')?.value || null,
            classroom: document.querySelector('[name="class_room"]')?.value || null,
            birth_date: document.querySelector('[name="birth_date"]')?.value || null,
            age_year: document.querySelector('[name="age_year"]')?.value || null,
            age_month: document.querySelector('[name="age_month"]')?.value || null,
            age_day: document.querySelector('[name="age_day"]')?.value || null,
            nickname: document.querySelector('[name="nickname"]')?.value || null,
            // jsonb fields (เก็บเป็น object ก่อน แล้ว serialize ตอนส่ง)
            vital_signs: {
                temperature: document.querySelector('[name="temperature"]')?.value || null,
                pulse: document.querySelector('[name="pulse"]')?.value || null,
                respiration: document.querySelector('[name="respiration"]')?.value || null,
                bp: document.querySelector('[name="bp"]')?.value || null,
                bp_date: document.querySelector('[name="bp_date"]')?.value || null,
            },
            behavior: {
                status: document.querySelector('input[name="behavior"]:checked')?.value || null,
                detail: document.querySelector('[name="behavior_detail"]')?.value || null,
            },
            physical_measures: {
                height: document.querySelector('[name="height"]')?.value || null,
                weight: document.querySelector('[name="weight"]')?.value || null,
                head_circ: document.querySelector('[name="head_circ"]')?.value || null,

                weight_for_age: Array.from(document.querySelectorAll('input[name="weightAge[]"]:checked')).map(el => el.value),
                height_for_age: Array.from(document.querySelectorAll('input[name="heightAge[]"]:checked')).map(el => el.value),
                weight_for_height: Array.from(document.querySelectorAll('input[name="weightHeight[]"]:checked')).map(el => el.value),
                head_percentile: Array.from(document.querySelectorAll('input[name="headCirc[]"]:checked')).map(el => el.value),
            },
            development_assessment: {
                gm: {
                    status: document.querySelector('input[name="gm_assessment[]"]:checked')?.value || '',
                    score: document.querySelector('input[name="gm_no"]')?.value || ''
                },
                fm: {
                    status: document.querySelector('input[name="fm_assessment[]"]:checked')?.value || '',
                    score: document.querySelector('input[name="fm_no"]')?.value || ''
                },
                rl: {
                    status: document.querySelector('input[name="rl_assessment[]"]:checked')?.value || '',
                    score: document.querySelector('input[name="rl_no"]')?.value || ''
                },
                el: {
                    status: document.querySelector('input[name="el_assessment[]"]:checked')?.value || '',
                    score: document.querySelector('input[name="el_no"]')?.value || ''
                },
                ps: {
                    status: document.querySelector('input[name="ps_assessment[]"]:checked')?.value || '',
                    score: document.querySelector('input[name="ps_no"]')?.value || ''
                }
            },
            physical_exam: {
                general: Array.from(document.querySelectorAll('input[name="general[]"]:checked')).map(el => el.value),
                general_detail: document.querySelector('[name="general_detail"]')?.value || null,
                skin: Array.from(document.querySelectorAll('input[name="skin[]"]:checked')).map(el => el.value),
                skin_detail: document.querySelector('[name="skin_detail"]')?.value || null,
                head: Array.from(document.querySelectorAll('input[name="head[]"]:checked')).map(el => el.value),
                head_detail: document.querySelector('[name="head_detail"]')?.value || null,
                face: Array.from(document.querySelectorAll('input[name="face[]"]:checked')).map(el => el.value),
                face_detail: document.querySelector('[name="face_detail"]')?.value || null,
                eyes: Array.from(document.querySelectorAll('input[name="eyes[]"]:checked')).map(el => el.value),
                eyes_detail: document.querySelector('[name="eyes_detail"]')?.value || null,
                ears: Array.from(document.querySelectorAll('input[name="ears[]"]:checked')).map(el => el.value),
                ears_detail: document.querySelector('[name="ears_detail"]')?.value || null,
                nose: Array.from(document.querySelectorAll('input[name="nose[]"]:checked')).map(el => el.value),
                nose_detail: document.querySelector('[name="nose_detail"]')?.value || null,
                mouth: Array.from(document.querySelectorAll('input[name="mouth[]"]:checked')).map(el => el.value),
                mouth_detail: document.querySelector('[name="mouth_detail"]')?.value || null,
                neck: Array.from(document.querySelectorAll('input[name="neck[]"]:checked')).map(el => el.value),
                neck_detail: document.querySelector('[name="neck_detail"]')?.value || null,
                breast: Array.from(document.querySelectorAll('input[name="breast[]"]:checked')).map(el => el.value),
                breast_detail: document.querySelector('[name="breast_detail"]')?.value || null,
                breathe: Array.from(document.querySelectorAll('input[name="breathe[]"]:checked')).map(el => el.value),
                breathe_detail: document.querySelector('[name="breathe_detail"]')?.value || null,
                lungs: Array.from(document.querySelectorAll('input[name="lungs[]"]:checked')).map(el => el.value),
                lungs_detail: document.querySelector('[name="lungs_detail"]')?.value || null,
                heart: Array.from(document.querySelectorAll('input[name="heart[]"]:checked')).map(el => el.value),
                heart_detail: document.querySelector('[name="heart_detail"]')?.value || null,
                heart_sound: Array.from(document.querySelectorAll('input[name="heart_sound[]"]:checked')).map(el => el.value),
                heart_sound_detail: document.querySelector('[name="heart_sound_detail"]')?.value || null,
                pulse: Array.from(document.querySelectorAll('input[name="pulse[]"]:checked')).map(el => el.value),
                pulse_detail: document.querySelector('[name="pulse_detail"]')?.value || null,
                abdomen: Array.from(document.querySelectorAll('input[name="abdomen[]"]:checked')).map(el => el.value),
                abdomen_detail: document.querySelector('[name="abdomen_detail"]')?.value || null,
                others: Array.from(document.querySelectorAll('input[name="others[]"]:checked')).map(el => el.value),
                others_detail: document.querySelector('[name="others_detail"]')?.value || null,
            },
            neurological: {
                neuro: Array.from(document.querySelectorAll('input[name="neuro[]"]:checked')).map(el => el.value),
                neuro_detail: document.querySelector('[name="neuro_detail"]')?.value || null,
                movement: Array.from(document.querySelectorAll('input[name="movement[]"]:checked')).map(el => el.value),
                movement_detail: document.querySelector('[name="movement_detail"]')?.value || null,
            },
            recommendation: document.querySelector('[name="recommendation"]')?.value || null,
            signature: document.querySelector('[name="signature"]')?.value || null,


        };
        // ส่วนที่เหลือของฟังก์ชันยังคงเหมือนเดิม...
        return data;
    }


    function exportToPdf() {
        Swal.fire({
            title: 'Export ข้อมูลการตรวจสุขภาพ',
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
                    
                    <select name="doctor" class="form-select">
                        <option value="all">-- ทั้งหมด --</option>
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
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#exportForm');
                const formData = new FormData(form);
                savedFormData = formData;

                // ส่งค่าไป PHP เพื่อนับข้อมูลก่อน
                return fetch('./function/get_count_health_export.php', {
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

        const exportUrl = `./process/export_health_external.php?${queryParams.toString()}`;

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
            fetch(`./function/get_health_external_detail.php?id=${studentId}`)
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
                    const vitalSigns = JSON.parse(studentData.vital_signs);
                    const physicalExam = JSON.parse(studentData.physical_exam);
                    const neurological = JSON.parse(studentData.neurological);
                    const behavior = JSON.parse(studentData.behavior);
                    const measures = JSON.parse(studentData.physical_measures);
                    const development = JSON.parse(studentData.development_assessment);

                    // สร้างเนื้อหา Modal
                    const modalContent = `
                    <form id="healthCheckForm">
                        <input type="hidden" name="student_id" value="${studentData.student_id}">
                        <input type="hidden" name="data_id" value="${studentData.id}">
                        <input type="hidden" name="prefix_th" value="${studentData.prefix_th}">
                        <input type="hidden" name="first_name_th" value="${studentData.first_name}">
                        <input type="hidden" name="last_name_th" value="${studentData.last_name_th}">
                        <input type="hidden" name="child_group" value="${studentData.child_grop}">
                        <input type="hidden" name="class_room" value="${studentData.classroom}">
                        
                        <!-- ข้อมูลนักเรียน -->
                        <div class="student-info mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>วันที่:</strong></p>                               
                                <?php if ($_SESSION['role'] == 'doctor') { ?>
                                            <input type="date" name="exam_date" class="form-control dotted-input measurement-input text-center" value="<?= date('Y-m-d') ?>" required>
                                          
                                <?php } else { ?>
                                            <input type="date" name="exam_date" class="form-control dotted-input measurement-input text-center" value="${studentData.exam_date || ''}" required>                 
                                <?php } ?>
                                </div>
                               
                                <div class="col-md-6">
                                    <p><strong>ประจำปีการศึกษา:</strong></p>
                                     <input type="text" class="form-control dotted-input measurement-input text-center" value="${studentData.academic_year}" placeholder="2567">
                                </div>                           
                            </div>
                        </div>

                        <!-- ส่วนที่เหลือของฟอร์มยังคงเหมือนเดิม -->
                    <div class="card form-section mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i>ข้อมูลพื้นฐาน
                            </div>
            
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div class="me-3 flex-grow-1">
                                            <label class="form-label fw-bold">ข้าพเจ้า นพ./พญ.</label>
                                            <?php if ($_SESSION['role'] == 'doctor') { ?>
                                                <input type="text" name="doctor_name" class="form-control dotted-input measurement-input text-center" placeholder="ชื่อแพทย์/พยาบาล" value="<?php echo getFullName(); ?>" required>
                                                                                        <?php } else { ?>
                                                <input type="text" name="doctor_name" class="form-control dotted-input measurement-input text-center" value="${studentData.doctor_name || ''}" readonly>
                                           <?php } ?>
                                            
                                        </div>
                                    <div class="text-end">
                                        <label class="form-label mb-0">จากภาควิชากุมารเวชศาสตร์ คณะแพทย์ศาสตร์ มหาวิทยาลัยขอนแก่น</label>
                                    </div>
                                </div>
                            </div>
                    
                            <div class="row g-3 mt-5 align-items-end ">
                                <!-- ได้ตรวจร่างกาย -->
                                <div class="col-md-2">
                                    <label class="form-label mb-0">ได้ตรวจร่างกาย</label>
                                </div>

                                <!-- ชื่อ-นามสกุล -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ชื่อ-นามสกุล</label>
                                    <input type="text" class="form-control dotted-input measurement-input text-center"
                                        value="${studentData.prefix_th}${studentData.first_name} ${studentData.last_name_th}">
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
                                    <input type="text" class="form-control dotted-input measurement-input text-center"
                                        value="${studentData.classroom}">
                                </div>
                            </div>


                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">วัน/เดือน/ปีเกิด</label>
                                    <input type="date" name="birth_date" class="form-control dotted-input measurement-input text-center" value="${studentData.birth_date || ''}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">อายุ (ปี)</label>
                                    <input type="number" name="age_year" class="form-control dotted-input measurement-input text-center" min="0" value="${studentData.age_year || 0}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">เดือน</label>
                                    <input type="number" name="age_month" class="form-control dotted-input measurement-input text-center" min="0" max="11" value="${studentData.age_month || 0}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">วัน</label>
                                    <input type="number" name="age_day" class="form-control dotted-input measurement-input text-center" min="0" max="31" value="${studentData.age_day || 0}">
                                </div>
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
                                        <input type="number" name="temperature" class="form-control measurement-input text-center" step="0.1" placeholder="36.5" value="${vitalSigns.temperature || ''}">
                                        <span class="input-group-text">°C</span>
                                    </div>
                                    <small class="text-muted d-block text-start">อุณหภูมิร่างกาย</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-activity"></i></span>
                                        <input type="number" name="pulse" class="form-control measurement-input text-center" placeholder="80" value="${vitalSigns.pulse || ''}">
                                        <span class="input-group-text">ครั้ง/นาที</span>
                                    </div>
                                    <small class="d-block text-start text-muted">ชีพจร</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lungs"></i></span>
                                        <input type="number" name="respiration" class="form-control measurement-input text-center" placeholder="20" value="${vitalSigns.respiration || ''}">
                                        <span class="input-group-text">ครั้ง/นาที</span>
                                    </div>
                                    <small class="text-muted d-block text-start">อัตราการหายใจ</small>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-heart"></i></span>
                                        <input type="text" name="bp" class="form-control measurement-input text-center" placeholder="120/80" value="${vitalSigns.bp || ''}">
                                        <span class="input-group-text"ครั้ง/นาที</span>
                                    </div>
                                    <small class="text-muted d-block text-start">ความดันโลหิต</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="date" name="bp_date" class="form-control measurement-input text-center" value="${vitalSigns.bp_date || ''}">                               
                                    </div>
                                    <small class="text-muted d-block text-start">วันที่ตรวจ</small>
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
                                        <input class="custom-checkbox custom-checkbox" type="radio" name="behavior" id="behaviorNone" value="none" ${behavior.status === 'none' ? 'checked' : ''}>
                                        <label class="form-check-label fw-bold text-success" for="behaviorNone">
                                            <i class="bi bi-check-circle me-1"></i>ไม่มี
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="custom-checkbox custom-checkbox" type="radio" name="behavior" id="behaviorHas" value="has" ${behavior.status === 'has' ? 'checked' : ''}>
                                        <label class="form-check-label fw-bold text-warning" for="behaviorHas">
                                            <i class="bi bi-exclamation-triangle me-1"></i>มี
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3" id="behaviorDetail" ${behavior.status === 'has' ? '' : 'style="display: none;"'}">
                                <label class="form-label fw-bold">ระบุพฤติกรรมที่พบ:</label>
                                <textarea class="form-control measurement-input" rows="3" name="behavior_detail" placeholder="อธิบายพฤติกรรมที่สังเกตพบ..."}>${behavior.detail || ''}</textarea>
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
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-arrow-up"></i></span>
                                        <input type="number" class="form-control measurement-input text-center" step="0.1" name="height" placeholder="120.5" id="height" value="${measures.height || ''}">
                                        <span class="input-group-text">เซนติเมตร</span>
                                    </div>
                                    <small class="form-label fw-bold mt-1 d-block text-start">ส่วนสูง</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                                        <input type="number" name="weight" class="form-control measurement-input text-center" step="0.1" placeholder="25.5" id="weight" value="${measures.weight || ''}">
                                        <span class="input-group-text">กิโลกรัม</span>
                                    </div>
                                    <small class="form-label fw-bold mt-1 d-block text-start">น้ำหนัก</small>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-circle"></i></span>
                                        <input type="number" name="head_circ" class="form-control measurement-input text-center" step="0.1" placeholder="52.0" id="head_circ" value="${measures.head_circ || ''}">
                                        <span class="input-group-text">เซนติเมตร</span>
                                    </div>
                                    <small class="form-label fw-bold mt-1 d-block text-start">รอบศีรษะ</small>
                                </div>                               
                            </div>

                            <!-- Weight for Age Table -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 d-block text-start">
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
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="น้อยกว่าเกณฑ์"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="ค่อนข้างน้อย"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="ตามเกณฑ์"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="ค่อนข้างมาก"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightAge[]" value="มากกว่าเกณฑ์"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Height for Age Table -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 d-block text-start">
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
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="เตี้ย"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="ค่อนข้างเตี้ย"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="ตามเกณฑ์"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="ค่อนข้างสูง"></td>
                                                <td><input type="radio" class="custom-checkbox" name="heightAge[]" value="สูง"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Weight for Height Table -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 d-block text-start">
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
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="ผอม"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="ค่อนข้างผอม"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="สมส่วน"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="ท้วม"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="เริ่มอ้วน"></td>
                                                <td><input type="radio" class="custom-checkbox" name="weightHeight[]" value="อ้วน"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Head Circumference Percentile -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 d-block text-start">
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
                                                <th style="background-color:#e3f2fd;">ความเสี่ยง</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="น้อยกว่า 3"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="3-15"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="15-50"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="50-85"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="85-97"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="มากกว่า 97"></td>
                                                <td><input type="radio" class="custom-checkbox" name="headCirc[]" value="ความเสี่ยง"></td>
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
                                            <th colspan="3" style="width:20%; background-color:#fce4ec;">มัดเล็กและสติปัญญา(FM)</th>
                                            <th colspan="3" style="width:20%; background-color:#e8f5e9;">เข้าใจภาษา(RL)</th>
                                            <th colspan="3" style="width:20%; background-color:#fff3e0;">ใช้ภาษา(EL)</th>
                                            <th colspan="3" style="width:20%; background-color:#f3e5f5;">ช่วยเหลือตนเองและสังคม(PS)</th>
                                        </tr>
                                        <tr>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                            <th style="background-color:#e3f2fd;">ผ่าน</th>
                                            <th style="background-color:#e3f2fd;">สงสัยล่าช้า</th>
                                            <th style="background-color:#e3f2fd;">ข้อที่</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <!-- GM -->
                                            <td><input type="radio" class="custom-checkbox" name="gm_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="gm_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="gm_no" value="${development.gm.score}"></td>
                                            <!-- FM -->
                                            <td><input type="radio" class="custom-checkbox" name="fm_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="fm_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="fm_no" value="${development.fm.score}"></td>
                                            <!-- RL -->
                                            <td><input type="radio" class="custom-checkbox" name="rl_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="rl_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="rl_no" value="${development.rl.score}"></td>
                                            <!-- EL -->
                                            <td><input type="radio" class="custom-checkbox" name="el_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="el_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="el_no" value="${development.el.score}"></td>
                                            <!-- PS -->
                                            <td><input type="radio" class="custom-checkbox" name="ps_assessment[]" value="pass"></td>
                                            <td><input type="radio" class="custom-checkbox" name="ps_assessment[]" value="delay"></td>
                                            <td><input type="text" class="custom-checkbox score-input" name="ps_no" value="${development.ps.score}"></td>
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

                            <!-- สภาพทั่วไป -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-flex d-block text-start">สภาพทั่วไป</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="general_normal" name="general[]" value="normal">
                                        <label class="form-check-label" for="general_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="general_abnormal" name="general[]" value="abnormal">
                                        <label class="form-check-label" for="general_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="general_detail" placeholder="ระบุอาการที่พบ..." ${physicalExam.general_detail ? `value="${physicalExam.general_detail}"` : ''} style="max-width: 30rem;">
                                </div>
                            </div>

                             <!-- ผิวหนัง -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">ผิวหนัง</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="skin_normal" name="skin[]" value="normal">
                                        <label class="form-check-label" for="skin_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="skin_abnormal" name="skin[]" value="abnormal">
                                        <label class="form-check-label" for="skin_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="skin_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.skin_detail ? `value="${physicalExam.skin_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- ศีรษะ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-flex d-block text-start">ศีรษะ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="head_normal" name="head[]" value="normal">
                                        <label class="form-check-label" for="head_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="head_abnormal" name="head[]" value="abnormal">
                                        <label class="form-check-label" for="head_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="head_detail" placeholder="ระบุอาการที่พบ..." ${physicalExam.head_detail ? `value="${physicalExam.head_detail}"` : ''} style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ใบหน้า -->
                            <div class="mb-3 ms-5">                       
                                    <label class="form-label fw-bold d-flex d-block text-start">ใบหน้า</label>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div class="form-check">
                                            <input class="custom-checkbox" type="radio" id="face_normal" name="face[]" value="normal">
                                            <label class="form-check-label" for="face_normal">ปกติ</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="custom-checkbox" type="radio" id="face_abnormal" name="face[]" value="abnormal">
                                            <label class="form-check-label" for="face_abnormal">ผิดปกติ ระบุ</label>
                                        </div>
                                        <input type="text" class="form-control measurement-input" name="face_detail" placeholder="ระบุอาการที่พบ..." ${physicalExam.face_detail ? `value="${physicalExam.face_detail}"` : ''} style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ตา -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ตา</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="eyes_normal" name="eyes[]" value="normal">
                                        <label class="form-check-label" for="eyes_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="eyes_abnormal" name="eyes[]" value="abnormal">
                                        <label class="form-check-label" for="eyes_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="eyes_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.eyes_detail ? `value="${physicalExam.eyes_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- หูและการได้ยิน -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">หูและการได้ยิน</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="ears_normal" name="ears[]" value="normal">
                                        <label class="form-check-label" for="ears_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="ears_abnormal" name="ears[]" value="abnormal">
                                        <label class="form-check-label" for="ears_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="ears_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.ears_detail ? `value="${physicalExam.ears_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- จมูก -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">จมูก</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="nose_normal" name="nose[]" value="normal">
                                        <label class="form-check-label" for="nose_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="nose_abnormal" name="nose[]" value="abnormal">
                                        <label class="form-check-label" for="nose_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="nose_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.nose_detail ? `value="${physicalExam.nose_detail}"` : ''}>
                                </div>
                            </div>                      

                            <!-- ปากและช่องปาก -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ปากและช่องปาก</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="mouth_normal" name="mouth[]" value="normal">
                                        <label class="form-check-label" for="mouth_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="mouth_abnormal" name="mouth[]" value="abnormal">
                                        <label class="form-check-label" for="mouth_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="mouth_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.mouth_detail ? `value="${physicalExam.mouth_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- คอ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">คอ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neck_normal" name="neck[]" value="normal">
                                        <label class="form-check-label" for="neck_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neck_abnormal" name="neck[]" value="abnormal">
                                        <label class="form-check-label" for="neck_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="neck_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.neck_detail ? `value="${physicalExam.neck_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- ทรวงอกและปอด -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">ทรวงอกและปอด</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breast_normal" name="breast[]" value="normal">
                                        <label class="form-check-label" for="breast_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breast_abnormal" name="breast[]" value="abnormal">
                                        <label class="form-check-label" for="breast_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="breast_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.breast_detail ? `value="${physicalExam.breast_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- การหายใจ -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">การหายใจ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breathe_normal" name="breathe[]" value="normal">
                                        <label class="form-check-label" for="breathe_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="breathe_abnormal" name="breathe[]" value="abnormal">
                                        <label class="form-check-label" for="breathe_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="breathe_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.breathe_detail ? `value="${physicalExam.breathe_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- ปอด -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ปอด</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="lungs_normal" name="lungs[]" value="normal">
                                        <label class="form-check-label" for="lungs_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="lungs_abnormal" name="lungs[]" value="abnormal">
                                        <label class="form-check-label" for="lungs_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="lungs_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.lungs_detail ? `value="${physicalExam.lungs_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- หัวใจ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-flex d-block text-start">หัวใจ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_normal" name="heart[]" value="normal">
                                        <label class="form-check-label" for="heart_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_abnormal" name="heart[]" value="abnormal">
                                        <label class="form-check-label" for="heart_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="heart_detail" placeholder="ระบุอาการที่พบ..." ${physicalExam.heart_detail ? `value="${physicalExam.heart_detail}"` : ''} style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- เสียงหัวใจ -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-flex d-block text-start">เสียงหัวใจ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_sound_normal" name="heart_sound[]" value="normal">
                                        <label class="form-check-label" for="heart_sound_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="heart_sound_sound_abnormal" name="heart_sound[]" value="abnormal">
                                        <label class="form-check-label" for="hheart_sound_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="heart_sound_detail" placeholder="ระบุอาการที่พบ..." ${physicalExam.heart_sound_detail ? `value="${physicalExam.heart_sound_detail}"` : ''} style="max-width: 30rem;">
                                </div>
                            </div>

                            <!-- ชีพจร -->
                            <div class="mb-3 ms-5">
                                <label class="form-label fw-bold d-block text-start">ชีพจร</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="pulse_normal" name="pulse[]" value="normal">
                                        <label class="form-check-label" for="pulse_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="pulse_abnormal" name="pulse[]" value="abnormal">
                                        <label class="form-check-label" for="pulse_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="pulse_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.pulse_detail ? `value="${physicalExam.pulse_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- ท้อง -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">ท้อง</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="abdomen_normal" name="abdomen[]" value="normal">
                                        <label class="form-check-label" for="abdomen_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="abdomen_abnormal" name="abdomen[]" value="abnormal">
                                        <label class="form-check-label" for="abdomen_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="abdomen_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.abdomen_detail ? `value="${physicalExam.abdomen_detail}"` : ''}>
                                </div>
                            </div>

                            <!-- อื่น ๆ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">อื่น ๆ</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="others_normal" name="others[]" value="normal">
                                        <label class="form-check-label" for="others_normal">ปกติ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="others_abnormal" name="others[]" value="abnormal">
                                        <label class="form-check-label" for="others_abnormal">ผิดปกติ ระบุ</label>
                                    </div>
                                    <input type="text" class="form-control measurement-input" name="others_detail" placeholder="ระบุอาการที่พบ..." style="max-width: 30rem;" ${physicalExam.others_detail ? `value="${physicalExam.others_detail}"` : ''}>
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
                                <label class="form-label fw-bold d-block text-start">ปฏิกิริยาขั้นพื้นฐาน</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neuro_normal" name="neuro[]" value="normal">
                                        <label class="form-check-label" for="neuro_normal">ปกติ</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="neuro_abnormal" name="neuro[]" value="abnormal">
                                        <label class="form-check-label" for="neuro_abnormal">ผิดปกติ ระบุ</label>
                                    </div>

                                    <input type="text" class="form-control dotted-line measurement-input" placeholder="ระบุรายละเอียดหากผิดปกติ" name="neuro_detail" style="max-width: 30rem;" ${neurological.neuro_detail ? `value="${neurological.neuro_detail}"` : ''}>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold d-block text-start">การเคลื่อนไหวร่างกาย กล้ามเนื้อและเส้นประสาท</label>
                                <div class="d-flex flex-wrap align-items-center gap-3">

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="movement_normal" name="movement[]" value="normal">
                                        <label class="form-check-label" for="movement_normal">ปกติ</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="custom-checkbox" type="radio" id="movement_abnormal" name="movement[]" value="abnormal">
                                        <label class="form-check-label" for="movement_abnormal">ผิดปกติ ระบุ</label>
                                    </div>

                                    <input type="text" class="form-control measurement-input" placeholder="ระบุรายละเอียดหากผิดปกติ" name="movement_detail" style="max-width: 30rem;" ${neurological.movement_detail ? `value="${neurological.movement_detail}"` : ''}>
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
                                <textarea class="form-control measurement-input" rows="6" name="recommendation" placeholder="กรอกคำแนะนำและข้อสังเกต..." style="border: none; border-bottom: 1px dotted #6c757d; background: transparent; resize: vertical;">${studentData.recommendation ? studentData.recommendation.trim() : ''}</textarea>
                            </div>
                        </div>
                    </div>
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
                            setupFormEventListeners(); // ถ้ามีการจับ event
                            checkArrayInputs('weightAge', measures.weight_for_age);
                            checkArrayInputs('heightAge', measures.height_for_age);
                            checkArrayInputs('weightHeight', measures.weight_for_height);
                            checkArrayInputs('headCirc', measures.head_percentile);
                            checkArrayInputs('gm_assessment', development.gm.status);
                            checkArrayInputs('fm_assessment', development.fm.status);
                            checkArrayInputs('rl_assessment', development.rl.status);
                            checkArrayInputs('el_assessment', development.el.status);
                            checkArrayInputs('ps_assessment', development.ps.status);
                            checkArrayInputs('general', physicalExam.general);
                            checkArrayInputs('skin', physicalExam.skin);
                            checkArrayInputs('head', physicalExam.head);
                            checkArrayInputs('face', physicalExam.face);
                            checkArrayInputs('eyes', physicalExam.eyes);
                            checkArrayInputs('ears', physicalExam.ears);
                            checkArrayInputs('nose', physicalExam.nose);
                            checkArrayInputs('mouth', physicalExam.mouth);
                            checkArrayInputs('neck', physicalExam.neck);
                            checkArrayInputs('breast', physicalExam.breast);
                            checkArrayInputs('breathe', physicalExam.breathe);
                            checkArrayInputs('lungs', physicalExam.lungs);
                            checkArrayInputs('heart', physicalExam.heart);
                            checkArrayInputs('heart_sound', physicalExam.heart_sound);
                            checkArrayInputs('pulse', physicalExam.pulse);
                            checkArrayInputs('abdomen', physicalExam.abdomen);
                            checkArrayInputs('others', physicalExam.others);
                            checkArrayInputs('neuro', neurological.neuro);
                            checkArrayInputs('movement', neurological.movement);

                        },
                        preConfirm: () => {
                            const form = document.getElementById('healthCheckForm');
                            const formData = collectFormData(form);

                            return fetch('./process/save_edit_health_data.php', {
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
            didOpen: () => {
                // โหลดห้องเรียนถ้ามีการเลือกกลุ่มเรียนไว้
                const currentGroup = document.getElementById('child_group').value;
                if (currentGroup) {
                    document.getElementById('exportChildGroup').value = currentGroup;
                    loadExportClassrooms();
                }
            },
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#exportFormExcel');
                const formData = new FormData(form);
                savedFormData = formData;

                // ส่งค่าไป PHP เพื่อนับข้อมูลก่อน
                return fetch('./function/get_count_health_export.php', {
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
        fetch('./process/export_excel_health.php', {
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