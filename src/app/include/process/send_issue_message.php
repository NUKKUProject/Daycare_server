<?php
header('Content-Type: application/json; charset=utf-8');

function respond($success, $message)
{
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'การร้องขอไม่ถูกต้อง');

$issueId   = (int)($_POST['issue_id']  ?? 0);
$studentId = trim($_POST['student_id'] ?? '');
$message   = trim($_POST['message']    ?? '');

if (!$issueId || $studentId === '' || $message === '') respond(false, 'ข้อมูลไม่ครบถ้วน');
if (mb_strlen($message) > 500) respond(false, 'ข้อความยาวเกิน 500 ตัวอักษร');

require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // rate limit: ไม่เกิน 10 ข้อความต่อ IP ต่อชั่วโมง
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_issue_lookups WHERE ip_address = :ip AND created_at > NOW() - INTERVAL '1 hour'");
    $stmt->execute(['ip' => $ip]);
    if ((int)$stmt->fetchColumn() >= 10) respond(false, 'ส่งข้อความบ่อยเกินไป กรุณาลองใหม่ภายหลัง');
    $pdo->prepare("INSERT INTO login_issue_lookups (ip_address) VALUES (:ip)")->execute(['ip' => $ip]);

    // ตรวจสิทธิ์ + สถานะ
    $check = $pdo->prepare("SELECT status FROM login_issues WHERE id = :id AND student_id = :sid");
    $check->execute(['id' => $issueId, 'sid' => $studentId]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(false, 'ไม่พบรายการ หรือรหัสนักเรียนไม่ตรงกัน');
    if ($row['status'] === 'resolved') respond(false, 'เรื่องนี้ได้รับการแก้ไขแล้ว ไม่สามารถส่งข้อความได้');

    $pdo->prepare(
        "INSERT INTO login_issue_messages (issue_id, sender_role, message) VALUES (:id, 'parent', :msg)"
    )->execute(['id' => $issueId, 'msg' => $message]);

    respond(true, 'ส่งข้อความเรียบร้อย');
} catch (PDOException $e) {
    error_log('send_issue_message failed: ' . $e->getMessage());
    respond(false, 'เกิดข้อผิดพลาดในระบบ');
}
