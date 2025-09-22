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
        /* Checklist History Page Styles */
.checklist-container {
    padding: 2rem;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}

/* Search Form */
.search-card {
    background: #f8f9fa;
    border: none;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.search-card .card-body {
    padding: 1.5rem;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-select, .form-control {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-select:focus, .form-control:focus {
    border-color: #26648E;
    box-shadow: 0 0 0 0.2rem rgba(38, 100, 142, 0.15);
}

/* Search Buttons */
.search-buttons {
    display: flex;
    gap: 0.8rem;
    margin-top: 1rem;
}

.btn-search {
    background: #26648E;
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: all 0.3s ease;
}

.btn-search:hover {
    background: #1E4F6F;
    transform: translateY(-2px);
}

.btn-reset {
    background: #e9ecef;
    color: #495057;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: all 0.3s ease;
}

.btn-reset:hover {
    background: #dee2e6;
    color: #212529;
}

/* Results Table */
.results-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.table {
    margin-bottom: 0;
}

.table thead {
    background: #26648E;
}

.table thead th {
    font-weight: bold;
    font-weight: 500;
    padding: 1rem;
    border: none;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.table tbody tr:hover {
    background-color: rgba(38, 100, 142, 0.05);
}

/* Status Badges */
.health-status {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.status-normal {
    background-color: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.status-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.status-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-view {
    background-color: rgba(38, 100, 142, 0.1);
    color: #26648E;
    border: none;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s ease;
}

.btn-view:hover {
    background-color: rgba(38, 100, 142, 0.2);
    transform: translateY(-2px);
}

.btn-edit {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.btn-edit:hover {
    background-color: rgba(255, 193, 7, 0.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .checklist-container {
        padding: 1rem;
    }

    .search-buttons {
        flex-direction: column;
    }

    .search-buttons .btn {
        width: 100%;
    }

    .action-buttons {
        flex-direction: column;
    }

    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }

    .table-responsive {
        margin: 0 -1rem;
    }
}

/* เพิ่ม Modern Detail Styles */
.detail-container {
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.detail-header {
    background: linear-gradient(120deg, #2C3E50, #3498DB);
    color: white;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.detail-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: linear-gradient(120deg, rgba(255,255,255,0.1), transparent);
    transform: skewX(-30deg);
}

.detail-header h5 {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: white;
    position: relative;
}

.detail-body {
    padding: 2rem;
}

.detail-section {
    background: #F8FAFC;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #ECF0F1;
    transition: all 0.3s ease;
}

.detail-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.detail-icon {
    color: #3498DB;
    font-size: 1.2rem;
    padding: 0.8rem;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 10px;
}

.detail-content {
    flex: 1;
}

.detail-content h6 {
    color: #2C3E50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.detail-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.detail-list li {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.5rem 0;
    color: #34495E;
}

.detail-list li::before {
    content: "•";
    color: #3498DB;
    font-size: 1.5rem;
    line-height: 0;
}

.detail-note {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(52, 152, 219, 0.05);
    border-left: 3px solid #3498DB;
    border-radius: 0 10px 10px 0;
    color: #34495E;
    font-style: italic;
}

.detail-footer {
    margin-top: 2rem;
    padding: 2rem;
    background: #F8FAFC;
    border-radius: 15px;
}

.detail-signature {
    text-align: right;
    font-style: italic;
    color: #34495E;
}

.detail-signature span {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Status Badges Modern Style */
.status-badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 500;
    margin-top: 1rem;
}

.status-normal-modern {
    background: rgba(46, 204, 113, 0.1);
    color: #27AE60;
    border: 1px solid rgba(46, 204, 113, 0.2);
}

.status-warning-modern {
    background: rgba(241, 196, 15, 0.1);
    color: #F1C40F;
    border: 1px solid rgba(241, 196, 15, 0.2);
}

.status-danger-modern {
    background: rgba(231, 76, 60, 0.1);
    color: #E74C3C;
    border: 1px solid rgba(231, 76, 60, 0.2);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .detail-header,
    .detail-body {
        padding: 1.5rem;
    }

    .detail-item {
        flex-direction: column;
    }

    .detail-icon {
        align-self: flex-start;
    }
}

    </style>

<main class="main-content">
<div class="checklist-container">
    <h2 class="mb-4">ประวัติการตรวจร่างกายของเด็ก</h2>

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
                        value="<?php echo isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); ?>">
                </div>

                <div class="col-md-3">
                    <label for="search" class="form-label">ค้นหาชื่อ</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="ชื่อ-นามสกุล"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>

                <!-- เพิ่มปุ่ม Export ไว้ข้างๆ ปุ่มค้นหาและรีเซ็ต -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">ค้นหา</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">รีเซ็ต</button>
                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
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

</main>
<script>
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
        
        fetch(`../include/function/get_health_history.php?${params.toString()}`)
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
                                                <th>สถานะ</th>
                                                <th>เวลาที่ตรวจ</th>
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
                                <td>
                                    <span class="badge ${hasRecord ? 'bg-success' : 'bg-secondary'}">
                                        ${hasRecord ? 'บันทึกแล้ว' : 'ยังไม่มีการบันทึก'}
                                    </span>
                                </td>
                                
                                <td>
                                    ${hasRecord ? 
                                        new Date(student.created_at).toLocaleTimeString('th-TH', {
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) + ' น.' : '-'}
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
                                    <?php if ($is_admin || $is_teacher): ?>
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

    // ฟังก์ชันดูรายละเอียด
    function viewDetails(id) {
        fetch(`../include/function/get_health_detail.php?id=${id}`)
            .then(response => response.json())
            .then(response => {
                if (response.status !== 'success') {
                    throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                }
                const data = response.data;

                // ฟังก์ชันสำหรับแสดงรายการที่ถูกเลือก
                const displayCheckedItems = (field) => {
                    if (!data[field] || (!data[field].checked && !data[field].unchecked)) return '';

                    const checkedItems = data[field].checked || [];
                    if (checkedItems.length === 0) return 'ไม่พบข้อมูล';

                    return checkedItems.map(item => `<li>- ${item}</li>`).join('');
                };

                // สร้าง HTML สำหรับแสดงรายละเอียด
                const modalContent = `
                    <div class="detail-container">
                        <div class="detail-header">
                            <h5>
                                <i class="bi bi-clipboard2-pulse"></i>
                                รายละเอียดการตรวจร่างกาย
                            </h5>
                            <div class="student-info">
                                <p><i class="bi bi-person"></i> <strong>รหัสนักเรียน:</strong> ${data.student_id}</p>
                                <p><i class="bi bi-person-vcard"></i> <strong>ชื่อ-นามสกุล:</strong> ${data.prefix_th} ${data.first_name_th} ${data.last_name_th}</p>
                                <p><i class="bi bi-people"></i> <strong>กลุ่มเรียน:</strong> ${data.child_group}</p>
                                <p><i class="bi bi-door-open"></i> <strong>ห้องเรียน:</strong> ${data.classroom}</p>
                                <p><i class="bi bi-calendar-check"></i> <strong>วันที่ตรวจ:</strong> ${data.formatted_date}</p>
                                <p><i class="bi bi-person-check"></i> <strong>ครูผู้ตรวจ:</strong> ${data.teacher_signature}</p>
                            </div>
                        </div>

                        <div class="detail-body">
                            <!-- ผลการตรวจร่างกาย -->
                            <div class="detail-section">
                                <h5><i class="bi bi-heart-pulse"></i> ผลการตรวจร่างกาย</h5>
                                
                                <!-- ผม/ศีรษะ -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-person-lines-fill"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>ผม/ศีรษะ</h6>
                                        <ul class="detail-list">${displayCheckedItems('hair')}</ul>
                                        ${data.hair_reason ? `<div class="detail-note">รายละอียดอื่นๆ: ${data.hair_reason}</div>` : ''}
                                    </div>
                                </div>

                                <!-- ตา -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>ตา</h6>
                                        <ul class="detail-list">${displayCheckedItems('eye')}</ul>
                                        ${data.eye_condition ? `<div class="detail-note">ลักษณะขี้ตา: ${data.eye_condition}</div>` : ''}
                                        ${data.eye_reason ? `<div class="detail-note">รายละอียดอื่นๆ: ${data.eye_reason}</div>` : ''}
                                    </div>
                                </div>

                                <!-- เพิ่มส่วนอื่นๆ ในรูปแบบเดียวกัน -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-emoji-smile"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>ปากและคอ</h6>
                                        <ul class="detail-list">${displayCheckedItems('mouth')}</ul>
                                    </div>
                                </div>

                                <!-- ฟัน -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-emoji-smile"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>ฟัน</h6>
                                        <ul class="detail-list">${displayCheckedItems('teeth')}</ul>
                                        ${data.teeth_count ? `<div class="detail-note">จำนวนฟันผุ: ${data.teeth_count}</div>` : ''}
                                    </div>
                                </div>

                                <!-- หู -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-ear"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>หู</h6>
                                        <ul class="detail-list">${displayCheckedItems('ears')}</ul>
                                    </div>
                                </div>

                                <!-- จมูก -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-emoji-neutral"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>จมูก</h6>
                                        <ul class="detail-list">${displayCheckedItems('nose')}</ul>
                                        ${data.nose_condition ? `<div class="detail-note">ลักษณะน้ำมูก: ${data.nose_condition}</div>` : ''}
                                        ${data.nose_reason ? `<div class="detail-note">รายละอียดอื่นๆ: ${data.nose_reason}</div>` : ''}
                                    </div>
                                </div>

                                <!-- เล็บ -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-hand-index"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>เล็บ</h6>
                                        <ul class="detail-list">${displayCheckedItems('nails')}</ul>
                                    </div>
                                </div>

                                <!-- ผิวหนัง -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-bandaid"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>ผิวหนัง</h6>
                                        <ul class="detail-list">${displayCheckedItems('skin')}</ul>
                                        ${data.skin_wound_detail ? `<div class="detail-note">รายละเอียดแผล: ${data.skin_wound_detail}</div>` : ''}
                                        ${data.skin_rash_detail ? `<div class="detail-note">รายละเอียดผื่น: ${data.skin_rash_detail}</div>` : ''}
                                    </div>
                                </div>

                                <!-- อาการผิดปกติ -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-thermometer-half"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>อาการผิดปกติ</h6>
                                        <ul class="detail-list">${displayCheckedItems('symptoms')}</ul>
                                        ${data.fever_temp ? `<div class="detail-note">อุณหภูมิ: ${data.fever_temp} °C</div>` : ''}
                                        ${data.cough_type ? `<div class="detail-note">ลักษณะการไอ: ${data.cough_type}</div>` : ''}
                                        ${data.symptoms_reason ? `<div class="detail-note">รายละอียดอื่นๆ: ${data.symptoms_reason}</div>` : ''}
                                    </div>
                                </div>

                                <!-- การใช้ยา -->
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-capsule"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>การใช้ยา</h6>
                                        <ul class="detail-list">${displayCheckedItems('medicine')}</ul>
                                        ${data.medicine_detail ? `<div class="detail-note">รายละเอียดยา: ${data.medicine_detail}</div>` : ''}
                                        ${data.medicine_reason ? `<div class="detail-note">รายละอียดอื่นๆ: ${data.medicine_reason}</div>` : ''}
                                    </div>
                                </div>
                            </div>

                            <!-- บันทึกเพิ่มเติม -->
                            <div class="detail-section">
                                <h5><i class="bi bi-journal-text"></i> บันทึกเพิ่มเติม</h5>
                                
                                ${data.illness_reason ? `
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-hospital"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>การเจ็บป่วย</h6>
                                        <div class="detail-note">รายละอียด: ${data.illness_reason}</div>
                                    </div>
                                </div>
                                ` : ''}

                                ${data.accident_reason ? `
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-bandaid"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>อุบัติเหตุ/แมลงกัดต่อย</h6>
                                        <div class="detail-note">รายละอียด: ${data.accident_reason}</div>
                                    </div>
                                </div>
                                ` : ''}

                                ${data.teacher_note ? `
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-pencil-square"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h6>บันทึกของครู</h6>
                                        <div class="detail-note">บันทึกของครู: ${data.teacher_note}</div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>

                            <!-- ลายเซ็นครู -->
                            <div class="detail-footer">
                                <div class="detail-signature">
                                    <span>
                                        <i class="bi bi-person-check"></i>
                                        ลงชื่อครูผู้ตรวจ: ${data.teacher_signature}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                Swal.fire({
                    title: 'รายละเอียดการตรวจร่างกาย',
                    html: modalContent,
                    width: '80%',
                    confirmButtonText: 'ปิด',
                    customClass: {
                        container: 'health-detail-modal'
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถโหลดข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            });
    }

        // เพิ่มฟังก์ชันสำหรับเก็บค่า checkbox
    function getCheckboxValues(fieldName) {
        const checkboxes = document.querySelectorAll(`input[name="${fieldName}"]`);
        const checked = [];
        const unchecked = [];

        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                checked.push(checkbox.value);
            } else {
                unchecked.push(`ไม่${checkbox.value}`);
            }
        });

        return {
            checked: checked,
            unchecked: unchecked
        };
    }


    // ฟังก์ชันแก้ไขข้อมูล
    function editRecord(id) {
        fetch(`../include/function/get_health_detail.php?id=${id}`)
            .then(response => response.json())
            .then(response => {
                if (response.status !== 'success') {
                    throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                }
                const data = response.data;

                // ฟังก์ชันสร้าง checkbox group
                const createCheckboxGroup = (field, options, checkedItems = []) => {
                    return options.map(option => {
                        // แยกค่า condition ออกจาก checkbox value
                        let isChecked = false;
                        if (Array.isArray(checkedItems)) {
                            // ตรวจสอบว่ามีค่า condition อยู่ในข้อความหรือไม่
                            isChecked = checkedItems.some(item => {
                                if (field === 'teeth' && option === 'ฟันผุ') {
                                    return item.includes('ฟันผุ');
                                } else if (field === 'symptoms' && option === 'มีไข้') {
                                    return item.includes('มีไข้');
                                } else if (field === 'symptoms' && option === 'ไอ') {
                                    return item.includes('ไอ');
                                } else if (field === 'nose' && option === 'มีน้ำมูก') {
                                    return item.includes('มีน้ำมูก');
                                } else if (field === 'eye' && option === 'มีขี้ตา') {
                                    return item.includes('มีขี้ตา');
                                } else {
                                    return item === option;
                                }
                            });
                        }

                        return `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                    name="${field}" value="${option}" 
                                    ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label">${option}</label>
                            </div>
                        `;
                    }).join('');
                };

                const modalContent = `
                                <form id="editHealthForm">
                                                <input type="hidden" name="id" value="${data.id}">
                                                <div class="row">
                                                <div class="detail-header">
                                            <h5>
                                                <i class="bi bi-clipboard2-pulse"></i>
                                                รายละเอียดการตรวจร่างกาย
                                            </h5>
                                    <div class="student-info">
                                            <p><i class="bi bi-person"></i> <strong>รหัสนักเรียน:</strong> ${data.student_id}</p>
                                            <p><i class="bi bi-person-vcard"></i> <strong>ชื่อ-นามสกุล:</strong> ${data.prefix_th} ${data.first_name_th} ${data.last_name_th}</p>
                                            <p><i class="bi bi-people"></i> <strong>กลุ่มเรียน:</strong> ${data.child_group}</p>
                                            <p><i class="bi bi-door-open"></i> <strong>ห้องเรียน:</strong> ${data.classroom}</p>
                                            <p><i class="bi bi-calendar-check"></i> <strong>วันที่ตรวจ:</strong> ${data.formatted_date}</p>
                                            <p><i class="bi bi-person-check"></i> <strong>ครูผู้ตรวจ:</strong> ${data.teacher_signature}</p>
                            
                                            <input type="hidden" name="student_id" value="${data.student_id}">
                                            <input type="hidden" name="prefix_th" value="${data.prefix_th}">
                                            <input type="hidden" name="first_name_th" value="${data.first_name_th}">
                                            <input type="hidden" name="last_name_th" value="${data.last_name_th}">
                                            <input type="hidden" name="child_group" value="${data.child_group}">
                                            <input type="hidden" name="classroom" value="${data.classroom}">
                                        </div>
                                                    </div>
                                        <div class="col-md-6">
                                            <h5 class="mb-3">ผลการตรวจร่างกาย</h5>
                            
                                            <!-- ผม/ศีรษะ -->
                                            <div class="mb-3">
                                                <label class="form-label">ผม/ศีรษะ</label>
                                                ${createCheckboxGroup('hair', ['สะอาด', 'ผมยาว', 'ไม่สะอาด', 'มีเหา'],
data.hair?.checked || [])}
                                                <textarea class="form-control mt-2" name="hair_reason" 
                                                    placeholder="หมายเหตุเพิ่มเติม">${data.hair_reason || ''}</textarea>
                                            </div>

                                            <!-- ตา -->
                                            <div class="mb-3">
                                                <label class="form-label">ตา</label>
                                                ${createCheckboxGroup('eye', ['ปกติ', 'ตาแดง', 'มีขี้ตา'],
data.eye?.checked || [])}
                                                <select class="form-select mt-2" name="eye_condition">
                                                    <option value="">เลือกลักษณะขี้ตา</option>
                                                        <option value="ขวาปกติ" ${data.eye_condition === 'ขวาปกติ' ? 'selected' : ''}>ขวาปกติ</option>
                                                        <option value="เหลือง/เขียว" ${data.eye_condition === 'เหลือง/เขียว' ? 'selected' : ''}>เหลือง/เขียว</option>
                                            </select>
                                                <textarea class="form-control mt-2" name="eye_reason" 
                                                    placeholder="หมายเหตุเพิ่มเติม">${data.eye_reason || ''}</textarea>
                                            </div>

                                            <!-- เพิ่มฟิลด์อื่นๆ ในลักษณะเดียวกัน -->

                                    <!-- ปากและคอ -->
                                    <div class="mb-3">
                                        <label class="form-label">ปากและคอ</label>
                                        ${createCheckboxGroup('mouth', ['สะอาด', 'มีกลิ่นปาก', 'มีแผลในปาก', 'มีตุ่มในปาก'],
data.mouth?.checked || [])}
                                    </div>

                                    <!-- ฟัน -->
                                    <div class="mb-3">
                                        <label class="form-label">ฟัน</label>
                                        ${createCheckboxGroup('teeth', ['สะอาด', 'ไม่สะอาด', 'ฟันผุ'],
    data.teeth?.checked || [])}
                                        <div class="mt-2" id="teethCountDiv" style="display: ${data.teeth_count ? 'block' : 'none'}">
                                            <label>จำนวนฟันผุ:</label>
                                            <input type="number" class="form-control" name="teeth_count" 
                                                value="${data.teeth_count || ''}" min="1" max="32">
                                        </div>
                                    </div>

                                    <!-- หู -->
                                    <div class="mb-3">
                                        <label class="form-label">หู</label>
                                        ${createCheckboxGroup('ears', ['สะอาด', 'ไม่สะอาด', 'มีขี้หู'],
        data.ears?.checked || [])}
                                    </div>

                                    <!-- จมูก -->
                                    <div class="mb-3">
                                        <label class="form-label">จมูก</label>
                                        ${createCheckboxGroup('nose', ['สะอาด', 'มีน้ำมูก'],
            data.nose?.checked || [])}
                                        <select class="form-select mt-2" name="nose_condition">
                                            <option value="">เลือกลักษณะน้ำมูก</option>
                                            <option value="ใส" ${data.nose_condition === 'ใส' ? 'selected' : ''}>ใส</option>
                                            <option value="เหลือง" ${data.nose_condition === 'เหลือง' ? 'selected' : ''}>เหลือง</option>
                                            <option value="เขียว" ${data.nose_condition === 'เขียว' ? 'selected' : ''}>เขียว</option>
                                        </select>
                                        <textarea class="form-control mt-2" name="nose_reason" 
                                            placeholder="หมายเหตุเพิ่มเติม">${data.nose_reason || ''}</textarea>
                                    </div>

                                    <!-- เล็บ -->
                                    <div class="mb-3">
                                        <label class="form-label">เล็บ</label>
                                        ${createCheckboxGroup('nails', ['สะอาด', 'ไม่สะอาด', 'เล็บยาว'],
                data.nails?.checked || [])}
                                    </div>

                                    <!-- ผิวหนัง -->
                                    <div class="mb-3">
                                        <label class="form-label">ผิวหนัง</label>
                                        ${createCheckboxGroup('skin', ['สะอาด', 'มีแผล', 'มีผื่น', 'มีขี้ไคล'],
                    data.skin?.checked || [])}
                                        <div class="mt-2" id="skinWoundDiv" style="display: ${data.skin_wound_detail ? 'block' : 'none'}">
                                            <label>รายละเอียดแผล:</label>
                                            <textarea class="form-control" name="skin_wound_detail">${data.skin_wound_detail || ''}</textarea>
                                        </div>
                                        <div class="mt-2" id="skinRashDiv" style="display: ${data.skin_rash_detail ? 'block' : 'none'}">
                                            <label>รายละเอียดผื่น:</label>
                                            <textarea class="form-control" name="skin_rash_detail">${data.skin_rash_detail || ''}</textarea>
                                        </div>
                                    </div>

                                    <!-- มือและเท้า -->
                                    <div class="mb-3">
                                        <label class="form-label">มือและเท้า</label>
                                        ${createCheckboxGroup('hands_feet', ['ปกติ', 'จุดหรือผื่น', 'ตุ่มใส', 'ตุ่มหนอง'],
                        data.hands_feet?.checked || [])}
                                    </div>

                                    <!-- แขนและขา -->
                                    <div class="mb-3">
                                        <label class="form-label">แขนและขา</label>
                                        ${createCheckboxGroup('arms_legs', ['ปกติ', 'จุดหรือผื่น', 'ตุ่มใส', 'ตุ่มหนอง'],
                            data.arms_legs?.checked || [])}
                                    </div>

                                    <!-- ลำตัวและหลัง -->
                                    <div class="mb-3">
                                        <label class="form-label">ลำตัวและหลัง</label>
                                        ${createCheckboxGroup('body', ['ปกติ', 'จุดหรือผื่น', 'ตุ่มใส', 'ตุ่มหนอง'],
                                data.body?.checked || [])}
                                    </div>

                                    <!-- อาการผิดปกติ -->
                                    <div class="mb-3">
                                        <label class="form-label">อาการผิดปกติ</label>
                                        ${createCheckboxGroup('symptoms', ['ไม่มี', 'มีไข้', 'ไอ'],
                                    data.symptoms?.checked || [])}
                                        <div class="mt-2" id="feverDiv" style="display: ${data.fever_temp ? 'block' : 'none'}">
                                            <label>อุณหภูมิ:</label>
                                            <input type="number" class="form-control" name="fever_temp" 
                                                value="${data.fever_temp || ''}" step="0.1" min="35" max="42">
                                        </div>
                                        <div class="mt-2" id="coughDiv" style="display: ${data.cough_type ? 'block' : 'none'}">
                                            <label>ลักษณะการไอ:</label>
                                            <select class="form-select" name="cough_type">
                                                <option value="">เลือกลักษณะการไอ</option>
                                                <option value="ไอแห้ง" ${data.cough_type === 'ไอแห้ง' ? 'selected' : ''}>ไอแห้ง</option>
                                                <option value="มีเสมหะ" ${data.cough_type === 'มีเสมหะ' ? 'selected' : ''}>มีเสมหะ</option>
                                            </select>
                                        </div>
                                        <textarea class="form-control mt-2" name="symptoms_reason" 
                                            placeholder="หมายเหตุเพิ่มเติม">${data.symptoms_reason || ''}</textarea>
                                    </div>

                                    <!-- การใช้ยา -->
                                    <div class="mb-3">
                                        <label class="form-label">การใช้ยา</label>
                                        ${createCheckboxGroup('medicine', ['ไม่มี', 'มียา'],
                                        data.medicine?.checked || [])}
                                        <div class="mt-2" id="medicineDetailDiv" style="display: ${data.medicine_detail ? 'block' : 'none'}">
                                            <label>รายละเอียดยา:</label>
                                            <textarea class="form-control" name="medicine_detail">${data.medicine_detail || ''}</textarea>
                                        </div>
                                        <textarea class="form-control mt-2" name="medicine_reason" 
                                            placeholder="หมายเหตุเพิ่มเติม">${data.medicine_reason || ''}</textarea>
                                    </div>
                            
                                    </div>
                                        <div class="col-12 mt-3">
                                            <h5 class="mb-3">บันทึกเพิ่มเติม</h5>
                                            <div class="mb-3">
                                                <label class="form-label">การเจ็บป่วย</label>
                                                <textarea class="form-control" name="illness_reason">${data.illness_reason || ''}</textarea>
                                    </div>
                                            <div class="mb-3">
                                                <label class="form-label">อุบัติเหตุ/แมลงกัดต่อย</label>
                                                <textarea class="form-control" name="accident_reason">${data.accident_reason || ''}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">บันทึกของครู</label>
                                                <textarea class="form-control" name="teacher_note">${data.teacher_note || ''}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ลงชื่อครู</label>
                                                <input type="text" class="form-control" name="teacher_signature" 
                                                    value="${data.teacher_signature || ''}" required>
                                    </div>
                                        </div>
                                    </div>
                                            </form>
                                        `;

                Swal.fire({
                    title: 'แก้ไขข้อมูลการตรวจร่างกาย',
                    html: modalContent,
                    width: '80%',
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    didOpen: () => {
                        // จัดการ checkbox ตา
                        document.querySelector('input[name="eye"][value="มีขี้ตา"]')?.addEventListener('change', function () {
                            const eyeConditionDiv = document.getElementById('eyeConditionDiv');
                            if (eyeConditionDiv) {
                                eyeConditionDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });

                        // จัดการ checkbox จมูก
                        document.querySelector('input[name="nose"][value="มีน้ำมูก"]')?.addEventListener('change', function () {
                            const noseConditionDiv = document.getElementById('noseConditionDiv');
                            if (noseConditionDiv) {
                                noseConditionDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });

                        // จัดการ checkbox ฟันผุ
                        document.querySelector('input[name="teeth"][value="ฟันผุ"]')?.addEventListener('change', function () {
                            const teethCountDiv = document.getElementById('teethCountDiv');
                            if (teethCountDiv) {
                                teethCountDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });

                        // จัดการ checkbox ผิวหนัง
                        document.querySelector('input[name="skin"][value="มีแผล"]')?.addEventListener('change', function () {
                            const skinWoundDiv = document.getElementById('skinWoundDiv');
                            if (skinWoundDiv) {
                                skinWoundDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });
                        document.querySelector('input[name="skin"][value="มีผื่น"]')?.addEventListener('change', function () {
                            const skinRashDiv = document.getElementById('skinRashDiv');
                            if (skinRashDiv) {
                                skinRashDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });

                        // จัดการ checkbox อาการผิดปกติ
                        document.querySelector('input[name="symptoms"][value="มีไข้"]')?.addEventListener('change', function () {
                            const feverDiv = document.getElementById('feverDiv');
                            if (feverDiv) {
                                feverDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });
                        document.querySelector('input[name="symptoms"][value="ไอ"]')?.addEventListener('change', function () {
                            const coughDiv = document.getElementById('coughDiv');
                            if (coughDiv) {
                                coughDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });

                        // จัดการ checkbox การใช้ยา
                        document.querySelector('input[name="medicine"][value="มียา"]')?.addEventListener('change', function () {
                            const medicineDetailDiv = document.getElementById('medicineDetailDiv');
                            if (medicineDetailDiv) {
                                medicineDetailDiv.style.display = this.checked ? 'block' : 'none';
                            }
                        });

                        // เช็คสถานะ checkbox เริ่มต้นและแสดง/ซ่อน div ที่เกี่ยวข้อง
                        const checkInitialState = (checkboxSelector, divId) => {
                            const checkbox = document.querySelector(checkboxSelector);
                            const div = document.getElementById(divId);
                            if (checkbox && div && checkbox.checked) {
                                div.style.display = 'block';
                            }
                        };

                        // เช็คสถานะเริ่มต้นของทุก checkbox
                        checkInitialState('input[name="eye"][value="มีขี้ตา"]', 'eyeConditionDiv');
                        checkInitialState('input[name="nose"][value="มีน้ำมูก"]', 'noseConditionDiv');
                        checkInitialState('input[name="teeth"][value="ฟันผุ"]', 'teethCountDiv');
                        checkInitialState('input[name="skin"][value="มีแผล"]', 'skinWoundDiv');
                        checkInitialState('input[name="skin"][value="มีผื่น"]', 'skinRashDiv');
                        checkInitialState('input[name="symptoms"][value="มีไข้"]', 'feverDiv');
                        checkInitialState('input[name="symptoms"][value="ไอ"]', 'coughDiv');
                        checkInitialState('input[name="medicine"][value="มียา"]', 'medicineDetailDiv');
                    },
                    preConfirm: () => {
                        // เก็บข้อมูลจากฟอร์ม
                        const form = document.getElementById('editHealthForm');
                        const formData = new FormData(form);
                        const data = {
                            id: formData.get('id'),
                            student_id: formData.get('student_id'),
                            prefix_th: formData.get('prefix_th'),
                            first_name_th: formData.get('first_name_th'),
                            last_name_th: formData.get('last_name_th'),
                            child_group: formData.get('child_group'),
                            classroom: formData.get('classroom'),
                            teacher_signature: formData.get('teacher_signature'),

                            // เก็บข้อมูล checkbox fields
                            hair: getCheckboxValues('hair'),
                            eye: getCheckboxValues('eye'),
                            mouth: getCheckboxValues('mouth'),
                            teeth: getCheckboxValues('teeth'),
                            ears: getCheckboxValues('ears'),
                            nose: getCheckboxValues('nose'),
                            nails: getCheckboxValues('nails'),
                            skin: getCheckboxValues('skin'),
                            hands_feet: getCheckboxValues('hands_feet'),
                            arms_legs: getCheckboxValues('arms_legs'),
                            body: getCheckboxValues('body'),
                            symptoms: getCheckboxValues('symptoms'),
                            medicine: getCheckboxValues('medicine'),

                            // เก็บข้อมูล condition และ detail
                            eye_condition: formData.get('eye_condition'),
                            nose_condition: formData.get('nose_condition'),
                            teeth_count: formData.get('teeth_count'),
                            fever_temp: formData.get('fever_temp'),
                            cough_type: formData.get('cough_type'),
                            skin_wound_detail: formData.get('skin_wound_detail'),
                            skin_rash_detail: formData.get('skin_rash_detail'),
                            medicine_detail: formData.get('medicine_detail'),

                            // เก็บข้อมูล reason
                            hair_reason: formData.get('hair_reason'),
                            eye_reason: formData.get('eye_reason'),
                            nose_reason: formData.get('nose_reason'),
                            symptoms_reason: formData.get('symptoms_reason'),
                            medicine_reason: formData.get('medicine_reason'),
                            illness_reason: formData.get('illness_reason'),
                            accident_reason: formData.get('accident_reason'),
                            teacher_note: formData.get('teacher_note')
                        };

                        // ส่งข้อมูลไปอัพเดท
                        return fetch('../include/process/update_health_record.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(data)
                        })
                            .then(response => response.json())
                            .then(result => {
                                if (result.status === 'success') {
                                    return result;
                                }
                                throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                            });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกข้อมูลสำเร็จ',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            loadResults(); // โหลดข้อมูลใหม่
                        });
                    }
                }).catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message,
                        confirmButtonText: 'ตกลง'
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถโหลดข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    // เพิ่มฟังก์ชันลบข้อมูล
    function deleteRecord(id) {
        console.log('Deleting record:', id); // เพิ่มบรรทัดนี้

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
                console.log('User confirmed deletion'); // เพิ่มบรรทัดนี้

                fetch('../include/process/delete_health_record.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                    .then(response => {
                        console.log('Response:', response); // เพิ่มบรรทัดนี้
                        return response.json();
                    })
                    .then(result => {
                        console.log('Result:', result); // เพิ่มบรรทัดนี้
                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบข้อมูลสำเร็จ',
                                text: 'ข้อมูลได้ถูกลบเรียบร้อยแล้ว',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                loadResults(); // โหลดข้อมูลใหม่
                            });
                        } else {
                            throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error); // เพิ่มบรรทัดนี้
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: error.message,
                            confirmButtonText: 'ตกลง'
                        });
                    });
            }
        });
    }

</script>

<script>
    // แก้ไขฟังก์ชัน addNewRecord
    function addNewRecord(studentId) {
        fetch(`../include/function/get_student_info.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                // เข้าถึงข้อมูลนักเรียนจาก object student
                const student = data.student;
                console.log('Student data:', student);

                const modalContent = `
                    <form id="healthCheckForm" class="needs-validation" novalidate>
                        <input type="hidden" name="student_id" value="${student.studentid}">
                        <input type="hidden" name="prefix_th" value="${student.prefix_th}">
                        <input type="hidden" name="first_name_th" value="${student.firstname_th}">
                        <input type="hidden" name="last_name_th" value="${student.lastname_th}">
                        <input type="hidden" name="child_group" value="${student.child_group}">
                        <input type="hidden" name="classroom" value="${student.classroom}">
                        
                        <!-- ข้อมูลนักเรียน -->
                        <div class="student-info mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>รหัสนักเรียน:</strong> ${student.studentid}</p>
                                    <p><strong>ชื่อ-นามสกุล:</strong> ${student.prefix_th} ${student.firstname_th} ${student.lastname_th}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>ชื่อเล่น:</strong> ${student.nickname || '-'}</p>
                                    <p><strong>ห้องเรียน:</strong> ${student.classroom || '-'}</p>
                                </div>
                            </div>
                        </div>

                        <!-- ส่วนที่เหลือของฟอร์มยังคงเหมือนเดิม -->
                        <div class="health-check-form">
                            <table class="table table-bordered">
                                <tbody>
                                    <!-- ผมศีรษะ -->
                                    <tr>
                                        <th style="width: 200px;">ผมศีรษะ</th>
                                        <td class="hair">
                                            <div><input type="checkbox" name="hair[]" value="สะอาด"> สะอาด</div>
                                            <div><input type="checkbox" name="hair[]" value="ผมยาว"> ผมยาวควรตัด</div>
                                            <div><input type="checkbox" name="hair[]" value="ไม่สะอาด"> ไม่สะอาด</div>
                                            <div><input type="checkbox" name="hair[]" value="มีเหา"> มีเหา</div>
                                            <div>อื่นๆ:<textarea name="hair_reason" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                                        </td>
                                    </tr>

                                    <!-- ตา -->
                                    <tr>
                                        <th>ตา</th>
                                        <td class="eye">
                                            <div><input type="checkbox" name="eye[]" value="ปกติ"> ปกติ</div>
                                            <div><input type="checkbox" name="eye[]" value="ตาแดง"> ตาแดง</div>
                                            <div>
                                                <input type="checkbox" id="eyeCheckbox" name="eye[]" value="มีขี้ตา" 
                                                    onchange="toggleConditionElement('eye')">
                                                <label for="eyeCheckbox">มีขี้ตา</label>
                                            </div>
                                            <div id="eyeDiv" class="eye-condition-div" style="display: none;">
                                                <label for="eyeCondition">เลือกอาการ</label>
                                                <select id="eyeCondition" name="eye_condition">
                                                    <option value="ขวาปกติ">ขวาปกติ</option>
                                                    <option value="เหลือง/เขียว">เหลือง/เขียว</option>
                                                </select>
                                            </div>
                                            <div>อื่นๆ: <textarea name="eye_reason" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                                        </td>
                                    </tr>

                                    <!-- ช่องปากและคอ -->
                                    <tr>
                                        <th>ช่องปากและคอ</th>
                                        <td class="mouth">
                                            <div><input type="checkbox" name="mouth[]" value="สะอาด"> สะอาด</div>
                                            <div><input type="checkbox" name="mouth[]" value="มีกลิ่นปาก"> มีกลิ่นปาก</div>
                                            <div><input type="checkbox" name="mouth[]" value="มีแผลในปาก"> มีแผลในปาก</div>
                                            <div><input type="checkbox" name="mouth[]" value="มีตุ่มในปาก"> มีตุ่มในปาก</div>
                                        </td>
                                    </tr>

                                    <!-- ฟัน -->
                                    <tr>
                                        <th>ฟัน</th>
                                        <td class="teeth">
                                            <div><input type="checkbox" name="teeth[]" value="สะอาด"> สะอาด</div>
                                            <div><input type="checkbox" name="teeth[]" value="มีคราบนม/ไม่สะอาด"> มีคราบนม/ไม่สะอาด</div>
                                            <div>
                                                <input type="checkbox" id="teethCheckbox" name="teeth[]" value="ฟันผุ" 
                                                    onchange="toggleConditionElement('teeth')">
                                                <label for="teethCheckbox">ฟันผุ</label>
                                                <input type="number" id="teethInput" name="teeth_count" 
                                                    placeholder="ระบุจำนวนซี่" min="1" max="32" disabled> ซี่
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- หู -->
                                    <tr>
                                        <th>หู</th>
                                        <td class="ears">
                                            <div><input type="checkbox" name="ears[]" value="สะอาด"> สะอาด</div>
                                            <div><input type="checkbox" name="ears[]" value="ไม่สะอาด"> ไม่สะอาด</div>
                                            <div><input type="checkbox" name="ears[]" value="มีขี้หู"> มีขี้หู</div>
                                        </td>
                                    </tr>

                                    <!-- จมูก -->
                                    <tr>
                                        <th>จมูก</th>
                                        <td class="nose">
                                            <div><input type="checkbox" name="nose[]" value="สะอาด"> สะอาด</div>
                                            <div>
                                                <input type="checkbox" id="noseCheckbox" name="nose[]" value="มีน้ำมูก" 
                                                    onchange="toggleConditionElement('nose')">
                                                <label for="noseCheckbox">มีน้ำมูก</label>
                                            </div>
                                            <div id="noseDiv" class="nose-condition-div" style="display: none;">
                                                <label for="noseCondition">เลือกอาการ</label>
                                                <select id="noseCondition" name="nose_condition">
                                                    <option value="ใส">ใส</option>
                                                    <option value="เหลือง">เหลือง</option>
                                                    <option value="เขียว">เขียว</option>
                                                </select>
                                            </div>
                                            <div>อื่นๆ: <textarea name="nose_reason" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                                        </td>
                                    </tr>

                                    <!-- เล็บมือ -->
                                    <tr>
                                        <th>เล็บมือ</th>
                                        <td class="nails">
                                            <div><input type="checkbox" name="nails[]" value="สะอาด"> สะอาด</div>
                                            <div><input type="checkbox" name="nails[]" value="ไม่สะอาด"> ไม่สะอาด/เล็บสกปรก</div>
                                            <div><input type="checkbox" name="nails[]" value="เล็บยาว"> เล็บยาว</div>
                                        </td>
                                    </tr>

                                    <!-- ผิวหนัง -->
                                    <tr>
                                        <th>ผิวหนัง</th>
                                        <td class="skin">
                                            <div>
                                                <input type="checkbox" name="skin[]" value="สะอาด">
                                                <label>สะอาด</label>
                                            </div>
                                            <div>
                                                <input type="checkbox" id="skinWoundCheckbox" name="skin[]" value="มีแผล" 
                                                    onchange="toggleConditionElement('skinWound')">
                                                <label for="skinWoundCheckbox">มีแผล</label>
                                                <div id="skinWoundDiv" style="display: none;">
                                                    รายละเอียด: 
                                                    <textarea name="skin_wound_detail" id="skinWoundInput" rows="1" class="form-control" 
                                                        placeholder="ระบุเพิ่มเติม"></textarea>
                                                </div>
                                            </div>
                                            <div>
                                                <input type="checkbox" id="skinRashCheckbox" name="skin[]" value="มีผื่น" 
                                                    onchange="toggleConditionElement('skinRash')">
                                                <label for="skinRashCheckbox">มีผื่น</label>
                                                <div id="skinRashDiv" style="display: none;">
                                                    รายละเอียด: 
                                                    <textarea name="skin_rash_detail" id="skinRashInput" rows="1" class="form-control" 
                                                        placeholder="ระบุเพิ่มเติม"></textarea>
                                                </div>
                                            </div>
                                            <div>
                                                <input type="checkbox" name="skin[]" value="มีขี้ไคล">
                                                <label>มีขี้ไคล</label>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- ฝ่ามือและฝ่าเท้า -->
                                    <tr>
                                        <th>ฝ่ามือและฝ่าเท้า</th>
                                        <td class="hands_feet">
                                            <div><input type="checkbox" name="hands_feet[]" value="ปกติ"> ปกติ</div>
                                            <div><input type="checkbox" name="hands_feet[]" value="จุดหรือผื่น"> จุดหรือผื่น</div>
                                            <div><input type="checkbox" name="hands_feet[]" value="ตุ่มใส"> ตุ่มใส</div>
                                            <div><input type="checkbox" name="hands_feet[]" value="ตุ่มหนอง"> ตุ่มหนอง</div>
                                        </td>
                                    </tr>

                                    <!-- แขนและขา -->
                                    <tr>
                                        <th>แขนและขา</th>
                                        <td class="arms_legs">
                                            <div><input type="checkbox" name="arms_legs[]" value="ปกติ"> ปกติ</div>
                                            <div><input type="checkbox" name="arms_legs[]" value="จุดหรือผื่น"> จุดหรือผื่น</div>
                                            <div><input type="checkbox" name="arms_legs[]" value="ตุ่มใส"> ตุ่มใส</div>
                                            <div><input type="checkbox" name="arms_legs[]" value="ตุ่มหนอง"> ตุ่มหนอง</div>
                                        </td>
                                    </tr>

                                    <!-- ลำตัวและหลัง -->
                                    <tr>
                                        <th>ลำตัวและหลัง</th>
                                        <td class="body">
                                            <div><input type="checkbox" name="body[]" value="ปกติ"> ปกติ</div>
                                            <div><input type="checkbox" name="body[]" value="จุดหรือผื่น"> จุดหรือผื่น</div>
                                            <div><input type="checkbox" name="body[]" value="ตุ่มใส"> ตุ่มใส</div>
                                            <div><input type="checkbox" name="body[]" value="ตุ่มหนอง"> ตุ่มหนอง</div>
                                        </td>
                                    </tr>

                                    <!-- อาการผิดปกติ -->
                                    <tr>
                                        <th>อาการผิดปกติ</th>
                                        <td class="symptoms">
                                            <div>
                                                <input type="checkbox" name="symptoms[]" value="ไม่มี"> ไม่มี
                                            </div>
                                            <div>
                                                <input type="checkbox" id="feverCheckbox" name="symptoms[]" value="มีไข้" 
                                                    onchange="toggleConditionElement('fever')">
                                                <label for="feverCheckbox">มีไข้</label>
                                                <input type="number" id="feverInput" name="fever_temp" 
                                                    placeholder="ระบุอุณหภูมิ" min="35" max="42" step="0.1" disabled> องศา
                                            </div>
                                            <div>
                                                <input type="checkbox" id="coughCheckbox" name="symptoms[]" value="ไอ" 
                                                    onchange="toggleConditionElement('cough')">
                                                <label for="coughCheckbox">ไอ</label>
                                            </div>
                                            <div id="coughDiv" class="cough-condition-div" style="display: none;">
                                                <label for="coughCondition">เลือกอาการ</label>
                                                <select id="coughCondition" name="cough_type">
                                                    <option value="ไอแห้ง">ไอแห้ง</option>
                                                    <option value="มีเสมหะ">มีเสมหะ</option>
                                                </select>
                                            </div>
                                            <div>อื่นๆ: <textarea name="symptoms_reason" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                                        </td>
                                    </tr>

                                    <!-- มียา -->
                                    <tr>
                                        <th>มียา</th>
                                        <td class="medicine">
                                            <div>
                                                <input type="checkbox" name="medicine[]" value="ไม่มี" id="medicineNoneCheckbox">
                                                <label for="medicineNoneCheckbox">ไม่มี</label>
                                            </div>
                                            <div>
                                                <input type="checkbox" name="medicine[]" value="มียา" id="medicineHaveCheckbox" 
                                                    onchange="toggleConditionElement('medicineHave')">
                                                <label for="medicineHaveCheckbox">มียา</label>
                                                <div id="medicineHaveDiv" style="display: none;">
                                                    รายละเอียด: 
                                                    <textarea name="medicine_detail" id="medicineHaveInput" rows="1" class="form-control" 
                                                        placeholder="ระบุเพิ่มเติม"></textarea>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- การเจ็บป่วยอื่นๆ -->
                                    <tr>
                                        <th>การเจ็บป่วยอื่นๆ</th>
                                        <td class="illness_reason">
                                            <div>
                                                อื่นๆ: 
                                                <textarea name="illness_reason" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- การเกิดอุบัติเหตุ/แมลงกัดต่อย -->
                                    <tr>
                                        <th>การเกิดอุบัติเหตุ/แมลงกัดต่อย</th>
                                        <td class="accident_reason">
                                            <div>
                                                อื่นๆ: 
                                                <textarea name="accident_reason" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- บันทึกของคุณครูพี่เลี้ยง -->
                                    <tr>
                                        <th>บันทึกของคุณครูพี่เลี้ยง</th>
                                        <td class="teacher_note">
                                            <div>
                                                อื่นๆ: 
                                                <textarea name="teacher_note" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- ลงชื่อคุณครู/ครูพี่เลี้ยง -->
                                    <tr>
                                        <th>ลงชื่อคุณครู/ครูพี่เลี้ยง</th>
                                        <td class="teacher_signature">
                                            <div class="form-group">
                                                <input type="text" 
                                                    class="form-control" 
                                                    name="teacher_signature" 
                                                    required 
                                                    placeholder="กรุณาลงชื่อครู"
                                                    oninvalid="this.setCustomValidity('กรุณาลงชื่อครูก่อนบันทึกข้อมูล')"
                                                    oninput="this.setCustomValidity('')">
                                                <div class="invalid-feedback">
                                                    กรุณาลงชื่อครูก่อนบันทึกข้อมูล
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </form>
                `;

                // แสดง Modal
                Swal.fire({
                    title: 'เพิ่มข้อมูลการตรวจร่างกาย',
                    html: modalContent,
                    width: '900px',
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    didOpen: () => {
                        setupFormEventListeners();
                    },
                    preConfirm: () => {
                        // ตรวจสอบการลงชื่อครูก่อนบันทึก
                        const form = document.getElementById('healthCheckForm');
                        const teacherSignature = form.querySelector('[name="teacher_signature"]').value.trim();

                        if (!teacherSignature) {
                            Swal.showValidationMessage('กรุณาลงชื่อครูก่อนบันทึกข้อมูล');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveHealthCheck();
                    }
                });
            });
    }

    // ฟังก์ชันตั้งค่า Event Listeners
    function setupFormEventListeners() {
        // ตั้งค่า event listeners สำหรับ checkboxes ที่มีรายละเอียดเพิ่มเติม
        const conditions = ['eye', 'teeth', 'nose', 'skinWound', 'skinRash', 'fever', 'cough', 'medicineHave'];
        conditions.forEach(condition => {
            const checkbox = document.getElementById(`${condition}Checkbox`);
            if (checkbox) {
                checkbox.addEventListener('change', () => toggleConditionElement(condition));
            }
        });

        // ตั้งค่า event listeners สำหรับ exclusive checkboxes
        setupExclusiveCheckboxes();
    }

    function toggleConditionElement(condition) {
    const checkbox = document.getElementById(`${condition}Checkbox`);
    const div = document.getElementById(`${condition}Div`);
    const input = document.getElementById(`${condition}Input`);
    const select = document.getElementById(`${condition}Condition`);

    if (div) div.style.display = checkbox.checked ? 'block' : 'none';
    if (input) {
        input.disabled = !checkbox.checked;
        if (!checkbox.checked) {
            input.value = ''; // เคลียร์ค่าหาก checkbox ไม่ถูกเลือก
        }
    }
    if (select) {
        select.disabled = !checkbox.checked;
        if (!checkbox.checked) {
            select.value = ''; // เคลียร์ค่าหาก checkbox ไม่ถูกเลือก
        }
    }
}

    // ฟังก์ชันสำหรับจัดการ exclusive checkboxes
    function setupExclusiveCheckboxes() {
        const exclusiveGroups = ['medicine'];
        exclusiveGroups.forEach(group => {
            const checkboxes = document.querySelectorAll(`input[name="${group}[]"]`);
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        checkboxes.forEach(cb => {
                            if (cb !== e.target) cb.checked = false;
                        });
                    }
                });
            });
        });
    }

    // ฟังก์ชันสำหรับจัดการการส่งฟอร์ม
    function saveHealthCheck() {
    const form = document.getElementById('healthCheckForm');
    const formData = collectFormData(form);

    // ตรวจสอบข้อมูลก่อนส่ง
    if (formData.teeth_count === "") formData.teeth_count = null;
    if (formData.fever_temp === "") formData.fever_temp = null;

    // ส่งข้อมูลไปยัง API
    fetch('../include/process/save_health_check.php', {
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
                icon: 'success',
                title: 'บันทึกข้อมูลสำเร็จ',
                text: data.message,
                confirmButtonText: 'ตกลง'
            }).then(() => {
                loadResults(); // โหลดข้อมูลใหม่
            });
        } else {
            throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: error.message || 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
            confirmButtonText: 'ตกลง'
        });
    });
}

function getCheckboxSaveValues(groupName) {
    const checkboxes = document.querySelectorAll(`input[name="${groupName}[]"]`);
    const checked = [];
    const unchecked = [];

    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checked.push(checkbox.value);
        } else {
            unchecked.push(checkbox.value);
        }
    });

    return { checked, unchecked };
}
    // ฟังก์ชันรวบรวมข้อมูลจากฟอร์ม
function collectFormData(form) {
    const formData = new FormData(form);
    // ดึงค่าวันที่จาก date picker ที่อยู่ในหน้าหลัก
    const selectedDate = document.getElementById('date').value;
    
    const data = {
        student_id: formData.get('student_id'),
        prefix_th: formData.get('prefix_th'),
        first_name_th: formData.get('first_name_th'),
        last_name_th: formData.get('last_name_th'),
        child_group: formData.get('child_group'),
        classroom: formData.get('classroom'),
        teacher_signature: formData.get('teacher_signature'),
        created_at: selectedDate, // ใช้วันที่ที่เลือกจาก date picker

        // ส่วนที่เหลือยังคงเหมือนเดิม...
        hair: getCheckboxSaveValues('hair'),
        eye: getCheckboxSaveValues('eye'),
        mouth: getCheckboxSaveValues('mouth'),
        teeth: getCheckboxSaveValues('teeth'),
        ears: getCheckboxSaveValues('ears'),
        nose: getCheckboxSaveValues('nose'),
        nails: getCheckboxSaveValues('nails'),
        skin: getCheckboxSaveValues('skin'),
        hands_feet: getCheckboxSaveValues('hands_feet'),
        arms_legs: getCheckboxSaveValues('arms_legs'),
        body: getCheckboxSaveValues('body'),
        symptoms: getCheckboxSaveValues('symptoms'),
        medicine: getCheckboxSaveValues('medicine'),

        // เก็บข้อมูล condition และ detail
        eye_condition: formData.get('eye_condition'),
        nose_condition: formData.get('nose_condition'),
        teeth_count: formData.get('teeth_count') || null, // แปลงค่าว่างเป็น null
        fever_temp: formData.get('fever_temp') || null,  // แปลงค่าว่างเป็น null
        cough_type: formData.get('cough_type'),
        skin_wound_detail: formData.get('skin_wound_detail'),
        skin_rash_detail: formData.get('skin_rash_detail'),
        medicine_detail: formData.get('medicine_detail'),

        // เก็บข้อมูล reason
        hair_reason: formData.get('hair_reason'),
        eye_reason: formData.get('eye_reason'),
        nose_reason: formData.get('nose_reason'),
        symptoms_reason: formData.get('symptoms_reason'),
        medicine_reason: formData.get('medicine_reason'),
        illness_reason: formData.get('illness_reason'),
        accident_reason: formData.get('accident_reason'),
        teacher_note: formData.get('teacher_note')
    };
    // ส่วนที่เหลือของฟังก์ชันยังคงเหมือนเดิม...
    return data;
}



    // ฟังก์ชันสำหรับจัดการการเลือกหลายรายการ
    function handleMultipleSelection(checkbox, group, index) {
        const container = checkbox.closest(`.${group}`);
        const checkboxes = container.querySelectorAll(`input[name^="${group}_${index}"]`);
        
        if (checkbox.value === 'ไม่มี' || checkbox.value === 'สะอาด' || checkbox.value === 'ปกติ') {
            if (checkbox.checked) {
                checkboxes.forEach(cb => {
                    if (cb !== checkbox) {
                        cb.checked = false;
                    }
                });
            }
        } else {
            const normalCheckbox = Array.from(checkboxes).find(cb => 
                cb.value === 'ไม่มี' || cb.value === 'สะอาด' || cb.value === 'ปกติ'
            );
            if (normalCheckbox) {
                normalCheckbox.checked = false;
            }
        }
    }
</script>

<!-- เพิ่ม Script สำหรับ Export -->
<script>
function exportToExcel() {
    Swal.fire({
        title: 'Export ข้อมูลการตรวจร่างกาย',
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
            const exportUrl = `../include/process/export_checklist.php?${params.toString()}`;
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