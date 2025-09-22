<?php
include('../../include/auth/auth.php');
checkUserRole(['admin']);
include __DIR__ . '../../partials/Header.php';
include('../../include/auth/auth_navbar.php');
include __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once '../../include/function/child_functions.php';
require_once '../../include/function/get_student_transitions.php';

// ดึงข้อมูลการเลื่อนสำเร็จการศึกษาทั้งหมด
$transitions = getStudentTransitions();

// ใช้ user_id จาก session เป็น teacher_id
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id']; // ดึงค่า user_id มาเป็น teacher_id
} else {
    die('ไม่พบข้อมูลผู้สอน. กรุณาเข้าสู่ระบบอีกครั้ง.'); // กรณีที่ไม่มี user_id ใน session
}

?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= nl2br(htmlspecialchars($_GET['error'])) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= nl2br(htmlspecialchars($_GET['success'])) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// ตรวจสอบว่ามีสถานะถูกส่งมาหรือไม่
$status = isset($_GET['status']) ? $_GET['status'] : null;
$message = isset($_GET['message']) ? urldecode($_GET['message']) : null;
?>
<?php if (!empty($status) && !empty($message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: '<?php echo $status === "success" ? "success" : "error"; ?>',
                title: '<?php echo $status === "success" ? "สำเร็จ" : "ไม่สำเร็จ"; ?>',
                text: '<?php echo $message; ?>',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                // ลบพารามิเตอร์ status และ message ออกจาก URL
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                url.searchParams.delete('message');
                history.pushState({}, '', url);
            });
        });
    </script>
<?php endif; ?>
<style>
    .status-studying {
        background: linear-gradient(135deg, #007bff, #6f42c1);
        color: white;
    }
</style>
<main class="main-content">
    <div class="container-fluid px-4">
        <h1 class="">จัดการการสำเร็จการศึกษา</h1>

        <!-- ส่วนค้นหาและกรอง -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="searchForm" class="row g-3">
                    <div class="col-md-4">
                        <label for="child_group" class="form-label">กลุ่มเรียน</label>
                        <select name="child_group" id="child_group" class="form-select" onchange="loadClassrooms()">
                            <option value="">ทั้งหมด</option>
                            <?php
                            $groups = get_childgroup();
                            foreach ($groups as $group) {
                                echo "<option value='{$group['child_group']}'>{$group['child_group']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="classroom" class="form-label">ห้องเรียน</label>
                        <select name="classroom" id="classroom" class="form-select">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_academic_year" class="form-label">ปีการศึกษาที่ต้องการเลื่อนสำเร็จการศึกษา</label>
                        <select name="filter_academic_year" id="filter_academic_year" class="form-select">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" onclick="loadResults()">ค้นหา</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">รีเซ็ต</button>

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

        <!-- Modal สำหรับเลื่อนสำเร็จการศึกษาแบบกลุ่ม -->
        <div class="modal fade" id="bulkTransitionModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">เลื่อนสำเร็จการศึกษาแบบกลุ่ม</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> จำนวนนักเรียนที่จะเลื่อนสำเร็จการศึกษา: <span id="studentCount" class="fw-bold">0</span> คน
                        </div>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-striped table-hover mb-3 text-center">
                                <thead>
                                    <tr class="table-primary">
                                        <th>ลำดับ</th>
                                        <th>รหัสนักเรียน</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>ชื่อเล่น</th>
                                        <th>กลุ่มเรียน</th>
                                        <th>ห้องเรียน</th>
                                        <th>ปีการศึกษา</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody id="StudentsList">
                                    <!-- รายชื่อนักเรียนที่เลือกจะถูกเพิ่มที่นี่ -->
                                </tbody>
                            </table>
                        </div>
                        <form id="bulkTransitionForm">
                            <div class="row mb-3 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">วันที่จบการศึกษา <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_success" name="date_success" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ประเภทการจบ <span class="text-danger">*</span></label>
                                    <select class="form-select" id="success_type" name="success_type" required>
                                        <option value="">-- เลือกประเภทการจบ --</option>
                                        <option value="สำเร็จการศึกษา">สำเร็จการศึกษาแบบปกติ</option>
                                        <option value="ย้ายไปโรงเรียนอื่น">ย้ายไปโรงเรียนอื่น</option>
                                        <option value="ลาออก">ลาออก</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="text-center" id="errorMessage"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="button" class="btn btn-primary" onclick="saveBulkTransition()">บันทึก</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal สำหรับเลื่อนสำเร็จการศึกษาแบบหลายห้องเรียน -->
        <div class="modal fade" id="multiClassTransitionModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">เลื่อนสำเร็จการศึกษาแบบหลายห้องเรียน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> เลือกปีการศึกษาที่ต้องการเลื่อนสำเร็จการศึกษาก่อน
                        </div>
                        <form id="multiClassTransitionForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">ปีการศึกษาที่ต้องการเลื่อนสำเร็จการศึกษา</label>
                                    <select class="form-select" name="multi_current_academic_year_select" id="multi_current_academic_year_select" required onchange="handleAcademicYearChange(this)">
                                        <option value="">-- เลือกปีการศึกษา --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">กลุ่มเรียนปัจจุบัน</label>
                                    <select class="form-select" name="multi_current_group" id="multi_current_group" required disabled>
                                        <option value="">-- เลือกกลุ่มเรียน --</option>
                                        <?php
                                        $groups = get_childgroup();
                                        foreach ($groups as $group) {
                                            echo "<option value='{$group['child_group']}'>{$group['child_group']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ห้องเรียนที่ต้องการเลื่อนสำเร็จการศึกษา</label>
                                    <div class="classroom-checkboxes" style="max-height: 200px; overflow-y: auto;">
                                        <div class="alert alert-warning">กรุณาเลือกปีการศึกษาและกลุ่มเรียนก่อน</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">ปีการศึกษาใหม่</label>
                                    <select class="form-select" name="bulk_new_academic_year" id="bulk_new_academic_year" required>
                                        <option value="">-- เลือกปีการศึกษา --</option>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">กลุ่มเรียนใหม่</label>
                                    <select class="form-select" name="multi_new_group" id="multi_new_group" required disabled>
                                        <option value="">-- เลือกกลุ่มเรียน --</option>
                                        <option value="เด็กกลาง">เด็กกลาง</option>
                                        <option value="เด็กโต">เด็กโต</option>
                                        <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ห้องเรียนใหม่</label>
                                    <select class="form-select" name="multi_new_classroom" id="multi_new_classroom" required disabled>
                                        <option value="">-- เลือกห้องเรียน --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">ปีการศึกษาใหม่</label>
                                    <select class="form-select" name="multi_new_academic_year" id="multi_new_academic_year" required disabled>
                                        <option value="">-- เลือกปีการศึกษา --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">วันที่มีผล</label>
                                <input type="date" class="form-control" name="multi_effective_date" required disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">หมายเหตุ</label>
                                <textarea class="form-control" name="multi_reason" rows="3" disabled></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="button" class="btn btn-primary" onclick="saveMultiClassTransition()" disabled id="saveMultiClassBtn">บันทึก</button>
                    </div>
                </div>
            </div>
        </div>
</main>

<script>
    // โหลดห้องเรียนเมื่อเลือกกลุ่มเรียน
    function loadClassrooms(childGroupId = 'child_group', classroomId = 'classroom') {
        var childGroup = document.getElementById(childGroupId).value;
        var classroomSelect = document.getElementById(classroomId);
        classroomSelect.innerHTML = '<option value="">ทั้งหมด</option>';

        if (!childGroup) {
            return;
        }

        // แสดง loading
        classroomSelect.innerHTML = '<option value="">กำลังโหลด...</option>';

        fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(childGroup)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (Array.isArray(data)) {
                    classroomSelect.innerHTML = '<option value="">ทั้งหมด</option>';
                    data.forEach(function(classroom) {
                        var option = document.createElement('option');
                        option.value = classroom.classroom_name;
                        option.textContent = classroom.classroom_name;
                        classroomSelect.appendChild(option);
                    });
                } else {
                    throw new Error('Invalid data format received');
                }
            })
            .catch(error => {
                console.error('Error loading classrooms:', error);
                classroomSelect.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
            });
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
        const table = document.getElementById('resultTable');

        // แสดง loading
        table.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        // เพิ่มปีการศึกษาที่เลือกไปด้วย
        const academicYear = document.getElementById('filter_academic_year').value;
        if (academicYear) {
            formData.append('academic_year', academicYear);
        }

        fetch('../../include/function/ajax_get_student_transitions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }

                if (!data.data || data.data.length === 0) {
                    table.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูล</div>';
                    return;
                }

                // จัดกลุ่มข้อมูลตามห้องเรียน
                const groupedData = groupStudentsByClass(data.data);
                let html = '';

                // สร้าง HTML สำหรับแต่ละกลุ่ม
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
                                            <th><input type="checkbox" onclick="toggleSelectAll(this, '${key}')" /></th> <!-- เลือกทั้งหมด -->
                                            <th>รหัสนักเรียน</th>
                                            <th>ชื่อ-นามสกุล</th>
                                            <th>ชื่อเล่น</th>
                                            <th>กลุ่มเรียน</th>
                                            <th>ห้องเรียน</th>
                                            <th>ปีการศึกษา</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;

                    group.students.forEach((student, idx) => {
                        html += `
                        <tr>
                        <td>
                    <input type="checkbox" 
                        class="select-student-checkbox" 
                        data-group="${key}" 
                        data-index="${idx}" 
                        value="${student.studentid}" 
                        data-student='${JSON.stringify(student)}'>
                </td>
                            <td>${student.studentid || ''}</td>
                            <td>${(student.prefix_th || '') + (student.firstname_th || '') + ' ' + (student.lastname_th || '')}</td>
                            <td>${student.nickname || ''}</td>
                            <td>${student.child_group || ''}</td>
                            <td>${student.classroom || ''}</td>
                            <td>${student.child_academic_year || '-'}</td>
                            <td><span class="badge status-studying">กำลังศึกษา</span></td>
                        </tr>
                    `;
                    });

                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                         <button
                                type="button"
                                class="btn btn-success"
                                onclick="bulkAction('${key}')"
                            >
                                <i class="bi bi-arrow-up-circle"></i> ดำเนินการนักเรียนที่เลือก
                        </button>
                            <button type="button" class="btn btn-primary" onclick='showBulkTransitionModal("${group.child_group}", "${group.classroom}", ${JSON.stringify(group.students)})'>
                                <i class="bi bi-arrow-up-circle"></i> เลื่อนสำเร็จการศึกษา (${group.students.length} คน)
                            </button>
                        </div>
                    </div>
                `;
                });

                table.innerHTML = html;
            })
            .catch(error => {
                table.innerHTML = `<div class="alert alert-danger">${error.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล'}</div>`;
            });
    }

    function bulkAction(groupKey) {
        // กรอง checkbox ที่ถูกเลือก (เฉพาะกลุ่มนี้)
        const checkboxes = document.querySelectorAll(`.select-student-checkbox[data-group="${groupKey}"]:checked`);
        const selectedStudents = Array.from(checkboxes).map(cb => JSON.parse(cb.dataset.student));
        if (selectedStudents.length === 0) {
            alert('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
            return;
        }
        modalContent =
            `<form id="editTransitionForm">
                      
        <div class="mb-3">
       <div class="mb-3">
            <label class="form-label">รายชื่อนักเรียนที่เลือก</label>
            <div class="table-responsive" style="max-height: 300px;">
                <div style="overflow-x: auto; max-height: 300px;">
                    <table class="table table-striped table-hover mb-3 text-center" style="min-width: 700px;">
                    <thead>
                        <tr class="table-primary">
                            <th>ลำดับ</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>รหัสนักเรียน</th>
                            <th>กลุ่มเรียน</th>
                            <th>ห้องเรียน</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${selectedStudents.map((s, i) => `
                            <tr>
                                <td>${i + 1}</td>
                                <td>${s.prefix_th || ''}${s.firstname_th || ''} ${s.lastname_th || ''}</td>
                                <td>${s.studentid}</td>
                                <td>${s.child_group}</td>
                                <td>${s.classroom}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    </table>
                </div>
          
        </div>
       <form id="bulkTransitionForm">
                            <div class="row mb-3 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">วันที่จบการศึกษา <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_success_select" name="date_success_select" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ประเภทการจบ <span class="text-danger">*</span></label>
                                    <select class="form-select" id="success_type_select" name="success_type_select" required>
                                        <option value="">-- เลือกประเภทการจบ --</option>
                                        <option value="สำเร็จการศึกษา">สำเร็จการศึกษาแบบปกติ</option>
                                        <option value="ย้ายไปโรงเรียนอื่น">ย้ายไปโรงเรียนอื่น</option>
                                        <option value="ลาออก">ลาออก</option>
                                    </select>
                                </div>
                            </div>
                        </form>`;
        try {
            Swal.fire({
                title: 'เลื่อนสำเร็จการศึกษา',
                html: modalContent,
                confirmButtonText: 'บันทึก',
                showCloseButton: true,
                cancelButtonText: 'ยกเลิก',
                width: window.innerWidth > 1024 ? '800px' : '100%',
                preConfirm: () => {
                    const date_success = document.getElementById('date_success_select').value;
                    const success_type = document.getElementById('success_type_select').value;


                    if (!date_success || !success_type) {
                        Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบทุกช่อง');
                        return false;
                    }


                }
            }).then((result) => {
                    if (result.isConfirmed) {

                        ////เหลือส่งค่าหากจะทำต่อ
                        const dateSuccess = document.getElementById('date_success_select').value;
                        const SuccessType = document.getElementById('success_type_select').value;
                        const student_ids = selectedStudents.map(s => s.id);
                        fetch('./process/transition_success.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                dateSuccess,
                                SuccessType,
                                student_ids: student_ids
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ',
                                    text: data.message || 'บันทึกข้อมูลสำเร็จ',
                                    confirmButtonText: 'ตกลง'
                                }).then(() => {
                                    loadResults(); // โหลดผลลัพธ์ใหม่
                                });
                            } else {
                                throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'ไม่สำเร็จ',
                                text: error.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
                                confirmButtonText: 'ตกลง'
                            });
                        });
                    }
            });
    } catch (error) {
        Swal.fire({
            toast: true,
            position: 'top-end', // มุมขวาบน
            icon: 'error', // success | error | warning | info | question
            title: 'error!',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        })

    }
    }

    function toggleSelectAll(source, groupKey) {
        const checkboxes = document.querySelectorAll(`.select-student-checkbox[data-group="${groupKey}"]`);
        checkboxes.forEach(cb => cb.checked = source.checked);
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
    document.addEventListener('DOMContentLoaded', function() {
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

        // โหลดปีการศึกษาจาก API มาเติมใน select
        fetch('../../include/function/get_academic_years.php') // แก้ไข path ให้ถูกต้อง
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.years)) {
                    const select = document.getElementById('filter_academic_year');
                    if (select) { // เพิ่มการตรวจสอบว่า element มีอยู่จริง
                        data.years.forEach(year => {
                            const option = document.createElement('option');
                            option.value = year;
                            option.textContent = year;
                            select.appendChild(option);
                        });
                    }
                }
            })
            .catch(err => {
                console.error('ไม่สามารถโหลดปีการศึกษา:', err);
            });
    });

    // เพิ่มฟังก์ชันช่วย
    function getBadgeClass(status) {
        return {
            'pending': 'bg-warning',
            'completed': 'bg-success',
            'cancelled': 'bg-danger'
        } [status] || 'bg-secondary';
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // เพิ่มฟังก์ชันสำหรับโหลดห้องเรียนเมื่อเลือกกลุ่มเรียนในฟอร์มหลายห้องเรียน
    document.querySelector('select[name="multi_current_group"]').addEventListener('change', function() {
        const currentGroup = this.value;
        const currentYear = document.getElementById('multi_current_academic_year_select').value;
        const classroomCheckboxes = document.querySelector('.classroom-checkboxes');
        const newGroupSelect = document.getElementById('multi_new_group');
        const newClassroomSelect = document.getElementById('multi_new_classroom');
        const effectiveDateInput = document.querySelector('input[name="multi_effective_date"]');
        const reasonTextarea = document.querySelector('textarea[name="multi_reason"]');
        const saveBtn = document.getElementById('saveMultiClassBtn');

        if (currentGroup && currentYear) {
            // แสดง loading
            classroomCheckboxes.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            // ตรวจสอบว่ามีนักเรียนในกลุ่มและปีการศึกษาที่เลือกหรือไม่
            fetch('../../include/function/ajax_get_multi_class_students.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        child_group: currentGroup,
                        academic_year: parseInt(currentYear) || currentYear // ถ้าแปลงเป็นตัวเลขไม่ได้ ให้ใช้ค่าเดิม
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        // ถ้ามีนักเรียน ให้โหลดห้องเรียน
                        return fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(currentGroup)}`);
                    } else {
                        throw new Error('ไม่พบข้อมูลนักเรียนในกลุ่มและปีการศึกษาที่เลือก');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        classroomCheckboxes.innerHTML = '';
                        data.forEach(function(classroom) {
                            const div = document.createElement('div');
                            div.className = 'form-check';
                            div.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="multi_current_classrooms" value="${classroom.classroom_name}" id="classroom_${classroom.classroom_name}">
                            <label class="form-check-label" for="classroom_${classroom.classroom_name}">
                                ${classroom.classroom_name}
                            </label>
                        `;
                            classroomCheckboxes.appendChild(div);
                        });

                        // เปิดใช้งานฟิลด์ที่จำเป็น
                        newGroupSelect.disabled = false;
                        effectiveDateInput.disabled = false;
                        reasonTextarea.disabled = false;
                    } else {
                        throw new Error('Invalid data format received');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    classroomCheckboxes.innerHTML = `<div class="alert alert-danger">${error.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูลห้องเรียน'}</div>`;
                    // ปิดการใช้งานฟิลด์ที่จำเป็น
                    newGroupSelect.disabled = true;
                    newClassroomSelect.disabled = true;
                    effectiveDateInput.disabled = true;
                    reasonTextarea.disabled = true;
                    saveBtn.disabled = true;
                });
        } else {
            classroomCheckboxes.innerHTML = '<div class="alert alert-warning">กรุณาเลือกกลุ่มเรียนและปีการศึกษาก่อน</div>';
            newGroupSelect.disabled = true;
            newClassroomSelect.disabled = true;
            effectiveDateInput.disabled = true;
            reasonTextarea.disabled = true;
            saveBtn.disabled = true;
        }
    });

    // เพิ่มฟังก์ชันสำหรับอัพเดทปีการศึกษาปัจจุบัน
    function updateCurrentAcademicYear() {
        const selectedClassrooms = Array.from(document.querySelectorAll('input[name="multi_current_classrooms"]:checked')).map(cb => cb.value);
        if (selectedClassrooms.length === 0) {
            document.getElementById('multi_current_academic_year_input').value = '';
            return;
        }

        // สร้าง FormData สำหรับส่งข้อมูล
        const formData = new FormData();
        formData.append('child_group', document.querySelector('select[name="multi_current_group"]').value);
        formData.append('academic_year', document.getElementById('multi_current_academic_year_input').value);
        selectedClassrooms.forEach(classroom => {
            formData.append('classroom', classroom);
        });

        // ส่งข้อมูลไปดึงปีการศึกษาจากห้องเรียนที่เลือก
        fetch('../../include/function/ajax_get_multi_class_students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    child_group: document.querySelector('select[name="multi_current_group"]').value,
                    academic_year: parseInt(document.getElementById('multi_current_academic_year_input').value) || document.getElementById('multi_current_academic_year_input').value,
                    classroom: selectedClassrooms
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    // หาปีการศึกษาที่ซ้ำกันมากที่สุด
                    const academicYears = data.data.map(student => student.child_academic_year).filter(year => year);
                    if (academicYears.length > 0) {
                        const mostCommonYear = academicYears.sort((a, b) =>
                            academicYears.filter(v => v === a).length - academicYears.filter(v => v === b).length
                        ).pop();

                        const currentYearInput = document.getElementById('multi_current_academic_year_input');
                        currentYearInput.value = mostCommonYear;

                        // อัพเดทปีการศึกษาใหม่
                        const newYearSelect = document.querySelector('select[name="multi_new_academic_year"]');
                        if (mostCommonYear) {
                            let newYear = '';
                            const yearStr = String(mostCommonYear);
                            if (yearStr.includes('/')) {
                                const [year1, year2] = yearStr.split('/');
                                newYear = `${parseInt(year1) + 1}/${parseInt(year2) + 1}`;
                            } else {
                                const year1 = parseInt(yearStr);
                                newYear = `${year1 + 1}/${year1 + 2}`;
                            }
                            // เลือก option ปีการศึกษาใหม่ใน dropdown
                            if (newYearSelect) {
                                const found = Array.from(newYearSelect.options).find(opt => opt.value === newYear);
                                if (found) {
                                    newYearSelect.value = newYear;
                                } else {
                                    const option = document.createElement('option');
                                    option.value = newYear;
                                    option.textContent = newYear;
                                    newYearSelect.appendChild(option);
                                    newYearSelect.value = newYear;
                                }
                            }
                        }
                    }
                } else {
                    console.error('Error:', data.message || 'ไม่พบข้อมูลนักเรียน');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // เพิ่ม event listener สำหรับการเปลี่ยนแปลงการติ๊กห้องเรียน
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'multi_current_classrooms') {
            const selectedClassrooms = Array.from(document.querySelectorAll('input[name="multi_current_classrooms"]:checked')).map(cb => cb.value);
            if (selectedClassrooms.length === 0) {
                document.getElementById('multi_current_academic_year_input').value = '';
                return;
            }

            // สร้าง FormData สำหรับส่งข้อมูล
            const formData = new FormData();
            formData.append('child_group', document.querySelector('select[name="multi_current_group"]').value);
            formData.append('academic_year', document.getElementById('multi_current_academic_year_input').value);
            selectedClassrooms.forEach(classroom => {
                formData.append('classroom', classroom);
            });

            // ส่งข้อมูลไปดึงปีการศึกษาจากห้องเรียนที่เลือก
            fetch('../../include/function/ajax_get_multi_class_students.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        child_group: document.querySelector('select[name="multi_current_group"]').value,
                        academic_year: parseInt(document.getElementById('multi_current_academic_year_input').value) || document.getElementById('multi_current_academic_year_input').value,
                        classroom: selectedClassrooms
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        // หาปีการศึกษาที่ซ้ำกันมากที่สุด
                        const academicYears = data.data.map(student => student.child_academic_year).filter(year => year);
                        if (academicYears.length > 0) {
                            const mostCommonYear = academicYears.sort((a, b) =>
                                academicYears.filter(v => v === a).length - academicYears.filter(v => v === b).length
                            ).pop();

                            const currentYearInput = document.getElementById('multi_current_academic_year_input');
                            currentYearInput.value = mostCommonYear;

                            // อัพเดทปีการศึกษาใหม่
                            const newYearSelect = document.querySelector('select[name="multi_new_academic_year"]');
                            if (mostCommonYear) {
                                let newYear = '';
                                const yearStr = String(mostCommonYear);
                                if (yearStr.includes('/')) {
                                    const [year1, year2] = yearStr.split('/');
                                    newYear = `${parseInt(year1) + 1}/${parseInt(year2) + 1}`;
                                } else {
                                    const year1 = parseInt(yearStr);
                                    newYear = `${year1 + 1}/${year1 + 2}`;
                                }
                                // เลือก option ปีการศึกษาใหม่ใน dropdown
                                if (newYearSelect) {
                                    const found = Array.from(newYearSelect.options).find(opt => opt.value === newYear);
                                    if (found) {
                                        newYearSelect.value = newYear;
                                    } else {
                                        const option = document.createElement('option');
                                        option.value = newYear;
                                        option.textContent = newYear;
                                        newYearSelect.appendChild(option);
                                        newYearSelect.value = newYear;
                                    }
                                }
                            }
                        }
                    } else {
                        console.error('Error:', data.message || 'ไม่พบข้อมูลนักเรียน');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    });

    // เพิ่มฟังก์ชันสำหรับโหลดห้องเรียนเมื่อเลือกกลุ่มเรียนในฟอร์มหลายห้องเรียน
    function showBulkTransitionModal(childGroup, classroom, students) {
        // students = array ของเด็กในกลุ่มนี้
        // ดึงปีการศึกษาทั้งหมด
        const years = students.map(s => s.child_academic_year).filter(y => y && y !== '-');
        //แสดงข้อมูลรายชื่อนักเรียนใน modal
        const studentListDiv = document.getElementById('StudentsList');
        if (students.length > 0) {
            let listHtml = '';
            students.forEach((student, index) => {

                listHtml += `
                <tr>
                <td>${index + 1}<input type="checkbox" hidden class="select-success-checkbox" value="${student.id}" checked></td>
                    
              
                <td>${student.studentid}</td>     
                <td class="text-start">${(student.prefix_th || '') + (student.firstname_th || '') + ' ' + (student.lastname_th || '')}</td>        
                <td>${student.nickname || ''}</td>
                <td>${student.child_group || ''}</td>
                <td>${student.classroom || ''}</td>
                <td>${student.child_academic_year || '-'}</td>
                <td>
                    ${student.status ? `<span class="badge status-studying">${student.status}</span>` : '-'}
                </td>
                </tr>
                `;
            });
            listHtml += '</tr>';
            studentListDiv.innerHTML = listHtml;
        } else {
            studentListDiv.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูลนักเรียน</div>';
        }

        // แสดงจำนวนนักเรียนที่จะเลื่อนสำเร็จการศึกษา
        document.querySelector('#studentCount').textContent = students.length;



        // แสดง modal
        const modal = new bootstrap.Modal(document.getElementById('bulkTransitionModal'));
        modal.show();
    }

    // เพิ่มฟังก์ชันสำหรับอัพเดทปีการศึกษาปัจจุบัน
    function updateNewAcademicYear(currentYearSelect, newYearSelect) {
        const currentYear = currentYearSelect.value;
        if (currentYear) {
            // แยกปีการศึกษาเป็นปีแรกและปีที่สอง
            const [year1, year2] = currentYear.split('/');
            // สร้างปีการศึกษาใหม่โดยเพิ่มปีขึ้นไป 1 ปี
            const newYear = `${parseInt(year1) + 1}/${parseInt(year2) + 1}`;
            // ตั้งค่าปีการศึกษาใหม่
            newYearSelect.value = newYear;
        }
    }

    // เพิ่ม event listener สำหรับการเปลี่ยนปีการศึกษาปัจจุบัน
    const multiCurrentAcademicYear = document.querySelector('select[name="multi_current_academic_year_select"]');
    if (multiCurrentAcademicYear) { // เพิ่มการตรวจสอบว่า element มีอยู่จริง
        multiCurrentAcademicYear.addEventListener('change', function() {
            updateNewAcademicYear(this, document.querySelector('select[name="multi_new_academic_year"]'));
        });
    }

    function saveBulkTransition() {
        // ดึง student id ที่ถูกเลือกจาก checkbox
        const selectedCheckboxes = document.querySelectorAll('.select-success-checkbox:checked');
        const selectedStudentIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        const dateSuccess = document.getElementById('date_success').value;
        const SuccessType = document.getElementById('success_type').value;
        //ล้าง classList is-invalid
        document.getElementById('date_success').classList.remove('is-invalid');
        document.getElementById('success_type').classList.remove('is-invalid');

        if (dateSuccess === '' || SuccessType === '') {
            if (dateSuccess === '') {
                document.getElementById('date_success').classList.add('is-invalid');
            } else {
                document.getElementById('date_success').classList.remove('is-invalid');
            }
            if (SuccessType === '') {
                document.getElementById('success_type').classList.add('is-invalid');
            } else {
                document.getElementById('success_type').classList.remove('is-invalid');
            }

        } else {
            fetch('./process/transition_success.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        dateSuccess,
                        SuccessType,
                        student_ids: selectedStudentIds
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ',
                                text: 'เลื่อนสำเร็จการศึกษาสำเร็จแล้ว!',
                                confirmButtonText: 'ตกลง'
                            }).then(() => {
                                loadResults();
                                const modalElement = document.getElementById('bulkTransitionModal');
                                const modal = bootstrap.Modal.getInstance(modalElement);
                                modal.hide();
                            });
                        } else {
                            loadResults();
                            const modalElement = document.getElementById('bulkTransitionModal');
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            modal.hide();
                        }
                    } else {
                        errorDiv.textContent = data.message || 'เกิดข้อผิดพลาด';
                        console.error('เกิดข้อผิดพลาด:', data.message || '');
                    }
                })
                .catch(err => {
                    errorDiv.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                    console.error('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', err);
                });
        }
    }

    // เพิ่มฟังก์ชันสำหรับบันทึกการเลื่อนสำเร็จการศึกษาแบบหลายห้องเรียน
    function saveMultiClassTransition() {
        const form = document.getElementById('multiClassTransitionForm');
        const formData = new FormData(form);
        // บังคับให้แน่ใจว่ามีค่า academic_year
        let academicYear = document.getElementById('multi_current_academic_year_input').value;
        if (academicYear && academicYear.includes('/')) {
            academicYear = academicYear.split('/')[0]; // ใช้ปีแรกเท่านั้น
        }
        if (!academicYear) {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'กรุณาเลือกปีการศึกษาที่ต้องการเลื่อนสำเร็จการศึกษา'
            });
            return;
        }
        formData.set('multi_current_academic_year', academicYear);

        // แปลงข้อมูลห้องเรียนที่เลือกเป็น array
        const selectedClassrooms = Array.from(document.querySelectorAll('input[name="multi_current_classrooms"]:checked')).map(cb => cb.value);

        if (selectedClassrooms.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'กรุณาเลือกห้องเรียนอย่างน้อย 1 ห้อง'
            });
            return;
        }

        // สร้าง object สำหรับส่งข้อมูล
        const data = {
            current_group: formData.get('multi_current_group'),
            current_classrooms: selectedClassrooms,
            current_academic_year: academicYear,
            new_group: formData.get('multi_new_group'),
            new_classroom: formData.get('multi_new_classroom'),
            new_academic_year: formData.get('multi_new_academic_year'),
            effective_date: formData.get('multi_effective_date'),
            reason: formData.get('multi_reason')
        };

        // ส่งข้อมูลไปยัง API
        fetch('../../include/function/ajax_multi_class_transition.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'บันทึกการเลื่อนสำเร็จการศึกษาเรียบร้อยแล้ว'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message || 'ไม่สามารถบันทึกข้อมูลได้'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                });
            });
    }

    // เพิ่ม event listener สำหรับการตรวจสอบความพร้อมในการบันทึก
    document.getElementById('multiClassTransitionForm').addEventListener('change', function() {
        const saveBtn = document.getElementById('saveMultiClassBtn');
        const requiredFields = [
            'multi_current_academic_year_select',
            'multi_current_group',
            'multi_new_group',
            'multi_new_classroom',
            'multi_new_academic_year',
            'multi_effective_date'
        ];

        const hasSelectedClassrooms = document.querySelectorAll('input[name="multi_current_classrooms"]:checked').length > 0;
        const allFieldsFilled = requiredFields.every(field => {
            const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
            return element && element.value;
        });

        saveBtn.disabled = !(allFieldsFilled && hasSelectedClassrooms);
    });

    // เพิ่มฟังก์ชันสำหรับจัดการการเปลี่ยนแปลงปีการศึกษา
    function handleAcademicYearChange(select) {
        const currentYear = select.value;
        const currentYearInput = document.getElementById('multi_current_academic_year_input');
        currentYearInput.value = currentYear;
        const form = document.getElementById('multiClassTransitionForm');
        const saveBtn = document.getElementById('saveMultiClassBtn');
        const alertDiv = document.querySelector('.alert-info');
        const classroomCheckboxes = document.querySelector('.classroom-checkboxes');
        const groupSelect = document.getElementById('multi_current_group');
        const newYearSelect = document.getElementById('multi_new_academic_year');

        // ปิดการใช้งานทุกฟิลด์ก่อน
        groupSelect.disabled = true;
        document.getElementById('multi_new_group').disabled = true;
        document.getElementById('multi_new_classroom').disabled = true;
        document.querySelector('input[name="multi_effective_date"]').disabled = true;
        document.querySelector('textarea[name="multi_reason"]').disabled = true;
        saveBtn.disabled = true;

        if (currentYear) {
            // แสดง loading
            alertDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            classroomCheckboxes.innerHTML = '<div class="alert alert-warning">กำลังตรวจสอบข้อมูล...</div>';

            // ตั้งค่าปีการศึกษาปัจจุบัน
            currentYearInput.value = currentYear;

            // เติม option ปีการศึกษาใหม่
            newYearSelect.innerHTML = '<option value="">-- เลือกปีการศึกษา --</option>';
            const nowYear = new Date().getFullYear() + 543;
            for (let i = nowYear - 2; i <= nowYear + 1; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                newYearSelect.appendChild(option);
            }

            // คำนวณปีการศึกษาใหม่อัตโนมัติ
            let nextYear = '';
            if (!isNaN(parseInt(currentYear))) {
                nextYear = parseInt(currentYear) + 1;
                newYearSelect.value = nextYear;
            } else {
                newYearSelect.value = '';
            }
            newYearSelect.disabled = false;

            // ตรวจสอบจำนวนนักเรียนในปีการศึกษาที่เลือก
            fetch('../../include/function/ajax_get_multi_class_students.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        academic_year: parseInt(currentYear) || currentYear // ถ้าแปลงเป็นตัวเลขไม่ได้ ให้ใช้ค่าเดิม
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        // ถ้ามีนักเรียน
                        alertDiv.innerHTML = `<i class="bi bi-info-circle"></i> พบนักเรียน ${data.data.length} คน ในปีการศึกษา ${currentYear}`;
                        groupSelect.disabled = false;
                        classroomCheckboxes.innerHTML = '<div class="alert alert-warning">กรุณาเลือกกลุ่มเรียนก่อน</div>';
                    } else {
                        // ถ้าไม่มีนักเรียน
                        alertDiv.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ไม่พบข้อมูลนักเรียนในปีการศึกษา ${currentYear}`;
                        classroomCheckboxes.innerHTML = '<div class="alert alert-danger">ไม่พบข้อมูลนักเรียนในปีการศึกษานี้</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> เกิดข้อผิดพลาดในการโหลดข้อมูล';
                    classroomCheckboxes.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
        } else {
            alertDiv.innerHTML = '<i class="bi bi-info-circle"></i> เลือกปีการศึกษาที่ต้องการเลื่อนสำเร็จการศึกษาก่อน';
            classroomCheckboxes.innerHTML = '<div class="alert alert-warning">กรุณาเลือกปีการศึกษาที่ต้องการเลื่อนสำเร็จการศึกษาก่อน</div>';
            currentYearInput.value = '';
            newYearSelect.value = '';
            newYearSelect.disabled = true;
        }
    }

    // เพิ่ม event listener สำหรับการปิด modal
    document.getElementById('multiClassTransitionModal').addEventListener('hidden.bs.modal', function() {
        // ลบ modal-backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        // ลบ class modal-open จาก body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });

    function showMultiClassTransitionModal() {
        // โหลดปีการศึกษาจาก API
        fetch('../../include/function/get_academic_years.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.years)) {
                    const select = document.getElementById('multi_current_academic_year_select');
                    select.innerHTML = '<option value="">-- เลือกปีการศึกษา --</option>';

                    // ตรวจสอบข้อมูลนักเรียนในแต่ละปีการศึกษา
                    const yearPromises = data.years.map(year => {
                        return fetch('../../include/function/ajax_get_multi_class_students.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    academic_year: year
                                })
                            })
                            .then(response => response.json())
                            .then(result => ({
                                year: year,
                                hasStudents: result.success && result.data && result.data.length > 0,
                                studentCount: result.data ? result.data.length : 0
                            }));
                    });

                    // รอให้ตรวจสอบข้อมูลนักเรียนครบทุกปีการศึกษา
                    Promise.all(yearPromises)
                        .then(yearResults => {
                            yearResults.forEach(result => {
                                const option = document.createElement('option');
                                option.value = result.year;
                                if (result.hasStudents) {
                                    option.textContent = `${result.year} (มีนักเรียน ${result.studentCount} คน)`;
                                } else {
                                    option.textContent = `${result.year} (ไม่มีข้อมูลนักเรียน)`;
                                    option.disabled = true;
                                }
                                select.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Error checking students:', error);
                            const select = document.getElementById('multi_current_academic_year_select');
                            select.innerHTML = '<option value="">-- เกิดข้อผิดพลาดในการโหลดข้อมูล --</option>';
                        });
                }
            })
            .catch(error => {
                console.error('Error loading academic years:', error);
            });


        // แสดง modal
        const modal = new bootstrap.Modal(document.getElementById('multiClassTransitionModal'));
        modal.show();
    }

    // เพิ่ม event listener สำหรับการเปลี่ยนกลุ่มเรียน
    document.getElementById('multi_current_group').addEventListener('change', function() {
        const currentGroup = this.value;
        const currentYear = document.getElementById('multi_current_academic_year_select').value;
        const classroomCheckboxes = document.querySelector('.classroom-checkboxes');
        const newGroupSelect = document.getElementById('multi_new_group');
        const newClassroomSelect = document.getElementById('multi_new_classroom');
        const effectiveDateInput = document.querySelector('input[name="multi_effective_date"]');
        const reasonTextarea = document.querySelector('textarea[name="multi_reason"]');
        const saveBtn = document.getElementById('saveMultiClassBtn');

        if (currentGroup && currentYear) {
            // แสดง loading
            classroomCheckboxes.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            // ตรวจสอบว่ามีนักเรียนในกลุ่มและปีการศึกษาที่เลือกหรือไม่
            fetch('../../include/function/ajax_get_multi_class_students.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        child_group: currentGroup,
                        academic_year: parseInt(currentYear) || currentYear // ถ้าแปลงเป็นตัวเลขไม่ได้ ให้ใช้ค่าเดิม
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        // ถ้ามีนักเรียน ให้โหลดห้องเรียน
                        return fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(currentGroup)}`);
                    } else {
                        throw new Error('ไม่พบข้อมูลนักเรียนในกลุ่มและปีการศึกษาที่เลือก');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        classroomCheckboxes.innerHTML = '';
                        data.forEach(function(classroom) {
                            const div = document.createElement('div');
                            div.className = 'form-check';
                            div.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="multi_current_classrooms" value="${classroom.classroom_name}" id="classroom_${classroom.classroom_name}">
                            <label class="form-check-label" for="classroom_${classroom.classroom_name}">
                                ${classroom.classroom_name}
                            </label>
                        `;
                            classroomCheckboxes.appendChild(div);
                        });

                        // เปิดใช้งานฟิลด์ที่จำเป็น
                        newGroupSelect.disabled = false;
                        effectiveDateInput.disabled = false;
                        reasonTextarea.disabled = false;
                    } else {
                        throw new Error('Invalid data format received');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    classroomCheckboxes.innerHTML = `<div class="alert alert-danger">${error.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูลห้องเรียน'}</div>`;
                    // ปิดการใช้งานฟิลด์ที่จำเป็น
                    newGroupSelect.disabled = true;
                    newClassroomSelect.disabled = true;
                    effectiveDateInput.disabled = true;
                    reasonTextarea.disabled = true;
                    saveBtn.disabled = true;
                });
        } else {
            classroomCheckboxes.innerHTML = '<div class="alert alert-warning">กรุณาเลือกกลุ่มเรียนและปีการศึกษาก่อน</div>';
            newGroupSelect.disabled = true;
            newClassroomSelect.disabled = true;
            effectiveDateInput.disabled = true;
            reasonTextarea.disabled = true;
            saveBtn.disabled = true;
        }
    });

    // เพิ่ม event listener สำหรับปุ่มเปิด modal
    document.querySelector('[data-bs-target="#multiClassTransitionModal"]').addEventListener('click', function() {
        showMultiClassTransitionModal();
    });

    // โหลดห้องเรียนใหม่เมื่อเลือกกลุ่มเรียนใหม่ในฟอร์มเลื่อนสำเร็จการศึกษาหลายคน
    if (document.getElementById('multi_new_group')) {
        document.getElementById('multi_new_group').addEventListener('change', function() {
            loadClassrooms('multi_new_group', 'multi_new_classroom');
            document.getElementById('multi_new_classroom').disabled = false;
            document.getElementById('multi_new_academic_year').disabled = false;
        });
    }
</script>