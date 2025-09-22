<?php
// เชื่อมต่อฐานข้อมูล
require_once(__DIR__ . '/../../../config/database.php');

function getListDoctors()
{
    try {
        $pdo = getDatabaseConnection(); // เชื่อมต่อฐานข้อมูล

        // ดึงข้อมูลเด็กพร้อมข้อมูลครูที่ดูแลตามเงื่อนไข
        $stmt = $pdo->prepare("
        SELECT id, email, username
        FROM doctors_user;
    ");

        // เรียกใช้งานคำสั่ง SQL
        $stmt->execute();
        $doctor = $stmt->fetchAll(PDO::FETCH_ASSOC); // ใช้ FETCH_ASSOC เพื่อคืนค่าข้อมูลแบบ key-value

        // หากไม่มีข้อมูล ส่ง JSON เปล่า
        if (!$doctor) {
            echo json_encode([]); // ส่ง JSON ว่างๆ
            exit;
        }

        return json_encode([
        'status' => 'success',
        'data' => $doctor
    ]);
    } catch (PDOException $e) {
        // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือการ query
        echo json_encode(['error' => $e->getMessage()]);
    }
}