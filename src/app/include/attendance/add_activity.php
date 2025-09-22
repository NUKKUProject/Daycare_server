<?php
session_start();

require_once(__DIR__ . '/../../../config/database.php');// เชื่อมต่อไฟล์ database.php

// ตรวจสอบการเข้าสู่ระบบ และบทบาทของผู้ใช้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// ฟังก์ชันเพิ่มกิจกรรม
function addActivity($activityTime, $activityName, $activityDate) {
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare("INSERT INTO activities (activity_time, activity_name, activity_date) VALUES (:time, :name, :date)");
        $stmt->bindParam(':time', $activityTime);
        $stmt->bindParam(':name', $activityName);
        $stmt->bindParam(':date', $activityDate);
        $stmt->execute();

        return [
            'status' => 'success',
            'message' => 'เพิ่มกิจกรรมสำเร็จ'
        ];
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());

        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มกิจกรรม'
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $dayOfWeek = $_POST['day_of_week'];  // อาร์เรย์ของวันในสัปดาห์
    $activityTime = $_POST['activity_time'];  // อาร์เรย์ของเวลา
    $activityName = $_POST['activity_name'];  // อาร์เรย์ของกิจกรรม

    try {
        $pdo = getDatabaseConnection();

        // เตรียมการบันทึกข้อมูลทีละแถว
        for ($i = 0; $i < count($dayOfWeek); $i++) {
            $stmt = $pdo->prepare("INSERT INTO activities (day_of_week, activity_time, activity_name) VALUES (:day, :time, :name)");
            $stmt->bindParam(':day', $dayOfWeek[$i]);
            $stmt->bindParam(':time', $activityTime[$i]);
            $stmt->bindParam(':name', $activityName[$i]);
            $stmt->execute();
        }

        // ส่งข้อมูลสถานะกลับไปยังหน้าเดิม
        header("Location: ../views/activity_child.php?status=success&message=" . urlencode("เพิ่มกิจกรรมสำเร็จ"));
        exit();
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
        header("Location: ../views/activity_child.php?status=error&message=" . urlencode("เกิดข้อผิดพลาดในการเพิ่มกิจกรรม"));
        exit();
    }
}

?>
