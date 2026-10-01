<?php include __DIR__ . '/../include/auth/auth.php'; 


$_SESSION['url'] = 'testsdso;dfdsodfhsdik';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- <link rel="stylesheet" href="/css/styleHome.css" /> -->
    <link rel="icon" type="image/x-icon" href="/pic/apple-touch-icon.png" />
    <title>ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย คณะพยาบาลศาสตร์ มข.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" integrity="sha384-Gu3KVV2H9d+yA4QDpVB7VcOyhJlAVrcXd0thEjr4KznfaFPLe0xQJyonVxONa4ZC" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" integrity="sha384-EvBWSlnoFgZlXJvpzS+MAUEjvN7+gcCwH+qh7GRFOGgZO0PuwOFro7qPOJnLfe7l" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-EvBWSlnoFgZlXJvpzS+MAUEjvN7+gcCwH+qh7GRFOGgZO0PuwOFro7qPOJnLfe7l" crossorigin="anonymous">
    <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
    <!-- Favicons -->
    <meta name="theme-color" content="#7952b3">
    <!-- Custom styles for this template -->

    <!-- ใช้ fallback font ในเครื่อง เพื่อลด third-party resource ที่ไม่มี SRI -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"
        integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"
        integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha"
        crossorigin="anonymous"></script>
    <!-- <script src="dashboard.js"></script> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.3/dist/sweetalert2.min.css" integrity="sha384-gw5/Zpf2X1U9enIui8xFc+SNK5YIqEPNQ1dc0Tx3erA+znxx5UnPQpisVSel7VZH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.3/dist/sweetalert2.min.js" integrity="sha384-9GynVk5jPocYbuypwpDEOLV5SC9mGDlWn1YMabUKSQlG7DK/zmn+MX0gtHLGjXRa" crossorigin="anonymous"></script>
    <!-- Bootstrap JS (Popper.js รวมอยู่ด้วย) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha384-Yv5O+t3uE3hunW8uyrbpPW3iw6/5/Y7HitWJBLgqfMoA36NogMmy+8wWZMpn3HWc" crossorigin="anonymous">
    </script>

    <!-- ในส่วน head ให้เรียงลำดับการโหลด scripts ดังนี้ -->
    <!-- jQuery (if needed) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script>

    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>

    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>


    <link href="../../../public/assets/css/navbar.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/common.css">

    <!-- เพิ่มบรรทัดนี้หลัง Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha384-t1nt8BQoYMLFN5p42tRAtuAAFQaCQODekUVeKKZrEnEyp4H2R0RHFz0KWpmj7i8g" crossorigin="anonymous">

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
                                <button type="button" class="btn btn-light w-100 py-2 mb-3" id="normalLoginBtn">
                                    <i class="bi bi-person-fill me-2"></i>เข้าสู่ระบบสำหรับผู้ปกครอง
                                </button>
                                <a class="btn btn-success w-100 py-2 mb-3" href="https://ssonext.kku.ac.th/login?app=0198bb3e-beab-7004-95c1-864db39d9e85">
                                    <i class="bi bi-shield-lock-fill me-2"></i>เข้าสู่ระบบด้วย KKU SSO
                                </a>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-link text-white flex-fill" onclick="reportLoginIssue()">
                                        <i class="bi bi-exclamation-circle me-1"></i>แจ้งปัญหา
                                    </button>
                                    <button type="button" class="btn btn-link text-white flex-fill" onclick="checkLoginIssue()">
                                        <i class="bi bi-search me-1"></i>ตรวจสอบเรื่องที่แจ้ง
                                    </button>
                                </div>
                                <!-- <button type="button" class="btn btn-success w-100 py-2" onclick="docterLogin()">
                                    <i class="fa-solid fa-stethoscope"></i> เข้าสู่ระบบสำหรับแพทย์
                                </button> -->
                            </div>

                            <!-- ฟอร์มล็อกอินปกติ -->
                            <form action="login.php" method="POST" id="normalLoginForm" style="display: none;">
                                <input type="hidden" name="login_method" value="normal">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้" required>
                                    <label for="username">รหัสประจำตัวผู้เรียน</label>
                                </div>
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                                    <label for="password">เลขบัตรประชาชนผู้เรียน</label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
                                </button>                             
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

        /* Modal แจ้งปัญหาการเข้าสู่ระบบ */
        .issue-popup {
            border-radius: 20px !important;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(15, 40, 60, 0.35) !important;
        }

        .issue-popup-done {
            padding: 1.5rem !important;
        }

        .issue-popup .swal2-html-container.issue-body {
            margin: 0;
            padding: 0;
            text-align: left;
        }

        .issue-header {
            background: linear-gradient(135deg, #F97316, #EA580C);
            color: #fff;
            text-align: center;
            padding: 1.75rem 1.5rem 1.5rem;
        }

        .issue-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0.75rem 0 0.25rem;
        }

        .issue-header p {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.85;
        }

        .issue-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .issue-form {
            padding: 1.25rem 1.5rem 0.25rem;
        }

        .issue-field {
            display: block;
            margin-bottom: 1rem;
        }

        .issue-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #344054;
            margin-bottom: 0.35rem;
        }

        .issue-label em {
            font-style: normal;
            color: #98a2b3;
            font-weight: 400;
        }

        .issue-label b {
            color: #dc3545;
        }

        .issue-input-wrap {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: #f5f8fa;
            border: 1.5px solid #e2e8ee;
            border-radius: 12px;
            padding: 0 0.85rem;
            transition: all 0.2s ease;
        }

        .issue-input-wrap:focus-within {
            background: #fff;
            border-color: #26648E;
            box-shadow: 0 0 0 4px rgba(38, 100, 142, 0.15);
        }

        .issue-input-wrap i {
            color: #7a8ea0;
            font-size: 1rem;
        }

        .issue-input-wrap:focus-within i {
            color: #26648E;
        }

        .issue-input-wrap input,
        .issue-input-wrap textarea {
            flex: 1;
            width: 100%;
            border: 0;
            outline: 0;
            box-shadow: none;
            background: transparent;
            padding: 0.7rem 0;
            font-size: 0.95rem;
            color: #1f2d3a;
            resize: none;
        }

        .issue-textarea-wrap {
            align-items: flex-start;
        }

        .issue-textarea-wrap i {
            margin-top: 0.85rem;
        }

        .issue-hint {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #7a8ea0;
            margin-top: 0.35rem;
        }

        .issue-actions {
            gap: 0.6rem;
            margin: 0.5rem 1.5rem 1.5rem !important;
            width: auto !important;
            flex-direction: row-reverse;
        }

        .issue-btn {
            flex: 1;
            border: 0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .issue-btn-primary {
            background: linear-gradient(135deg, #26648E, #1E4F6F);
            color: #fff;
        }

        .issue-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(38, 100, 142, 0.35);
        }

        .issue-btn-ghost {
            background: #eef2f5;
            color: #475467;
        }

        .issue-btn-ghost:hover {
            background: #e2e8ee;
        }

        .issue-popup .swal2-close.issue-close {
            color: #fff;
        }

        .issue-popup .swal2-validation-message {
            margin: 0.5rem 1.5rem 0;
            border-radius: 10px;
        }

        @media (max-width: 480px) {
            .issue-form {
                padding: 1rem 1rem 0;
            }

            .issue-actions {
                margin: 0.5rem 1rem 1rem !important;
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


        function reportLoginIssue() {
            const prefill = <?= json_encode($_GET['error'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
            Swal.fire({
                width: 480,
                padding: 0,
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-send-fill me-2"></i>ส่งเรื่อง',
                cancelButtonText: 'ยกเลิก',
                buttonsStyling: false,
                customClass: {
                    popup: 'issue-popup',
                    htmlContainer: 'issue-body',
                    actions: 'issue-actions',
                    confirmButton: 'issue-btn issue-btn-primary',
                    cancelButton: 'issue-btn issue-btn-ghost',
                    closeButton: 'issue-close'
                },
                html: `
                    <div class="issue-header">
                        <div class="issue-icon"><i class="bi bi-life-preserver"></i></div>
                        <h3>แจ้งปัญหาการเข้าสู่ระบบ</h3>
                        <p>กรอกข้อมูลด้านล่าง ผู้ดูแลระบบจะตรวจสอบและแก้ไขให้</p>
                    </div>
                    <div class="issue-form">
                        <label class="issue-field">
                            <span class="issue-label">รหัสประจำตัวผู้เรียน <em>(ถ้ามี)</em></span>
                            <span class="issue-input-wrap">
                                <i class="bi bi-hash"></i>
                                <input type="text" id="issueStudentId" maxlength="50" placeholder="เช่น 6612345">
                            </span>
                        </label>
                        <label class="issue-field">
                            <span class="issue-label">เลขบัตรประชาชนผู้เรียน <em>(ถ้ามี)</em></span>
                            <span class="issue-input-wrap">
                                <i class="bi bi-credit-card-2-front"></i>
                                <input type="text" id="issueNationalId" maxlength="13" inputmode="numeric" pattern="[0-9]*" placeholder="เลข 13 หลัก">
                            </span>
                        </label>
                        <label class="issue-field">
                            <span class="issue-label">ชื่อ-สกุลผู้เรียน <b>*</b></span>
                            <span class="issue-input-wrap">
                                <i class="bi bi-person"></i>
                                <input type="text" id="issueName" maxlength="100" placeholder="ชื่อ นามสกุล">
                            </span>
                        </label>
                        <label class="issue-field">
                            <span class="issue-label">รายละเอียดปัญหา <b>*</b></span>
                            <span class="issue-input-wrap issue-textarea-wrap">
                                <i class="bi bi-chat-left-text"></i>
                                <textarea id="issueDesc" maxlength="1000" rows="3" placeholder="อธิบายปัญหาที่พบ เช่น เข้าสู่ระบบไม่ได้ ขึ้นข้อความ..."></textarea>
                            </span>
                            <span class="issue-hint"><span><i class="bi bi-shield-lock me-1"></i>อย่าใส่รหัสผ่านในช่องนี้</span><span id="issueCount">0/1000</span></span>
                        </label>
                        <input type="text" id="issueWebsite" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">
                    </div>
                `,
                didOpen: () => {
                    const desc = document.getElementById('issueDesc');
                    const count = document.getElementById('issueCount');
                    desc.value = prefill;
                    const update = () => count.textContent = desc.value.length + '/1000';
                    desc.addEventListener('input', update);
                    update();
                    document.getElementById('issueName').focus();
                },
                preConfirm: () => {
                    const data = new FormData();
                    data.append('student_id', document.getElementById('issueStudentId').value.trim());
                    data.append('national_id', document.getElementById('issueNationalId').value.trim());
                    data.append('student_name', document.getElementById('issueName').value.trim());
                    data.append('description', document.getElementById('issueDesc').value.trim());
                    data.append('website', document.getElementById('issueWebsite').value);

                    if (!data.get('student_name') || !data.get('description')) {
                        Swal.showValidationMessage('กรุณากรอกข้อมูลที่มี * ให้ครบถ้วน');
                        return false;
                    }

                    const nationalId = data.get('national_id');
                    if (nationalId && !/^\d{13}$/.test(nationalId)) {
                        Swal.showValidationMessage('เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก');
                        return false;
                    }

                    return fetch('../include/process/report_login_issue.php', {
                            method: 'POST',
                            body: data
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (!res.success) {
                                Swal.showValidationMessage(res.message);
                                return false;
                            }
                            return res;
                        })
                        .catch(() => {
                            Swal.showValidationMessage('ไม่สามารถส่งข้อมูลได้ กรุณาลองใหม่');
                            return false;
                        });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ส่งเรื่องเรียบร้อย',
                        text: result.value.message,
                        confirmButtonText: 'ตกลง',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'issue-popup issue-popup-done',
                            confirmButton: 'issue-btn issue-btn-primary'
                        }
                    });
                }
            });
        }

        function checkLoginIssue() {
            Swal.fire({
                width: 460,
                padding: 0,
                showCloseButton: true,
                showCancelButton: false,
                confirmButtonText: '<i class="bi bi-search me-2"></i>ค้นหา',
                buttonsStyling: false,
                customClass: {
                    popup: 'issue-popup',
                    htmlContainer: 'issue-body',
                    actions: 'issue-actions',
                    confirmButton: 'issue-btn issue-btn-primary',
                    closeButton: 'issue-close'
                },
                html: `
                    <div class="issue-header" style="background: linear-gradient(135deg, #1E4F6F, #26648E);">
                        <div class="issue-icon"><i class="bi bi-search"></i></div>
                        <h3>ตรวจสอบเรื่องที่แจ้ง</h3>
                        <p>กรอกรหัสประจำตัวผู้เรียนเพื่อดูสถานะ</p>
                    </div>
                    <div class="issue-form">
                        <label class="issue-field">
                            <span class="issue-label">รหัสประจำตัวผู้เรียน <b>*</b></span>
                            <span class="issue-input-wrap">
                                <i class="bi bi-hash"></i>
                                <input type="text" id="checkStudentId" maxlength="50" placeholder="เช่น 6612345">
                            </span>
                        </label>
                        <div id="issueResultArea" style="margin-bottom:0.5rem;"></div>
                    </div>
                `,
                didOpen: () => {
                    document.getElementById('checkStudentId').focus();
                },
                preConfirm: async () => {
                    const sid = document.getElementById('checkStudentId').value.trim();
                    if (!sid) {
                        Swal.showValidationMessage('กรุณากรอกรหัสประจำตัวผู้เรียน');
                        return false;
                    }
                    const data = new FormData();
                    data.append('student_id', sid);
                    const res = await fetch('../include/process/get_login_issues.php', { method: 'POST', body: data })
                        .then(r => r.json())
                        .catch(() => null);
                    if (!res) {
                        Swal.showValidationMessage('ไม่สามารถเชื่อมต่อได้ กรุณาลองใหม่');
                        return false;
                    }
                    if (!res.success) {
                        Swal.showValidationMessage(res.message);
                        return false;
                    }
                    const sid = document.getElementById('checkStudentId').value.trim();
                    const area = document.getElementById('issueResultArea');
                    if (!res.data || res.data.length === 0) {
                        area.innerHTML = `<div style="text-align:center;padding:1rem;color:#6c757d;font-size:0.9rem;"><i class="bi bi-inbox me-2"></i>ไม่พบเรื่องที่แจ้งสำหรับรหัสนี้</div>`;
                    } else {
                        const statusLabel = { pending: '<span style="color:#f97316;font-size:0.8rem;">รอดำเนินการ</span>', resolved: '<span style="color:#198754;font-size:0.8rem;">แก้ไขแล้ว</span>' };
                        const rows = res.data.map(r => `
                            <div id="issue-card-${r.id}" style="border:1px solid #e2e8ee;border-radius:10px;padding:0.75rem 1rem;margin-bottom:0.6rem;font-size:0.88rem;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.3rem;">
                                    <span style="font-weight:600;color:#1f2d3a;">${r.student_name}</span>
                                    ${statusLabel[r.status] || statusLabel.pending}
                                </div>
                                <div style="color:#475467;white-space:pre-wrap;margin-bottom:0.4rem;">${r.description}</div>
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <span style="color:#98a2b3;font-size:0.78rem;">${r.created_at}</span>
                                    ${r.status !== 'resolved' ? `<button onclick="openAppend(${r.id},'${encodeURIComponent(sid)}')" style="border:none;background:none;color:#26648E;font-size:0.8rem;cursor:pointer;padding:0;"><i class="bi bi-pencil-square me-1"></i>เพิ่มรายละเอียด</button>` : ''}
                                </div>
                                <div id="append-form-${r.id}" style="display:none;margin-top:0.6rem;">
                                    <div class="issue-input-wrap issue-textarea-wrap" style="margin-bottom:0.4rem;">
                                        <i class="bi bi-chat-left-text" style="margin-top:0.6rem;"></i>
                                        <textarea id="append-text-${r.id}" maxlength="500" rows="2" placeholder="พิมพ์รายละเอียดเพิ่มเติม..."></textarea>
                                    </div>
                                    <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                                        <button onclick="cancelAppend(${r.id})" style="border:none;background:#eef2f5;color:#475467;border-radius:8px;padding:0.3rem 0.8rem;font-size:0.82rem;cursor:pointer;">ยกเลิก</button>
                                        <button onclick="submitAppend(${r.id},'${encodeURIComponent(sid)}')" style="border:none;background:linear-gradient(135deg,#26648E,#1E4F6F);color:#fff;border-radius:8px;padding:0.3rem 0.8rem;font-size:0.82rem;cursor:pointer;">ส่ง</button>
                                    </div>
                                </div>
                            </div>`).join('');
                        area.innerHTML = `<div style="max-height:320px;overflow-y:auto;">${rows}</div>`;
                    }
                    // ไม่ปิด popup — ให้ผู้ใช้ดูผลแล้วกด X เอง
                    return false;
                }
            });
        }

        function openAppend(id, encodedSid) {
            document.getElementById('append-form-' + id).style.display = 'block';
            document.getElementById('append-text-' + id).focus();
        }

        function cancelAppend(id) {
            document.getElementById('append-form-' + id).style.display = 'none';
            document.getElementById('append-text-' + id).value = '';
        }

        async function submitAppend(id, encodedSid) {
            const text = document.getElementById('append-text-' + id).value.trim();
            if (!text) return;
            const btn = document.querySelector(`#append-form-${id} button:last-child`);
            btn.disabled = true;
            btn.textContent = 'กำลังส่ง...';

            const data = new FormData();
            data.append('issue_id', id);
            data.append('student_id', decodeURIComponent(encodedSid));
            data.append('append_text', text);

            const res = await fetch('../include/process/append_login_issue.php', { method: 'POST', body: data })
                .then(r => r.json())
                .catch(() => null);

            if (!res || !res.success) {
                btn.disabled = false;
                btn.textContent = 'ส่ง';
                Swal.showValidationMessage(res?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
                return;
            }

            // ซ่อนฟอร์มและแสดง toast เล็ก ๆ
            cancelAppend(id);
            const card = document.getElementById('issue-card-' + id);
            const toast = document.createElement('div');
            toast.style.cssText = 'background:#d1fae5;color:#065f46;border-radius:8px;padding:0.4rem 0.8rem;font-size:0.82rem;margin-top:0.4rem;';
            toast.textContent = '✓ เพิ่มรายละเอียดเรียบร้อยแล้ว';
            card.appendChild(toast);
        }

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
