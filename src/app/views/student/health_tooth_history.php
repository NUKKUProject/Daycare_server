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
?>

<style>
    .th-page {
        --th-primary: #26648E;
        --th-primary-dark: #1E4F6F;
        --th-border: #d9e6ee;
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem 1.75rem 3.5rem;
    }

    .th-header {
        align-items: center;
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .th-header h1 {
        color: var(--th-primary-dark);
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
    }

    .th-back {
        align-items: center;
        background: #fff;
        border: 1px solid var(--th-border);
        border-radius: 0.75rem;
        color: var(--th-primary-dark);
        display: inline-flex;
        gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .th-back:hover { background: #ebf4fb; color: var(--th-primary-dark); }

    .th-record-card {
        background: #fff;
        border: 1px solid var(--th-border);
        border-left: 5px solid var(--th-primary);
        border-radius: 1.1rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        margin-bottom: 1rem;
        padding: 1.1rem 1.4rem;
    }

    .th-record-top {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: space-between;
    }

    .th-record-title { color: var(--th-primary-dark); font-weight: 700; }

    .th-badge {
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.25rem 0.7rem;
    }

    .th-badge.checked { background: #e6f7ec; color: #1e7e42; }
    .th-badge.pending { background: #fff4e0; color: #a15c00; }

    .th-record-meta { color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; }
    .th-record-actions { margin-top: 0.8rem; }

    .th-btn-view {
        background: var(--th-primary);
        border: none;
        border-radius: 0.65rem;
        color: #fff;
        font-size: 0.85rem;
        padding: 0.45rem 0.9rem;
    }

    .th-btn-view:hover { background: var(--th-primary-dark); }

    .th-empty {
        background: #fff;
        border: 1px solid var(--th-border);
        border-radius: 1rem;
        color: #64748b;
        padding: 2rem;
        text-align: center;
    }

    /* ===== รายละเอียดใน SweetAlert modal ===== */
    .th-detail { text-align: left; }

    .th-detail-banner {
        align-items: center;
        background: linear-gradient(135deg, #0e5c68 0%, #1f8fa3 100%);
        border-radius: 0.9rem;
        color: #fff;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 0.8rem;
        margin-bottom: 1rem;
        padding: 0.9rem 1.1rem;
    }

    .th-banner-title { font-size: 1rem; font-weight: 700; width: 100%; }

    .th-banner-chip {
        background: rgba(255,255,255,0.15);
        border-radius: 999px;
        font-size: 0.78rem;
        padding: 0.25rem 0.7rem;
    }

    .th-detail-section {
        background: #fff;
        border: 1px solid #eef2f7;
        border-left: 4px solid var(--th-accent, var(--th-primary));
        border-radius: 0.9rem;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        margin-bottom: 0.85rem;
        padding: 0.9rem 1.1rem;
    }

    .th-detail-section h6 {
        align-items: center;
        color: var(--th-primary-dark);
        display: flex;
        font-size: 0.88rem;
        font-weight: 700;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .th-detail-section h6 .th-icon {
        align-items: center;
        background: var(--th-accent-soft, #ebf4fb);
        border-radius: 0.6rem;
        color: var(--th-accent, var(--th-primary));
        display: inline-flex;
        font-size: 0.85rem;
        height: 28px;
        justify-content: center;
        width: 28px;
    }

    .th-detail-grid {
        display: grid;
        gap: 0.6rem;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }

    .th-detail-item { background: #f8fbfd; border-radius: 0.65rem; padding: 0.5rem 0.7rem; }
    .th-detail-item label { color: #94a3b8; display: block; font-size: 0.72rem; }
    .th-detail-item div { color: var(--th-primary-dark); font-size: 0.95rem; font-weight: 700; }

    .th-pill {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.78rem;
        font-weight: 600;
        gap: 0.3rem;
        padding: 0.2rem 0.65rem;
    }

    .th-pill::before { border-radius: 50%; content: ''; height: 6px; width: 6px; }
    .th-pill.good { background: #e6f7ec; color: #1e7e42; }
    .th-pill.good::before { background: #22c55e; }
    .th-pill.warn { background: #fdecec; color: #c0392b; }
    .th-pill.warn::before { background: #ef4444; }
    .th-pill.info { background: #e6f4fb; color: #1e6ea3; }
    .th-pill.info::before { background: #3b9fd4; }

    .th-position-grid {
        display: grid;
        gap: 0.6rem;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    }

    .th-position-item { border-radius: 0.75rem; padding: 0.6rem; text-align: center; }
    .th-position-item.decayed { background: #fdecec; }
    .th-position-item.clean { background: #e6f7ec; }
    .th-position-item strong { display: block; font-size: 0.95rem; }
    .th-position-item span { color: #64748b; font-size: 0.75rem; }

    .th-treatment-list { display: flex; flex-wrap: wrap; gap: 0.4rem; }

    .th-treatment-chip {
        background: #e6f4fb;
        border-radius: 999px;
        color: #1e6ea3;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.25rem 0.7rem;
    }
</style>

<body>
    <main class="main-content">
        <div class="th-page">
            <div class="th-header">
                <a class="th-back" href="student_dashboard.php"><i class="bi bi-arrow-left"></i> กลับ</a>
                <h1><i class="fa-solid fa-tooth me-2"></i>ประวัติการตรวจสุขภาพช่องปากจากทันตแพทย์</h1>
            </div>

            <?php if (!$child): ?>
                <div class="th-empty">ไม่พบข้อมูลเด็กของบัญชีนี้ กรุณาติดต่อผู้ดูแลระบบ</div>
            <?php else: ?>
                <div id="thRecordList">
                    <div class="th-empty"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...</div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        let thRecords = [];

        const thTreatmentLabels = {
            filling: 'อุดฟัน',
            fluoride: 'เคลือบฟลูออไรด์',
            root_canal: 'รักษาคลองรากฟัน',
            fluoride_molar: 'เคลือบหลุมร่องฟันที่ฟันกราม',
            crown: 'ครอบฟัน',
            extraction: 'ถอนฟัน',
            other: 'อื่นๆ'
        };

        const thPositionLabels = {
            upper_front_teeth: 'ฟันหน้าบน',
            upper_right_molar: 'ฟันกรามบนขวา',
            upper_left_molar: 'ฟันกรามบนซ้าย',
            lower_front_teeth: 'ฟันหน้าล่าง',
            lower_right_molar: 'ฟันกรามล่างขวา',
            lower_left_molar: 'ฟันกรามล่างซ้าย'
        };

        function thEscapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        function thParseJson(value, fallback) {
            fallback = fallback || {};
            if (value && typeof value === 'object') return value;
            if (!value) return fallback;
            try { return JSON.parse(value) || fallback; } catch (e) { return fallback; }
        }

        function thFormatDate(str) {
            if (!str) return '-';
            const d = new Date(str);
            if (isNaN(d)) return '-';
            return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        function thUrgencyPill(urgency) {
            if (urgency === 'urgent') return '<span class="th-pill warn">ควรได้รับการรักษาโดยด่วน</span>';
            if (urgency === 'not_urgent') return '<span class="th-pill good">ไม่เร่งด่วน</span>';
            if (urgency === 'preventable') return '<span class="th-pill info">ป้องกันได้</span>';
            return '<span class="th-pill info">ไม่ระบุ</span>';
        }

        function thShowDetail(index) {
            const r = thRecords[index];
            const positions = thParseJson(r.decayed_teeth_positions);
            const treatments = thParseJson(r.treatments, []);
            const hasDecay = r.teeth_status === 'abnormal';
            const age = `${r.age_year || 0} ปี ${r.age_month || 0} เดือน ${r.age_day || 0} วัน`;

            const positionsHtml = Object.keys(thPositionLabels).map(key => {
                const count = positions[key] || 0;
                return `
                    <div class="th-position-item ${count > 0 ? 'decayed' : 'clean'}">
                        <strong>${count > 0 ? count + ' ซี่' : 'ไม่มี'}</strong>
                        <span>${thPositionLabels[key]}</span>
                    </div>`;
            }).join('');

            const treatmentsHtml = Array.isArray(treatments) && treatments.length > 0
                ? '<div class="th-treatment-list">' + treatments.map(t =>
                    `<span class="th-treatment-chip">${thEscapeHtml(thTreatmentLabels[t] || t)}</span>`
                  ).join('') + '</div>'
                : '<div style="color:#94a3b8;font-size:0.85rem;">ไม่มีรายการรักษาที่จำเป็น</div>';

            const html = `
                <div class="th-detail">
                    <div class="th-detail-banner">
                        <div class="th-banner-title"><i class="bi bi-mortarboard me-1"></i>ปีการศึกษา ${thEscapeHtml(r.academic_year || '-')}</div>
                        <span class="th-banner-chip"><i class="bi bi-person-arms-up me-1"></i>อายุ ${thEscapeHtml(age)}</span>
                        <span class="th-banner-chip">${r.doctor_name ? '<i class="bi bi-person-badge me-1"></i>ทันตแพทย์: ' + thEscapeHtml(r.doctor_name) : '<i class="bi bi-hourglass-split me-1"></i>รอทันตแพทย์ตรวจ'}</span>
                    </div>

                    <div class="th-detail-section" style="--th-accent:#1f8fa3;--th-accent-soft:#e6f4fb;">
                        <h6><span class="th-icon"><i class="fa-solid fa-tooth"></i></span>สภาพฟันโดยรวม</h6>
                        <div class="th-detail-grid">
                            <div class="th-detail-item"><label>จำนวนฟันทั้งหมด</label><div>${thEscapeHtml(r.total_teeth ?? '-')} ซี่</div></div>
                            <div class="th-detail-item"><label>จำนวนฟันผุ</label><div>${thEscapeHtml(r.decayed_teeth ?? '-')} ซี่</div></div>
                        </div>
                        <div style="margin-top:0.7rem;">
                            <span class="th-pill ${hasDecay ? 'warn' : 'good'}">${hasDecay ? 'มีฟันผุ' : 'ยังไม่มีฟันผุ'}</span>
                            ${r.missing_teeth_detail ? '<div style="margin-top:0.5rem;color:#64748b;font-size:0.85rem;">' + thEscapeHtml(r.missing_teeth_detail) + '</div>' : ''}
                        </div>
                    </div>

                    ${hasDecay ? `
                    <div class="th-detail-section" style="--th-accent:#c0392b;--th-accent-soft:#fdecec;">
                        <h6><span class="th-icon"><i class="bi bi-geo-alt"></i></span>ตำแหน่งฟันผุ</h6>
                        <div class="th-position-grid">${positionsHtml}</div>
                    </div>` : ''}

                    ${r.oral_components ? `
                    <div class="th-detail-section" style="--th-accent:#7c5cbf;--th-accent-soft:#f1ecfb;">
                        <h6><span class="th-icon"><i class="bi bi-droplet-half"></i></span>ส่วนประกอบของช่องปาก (เหงือก/ลิ้น/เพดาน)</h6>
                        <div style="color:#334155;font-size:0.9rem;">${thEscapeHtml(r.oral_components)}</div>
                    </div>` : ''}

                    <div class="th-detail-section" style="--th-accent:#26648E;--th-accent-soft:#ebf4fb;">
                        <h6><span class="th-icon"><i class="bi bi-tools"></i></span>การรักษาที่จำเป็น</h6>
                        ${treatmentsHtml}
                        ${r.other_treatment_detail ? '<div style="margin-top:0.5rem;color:#64748b;font-size:0.85rem;">อื่นๆ: ' + thEscapeHtml(r.other_treatment_detail) + '</div>' : ''}
                    </div>

                    <div class="th-detail-section" style="--th-accent:#c99a2e;--th-accent-soft:#fff9e6;margin-bottom:0;">
                        <h6><span class="th-icon"><i class="bi bi-clock"></i></span>ความเร่งด่วนในการรักษา</h6>
                        ${thUrgencyPill(r.urgency)}
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'รายละเอียดการตรวจสุขภาพช่องปาก',
                html: html,
                width: window.innerWidth < 768 ? '95%' : '650px',
                showCloseButton: true,
                showConfirmButton: false,
                heightAuto: false
            });
        }

        function thRenderList(records) {
            const container = document.getElementById('thRecordList');
            if (!records || records.length === 0) {
                container.innerHTML = '<div class="th-empty"><i class="bi bi-info-circle me-2"></i>ยังไม่มีประวัติการตรวจสุขภาพช่องปากจากทันตแพทย์</div>';
                return;
            }

            container.innerHTML = records.map((r, index) => {
                const checked = !!r.doctor_name;
                const hasDecay = r.teeth_status === 'abnormal';
                return `
                    <div class="th-record-card">
                        <div class="th-record-top">
                            <span class="th-record-title"><i class="bi bi-mortarboard me-1"></i>ปีการศึกษา ${thEscapeHtml(r.academic_year || '-')}</span>
                            <span class="th-badge ${checked ? 'checked' : 'pending'}">${checked ? 'ทันตแพทย์ตรวจแล้ว' : 'รอทันตแพทย์ตรวจ'}</span>
                        </div>
                        <div class="th-record-meta">
                            <i class="bi bi-${hasDecay ? 'exclamation-triangle text-danger' : 'check-circle text-success'} me-1"></i>${hasDecay ? 'มีฟันผุ' : 'ยังไม่มีฟันผุ'}
                            ${checked ? ' &nbsp;•&nbsp; <i class="bi bi-person-badge me-1"></i>ทันตแพทย์: ' + thEscapeHtml(r.doctor_name) : ''}
                        </div>
                        <div class="th-record-actions">
                            <button type="button" class="th-btn-view" onclick="thShowDetail(${index})">
                                <i class="bi bi-eye me-1"></i>ดูรายละเอียด
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        <?php if ($child): ?>
        fetch('../../include/function/get_own_child_health_tooth.php')
            .then(response => response.json())
            .then(result => {
                if (result.status !== 'success') {
                    throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
                }
                thRecords = result.data;
                thRenderList(thRecords);
            })
            .catch(error => {
                document.getElementById('thRecordList').innerHTML =
                    '<div class="th-empty" style="color:#c0392b;"><i class="bi bi-exclamation-triangle me-2"></i>' + thEscapeHtml(error.message) + '</div>';
            });
        <?php endif; ?>
    </script>
</body>
</html>
