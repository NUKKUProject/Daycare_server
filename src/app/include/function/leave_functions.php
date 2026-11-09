<?php
// ค่าคงที่ของการลา ใช้ร่วมกันระหว่างหน้าแจ้งลากับ endpoint (ให้รายการโรคตรงกันทั้งสองฝั่ง)

const LEAVE_TYPES = ['personal' => 'ลากิจ', 'sick' => 'ลาป่วย'];

// โรคที่พบบ่อยในเด็กเล็ก 5 อันดับ (รหัส => ชื่อที่แสดง) ที่เหลือให้เลือก "อื่นๆ" แล้วพิมพ์เอง
const LEAVE_COMMON_DISEASES = [
    'cold' => 'ไข้หวัด / ไข้หวัดใหญ่',
    'hfmd' => 'มือ เท้า ปาก',
    'diarrhea' => 'ท้องเสีย / อุจจาระร่วง',
    'dengue' => 'ไข้เลือดออก',
    'conjunctivitis' => 'ตาแดง',
];

const LEAVE_MAX_DAYS = 30;          // ลาต่อครั้งได้ไม่เกิน (วัน)
const LEAVE_BACKDATE_DAYS = 7;      // แจ้งย้อนหลังได้ไม่เกิน (วัน) เผื่อลืมแจ้งในวันที่ลา

const LEAVE_STATUS_TEXT = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ไม่อนุมัติ', 'cancelled' => 'ยกเลิกแล้ว'];

// ข้อความหมายเหตุที่ใส่ในการเช็กชื่อเมื่อลา เช่น "ลาป่วย: มือ เท้า ปาก"
function leaveAttendanceNote(array $row): string
{
    $text = LEAVE_TYPES[$row['leave_type']] ?? 'ลา';
    $detail = $row['leave_type'] === 'sick' ? ($row['illness_name'] ?? '') : ($row['reason'] ?? '');
    $detail = trim((string)$detail);
    return $detail !== '' ? "$text: $detail" : $text;
}

// ใส่สถานะ "ลา" ในการเช็กชื่อของวันที่ลาที่ผ่านมาแล้ว/วันนี้ (เฉพาะวันที่ยังเป็นว่างหรือขาดเรียน ไม่ทับที่สแกนมาเรียนแล้ว)
// วันในอนาคตไม่ต้องทำ: สคริปต์ปิดวันจะตั้งให้เองตอนสิ้นวัน
function leaveApplyToAttendance(PDO $pdo, array $row): int
{
    $stmt = $pdo->prepare("UPDATE attendance
        SET status = 'leave', leave_note = :note, updated_at = NOW()
        WHERE student_id = :sid
          AND DATE(check_date) BETWEEN :s AND LEAST(CAST(:e AS date), CURRENT_DATE)
          AND (status IS NULL OR status = 'absent')");
    $stmt->execute([':note' => leaveAttendanceNote($row), ':sid' => $row['student_id'], ':s' => $row['start_date'], ':e' => $row['end_date']]);
    $updated = $stmt->rowCount();

    // วันธรรมดาที่ผ่านมา/วันนี้ ที่ยังไม่มีแถวเช็กชื่อเลย ให้สร้างเป็น "ลา" เพื่อให้ขึ้นในประวัติ
    $ins = $pdo->prepare("INSERT INTO attendance (student_id, check_date, status, leave_note, updated_at)
        SELECT :sid, d::date, 'leave', :note, NOW()
        FROM generate_series(CAST(:s AS date), LEAST(CAST(:e AS date), CURRENT_DATE), INTERVAL '1 day') AS d
        WHERE EXTRACT(ISODOW FROM d) < 6
          AND NOT EXISTS (SELECT 1 FROM attendance a WHERE a.student_id = :sid2 AND DATE(a.check_date) = d::date)");
    $ins->execute([':sid' => $row['student_id'], ':sid2' => $row['student_id'], ':note' => leaveAttendanceNote($row),
        ':s' => $row['start_date'], ':e' => $row['end_date']]);
    return $updated + $ins->rowCount();
}

// จำนวนคำขอลาที่รออนุมัติ (ตัวเลขบนเมนูของครู/แอดมิน) ครูนับเฉพาะเด็กในห้องที่ดูแล
function leave_pending_count(): int
{
    static $count = null;
    if ($count !== null) {
        return $count;
    }
    try {
        require_once __DIR__ . '/dev_screening_functions.php';
        $pdo = getDatabaseConnection();
        $sql = "SELECT COUNT(*) FROM leave_requests l JOIN children c ON c.studentid = l.student_id
                WHERE l.status = 'pending'";
        $params = [];
        if (getUserRole() === 'teacher') {
            $sql .= devScreeningTeacherScopeSql();
            $params[':teacher_id'] = $_SESSION['user_id'] ?? null;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $count = 0;
    }
    return $count;
}
