<?php
require_once(__DIR__ . '../../../../../config/database.php');
require_once __DIR__ . '/health_round_helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();

    // รับค่าจาก POST
    $roundId = (int) ($_POST['round_id'] ?? 0);
    $doctor = $_POST['doctor'] ?? 'all';

    $cond = $roundId ? health_round_condition($pdo, $roundId) : null;
    if (!$cond) {
        echo json_encode(['count' => 0]);
        exit;
    }

    // กรองตามแพทย์ (ถ้าไม่ใช่ 'all')
    if ($doctor !== 'all' && !empty($doctor)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM health_data_external WHERE {$cond[0]} AND doctor_name = :doctor");
        $stmt->execute($cond[1] + [':doctor' => $doctor]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM health_data_external WHERE {$cond[0]} AND doctor_name IS NOT NULL AND doctor_name != ''");
        $stmt->execute($cond[1]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['count' => (int)$row['total']]);
} catch (PDOException $e) {
    error_log('Database error in get_count_health_export_pdf.php: ' . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log('Error in get_count_health_export_pdf.php: ' . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Server error']);
}
