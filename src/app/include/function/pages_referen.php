<?php
// ตรวจสอบ Referer
$previous_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

// ตัวอย่างการตั้งชื่อพาธที่เหมาะสม
function getPageNameFromURL($url) {
    if (!$url) return "หน้าหลัก"; // หากไม่มี URL ให้แสดงค่าเริ่มต้น

    // แยกชื่อไฟล์ PHP ออกจาก URL
    $parts = parse_url($url);
    $path = isset($parts['path']) ? basename($parts['path']) : '';

    // จับคู่ชื่อไฟล์กับชื่อที่แสดง
    switch ($path) {
        case 'admin_dashboard.php':
            return 'Dashboard';
        case 'children_history.php':
            return 'ข้อมูลของเด็ก';
        case 'view_child.php':
            return 'ประวัติประจำตัวของเด็ก';
        case 'attendance.php':
            return 'แสกนเช็คชื่อ';
        case 'attendance_history.php':
            return 'บันทึกประวัติการเช็คชื่อมาเรียน';
        default:
            return 'หน้าหลัก';
    }
}

// กำหนดชื่อหน้า
$previous_page_name = getPageNameFromURL($previous_page);
?>
