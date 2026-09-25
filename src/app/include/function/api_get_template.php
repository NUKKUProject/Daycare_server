<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../function/template_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed', 405);
    }
    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('Missing template id', 400);
    }
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT * FROM card_templates WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        throw new Exception('Template not found', 404);
    }
    $template['layout_config'] = json_decode($template['layout_config'], true);
    echo json_encode($template, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
