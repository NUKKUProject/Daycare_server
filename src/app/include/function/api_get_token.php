<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/token_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$children_id = $_GET['children_id'] ?? null;

if (!$children_id || !is_numeric($children_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'children_id is required']);
    exit;
}

$pdo = getDatabaseConnection();

$active = getActiveToken($pdo, $children_id);
$history = getTokenHistory($pdo, $children_id);

echo json_encode([
    'success' => true,
    'token' => $active['token'] ?? null,
    'created_at' => $active['created_at'] ?? null,
    'expires_at' => $active['expires_at'] ?? null,
    'history' => $history
]);
