<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    $pdo = getDatabaseConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    error_log('Received data: ' . print_r($data, true));

    if (empty($data)) {
        throw new Exception('ไม่พบข้อมูลที่ต้องการบันทึก');
    }

    // เพิ่มการตรวจสอบข้อมูลซ้ำ
    $duplicateChecks = [];
    foreach ($data as $student) {
        if (empty($student['student_id'])) {
            continue;
        }
        
        // ตรวจสอบว่ามีการบันทึกข้อมูลของนักเรียนในวันนี้แล้วหรือไม่
        $checkDuplicate = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM health_data 
            WHERE student_id = :student_id 
            AND created_at::date = CURRENT_DATE
        ");
        
        $checkDuplicate->execute([':student_id' => $student['student_id']]);
        $result = $checkDuplicate->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $duplicateChecks[] = [
                'student_id' => $student['student_id'],
                'name' => $student['prefix_th'] . $student['first_name_th'] . ' ' . $student['last_name_th']
            ];
        }
    }
    
    // ถ้าพบข้อมูลซ้ำ ให้ส่งข้อความแจ้งเตือนกลับไป
    if (!empty($duplicateChecks)) {
        $duplicateNames = array_map(function($student) {
            return $student['name'];
        }, $duplicateChecks);
        
        echo json_encode([
            'status' => 'duplicate',
            'message' => 'พบการบันทึกข้อมูลซ้ำในวันนี้สำหรับนักเรียน: ' . implode(', ', $duplicateNames),
            'duplicates' => $duplicateChecks
        ]);
        exit;
    }

    // แยกประเภทฟิลด์
    $checkbox_fields = [
        'hair', 'eye', 'mouth', 'teeth', 'ears', 'nose', 'nails',
        'skin', 'hands_feet', 'arms_legs', 'body', 'symptoms', 'medicine'
    ];

    $condition_fields = [
        'eye_condition',    // สำหรับอาการตา
        'nose_condition',   // สำหรับอาการน้ำมูก
        'teeth_count',      // จำนวนฟันผุ
        'fever_temp',       // อุณหภูมิไข้
        'cough_type'        // ประเภทอาการไอ
    ];

    $detail_fields = [
        'skin_wound_detail',  // รายละเอียดแผล
        'skin_rash_detail',   // รายละเอียดผื่น
        'medicine_detail'     // รายละเอียดยา
    ];

    $reason_fields = [
        'hair_reason',
        'eye_reason', 
        'nose_reason',
        'symptoms_reason',
        'medicine_reason',
        'illness_reason',
        'accident_reason',
        'teacher_note'
    ];

    $basic_fields = [
        'student_id',
        'prefix_th',
        'first_name_th', 
        'last_name_th',
        'child_group',
        'classroom',
        'teacher_signature'
    ];

    // รวมทุกฟิลด์
    $all_fields = array_merge(
        $basic_fields,
        $checkbox_fields,
        $condition_fields,
        $detail_fields,
        $reason_fields
    );

    // เตรียม SQL
    $placeholders = '(' . implode(', ', array_map(fn($field) => ":$field", $all_fields)) . ')';
    $sql = "INSERT INTO health_data (" . implode(", ", $all_fields) . ") VALUES " . $placeholders;
    
    $stmt = $pdo->prepare($sql);

    // เริ่ม Transaction
    $pdo->beginTransaction();

    try {
        foreach ($data as $index => $student) {
            $params = [];

            // Debug: ตรวจสอบข้อมูลที่ได้รับ
            error_log('Processing student data: ' . print_r($student, true));

            // บันทึกข้อมูลพื้นฐาน
            foreach ($basic_fields as $field) {
                if ($field === 'student_id') {
                    if (empty($student['student_id'])) {
                        throw new Exception("ไม่พบรหัสนักเรียนสำหรับนักเรียนคนที่ " . ($index + 1));
                    }
                    $params[":$field"] = $student['student_id'];
                } else {
                    $params[":$field"] = $student[$field] ?? null;
                }
            }

            // บันทึก checkbox fields
            foreach ($checkbox_fields as $field) {
                if (isset($student[$field])) {
                    $checked_values = [];
                    if (!empty($student[$field]['checked'])) {
                        $checked_values['checked'] = $student[$field]['checked'];
                    }
                    if (!empty($student[$field]['unchecked'])) {
                        $checked_values['unchecked'] = $student[$field]['unchecked'];
                    }
                    $params[":$field"] = !empty($checked_values) ? 
                        json_encode($checked_values, JSON_UNESCAPED_UNICODE) : null;
                } else {
                    $params[":$field"] = null;
                }
            }

            // บันทึก condition fields
            foreach ($condition_fields as $field) {
                $params[":$field"] = $student[$field] ?? null;
            }

            // บันทึก detail fields
            foreach ($detail_fields as $field) {
                $params[":$field"] = $student[$field] ?? null;
            }

            // บันทึก reason fields
            foreach ($reason_fields as $field) {
                $params[":$field"] = $student[$field] ?? null;
            }

            if (!$stmt->execute($params)) {
                throw new Exception("ไม่สามารถบันทึกข้อมูลของนักเรียนคนที่ " . ($index + 1));
            }
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกข้อมูลสำเร็จ'
        ]);

    } catch (Exception $e) {
        // Rollback ถ้าเกิดข้อผิดพลาด
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
