<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['doctor']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?> 
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>


<?php
$doctorName = $_SESSION['username'] ?? 'แพทย์';
?>

<style>
    .doctor-dashboard-page {
        background: #f5f8fc;
        color: #1e293b;
        min-height: 100vh;
    }

    .doctor-dashboard {
        margin: 0 auto;
        max-width: 1180px;
        padding: clamp(1.5rem, 4vw, 3rem) clamp(1rem, 3vw, 2.5rem) 3.5rem;
    }

    .doctor-hero {
        background:
            radial-gradient(circle at 86% 15%, rgba(255, 255, 255, 0.18) 0 7%, transparent 7.5%),
            radial-gradient(circle at 72% 110%, rgba(255, 255, 255, 0.12) 0 15%, transparent 15.5%),
            linear-gradient(120deg, #1e4564 0%, #26648e 52%, #4a91b2 100%);
        border-radius: 1.75rem;
        box-shadow: 0 10px 24px rgba(30, 69, 100, 0.16);
        color: #fff;
        overflow: hidden;
        padding: clamp(1.25rem, 3vw, 2.25rem);
        position: relative;
    }

    .doctor-hero::after {
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 50%;
        content: '';
        height: 280px;
        position: absolute;
        right: -95px;
        top: -145px;
        width: 280px;
    }

    .doctor-hero-content {
        align-items: center;
        display: flex;
        gap: 1.5rem;
        justify-content: space-between;
        position: relative;
        z-index: 1;
    }

    .doctor-hero-copy {
        max-width: 720px;
    }

    .doctor-eyebrow {
        align-items: center;
        color: rgba(255, 255, 255, 0.82);
        display: flex;
        font-size: 0.82rem;
        font-weight: 500;
        gap: 0.5rem;
        letter-spacing: 0.08em;
        margin-bottom: 0.8rem;
        text-transform: uppercase;
    }

    .doctor-eyebrow i {
        color: #bfdbfe;
    }

    .doctor-hero h1 {
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        font-weight: 700;
        letter-spacing: -0.04em;
        margin: 0 0 0.75rem;
    }

    .doctor-welcome-name {
        color: #fff;
    }

    .doctor-hero-description {
        color: rgba(255, 255, 255, 0.78);
        font-size: 1rem;
        line-height: 1.6;
        margin: 0 0 1rem;
        max-width: 620px;
    }

    .doctor-avatar {
        align-items: center;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 1.5rem;
        display: flex;
        flex: 0 0 90px;
        height: 90px;
        justify-content: center;
        width: 90px;
    }

    .doctor-avatar i {
        color: #fff;
        font-size: 2.7rem;
    }

    .doctor-section {
        background: #fff;
        border: 1px solid #dbe7ee;
        border-radius: 1.5rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
        margin-top: 1.5rem;
        padding: clamp(1.25rem, 3vw, 2rem);
    }

    .doctor-section-heading {
        align-items: end;
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .doctor-section-heading h2 {
        color: #1e5678;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }

    .doctor-section-heading p {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }

    .doctor-menu-grid {
        display: grid;
        gap: 1.5rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .doctor-menu-card {
        align-items: center;
        background: #fff;
        border: 1px solid #cfe1e8;
        border-radius: 1.25rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        color: #334155;
        display: flex;
        gap: 1rem;
        min-height: 180px;
        padding: 1.5rem;
        position: relative;
        text-decoration: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .doctor-menu-card::after {
        color: #94a3b8;
        content: '\F285';
        font-family: bootstrap-icons;
        font-size: 1.1rem;
        position: absolute;
        right: 1.25rem;
        top: 1.25rem;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    .doctor-menu-card:hover {
        border-color: #78afd0;
        box-shadow: 0 16px 32px rgba(30, 69, 100, 0.16);
        color: #334155;
        transform: translateY(-4px);
    }

    .doctor-menu-card:hover::after {
        color: #26648e;
        transform: translateX(3px);
    }

    .doctor-menu-icon {
        align-items: center;
        background: #eaf4fb;
        border-radius: 1rem;
        color: #26648e;
        display: inline-flex;
        flex: 0 0 74px;
        font-size: 2rem;
        height: 74px;
        justify-content: center;
        width: 74px;
    }

    .doctor-menu-card.oral-health .doctor-menu-icon {
        background: #fff1f2;
        color: #d35d70;
    }

    .doctor-menu-content {
        padding-right: 1.5rem;
    }

    .doctor-menu-content h3 {
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0 0 0.35rem;
    }

    .doctor-menu-content p {
        color: #64748b;
        font-size: 0.88rem;
        line-height: 1.55;
        margin: 0;
    }

    .doctor-info {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 1rem;
        color: #1e40af;
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
        padding: 0.9rem 1rem;
    }

    .doctor-info i {
        color: #2563eb;
        font-size: 1.1rem;
    }

    .doctor-info p {
        font-size: 0.88rem;
        margin: 0;
    }

    @media (max-width: 767px) {
        .doctor-hero-content {
            align-items: flex-start;
            flex-direction: column-reverse;
        }

        .doctor-avatar {
            border-radius: 1rem;
            flex-basis: 68px;
            height: 68px;
            width: 68px;
        }

        .doctor-avatar i {
            font-size: 2rem;
        }

        .doctor-hero h1 {
            font-size: 1.95rem;
        }

        .doctor-menu-grid {
            grid-template-columns: 1fr;
        }

        .doctor-section-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 0.35rem;
        }
    }
</style>

<main class="main-content doctor-dashboard-page">
    <div class="doctor-dashboard">
        <div class="doctor-hero">
            <div class="doctor-hero-content">
                <div class="doctor-hero-copy">
                    <div class="doctor-eyebrow">
                        <i class="fa-solid fa-shield-heart"></i>
                        พื้นที่ทำงานสำหรับแพทย์
                    </div>
                    <h1>
                        ยินดีต้อนรับ -
                        <span class="doctor-welcome-name"><?php echo htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8'); ?></span>
                    </h1>
                    <p class="doctor-hero-description">
                        ระบบตรวจสุขภาพเด็ก ณ ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย
                    </p>
                </div>
                <div class="doctor-avatar" aria-hidden="true">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
            </div>
        </div>

        <?php if (getUserRole() === 'doctor'): ?>
            <section class="doctor-section" aria-labelledby="doctor-menu-title">
                <div class="doctor-section-heading">
                    <h2 id="doctor-menu-title">เมนูสำหรับแพทย์</h2>
                    <p>เลือกบริการที่ต้องการใช้งาน</p>
                </div>

                <div class="doctor-menu-grid">
                    <a class="doctor-menu-card" href="/app/views/check_health_external/checklist_name.php">
                        <div class="doctor-menu-icon">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <div class="doctor-menu-content">
                            <h3>ตรวจสุขภาพเด็ก</h3>
                            <p>บันทึกและติดตามผลการตรวจสุขภาพเด็ก</p>
                        </div>
                    </a>

                    <a class="doctor-menu-card oral-health" href="/app/views/check_health_tooth/checklist_name.php">
                        <div class="doctor-menu-icon">
                            <i class="fa-solid fa-tooth"></i>
                        </div>
                        <div class="doctor-menu-content">
                            <h3>ตรวจสุขภาพช่องปาก</h3>
                            <p>บันทึกและติดตามผลการตรวจสุขภาพช่องปาก</p>
                        </div>
                    </a>
                </div>

                <div class="doctor-info">
                    <i class="fa-solid fa-circle-check"></i>
                    <p>เมนูนี้แสดงตามสิทธิ์การใช้งานของแพทย์ และข้อมูลจะถูกบันทึกเข้าสู่ระบบทันที</p>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>



</body>

</html>
