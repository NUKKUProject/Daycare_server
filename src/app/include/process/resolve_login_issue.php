<?php
include __DIR__ . '/../auth/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['role'] ?? null) !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ใช้งาน']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("UPDATE login_issues SET status = 'resolved', resolved_at = NOW() WHERE id = :id");
    $stmt->execute(['id' => $id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    error_log('resolve_login_issue failed: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในระบบ']);
}
