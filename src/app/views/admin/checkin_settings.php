<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
?>

<style>
    .cs-header {
        background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
        padding: 1.75rem 2rem;
        border-radius: 15px;
        color: #fff;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .cs-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
    }

    .cs-header .sub {
        opacity: 0.8;
        font-size: 0.9rem;
        margin-top: 4px;
    }

    .cs-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .cs-card h4 {
        color: #0f2460;
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }

    .cs-card .hint {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }

    .late-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .late-row input[type="time"] {
        max-width: 160px;
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f2460;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        padding: 0.4rem 0.8rem;
    }

    .late-row input[type="time"]:focus {
        border-color: #1e4db7;
        box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
        outline: none;
    }

    .late-preview {
        margin-top: 0.9rem;
        padding: 0.7rem 1rem;
        background: #eff3ff;
        border-radius: 12px;
        color: #0f2460;
        font-size: 0.9rem;
    }

    .option-list {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .option-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        padding: 0.7rem 0.9rem;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        background: #f8faff;
    }

    .option-row.inactive {
        opacity: 0.55;
        background: #f1f5f9;
    }

    .option-row .emoji {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #fff;
        border: 2px solid #c7d7f8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
        font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif;
    }

    .option-row .main {
        flex: 1;
        min-width: 160px;
    }

    .option-row .label {
        font-weight: 700;
        color: #0f2460;
    }

    .option-row .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        margin-top: 4px;
    }

    .chip {
        font-size: 0.74rem;
        font-weight: 600;
        padding: 1px 10px;
        border-radius: 20px;
        background: #eff3ff;
        color: #1e4db7;
        border: 1px solid #c7d7f8;
    }

    .chip.text {
        background: #fff7e0;
        color: #b45309;
        border-color: #fcd34d;
    }

    .option-row .actions {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-left: auto;
    }

    .option-row .actions .btn {
        width: 34px;
        height: 34px;
        padding: 0;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .option-row .form-switch {
        margin: 0 0.4rem 0 0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        color: #475569;
    }

    .option-row .form-switch .form-check-input {
        margin: 0;
        cursor: pointer;
    }

    .empty-box {
        text-align: center;
        padding: 1.5rem;
        color: #64748b;
        border: 2px dashed #e2e8f0;
        border-radius: 14px;
    }

    .nav-pills .nav-link {
        border-radius: 25px;
        font-weight: 700;
        color: #475569;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #0f2460, #1e4db7);
        color: #fff;
    }

    /* modal */
    #optionModal .modal-content {
        border: none;
        border-radius: 24px;
        overflow: hidden;
    }

    #optionModal .modal-header {
        background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
        border: none;
        color: #fff;
        padding: 1.25rem 1.75rem;
    }

    #optionModal .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.7;
    }

    #optionModal .modal-body {
        background: #f0f4f8;
        padding: 1.5rem;
    }

    #optionModal .modal-footer {
        background: #f0f4f8;
        border-top: 1px solid #e2e8f0;
    }

    #optionModal .form-control {
        border-radius: 12px;
        border: 2px solid #e2e8f0;
    }

    #optionModal .form-control:focus {
        border-color: #1e4db7;
        box-shadow: 0 0 0 4px rgba(30, 77, 183, 0.1);
    }

    .emoji-palette {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        margin-top: 0.5rem;
    }

    .emoji-palette button {
        width: 38px;
        height: 38px;
        border: 2px solid #e2e8f0;
        background: #fff;
        border-radius: 10px;
        font-size: 1.2rem;
        line-height: 1;
        padding: 0;
        font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif;
    }

    .emoji-palette button:hover {
        border-color: #1e4db7;
        background: #eff3ff;
    }

    .sub-row {
        display: flex;
        gap: 0.4rem;
        margin-bottom: 0.4rem;
    }

    .sub-row input {
        flex: 1;
    }

    #iconInput {
        max-width: 90px;
        font-size: 1.4rem;
        text-align: center;
        font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif;
    }
</style>

<main class="main-content">
    <div class="container-fluid mt-4">
        <div class="cs-header">
            <h2><i class="bi bi-gear-fill me-2"></i>ตั้งค่าการเช็คชื่อ</h2>
            <div class="sub">กำหนดเวลามาสาย และจัดการตัวเลือกอาการ/การดูแลที่ครูเลือกตอนเช็คชื่อ</div>
        </div>

        <div id="migrationAlert" class="alert alert-warning" style="display:none;"></div>

        <!-- เวลามาสาย -->
        <div class="cs-card">
            <h4>⏰ เวลามาสาย</h4>
            <div class="hint">เด็กที่เช็คชื่อ<strong>หลัง</strong>เวลานี้จะถูกบันทึกเป็น "มาสาย" (ตรงกับเวลานี้พอดีถือว่ายังไม่สาย)</div>
            <div class="late-row">
                <input type="time" id="lateTime" value="08:30">
                <button type="button" class="btn btn-primary" id="saveLateBtn"><i class="bi bi-check-circle me-1"></i> บันทึกเวลา</button>
            </div>
            <div class="late-preview" id="latePreview"></div>
        </div>

        <!-- ตัวเลือก -->
        <div class="cs-card">
            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabSymptom" type="button">🤧 อาการที่พบ</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabCare" type="button">🩹 การดูแล/ช่วยเหลือ</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabSymptom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="hint mb-0">อาการที่ครูติ๊กตอนรับเด็ก แต่ละอาการมีตัวเลือกย่อยได้ (เช่น น้ำมูก: ใส/เหลือง/เขียว)</div>
                        <button type="button" class="btn btn-primary btn-sm" data-add="symptom"><i class="bi bi-plus-circle me-1"></i> เพิ่มอาการ</button>
                    </div>
                    <div class="option-list" id="listSymptom"></div>
                </div>
                <div class="tab-pane fade" id="tabCare">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="hint mb-0">สิ่งที่ครูทำให้เด็กตอนรับเข้า เช่น ล้างมือ ป้อนยา</div>
                        <button type="button" class="btn btn-primary btn-sm" data-add="care"><i class="bi bi-plus-circle me-1"></i> เพิ่มการดูแล</button>
                    </div>
                    <div class="option-list" id="listCare"></div>
                </div>
            </div>

            <div class="form-text mt-3">
                <i class="bi bi-info-circle me-1"></i>
                "ปิดใช้งาน" = ซ่อนจากหน้าเช็คชื่อ แต่ประวัติเก่ายังแสดงชื่อได้ · "ลบ" = เอาออกจากรายการนี้ (ประวัติเก่าที่เคยเลือกไว้ยังอ่านชื่อได้เช่นกัน)
            </div>
        </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขตัวเลือก -->
    <div class="modal fade" id="optionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="optionModalTitle">เพิ่มตัวเลือก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="optId">
                    <input type="hidden" id="optType">

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="optLabel">ชื่อตัวเลือก</label>
                        <input type="text" class="form-control" id="optLabel" maxlength="100" placeholder="เช่น น้ำมูก" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="iconInput">ไอคอน (อีโมจิ)</label>
                        <input type="text" class="form-control" id="iconInput" maxlength="8" placeholder="🙂">
                        <div class="emoji-palette" id="emojiPalette"></div>
                    </div>

                    <div class="mb-3" id="subsGroup">
                        <label class="form-label fw-bold">ตัวเลือกย่อย <span class="text-muted fw-normal">(ไม่บังคับ)</span></label>
                        <div id="subsList"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addSubBtn"><i class="bi bi-plus me-1"></i> เพิ่มตัวเลือกย่อย</button>
                    </div>

                    <div class="form-check mb-3" id="textGroup">
                        <input class="form-check-input" type="checkbox" id="optAllowsText">
                        <label class="form-check-label" for="optAllowsText">ให้ครูพิมพ์ข้อความเพิ่มได้เมื่อเลือกข้อนี้ (เช่น "อื่นๆ")</label>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="optActive" checked>
                        <label class="form-check-label" for="optActive">เปิดใช้งานในหน้าเช็คชื่อ</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveOptionBtn"><i class="bi bi-check-circle me-1"></i> บันทึก</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const API_URL = '../../include/function/checkin_settings_api.php';
    const EMOJIS = ['🤧', '😷', '🤒', '🤕', '🤢', '🤮', '😴', '😭', '🔥', '🦷', '👄', '👁️', '👂', '🖐️', '🦟', '🩹', '🔴', '🌡️', '💧', '🧼', '💊', '🧴', '💉', '🩺', '📋', '✏️', '❤️', '⭐'];

    let state = { late_time: '08:30', symptoms: [], care: [] };
    let optionModal = null;

    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    async function api(action, body = {}) {
        const isGet = action === 'get';
        const res = await fetch(isGet ? `${API_URL}?action=get` : API_URL, {
            method: isGet ? 'GET' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: isGet ? undefined : JSON.stringify({ action, ...body })
        });
        let data;
        try { data = await res.json(); } catch (e) { throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง'); }
        if (data.status === 'needs_migration') {
            byId('migrationAlert').style.display = '';
            byId('migrationAlert').innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i> ${esc(data.message)} — ระหว่างนี้ระบบใช้ค่าเริ่มต้น (08:30 และรายการเดิม)`;
            throw new Error(data.message);
        }
        if (data.status !== 'success') throw new Error(data.message || 'เกิดข้อผิดพลาด');
        return data;
    }

    function toast(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 1800, timerProgressBar: true });
    }

    function showError(error) {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error.message, confirmButtonText: 'ตกลง' });
    }

    function applySnapshot(data) {
        state = data.data;
        render();
    }

    function updateLatePreview() {
        const t = byId('lateTime').value || state.late_time;
        byId('latePreview').innerHTML = `เช็คชื่อ <strong>ก่อนหรือตรงกับ ${esc(t)} น.</strong> = ตรงเวลา · เช็คชื่อ <strong>หลัง ${esc(t)} น.</strong> = มาสาย`;
    }

    function renderList(type) {
        const list = state[type === 'symptom' ? 'symptoms' : 'care'];
        const box = byId(type === 'symptom' ? 'listSymptom' : 'listCare');

        if (!list.length) {
            box.innerHTML = '<div class="empty-box">ยังไม่มีตัวเลือก กด "เพิ่ม" เพื่อสร้างตัวเลือกแรก</div>';
            return;
        }

        box.innerHTML = list.map((o, i) => `
            <div class="option-row ${o.is_active ? '' : 'inactive'}">
                <div class="emoji">${esc(o.icon) || '🔹'}</div>
                <div class="main">
                    <div class="label">${esc(o.label)}${o.is_active ? '' : ' <span class="badge bg-secondary ms-1">ปิดใช้งาน</span>'}</div>
                    <div class="chips">
                        ${(o.subs || []).map((s) => `<span class="chip">${esc(s.label)}</span>`).join('')}
                        ${o.allows_text ? '<span class="chip text">✏️ พิมพ์ข้อความเพิ่มได้</span>' : ''}
                    </div>
                </div>
                <div class="actions">
                    <label class="form-switch" title="เปิด/ปิดใช้งาน">
                        <input class="form-check-input" type="checkbox" role="switch" data-act="toggle" data-id="${o.id}" ${o.is_active ? 'checked' : ''}>
                        <span>เปิดใช้</span>
                    </label>
                    <button type="button" class="btn btn-outline-secondary" data-act="up" data-id="${o.id}" title="เลื่อนขึ้น" ${i === 0 ? 'disabled' : ''}><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-act="down" data-id="${o.id}" title="เลื่อนลง" ${i === list.length - 1 ? 'disabled' : ''}><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-warning" data-act="edit" data-id="${o.id}" data-type="${type}" title="แก้ไข"><i class="bi bi-pencil-square"></i></button>
                    <button type="button" class="btn btn-danger" data-act="delete" data-id="${o.id}" data-type="${type}" title="ลบ"><i class="bi bi-trash"></i></button>
                </div>
            </div>`).join('');
    }

    function render() {
        byId('lateTime').value = state.late_time;
        updateLatePreview();
        renderList('symptom');
        renderList('care');
    }

    // ===== Modal =====
    function addSubRow(label = '', code = '') {
        const row = document.createElement('div');
        row.className = 'sub-row';
        row.dataset.code = code;
        row.innerHTML = `
            <input type="text" class="form-control form-control-sm" maxlength="100" placeholder="ชื่อตัวเลือกย่อย เช่น ใส" value="${esc(label)}">
            <button type="button" class="btn btn-outline-danger btn-sm" title="เอาออก"><i class="bi bi-x-lg"></i></button>`;
        row.querySelector('button').addEventListener('click', () => row.remove());
        byId('subsList').appendChild(row);
        return row;
    }

    function openOptionModal(type, option = null) {
        byId('optionModalTitle').textContent = option ? 'แก้ไขตัวเลือก' : (type === 'symptom' ? 'เพิ่มอาการ' : 'เพิ่มการดูแล/ช่วยเหลือ');
        byId('optId').value = option ? option.id : '';
        byId('optType').value = type;
        byId('optLabel').value = option ? option.label : '';
        byId('iconInput').value = option ? option.icon : '';
        byId('optActive').checked = option ? option.is_active : true;
        byId('optAllowsText').checked = option ? option.allows_text : false;

        byId('subsGroup').style.display = type === 'symptom' ? '' : 'none';
        byId('textGroup').style.display = type === 'care' ? '' : 'none';
        byId('subsList').innerHTML = '';
        if (type === 'symptom' && option) {
            (option.subs || []).forEach((s) => addSubRow(s.label, s.code));
        }

        optionModal.show();
        setTimeout(() => byId('optLabel').focus(), 300);
    }

    async function saveOption() {
        const type = byId('optType').value;
        const label = byId('optLabel').value.trim();
        if (!label) {
            Swal.fire({ icon: 'warning', title: 'กรุณาระบุชื่อตัวเลือก', confirmButtonText: 'ตกลง' });
            return;
        }
        const subs = Array.from(byId('subsList').querySelectorAll('.sub-row')).map((row) => ({
            code: row.dataset.code || '',
            label: row.querySelector('input').value.trim()
        })).filter((s) => s.label);

        const btn = byId('saveOptionBtn');
        btn.disabled = true;
        try {
            const data = await api('save_option', {
                id: byId('optId').value || null,
                option_type: type,
                label,
                icon: byId('iconInput').value.trim(),
                allows_text: byId('optAllowsText').checked,
                is_active: byId('optActive').checked,
                subs: type === 'symptom' ? subs : undefined
            });
            optionModal.hide();
            applySnapshot(data);
            toast('success', 'บันทึกแล้ว');
        } catch (error) {
            showError(error);
        } finally {
            btn.disabled = false;
        }
    }

    function findOption(id) {
        return [...state.symptoms, ...state.care].find((o) => Number(o.id) === Number(id));
    }

    // ===== เหตุการณ์ =====
    document.addEventListener('click', async (e) => {
        const add = e.target.closest('[data-add]');
        if (add) {
            openOptionModal(add.dataset.add);
            return;
        }

        const btn = e.target.closest('button[data-act]');
        if (!btn) return;
        const id = Number(btn.dataset.id);
        const act = btn.dataset.act;

        try {
            if (act === 'edit') {
                openOptionModal(btn.dataset.type, findOption(id));
            } else if (act === 'up' || act === 'down') {
                applySnapshot(await api('move_option', { id, direction: act }));
            } else if (act === 'delete') {
                const opt = findOption(id);
                const result = await Swal.fire({
                    title: 'ลบตัวเลือกนี้?',
                    html: `<b>${esc(opt ? opt.label : '')}</b><br>ประวัติเก่าที่เคยเลือกไว้จะยังอ่านชื่อได้`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'ลบ',
                    cancelButtonText: 'ยกเลิก'
                });
                if (result.isConfirmed) {
                    applySnapshot(await api('delete_option', { id }));
                    toast('success', 'ลบแล้ว');
                }
            }
        } catch (error) {
            showError(error);
        }
    });

    document.addEventListener('change', async (e) => {
        const sw = e.target.closest('input[data-act="toggle"]');
        if (!sw) return;
        try {
            applySnapshot(await api('toggle_active', { id: Number(sw.dataset.id), is_active: sw.checked }));
        } catch (error) {
            sw.checked = !sw.checked;
            showError(error);
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        optionModal = new bootstrap.Modal(byId('optionModal'));

        byId('emojiPalette').innerHTML = EMOJIS.map((em) => `<button type="button" data-emoji="${em}">${em}</button>`).join('');
        byId('emojiPalette').addEventListener('click', (e) => {
            const b = e.target.closest('button[data-emoji]');
            if (b) byId('iconInput').value = b.dataset.emoji;
        });

        byId('addSubBtn').addEventListener('click', () => addSubRow().querySelector('input').focus());
        byId('saveOptionBtn').addEventListener('click', saveOption);
        byId('optLabel').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); saveOption(); } });

        byId('lateTime').addEventListener('input', updateLatePreview);
        byId('saveLateBtn').addEventListener('click', async () => {
            const value = byId('lateTime').value;
            if (!value) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกเวลา', confirmButtonText: 'ตกลง' });
                return;
            }
            try {
                applySnapshot(await api('save_late_time', { late_time: value }));
                toast('success', `ตั้งเวลามาสายเป็น ${value} น. แล้ว`);
            } catch (error) {
                showError(error);
            }
        });

        try {
            applySnapshot(await api('get'));
        } catch (error) {
            if (byId('migrationAlert').style.display === 'none') showError(error);
            render();
        }
    });
</script>
