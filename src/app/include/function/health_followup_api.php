<?php
// แจ้งกลับเรื่องสุขภาพที่ต้องติดตาม: รับทราบ / นัดหมอแล้ว / พาไปรักษาแล้ว (ทุกชนิดการตรวจ)
// ผู้ปกครอง (บัญชีนักเรียน) แจ้งได้เฉพาะลูกตัวเอง  ศูนย์ (admin / ครู / แพทย์) แจ้งแทนผู้ปกครองได้ทุกคน (เช่น ศูนย์พาไปรักษาเอง)
require_once __DIR__ . '/../auth/auth.php';
checkUserRole(['student', 'admin', 'teacher', 'doctor']);
require_once __DIR__ . '/health_followup_functions.php';

header('Content-Type: application/json; charset=utf-8');

function out(array $body, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out(['status' => 'error', 'message' => 'วิธีเรียกใช้ไม่ถูกต้อง'], 405);
    }
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $isParent = ($_SESSION['role'] ?? '') === 'student';
    $username = (string) ($_SESSION['username'] ?? '');
    $type = (string) ($in['type'] ?? 'dental');
    $id = (int) ($in['id'] ?? 0);
    $status = (string) ($in['status'] ?? '');
    if ($username === '' || !$id || !isset(hf_types()[$type]) || !in_array($status, HF_STATUSES, true)) {
        out(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
    }

    $date = null;
    if ($status !== 'acknowledged') {
        $d = DateTime::createFromFormat('Y-m-d', (string) ($in['date'] ?? ''));
        if (!$d) {
            out(['status' => 'error', 'message' => 'กรุณาระบุวันที่'], 400);
        }
        // วันที่พาไปรักษาแล้วต้องไม่เป็นอนาคต (วันนัดหมอเป็นอนาคตได้)
        if ($status === 'treated' && $d > new DateTime('today')) {
            out(['status' => 'error', 'message' => 'วันที่พาไปรักษาต้องไม่เป็นวันในอนาคต'], 400);
        }
        $date = $d->format('Y-m-d');
    }
    $note = mb_substr(trim((string) ($in['note'] ?? '')), 0, 300);

    $pdo = getDatabaseConnection();
    $studentId = hf_verify_source($pdo, $type, $id, $isParent ? $username : null);
    if ($studentId === null) {
        out(['status' => 'error', 'message' => 'ไม่พบรายการที่ต้องการ'], 404);
    }

    hf_save($pdo, $type, $id, $studentId, $status, $date, $note !== '' ? $note : null, $username, $isParent ? 'parent' : 'center');
    out(['status' => 'success', 'message' => $isParent ? 'แจ้งศูนย์เรียบร้อยแล้ว' : 'บันทึกการติดตามแล้ว']);
} catch (Exception $e) {
    error_log('health_followup_api: ' . $e->getMessage());
    out(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ'], 500);
}
