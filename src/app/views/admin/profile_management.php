<?php
include('../../include/auth/auth.php');
checkUserRole(['admin']);
include __DIR__ . '../../partials/Header.php';
include('../../include/auth/auth_navbar.php');
include __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once '../../include/function/child_functions.php';
require_once '../../include/function/pages_referen.php';
//$children = getChildrenData();
$teachers = getAllTeachers();  // ดึงข้อมูลคุณครู
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
                text: <?php echo json_encode($message); ?>,
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
    .page-header {
        background: linear-gradient(135deg, #26648E 0%, #1F4E6E 100%);
        padding: 2rem;
        border-radius: 15px;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .teacher-card {
        border: none;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }

    .teacher-card:hover {
        transform: translateY(-5px);
    }

    .avatar-wrapper {
        width: 120px;
        height: 120px;
        margin: 0 auto;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .avatar-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .info-group {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #495057;
    }

    .info-item i {
        font-size: 1.1rem;
        width: 24px;
    }

    .btn-outline-primary {
        border-radius: 25px;
        padding: 0.5rem 1.5rem;
    }

    .alert {
        border-radius: 10px;
        padding: 1rem;
    }

    .group-header {
        margin-top: 2rem;
        margin-bottom: 1rem;
        position: relative;
    }

    .group-title {
        font-size: 1.5rem;
        color: #26648E;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }

    .group-line {
        height: 3px;
        background: linear-gradient(90deg, #26648E 0%, rgba(38, 100, 142, 0.1) 100%);
        border-radius: 2px;
    }

    .group-title i {
        color: #26648E;
    }

    .group-section {
        background: #fff;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    }


    .info-group {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.5rem;
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.02);
    }

    .info-item i {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        background: rgba(0, 0, 0, 0.05);
    }
</style>

<main class="main-content">
    <div class="container mt-4">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">จัดการข้อมูลครู</h2>
                    <div class="text-white mt-1 ">จัดการข้อมูลและสิทธิ์การเข้าถึงของคุณครู</div>
                </div>
            </div>
        </div>


        <div class="tab-content" id="profileTabsContent">
            <!-- แท็บจัดการข้อมูลคุณครู -->
            <div class="tab-pane fade show active" id="teachers" role="tabpanel">
                <div class="row g-4">
                    <?php if ($teachers && count($teachers) > 0): ?>
                        <?php
                        // จัดกลุ่มครูตามระดับชั้น
                        $teacherGroups = [
                            'เด็กโต' => [],
                            'เด็กกลาง' => [],
                            'เตรียมอนุบาล' => [],
                            'อื่นๆ' => []
                        ];

                        foreach ($teachers as $teacher) {
                            $group = $teacher['teacher_group'] ?? '';

                            if (!empty($group) && strpos($group, 'เด็กโต') !== false) {
                                $teacherGroups['เด็กโต'][] = $teacher;
                            } elseif (!empty($group) && strpos($group, 'เด็กกลาง') !== false) {
                                $teacherGroups['เด็กกลาง'][] = $teacher;
                            } elseif (!empty($group) && strpos($group, 'เตรียมอนุบาล') !== false) {
                                $teacherGroups['เตรียมอนุบาล'][] = $teacher;
                            } else {
                                $teacherGroups['อื่นๆ'][] = $teacher;
                            }
                        }
                        ?>

                        <?php foreach ($teacherGroups as $groupName => $groupTeachers): ?>
                            <?php if (!empty($groupTeachers)): ?>
                                <div class="col-12 mb-2">
                                    <div class="group-header">
                                        <h3 class="group-title">
                                            <i class="bi bi-people-fill me-2"></i>
                                            กลุ่ม<?= $groupName ?>
                                        </h3>
                                        <div class="group-line"></div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle text-center">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>รูป</th>
                                                    <th>ชื่อ-นามสกุล</th>
                                                    <th>อีเมล</th>
                                                    <th>เบอร์โทร</th>
                                                    <th>กลุ่ม</th>
                                                    <th>ห้อง</th>
                                                    <th>บทบาท</th>
                                                    <th>แก้ไข</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($groupTeachers as $teacher): ?>
                                                    <tr>
                                                        <td>
                                                            <img src="<?= htmlspecialchars($teacher['teacher_image'] ?? '../../../public/assets/images/avatar.png') ?>" class="rounded-circle" alt="avatar" style="width:60px;height:60px;object-fit:cover;" onerror="this.src='../../../public/assets/images/avatar.png'">
                                                        </td>
                                                        <td><?= htmlspecialchars($teacher['teacher_firstname'] . ' ' . $teacher['teacher_lastname']) ?></td>
                                                        <td><?= htmlspecialchars($teacher['email'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($teacher['phone_number'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($teacher['teacher_group'] ?? 'ไม่ระบุ') ?></td>
                                                        <td><?= htmlspecialchars($teacher['teacher_classroom'] ?? 'ไม่ระบุ') ?></td>
                                                        <td>
                                                            <?php
                                                                if (($teacher['user_role'] ?? '') === 'admin') {
                                                                    echo 'ผู้ดูแลระบบ';
                                                                } else if (($teacher['user_role'] ?? '') === 'teacher') {
                                                                    echo 'ครูผู้สอน';
                                                                }else {
                                                                    echo '';
                                                                }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-outline-primary btn-sm" onclick="editTeacher(<?= $teacher['teacher_id'] ?>)"><i class="bi bi-pencil-square me-2"></i>แก้ไข</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>ไม่พบข้อมูลคุณครู
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข้อมูลคุณครู -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTeacherModalLabel">แก้ไขข้อมูลคุณครู</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTeacherForm" action="../../include/function/teacher_functions.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_teacher">
                        <input type="hidden" name="teacher_id" id="edit_teacher_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ</label>
                                <input type="text" class="form-control" name="teacher_firstname" id="edit_teacher_firstname" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" class="form-control" name="teacher_lastname" id="edit_teacher_lastname" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">อีเมล</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทรศัพท์</label>
                                <input type="tel" class="form-control" name="phone_number" id="edit_phone_number" required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_child_group" class="form-label">กลุ่มที่สอน</label>
                                <select name="teacher_group" id="edit_child_group" class="form-select" onchange="loadEditClassrooms()">
                                    <option value="">-- เลือกกลุ่มเรียน --</option>
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
                            <div class="col-md-3">
                                <label for="edit_classroom" class="form-label">ห้องเรียน</label>
                                <select name="teacher_classroom" id="edit_classroom" class="form-select">
                                    <option value="">-- เลือกห้องเรียน --</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">รูปโปรไฟล์</label>
                                <input type="file" class="form-control" name="teacher_image" accept="image/*">
                                <div class="form-text">อัพโหลดรูปใหม่เฉพาะเมื่อต้องการเปลี่ยนรูปโปรไฟล์</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">บทบาท</label>
                                <select class="form-select" name="role" id="edit_role" required>
                                    <option value="teacher">ครูผู้สอน</option>
                                    <option value="admin">ผู้ดูแลระบบ</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
        // ฟังก์ชันสำหรับเปิด modal แก้ไขข้อมูลคุณครู
        function editTeacher(teacherId) {
            fetch(`../../include/function/teacher_functions.php?action=get_teacher&teacher_id=${teacherId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_teacher_id').value = data.teacher_id;
                    document.getElementById('edit_teacher_firstname').value = data.teacher_firstname;
                    document.getElementById('edit_teacher_lastname').value = data.teacher_lastname;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_phone_number').value = data.phone_number;
                    document.getElementById('edit_role').value = data.role;

                    // เลือกกลุ่มเรียนและโหลดห้องเรียน
                    const childGroupSelect = document.getElementById('edit_child_group');
                    childGroupSelect.value = data.teacher_group;

                    // โหลดห้องเรียนและรอให้โหลดเสร็จก่อนเลือกห้องเรียน
                    loadEditClassrooms();
                    setTimeout(() => {
                        const classroomSelect = document.getElementById('edit_classroom');
                        if (classroomSelect) {
                            classroomSelect.value = data.teacher_classroom;
                        }
                    }, 1000); // เพิ่มเวลารอเป็น 1 วินาที

                    new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถดึงข้อมูลคุณครูได้'
                    });
                });
        }


        // เพิ่ม DataTable สำหรับแต่ละตาราง
        $(document).ready(function() {
            $('table.table-bordered').DataTable();
        });

        // ฟังก์ชันที่ใช้ดึงข้อมูลห้องเรียน
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

                    data.classrooms.forEach(function(classroom) {
                        var option = document.createElement('option');
                        option.value = classroom.classroom;
                        option.textContent = classroom.classroom;
                        classroomSelect.appendChild(option);
                    });

                    <?php if (isset($_GET['classroom'])): ?>
                        classroomSelect.value = '<?php echo json_encode($_GET['classroom']); ?>';
                    <?php endif; ?>
                })
                .catch(error => console.error('Error:', error));
        }

        // เพิ่มฟังก์ชัน loadEditClassrooms()
        function loadEditClassrooms() {
            var childGroup = document.getElementById('edit_child_group').value;

            if (!childGroup) {
                document.getElementById('edit_classroom').innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';
                return;
            }

            // เรียก API เพื่อดึงข้อมูลห้องเรียน
            fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(childGroup)}`)
                .then(response => response.json())
                .then(data => {
                    var classroomSelect = document.getElementById('edit_classroom');
                    classroomSelect.innerHTML = '<option value="">-- เลือกห้องเรียน --</option>';

                    // ตรวจสอบว่าข้อมูลมาในรูปแบบไหน
                    const classrooms = Array.isArray(data) ? data : (data.classrooms || []);

                    classrooms.forEach(function(classroom) {
                        var option = document.createElement('option');
                        const classroomValue = classroom.classroom_name || classroom.classroom || '';
                        option.value = classroomValue;
                        option.textContent = classroomValue;
                        classroomSelect.appendChild(option);
                    });
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
    </script>
</main>