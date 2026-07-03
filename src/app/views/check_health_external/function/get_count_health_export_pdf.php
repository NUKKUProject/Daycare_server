<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();
    
    // รับค่าจาก POST
    $exam_date = $_POST['exam_date'] ?? null;
    $doctor = $_POST['doctor'] ?? 'all';
    
    // ตรวจสอบข้อมูลนำเข้า
    if (!$exam_date) {
        echo json_encode(['count' => 0]);
        exit;
    }
    
    // กรองตามแพทย์ (ถ้าไม่ใช่ 'all')
    if ($doctor !== 'all' && !empty($doctor)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM health_data_external
            WHERE exam_date = :exam_date
            AND doctor_name = :doctor
        ");
        $stmt->execute([':exam_date' => $exam_date, ':doctor' => $doctor]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM health_data_external
            WHERE exam_date = :exam_date
            AND doctor_name IS NOT NULL 
            AND doctor_name != ''
        ");
        $stmt->execute([':exam_date' => $exam_date]);
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['count' => (int)$row['total']]);
    
} catch (PDOException $e) {
    error_log('Database error in get_count_health_export_pdf.php: ' . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log('Error in get_count_health_export_pdf.php: ' . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Server error']);
}
?>