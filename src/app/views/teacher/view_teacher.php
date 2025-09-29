<?php
include __DIR__ .'../include/auth.php';
checkUserRole(['admin', 'teacher', 'parent']);
include __DIR__ . '/partials/header.php';
include __DIR__ .'../include/auth_navbar.php';
require_once __DIR__ .'../include/pages_referen.php';
require_once __DIR__.'../include/child_functions.php';
$is_admin = getUserRole() === 'admin';
$is_parent = getUserRole() === 'parent';
$is_teacher = getUserRole() === 'teacher';


include __DIR__ .'../include/auth_dashboard.php';

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

<div class="container col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <?php
    // PHP Section for Breadcrumb
    $previous_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    $previous_page_name = getPageNameFromURL($previous_page);
    ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <?php if ($previous_page): ?>
                <li class="breadcrumb-item"><a
                        href="<?= htmlspecialchars($previous_page) ?>"><?= htmlspecialchars($previous_page_name) ?></a></li>
            <?php else: ?>
                <li class="breadcrumb-item">Dashboard</li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">ประวัติประจำตัวของเด็ก</li>
        </ol>
    </nav>
    <h2 class="mt-4">ประวัติประจำตัวของเด็ก</h2>
    <div class="options">

        <div>

        </div>
        <?php if ($is_admin): ?>
            <a href="generate-qr.php?id=<?= htmlspecialchars($child['id']) ?>" class="btn btn-primary btn-sm mt-auto">สร้าง
                QRcode</a>
            <!-- Admin: ปุ่มแก้ไข -->
            <button id="editButton" type="button" class="btn btn-warning btn-sm">
                แก้ไขข้อมูล
            </button>

            <!-- Admin: ปุ่มลบ -->
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                ลบข้อมูล
            </button>
        <?php elseif ($is_parent || $is_teacher): ?>
            <!-- Parent: ข้อความแจ้งเตือน -->
            <div class="alert alert-info" role="alert">
                คุณสามารถดูข้อมูลของเด็กได้เท่านั้น
            </div>
        <?php endif; ?>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- แสดงข้อมูลเด็ก -->
            <form action="../include/edit_child.php" method="POST" id="check" class="form-horizontal"
                enctype="multipart/form-data" novalidate>
                <div class="row mb-4">
                    <!-- แสดงรูปภาพ -->
                    <div class="col-md-3 text-center">
                        <img id="profileImage" src="<?= htmlspecialchars($child['profile_image']) ?>"
                            class="img-thumbnail mb-2" alt="Profile Image" style="width: 150px; height: 150px;">
                        <input type="file" id="fileInput" class="form-control" accept="image/*">
                    </div>
                    <!-- ข้อมูลทั่วไป -->
                    <div class="col-md-9">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">กลุ่มเด็ก:</label>
                            <div class="col-sm-9">
                                <input class="form-control" name="child_group" id="Child_group" type="text"
                                    value="<?= htmlspecialchars($child['child_group'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ห้องเรียน:</label>
                            <div class="col-sm-9">
                                <input class="form-control" name="classroom" id="Classroom" type="text"
                                    value="<?= htmlspecialchars($child['classroom'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <!-- ฟิลด์ hidden สำหรับส่ง id ไปยัง PHP -->
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($child['id']); ?>" />
                            <label class="col-sm-3 col-form-label">รหัสประจำตัว:</label>
                            <div class="col-sm-9">
                                <input class="form-control" name="studentid" id="StudentID" type="text"
                                    value="<?= htmlspecialchars($child['studentid'] ?? '') ?>" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ชื่อ-สกุล:</label>
                            <div class="col-sm-3">
                                <input class="form-control" name="prefix_th" id="TitleID_th_student" type="text"
                                    value="<?= htmlspecialchars($child['prefix_th'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-sm-3">
                                <input class="form-control" name="firstname_th" id="FirstName_th_student" type="text"
                                    value="<?= htmlspecialchars($child['firstname_th'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-sm-3">
                                <input class="form-control" name="lastname_th" id="LastName_th_student" type="text"
                                    value="<?= htmlspecialchars($child['lastname_th'] ?? '') ?>" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ชื่อเล่น:</label>
                            <div class="col-sm-9">
                                <input class="form-control" name="nickname" id="Nickname" type="text"
                                    value="<?= htmlspecialchars($child['nickname'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Name-Surname:</label>
                            <div class="col-sm-3">
                                <input class="form-control" name="prefix_en" id="TitleID_eng_student" type="text"
                                    value="<?= htmlspecialchars($child['prefix_en'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-sm-3">
                                <input class="form-control" name="firstname_en" id="FirstName_eng_student" type="text"
                                    value="<?= htmlspecialchars($child['firstname_en'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-sm-3">
                                <input class="form-control" name="lastname_en" id="LastName_eng_student" type="text"
                                    value="<?= htmlspecialchars($child['lastname_en'] ?? '') ?>" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ชื่อพ่อ:</label>
                            <div class="col-sm-4">
                                <input class="form-control" name="father_first_name" id="FatherFirstName" type="text"
                                    value="<?= htmlspecialchars($child['father_first_name'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <input class="form-control" name="father_last_name" id="FatherLastName" type="text"
                                    value="<?= htmlspecialchars($child['father_last_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">เบอร์โทรพ่อ:</label>
                            <div class="col-sm-9">
                                <input class="form-control" name="father_phone" id="FatherPhone" type="text"
                                    value="<?= htmlspecialchars($child['father_phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ชื่อแม่:</label>
                            <div class="col-sm-4">
                                <input class="form-control" name="mother_first_name" id="MotherFirstName" type="text"
                                    value="<?= htmlspecialchars($child['mother_first_name'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <input class="form-control" name="mother_last_name" id="MotherLastName" type="text"
                                    value="<?= htmlspecialchars($child['mother_last_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">เบอร์โทรแม่:</label>
                            <div class="col-sm-9">
                                <input class="form-control" name="mother_phone" id="MotherPhone" type="text"
                                    value="<?= htmlspecialchars($child['mother_phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <!-- ข้อมูลส่วนตัว -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">เลขบัตรประชาชน:</label>
                        <input class="form-control" name="id_card" id="Id_card" type="text"
                            value="<?= htmlspecialchars($child['id_card'] ?? '') ?>" maxlength="20" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">สถานที่ออกบัตร:</label>
                        <input class="form-control" name="issue_at" id="Issue_at" type="text"
                            value="<?= htmlspecialchars($child['issue_at'] ?? '') ?>" maxlength="40" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">วันที่ออกบัตร:</label>
                        <input class="form-control" name="issue_date" id="Issue_date" type="text"
                            value="<?= htmlspecialchars($child['issue_date'] ?? '') ?>" placeholder="วว/ดด/ปปปป"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">วันหมดอายุ:</label>
                        <input class="form-control" name="expiry_date" id="Expiry_date" type="text"
                            value="<?= htmlspecialchars($child['expiry_date'] ?? '') ?>" placeholder="วว/ดด/ปปปป"
                            required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">เชื้อชาติ:</label>
                        <input class="form-control" name="race" id="Race" type="text"
                            value="<?= htmlspecialchars($child['race'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">สัญชาติ:</label>
                        <input class="form-control" name="nationality" id="Nationality" type="text"
                            value="<?= htmlspecialchars($child['nationality'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ศาสนา:</label>
                        <input class="form-control" name="religion" id="Religion" type="text"
                            value="<?= htmlspecialchars($child['religion'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">อายุ:</label>
                        <div class="input-group">
                            <input class="form-control" name="age_student" id="Age_student" type="text"
                                value="<?= htmlspecialchars($child['age_student'] ?? '') ?>" required>
                            <span class="input-group-text">ปี</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">วัน/เดือน/ปี เกิด:</label>
                        <input class="form-control" name="birthday" id="Birthday" type="text"
                            value="<?= htmlspecialchars($child['birthday'] ?? '') ?>" placeholder="วว/ดด/ปปปป" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">สถานที่เกิด:</label>
                        <input class="form-control" name="place_birth" id="Place_birth" type="text"
                            value="<?= htmlspecialchars($child['place_birth'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">เพศ:</label>
                        <select class="form-control" name="sex" id="Sex" required>
                            <option value="ชาย" <?= $child['sex'] == 'ชาย' ? 'selected' : '' ?>>ชาย</option>
                            <option value="หญิง" <?= $child['sex'] == 'หญิง' ? 'selected' : '' ?>>หญิง</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ส่วนสูง:</label>
                        <div class="input-group">
                            <input class="form-control" name="height" id="Height" type="text"
                                value="<?= htmlspecialchars($child['height'] ?? '') ?>" required>
                            <span class="input-group-text">ซม.</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">น้ำหนัก:</label>
                        <div class="input-group">
                            <input class="form-control" name="weight" id="Weight" type="text"
                                value="<?= htmlspecialchars($child['weight'] ?? '') ?>" required>
                            <span class="input-group-text">กก.</span>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">โรคประจำตัว:</label>
                        <input class="form-control" name="congenital_disease" id="Congenital_disease" type="text"
                            value="<?= htmlspecialchars($child['congenital_disease'] ?? '') ?>" required>
                    </div>
                </div>
                <!-- ปุ่มบันทึก -->
                <button id="saveButton" type="submit" class="btn btn-success btn-sm" style="display: none;">
                    บันทึกข้อมูล
                </button>
                <!-- ปุ่มยกเลิก -->
                <button id="cancelButton" type="button" class="btn btn-secondary btn-sm" style="display: none;">
                    ยกเลิก
                </button>
            </form>
        </div>
    </div>

</div>


<!-- Modal แก้ไขข้อมูล -->
<?php if ($is_admin): ?>
    <!-- Modal ยืนยันการลบ -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">ยืนยันการลบข้อมูล</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    คุณต้องการลบข้อมูลของเด็กคนนี้หรือไม่? การลบข้อมูลจะไม่สามารถกู้คืนได้
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <form action="../include/delete_child.php" method="POST">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($child['id']) ?>">
                        <button type="submit" class="btn btn-danger">ยืนยันการลบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const editButton = document.getElementById("editButton");
        const saveButton = document.getElementById("saveButton");
        const cancelButton = document.getElementById("cancelButton");
        const formInputs = document.querySelectorAll("#check input, #check select");

        // ตั้งค่าเริ่มต้น: input ทั้งหมดเป็น readonly
        formInputs.forEach(input => {
            input.setAttribute("readonly", true);
            input.setAttribute("disabled", true);
        });

        // กดปุ่มแก้ไขข้อมูล
        editButton.addEventListener("click", () => {
            formInputs.forEach(input => {
                input.removeAttribute("readonly");
                input.removeAttribute("disabled");
            });
            saveButton.style.display = "inline-block";
            cancelButton.style.display = "inline-block";
            editButton.style.display = "none";
        });

        // กดปุ่มยกเลิก
        cancelButton.addEventListener("click", () => {
            formInputs.forEach(input => {
                input.setAttribute("readonly", true);
                input.setAttribute("disabled", true);
            });
            saveButton.style.display = "none";
            cancelButton.style.display = "none";
            editButton.style.display = "inline-block";
        });
    });
</script>


<script>
    // ฟังก์ชันเพื่อแสดงรูปภาพที่ผู้ใช้เลือก
    document.getElementById("fileInput").addEventListener("change", function (event) {
        const file = event.target.files[0]; // เลือกไฟล์แรก
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById("profileImage").src = e.target.result; // เปลี่ยนแหล่งที่มาของภาพ
            }
            reader.readAsDataURL(file); // อ่านไฟล์และแปลงเป็น URL
        }
    });
</script>
</body>

</html>