<?php include __DIR__ . '/../include/auth/auth.php'; ?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <link rel="stylesheet" href="/css/styleHome.css" /> -->
    <link rel="icon" type="image/x-icon" href="/pic/apple-touch-icon.png" />
    <title>ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย คณะพยาบาลศาสตร์ มข.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
    <!-- Favicons -->
    <meta name="theme-color" content="#7952b3">
    <!-- Custom styles for this template -->

    <link
        href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"
        integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"
        integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha"
        crossorigin="anonymous"></script>
    <!-- <script src="dashboard.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap JS (Popper.js รวมอยู่ด้วย) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js">
    </script>

    <!-- ในส่วน head ให้เรียงลำดับการโหลด scripts ดังนี้ -->
    <!-- jQuery (if needed) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <link href="../../../public/assets/css/navbar.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/common.css">

    <!-- เพิ่มบรรทัดนี้หลัง Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body class="d-flex flex-column min-vh-100">
    <!-- เพิ่ม Background Image Container -->
    <div class="bg-image"></div>

    <main class="main-content flex-grow-1">
        <div class="container mt-5 mb-5">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <div class="text-center">
                                <img src="../../public/assets/images/logo.png" alt="Logo" class="login-logo ">
                                <h2 class="text-white mb-4">เข้าสู่ระบบ</h2>
                            </div>

                            <?php if (!empty($_GET['message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show auto-dismiss" role="alert">
                                    <?= htmlspecialchars($_GET['message']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($_GET['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show auto-dismiss" role="alert">
                                    <?= htmlspecialchars($_GET['error']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- ปุ่มเลือกวิธีการเข้าสู่ระบบ -->
                            <div class="login-method-buttons mb-4">
                                <!-- <button type="button" class="btn btn-light w-100 py-2 mb-3" id="normalLoginBtn">
                                    <i class="bi bi-person-fill me-2"></i>เข้าสู่ระบบด้วยบัญชีทั่วไป
                                </button> -->
                                <a class="btn btn-success w-100 py-2 mb-3" href="https://sso-uat-web.kku.ac.th/login?app=0198bb3e-bead-7eb3-97a7-70e4e9e4aebe">
                                    <i class="bi bi-shield-lock-fill me-2"></i>เข้าสู่ระบบด้วย KKU SSO
                                </a>
                                <!-- <button type="button" class="btn btn-success w-100 py-2" onclick="docterLogin()">
                                    <i class="fa-solid fa-stethoscope"></i> เข้าสู่ระบบสำหรับแพทย์
                                </button> -->
                            </div>

                            <!-- ฟอร์มล็อกอินปกติ -->
                            <form action="login.php" method="POST" id="normalLoginForm" style="display: none;">
                                <input type="hidden" name="login_method" value="normal">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้" required>
                                    <label for="username">ชื่อผู้ใช้</label>
                                </div>
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                                    <label for="password">รหัสผ่าน</label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
                                </button>
                                <div class="text-center">
                                    <a href="#" class="text-white-50 text-decoration-none small">ลืมรหัสผ่าน?</a>
                                    <span class="text-white-50 mx-2">•</span>
                                    <a href="register" class="text-white-50 text-decoration-none small">ลงทะเบียนใหม่</a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Auto dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.auto-dismiss');
            alerts.forEach(function(alert) {
                // เพิ่ม animation เมื่อ alert ปรากฏ
                alert.style.animation = 'fadeInDown 0.5s ease-in-out';

                // ตั้งเวลาให้ alert หายไป
                setTimeout(function() {
                    // เพิ่ม animation ก่อนที่ alert จะหายไป
                    alert.style.animation = 'fadeOutUp 0.5s ease-in-out';
                    setTimeout(function() {
                        alert.remove();
                    }, 450); // รอให้ animation เล่นจบก่อนลบ element
                }, 3000); // แสดง alert เป็นเวลา 3 วินาที
            });
        });
    </script>

    <style>
        body {
            min-height: 100vh;
            position: relative;
            margin: 0;
            padding: 0;
            padding-top: 80px;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            position: relative;
            z-index: 1;
            margin-bottom: 2rem;
        }

        .login-logo {
            height: 130px;
            width: auto;
            margin-bottom: 10px;
        }

        .footer {
            position: relative;
            z-index: 1;
            margin-top: 150px;
        }

        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://www.lighthousewillis.com/site/wp-content/uploads/2021/11/shutterstock_1240454104.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.8;
            z-index: -1;
        }

        .bg-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(38, 100, 142, 0.8),
                    rgba(0, 0, 0, 0.7));
        }

        .card {
            background: linear-gradient(135deg, rgba(38, 100, 142, 0.95), rgba(30, 79, 111, 0.95)) !important;
            backdrop-filter: blur(10px);
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #495057;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: #80bdff;
            color: #495057;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-floating label {
            color: #6c757d;
        }

        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label {
            color: #26648E;
            background-color: transparent;
        }

        .form-floating>.form-control~label {
            background-color: transparent;
        }

        .form-control::placeholder {
            color: #6c757d;
            opacity: 0.7;
        }

        .form-control,
        .form-floating label {
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #1E4F6F;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #173d57;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .alert {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            backdrop-filter: blur(10px);
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background-color: rgba(25, 135, 84, 0.2);
            color: #d1e7dd;
            border-left: 4px solid #198754;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .btn-close {
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .btn-close:hover {
            opacity: 1;
        }

        a:hover {
            color: white !important;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOutUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        .alert {
            animation-fill-mode: both;
        }

        .login-method-buttons .btn {
            transition: all 0.3s ease;
            border: none;
        }

        .login-method-buttons .btn-light {
            background-color: rgba(255, 255, 255, 0.9);
        }

        .login-method-buttons .btn-light:hover {
            background-color: #fff;
            transform: translateY(-2px);
        }

        .login-method-buttons .btn-secondary {
            background-color: rgba(108, 117, 125, 0.9);
        }

        .login-method-buttons .btn-secondary:hover {
            background-color: #6c757d;
            transform: translateY(-2px);
        }

        .login-method-buttons .btn i {
            font-size: 1.1em;
        }

        #normalLoginForm {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .card {
                margin: 1rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .login-logo {
                height: 100px;
                /* ลดขนาดโลโก้บนมือถือ */
            }

            h2 {
                font-size: 1.5rem;
            }

            .form-floating {
                margin-bottom: 1rem;
            }

            .btn {
                padding: 0.5rem 1rem;
            }

            .login-method-buttons .btn {
                font-size: 0.9rem;
                padding: 0.5rem;
            }

            .main-content {
                padding: 0 15px;
            }
        }

        /* เพิ่ม Animation สำหรับ Form */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #normalLoginForm {
            animation: slideIn 0.3s ease-out;
        }

        /* ปรับปรุง Input fields บนมือถือ */
        @media (max-width: 768px) {
            .form-control {
                font-size: 16px;
                /* ป้องกัน iOS zoom เมื่อ focus */
            }

            .form-floating>label {
                font-size: 0.9rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const normalLoginBtn = document.getElementById('normalLoginBtn');
            const normalLoginForm = document.getElementById('normalLoginForm');

            // แสดงฟอร์มล็อกอินปกติ
            normalLoginBtn.addEventListener('click', function() {
                normalLoginForm.style.display = 'block';
                normalLoginBtn.classList.add('active');
            });
        });


        function docterLogin() {
            Swal.fire({
                title: '<i class="bi bi-person-badge me-2"></i>เข้าสู่ระบบสำหรับแพทย์',
                html: `
            <form  id="doctorLoginForm" action="login.php" method="POST">
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg border border-secondary" name="username" id="doctorUsername" placeholder="กรอก Email" required>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" class="form-control form-control-lg border border-secondary" id="doctorPassword" name="password" placeholder="กรอกรหัสผ่าน" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>   
                <input type="hidden" name="role" value="doctor">   
                <div>
                    <button class="btn btn-primary shadow-sm px-4 py-2" style="border: none;" onclick="registerDoctor()">
                        สมัครบัญชีแพทย์
                    </button>
                </div>
            </form>
    `,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-box-arrow-in-right me-2 "></i>เข้าสู่ระบบ',
                cancelButtonText: '<i class="bi bi-x-circle me-2"></i>ยกเลิก',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                buttonsStyling: false, // ✅ เปลี่ยนเป็น false
                customClass: {
                    confirmButton: 'btn btn-success btn-login-confirm ', // ✅ เพิ่ม class
                    cancelButton: 'btn btn-secondary btn-login-cancel ms-2' // ✅ เพิ่ม class
                },
                allowOutsideClick: true, // ✅ ป้องกันการปิดโดยคลิกข้างนอก
                allowEscapeKey: true, // ✅ อนุญาตให้กด ESC เพื่อปิด
                didOpen: () => {
                    // เพิ่มฟังก์ชันแสดง/ซ่อนรหัสผ่าน
                    const togglePassword = document.getElementById('togglePassword');
                    const passwordInput = document.getElementById('doctorPassword');

                    togglePassword.addEventListener('click', function() {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);

                        // เปลี่ยนไอคอน
                        const icon = this.querySelector('i');
                        if (type === 'password') {
                            icon.className = 'bi bi-eye-fill';
                        } else {
                            icon.className = 'bi bi-eye-slash-fill';
                        }
                    });

                    // โฟกัส input
                    document.getElementById('doctorUsername').focus();

                    // Enter เพื่อ login
                    document.getElementById('doctorLoginForm').addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            document.querySelector('.swal2-confirm').click();
                        }
                    });
                },
                preConfirm: () => {
                    const username = document.getElementById('doctorUsername').value.trim();
                    const password = document.getElementById('doctorPassword').value;

                    if (!username || !password) {
                        Swal.showValidationMessage('กรุณากรอก Email และ รหัสผ่าน');
                        return false;
                    }

                    document.getElementById('doctorLoginForm').submit(); // ส่ง form ปกติ

                }
            })
        }

        function registerDoctor() {
            Swal.fire({
                title: '<i class="bi bi-person-plus-fill me-2"></i>สมัครบัญชีแพทย์',
                html: `
            <form id="doctorRegisterForm" action="register.php" method="POST">
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="bi bi-person-badge-fill"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg border border-secondary" name="full_name" placeholder="นพ.สมชาย ใจดี" required>
                    </div>
                </div>      
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <input type="email" class="form-control form-control-lg border border-secondary" name="usernameEmail" placeholder="กรอก Email" required>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" class="form-control form-control-lg border border-secondary" name="password_register" placeholder="กรอกรหัสผ่าน" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleRegisterPassword">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>
                   <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" class="form-control form-control-lg border border-secondary" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                    </div>
                     <div class="invalid-feedback">
            รหัสผ่านไม่ตรงกัน
        </div>
                </div>     
                <input type="hidden" name="role" value="doctor">
            </form>
    `,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-check-circle me-2"></i>สมัคร',
                cancelButtonText: '<i class="bi bi-x-circle me-2"></i>ยกเลิก',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                buttonsStyling: false, // ✅ เปลี่ยนเป็น false
                customClass: {
                    confirmButton: 'btn btn-success btn-login-confirm ', // ✅ เพิ่ม class
                    cancelButton: 'btn btn-secondary btn-login-cancel ms-2' // ✅ เพิ่ม class
                },
                allowOutsideClick: true, // ✅ ป้องกันการปิดโดยคลิกข้างนอก
                allowEscapeKey: true, // ✅ อนุญาตให้กด ESC เพื่อปิด
                didOpen: () => {
                    // เพิ่มฟังก์ชันแสดง/ซ่อนรหัสผ่าน
                    const toggleBtn = document.getElementById('toggleRegisterPassword');
                    const passwordInput = document.querySelector('input[name="password_register"]');
                    const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');

                    toggleBtn.addEventListener('click', function() {
                        // ตรวจสอบว่าทั้งสองช่องยังเป็น password อยู่หรือไม่
                        const isHidden = passwordInput.type === 'password' && confirmPasswordInput.type === 'password';

                        // สลับ type ของทั้งสองช่อง
                        passwordInput.type = isHidden ? 'text' : 'password';
                        confirmPasswordInput.type = isHidden ? 'text' : 'password';

                        // เปลี่ยนไอคอน
                        const icon = this.querySelector('i');
                        icon.className = isHidden ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
                    });

                    // โฟกัส input
                    document.querySelector('input[name="username"]').focus();

                    // Enter เพื่อสมัคร
                    document.getElementById('doctorRegisterForm').addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            document.querySelector('.swal2-confirm').click();
                        }
                    });
                },
                preConfirm: () => {
                    const username = document.querySelector('input[name="usernameEmail"]').value.trim();
                    const fullName = document.querySelector('input[name="full_name"]').value.trim();
                    const password = document.querySelector('input[name="password_register"]').value;
                    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;


                    if (!username || !fullName || !password || !confirmPassword) {
                        Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                        return false;
                    }

                    if (password !== confirmPassword) {
                        // แสดงข้อความผิดพลาด (ตัวอย่างใช้ Swal)
                        Swal.showValidationMessage('รหัสผ่านไม่ตรงกัน');
                        return false; // หยุดไม่ให้ส่งฟอร์ม
                    }
                    
                    //ส่งค่าไปบันทึกไฟล์ register_doctor.php ด้วย AJAX
                    const formData = new FormData(document.getElementById('doctorRegisterForm'));
                    formData.append('action', 'register_doctor');
                    fetch('../include/process/register_doctor.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        if (data.success) {                          
                            Swal.fire({
                                icon: 'success',
                                title: 'สมัครบัญชีแพทย์สำเร็จ',
                                text: data.message,
                                confirmButtonText: 'ตกลง'
                            }).then(() => {
                                docterLogin();
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

                }
            });
        }
    </script>

    <script>
        // เพิ่ม Touch events สำหรับมือถือ
        document.addEventListener('DOMContentLoaded', function() {
            const loginCard = document.querySelector('.card');

            // ป้องกันการ scroll เมื่อ swipe บนการ์ด
            loginCard.addEventListener('touchmove', function(e) {
                e.preventDefault();
            }, {
                passive: false
            });

            // ปรับความสูงของ viewport สำหรับมือถือ
            function adjustViewportHeight() {
                let vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            }

            window.addEventListener('resize', adjustViewportHeight);
            adjustViewportHeight();
        });
    </script>
</body>

</html>