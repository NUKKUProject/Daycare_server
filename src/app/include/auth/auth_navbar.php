<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
        <!-- Overlay for mobile -->
        <button
            class="btn d-md-block border-1 border-white"
            onclick="toggleSidebar()"
            style="color: white;">
            <i class="fas fa-bars"></i>
        </button>
        <!-- Logo และชื่อ -->
        <a class="navbar-brand d-flex align-items-center justify-content-center" style="margin: 0px;"
            href="<?php
                    if (isLoggedIn()) {
                        if ($_SESSION['role'] === 'admin') {
                            echo '/app/views/admin/admin_dashboard.php';
                        } elseif ($_SESSION['role'] === 'student') {
                            echo '/app/views/student/student_dashboard.php';
                        } elseif ($_SESSION['role'] === 'doctor') {
                            echo '/app/views/doctor/doctor_dashboard.php';
                        } else {
                            echo '/app/views/teacher/teacher_dashboard.php';
                        }
                    } else {
                        echo '../views';
                    }
                    ?>">
            <img src="../../../public/assets/images/logo.png" alt="Logo" class="nav-logo me-2">
            <div class="brand-text">
                <span class="brand-title d-none d-sm-inline">ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย</span>
                <span class="brand-subtitle d-none d-sm-inline">คณะพยาบาลศาสตร์ มหาวิทยาลัยขอนแก่น</span>
            </div>
        </a>

        <!-- <img src="../../../public/assets/images/baner+logo.png" alt="Logo" class="nav-logo me-2"> -->


        <!-- เมนูด้านขวา -->
        <div class="d-flex align-items-center g" >
            <div class="dropdown d-inline-block ">
                <button type="button" class="btn d-flex dropdown-toggle align-items-center" id=""
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    style="border: 1px solid white;"
                    >
                    <div class="user-avatar me-2">
                        <i class="fas fa-user-circle"></i>
                    </div>

                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars(getFullName()); ?></div>
                        <div class="user-role">
                            <?php
                            $role = getUserRole();
                            echo $role === 'admin' ? 'ผู้ดูแลระบบ' : ($role === 'doctor' ? 'แพทย์' : ($role === 'teacher' ? 'คุณครู' : 'นักเรียน'));

                            ?>
                        </div>
                    </div>
                    <i class="uil-angle-down d-none d-xl-inline-block font-size-15"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                      <?php if (isLoggedIn()) {
                        if ($_SESSION['role'] === 'doctor' || $_SESSION['role'] === 'teacher') { ?>
                            <button class="dropdown-item" onclick="onClickEditProfile()">
                            <i class="fa-regular fa-user me-2"></i><span class="align-middle">แก้ไขข้อมูล</span>
                             </button>
                    <?php  } } ?>         
                   
                    <a class="dropdown-item text-danger" href="/app/views/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> <span class="align-middle">ออกจากระบบ</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>


<script>
    function onClickEditProfile() {
        const role = '<?php echo getUserRole(); ?>';
        Swal.fire({
            title: 'แก้ไขข้อมูลส่วนตัว',
            html: `
                <input type="hidden" name="role" id="role" value="${role}">
                <input type="hidden" name="id" id="id" value="<?php echo $_SESSION['user_id'] ?>">
                <div class="mb-3 text-start">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email'])?>" disabled>
                </div>
                <div class="mb-3 text-start">
                    <label for="name" class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" id="name" class="form-control" value="<?php echo htmlspecialchars(getFullName()); ?>">
                </div>               
                

            `,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            
        })
        //กดยืนยันแล้วส่งค่าไป update_profile.php
        .then((result) => {
            if (result.isConfirmed) {
                const id = document.getElementById('id').value;
                const name = document.getElementById('name').value;
                const role = document.getElementById('role').value;

                // ส่งข้อมูลไปยังเซิร์ฟเวอร์
                fetch('../../include/auth/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id,
                        name: name,
                        role: role
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'แก้ไขข้อมูลส่วนตัวเรียบร้อยแล้ว',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            location.reload(); // รีโหลดหลัง toast ปิด
                        });
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: data.message || 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลส่วนตัว',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                });
            }
        });

    }


    function toggleSidebar() {
        const sidebar = document.getElementById('sidebarMenu');
        const mainContent = document.querySelector('.main-content'); // ใช้ querySelector
        

        if (window.innerWidth >= 1024) {
            // Desktop
            if (sidebar.classList.contains('sidebar-open')) {
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-closed');
                //ถ้าใช้ qr-scanner ต้องมีการปรับขนาด
                if (typeof updateQrScannerSize === 'function'){
                    setTimeout(updateQrScannerSize, 500);
                }

                
                
                // ใช้ class ของ Bootstrap 5
                mainContent.classList.remove('ms-lg-6'); // หรือ ms-5
                mainContent.classList.add('ms-0');
            } else {

                sidebar.classList.remove('sidebar-closed');
                sidebar.classList.add('sidebar-open');

                mainContent.classList.remove('ms-0');
                mainContent.classList.add('ms-lg-6'); // หรือ ms-5
            }
        } else {
            // Mobile & Tablet
            if (sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open', 'show');

            } else {
                
                sidebar.classList.add('mobile-open', 'show');

            }
        }
    }

    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebarMenu'); // ให้ตรงกัน
        const mainContent = document.querySelector('.main-content'); // ให้ตรงกัน

        if (window.innerWidth >= 1024) {
            // Desktop mode
            sidebar.classList.remove('mobile-open');

            // เปิด sidebar โดย default
            if (!sidebar.classList.contains('sidebar-closed')) {
                sidebar.classList.add('sidebar-open');
                mainContent.classList.remove('ms-0');
                mainContent.classList.add('ms-lg-6'); // Bootstrap 5
                //ถ้าใช้ qr-scanner ต้องมีการปรับขนาด
                if (typeof updateQrScannerSize === 'function'){
                    setTimeout(updateQrScannerSize, 500);
                    
                }
            }
        } else {
            // Mobile mode
            sidebar.classList.remove('sidebar-open', 'sidebar-closed');
            mainContent.classList.remove('ms-lg-6', 'ms-0'); // Bootstrap 5

        }
    });
</script>

<!-- เพิ่ม Font Awesome ถ้ายังไม่มี -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">