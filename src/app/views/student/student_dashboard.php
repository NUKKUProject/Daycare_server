<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['student']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>
<?php
$studentid = $_SESSION['username'] ?? '';
$child = $studentid !== '' ? getChildById($studentid) : false;

$viewTabs = [
    [
        'id' => 'profile',
        'icon' => 'bi bi-person-badge',
        'title' => 'ประวัติประจำตัว',
        'description' => 'ข้อมูลส่วนตัว ผู้ปกครอง และที่อยู่',
        'color' => 'blue',
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
        'title' => 'ตรวจร่างกาย',
        'description' => 'ผลการตรวจสุขภาพและการแพ้',
        'color' => 'red',
    ],
    [
        'id' => 'growth',
        'icon' => 'bi bi-graph-up',
        'title' => 'การเจริญเติบโต',
        'description' => 'ติดตามส่วนสูง น้ำหนัก และพัฒนาการ',
        'color' => 'purple',
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
        padding: 2rem 1.75rem 3.5rem;
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

    .student-summary {
        align-items: center;
        background: #fff;
        border: 1px solid var(--student-border);
        border-left: 4px solid var(--student-primary);
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        padding: 1.25rem;
        position: relative;
    }

    .student-summary img {
        border: 4px solid #fff;
        border-radius: 1rem;
        box-shadow: 0 5px 14px rgba(38, 100, 142, 0.2);
        height: 84px;
        object-fit: cover;
        width: 84px;
    }

    .student-summary h2 {
        color: var(--student-primary-dark);
        font-size: 1.25rem;
        margin: 0 0 0.35rem;
    }

    .student-summary p {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }

    .student-summary .student-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem 0.75rem;
        margin-top: 0.5rem;
    }

    .student-meta span {
        background: var(--student-primary-soft);
        border: 1px solid #cfe0eb;
        border-radius: 999px;
        color: #34566c;
        font-size: 0.82rem;
        padding: 0.28rem 0.65rem;
    }

    .student-meta i {
        color: var(--student-primary);
        margin-right: 0.2rem;
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
        grid-template-columns: repeat(5, minmax(0, 1fr));
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

    .student-tab-icon.blue { background: #dcebf3; color: #26648E; }
    .student-tab-icon.green { background: #e1f0ea; color: #39755f; }
    .student-tab-icon.orange { background: #f5eadb; color: #9d6528; }
    .student-tab-icon.red { background: #f5e2e2; color: #a94949; }
    .student-tab-icon.purple { background: #e6e9f3; color: #586b98; }

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
        .student-dashboard { padding: 1.25rem 1rem 2rem; }
        .student-dashboard-header { border-radius: 1.25rem; padding: 1.5rem; }
        .student-dashboard-header h1::before { height: 36px; width: 36px; }
        .student-summary { align-items: flex-start; }
        .student-summary img { height: 64px; width: 64px; }
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
        <div class="student-dashboard-header">
            <h1>แดชบอร์ดข้อมูลเด็ก</h1>
            <p>เลือกหัวข้อที่ต้องการดูข้อมูลจากปุ่มด้านล่าง</p>
        </div>

        <?php if (!$child): ?>
            <div class="student-empty">
                <i class="bi bi-exclamation-circle me-2"></i>
                ไม่พบข้อมูลเด็กของบัญชีนี้ กรุณาติดต่อผู้ดูแลระบบ
            </div>
        <?php else: ?>
            <div class="student-summary">
                <img src="<?= !empty($child['profile_image']) ? htmlspecialchars($child['profile_image']) : '../../../public/assets/images/avatar.png' ?>"
                     alt="รูปประจำตัวของ <?= htmlspecialchars(($child['firstname_th'] ?? '') . ' ' . ($child['lastname_th'] ?? '')) ?>">
                <div>
                    <h2><?= htmlspecialchars(($child['prefix_th'] ?? '') . ($child['firstname_th'] ?? '') . ' ' . ($child['lastname_th'] ?? '')) ?></h2>
                    <p>ข้อมูลของเด็กที่ผูกกับบัญชีผู้ปกครอง</p>
                    <div class="student-meta">
                        <span><i class="bi bi-person-badge"></i><?= htmlspecialchars($child['studentid']) ?></span>
                        <span><i class="bi bi-people"></i><?= htmlspecialchars($child['child_group'] ?? '-') ?></span>
                        <span><i class="bi bi-door-open"></i>ห้อง <?= htmlspecialchars($child['classroom'] ?? '-') ?></span>
                    </div>
                </div>
            </div>

            <div class="student-section-title">เลือกดูข้อมูล</div>
            <div class="student-tab-grid" aria-label="เมนูข้อมูลเด็ก">
                <?php foreach ($viewTabs as $tab): ?>
                    <a class="student-tab-button <?= htmlspecialchars($tab['color']) ?>"
                       href="view_child.php?studentid=<?= rawurlencode($studentid) ?>&amp;tab=<?= rawurlencode($tab['id']) ?>">
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
