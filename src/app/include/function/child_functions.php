<?php
require_once(__DIR__ . '/../../../config/database.php');// ใช้ require_once แทน include เพื่อป้องกันการโหลดซ้ำ


$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// ดึงข้อมูลเด็กทั้งหมด
function getChildrenData($checkDate = null)
{
    try {
        $pdo = getDatabaseConnection();

        if (!$pdo) {
            throw new Exception("Failed to connect to the database.");
        }

        $sql = "
            SELECT 
                c.*,
                a.check_date,
                a.check_out_time,
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
                attendance a ON c.studentid = a.student_id 
                AND DATE(a.check_date) = CURRENT_DATE
            ORDER BY 
                CASE 
                    WHEN c.child_group LIKE '%เด็กโต%' THEN 1
                    WHEN c.child_group LIKE '%เด็กกลาง%' THEN 2
                    WHEN c.child_group LIKE '%เตรียมอนุบาล%' THEN 3
                    ELSE 4
                END,
                c.classroom,
                c.firstname_th
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // แปลงรูปแบบวันที่และจัดการค่า null
        foreach ($children as &$child) {
            // แปลงรูปแบบวันเกิด
            if (!empty($child['birthday'])) {
                $child['birthday'] = date('Y-m-d', strtotime($child['birthday']));
            }

            // จัดการรูปโปรไฟล์
            if (empty($child['profile_image'])) {
                $child['profile_image'] = '../../../public/assets/images/avatar.png';
            }

            // จัดการค่า null สำหรับฟิลด์ต่างๆ
            $nullableFields = [
                'blood_type', 'congenital_disease', 'allergic_food', 
                'allergic_medicine', 'address', 'district', 'amphoe', 
                'province', 'zipcode', 'emergency_contact', 
                'emergency_phone', 'emergency_relation'
            ];
            
            foreach ($nullableFields as $field) {
                if (empty($child[$field])) {
                    $child[$field] = '-';
                }
            }
        }

        // ดีบัก: ตรวจสอบข้อมูลที่ได้
        error_log("Children data: " . print_r($children, true));

        return $children;

    } catch (Exception $e) {
        error_log("Error fetching children data: " . $e->getMessage());
        return [];
    }
}


// ดึงข้อมูลเด็กที่อยู่ในความดูแลของคุณครูแต่ละคน กลุ่มเรียน ห้องเรียน
function getChildrenDataByTeacher($teacher_id, $currentTab)
{
    try {
        $pdo = getDatabaseConnection();

        $sql = "
            SELECT 
                c.studentid, 
                c.child_group, 
                c.classroom,
                c.nickname, 
                c.id, 
                c.firstname_th, 
                c.lastname_th, 
                c.profile_image, 
                c.prefix_th,
                t.teacher_id,
                t.first_name AS teacher_firstname,
                t.last_name AS teacher_lastname,
                t.group_ids AS teacher_group,
                t.classroom_ids AS teacher_classroom,
                t.profile_image AS teacher_image,
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
                END AS status_checkout,
                CASE 
                    WHEN c.child_group = 'เด็กโต' THEN 1
                    WHEN c.child_group = 'เด็กกลาง' THEN 2
                    WHEN c.child_group = 'เตรียมอนุบาล' THEN 3
                    ELSE 4
                END AS child_group_order
            FROM 
                children c
            LEFT JOIN 
                public.attendance a 
                ON c.studentid = a.student_id
                AND DATE(a.check_date) = CURRENT_DATE
            JOIN 
                teachers t 
                ON c.child_group = ANY(string_to_array(t.group_ids, ','))
                AND c.classroom = ANY(string_to_array(t.classroom_ids, ','))
            WHERE 
                t.teacher_id = :teacher_id
        ";

        // เพิ่มเงื่อนไขการกรองตามแท็บ (ยกเว้นแท็บ 'all')
        if ($currentTab !== 'all') {
            $group = '';
            if ($currentTab === 'big') {
                $group = 'เด็กโต';
            } elseif ($currentTab === 'medium') {
                $group = 'เด็กกลาง';
            } elseif ($currentTab === 'prep') {
                $group = 'เตรียมอนุบาล';
            }

            $sql .= " AND c.child_group = :group";
        }
        $sql .= " ORDER BY child_group_order, c.classroom, c.id";

        $stmt = $pdo->prepare($sql);

        // ผูกค่าพารามิเตอร์
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        if ($currentTab !== 'all') {
            $stmt->bindParam(':group', $group, PDO::PARAM_STR);
        }

        $stmt->execute();
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($children) {
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
        } else {
            return null;
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return null;
    }
}


// ดึงข้อมูลของคุณครูตาม id ของคุณครู
function getTeacherById($teacher_id)
{
    try {
        $pdo = getDatabaseConnection(); // เชื่อมต่อฐานข้อมูล

        // สร้าง SQL query
        $sql = "
            SELECT 
                t.teacher_id,
                t.first_name AS teacher_firstname,
                t.last_name AS teacher_lastname,
                t.email,
                t.phone_number,
                t.classroom_ids AS teacher_classroom,
                t.group_ids AS teacher_group,
                t.profile_image AS teacher_image
            FROM teachers t
            WHERE t.teacher_id = :teacher_id
        ";

        // เตรียมคำสั่ง SQL
        $stmt = $pdo->prepare($sql);

        // ผูกค่าพารามิเตอร์
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);

        // เรียกใช้คำสั่ง SQL
        $stmt->execute();

        // ดึงข้อมูลที่ได้
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        // หากข้อมูลมี ส่งข้อมูลเป็น JSON
        if ($teacher) {
            return $teacher;
        } else {
            return null; // หากไม่พบข้อมูล
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return null;
    }
}


// ดึงข้อมูลของคุณครูทั้งหมด
function getAllTeachers()
{
    try {
        $pdo = getDatabaseConnection(); // เชื่อมต่อฐานข้อมูล

        // สร้าง SQL query
        $sql = "
            SELECT 
                t.teacher_id AS teacher_id,
                t.first_name AS teacher_firstname,
                t.last_name AS teacher_lastname,
                t.email,
                t.phone_number,
                t.classroom_ids AS teacher_classroom,
                t.group_ids AS teacher_group,
                t.profile_image AS teacher_image,
                u.role AS user_role
            FROM teachers t
            LEFT JOIN users u ON u.username = t.email
        ";

        // เตรียมคำสั่ง SQL
        $stmt = $pdo->prepare($sql);

        // เรียกใช้คำสั่ง SQL
        $stmt->execute();

        // ดึงข้อมูลทั้งหมด
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // หากมีข้อมูล
        if ($teachers) {
            return $teachers;
        } else {
            return null; // หากไม่พบข้อมูล
        }
    } catch (PDOException $e) {
        // แสดงข้อความข้อผิดพลาดหากมีการเชื่อมต่อหรือการ query ฐานข้อมูลผิด
        echo "Error: " . $e->getMessage();
        return null;
    }
}



// ฟังก์ชันเพื่อดึงข้อมูลเด็กตามกลุ่มและเรียงตามห้อง
function getChildrenGroupedByTab($currentTab)
{
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

    // คำสั่ง SQL เพื่อดึงข้อมูลเด็กตามกลุ่มและแยกตามห้องเรียน
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
    LEFT JOIN 
        public.attendance a 
        ON c.studentid = a.student_id  
        AND DATE(a.check_date) = CURRENT_DATE
    WHERE 
        (:group = '' OR c.child_group = :group)
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
    $stmt->execute([':group' => $group]);

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
}

function get_childgroup()
{
    try {
        $pdo = getDatabaseConnection();

        // สร้าง SQL query เพื่อดึง child_group ที่ครูคนนี้ดูแล
        $sql = "
            SELECT DISTINCT c.child_group
            FROM children c
            JOIN teachers t
                ON (c.child_group = ANY(string_to_array(t.group_ids, ',')))
        ";

        // เตรียมคำสั่ง SQL
        $stmt = $pdo->prepare($sql);

        // เรียกใช้คำสั่ง SQL
        $stmt->execute();

        // ดึงข้อมูลทั้งหมดจากฐานข้อมูล
        $childGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ตรวจสอบว่ามีข้อมูลหรือไม่
        if ($childGroups) {
            return $childGroups;
        } else {
            return []; // หากไม่พบข้อมูล
        }
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาดในการเชื่อมต่อ
        echo "Error: " . $e->getMessage();
        return [];
    }
}

// ฟังก์ชันดึงข้อมูล child_group โดยตรวจสอบ teacher_id
function getChildGroups($teacher_id)
{
    try {
        $pdo = getDatabaseConnection();

        // สร้าง SQL query เพื่อดึง child_group ที่ครูคนนี้ดูแล
        $sql = "
            SELECT DISTINCT c.child_group
            FROM children c
            JOIN teachers t
                ON (c.child_group = ANY(string_to_array(t.group_ids, ',')))
            WHERE t.teacher_id = :teacher_id
        ";

        // เตรียมคำสั่ง SQL
        $stmt = $pdo->prepare($sql);

        // ผูกค่าพารามิเตอร์
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);

        // เรียกใช้คำสั่ง SQL
        $stmt->execute();

        // ดึงข้อมูลทั้งหมดจากฐานข้อมูล
        $childGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ตรวจสอบว่ามีข้อมูลหรือไม่
        if ($childGroups) {
            return $childGroups;
        } else {
            return []; // หากไม่พบข้อมูล
        }
    } catch (PDOException $e) {
        // หากเกิดข้อผิดพลาดในการเชื่อมต่อ
        echo "Error: " . $e->getMessage();
        return [];
    }
}


// ฟังก์ชันดึงข้อมูล classroom
function getClassrooms($child_group = null)
{
    $pdo = getDatabaseConnection();

    // ถ้าเลือกกลุ่มเรียน จะกรองห้องเรียนตามกลุ่มนั้น
    if ($child_group) {
        $query = "SELECT DISTINCT classroom FROM children WHERE child_group = :child_group";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':child_group', $child_group);
    } else {
        // ถ้าไม่ได้เลือกกลุ่มเรียน ให้แสดงห้องทั้งหมด
        $query = "SELECT DISTINCT classroom FROM children";
        $stmt = $pdo->query($query);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ฟังก์ชันดึงข้อมูลที่เลือกจาก child_group และ classroom
function getChildrenGroupClassroom($child_group, $classroom)
{
    $pdo = getDatabaseConnection();
    $query = "SELECT firstname_th, lastname_th, child_group, classroom
              FROM children
              WHERE child_group = :child_group AND classroom = :classroom";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['child_group' => $child_group, 'classroom' => $classroom]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันเพื่อดึงข้อมูลเด็กตาม ID
function getChildById($studentid)
{
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "SELECT * FROM public.children WHERE studentid = :studentid";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':studentid', $studentid, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            error_log("No child found with studentid: " . $studentid);
            return false;
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error fetching child by studentid: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลเด็กจากฐานข้อมูล
function getAllChildren()
{
    try {
        $pdo = getDatabaseConnection(); // เชื่อมต่อฐานข้อมูล

        // คำสั่ง SQL สำหรับเลือกข้อมูลเด็กทั้งหมด
        $query = "SELECT * FROM children";
        $stmt = $pdo->prepare($query); // เตรียมคำสั่ง SQL
        $stmt->execute(); // รันคำสั่ง SQL

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // ส่งข้อมูลทั้งหมดที่ได้กลับในรูปแบบ Array
    } catch (Exception $e) {
        error_log("Error fetching children: " . $e->getMessage()); // บันทึกข้อผิดพลาด
        return false; // หากเกิดข้อผิดพลาด
    }
}

/**
 * ดึงข้อมูลกิจกรรมตามวันที่ที่กำหนด
 * 
 * @param string $date วันที่ในรูปแบบ 'Y-m-d' (เช่น '2024-12-03')
 * @return array ข้อมูลกิจกรรมที่ดึงมาจากฐานข้อมูล
 */
function getActivitiesByDate($date)
{
    try {
        $pdo = getDatabaseConnection();

        // ดึงกิจกรรมตามวันที่ที่กำหนด
        $stmt = $pdo->prepare("SELECT * FROM activities WHERE activity_date = :date ORDER BY activity_time");
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("เกิดข้อผิดพลาด: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงประวัติการฉีดวัคซีน
function getVaccinationHistory($studentid) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM vaccinations 
            WHERE student_id = :studentid 
            ORDER BY vaccination_date DESC
        ");
        $stmt->execute(['studentid' => $studentid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching vaccination history: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันดึงประวัติการมาเรียน
function getAttendanceHistory($studentid, $search_date = null) {
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "SELECT a.*, t.first_name as teacher_name 
                FROM attendance a 
                LEFT JOIN teachers t ON a.teacher_id = t.teacher_id 
                WHERE a.student_id = :studentid";
        $params = ['studentid' => $studentid];
        
        if ($search_date) {
            $sql .= " AND DATE(a.check_date) = :search_date";
            $params['search_date'] = $search_date;
        }
        
        $sql .= " ORDER BY a.check_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching attendance history: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันดึงประวัติการตรวจร่างกาย
function getHealthCheckHistory($studentid, $search_date = null) {
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "SELECT * FROM health_data WHERE student_id = :studentid";
        $params = ['studentid' => $studentid];
        
        if ($search_date) {
            $sql .= " AND DATE(created_at) = :search_date";
            $params['search_date'] = $search_date;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching health check history: " . $e->getMessage());
        return false;
    }
}

function getAllChildGroups() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT DISTINCT child_group 
            FROM children 
            WHERE child_group IS NOT NULL 
            ORDER BY child_group
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching all child groups: " . $e->getMessage());
        return [];
    }
}

function checkUserExists($studentId) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

?>