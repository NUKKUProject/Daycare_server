<?php
require_once(__DIR__ . '/../../include/function/child_functions.php');

// เพิ่ม error reporting
error_reporting(0); // ปิด error reporting ไว้ก่อน
ini_set('display_errors', 0);

try {
    // รับค่าจากฟอร์ม
    $childGroup = $_POST['child_group'] ?? 'all';
    
    // ดึงข้อมูลเด็กตามกลุ่มที่เลือก
    $data = getChildrenGroupedByTab($childGroup);

    // กำหนดชื่อไฟล์
    $filename = 'children_data_' . date('Y-m-d_His') . '.csv';

    // เคลียร์ output buffer ทั้งหมด
    while (ob_get_level()) {
        ob_end_clean();
    }

    // ส่ง headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // เปิด output stream
    $output = fopen('php://output', 'w');
    
    // เขียน BOM สำหรับ UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // เขียน headers
    $headers = ['รหัสนักเรียน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'ชื่อเล่น', 'กลุ่ม', 'ห้องเรียน'];
    fputcsv($output, $headers, ',', '"', '\\');  // เพิ่ม escape character

    // เขียนข้อมูล
    foreach ($data as $groupData) {
        if (!empty($groupData['classrooms'])) {
            foreach ($groupData['classrooms'] as $classroomData) {
                if (!empty($classroomData['children'])) {
                    foreach ($classroomData['children'] as $child) {
                        $rowData = [
                            $child['studentid'],
                            $child['prefix_th'],
                            $child['firstname_th'],
                            $child['lastname_th'],
                            $child['nickname'],
                            $child['child_group'],
                            $child['classroom']
                        ];
                        fputcsv($output, $rowData, ',', '"', '\\');  // เพิ่ม escape character
                    }
                }
            }
        }
    }

    // ปิด file handle
    fclose($output);
    exit;

} catch (Exception $e) {
    // เคลียร์ output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('HTTP/1.1 500 Internal Server Error');
    echo "เกิดข้อผิดพลาดในการ export ข้อมูล: " . $e->getMessage();
}
?>
