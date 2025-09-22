<?php
require_once __DIR__ . '/../../config/database.php';

function getAcademicYears() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT academic_year FROM academic_year ORDER BY academic_year DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching academic years: " . $e->getMessage());
        return [];
    }
}
?> 