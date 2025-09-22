<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->query("
        SELECT * 
        FROM vaccine_age_groups 
        ORDER BY display_order
    ");
    
    $age_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'age_groups' => $age_groups
    ]);

} catch (Exception $e) {
    error_log("Error in get_age_groups: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่สามารถดึงข้อมูลกลุ่มอายุได้'
    ]);
}
?> 