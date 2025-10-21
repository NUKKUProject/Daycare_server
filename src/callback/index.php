<?php
// เก็บข้อมูลใน session
session_start();
include '../config/database.php';


$pdo = getDatabaseConnection();



$code = $_GET['code'];


$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://ssonext-api.kku.ac.th/auth.token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => '{ 
    "code": "' . $code . '",
    "redirectUrl": "https://mis-daycare.kku.ac.th/callback",
    "clientId": "0198bb3e-beab-7004-95c1-864db39d9e85",
    "clientSecret":"xFrsvhQ6qTatibLLY4L6iPsn7xNjMrkT91gub0bRjbta3XagBm9AE7VYHgeDHibY"
}',
    CURLOPT_HTTPHEADER => array(
    'Content-Type: text/plain'
  ),
));

$responseReq = curl_exec($curl);

$dataReq = json_decode($responseReq, true);

$access_token = $dataReq['accessToken'];

//////////////////////////////////////////////////////////////////////////////////

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://ssonext-api.kku.ac.th/user.profile',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $access_token,
    ),
));

$response = curl_exec($curl);


// แปลง JSON เป็นอาร์เรย์ใน PHP
$data = json_decode($response, true);

// เก็บค่าในตัวแปร PHP
$user_id = $data['profile']['userId'];
$citizen_id = $data['profile']['citizenId'];
$mail = $data['profile']['mail'];
$account_type = $data['profile']['type'];
$title_th = $data['profile']['title'];
$firstname_th = $data['profile']['firstname'];
$lastname_th = $data['profile']['lastname'];
$title = $data['profile']['titleEng'];
$firstname = $data['profile']['firstnameEng'];
$lastname = $data['profile']['lastnameEng'];
$position = $data['profile']['positionName'];
$workline = $data['profile']['workline'];
$faculty_name = $data['profile']['facultyName'];

// $mail = "adisai@kku.ac.th"; // ตัวอย่างอีเมล
// $citizen_id = "1234567890124";
// $title_th = "นาย";
// $firstname_th = "สมศักดิ์1";
// $lastname_th = "ดีใจ";
// $title = "Mr.";
// $firstname = "Somsak";
// $lastname = "Deejai";
// $position = "อาจารย์";
// $workline = "สายวิชาการ";
// $faculty_name = "คณะพยาบาลศาสตร์";


try {
    // ค้นหาผู้ใช้ในฐานข้อมูล
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $mail, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $role = $user['role'];
        // ตรวจสอบบทบาทและเปลี่ยนเส้นทาง
        if ($role === 'wait') {
            // แสดงหน้ารออนุมัติ
            showWaitingPage($firstname_th, $lastname_th);
        } else {
            if ($role === 'doctor') {
                $stmt = $pdo->prepare("SELECT * FROM doctors_user WHERE email = :username");
                $stmt->bindParam(':username', $mail);
                $stmt->execute();
                $user_doctor = $stmt->fetch();
                if ($user_doctor) {
                    // เริ่ม Session เมื่อ Login สำเร็จ
                    session_regenerate_id(true); // ป้องกัน Session Fixation Attack
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user_doctor['username'];
                    $_SESSION['role'] = $role;
                    $_SESSION['email'] = $mail;
                    $_SESSION['last_activity'] = time(); // เพิ่มบรรทัดนี้
              
                    redirectByRole($role);
                } else {
                    $name = $title_th . $firstname_th . ' ' . $lastname_th;
                    // ไม่มีข้อมูล ให้เพิ่มใหม่
                    $insert_teacher_sql = "INSERT INTO doctors_user (email, username, role, user_id) VALUES (:email, :username, 'doctor', :user_id)";
                    $insert_teacher_stmt = $pdo->prepare($insert_teacher_sql);
                    $insert_teacher_stmt->bindParam(':email', $mail, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindParam(':username', $name, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                    $insert_teacher_stmt->execute();

                    session_regenerate_id(true); // ป้องกัน Session Fixation Attack
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $name;
                    $_SESSION['role'] = $role;
                    $_SESSION['email'] = $mail;
                    $_SESSION['last_activity'] = time(); // เพิ่มบรรทัดนี้

                    redirectByRole($role);
                }
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->bindParam(':username', $mail);
                $stmt->execute();
                $user = $stmt->fetch();
                if ($user) {
                    // เริ่ม Session เมื่อ Login สำเร็จ
                    session_regenerate_id(true); // ป้องกัน Session Fixation Attack
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $role;
                    $_SESSION['email'] = $mail;
                    $_SESSION['last_activity'] = time(); // เพิ่มบรรทัดนี้
                    // ตรวจสอบและเพิ่มข้อมูลในตาราง teachers ถ้าเป็น teacher
                    if ($role === 'teacher') {
                        $teacher_sql = "SELECT * FROM teachers WHERE email = :email";
                        $teacher_stmt = $pdo->prepare($teacher_sql);
                        $teacher_stmt->bindParam(':email', $mail, PDO::PARAM_STR);
                        $teacher_stmt->execute();

                        if ($teacher_stmt->rowCount() === 0) {
                            $name = $title_th . $firstname_th;
                            // ไม่มีข้อมูล ให้เพิ่มใหม่
                            $insert_teacher_sql = "INSERT INTO teachers (email, first_name, last_name, teacher_id) VALUES (:email, :firstname_th, :lastname_th ,:teacher_id)";
                            $insert_teacher_stmt = $pdo->prepare($insert_teacher_sql);
                            $insert_teacher_stmt->bindParam(':email', $mail, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindParam(':firstname_th', $name, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindParam(':lastname_th', $lastname_th, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindParam(':teacher_id', $user['id'], PDO::PARAM_INT);
                            $insert_teacher_stmt->execute();
                        }
                    }
                    redirectByRole($role);
                }
            }
        }
    } else {
        $insert_sql = "INSERT INTO users (username, role, name) 
                       VALUES (:username, 'wait', :name)";

        $stmt = $pdo->prepare($insert_sql);
        $stmt->bindParam(':username', $mail, PDO::PARAM_STR);
        $name = $title_th . $firstname_th . ' ' . $lastname_th;
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // แสดงหน้ารออนุมัติ
            showWaitingPage($firstname_th, $lastname_th);
        } else {
            throw new Exception("ไม่สามารถสร้างบัญชีใหม่ได้");
        }
    }
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

function showWaitingPage($firstname_th = '', $lastname_th = '')
{
    include '../app/views/partials/Header.php';
?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>รออนุมัติ</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                background-color: #f5f5f5;
            }

            .waiting-container {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 40rem;
            }


            .icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
                animation: pulse 2s infinite;
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

            .status-card {
                background: #f8f9fa;
                border: 2px dashed #dee2e6;
                border-radius: 10px;
                padding: 25px;
                margin: 30px 0px;
            }

            .status-text {
                font-size: 16px;
                color: #495057;
                margin-bottom: 15px;
            }

            .loading-spinner {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-right: 10px;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
    </head>

    <body>
        <div class="bg-image"></div>
        <div class="waiting-container">
            <div class="icon">
                ⏳
            </div>

            <h3 class="title">รออนุมัติสิทธิ์การใช้งาน</h3>
            <h5>บัญชีของคุณ <label style="color:#1976d2;font-weight:bold;"><?php echo htmlspecialchars(trim($firstname_th . ' ' . $lastname_th), ENT_QUOTES, 'UTF-8'); ?></label></h5>

            <div class="status-card">
                <div class="status-text">
                    <span class="loading-spinner"></span>
                    สถานะ: รอการอนุมัติ
                </div>
                <p style="font-size: 14px; color: #868e96;">
                    อยู่ระหว่างการรออนุมัติจากผู้ดูแลระบบกรุณารอสักครู่หรือติดต่อผู้ดูแลระบบ
                </p>
            </div>


            <button class="btn btn-primary" onclick="refreshPage()">รีเฟรช</button>
            <a href="../app/views/logout.php" class="btn btn-danger">ออกจากระบบ</a>
        </div>

        <script>
            function refreshPage() {
                window.location.reload();
            }

            // Auto refresh every 30 seconds
            setTimeout(function() {
                refreshPage();
            }, 30000);
        </script>
    </body>

    </html>
<?php
    exit();
}

function redirectByRole($role)
{
    switch ($role) {
        case 'admin':
            header('Location: ../app/views/admin/admin_dashboard.php');
            break;
        case 'teacher':
            header('Location: ../app/views/teacher/teacher_dashboard.php');
            break;
        case 'doctor':
            header('Location: ../app/views/doctor/doctor_dashboard.php');
            break;
    }
    exit();
}
?>