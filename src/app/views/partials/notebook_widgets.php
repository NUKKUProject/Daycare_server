<?php
// ตัวช่วยกรอกสมุดสื่อสารให้ง่ายขึ้น ใช้ร่วมกันทั้งหน้าครู/admin และหน้าผู้ปกครอง
//   <input data-stepper="step=10;min=0;max=600;presets=60|90|120">  ปุ่ม − / + และปุ่มเลือกค่าด่วน
//   <input data-quick="หมด|ครึ่งหนึ่ง|น้อย|ไม่ทาน">                 ปุ่มเลือกคำตอบด่วน (กดซ้ำเพื่อล้าง)
// include ไว้ก่อนสคริปต์ของหน้า แล้วเรียก NotebookWidgets.enhance() หลังหน้าพร้อม
?>
<style>
    .nw-step { display: flex; align-items: stretch; gap: .4rem; }
    .nw-step > :nth-child(2) { flex: 1 1 auto; min-width: 110px; }
    .nw-step .unit .form-control { padding-right: 2.9rem; }
    .nw-step-btn {
        flex: 0 0 auto; width: 42px; border: 2px solid #c7d7f8; background: #eff3ff; color: #1e4db7;
        border-radius: 12px; font-size: 1.4rem; font-weight: 700; line-height: 1; padding: 0;
        display: inline-flex; align-items: center; justify-content: center; user-select: none;
    }
    .nw-step-btn:hover:not(:disabled) { background: #dbe7ff; }
    .nw-step-btn:active:not(:disabled) { transform: scale(.94); }
    .nw-step-btn:disabled { opacity: .45; cursor: default; }
    .nw-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .4rem; }
    .nw-chip {
        border: 2px solid #e2e8f0; background: #fff; color: #475569; border-radius: 20px;
        padding: .15rem .8rem; font-size: .8rem; font-weight: 700; line-height: 1.5;
    }
    .nw-chip:hover:not(:disabled) { border-color: #93b4f0; }
    .nw-chip.active { border-color: #1e4db7; background: linear-gradient(135deg, #0f2460, #1e4db7); color: #fff; }
    .nw-chip:disabled { opacity: .5; cursor: default; }
</style>
<script>
    window.NotebookWidgets = (function () {
        function parseOpts(str) {
            const o = {};
            String(str || '').split(';').forEach((kv) => {
                const i = kv.indexOf('=');
                if (i > 0) o[kv.slice(0, i).trim()] = kv.slice(i + 1).trim();
            });
            return o;
        }

        function fire(input) {
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function decimals(step) {
            const s = String(step);
            return s.includes('.') ? s.split('.')[1].length : 0;
        }

        function setupStepper(input) {
            const o = parseOpts(input.dataset.stepper);
            const step = parseFloat(o.step) || 1;
            const min = o.min !== undefined ? parseFloat(o.min) : 0;
            const max = o.max !== undefined ? parseFloat(o.max) : Infinity;
            const dec = decimals(step);
            const presets = o.presets ? o.presets.split('|').map(Number).filter((n) => !isNaN(n)) : [];

            const core = input.closest('.unit') || input;
            const wrap = document.createElement('div');
            wrap.className = 'nw-step';
            const minus = document.createElement('button');
            minus.type = 'button'; minus.className = 'nw-step-btn'; minus.textContent = '−'; minus.setAttribute('aria-label', 'ลดค่า');
            const plus = document.createElement('button');
            plus.type = 'button'; plus.className = 'nw-step-btn'; plus.textContent = '+'; plus.setAttribute('aria-label', 'เพิ่มค่า');
            core.parentNode.insertBefore(wrap, core);
            wrap.append(minus, core, plus);

            const clamp = (v) => Math.min(max, Math.max(min, v));
            const fmt = (v) => (dec ? v.toFixed(dec) : String(Math.round(v)));
            const current = () => { const v = parseFloat(input.value); return isNaN(v) ? null : v; };
            const apply = (v) => { input.value = fmt(clamp(v)); fire(input); };

            minus.addEventListener('click', () => { const v = current(); apply(v === null ? min : v - step); });
            plus.addEventListener('click', () => { const v = current(); apply(v === null ? (presets[0] ?? step) : v + step); });

            let chipBtns = [];
            if (presets.length) {
                const row = document.createElement('div');
                row.className = 'nw-chips';
                chipBtns = presets.map((p) => {
                    const b = document.createElement('button');
                    b.type = 'button'; b.className = 'nw-chip'; b.textContent = String(p); b.dataset.v = p;
                    b.addEventListener('click', () => {
                        const same = current() === p;
                        if (same) { input.value = ''; fire(input); } else { apply(p); }
                    });
                    row.appendChild(b);
                    return b;
                });
                wrap.insertAdjacentElement('afterend', row);
            }

            const sync = () => {
                const v = current();
                chipBtns.forEach((b) => b.classList.toggle('active', v !== null && Number(b.dataset.v) === v));
            };
            input.addEventListener('input', sync);
            // ค่าที่ถูกตั้งจากโค้ดโดยตรง (เช่นตอนโหลดข้อมูล) ไม่ยิง event จึงให้หน้าเรียก refresh()
            input._nwSync = sync;
            sync();
        }

        function setupQuick(input) {
            const options = String(input.dataset.quick || '').split('|').map((s) => s.trim()).filter(Boolean);
            if (!options.length) return;
            const row = document.createElement('div');
            row.className = 'nw-chips';
            const btns = options.map((text) => {
                const b = document.createElement('button');
                b.type = 'button'; b.className = 'nw-chip'; b.textContent = text;
                b.addEventListener('click', () => {
                    input.value = input.value.trim() === text ? '' : text;
                    fire(input);
                    sync();
                });
                row.appendChild(b);
                return b;
            });
            input.insertAdjacentElement('afterend', row);

            const sync = () => btns.forEach((b) => b.classList.toggle('active', input.value.trim() === b.textContent));
            input.addEventListener('input', sync);
            input._nwSync = sync;
            sync();
        }

        return {
            enhance(root) {
                (root || document).querySelectorAll('input[data-stepper]:not([data-nw])').forEach((el) => { el.dataset.nw = '1'; setupStepper(el); });
                (root || document).querySelectorAll('input[data-quick]:not([data-nw])').forEach((el) => { el.dataset.nw = '1'; setupQuick(el); });
            },
            // เรียกหลังตั้งค่าช่องจากโค้ด เพื่ออัปเดตปุ่มที่ถูกเลือกให้ตรงกับค่า
            refresh(root) {
                (root || document).querySelectorAll('input[data-nw]').forEach((el) => { if (el._nwSync) el._nwSync(); });
            }
        };
    })();
</script>
