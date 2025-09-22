<?php
require_once 'child_functions.php';

// ตรวจสอบการส่งค่ามา
if (isset($_GET['teacher_id']) && isset($_GET['child_group'])) {
    $teacher_id = intval($_GET['teacher_id']);
    $child_group = filter_input(INPUT_GET, 'child_group', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // ใช้ FILTER_SANITIZE_FULL_SPECIAL_CHARS แทน

    error_log("Received teacher_id: $teacher_id, child_group: $child_group");

    // ดึงข้อมูลครู
    $teacher = getTeacherById($teacher_id);

    if (!$teacher) {
        sendResponse(['error' => 'Teacher not found'], 404); // ถ้าไม่พบครู
    }

    // ดึงข้อมูลห้องเรียน
    $classrooms = getClassroomsByTeacherAndGroup($teacher_id, $child_group);

    // ตรวจสอบห้องเรียน
    if (empty($classrooms)) {
        sendResponse(['error' => 'No classrooms found for this teacher and group'], 404);
    }

    // ส่งข้อมูลครูและห้องเรียนกลับในรูปแบบ JSON
    sendResponse([
        'teacher' => $teacher,
        'classrooms' => $classrooms
    ]);
} elseif (isset($_GET['classroom']) && isset($_GET['child_group'])) {
    $classroom = filter_input(INPUT_GET, 'classroom', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // ใช้ FILTER_SANITIZE_FULL_SPECIAL_CHARS แทน
    $child_group = filter_input(INPUT_GET, 'child_group', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // ใช้ FILTER_SANITIZE_FULL_SPECIAL_CHARS แทน

    error_log("Received classroom: $classroom, child_group: $child_group");

    // ดึงข้อมูลครูที่ดูแลห้องเรียน
    $teachers = getTeachersByClassroomAndGroup($classroom, $child_group);

    if (empty($teachers)) {
        sendResponse(['error' => 'No teachers found for this classroom and group'], 404);
    }

    sendResponse($teachers); // ส่งข้อมูลครูที่ดูแลห้องเรียน
} else {
    error_log("Missing or invalid parameters: " . json_encode($_GET));
    sendResponse(['error' => 'Invalid parameters'], 400);
}

// ฟังก์ชันส่งข้อมูล JSON Response
function sendResponse($data, $status = 200) {
    if (isset($data['error'])) {
        $response = [
            'teacher' => $data['teacher'] ?? null,
            'classrooms' => []
        ];
    } else {
        // ส่งข้อมูล classrooms โดยตรง ไม่ต้องซ้อนกัน
        $response = [
            'teacher' => $data['teacher'] ?? null,
            'classrooms' => $data['classrooms']['classrooms'] ?? []
        ];
    }

    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ฟังก์ชันดึงข้อมูลห้องเรียนที่ครูดูแล
function getClassroomsByTeacherAndGroup($teacher_id, $child_group) {
    try {
        $pdo = getDatabaseConnection();
        
        // แก้ไข SQL query ให้ดึงข้อมูลจากตาราง classrooms โดยตรง
        $sql = "
            SELECT DISTINCT c.classroom_id, c.classroom_name
            FROM classrooms c
            WHERE c.child_group = :child_group
            AND c.status = 'active'
            AND EXISTS (
                SELECT 1 
                FROM children ch
                JOIN teachers t ON ch.classroom = ANY(string_to_array(t.classroom_ids, ','))
                WHERE t.teacher_id = :teacher_id
                AND ch.classroom = c.classroom_name
            )
            ORDER BY c.classroom_name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->bindParam(':child_group', $child_group, PDO::PARAM_STR);
        $stmt->execute();

        $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($classrooms)) {
            // ถ้าไม่พบข้อมูล ให้ลองค้นหาจากตาราง children
            $sql = "
                SELECT DISTINCT c.classroom as classroom_name
                FROM children c
                JOIN teachers t ON (c.classroom = ANY(string_to_array(t.classroom_ids, ',')))
                WHERE t.teacher_id = :teacher_id 
                AND c.child_group = :child_group
                AND c.status = 'active'
                ORDER BY c.classroom
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->bindParam(':child_group', $child_group, PDO::PARAM_STR);
            $stmt->execute();
            
            $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!empty($classrooms)) {
            return ['classrooms' => $classrooms];
        }

        return ['classrooms' => []];

    } catch (PDOException $e) {
        error_log("Error in getClassroomsByTeacherAndGroup: " . $e->getMessage());
        return ['classrooms' => []]; // ส่งค่าว่างแทนที่จะส่ง error
    }
}
?>
