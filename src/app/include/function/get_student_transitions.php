<?php
require_once(__DIR__ . '/../../../config/database.php');

function getStudentTransitions($filters = []) {
    try {
        $pdo = getDatabaseConnection();
        
        // รับค่า filters จาก POST request หรือ parameter
        $child_group = $_POST['child_group'] ?? $filters['child_group'] ?? null;
        $classroom = $_POST['classroom'] ?? $filters['classroom'] ?? null;
        
        error_log("Filters received - Group: $child_group, Classroom: $classroom");

        // Base query
        $query = "
            SELECT 
                c.studentid as student_id,
                c.prefix_th,
                c.firstname_th,
                c.lastname_th,
                c.nickname,
                c.child_group,
                c.classroom,
                c.status as student_status
            FROM children c
            WHERE c.status = 'กำลังศึกษา'
        ";
        
        $params = [];

        if ($child_group) {
            $query .= " AND c.child_group = :child_group";
            $params['child_group'] = $child_group;
        }

        if ($classroom) {
            $query .= " AND c.classroom = :classroom";
            $params['classroom'] = $classroom;
        }

        $query .= " ORDER BY c.child_group, c.classroom, c.firstname_th";
        
        error_log("Query: " . $query);
        error_log("Params: " . print_r($params, true));

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Found " . count($results) . " results");

        return [
            'success' => true,
            'data' => $results,
            'recordsTotal' => count($results),
            'recordsFiltered' => count($results)
        ];

    } catch (PDOException $e) {
        error_log("Error in getStudentTransitions: " . $e->getMessage());
        return [
            'success' => false,
            'error' => true,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลการเลื่อนชั้นรายบุคคล
function getStudentTransitionById($id) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT *
            FROM student_transitions
            WHERE id = :id
        ");
        
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error in getStudentTransitionById: " . $e->getMessage());
        return null;
    }
}
