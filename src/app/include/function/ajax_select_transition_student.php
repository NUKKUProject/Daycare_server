<?php
require_once(__DIR__ . '/../../../config/database.php');
header('Content-Type: application/json,charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);


$pdo = getDatabaseConnection();

try {
    // รับค่าจาก POST
    $studentids = [];
    if (isset($_POST['studentids'])) {
        // ถ้ารับมาจาก FormData แบบ comma string
        $studentids = explode(',', $_POST['studentids']);
    } elseif (isset($_POST['studentids[]'])) {
        // กรณีเป็น array จริง ๆ
        $studentids = $_POST['studentids'];
    }

    // ฟิลด์อื่น ๆ
    $group = $_POST['bulk_new_level'] ;
    $classroom = $_POST['bulk_new_classroom'] ;
    $academicYear = $_POST['bulk_new_academic_year'];
    $newLevel = $_POST['bulk_new_level'] ;
    $newClassroom = $_POST['bulk_new_classroom'];
    $newAcademicYear = $_POST['bulk_new_academic_year'] ;
    $effectiveDate = trim($_POST['bulk_effective_date'] ?? '');
    $effectiveDate = $effectiveDate === '' ? null : $effectiveDate;
    $reason = $_POST['bulk_reason'] ?? '';

    
    session_start();
    $createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // หรือ 0

    if (empty($studentids)) {
        echo json_encode(['success' => false, 'message' => 'กรุณาระบุ studentids']);
        exit;
    }

    // 1. ค้นหาเด็กที่ตรงกับ studentids ที่ระบุและสถานะ 'กำลังศึกษา'
    $studentid = implode(',', array_fill(0, count($studentids), '?'));


    $query = "SELECT id, child_group, classroom FROM children WHERE studentid IN ($studentid) AND status='กำลังศึกษา'";
    $stmt = $pdo->prepare($query);
    $stmt->execute($studentids);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($children)) {
        echo json_encode(['success' => false, 'message' => 'studentids ไม่ถูกต้องหรือไม่มีนักเรียนสถานะกำลังศึกษา']);
        exit;
    }

    // 2. บันทึกการเลื่อนชั้นใหม่
    $insert = $pdo->prepare('INSERT INTO student_transitions (
    child_id, current_class_level, current_classroom, 
    new_class_level, new_classroom, 
    academic_year, new_academic_year, 
    effective_date, reason, status, transition_type, created_by
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    foreach ($children as $child) {
        $insert->execute([
            $child['id'],
            $child['child_group'],          // ระดับเดิมของเด็กแต่ละคน (อาจจะต้องมาจาก $child)
            $child['classroom'],      // ห้องเดิมของเด็กแต่ละคน (อาจจะต้องมาจาก $child)
            $newLevel,
            $newClassroom,
            $academicYear,
            $newAcademicYear,
            $effectiveDate,
            $reason,
            'pending',
            'bulk',
            $createdBy   // เช่น 'system' ← ตรงนี้ถ้ามี field อื่นเป็น integer อาจสลับ
        ]);
    }

         // 3. อัปเดตข้อมูลใน children
        $childIds = array_column($children, 'id');
        $in2 = implode(',', array_fill(0, count($childIds), '?'));
        $update = $pdo->prepare("UPDATE children SET academic_year=?, child_group=?, classroom=? WHERE id IN ($in2)");
        $update->execute(array_merge([$newAcademicYear, $newLevel, $newClassroom], $childIds));

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
