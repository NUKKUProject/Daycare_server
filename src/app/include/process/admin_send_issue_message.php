<?php
include __DIR__ . '/../auth/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['role'] ?? null) !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ใช้งาน'], JSON_UNESCAPED_UNICODE);
    exit;
}

function respond($success, $message)
{
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$issueId = (int)($_POST['issue_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$issueId || $message === '') respond(false, 'ข้อมูลไม่ครบถ้วน');
if (mb_strlen($message) > 1000) respond(false, 'ข้อความยาวเกิน 1000 ตัวอักษร');

require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    $check = $pdo->prepare("SELECT status FROM login_issues WHERE id = :id");
    $check->execute(['id' => $issueId]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(false, 'ไม่พบรายการ');
    if (!in_array($row['status'], ['pending', 'open'])) respond(false, 'เรื่องนี้ปิดแล้ว');

    $pdo->prepare(
        "INSERT INTO login_issue_messages (issue_id, sender_role, message) VALUES (:id, 'admin', :msg)"
    )->execute(['id' => $issueId, 'msg' => $message]);

    respond(true, 'ส่งข้อความเรียบร้อย');
} catch (PDOException $e) {
    error_log('admin_send_issue_message failed: ' . $e->getMessage());
    respond(false, 'เกิดข้อผิดพลาดในระบบ');
}
