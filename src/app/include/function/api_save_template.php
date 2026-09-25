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

$id = isset($input['id']) && $input['id'] ? (int)$input['id'] : null;
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$header_color = trim($input['header_color'] ?? '#1E3A8A');
$layout_config = $input['layout_config'] ?? [];

if (!$name) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'name is required']);
    exit;
}

try {
    $saved_id = saveTemplate($id, $name, $description, $header_color, $layout_config);
    echo json_encode(['success' => true, 'id' => $saved_id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
