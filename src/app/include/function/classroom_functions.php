<?php
require_once(__DIR__ . '/../../../config/database.php');

// รับ POST request สำหรับจัดการห้องเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล JSON จาก request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $response = ['success' => false, 'message' => 'Invalid action'];

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'add':
                $response = addClassroom($data);
                break;
            case 'edit':
                if (isset($data['old_classroom_name'], $data['old_child_group'], $data['classroom_name'], $data['child_group'])) {
                    $response = updateClassroom($data);
                }
                break;
            case 'delete':
                if (isset($data['classroom_name'], $data['child_group'])) {
                    $response = deleteClassroom($data);
                }
                break;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * ฟังก์ชันดึงข้อมูลห้องเรียนทั้งหมด
 */
function getAllClassrooms() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM classrooms 
            WHERE status = 'active' 
            ORDER BY child_group, classroom_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching classrooms: " . $e->getMessage());
        return false;
    }
}

/**
 * ฟังก์ชันดึงข้อมูลห้องเรียนตามกลุ่ม
 */
function getClassroomsByGroup($childGroup) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM classrooms 
            WHERE child_group = :child_group 
            AND status = 'active' 
            ORDER BY classroom_name
        ");
        $stmt->execute(['child_group' => $childGroup]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching classrooms by group: " . $e->getMessage());
        return false;
    }
}

/**
 * ฟังก์ชันเพิ่มห้องเรียนใหม่
 */
function addClassroom($data) {
    try {
        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีห้องเรียนนี้อยู่แล้วหรือไม่
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM classrooms 
            WHERE classroom_name = :classroom_name 
            AND child_group = :child_group 
            AND status = 'active'
        ");
        $checkStmt->execute([
            'classroom_name' => $data['classroom_name'],
            'child_group' => $data['child_group']
        ]);
        
        if ($checkStmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'ห้องเรียนนี้มีอยู่แล้ว'
            ];
        }

        // เพิ่มห้องเรียนใหม่
        $stmt = $pdo->prepare("
            INSERT INTO classrooms (classroom_name, child_group) 
            VALUES (:classroom_name, :child_group)
        ");
        
        if ($stmt->execute([
            'classroom_name' => $data['classroom_name'],
            'child_group' => $data['child_group']
        ])) {
            return [
                'success' => true,
                'message' => 'เพิ่มห้องเรียนสำเร็จ'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มห้องเรียน'
        ];
    } catch (PDOException $e) {
        error_log("Error adding classroom: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
}

/**
 * ฟังก์ชันแก้ไขข้อมูลห้องเรียน
 */
function updateClassroom($data) {
    try {
        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีห้องเรียนที่จะแก้ไขอยู่จริงหรือไม่
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM classrooms 
            WHERE classroom_name = :old_classroom_name 
            AND child_group = :old_child_group
            AND status = 'active'
        ");
        $checkStmt->execute([
            'old_classroom_name' => $data['old_classroom_name'],
            'old_child_group' => $data['old_child_group']
        ]);
        
        if ($checkStmt->fetchColumn() == 0) {
            return [
                'success' => false,
                'message' => 'ไม่พบห้องเรียนที่ต้องการแก้ไข'
            ];
        }

        // อัพเดทข้อมูลห้องเรียน
        $stmt = $pdo->prepare("
            UPDATE classrooms 
            SET classroom_name = :classroom_name,
                child_group = :child_group,
                updated_at = CURRENT_TIMESTAMP
            WHERE classroom_name = :old_classroom_name 
            AND child_group = :old_child_group
            AND status = 'active'
        ");
        
        if ($stmt->execute([
            'classroom_name' => $data['classroom_name'],
            'child_group' => $data['child_group'],
            'old_classroom_name' => $data['old_classroom_name'],
            'old_child_group' => $data['old_child_group']
        ])) {
            return [
                'success' => true,
                'message' => 'แก้ไขข้อมูลห้องเรียนสำเร็จ'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล'
        ];
    } catch (PDOException $e) {
        error_log("Error updating classroom: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
}

/**
 * ฟังก์ชันลบห้องเรียน (Soft Delete)
 */
function deleteClassroom($data) {
    try {
        $pdo = getDatabaseConnection();
        
        // Soft delete ห้องเรียน
        $stmt = $pdo->prepare("
            UPDATE classrooms 
            SET status = 'deleted',
                updated_at = CURRENT_TIMESTAMP
            WHERE classroom_name = :classroom_name
            AND child_group = :child_group
            AND status = 'active'
        ");
        
        if ($stmt->execute([
            'classroom_name' => $data['classroom_name'],
            'child_group' => $data['child_group']
        ])) {
            return [
                'success' => true,
                'message' => 'ลบห้องเรียนสำเร็จ'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการลบห้องเรียน'
        ];
    } catch (PDOException $e) {
        error_log("Error deleting classroom: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
} 