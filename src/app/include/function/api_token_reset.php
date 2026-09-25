<?php
/**
 * Token Reset Script
 * Generates brand-new tokens for all active students who lack one.
 * Usage: php api_token_reset.php
 * Safe to run multiple times (only fills gaps, does not revoke existing tokens).
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/token_helper.php';

$pdo = getDatabaseConnection();

$students = $pdo->query("SELECT id, CONCAT(firstname_th, ' ', lastname_th) AS fullname FROM children WHERE status = 'active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
if (empty($students)) {
    echo "No active students found.\n";
    exit(0);
}

$count = 0;
foreach ($students as $s) {
    $existing = $pdo->prepare("SELECT COUNT(*) FROM student_qr_tokens WHERE children_id = :id AND is_active = TRUE AND (expires_at IS NULL OR expires_at > NOW())");
    $existing->execute(['id' => $s['id']]);
    if ($existing->fetchColumn() > 0) {
        echo "SKIP id={$s['id']} {$s['fullname']} – already has active token\n";
        continue;
    }

    $token = generateToken();
    $stmt = $pdo->prepare("INSERT INTO student_qr_tokens (children_id, token, is_active, created_at, expires_at) VALUES (:cid, :token, TRUE, NOW(), NOW() + INTERVAL '3 years')");
    $stmt->execute(['cid' => $s['id'], 'token' => $token]);
    echo "CREATED id={$s['id']} {$s['fullname']} token=" . substr($token, 0, 16) . "...\n";
    $count++;
}

echo "\nDone. Created $count new token(s).\n";
