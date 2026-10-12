<?php
// ประวัติการคัดกรองพัฒนาการของเด็ก 1 คน (ผู้ปกครองดูได้เฉพาะลูกตัวเอง แก้ไขไม่ได้)
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/dev_screening_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher', 'student']);

try {
    $studentId = $_GET['student_id'] ?? '';
    if ($studentId === '') {
        throw new Exception('Missing student_id parameter');
    }
    if (getUserRole() === 'student' && $studentId !== ($_SESSION['username'] ?? null)) {
        http_response_code(403);
        throw new Exception('ไม่มีสิทธิ์ดูข้อมูลนี้');
    }

    $pdo = getDatabaseConnection();
    if (getUserRole() === 'teacher') {
        $chk = $pdo->prepare("SELECT 1 FROM children c WHERE c.studentid = :sid" . devScreeningTeacherScopeSql());
        $chk->execute([':sid' => $studentId, ':teacher_id' => $_SESSION['user_id']]);
        if (!$chk->fetch()) {
            http_response_code(403);
            throw new Exception('ไม่มีสิทธิ์ดูข้อมูลนี้');
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM development_screenings WHERE student_id = :sid ORDER BY assess_date DESC");
    $stmt->execute([':sid' => $studentId]);
    echo json_encode(['status' => 'success', 'records' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
