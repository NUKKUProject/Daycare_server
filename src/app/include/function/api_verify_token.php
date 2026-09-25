<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'reason' => 'method_not_allowed']);
    exit;
}

$token = $_GET['token'] ?? null;

if (!$token) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'reason' => 'token_required']);
    exit;
}

$pdo = getDatabaseConnection();

$stmt = $pdo->prepare("
    SELECT t.token, t.is_active, t.expires_at, t.revoked_at, t.revoked_reason,
           c.id as children_id, c.prefix_th, c.firstname_th, c.lastname_th,
           c.nickname, c.classroom, c.child_group, c.studentid
    FROM student_qr_tokens t
    JOIN children c ON c.id = t.children_id
    WHERE t.token = :token
    LIMIT 1
");
$stmt->execute(['token' => $token]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['valid' => false, 'reason' => 'not_found']);
    exit;
}

if (!$row['is_active']) {
    echo json_encode([
        'valid' => false,
        'reason' => 'revoked',
        'revoked_at' => $row['revoked_at'],
        'revoked_reason' => $row['revoked_reason']
    ]);
    exit;
}

if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
    echo json_encode([
        'valid' => false,
        'reason' => 'expired',
        'expires_at' => $row['expires_at']
    ]);
    exit;
}

echo json_encode([
    'valid' => true,
    'student' => [
        'id' => $row['children_id'],
        'studentid' => $row['studentid'],
        'name' => $row['prefix_th'] . $row['firstname_th'] . ' ' . $row['lastname_th'],
        'nickname' => $row['nickname'],
        'classroom' => $row['classroom'],
        'child_group' => $row['child_group']
    ]
]);
