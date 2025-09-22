<?php
session_start();

require_once(__DIR__ . '/../../../config/database.php');

// ตรวจสอบการเข้าสู่ระบบ และบทบาทของผู้ใช้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// ฟังก์ชันสำหรับลบข้อมูลเด็ก
function deleteChildById($student_id) {
    try {
        $pdo = getDatabaseConnection();
        
        // เริ่ม transaction
        $pdo->beginTransaction();
        
        // ลบข้อมูลที่เกี่ยวข้องในตารางอื่นๆ ก่อน (ถ้ามี)
        // เช่น ประวัติการเข้าเรียน ประวัติสุขภาพ ฯลฯ
        
        // ลบข้อมูลจากตาราง children
        $query = "DELETE FROM children WHERE studentid = :studentid";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':studentid', $student_id, PDO::PARAM_STR);
        $result = $stmt->execute();
        
        if ($result) {
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error deleting child: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['studentid'])) {
    $student_id = $_POST['studentid'];
    
    if (deleteChildById($student_id)) {
        echo "success"; // ส่งค่ากลับเพื่อให้ JavaScript รู้ว่าลบสำเร็จ
    } else {
        echo "error"; // ส่งค่ากลับเพื่อให้ JavaScript รู้ว่าลบไม่สำเร็จ
    }
    exit();
}
?>
