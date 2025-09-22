<?php
require_once(__DIR__ . '/../../include/function/child_functions.php');
require_once(__DIR__ . '/../../include/function/vaccine_functions.php');

error_reporting(0);
ini_set('display_errors', 0);

try {
    $studentid = $_POST['studentid'] ?? null;
    if (!$studentid) {
        throw new Exception("ไม่พบรหัสนักเรียน");
    }

    // ดึงข้อมูลวัคซีน
    $age_groups = getVaccineAgeGroups();
    
    $filename = 'child_vaccine_' . $studentid . '_' . date('Y-m-d_His') . '.csv';

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

    // เขียน headers
    $headers = ['ช่วงอายุ', 'วัคซีน', 'วันที่ได้รับ'];
    fputcsv($output, $headers, ',', '"', '\\');

    // เขียนข้อมูลวัคซีน
    foreach ($age_groups as $group) {
        $vaccines = getVaccinesByAgeGroup($group['id']);
        foreach ($vaccines as $vaccine) {
            $vaccine_record = getVaccineRecord($studentid, $vaccine['id']);
            $row = [
                $group['age_group'],
                $vaccine['vaccine_name'],
                $vaccine_record ? $vaccine_record['vaccination_date'] : 'ยังไม่ได้รับ'
            ];
            fputcsv($output, $row, ',', '"', '\\');
        }
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