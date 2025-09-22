<?php
// เชื่อมต่อฐานข้อมูล
require_once(__DIR__ . '../../../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // รับข้อมูล JSON จาก request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $userId = $data['id'];
    $newRole = $data['role'];
    // ตรวจสอบว่ามีการส่งข้อมูลครบถ้วนหรือไม่
    if (empty($userId) || empty($newRole)) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }
    // อัปเดตบทบาทของผู้ใช้ในฐานข้อมูล
    $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
    $stmt->bindParam(':role', $newRole, PDO::PARAM_STR);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตบทบาทผู้ใช้เรียบร้อยแล้ว']);
    } else {
        throw new Exception('เกิดข้อผิดพลาดในการอัปเดตบทบาทผู้ใช้');
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}