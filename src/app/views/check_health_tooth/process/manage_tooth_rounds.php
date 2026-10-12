<?php
// จัดการรอบตรวจสุขภาพช่องปาก: list (ทุกบทบาท) / create / close / reopen (เฉพาะ admin)
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../function/tooth_exam_helpers.php';

header('Content-Type: application/json; charset=utf-8');

function out(array $body, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $input = $_SERVER['REQUEST_METHOD'] === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];
    $action = $_GET['action'] ?? $input['action'] ?? 'list';
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

    if ($action === 'list') {
        $year = trim($_GET['academic_year'] ?? '');
        $sql = "SELECT r.*,
                   COUNT(DISTINCT h.student_id) FILTER (WHERE h.exam_type = 'teacher') AS teacher_count,
                   COUNT(DISTINCT h.student_id) FILTER (WHERE h.exam_type = 'doctor')  AS doctor_count,
                   COUNT(DISTINCT h.student_id) AS child_count
                FROM tooth_exam_rounds r
                LEFT JOIN health_tooth_external h ON h.round_id = r.id";
        $params = [];
        if ($year !== '') {
            $sql .= ' WHERE r.academic_year = :y';
            $params[':y'] = $year;
        }
        $sql .= ' GROUP BY r.id ORDER BY r.academic_year DESC, r.round_no DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        out(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'is_admin' => $isAdmin]);
    }

    if (!$isAdmin) {
        out(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่จัดการรอบตรวจได้'], 403);
    }

    if ($action === 'create') {
        $year = trim((string) ($input['academic_year'] ?? ''));
        if ($year === '' || !ctype_digit($year)) {
            out(['status' => 'error', 'message' => 'กรุณาระบุปีการศึกษา'], 400);
        }
        $next = $pdo->prepare('SELECT COALESCE(MAX(round_no), 0) + 1 FROM tooth_exam_rounds WHERE academic_year = :y');
        $next->execute([':y' => $year]);
        $no = (int) $next->fetchColumn();
        $title = trim((string) ($input['title'] ?? '')) ?: "ครั้งที่ {$no}";
        $date = fn($v) => (!empty($v) && DateTime::createFromFormat('Y-m-d', $v)) ? $v : null;

        $stmt = $pdo->prepare('INSERT INTO tooth_exam_rounds (academic_year, round_no, title, start_date, end_date, status, created_by)
                               VALUES (:y, :no, :t, :s, :e, \'open\', :by)');
        $stmt->execute([
            ':y' => $year, ':no' => $no, ':t' => mb_substr($title, 0, 150),
            ':s' => $date($input['start_date'] ?? null), ':e' => $date($input['end_date'] ?? null),
            ':by' => tooth_current_user_label(),
        ]);
        out(['status' => 'success', 'message' => "เปิดรอบ \"{$title}\" แล้ว"]);
    }

    if ($action === 'close' || $action === 'reopen') {
        $id = (int) ($input['id'] ?? 0);
        $round = $id ? tooth_get_round($pdo, $id) : null;
        if (!$round) {
            out(['status' => 'error', 'message' => 'ไม่พบรอบตรวจ'], 404);
        }
        if ($action === 'close') {
            $pdo->prepare("UPDATE tooth_exam_rounds SET status = 'closed', closed_at = NOW() WHERE id = :id")->execute([':id' => $id]);
            out(['status' => 'success', 'message' => 'ปิดรอบแล้ว ผลตรวจของรอบนี้ถูกล็อก']);
        }
        $pdo->prepare("UPDATE tooth_exam_rounds SET status = 'open', closed_at = NULL WHERE id = :id")->execute([':id' => $id]);
        out(['status' => 'success', 'message' => 'เปิดรอบอีกครั้งแล้ว']);
    }

    // แก้ไขชื่อรอบ / ช่วงวันที่ (ปีการศึกษาและลำดับรอบไม่เปลี่ยน เพราะผูกกับข้อมูลที่บันทึก)
    if ($action === 'update') {
        $id = (int) ($input['id'] ?? 0);
        if (!$id || !tooth_get_round($pdo, $id)) {
            out(['status' => 'error', 'message' => 'ไม่พบรอบตรวจ'], 404);
        }
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            out(['status' => 'error', 'message' => 'กรุณาระบุชื่อรอบ'], 400);
        }
        $date = fn($v) => (!empty($v) && DateTime::createFromFormat('Y-m-d', $v)) ? $v : null;
        $s = $date($input['start_date'] ?? null);
        $e = $date($input['end_date'] ?? null);
        if ($s && $e && $e < $s) {
            out(['status' => 'error', 'message' => 'วันสิ้นสุดต้องไม่ก่อนวันเริ่ม'], 400);
        }
        $pdo->prepare('UPDATE tooth_exam_rounds SET title = :t, start_date = :s, end_date = :e WHERE id = :id')
            ->execute([':t' => mb_substr($title, 0, 150), ':s' => $s, ':e' => $e, ':id' => $id]);
        out(['status' => 'success', 'message' => 'บันทึกการแก้ไขรอบแล้ว']);
    }

    // ลบรอบ: ลบได้เฉพาะรอบที่ยังไม่มีข้อมูลการตรวจ
    if ($action === 'delete') {
        $id = (int) ($input['id'] ?? 0);
        if (!$id || !tooth_get_round($pdo, $id)) {
            out(['status' => 'error', 'message' => 'ไม่พบรอบตรวจ'], 404);
        }
        $cnt = $pdo->prepare('SELECT COUNT(*) FROM health_tooth_external WHERE round_id = :id');
        $cnt->execute([':id' => $id]);
        if ((int) $cnt->fetchColumn() > 0) {
            out(['status' => 'error', 'message' => 'ลบไม่ได้ เพราะรอบนี้มีข้อมูลการตรวจแล้ว'], 409);
        }
        $pdo->prepare('DELETE FROM tooth_exam_rounds WHERE id = :id')->execute([':id' => $id]);
        out(['status' => 'success', 'message' => 'ลบรอบแล้ว']);
    }

    out(['status' => 'error', 'message' => 'คำสั่งไม่ถูกต้อง'], 400);
} catch (Exception $e) {
    error_log('manage_tooth_rounds: ' . $e->getMessage());
    out(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
}
