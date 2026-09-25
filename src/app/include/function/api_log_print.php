<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/template_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$printed_by = $input['printed_by'] ?? $_SESSION['username'] ?? 'unknown';
$template_id = isset($input['template_id']) ? (int)$input['template_id'] : null;
$children_ids = $input['children_ids'] ?? [];
$print_type = $input['print_type'] ?? 'browser';
$total_cards = $input['total_cards'] ?? count($children_ids);

if (empty($children_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'children_ids is required']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    logPrint($pdo, $printed_by, $template_id, $children_ids, $print_type, (int)$total_cards);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
