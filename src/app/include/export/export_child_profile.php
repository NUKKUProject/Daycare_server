<?php
require_once(__DIR__ . '/../../include/function/child_functions.php');

error_reporting(0);
ini_set('display_errors', 0);

try {
    $studentid = $_POST['studentid'] ?? null;
    if (!$studentid) {
        throw new Exception("ไม่พบรหัสนักเรียน");
    }

    // ดึงข้อมูลเด็ก
    $child = getChildById($studentid);
    if (!$child) {
        throw new Exception("ไม่พบข้อมูลนักเรียน");
    }

    $filename = 'child_profile_' . $studentid . '_' . date('Y-m-d_His') . '.csv';

    // เคลียร์ output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // ส่ง headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // เขียนข้อมูลส่วนตัว
    $headers = ['หัวข้อ', 'รายละเอียด'];
    fputcsv($output, $headers, ',', '"', '\\');

    $data = [
        ['รหัสนักเรียน', $child['studentid']],
        ['คำนำหน้า', $child['prefix_th']],
        ['ชื่อ', $child['firstname_th']],
        ['นามสกุล', $child['lastname_th']],
        ['ชื่อเล่น', $child['nickname']],
        ['กลุ่ม', $child['child_group']],
        ['ห้องเรียน', $child['classroom']],
        // เพิ่มข้อมูลอื่นๆ ตามต้องการ
    ];

    foreach ($data as $row) {
        fputcsv($output, $row, ',', '"', '\\');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('HTTP/1.1 500 Internal Server Error');
    echo "เกิดข้อผิดพลาดในการ export ข้อมูล: " . $e->getMessage();
}
?> 