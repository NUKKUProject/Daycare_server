<?php
// รายชื่อเด็กในห้อง พร้อมผลคัดกรองพัฒนาการของวันที่ประเมินที่เลือก (ครู/แอดมินเท่านั้น)
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/dev_screening_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher']);

try {
    $pdo = getDatabaseConnection();
    $childGroup = $_GET['child_group'] ?? '';
    $classroom = $_GET['classroom'] ?? '';
    $date = $_GET['date'] ?? date('Y-m-d');

    if ($childGroup === '' || $classroom === '') {
        throw new Exception('กรุณาเลือกกลุ่มเรียนและห้องเรียน');
    }
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        throw new Exception('วันที่ประเมินไม่ถูกต้อง');
    }

    $sql = "SELECT c.studentid, c.prefix_th, c.firstname_th, c.lastname_th, c.nickname,
                   c.child_group, c.classroom, c.birthday,
                   d.gm_status, d.gm_note, d.fm_status, d.fm_note, d.rl_status, d.rl_note,
                   d.el_status, d.el_note, d.ps_status, d.ps_note
            FROM children c
            LEFT JOIN (
                SELECT DISTINCT ON (student_id) *
                FROM growth_records
                WHERE DATE(created_at) = :date AND is_draft = false
                ORDER BY student_id, id DESC
            ) d ON d.student_id = c.studentid
            WHERE c.status = 'กำลังศึกษา'";
    $params = [':date' => $date];

    $sql .= " AND c.child_group = :child_group AND c.classroom = :classroom";
    $params[':child_group'] = $childGroup;
    $params[':classroom'] = $classroom;
    if (getUserRole() === 'teacher') {
        $sql .= devScreeningTeacherScopeSql();
        $params[':teacher_id'] = $_SESSION['user_id'];
    }
    $sql .= " ORDER BY c.classroom, c.firstname_th, c.lastname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['status' => 'success', 'students' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
