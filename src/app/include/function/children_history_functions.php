<?php
require_once(__DIR__ . '/../../../config/database.php');

// ฟังก์ชันดึงข้อมูลปีการศึกษาทั้งหมด
function getAcademicYears() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT id, name, is_active 
            FROM academic_years 
            ORDER BY name DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching academic years: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงข้อมูลเด็กตามกลุ่มและปีการศึกษา
function getChildrenByGroupAndYear($currentTab, $academicYear) {
    try {
        $pdo = getDatabaseConnection();

        // กำหนดกลุ่มเด็กตาม tab
        $group = '';
        if ($currentTab === 'big') {
            $group = 'เด็กโต';
        } elseif ($currentTab === 'medium') {
            $group = 'เด็กกลาง';
        } elseif ($currentTab === 'prep') {
            $group = 'เตรียมอนุบาล';
        }

        // คำสั่ง SQL เพื่อดึงข้อมูลเด็ก
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
            ay.name as academic_year_name,
            -- สถานะการเช็คเข้า
            CASE 
                WHEN a.status IS NULL THEN 'ยังไม่มาเรียน'
                WHEN a.status = 'present' THEN 'มาเรียน'
                WHEN a.status = 'absent' THEN 'ไม่มาเรียน'
                WHEN a.status = 'leave' THEN 'ลา'
                ELSE a.status
            END AS status,
            -- สถานะการเช็คออก
            CASE 
                WHEN a.status_checkout IS NULL THEN 'ยังไม่มีการบันทึก'
                WHEN a.status_checkout = 'checked_out' THEN 'กลับบ้านแล้ว'
                WHEN a.status_checkout = 'no_checked_out' THEN 'ยังไม่กลับบ้าน'
                ELSE a.status_checkout 
            END AS status_checkout
        FROM 
            public.children c 
        INNER JOIN
            public.academic_years ay
            ON c.academic_year = ay.name::integer
        LEFT JOIN 
            public.attendance a 
            ON c.studentid = a.student_id  
            AND DATE(a.check_date) = CURRENT_DATE
        WHERE 
            (:group = '' OR c.child_group = :group)
            AND c.academic_year = :academic_year::integer
        ORDER BY 
            CASE 
                WHEN c.child_group = 'เด็กกลาง' THEN 1
                WHEN c.child_group = 'เด็กโต' THEN 2
                WHEN c.child_group = 'เตรียมอนุบาล' THEN 3
                ELSE 4
            END,
            c.classroom,
            c.id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':group' => $group,
            ':academic_year' => $academicYear
        ]);

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
        error_log("Error fetching children data: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงข้อมูลห้องเรียนตามกลุ่ม
function getClassroomsByGroup($group) {
    try {
        $pdo = getDatabaseConnection();

        // กำหนดกลุ่มเด็กตาม tab
        $groupName = '';
        if ($group === 'big') {
            $groupName = 'เด็กโต';
        } elseif ($group === 'medium') {
            $groupName = 'เด็กกลาง';
        } elseif ($group === 'prep') {
            $groupName = 'เตรียมอนุบาล';
        }

        $sql = "
            SELECT DISTINCT classroom 
            FROM children 
            WHERE child_group = :group 
            ORDER BY classroom
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':group' => $groupName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching classrooms: " . $e->getMessage());
        return [];
    }
}
?> 