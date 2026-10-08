<?php
// ตัวช่วยร่วมของระบบตรวจสุขภาพช่องปาก: รอบตรวจ + ประเภทผู้ตรวจ (ครูคัดกรอง / แพทย์)

/** ลำดับความสำคัญของผลตรวจในรอบเดียวกัน: แพทย์ > ครู > ข้อมูลเดิม */
const TOOTH_TYPE_ORDER_SQL = "CASE exam_type WHEN 'doctor' THEN 0 WHEN 'teacher' THEN 1 ELSE 2 END";

/** ประเภทผู้ตรวจที่ผู้ใช้ตามบทบาทบันทึกได้ (admin เลือกได้ทั้งสอง) */
function tooth_allowed_types(?string $role): array
{
    if ($role === 'doctor') {
        return ['doctor'];
    }
    if ($role === 'teacher') {
        return ['teacher'];
    }
    return ['teacher', 'doctor'];   // admin: ค่าเริ่มต้นเป็นครูคัดกรอง (ไม่ระบุว่าเป็นผลของแพทย์โดยไม่ตั้งใจ)
}

/** เลือกประเภทผู้ตรวจ: ถ้าส่งมาและอนุญาตให้ใช้ ไม่งั้นใช้ตัวแรกที่บทบาทนี้บันทึกได้ */
function tooth_resolve_exam_type(?string $role, ?string $requested): string
{
    $allowed = tooth_allowed_types($role);
    return in_array($requested, $allowed, true) ? $requested : $allowed[0];
}

/** รอบตรวจของปีการศึกษา (ใหม่ล่าสุดก่อน) */
function tooth_get_rounds(PDO $pdo, string $academicYear): array
{
    $stmt = $pdo->prepare('SELECT * FROM tooth_exam_rounds WHERE academic_year = :y ORDER BY round_no DESC');
    $stmt->execute([':y' => $academicYear]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tooth_get_round(PDO $pdo, int $roundId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM tooth_exam_rounds WHERE id = :id');
    $stmt->execute([':id' => $roundId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * รอบที่ใช้บันทึก: ระบุ round_id มาก็ใช้รอบนั้น ไม่งั้นใช้รอบที่เปิดอยู่ล่าสุดของปีนั้น
 * รอบต้องถูกเปิดโดย admin ก่อน ไม่สร้างให้อัตโนมัติ
 * @throws Exception เมื่อไม่มีรอบ / รอบที่เลือกถูกปิด / ทุกรอบของปีนั้นปิดอยู่
 */
function tooth_resolve_round(PDO $pdo, string $academicYear, ?int $roundId, ?string $createdBy = null): array
{
    if ($roundId) {
        $round = tooth_get_round($pdo, $roundId);
        if (!$round) {
            throw new Exception('ไม่พบรอบตรวจที่เลือก');
        }
        if ($round['status'] !== 'open') {
            throw new Exception('รอบตรวจนี้ถูกปิดแล้ว ไม่สามารถบันทึกหรือแก้ไขได้');
        }
        return $round;
    }

    $rounds = tooth_get_rounds($pdo, $academicYear);
    foreach ($rounds as $r) {
        if ($r['status'] === 'open') {
            return $r;
        }
    }
    if ($rounds) {
        throw new Exception('ทุกรอบตรวจของปีการศึกษานี้ถูกปิดแล้ว กรุณาให้ผู้ดูแลระบบเปิดรอบใหม่');
    }
    throw new Exception('ยังไม่มีรอบตรวจของปีการศึกษานี้ กรุณาให้ผู้ดูแลระบบเปิดรอบตรวจก่อน');
}

/** ชื่อผู้ใช้สำหรับบันทึกว่าใครตรวจ */
function tooth_current_user_label(): string
{
    return trim((string) ($_SESSION['username'] ?? $_SESSION['user_id'] ?? ''));
}

/**
 * SQL เลือก "ผลตรวจที่ใช้" ต่อเด็ก 1 คน ในปีการศึกษา (ไม่ระบุรอบ = รอบล่าสุดที่มีผล, ในรอบเดียวกันแพทย์มาก่อนครู)
 * ใช้ใน export เพื่อไม่ให้เด็กซ้ำเมื่อมีทั้งผลครูและผลแพทย์
 * พารามิเตอร์: :year และ :round (เมื่อ $withRound เป็น true)
 * $doctorOnly = true จะนับเฉพาะผลที่แพทย์ตรวจ (ใช้ตอนกรองตามชื่อแพทย์)
 */
function tooth_effective_sql(bool $withRound, bool $doctorOnly = false): string
{
    return "SELECT DISTINCT ON (student_id) * FROM health_tooth_external
            WHERE academic_year = :year" . ($withRound ? ' AND round_id = :round' : '') . ($doctorOnly ? " AND exam_type = 'doctor'" : '') . "
            ORDER BY student_id, round_id DESC NULLS LAST, " . TOOTH_TYPE_ORDER_SQL . ", id DESC";
}
