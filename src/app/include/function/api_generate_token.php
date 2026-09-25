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
$pdo = getDatabaseConnection();

$expiryDays = isset($input['expiry_days']) ? (int)$input['expiry_days'] : TOKEN_EXPIRY_DAYS;

// Bulk generation
if (isset($input['group']) || isset($input['only_missing'])) {
    $group = $input['group'] ?? null;
    $onlyMissing = $input['only_missing'] ?? true;
    $groupMap = ['big'=>'เด็กโต','medium'=>'เด็กกลาง','prep'=>'เตรียมอนุบาล'];
    $groupName = $group ? ($groupMap[$group] ?? $group) : null;

    $sql = "SELECT id FROM children WHERE status = 'กำลังศึกษา'";
    $params = [];
    if ($groupName) {
        $sql .= " AND child_group = :grp";
        $params['grp'] = $groupName;
    }
    if ($onlyMissing) {
        $sql .= " AND id NOT IN (SELECT children_id FROM student_qr_tokens WHERE is_active = TRUE AND (expires_at IS NULL OR expires_at > NOW()))";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $count = 0;
    foreach ($ids as $cid) {
        $existing = getActiveToken($pdo, $cid);
        if ($existing) continue;
        try {
            createToken($pdo, $cid, 'student_card', $expiryDays);
            $count++;
        } catch (Exception $e) {
            continue;
        }
    }

    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// Single generation
$children_id = $input['children_id'] ?? null;
if (!$children_id || !is_numeric($children_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'children_id is required']);
    exit;
}

$existing = getActiveToken($pdo, $children_id);
if ($existing) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'มี token ที่ใช้งานอยู่แล้ว']);
    exit;
}

try {
    $result = createToken($pdo, $children_id, 'student_card', $expiryDays);
    echo json_encode([
        'success' => true,
        'token' => $result['token'],
        'id' => $result['id']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
