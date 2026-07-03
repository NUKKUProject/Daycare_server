<?php

require_once(__DIR__ . '/../../../config/database.php'); // เชื่อมต่อไฟล์ database.php

// เพิ่ม error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = getDatabaseConnection();


?>