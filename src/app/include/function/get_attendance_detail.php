<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // รับ ID จาก URL
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ไม่พบ ID ที่ต้องการดู');
    }

    // ดึงข้อมูลรายละเอียดจากตาราง attendance และ children
    $sql = "SELECT a.*,
            c.studentid as student_id,
            c.prefix_th,
            c.firstname_th,
            c.lastname_th,
            c.child_group,
            c.classroom,
            CASE 
                WHEN a.status_checkout = 'checked_out' THEN 'กลับแล้ว'
                WHEN a.status_checkout = 'no_checked_out' THEN 'ยังไม่กลับ'
                ELSE NULL 
            END as status_checkout_th,
            CASE 
                WHEN a.status = 'present' THEN 'มาเรียน'
                WHEN a.status = 'absent' THEN 'ไม่มาเรียน'
                WHEN a.status = 'leave' THEN 'ลา'
                ELSE a.status 
            END as status_th
            FROM attendance a
            JOIN children c ON a.student_id = c.studentid
            WHERE a.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('ไม่พบข้อมูล');
    }

    // แปลงวันที่และเวลาให้อยู่ในรูปแบบที่ถูกต้อง
    if ($result['check_date']) {
        $result['check_date'] = date('Y-m-d H:i:s', strtotime($result['check_date']));
    }
    if ($result['check_out_time']) {
        $result['check_out_time'] = date('Y-m-d H:i:s', strtotime($result['check_out_time']));
    }
    if ($result['created_at']) {
        $result['created_at'] = date('Y-m-d H:i:s', strtotime($result['created_at']));
    }
    if ($result['updated_at']) {
        $result['updated_at'] = date('Y-m-d H:i:s', strtotime($result['updated_at']));
    }

    // ส่งผลลัพธ์
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 