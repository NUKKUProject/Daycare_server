<?php
require_once(__DIR__ . '/../../../config/database.php');

/**
 * ดึงข้อมูลปีการศึกษาทั้งหมด
 * @return array รายการปีการศึกษา
 */
function getAcademicYears() {
    try {
        $pdo = getDatabaseConnection();
        $sql = "SELECT id, name, is_active FROM academic_years ORDER BY name DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching academic years: " . $e->getMessage());
        return [];
    }
}

/**
 * เพิ่มปีการศึกษาใหม่
 * @param string $name ชื่อปีการศึกษา
 * @return array ผลลัพธ์การทำงาน
 */
function addAcademicYear($name) {
    try {
        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีปีการศึกษานี้อยู่แล้วหรือไม่
        $stmt = $pdo->prepare("SELECT id FROM academic_years WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'ปีการศึกษานี้มีในระบบแล้ว'
            ];
        }

        // เพิ่มปีการศึกษาใหม่
        $sql = "INSERT INTO academic_years (name) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name]);

        return [
            'success' => true,
            'message' => 'เพิ่มปีการศึกษาเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        error_log("Error adding academic year: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มปีการศึกษา'
        ];
    }
}

/**
 * แก้ไขข้อมูลปีการศึกษา
 * @param int $id ID ปีการศึกษา
 * @param string $name ชื่อปีการศึกษา
 * @param bool $isActive สถานะการใช้งาน
 * @return array ผลลัพธ์การทำงาน
 */
function editAcademicYear($id, $name, $isActive) {
    try {
        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีปีการศึกษานี้อยู่แล้วหรือไม่ (ยกเว้นปีการศึกษาที่กำลังแก้ไข)
        $stmt = $pdo->prepare("SELECT id FROM academic_years WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'ปีการศึกษานี้มีในระบบแล้ว'
            ];
        }

        // อัพเดทข้อมูลปีการศึกษา
        $sql = "UPDATE academic_years SET name = ?, is_active = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $isActive, $id]);

        return [
            'success' => true,
            'message' => 'แก้ไขข้อมูลปีการศึกษาเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        error_log("Error editing academic year: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลปีการศึกษา'
        ];
    }
}

/**
 * ลบปีการศึกษา
 * @param int $id ID ปีการศึกษา
 * @return array ผลลัพธ์การทำงาน
 */
function deleteAcademicYear($id) {
    try {
        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีเด็กที่ใช้ปีการศึกษานี้อยู่หรือไม่
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM children WHERE academic_year_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถลบปีการศึกษาได้เนื่องจากมีเด็กที่ใช้ปีการศึกษานี้อยู่'
            ];
        }

        // ลบปีการศึกษา
        $sql = "DELETE FROM academic_years WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        return [
            'success' => true,
            'message' => 'ลบปีการศึกษาเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        error_log("Error deleting academic year: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการลบปีการศึกษา'
        ];
    }
}

// จัดการการเรียกใช้ API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // รับข้อมูล JSON จาก request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่ระบุการทำงาน'
        ]);
        exit;
    }

    switch ($data['action']) {
        case 'add':
            if (!isset($data['name'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน'
                ]);
                exit;
            }
            $result = addAcademicYear($data['name']);
            echo json_encode($result);
            break;

        case 'edit':
            if (!isset($data['id']) || !isset($data['name'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน'
                ]);
                exit;
            }
            $result = editAcademicYear(
                $data['id'],
                $data['name'],
                $data['is_active'] ?? false
            );
            echo json_encode($result);
            break;

        case 'delete':
            if (!isset($data['id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่ระบุ ID ปีการศึกษา'
                ]);
                exit;
            }
            $result = deleteAcademicYear($data['id']);
            echo json_encode($result);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'การทำงานไม่ถูกต้อง'
            ]);
    }
    exit;
}

// ถ้าเป็นการเรียก GET ให้ส่งข้อมูลปีการศึกษาทั้งหมด
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(getAcademicYears());
    exit;
} 