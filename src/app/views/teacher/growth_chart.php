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
        function clearSwalContainers() {
            // ลบ container ทั้งหมด
            const containers = document.querySelectorAll('.swal2-container');
            containers.forEach(container => {
                // ปิด popup ก่อน
                const popup = container.querySelector('.swal2-popup');
                if (popup) {
                    popup.style.display = 'none';
                }
                // ซ่อน container
                container.style.display = 'none';
                // ลบ container
                container.remove();
            });

            // ลบ class และ style จาก body
            const body = document.body;
            body.classList.remove(
                'swal2-shown',
                'swal2-height-auto',
                'swal2-toast-shown',
                'swal2-has-input',
                'swal2-backdrop-show'
            );
            body.style.removeProperty('padding-right');
            body.style.removeProperty('overflow');

            // ลบ backdrop ถ้ามี
            const backdrop = document.querySelector('.swal2-backdrop-show');
            if (backdrop) {
                backdrop.remove();
            }

            // เคลียร์ timeout ที่อาจค้างอยู่
            const timeoutIds = window.swalCloseTimeouts || [];
            timeoutIds.forEach(id => clearTimeout(id));
            window.swalCloseTimeouts = [];
        }

        // เรียกใช้ฟังก์ชันเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', clearSwalContainers);
        // เรียกใช้เมื่อมีการเปลี่ยนหน้า
        window.addEventListener('popstate', clearSwalContainers);
        // เรียกใช้เมื่อกำลังจะออกจากหน้า
        window.addEventListener('beforeunload', clearSwalContainers);
        // เรียกใช้หลังจากโหลดหน้าเสร็จ
        window.addEventListener('load', () => {
            setTimeout(clearSwalContainers, 100);
        });
    </script>
<?php endif; ?>

<main class="main-content">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">บันทึกข้อมูลเพื่อพล็อตกราฟมาตรฐานการเจริญเติบโตของเด็ก</h3>
                </div>
                <div class="card-body">
                    <!-- แก้ไขส่วนของการเลือกข้อมูล -->
                    <div class="row mb-3">
                        <!-- ตัวเลือกครู -->
                        <input type="hidden" id="teacher_id" value="<?php echo $teacher_id; ?>">
                        
                        <!-- เลือกกลุ่มเรียน -->
                        <div class="col-md-2">
                            <label for="child_group" class="form-label fw-bold">กลุ่มเรียน</label>
                            <select name="child_group" id="child_group" class="form-select" onchange="loadClassrooms()">
                                <option value="">-- เลือกกลุ่มเรียน --</option>
                                <?php
                                if ($is_admin) {
                                    $groups = getAllChildGroups();
                                } else {
                                    $groups = getChildGroups($teacher_id);
                                }
                                foreach ($groups as $group) {
                                    if (!empty($group['child_group'])) {
                                        echo "<option value='" . $group['child_group'] . "'>" . $group['child_group'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- เลือกห้องเรียน -->
                        <div class="col-md-2">
                            <label for="classroom" class="form-label">ห้องเรียน</label>
                            <select name="classroom" id="classroom" class="form-select" onchange="getChildren()">
                                <option value="">-- เลือกห้องเรียน --</option>
                            </select>
                        </div>

                        <!-- เลือกเพศ -->
                        <div class="col-md-2">
                            <label for="sex" class="form-label">เพศ</label>
                            <select name="sex" id="sex" class="form-select" onchange="getChildren()">
                                <option value="">ทั้งหมด</option>
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                            </select>
                        </div>

                        <!-- เลือกช่วงอายุ -->
                        <div class="col-md-2">
                            <label for="age_range" class="form-label">ช่วงอายุ</label>
                            <select class="form-select" id="age_range" onchange="updateAgeInputLimits()">
                                <option value="">เลือกช่วงอายุ</option>
                                <option value="0-2">0-2 ปี</option>
                                <option value="2-5">2-5 ปี</option>
                            </select>
                        </div>
                    </div>

                    <!-- ย้าย header section มาไว้ตรงนี้ -->
                    <div id="headerSection"></div>

                    <!-- รายชื่อเด็ก -->
                    <div class="mb-3">
                        <label class="form-label">ตารางบันทึกข้อมูลเพื่อพล็อตกราฟมาตรฐานการเจริญเติบโตของเด็ก</label>
                        <div id="childrenList" class="container">
                            <!-- ตารางจะแสดงผลที่นี่ -->
                        </div>
                    </div>

                    <!-- ส่วนแสดงผลการประเมิน -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info" id="growthStatus" style="display: none;">
                                ภาวะการเจริญเติบโต: <span id="growthStatusText"></span>
                            </div>
                        </div>
                    </div>

                    <!-- เพิ่มส่วนปุ่มบันทึกข้อมูลด้านล่างตาราง -->
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-success" onclick="saveAllGrowthData()">
                                <i class="bi bi-save"></i> บันทึกข้อมูลทั้งหมด
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
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
    
    // จัดการ URL parameters สำหรับการเลือกกลุ่มเรียนและห้องเรียน
    if (urlParams.get('child_group')) {
        document.getElementById('child_group').value = urlParams.get('child_group');
        loadClassrooms();
    }

    // จัดการ URL parameters สำหรับการเลือกเพศและช่วงอายุ
    if (urlParams.get('sex')) {
        document.getElementById('sex').value = urlParams.get('sex');
    }
    if (urlParams.get('age_range')) {
        document.getElementById('age_range').value = urlParams.get('age_range');
        updateAgeInputLimits(); // อัพเดทข้อจำกัดของช่องกรอกอายุ
    }

    // จัดการการแก้ไขข้อมูล (ถ้ามี)
    const editId = urlParams.get('edit_id');
    const studentId = urlParams.get('student_id');
    if (editId && studentId) {
        loadEditData(editId, studentId);
    }
});

// แก้ไขฟังก์ชัน getChildren() ให้ใช้ fetch แทน XMLHttpRequest
function getChildren() {
    const teacherId = document.getElementById('teacher_id').value;
    const childGroup = document.getElementById('child_group').value;
    const classroom = document.getElementById('classroom').value;
    const gender = document.getElementById('sex').value;

    if (childGroup && classroom && teacherId) {
        let url = '../../include/function/get_children_by_class.php?child_group=' + childGroup + 
                 '&classroom=' + classroom + 
                 '&teacher_id=' + teacherId;
        
        if (gender) {
            url += '&gender=' + gender;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                displayChildrenTable(data);
            })
            .catch(error => console.error('Error:', error));
    }
}

// แก้ไขฟังก์ชัน displayChildrenTable
function displayChildrenTable(children) {
    console.log('Received children data:', children); // เพิ่ม debug log
    
    const tableContainer = document.getElementById('childrenList');
    if (!children || !children.children || children.children.length === 0) {
        tableContainer.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูลเด็ก</div>';
        return;
    }

    let html = `
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ชื่อเล่น</th>
                    <th>กลุ่มเรียน</th>
                    <th>ห้องเรียน</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
    `;

    children.children.forEach((child, index) => {
        html += `
            <tr class="child-row" data-child-id="${child.studentid}" data-sex="${child.sex || ''}">
                <td>${index + 1}</td>
                <td class="child-name" style="cursor: pointer;" onclick="toggleDetails('${child.studentid}')">
                    ${child.prefix_th}${child.firstname_th} ${child.lastname_th}
                </td>
                <td>${child.nickname || '-'}</td>
                <td>${child.child_group || '-'}</td>
                <td>${child.classroom || '-'}</td>
                <td>
                    <span class="status-badge badge bg-danger">ยังไม่บันทึก</span>
                </td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="toggleDetails('${child.studentid}')">
                        <i class="bi bi-pencil"></i> บันทึกข้อมูล
                    </button>
                </td>
            </tr>
            <tr id="details-${child.studentid}" class="d-none">
                <td colspan="7">
                    <div class="card mb-3">
                        <div class="card-body">
                            <form class="growth-form" data-student-id="${child.studentid}">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label">อายุ (ปี)</label>
                                        <input type="number" class="form-control" name="age_year" min="0" max="6" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">อายุ (เดือน)</label>
                                        <input type="number" class="form-control" name="age_month" min="0" max="11" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">อายุ (วัน)</label>
                                        <input type="number" class="form-control" name="age_day" min="0" max="31" required>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label class="form-label">น้ำหนัก (กก.)</label>
                                        <input type="number" class="form-control" name="weight" step="0.1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">ส่วนสูง (ซม.)</label>
                                        <input type="number" class="form-control" name="height" step="0.1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">เส้นรอบศีรษะ (ซม.)</label>
                                        <input type="number" class="form-control" name="head_circumference" step="0.1" required>
                                    </div>
                                </div>

                                <!-- เพิ่มช่องพัฒนาการทั้ง 5 ด้าน -->
                                <div class="row mt-3">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-bold">การเคลื่อนไหว (GM)</label>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="gm_pass" id="gm_pass">
                                            <label class="form-check-label" for="gm_pass">ผ่าน</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="gm_delay" id="gm_delay">
                                            <label class="form-check-label" for="gm_delay">สงสัยล่าช้า</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="number" class="form-control form-control-sm" name="gm_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-bold">กล้ามเนื้อมัดเล็กและสติปัญญา (FM)</label>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="fm_pass" id="fm_pass">
                                            <label class="form-check-label" for="fm_pass">ผ่าน</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="fm_delay" id="fm_delay">
                                            <label class="form-check-label" for="fm_delay">สงสัยล่าช้า</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="number" class="form-control form-control-sm" name="fm_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-bold">ความเข้าใจภาษา (RL)</label>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="rl_pass" id="rl_pass">
                                            <label class="form-check-label" for="rl_pass">ผ่าน</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="rl_delay" id="rl_delay">
                                            <label class="form-check-label" for="rl_delay">สงสัยล่าช้า</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="number" class="form-control form-control-sm" name="rl_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-bold">การใช้ภาษา (EL)</label>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="el_pass" id="el_pass">
                                            <label class="form-check-label" for="el_pass">ผ่าน</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="el_delay" id="el_delay">
                                            <label class="form-check-label" for="el_delay">สงสัยล่าช้า</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="number" class="form-control form-control-sm" name="el_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-bold">การช่วยเหลือตัวเองและสังคม (PS)</label>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="ps_pass" id="ps_pass">
                                            <label class="form-check-label" for="ps_pass">ผ่าน</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" name="ps_delay" id="ps_delay">
                                            <label class="form-check-label" for="ps_delay">สงสัยล่าช้า</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="number" class="form-control form-control-sm" name="ps_issue" style="width: 80px;" min="1" placeholder="ข้อที่">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="growth-status alert" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleDetails('${child.studentid}')">
                                        ยกเลิก
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="handleDraftSave(event)">
                                        บันทึกแบบร่าง
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    tableContainer.innerHTML = html;
    
    // Setup handlers หลังจากเพิ่ม HTML
    const forms = document.querySelectorAll('form[data-student-id]');
    forms.forEach(form => {
        setupCheckboxHandlers(form);
    });
    
    // อัพเดท header section
    updateHeaderSection();
    
    // อัพเดทข้อจำกัดช่วงอายุ
    updateAgeInputLimits();
}

// เพิ่มฟังก์ชันจัดการ checkbox ให้ทำงานเหมือน radio
function handleCheckboxChange(name, form) {
    const pass = form.querySelector(`input[name="${name}_pass"]`);
    const delay = form.querySelector(`input[name="${name}_delay"]`);
    
    pass.addEventListener('change', function() {
        if (this.checked) {
            delay.checked = false;
        }
    });
    
    delay.addEventListener('change', function() {
        if (this.checked) {
            pass.checked = false;
        }
    });
}

// เพิ่มฟังก์ชันสำหรับเพิ่ม event listeners ให้กับทุกฟอร์ม
function setupCheckboxHandlers(form) {
    // เพิ่ม handlers สำหรับแต่ละหมวด
    handleCheckboxChange('gm', form);
    handleCheckboxChange('fm', form);
    handleCheckboxChange('rl', form);
    handleCheckboxChange('el', form);
    handleCheckboxChange('ps', form);
}

function resetForm() {
    // รีเซ็ตค่าฟอร์มกลับไปเป็นค่าเริ่มต้น
    document.getElementById('growth-form').reset();

    // ซ่อนองค์ประกอบเพิ่มเติม
    document.getElementById('childrenList').innerHTML = ''; // ล้างตารางรายชื่อเด็ก
    document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>'; // ล้างตัวเลือกห้องเรียน
}

// ฟังก์ชันสำหรับ toggle การแสดงรายละเอียด
    function toggleDetails(studentId) {
        const detailsRow = document.getElementById(`details-${studentId}`);
        detailsRow.classList.toggle('d-none');
    }
    
// เพิ่มฟังก์ชันใหม่สำหรับจัดการการแสดง/ซ่อนฟิลด์ในแต่ละฟอร์ม
function toggleMeasurementInputs(selectElement, studentId) {
    const form = selectElement.closest('form');
    const type = selectElement.value;
    
    // ซ่อนทุก input ก่อน
    form.querySelectorAll('.measurement-input').forEach(input => {
        input.style.display = 'none';
        input.querySelector('input').required = false;
    });
    
    // แสดง input ตามประเภทที่เลือก
    if (type) {
        if (type === 'height') {
            // กรณีน้ำหนักตามเกณฑ์ส่วนสูง แสดงทั้งสองช่อง
            const weightInput = form.querySelector('.weight-input');
            const heightInput = form.querySelector('.height-input');
            if (weightInput) {
                weightInput.style.display = 'block';
                weightInput.querySelector('input').required = true;
            }
            if (heightInput) {
                heightInput.style.display = 'block';
                heightInput.querySelector('input').required = true;
            }
        } else {
            // กรณีอื่นๆ แสดงเฉพาะช่องที่เลือก
            const targetInput = form.querySelector(`.${type}-input`);
            if (targetInput) {
                targetInput.style.display = 'block';
                targetInput.querySelector('input').required = true;
            }
        }
    }
}

// เพิ่มฟังก์ชันใหม่สำหรับจำกัดช่วงอายุ
function updateAgeInputLimits() {
    const ageRange = document.getElementById('age_range').value;
    const forms = document.querySelectorAll('.growth-form');
    
    forms.forEach(form => {
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
    });
}

// แก้ไขฟังก์ชัน handleDraftSave
async function handleDraftSave(e) {
    const form = e.target.closest('form');
    const studentId = form.dataset.studentId;
    const ageRange = document.getElementById('age_range').value;
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!ageRange || !form.checkValidity()) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณากรอกข้อมูลให้ครบถ้วน',
            text: 'กรุณาเลือกช่วงอายุ และกรอกข้อมูลที่จำเป็น'
        });
        return;
    }

    try {
        // อัพเดทสถานะในตารางโดยตรง
        const row = document.querySelector(`[data-child-id="${studentId}"]`);
        const statusBadge = row.querySelector('.status-badge');
        statusBadge.className = 'status-badge badge bg-warning';
        statusBadge.textContent = 'บันทึกแบบร่าง';
        
        // แสดงผลการประเมินทั้งหมด
        const weight = form.querySelector('input[name="weight"]').value;
        const height = form.querySelector('input[name="height"]').value;
        const head = form.querySelector('input[name="head_circumference"]').value;

        const growthStatusElement = form.querySelector('.growth-status');
        growthStatusElement.className = 'growth-status alert alert-warning';
        growthStatusElement.style.display = 'block';
        growthStatusElement.innerHTML = `
            <p><strong>ผลการประเมินเบื้องต้น:</strong></p>
            <p>น้ำหนักตามเกณฑ์อายุ: ${evaluateGrowthStatus('weight', weight)}</p>
            <p>ส่วนสูงตามเกณฑ์อายุ: ${evaluateGrowthStatus('height_age', height)}</p>
            <p>น้ำหนักตามเกณฑ์ส่วนสูง: ${evaluateGrowthStatus('weight_height', weight, height)}</p>
            <p>เส้นรอบศีรษะตามเกณฑ์อายุ: ${evaluateGrowthStatus('head', head)}</p>
        `;

        // เก็บค่าช่วงอายุไว้ใน hidden input
        let ageRangeInput = form.querySelector('input[name="age_range"]');
        if (!ageRangeInput) {
            ageRangeInput = document.createElement('input');
            ageRangeInput.type = 'hidden';
            ageRangeInput.name = 'age_range';
            form.appendChild(ageRangeInput);
        }
        ageRangeInput.value = ageRange;

        // แสดง toast alert
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
            text: error.message
        });
    }
}

// แก้ไขฟังก์ชัน evaluateGrowthStatus
function evaluateGrowthStatus(type, value1, value2 = null) {
    if (!value1) return 'ไม่สามารถประเมินได้';
    
    value1 = parseFloat(value1);
    
    switch(type) {
        case 'weight':
            if (value1 < 10) return 'น้ำหนักน้อยกว่าเกณฑ์';
            if (value1 < 12) return 'น้ำหนักค่อนข้างน้อย';
            if (value1 < 15) return 'น้ำหนักตามเกณฑ์';
            if (value1 < 17) return 'น้ำหนักค่อนข้างมาก';
            return 'น้ำหนักมากเกินเกณฑ์';
        
        case 'height_age':
            if (value1 < 90) return 'เตี้ย';
            if (value1 < 95) return 'ค่อนข้างเตี้ย';
            if (value1 < 110) return 'ส่วนสูงตามเกณฑ์';
            if (value1 < 115) return 'ค่อนข้างสูง';
            return 'สูง';
            
        case 'weight_height':
            if (!value2) return 'ไม่สามารถประเมินได้';
            value2 = parseFloat(value2);
            const bmi = value1 / ((value2/100) * (value2/100));
            if (bmi < 16) return 'ผอม';
            if (bmi < 17) return 'ค่อนข้างผอม';
            if (bmi < 23) return 'สมส่วน';
            if (bmi < 25) return 'ท้วม';
            return 'อ้วน';
        
        case 'head':
            if (value1 < 40) return 'น้อยกว่าเปอร์เซ็นไทล์ที่ 3';
            if (value1 < 42) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 3 - 15';
            if (value1 < 44) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 15 - 50';
            if (value1 < 46) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 50 - 85';
            if (value1 < 48) return 'อยู่ระหว่างเปอร์เซ็นไทล์ที่ 85 - 97';
            return 'มากกว่าเปอร์เซ็นไทล์ที่ 97';
    }
    return 'ไม่สามารถประเมินได้';
}

// แก้ไขฟังก์ชัน handleFormSubmit สำหรับการบันทึกข้อมูลจริง
async function handleFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const studentId = form.dataset.studentId;
    
    try {
        const formData = new FormData(form);
        formData.append('student_id', studentId);
        formData.append('is_draft', '0');
        formData.append('age_range', document.getElementById('age_range').value);
        formData.append('child_group', document.getElementById('child_group').value);
        formData.append('sex', document.getElementById('sex').value);

        // เพิ่มการเก็บข้อมูลพัฒนาการ 5 ด้าน
        const developmentFields = ['gm', 'fm', 'rl', 'el', 'ps'];
        developmentFields.forEach(field => {
            // เก็บค่า checkbox pass และ delay
            const passCheckbox = form.querySelector(`input[name="${field}_pass"]`);
            const delayCheckbox = form.querySelector(`input[name="${field}_delay"]`);
            formData.set(`${field}_pass`, passCheckbox.checked ? '1' : '0');
            formData.set(`${field}_delay`, delayCheckbox.checked ? '1' : '0');
            
            // เก็บค่า issue ถ้ามี
            const issueInput = form.querySelector(`input[name="${field}_issue"]`);
            if (issueInput && issueInput.value) {
                formData.set(`${field}_issue`, issueInput.value);
            }
        });
        
        const response = await fetch('../../include/process/save_growth_data.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            // อัพเดทสถานะในตาราง
            const row = document.querySelector(`[data-child-id="${studentId}"]`);
            const statusBadge = row.querySelector('.status-badge');
            statusBadge.className = 'status-badge badge bg-success';
            statusBadge.textContent = 'บันทึกแล้ว';
            
            // แสดงผลการประเมิน
            const growthStatus = form.querySelector('.growth-status');
            growthStatus.className = 'growth-status alert alert-info';
            growthStatus.style.display = 'block';
            growthStatus.textContent = `ภาวะการเจริญเติบโต: ${result.growth_status}`;
            
            // ซ่อนฟอร์ม
            toggleDetails(studentId);
            
            // แสดง toast แจ้งเตือนสำเร็จ
            Swal.fire({
                icon: 'success',
                title: 'บันทึกข้อมูลสำเร็จ',
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: error.message
        });
    }
}

// แก้ไขฟังก์ชัน saveAllGrowthData
async function saveAllGrowthData() {
    // หาฟอร์มทั้งหมดที่มีสถานะ "บันทึกแบบร่าง"
    const draftRows = document.querySelectorAll('.child-row');
    const draftForms = [];
    
    draftRows.forEach(row => {
        const statusBadge = row.querySelector('.status-badge');
        if (statusBadge.textContent === 'บันทึกแบบร่าง') {
            const studentId = row.dataset.childId;
            const form = document.querySelector(`#details-${studentId} .growth-form`);
            if (form) {
                draftForms.push({
                    form: form,
                    studentId: studentId,
                    studentName: row.querySelector('.child-name').textContent.trim()
                });
            }
        }
    });

    if (draftForms.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'ไม่พบข้อมูลที่ต้องบันทึก',
            text: 'กรุณาบันทึกแบบร่างอย่างน้อย 1 รายการก่อนบันทึกข้อมูล'
        });
        return;
    }

    // แสดง confirmation dialog
    const confirmResult = await Swal.fire({
        title: 'ยืนยันการบันทึกข้อมูล',
        html: `
            <p>ต้องการบันทึกข้อมูลทั้งหมด ${draftForms.length} รายการใช่หรือไม่?</p>
            <small class="text-danger">หมายเหตุ: หากมีการบันทึกข้อมูลแล้วในวันนี้ จะไม่สามารถบันทึกซ้ำได้</small>
            <div class="mt-3 text-left">
                <p><strong>รายชื่อที่จะบันทึก:</strong></p>
                ${draftForms.map((item, index) => `
                    <p class="mb-1">${index + 1}. ${item.studentName}</p>
                `).join('')}
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545'
    });

    if (!confirmResult.isConfirmed) return;

    try {
        // แสดง loading
        Swal.fire({
            title: 'กำลังบันทึกข้อมูล',
            text: 'กรุณารอสักครู่...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const results = await Promise.all(draftForms.map(async ({ form, studentId }) => {
            const formData = new FormData(form);
            formData.append('is_draft', '0');
            formData.append('student_id', studentId);
            formData.append('age_range', document.getElementById('age_range').value);
            formData.append('child_group', document.getElementById('child_group').value);
            formData.append('sex', document.getElementById('sex').value);
            
            // เพิ่มการเก็บข้อมูลพัฒนาการ 5 ด้าน
            const developmentFields = ['gm', 'fm', 'rl', 'el', 'ps'];
            developmentFields.forEach(field => {
                // เก็บค่า checkbox pass และ delay
                const passCheckbox = form.querySelector(`input[name="${field}_pass"]`);
                const delayCheckbox = form.querySelector(`input[name="${field}_delay"]`);
                formData.set(`${field}_pass`, passCheckbox.checked ? '1' : '0');
                formData.set(`${field}_delay`, delayCheckbox.checked ? '1' : '0');
                
                // เก็บค่า issue ถ้ามี
                const issueInput = form.querySelector(`input[name="${field}_issue"]`);
                if (issueInput && issueInput.value) {
                    formData.set(`${field}_issue`, issueInput.value);
                }
            });
            
            const response = await fetch('../../include/process/save_growth_data.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            return {
                result,
                studentId,
                studentName: draftForms.find(f => f.studentId === studentId).studentName
            };
        }));

        // ปิด loading
        Swal.close();

        // ตรวจสอบผลลัพธ์และอัพเดทสถานะ
        let successCount = 0;
        let errorMessages = [];

        results.forEach(({ result, studentId, studentName }) => {
            if (result.status === 'success') {
                successCount++;
                const row = document.querySelector(`[data-child-id="${studentId}"]`);
                const statusBadge = row.querySelector('.status-badge');
                statusBadge.className = 'status-badge badge bg-success';
                statusBadge.textContent = 'บันทึกแล้ว';
                toggleDetails(studentId);
            } else {
                errorMessages.push(`- ${studentName}: ${result.message}`);
            }
        });

        // แสดงผลการบันทึก
        if (errorMessages.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'บันทึกข้อมูลเสร็จสิ้น',
                html: `
                    <p>บันทึกสำเร็จ ${successCount} รายการ จากทั้งหมด ${draftForms.length} รายการ</p>
                    <div class="mt-3">
                        <strong>รายการที่ไม่สำเร็จ:</strong><br>
                        ${errorMessages.join('<br>')}
                    </div>
                `,
                confirmButtonText: 'ตกลง'
            });
        } else {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกข้อมูลสำเร็จ',
                text: `บันทึกสำเร็จทั้งหมด ${successCount} รายการ`,
                showConfirmButton: false,
                timer: 2000
            });
        }

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: error.message
        });
    }
}

// แก้ไขฟังก์ชัน updateHeaderSection
function updateHeaderSection() {
    const childGroup = document.getElementById('child_group').value || '-';
    const classroom = document.getElementById('classroom').value || '-';
    const gender = document.getElementById('sex').value || 'ทั้งหมด';
    const ageRange = document.getElementById('age_range');
    
    const ageRangeText = ageRange.options[ageRange.selectedIndex]?.text || '-';
    
    const headerHtml = `
        <div class="alert alert-info mb-3">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-3">รายละเอียดการเลือก:</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1"><strong>กลุ่มเรียน:</strong> ${childGroup}</p>
                            <p class="mb-1"><strong>ห้องเรียน:</strong> ${classroom}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>เพศ:</strong> ${gender}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>ช่วงอายุ:</strong> ${ageRangeText}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const headerSection = document.getElementById('headerSection');
    if (headerSection) {
        headerSection.innerHTML = headerHtml;
    }
}

// เพิ่ม event listeners สำหรับการเปลี่ยนแปลงตัวเลือกต่างๆ
document.addEventListener('DOMContentLoaded', function() {
    const selectors = ['measurement_type', 'age_range', 'sex'];
    selectors.forEach(id => {
        document.getElementById(id)?.addEventListener('change', updateHeaderSection);
    });

    // ตรวจสอบว่ามีการส่ง edit_id มาหรือไม่
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit_id');
    const studentId = urlParams.get('student_id');
    
    if (editId && studentId) {
        // ดึงข้อมูลเดิมมาแสดงในฟอร์ม
        fetch(`../../include/process/get_growth_status.php?record_id=${editId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const record = data.record;
                    // เติมข้อมูลลงในฟอร์ม
                    const form = document.querySelector(`form[data-student-id="${studentId}"]`);
                    if (form) {
                        form.querySelector('input[name="weight"]').value = record.weight;
                        form.querySelector('input[name="height"]').value = record.height;
                        form.querySelector('input[name="head_circumference"]').value = record.head_circumference;
                        form.querySelector('input[name="age_year"]').value = record.age_year;
                        form.querySelector('input[name="age_month"]').value = record.age_month;
                        form.querySelector('input[name="age_day"]').value = record.age_day;
                        
                        // เปิดฟอร์มอัตโนมัติ
                        toggleDetails(studentId);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }
});

function getHeadCircumferencePercentile(ageMonths, gender) {
    // ตัวอย่างข้อมูลเกณฑ์วัด
    const percentiles = {
        female: {
            3: [32, 34, 36, 38, 40],
            6: [34, 36, 38, 40, 42],
            12: [36, 38, 40, 42, 44],
            // เพิ่มข้อมูลตามต้องการ
        },
        male: {
            3: [33, 35, 37, 39, 41],
            6: [35, 37, 39, 41, 43],
            12: [37, 39, 41, 43, 45],
            // เพิ่มข้อมูลตามต้องการ
        }
    };

    const ageGroup = Math.floor(ageMonths / 6) * 6; // จัดกลุ่มอายุเป็นช่วง 6 เดือน
    return percentiles[gender][ageGroup] || [];
}

</script>

    
</body>

</html>