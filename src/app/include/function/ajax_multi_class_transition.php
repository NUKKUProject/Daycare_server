<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$currentGroup = $data['current_group'];
$currentClassrooms = $data['current_classrooms']; // array
$currentAcademicYear = $data['current_academic_year'];
$newGroup = $data['new_group'];
$newClassroom = $data['new_classroom'];
$newAcademicYear = $data['new_academic_year'];
$effectiveDate = $data['effective_date'];
$reason = $data['reason'];

session_start();
$createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

try {
    $pdo = getDatabaseConnection();
    // 1. ค้นหาเด็กที่ตรงกับ group, classrooms, academicYear
    $placeholders = implode(',', array_fill(0, count($currentClassrooms), '?'));
    $params = array_merge([$currentGroup, $currentAcademicYear], $currentClassrooms);
    $sql = "SELECT id, classroom FROM children WHERE child_group=? AND academic_year=? AND classroom IN ($placeholders) AND status='กำลังศึกษา'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. บันทึกการเลื่อนชั้นใหม่
    $insert = $pdo->prepare('INSERT INTO student_transitions (child_id, current_class_level, current_classroom, new_class_level, new_classroom, academic_year, new_academic_year, effective_date, reason, status, transition_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($children as $child) {
        $insert->execute([
            $child['id'], $currentGroup, $child['classroom'], $newGroup, $newClassroom, $currentAcademicYear, $newAcademicYear, $effectiveDate, $reason, 'pending', 'multi', $createdBy
        ]);
    }

    // 3. อัปเดตปีการศึกษาใหม่, กลุ่มเรียนใหม่, ห้องเรียนใหม่ใน children
    if (!empty($children)) {
        $ids = array_column($children, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $update = $pdo->prepare("UPDATE children SET academic_year=?, child_group=?, classroom=? WHERE id IN ($in)");
        $update->execute(array_merge([$newAcademicYear, $newGroup, $newClassroom], $ids));
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 