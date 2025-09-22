<?php
require_once(__DIR__ . '/../../../config/database.php');

function getVaccineHistory($student_id) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM vaccines 
            WHERE student_id = :student_id 
            ORDER BY vaccine_date DESC
        ");
        
        $stmt->execute(['student_id' => $student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error getting vaccine history: " . $e->getMessage());
        return [];
    }
}

// ถ้าถูกเรียกโดยตรงผ่าน AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(getVaccineHistory($_GET['student_id']));
}
?> 