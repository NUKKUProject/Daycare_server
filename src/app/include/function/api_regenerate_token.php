<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/token_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$children_id = $input['children_id'] ?? null;
$reason = $input['reason'] ?? 'regenerate';
$expiryDays = isset($input['expiry_days']) ? (int)$input['expiry_days'] : TOKEN_EXPIRY_DAYS;

$validReasons = ['lost_card', 'regenerate', 'damaged'];
if (!in_array($reason, $validReasons)) {
    $reason = 'regenerate';
}

if (!$children_id || !is_numeric($children_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'children_id is required']);
    exit;
}

$pdo = getDatabaseConnection();

$oldToken = getActiveToken($pdo, $children_id);
$oldTokenStr = $oldToken['token'] ?? null;

try {
    $pdo->beginTransaction();
    revokeActiveTokens($pdo, $children_id, $reason);
    $result = createToken($pdo, $children_id, 'student_card', $expiryDays);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'token' => $result['token'],
        'old_token' => $oldTokenStr
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
