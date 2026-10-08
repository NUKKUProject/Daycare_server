<?php
// เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';

try {
    $pdo = getDatabaseConnection();

    // รับข้อมูล JSON จาก request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // ตรวจสอบและกำหนดค่าเริ่มต้นสำหรับตัวเลข
    $data['total_teeth'] = isset($data['total_teeth']) && is_numeric($data['total_teeth']) ? (int)$data['total_teeth'] : 0;
    $data['decayed_teeth'] = isset($data['decayed_teeth']) && is_numeric($data['decayed_teeth']) ? (int)$data['decayed_teeth'] : 0;
    $data['age_year'] = isset($data['age_year']) && is_numeric($data['age_year']) ? (int)$data['age_year'] : 0;
    $data['age_month'] = isset($data['age_month']) && is_numeric($data['age_month']) ? (int)$data['age_month'] : 0;
    $data['age_day'] = isset($data['age_day']) && is_numeric($data['age_day']) ? (int)$data['age_day'] : 0;

    // ตรวจสอบและกำหนดค่าเริ่มต้นสำหรับ decayed_teeth_positions
    if (isset($data['decayed_teeth_positions']) && is_array($data['decayed_teeth_positions'])) {
        foreach ($data['decayed_teeth_positions'] as $key => $value) {
            $data['decayed_teeth_positions'][$key] = isset($value) && is_numeric($value) ? (int)$value : 0;
        }
    } else {
        $data['decayed_teeth_positions'] = [
            'upper_front_teeth' => 0, 'upper_right_molar' => 0, 'lower_right_molar' => 0,
            'lower_front_teeth' => 0, 'upper_left_molar' => 0, 'lower_left_molar' => 0
        ];
    }

    // ตรวจสอบว่ามีข้อมูลหรือไม่
    if (!$data) {
        throw new Exception('ไม่มีข้อมูลที่ส่งมา');
    }

    // รอบที่ปิดแล้วแก้ไขไม่ได้ (ต้องให้ admin เปิดรอบอีกครั้งก่อน)
    $lock = $pdo->prepare('SELECT r.status FROM health_tooth_external h LEFT JOIN tooth_exam_rounds r ON r.id = h.round_id WHERE h.id = :id');
    $lock->execute([':id' => $data['data_id'] ?? 0]);
    if ($lock->fetchColumn() === 'closed') {
        throw new Exception('รอบตรวจนี้ถูกปิดแล้ว ไม่สามารถแก้ไขได้');
    }

    // แพทย์แก้แล้วผลนั้นเป็นของแพทย์ ครูแก้ผลที่แพทย์ตรวจแล้วไม่ได้ ผลคัดกรองของครูไม่เก็บชื่อแพทย์
    $typeStmt = $pdo->prepare('SELECT exam_type FROM health_tooth_external WHERE id = :id');
    $typeStmt->execute([':id' => $data['data_id'] ?? 0]);
    $currentType = (string) $typeStmt->fetchColumn();
    $role = $_SESSION['role'] ?? '';
    if ($currentType === 'doctor' && $role === 'teacher') {
        throw new Exception('แพทย์ตรวจแล้ว ครูแก้ไขไม่ได้');
    }
    $newType = $role === 'doctor' ? 'doctor' : ($currentType ?: 'teacher');
    if ($newType !== 'doctor') {
        $data['doctor_name'] = null;
    }

    // เตรียมคำสั่ง SQL สำหรับการอัปเดตข้อมูล
    $stmt = $pdo->prepare("UPDATE health_tooth_external SET
        student_id = :student_id,
        prefix_th = :prefix_th,
        first_name = :first_name_th,
        last_name = :last_name_th,
        nickname = :nickname,
        classroom = :class_room,
        doctor_name = :doctor_name,
        age_year = :age_year,
        age_month = :age_month,
        age_day = :age_day,
        academic_year = :academic_year,
        total_teeth = :total_teeth,
        decayed_teeth = :decayed_teeth,
        oral_components = :oral_components,
        teeth_status = :teeth_status,
        missing_teeth_detail = :missing_teeth_detail,
        decayed_teeth_positions = :decayed_teeth_positions,
        treatments = :treatments,
        other_treatment_detail = :other_treatment_detail,
        urgency = :urgency,
        exam_type = :exam_type,
        updated_at = NOW()
    WHERE id = :data_id");

    // ผูกค่าพารามิเตอร์สำหรับข้อมูลนักเรียน
    $stmt->bindParam(':student_id', $data['student_id']);
    $stmt->bindParam(':prefix_th', $data['prefix_th']);
    $stmt->bindParam(':first_name_th', $data['first_name_th']);
    $stmt->bindParam(':last_name_th', $data['last_name_th']);
    $stmt->bindParam(':nickname', $data['nickname']);
    $stmt->bindParam(':class_room', $data['class_room']);
    $stmt->bindParam(':doctor_name', $data['doctor_name']);

    // ผูกค่าพารามิเตอร์สำหรับข้อมูลอายุ
    $stmt->bindParam(':age_year', $data['age_year']);
    $stmt->bindParam(':age_month', $data['age_month']);
    $stmt->bindParam(':age_day', $data['age_day']);
    $stmt->bindParam(':academic_year', $data['academic_year']);

    // ผูกค่าพารามิเตอร์
    $stmt->bindParam(':total_teeth', $data['total_teeth']);
    $stmt->bindParam(':decayed_teeth', $data['decayed_teeth']);
    $stmt->bindParam(':oral_components', $data['oral_components']);
    $stmt->bindParam(':teeth_status', $data['teeth_status']);
    $stmt->bindParam(':missing_teeth_detail', $data['missing_teeth_detail']);

    $decayed_teeth_positions_json = json_encode($data['decayed_teeth_positions']);
    $treatments_json = json_encode($data['treatments']);

    $stmt->bindParam(':decayed_teeth_positions', $decayed_teeth_positions_json);
    $stmt->bindParam(':treatments', $treatments_json);

    $stmt->bindParam(':other_treatment_detail', $data['other_treatment_detail']);
    $stmt->bindParam(':urgency', $data['urgency']);
    $stmt->bindParam(':exam_type', $newType);
    $stmt->bindParam(':data_id', $data['data_id']);

    // ประมวลผลคำสั่ง SQL
    $stmt->execute();

    // ตรวจสอบว่ามีการอัปเดตข้อมูลหรือไม่
    if ($stmt->rowCount() > 0) {
        $response = array(
            'status' => 'success',
            'message' => 'อัปเดตข้อมูลสำเร็จ'
        );
    } else {
        $response = array(
            'status' => 'info',
            'id' => $data['data_id'],
            'message' => 'ไม่มีการเปลี่ยนแปลงข้อมูล'
        );
    }

    echo json_encode($response);
} catch (Exception $e) {
    // ส่งข้อความข้อผิดพลาดกลับไปยัง AJAX
    $response = array(
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    );

    echo json_encode($response);
}
?>