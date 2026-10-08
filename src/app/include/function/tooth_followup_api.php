<?php
// ผู้ปกครอง (บัญชีนักเรียน) แจ้งกลับศูนย์เรื่องผลตรวจฟันของลูกตัวเอง: รับทราบ / นัดหมอแล้ว / พาไปรักษาแล้ว
require_once __DIR__ . '/../auth/auth.php';
checkUserRole(['student']);
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
    $studentId = (string) ($_SESSION['username'] ?? '');
    $id = (int) ($in['id'] ?? 0);
    $status = (string) ($in['status'] ?? '');
    if ($studentId === '' || !$id || !in_array($status, ['acknowledged', 'scheduled', 'treated'], true)) {
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
    // แก้ได้เฉพาะผลตรวจของแพทย์ของลูกตัวเองเท่านั้น
    $own = $pdo->prepare("SELECT id FROM health_tooth_external WHERE id = :id AND student_id = :s AND exam_type = 'doctor'");
    $own->execute([':id' => $id, ':s' => $studentId]);
    if (!$own->fetchColumn()) {
        out(['status' => 'error', 'message' => 'ไม่พบผลตรวจที่ต้องการ'], 404);
    }

    $upd = $pdo->prepare("UPDATE health_tooth_external SET
            followup_status = :st, followup_date = :d, followup_note = :n,
            parent_ack_at = COALESCE(parent_ack_at, NOW()), followup_updated_at = NOW(), followup_by = :by
        WHERE id = :id");
    $upd->execute([':st' => $status, ':d' => $date, ':n' => $note !== '' ? $note : null, ':by' => $studentId, ':id' => $id]);

    out(['status' => 'success', 'message' => 'แจ้งศูนย์เรียบร้อยแล้ว']);
} catch (Exception $e) {
    error_log('tooth_followup_api: ' . $e->getMessage());
    out(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ'], 500);
}
