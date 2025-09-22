<?php
session_start();
header('Content-Type: application/json');

// ตรวจสอบว่าเป็นการส่งแบบ POST และมี action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'register_doctor') {
    
    // รับค่าจาก Form
    $email = trim($_POST['usernameEmail'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password_register'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // ตรวจสอบความถูกต้องเบื้องต้น
    if (empty($email) || empty($fullName) || empty($password) || empty($confirmPassword)) {
        echo json_encode([
            'success' => false,
            'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
        ]);
        exit;
    }

    if ($password !== $confirmPassword) {
        echo json_encode([
            'success' => false,
            'message' => 'รหัสผ่านไม่ตรงกัน'
        ]);
        exit;
    }

require_once(__DIR__ . '/../../../config/database.php');

    $conn = getDatabaseConnection();

    try {
        // ตรวจสอบว่ามี email นี้อยู่แล้วหรือไม่
        $stmt = $conn->prepare("SELECT id FROM doctors_user WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'มีอีเมลนี้ในระบบแล้ว'
            ]);
            exit;
        }

        // เข้ารหัสรหัสผ่าน
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // บันทึกลงฐานข้อมูล
        $insert = $conn->prepare("INSERT INTO doctors_user (email, username, password , role) VALUES (:email, :username, :password , :role)");
        $insert->execute([
            'email' => $email,
            'username' => $fullName,
            'password' => $hashedPassword,
            'role' => 'doctor'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'สมัครบัญชีเรียบร้อยแล้ว'
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()
        ]);
        exit;
    }

} else {
    echo json_encode([
        'success' => false,
        'message' => 'การร้องขอไม่ถูกต้อง'
    ]);
    exit;
}
