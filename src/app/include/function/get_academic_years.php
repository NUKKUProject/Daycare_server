<?php
// ปิดการแสดงข้อผิดพลาด PHP
error_reporting(0);
ini_set('display_errors', 0);

// ตั้งค่า header สำหรับ JSON
header('Content-Type: application/json; charset=utf-8');

// ฟังก์ชันสำหรับส่ง JSON response
function sendJsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ฟังก์ชันสำหรับสร้างปีการศึกษาเริ่มต้น
function generateDefaultAcademicYears() {
    $currentYear = date('Y') + 543; // แปลงเป็น พ.ศ.
    return [
        ($currentYear - 1) . '/' . $currentYear,
        $currentYear . '/' . ($currentYear + 1),
        ($currentYear + 1) . '/' . ($currentYear + 2)
    ];
}

try {
    // ตรวจสอบว่ามีการเรียกไฟล์โดยตรงหรือไม่
    if (basename($_SERVER['PHP_SELF']) != basename(__FILE__)) {
        return;
    }

    // ตรวจสอบว่ามีไฟล์ database.php
    $databaseFile = __DIR__ . '/../../../config/database.php';
    if (!file_exists($databaseFile)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'ไม่พบไฟล์การตั้งค่าฐานข้อมูล: ' . $databaseFile
        ]);
    }

    require_once $databaseFile;

    // ตรวจสอบว่ามีฟังก์ชัน getDatabaseConnection
    if (!function_exists('getDatabaseConnection')) {
        sendJsonResponse([
            'success' => false,
            'message' => 'ไม่พบฟังก์ชันเชื่อมต่อฐานข้อมูล'
        ]);
    }

    try {
        $conn = getDatabaseConnection();
        
        // ตรวจสอบการเชื่อมต่อฐานข้อมูล
        if (!$conn) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้'
            ]);
        }

        // ดึงข้อมูลปีการศึกษาจากตาราง academic_years
        $sql = "SELECT name 
                FROM academic_years 
                WHERE is_active = true 
                ORDER BY name DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ไม่สามารถเตรียมคำสั่ง SQL ได้'
            ]);
        }

        $stmt->execute();
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // ถ้าไม่มีข้อมูลในฐานข้อมูล ให้สร้างปีการศึกษาปัจจุบันและอนาคต
        if (empty($years)) {
            $years = generateDefaultAcademicYears();
        }
        
        sendJsonResponse([
            'success' => true,
            'years' => $years
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in getAcademicYears: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลปีการศึกษา'
        ]);
    }

} catch (Exception $e) {
    error_log("Critical error in get_academic_years.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อระบบ'
    ]);
}
?> 