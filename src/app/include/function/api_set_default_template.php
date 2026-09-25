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
$id = isset($input['id']) ? (int)$input['id'] : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id is required']);
    exit;
}

try {
    setDefaultTemplate($id);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
