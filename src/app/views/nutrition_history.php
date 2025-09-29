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
        <h2 class="mb-4">ประวัติการบันทึกโภชนาการและการเจริญเติบโตของเด็ก</h2>
        <!-- ส่วนค้นหา -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form id="searchForm" class="row g-3">
                    <!-- กลุ่มเรียน -->
                    <div class="col-md-3">
                        <label for="child_group" class="form-label">กลุ่มเรียน</label>
                        <select class="form-select" id="child_group" name="child_group" onchange="loadClassrooms()">
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

                    <!-- ห้องเรียน -->
                    <div class="col-md-3">
                        <label for="classroom" class="form-label">ห้องเรียน</label>
                        <select name="classroom" id="classroom" class="form-select">
                            <option value="">-- เลือกห้องเรียน --</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="date" class="form-label">วันที่</label>
                        <input type="date" class="form-control" id="date" name="date"
                            value="<?php echo htmlspecialchars(isset($_GET['date']) ? $_GET['date'] : date('Y-m-d')); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="search" class="form-label">ค้นหาชื่อ</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="ชื่อ-นามสกุล"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>

                    <!-- ปุ่มค้นหาและรีเซ็ต -->
                    <div class="col-12">
                        <div class="search-buttons">
                            <button type="submit" class="btn btn-search">
                                <i class="bi bi-search"></i> ค้นหา
                            </button>
                            <button type="button" class="btn btn-reset" onclick="resetForm()">
                                <i class="bi bi-x-circle"></i> รีเซ็ต
                            </button>
                            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                <i class="bi bi-file-excel"></i> Export Excel
                            </button>
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

    <!-- Modal สำหรับ Export -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export ข้อมูลการรับประทานอาหาร</h5>
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
                        classroomSelect.value = '<?php echo json_encode($_GET['classroom']) ?>';
                    <?php endif; ?>
                })
                .catch(error => console.error('Error:', error));
        }

        // รีเซ็ตฟอร์ม
        function resetForm() {
            document.getElementById('searchForm').reset();
            document.getElementById('classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
            loadResults();
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

            fetch('../include/process/get_nutrition_records.php', {
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
                                                <?php if ($is_admin || $is_teacher): ?>
                                                <th>จัดการ</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    group.students.forEach(student => {
                        const hasRecord = student.id != null;
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
                                <?php if ($is_admin || $is_teacher): ?>
                                <td>
                                    ${hasRecord ? `
                                        <button type="button" class="btn btn-info btn-sm" onclick="showNutritionDetails('${student.studentid}', '${student.id}')">
                                            <i class="bi bi-eye"></i> ดูรายละเอียด
                                        </button>
                                        <?php if ($is_admin): ?>
                                        <button type="button" class="btn btn-warning btn-sm" onclick="editRecord('${student.id}')">
                                            <i class="bi bi-pencil"></i> แก้ไข
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($is_admin): ?>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteRecord('${student.id}')">
                                            <i class="bi bi-trash"></i> ลบ
                                        </button>
                                        <?php endif; ?>
                                    ` : `
                                        <button type="button" class="btn btn-success btn-sm" onclick="showAddNutritionModal('${student.studentid}', '${student.prefix_th}${student.firstname_th} ${student.lastname_th}')">
                                            <i class="bi bi-plus-circle"></i> เพิ่มข้อมูล
                                        </button>
                                    `}
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

        // ฟังก์ชันจัดกลุ่มข้อมูลตามกลุ่มเรียนและห้องเรียน
        function groupStudentsByClass(data) {
            const groups = {};
            data.forEach(student => {
                const key = `${student.child_group}-${student.classroom}`;
                if (!groups[key]) {
                    groups[key] = {
                        child_group: student.child_group,
                        classroom: student.classroom,
                        students: []
                    };
                }
                groups[key].students.push(student);
            });
            return groups;
        }

        // เพิ่มฟังก์ชันสำหรับจัดการข้อมูล
        function showNutritionDetails(studentId, recordId) {
            fetch(`../include/process/get_nutrition_details.php?id=${recordId}`)
                .then(response => response.json())
                .then(response => {
                    if (response.status !== 'success') {
                        throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                    }

                    const data = response.data;
                    Swal.fire({
                        title: 'รายละเอียดการรับประทานอาหาร',
                        html: `
                            <div class="text-start">
                                <p><strong>รหัสนักเรียน:</strong> ${data.student.id}</p>
                                <p><strong>ชื่อ-นามสกุล:</strong> ${data.student.name}</p>
                                <p><strong>ชื่อเล่น:</strong> ${data.student.nickname}</p>
                                <p><strong>กลุ่มเรียน:</strong> ${data.student.child_group}</p>
                                <p><strong>ห้องเรียน:</strong> ${data.student.classroom}</p>
                                <hr>
                                <p><strong>น้ำหนัก:</strong> ${data.nutrition.weight} กก.</p>
                                <p><strong>ส่วนสูง:</strong> ${data.nutrition.height} ซม.</p>
                                <p><strong>ประเภทอาหาร:</strong> ${data.nutrition.meal_type}</p>
                                <p><strong>สถานะ:</strong> ${data.nutrition.meal_status}</p>
                                <p><strong>หมายเหตุ:</strong> ${data.nutrition.note || '-'}</p>
                                <hr>
                                <p><strong>วันที่บันทึก:</strong> ${data.record_info.date}</p>
                                <p><strong>เวลา:</strong> ${data.record_info.time} น.</p>
                                <p><strong>ผู้บันทึก:</strong> ${data.record_info.recorded_by ? `${data.record_info.staff_firstname} ${data.record_info.staff_lastname}` : '-'}</p>
                            </div>
                        `,
                        width: '600px',
                        showCloseButton: true,
                        confirmButtonText: 'ปิด',
                        customClass: {
                            confirmButton: 'btn btn-secondary',
                            cancelButton: 'btn btn-warning',
                            denyButton: 'btn btn-danger'
                        }
                    }).then((result) => {
                        if (result.isDenied) {
                            deleteRecord(recordId);
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            editRecord(recordId);
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

        function showNutritionOverview(studentId) {
            window.location.href = `nutrition_overview.php?student_id=${studentId}`;
        }

        function showAddNutritionModal(studentId, studentName) {
            // TODO: สร้าง Modal สำหรับเพิ่มข้อมูล
            window.location.href = `nutrition_form.php?student_id=${studentId}&student_name=${encodeURIComponent(studentName)}`;
        }

        function editRecord(id) {
            // ดึงข้อมูลเดิมก่อนแสดงฟอร์มแก้ไข
            fetch(`../include/process/get_nutrition_details.php?id=${id}`)
                .then(response => response.json())
                .then(response => {
                    if (response.status !== 'success') {
                        throw new Error(response.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                    }

                    const data = response.data;
                    Swal.fire({
                        title: 'แก้ไขข้อมูลการรับประทานอาหาร',
                        html: `
                            <div class="mb-3">
                                <label class="form-label">น้ำหนัก (กก.)</label>
                                <input type="number" class="form-control" id="edit-weight" step="0.1" value="${data.nutrition.weight}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ส่วนสูง (ซม.)</label>
                                <input type="number" class="form-control" id="edit-height" step="0.1" value="${data.nutrition.height}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ประเภทอาหาร</label>
                                <select class="form-select" id="edit-meal-type">
                                    <option value="อาหารเช้า" ${data.nutrition.meal_type === 'อาหารเช้า' ? 'selected' : ''}>อาหารเช้า</option>
                                    <option value="อาหารว่างเช้า" ${data.nutrition.meal_type === 'อาหารว่างเช้า' ? 'selected' : ''}>อาหารว่างเช้า</option>
                                    <option value="อาหารกลางวัน" ${data.nutrition.meal_type === 'อาหารกลางวัน' ? 'selected' : ''}>อาหารกลางวัน</option>
                                    <option value="อาหารว่างบ่าย" ${data.nutrition.meal_type === 'อาหารว่างบ่าย' ? 'selected' : ''}>อาหารว่างบ่าย</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">สถานะการรับประทาน</label>
                                <select class="form-select" id="edit-meal-status">
                                    <option value="รับประทานหมด" ${data.nutrition.meal_status === 'รับประทานหมด' ? 'selected' : ''}>รับประทานหมด</option>
                                    <option value="รับประทานได้บางส่วน" ${data.nutrition.meal_status === 'รับประทานได้บางส่วน' ? 'selected' : ''}>รับประทานได้บางส่วน</option>
                                    <option value="รับประทานได้น้อย" ${data.nutrition.meal_status === 'รับประทานได้น้อย' ? 'selected' : ''}>รับประทานได้น้อย</option>
                                    <option value="ไม่รับประทาน" ${data.nutrition.meal_status === 'ไม่รับประทาน' ? 'selected' : ''}>ไม่รับประทาน</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">หมายเหตุ</label>
                                <textarea class="form-control" id="edit-note">${data.nutrition.note || ''}</textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'บันทึก',
                        cancelButtonText: 'ยกเลิก',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        preConfirm: () => {
                            return {
                                id: id,
                                weight: document.getElementById('edit-weight').value,
                                height: document.getElementById('edit-height').value,
                                meal_type: document.getElementById('edit-meal-type').value,
                                meal_status: document.getElementById('edit-meal-status').value,
                                note: document.getElementById('edit-note').value
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // ส่งข้อมูลไปอัพเดท
                            fetch('../include/process/update_nutrition_record.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(result.value)
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'บันทึกสำเร็จ',
                                        text: 'ข้อมูลได้รับการอัพเดทเรียบร้อยแล้ว',
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        loadResults(); // โหลดข้อมูลใหม่
                                    });
                                } else {
                                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการอัพเดทข้อมูล');
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

        function showNutritionOverview(studentId) {
            window.location.href = `nutrition_overview.php?student_id=${studentId}`;
        }

        function showAddNutritionModal(studentId, studentName) {
            Swal.fire({
                title: 'เพิ่มข้อมูลการรับประทานอาหาร',
                html: `
                    <div class="text-start">
                        <p><strong>รหัสนักเรียน:</strong> ${studentId}</p>
                        <p><strong>ชื่อ-นามสกุล:</strong> ${studentName}</p>
                        <hr>
                        <form id="nutritionForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">น้ำหนัก (กก.)</label>
                                        <input type="number" class="form-control" id="weight" name="weight" step="0.1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ส่วนสูง (ซม.)</label>
                                        <input type="number" class="form-control" id="height" name="height" step="0.1" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">มื้ออาหาร</label>
                                        <select class="form-select" id="meal_type" name="meal_type" required>
                                            <option value="">เลือกมื้ออาหาร</option>
                                            <option value="อาหารเช้า">อาหารเช้า</option>
                                            <option value="อาหารว่างเช้า">อาหารว่างเช้า</option>
                                            <option value="อาหารกลางวัน">อาหารกลางวัน</option>
                                            <option value="อาหารว่างบ่าย">อาหารว่างบ่าย</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">สถานะการทานอาหาร</label>
                                        <select class="form-select" id="meal_status" name="meal_status" required>
                                            <option value="">เลือกสถานะ</option>
                                            <option value="รับประทานหมด">รับประทานหมด</option>
                                            <option value="รับประทานได้บางส่วน">รับประทานได้บางส่วน</option>
                                            <option value="รับประทานได้น้อย">รับประทานได้น้อย</option>
                                            <option value="ไม่รับประทาน">ไม่รับประทาน</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">บันทึกเพิ่มเติม</label>
                                <textarea class="form-control" id="note" name="note" rows="2"></textarea>
                            </div>
                        </form>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                width: '600px',
                preConfirm: () => {
                    const form = document.getElementById('nutritionForm');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return false;
                    }
                    return {
                        student_id: studentId,
                        weight: document.getElementById('weight').value,
                        height: document.getElementById('height').value,
                        meal_type: document.getElementById('meal_type').value,
                        meal_status: document.getElementById('meal_status').value,
                        note: document.getElementById('note').value
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // ส่งข้อมูลไปบันทึก
                    fetch('../include/process/add_nutrition_record.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'บันทึกสำเร็จ',
                                text: 'ข้อมูลได้รับการบันทึกเรียบร้อยแล้ว',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                loadResults(); // โหลดข้อมูลใหม่
                            });
                        } else {
                            throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
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
                    fetch(`../include/process/delete_nutrition_record.php?id=${id}`, {
                        method: 'DELETE'
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
                                loadResults(); // โหลดข้อมูลใหม่
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

        // ฟังก์ชันสำหรับ Export
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
            
            let url = '../include/process/export_nutrition_history.php?';
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

        // โหลดข้อมูลเมื่อโหลดหน้า - ยกเลิกการโหลดอัตโนมัติ
        document.addEventListener('DOMContentLoaded', function() {
            loadClassrooms();
            // แสดงข้อความแนะนำเมื่อโหลดหน้าครั้งแรก
            const table = document.getElementById('resultTable');
            table.innerHTML = '<div class="alert alert-info">กรุณาเลือกกลุ่มเรียน, ห้องเรียน หรือค้นหาจากชื่อนักเรียน แล้วกดปุ่มค้นหา</div>';
        });

        // เพิ่ม event listener สำหรับฟอร์มค้นหา
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            loadResults();
        });
    </script>
</body>
</html>