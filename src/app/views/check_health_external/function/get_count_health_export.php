<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();
    
    // รับค่าจาก POST
    $export_type = $_POST['export_type'] ?? 'academic_year';
    $academic_year = $_POST['academic_year'] ?? null;
    $exam_date = $_POST['exam_date'] ?? null;
    $doctor = $_POST['doctor'] ?? 'all';
    
    // สร้าง query ตามประเภทที่เลือก (เฉพาะข้อมูลที่มี doctor_name ไม่เป็นค่าว่าง)
    if ($export_type === 'exam_date') {
        // นับข้อมูลตามวันที่ตรวจ
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
        
    } else {
        // นับข้อมูลตามปีการศึกษา
        if (!$academic_year) {
            echo json_encode(['count' => 0]);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM health_data_external
            WHERE academic_year = :academic_year
            AND doctor_name IS NOT NULL 
            AND doctor_name != ''
        ");
        $stmt->execute([':academic_year' => $academic_year]);
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['count' => (int)$row['total']]);
    
} catch (PDOException $e) {
    error_log('Database error in get_count_health_export.php: ' . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log('Error in get_count_health_export.php: ' . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Server error']);
}
?>
