<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../../config/database.php'); // เชื่อมต่อไฟล์ database.php
require_once(__DIR__ . '/auth.php'); // เชื่อมต่อไฟล์ auth.php
// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
// รับข้อมูลจากคำขอ POST
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$name = $input['name'] ?? null;
$role = $input['role'] ?? null;

// ตรวจสอบข้อมูลที่รับมา
if (!$id || !$name || !$role) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}
// ตรวจสอบว่าผู้ใช้ที่ล็อกอินตรงกับ ID ที่ส่งมาหรือไม่
if ($_SESSION['user_id'] != $id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
// อัปเดตข้อมูลในฐานข้อมูล
try {
    $pdo = getDatabaseConnection();
    if ($role === 'doctor') {
        $update_stmt = $pdo->prepare("UPDATE doctors_user SET username = ? WHERE user_id = ?");
        $update_stmt->execute([$name, $id]);
        //ดึงข้อมูล user_id จาก doctors_user
        $stmt = $pdo->prepare("SELECT user_id FROM doctors_user WHERE user_id = ?");
        $stmt->execute([$id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($doctor) {
            $user_id = $doctor['user_id'];
            //อัปเดตชื่อในตาราง users
            $update_user_stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $update_user_stmt->execute([$name, $user_id]);
        }
    } else if ($role === 'teacher') {
        //แยกชื่อกับนามสกุล
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';
        $update_stmt = $pdo->prepare("UPDATE teachers SET first_name = ? , last_name = ? WHERE teacher_id = ?");
        $update_stmt->execute([$first_name, $last_name, $id]);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $role, $id]);
    }
    // อัปเดตข้อมูลใน session
    $_SESSION['username'] = $name;
    // ส่งผลลัพธ์กลับไปยังไคลเอนต์

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
