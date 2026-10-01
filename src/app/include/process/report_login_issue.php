<?php
// endpoint สาธารณะ (ไม่ต้อง login) สำหรับแจ้งปัญหาการเข้าสู่ระบบ
header('Content-Type: application/json; charset=utf-8');

function respond($success, $message)
{
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'การร้องขอไม่ถูกต้อง');
}

// honeypot: บอทมักกรอกช่องนี้ — ตอบสำเร็จหลอกไว้
if (!empty($_POST['website'] ?? '')) {
    respond(true, 'ส่งเรื่องแจ้งปัญหาเรียบร้อยแล้ว');
}

$studentId  = trim($_POST['student_id']   ?? '');
$nationalId = trim($_POST['national_id']  ?? '');
$name       = trim($_POST['student_name'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '' || $description === '') {
    respond(false, 'กรุณากรอกข้อมูลให้ครบถ้วน');
}

if (
    mb_strlen($studentId) > 50 || mb_strlen($nationalId) > 13 ||
    mb_strlen($name) > 100 || mb_strlen($description) > 1000
) {
    respond(false, 'ข้อมูลยาวเกินกำหนด');
}

// ตรวจรูปแบบเลขบัตรประชาชน (ถ้ากรอก)
if ($nationalId !== '' && !preg_match('/^\d{13}$/', $nationalId)) {
    respond(false, 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก');
}

// กันการกรอกเลขบัตรประชาชน 13 หลักลงในรายละเอียด
if (preg_match('/\d{13}/', preg_replace('/[\s-]/', '', $description))) {
    respond(false, 'กรุณาอย่ากรอกรหัสผ่านหรือข้อมูลลับในช่องรายละเอียด');
}

require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    // rate limit: ไม่เกิน 3 เรื่องต่อ IP ต่อชั่วโมง
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_issues WHERE ip_address = :ip AND created_at > NOW() - INTERVAL '1 hour'");
    $stmt->execute(['ip' => $ip]);
    if ((int)$stmt->fetchColumn() >= 3) {
        respond(false, 'ส่งเรื่องบ่อยเกินไป กรุณาลองใหม่ภายหลัง');
    }

    $insert = $pdo->prepare(
        "INSERT INTO login_issues (student_id, national_id, student_name, description, ip_address)
         VALUES (:student_id, :national_id, :student_name, :description, :ip)"
    );
    $insert->execute([
        'student_id'  => $studentId  !== '' ? $studentId  : null,
        'national_id' => $nationalId !== '' ? $nationalId : null,
        'student_name' => $name,
        'description' => $description,
        'ip'          => $ip,
    ]);

    respond(true, 'ส่งเรื่องแจ้งปัญหาเรียบร้อยแล้ว ผู้ดูแลระบบจะตรวจสอบและแก้ไขให้');
} catch (PDOException $e) {
    error_log('report_login_issue failed: ' . $e->getMessage());
    respond(false, 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่ภายหลัง');
}
