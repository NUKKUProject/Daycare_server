<footer class="footer mt-auto py-4">
    <div class="container">
        <div class="row">
            <!-- ข้อมูลติดต่อ -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="text-white mb-3">ติดต่อเรา</h5>
                <p class="text-white-50 mb-1">
                    <i class="bi bi-geo-alt-fill me-2"></i>
####
                </p>
                <p class="text-white-50 mb-1">
                    <i class="bi bi-telephone-fill me-2"></i>
####
                </p>
                <p class="text-white-50 mb-3">
                    <i class="bi bi-envelope-fill me-2"></i>
                    info@kku.ac.th
                </p>
                <div class="social-links">
                    <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-twitter" style="color: #40C4FF;"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-youtube" style="color: #F44336;"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-line"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="text-white mb-3">ลิงก์ด่วน</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-chevron-right me-2"></i>เกี่ยวกับเรา
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-chevron-right me-2"></i>ข่าวประชาสัมพันธ์
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-chevron-right me-2"></i>ปฏิทินกิจกรรม
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-chevron-right me-2"></i>ติดต่อสอบถาม
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="col-lg-4">
                <h5 class="text-white mb-3">รับข่าวสารจากเรา</h5>
                <p class="text-white-50 mb-3">ลงทะเบียนเพื่อรับข่าวสารและกิจกรรมล่าสุด</p>
                <div class="input-group mb-3">
                    <input type="email" class="form-control" placeholder="อีเมลของคุณ" aria-label="อีเมลของคุณ">
                    <button class="btn btn-primary" type="button">สมัครรับข่าวสาร</button>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="row mt-4">
            <div class="col-12">
                <hr class="border-white-50">
                <p class="text-white-50 text-center mb-0">
                    © <?php echo date('Y'); ?> ###. สงวนลิขสิทธิ์.
                </p>
            </div>
        </div>
    </div>

    <style>
        .footer {
            background: linear-gradient(135deg, rgba(38, 100, 142, 0.97), rgba(30, 79, 111, 0.97));
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        .hover-white:hover {
            color: white !important;
            transition: color 0.3s ease;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .footer {
                padding: 2rem 0;
            }

            .footer h5 {
                font-size: 1.1rem;
                margin-bottom: 1rem;
            }

            .social-links {
                margin-bottom: 1.5rem;
            }

            .social-links a {
                width: 40px;
                height: 40px;
                margin-right: 1rem;
            }

            .input-group {
                flex-direction: column;
            }

            .input-group .form-control {
                border-radius: 4px;
                margin-bottom: 0.5rem;
            }

            .input-group .btn {
                border-radius: 4px;
                width: 100%;
            }
        }
    </style>
</footer>
