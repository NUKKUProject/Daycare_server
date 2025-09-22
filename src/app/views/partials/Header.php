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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <!-- เพิ่มในส่วน แสดงกราฟ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ในส่วน head ให้เรียงลำดับการโหลด scripts ดังนี้ -->
    <!-- jQuery (if needed) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">


    <link href="../../../public/assets/css/dashboard.css" rel="stylesheet">


    <link href="../../../public/assets/css/navbar.css" rel="stylesheet">
    <link href="../../../public/assets/css/dashboard.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/common.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/public/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="/public/assets/images/apple-touch-icon.png">

    <!-- Logo preload -->
    <link rel="preload" href="../../../public/assets/images/logo.png" as="image">
    <link rel="preload" href="../../../public/assets/images/logo-small.png" as="image">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CoreUI icon -->
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/free.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/brand.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/flag.min.css">

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

    <!-- (Optional) Latest compiled and minified JavaScript translation files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/i18n/defaults-*.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />

    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <style>
        /* Custom styles for logo */
        .site-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .site-logo img {
            height: 50px;
            width: auto;
            object-fit: contain;
        }

        @media (max-width: 768px) {
            .site-logo img {
                height: 40px;
            }
        }

        .bootstrap-select .dropdown-menu li a:focus,
        .bootstrap-select .dropdown-menu .show>li>a:active:hover,
        .bootstrap-select .dropdown-menu .show>li>a.active:hover,
        .bootstrap-select .dropdown-menu li a:hover {
            background-color: #c7dcf9ff;
            /* เปลี่ยนเป็นสีเทาอ่อน */
            color: #000;
            /* เปลี่ยนสีข้อความเป็นสีดำ */
        }

        .bootstrap-select .dropdown-menu .show>li>a.active,
        .bootstrap-select .dropdown-menu .show>li>a:active {
            background-color: #f0f0f0;
            /* เปลี่ยนเป็นสีเทาอ่อน */
            color: #000;
            /* เปลี่ยนสีข้อความเป็นสีดำ */
        }
    </style>
</head>