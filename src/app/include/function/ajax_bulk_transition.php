<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$group = $data['group'];
$classroom = $data['classroom'];
$academicYear = $data['academicYear'];
$newLevel = $data['newLevel'];
$newClassroom = $data['newClassroom'];
$newAcademicYear = $data['newAcademicYear'];
$effectiveDate = $data['effectiveDate'];
$reason = $data['reason'];

session_start();
$createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // หรือ 0

try {
    $pdo = getDatabaseConnection();
    // 1. ค้นหาเด็กที่ตรงกับ group, classroom, academicYear (ใช้ single quote)
    $stmt = $pdo->prepare("SELECT id FROM children WHERE child_group=? AND classroom=? AND academic_year=? AND status='กำลังศึกษา'");
    $stmt->execute([$group, $classroom, $academicYear]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. บันทึกการเลื่อนชั้นใหม่
    $insert = $pdo->prepare('INSERT INTO student_transitions (child_id, current_class_level, current_classroom, new_class_level, new_classroom, academic_year, new_academic_year, effective_date, reason, status, transition_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($children as $child) {
        $insert->execute([
            $child['id'], $group, $classroom, $newLevel, $newClassroom, $academicYear, $newAcademicYear, $effectiveDate, $reason, 'pending', 'bulk', $createdBy
        ]);
    }

    // 3. อัปเดตปีการศึกษาใหม่, กลุ่มเรียนใหม่, ห้องเรียนใหม่ใน children
    if (!empty($children)) {
        $ids = array_column($children, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $update = $pdo->prepare("UPDATE children SET academic_year=?, child_group=?, classroom=? WHERE id IN ($in)");
        $update->execute(array_merge([$newAcademicYear, $newLevel, $newClassroom], $ids));
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 