<?php
session_start();
require_once(__DIR__ . '/../../../config/database.php');

// สร้างการเชื่อมต่อฐานข้อมูล
$pdo = getDatabaseConnection();

// ตรวจสอบว่าผู้ใช้เป็น admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /app/views/login.php?error=' . urlencode('กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ'));
    exit();
}

// ตรวจสอบว่ามีบัญชีผู้ใช้อยู่แล้วหรือไม่
function checkUserExists($studentId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$studentId]);
    return $stmt->rowCount() > 0;
}

// สร้างบัญชีผู้ใช้สำหรับนักเรียน
function createStudentAccount($studentId) {
    $pdo = getDatabaseConnection();
    
    // ตรวจสอบว่ามีบัญชีอยู่แล้วหรือไม่
    if (checkUserExists($studentId)) {
        return [
            'status' => 'error',
            'message' => "บัญชีผู้ใช้สำหรับรหัสนักเรียน $studentId มีอยู่แล้ว"
        ];
    }

    // ตรวจสอบรูปแบบของ studentId
    if (empty($studentId)) {
        return [
            'status' => 'error',
            'message' => "รหัสนักเรียนไม่ถูกต้อง"
        ];
    }

    // สร้างรหัสผ่าน (studentid + nu**)
    $password = $studentId . "nu**";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // เพิ่มบัญชีผู้ใช้ใหม่
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, studentid) VALUES (?, ?, 'student', ?)");
    
    if ($stmt->execute([$studentId, $hashedPassword, $studentId])) {
        return [
            'status' => 'success',
            'message' => "สร้างบัญชีผู้ใช้สำหรับรหัสนักเรียน $studentId สำเร็จ"
        ];
    } else {
        return [
            'status' => 'error',
            'message' => "เกิดข้อผิดพลาดในการสร้างบัญชีผู้ใช้"
        ];
    }
}

// ลบบัญชีผู้ใช้นักเรียน
function deleteStudentAccount($studentId) {
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ? AND role = 'student'");
    
    if ($stmt->execute([$studentId])) {
        return [
            'status' => 'success',
            'message' => "ลบบัญชีผู้ใช้สำหรับรหัสนักเรียน $studentId สำเร็จ"
        ];
    } else {
        return [
            'status' => 'error',
            'message' => "เกิดข้อผิดพลาดในการลบบัญชีผู้ใช้"
        ];
    }
}

// เพิ่มฟังก์ชันแก้ไขบัญชีนักเรียน
function editStudentAccount($studentId, $newPassword = null) {
    $pdo = getDatabaseConnection();
    
    // ตรวจสอบว่ามีบัญชีอยู่หรือไม่
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'student'");
    $stmt->execute([$studentId]);
    if ($stmt->rowCount() === 0) {
        return [
            'status' => 'error',
            'message' => "ไม่พบบัญชีผู้ใช้สำหรับรหัสนักเรียน $studentId"
        ];
    }

    // ถ้ามีการส่งรหัสผ่านใหม่มา
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, studentid = ? WHERE username = ? AND role = 'student'");
        
        if ($stmt->execute([$hashedPassword, $studentId, $studentId])) {
            return [
                'status' => 'success',
                'message' => "อัพเดทรหัสผ่านสำหรับรหัสนักเรียน $studentId สำเร็จ"
            ];
        } else {
            return [
                'status' => 'error',
                'message' => "เกิดข้อผิดพลาดในการอัพเดทรหัสผ่าน"
            ];
        }
    }

    return [
        'status' => 'error',
        'message' => "ไม่มีข้อมูลที่ต้องการแก้ไข"
    ];
}

// ตรวจสอบการเรียกใช้งาน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectUrl = "/app/views/admin/profile_management.php";

    // เพิ่ม error logging
    error_log("Received POST request with action: " . $action);
    error_log("StudentId: " . ($_POST['studentId'] ?? 'not set'));

    switch ($action) {
        case 'create_single':
            if (isset($_POST['studentId'])) {
                $result = createStudentAccount($_POST['studentId']);
                error_log("Create result: " . print_r($result, true));
                header("Location: $redirectUrl?status={$result['status']}&message=" . urlencode($result['message']));
            } else {
                error_log("StudentId not set in POST data");
                header("Location: $redirectUrl?status=error&message=" . urlencode("ไม่พบข้อมูลรหัสนักเรียน"));
            }
            break;

        case 'create_all':
            // ดึงข้อมูลนักเรียนทั้งหมดที่ยังไม่มีบัญชี
            $query = "SELECT studentid FROM children WHERE studentid NOT IN (SELECT username FROM users WHERE role = 'student')";
            $result = $pdo->query($query);
            
            $successCount = 0;
            $errorCount = 0;
            
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $createResult = createStudentAccount($row['studentid']);
                if ($createResult['status'] === 'success') {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
            
            $message = "สร้างบัญชีผู้ใช้สำเร็จ $successCount บัญชี";
            if ($errorCount > 0) {
                $message .= ", ไม่สำเร็จ $errorCount บัญชี";
            }
            
            header("Location: $redirectUrl?status=success&message=" . urlencode($message));
            break;

        case 'create_by_group':
            if (isset($_POST['group'])) {
                $group = $_POST['group'];
                // ดึงข้อมูลนักเรียนในกลุ่มที่ยังไม่มีบัญชี
                $query = "SELECT studentid FROM children WHERE child_group = ? AND studentid NOT IN (SELECT username FROM users WHERE role = 'student')";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$group]);
                
                $successCount = 0;
                $errorCount = 0;
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $createResult = createStudentAccount($row['studentid']);
                    if ($createResult['status'] === 'success') {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                
                $message = "สร้างบัญชีผู้ใช้ในกลุ่ม $group สำเร็จ $successCount บัญชี";
                if ($errorCount > 0) {
                    $message .= ", ไม่สำเร็จ $errorCount บัญชี";
                }
                
                header("Location: $redirectUrl?status=success&message=" . urlencode($message));
            } else {
                header("Location: $redirectUrl?status=error&message=" . urlencode("ไม่พบข้อมูลกลุ่มเรียน"));
            }
            break;

        case 'create_by_classroom':
            if (isset($_POST['classroom'])) {
                $classroom = $_POST['classroom'];
                // ดึงข้อมูลนักเรียนในห้องที่ยังไม่มีบัญชี
                $query = "SELECT studentid FROM children WHERE classroom = ? AND studentid NOT IN (SELECT username FROM users WHERE role = 'student')";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$classroom]);
                
                $successCount = 0;
                $errorCount = 0;
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $createResult = createStudentAccount($row['studentid']);
                    if ($createResult['status'] === 'success') {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                
                $message = "สร้างบัญชีผู้ใช้ในห้อง $classroom สำเร็จ $successCount บัญชี";
                if ($errorCount > 0) {
                    $message .= ", ไม่สำเร็จ $errorCount บัญชี";
                }
                
                header("Location: $redirectUrl?status=success&message=" . urlencode($message));
            } else {
                header("Location: $redirectUrl?status=error&message=" . urlencode("ไม่พบข้อมูลห้องเรียน"));
            }
            break;

        case 'delete':
            if (isset($_POST['studentId'])) {
                $result = deleteStudentAccount($_POST['studentId']);
                header("Location: $redirectUrl?status={$result['status']}&message=" . urlencode($result['message']));
            }
            break;

        case 'edit':
            if (isset($_POST['studentId'])) {
                $newPassword = $_POST['new_password'] ?? null;
                $result = editStudentAccount($_POST['studentId'], $newPassword);
                header("Location: $redirectUrl?status={$result['status']}&message=" . urlencode($result['message']));
            } else {
                header("Location: $redirectUrl?status=error&message=" . urlencode("ไม่พบข้อมูลรหัสนักเรียน"));
            }
            break;
    }
    exit();
}
?>