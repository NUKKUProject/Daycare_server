<?php
// แจ้งกลับเรื่องผลตรวจฟัน: รับทราบ / นัดหมอแล้ว / พาไปรักษาแล้ว
// ผู้ปกครอง (บัญชีนักเรียน) แจ้งได้เฉพาะลูกตัวเอง  ศูนย์ (admin / ครู / แพทย์) แจ้งแทนผู้ปกครองได้ทุกคน (เช่น ศูนย์พาไปรักษาเอง)
require_once __DIR__ . '/../auth/auth.php';
checkUserRole(['student', 'admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../config/database.php';

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
    $role = (string) ($_SESSION['role'] ?? '');
    $isParent = $role === 'student';
    $username = (string) ($_SESSION['username'] ?? '');
    $id = (int) ($in['id'] ?? 0);
    $status = (string) ($in['status'] ?? '');
    if ($username === '' || !$id || !in_array($status, ['acknowledged', 'scheduled', 'treated'], true)) {
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
    // ผู้ปกครองแก้ได้เฉพาะผลตรวจของแพทย์ของลูกตัวเอง ศูนย์แก้ได้ทุกคน (เฉพาะผลที่แพทย์ตรวจ)
    $sql = "SELECT id FROM health_tooth_external WHERE id = :id AND exam_type = 'doctor'" . ($isParent ? ' AND student_id = :s' : '');
    $own = $pdo->prepare($sql);
    $own->execute($isParent ? [':id' => $id, ':s' => $username] : [':id' => $id]);
    if (!$own->fetchColumn()) {
        out(['status' => 'error', 'message' => 'ไม่พบผลตรวจที่ต้องการ'], 404);
    }

    $upd = $pdo->prepare("UPDATE health_tooth_external SET
            followup_status = :st, followup_date = :d, followup_note = :n,
            parent_ack_at = COALESCE(parent_ack_at, NOW()), followup_updated_at = NOW(), followup_by = :by, followup_by_role = :role
        WHERE id = :id");
    $upd->execute([':st' => $status, ':d' => $date, ':n' => $note !== '' ? $note : null, ':by' => $username, ':role' => $isParent ? 'parent' : 'center', ':id' => $id]);

    out(['status' => 'success', 'message' => 'แจ้งศูนย์เรียบร้อยแล้ว']);
} catch (Exception $e) {
    error_log('tooth_followup_api: ' . $e->getMessage());
    out(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ'], 500);
}
