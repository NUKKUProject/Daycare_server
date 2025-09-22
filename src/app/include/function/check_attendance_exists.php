<?php
require_once '../connect.php';

$student_id = $_GET['student_id'];
$date = $_GET['date'];

try {
    $sql = "SELECT id FROM attendance WHERE student_id = ? AND DATE(check_date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id, $date]);
    
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'exists' => $exists !== false,
        'existing_id' => $exists ? $exists['id'] : null
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'exists' => false,
        'error' => $e->getMessage()
    ]);
} 