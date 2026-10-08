<?php
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // รับค่า ID จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการลบ');
    }

    $id = $data['id'];

    // รอบที่ปิดแล้วลบไม่ได้
    $lock = $pdo->prepare('SELECT r.status FROM health_tooth_external h LEFT JOIN tooth_exam_rounds r ON r.id = h.round_id WHERE h.id = :id');
    $lock->execute([':id' => $id]);
    if ($lock->fetchColumn() === 'closed') {
        throw new Exception('รอบตรวจนี้ถูกปิดแล้ว ไม่สามารถลบได้');
    }

    // เตรียมคำสั่ง SQL สำหรับลบข้อมูล
    $sql = "DELETE FROM health_tooth_external WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    // ทำการลบข้อมูล
    if (!$stmt->execute(['id' => $id])) {
        throw new Exception('ไม่สามารถลบข้อมูลได้');
    }

    // ส่งผลลัพธ์กลับ
    echo json_encode([
        'status' => 'success',
        'message' => 'ลบข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 