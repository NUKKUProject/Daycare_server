<?php include __DIR__ .'/../../include/auth/auth.php'; ?>
<?php checkUserRole(['admin', 'teacher']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ .'/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include//function/pages_referen.php'; ?>
<?php require_once __DIR__. '/../../include/function/child_functions.php'; ?>
<?php include __DIR__ .'/../../include/auth/auth_dashboard.php'; ?>
<?php
$children = getChildrenData();

// รับค่า tab และ room จาก URL
$currentTab = $_GET['tab'] ?? 'all';  // ใช้ค่าเริ่มต้น 'all' หากไม่ได้รับค่า
// ดึงข้อมูลจากฟังก์ชัน
$data = getChildrenGroupedByTab($currentTab);
?>
<style>
/* Main Layout */
.main-content {
    background-color: #f8f9fa;
}

/* Scanner Section */
#scanner-container {
        display: flex;
        flex-direction: column;
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
        width: 100%;
        min-height: 250px;
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

/* Back Button */
.back-button {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background: #6c757d;
    color: white;
    border-radius: 25px;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    border: none;
    cursor: pointer;
}

.back-button:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.back-button .nav-link {
    color: white !important;
    text-decoration: none;
}

/* Page Title */
h1 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 2rem;
    text-align: center;
}

/* Tables */
.table-container {
    flex-grow: 1;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
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

/* Student List Section */
.student-section {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
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
    display: flex;
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

/* Toast */
.toast {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.toast-header {
    background: #4a90e2;
    color: white;
    border-radius: 10px 10px 0 0;
}

/* Responsive Design */
   
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
            width: 100%;
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
            width: 40%;
            min-height: 250px;
        }

        #reader {
            min-height: 250px !important;
        }
    }

    @media (min-width: 1440px) {
        #video-container {
            width: 45%;
            min-height: 250px;
        }

        #reader {
            min-height: 250px !important;
        }
    }
    .selected-guardian {
  border: 3px solid #007bff !important;  /* Bootstrap primary */
  box-shadow: 0 0 10px rgba(0,123,255,0.2);
  transition: border 0.2s, box-shadow 0.2s;
}
.form-check-input[type="radio"] {
  display: none;     /* ซ่อนปุ่ม radio */
}
</style>

<body>
    <main class="main-content">
        <div class="container-fluid mt-4">
            <button class="back-button">
                <a class="nav-link" href="attendance.php">
                    <i class="bi bi-arrow-left"></i> กลับไปหน้าเช็คชื่อมาเรียน
                </a>
            </button>

            <h1>บันทึกการเช็คชื่อกลับบ้าน วันที่ <?php echo date('d/m/Y'); ?></h1>

            <div id="scanner-container">
                <div id="video-container">
                <div id="reader"></div>
                </div>

                <div class="table-responsive" style="width:100% ; max-height: 400px; overflow: scroll; ">
                    <table class="table table-striped" >
                        <thead>
                            <tr class="table-primary">
                                <th>ลำดับ</th>
                                <th>รหัสนักเรียน</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ห้องเรียน</th>
                                <th>เวลา</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table-body">
                            <!-- ข้อมูลจะถูกเพิ่มด้วย JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Toast Notification -->
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div id="scanToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto">การแสกนสำเร็จ</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        การเช็คชื่อกลับได้ถูกบันทึกสำเร็จ
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
                            <div class="table-responsive">
                                <table class="student-table">
                                    <thead>
                                        <tr>
                                            <th>รหัสประจำตัว</th>
                                            <th>ชื่อ</th>
                                            <th>นามสกุล</th>
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
                                                    <td><span class="badge bg-info"><?= htmlspecialchars($child['child_group']) ?></span></td>
                                                    <td><span class="badge bg-primary"><?= htmlspecialchars($child['classroom']) ?></span></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = $child['status_checkout'] === 'กลับบ้านแล้ว' ? 'bg-success' : 'bg-warning';
                                                        ?>
                                                        <span class="badge <?= $statusClass ?>">
                                                            <?= htmlspecialchars($child['status_checkout']) ?>
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
    </main>
    <!-- script สำหรับแสกน qrcode เช็คชื่อ -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script>
         // เริ่มต้นการสแกน

        const attendanceTableBody = document.getElementById('attendance-table-body');

        let isScanning = false;  // ตัวแปรสำหรับเช็คสถานะการสแกน
        let lastScannedData = ''; // ตัวแปรเก็บข้อมูล QR ล่าสุดที่สแกน


        // ใช้ Html5Qrcode สแกน QR จากวิดีโอ
        const onScanSuccess = (decodedText, decodedResult) => {
            if (isScanning || decodedText === lastScannedData) return;

            isScanning = true;
            lastScannedData = decodedText;
            
            const studentData = JSON.parse(decodedText);
            
            // ดึงข้อมูลนักเรียนและผู้ปกครอง
            fetch(`../../include/attendance/get_student_guardians.php?student_id=${studentData.student_id}`)
                .then(response => response.json())
                .then(data => {
                    showPickupModal(data, studentData);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถดึงข้อมูลผู้ปกครองได้'
                    });
                })
                .finally(() => {
                    setTimeout(() => {
                        isScanning = false;
                    }, 3000);
                });
        };

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

                // ฟังก์ชันปรับขนาด qrbox เมื่อขนาดหน้าจอเปลี่ยน
        function updateQrScannerSize() {
            const newQrBoxSize = calculateQrBoxSize();
            html5QrCode.stop().then(() => {
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
                    },
                    newConfig,
                    onScanSuccess
                ).catch(err => {
                    console.error('Error restarting QR scanner:', err);
                });
            }).catch(err => {
                console.error('Error stopping QR scanner:', err);
            });
        }

       
        // เพิ่มฟังก์ชันแสดง Modal
        function showPickupModal(guardianData, studentData) {
            console.log('Guardian Data:', guardianData); // เพิ่ม debug log

            // กำหนด path ของรูป default
            const defaultAvatar = '../../../public/assets/images/avatar.png';

            Swal.fire({
                title: 'ยืนยันผู้รับเด็กกลับบ้าน',
                html: `
                    <div class="container">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>กรุณาเลือกผู้รับเด็กกลับบ้าน</h5>
                            </div>
                        </div>
                        <div class="row guardian-images">
                            <!-- รูปพ่อ -->
                            <div class="col-4">
                                <div class="card h-100 select-guardian-card" data-radio="fatherRadio" style="cursor:pointer;">
                                    <img src="${guardianData.father_image || defaultAvatar}" 
                                         class="card-img-top guardian-img rounded-circle" 
                                         alt="รูปพ่อ${guardianData.father_first_name ? ' - ' + guardianData.father_first_name : ''}"
                                         onerror="this.src='${defaultAvatar}'"
                                         style="width: 100px; height: 100px; object-fit: cover; margin: 10px auto;">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="guardian" value="father" id="fatherRadio">
                                            <label class="form-check-label" for="fatherRadio">
                                                พ่อ${guardianData.father_first_name ? ' - ' + guardianData.father_first_name : ''}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- รูปแม่ -->
                            <div class="col-4">
                                <div class="card h-100 select-guardian-card" data-radio="motherRadio" style="cursor:pointer;">
                                    <img src="${guardianData.mother_image || defaultAvatar}" 
                                         class="card-img-top guardian-img rounded-circle" 
                                         alt="รูปแม่${guardianData.mother_first_name ? ' - ' + guardianData.mother_first_name : ''}"
                                         onerror="this.src='${defaultAvatar}'"
                                         style="width: 100px; height: 100px; object-fit: cover; margin: 10px auto;">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="guardian" value="mother" id="motherRadio">
                                            <label class="form-check-label" for="motherRadio">
                                                แม่${guardianData.mother_first_name ? ' - ' + guardianData.mother_first_name : ''}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- รูปญาติ -->
                            <div class="col-4">
                                <div class="card h-100 select-guardian-card" data-radio="relativeRadio" style="cursor:pointer;">
                                    <img src="${guardianData.relative_image || defaultAvatar}" 
                                         class="card-img-top guardian-img rounded-circle" 
                                         alt="รูปญาติ${guardianData.relative_first_name ? ' - ' + guardianData.relative_first_name : ''}"
                                         onerror="this.src='${defaultAvatar}'"
                                         style="width: 100px; height: 100px; object-fit: cover; margin: 10px auto;">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="guardian" value="relative" id="relativeRadio">
                                            <label class="form-check-label" for="relativeRadio">
                                                ญาติ${guardianData.relative_first_name ? ' - ' + guardianData.relative_first_name : ''}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="card h-100 select-guardian-card" data-radio="otherRadio" style="cursor:pointer;">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="guardian" value="other" id="otherRadio">
                                        <label class="form-check-label" for="otherRadio">
                                            อื่นๆ
                                        </label>
                                    </div>
                                    <div id="otherDetails" class="mt-2" style="display: none;">
                                        <textarea class="form-control" id="otherGuardianDetails" 
                                                placeholder="กรุณาระบุรายละเอียดผู้รับเด็ก"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                didOpen: () => {
                    // จัดการการแสดง/ซ่อนช่องกรอกรายละเอียดอื่นๆ
                    document.querySelectorAll('input[name="guardian"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            document.getElementById('otherDetails').style.display = 
                                this.value === 'other' ? 'block' : 'none';
                        });
                    });

                    // กำหนดเหตุการณ์เมื่อคลิกการ์ด
                    document.querySelectorAll('.select-guardian-card').forEach(function(card){
                    card.addEventListener('click', function(e){
                        if(e.target.tagName.toLowerCase() === 'input' || e.target.tagName === 'LABEL') return;
                        var radioId = card.getAttribute('data-radio');
                        var radio = document.getElementById(radioId);
                        if(radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                        }
                    });
                    });

                    // ฟัง event change เพื่ออัปเดตขอบ
                    document.querySelectorAll('input[name="guardian"]').forEach(function(radio){
                    radio.addEventListener('change', function(e){
                        // ลูปทุกการ์ด เอาคลาส selected-guardian ออกก่อน
                        document.querySelectorAll('.select-guardian-card').forEach(function(card){
                        card.classList.remove('selected-guardian');
                        });
                        // ใส่ selected-guardian ให้การ์ดที่ radio นี้
                        if(this.checked){
                        var card = document.querySelector('[data-radio="' + this.id + '"]');
                        if(card) card.classList.add('selected-guardian');
                        }
                    });
                    });

                    // อัปเดต selected ตอนโหลด (เช่นกรณี reload แล้วค่าค้าง)
                    window.addEventListener('DOMContentLoaded', function(){
                    document.querySelectorAll('input[name="guardian"]').forEach(function(radio){
                        if(radio.checked){
                        var card = document.querySelector('[data-radio="' + radio.id + '"]');
                        if(card) card.classList.add('selected-guardian');
                        }
                    });
                    });
                },
                preConfirm: () => {
                    const selectedGuardian = document.querySelector('input[name="guardian"]:checked');
                    if (!selectedGuardian) {
                        Swal.showValidationMessage('กรุณาเลือกผู้รับเด็ก');
                        return false;
                    }
                    
                    if (selectedGuardian.value === 'other') {
                        const details = document.getElementById('otherGuardianDetails').value.trim();
                        if (!details) {
                            Swal.showValidationMessage('กรุณากรอกรายละเอียดผู้รับเด็ก');
                            return false;
                        }
                        return { type: 'other', details: details };
                    }
                    
                    return { type: selectedGuardian.value, details: null };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // ส่งข้อมูลไปบันทึก
                    const checkoutData = {
                        ...studentData,
                        picked_up_by: result.value.type,
                        picked_up_detail: result.value.details
                    };
                    
                    fetch('../../include/attendance/scan_checkedout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(checkoutData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'บันทึกสำเร็จ',
                                text: 'บันทึกการรับเด็กกลับบ้านเรียบร้อยแล้ว',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            updateAttendanceTable();
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: error.message
                        });
                    });
                }
            });
        }

        // ฟังก์ชันอัปเดตตารางการเช็คชื่อ
        function updateAttendanceTable() {
            fetch('../../include/attendance/get-checkout.php')  // ดึงข้อมูลการเช็คชื่อจากฐานข้อมูล
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch attendance data');
                    }
                    return response.json();  // แปลงข้อมูลเป็น JSON
                })
                .then(data => {
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = ''; // เคลียร์ข้อมูลก่อน

                    if (data.message) {
                        // ถ้าไม่มีข้อมูลการเช็คชื่อ
                        tableBody.innerHTML = `<tr><td colspan="6">${data.message}</td></tr>`;
                        return;
                    }

                    // ถ้ามีข้อมูล
                    data.forEach((record, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                    <td>${index + 1}</td> <!-- เพิ่มเลขลำดับ -->
                    <td>${record.student_id}</td>
                    <td>${record.prefix_th} ${record.firstname_th} ${record.lastname_th}</td>
                    <td><span class="badge bg-primary">${record.classroom}</span></td>
                    <td><span class="badge bg-secondary">${record.timestamp}</span></td>
                    <td>
                        ${record.status_checkout && 
                        record.status_checkout !== 'ยังไม่กลับบ้าน' && 
                        record.status_checkout !== null && 
                        record.status_checkout !== undefined ? 
                            `<span class="badge bg-success">${record.status_checkout}</span>` : 
                            `<span class="badge bg-secondary">ยังไม่กลับบ้าน</span>`
                        }
                    </td>
                `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching attendance:', error);
                    const tableBody = document.getElementById('attendance-table-body');
                    tableBody.innerHTML = '<tr><td colspan="6">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>';
                });
        }

        // เรียก updateAttendanceTable() ทันทีที่ DOM โหลดเสร็จ
        document.addEventListener('DOMContentLoaded', () => {
            updateAttendanceTable(); // โหลดข้อมูลการเช็คชื่อเมื่อหน้าโหลดเสร็จ
            setInterval(updateAttendanceTable, 3000);
        });

        
        // เพิ่ม CSS สำหรับ Modal
        const style = document.createElement('style');
        style.textContent = `
            .guardian-images .card {
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .guardian-images .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .guardian-img {
                height: 200px;
                object-fit: cover;
            }
            .form-check {
                padding: 10px;
                text-align: center;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>