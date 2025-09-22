<?php
require_once 'child_functions.php';

// รับค่าจาก GET
$child_group = isset($_GET['child_group']) ? $_GET['child_group'] : '';

$pdo = getDatabaseConnection(); // เชื่อมต่อฐานข้อมูล

// เรียกใช้ฟังก์ชัน getClassrooms
$classrooms = getClassrooms($child_group);

// ส่งผลลัพธ์กลับในรูปแบบ JSON
echo json_encode($classrooms);
?>
