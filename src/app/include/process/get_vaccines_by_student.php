<?php
require_once(__DIR__ . '/../../../config/database.php');

try {
    if (!isset($_GET['student_id'])) {
        throw new Exception('ไม่พบรหัสนักเรียน');
    }

    $student_id = $_GET['student_id'];
    $pdo = getDatabaseConnection();

    // ดึงข้อมูลกลุ่มอายุทั้งหมด
    $stmt = $pdo->query("SELECT * FROM vaccine_age_groups ORDER BY display_order");
    $age_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    $total_vaccines = 0;
    $received_vaccines = 0;

    foreach ($age_groups as $group) {
        // ดึงรายการวัคซีนในกลุ่มอายุนี้
        $stmt = $pdo->prepare("
            SELECT vl.*, 
                   v.id as vaccine_record_id,
                   v.vaccine_date,
                   v.vaccine_number,
                   v.vaccine_location,
                   v.vaccine_provider,
                   v.lot_number,
                   v.next_appointment,
                   v.vaccine_note,
                   v.image_path
            FROM vaccine_list vl
            LEFT JOIN vaccines v ON vl.id = v.vaccine_list_id AND v.student_id = ?
            WHERE vl.age_group_id = ? AND vl.is_active = true
            ORDER BY vl.id
        ");
        $stmt->execute([$student_id, $group['id']]);
        $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $group_received = 0;
        foreach ($vaccines as $vaccine) {
            $total_vaccines++;
            if ($vaccine['vaccine_record_id']) {
                $received_vaccines++;
                $group_received++;
            }
        }

        $result[] = [
            'id' => $group['id'],
            'age_group' => $group['age_group'],
            'display_order' => $group['display_order'],
            'vaccines' => $vaccines,
            'received_count' => $group_received,
            'total_count' => count($vaccines)
        ];
    }

    $coverage_percent = $total_vaccines > 0 ? round(($received_vaccines / $total_vaccines) * 100) : 0;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'age_groups' => $result,
            'summary' => [
                'total_vaccines' => $total_vaccines,
                'received_vaccines' => $received_vaccines,
                'pending_vaccines' => $total_vaccines - $received_vaccines,
                'coverage_percent' => $coverage_percent
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_vaccines_by_student: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>