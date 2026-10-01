<?php
// endpoint สาธารณะ — เพิ่มรายละเอียดให้เรื่องที่แจ้งไปแล้ว
header('Content-Type: application/json; charset=utf-8');

function respond($success, $message)
{
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'การร้องขอไม่ถูกต้อง');
}

$issueId   = (int)($_POST['issue_id']   ?? 0);
$studentId = trim($_POST['student_id']  ?? '');
$appendText = trim($_POST['append_text'] ?? '');

if (!$issueId || $studentId === '' || $appendText === '') {
    respond(false, 'ข้อมูลไม่ครบถ้วน');
}
if (mb_strlen($appendText) > 500) {
    respond(false, 'ข้อความยาวเกิน 500 ตัวอักษร');
}

require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // rate limit: ไม่เกิน 5 ครั้งต่อ IP ต่อชั่วโมง
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_issue_lookups WHERE ip_address = :ip AND created_at > NOW() - INTERVAL '1 hour'");
    $stmt->execute(['ip' => $ip]);
    if ((int)$stmt->fetchColumn() >= 5) {
        respond(false, 'ดำเนินการบ่อยเกินไป กรุณาลองใหม่ภายหลัง');
    }
    $pdo->prepare("INSERT INTO login_issue_lookups (ip_address) VALUES (:ip)")->execute(['ip' => $ip]);

    // ตรวจว่า issue นี้เป็นของ student_id นี้จริง และยังไม่ resolved
    $check = $pdo->prepare("SELECT id, status FROM login_issues WHERE id = :id AND student_id = :student_id");
    $check->execute(['id' => $issueId, 'student_id' => $studentId]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        respond(false, 'ไม่พบรายการ หรือรหัสนักเรียนไม่ตรงกัน');
    }
    if ($row['status'] === 'resolved') {
        respond(false, 'เรื่องนี้ได้รับการแก้ไขแล้ว ไม่สามารถเพิ่มข้อมูลได้');
    }

    // ต่อท้าย description เดิมด้วยเวลาและข้อความใหม่
    $pdo->prepare(
        "UPDATE login_issues
         SET description = description || E'\n\n[เพิ่มเติม " . date('d/m/Y H:i') . "]\n' || :text
         WHERE id = :id"
    )->execute(['text' => $appendText, 'id' => $issueId]);

    respond(true, 'เพิ่มรายละเอียดเรียบร้อยแล้ว');
} catch (PDOException $e) {
    error_log('append_login_issue failed: ' . $e->getMessage());
    respond(false, 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่ภายหลัง');
}
