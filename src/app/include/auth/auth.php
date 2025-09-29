<?php
ob_start();
session_start(); // ต้องเรียกใช้งานก่อนทุกสิ่งทุกอย่าง

require_once(__DIR__ . '/../../../config/database.php'); // เชื่อมต่อไฟล์ database.php

// เพิ่มการกำหนดเวลาหมดอายุของ session
$session_timeout = 30 * 60; // 30 นาที (เป็นวินาที)



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = htmlspecialchars(trim($_POST['username'])); // ป้องกัน XSS
    $password = trim($_POST['password']);

    try {
        $pdo = getDatabaseConnection();  // เชื่อมต่อฐานข้อมูล

        if ($username === 'admin') {
            // กรณี admin
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);

            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user) {
                header("Location: /app/views/login.php?error=" . urlencode("User not found"));
                exit();
            }

            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                header("Location: /app/views/admin/admin_dashboard.php");
            } else {
                header("Location: /app/views/login.php?error=" . urlencode("Invalid password"));
            }
            exit();
        } else {
            // กรณีอื่นๆ เข้า children
            $stmt = $pdo->prepare("SELECT * FROM children WHERE studentid = :studentid");
            $stmt->bindParam(':studentid', $username);
            $stmt->execute();
            $child = $stmt->fetch();

            if (!$child) {
                header("Location: /app/views/login.php?error=" . urlencode("StudentID not found"));
                exit();
            }

            // ใช้ id_card เป็น password ถ้าไม่มีให้เทียบกับ '0000000000000'
            $id_card = isset($child['id_card']) && strlen(trim($child['id_card'])) === 13 ? trim($child['id_card']) : '0000000000000';

            if ($password === $id_card) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $child['id'];
                $_SESSION['role'] = "student";
                $_SESSION['username'] = $child['studentid'];
                $_SESSION['last_activity'] = time();
                header("Location: /app/views/student/student_dashboard.php");
            } else {
                header("Location: /app/views/login.php?error=" . urlencode("Invalid studentid or id_card"));
            }
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        $error_message = urlencode("Database connection failed: " . str_replace(["\n", "\r"], ' ', $e->getMessage()));
        header("Location: /app/views/login.php?error=" . $error_message);
        exit();
    }
}

// เพิ่มฟังก์ชันตรวจสอบ session timeout
function checkSessionTimeout()
{
    global $session_timeout;

    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];

        if ($inactive_time >= $session_timeout) {
            // Session หมดอายุ ทำการ logout
            session_unset();
            session_destroy();

            // ส่ง response เป็น JSON ถ้าเป็น AJAX request
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
            ) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'session_timeout',
                    'message' => 'Your session has expired. Please login again.'
                ]);
                exit;
            }

            // ถ้าไม่ใช่ AJAX redirect ไปหน้า login
            header("Location: /app/views/login.php?error=" . urlencode("Your session has expired. Please login again."));
            exit;
        }
    }

    // อัพเดทเวลาล่าสุดที่มีการใช้งาน
    $_SESSION['last_activity'] = time();
}

// แก้ไขฟังก์ชัน isLoggedIn
function isLoggedIn()
{
    checkSessionTimeout(); // เพิ่มการเช็ค timeout ทุกครั้งที่เช็คการล็อกอิน
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// ฟังก์ชันดึงบทบาทของผู้ใช้
function getUserRole()
{
    return $_SESSION['role'] ?? null;
}

// แก้ไขฟังก์ชัน checkUserRole
function checkUserRole($requiredRoles)
{
    checkSessionTimeout(); // เพิ่มการเช็ค timeout

    if (!isset($_SESSION['role'])) {
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'session_timeout',
                'message' => 'Please login to continue.'
            ]);
            exit;
        }

        header('Location: /app/views/login.php');
        exit();
    }

    if (!in_array($_SESSION['role'], $requiredRoles)) {
        header('Location: /app/views/unauthorized.php');
        exit();
    }
}

function getFullName()
{
    $role = getUserRole(); // ฟังก์ชันดึง role ของผู้ใช้
    $pdo = getDatabaseConnection(); // ฟังก์ชันเชื่อมต่อฐานข้อมูล
    $fullName = '';

    try {
        if ($role === 'teacher') {
            // ดึงชื่อครูจาก teacher_id
            $teacher_id = $_SESSION['user_id'] ?? null; // ดึง teacher_id จาก session
            if ($teacher_id) {
                $stmt = $pdo->prepare("SELECT first_name, last_name FROM teachers WHERE teacher_id = :teacher_id");
                $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
                $stmt->execute();
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($teacher) {
                    $fullName = $teacher['first_name'] . ' ' . $teacher['last_name'];
                }
            }
        } elseif ($role === 'student') {
            // ดึงชื่อเด็กจาก studentid
            $studentid = $_SESSION['username'] ?? null; // ดึง studentid จาก session
            if ($studentid) {
                $stmt = $pdo->prepare("
                    SELECT c.firstname_th, c.lastname_th 
                    FROM children c
                    WHERE c.studentid = :studentid
                ");
                $stmt->bindParam(':studentid', $studentid, PDO::PARAM_STR);
                $stmt->execute();
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($student) {
                    $fullName = $student['firstname_th'] . ' ' . $student['lastname_th'];
                }
            }
        } elseif ($role === 'admin') {
            // แสดงชื่อผู้ใช้เป็น username สำหรับ admin
            $username = $_SESSION['username'] ?? null; // ดึง username จาก session
            if ($username) {
                $fullName = $username; // ใช้ username เป็นชื่อ
            }
        }
        elseif ($role === 'doctor') {
            // แสดงชื่อผู้ใช้เป็น username สำหรับ admin
            $username = $_SESSION['username'] ?? null; // ดึง username จาก session
            if ($username) {
                $fullName = $username; // ใช้ username เป็นชื่อ
            }
        }
    } catch (Exception $e) {
        // จัดการข้อผิดพลาด (optional)
        error_log($e->getMessage());
    }

    return $fullName ?: 'Guest'; // คืนค่า 'Guest' หากไม่พบข้อมูล
}

// ฟังก์ชันสำหรับการเข้าสู่ระบบด้วย SSO KKU
function handleSSOLogin()
{
    $code = isset($_GET['code']) ? $_GET['code'] : null;
    if (!$code) {
        return false;
    }

    // กำหนดค่าที่จำเป็นสำหรับการขอ access token
    $redirectUrl = 'https://pmnu.kku.ac.th/auth/saml';
    $clientId = '01917deb-1950-72d3-8b83-bf359696c8cb';
    $clientSecret = '9dJhucvev8QWtr0rLBFfI9aVb82yXcqNmXAgQzvtw9GxUfZCSiZJbtYYgtPbIftt';

    $data = [
        "code" => $code,
        "redirectUrl" => $redirectUrl,
        "clientId" => $clientId,
        "clientSecret" => $clientSecret
    ];

    // ขอ access token
    $ch = curl_init('https://ssonext-api.kku.ac.th/auth.token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('SSO Error: ' . curl_error($ch));
        return false;
    }

    $result = json_decode($response, true);
    if (!$result['ok']) {
        return false;
    }

    $access_token = $result['accessToken'];

    // ขอข้อมูลโปรไฟล์
    $ch = curl_init('https://ssonext-api.kku.ac.th/user.profile');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Profile Error: ' . curl_error($ch));
        return false;
    }

    $result = json_decode($response, true);
    if (!$result['ok']) {
        return false;
    }

    $profile = $result['profile'];

    // ตรวจสอบว่าอยู่ในคณะพยาบาลศาสตร์หรือไม่
    if ($profile['facultyName'] !== 'คณะพยาบาลศาสตร์') {
        return false;
    }

    // ตรวจสอบและอัพเดทข้อมูลในฐานข้อมูล
    $pdo = getDatabaseConnection();

    // ตรวจสอบว่ามีข้อมูลในตาราง hremployee หรือไม่
    $stmt = $pdo->prepare("SELECT * FROM hremployee WHERE identification = ?");
    $stmt->execute([$profile['citizenId']]);
    if ($stmt->rowCount() == 0) {
        // เพิ่มข้อมูลใหม่
        $stmt = $pdo->prepare("INSERT INTO hremployee (id, identification, prename, firstname, lastname, title_eng, firstname_eng, lastname_eng) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $profile['userId'],
            $profile['citizenId'],
            $profile['title'],
            $profile['firstname'],
            $profile['lastname'],
            $profile['titleEng'],
            $profile['firstnameEng'],
            $profile['lastnameEng']
        ]);
    }

    // ตรวจสอบสิทธิ์จากตาราง sso_roles
    $stmt = $pdo->prepare("SELECT role FROM sso_roles WHERE citizen_id = ? AND status = 'active'");
    $stmt->execute([$profile['citizenId']]);
    $roleData = $stmt->fetch();

    // กำหนดบทบาทตามข้อมูลจากตาราง sso_roles
    $role = $roleData ? $roleData['role'] : 'teacher';

    // ตรวจสอบและอัพเดทข้อมูลในตาราง users
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$profile['citizenId']]);
    $user = $stmt->fetch();

    if (!$user) {
        // เพิ่มผู้ใช้ใหม่
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $profile['citizenId'],
            password_hash($profile['citizenId'], PASSWORD_DEFAULT), // ใช้ citizenId เป็นรหัสผ่านเริ่มต้น
            $role,
            $profile['mail']
        ]);
    } else {
        // อัพเดทข้อมูลผู้ใช้
        $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE username = ?");
        $stmt->execute([$profile['mail'], $role, $profile['citizenId']]);
    }

    // ตั้งค่า session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $profile['userId'];
    $_SESSION['role'] = $role;
    $_SESSION['username'] = $profile['citizenId'];
    $_SESSION['last_activity'] = time();

    return true;
}