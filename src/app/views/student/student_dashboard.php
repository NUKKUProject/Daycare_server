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
$menuToday = [];
$mealLabels = [
    'morning_snack' => 'อาหารว่างเช้า',
    'lunch' => 'อาหารกลางวัน',
    'afternoon_snack' => 'อาหารว่างบ่าย',
];

if ($child) {
    $pdo = getDatabaseConnection();

    $attStmt = $pdo->prepare("
        SELECT status, TO_CHAR(check_date, 'HH24:MI') AS checkin_time,
               temperature, symptoms, other_symptoms, dropped_off_by, dropped_off_detail
        FROM attendance
        WHERE student_id = :s AND DATE(check_date) = CURRENT_DATE
        ORDER BY check_date ASC LIMIT 1
    ");
    $attStmt->execute(['s' => $studentid]);
    $todayAtt = $attStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // ผู้มาส่ง: ชื่อ/รูปจากข้อมูลผู้ปกครองของเด็ก (กรณีอื่นๆ ใช้รายละเอียดที่ครูระบุ)
    $dropOff = null;
    if ($todayAtt && !empty($todayAtt['dropped_off_by'])) {
        $dropLabels = ['father' => 'พ่อ', 'mother' => 'แม่', 'relative' => 'ผู้ปกครอง/ผู้ดูแล', 'other' => 'อื่นๆ'];
        $type = $todayAtt['dropped_off_by'];
        $dropOff = ['label' => $dropLabels[$type] ?? $type, 'name' => '', 'image' => ''];
        if ($type === 'other') {
            $dropOff['name'] = $todayAtt['dropped_off_detail'] ?? '';
        } elseif (isset($dropLabels[$type])) {
            $gStmt = $pdo->prepare("SELECT {$type}_first_name AS first_name, {$type}_last_name AS last_name, {$type}_image AS image FROM children WHERE studentid = :s");
            $gStmt->execute(['s' => $studentid]);
            if ($g = $gStmt->fetch(PDO::FETCH_ASSOC)) {
                $dropOff['name'] = trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''));
                $dropOff['image'] = $g['image'] ?? '';
            }
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
    .student-dashboard-toggle {
        background: #26648E;
        border: 0;
        color: #fff;
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .student-dashboard-toggle:hover,
    .student-dashboard-toggle:focus-visible {
        background: #1E4F6F;
        color: #fff;
        transform: translateY(-1px);
    }

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

    .attendance-summary-total {
        align-items: center;
        display: flex;
        gap: 0.85rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--student-border);
    }

    .attendance-summary-total i {
        background: var(--student-primary-soft);
        border-radius: 0.85rem;
        color: var(--student-primary);
        font-size: 1.4rem;
        padding: 0.7rem;
    }

    .attendance-summary-total strong {
        color: var(--student-primary-dark);
        display: block;
        font-size: 1.35rem;
    }

    .attendance-summary-total span {
        color: var(--student-muted);
        font-size: 0.85rem;
    }

    .attendance-summary-groups {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .attendance-summary-card {
        background: var(--student-primary-soft);
        border-radius: 1rem;
        padding: 0.85rem;
        text-align: center;
    }

    .attendance-summary-card.highlight {
        background: #fff7e6;
        border: 1px solid #f3d9a3;
    }

    .attendance-summary-card-label {
        color: var(--student-text);
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
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

    .attendance-summary-groups.today-info { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .attendance-summary-groups.meal-info { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .attendance-summary-card-value { overflow-wrap: anywhere; }
    .dropoff-avatar {
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 3px 10px rgba(38, 100, 142, 0.25);
        display: block;
        height: 72px;
        margin: 0 auto 0.4rem;
        object-fit: cover;
        width: 72px;
    }

    @media (max-width: 768px) {
        .attendance-summary-groups,
        .attendance-summary-groups.today-info,
        .attendance-summary-groups.meal-info { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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
        .main-content:has(.student-dashboard) { padding-top: 0.25rem; }
        .student-dashboard { padding: 0.25rem 0.75rem 2rem; }
        .student-dashboard-toggle { margin: 0.5rem 0.75rem 0 !important; }
        .student-dashboard-header { border-radius: 1.25rem; padding: 1.5rem; }
        .student-dashboard-header h1::before { height: 36px; width: 36px; }
        .student-hero { gap: 0.75rem; padding: 0.85rem 1rem; }
        .student-hero img { height: 84px; width: 84px; }
        .student-hero h1 { font-size: 1.05rem; }
        .student-tab-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .student-tab-button { min-height: 155px; padding: 1rem; }
    }
</style>

<button class="btn student-dashboard-toggle d-md-none m-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
        aria-controls="sidebarMenu" aria-label="เปิดเมนู">
    <i class="bi bi-list"></i>
</button>

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
                <?php if (!$todayAtt): ?>
                    <div class="text-muted"><i class="bi bi-info-circle me-1"></i>วันนี้ยังไม่มีการสแกนบัตรเข้าศูนย์</div>
                <?php else: ?>
                    <div class="attendance-summary-groups today-info">
                        <div class="attendance-summary-card">
                            <span class="attendance-summary-card-label"><i class="bi bi-clock"></i> สแกนบัตรถึงศูนย์</span>
                            <span class="attendance-summary-card-value"><?= $todayAtt['checkin_time'] === '00:00' ? '-' : htmlspecialchars($todayAtt['checkin_time']) . ' น.' ?></span>
                        </div>
                        <div class="attendance-summary-card">
                            <span class="attendance-summary-card-label"><i class="bi bi-person-heart"></i> ใครมาส่ง</span>
                            <?php if ($dropOff): ?>
                                <?php if ($dropOff['image'] !== ''): ?>
                                    <img class="dropoff-avatar" src="<?= htmlspecialchars($dropOff['image']) ?>" alt="รูป<?= htmlspecialchars($dropOff['label']) ?>"
                                         onerror="this.src='../../../public/assets/images/avatar.png'">
                                <?php endif; ?>
                                <span class="attendance-summary-card-value"><?= htmlspecialchars($dropOff['label']) ?></span>
                                <?php if ($dropOff['name'] !== ''): ?>
                                    <small class="d-block text-muted"><?= htmlspecialchars($dropOff['name']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="attendance-summary-card-value">-</span>
                            <?php endif; ?>
                        </div>
                        <div class="attendance-summary-card">
                            <span class="attendance-summary-card-label"><i class="bi bi-thermometer-half"></i> อุณหภูมิ</span>
                            <span class="attendance-summary-card-value"><?= $todayAtt['temperature'] !== null ? htmlspecialchars(number_format((float) $todayAtt['temperature'], 1)) . ' °C' : '-' ?></span>
                        </div>
                        <div class="attendance-summary-card <?= $symptomList ? 'highlight' : '' ?>">
                            <span class="attendance-summary-card-label"><i class="bi bi-heart-pulse"></i> อาการผิดปกติ</span>
                            <span class="attendance-summary-card-value"><?= $symptomList ? htmlspecialchars(implode(', ', $symptomList)) : 'ไม่มีอาการ' ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="attendance-summary-total mt-3 mb-2 pb-2">
                    <i class="bi bi-egg-fried"></i>
                    <div><strong>รายการอาหารประจำวัน</strong></div>
                </div>
                <?php if (!$menuToday): ?>
                    <div class="text-muted">วันนี้ยังไม่มีการบันทึกรายการอาหาร</div>
                <?php else: ?>
                    <div class="attendance-summary-groups meal-info">
                        <?php foreach ($mealLabels as $slot => $label): ?>
                            <div class="attendance-summary-card">
                                <span class="attendance-summary-card-label"><?= $label ?></span>
                                <span class="attendance-summary-card-value"><?= htmlspecialchars($menuToday[$slot] ?? '-') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
