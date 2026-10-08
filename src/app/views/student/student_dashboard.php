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

// ผลตรวจฟันล่าสุดที่แพทย์ตรวจแล้ว (ผลที่ครูคัดกรองไว้ยังไม่แสดงให้ผู้ปกครอง)
$dental = null;
if ($child) {
    try {
        $dStmt = $pdo->prepare("
            SELECT h.id, h.total_teeth, h.decayed_teeth, h.teeth_status, h.urgency, h.doctor_name, h.oral_components,
                   h.missing_teeth_detail, h.other_treatment_detail, h.examined_at, h.updated_at,
                   h.decayed_teeth_positions::text AS positions, h.treatments::text AS treatments,
                   f.status AS followup_status, f.followup_date, f.note AS followup_note, f.by_role AS followup_by_role, f.ack_at AS parent_ack_at,
                   r.title AS round_title, r.academic_year
            FROM health_tooth_external h
            LEFT JOIN tooth_exam_rounds r ON r.id = h.round_id
            LEFT JOIN health_followups f ON f.source_type = 'dental' AND f.source_id = h.id
            WHERE h.student_id = :s AND h.exam_type = 'doctor'
            ORDER BY r.academic_year DESC NULLS LAST, r.round_no DESC NULLS LAST, h.id DESC
            LIMIT 1
        ");
        $dStmt->execute(['s' => $studentid]);
        $dental = $dStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('student_dashboard dental: ' . $e->getMessage());   // ยังไม่ได้รัน migration ก็ไม่ให้หน้าพัง
    }
}
if ($dental) {
    $posLabels = ['upper_front_teeth' => 'ฟันหน้าบน', 'upper_right_molar' => 'กรามขวาบน', 'lower_right_molar' => 'กรามขวาล่าง',
        'lower_front_teeth' => 'ฟันหน้าล่าง', 'upper_left_molar' => 'กรามซ้ายบน', 'lower_left_molar' => 'กรามซ้ายล่าง'];
    $treatLabels = ['filling' => 'อุดฟัน', 'fluoride' => 'เคลือบฟลูออไรด์', 'root_canal' => 'รักษาคลองรากฟัน',
        'fluoride_molar' => 'เคลือบหลุมร่องฟันที่ฟันกราม', 'crown' => 'ครอบฟัน', 'extraction' => 'ถอนฟัน', 'other' => 'อื่นๆ'];
    $positions = json_decode($dental['positions'] ?? '{}', true);
    $treatments = json_decode($dental['treatments'] ?? '[]', true);
    $dental['pos_list'] = [];
    foreach ($posLabels as $k => $label) {
        $n = (int) (($positions[$k] ?? 0));
        if ($n > 0) {
            $dental['pos_list'][] = $label . ' ' . $n . ' ซี่';
        }
    }
    $dental['treat_list'] = [];
    foreach (is_array($treatments) ? $treatments : [] as $code) {
        $label = $treatLabels[$code] ?? $code;
        if ($code === 'other' && !empty($dental['other_treatment_detail'])) {
            $label = 'อื่นๆ: ' . $dental['other_treatment_detail'];
        }
        $dental['treat_list'][] = $label;
    }
    // ข้อความอิสระของแพทย์ที่ไม่ได้อยู่ในรายการติ๊ก (กรณีพิมพ์ "อื่นๆ" แต่ไม่ได้ติ๊ก)
    if (!empty($dental['other_treatment_detail']) && !in_array('other', is_array($treatments) ? $treatments : [], true)) {
        $dental['treat_list'][] = 'อื่นๆ: ' . $dental['other_treatment_detail'];
    }
    // จำนวนฟันผุ > 0 ถือว่ามีฟันผุ แม้แพทย์ไม่ได้เลือกสภาพฟัน
    $dental['decay_count'] = max((int) ($dental['decayed_teeth'] ?? 0), is_array($positions) ? (int) array_sum(array_map('intval', $positions)) : 0);
    $dental['has_decay'] = $dental['decay_count'] > 0 || ($dental['teeth_status'] ?? '') === 'abnormal' || !empty($dental['treat_list']);
    $urgLabels = ['urgent' => ['ควรรักษาโดยด่วน', 'urgent'], 'preventable' => ['ผัดผ่อนได้ในระยะเวลาไม่นานนัก', 'soon'], 'not_urgent' => ['ไม่เร่งด่วน', 'calm']];
    $dental['urg'] = $urgLabels[$dental['urgency'] ?? ''] ?? null;
    $dental['needs_reply'] = $dental['has_decay'] && empty($dental['followup_status']);
    $dental['date'] = $dental['examined_at'] ?: substr((string) $dental['updated_at'], 0, 10);
}

// รายการในศูนย์รวม "สุขภาพและการติดตาม"
$hubItems = [];
if ($child) {
    // 1) สุขภาพช่องปาก
    if (!$dental) {
        $hubItems[] = ['icon' => 'fa-solid fa-tooth', 'tone' => 'gray', 'title' => 'สุขภาพช่องปาก', 'sub' => 'ยังไม่มีผลตรวจจากทันตแพทย์', 'badge' => 'ยังไม่มีผล', 'attn' => false, 'modal' => false, 'href' => 'health_tooth_history.php'];
    } elseif (!$dental['has_decay']) {
        // ผลปกติ ไม่ต้องแสดงในศูนย์รวม (ดูย้อนหลังได้ที่เมนูตรวจสุขภาพช่องปาก)
    } else {
        $fs = $dental['followup_status'] ?? '';
        $badge = ['' => 'ต้องแจ้งกลับ', 'acknowledged' => 'รับทราบแล้ว', 'scheduled' => 'นัดหมอ ' . thaiDateShort($dental['followup_date']), 'treated' => 'พาไปรักษาแล้ว'][$fs] ?? 'ต้องแจ้งกลับ';
        $tone = $fs === '' ? 'red' : ($fs === 'treated' ? 'green' : 'blue');
        $hubItems[] = ['icon' => 'fa-solid fa-tooth', 'tone' => $tone, 'title' => 'สุขภาพช่องปาก',
            'sub' => 'พบฟันผุ' . ($dental['decay_count'] > 0 ? ' ' . $dental['decay_count'] . ' ซี่' : '') . ($dental['urg'] ? ' · ' . $dental['urg'][0] : '') . ' · ' . thaiDateShort($dental['date']),
            'badge' => $badge, 'attn' => $fs === '', 'modal' => true];
    }

    // 2) สมุดสื่อสารประจำวัน (วันนี้)
    $nb = null;
    try {
        $nbStmt = $pdo->prepare("SELECT parent_updated_at, teacher_updated_at FROM daily_reports WHERE student_id = :s AND report_date = CURRENT_DATE");
        $nbStmt->execute(['s' => $studentid]);
        $nb = $nbStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $nb = null;   // ยังไม่ได้สร้างตารางสมุดสื่อสาร
    }
    if ($nb !== null) {
        if (!empty($nb['teacher_updated_at'])) {
            $nbSub = 'คุณครูบันทึกข้อมูลวันนี้แล้ว'; $nbBadge = 'ครูบันทึกแล้ว'; $nbTone = 'green'; $nbAttn = false;
        } elseif (!empty($nb['parent_updated_at'])) {
            $nbSub = 'ท่านกรอกแล้ว รอคุณครูบันทึก'; $nbBadge = 'ส่งแล้ว'; $nbTone = 'blue'; $nbAttn = false;
        } else {
            $nbSub = 'ส่งข้อมูลที่บ้านถึงครู และดูบันทึกของครู'; $nbBadge = 'วันนี้ยังไม่กรอก'; $nbTone = 'amber'; $nbAttn = true;
        }
        $hubItems[] = ['icon' => 'bi bi-journal-text', 'tone' => $nbTone, 'title' => 'สมุดสื่อสารประจำวัน', 'sub' => $nbSub, 'badge' => $nbBadge, 'attn' => $nbAttn, 'modal' => false, 'href' => 'daily_notebook.php'];
    }
}

function thaiDateShort(?string $d): string
{
    if (!$d) {
        return '';
    }
    $t = strtotime(substr($d, 0, 10));
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return $t ? (int) date('j', $t) . ' ' . $months[(int) date('n', $t)] . ' ' . ((int) date('Y', $t) + 543) : '';
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
        max-width: 1680px;
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

    /* แดชบอร์ด 2 คอลัมน์: ซ้าย = ข้อมูลวันนี้ / ขวา = สุขภาพและการติดตาม */
    .dash-layout { display: grid; gap: 1.25rem; grid-template-columns: minmax(0, 1fr) 360px; align-items: start; }
    .dash-main .attendance-summary-groups.today-info { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
    .dash-main .attendance-summary-groups.meal-info { grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
    .dash-main .attendance-summary-card.symptoms { grid-column: auto; }
    .dash-side { position: sticky; top: 76px; }
    @media (max-width: 1100px) {
        .dash-layout { grid-template-columns: minmax(0, 1fr); }
        .dash-side { position: static; }
    }

    .dental-card { background: #fff; border: 1px solid var(--student-border); border-radius: 1.25rem; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); overflow: hidden; margin-bottom: 1rem; }
    .dental-head { align-items: center; display: flex; gap: .6rem; padding: .85rem 1.1rem; border-bottom: 1px solid var(--student-border); }
    .dental-head i { color: var(--student-primary); font-size: 1.25rem; }
    .dental-head .t { font-weight: 700; color: var(--student-primary-dark); line-height: 1.2; }
    .dental-head .s { font-size: .75rem; color: var(--student-muted); }
    .dental-body { padding: 1rem 1.1rem; }
    .dental-banner { border-radius: .9rem; padding: .8rem 1rem; margin-bottom: .9rem; font-weight: 700; }
    .dental-banner small { display: block; font-weight: 500; opacity: .9; }
    .dental-banner.ok { background: #dcfce7; color: #15803d; }
    .dental-banner.urgent { background: #fee2e2; color: #b91c1c; }
    .dental-banner.soon { background: #ffedd5; color: #c2410c; }
    .dental-banner.calm { background: #fef3c7; color: #92400e; }
    .dental-banner.none { background: #f1f5f9; color: #475569; }
    .dental-sec { margin-bottom: .85rem; }
    .dental-sec .lb { font-size: .75rem; font-weight: 700; color: var(--student-muted); margin-bottom: .25rem; }
    .dental-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .dental-chip { border-radius: 999px; padding: .15rem .65rem; font-size: .85rem; font-weight: 700; background: #fde68a; color: #92400e; }
    .dental-chip.treat { background: #dbeafe; color: #1e40af; }
    .dental-note { background: #f8fafc; border-left: 4px solid var(--student-primary); border-radius: .5rem; padding: .6rem .8rem; font-size: .9rem; white-space: pre-wrap; overflow-wrap: anywhere; }
    .dental-note + .dental-note { margin-top: .4rem; }
    .dental-follow { border-top: 1px dashed var(--student-border); padding-top: .85rem; }
    .dental-follow .now { font-size: .88rem; margin-bottom: .6rem; }
    .dental-follow .now b { color: var(--student-primary-dark); }
    .hub-list { display: flex; flex-direction: column; gap: .65rem; margin-bottom: 1rem; }
    .hub-item { align-items: center; background: #fff; border: 1px solid var(--student-border); border-left: 5px solid var(--student-border); border-radius: 1rem;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .06); color: var(--student-text); cursor: pointer; display: flex; gap: .8rem; padding: .8rem 1rem; text-align: left;
        text-decoration: none; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; width: 100%; }
    .hub-item:hover { box-shadow: 0 12px 24px rgba(38, 100, 142, .15); color: var(--student-text); transform: translateY(-2px); }
    .hub-item.attn { border-left-color: #dc2626; }
    .hub-ic { align-items: center; border-radius: .8rem; display: flex; flex-shrink: 0; font-size: 1.25rem; height: 44px; justify-content: center; width: 44px; }
    .hub-ic.gray { background: #f1f5f9; color: #64748b; } .hub-ic.green { background: #dcfce7; color: #15803d; } .hub-ic.red { background: #fee2e2; color: #b91c1c; }
    .hub-ic.blue { background: #dbeafe; color: #1d4ed8; } .hub-ic.amber { background: #fef3c7; color: #b45309; }
    .hub-main { flex: 1; min-width: 0; }
    .hub-title { color: var(--student-primary-dark); font-weight: 700; line-height: 1.25; }
    .hub-sub { color: var(--student-muted); font-size: .8rem; line-height: 1.3; overflow-wrap: anywhere; }
    .hub-badge { border-radius: 999px; flex-shrink: 0; font-size: .74rem; font-weight: 700; padding: .15rem .65rem; white-space: nowrap; }
    .hub-badge.gray { background: #f1f5f9; color: #475569; } .hub-badge.green { background: #dcfce7; color: #15803d; } .hub-badge.red { background: #fee2e2; color: #b91c1c; }
    .hub-badge.blue { background: #dbeafe; color: #1d4ed8; } .hub-badge.amber { background: #fef3c7; color: #b45309; }
    .hub-go { color: #94a3b8; flex-shrink: 0; }
    .hub-empty { background: #f0fdf4; border: 1px dashed #86efac; border-radius: 1rem; color: #15803d; font-weight: 700; padding: 1rem; text-align: center; }
    .hub-empty i { margin-right: .35rem; }
    #dentalModal .dental-card { border: 0; box-shadow: none; margin: 0; border-radius: 0; }
    #dentalModal {
        --student-primary: #26648E; --student-primary-dark: #1E4F6F; --student-primary-soft: #ebf4fb;
        --student-text: #2c3e50; --student-muted: #64748b; --student-border: #d9e6ee;
    }
    #dentalModal .dental-head { background: #f4f9fd; }
    #dentalModal .dental-body { padding: 1.1rem 1.25rem 1.25rem; }

    /* ตัวเลือกแจ้งกลับศูนย์: การ์ดที่กดได้ชัดเจน */
    .dental-actions { display: grid; gap: .6rem; }
    .dental-actions .opt { align-items: center; background: #fff; border: 2px solid #cbd5e1; border-radius: 1rem; box-shadow: 0 2px 6px rgba(15, 23, 42, .06); color: #1E4F6F;
        cursor: pointer; display: flex; gap: .8rem; padding: .7rem .9rem; text-align: left; transition: border-color .15s, background-color .15s, transform .12s, box-shadow .15s; width: 100%; }
    .dental-actions .opt:hover { background: #f4f9fd; border-color: #26648E; box-shadow: 0 6px 16px rgba(38, 100, 142, .18); transform: translateY(-1px); }
    .dental-actions .opt:active { transform: scale(.99); }
    .dental-actions .opt .ic { align-items: center; border-radius: .8rem; display: flex; flex-shrink: 0; font-size: 1.2rem; height: 42px; justify-content: center; width: 42px; }
    .dental-actions .opt.ack .ic { background: #dbeafe; color: #1d4ed8; }
    .dental-actions .opt.sch .ic { background: #ede9fe; color: #6d28d9; }
    .dental-actions .opt.trt .ic { background: #dcfce7; color: #15803d; }
    .dental-actions .opt .tx { flex: 1; min-width: 0; }
    .dental-actions .opt .tx b { display: block; font-size: 1rem; line-height: 1.25; }
    .dental-actions .opt .tx small { color: #64748b; display: block; font-weight: 500; line-height: 1.3; }
    .dental-actions .opt .ring { align-items: center; border: 2px solid #94a3b8; border-radius: 50%; color: transparent; display: flex; flex-shrink: 0; font-size: .85rem; height: 26px; justify-content: center; width: 26px; }
    .dental-actions .opt:hover .ring { border-color: #26648E; }
    .dental-actions .opt.active { background: #f0fdf4; border-color: #16a34a; }
    .dental-actions .opt.active .ring { background: #16a34a; border-color: #16a34a; color: #fff; }
    .dental-follow .lb { color: #1E4F6F; font-size: .95rem; font-weight: 700; }
    /* กล่องกรอกหลังเลือก (SweetAlert) */
    .fu-popup { border-radius: 1.25rem !important; padding: 1.4rem 1.5rem 1.25rem !important; width: 30rem !important; max-width: 94vw; }
    .fu-popup .swal2-html-container { margin: 0 !important; padding: 0 !important; text-align: left; overflow: visible; }
    .fu-popup .swal2-actions { margin: 1.25rem 0 0 !important; width: 100%; gap: .6rem; }
    .fu-head { align-items: center; display: flex; gap: .8rem; margin-bottom: 1.1rem; }
    .fu-ic { align-items: center; border-radius: .9rem; display: flex; flex-shrink: 0; font-size: 1.4rem; height: 48px; justify-content: center; width: 48px; }
    .fu-ic.ack { background: #dbeafe; color: #1d4ed8; } .fu-ic.sch { background: #ede9fe; color: #6d28d9; } .fu-ic.trt { background: #dcfce7; color: #15803d; }
    .fu-title { color: #1E4F6F; font-size: 1.2rem; font-weight: 700; line-height: 1.2; }
    .fu-sub { color: #64748b; font-size: .85rem; line-height: 1.3; }
    .fu-field { margin-bottom: 1rem; }
    .fu-field > label { color: #334155; display: block; font-size: .85rem; font-weight: 700; margin-bottom: .35rem; }
    .fu-field .opt { color: #94a3b8; font-weight: 500; }
    .fu-field input[type=date], .fu-field textarea { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: .8rem; color: #0f2460; font-size: 1rem; padding: .55rem .75rem; width: 100%; }
    .fu-field input[type=date]:focus, .fu-field textarea:focus { background: #fff; border-color: #26648E; box-shadow: 0 0 0 4px rgba(38, 100, 142, .12); outline: none; }
    .fu-field textarea { resize: vertical; }
    .fu-quick { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .5rem; }
    .fu-quick button { background: #fff; border: 1.5px solid #cbd5e1; border-radius: 999px; color: #1E4F6F; font-size: .82rem; font-weight: 700; padding: .2rem .8rem; }
    .fu-quick button:hover, .fu-quick button.on { background: #26648E; border-color: #26648E; color: #fff; }
    .fu-thai { color: #64748b; font-size: .82rem; margin-top: .35rem; }
    .fu-count { color: #94a3b8; font-size: .75rem; text-align: right; }
    .fu-confirm { background: #15803d; border: 0; border-radius: .8rem; color: #fff; font-size: 1rem; font-weight: 700; padding: .65rem 1.4rem; }
    .fu-confirm:hover { background: #166534; }
    .fu-cancel { background: #fff; border: 2px solid #cbd5e1; border-radius: .8rem; color: #475569; font-size: 1rem; font-weight: 700; padding: .6rem 1.2rem; }
    .fu-cancel:hover { background: #f1f5f9; }
    .dental-alert { border-radius: 1rem; padding: .8rem 1rem; margin-bottom: 1rem; display: flex; gap: .75rem; align-items: center; background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; }
    .dental-alert a { margin-left: auto; font-weight: 700; color: #9a3412; white-space: nowrap; }

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
            <?php if ($dental && $dental['needs_reply']): ?>
                <div class="dental-alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    <div><b>ตรวจพบฟันผุ<?= $dental['decay_count'] > 0 ? ' ' . (int) $dental['decay_count'] . ' ซี่' : '' ?></b> — กรุณาแจ้งศูนย์ว่ารับทราบหรือพาไปพบทันตแพทย์แล้ว</div>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#dentalModal">ดูรายละเอียด <i class="bi bi-chevron-right"></i></a>
                </div>
            <?php endif; ?>

            <div class="dash-layout">
            <div class="dash-main">
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
            </div><!-- /dash-main -->

            <aside class="dash-side" id="healthHub">
                <div class="student-section-title">สุขภาพและการติดตาม</div>
                <div class="hub-list">
                    <?php foreach ($hubItems as $it): ?>
                        <?php $isModal = !empty($it['modal']); ?>
                        <<?= $isModal ? 'button type="button" data-bs-toggle="modal" data-bs-target="#dentalModal"' : 'a href="' . htmlspecialchars($it['href']) . '"' ?> class="hub-item<?= $it['attn'] ? ' attn' : '' ?>">
                            <span class="hub-ic <?= $it['tone'] ?>"><i class="<?= htmlspecialchars($it['icon']) ?>"></i></span>
                            <span class="hub-main">
                                <span class="hub-title d-block"><?= htmlspecialchars($it['title']) ?></span>
                                <span class="hub-sub d-block"><?= htmlspecialchars($it['sub']) ?></span>
                            </span>
                            <span class="hub-badge <?= $it['tone'] ?>"><?= htmlspecialchars($it['badge']) ?></span>
                            <i class="bi bi-chevron-right hub-go"></i>
                        </<?= $isModal ? 'button' : 'a' ?>>
                    <?php endforeach; ?>
                    <?php if (!$hubItems): ?>
                        <div class="hub-empty"><i class="bi bi-check-circle-fill"></i> ไม่มีเรื่องที่ต้องติดตาม</div>
                    <?php endif; ?>
                </div>
            </aside>
            </div><!-- /dash-layout -->

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


<?php if ($dental): ?>
<!-- รายละเอียดสุขภาพช่องปาก + แจ้งกลับศูนย์ -->
<div class="modal fade" id="dentalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:1.25rem;overflow:hidden;">
                <div class="dental-card">
                    <div class="dental-head">
                        <i class="fa-solid fa-tooth"></i>
                        <div>
                            <div class="t">สุขภาพช่องปาก</div>
                            <?php if ($dental): ?>
                                <div class="s">ตรวจโดย <?= htmlspecialchars($dental['doctor_name'] ?: 'ทันตแพทย์') ?> · <?= htmlspecialchars(thaiDateShort($dental['date'])) ?><?= !empty($dental['round_title']) ? ' · ' . htmlspecialchars($dental['academic_year'] . ' ' . $dental['round_title']) : '' ?></div>
                            <?php else: ?>
                                <div class="s">ผลตรวจจากทันตแพทย์</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dental-body">
                        <?php if (!$dental): ?>
                            <div class="dental-banner none">ยังไม่มีผลตรวจช่องปากจากทันตแพทย์<small>เมื่อทันตแพทย์ตรวจแล้ว ผลจะแสดงที่นี่</small></div>
                        <?php elseif (!$dental['has_decay']): ?>
                            <div class="dental-banner ok"><i class="bi bi-check-circle-fill me-1"></i>ฟันปกติ ไม่พบฟันผุ<small>ฟันทั้งหมด <?= (int) $dental['total_teeth'] ?> ซี่</small></div>
                        <?php else: ?>
                            <div class="dental-banner <?= htmlspecialchars($dental['urg'][1] ?? 'calm') ?>">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>พบฟันผุ<?= $dental['decay_count'] > 0 ? ' ' . (int) $dental['decay_count'] . ' ซี่' : '' ?>
                                <small><?= $dental['urg'] ? htmlspecialchars($dental['urg'][0]) : 'ควรพาไปพบทันตแพทย์' ?><?= (int) $dental['total_teeth'] > 0 ? ' · ฟันทั้งหมด ' . (int) $dental['total_teeth'] . ' ซี่' : '' ?></small>
                            </div>
                            <?php if ($dental['pos_list']): ?>
                                <div class="dental-sec"><div class="lb">ตำแหน่งที่พบ</div>
                                    <div class="dental-chips"><?php foreach ($dental['pos_list'] as $x): ?><span class="dental-chip"><?= htmlspecialchars($x) ?></span><?php endforeach; ?></div></div>
                            <?php endif; ?>
                            <?php if ($dental['treat_list']): ?>
                                <div class="dental-sec"><div class="lb">การรักษาที่แนะนำ</div>
                                    <div class="dental-chips"><?php foreach ($dental['treat_list'] as $x): ?><span class="dental-chip treat"><?= htmlspecialchars($x) ?></span><?php endforeach; ?></div></div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($dental && (!empty($dental['oral_components']) || !empty($dental['missing_teeth_detail']))): ?>
                            <div class="dental-sec"><div class="lb">หมายเหตุจากทันตแพทย์</div>
                                <?php if (!empty($dental['oral_components'])): ?><div class="dental-note"><b>ช่องปาก:</b> <?= htmlspecialchars($dental['oral_components']) ?></div><?php endif; ?>
                                <?php if (!empty($dental['missing_teeth_detail'])): ?><div class="dental-note"><?= htmlspecialchars($dental['missing_teeth_detail']) ?></div><?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($dental && $dental['has_decay']): ?>
                            <div class="dental-follow">
                                <div class="lb mb-1">แจ้งกลับศูนย์ — เลือกสิ่งที่ต้องการแจ้ง</div>
                                <div class="now">
                                    <?php
                                    $fs = $dental['followup_status'] ?? '';
                                    if ($fs === 'treated') {
                                        echo '<b>พาไปรักษาแล้ว</b> ' . htmlspecialchars(thaiDateShort($dental['followup_date']));
                                    } elseif ($fs === 'scheduled') {
                                        echo '<b>นัดหมอแล้ว</b> วันที่ ' . htmlspecialchars(thaiDateShort($dental['followup_date']));
                                    } elseif ($fs === 'acknowledged') {
                                        echo '<b>รับทราบแล้ว</b>';
                                    } else {
                                        echo '<span class="text-danger fw-bold">ยังไม่ได้แจ้งกลับ</span>';
                                    }
                                    if ($fs !== '' && ($dental['followup_by_role'] ?? '') === 'center') {
                                        echo '<div class="text-muted small mt-1"><i class="bi bi-building me-1"></i>ศูนย์เป็นผู้บันทึกให้</div>';
                                    }
                                    if (!empty($dental['followup_note'])) {
                                        echo '<div class="text-muted small mt-1">' . htmlspecialchars($dental['followup_note']) . '</div>';
                                    }
                                    ?>
                                </div>
                                <div class="dental-actions">
                                    <button type="button" data-follow="acknowledged" class="opt ack<?= $fs === 'acknowledged' ? ' active' : '' ?>">
                                        <span class="ic"><i class="bi bi-hand-thumbs-up"></i></span>
                                        <span class="tx"><b>รับทราบ</b><small>แจ้งศูนย์ว่าท่านทราบผลตรวจแล้ว</small></span>
                                        <span class="ring"><i class="bi bi-check-lg"></i></span>
                                    </button>
                                    <button type="button" data-follow="scheduled" class="opt sch<?= $fs === 'scheduled' ? ' active' : '' ?>">
                                        <span class="ic"><i class="bi bi-calendar-event"></i></span>
                                        <span class="tx"><b>นัดหมอแล้ว</b><small>ระบุวันที่นัดพบทันตแพทย์</small></span>
                                        <span class="ring"><i class="bi bi-check-lg"></i></span>
                                    </button>
                                    <button type="button" data-follow="treated" class="opt trt<?= $fs === 'treated' ? ' active' : '' ?>">
                                        <span class="ic"><i class="bi bi-check2-circle"></i></span>
                                        <span class="tx"><b>พาไปรักษาแล้ว</b><small>ระบุวันที่พาไปรักษา</small></span>
                                        <span class="ring"><i class="bi bi-check-lg"></i></span>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="mt-3"><a href="health_tooth_history.php" class="small">ดูประวัติตรวจช่องปากทั้งหมด <i class="bi bi-arrow-right"></i></a></div>
                    </div>
                </div>

            <div class="modal-footer py-2"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ปิด</button></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($dental && $dental['has_decay']): ?>
<script>
    (function () {
        const RECORD_ID = <?= (int) $dental['id'] ?>;
        const META = {
            acknowledged: { title: 'รับทราบผลตรวจ', sub: 'แจ้งศูนย์ว่าท่านทราบผลตรวจฟันแล้ว', cls: 'ack', icon: 'bi-hand-thumbs-up', ph: 'เช่น จะพาไปพบทันตแพทย์เร็วๆ นี้', dateLabel: '', quick: [] },
            scheduled: { title: 'นัดหมอแล้ว', sub: 'ระบุวันที่นัดพบทันตแพทย์ ศูนย์จะได้ช่วยติดตาม', cls: 'sch', icon: 'bi-calendar-event', ph: 'เช่น นัดที่โรงพยาบาล... เวลา...', dateLabel: 'วันที่นัดหมอ', quick: [['วันนี้', 0], ['พรุ่งนี้', 1], ['อีก 1 สัปดาห์', 7]] },
            treated: { title: 'พาไปรักษาแล้ว', sub: 'ระบุวันที่พาลูกไปรักษา และผลที่ทันตแพทย์แจ้ง', cls: 'trt', icon: 'bi-check2-circle', ph: 'เช่น อุดฟันเรียบร้อย ทันตแพทย์แนะนำให้...', dateLabel: 'วันที่พาไปรักษา', quick: [['วันนี้', 0], ['เมื่อวาน', -1], ['3 วันก่อน', -3]] }
        };
        const fmt = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;   // วันที่ตามเวลาเครื่อง ไม่ใช่ UTC
        const shift = (n) => { const d = new Date(); d.setDate(d.getDate() + n); return fmt(d); };
        const thai = (v) => (v ? new Date(v + 'T00:00:00').toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '');
        document.querySelectorAll('[data-follow]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const status = btn.dataset.follow;
                const m = META[status];
                const needDate = status !== 'acknowledged';
                // ปิดหน้าต่างรายละเอียดชั่วคราว ไม่งั้น Bootstrap แย่งโฟกัสจนพิมพ์ในกล่องของ SweetAlert ไม่ได้
                const modalEl = document.getElementById('dentalModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
                const today = fmt(new Date());
                const r = await Swal.fire({
                    html: `<div class="fu-head"><span class="fu-ic ${m.cls}"><i class="bi ${m.icon}"></i></span>
                            <div><div class="fu-title">${m.title}</div><div class="fu-sub">${m.sub}</div></div></div>` +
                        (needDate ? `<div class="fu-field"><label for="swDate">${m.dateLabel}</label>
                            <input id="swDate" type="date" value="${today}" ${status === 'treated' ? `max="${today}"` : ''}>
                            <div class="fu-quick">${m.quick.map(([t, n]) => `<button type="button" data-off="${n}">${t}</button>`).join('')}</div>
                            <div class="fu-thai" id="swThai"></div></div>` : '') +
                        `<div class="fu-field"><label for="swNote">ข้อความถึงศูนย์ <span class="opt">(ไม่บังคับ)</span></label>
                            <textarea id="swNote" rows="3" maxlength="300" placeholder="${m.ph}"></textarea>
                            <div class="fu-count"><span id="swCount">0</span> / 300</div></div>`,
                    showCancelButton: true, buttonsStyling: false, reverseButtons: true,
                    confirmButtonText: '<i class="bi bi-send me-1"></i>ส่งให้ศูนย์', cancelButtonText: 'ยกเลิก',
                    customClass: { popup: 'fu-popup', confirmButton: 'fu-confirm', cancelButton: 'fu-cancel' },
                    didOpen: () => {
                        const date = document.getElementById('swDate'), thaiBox = document.getElementById('swThai');
                        const syncDate = () => {
                            if (!date) return;
                            thaiBox.textContent = date.value ? 'ตรงกับ' + thai(date.value) : '';
                            document.querySelectorAll('.fu-quick button').forEach((b) => b.classList.toggle('on', shift(+b.dataset.off) === date.value));
                        };
                        if (date) {
                            date.addEventListener('input', syncDate);
                            document.querySelectorAll('.fu-quick button').forEach((b) => b.addEventListener('click', () => { date.value = shift(+b.dataset.off); syncDate(); }));
                            syncDate();
                        }
                        const note = document.getElementById('swNote');
                        note.addEventListener('input', () => { document.getElementById('swCount').textContent = note.value.length; });
                    },
                    preConfirm: () => {
                        const date = needDate ? document.getElementById('swDate').value : '';
                        if (needDate && !date) { Swal.showValidationMessage('กรุณาระบุวันที่'); return false; }
                        return { date, note: document.getElementById('swNote').value.trim() };
                    }
                });
                if (!r.isConfirmed) { modal.show(); return; }
                try {
                    const res = await fetch('../../include/function/health_followup_api.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ type: 'dental', id: RECORD_ID, status, date: r.value.date, note: r.value.note })
                    });
                    const data = await res.json();
                    if (data.status !== 'success') throw new Error(data.message || 'บันทึกไม่สำเร็จ');
                    await Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                    location.reload();
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message, confirmButtonText: 'ตกลง' }).then(() => modal.show());
                }
            });
        });
    })();
</script>
<?php endif; ?>

</body>
</html>
