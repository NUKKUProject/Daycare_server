<?php
// การลาของเด็ก (ผู้ปกครองเท่านั้น): list / create / cancel  ผู้ปกครองทำได้เฉพาะรายการของลูกตัวเอง
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../function/leave_functions.php';
header('Content-Type: application/json');
checkUserRole(['student']);
date_default_timezone_set('Asia/Bangkok');

function leaveFail(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function leaveValidDate(?string $d): ?DateTime
{
    $dt = $d ? DateTime::createFromFormat('Y-m-d', $d) : false;
    return ($dt && $dt->format('Y-m-d') === $d) ? $dt : null;
}

try {
    $pdo = getDatabaseConnection();
    $studentId = (string)($_SESSION['username'] ?? '');
    $chk = $pdo->prepare("SELECT 1 FROM children WHERE studentid = :sid");
    $chk->execute([':sid' => $studentId]);
    if (!$studentId || !$chk->fetch()) {
        leaveFail('ไม่พบข้อมูลเด็กของบัญชีนี้', 403);
    }

    $input = $_SERVER['REQUEST_METHOD'] === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];
    $action = $_GET['action'] ?? ($input['action'] ?? 'list');

    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT id, leave_type, illness, illness_name, start_date, end_date, reason, status, created_at, decided_by, decision_note
                               FROM leave_requests WHERE student_id = :sid
                               ORDER BY start_date DESC, id DESC LIMIT 200");
        $stmt->execute([':sid' => $studentId]);
        echo json_encode(['status' => 'success', 'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'today' => date('Y-m-d')]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        leaveFail('Method not allowed', 405);
    }

    if ($action === 'create') {
        $type = (string)($input['leave_type'] ?? '');
        if (!isset(LEAVE_TYPES[$type])) {
            leaveFail('กรุณาเลือกประเภทการลา');
        }

        $start = leaveValidDate($input['start_date'] ?? null);
        $end = leaveValidDate($input['end_date'] ?? null);
        if (!$start || !$end) {
            leaveFail('กรุณาระบุวันที่ลาให้ครบ');
        }
        if ($end < $start) {
            leaveFail('วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มลา');
        }
        $today = new DateTime('today');
        if ($start < (clone $today)->modify('-' . LEAVE_BACKDATE_DAYS . ' days')) {
            leaveFail('แจ้งลาย้อนหลังได้ไม่เกิน ' . LEAVE_BACKDATE_DAYS . ' วัน');
        }
        if ($start->diff($end)->days + 1 > LEAVE_MAX_DAYS) {
            leaveFail('ลาได้ครั้งละไม่เกิน ' . LEAVE_MAX_DAYS . ' วัน');
        }

        $illness = null;
        $illnessName = null;
        if ($type === 'sick') {
            $illness = (string)($input['illness'] ?? '');
            if (isset(LEAVE_COMMON_DISEASES[$illness])) {
                $illnessName = LEAVE_COMMON_DISEASES[$illness];
            } elseif ($illness === 'other') {
                $illnessName = trim((string)($input['illness_other'] ?? ''));
                if ($illnessName === '') {
                    leaveFail('กรุณาระบุโรคที่ป่วย');
                }
                $illnessName = mb_substr($illnessName, 0, 150);
            } else {
                leaveFail('กรุณาเลือกโรคที่ป่วย');
            }
        }

        $reason = trim((string)($input['reason'] ?? ''));
        if ($type === 'personal' && $reason === '') {
            leaveFail('กรุณาระบุเหตุผลการลากิจ');
        }
        $reason = $reason !== '' ? mb_substr($reason, 0, 500) : null;

        // ห้ามมีรายการลาที่รออนุมัติหรืออนุมัติแล้วทับซ้อนวันกัน
        $dup = $pdo->prepare("SELECT 1 FROM leave_requests
                              WHERE student_id = :sid AND status IN ('pending', 'approved')
                                AND start_date <= :e AND end_date >= :s LIMIT 1");
        $dup->execute([':sid' => $studentId, ':s' => $start->format('Y-m-d'), ':e' => $end->format('Y-m-d')]);
        if ($dup->fetch()) {
            leaveFail('มีรายการลาที่ทับซ้อนกับช่วงวันนี้อยู่แล้ว');
        }

        $ins = $pdo->prepare("INSERT INTO leave_requests
            (student_id, leave_type, illness, illness_name, start_date, end_date, reason, created_by)
            VALUES (:sid, :t, :ill, :iname, :s, :e, :r, :by) RETURNING id");
        $ins->execute([
            ':sid' => $studentId, ':t' => $type, ':ill' => $illness, ':iname' => $illnessName,
            ':s' => $start->format('Y-m-d'), ':e' => $end->format('Y-m-d'), ':r' => $reason,
            ':by' => function_exists('getFullName') ? getFullName() : $studentId,
        ]);
        echo json_encode(['status' => 'success', 'id' => $ins->fetchColumn(), 'message' => 'ส่งแจ้งลาเรียบร้อย รอครูอนุมัติ']);
        exit;
    }

    if ($action === 'cancel') {
        $id = (int)($input['id'] ?? 0);
        $upd = $pdo->prepare("UPDATE leave_requests SET status = 'cancelled', cancelled_at = NOW()
                              WHERE id = :id AND student_id = :sid
                                AND ((status = 'pending' AND end_date >= CURRENT_DATE) OR (status = 'approved' AND start_date > CURRENT_DATE))");
        $upd->execute([':id' => $id, ':sid' => $studentId]);
        if (!$upd->rowCount()) {
            leaveFail('ยกเลิกไม่ได้ (รายการไม่พบ ถูกยกเลิกหรือไม่อนุมัติไปแล้ว หรือที่อนุมัติแล้วและเริ่มลาแล้ว)');
        }
        echo json_encode(['status' => 'success', 'message' => 'ยกเลิกการลาแล้ว']);
        exit;
    }

    leaveFail('คำสั่งไม่ถูกต้อง');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่']);
    error_log('leave_request: ' . $e->getMessage());
}
