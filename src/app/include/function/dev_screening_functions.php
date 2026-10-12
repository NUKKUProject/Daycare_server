<?php
// ฟังก์ชันร่วมของการคัดกรองพัฒนาการ 5 ด้าน (ใช้ทั้งตอนโหลดรายชื่อ บันทึก และแสดงผล)

const DEV_SCREENING_DOMAINS = ['gm', 'fm', 'rl', 'el', 'ps'];

// อายุเต็ม ณ วันประเมิน (ปี/เดือน/วัน) และอายุรวมเป็นเดือน
function devScreeningAge(string $birthday, string $assessDate): ?array
{
    $b = DateTime::createFromFormat('Y-m-d', $birthday);
    $a = DateTime::createFromFormat('Y-m-d', $assessDate);
    if (!$b || !$a || $a < $b) {
        return null;
    }
    $d = $b->diff($a);
    return ['year' => $d->y, 'month' => $d->m, 'day' => $d->d, 'months' => $d->y * 12 + $d->m];
}

// ช่วงอายุเกณฑ์ DSPM ที่ใช้ประเมิน: ใช้เกณฑ์ของอายุเต็มที่ใกล้ที่สุดที่ไม่เกินอายุเด็ก
function devScreeningAgeRange(int $months): string
{
    $steps = [1, 2, 4, 6, 9, 12, 18, 24, 30, 36, 42, 48, 54, 60, 66];
    if ($months < 1) {
        return 'แรกเกิด - 1 เดือน 29 วัน';
    }
    for ($i = count($steps) - 1; $i >= 0; $i--) {
        if ($months >= $steps[$i]) {
            $from = $steps[$i];
            $to = $steps[$i + 1] ?? 72;
            return "อายุ {$from} เดือน ({$from} เดือน - " . ($to - 1) . " เดือน 29 วัน)";
        }
    }
    return '';
}

// สรุปผลจากการติ๊ก: $statuses = ['gm'=>'pass'|'fail', ...] ครบ 5 ด้าน
function devScreeningSummary(array $statuses, string $assessDate): array
{
    $pass = 0;
    foreach (DEV_SCREENING_DOMAINS as $d) {
        if (($statuses[$d] ?? '') === 'pass') {
            $pass++;
        }
    }
    $fail = count(DEV_SCREENING_DOMAINS) - $pass;
    $recheck = null;
    if ($fail > 0) {
        $recheck = (new DateTime($assessDate))->modify('+30 days')->format('Y-m-d');
    }
    return [
        'pass_count' => $pass,
        'fail_count' => $fail,
        'result' => $fail === 0 ? 'normal' : 'delay',
        'recheck_date' => $recheck,
    ];
}

// ครูดูแลเฉพาะเด็กในกลุ่ม/ห้องของตัวเอง แอดมินดูได้ทั้งหมด
function devScreeningTeacherScopeSql(): string
{
    return " AND EXISTS (
        SELECT 1 FROM teachers t
        WHERE t.teacher_id = :teacher_id
          AND c.child_group = ANY(string_to_array(t.group_ids, ','))
          AND c.classroom = ANY(string_to_array(t.classroom_ids, ','))
    )";
}
