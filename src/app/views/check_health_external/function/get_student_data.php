<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();

    if (!isset($_GET['student_id'])) {
        throw new Exception('ไม่พบรหัสนักเรียน');
    }

    $sql = "SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, 
            child_group, sex, classroom , birthday
            FROM children 
            WHERE studentid = :student_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => $_GET['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception('ไม่พบข้อมูลนักเรียน');
    }

    // ส่งข้อมูลกลับในรูปแบบที่ frontend ต้องการ
    echo json_encode([
        'status' => 'success',
        'student' => $student
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 