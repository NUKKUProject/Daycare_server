<?php
// endpoint สาธารณะ — ดูสถานะเรื่องที่แจ้งตามรหัสนักเรียน
header('Content-Type: application/json; charset=utf-8');

function respond($success, $message, $data = null)
{
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'การร้องขอไม่ถูกต้อง');
}

$studentId = trim($_POST['student_id'] ?? '');
if ($studentId === '') {
    respond(false, 'กรุณากรอกรหัสประจำตัวผู้เรียน');
}
if (mb_strlen($studentId) > 50) {
    respond(false, 'ข้อมูลยาวเกินกำหนด');
}

require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();

    // rate limit: ไม่เกิน 10 ครั้งต่อ IP ต่อชั่วโมง
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_issue_lookups WHERE ip_address = :ip AND created_at > NOW() - INTERVAL '1 hour'");
    $stmt->execute(['ip' => $ip]);
    if ((int)$stmt->fetchColumn() >= 10) {
        respond(false, 'ค้นหาบ่อยเกินไป กรุณาลองใหม่ภายหลัง');
    }

    // บันทึก log การค้นหา
    $pdo->prepare("INSERT INTO login_issue_lookups (ip_address) VALUES (:ip)")->execute(['ip' => $ip]);

    $stmt = $pdo->prepare(
        "SELECT id, student_name, description, status, TO_CHAR(created_at, 'DD/MM/YYYY HH24:MI') AS created_at
         FROM login_issues
         WHERE student_id = :student_id
         ORDER BY created_at DESC
         LIMIT 10"
    );
    $stmt->execute(['student_id' => $studentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(true, 'ok', $rows);
} catch (PDOException $e) {
    error_log('get_login_issues failed: ' . $e->getMessage());
    respond(false, 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่ภายหลัง');
}
