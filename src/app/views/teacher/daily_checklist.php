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
                text: '<?php echo htmlspecialchars($message); ?>',
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
    <h2 class="mb-4">บันทึกการตรวจความสะอาดของร่างกายและการเจ็บป่วยประจำวันของเด็ก</h2>
    
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
                        onchange="loadClassrooms()">
                        <option value="">-- เลือกกลุ่มเรียน --</option>
                        <?php
                        if ($is_admin) {
                            // ถ้าเป็น admin ให้ดึงข้อมูลกลุ่มเรียนทั้งหมด
                            $groups = getAllChildGroups(); // สร้างฟังก์ชันใหม่สำหรับดึงกลุ่มทั้งหมด
                        } else {
                            // ถ้าเป็นครู ให้ดึงเฉพาะกลุ่มที่ครูดูแล
                            $groups = getChildGroups($teacher_id);
                        }
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
                    <button type="submit" class="btn btn-success">บันทึกข้อมูล</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
</body>

<script>
// แก้ไขฟังก์ชัน loadClassrooms()
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

// แก้ไขฟังก์ชัน getChildren
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

// แก้ไขฟังก์ชัน displayChildrenTable
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
<td colspan="6">
    <div class="card mb-3">
        <div class="card-body">
            <!-- เพิ่มปุ่มลัดด้านบนฟอร์ม -->
            <div class="mb-3 d-flex gap-2">
                <button type="button" class="btn btn-outline-success btn-sm" 
                        onclick="markAllNormal('${child.studentid}')">
                    <i class="bi bi-check-all"></i> ติ๊กทั้งหมดปกติ/สะอาด
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="clearAllChecks('${child.studentid}')">
                    <i class="bi bi-x-lg"></i> ล้างการติ๊กทั้งหมด
                </button>
            </div>
            <form class="health-check-form" data-student-id="${child.studentid}" onsubmit="handleFormSubmit(event)">
                <input type="hidden" name="student_id" value="${child.studentid}">
                <input type="hidden" name="prefix_th" value="${child.prefix_th}">
                <input type="hidden" name="first_name_th" value="${child.firstname_th}">
                <input type="hidden" name="last_name_th" value="${child.lastname_th}">
                <input type="hidden" name="child_group" value="${document.getElementById('child_group').value}">
                <input type="hidden" name="classroom" value="${document.getElementById('classroom').value}">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ผมศีรษะ</th>
                                <td class="hair">
                                    <div><input type="checkbox" name="hair_${index}" value="สะอาด" onchange="handleMultipleSelection(this, 'hair', ${index})"> สะอาด</div>
                                    <div><input type="checkbox" name="hair_${index}" value="ผมยาว"> ผมยาวควรตัด</div>
                                    <div><input type="checkbox" name="hair_${index}" value="ไม่สะอาด" onchange="handleMultipleSelection(this, 'hair', ${index})"> ไม่สะอาด</div>
                                    <div><input type="checkbox" name="hair_${index}" value="มีเหา"> มีเหา</div>
                                    <div>อื่นๆ:<textarea name="hair_reason_${index}" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                                </td>
                            </tr>

                            <tr>
                                <th>ตา</th>
                                    <td class="eye">
                                        <div><input type="checkbox" name="eye_${index}" value="ปกติ"> ปกติ</div>
                                        <div><input type="checkbox" name="eye_${index}" value="ตาแดง"> ตาแดง</div>
                                        <div>
                                            <input type="checkbox" id="eyeCheckbox_${index}" name="eye_${index}" value="มีขี้ตา" 
                                                onchange="toggleConditionElement('eye', ${index})">
                                            <label for="eyeCheckbox_${index}">มีขี้ตา</label>
                                        </div>
                                        <div id="eyeDiv_${index}" class="eye-condition-div" style="display: none;">
                                            <label for="eyeCondition_${index}">เลือกอาการ</label>
                                            <select id="eyeCondition_${index}" name="eyeCondition_${index}" disabled>
                                                <option value="ขวาปกติ">ขวาปกติ</option>
                                                <option value="เหลือง/เขียว">เหลือง/เขียว</option>
                                            </select>
                                        </div>
                                        <div>อื่นๆ: <textarea name="eye_reason_${index}" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                                    </td>
                            </tr>

                            <tr>
                                <th>ช่องปากและคอ</th>
                                    <td class="mouth">
                                        <div><input type="checkbox" name="mouth_${index}" value="สะอาด"> สะอาด</div>
                                        <div><input type="checkbox" name="mouth_${index}" value="มีกลิ่นปาก"> มีกลิ่นปาก</div>
                                        <div><input type="checkbox" name="mouth_${index}" value="มีแผลในปาก"> มีแผลในปาก</div>
                                        <div><input type="checkbox" name="mouth_${index}" value="มีตุ่มในปาก"> มีตุ่มในปาก</div>
                                    </td>
                            </tr>

                            <tr>
                                <th>ฟัน</th>
                                    <td class="teeth">
                                        <div><input type="checkbox" name="teeth_${index}" value="สะอาด"> สะอาด</div>
                                        <div><input type="checkbox" name="teeth_${index}" value="มีคราบนม/ไม่สะอาด"> มีคราบนม/ไม่สะอาด</div>
                                        <div>
                                            <input type="checkbox" id="teethCheckbox_${index}" name="teeth_${index}" value="ฟันผุ" 
                                                onchange="toggleConditionElement('teeth', ${index})">
                                            <label for="teethCheckbox_${index}">ฟันผุ</label>
                                            <input type="number" id="teethInput_${index}" name="teeth_count_${index}" 
                                                placeholder="ระบุจำนวนซี่" min="1" max="32" disabled> ซี่.
                                        </div>
                                    </td>
                            </tr>
                        
                        <tr>
                            <th class="align-middle" style="min-width: 180px; width: 12%">หู</th>
                            <td class="ears">
                                <div><input type="checkbox" name="ears_${index}" value="สะอาด"> สะอาด</div>
                                <div><input type="checkbox" name="ears_${index}" value="ไม่สะอาด"> ไม่สะอาด</div>
                                <div><input type="checkbox" name="ears_${index}" value="มีขี้หู"> มีขี้หู</div>
                            </td>
                        </tr>

                        <tr>
                        <th class="align-middle" style="min-width: 180px; width: 12%">จมูก</th>
                            <td class="nose">
                                <div><input type="checkbox" name="nose_${index}" value="สะอาด"> สะอาด</div>
                                <div>
                                    <input type="checkbox" id="noseCheckbox_${index}" name="nose_${index}" value="มีน้ำมูก" 
                                        onchange="toggleConditionElement('nose', ${index})">
                                    <label for="noseCheckbox_${index}">มีน้ำมูก</label>
                                </div>
                                <div id="noseDiv_${index}" class="nose-condition-div" style="display: none;">
                                    <label for="noseCondition_${index}">เลือกอาการ</label>
                                    <select id="noseCondition_${index}" name="noseCondition_${index}" disabled>
                                        <option value="ใส">ใส</option>
                                        <option value="เหลือง">เหลือง</option>
                                        <option value="เขียว">เขียว</option>
                                    </select>
                                </div>
                                <div>อื่นๆ: <textarea name="nose_reason_${index}" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                            </td>
                        </tr>

                    <tr>
                        <th class="align-middle" style="min-width: 180px; width: 12%">เล็บมือ</th>
                            <td class="nails">
                                <div><input type="checkbox" name="nails_${index}" value="สะอาด"> สะอาด</div>
                                <div><input type="checkbox" name="nails_${index}" value="ไม่สะอาด"> ไม่สะอาด/เล็บสกปรก</div>
                                <div><input type="checkbox" name="nails_${index}" value="เล็บยาว"> เล็บยาว</div>
                            </td>
                    </tr>

                    <tr>
                        <th class="align-middle" style="min-width: 180px; width: 12%">ผิวหนัง</th>
                            <td class="skin">
                                <div>
                                    <input type="checkbox" name="skin_${index}" value="สะอาด" id="skinCleanCheckbox_${index}">
                                    <label for="skinCleanCheckbox_${index}">สะอาด</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="skinWoundCheckbox_${index}" name="skin_${index}" value="มีแผล" 
                                        onchange="toggleConditionElement('skinWound', ${index})">
                                    <label for="skinWoundCheckbox_${index}">มีแผล</label>
                                    <div id="skinWoundDiv_${index}" style="display: none;"> <!-- ซ่อนตั้งแต่เริ่มต้น -->
                                        รายละเอียด: 
                                        <textarea name="skinWound_reason_${index}" id="skinWoundInput_${index}" rows="1" class="form-control" 
                                            placeholder="ระบุเพิ่มเติม" disabled></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="checkbox" id="skinRashCheckbox_${index}" name="skin_${index}" value="มีผื่น" 
                                        onchange="toggleConditionElement('skinRash', ${index})">
                                    <label for="skinRashCheckbox_${index}">มีผื่น</label>
                                    <div id="skinRashDiv_${index}" style="display: none;"> <!-- ซ่อนตั้งแต่เริ่มต้น -->
                                        รายละเอียด: 
                                        <textarea name="skinRash_reason_${index}" id="skinRashInput_${index}" rows="1" class="form-control" 
                                            placeholder="ระบุเพิ่มเติม" disabled></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="checkbox" name="skin_${index}" value="มีขี้ไคล" id="skinScurfCheckbox_${index}">
                                    <label for="skinScurfCheckbox_${index}">มีขี้ไคล</label>
                                </div>
                                </td>
                            </tr>

                <tr>   
                    <th class="align-middle" style="min-width: 180px; width: 12%">ฝ่ามือและฝ่าเท้า</th>
                        <td class="hands_feet">
                            <div><input type="checkbox" name="hands_feet_${index}" value="ปกติ"> ปกติ</div>
                            <div><input type="checkbox" name="hands_feet_${index}" value="จุดหรือผื่น"> จุดหรือผื่น</div>
                            <div><input type="checkbox" name="hands_feet_${index}" value="ตุ่มใส"> ตุ่มใส</div>
                            <div><input type="checkbox" name="hands_feet_${index}" value="ตุ่มหนอง"> ตุ่มหนอง</div>
                        </td>
                </tr>

                <tr>
                    <th class="align-middle" style="min-width: 180px; width: 12%">แขนและขา</th>
                        <td class="arms_legs">
                            <div><input type="checkbox" name="arms_legs_${index}" value="ปกติ"> ปกติ</div>
                            <div><input type="checkbox" name="arms_legs_${index}" value="จุดหรือผื่น"> จุดหรือผื่น</div>
                            <div><input type="checkbox" name="arms_legs_${index}" value="ตุ่มใส"> ตุ่มใส</div>
                            <div><input type="checkbox" name="arms_legs_${index}" value="ตุ่มหนอง"> ตุ่มหนอง</div>
                        </td>
                </tr>

                    <tr>
                    <th class="align-middle" style="min-width: 180px; width: 12%">ลำตัวและหลัง</th>
                        <td class="body">
                            <div><input type="checkbox" name="body_${index}" value="ปกติ"> ปกติ</div>
                            <div><input type="checkbox" name="body_${index}" value="จุดหรือผื่น"> จุดหรือผื่น</div>
                            <div><input type="checkbox" name="body_${index}" value="ตุ่มใส"> ตุ่มใส</div>
                            <div><input type="checkbox" name="body_${index}" value="ตุ่มหนอง"> ตุ่มหนอง</div>
                        </td>
                    </tr>

                <tr>
                    <th class="align-middle" style="min-width: 180px; width: 12%">อาการผิดปกติ</th>
                    <td class="symptoms">
                        <div>
                            <input type="checkbox" name="symptoms_${index}" value="ไม่มี" 
                                onchange="handleMultipleSelection(this, 'symptoms', ${index})"> ไม่มี
                        </div>
                        <div>
                            <input type="checkbox" id="feverCheckbox_${index}" name="symptoms_${index}" value="มีไข้" 
                                onchange="toggleConditionElement('fever', ${index}); handleMultipleSelection(this, 'symptoms', ${index})">
                            <label for="feverCheckbox_${index}">มีไข้</label>
                            <input type="number" id="feverInput_${index}" name="fever_temp_${index}" 
                                placeholder="ระบุอุณหภูมิ" min="35" max="42" step="0.1" disabled> องศา.
                        </div>
                        <div>
                            <input type="checkbox" id="coughCheckbox_${index}" name="symptoms_${index}" value="ไอ" 
                                onchange="toggleConditionElement('cough', ${index}); handleMultipleSelection(this, 'symptoms', ${index})">
                            <label for="coughCheckbox_${index}">ไอ</label>
                        </div>
                        <div id="coughDiv_${index}" class="cough-condition-div" style="display: none;">
                            <label for="coughCondition_${index}">เลือกอาการ</label>
                            <select id="coughCondition_${index}" name="coughCondition_${index}" disabled>
                                <option value="ไอแห้ง">ไอแห้ง</option>
                                <option value="มีเสมหะ">มีเสมหะ</option>
                            </select>
                        </div>
                        <div>อื่นๆ: <textarea name="symptoms_reason_${index}" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea></div>
                    </td>
                </tr>

                <tr>
                    <th class="align-middle" style="min-width: 180px; width: 12%">มียา</th>
                        <td class="medicine">
                            <!-- ไม่มี -->
                            <div>
                                <input type="checkbox" name="medicine_${index}" value="ไม่มี" id="medicineNoneCheckbox_${index}" 
                                    onchange="toggleExclusiveSelection('medicine', ${index}, 'ไม่มี'); toggleConditionElement('medicineNone', ${index});">
                                <label for="medicineNoneCheckbox_${index}">ไม่มี</label>
                            </div>

                            <!-- มียา -->
                            <div>
                                <input type="checkbox" name="medicine_${index}" value="มียา" id="medicineHaveCheckbox_${index}" 
                                    onchange="toggleExclusiveSelection('medicine', ${index}, 'มียา'); toggleConditionElement('medicineHave', ${index});">
                                <label for="medicineHaveCheckbox_${index}">มียา</label>
                                <!-- ซ่อน textarea ตั้งแต่เริ่มต้น -->
                                <div id="medicineHaveDiv_${index}" style="display: none;">
                                    รายละเอียด: 
                                    <textarea name="medicine_reason_${index}" id="medicineHaveInput_${index}" rows="1" class="form-control" 
                                        placeholder="ระบุเพิ่มเติม" disabled></textarea>
                                </div>
                            </div>
                        </td>
                </tr>

                <tr>
                    <th class="align-middle fw-bold" style="min-width: 180px; width: 8%">การเจ็บป่วยอื่นๆ</th>
                        <td class="illness_reason">
                            <div>
                                อื่นๆ: 
                                <textarea name="illness_reason_${index}" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea>
                            </div>
                        </td>
                </tr>

                <tr>
                    <th class="align-middle fw-bold" style="min-width: 180px; width: 8%">การเกิดอุบัติเหตุ/แมลงกัดต่อย</th>
                        <td class="accident_reason">
                            <div>
                                อื่นๆ: 
                                <textarea name="accident_reason_${index}" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea>
                            </div>
                        </td>
                </tr> 
                
                <tr>
                    <th class="align-middle fw-bold" style="min-width: 180px; width: 8%">บันทึกของคุณครูพี่เลี้ยง</th>
                        <td class="teacher_note">
                            <div>
                                อื่นๆ: 
                                <textarea name="teacher_note_${index}" rows="1" class="form-control" placeholder="ระบุเพิ่มเติม"></textarea>
                            </div>
                        </td>
                </tr>

                <tr>
                    <th class="align-middle fw-bold" style="min-width: 180px; width: 8%">ลงชื่อคุณครู/ครูพี่เลี้ยง</th>
                        <td class="teacher_signature">
                            ลงชื่อ: ${child.teacher_first_name} ${child.teacher_last_name}
                        </td>
                </tr>        
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
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

    // ฟังก์ชันจัดการการส่งฟอร์ม
    async function handleFormSubmit(e) {
        e.preventDefault(); // ป้องกันการรีเฟรชหน้า

        const form = e.target; // ดึงฟอร์มที่ถูกส่ง
        const studentId = form.dataset.studentId; // ดึง ID ของเด็กจาก data attribute

        try {
            // ---- เริ่มต้นจำลองการบันทึกข้อมูล ----
            // เปลี่ยนสถานะในแถวของตารางให้เป็น "บันทึกแล้ว"
            const row = document.querySelector(`[data-child-id="${studentId}"]`);
            const statusBadge = row.querySelector('.status-badge');
            statusBadge.className = 'status-badge badge bg-success';
            statusBadge.textContent = 'บันทึกแบบร่างแล้ว';

            // ซ่อนฟอร์มหลังจากกดบันทึก
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
            // ---- จบส่วนจำลอง ----

        } catch (error) {
            // หากมีข้อผิดพลาด แสดง toast alert แจ้งเตือนข้อผิดพลาด
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
                text: 'ไม่สามารถบันทึกข้อมูลได้'
            });
        }
    }

    // ตัวแปรเก็บสถานะของแต่ละคอลัมน์และค่า
    const checkedStates = {};

    // ฟังก์ชันสำหรับเลือก/ยกเลิก checkbox ตามคอลัมน์และค่า
    function toggleCheckboxesByValue(columnClass, value) {
        // ตรวจสอบสถานะปัจจุบันของคอลัมน์และค่า
        if (!checkedStates[columnClass]) {
            checkedStates[columnClass] = {};
        }

        // ถ้ามีการเลือกค่าแล้วในคอลัมน์นี้ ต้องยกเลิกการเลือกค่าอื่น
        const currentlyChecked = Object.keys(checkedStates[columnClass]).filter(key => checkedStates[columnClass][key]);
        if (currentlyChecked.length > 0 && !checkedStates[columnClass][value]) {
            // ถ้ามีค่าอื่นที่ถูกติ๊กแล้วและไม่ใช่ค่าที่เลือกในตอนนี้
            currentlyChecked.forEach(checkedValue => {
                // ยกเลิกการติ๊กค่าที่เลือกก่อนหน้า
                checkedStates[columnClass][checkedValue] = false;
                const checkboxes = document.querySelectorAll(`td.${columnClass} input[type="checkbox"][value="${checkedValue}"]`);
                checkboxes.forEach(checkbox => (checkbox.checked = false));
            });
        }

        // ตั้งค่าสถานะของค่าปัจจุบัน
        checkedStates[columnClass][value] = !checkedStates[columnClass][value]; // สลับสถานะ

        // เลือกหรือยกเลิก checkbox ที่มี value ตรงกันในคอลัมน์ที่ระบุ
        const checkboxes = document.querySelectorAll(`td.${columnClass} input[type="checkbox"][value="${value}"]`);
        checkboxes.forEach(checkbox => (checkbox.checked = checkedStates[columnClass][value]));
    }

    function toggleConditionElement(type, index) {
        const checkbox = document.getElementById(`${type}Checkbox_${index}`);
        const div = document.getElementById(`${type}Div_${index}`);
        const input = document.getElementById(`${type}Input_${index}`);
        const select = document.getElementById(`${type}Condition_${index}`);

        if (checkbox.checked) {
            if (div) div.style.display = 'block';
            if (input) input.disabled = false;
            if (select) select.disabled = false;
        } else {
            if (div) div.style.display = 'none';
            if (input) {
                input.disabled = true;
                input.value = '';
            }
            if (select) {
                select.disabled = true;
                select.value = select.options[0].value; // รีเซ็ตกลับไปค่าแรก
            }
        }
    }

    function toggleExclusiveSelection(groupName, index, value) {
        const checkboxes = document.querySelectorAll(`input[name="${groupName}_${index}"]`);
        const currentCheckbox = document.querySelector(`input[name="${groupName}_${index}"][value="${value}"]`);
        
        // ถ้าเป็นค่าปกติ/สะอาด และถูกติ๊ก
        if ((value === 'ปกติ' || value === 'สะอาด' || value === 'ไม่มี') && currentCheckbox.checked) {
            // ยกเลิกการติ๊กทุกตัวอื่น
            checkboxes.forEach((checkbox) => {
                if (checkbox.value !== value) {
                    checkbox.checked = false;
                    
                    // ถ้ามี input หรือ select ที่เกี่ยวข้อง ให้ปิดการใช้งาน
                    const fieldId = checkbox.value.replace(/\s+/g, '');
                    const relatedInput = document.getElementById(`${fieldId}Input_${index}`);
                    const relatedSelect = document.getElementById(`${fieldId}Condition_${index}`);
                    const relatedDiv = document.getElementById(`${fieldId}Div_${index}`);
                    
                    if (relatedInput) {
                        relatedInput.disabled = true;
                        relatedInput.value = '';
                    }
                    if (relatedSelect) {
                        relatedSelect.disabled = true;
                        relatedSelect.value = relatedSelect.options[0].value;
                    }
                    if (relatedDiv) {
                        relatedDiv.style.display = 'none';
                    }
                }
            });
        } 
        // ถ้าติ๊กตัวเลือกอื่นที่ไม่ใช่ปกติ/สะอาด
        else {
            // ยกเลิกการติ๊กปกติ/สะอาด
            checkboxes.forEach((checkbox) => {
                if (checkbox.value === 'ปกติ' || checkbox.value === 'สะอาด' || checkbox.value === 'ไม่มี') {
                    checkbox.checked = false;
                }
            });
        }
    }

    function handleMultipleSelection(checkbox, groupName, index) {
        const value = checkbox.value;
        
        // ถ้าเป็นการติ๊กค่าปกติ/สะอาด
        if ((value === 'ปกติ' || value === 'สะอาด' || value === 'ไม่มี') && checkbox.checked) {
            toggleExclusiveSelection(groupName, index, value);
        }
        // ถ้าเป็นการติ๊กค่าอื่น
        else {
            const normalCheckbox = document.querySelector(`input[name="${groupName}_${index}"][value="ปกติ"]`);
            const cleanCheckbox = document.querySelector(`input[name="${groupName}_${index}"][value="สะอาด"]`);
            const noneCheckbox = document.querySelector(`input[name="${groupName}_${index}"][value="ไม่มี"]`);
            
            if (normalCheckbox) normalCheckbox.checked = false;
            if (cleanCheckbox) cleanCheckbox.checked = false;
            if (noneCheckbox) noneCheckbox.checked = false;
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
        const formData = [];
        const rows = document.querySelectorAll('[data-child-id]'); // เลือกแถวที่มี data-child-id
        let hasDraftData = false;
        
        rows.forEach((row) => {
            // ตรวจสอบสถานะว่าเป็น "บันทึกแบบร่างแล้ว" หรือไม่
            const statusBadge = row.querySelector('.status-badge');
            if (statusBadge && statusBadge.textContent === 'บันทึกแบบร่างแล้ว') {
                hasDraftData = true;
                const studentId = row.dataset.childId;
                
                // หา form ที่เกี่ยวข้องกับนักเรียนคนนี้
                const form = document.querySelector(`.health-check-form[data-student-id="${studentId}"]`);
                if (!form) return;

                // รวบรวมข้อมูลพื้นฐาน
                const studentData = {
                    student_id: form.querySelector('[name="student_id"]').value,
                    prefix_th: form.querySelector('[name="prefix_th"]').value,
                    first_name_th: form.querySelector('[name="first_name_th"]').value,
                    last_name_th: form.querySelector('[name="last_name_th"]').value,
                    child_group: form.querySelector('[name="child_group"]').value,
                    classroom: form.querySelector('[name="classroom"]').value
                };

                // เก็บข้อมูล checkbox fields
                const checkboxFields = [
                    'hair', 'eye', 'mouth', 'teeth', 'ears', 'nose', 'nails',
                    'skin', 'hands_feet', 'arms_legs', 'body', 'symptoms', 'medicine'
                ];

                checkboxFields.forEach(field => {
                    const fieldElement = form.querySelector(`.${field}`);
                    if (fieldElement) {
                        const checkboxes = fieldElement.querySelectorAll(`input[name^="${field}_"]`);
                        studentData[field] = {
                            checked: Array.from(checkboxes)
                                .filter(cb => cb.checked)
                                .map(cb => cb.value),
                            unchecked: Array.from(checkboxes)
                                .filter(cb => !cb.checked)
                                .map(cb => cb.value)
                        };

                        // เก็บข้อมูล reason fields
                        const reasonTextarea = fieldElement.querySelector(`textarea[name^="${field}_reason"]`);
                        if (reasonTextarea) {
                            studentData[`${field}_reason`] = reasonTextarea.value;
                        }
                    }
                });

                // เก็บข้อมูล condition fields
                const index = Array.from(rows).indexOf(row);
                if (form.querySelector(`#eyeCondition_${index}`)) {
                    studentData.eye_condition = form.querySelector(`#eyeCondition_${index}`).value || null;
                }
                if (form.querySelector(`#noseCondition_${index}`)) {
                    studentData.nose_condition = form.querySelector(`#noseCondition_${index}`).value || null;
                }
                if (form.querySelector(`#teethInput_${index}`)) {
                    const teethCount = form.querySelector(`#teethInput_${index}`).value;
                    studentData.teeth_count = teethCount ? parseInt(teethCount) : null;
                }
                if (form.querySelector(`#feverInput_${index}`)) {
                    const feverTemp = form.querySelector(`#feverInput_${index}`).value;
                    studentData.fever_temp = feverTemp ? parseFloat(feverTemp) : null;
                }
                if (form.querySelector(`#coughCondition_${index}`)) {
                    studentData.cough_type = form.querySelector(`#coughCondition_${index}`).value || null;
                }

                // เก็บข้อมูล detail fields
                if (form.querySelector(`#skinWoundInput_${index}`)) {
                    studentData.skin_wound_detail = form.querySelector(`#skinWoundInput_${index}`).value || null;
                }
                if (form.querySelector(`#skinRashInput_${index}`)) {
                    studentData.skin_rash_detail = form.querySelector(`#skinRashInput_${index}`).value || null;
                }
                if (form.querySelector(`#medicineHaveInput_${index}`)) {
                    studentData.medicine_detail = form.querySelector(`#medicineHaveInput_${index}`).value || null;
                }

                // เก็บข้อมูลเพิ่มเติม
                studentData.illness_reason = form.querySelector('.illness_reason textarea')?.value || null;
                studentData.accident_reason = form.querySelector('.accident_reason textarea')?.value || null;
                studentData.teacher_note = form.querySelector('.teacher_note textarea')?.value || null;
                
                // เก็บลายเซ็นครู
                const teacherSignature = form.closest('tr').querySelector('.teacher_signature');
                if (teacherSignature) {
                    studentData.teacher_signature = teacherSignature.textContent.replace('ลงชื่อ: ', '').trim();
                }

                formData.push(studentData);
            }
        });

        // ตรวจสอบว่ามีข้อมูลแบบร่างหรือไม่
        if (!hasDraftData) {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบข้อมูลแบบร่าง',
                text: 'กรุณาบันทึกแบบร่างอย่างน้อย 1 รายการก่อนบันทึกข้อมูล',
                confirmButtonText: 'ตกลง'
            });
            return false;
        }

        // ส่งข้อมูลไปยัง API
        fetch('../../include/process/save_health_data.php', {
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
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message,
                    confirmButtonText: 'ตกลง'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
                confirmButtonText: 'ตกลง'
            });
        });

        return false;
    }

    // เพิ่มฟังก์ชันสำหรับติ๊กทั้งหมดที่ปกติ/สะอาด
    function markAllNormal(studentId) {
        const form = document.querySelector(`.health-check-form[data-student-id="${studentId}"]`);
        if (!form) return;

        // รายการ fields ที่จะติ๊ก "ปกติ" หรือ "สะอาด"
        const normalFields = {
            'hair': 'สะอาด',
            'eye': 'ปกติ',
            'mouth': 'สะอาด',
            'teeth': 'สะอาด',
            'ears': 'สะอาด',
            'nose': 'สะอาด',
            'nails': 'สะอาด',
            'skin': 'สะอาด',
            'hands_feet': 'ปกติ',
            'arms_legs': 'ปกติ',
            'body': 'ปกติ',
            'symptoms': 'ไม่มี',
            'medicine': 'ไม่มี'
        };

        // ติ๊กทุก checkbox ที่เป็นค่าปกติ
        Object.entries(normalFields).forEach(([field, value]) => {
            const checkbox = form.querySelector(`input[name^="${field}_"][value="${value}"]`);
            if (checkbox) {
                checkbox.checked = true;
                // Trigger events
                const event = new Event('change');
                checkbox.dispatchEvent(event);
            }
        });
    }

    // เพิ่มฟังก์ชันล้างการติ๊กทั้งหมด
    function clearAllChecks(studentId) {
        const form = document.querySelector(`.health-check-form[data-student-id="${studentId}"]`);
        if (!form) return;

        // ล้างการติ๊กทุก checkbox
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
            // Trigger change event
            const event = new Event('change');
            checkbox.dispatchEvent(event);
        });

        // ล้างค่า input และ textarea
        form.querySelectorAll('input[type="text"], input[type="number"], textarea').forEach(input => {
            input.value = '';
        });

        // รีเซ็ต select กลับไปค่าเริ่มต้น
        form.querySelectorAll('select').forEach(select => {
            select.value = select.options[0].value;
        });
    }

    // เพิ่ม CSS สำหรับ hover effect
    const style = document.createElement('style');
    style.textContent = `
        .child-name:hover {
            color: #0d6efd;
            text-decoration: underline;
        }
        .table-active {
            background-color: #f8f9fa;
        }
        .details-row {
            transition: all 0.3s ease;
        }
        
        /* เพิ่ม hover effect สำหรับ checkbox */
        input[type="checkbox"] {
            cursor: pointer;
        }
        input[type="checkbox"]:hover + label {
            color: #0d6efd;
        }
        
        /* ทำให้ label คลิกได้ง่ายขึ้น */
        label {
            cursor: pointer;
            padding: 5px;
            margin: -5px;
            border-radius: 4px;
        }
        label:hover {
            background-color: #f8f9fa;
        }
    `;
    document.head.appendChild(style);

</script>


</html>