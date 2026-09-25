<?php
require_once(__DIR__ . '/../../../../config/database.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection();

    $childGroup    = $_GET['child_group'] ?? '';
    $classroom     = $_GET['classroom'] ?? '';
    $academicYear  = $_GET['academic_year'] ?? '';
    $studentYear   = $_GET['student_year'] ?? '';

    // ถ้าเป็น 'all' ให้ถือว่าไม่ได้กรองตามปีของเด็ก
    if ($studentYear === 'all') {
        $studentYear = '';
    }

    // ดึงรายชื่อเด็ก
    $sql = "SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, child_group, classroom, birthday, academic_year
            FROM children
            WHERE status = 'กำลังศึกษา'";
    $params = [];

    if (!empty($childGroup)) {
        $sql .= " AND child_group = :cg";
        $params[':cg'] = $childGroup;
    }
    if (!empty($classroom)) {
        $sql .= " AND classroom = :cr";
        $params[':cr'] = $classroom;
    }
    if (!empty($studentYear)) {
        $sql .= " AND academic_year = :sy";
        $params[':sy'] = $studentYear;
    }
    $sql .= " ORDER BY child_group, classroom, firstname_th";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($children as $c) {
        $row = [
            'student_id'    => $c['studentid'],
            'prefix_th'     => $c['prefix_th'] ?? '',
            'first_name_th' => $c['firstname_th'] ?? '',
            'last_name_th'  => $c['lastname_th'] ?? '',
            'nickname'      => $c['nickname'] ?? '',
            'child_group'   => $c['child_group'] ?? '',
            'classroom'     => $c['classroom'] ?? '',
            'birthday'      => $c['birthday'] ?? null,
            'birth_date'    => $c['birthday'] ?? null,
            'academic_year' => $academicYear ?: ($c['academic_year'] ?? ''),
            'exam_date'     => date('Y-m-d'),
            'measurement_date' => date('Y-m-d'),
            'check_round'   => 1,
            'existing_id'   => null,
            'is_doctor_checked' => false,
            'existing_data' => null,
        ];

        // รายการใหม่ต้องแสดงอายุ ณ วันที่ตรวจเริ่มต้น เพื่อให้ผู้บันทึกตรวจทานได้
        if (!empty($row['birth_date'])) {
            $age = (new DateTime($row['birth_date']))->diff(new DateTime($row['exam_date']));
            $row['age_year'] = $age->y;
            $row['age_month'] = $age->m;
            $row['age_day'] = $age->d;
        } else {
            $row['age_year'] = null;
            $row['age_month'] = null;
            $row['age_day'] = null;
        }

        // หา record ล่าสุดของปีการศึกษานี้
        if (!empty($row['academic_year'])) {
            $stmt2 = $pdo->prepare("
                SELECT id, exam_date, measurement_date, check_round, is_doctor_checked, doctor_name,
                       birth_date, age_year, age_month, age_day,
                       vital_signs, behavior, physical_measures,
                       development_assessment, physical_exam, neurological, recommendation
                FROM health_data_external
                WHERE student_id = :sid AND academic_year = :year
                ORDER BY check_round DESC
                LIMIT 1
            ");
            $stmt2->execute([':sid' => $c['studentid'], ':year' => $row['academic_year']]);
            $rec = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($rec) {
                $row['existing_id'] = $rec['id'];
                $row['is_doctor_checked'] = (bool)$rec['is_doctor_checked'];
                $hasDoctor = !empty($rec['doctor_name']);
                // ถ้า doctor_name ว่าง → แก้ไข record เดิม
                // ถ้ามี doctor_name → เพิ่มรอบใหม่
                $row['check_round'] = $hasDoctor
                    ? (int)$rec['check_round'] + 1
                    : (int)$rec['check_round'];
                if (!$hasDoctor && $rec['exam_date']) {
                    $row['exam_date'] = $rec['exam_date'];
                }
                // ถ้า doctor_name ว่าง → โหลดข้อมูลเดิมมาให้แก้ไข
                // ถ้ามี doctor_name → ไม่โหลดข้อมูลเดิม (เริ่มรอบใหม่เป็นช่องว่าง)
                if (!$hasDoctor) {
                    // ใช้ข้อมูลที่เคยบันทึกไว้จริง เพื่อให้การแก้ไขแบบตารางไม่ทำให้
                    // วันเกิดและอายุของรายการเดิมหายไป
                    $row['birth_date'] = $rec['birth_date'] ?? $row['birthday'];
                    $row['measurement_date'] = $rec['measurement_date'] ?? $row['exam_date'];
                    $row['age_year'] = $rec['age_year'];
                    $row['age_month'] = $rec['age_month'];
                    $row['age_day'] = $rec['age_day'];
                    $row['existing_data'] = [
                        'vital_signs'            => json_decode($rec['vital_signs'], true) ?? [],
                        'behavior'               => json_decode($rec['behavior'], true) ?? [],
                        'physical_measures'      => json_decode($rec['physical_measures'], true) ?? [],
                        'development_assessment' => json_decode($rec['development_assessment'], true) ?? [],
                        'physical_exam'          => json_decode($rec['physical_exam'], true) ?? [],
                        'neurological'           => json_decode($rec['neurological'], true) ?? [],
                        'recommendation'         => $rec['recommendation'] ?? '',
                    ];
                }
            }
        }

        $result[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $result,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
