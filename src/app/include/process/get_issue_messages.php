<?php
header('Content-Type: application/json; charset=utf-8');

function respond($success, $message, $data = null)
{
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'การร้องขอไม่ถูกต้อง');

$issueId   = (int)($_POST['issue_id']  ?? 0);
$studentId = trim($_POST['student_id'] ?? '');
if (!$issueId || $studentId === '') respond(false, 'ข้อมูลไม่ครบถ้วน');

require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // ตรวจสิทธิ์: issue นี้ต้องเป็นของ student_id นี้
    $check = $pdo->prepare("SELECT id, description, TO_CHAR(created_at,'DD/MM/YYYY HH24:MI') AS created_at FROM login_issues WHERE id = :id AND student_id = :sid");
    $check->execute(['id' => $issueId, 'sid' => $studentId]);
    $issue = $check->fetch(PDO::FETCH_ASSOC);
    if (!$issue) respond(false, 'ไม่พบรายการ หรือรหัสนักเรียนไม่ตรงกัน');

    // ดึงข้อความจาก login_issue_messages
    $stmt = $pdo->prepare(
        "SELECT sender_role, message, TO_CHAR(created_at,'DD/MM/YYYY HH24:MI') AS created_at
         FROM login_issue_messages
         WHERE issue_id = :id
         ORDER BY created_at ASC"
    );
    $stmt->execute(['id' => $issueId]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // description เดิมเป็นข้อความแรกเสมอ
    array_unshift($msgs, [
        'sender_role' => 'parent',
        'message'     => $issue['description'],
        'created_at'  => $issue['created_at'],
    ]);

    respond(true, 'ok', $msgs);
} catch (PDOException $e) {
    error_log('get_issue_messages failed: ' . $e->getMessage());
    respond(false, 'เกิดข้อผิดพลาดในระบบ');
}
