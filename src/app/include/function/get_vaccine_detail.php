<?php
require_once(__DIR__ . '/../../../config/database.php');

function getVaccineDetail($id) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT v.*, vl.vaccine_name 
            FROM vaccines v
            LEFT JOIN vaccine_list vl ON v.vaccine_list_id = vl.id
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getVaccineDetail: " . $e->getMessage());
        return null;
    }
}

// สำหรับการเรียกใช้งานผ่าน AJAX
if (isset($_GET['id'])) {
    $result = getVaccineDetail($_GET['id']);
    echo json_encode($result ? $result : ['error' => 'ไม่พบข้อมูล']);
}
?> 