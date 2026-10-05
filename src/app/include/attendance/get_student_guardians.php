<?php
require_once(__DIR__ . '/../../../config/database.php');

$pdo = getDatabaseConnection();

if (isset($_GET['student_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                studentid as student_id,
                prefix_th as prefix,
                firstname_th as first_name,
                lastname_th as last_name,
                nickname,
                profile_image,
                classroom,             
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
            // เวลาเช็คชื่อเข้าของวันนี้ (ไม่มี หรือเป็น 00:00:00 ที่เกิดจากการบันทึกกลับบ้านอย่างเดียว -> null)
            $checkin_stmt = $pdo->prepare("
                SELECT
                    CASE
                        WHEN TO_CHAR(check_date, 'HH24:MI:SS') = '00:00:00' THEN NULL
                        ELSE TO_CHAR(check_date, 'HH24:MI:SS')
                    END AS checkin_time
                FROM attendance
                WHERE student_id = :student_id
                AND DATE(check_date) = CURRENT_DATE
                ORDER BY check_date ASC
                LIMIT 1
            ");
            $checkin_stmt->execute(['student_id' => $_GET['student_id']]);
            $data['checkin_time'] = $checkin_stmt->fetchColumn() ?: null;

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
