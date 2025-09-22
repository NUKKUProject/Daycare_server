<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['admin', 'teacher']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>
<?php
$children = getChildrenData();

// รับค่า tab และ room จาก URL
$currentTab = $_GET['tab'] ?? 'all';  // ใช้ค่าเริ่มต้น 'all' หากไม่ได้รับค่า
// ดึงข้อมูลจากฟังก์ชัน
$data = getChildrenGroupedByTab($currentTab);
?>

<style>
    .attendance {
        padding: 0;
    }

    #scanner-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);

        padding: 1rem;
    }

    #video-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 50%;
        min-height: 300px;
        /* กำหนดความสูงขั้นต่ำ */
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    #reader {
        display: flex;
        width: 100% !important;
        height: auto !important;
        min-height: 300px !important;
        border: none !important;
    }

    /* Attendance Table */
    .table-container {
        flex-grow: 1;
    }

    /* Page Title */
    h1 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 2rem;
        text-align: center;
    }

    .https {
        font-size: 1.3rem;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    table th {
        background-color: #4a90e2;
        color: white;
        padding: 1rem;
        font-weight: 500;
        text-align: center;
    }

    table td {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        text-align: center;
    }

    tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Checkout Button */
    .checkout-button {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        background: #4a90e2;
        color: white;
        border-radius: 25px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 2rem;
        border: none;
        cursor: pointer;
    }

    .checkout-button:hover {
        background: #357abd;
        transform: translateY(-2px);
    }

    /* Tabs */
    .nav-tabs {
        border: none;
        margin-bottom: 1.5rem;
        gap: 0.5rem;
        display: flex;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 0.8rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .nav-tabs .nav-link:hover {
        background: #e9ecef;
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        background: #4a90e2;
        color: white;
    }

    /* Toast */
    .toast {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .toast-header {
        background: #4a90e2;
        color: white;
        border-radius: 10px 10px 0 0;
    }

    /* Responsive Design */
    @media (max-width: 480px) {
        #scanner-container {
            flex-direction: column;
            gap: 15px;
            padding: 0.5rem;
        }

        #video-container {
            width: 100%;
            min-height: 280px;
        }

        #reader {
            min-height: 280px !important;
        }
    }

    @media (min-width: 481px) and (max-width: 768px) {
        #scanner-container {
            flex-direction: column;
            gap: 18px;
        }

        #video-container {
            width: 90%;
            min-height: 320px;
        }

        #reader {
            min-height: 320px !important;
        }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
        #scanner-container {
            flex-direction: column;
            gap: 18px;
        }
        #video-container {
            width: 60%;
            min-height: 350px;
        }

        #reader {
            min-height: 350px !important;
        }
    }

    @media (min-width: 1025px) {
        #video-container {
            width: 50%;
            min-height: 400px;
        }

        #reader {
            min-height: 400px !important;
        }
    }

    @media (min-width: 1440px) {
        #video-container {
            width: 45%;
            min-height: 450px;
        }

        #reader {
            min-height: 450px !important;
        }
    }

    /* Responsive Design */


    /* Student Section Styles */
    .student-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        margin-top: 2rem;
    }

    .section-title {
        color: #2c3e50;
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #4a90e2;
    }

    /* Group Styles */
    .group-section {
        margin-bottom: 2rem;
    }

    .group-header {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .group-header h3 {
        color: #2c3e50;
        margin: 0;
        font-size: 1.4rem;
    }

    /* Classroom Styles */
    .classroom-section {
        margin-bottom: 2rem;
        padding: 0 1rem;
    }

    .classroom-title {
        color: #4a90e2;
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }

    /* Table Styles */
    .student-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }

    .student-table th {
        background-color: #4a90e2;
        color: white;
        padding: 1rem;
        font-weight: 500;
        text-align: center;
    }

    .student-table td {
        padding: 0.8rem;
        border-bottom: 1px solid #eee;
        text-align: center;
        vertical-align: middle;
    }

    .student-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Badge Styles */
    .badge {
        padding: 0.5em 1em;
        font-weight: 500;
        font-size: 0.85em;
        border-radius: 20px;
    }

    /* Tab Styles */
    .nav-tabs {
        border: none;
        margin-bottom: 1.5rem;
        gap: 0.5rem;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 0.8rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-tabs .nav-link i {
        font-size: 1.1em;
    }

    .nav-tabs .nav-link:hover {
        background: #e9ecef;
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        background: #4a90e2;
        color: white;
    }

    /* Empty State */
    .text-muted {
        color: #6c757d;
        font-style: italic;
    }

    /* Icons */
    .bi {
        margin-right: 0.3rem;
    }
</style>

<body>
    <main class="main-content">
        <div class="container-fluid mt-4">
            <button class="checkout-button">
                <a class="nav-link" href="scan_checkout.php">
                    <i class="bi bi-box-arrow-right"></i> ไปหน้าเช็คชื่อกลับบ้าน
                </a>
            </button>
            <h1>บันทึกการเช็คชื่อมาเรียน วันที่ <?php echo date('d/m/Y'); ?><br>
                
            </h1>

            <div id="scanner-container">

                <!-- กล้องพร้อมกรอบ -->

                <div id="video-container">
                    <div id="reader"></div>
                </div>

                
                <!-- ตารางแสดงข้อมูลเช็คชื่อ -->
                <div class="table-responsive" style="width:100% ; max-height: 400px; overflow: scroll; ">
                    <table class="table table-striped" >
                        <thead>
                            <tr class="table-primary">
                                <th>ลำดับ</th>
                                <th>รหัสนักเรียน</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ห้องเรียน</th>
                                <th>วันเวลา</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table-body">
                            <?php if (count($children) > 0): ?>
                                <?php
                                $counter = 1; // เริ่มต้นตัวนับที่ 1
                                $hasAttendance = false; // ตัวแปรเพื่อตรวจสอบว่ามีข้อมูลการเช็คชื่อหรือไม่
                                foreach ($children as $record):
                                    if (!empty($record['check_date'])): // แสดงเฉพาะรายการที่มีการเช็คชื่อ
                                        $hasAttendance = true;
                                ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($record['studentid']); ?></td>
                                            <td><?php echo htmlspecialchars($record['prefix_th'] . ' ' . $record['firstname_th'] . ' ' . $record['lastname_th']); ?></td>
                                            <td><?php echo htmlspecialchars($record['classroom']); ?></td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($record['check_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['status']); ?></td>
                                        </tr>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                                <?php if (!$hasAttendance): // ถ้าไม่มีรายการที่มีการเช็คชื่อ 
                                ?>
                                    <tr>
                                        <td colspan="6">ไม่มีข้อมูลการเช็คชื่อในวันนี้</td>
                                    </tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">ไม่มีข้อมูลการเช็คชื่อในวันนี้</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Toast -->
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div id="scanToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto">การแสกนสำเร็จ</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        การเช็คชื่อได้ถูกบันทึกสำเร็จ
                    </div>
                </div>
            </div>

            <div class="student-section">
                <h2 class="section-title">รายชื่อเด็กในระบบ</h2>

                <!-- Tabs -->
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'all' ? 'active' : '' ?>" href="?tab=all">
                            <i class="bi bi-people-fill"></i> ทั้งหมด
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'big' ? 'active' : '' ?>" href="?tab=big">
                            <i class="bi bi-person-check-fill"></i> เด็กโต
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'medium' ? 'active' : '' ?>" href="?tab=medium">
                            <i class="bi bi-person"></i> เด็กกลาง
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentTab === 'prep' ? 'active' : '' ?>" href="?tab=prep">
                            <i class="bi bi-person-heart"></i> เตรียมอนุบาล
                        </a>
                    </li>
                </ul>

                <!-- Student Groups -->
                <?php foreach ($data as $groupData): ?>
                    <div class="group-section">
                        <div class="group-header">
                            <h3><i class="bi bi-bookmark-star-fill"></i> <?= htmlspecialchars($groupData['group']) ?></h3>
                        </div>
                        <?php foreach ($groupData['classrooms'] as $classroomData): ?>
                            <div class="classroom-section">
                                <h4 class="classroom-title">
                                    <i class="bi bi-door-open-fill"></i> ห้อง: <?= htmlspecialchars($classroomData['classroom']) ?>
                                </h4>
                                <div class="table-responsive" style="max-height:350px; overflow:scroll; overflow-x:hidden;">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr class="table-primary">
                                                <th>รหัสประจำตัว</th>
                                                <th>ชื่อ</th>
                                                <th>นามสกุล</th>
                                                <th>ชื่อเล่น</th>
                                                <th>กลุ่ม</th>
                                                <th>ห้องเรียน</th>
                                                <th>สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($classroomData['children'])): ?>
                                                <?php foreach ($classroomData['children'] as $child): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($child['studentid']) ?></td>
                                                        <td><?= htmlspecialchars($child['prefix_th']) ?> <?= htmlspecialchars($child['firstname_th']) ?></td>
                                                        <td><?= htmlspecialchars($child['lastname_th']) ?></td>
                                                        <td><?= htmlspecialchars($child['nickname']) ?></td>
                                                        <td><span class="badge bg-info"><?= htmlspecialchars($child['child_group']) ?></span></td>
                                                        <td><span class="badge bg-primary"><?= htmlspecialchars($child['classroom']) ?></span></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = $child['status'] === 'มาเรียน' ? 'bg-success' : 'bg-danger';
                                                            ?>
                                                            <span class="badge <?= $statusClass ?>">
                                                                <?= htmlspecialchars($child['status']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">
                                                        <i class="bi bi-inbox"></i> ไม่มีข้อมูลในห้องนี้
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </main>
    <!-- script สำหรับแสกน qrcode เช็คชื่อ -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script>
        const attendanceTableBody = document.getElementById('attendance-table-body');
        let isScanning = false; // ตัวแปรสำหรับเช็คสถานะการสแกน
        let lastScannedData = ''; // ตัวแปรเก็บข้อมูล QR ล่าสุดที่สแกน

        // ฟังก์ชันสำหรับการสแกน QR Code
        const onScanSuccess = (decodedText, decodedResult) => {
            if (isScanning || decodedText === lastScannedData) return;

            isScanning = true;
            lastScannedData = decodedText;

            try {
                const studentData = JSON.parse(decodedText);

                // ส่งข้อมูลไปยัง server
                fetch('../../include/attendance/attendance-submit.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(studentData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {

                        if (data.status === 'success') {
                            // แสดง Sweet Alert สำเร็จ
                            Swal.fire({
                                icon: 'success',
                                title: 'บันทึกสำเร็จ',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                // อัพเดทตารางแสดงผล
                                updateAttendanceTable();
                            });
                        } else {
                            throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึก');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: error.message
                        });
                    })
                    .finally(() => {
                        setTimeout(() => {
                            isScanning = false;
                        }, 3000);
                    });

            } catch (error) {
                console.error('Error parsing QR code data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'QR Code ไม่ถูกต้อง'
                });
                setTimeout(() => {
                    isScanning = false;
                }, 3000);
            }
        };

        // ฟังก์ชันอัพเดทตารางแสดงผล
        function updateAttendanceTable() {
            fetch('../../include/attendance/get-attendance.php')
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = '';

                    if (data.message) {
                        tableBody.innerHTML = `<tr><td colspan="6" class="text-center">${data.message}</td></tr>`;
                        return;
                    }

                    data.forEach((record, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${record.student_id}</td>
                            <td>${record.prefix_th} ${record.firstname_th} ${record.lastname_th}</td>
                            <td>${record.classroom}</td>
                            <td>${record.timestamp}</td>
                            <td>
                                <span class="badge ${getBadgeClass(record.status)}">
                                    ${record.status}
                                </span>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                });
        }

        // ฟังก์ชันกำหนดสีของ badge
        function getBadgeClass(status) {
            switch (status) {
                case 'มาเรียน':
                    return 'bg-success';
                case 'มาสาย':
                    return 'bg-warning';
                case 'ขาดเรียน':
                    return 'bg-danger';
                default:
                    return 'bg-secondary';
            }
        }

        // เริ่มต้นการสแกน
        const html5QrCode = new Html5Qrcode("reader");
        const readerElem = document.getElementById('reader');

        // ฟังก์ชันคำนวณขนาด qrbox ตามหน้าจอ
        function calculateQrBoxSize() {
            const containerWidth = readerElem.offsetWidth;
            const viewportWidth = window.innerWidth;
            let qrBoxSize;

            if (viewportWidth <= 480) {
                // หน้าจอเล็ก (มือถือ)
                qrBoxSize = Math.min(containerWidth * 0.8, 250);
            } else if (viewportWidth <= 768) {
                // หน้าจอขนาดกลาง (แท็บเล็ต)
                qrBoxSize = Math.min(containerWidth * 0.7, 300);
            } else if (viewportWidth <= 1024) {
                // หน้าจอขนาดใหญ่ (แล็ปท็อป)
                qrBoxSize = Math.min(containerWidth * 0.6, 350);
            } else {
                // หน้าจอขนาดใหญ่มาก (เดสก์ท็อป)
                qrBoxSize = Math.min(containerWidth * 0.5, 400);
            }

            return Math.max(200, qrBoxSize); // ขนาดต่ำสุด 200px
        }

        const qrBoxSize = calculateQrBoxSize();

        const config = {
            fps: 10,
            qrbox: {
                width: qrBoxSize,
                height: qrBoxSize
            },
            aspectRatio: 1.0 // รักษาสัดส่วนให้เป็นสี่เหลี่ยมจัตุรัส
        };

        html5QrCode.start({
                facingMode: "environment"
            },
            config,
            onScanSuccess
        ).catch(err => {
            console.error('Error starting QR scanner:', err);
        });

        // เพิ่มการ resize เมื่อหน้าจอเปลี่ยนขนาด
        window.addEventListener('resize', () => {
            // รอสักครู่ก่อนเริ่มใหม่เพื่อหลีกเลี่ยง lag
            setTimeout(() => {
                html5QrCode.stop().then(() => {
                    const newQrBoxSize = calculateQrBoxSize();
                    const newConfig = {
                        fps: 10,
                        qrbox: {
                            width: newQrBoxSize,
                            height: newQrBoxSize
                        },
                        aspectRatio: 1.0
                    };

                    html5QrCode.start({
                        facingMode: "environment"
                    }, newConfig, onScanSuccess);
                }).catch(err => console.error('Error restarting scanner:', err));
            }, 500);
        });
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                html5QrCode.stop().then(() => {
                    const newQrBoxSize = calculateQrBoxSize();
                    const newConfig = {
                        fps: 10,
                        qrbox: {
                            width: newQrBoxSize,
                            height: newQrBoxSize
                        },
                        aspectRatio: 1.0
                    };

                    html5QrCode.start({
                        facingMode: "environment"
                    }, newConfig, onScanSuccess);
                }).catch(err => console.error('Error restarting scanner:', err));
            }, 1000); // รอให้ orientation เสร็จสิ้น
        });

        // อัพเดทตารางเมื่อโหลดหน้าเว็บ
        document.addEventListener('DOMContentLoaded', () => {
            updateAttendanceTable();
            // อัพเดททุก 30 วินาที
            setInterval(updateAttendanceTable, 30000);
        });
    </script>

    <script>
        // ฟังก์ชันที่ใช้ดึงข้อมูลเด็กจากฐานข้อมูล
        function getChildren() {
            var child_group = document.getElementById('child_group').value;
            var classroom = document.getElementById('classroom').value;

            if (child_group && classroom) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '../../include/attendance/get_children.php?child_group=' + child_group + '&classroom=' + classroom, true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var data = JSON.parse(xhr.responseText);
                        var output = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>รหัสประจำตัว</th>
                            <th>ชื่อ</th>
                            <th>นามสกุล</th>
                            <th>ชื่อเล่น</th>
                            <th>กลุ่ม</th>
                            <th>ห้องเรียน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>`;

                        // วนลูปรายชื่อเด็กและสร้างแถวในตาราง
                        data.forEach(function(child, index) {
                            output += `
                    <tr>
                        <td>${child.studentid}</td>
                        <td>${child.prefix_th} ${child.firstname_th}</td>
                        <td>${child.lastname_th}</td>
                        <td>${child.nickname}</td>
                        <td>${child.child_group}</td>
                        <td>${child.classroom}</td>
                        <td>${child.status}</td>

                    </tr>`;
                        });

                        output += `</tbody></table>`;
                        document.getElementById('childrenList').innerHTML = output;
                    }
                };
                xhr.send();
            }
        }

        $(document).ready(function() {
            $('#example').DataTable({
                //disable sorting on last column
                "columnDefs": [{
                    "orderable": false,
                    "targets": 5
                }],
                language: {
                    //customize pagination prev and next buttons: use arrows instead of words
                    'paginate': {
                        'previous': '<span class="fa fa-chevron-left"></span>',
                        'next': '<span class="fa fa-chevron-right"></span>'
                    },
                    //customize number of elements to be displayed
                    "lengthMenu": 'Display <select class="form-control input-sm">' +
                        '<option value="10">10</option>' +
                        '<option value="20">20</option>' +
                        '<option value="30">30</option>' +
                        '<option value="40">40</option>' +
                        '<option value="50">50</option>' +
                        '<option value="-1">All</option>' +
                        '</select> results'
                }
            })
        });
    </script>
</body>

</html>