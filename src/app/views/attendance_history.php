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
    .attendance-container {
    padding: 2rem;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}

</style>

<main class="main-content">
<div class="attendance-container">
    <h2 class="mb-4">ประวัติการมาเรียนของเด็ก</h2>

    <!-- ฟอร์มค้นหา -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="searchForm" method="GET" class="row g-3">
                <!-- เลือกกลุ่มเรียน -->
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

                <!-- เลือกห้องเรียน -->
                <div class="col-md-3">
                    <label for="classroom" class="form-label">ห้องเรียน</label>
                    <select name="classroom" id="classroom" class="form-select">
                        <option value="">-- เลือกห้องเรียน --</option>
                    </select>
                </div>

                <!-- เลือกวันที่ -->
                <div class="col-md-3">
                    <label for="date" class="form-label">วันที่</label>
                    <input type="date" class="form-control" id="date" name="date"
                        value="<?php echo htmlspecialchars(isset($_GET['date']) ? $_GET['date'] : date('Y-m-d')); ?>">
                </div>

                <!-- ค้นหาชื่อ -->
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

</div>
</main>

<script>
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
                    classroomSelect.value = '<?php echo htmlspecialchars($_GET['classroom']); ?>';
                <?php endif; ?>
            })
            .catch(error => console.error('Error:', error));
    }

    // รีเซ็ตฟอร์ม
    function resetForm() {
        // รีเซ็ตฟอร์ม
        document.getElementById('searchForm').reset();

        // รีเซ็ตค่าห้องเรียน
        document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';

        // รีเซ็ตค่ากลุ่มเรียน
        document.getElementById('child_group').value = '';

        // รีเซ็ตค่าวันที่เป็นวันปัจจุบัน
        document.getElementById('date').value = new Date().toISOString().split('T')[0];

        // ล้างค่าช่องค้นหา
        document.getElementById('search').value = '';

        // แสดงข้อความแนะนำ
        const table = document.getElementById('resultTable');
        table.innerHTML = '<div class="alert alert-info">กรุณาเลือกกลุ่มเรียน, ห้องเรียน หรือค้นหาจากชื่อนักเรียน แล้วกดปุ่มค้นหา</div>';

        // ล้าง URL parameters
        window.history.replaceState({}, '', window.location.pathname);
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

        // สร้าง URL parameters
        const params = new URLSearchParams(formData);

        fetch(`../include/function/get_attendance_history.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                const table = document.getElementById('resultTable');
                if (data.length === 0) {
                    table.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูล</div>';
                    return;
                }

                // แสดงสรุปจำนวนรวม
                let html = showStatusSummary(data);

                // จัดกลุ่มข้อมูลตามกลุ่มเรียนและห้องเรียน
                const groupedData = groupStudentsByClass(data);

                // วนลูปแสดงผลแต่ละกลุ่ม
                Object.entries(groupedData).forEach(([groupKey, group]) => {
                    // แสดงหัวข้อกลุ่ม
                    html += `
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">กลุ่มเรียน: ${group.child_group} | ห้องเรียน: ${group.classroom}</h5>
                            </div>
                            <div class="card-body">
                                ${showStatusSummary(group.students)} <!-- แสดงสรุปจำนวนของแต่ละกลุ่ม -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>รหัสนักเรียน</th>
                                                <th>ชื่อ-นามสกุล</th>
                                                <th>ชื่อเล่น</th>
                                                <th>สถานะ</th>
                                                <th>เวลามา</th>
                                                <th>สถานะกลับบ้าน</th>
                                                <th>เวลากลับ</th>
                                                <th>จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    // แสดงข้อมูลนักเรียนในกลุ่ม
                    group.students.forEach(student => {
                        const statusClass = student.status === 'absent' ? 'table-danger' :
                            student.status === 'present' ? 'table-success' :
                                student.status === 'leave' ? 'table-warning' : '';

                        // แก้ไขการจัดการกับเวลา
                        const checkDate = student.check_date ? formatTime(student.check_date) : '-';
                        const checkOutTime = student.check_out_time ? formatTime(student.check_out_time) : '-';

                        html += `
                            <tr class="${statusClass}">
                                <td>${student.student_id}</td>
                                <td>${student.prefix_th} ${student.firstname_th} ${student.lastname_th}</td>
                                <td>${student.nickname}</td>
                                <td>
                                    <span class="badge ${getStatusBadgeClass(student.status)}">
                                        ${student.status ? getStatusText(student.status) : 'ยังไม่บันทึก'}
                                    </span>
                                </td>
                                <td>${checkDate}</td>
                                <td>
                                    <span class="badge ${student.status_checkout === 'checked_out' ? 'bg-success' : 'bg-secondary'}">
                                        ${student.status_checkout === 'checked_out' ? 'กลับแล้ว' : 'ยังไม่กลับ'}
                                    </span>
                                </td>
                                <td>${checkOutTime}</td>
                                <td>
                                    <?php if ($is_admin || $is_teacher): ?>
                                        ${renderActionButtons(student)}
                                    <?php else: ?>
                                        <span class="text-muted">ดูข้อมูลเท่านั้น</span>
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

    // เพิ่มฟังก์ชันใหม่สำหรับจัดการกับเวลา
    function formatTime(timeString) {
        if (!timeString) return '-';
        
        // ถ้าเป็นรูปแบบ HH:mm:ss
        if (timeString.match(/^\d{2}:\d{2}:\d{2}$/)) {
            return timeString.substring(0, 5) + ' น.';
        }
        
        // ถ้าเป็น timestamp หรือวันที่เต็ม
        const date = new Date(timeString);
        if (isNaN(date.getTime())) return '-';
        
        return date.toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit'
        }) + ' น.';
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

    // เพิ่ม event listener สำหรับการ submit form
    document.getElementById('searchForm').addEventListener('submit', function (e) {
        e.preventDefault(); // ป้องกันการ refresh หน้า
        loadResults();
    });

    // ฟังก์ชันเพิ่มข้อมูลใหม่
    function addAttendance(studentData) {
        const currentDate = document.getElementById('date').value;
        
        // ดึงเวลาปัจจุบัน
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const currentTime = `${hours}:${minutes}:${seconds}`;

        showAttendanceForm({
            id: null,
            student_id: studentData.student_id,
            prefix_th: studentData.prefix_th,
            firstname_th: studentData.firstname_th,
            lastname_th: studentData.lastname_th,
            status: 'present',
            check_date: currentTime, // ใช้เวลาปัจจุบันในรูปแบบ HH:mm:ss
            check_out_time: '',
            leave_note: '',
            attendance_date: currentDate
        }, true);
    }

    // ฟังก์ชันดึงวันที่ปัจจุบัน
    function getCurrentDate() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // ฟังก์ชันแก้ไขข้อมูล
    function editAttendance(id) {
        const currentDate = document.getElementById('date').value;
        fetch(`../include/function/get_attendance_detail.php?id=${id}&date=${currentDate}`)
            .then(response => response.json())
            .then(response => {
                if (response.status !== 'success') {
                    throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                }

                // แปลงรูปแบบเวลาให้ถูกต้อง
                const data = response.data;
                if (data.check_date) {
                    const date = new Date(data.check_date);
                    data.formatted_check_date = date.toTimeString().slice(0, 8); // แปลงเป็น HH:mm:ss
                }
                
                if (data.check_out_time) {
                    data.formatted_check_out_time = data.check_out_time.slice(0, 8); // แปลงเป็น HH:mm:ss
                }

                showAttendanceForm({
                    ...data,
                    attendance_date: currentDate
                }, false);
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message,
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    // ฟังก์ชันแสดงฟอร์ม
    function showAttendanceForm(data, isNewRecord) {
        // ดึงเวลาปัจจุบันสำหรับกรณีเพิ่มข้อมูลใหม่
        const now = new Date();
        const currentTime = isNewRecord ? 
            `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}` : 
            '';

        // จัดการกับเวลาที่มีอยู่
        let checkTime = '';
        let checkOutTime = '';

        if (data.formatted_check_date) {
            checkTime = data.formatted_check_date.substring(0, 5);
        } else if (data.check_date) {
            const date = new Date(data.check_date);
            if (!isNaN(date.getTime())) {
                checkTime = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
            }
        }

        if (data.formatted_check_out_time) {
            checkOutTime = data.formatted_check_out_time.substring(0, 5);
        } else if (data.check_out_time) {
            if (data.check_out_time.includes(':')) {
                checkOutTime = data.check_out_time.substring(0, 5);
            } else {
                const date = new Date(data.check_out_time);
                if (!isNaN(date.getTime())) {
                    checkOutTime = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
                }
            }
        }

        Swal.fire({
            title: isNewRecord ? 'บันทึกการเข้าเรียน' : 'แก้ไขข้อมูลการเข้าเรียน',
            html: `
                <form id="attendanceForm">
                    <input type="hidden" name="id" value="${data.id || ''}">
                    <input type="hidden" name="student_id" value="${data.student_id || ''}">
                    <input type="hidden" name="attendance_date" value="${data.attendance_date}">
                    <div class="mb-3">
                        <label class="form-label">รหัสนักเรียน</label>
                        <input type="text" class="form-control" value="${data.student_id || ''}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" value="${data.prefix_th || ''} ${data.firstname_th || ''} ${data.lastname_th || ''}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วันที่</label>
                        <input type="date" class="form-control" value="${data.attendance_date}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สถานะการมาเรียน</label>
                        <select class="form-select" name="status" id="attendanceStatus">
                            <option value="present" ${data.status === 'present' ? 'selected' : ''}>มาเรียน</option>
                            <option value="absent" ${data.status === 'absent' ? 'selected' : ''}>ไม่มาเรียน</option>
                            <option value="leave" ${data.status === 'leave' ? 'selected' : ''}>ลา</option>
                        </select>
                    </div>
                    <div class="mb-3" id="timeDiv">
                        <label class="form-label">เวลามาเรียน</label>
                        <input type="time" class="form-control" name="check_date" 
                            value="${isNewRecord ? currentTime : (data.formatted_check_date ? data.formatted_check_date.substring(0, 5) : '')}">
                    </div>
                    <div class="mb-3" id="checkoutDiv">
                        <label class="form-label">เวลากลับ</label>
                        <input type="time" class="form-control" name="check_out_time"
                            value="${data.formatted_check_out_time ? data.formatted_check_out_time.substring(0, 5) : ''}">
                    </div>
                    <div class="mb-3" id="leaveNoteDiv" style="display:none">
                        <label class="form-label">หมายเหตุการลา</label>
                        <textarea class="form-control" name="leave_note" rows="3">${data.leave_note || ''}</textarea>
                    </div>
                </form>
            `,
            didOpen: () => {
                const attendanceStatus = document.getElementById('attendanceStatus');
                const timeDiv = document.getElementById('timeDiv');
                const checkoutDiv = document.getElementById('checkoutDiv');
                const leaveNoteDiv = document.getElementById('leaveNoteDiv');

                function updateFieldsVisibility() {
                    const status = attendanceStatus.value;
                    timeDiv.style.display = status === 'present' ? 'block' : 'none';
                    checkoutDiv.style.display = status === 'present' ? 'block' : 'none';
                    leaveNoteDiv.style.display = status === 'leave' ? 'block' : 'none';
                }

                attendanceStatus.addEventListener('change', updateFieldsVisibility);
                updateFieldsVisibility();
            },
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                const form = document.getElementById('attendanceForm');
                const formData = new FormData(form);
                const status = formData.get('status');
                
                // ตรวจสอบความถูกต้องของข้อมูล
                if (!formData.get('student_id')) {
                    Swal.showValidationMessage('กรุณาระบุรหัสนักเรียน');
                    return false;
                }

                if (status === 'present' && !formData.get('check_date')) {
                    Swal.showValidationMessage('กรุณาระบุเวลามาเรียน');
                    return false;
                }

                if (status === 'leave' && !formData.get('leave_note')) {
                    Swal.showValidationMessage('กรุณาระบุหมายเหตุการลา');
                    return false;
                }

                // แปลง FormData เป็น object ที่จะส่งไป
                const requestData = {
                    id: formData.get('id'),
                    student_id: formData.get('student_id'),
                    attendance_date: formData.get('attendance_date'),
                    status: status,
                    check_date: formData.get('check_date'),
                    check_out_time: formData.get('check_out_time'),
                    leave_note: formData.get('leave_note')
                };

                // เลือก URL ตามประเภทการทำงาน
                const url = isNewRecord ? 
                    '../include/process/save_attendance_record.php' : 
                    '../include/process/update_attendance_record.php';

                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        return result;
                    }
                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `เกิดข้อผิดพลาด: ${error.message}`
                    );
                    return false;
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: isNewRecord ? 'บันทึกข้อมูลสำเร็จ' : 'แก้ไขข้อมูลสำเร็จ',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    loadResults(); // โหลดข้อมูลใหม่
                });
            }
        });
    }

    function deleteAttendance(id) {
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
                fetch('../include/process/delete_attendance_record.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบข้อมูลสำเร็จ',
                                text: 'ข้อมูลได้ถูกลบเรียบร้อยแล้ว',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                loadResults(); // โหลดข้อมูลใหม่แทนการรีเฟรชหน้า
                            });
                        } else {
                            throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
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

    // ในส่วนของการแสดงสถานะในตาราง
    function getStatusText(status) {
        if (!status) return 'ยังไม่บันทึก';
        switch (status) {
            case 'present': return 'มาเรียน';
            case 'absent': return 'ไม่มาเรียน';
            case 'leave': return 'ลา';
            case 'late': return 'มาสาย';
            default: return status;
        }
    }

    // แก้ไขฟังก์ชัน getStatusBadgeClass
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'present':
                return 'bg-success';
            case 'absent':
                return 'bg-danger';
            case 'leave':
                return 'bg-warning';
            case 'late':
                return 'bg-warning text-dark';
            default:
                return 'bg-secondary';
        }
    }

    // เพิ่มฟังก์ชันคำนวณจำนวนแต่ละสถานะ
    function calculateStatusCounts(data) {
        const counts = {
            present: 0,
            late: 0,
            absent: 0,
            leave: 0,
            notRecorded: 0
        };

        data.forEach(student => {
            if (!student.status) {
                counts.notRecorded++;
            } else if (student.status === 'present') {
                counts.present++;
            } else if (student.status === 'late') {
                counts.late++;
            } else if (student.status === 'absent') {
                counts.absent++;
            } else if (student.status === 'leave') {
                counts.leave++;
            }
        });

        return counts;
    }

    // แก้ไขฟังก์ชัน showStatusSummary
    function showStatusSummary(data) {
        const counts = calculateStatusCounts(data);
        const total = data.length;

        return `
            <div class="alert alert-info mb-3">
                <h6 class="mb-2">สรุปการมาเรียน:</h6>
                <div class="row g-2">
                    <div class="col text-center">
                        <span class="badge bg-success d-block">มาเรียน: ${counts.present} คน</span>
                    </div>
                    <div class="col text-center">
                        <span class="badge bg-warning text-dark d-block">มาสาย: ${counts.late} คน</span>
                    </div>
                    <div class="col text-center">
                        <span class="badge bg-danger d-block">ไม่มาเรียน: ${counts.absent} คน</span>
                    </div>
                    <div class="col text-center">
                        <span class="badge bg-warning d-block">ลา: ${counts.leave} คน</span>
                    </div>
                    <div class="col text-center">
                        <span class="badge bg-secondary d-block">ยังไม่บันทึก: ${counts.notRecorded} คน</span>
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <small>จำนวนนักเรียนทั้งหมด: ${total} คน</small>
                </div>
            </div>
        `;
    }

    // แก้ไขฟังก์ชัน renderActionButtons
    function renderActionButtons(student) {
        let buttons = '';

        // ตรวจสอบว่ามีข้อมูลการเข้าเรียนหรือไม่
        if (student.id) {  // ถ้ามี id แสดงว่ามีการบันทึกข้อมูลแล้ว
            buttons = `
                <button type="button" class="btn btn-info btn-sm" onclick="viewAttendanceDetail(${student.id})">
                    <i class="fas fa-eye"></i> ดู
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="editAttendance(${student.id})">
                    <i class="fas fa-edit"></i> แก้ไข
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteAttendance(${student.id})">
                    <i class="fas fa-trash"></i> ลบ
                </button>
            `;
        } else {
            // กรณีไม่มีข้อมูลการเข้าเรียน
            buttons = `
                <button type="button" class="btn btn-primary btn-sm" 
                    onclick="addAttendance({
                        student_id: '${student.student_id}',
                        prefix_th: '${student.prefix_th}',
                        firstname_th: '${student.firstname_th}',
                        lastname_th: '${student.lastname_th}'
                    })">
                    <i class="fas fa-plus"></i> เพิ่ม
                </button>
            `;
        }

        return buttons;
    }

    // เพิ่มฟังก์ชันดูรายละเอียด
    function viewAttendanceDetail(id) {
        fetch(`../include/function/get_attendance_detail.php?id=${id}`)
            .then(response => response.json())
            .then(response => {
                if (response.status !== 'success') {
                    throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                }

                const data = response.data;
                let statusText = getStatusText(data.status);
                let statusClass = getStatusBadgeClass(data.status);

                Swal.fire({
                    title: 'รายละเอียดการเข้าเรียน',
                    html: `
                        <div class="text-start">
                            <p><strong>รหัสนักเรียน:</strong> ${data.student_id}</p>
                            <p><strong>ชื่อ-นามสกุล:</strong> ${data.prefix_th} ${data.firstname_th} ${data.lastname_th}</p>
                            <p><strong>กลุ่มเรียน:</strong> ${data.child_group}</p>
                            <p><strong>ห้องเรียน:</strong> ${data.classroom}</p>
                            <p>
                                <strong>สถานะ:</strong> 
                                <span class="badge ${statusClass}">${statusText}</span>
                            </p>
                            <p><strong>วันที่:</strong> ${new Date(data.check_date).toLocaleDateString('th-TH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })}</p>
                            <p><strong>เวลามา:</strong> ${data.check_date && new Date(data.check_date).getHours() !== 0 && new Date(data.check_date).getMinutes() !== 0 
                                    ? new Date(data.check_date).toLocaleTimeString('th-TH', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) + ' น.'
                                    : '-'}</p>
                            <p>
                                <strong>สถานะกลับบ้าน:</strong> 
                                <span class="badge ${data.status_checkout === 'checked_out' ? 'bg-success' : 'bg-secondary'}">
                                    ${data.status_checkout === 'checked_out' ? 'กลับแล้ว' : 'ยังไม่กลับ'}
                                </span>
                            </p>
                            <p><strong>เวลากลับ:</strong> ${data.check_out_time ?
                            new Date(data.check_out_time).toLocaleTimeString('th-TH', {
                                hour: '2-digit',
                                minute: '2-digit'
                            }) + ' น.' : '-'}</p>
                            ${data.status === 'leave' ? `<p><strong>หมายเหตุ:</strong> ${data.leave_note || '-'}</p>` : ''}
                            <hr>
                            <p><small><strong>บันทึกเมื่อ:</strong> ${new Date(data.created_at).toLocaleString('th-TH', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })} น.</small></p>
                            <p><small><strong>แก้ไขล่าสุด:</strong> ${new Date(data.updated_at).toLocaleString('th-TH', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })} น.</small></p>
                        </div>
                    `,
                    width: '600px',
                    confirmButtonText: 'ปิด',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message,
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    // ฟังก์ชันแสดงฟอร์มแก้ไข
    function showEditForm(student) {
        let checkDate = '';
        let checkOutTime = '';
        
        // ถ้ามีเวลาที่บันทึกไว้ ให้แปลงเป็นรูปแบบที่ input time รับได้
        if (student.check_date) {
            const date = new Date(student.check_date);
            checkDate = date.toTimeString().slice(0, 8); // แปลงเป็น HH:mm:ss
        }
        
        if (student.check_out_time) {
            checkOutTime = student.check_out_time.slice(0, 8); // แปลงเป็น HH:mm:ss
        }

        Swal.fire({
            title: 'แก้ไขข้อมูลการเข้าเรียน',
            html: `
                <div class="mb-3">
                    <label class="form-label">สถานะ</label>
                    <select class="form-select" id="edit-status">
                        <option value="present" ${student.status === 'present' ? 'selected' : ''}>มาเรียน</option>
                        <option value="absent" ${student.status === 'absent' ? 'selected' : ''}>ไม่มาเรียน</option>
                        <option value="leave" ${student.status === 'leave' ? 'selected' : ''}>ลา</option>
                        <option value="late" ${student.status === 'late' ? 'selected' : ''}>มาสาย</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">เวลาเข้าเรียน</label>
                    <input type="time" class="form-control" id="edit-check-date" step="1" value="${checkDate}">
                </div>
                <div class="mb-3">
                    <label class="form-label">เวลากลับ</label>
                    <input type="time" class="form-control" id="edit-check-out-time" step="1" value="${checkOutTime}">
                </div>
                <div class="mb-3">
                    <label class="form-label">หมายเหตุ (กรณีลา)</label>
                    <textarea class="form-control" id="edit-leave-note">${student.leave_note || ''}</textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                return {
                    id: student.id,
                    student_id: student.student_id,
                    status: document.getElementById('edit-status').value,
                    check_date: document.getElementById('edit-check-date').value,
                    check_out_time: document.getElementById('edit-check-out-time').value,
                    leave_note: document.getElementById('edit-leave-note').value,
                    attendance_date: student.check_date.split(' ')[0] // เก็บวันที่เดิมไว้
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateAttendance(result.value);
            }
        });
    }

    // อัพเดทการแสดงผลในตาราง
    function updateTableRow(student) {
        const row = document.querySelector(`tr[data-id="${student.id}"]`);
        if (row) {
            row.innerHTML = `
                <td>${student.student_id}</td>
                <td>${student.prefix_th}${student.firstname_th} ${student.lastname_th}</td>
                <td>${student.classroom}</td>
                <td>
                    <span class="badge ${getStatusBadgeClass(student.status)}">
                        ${getStatusText(student.status)}
                    </span>
                </td>
                <td>${student.check_date ? new Date(student.check_date).toLocaleTimeString() : '-'}</td>
                <td>${student.check_out_time || '-'}</td>
                <td>${student.leave_note || '-'}</td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="showEditForm(${JSON.stringify(student)})">
                        <i class="fas fa-edit"></i> แก้ไข
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteAttendance(${student.id})">
                        <i class="fas fa-trash"></i> ลบ
                    </button>
                </td>
            `;
        }
    }

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


