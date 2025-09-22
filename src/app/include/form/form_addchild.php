<!-- ฟอร์มเพิ่มข้อมูลเด็ก -->
<div class="modal-body">
    <form id="addChildForm" method="post" action="../../include/process/process_add_child.php" enctype="multipart/form-data" class="child-form-modal">
        <!-- รูปโปรไฟล์ -->
        <div class="form-section mb-4">
            <h3 class="section-title">
                <i class="bi bi-person-badge"></i>
                รูปโปรไฟล์
            </h3>
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div class="profile-image-container mb-3">
                        <img id="preview_image" src="../../../public/assets/images/avatar.png" 
                             class="profile-preview rounded-circle">
                        <div class="profile-image-overlay">
                            <i class="bi bi-camera"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">อัพโหลดรูปโปรไฟล์:</label>
                        <input type="file" class="form-control" id="profile_image" 
                               accept="image/*" onchange="handleImageSelect(this)">
                        <input type="hidden" name="profile_image_data" id="profile_image_data">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i>
                            รองรับไฟล์ภาพ (jpg, jpeg, png) ขนาดไม่เกิน 5MB
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ข้อมูลการเรียน -->
        <div class="form-section mb-4">
            <h3 class="section-title">
                <i class="bi bi-book"></i>
                ข้อมูลการเรียน
            </h3>
            <div class="row">
                <!-- รหัสนักเรียน -->
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">รหัสนักเรียน:</label>
                        <input type="text" class="form-control" name="student_id" id="student_id" 
                               placeholder="กรุณากรอกรหัสนักเรียน" required>
                    </div>
                </div>
                <!-- ปีการศึกษา -->
                <div class="col-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="academic_year" class="form-label mb-0">ปีการศึกษา:</label>
                        <button type="button" class="btn btn-sm btn-primary" onclick="openAcademicYearManager()">
                            <i class="bi bi-gear-fill"></i> จัดการปีการศึกษา
                        </button>
                    </div>
                    <select name="academic_year" id="academic_year" class="form-select" required>
                        <option value="" disabled selected>กรุณาเลือกปีการศึกษา</option>
                    </select>
                </div>
                <!-- กลุ่มเด็ก -->
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="child_group" class="form-label">กลุ่มเด็ก:</label>
                        <select name="child_group" id="child_group" class="form-select" onchange="loadClassrooms()" required>
                            <option value="" disabled selected>กรุณาเลือกกลุ่มเด็ก</option>
                            <option value="เด็กกลาง">เด็กกลาง</option>
                            <option value="เด็กโต">เด็กโต</option>
                            <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
                        </select>
                    </div>
                </div>
                <!-- ห้องเรียน -->
                <div class="col-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="classroom" class="form-label mb-0">ห้องเรียน:</label>
                        <button type="button" class="btn btn-sm btn-primary" onclick="openClassroomManager()">
                            <i class="bi bi-gear-fill"></i> จัดการห้องเรียน
                        </button>
                    </div>
                    <select name="classroom" id="classroom" class="form-select" required>
                        <option value="" disabled selected>กรุณาเลือกห้องเรียน</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ข้อมูลส่วนตัวและผู้ปกครอง -->
        <div class="row">
            <!-- ข้อมูลส่วนตัว -->
            <div class="col-md-6">
                <div class="form-section h-100">
                    <h3 class="section-title">
                        <i class="bi bi-person-circle"></i>
                        ข้อมูลส่วนตัว
                    </h3>
                    <!-- หมายเลขบัตรประชาชน -->
                    <div class="mb-3">
                        <label for="id_card" class="form-label">หมายเลขบัตรประชาชน (ถ้ามี):</label>
                        <input type="text" class="form-control" name="id_card" placeholder="กรุณากรอกหมายเลขบัตรประชาชน">
                    </div>

                    <!-- ชื่อเล่น -->
                    <div class="mb-3">
                        <label for="nickname" class="form-label">ชื่อเล่น:</label>
                        <input type="text" class="form-control" name="nickname" placeholder="กรุณากรอกชื่อเล่น" required>
                    </div>

                    <div class="row">
                        <!-- คำนำหน้าชื่อ (ภาษาไทย) -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="prefix_th" class="form-label">คำนำหน้าชื่อ (ไทย):</label>
                                <select name="prefix_th" class="form-select" required>
                                    <option value="">เลือกคำนำหน้าชื่อ</option>
                                    <option value="เด็กชาย">เด็กชาย</option>
                                    <option value="เด็กหญิง">เด็กหญิง</option>
                                </select>
                            </div>
                        </div>

                        <!-- ชื่อและนามสกุล ภาษาไทย -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="first_name_th" class="form-label">ชื่อ (ไทย):</label>
                                <input type="text" class="form-control" name="first_name_th" placeholder="กรุณากรอกชื่อ" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="last_name_th" class="form-label">นามสกุล (ไทย):</label>
                                <input type="text" class="form-control" name="last_name_th" placeholder="กรุณากรอกนามสกุล" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- คำนำหน้าชื่อ (ภาษาอังกฤษ) -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="prefix_en" class="form-label">คำนำหน้าชื่อ (อังกฤษ):</label>
                                <select name="prefix_en" class="form-select">
                                    <option value="">Select prefix</option>
                                    <option value="Mr.">Mr.</option>
                                    <option value="Ms.">Ms.</option>
                                </select>
                            </div>
                        </div>

                        <!-- ชื่อและนามสกุล ภาษาอังกฤษ -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="first_name_en" class="form-label">ชื่อ (อังกฤษ):</label>
                                <input type="text" class="form-control" name="first_name_en" placeholder="กรุณากรอกชื่อ">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="last_name_en" class="form-label">นามสกุล (อังกฤษ):</label>
                                <input type="text" class="form-control" name="last_name_en" placeholder="กรุณากรอกนามสกุล">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ข้อมูลผู้ปกครอง -->
            <div class="col-md-6">
                <div class="form-section h-100">
                    <h3 class="section-title">
                        <i class="bi bi-people"></i>
                        ข้อมูลผู้ปกครอง
                    </h3>
                    <!-- ข้อมูลบิดา -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="father_first_name" class="form-label">ชื่อบิดา:</label>
                                <input type="text" class="form-control" name="father_first_name" placeholder="กรุณากรอกชื่อบิดา" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="father_last_name" class="form-label">นามสกุลบิดา:</label>
                                <input type="text" class="form-control" name="father_last_name" placeholder="กรุณากรอกนามสกุลบิดา" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="father_phone" class="form-label">เบอร์โทรบิดา:</label>
                                <input type="text" class="form-control" name="father_phone" placeholder="กรุณากรอกเบอร์โทรบิดา" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="father_phone_backup" class="form-label">เบอร์โทรสำรองบิดา:</label>
                                <input type="text" class="form-control" name="father_phone_backup" placeholder="กรุณากรอกเบอร์โทรสำรองบิดา">
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลมารดา -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mother_first_name" class="form-label">ชื่อมารดา:</label>
                                <input type="text" class="form-control" name="mother_first_name" placeholder="กรุณากรอกชื่อมารดา" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mother_last_name" class="form-label">นามสกุลมารดา:</label>
                                <input type="text" class="form-control" name="mother_last_name" placeholder="กรุณากรอกนามสกุลมารดา" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mother_phone" class="form-label">เบอร์โทรมารดา:</label>
                                <input type="text" class="form-control" name="mother_phone" placeholder="กรุณากรอกเบอร์โทรมารดา" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mother_phone_backup" class="form-label">เบอร์โทรสำรองมารดา:</label>
                                <input type="text" class="form-control" name="mother_phone_backup" placeholder="กรุณากรอกเบอร์โทรสำรองมารดา">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ปุ่มบันทึกข้อมูล -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-submit">
                <i class="bi bi-check-circle me-2"></i>
                บันทึกข้อมูล
            </button>
        </div>
    </form>
</div>

<!-- Modal จัดการห้องเรียน -->
<div class="modal fade" id="classroomManagerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการห้องเรียน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- ฟอร์มเพิ่มห้องเรียน -->
                <form id="addClassroomForm" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <select id="new_classroom_group" class="form-select" required>
                                <option value="">เลือกกลุ่ม</option>
                                <option value="เด็กกลาง">เด็กกลาง</option>
                                <option value="เด็กโต">เด็กโต</option>
                                <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="new_classroom_name" class="form-control" 
                                   placeholder="ชื่อห้องเรียน" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">เพิ่ม</button>
                        </div>
                    </div>
                </form>

                <!-- ตารางแสดงห้องเรียน -->
                <div class="table-responsive">
                    <table class="table" id="classroomTable">
                        <thead>
                            <tr>
                                <th>กลุ่ม</th>
                                <th>ห้องเรียน</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal จัดการปีการศึกษา -->
<div class="modal fade" id="academicYearManagerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการปีการศึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- ฟอร์มเพิ่มปีการศึกษา -->
                <form id="addAcademicYearForm" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-10">
                            <input type="text" id="new_academic_year" class="form-control" 
                                   placeholder="ปีการศึกษา (พ.ศ.)" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">เพิ่ม</button>
                        </div>
                    </div>
                </form>

                <!-- ตารางแสดงปีการศึกษา -->
                <div class="table-responsive">
                    <table class="table" id="academicYearTable">
                        <thead>
                            <tr>
                                <th>ปีการศึกษา</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-image-container {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto;
    cursor: pointer;
}

.profile-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 3px solid #e0e0e0;
}

.profile-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    border-radius: 50%;
}

.profile-image-overlay i {
    color: white;
    font-size: 2rem;
}

.profile-image-container:hover .profile-image-overlay {
    opacity: 1;
}

/* เมื่อ drag ไฟล์เข้ามา */
.profile-image-container.dragover {
    border: 2px dashed #4CAF50;
}

.yearpicker-container {
    position: absolute;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 10000;
    max-height: 200px;
    overflow-y: auto;
    padding: 10px;
}

.yearpicker-items {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 5px;
}

.yearpicker-item {
    padding: 5px 10px;
    text-align: center;
    cursor: pointer;
    border-radius: 3px;
}

.yearpicker-item:hover {
    background-color: #f0f0f0;
}

.yearpicker-item.selected {
    background-color: #007bff;
    color: white;
}
</style>

<script>
function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // ตรวจสอบประเภทไฟล์
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'ไฟล์ไม่ถูกต้อง',
                text: 'กรุณาเลือกไฟล์ภาพ (jpg, jpeg, png) เท่านั้น'
            });
            input.value = '';
            return;
        }
        
        // ตรวจสอบขนาดไฟล์
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'ไฟล์มีขนาดใหญ่เกินไป',
                text: 'ขนาดไฟล์ต้องไม่เกิน 5MB'
            });
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            // เก็บข้อมูล base64
            document.getElementById('profile_image_data').value = e.target.result;
            
            // แสดงตัวอย่างรูป
            const preview = document.getElementById('preview_image');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// เพิ่มการตรวจสอบก่อนส่งฟอร์ม
document.getElementById('addChildForm').onsubmit = function(e) {
    const imageData = document.getElementById('profile_image_data').value;
    if (imageData) {
        // ตรวจสอบขนาดข้อมูล base64
        const base64Size = Math.ceil((imageData.length * 3) / 4);
        if (base64Size > 5 * 1024 * 1024) {
            alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
            e.preventDefault();
            return false;
        }
    }
    return true;
};

// เพิ่ม event listener สำหรับการคลิกที่รูป
document.querySelector('.profile-image-container').addEventListener('click', function() {
    document.getElementById('profile_image').click();
});

// เพิ่ม drag and drop support
const container = document.querySelector('.profile-image-container');

container.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

container.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

container.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    
    const input = document.getElementById('profile_image');
    input.files = e.dataTransfer.files;
    handleImageSelect(input);
});

// ฟังก์ชันโหลดห้องเรียนตามกลุ่มที่เลือก
function loadClassrooms() {
    const childGroup = document.getElementById('child_group').value;
    const classroomSelect = document.getElementById('classroom');
    
    if (!childGroup) {
        classroomSelect.innerHTML = '<option value="" disabled selected>กรุณาเลือกห้องเรียน</option>';
        return;
    }

    // เรียก API เพื่อดึงข้อมูลห้องเรียน
    fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(childGroup)}`)
        .then(response => response.json())
        .then(data => {
            classroomSelect.innerHTML = '<option value="" disabled selected>กรุณาเลือกห้องเรียน</option>';
            if (Array.isArray(data)) {
                data.forEach(classroom => {
                    const option = document.createElement('option');
                    option.value = classroom.classroom_name;
                    option.textContent = classroom.classroom_name;
                    classroomSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            classroomSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
        });
}

// ฟังก์ชันเปิด Modal จัดการห้องเรียน
function openClassroomManager() {
    loadClassroomTable();
    const modal = new bootstrap.Modal(document.getElementById('classroomManagerModal'));
    modal.show();
}

// ฟังก์ชันโหลดตารางห้องเรียน
function loadClassroomTable() {
    fetch('../../include/function/get_classrooms.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#classroomTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(classroom => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${classroom.child_group}</td>
                    <td>${classroom.classroom_name}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editClassroom('${classroom.classroom_name}', '${classroom.child_group}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClassroom('${classroom.classroom_name}', '${classroom.child_group}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => console.error('Error:', error));
}

// เพิ่ม Event Listener สำหรับฟอร์มเพิ่มห้องเรียน
document.getElementById('addClassroomForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const data = {
        action: 'add',
        child_group: document.getElementById('new_classroom_group').value,
        classroom_name: document.getElementById('new_classroom_name').value
    };

    fetch('../../include/function/classroom_functions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire('สำเร็จ', result.message, 'success');
            loadClassroomTable();
            if (document.getElementById('child_group').value === data.child_group) {
                loadClassrooms();
            }
            this.reset();
        } else {
            Swal.fire('ข้อผิดพลาด', result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเพิ่มห้องเรียน', 'error');
    });
});

function editClassroom(classroomName, childGroup) {
    document.activeElement?.blur();

    const modal = document.getElementById('addChildModal');
    const instance = bootstrap.Modal.getInstance(modal);
    if (instance) {
        instance.hide();
    }

    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

    setTimeout(() => {
        Swal.fire({
            title: 'แก้ไขข้อมูลห้องเรียน',
            html: `
                <div class="mb-3">
                    <label for="edit_child_group" class="form-label">กลุ่มเด็ก:</label>
                    <select id="edit_child_group" class="form-control form-select">
                        <option value="เด็กกลาง" ${childGroup === 'เด็กกลาง' ? 'selected' : ''}>เด็กกลาง</option>
                        <option value="เด็กโต" ${childGroup === 'เด็กโต' ? 'selected' : ''}>เด็กโต</option>
                        <option value="เตรียมอนุบาล" ${childGroup === 'เตรียมอนุบาล' ? 'selected' : ''}>เตรียมอนุบาล</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_classroom_name" class="form-label">ชื่อห้องเรียน:</label>
                    <input type="text" id="edit_classroom_name" class="form-control">
                </div>
            `,
            didOpen: () => {
                document.getElementById('edit_classroom_name').value = classroomName;
            },
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',

            preConfirm: () => {
                const newClassroomName = document.getElementById('edit_classroom_name').value.trim();
                const newChildGroup = document.getElementById('edit_child_group').value;

                if (!newClassroomName) {
                    Swal.showValidationMessage('กรุณากรอกชื่อห้องเรียน');
                    return false;
                }

                return {
                    action: 'edit',  // สำคัญ! ต้องส่ง action ให้ PHP รู้ว่าทำอะไร
                    old_classroom_name: classroomName,
                    old_child_group: childGroup,
                    classroom_name: newClassroomName,
                    child_group: newChildGroup
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../../include/function/classroom_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('สำเร็จ', data.message, 'success');
                        loadClassroomTable();
                        const currentGroup = document.getElementById('child_group')?.value;
                        if (currentGroup === childGroup || currentGroup === result.value.child_group) {
                            loadClassrooms();
                        }
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                });
            }
        });
    }, 300);
}




// ฟังก์ชันลบห้องเรียน
function deleteClassroom(classroomName, childGroup) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: `คุณต้องการลบห้องเรียน ${classroomName} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../include/function/classroom_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    classroom_name: classroomName,
                    child_group: childGroup
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    loadClassroomTable();
                    // รีเฟรชรายการห้องเรียนในฟอร์มหลัก
                    if (document.getElementById('child_group').value === childGroup) {
                        loadClassrooms();
                    }
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

// เพิ่ม Event Listener สำหรับฟอร์มเพิ่มห้องเรียน
document.getElementById('addChildForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // แสดง loading
    Swal.fire({
        title: 'กำลังบันทึกข้อมูล...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: data.message,
                showConfirmButton: true
            }).then(() => {
                // กลับไปหน้า children_history
                window.location.href = '../student/children_history.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message,
                showConfirmButton: true
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
            showConfirmButton: true
        });
    });
});

$(document).ready(function() {
    // สร้าง Year Picker
    $('.yearpicker').each(function() {
        const input = $(this);
        const container = $('<div class="yearpicker-container" style="display:none;"></div>');
        const itemsContainer = $('<div class="yearpicker-items"></div>');
        
        // สร้างปีย้อนหลัง 10 ปี และล่วงหน้า 5 ปี
        const currentYear = new Date().getFullYear() + 543;
        for(let year = currentYear + 5; year >= currentYear - 10; year--) {
            const item = $(`<div class="yearpicker-item" data-year="${year}">${year}</div>`);
            item.click(function() {
                input.val(year);
                container.hide();
                // ไฮไลท์ปีที่เลือก
                itemsContainer.find('.yearpicker-item').removeClass('selected');
                $(this).addClass('selected');
            });
            itemsContainer.append(item);
        }
        
        container.append(itemsContainer);
        input.after(container);
        
        // แสดง/ซ่อน picker เมื่อคลิกที่ input
        input.click(function(e) {
            e.stopPropagation();
            $('.yearpicker-container').not(container).hide();
            container.toggle();
            
            // ไฮไลท์ปีที่เลือกปัจจุบัน
            const selectedYear = input.val();
            itemsContainer.find('.yearpicker-item').removeClass('selected');
            itemsContainer.find(`[data-year="${selectedYear}"]`).addClass('selected');
            
            // เลื่อนไปที่ปีที่เลือก
            const selectedItem = container.find('.selected');
            if(selectedItem.length) {
                container.scrollTop(selectedItem.position().top - container.height()/2);
            }
        });
        
        // ซ่อน picker เมื่อคลิกที่อื่น
        $(document).click(function(e) {
            if(!$(e.target).closest('.yearpicker-container').length) {
                container.hide();
            }
        });
    });
});

// ฟังก์ชันโหลดปีการศึกษา
function loadAcademicYears() {
    fetch('../../include/function/get_academic_years.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('academic_year');
            select.innerHTML = '<option value="" disabled selected>กรุณาเลือกปีการศึกษา</option>';
            
            if (data.success && Array.isArray(data.years)) {
                data.years.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="" disabled selected>ไม่พบข้อมูลปีการศึกษา</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const select = document.getElementById('academic_year');
            select.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
        });
}

// ฟังก์ชันเปิด Modal จัดการปีการศึกษา
function openAcademicYearManager() {
    loadAcademicYearTable();
    const modal = new bootstrap.Modal(document.getElementById('academicYearManagerModal'));
    modal.show();
}

// ฟังก์ชันโหลดตารางปีการศึกษา
function loadAcademicYearTable() {
    fetch('../../include/function/academic_year_functions.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#academicYearTable tbody');
            tbody.innerHTML = '';
            
            if (Array.isArray(data)) {
                data.forEach(year => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${year.name}</td>
                        <td>
                            <span class="badge ${year.is_active ? 'bg-success' : 'bg-secondary'}">
                                ${year.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editAcademicYear(${year.id}, '${year.name}', ${year.is_active})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteAcademicYear(${year.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center">ไม่พบข้อมูลปีการศึกษา</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const tbody = document.querySelector('#academicYearTable tbody');
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
        });
}

// ฟังก์ชันจัดรูปแบบวันที่
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// เพิ่ม Event Listener สำหรับฟอร์มเพิ่มปีการศึกษา
document.getElementById('addAcademicYearForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const data = {
        action: 'add',
        name: document.getElementById('new_academic_year').value
    };

    fetch('../../include/function/academic_year_functions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire('สำเร็จ', result.message, 'success');
            loadAcademicYearTable();
            loadAcademicYears();
            this.reset();
        } else {
            Swal.fire('ข้อผิดพลาด', result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเพิ่มปีการศึกษา', 'error');
    });
});

// ฟังก์ชันแก้ไขปีการศึกษา
function editAcademicYear(id, name, isActive) {
    Swal.fire({
        title: 'แก้ไขข้อมูลปีการศึกษา',
        html: `
            <div class="mb-3">
                <label for="edit_academic_year" class="form-label">ปีการศึกษา:</label>
                <input type="text" id="edit_academic_year" class="form-control" value="${name}">
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" id="edit_is_active" class="form-check-input" ${isActive ? 'checked' : ''}>
                    <label class="form-check-label" for="edit_is_active">เปิดใช้งาน</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            return {
                action: 'edit',
                id: id,
                name: document.getElementById('edit_academic_year').value,
                is_active: document.getElementById('edit_is_active').checked
            };
        }
    })
    .then((result) => {
        if (result.isConfirmed) {
            fetch('../../include/function/academic_year_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    loadAcademicYearTable();
                    loadAcademicYears();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

// ฟังก์ชันลบปีการศึกษา
function deleteAcademicYear(id) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบปีการศึกษานี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../include/function/academic_year_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    loadAcademicYearTable();
                    loadAcademicYears();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

// โหลดปีการศึกษาเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    loadAcademicYears();
});
</script>