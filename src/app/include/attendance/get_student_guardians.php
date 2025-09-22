<?php
require_once(__DIR__ . '/../../../config/database.php');

$pdo = getDatabaseConnection();

if (isset($_GET['student_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                father_image,
                mother_image,
                relative_image,
                father_first_name,
                father_last_name,
                mother_first_name,
                mother_last_name,
                relative_first_name,
                relative_last_name
            FROM children 
            WHERE studentid = :student_id
        ");
        
        $stmt->execute(['student_id' => $_GET['student_id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            echo json_encode([
                'status' => 'success',
                'data' => $data
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบรหัสนักเรียน'
    ]);
}
?> 