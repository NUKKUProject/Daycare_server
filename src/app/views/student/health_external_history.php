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
    .eh-page {
        --eh-primary: #26648E;
        --eh-primary-dark: #1E4F6F;
        --eh-border: #d9e6ee;
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem 1.75rem 3.5rem;
    }

    .eh-header {
        align-items: center;
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .eh-header h1 {
        color: var(--eh-primary-dark);
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
    }

    .eh-back {
        align-items: center;
        background: #fff;
        border: 1px solid var(--eh-border);
        border-radius: 0.75rem;
        color: var(--eh-primary-dark);
        display: inline-flex;
        gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .eh-back:hover { background: #ebf4fb; color: var(--eh-primary-dark); }

    .eh-record-card {
        background: #fff;
        border: 1px solid var(--eh-border);
        border-left: 5px solid var(--eh-primary);
        border-radius: 1.1rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        margin-bottom: 1rem;
        padding: 1.1rem 1.4rem;
    }

    .eh-record-top {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: space-between;
    }

    .eh-record-title {
        color: var(--eh-primary-dark);
        font-weight: 700;
    }

    .eh-badge {
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.25rem 0.7rem;
    }

    .eh-badge.checked { background: #e6f7ec; color: #1e7e42; }
    .eh-badge.pending { background: #fff4e0; color: #a15c00; }

    .eh-record-meta {
        color: #64748b;
        font-size: 0.85rem;
        margin-top: 0.5rem;
    }

    .eh-record-actions { margin-top: 0.8rem; }

    .eh-btn-view {
        background: var(--eh-primary);
        border: none;
        border-radius: 0.65rem;
        color: #fff;
        font-size: 0.85rem;
        padding: 0.45rem 0.9rem;
    }

    .eh-btn-view:hover { background: var(--eh-primary-dark); }

    .eh-empty {
        background: #fff;
        border: 1px solid var(--eh-border);
        border-radius: 1rem;
        color: #64748b;
        padding: 2rem;
        text-align: center;
    }

    /* ===== รายละเอียดใน SweetAlert modal ===== */
    .eh-detail { text-align: left; }

    .eh-detail-section {
        background: #f8fbfd;
        border-radius: 0.9rem;
        margin-bottom: 0.85rem;
        padding: 0.9rem 1.1rem;
    }

    .eh-detail-section h6 {
        color: var(--eh-primary-dark);
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.6rem;
    }

    .eh-detail-grid {
        display: grid;
        gap: 0.6rem;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }

    .eh-detail-item label {
        color: #94a3b8;
        display: block;
        font-size: 0.72rem;
    }

    .eh-detail-item div {
        color: var(--eh-primary-dark);
        font-size: 0.92rem;
        font-weight: 600;
    }

    .eh-pill {
        border-radius: 999px;
        display: inline-block;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.15rem 0.6rem;
    }

    .eh-pill.normal { background: #e6f7ec; color: #1e7e42; }
    .eh-pill.abnormal { background: #fdecec; color: #c0392b; }
    .eh-pill.na { background: #eef1f4; color: #64748b; }

    .eh-exam-row {
        align-items: center;
        border-bottom: 1px dashed #e2e8f0;
        display: flex;
        font-size: 0.85rem;
        justify-content: space-between;
        padding: 0.35rem 0;
    }

    .eh-exam-row:last-child { border-bottom: none; }

    .eh-dev-grid {
        display: grid;
        gap: 0.6rem;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    }

    .eh-dev-item {
        border-radius: 0.75rem;
        padding: 0.6rem;
        text-align: center;
    }

    .eh-dev-item.pass { background: #e6f7ec; }
    .eh-dev-item.fail { background: #fdecec; }
    .eh-dev-item.na { background: #eef1f4; }

    .eh-dev-item strong { display: block; font-size: 0.9rem; }
    .eh-dev-item span { color: #64748b; font-size: 0.75rem; }
</style>

<body>
    <main class="main-content">
        <div class="eh-page">
            <div class="eh-header">
                <a class="eh-back" href="student_dashboard.php"><i class="bi bi-arrow-left"></i> กลับ</a>
                <h1><i class="fa-solid fa-user-doctor me-2"></i>ประวัติการตรวจร่างกายจากกุมารแพทย์</h1>
            </div>

            <?php if (!$child): ?>
                <div class="eh-empty">ไม่พบข้อมูลเด็กของบัญชีนี้ กรุณาติดต่อผู้ดูแลระบบ</div>
            <?php else: ?>
                <div id="ehRecordList">
                    <div class="eh-empty"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลดข้อมูล...</div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        let ehRecords = [];

        function ehEscapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        function ehParseJson(value) {
            if (value && typeof value === 'object') return value;
            if (!value) return {};
            try { return JSON.parse(value) || {}; } catch (e) { return {}; }
        }

        function ehFormatDate(str) {
            if (!str) return '-';
            const d = new Date(str);
            if (isNaN(d)) return '-';
            return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        function ehExamPill(val) {
            if (val === 'normal' || (Array.isArray(val) && val.includes('normal'))) {
                return '<span class="eh-pill normal">ปกติ</span>';
            }
            if (val === 'abnormal' || (Array.isArray(val) && val.includes('abnormal'))) {
                return '<span class="eh-pill abnormal">ผิดปกติ</span>';
            }
            return '<span class="eh-pill na">ไม่ได้ตรวจ</span>';
        }

        function ehExamRow(label, key, source) {
            const val = source[key];
            const detail = source[key + '_detail'];
            return `
                <div class="eh-exam-row">
                    <span>${ehEscapeHtml(label)}</span>
                    <span>${ehExamPill(val)}${detail ? ' <small style="color:#94a3b8;">' + ehEscapeHtml(detail) + '</small>' : ''}</span>
                </div>`;
        }

        function ehDevItem(code, label, obj) {
            obj = obj || {};
            const cls = obj.status === 'pass' ? 'pass' : (obj.status === 'fail' ? 'fail' : 'na');
            const statusLabel = obj.status === 'pass' ? 'ผ่าน' : (obj.status === 'fail' ? 'ไม่ผ่าน' : 'ไม่ได้ประเมิน');
            return `
                <div class="eh-dev-item ${cls}">
                    <strong>${code}</strong>
                    <span>${ehEscapeHtml(label)}</span><br>
                    <span>${statusLabel}${obj.score ? ' (ข้อที่ ' + ehEscapeHtml(obj.score) + ')' : ''}</span>
                </div>`;
        }

        function ehShowDetail(index) {
            const r = ehRecords[index];
            const vital = ehParseJson(r.vital_signs);
            const measures = ehParseJson(r.physical_measures);
            const behavior = ehParseJson(r.behavior);
            const physical = ehParseJson(r.physical_exam);
            const neuro = ehParseJson(r.neurological);
            const dev = ehParseJson(r.development_assessment);
            const behaviorNormal = behavior.status === 'none';

            const html = `
                <div class="eh-detail">
                    <div class="eh-detail-section">
                        <h6><i class="bi bi-heart-pulse me-1"></i>สัญญาณชีพ</h6>
                        <div class="eh-detail-grid">
                            <div class="eh-detail-item"><label>อุณหภูมิ</label><div>${ehEscapeHtml(vital.temperature || '-')} °C</div></div>
                            <div class="eh-detail-item"><label>ความดันโลหิต</label><div>${ehEscapeHtml(vital.bp || '-')} mmHg</div></div>
                            <div class="eh-detail-item"><label>วันที่ตรวจความดัน</label><div>${ehEscapeHtml(vital.bp_date || '-')}</div></div>
                        </div>
                    </div>

                    <div class="eh-detail-section">
                        <h6><i class="bi bi-rulers me-1"></i>ข้อมูลการวัด (${ehFormatDate(r.measurement_date)})</h6>
                        <div class="eh-detail-grid">
                            <div class="eh-detail-item"><label>ส่วนสูง</label><div>${ehEscapeHtml(measures.height || '-')} ซม.</div></div>
                            <div class="eh-detail-item"><label>น้ำหนัก</label><div>${ehEscapeHtml(measures.weight || '-')} กก.</div></div>
                            <div class="eh-detail-item"><label>รอบศีรษะ</label><div>${ehEscapeHtml(measures.head_circ || '-')} ซม.</div></div>
                            <div class="eh-detail-item"><label>น้ำหนัก/อายุ</label><div>${ehEscapeHtml(measures.weight_for_age || '-')}</div></div>
                            <div class="eh-detail-item"><label>ส่วนสูง/อายุ</label><div>${ehEscapeHtml(measures.height_for_age || '-')}</div></div>
                            <div class="eh-detail-item"><label>น้ำหนัก/ส่วนสูง</label><div>${ehEscapeHtml(measures.weight_for_height || '-')}</div></div>
                        </div>
                    </div>

                    <div class="eh-detail-section">
                        <h6><i class="bi bi-emoji-smile me-1"></i>พฤติกรรม</h6>
                        <span class="eh-pill ${behaviorNormal ? 'normal' : 'abnormal'}">${behaviorNormal ? 'ปกติ' : 'มีพฤติกรรมผิดปกติ'}</span>
                        ${!behaviorNormal && behavior.detail ? '<div style="margin-top:0.4rem;color:#64748b;font-size:0.85rem;">' + ehEscapeHtml(behavior.detail) + '</div>' : ''}
                    </div>

                    <div class="eh-detail-section">
                        <h6><i class="bi bi-graph-up me-1"></i>การประเมินพัฒนาการ 5 ด้าน</h6>
                        <div class="eh-dev-grid">
                            ${ehDevItem('GM', 'การเคลื่อนไหว', dev.gm)}
                            ${ehDevItem('FM', 'มัดเล็ก/สติปัญญา', dev.fm)}
                            ${ehDevItem('RL', 'เข้าใจภาษา', dev.rl)}
                            ${ehDevItem('EL', 'ใช้ภาษา', dev.el)}
                            ${ehDevItem('PS', 'ช่วยเหลือตนเอง/สังคม', dev.ps)}
                        </div>
                    </div>

                    <div class="eh-detail-section">
                        <h6><i class="fa-solid fa-stethoscope me-1"></i>การตรวจร่างกาย</h6>
                        ${ehExamRow('สภาพทั่วไป', 'general', physical)}
                        ${ehExamRow('ผิวหนัง', 'skin', physical)}
                        ${ehExamRow('ศีรษะ', 'head', physical)}
                        ${ehExamRow('ใบหน้า', 'face', physical)}
                        ${ehExamRow('ตา', 'eyes', physical)}
                        ${ehExamRow('หูและการได้ยิน', 'ears', physical)}
                        ${ehExamRow('จมูก', 'nose', physical)}
                        ${ehExamRow('ปากและช่องปาก', 'mouth', physical)}
                        ${ehExamRow('คอ', 'neck', physical)}
                        ${ehExamRow('ทรวงอกและปอด', 'breast', physical)}
                        ${ehExamRow('การหายใจ', 'breathe', physical)}
                        ${ehExamRow('ปอด', 'lungs', physical)}
                        ${ehExamRow('หัวใจ', 'heart', physical)}
                        ${ehExamRow('เสียงหัวใจ', 'heart_sound', physical)}
                        ${ehExamRow('ชีพจร', 'pulse', physical)}
                        ${ehExamRow('ท้อง', 'abdomen', physical)}
                        ${ehExamRow('อื่นๆ', 'others', physical)}
                    </div>

                    <div class="eh-detail-section">
                        <h6><i class="fa-solid fa-brain me-1"></i>ระบบประสาท</h6>
                        ${ehExamRow('ปฏิกิริยาขั้นพื้นฐาน', 'neuro', neuro)}
                        ${ehExamRow('การเคลื่อนไหว', 'movement', neuro)}
                    </div>

                    <div class="eh-detail-section" style="margin-bottom:0;">
                        <h6><i class="bi bi-clipboard-check me-1"></i>คำแนะนำ</h6>
                        <div style="color:#334155;font-size:0.9rem;">${r.recommendation ? ehEscapeHtml(r.recommendation) : 'ไม่มีคำแนะนำ'}</div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'รายละเอียดการตรวจสุขภาพ',
                html: html,
                width: window.innerWidth < 768 ? '95%' : '650px',
                showCloseButton: true,
                showConfirmButton: false,
                heightAuto: false
            });
        }

        function ehRenderList(records) {
            const container = document.getElementById('ehRecordList');
            if (!records || records.length === 0) {
                container.innerHTML = '<div class="eh-empty"><i class="bi bi-info-circle me-2"></i>ยังไม่มีประวัติการตรวจร่างกายจากกุมารแพทย์</div>';
                return;
            }

            container.innerHTML = records.map((r, index) => {
                const checked = !!r.doctor_name;
                return `
                    <div class="eh-record-card">
                        <div class="eh-record-top">
                            <span class="eh-record-title">ครั้งที่ ${ehEscapeHtml(r.check_round || 1)} — ${ehFormatDate(r.exam_date)}</span>
                            <span class="eh-badge ${checked ? 'checked' : 'pending'}">${checked ? 'หมอตรวจแล้ว' : 'รอแพทย์ตรวจ'}</span>
                        </div>
                        <div class="eh-record-meta">
                            <i class="bi bi-calendar3 me-1"></i>ปีการศึกษา ${ehEscapeHtml(r.academic_year || '-')}
                            ${checked ? ' &nbsp;•&nbsp; <i class="bi bi-person-badge me-1"></i>แพทย์: ' + ehEscapeHtml(r.doctor_name) : ''}
                        </div>
                        <div class="eh-record-actions">
                            <button type="button" class="eh-btn-view" onclick="ehShowDetail(${index})">
                                <i class="bi bi-eye me-1"></i>ดูรายละเอียด
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        <?php if ($child): ?>
        fetch('../../include/function/get_own_child_health_external.php')
            .then(response => response.json())
            .then(result => {
                if (result.status !== 'success') {
                    throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
                }
                ehRecords = result.data;
                ehRenderList(ehRecords);
            })
            .catch(error => {
                document.getElementById('ehRecordList').innerHTML =
                    '<div class="eh-empty" style="color:#c0392b;"><i class="bi bi-exclamation-triangle me-2"></i>' + ehEscapeHtml(error.message) + '</div>';
            });
        <?php endif; ?>
    </script>
</body>
</html>
