<?php
$returnUrl = '0198bb3e-bead-7eb3-97a7-70e4e9e4aebe';
session_start();
session_unset(); // ลบข้อมูลทั้งหมดใน Session
session_destroy(); // ทำลาย Session
header("Location: login.php");
//header("Location: https://sso-uat-web.kku.ac.th/logout?app=" . $returnUrl);
exit();


?>
