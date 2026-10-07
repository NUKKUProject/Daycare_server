<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['student']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>
<?php require_once __DIR__ . '/../../include/function/dashboard_functions.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>
<?php
$studentid = $_SESSION['username'] ?? '';
$child = $studentid !== '' ? getChildById($studentid) : false;

// ข้อมูลของเด็กวันนี้: เวลาสแกนบัตร / ผู้ดูแล / อุณหภูมิ / อาการผิดปกติ / อาหารประจำวัน
$todayAtt = null;
$symptomList = [];
$careList = [];
$menuToday = [];
$mealIcons = ['morning_snack' => 'bi-sunrise', 'lunch' => 'bi-brightness-high', 'afternoon_snack' => 'bi-sunset'];
$mealLabels = [
    'morning_snack' => ['time' => 'เช้า', 'kind' => 'อาหารว่าง'],
    'lunch' => ['time' => 'กลางวัน', 'kind' => 'อาหารหลัก'],
    'afternoon_snack' => ['time' => 'บ่าย', 'kind' => 'อาหารว่าง'],
];

if ($child) {
    $pdo = getDatabaseConnection();

    $attStmt = $pdo->prepare("
        SELECT status, TO_CHAR(check_date, 'HH24:MI') AS checkin_time,
               temperature, symptoms, other_symptoms, care_actions, care_other, caretaker_name,
               dropped_off_by, dropped_off_detail,
               picked_up_by, picked_up_detail, leave_note,
               SUBSTRING(check_out_time::text FROM '(\d{2}:\d{2}):\d{2}') AS checkout_time
        FROM attendance
        WHERE student_id = :s AND DATE(check_date) = CURRENT_DATE
        ORDER BY check_date ASC LIMIT 1
    ");
    $attStmt->execute(['s' => $studentid]);
    $todayAtt = $attStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // ผู้มาส่ง / ผู้มารับ: ชื่อ/รูปจากข้อมูลผู้ปกครองของเด็ก (กรณีอื่นๆ ใช้รายละเอียดที่ครูระบุ)
    $guardianLabels = ['father' => 'พ่อ', 'mother' => 'แม่', 'relative' => 'ผู้ปกครอง/ผู้ดูแล', 'other' => 'บุคคลอื่น'];
    $resolveGuardian = function (?string $type, ?string $detail) use ($pdo, $studentid, $guardianLabels) {
        if (empty($type)) {
            return null;
        }
        $person = ['label' => $guardianLabels[$type] ?? $type, 'name' => '', 'image' => ''];
        if ($type === 'other') {
            $person['name'] = $detail ?? '';
        } elseif (isset($guardianLabels[$type])) {
            $gStmt = $pdo->prepare("SELECT {$type}_first_name AS first_name, {$type}_last_name AS last_name, {$type}_image AS image FROM children WHERE studentid = :s");
            $gStmt->execute(['s' => $studentid]);
            if ($g = $gStmt->fetch(PDO::FETCH_ASSOC)) {
                $person['name'] = trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''));
                $person['image'] = $g['image'] ?? '';
            }
        }
        return $person;
    };
    $dropOff = $todayAtt ? $resolveGuardian($todayAtt['dropped_off_by'] ?? null, $todayAtt['dropped_off_detail'] ?? null) : null;
    $pickUp = $todayAtt ? $resolveGuardian($todayAtt['picked_up_by'] ?? null, $todayAtt['picked_up_detail'] ?? null) : null;

    // ข้อมูลรับกลับเก่าเก็บเป็นข้อความใน leave_note เช่น "ผู้รับเด็ก: นายดำ ศรีโคตร (พ่อ)"
    if (!$pickUp && $todayAtt && preg_match('/^ผู้รับเด็ก:\s*(.*?)\s*\(([^)]+)\)(?:\s*-\s*(.*))?$/su', (string) ($todayAtt['leave_note'] ?? ''), $m)) {
        $typeByLabel = array_flip($guardianLabels) + ['อื่นๆ' => 'other']; // รองรับข้อมูลเก่าที่บันทึกว่า "อื่นๆ"
        $type = $typeByLabel[trim($m[2])] ?? null;
        $detail = trim($m[3] ?? '');
        $pickUp = $resolveGuardian($type, $detail);
        if ($pickUp && trim($m[1]) !== '') {
            $pickUp['name'] = trim($m[1]);
        }
    }

    if ($todayAtt) {
        require_once __DIR__ . '/../../include/function/checkin_settings.php';
        $options = checkin_load_options(false, true);
        $lookup = [];
        foreach ($options['symptoms'] as $opt) {
            $subs = [];
            foreach ($opt['subs'] ?? [] as $sub) {
                $subs[$sub['code']] = $sub['label'];
            }
            $lookup[$opt['code']] = ['label' => $opt['label'], 'subs' => $subs];
        }

        $symptoms = json_decode($todayAtt['symptoms'] ?? '{}', true);
        foreach (is_array($symptoms) ? $symptoms : [] as $code => $subCodes) {
            $label = $lookup[$code]['label'] ?? $code;
            $subLabels = array_map(fn($c) => $lookup[$code]['subs'][$c] ?? $c, is_array($subCodes) ? $subCodes : []);
            $symptomList[] = $subLabels ? $label . ' (' . implode(', ', $subLabels) . ')' : $label;
        }
        if (!empty($todayAtt['other_symptoms'])) {
            $symptomList[] = $todayAtt['other_symptoms'];
        }

        $careLookup = [];
        foreach ($options['care'] as $opt) {
            $careLookup[$opt['code']] = $opt['label'];
        }
        $careActions = json_decode($todayAtt['care_actions'] ?? '[]', true);
        foreach (is_array($careActions) ? $careActions : [] as $code) {
            $label = $careLookup[$code] ?? $code;
            if (!empty($todayAtt['care_other']) && $code === 'other') {
                $label .= ' (' . $todayAtt['care_other'] . ')';
            }
            $careList[] = $label;
        }
    }

    $classroom = $child['classroom'] ?? '';
    if ($classroom !== '') {
        try {
            $menuStmt = $pdo->prepare("SELECT meal_slot, menu_text FROM daily_menus WHERE menu_date = CURRENT_DATE AND classroom = :c");
            $menuStmt->execute(['c' => $classroom]);
            foreach ($menuStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $menuToday[$row['meal_slot']] = $row['menu_text'];
            }
        } catch (PDOException $e) {
            error_log('student_dashboard menu: ' . $e->getMessage());
        }
    }
}

function renderGuardian(?array $person): void
{
    if (!$person) {
        echo '<span class="dropoff-name">-</span>';
        return;
    }
    echo '<div class="dropoff-row">';
    // บุคคลอื่น / ไม่มีรูปในระบบ ใช้รูปเริ่มต้น เพื่อให้หน้าตาการ์ดเหมือนกัน
    $src = $person['image'] !== '' ? $person['image'] : '../../../public/assets/images/avatar.png';
    echo '<img class="dropoff-avatar" src="' . htmlspecialchars($src) . '" alt="รูป' . htmlspecialchars($person['label']) . '" onerror="this.src=\'../../../public/assets/images/avatar.png\'">';
    echo '<div><span class="attendance-summary-card-value">' . htmlspecialchars($person['label']) . '</span>';
    if ($person['name'] !== '') {
        echo '<span class="dropoff-name">' . htmlspecialchars($person['name']) . '</span>';
    }
    echo '</div></div>';
}

$viewTabs = [
    [
        'id' => 'profile',
        'icon' => 'bi bi-person-badge',
        'title' => 'ประวัติประจำตัว',
        'description' => 'ข้อมูลส่วนตัว ผู้ปกครอง และที่อยู่',
        'color' => 'blue',
    ],
    [
        'id' => 'daily_notebook',
        'icon' => 'bi bi-journal-text',
        'title' => 'สมุดสื่อสารประจำวัน',
        'description' => 'ส่งข้อมูลที่บ้านถึงครู และดูบันทึกของครูที่ศูนย์',
        'color' => 'orange',
        'href' => 'daily_notebook.php',
    ],
    [
        'id' => 'vaccine',
        'icon' => 'fa-solid fa-syringe',
        'title' => 'วัคซีน',
        'description' => 'ประวัติการรับวัคซีนของเด็ก',
        'color' => 'green',
    ],
    [
        'id' => 'attendance',
        'icon' => 'bi bi-calendar-check',
        'title' => 'การมาเรียน',
        'description' => 'ตรวจสอบประวัติการมาเรียน',
        'color' => 'orange',
    ],
    [
        'id' => 'health',
        'icon' => 'fa-solid fa-stethoscope',
        'title' => 'ตรวจร่างกายประจำวัน',
        'description' => 'ผลการตรวจร่างกายประจำวันของเด็ก',
        'color' => 'red',
    ],
    [
        'id' => 'growth',
        'icon' => 'bi bi-graph-up',
        'title' => 'การเจริญเติบโต',
        'description' => 'ติดตามส่วนสูง น้ำหนัก และพัฒนาการ',
        'color' => 'purple',
    ],
    [
        'id' => 'health_external',
        'icon' => 'fa-solid fa-user-doctor',
        'title' => 'ตรวจร่างกายจากกุมารแพทย์',
        'description' => 'ประวัติการตรวจสุขภาพโดยกุมารแพทย์ทั้งหมด',
        'color' => 'teal',
        'href' => 'health_external_history.php',
    ],
    [
        'id' => 'health_tooth',
        'icon' => 'fa-solid fa-tooth',
        'title' => 'ตรวจสุขภาพช่องปากจากทันตแพทย์',
        'description' => 'ประวัติการตรวจฟันและสุขภาพช่องปากทั้งหมด',
        'color' => 'cyan',
        'href' => 'health_tooth_history.php',
    ],
];
?>

<style>
    .student-dashboard {
        --student-primary: #26648E;
        --student-primary-dark: #1E4F6F;
        --student-primary-soft: #ebf4fb;
        --student-text: #2c3e50;
        --student-muted: #64748b;
        --student-border: #d9e6ee;
        max-width: 1280px;
        margin: 0 auto;
        padding: 1rem 1.25rem 2.5rem;
    }

    .student-dashboard-header {
        background:
            radial-gradient(circle at 92% 15%, rgba(255,255,255,0.22) 0 8%, transparent 8.5%),
            radial-gradient(circle at 78% 95%, rgba(255,255,255,0.12) 0 12%, transparent 12.5%),
            linear-gradient(120deg, #1E4F6F 0%, #26648E 58%, #4f88aa 100%);
        border-radius: 1.75rem;
        box-shadow: 0 18px 40px rgba(38, 100, 142, 0.2);
        margin-bottom: 1.5rem;
        overflow: hidden;
        padding: 2.25rem 2.5rem;
        position: relative;
    }

    .student-dashboard-header h1 {
        color: #fff;
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        margin-bottom: 0.5rem;
        position: relative;
    }

    .student-dashboard-header p {
        color: rgba(255,255,255,0.82);
        margin: 0;
        position: relative;
    }

    .student-dashboard-header h1::before {
        align-items: center;
        background: rgba(255,255,255,0.16);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 0.8rem;
        content: '\F425';
        display: inline-flex;
        font-family: bootstrap-icons;
        font-size: 1.2rem;
        height: 42px;
        justify-content: center;
        margin-right: 0.75rem;
        vertical-align: -0.4rem;
        width: 42px;
    }

    .student-hero {
        align-items: center;
        border-radius: 1.25rem;
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 1rem 1.25rem;
    }

    .student-hero img {
        border: 4px solid rgba(255,255,255,0.85);
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        flex-shrink: 0;
        height: 88px;
        object-fit: cover;
        position: relative;
        width: 88px;
    }

    .student-hero h1 { font-size: 1.2rem; margin-bottom: 0.4rem; }
    .student-hero h1::before { content: none; }

    .student-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem 0.5rem;
        position: relative;
    }

    .student-meta span {
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 999px;
        color: #fff;
        font-size: 0.75rem;
        padding: 0.15rem 0.55rem;
    }

    .student-meta i {
        color: #fff;
        margin-right: 0.2rem;
    }

    .attendance-summary {
        background: #fff;
        border: 1px solid var(--student-border);
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        margin-bottom: 2rem;
        padding: 1.25rem;
    }

    .attendance-subtitle {
        border-top: 1px solid var(--student-border);
        color: var(--student-primary-dark);
        font-size: 1rem;
        font-weight: 700;
        margin: 1.1rem 0 0.75rem;
        padding-top: 1rem;
    }

    .attendance-subtitle i {
        color: var(--student-primary);
        margin-right: 0.4rem;
    }

    .attendance-summary-groups {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .attendance-summary-card {
        align-items: center;
        background: var(--student-primary-soft);
        border-radius: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        justify-content: center;
        min-height: 104px;
        padding: 0.9rem 0.75rem;
        text-align: center;
    }

    .attendance-summary-card.highlight {
        background: #fff7e6;
        border: 1px solid #f3d9a3;
    }

    .attendance-summary-card-label {
        color: var(--student-muted);
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        margin: 0;
    }

    .attendance-summary-card-label i {
        color: var(--student-primary);
        margin-right: 0.15rem;
    }

    .attendance-summary-card-value {
        color: var(--student-primary-dark);
        font-size: 1.15rem;
        font-weight: 700;
    }

    .attendance-charts {
        display: grid;
        gap: 1.5rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 1.25rem;
    }

    .card-title-graph {
        color: rgba(31, 102, 153, 0.91);
        font-weight: 600;
    }

    .attendance-charts canvas {
        max-height: 300px;
    }

    .attendance-summary-groups.today-info { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .attendance-summary-groups.meal-info { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .attendance-summary-card-value { overflow-wrap: anywhere; }
    .meal-item {
        align-items: stretch;
        background: #f8fbfd;
        border: 1px solid var(--student-border);
        border-radius: 1rem;
        display: flex;
        overflow: hidden;
    }

    .meal-badge {
        align-items: center;
        background: var(--meal-bg);
        color: var(--meal-fg);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        gap: 0.15rem;
        justify-content: center;
        padding: 0.75rem 0.5rem;
        width: 92px;
    }

    .meal-badge i { font-size: 1.4rem; line-height: 1; }
    .meal-badge strong { font-size: 1.05rem; line-height: 1.1; }
    .meal-badge small { font-size: 0.7rem; font-weight: 600; opacity: 0.85; }

    .meal-text {
        align-items: center;
        color: var(--student-primary-dark);
        display: flex;
        flex: 1;
        font-size: 1.1rem;
        font-weight: 700;
        padding: 0.75rem 1rem;
    }

    .meal-item.morning_snack   { --meal-bg: #fef3c7; --meal-fg: #b45309; }
    .meal-item.lunch           { --meal-bg: #ffedd5; --meal-fg: #c2410c; }
    .meal-item.afternoon_snack { --meal-bg: #e0e7ff; --meal-fg: #4338ca; }

    .attendance-summary-card.symptoms {
        align-items: stretch;
        grid-column: 1 / -1;
        gap: 0.75rem;
        min-height: 0;
        padding: 1rem 1.25rem;
        text-align: left;
    }

    .attendance-summary-card.symptoms .attendance-summary-card-label {
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        color: var(--student-primary-dark);
        font-size: 0.95rem;
        padding-bottom: 0.5rem;
        text-align: left;
    }

    .symptom-rows { display: flex; flex-direction: column; gap: 0.5rem; }

    .symptom-row {
        align-items: baseline;
        display: flex;
        gap: 0.75rem;
    }

    .symptom-row-label {
        color: var(--student-muted);
        flex-shrink: 0;
        font-size: 0.82rem;
        font-weight: 600;
        width: 62px;
    }

    .symptom-row-value {
        color: var(--student-primary-dark);
        display: flex;
        flex-wrap: wrap;
        font-weight: 700;
        gap: 0.35rem;
    }

    .chip {
        border-radius: 999px;
        font-size: 0.9rem;
        font-weight: 700;
        padding: 0.15rem 0.7rem;
    }

    .chip-warn { background: #fde68a; color: #92400e; }
    .chip-care { background: #dbeafe; color: #1e40af; }
    .chip-ok   { background: #dcfce7; color: #15803d; }

    .attendance-summary-card.person { gap: 0.6rem; }

    .attendance-summary-card.person .attendance-summary-card-label {
        color: var(--student-primary-dark);
        font-size: 0.95rem;
    }

    .time-chip {
        background: #fff;
        border: 1px solid #cfe0eb;
        border-radius: 999px;
        color: var(--student-primary-dark);
        font-size: 0.9rem;
        font-weight: 700;
        padding: 0.2rem 0.8rem;
    }

    .time-chip i { color: var(--student-primary); margin-right: 0.15rem; }

    .attendance-summary-card.person .dropoff-avatar { height: 72px; width: 72px; }
    .attendance-summary-card.person .dropoff-row .attendance-summary-card-value { font-size: 1.3rem; }
    .attendance-summary-card.person .dropoff-name { font-size: 0.85rem; margin-top: 0.1rem; }

    .dropoff-row {
        align-items: center;
        display: flex;
        gap: 0.65rem;
        justify-content: center;
        text-align: left;
    }

    .dropoff-avatar {
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 3px 10px rgba(38, 100, 142, 0.25);
        flex-shrink: 0;
        height: 56px;
        object-fit: cover;
        width: 56px;
    }

    .dropoff-name { color: var(--student-muted); display: block; font-size: 0.8rem; line-height: 1.25; }

    /* จอใหญ่: การ์ดอาการเรียงต่อในแถวเดียวกับ 3 การ์ดแรก */
    @media (min-width: 1100px) {
        .attendance-summary-groups.today-info {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .attendance-summary-card.symptoms { grid-column: auto; }
    }

    @media (max-width: 768px) {
        .attendance-summary-groups,
        .attendance-summary-groups.today-info,
        .attendance-summary-groups.meal-info { grid-template-columns: minmax(0, 1fr); }
        .attendance-summary-card.person { grid-column: 1 / -1; }
        .attendance-charts { grid-template-columns: minmax(0, 1fr); }
    }

    .student-section-title {
        color: var(--student-primary-dark);
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .student-section-title::after {
        background: linear-gradient(90deg, var(--student-primary), #73a6c2);
        border-radius: 999px;
        content: '';
        display: block;
        height: 4px;
        margin-top: 0.6rem;
        width: 48px;
    }

    .student-tab-grid {
        display: grid;
        gap: 1.15rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .student-tab-button {
        align-items: flex-start;
        background: linear-gradient(145deg, #fff 0%, #f8fbfd 100%);
        border: 1px solid var(--student-border);
        border-radius: 1.25rem;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        color: var(--student-text);
        display: flex;
        flex-direction: column;
        min-height: 178px;
        overflow: hidden;
        padding: 1.25rem;
        position: relative;
        text-decoration: none;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .student-tab-button::before {
        background: var(--tab-accent, var(--student-primary));
        content: '';
        height: 4px;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
    }

    .student-tab-button::after {
        color: #8aa0ad;
        content: '\F138';
        font-family: bootstrap-icons;
        font-size: 1.1rem;
        position: absolute;
        right: 1.15rem;
        top: 1.25rem;
        transition: transform 0.25s ease, color 0.25s ease;
    }

    .student-tab-button:hover,
    .student-tab-button:focus-visible {
        border-color: #7da8bf;
        box-shadow: 0 16px 28px rgba(38, 100, 142, 0.15);
        color: var(--student-primary-dark);
        outline: none;
        transform: translateY(-5px);
    }

    .student-tab-button:hover::after,
    .student-tab-button:focus-visible::after {
        color: var(--student-primary);
        transform: translateX(3px);
    }

    .student-tab-icon {
        align-items: center;
        border-radius: 0.75rem;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.4);
        display: inline-flex;
        font-size: 1.35rem;
        height: 44px;
        justify-content: center;
        margin-bottom: 0.8rem;
        width: 44px;
    }

    .student-tab-button.blue { --tab-accent: #26648E; }
    .student-tab-button.green { --tab-accent: #4b8c75; }
    .student-tab-button.orange { --tab-accent: #b97b36; }
    .student-tab-button.red { --tab-accent: #bd5a5a; }
    .student-tab-button.purple { --tab-accent: #687ba8; }
    .student-tab-button.teal { --tab-accent: #2f8f83; }
    .student-tab-button.cyan { --tab-accent: #2a8fb0; }

    .student-tab-icon.blue { background: #dcebf3; color: #26648E; }
    .student-tab-icon.green { background: #e1f0ea; color: #39755f; }
    .student-tab-icon.orange { background: #f5eadb; color: #9d6528; }
    .student-tab-icon.red { background: #f5e2e2; color: #a94949; }
    .student-tab-icon.purple { background: #e6e9f3; color: #586b98; }
    .student-tab-icon.teal { background: #dcf3f0; color: #2f8f83; }
    .student-tab-icon.cyan { background: #dcf0f7; color: #2a8fb0; }

    .student-tab-button strong {
        font-size: 0.98rem;
        margin-bottom: 0.35rem;
        padding-right: 1.5rem;
    }

    .student-tab-button small {
        color: var(--student-muted);
        line-height: 1.45;
    }

    .student-empty {
        background: #fff;
        border: 1px solid #fecaca;
        border-radius: 1rem;
        color: #991b1b;
        padding: 1.25rem;
    }

    @media (max-width: 992px) {
        .student-tab-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 576px) {
        .student-dashboard { padding: 1rem 1rem 2rem; }
        .student-dashboard-header { border-radius: 1.25rem; padding: 1.5rem; }
        .student-dashboard-header h1::before { height: 36px; width: 36px; }
        .student-hero { gap: 0.75rem; padding: 0.85rem 1rem; }
        .student-hero img { height: 84px; width: 84px; }
        .student-hero h1 { font-size: 1.05rem; }
        .student-tab-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .student-tab-button { min-height: 155px; padding: 1rem; }
    }

    /* ===== Simple look: การ์ดสีขาว ขอบบาง จัดชิดซ้าย ===== */
    .attendance-summary { box-shadow: none; padding: 1.1rem; }

    .attendance-summary-card {
        align-items: flex-start;
        background: #fff;
        border: 1px solid var(--student-border);
        justify-content: flex-start;
        min-height: 0;
        text-align: left;
    }

    .attendance-summary-card.highlight { background: #fffbeb; border-color: #f3d9a3; }

    .attendance-summary-card-label {
        color: var(--student-muted);
        font-size: 0.8rem;
        font-weight: 600;
    }

    .attendance-summary-card.person .attendance-summary-card-label { color: var(--student-muted); font-size: 0.8rem; }

    .time-chip {
        background: none;
        border: 0;
        color: var(--student-muted);
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0;
    }

    .time-chip i { display: none; }

    .dropoff-row { justify-content: flex-start; }
    .attendance-summary-card.person .dropoff-avatar { height: 52px; width: 52px; }
    .attendance-summary-card.person .dropoff-row .attendance-summary-card-value { font-size: 1.15rem; }
    .attendance-summary-card.person .dropoff-name { font-size: 0.8rem; }

    .attendance-summary-card.symptoms .attendance-summary-card-label {
        border-bottom: 0;
        color: var(--student-muted);
        font-size: 0.8rem;
        padding-bottom: 0;
    }

    .symptom-row-label { font-size: 0.8rem; }
    .chip { font-size: 0.85rem; }
    .chip-care { background: #eef2f6; color: #334155; }

    .attendance-subtitle { border-top: 0; margin-top: 1rem; padding-top: 0; }

    .meal-item { background: #fff; }
    .meal-badge {
        background: none;
        color: var(--student-primary-dark);
        flex-direction: row;
        gap: 0.4rem;
        padding: 0.75rem 0 0.75rem 1rem;
        width: auto;
    }
    .meal-badge i { color: var(--student-primary); font-size: 1.1rem; }
    .meal-badge strong { font-size: 0.95rem; }
    .meal-badge small { display: none; }
    .meal-text { font-size: 1rem; padding: 0.75rem 1rem 0.75rem 0.5rem; justify-content: flex-end; text-align: right; }
</style>

<main class="main-content">
    <div class="student-dashboard">
        <?php if ($child): ?>
            <div class="student-dashboard-header student-hero">
                <img src="<?= !empty($child['profile_image']) ? htmlspecialchars($child['profile_image']) : '../../../public/assets/images/avatar.png' ?>"
                     alt="รูปประจำตัวของ <?= htmlspecialchars(($child['firstname_th'] ?? '') . ' ' . ($child['lastname_th'] ?? '')) ?>">
                <div>
                    <h1><?= htmlspecialchars(($child['prefix_th'] ?? '') . ($child['firstname_th'] ?? '') . ' ' . ($child['lastname_th'] ?? '')) ?></h1>
                    <div class="student-meta">
                        <span><i class="bi bi-person-badge"></i><?= htmlspecialchars($child['studentid']) ?></span>
                        <span><i class="bi bi-people"></i><?= htmlspecialchars($child['child_group'] ?? '-') ?></span>
                        <span><i class="bi bi-door-open"></i>ห้อง <?= htmlspecialchars($child['classroom'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="student-dashboard-header">
                <h1>แดชบอร์ดข้อมูลเด็ก</h1>
                <p>เลือกหัวข้อที่ต้องการดูข้อมูลจากปุ่มด้านล่าง</p>
            </div>
        <?php endif; ?>

        <?php if (!$child): ?>
            <div class="student-empty">
                <i class="bi bi-exclamation-circle me-2"></i>
                ไม่พบข้อมูลเด็กของบัญชีนี้ กรุณาติดต่อผู้ดูแลระบบ
            </div>
        <?php else: ?>
            <div class="student-section-title">ข้อมูลวันนี้</div>
            <div class="attendance-summary">
                <div class="attendance-summary-groups today-info">
                        <div class="attendance-summary-card person">
                            <span class="attendance-summary-card-label"><i class="bi bi-box-arrow-in-right"></i> ผู้ส่ง</span>
                            <span class="time-chip"><i class="bi bi-clock"></i> สแกนบัตร <?= $todayAtt && $todayAtt['checkin_time'] !== '00:00' ? htmlspecialchars($todayAtt['checkin_time']) . ' น.' : '-' ?></span>
                            <?php renderGuardian($dropOff); ?>
                        </div>
                        <div class="attendance-summary-card person">
                            <span class="attendance-summary-card-label"><i class="bi bi-box-arrow-right"></i> ผู้รับ</span>
                            <span class="time-chip"><i class="bi bi-clock"></i> กลับบ้าน <?= !empty($todayAtt['checkout_time']) ? htmlspecialchars($todayAtt['checkout_time']) . ' น.' : '-' ?></span>
                            <?php renderGuardian($pickUp); ?>
                        </div>
                        <div class="attendance-summary-card">
                            <span class="attendance-summary-card-label"><i class="bi bi-thermometer-half"></i> อุณหภูมิ</span>
                            <span class="attendance-summary-card-value"><?= $todayAtt && $todayAtt['temperature'] !== null ? htmlspecialchars(number_format((float) $todayAtt['temperature'], 1)) . ' °C' : '-' ?></span>
                        </div>
                        <div class="attendance-summary-card symptoms <?= $symptomList ? 'highlight' : '' ?>">
                            <span class="attendance-summary-card-label"><i class="bi bi-heart-pulse"></i> อาการผิดปกติ</span>
                            <div class="symptom-rows">
                                <div class="symptom-row">
                                    <span class="symptom-row-label">อาการ</span>
                                    <span class="symptom-row-value">
                                        <?php if ($symptomList): ?>
                                            <?php foreach ($symptomList as $item): ?><span class="chip chip-warn"><?= htmlspecialchars($item) ?></span><?php endforeach; ?>
                                        <?php else: ?>
                                            <?= $todayAtt ? '<span class="chip chip-ok"><i class="bi bi-check-circle"></i> ไม่มีอาการ</span>' : '-' ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($careList): ?>
                                    <div class="symptom-row">
                                        <span class="symptom-row-label">การดูแล</span>
                                        <span class="symptom-row-value">
                                            <?php foreach ($careList as $item): ?><span class="chip chip-care"><?= htmlspecialchars($item) ?></span><?php endforeach; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($todayAtt['caretaker_name'])): ?>
                                    <div class="symptom-row">
                                        <span class="symptom-row-label">ผู้ดูแล</span>
                                        <span class="symptom-row-value"><?= htmlspecialchars($todayAtt['caretaker_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                </div>

                <div class="attendance-subtitle"><i class="bi bi-egg-fried"></i>รายการอาหารประจำวัน</div>
                <div class="attendance-summary-groups meal-info">
                        <?php foreach ($mealLabels as $slot => $meal): ?>
                            <div class="meal-item <?= $slot ?>">
                                <div class="meal-badge">
                                    <i class="bi <?= $mealIcons[$slot] ?>"></i>
                                    <strong><?= $meal['time'] ?></strong>
                                    <small><?= $meal['kind'] ?></small>
                                </div>
                                <div class="meal-text"><?= htmlspecialchars($menuToday[$slot] ?? '-') ?></div>
                            </div>
                        <?php endforeach; ?>
                </div>
            </div>

            <div class="student-section-title">เลือกดูข้อมูล</div>
            <div class="student-tab-grid" aria-label="เมนูข้อมูลเด็ก">
                <?php foreach ($viewTabs as $tab): ?>
                    <?php $tabHref = $tab['href'] ?? ('view_child.php?studentid=' . rawurlencode($studentid) . '&tab=' . rawurlencode($tab['id'])); ?>
                    <a class="student-tab-button <?= htmlspecialchars($tab['color']) ?>"
                       href="<?= htmlspecialchars($tabHref) ?>">
                        <span class="student-tab-icon <?= htmlspecialchars($tab['color']) ?>">
                            <i class="<?= htmlspecialchars($tab['icon']) ?>" aria-hidden="true"></i>
                        </span>
                        <strong><?= htmlspecialchars($tab['title']) ?></strong>
                        <small><?= htmlspecialchars($tab['description']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
