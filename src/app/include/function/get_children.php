<?php
// เชื่อมต่อฐานข้อมูล
require_once 'child_functions.php';

try {
    $pdo = getDatabaseConnection(); // เชื่อมต่อฐานข้อมูล

    // ตรวจสอบว่ามีการส่งค่า child_group, classroom, และ teacher_id
    if (isset($_GET['child_group'], $_GET['classroom'], $_GET['teacher_id'])) {
        $child_group = $_GET['child_group'];
        $classroom = $_GET['classroom'];
        $teacher_id = $_GET['teacher_id'];

        // ดึงข้อมูลเด็กพร้อมข้อมูลครูที่ดูแลตามเงื่อนไข
        $stmt = $pdo->prepare("
            SELECT DISTINCT ON (c.studentid) 
                c.studentid, 
                c.prefix_th, 
                c.firstname_th, 
                c.lastname_th,
                c.nickname, 
                c.child_group, 
                c.classroom, 
                t.group_ids AS group_name,
                t.classroom_ids AS classroom_name,
                t.first_name AS teacher_first_name, 
                t.last_name AS teacher_last_name,
                -- เพิ่มกรณีสำหรับ status ที่เป็น NULL
                CASE 
                    WHEN a.status IS NULL THEN 'ยังไม่มาเรียน'
                    WHEN a.status = 'present' THEN 'มาเรียน'
                    WHEN a.status = 'absent' THEN 'ไม่มาเรียน'
                    ELSE a.status
                END AS status
            FROM children c
            LEFT JOIN teachers t 
                ON c.classroom = ANY(string_to_array(t.classroom_ids, ',')::text[])  -- ใช้เครื่องหมายคอมมา (',') แทนช่องว่าง
                AND c.child_group = ANY(string_to_array(t.group_ids, ',')::text[])  -- ใช้เครื่องหมายคอมมา (',') แทนช่องว่าง
            LEFT JOIN public.attendance a
                ON c.studentid = a.student_id
                AND DATE(a.check_date) = CURRENT_DATE  -- ตรวจสอบวันที่ปัจจุบัน
            WHERE t.teacher_id = :teacher_id
                AND c.child_group = :child_group
                AND c.classroom = :classroom
                AND (a.status IS NOT NULL AND a.status != 'absent')  -- เงื่อนไขให้ไม่ดึงเด็กที่สถานะ NULL หรือ absent
            ORDER BY c.studentid, t.teacher_id;
        ");

        // เตรียมคำสั่ง SQL และผูกค่าพารามิเตอร์
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->bindParam(':child_group', $child_group, PDO::PARAM_STR);
        $stmt->bindParam(':classroom', $classroom, PDO::PARAM_STR);

        // เรียกใช้งานคำสั่ง SQL
        $stmt->execute();
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC); // ใช้ FETCH_ASSOC เพื่อคืนค่าข้อมูลแบบ key-value

        // หากไม่มีข้อมูล ส่ง JSON เปล่า
        if (!$children) {
            echo json_encode([]); // ส่ง JSON ว่างๆ
            exit;
        }

        // ส่งข้อมูลกลับเป็น JSON
        echo json_encode($children);
    } else {
        // หากไม่มีการส่งค่า ส่ง JSON เปล่า
        echo json_encode([]); // ส่ง JSON ว่างๆ
    }
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ส่งข้อความกลับในรูปแบบ JSON
    echo json_encode(['error' => $e->getMessage()]);
}
?>
