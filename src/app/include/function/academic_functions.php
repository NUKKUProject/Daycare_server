<?php
require_once(__DIR__ . '/../../../config/database.php');

/**
 * ดึงข้อมูลปีการศึกษาทั้งหมด
 * @return array รายการปีการศึกษา
 */
function getAcademicYears() {
    try {
        $pdo = getDatabaseConnection();
        $sql = "SELECT id, name FROM academic_years ORDER BY name DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching academic years: " . $e->getMessage());
        return [];
    }
}

/**
 * ดึงข้อมูลเด็กตามปีการศึกษา กลุ่มเรียน และห้องเรียน
 * @param int $academicYearId ID ปีการศึกษา
 * @param string $group กลุ่มเรียน (medium, big, prep)
 * @param string $classroom ห้องเรียน
 * @return array ข้อมูลเด็กที่จัดกลุ่มแล้ว
 */
function getChildrenByAcademicYear($academicYearId, $group = null, $classroom = null) {
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "
            SELECT 
                c.studentid, 
                c.child_group, 
                c.classroom, 
                c.id, 
                c.firstname_th, 
                c.lastname_th, 
                c.profile_image, 
                c.prefix_th,
                c.nickname,
                c.qr_code,
                CASE 
                    WHEN a.status IS NULL THEN 'ยังไม่มาเรียน'
                    WHEN a.status = 'present' THEN 'มาเรียน'
                    WHEN a.status = 'absent' THEN 'ไม่มาเรียน'
                    WHEN a.status = 'leave' THEN 'ลา'
                    ELSE a.status
                END AS status,
                CASE 
                    WHEN a.status_checkout IS NULL THEN 'ยังไม่มีการบันทึก'
                    WHEN a.status_checkout = 'checked_out' THEN 'กลับบ้านแล้ว'
                    WHEN a.status_checkout = 'no_checked_out' THEN 'ยังไม่กลับบ้าน'
                    ELSE a.status_checkout 
                END AS status_checkout
            FROM 
                children c 
            LEFT JOIN 
                attendance a 
                ON c.studentid = a.student_id  
                AND DATE(a.check_date) = CURRENT_DATE
            WHERE 
                c.academic_year_id = :academic_year_id";

        $params = [':academic_year_id' => $academicYearId];

        if ($group) {
            $groupName = '';
            switch ($group) {
                case 'big':
                    $groupName = 'เด็กโต';
                    break;
                case 'medium':
                    $groupName = 'เด็กกลาง';
                    break;
                case 'prep':
                    $groupName = 'เตรียมอนุบาล';
                    break;
            }
            $sql .= " AND c.child_group = :group";
            $params[':group'] = $groupName;
        }

        if ($classroom) {
            $sql .= " AND c.classroom = :classroom";
            $params[':classroom'] = $classroom;
        }

        $sql .= " ORDER BY 
            CASE 
                WHEN c.child_group = 'เด็กกลาง' THEN 1
                WHEN c.child_group = 'เด็กโต' THEN 2
                WHEN c.child_group = 'เตรียมอนุบาล' THEN 3
                ELSE 4
            END,
            c.classroom,
            c.id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // จัดกลุ่มข้อมูล
        $result = [];
        foreach ($children as $child) {
            $groupKey = $child['child_group'];
            $classroomKey = $child['classroom'];

            if (!isset($result[$groupKey])) {
                $result[$groupKey] = ['group' => $groupKey, 'classrooms' => []];
            }

            if (!isset($result[$groupKey]['classrooms'][$classroomKey])) {
                $result[$groupKey]['classrooms'][$classroomKey] = [
                    'classroom' => $classroomKey,
                    'children' => []
                ];
            }

            $result[$groupKey]['classrooms'][$classroomKey]['children'][] = $child;
        }

        return array_values($result);
    } catch (PDOException $e) {
        error_log("Error fetching children by academic year: " . $e->getMessage());
        return [];
    }
}

/**
 * ดึงข้อมูลห้องเรียนตามปีการศึกษาและกลุ่มเรียน
 * @param int $academicYearId ID ปีการศึกษา
 * @param string $group กลุ่มเรียน
 * @return array รายการห้องเรียน
 */
function getClassroomsByAcademicYear($academicYearId, $group = null) {
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "SELECT DISTINCT classroom FROM children WHERE academic_year_id = :academic_year_id";
        $params = [':academic_year_id' => $academicYearId];

        if ($group) {
            $groupName = '';
            switch ($group) {
                case 'big':
                    $groupName = 'เด็กโต';
                    break;
                case 'medium':
                    $groupName = 'เด็กกลาง';
                    break;
                case 'prep':
                    $groupName = 'เตรียมอนุบาล';
                    break;
            }
            $sql .= " AND child_group = :group";
            $params[':group'] = $groupName;
        }

        $sql .= " ORDER BY classroom";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching classrooms by academic year: " . $e->getMessage());
        return [];
    }
}
?> 