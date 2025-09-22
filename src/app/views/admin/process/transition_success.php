<?php
require_once(__DIR__ . '../../../../../config/database.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$student_ids = $data['student_ids'];
$dateSuccess = $data['dateSuccess'];
$SuccessType = $data['SuccessType'];

session_start();
$createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // หรือ 0

try {
    $pdo = getDatabaseConnection();
    // 1. อัปเดตสถานะใน students
    if (!empty($student_ids)) {
        // กรณี student_ids เป็น array ของ id (เช่น [1,2,3])
        $ids = $student_ids;
        // ถ้าเป็น array ของ object เช่น [{id:1}, {id:2}] ให้ดึง id
        if (is_array($ids) && isset($ids[0]) && is_array($ids[0]) && isset($ids[0]['id'])) {
            $ids = array_column($student_ids, 'id');
        }
        if (count($ids) > 0) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $update = $pdo->prepare("UPDATE children SET status='สำเร็จการศึกษา',date_success = ?, success_type = ? WHERE id IN ($in)");
            $update->execute(array_merge([$dateSuccess, $SuccessType], $ids));
        }
    }
  
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 