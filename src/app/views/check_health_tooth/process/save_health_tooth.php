<?php
// บันทึกผลตรวจสุขภาพช่องปากของเด็ก 1 คน (ฟอร์มรายคน)
// ผูกกับรอบตรวจและประเภทผู้ตรวจ (ครูคัดกรอง / แพทย์) ถ้าเด็กมีผลของประเภทนี้ในรอบนี้แล้วจะอัปเดต ไม่เพิ่มซ้ำ
require_once __DIR__ . '/../../../include/auth/auth.php';
checkUserRole(['admin', 'teacher', 'doctor']);
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../function/tooth_exam_helpers.php';

try {
    $pdo = getDatabaseConnection();

    // รับข้อมูล JSON จาก request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!$data) {
        throw new Exception('ไม่มีข้อมูลที่ส่งมา');
    }

    $data['total_teeth'] = isset($data['total_teeth']) && is_numeric($data['total_teeth']) ? (int)$data['total_teeth'] : 0;
    $data['decayed_teeth'] = isset($data['decayed_teeth']) && is_numeric($data['decayed_teeth']) ? (int)$data['decayed_teeth'] : 0;
    $data['age_year'] = isset($data['age_year']) && is_numeric($data['age_year']) ? (int)$data['age_year'] : 0;
    $data['age_month'] = isset($data['age_month']) && is_numeric($data['age_month']) ? (int)$data['age_month'] : 0;
    $data['age_day'] = isset($data['age_day']) && is_numeric($data['age_day']) ? (int)$data['age_day'] : 0;

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

    // รอบตรวจ + ประเภทผู้ตรวจ ตามบทบาทผู้ใช้
    $role = $_SESSION['role'] ?? '';
    $academicYear = trim((string) ($data['academic_year'] ?? ''));
    $round = tooth_resolve_round($pdo, $academicYear, !empty($data['round_id']) ? (int) $data['round_id'] : null, tooth_current_user_label());
    $examType = tooth_resolve_exam_type($role, $data['exam_type'] ?? null);

    $params = [
        ':prefix_th' => $data['prefix_th'] ?? null,
        ':first_name' => $data['first_name_th'] ?? null,
        ':last_name' => $data['last_name_th'] ?? null,
        ':nickname' => $data['nickname'] ?? null,
        ':classroom' => $data['class_room'] ?? null,
        // ชื่อแพทย์เก็บเฉพาะผลที่แพทย์ตรวจ
        ':doctor_name' => $examType === 'doctor' && trim((string) ($data['doctor_name'] ?? '')) !== '' ? trim($data['doctor_name']) : null,
        ':age_year' => $data['age_year'],
        ':age_month' => $data['age_month'],
        ':age_day' => $data['age_day'],
        ':total_teeth' => $data['total_teeth'],
        ':decayed_teeth' => $data['decayed_teeth'],
        ':oral_components' => $data['oral_components'] ?? null,
        ':teeth_status' => $data['teeth_status'] ?? null,
        ':missing_teeth_detail' => $data['missing_teeth_detail'] ?? null,
        ':positions' => json_encode($data['decayed_teeth_positions']),
        ':treatments' => json_encode($data['treatments'] ?? []),
        ':other_detail' => $data['other_treatment_detail'] ?? null,
        ':urgency' => $data['urgency'] ?? null,
        ':by' => tooth_current_user_label(),
    ];

    $find = $pdo->prepare('SELECT id FROM health_tooth_external WHERE student_id = :sid AND round_id = :rid AND exam_type = :t ORDER BY id DESC LIMIT 1');
    $find->execute([':sid' => $data['student_id'], ':rid' => $round['id'], ':t' => $examType]);
    $existingId = $find->fetchColumn();

    if ($existingId) {
        $stmt = $pdo->prepare("UPDATE health_tooth_external SET
            prefix_th = :prefix_th, first_name = :first_name, last_name = :last_name, nickname = :nickname,
            classroom = :classroom, doctor_name = :doctor_name,
            age_year = :age_year, age_month = :age_month, age_day = :age_day,
            total_teeth = :total_teeth, decayed_teeth = :decayed_teeth, oral_components = :oral_components,
            teeth_status = :teeth_status, missing_teeth_detail = :missing_teeth_detail,
            decayed_teeth_positions = :positions, treatments = :treatments,
            other_treatment_detail = :other_detail, urgency = :urgency,
            examined_by = :by, examined_at = CURRENT_DATE, updated_at = NOW()
            WHERE id = :id");
        $stmt->execute($params + [':id' => $existingId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO health_tooth_external (
            student_id, prefix_th, first_name, last_name, nickname, classroom, doctor_name,
            age_year, age_month, age_day, academic_year,
            total_teeth, decayed_teeth, oral_components, teeth_status, missing_teeth_detail,
            decayed_teeth_positions, treatments, other_treatment_detail, urgency,
            round_id, exam_type, examined_by, examined_at
        ) VALUES (
            :sid, :prefix_th, :first_name, :last_name, :nickname, :classroom, :doctor_name,
            :age_year, :age_month, :age_day, :academic_year,
            :total_teeth, :decayed_teeth, :oral_components, :teeth_status, :missing_teeth_detail,
            :positions, :treatments, :other_detail, :urgency,
            :rid, :t, :by, CURRENT_DATE
        )");
        $stmt->execute($params + [
            ':sid' => $data['student_id'], ':academic_year' => $academicYear,
            ':rid' => $round['id'], ':t' => $examType,
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
