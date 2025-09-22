<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <!-- Logo และชื่อ -->
        <a class="navbar-brand d-flex align-items-center" href="../../index.php">
            <img src="../../../public/assets/images/logo.png" alt="Logo" class="nav-logo me-2">
            <div class="brand-text">
                <span class="brand-title">ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย</span>
                <span class="brand-subtitle d-none d-sm-inline">คณะพยาบาลศาสตร์ มหาวิทยาลัยขอนแก่น</span>
            </div>
        </a>

        <!-- ปุ่ม Toggle สำหรับมือถือ -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>

        <!-- เมนู -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php">หน้าหลัก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">เกี่ยวกับเรา</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">หลักสูตร</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">ข่าวสาร</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">ติดต่อ</a>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-outline-light btn-sm px-3" href="/app/views/login.php">
                        <i class="fas fa-user me-2"></i>เข้าสู่ระบบ
                    </a>
                </li>
                <!-- <li class="nav-item ms-lg-2">
                    <a class="btn btn-primary btn-sm px-3" href="register">
                        <i class="fas fa-user-plus me-2"></i>ลงทะเบียน
                    </a>
                </li> -->
            </ul>
        </div>
    </div>

    <style>
        .navbar {
            background: rgba(38, 100, 142, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(38, 100, 142, 0.98);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-logo {
            height: 40px;
            width: auto;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            transition: all 0.3s ease;
        }

        .brand-title {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .navbar-brand:hover .brand-title {
            color: rgba(255, 255, 255, 0.9);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateY(-1px);
        }

        .navbar-toggler {
            border: none;
            color: white;
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .btn-outline-light {
            border-width: 2px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

        /* ปรับปรุงการตอบสนองของปุ่ม */
        .btn {
            position: relative;
            overflow: hidden;
            transform: translateZ(0);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        .btn:active::after {
            width: 200%;
            height: 200%;
            opacity: 0;
        }

        /* ปรับปรุงสำหรับมือถือ */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(38, 100, 142, 0.98);
                padding: 1rem;
                border-radius: 0.5rem;
                margin-top: 0.5rem;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 1000;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .navbar-brand {
                max-width: 75%;
            }

            .brand-text {
                max-width: calc(100% - 50px); /* ลบด้วยความกว้างของ logo */
            }

            .brand-title {
                font-size: 1rem;
                max-width: 200px;
            }

            .brand-subtitle {
                font-size: 0.75rem;
                opacity: 0.8;
            }

            /* ปรับปรุงปุ่มใน navbar */
            .navbar .btn {
                width: 100%;
                margin: 0.5rem 0;
                padding: 0.75rem 1rem;
                transition: all 0.2s ease;
            }

            .navbar .btn:active {
                transform: scale(0.98);
            }

            /* เพิ่ม active state ที่ชัดเจน */
            .nav-link:active,
            .btn:active {
                background-color: rgba(255, 255, 255, 0.1);
            }
        }

        /* ปรับขนาดตามหน้าจอ */
        @media (max-width: 576px) {
            .brand-title {
                font-size: 0.9rem;
                max-width: 150px;
            }

            .brand-subtitle {
                display: none;
            }

            .nav-logo {
                height: 35px;
            }
        }

        /* ปรับปรุง Animation ของ Navbar */
        .navbar-collapse {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .navbar-collapse.collapsing {
            transform: translateY(-10px);
            opacity: 0;
        }

        .navbar-collapse.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // จัดการการคลิกปุ่มและการแสดงผล active state
            const navLinks = document.querySelectorAll('.nav-link, .btn');
            
            navLinks.forEach(link => {
                link.addEventListener('touchstart', function() {
                    this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                });
                
                link.addEventListener('touchend', function() {
                    this.style.backgroundColor = '';
                });
            });

            // ปรับขนาดชื่อตามความกว้างที่มี
            function adjustBrandText() {
                const brandText = document.querySelector('.brand-text');
                const brandTitle = document.querySelector('.brand-title');
                const container = document.querySelector('.navbar > .container');
                
                if (brandText && brandTitle && container) {
                    const maxWidth = container.offsetWidth * 0.6; // ใช้ 60% ของความกว้าง container
                    brandText.style.maxWidth = `${maxWidth}px`;
                }
            }

            window.addEventListener('resize', adjustBrandText);
            adjustBrandText();
        });
    </script>
</nav>
