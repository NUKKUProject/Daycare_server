<?php
require_once(__DIR__ . '/../../../config/database.php');

// ฟังก์ชันดึงข้อมูลกลุ่มอายุที่ควรได้รับวัคซีน
function getVaccineAgeGroups() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT * FROM vaccine_age_groups ORDER BY display_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching vaccine age groups: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงข้อมูลวัคซีนตามกลุ่มอายุ
function getVaccinesByAgeGroup($age_group_id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM vaccine_list 
            WHERE age_group_id = ? AND is_active = true 
            ORDER BY id
        ");
        $stmt->execute([$age_group_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching vaccines by age group: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงข้อมูลการรับวัคซีนของนักเรียน
function getVaccineRecord($student_id, $vaccine_list_id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM vaccines 
            WHERE student_id = ? AND vaccine_list_id = ?
            ORDER BY vaccine_date DESC 
            LIMIT 1
        ");
        $stmt->execute([$student_id, $vaccine_list_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching vaccine record: " . $e->getMessage());
        return null;
    }
}

// ฟังก์ชันเพิ่ม/อัพเดทข้อมูลการรับวัคซีน
function saveVaccineRecord($data) {
    try {
        $pdo = getDatabaseConnection();
        
        if (isset($data['id'])) {
            // อัพเดทข้อมูลที่มีอยู่
            $stmt = $pdo->prepare("
                UPDATE vaccines SET 
                    vaccine_date = :vaccine_date,
                    vaccine_location = :vaccine_location,
                    vaccine_provider = :vaccine_provider,
                    lot_number = :lot_number,
                    next_appointment = :next_appointment,
                    vaccine_note = :vaccine_note,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            return $stmt->execute($data);
        } else {
            // เพิ่มข้อมูลใหม่
            $stmt = $pdo->prepare("
                INSERT INTO vaccines (
                    student_id, vaccine_list_id, vaccine_date, 
                    vaccine_location, vaccine_provider, lot_number,
                    next_appointment, vaccine_note
                ) VALUES (
                    :student_id, :vaccine_list_id, :vaccine_date,
                    :vaccine_location, :vaccine_provider, :lot_number,
                    :next_appointment, :vaccine_note
                )
            ");
            return $stmt->execute($data);
        }
    } catch (PDOException $e) {
        error_log("Error saving vaccine record: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันลบข้อมูลการรับวัคซีน
function deleteVaccineRecord($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("DELETE FROM vaccines WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting vaccine record: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันดึงข้อมูลวัคซีนตาม ID
function getVaccineById($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT vl.*, vag.age_group 
            FROM vaccine_list vl
            JOIN vaccine_age_groups vag ON vl.age_group_id = vag.id
            WHERE vl.id = ? AND vl.is_active = true
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching vaccine by ID: " . $e->getMessage());
        return null;
    }
}

// ฟังก์ชันแก้ไขข้อมูลวัคซีน
function updateVaccine($data) {
    try {
        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีวัคซีนซ้ำในกลุ่มอายุเดียวกันหรือไม่ (ยกเว้นตัวเอง)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM vaccine_list 
            WHERE age_group_id = ? 
            AND vaccine_name = ? 
            AND id != ? 
            AND is_active = true
        ");
        $stmt->execute([
            $data['age_group_id'],
            $data['vaccine_name'],
            $data['id']
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('มีวัคซีนนี้ในกลุ่มอายุนี้แล้ว');
        }

        // อัพเดทข้อมูลวัคซีน
        $stmt = $pdo->prepare("
            UPDATE vaccine_list 
            SET age_group_id = :age_group_id,
                vaccine_name = :vaccine_name,
                vaccine_description = :vaccine_description,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $result = $stmt->execute([
            'id' => $data['id'],
            'age_group_id' => $data['age_group_id'],
            'vaccine_name' => $data['vaccine_name'],
            'vaccine_description' => $data['vaccine_description']
        ]);

        if (!$result) {
            throw new Exception('ไม่สามารถอัพเดทข้อมูลได้');
        }

        return [
            'status' => 'success',
            'message' => 'อัพเดทข้อมูลวัคซีนสำเร็จ'
        ];

    } catch (Exception $e) {
        error_log("Error updating vaccine: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// ฟังก์ชันลบข้อมูลวัคซีน (Soft Delete)
function deleteVaccine($id) {
    try {
        $pdo = getDatabaseConnection();
        
        // ตรวจสอบว่ามีการใช้งานวัคซีนนี้ในประวัติการฉีดหรือไม่
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM vaccines 
            WHERE vaccine_list_id = ?
        ");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            // ถ้ามีการใช้งาน ให้ทำ soft delete
            $stmt = $pdo->prepare("
                UPDATE vaccine_list 
                SET is_active = false,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
        } else {
            // ถ้าไม่มีการใช้งาน สามารถลบออกจากฐานข้อมูลได้เลย
            $stmt = $pdo->prepare("
                DELETE FROM vaccine_list 
                WHERE id = ?
            ");
        }

        $result = $stmt->execute([$id]);

        if (!$result) {
            throw new Exception('ไม่สามารถลบข้อมูลได้');
        }

        return [
            'status' => 'success',
            'message' => 'ลบข้อมูลวัคซีนสำเร็จ'
        ];

    } catch (Exception $e) {
        error_log("Error deleting vaccine: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
} 