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

// ช่วงอายุเกณฑ์ตามตัวเลือกเดิมของหน้าบันทึกกราฟการเจริญเติบโต (growth_records.age_range)
const DEV_SCREENING_AGE_RANGES = ['0-2', '2-5'];

// ค่าเริ่มต้นตามอายุเด็ก: ต่ำกว่า 2 ปี = 0-2 ที่เหลือ = 2-5
function devScreeningDefaultRange(int $months): string
{
    return $months < 24 ? '0-2' : '2-5';
}

// สรุปผลจากการติ๊ก: $statuses = ['gm'=>'pass'|'delay', ...] ครบ 5 ด้าน
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
