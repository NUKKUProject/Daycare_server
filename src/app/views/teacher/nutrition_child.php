<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once '../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/function/child_functions.php';
$is_admin = getUserRole() === 'admin';
$is_parent = getUserRole() === 'parent';

// ใช้ user_id จาก session เป็น teacher_id
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id']; // ดึงค่า user_id มาเป็น teacher_id
} else {
    die('ไม่พบข้อมูลผู้สอน. กรุณาเข้าสู่ระบบอีกครั้ง.'); // กรณีที่ไม่มี user_id ใน session
}

include __DIR__ . '/../../include/auth/auth_dashboard.php';
?>
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

<main class="main-content">
<div class="container col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <?php
    // PHP Section for Breadcrumb
    $previous_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    $previous_page_name = getPageNameFromURL($previous_page);
    ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0">
            <?php if ($previous_page): ?>
                <li class="breadcrumb-item"><a class="text-decoration-none"
                        href="<?= htmlspecialchars($previous_page) ?>"><?= htmlspecialchars($previous_page_name) ?></a></li>
            <?php else: ?>
                <li class="breadcrumb-item">Dashboard</li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">ประวัติประจำตัวของเด็ก</li>
        </ol>
    </nav>
    <h2 class="mb-4">บันทึกโภชนาการประจำวันของเด็ก</h2>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <!-- แสดงข้อมูลเด็ก -->
            <form id="healthCheckForm" method="post" action="../../include/process/save_health_data.php"
                onsubmit="return validateForm()">
                <!-- ตัวเลือกครู -->
                <input type="hidden" id="teacher_id"
                    value="<?php echo $teacher_id; ?>"><!-- ค่า teacherId ที่รับมาจาก PHP -->
                <!-- เลือกกลุ่มเรียน -->
                <div class="mb-3">
                    <label for="child_group" class="form-label fw-bold">กลุ่มเรียน</label>
                    <select name="child_group" id="child_group" class="form-select"
                        onchange="loadClassrooms(teacher_id)">
                        <option value="">-- เลือกกลุ่มเรียน --</option>
                        <?php
                        // ฟังก์ชันเพื่อดึงข้อมูลกลุ่มเรียน
                        $groups = getChildGroups($teacher_id);
                        foreach ($groups as $group) {
                            // ตรวจสอบว่า child_group ไม่เป็น null หรือค่าว่าง
                            if (!empty($group['child_group'])) {
                                echo "<option value='" . $group['child_group'] . "'>" . $group['child_group'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- เลือกห้องเรียน -->
                <div class="mb-3">
                    <label for="classroom" class="form-label">ห้องเรียน</label>
                    <select name="classroom" id="classroom" class="form-select" onchange="getChildren()">
                        <option value="">-- เลือกห้องเรียน --</option>
                        <!-- ห้องเรียนจะถูกเพิ่มโดย JavaScript -->
                    </select>
                </div>

                <!-- รายชื่อเด็ก -->
                <div class="mb-3">
                    <label class="form-label">ตารางการตรวจร่างกาย</label>
                    <div id="childrenList" class="container">
                        <!-- ตารางจะแสดงผลที่นี่ -->
                    </div>
                </div>

                <!-- ปุ่มบันทึก -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="saveAllDrafts()">บันทึกข้อมูล</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

</div>
</main>
</body>

<script>
    // ฟังก์ชันที่ใช้ดึงข้อมูลห้องเรียน
    function loadClassrooms() {
        var teacherId = document.getElementById('teacher_id').value;
        var childGroup = document.getElementById('child_group').value;

        if (!childGroup || !teacherId) {
            document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
            return;
        }

        fetch(`../../include/function/get_teacher_classrooms.php?teacher_id=${teacherId}&child_group=${encodeURIComponent(childGroup)}`)
            .then(response => response.json())
            .then(data => {
                var classroomSelect = document.getElementById('classroom');
                classroomSelect.innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';

                if (data.classrooms && Array.isArray(data.classrooms)) {
                    data.classrooms.forEach(function(classroom) {
                        var option = document.createElement('option');
                        option.value = classroom.classroom_name || classroom.classroom;
                        option.textContent = classroom.classroom_name || classroom.classroom;
                        classroomSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถโหลดข้อมูลห้องเรียนได้'
                });
            });
    }

    // เพิ่มการจัดการ URL parameters เมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('child_group')) {
            document.getElementById('child_group').value = urlParams.get('child_group');
            loadClassrooms();
        }
    });

    // ฟังก์ชันโหลดข้อมูลเด็ก
    function getChildren() {
        const childGroup = document.getElementById('child_group').value;
        const classroom = document.getElementById('classroom').value;
        const teacherId = document.getElementById('teacher_id').value;

        if (childGroup && classroom && teacherId) {
            fetch(`../../include/function/get_children.php?child_group=${encodeURIComponent(childGroup)}&classroom=${encodeURIComponent(classroom)}&teacher_id=${teacherId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Received data:', data); // เพิ่ม debug log
                    if (Array.isArray(data) && data.length > 0) {
                        displayChildrenTable(data);
                    } else {
                        document.getElementById('childrenList').innerHTML = 
                            '<div class="alert alert-info">ไม่พบข้อมูลเด็ก</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('childrenList').innerHTML = 
                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
        }
    }

    // แก้ไขส่วนที่แสดงรายชื่อเด็กและฟอร์ม
    function displayChildrenTable(children) {
        if (!Array.isArray(children) || children.length === 0) {
            document.getElementById('childrenList').innerHTML = 
                '<div class="alert alert-info">ไม่พบข้อมูลเด็ก</div>';
            return;
        }

        const table = document.createElement('table');
        table.className = 'table table-hover';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>รหัสนักเรียน</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ชื่อเล่น</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
        `;

        children.forEach((child, index) => {
            const row = document.createElement('tr');
            row.className = 'child-row';
            row.dataset.childId = child.studentid;
            
            // สร้าง div สำหรับฟอร์มข้อมูลที่ซ่อนไว้
            const detailsRow = document.createElement('tr');
            detailsRow.className = 'details-row d-none';
            detailsRow.id = `details-${child.studentid}`;
            
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${child.studentid}</td>
                <td class="child-name" style="cursor: pointer;">
                    ${child.prefix_th} ${child.firstname_th} ${child.lastname_th}
                </td>
                <td>
                    ${child.nickname}
                </td>
                <td>
                    <span class="status-badge badge bg-danger">ยังไม่บันทึก</span>
                </td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm toggle-form">
                        <i class="bi bi-pencil-square"></i> บันทึกข้อมูล
                    </button>
                </td>
            `;

            detailsRow.innerHTML = `
                <td colspan="5">
                    <div class="card mb-3">
                        <div class="card-body">
                            <form class="nutrition-form" data-student-id="${child.studentid}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">น้ำหนัก (กก.)</label>
                                            <input type="number" class="form-control" name="weight" step="0.1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ส่วนสูง (ซม.)</label>
                                            <input type="number" class="form-control" name="height" step="0.1" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">มื้ออาหาร</label>
                                            <select class="form-select" name="meal_type" required>
                                                <option value="">เลือกมื้ออาหาร</option>
                                                <option value="เช้า">เช้า</option>
                                                <option value="กลางวัน">กลางวัน</option>
                                                <option value="เย็น">เย็น</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">สถานะการทานอาหาร</label>
                                            <select class="form-select" name="meal_status" required>
                                                <option value="">เลือกสถานะ</option>
                                                <option value="ทานหมด">รับประทานหมด</option>
                                                <option value="ทานได้ครึ่งหนึ่ง">รับประทานได้ครึ่งหนึ่ง</option>
                                                <option value="ทานได้น้อย">รับประทานได้น้อย</option>
                                                <option value="ไม่ทาน">ไม่รับประทาน</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">บันทึกเพิ่มเติม</label>
                                    <textarea class="form-control" name="nutrition_note" rows="2"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary btn-sm cancel-form">ยกเลิก</button>
                                    <button type="submit" class="btn btn-success btn-sm save-form">บันทึกข้อมูล</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </td>
            `;

            // เพิ่ม event listeners
            row.querySelector('.child-name').addEventListener('click', () => toggleDetails(child.studentid));
            row.querySelector('.toggle-form').addEventListener('click', () => toggleDetails(child.studentid));

            table.querySelector('tbody').appendChild(row);
            table.querySelector('tbody').appendChild(detailsRow);

            // เพิ่มการเรียกใช้ loadDraftData หลังจากสร้างฟอร์ม
            loadDraftData(child.studentid);
        });

        document.getElementById('childrenList').innerHTML = '';
        document.getElementById('childrenList').appendChild(table);

        // เพิ่ม event listeners สำหรับฟอร์ม
        document.querySelectorAll('.nutrition-form').forEach(form => {
            form.addEventListener('submit', handleFormSubmit);
        });

        document.querySelectorAll('.cancel-form').forEach(button => {
            button.addEventListener('click', (e) => {
                const studentId = e.target.closest('form').dataset.studentId;
                toggleDetails(studentId);
            });
        });
    }

    // ฟังก์ชันสำหรับ toggle การแสดงรายละเอียด
    function toggleDetails(studentId) {
        const detailsRow = document.getElementById(`details-${studentId}`);
        detailsRow.classList.toggle('d-none');
    }

    // แก้ไขฟังก์ชัน handleFormSubmit
    async function handleFormSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const studentId = form.dataset.studentId;

        try {
            // เก็บข้อมูลจากฟอร์มไว้ใน localStorage
            const formData = {
                student_id: studentId,
                weight: form.querySelector('input[name="weight"]').value,
                height: form.querySelector('input[name="height"]').value,
                meal_type: form.querySelector('select[name="meal_type"]').value,
                meal_status: form.querySelector('select[name="meal_status"]').value,
                nutrition_note: form.querySelector('textarea[name="nutrition_note"]').value,
                saved_at: new Date().toISOString()
            };

            // บันทึกลง localStorage
            let savedData = JSON.parse(localStorage.getItem('nutrition_drafts') || '{}');
            savedData[studentId] = formData;
            localStorage.setItem('nutrition_drafts', JSON.stringify(savedData));

            // อัพเดทสถานะในตาราง
            const row = document.querySelector(`[data-child-id="${studentId}"]`);
            const statusBadge = row.querySelector('.status-badge');
            statusBadge.className = 'status-badge badge bg-success';
            statusBadge.textContent = 'บันทึกแบบร่างแล้ว';


            // ซ่อนฟอร์ม
            toggleDetails(studentId);

            // แสดง toast alert แจ้งเตือนความสำเร็จ
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: 'บันทึกแบบร่างเรียบร้อย'
            });

        } catch (error) {
            console.error('Error:', error);
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            Toast.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถบันทึกแบบร่างได้'
            });
        }
    }

    // เพิ่มฟังก์ชันสำหรับโหลดข้อมูลแบบร่าง
    function loadDraftData(studentId) {
        const savedData = JSON.parse(localStorage.getItem('nutrition_drafts') || '{}');
        const draftData = savedData[studentId];
        
        if (draftData) {
            const form = document.querySelector(`form[data-student-id="${studentId}"]`);
            if (form) {
                form.querySelector('input[name="weight"]').value = draftData.weight;
                form.querySelector('input[name="height"]').value = draftData.height;
                form.querySelector('select[name="meal_type"]').value = draftData.meal_type;
                form.querySelector('select[name="meal_status"]').value = draftData.meal_status;
                form.querySelector('textarea[name="nutrition_note"]').value = draftData.nutrition_note;

                // อัพเดทสถานะ
                const row = document.querySelector(`[data-child-id="${studentId}"]`);
                const statusBadge = row.querySelector('.status-badge');
                statusBadge.className = 'status-badge badge bg-warning';
                statusBadge.textContent = 'บันทึกแบบร่างแล้ว';
            }
        }
    }

    function resetForm() {
        // รีเซ็ตค่าฟอร์มกลับไปเป็นค่าเริ่มต้น
        document.getElementById('healthCheckForm').reset();

        // ซ่อนองค์ประกอบเพิ่มเติม
        document.getElementById('childrenList').innerHTML = ''; // ล้างตารางรายชื่อเด็ก
        document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>'; // ล้างตัวเลือกห้องเรียน
    }

    function validateForm() {
        let isValid = true;
        const weights = document.getElementsByName('weight[]');
        const heights = document.getElementsByName('height[]');
        const mealTypes = document.getElementsByName('meal_type[]');
        const mealStatuses = document.getElementsByName('meal_status[]');

        // ตรวจสอบน้ำหนักและส่วนสูง
        for (let i = 0; i < weights.length; i++) {
            const weight = parseFloat(weights[i].value);
            const height = parseFloat(heights[i].value);
            
            if (weight <= 0 || weight > 100) {
                alert('กรุณากรอกน้ำหนักให้ถูกต้อง (0-100 กก.)');
                isValid = false;
                break;
            }
            
            if (height <= 0 || height > 200) {
                alert('กรุณากรอกส่วนสูงให้ถูกต้อง (0-200 ซม.)');
                isValid = false;
                break;
            }
        }

        // ตรวจสอบการเลือกมื้ออาหารและสถานะการทาน
        for (let i = 0; i < mealTypes.length; i++) {
            if (!mealTypes[i].value || !mealStatuses[i].value) {
                alert('กรุณาเลือกมื้ออาหารและสถานะการทานให้ครบถ้วน');
                isValid = false;
                break;
            }
        }

        return isValid;
    }

    // แก้ไขฟังก์ชัน saveAllDrafts
    async function saveAllDrafts() {
        try {
            // ดึงข้อมูลแบบร่างทั้งหมดจาก localStorage
            const savedData = JSON.parse(localStorage.getItem('nutrition_drafts') || '{}');
            
            if (Object.keys(savedData).length === 0) {
                throw new Error('ไม่พบข้อมูลแบบร่างที่ต้องการบันทึก');
            }

            // แสดง confirm dialog
            const confirmResult = await Swal.fire({
                title: 'ยืนยันการบันทึก',
                text: "คุณต้องการบันทึกข้อมูลทั้งหมดใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, บันทึกข้อมูล',
                cancelButtonText: 'ยกเลิก'
            });

            if (!confirmResult.isConfirmed) {
                return;
            }

            // แปลงข้อมูลให้อยู่ในรูปแบบที่ต้องการส่งไป server
            const draftsToSave = Object.values(savedData).map(draft => ({
                student_id: draft.student_id,
                weight: draft.weight,
                height: draft.height,
                meal_type: draft.meal_type,
                meal_status: draft.meal_status,
                nutrition_note: draft.nutrition_note
            }));

            // ส่งข้อมูลไปบันทึกที่ server
            const response = await fetch('../../include/process/save_nutrition_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ drafts: draftsToSave })
            });

            const result = await response.json();

            if (result.status === 'success') {
                // ลบข้อมูลแบบร่างออกจาก localStorage
                localStorage.removeItem('nutrition_drafts');
                
                // อัพเดทสถานะทุกรายการเป็น "บันทึกแล้ว"
                document.querySelectorAll('.status-badge').forEach(badge => {
                    badge.className = 'status-badge badge bg-success';
                    badge.textContent = 'บันทึกแล้ว';
                });

                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกข้อมูลสำเร็จ',
                    text: 'บันทึกข้อมูลทั้งหมดเรียบร้อยแล้ว',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }

        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: error.message
            });
        }
    }

</script>


</html>