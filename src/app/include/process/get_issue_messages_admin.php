<?php
include __DIR__ . '/../auth/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['role'] ?? null) !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ใช้งาน'], JSON_UNESCAPED_UNICODE);
    exit;
}

function respond($success, $message, $data = null)
{
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

$issueId = (int)($_POST['issue_id'] ?? 0);
if (!$issueId) respond(false, 'ข้อมูลไม่ถูกต้อง');

require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    $check = $pdo->prepare("SELECT id, description, TO_CHAR(created_at,'DD/MM/YYYY HH24:MI') AS created_at FROM login_issues WHERE id = :id");
    $check->execute(['id' => $issueId]);
    $issue = $check->fetch(PDO::FETCH_ASSOC);
    if (!$issue) respond(false, 'ไม่พบรายการ');

    $stmt = $pdo->prepare(
        "SELECT sender_role, message, TO_CHAR(created_at,'DD/MM/YYYY HH24:MI') AS created_at
         FROM login_issue_messages WHERE issue_id = :id ORDER BY created_at ASC"
    );
    $stmt->execute(['id' => $issueId]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($msgs)) {
        $msgs = [['sender_role' => 'parent', 'message' => $issue['description'], 'created_at' => $issue['created_at']]];
    }

    respond(true, 'ok', $msgs);
} catch (PDOException $e) {
    error_log('get_issue_messages_admin failed: ' . $e->getMessage());
    respond(false, 'เกิดข้อผิดพลาดในระบบ');
}
