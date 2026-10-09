<?php
// ตัวช่วยเกี่ยวกับรอบตรวจสุขภาพ (health_exam_rounds)

function health_get_round(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM health_exam_rounds WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

/** หารอบจากปีการศึกษา + ครั้งที่ */
function health_find_round(PDO $pdo, string $year, int $no): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM health_exam_rounds WHERE academic_year = :y AND round_no = :n');
    $stmt->execute([':y' => $year, ':n' => $no]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

/** รอบต้องมีอยู่และเปิดอยู่ ไม่เช่นนั้นโยน Exception */
function health_require_open_round(PDO $pdo, int $id): array
{
    $r = health_get_round($pdo, $id);
    if (!$r) {
        throw new Exception('ไม่พบรอบตรวจ กรุณาให้ผู้ดูแลระบบเปิดรอบตรวจก่อน');
    }
    if ($r['status'] !== 'open') {
        throw new Exception("รอบ \"{$r['title']}\" ถูกปิดแล้ว บันทึกหรือแก้ไขไม่ได้");
    }
    return $r;
}

/** เงื่อนไข SQL ของผลตรวจในรอบที่เลือก (ใช้กับ health_data_external) พร้อมพารามิเตอร์ คืน null ถ้าไม่พบรอบ */
function health_round_condition(PDO $pdo, int $roundId): ?array
{
    $round = health_get_round($pdo, $roundId);
    if (!$round) {
        return null;
    }
    return [
        "academic_year::text = :ry AND COALESCE(check_round, 1) = :rn",
        [':ry' => (string) $round['academic_year'], ':rn' => (int) $round['round_no']],
    ];
}
