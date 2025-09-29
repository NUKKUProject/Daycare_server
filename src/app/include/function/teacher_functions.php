<?php
require_once(__DIR__ . '/../../../config/database.php');

function getAllTeachers()
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                t.teacher_id,
                t.first_name as teacher_firstname,
                t.last_name as teacher_lastname,
                t.email,
                t.phone_number,
                t.group_ids as teacher_group,
                t.classroom_ids as teacher_classroom,
                t.profile_image as teacher_image,
                COALESCE(u.role, 'teacher') as role,
                u.username
            FROM teachers t
            LEFT JOIN users u ON u.username = t.email
            ORDER BY first_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching teachers: " . $e->getMessage());
        return [];
    }
}

function getTeacherById($teacherId)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                t.teacher_id,
                t.first_name as teacher_firstname,
                t.last_name as teacher_lastname,
                t.email,
                t.phone_number,
                t.group_ids as teacher_group,
                t.classroom_ids as teacher_classroom,
                t.profile_image as teacher_image,
                u.role,
                u.username
            FROM teachers t
            LEFT JOIN users u ON u.username = t.email
            WHERE t.teacher_id = :teacherId
        ");
        $stmt->execute(['teacherId' => $teacherId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching teacher: " . $e->getMessage());
        return null;
    }
}

function updateTeacherProfile($teacherId, $data)
{
    try {
        $pdo = getDatabaseConnection();

        // ตรวจสอบสิทธิ์ผู้ใช้
        session_start();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            throw new Exception("คุณไม่มีสิทธิ์ในการแก้ไขข้อมูล");
        }

        // เริ่มต้น transaction
        $pdo->beginTransaction();

        // อัพเดทข้อมูลในตาราง teachers
        $sql = "
            UPDATE teachers 
            SET 
                first_name = :firstname,
                last_name = :lastname,
                email = :email,
                phone_number = :phone,
                group_ids = :teacher_group,
                classroom_ids = :teacher_classroom
        ";

        // จัดการรูปภาพ...
        if (!empty($_FILES['teacher_image']['name'])) {
            $uploadDir = '../../../public/assets/images/teachers/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // ตรวจสอบและ sanitize ชื่อไฟล์
            $originalFileName = $_FILES['teacher_image']['name'];
            $safeFileName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $originalFileName); // ลบอักขระที่ไม่ปลอดภัย
            $fileName = time() . '_' . $safeFileName;
            $uploadFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['teacher_image']['tmp_name'], $uploadFile)) {
                $sql .= ", profile_image = :profile_image";
            }
        }

        $sql .= " WHERE teacher_id = :teacherId";

        $stmt = $pdo->prepare($sql);

        // กำหนดค่าพารามิเตอร์สำหรับ teachers
        $params = [
            'teacherId' => $teacherId,
            'firstname' => $data['teacher_firstname'],
            'lastname' => $data['teacher_lastname'],
            'email' => $data['email'],
            'phone' => $data['phone_number'],
            'teacher_group' => $data['teacher_group'],
            'teacher_classroom' => $data['teacher_classroom']
        ];

        // เพิ่มพารามิเตอร์รูปภาพถ้ามีการอัพโหลด
        if (!empty($_FILES['teacher_image']['name'])) {
            $params['profile_image'] = $fileName;
        }

        // อัพเดทข้อมูลในตาราง teachers
        if (!$stmt->execute($params)) {
            throw new Exception("Failed to update teacher information");
        }

        // อัพเดท role ในตาราง users
        $updateUserSql = "UPDATE users SET role = :role WHERE username = :email";
        $userStmt = $pdo->prepare($updateUserSql);
        if (!$userStmt->execute([
            'role' => $data['role'],
            'email' => $data['email']
        ])) {
            throw new Exception("Failed to update user role");
        }

        // จัดการรหัสผ่าน (ถ้ามีการเปลี่ยน)
        if (!empty($data['new_password'])) {
            $updatePasswordSql = "UPDATE users 
                                SET password = :password 
                                WHERE username = :email 
                                AND role = 'teacher'";
            $pwdStmt = $pdo->prepare($updatePasswordSql);
            if (!$pwdStmt->execute([
                'password' => password_hash($data['new_password'], PASSWORD_DEFAULT),
                'email' => $data['email']
            ])) {
                throw new Exception("Failed to update password");
            }
        }

        $pdo->commit();
        return [
            'success' => true,
            'message' => 'อัพเดทข้อมูลสำเร็จ'
        ];
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error updating teacher profile: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()
        ];
    }
}

function addNewTeacher($data)
{
    try {
        $pdo = getDatabaseConnection();

        // เริ่ม transaction
        $pdo->beginTransaction();

        // ตรวจสอบว่าอีเมลซ้ำหรือไม่ในทั้งสองตาราง
        $checkEmail = $pdo->prepare("
            SELECT COUNT(*) FROM (
                SELECT email FROM teachers WHERE email = :email
                UNION
                SELECT username FROM users WHERE username = :email
            ) AS combined_check
        ");
        $checkEmail->execute(['email' => $data['email']]);
        if ($checkEmail->fetchColumn() > 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'อีเมลนี้ถูกใช้งานแล้ว'
            ];
        }

        // เข้ารหัสรหัสผ่าน
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // เพิ่มข้อมูลในตาราง users
        $userSql = "
            INSERT INTO users (username, password, role) 
            VALUES (:username, :password, 'teacher')
        ";
        $userStmt = $pdo->prepare($userSql);
        $userStmt->execute([
            'username' => $data['email'],
            'password' => $hashedPassword
        ]);

        // เพิ่มข้อมูลในตาราง teachers
        $teacherSql = "
            INSERT INTO teachers (
                teacher_id,
                first_name,
                last_name,
                email,
                phone_number,
                group_ids,
                classroom_ids,
                profile_image,
                created_at
            ) VALUES (
                (SELECT COALESCE(MAX(teacher_id), 0) + 1 FROM teachers),
                :first_name,
                :last_name,
                :email,
                :phone_number,
                :group_ids,
                :classroom_ids,
                :profile_image,
                CURRENT_TIMESTAMP
            )
        ";

        $teacherStmt = $pdo->prepare($teacherSql);

        // กำหนดค่าพารามิเตอร์สำหรับ teachers
        $params = [
            'first_name' => $data['teacher_firstname'],
            'last_name' => $data['teacher_lastname'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'group_ids' => $data['teacher_group'],
            'classroom_ids' => $data['teacher_classroom'],
            'profile_image' => $data['teacher_image'] ?? null
        ];

        if ($teacherStmt->execute($params)) {
            $pdo->commit();
            return [
                'success' => true,
                'message' => 'เพิ่มคุณครูใหม่สำเร็จ',
                'teacher_id' => $pdo->lastInsertId()
            ];
        } else {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล'
            ];
        }
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error adding new teacher: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()
        ];
    }
}

function getAllGroups()
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT DISTINCT child_group 
            FROM children 
            WHERE child_group IS NOT NULL 
            ORDER BY child_group
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching groups: " . $e->getMessage());
        return [];
    }
}

function getAllClassrooms()
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT DISTINCT classroom 
            FROM children 
            WHERE classroom IS NOT NULL 
            ORDER BY classroom
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching classrooms: " . $e->getMessage());
        return [];
    }
}

function checkTeacherAccess($teacherId, $userId)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT role 
            FROM teachers 
            WHERE teacher_id = :userId
        ");
        $stmt->execute(['userId' => $userId]);
        $userRole = $stmt->fetchColumn();

        // ถ้าเป็น admin สามารถเข้าถึงได้ทุกโปรไฟล์
        if ($userRole === 'admin') {
            return true;
        }

        // ถ้าไม่ใช่ admin ต้องเป็นเจ้าของโปรไฟล์เท่านั้น
        return $teacherId == $userId;
    } catch (PDOException $e) {
        error_log("Error checking teacher access: " . $e->getMessage());
        return false;
    }
}

// เพิ่ม handler สำหรับ AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get_teacher' && isset($_GET['teacher_id'])) {
        $teacherData = getTeacherById($_GET['teacher_id']);
        header('Content-Type: application/json');
        echo json_encode($teacherData);
        exit;
    }
}

// Handler สำหรับ form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action']) {
        case 'update_teacher':
            if (isset($_POST['teacher_id'])) {
                $response = updateTeacherProfile($_POST['teacher_id'], $_POST);
            }
            break;

        case 'add_teacher':
            $response = addNewTeacher($_POST);
            break;
    }

    // Redirect กลับไปที่หน้าจัดการข้อมูลพร้อม status
    $redirectUrl = '../../views/admin/profile_management.php';
    $redirectUrl .= '?status=' . ($response['success'] ? 'success' : 'error');
    $redirectUrl .= '&message=' . urlencode($response['message']);

    header('Location: ' . $redirectUrl);
    exit;
}
