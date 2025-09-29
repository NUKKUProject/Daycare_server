<?php
// กำหนด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// กำหนด error log path แบบ absolute
$logFile = dirname(dirname(dirname(dirname(__DIR__)))) . '/logs/php-error.log';
ini_set('log_errors', 1);
ini_set('error_log', $logFile);

// ทดสอบการเขียน log
error_log("Starting application... Log file: " . $logFile);

// เริ่ม output buffering
ob_start();
session_start();
require_once(__DIR__ . '/../../../config/database.php');

try {
    // ตรวจสอบว่าสามารถเขียน log ได้
    if (!is_writable(dirname($logFile))) {
        throw new Exception("Cannot write to log directory: " . dirname($logFile));
    }
    if (file_exists($logFile) && !is_writable($logFile)) {
        throw new Exception("Cannot write to log file: " . $logFile);
    }

    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Files: " . print_r($_FILES, true));

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploadedFile'])) {
        $file = $_FILES['uploadedFile'];
        $user_id = $_SESSION['user_id']; // ดึง user_id จาก session

        // ตรวจสอบข้อผิดพลาดในการอัพโหลด
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
        }

        // ตรวจสอบประเภทไฟล์
        $type = $file['type'];
        if (!in_array($type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'])) {
            throw new Exception('อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF เท่านั้น');
        }

        // ตรวจสอบขนาดไฟล์ (5MB)
        if ($file['size'] > 5000000) {
            throw new Exception('ขนาดไฟล์ต้องไม่เกิน 5MB');
        }

        // สร้างชื่อไฟล์ใหม่
        $originalFileName = basename($file['name']); // ลบพาธ traversal ออก
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

        // ทำความสะอาดนามสกุลไฟล์
        $fileExtension = preg_replace('/[^a-zA-Z0-9]/', '', $fileExtension);

        // จำกัดนามสกุลไฟล์ที่อนุญาต
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            throw new Exception('นามสกุลไฟล์ไม่ถูกต้อง');
        }

        // สร้างชื่อไฟล์ใหม่อย่างปลอดภัย
        $new_name = date("d_m_Y_H_i_s") . '-' . uniqid() . '.' . $fileExtension;

        // กำหนด path
        $upload_dir = __DIR__ . '/../../../../public/uploads/profile_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $path = $upload_dir . $new_name;

        // ตรวจสอบพาธจริงเพื่อป้องกัน Path Traversal
        $realUploadDir = realpath($upload_dir);
        $realUploadFile = realpath(dirname($path)) . '/' . basename($path);

        // ตรวจสอบว่าพาธจริงอยู่ในไดเรกทอรีที่อนุญาต
        if (strpos($realUploadFile, $realUploadDir) !== 0) {
            throw new Exception('Security violation: Invalid file path.');
        }

        // ตรวจสอบว่ามีไฟล์อยู่แล้วหรือไม่
        if (file_exists($path)) {
            throw new Exception('มีไฟล์นี้อยู่ในระบบแล้ว');
        }

        // ย้ายไฟล์
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new Exception('ไม่สามารถอัพโหลดไฟล์ได้');
        }

        // ตั้งค่าสิทธิ์ไฟล์
        chmod($path, 0644);

        // อัพเดทฐานข้อมูล
        $pdo = getDatabaseConnection();

        // ลบรูปเก่า (ถ้ามี)
        $stmt = $pdo->prepare("SELECT profile_img FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old_image = $stmt->fetchColumn();

        if ($old_image) {
            $old_path = $upload_dir . $old_image;
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }

        // อัพเดทชื่อไฟล์ใหม่
        $stmt = $pdo->prepare("UPDATE users SET profile_img = ? WHERE id = ?");
        if (!$stmt->execute([$new_name, $user_id])) {
            throw new Exception('ไม่สามารถอัพเดทข้อมูลในฐานข้อมูลได้');
        }

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'อัพโหลดรูปภาพสำเร็จ';

    } else {
        throw new Exception('ไม่พบไฟล์ที่อัพโหลด');
    }
} catch (Exception $e) {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = $e->getMessage();
}

// Redirect กลับไปหน้าโปรไฟล์
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();

// ล้าง output buffer
ob_end_clean(); 