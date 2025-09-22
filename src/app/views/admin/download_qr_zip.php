<?php
$filename = $_GET['file'] ?? '';
$basename = basename($filename); // ป้องกัน path traversal
$zipPath = __DIR__ . '/' . $basename;

if (!file_exists($zipPath)) {
    http_response_code(404);
    echo "ไม่พบไฟล์";
    exit;
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $basename . '"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
flush(); // เริ่มส่งไฟล์

readfile($zipPath);    // ✅ ส่งไฟล์ไปยัง browser
unlink($zipPath);      // ✅ ลบทันทีหลังส่งเสร็จ

exit;
?>