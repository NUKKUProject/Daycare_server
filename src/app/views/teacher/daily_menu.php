<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
?>

<style>
    .dm-header {
        background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
        padding: 1.75rem 2rem;
        border-radius: 15px;
        color: #fff;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .dm-header h2 { margin: 0; font-size: 1.5rem; font-weight: 800; }
    .dm-header .sub { opacity: .8; font-size: .9rem; margin-top: 4px; }

    .dm-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, .05);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dm-card h4 { color: #0f2460; font-size: 1.1rem; font-weight: 800; margin-bottom: .25rem; }
    .dm-card .hint { color: #64748b; font-size: .85rem; margin-bottom: 1rem; }

    .dm-label { font-weight: 700; color: #334155; font-size: .88rem; margin-bottom: .35rem; display: block; }

    .dm-input {
        height: 46px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background: #f8faff;
    }

    .dm-input:focus {
        border-color: #1e4db7;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(30, 77, 183, .1);
        outline: none;
    }

    .date-nav { display: flex; gap: .4rem; align-items: stretch; }
    .date-nav input { flex: 1 1 auto; min-width: 0; }
    .date-nav .btn {
        height: 46px; border-radius: 10px; border: 2px solid #e2e8f0; background: #fff;
        color: #475569; font-weight: 600; white-space: nowrap; padding: 0 .8rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .date-nav .btn:hover { border-color: #1e4db7; color: #1e4db7; }

    .room-group { margin-bottom: .9rem; }
    .room-group-head {
        display: flex; align-items: center; gap: .6rem; margin-bottom: .4rem;
        font-weight: 800; color: #0f2460; font-size: .92rem;
    }
    .room-group-head button {
        border: none; background: #eff3ff; color: #1e4db7; border-radius: 20px;
        padding: 1px 12px; font-size: .75rem; font-weight: 700;
    }
    .room-chips { display: flex; flex-wrap: wrap; gap: .45rem; }
    .room-chip { position: relative; margin: 0; cursor: pointer; }
    .room-chip input { position: absolute; opacity: 0; pointer-events: none; }
    .room-chip span {
        display: inline-block; padding: .4rem 1rem; border-radius: 20px; border: 2px solid #e2e8f0;
        background: #fff; color: #475569; font-weight: 600; font-size: .88rem; user-select: none;
        transition: all .15s ease;
    }
    .room-chip input:checked + span {
        border-color: #1e4db7; background: linear-gradient(135deg, #0f2460, #1e4db7); color: #fff;
    }
    .room-chip input:focus-visible + span { box-shadow: 0 0 0 3px rgba(30, 77, 183, .25); }

    .meal-row { display: flex; gap: .75rem; align-items: center; margin-bottom: .75rem; }
    .meal-icon {
        width: 44px; height: 44px; border-radius: 12px; background: #eff3ff; border: 2px solid #c7d7f8;
        display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;
        font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif;
    }
    .meal-field { flex: 1; min-width: 0; }

    .btn-save-menu {
        border-radius: 12px; padding: .65rem 1.8rem; font-weight: 800; border: none; color: #fff;
        background: linear-gradient(135deg, #0f2460, #1e4db7); box-shadow: 0 4px 16px rgba(15, 36, 96, .3);
    }
    .btn-save-menu:hover { color: #fff; background: linear-gradient(135deg, #0a1a4f, #1a43a8); }
    .btn-save-menu:disabled { opacity: .7; color: #fff; }

    .overview-wrap { overflow: auto; }
    .overview-table { min-width: 640px; }
    .overview-table thead th { background: #eff3ff; color: #0f2460; font-size: .85rem; }
    .overview-table td { vertical-align: middle; font-size: .9rem; }
    .overview-table td.room { font-weight: 700; color: #0f2460; white-space: nowrap; }
    .none { color: #cbd5e1; }

    .apply-note {
        margin-top: .5rem; padding: .6rem .9rem; border-radius: 10px;
        background: #fff7e0; color: #b45309; font-size: .85rem; display: none;
    }
</style>

<main class="main-content">
    <div class="container-fluid mt-4">
        <div class="dm-header">
            <h2><i class="bi bi-egg-fried me-2"></i>เมนูอาหารรายวัน</h2>
            <div class="sub">กรอกครั้งเดียว เลือกได้หลายห้อง เมนูจะแสดงในสมุดสื่อสารของเด็กทุกคนในห้องอัตโนมัติ</div>
        </div>

        <div id="migrationAlert" class="alert alert-warning" style="display:none;"></div>

        <div class="dm-card">
            <h4>1. เลือกวันที่และห้อง</h4>
            <div class="hint">เลือกหลายห้องได้ เมนูที่กรอกจะใช้กับทุกห้องที่ติ๊กไว้</div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="dm-label" for="menuDate">วันที่</label>
                    <div class="date-nav">
                        <button type="button" class="btn" id="datePrev" title="วันก่อนหน้า"><i class="fas fa-chevron-left"></i></button>
                        <input type="date" class="form-control dm-input" id="menuDate">
                        <button type="button" class="btn" id="dateNext" title="วันถัดไป"><i class="fas fa-chevron-right"></i></button>
                        <button type="button" class="btn" id="dateToday">วันนี้</button>
                    </div>
                </div>
            </div>

            <div id="roomGroups"></div>
            <div class="apply-note" id="applyNote"></div>
        </div>

        <div class="dm-card">
            <h4>2. กรอกเมนู</h4>
            <div class="hint">เว้นว่างช่องไหน = ไม่มีเมนูของช่วงนั้น (ถ้าเคยกรอกไว้จะถูกลบ)</div>

            <div class="meal-row">
                <div class="meal-icon">🥛</div>
                <div class="meal-field">
                    <label class="dm-label" for="menuMorning">อาหารว่างเช้า</label>
                    <input type="text" class="form-control dm-input" id="menuMorning" maxlength="255" list="sugMorning" placeholder="เช่น นมจืด + ขนมปัง" autocomplete="off">
                    <datalist id="sugMorning"></datalist>
                </div>
            </div>
            <div class="meal-row">
                <div class="meal-icon">🍚</div>
                <div class="meal-field">
                    <label class="dm-label" for="menuLunch">อาหารกลางวัน</label>
                    <input type="text" class="form-control dm-input" id="menuLunch" maxlength="255" list="sugLunch" placeholder="เช่น ข้าวผัดไก่ + แกงจืดวุ้นเส้น" autocomplete="off">
                    <datalist id="sugLunch"></datalist>
                </div>
            </div>
            <div class="meal-row">
                <div class="meal-icon">🍌</div>
                <div class="meal-field">
                    <label class="dm-label" for="menuAfternoon">อาหารว่างบ่าย</label>
                    <input type="text" class="form-control dm-input" id="menuAfternoon" maxlength="255" list="sugAfternoon" placeholder="เช่น กล้วยน้ำว้า" autocomplete="off">
                    <datalist id="sugAfternoon"></datalist>
                </div>
            </div>

            <button type="button" class="btn btn-save-menu mt-2" id="saveMenuBtn">
                <i class="bi bi-check-circle me-1"></i> บันทึกเมนู
            </button>
        </div>

        <div class="dm-card">
            <h4>เมนูที่บันทึกไว้ของวันนี้ (ทุกห้อง)</h4>
            <div class="hint">กดชื่อห้องเพื่อดึงเมนูมาแก้ไข</div>
            <div class="overview-wrap" id="overview"></div>
        </div>
    </div>
</main>

<script>
    const API = '../../include/function/daily_notebook_api.php';
    const SLOTS = [
        { key: 'morning_snack', label: 'อาหารว่างเช้า', input: 'menuMorning' },
        { key: 'lunch', label: 'อาหารกลางวัน', input: 'menuLunch' },
        { key: 'afternoon_snack', label: 'อาหารว่างบ่าย', input: 'menuAfternoon' }
    ];

    let rooms = [];            // [{classroom_name, child_group}]
    let menusOfDate = {};      // { classroom: {slot: text} }

    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    async function api(action, params = {}, body = null) {
        const url = `${API}?action=${action}&` + new URLSearchParams(params).toString();
        const res = await fetch(body ? API : url, body ? {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...body })
        } : undefined);
        let data;
        try { data = await res.json(); } catch (e) { throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง'); }
        if (data.status === 'needs_migration') {
            byId('migrationAlert').style.display = '';
            byId('migrationAlert').innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i> ${esc(data.message)}`;
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

    function todayStr() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    function shiftDate(days) {
        const input = byId('menuDate');
        const base = input.value ? new Date(input.value + 'T00:00:00') : new Date();
        base.setDate(base.getDate() + days);
        input.value = `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}-${String(base.getDate()).padStart(2, '0')}`;
        onDateChange();
    }

    function selectedRooms() {
        return Array.from(document.querySelectorAll('#roomGroups input[type="checkbox"]:checked')).map((i) => i.value);
    }

    function renderRooms() {
        const groups = new Map();
        rooms.forEach((r) => {
            if (!groups.has(r.child_group)) groups.set(r.child_group, []);
            groups.get(r.child_group).push(r.classroom_name);
        });
        if (groups.size === 0) {
            byId('roomGroups').innerHTML = '<div class="text-muted">ยังไม่มีห้องเรียนในระบบ</div>';
            return;
        }
        byId('roomGroups').innerHTML = Array.from(groups.entries()).map(([group, names]) => `
            <div class="room-group">
                <div class="room-group-head">
                    <span><i class="bi bi-bookmark-star-fill me-1"></i>${esc(group)}</span>
                    <button type="button" data-group="${esc(group)}">เลือกทั้งกลุ่ม</button>
                </div>
                <div class="room-chips">
                    ${names.map((n) => `<label class="room-chip"><input type="checkbox" value="${esc(n)}" data-group="${esc(group)}"><span>${esc(n)}</span></label>`).join('')}
                </div>
            </div>`).join('');
    }

    // เลือกห้องเดียว: ดึงเมนูที่เคยกรอกมาใส่ช่อง / เลือกหลายห้อง: แจ้งว่าจะเขียนทับ
    function onRoomsChange() {
        const sel = selectedRooms();
        const note = byId('applyNote');
        if (sel.length === 1) {
            const m = menusOfDate[sel[0]] || {};
            SLOTS.forEach((s) => { byId(s.input).value = m[s.key] || ''; });
        }
        if (sel.length > 1) {
            note.style.display = 'block';
            note.innerHTML = `<i class="bi bi-info-circle me-1"></i> เลือก ${sel.length} ห้อง — เมนูที่กรอกจะเขียนทับเมนูของวันนี้ในทุกห้องที่เลือก`;
        } else {
            note.style.display = 'none';
        }
    }

    function renderOverview() {
        const names = rooms.map((r) => r.classroom_name);
        if (names.length === 0) {
            byId('overview').innerHTML = '<div class="text-muted">ไม่มีข้อมูล</div>';
            return;
        }
        byId('overview').innerHTML = `
            <table class="table table-hover overview-table">
                <thead><tr><th>ห้อง</th>${SLOTS.map((s) => `<th>${s.label}</th>`).join('')}</tr></thead>
                <tbody>
                    ${rooms.map((r) => {
                        const m = menusOfDate[r.classroom_name] || {};
                        return `<tr>
                            <td class="room"><a href="#" data-room="${esc(r.classroom_name)}">${esc(r.classroom_name)}</a>
                                <div class="small text-muted fw-normal">${esc(r.child_group)}</div></td>
                            ${SLOTS.map((s) => `<td>${m[s.key] ? esc(m[s.key]) : '<span class="none">—</span>'}</td>`).join('')}
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>`;
    }

    async function loadMenusOfDate() {
        const data = await api('menus', { date: byId('menuDate').value });
        menusOfDate = data.data || {};
        renderOverview();
        onRoomsChange();
    }

    async function onDateChange() {
        try { await loadMenusOfDate(); } catch (e) { showError(e); }
    }

    async function saveMenus() {
        const sel = selectedRooms();
        if (sel.length === 0) {
            Swal.fire({ icon: 'warning', title: 'กรุณาเลือกห้องเรียนอย่างน้อย 1 ห้อง', confirmButtonText: 'ตกลง' });
            return;
        }
        const menus = {};
        SLOTS.forEach((s) => { menus[s.key] = byId(s.input).value.trim(); });

        const btn = byId('saveMenuBtn');
        btn.disabled = true;
        try {
            const data = await api('save_menus', {}, { date: byId('menuDate').value, classrooms: sel, menus });
            toast('success', data.message || 'บันทึกแล้ว');
            await loadMenusOfDate();
            loadSuggestions();
        } catch (error) {
            showError(error);
        } finally {
            btn.disabled = false;
        }
    }

    async function loadSuggestions() {
        try {
            const data = (await api('menu_suggest')).data;
            const fill = (id, list) => { byId(id).innerHTML = (list || []).map((t) => `<option value="${esc(t)}"></option>`).join(''); };
            fill('sugMorning', data.morning_snack);
            fill('sugLunch', data.lunch);
            fill('sugAfternoon', data.afternoon_snack);
        } catch (e) { /* ตัวช่วยพิมพ์ ไม่จำเป็นต้องแจ้ง error */ }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        byId('menuDate').value = todayStr();   // ใช้วันที่ของเครื่องผู้ใช้ ไม่พึ่งเขตเวลาของเซิร์ฟเวอร์
        byId('datePrev').addEventListener('click', () => shiftDate(-1));
        byId('dateNext').addEventListener('click', () => shiftDate(1));
        byId('dateToday').addEventListener('click', () => { byId('menuDate').value = todayStr(); onDateChange(); });
        byId('menuDate').addEventListener('change', onDateChange);
        byId('saveMenuBtn').addEventListener('click', saveMenus);

        byId('roomGroups').addEventListener('change', onRoomsChange);
        byId('roomGroups').addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-group]');
            if (!btn) return;
            const boxes = Array.from(document.querySelectorAll('#roomGroups input[type="checkbox"]'))
                .filter((i) => i.dataset.group === btn.dataset.group);
            const allOn = boxes.every((i) => i.checked);
            boxes.forEach((i) => { i.checked = !allOn; });
            onRoomsChange();
        });

        // กดชื่อห้องในตารางสรุป = เลือกห้องนั้นอย่างเดียว แล้วดึงเมนูมาแก้
        byId('overview').addEventListener('click', (e) => {
            const a = e.target.closest('a[data-room]');
            if (!a) return;
            e.preventDefault();
            document.querySelectorAll('#roomGroups input[type="checkbox"]').forEach((i) => { i.checked = i.value === a.dataset.room; });
            onRoomsChange();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        try {
            rooms = (await api('classrooms')).data;
            renderRooms();
            await loadMenusOfDate();
            loadSuggestions();
        } catch (error) {
            if (byId('migrationAlert').style.display === 'none') showError(error);
        }
    });
</script>
