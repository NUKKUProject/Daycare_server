<?php
// ครู/แอดมินจัดการคำขอลา: list / decide (อนุมัติ หรือ ไม่อนุมัติ)
// ครูเห็นและตัดสินได้เฉพาะเด็กในกลุ่ม/ห้องที่ดูแล แอดมินเห็นทั้งหมด
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../function/dev_screening_functions.php';
require_once __DIR__ . '/../function/leave_functions.php';
header('Content-Type: application/json');
checkUserRole(['admin', 'teacher']);
date_default_timezone_set('Asia/Bangkok');

function manageFail(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $isTeacher = getUserRole() === 'teacher';
    $input = $_SERVER['REQUEST_METHOD'] === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];
    $action = $_GET['action'] ?? ($input['action'] ?? 'list');

    if ($action === 'list') {
        $status = $_GET['status'] ?? 'pending';
        $from = $_GET['date_from'] ?? '';
        $to = $_GET['date_to'] ?? '';
        $group = $_GET['child_group'] ?? '';
        $room = $_GET['classroom'] ?? '';
        $search = trim($_GET['search'] ?? '');
        foreach ([$from, $to] as $d) {
            if ($d !== '' && !DateTime::createFromFormat('Y-m-d', $d)) {
                manageFail('รูปแบบวันที่ไม่ถูกต้อง');
            }
        }

        $sql = "SELECT l.id, l.student_id, l.leave_type, l.illness_name, l.start_date, l.end_date, l.reason, l.status,
                       l.created_at, l.created_by, l.decided_by, l.decided_at, l.decision_note,
                       c.prefix_th, c.firstname_th, c.lastname_th, c.nickname, c.child_group, c.classroom
                FROM leave_requests l JOIN children c ON c.studentid = l.student_id WHERE 1=1";
        $params = [];
        if ($isTeacher) {
            $sql .= devScreeningTeacherScopeSql();
            $params[':teacher_id'] = $_SESSION['user_id'];
        }
        if ($status !== 'all') {
            if (!isset(LEAVE_STATUS_TEXT[$status])) {
                manageFail('สถานะไม่ถูกต้อง');
            }
            $sql .= " AND l.status = :status";
            $params[':status'] = $status;
        }
        if ($from !== '') { $sql .= " AND l.end_date >= :from"; $params[':from'] = $from; }
        if ($to !== '') { $sql .= " AND l.start_date <= :to"; $params[':to'] = $to; }
        if ($group !== '') { $sql .= " AND c.child_group = :g"; $params[':g'] = $group; }
        if ($room !== '') { $sql .= " AND c.classroom = :r"; $params[':r'] = $room; }
        if ($search !== '') {
            $sql .= " AND (c.firstname_th ILIKE :q OR c.lastname_th ILIKE :q OR c.nickname ILIKE :q OR c.studentid ILIKE :q)";
            $params[':q'] = '%' . $search . '%';
        }
        // รออนุมัติ: วันที่ลาใกล้ที่สุดขึ้นก่อน  อื่นๆ: ล่าสุดก่อน
        $sql .= $status === 'pending' ? " ORDER BY l.start_date ASC, l.id ASC" : " ORDER BY l.start_date DESC, l.id DESC";
        $sql .= " LIMIT 500";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'today' => date('Y-m-d')]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manageFail('Method not allowed', 405);
    }

    if ($action === 'decide') {
        $id = (int)($input['id'] ?? 0);
        $decision = (string)($input['decision'] ?? '');
        if (!in_array($decision, ['approve', 'reject'], true)) {
            manageFail('คำสั่งไม่ถูกต้อง');
        }
        $note = trim((string)($input['note'] ?? ''));
        if ($decision === 'reject' && $note === '') {
            manageFail('กรุณาระบุเหตุผลที่ไม่อนุมัติ');
        }
        $note = $note !== '' ? mb_substr($note, 0, 300) : null;

        $sql = "SELECT l.* FROM leave_requests l JOIN children c ON c.studentid = l.student_id WHERE l.id = :id";
        $params = [':id' => $id];
        if ($isTeacher) {
            $sql .= devScreeningTeacherScopeSql();
            $params[':teacher_id'] = $_SESSION['user_id'];
        }
        $pdo->beginTransaction();
        $sel = $pdo->prepare($sql . " FOR UPDATE OF l");
        $sel->execute($params);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $pdo->rollBack();
            manageFail('ไม่พบคำขอลา หรือไม่มีสิทธิ์จัดการ', 404);
        }
        if ($row['status'] !== 'pending') {
            $pdo->rollBack();
            manageFail('คำขอนี้ถูกดำเนินการไปแล้ว (' . (LEAVE_STATUS_TEXT[$row['status']] ?? $row['status']) . ')');
        }

        $newStatus = $decision === 'approve' ? 'approved' : 'rejected';
        $by = function_exists('getFullName') ? getFullName() : ($_SESSION['username'] ?? '');
        $upd = $pdo->prepare("UPDATE leave_requests SET status = :st, decided_by = :by, decided_at = NOW(), decision_note = :note WHERE id = :id");
        $upd->execute([':st' => $newStatus, ':by' => $by, ':note' => $note, ':id' => $id]);

        // อนุมัติแล้ว: วันที่ลาที่ผ่านมา/วันนี้ ให้การเช็กชื่อเป็น "ลา" (ที่สแกนมาเรียนแล้วไม่ถูกทับ)
        $updated = $newStatus === 'approved' ? leaveApplyToAttendance($pdo, $row) : 0;
        $pdo->commit();

        echo json_encode(['status' => 'success', 'new_status' => $newStatus, 'attendance_updated' => $updated,
            'message' => $newStatus === 'approved' ? 'อนุมัติการลาแล้ว' : 'ไม่อนุมัติการลาแล้ว']);
        exit;
    }

    manageFail('คำสั่งไม่ถูกต้อง');
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่']);
    error_log('leave_manage: ' . $e->getMessage());
}
