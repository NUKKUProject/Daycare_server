<?php include __DIR__ . '/../../include/auth/auth.php'; ?>
<?php checkUserRole(['student']); ?>
<?php include __DIR__ . '/../partials/Header.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_navbar.php'; ?>
<?php require_once __DIR__ . '/../../include/function/pages_referen.php'; ?>
<?php require_once __DIR__ . '/../../include/function/child_functions.php'; ?>
<?php include __DIR__ . '/../../include/auth/auth_dashboard.php'; ?>
<?php
// ผู้ปกครองล็อกอินด้วยรหัสนักเรียน ดูและกรอกสมุดได้เฉพาะลูกของตัวเอง
$studentid = $_SESSION['username'] ?? '';
?>

<style>
    .pn-wrap { max-width: 1100px; margin: 0 auto; }

    /* แท็บสลับส่วน (จอเล็ก) / แสดงสองคอลัมน์ (จอใหญ่) */
    .pn-tabs {
        position: sticky; top: 62px; z-index: 5; display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem;
        background: #f0f4f8; padding: .4rem 0 .6rem; margin-bottom: .4rem;
    }
    .pn-tab {
        position: relative; border: 2px solid #e2e8f0; background: #fff; color: #475569; border-radius: 14px;
        padding: .6rem .3rem; font-weight: 800; font-size: .88rem; line-height: 1.25;
    }
    .pn-tab.active { border-color: #1e4db7; background: linear-gradient(135deg, #0f2460, #1e4db7); color: #fff; }
    .pn-tab .dot {
        display: none; position: absolute; top: 6px; right: 8px; width: 9px; height: 9px; border-radius: 50%;
        background: #22c55e; border: 2px solid #fff;
    }
    .pn-pane { display: none; }
    .pn-pane.active { display: block; }
    .save-bar {
        position: sticky; bottom: 0; z-index: 4; background: linear-gradient(to top, #fff 75%, rgba(255, 255, 255, 0));
        padding: .9rem 0 .2rem; margin-top: .5rem;
    }
    @media (min-width: 992px) {
        .pn-tabs { display: none; }
        .pn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start; }
        .pn-pane { display: block !important; }
        .pn-pane-history { grid-column: 1 / -1; }
    }

    .pn-header {
        background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
        border-radius: 18px; color: #fff; padding: 1.25rem 1.5rem; margin-bottom: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .1); display: flex; align-items: center; gap: 1rem;
    }
    .pn-header .avatar {
        width: 72px; height: 72px; border-radius: 16px; overflow: hidden; background: rgba(255, 255, 255, .18);
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 700; flex-shrink: 0;
    }
    .pn-header .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .pn-header .title { font-size: .85rem; opacity: .75; }
    .pn-header .nick { font-size: clamp(1.4rem, 6vw, 1.9rem); font-weight: 800; line-height: 1.15; }
    .pn-header .full { font-size: .85rem; opacity: .85; }

    .pn-date {
        background: #fff; border-radius: 15px; box-shadow: 0 2px 15px rgba(0, 0, 0, .05);
        padding: .9rem 1rem; margin-bottom: 1rem;
    }
    .pn-date-row { display: flex; gap: .4rem; align-items: stretch; }
    .pn-date-row input { flex: 1 1 auto; min-width: 0; height: 44px; border-radius: 10px; border: 2px solid #e2e8f0; }
    .pn-date-row .btn {
        height: 44px; border-radius: 10px; border: 2px solid #e2e8f0; background: #fff; color: #475569;
        font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; padding: 0 .8rem;
    }
    .pn-date-row .btn:hover { border-color: #1e4db7; color: #1e4db7; }
    .pn-date-title { font-weight: 800; color: #0f2460; margin-top: .6rem; text-align: center; }
    .pn-lock {
        margin-top: .6rem; padding: .55rem .9rem; border-radius: 10px; background: #fff7e0; color: #b45309;
        font-size: .85rem; text-align: center;
    }

    .pn-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 15px rgba(0, 0, 0, .06); margin-bottom: 1rem; overflow: hidden; }
    .pn-card-head {
        padding: .8rem 1.1rem; font-weight: 800; color: #0f2460; background: #eff3ff; border-bottom: 1px solid #dbe5fb;
        display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
    }
    .pn-card-head .stamp { margin-left: auto; font-size: .75rem; font-weight: 600; color: #64748b; }
    .pn-card-body { padding: 1rem 1.1rem; }

    .pn-sec { margin-bottom: 1rem; }
    .pn-sec:last-child { margin-bottom: 0; }
    .pn-sec-title { font-weight: 800; color: #0f2460; font-size: .92rem; margin-bottom: .6rem; }
    .pn-label { display: block; font-weight: 700; color: #334155; font-size: .85rem; margin-bottom: .3rem; }
    .pn-input { height: 46px; border-radius: 12px; border: 2px solid #e2e8f0; background: #f8faff; font-size: .95rem; box-shadow: none; }
    textarea.pn-input { height: auto; }
    .pn-input:focus { border-color: #1e4db7; background: #fff; box-shadow: 0 0 0 4px rgba(30, 77, 183, .1); outline: none; }
    .pn-input:disabled { background: #f1f5f9; color: #475569; }
    .unit { position: relative; }
    .unit .pn-input { padding-right: 3.2rem; }
    .unit::after { content: attr(data-unit); position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; font-weight: 700; pointer-events: none; }

    /* ช่วงเวลาที่บ้าน: เช้าก่อนมาศูนย์ / เย็นและกลางคืน */
    .period { border-radius: 16px; overflow: hidden; border: 2px solid; margin-bottom: 1rem; background: #fff; }
    .period-head { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; color: #fff; }
    .period-head .ico { font-size: 2rem; line-height: 1; font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; }
    .period-head .t { font-weight: 800; font-size: 1.08rem; line-height: 1.2; }
    .period-head .s { font-size: .78rem; opacity: .92; }
    .period-body { padding: 1rem; }
    .period.morning { border-color: #f59e0b; }
    .period.morning .period-head { background: linear-gradient(135deg, #f59e0b, #f97316); }
    .period.night { border-color: #4338ca; }
    .period.night .period-head { background: linear-gradient(135deg, #1e3a8a, #4338ca); }
    .sub-title {
        font-weight: 800; color: #0f2460; font-size: .9rem; margin: 0 0 .65rem; padding-bottom: .3rem;
        border-bottom: 1px dashed #cbd5e1;
    }

    .mood-group { display: grid; grid-template-columns: repeat(5, 1fr); gap: .45rem; }
    .mood-btn {
        border: 2px solid #e2e8f0; background: #f8faff; border-radius: 14px; padding: .55rem .15rem; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; gap: 2px;
    }
    .mood-btn .face { font-size: 2rem; line-height: 1.1; font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; }
    .mood-btn .txt { font-size: .7rem; font-weight: 700; color: #64748b; text-align: center; }
    .mood-btn.active { border-color: #1e4db7; background: #eff3ff; box-shadow: 0 4px 14px rgba(30, 77, 183, .18); }
    .mood-btn.active .txt { color: #0f2460; }
    .mood-btn:disabled { cursor: default; }
    .mood-btn:disabled:not(.active) { opacity: .45; }

    .check-pill { position: relative; margin: 0; cursor: pointer; }
    .check-pill input { position: absolute; opacity: 0; pointer-events: none; }
    .check-pill span {
        display: inline-block; padding: .45rem 1rem; border-radius: 20px; border: 2px solid #e2e8f0; background: #fff;
        color: #475569; font-weight: 600; font-size: .85rem; user-select: none;
    }
    .check-pill input:checked + span { border-color: #1e4db7; background: linear-gradient(135deg, #0f2460, #1e4db7); color: #fff; }
    .check-pill input:disabled + span { opacity: .6; cursor: default; }

    /* ฝั่งครู (อ่านอย่างเดียว) */
    .ro-row { display: flex; flex-wrap: wrap; gap: .25rem .75rem; padding: .55rem 0; border-bottom: 1px dashed #e2e8f0; align-items: baseline; }
    .ro-row:last-child { border-bottom: none; }
    .ro-label { flex: 0 0 130px; font-weight: 700; color: #475569; font-size: .88rem; }
    .ro-value { flex: 1 1 180px; color: #0f2460; font-weight: 600; word-break: break-word; }
    .ro-value .menu { display: block; font-size: .78rem; font-weight: 600; color: #b45309; }
    .ro-value .none { color: #cbd5e1; font-weight: 400; }
    .ro-mood { font-size: 1.5rem; font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; margin-right: 6px; vertical-align: middle; }
    .msg-box { background: #fff7e0; border-left: 4px solid #f59e0b; border-radius: 10px; padding: .7rem 1rem; color: #7a4a00; white-space: pre-wrap; }
    .empty-teacher { text-align: center; padding: 1.2rem; color: #64748b; }
    .empty-teacher .emoji { font-size: 2rem; display: block; }
    .flag-chip { display: inline-block; background: #dcfce7; color: #15803d; border-radius: 20px; padding: 1px 10px; font-size: .78rem; font-weight: 700; margin-right: 4px; }

    .soft-btn {
        border: 2px solid #c7d7f8; background: #eff3ff; color: #1e4db7; border-radius: 12px;
        padding: .45rem 1rem; font-weight: 700; font-size: .88rem;
    }
    .soft-btn:hover:not(:disabled) { background: #dbe7ff; }
    .soft-btn:disabled { opacity: .5; }
    .draft-note {
        background: #eef6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 10px;
        padding: .5rem .9rem; font-size: .85rem; margin-bottom: .9rem; display: none;
    }
    .draft-note a { font-weight: 700; margin-left: .4rem; }
    .btn-save-note {
        width: 100%; border-radius: 12px; padding: .8rem 1.5rem; font-weight: 800; border: none; color: #fff; font-size: 1rem;
        background: linear-gradient(135deg, #0f2460, #1e4db7); box-shadow: 0 4px 16px rgba(15, 36, 96, .3);
    }
    .btn-save-note:hover { color: #fff; background: linear-gradient(135deg, #0a1a4f, #1a43a8); }
    .btn-save-note:disabled { opacity: .7; color: #fff; }

    .hist-list { display: flex; flex-direction: column; }
    .hist-item {
        display: flex; align-items: center; gap: .6rem; padding: .6rem .9rem; border: none; background: none; text-align: left;
        border-bottom: 1px solid #eef2f7; width: 100%;
    }
    .hist-item:hover { background: #f8faff; }
    .hist-item.active { background: #eff3ff; }
    .hist-item .d { flex: 1; font-weight: 700; color: #0f2460; font-size: .9rem; }
    .hist-item .m { font-size: 1.2rem; font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; }
    .hist-item .tag { font-size: .72rem; font-weight: 700; border-radius: 20px; padding: 1px 8px; }
    .hist-item .tag.yes { background: #dcfce7; color: #15803d; }
    .hist-item .tag.no { background: #f1f5f9; color: #94a3b8; }

    @media (max-width: 575.98px) {
        .ro-label { flex: 1 1 100%; }
        .pn-header { padding: 1rem; }
        .pn-header .avatar { width: 60px; height: 60px; }
    }
</style>

<main class="main-content">
    <div class="container-fluid mt-4 pn-wrap">
        <div id="migrationAlert" class="alert alert-warning" style="display:none;"></div>

        <div class="pn-header">
            <div class="avatar" id="hAvatar">-</div>
            <div>
                <div class="title"><i class="bi bi-journal-text me-1"></i>สมุดสื่อสารประจำวัน</div>
                <div class="nick" id="hNick">-</div>
                <div class="full" id="hFull"></div>
            </div>
        </div>

        <div class="pn-date">
            <div class="pn-date-row">
                <button type="button" class="btn" id="datePrev" title="วันก่อนหน้า"><i class="fas fa-chevron-left"></i></button>
                <input type="date" class="form-control" id="pDate">
                <button type="button" class="btn" id="dateNext" title="วันถัดไป"><i class="fas fa-chevron-right"></i></button>
                <button type="button" class="btn" id="dateToday">วันนี้</button>
                <button type="button" class="btn" id="btnPrint" title="พิมพ์สมุดของวันนี้"><i class="bi bi-printer"></i></button>
            </div>
            <div class="pn-date-title" id="dateTitle"></div>
            <div class="pn-lock" id="lockNote" style="display:none;"><i class="bi bi-lock-fill me-1"></i> ผู้ปกครองแก้ไขได้เฉพาะสมุดของวันนี้ (วันอื่นดูได้อย่างเดียว)</div>
        </div>

        <div class="pn-tabs" id="pnTabs">
            <button type="button" class="pn-tab" data-pane="teacher">🏫 จากคุณครู<span class="dot" id="dotTeacher"></span></button>
            <button type="button" class="pn-tab" data-pane="parent">🏠 ที่บ้าน (กรอก)<span class="dot" id="dotParent"></span></button>
            <button type="button" class="pn-tab" data-pane="history">🗓️ ย้อนหลัง</button>
        </div>

        <div class="pn-grid">
        <div class="pn-pane" data-pane="teacher">
        <!-- ข้อมูลจากครู -->
        <div class="pn-card">
            <div class="pn-card-head">🏫 ข้อมูลจากคุณครู (ที่ศูนย์) <span class="stamp" id="teacherStamp"></span></div>
            <div class="pn-card-body" id="teacherBody"></div>
        </div>

        </div>

        <div class="pn-pane" data-pane="parent">
        <!-- ข้อมูลจากผู้ปกครอง -->
        <div class="pn-card">
            <div class="pn-card-head">🏠 ข้อมูลจากผู้ปกครอง (ที่บ้าน) <span class="stamp" id="parentStamp"></span></div>
            <div class="pn-card-body">
                <div class="draft-note" id="draftNote"><i class="bi bi-pencil-square me-1"></i> กู้คืนข้อมูลที่กรอกค้างไว้แล้ว (ยังไม่ได้บันทึก) <a href="#" id="draftClear">ล้างและโหลดใหม่</a></div>
                <div class="mb-3" id="copyRow">
                    <button type="button" class="soft-btn" id="btnCopyYesterday"><i class="bi bi-clipboard-check me-1"></i> ใช้ข้อมูลเมื่อวานเป็นต้นแบบ</button>
                    <div class="form-text">คัดลอกนม เวลานอน/ตื่น เฉพาะที่ซ้ำกันทุกวัน (ไม่คัดลอกอารมณ์และข้อความ)</div>
                </div>
                <fieldset id="parentFields">
                    <div class="pn-sec">
                        <div class="pn-sec-title">อารมณ์ของเด็กที่บ้าน</div>
                        <div class="mood-group" id="pMoodGroup"></div>
                    </div>

                    <!-- ===== ช่วงเช้าก่อนมาศูนย์ ===== -->
                    <div class="period morning">
                        <div class="period-head">
                            <span class="ico">🌅</span>
                            <div>
                                <div class="t">ช่วงเช้าก่อนมาศูนย์</div>
                                <div class="s">ที่บ้าน ตั้งแต่ตื่นนอนจนถึงเวลาส่งเด็ก</div>
                            </div>
                        </div>
                        <div class="period-body">
                            <div class="row g-3">
                                <div class="col-12 col-sm-5"><label class="pn-label" for="hMorningMilk">🥛 ดื่มนม</label><div class="unit" data-unit="มล."><input type="number" class="form-control pn-input" id="hMorningMilk" data-stepper="step=10;min=0;max=600;presets=60|90|120|150|180|210|240" min="0" inputmode="numeric"></div></div>
                                <div class="col-12 col-sm-7"><label class="pn-label" for="hMorningFood">🍚 อาหารเช้า (ปริมาณ/คุณภาพ)</label><input type="text" class="form-control pn-input" id="hMorningFood" data-quick="ทานหมด|ทานได้ดี|ครึ่งหนึ่ง|ทานน้อย|ไม่ทาน" maxlength="500"></div>
                                <div class="col-12">
                                    <label class="pn-label" for="pDropOff">🚗 ส่งเด็กเวลา <small class="text-muted fw-normal" id="dropOffHint"></small></label>
                                    <input type="time" class="form-control pn-input" id="pDropOff" style="max-width:200px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== ช่วงเย็นและกลางคืน ===== -->
                    <div class="period night">
                        <div class="period-head">
                            <span class="ico">🌙</span>
                            <div>
                                <div class="t">ช่วงเย็นและกลางคืน</div>
                                <div class="s">ที่บ้าน ตั้งแต่รับกลับจากศูนย์จนถึงเข้านอน</div>
                            </div>
                        </div>
                        <div class="period-body">
                            <div class="sub-title">🌆 ช่วงเย็น</div>
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-5"><label class="pn-label" for="hEveningMilk">🥛 ดื่มนม</label><div class="unit" data-unit="มล."><input type="number" class="form-control pn-input" id="hEveningMilk" data-stepper="step=10;min=0;max=600;presets=60|90|120|150|180|210|240" min="0" inputmode="numeric"></div></div>
                                <div class="col-12 col-sm-7"><label class="pn-label" for="hEveningFood">🍚 อาหารเย็น (ปริมาณ/คุณภาพ)</label><input type="text" class="form-control pn-input" id="hEveningFood" data-quick="ทานหมด|ทานได้ดี|ครึ่งหนึ่ง|ทานน้อย|ไม่ทาน" maxlength="500"></div>
                            </div>

                            <div class="sub-title">😴 กลางคืน</div>
                            <div class="row g-3">
                                <div class="col-12 col-sm-4"><label class="pn-label" for="hSleep">กลางคืนนอนหลับ</label><div class="unit" data-unit="ชม."><input type="number" class="form-control pn-input" id="hSleep" data-stepper="step=0.5;min=0;max=16;presets=8|9|10|11|12" min="0" max="24" step="0.5" inputmode="decimal"></div></div>
                                <div class="col-6 col-sm-4"><label class="pn-label" for="hBedtime">เข้านอนเวลา</label><input type="time" class="form-control pn-input" id="hBedtime"></div>
                                <div class="col-6 col-sm-4"><label class="pn-label" for="hWake">ตื่นนอนเวลา</label><input type="time" class="form-control pn-input" id="hWake"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== พัฒนาการ (ไม่ผูกกับช่วงเวลา) ===== -->
                    <div class="pn-sec">
                        <div class="pn-sec-title">🧸 พัฒนาการของลูก</div>
                        <div class="d-flex flex-wrap gap-2">
                            <label class="check-pill"><input type="checkbox" id="hStopDiaper"><span>เลิกใส่แพมเพิร์ส</span></label>
                            <label class="check-pill"><input type="checkbox" id="hStopBottle"><span>เลิกดื่มนมขวด</span></label>
                        </div>
                    </div>

                    <div class="pn-sec">
                        <div class="pn-sec-title">💬 สื่อสารจากผู้ปกครองถึงครู</div>
                        <textarea class="form-control pn-input" id="pMessage" rows="3" placeholder="เช่น เมื่อคืนลูกไม่ค่อยสบาย ฝากช่วยดูแลเป็นพิเศษ"></textarea>
                    </div>
                </fieldset>

                <div class="save-bar"><button type="button" class="btn btn-save-note" id="btnSave"><i class="bi bi-check-circle me-1"></i> บันทึกสมุดของวันนี้</button></div>
            </div>
        </div>

        </div>

        <div class="pn-pane pn-pane-history" data-pane="history">
        <!-- ย้อนหลัง -->
        <div class="pn-card">
            <div class="pn-card-head">🗓️ ย้อนหลัง 30 วัน</div>
            <div id="histBody"></div>
        </div>
        </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../partials/notebook_widgets.php'; ?>
<script>
    const API = '../../include/function/daily_notebook_api.php';
    const STUDENT_ID = <?php echo json_encode($studentid); ?>;
    const MOODS = [
        { key: 'happy', face: '😊', text: 'มีความสุข' },
        { key: 'scared', face: '😨', text: 'กลัว' },
        { key: 'cry', face: '😢', text: 'ร้องไห้' },
        { key: 'angry', face: '😠', text: 'โกรธ/หงุดหงิด' },
        { key: 'normal', face: '😐', text: 'ปกติ' }
    ];

    let canEdit = false;
    let menu = {};
    let paneInit = false;

    // สลับส่วนที่แสดง (เฉพาะจอเล็ก จอใหญ่แสดงสองคอลัมน์ด้วย CSS)
    function setPane(name) {
        document.querySelectorAll('.pn-pane').forEach((p) => p.classList.toggle('active', p.dataset.pane === name));
        document.querySelectorAll('.pn-tab').forEach((b) => b.classList.toggle('active', b.dataset.pane === name));
    }

    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
    const moodOf = (k) => MOODS.find((m) => m.key === k);
    const hhmm = (v) => (v ? String(v).slice(0, 5) : '');
    const isTrue = (v) => v === true || v === 't';

    async function api(action, params = {}, body = null) {
        const url = `${API}?action=${action}&` + new URLSearchParams(params).toString();
        const res = await fetch(body ? API : url, body ? {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action, ...body })
        } : undefined);
        let data;
        try { data = await res.json(); } catch (e) { throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง'); }
        if (data.status === 'needs_migration') {
            byId('migrationAlert').style.display = '';
            byId('migrationAlert').textContent = 'ระบบสมุดสื่อสารยังไม่พร้อมใช้งาน กรุณาแจ้งผู้ดูแลระบบ';
            throw new Error(data.message);
        }
        if (data.status !== 'success') throw new Error(data.message || 'เกิดข้อผิดพลาด');
        return data;
    }

    const toast = (icon, title) => Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 1800, timerProgressBar: true });
    const showError = (e) => Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message, confirmButtonText: 'ตกลง' });

    function todayStr() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    function thaiDate(s, long = true) {
        const m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return '-';
        return new Date(+m[1], +m[2] - 1, +m[3]).toLocaleDateString('th-TH',
            long ? { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' } : { weekday: 'short', day: 'numeric', month: 'short' });
    }

    function thaiDateTime(s) {
        const d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d) ? '' : d.toLocaleString('th-TH', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) + ' น.';
    }

    function shiftDate(days) {
        const base = byId('pDate').value ? new Date(byId('pDate').value + 'T00:00:00') : new Date();
        base.setDate(base.getDate() + days);
        byId('pDate').value = `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}-${String(base.getDate()).padStart(2, '0')}`;
        loadReport();
    }

    // ===== อารมณ์ =====
    function buildMoodButtons() {
        const box = byId('pMoodGroup');
        box.innerHTML = MOODS.map((m) => `<button type="button" class="mood-btn" data-mood="${m.key}" title="${esc(m.text)}">
            <span class="face">${m.face}</span><span class="txt">${esc(m.text)}</span></button>`).join('');
        box.addEventListener('click', (e) => {
            const b = e.target.closest('.mood-btn');
            if (!b || b.disabled) return;
            const was = b.classList.contains('active');
            box.querySelectorAll('.mood-btn').forEach((x) => x.classList.remove('active'));
            if (!was) b.classList.add('active');
        });
    }
    const getMood = () => document.querySelector('#pMoodGroup .mood-btn.active')?.dataset.mood || '';
    const setMood = (key) => document.querySelectorAll('#pMoodGroup .mood-btn').forEach((b) => b.classList.toggle('active', b.dataset.mood === key));

    // ===== ฝั่งครู (อ่านอย่างเดียว) =====
    function renderTeacher(r) {
        const row = (label, html) => `<div class="ro-row"><div class="ro-label">${label}</div><div class="ro-value">${html}</div></div>`;
        const none = '<span class="none">—</span>';
        const ml = (v) => (v === null || v === undefined || v === '' ? none : `${esc(v)} มล.`);
        const meal = (label, menuText, amount) => row(label,
            `${menuText ? `<span class="menu">เมนู: ${esc(menuText)}</span>` : ''}${amount ? esc(amount) : none}`);

        const hasTeacher = r && r.teacher_updated_at;
        let html = '';

        if (!hasTeacher) {
            html += `<div class="empty-teacher"><span class="emoji">📝</span>คุณครูยังไม่ได้บันทึกข้อมูลของวันนี้</div>`;
            // เมนูของวันนั้น แสดงให้เห็นแม้ครูยังไม่กรอก
            const lines = [['morning_snack', 'อาหารว่างเช้า'], ['lunch', 'อาหารกลางวัน'], ['afternoon_snack', 'อาหารว่างบ่าย']]
                .filter(([k]) => menu[k]);
            if (lines.length) {
                html += lines.map(([k, l]) => row(l, esc(menu[k]))).join('');
            }
            byId('teacherBody').innerHTML = html;
            byId('teacherStamp').textContent = '';
            byId('dotTeacher').style.display = 'none';
            return;
        }

        const mood = moodOf(r.teacher_mood);
        html += row('อารมณ์ที่ศูนย์', mood ? `<span class="ro-mood">${mood.face}</span>${esc(mood.text)}` : none);
        html += row('🥛 นม', `เช้า: ${ml(r.center_morning_milk_ml)} &nbsp;·&nbsp; บ่าย: ${ml(r.center_afternoon_milk_ml)}`);
        html += meal('อาหารว่างเช้า', menu.morning_snack, r.center_morning_snack_amount);
        html += meal('อาหารกลางวัน', menu.lunch, r.center_lunch_amount);
        html += meal('อาหารว่างบ่าย', menu.afternoon_snack, r.center_afternoon_snack_amount);
        html += row('😴 นอนกลางวัน', r.center_nap_hours !== null && r.center_nap_hours !== '' ? `${esc(r.center_nap_hours)} ชม.` : none);
        html += row('🚽 ขับถ่าย', `ปัสสาวะ ${r.center_urine_count ?? '—'} ครั้ง &nbsp;·&nbsp; อุจจาระ ${r.center_stool_count ?? '—'} ครั้ง`);

        const flags = [];
        if (isTrue(r.center_stopped_diaper)) flags.push('<span class="flag-chip">เลิกใส่แพมเพิร์ส</span>');
        if (isTrue(r.center_stopped_bottle)) flags.push('<span class="flag-chip">เลิกดื่มนมขวด</span>');
        if (flags.length) html += row('พัฒนาการ', flags.join(''));

        if (r.activities) html += row('🎨 กิจกรรม', esc(r.activities));
        if (r.teacher_message) html += `<div class="pn-sec-title mt-3">💬 ข้อความจากคุณครู</div><div class="msg-box">${esc(r.teacher_message)}</div>`;

        byId('teacherBody').innerHTML = html;
        byId('teacherStamp').textContent = 'บันทึกล่าสุด ' + thaiDateTime(r.teacher_updated_at);
        byId('dotTeacher').style.display = 'block';
    }

    // ===== ฝั่งผู้ปกครอง =====
    function fillParent(r, att) {
        r = r || {};
        setMood(r.parent_mood || '');
        byId('pMessage').value = r.parent_message || '';
        byId('hMorningMilk').value = r.home_morning_milk_ml ?? '';
        byId('hMorningFood').value = r.home_morning_food || '';
        byId('hEveningMilk').value = r.home_evening_milk_ml ?? '';
        byId('hEveningFood').value = r.home_evening_food || '';
        byId('hSleep').value = r.home_sleep_hours ?? '';
        byId('hBedtime').value = hhmm(r.home_bedtime);
        byId('hWake').value = hhmm(r.home_wake_time);
        byId('hStopDiaper').checked = isTrue(r.home_stopped_diaper);
        byId('hStopBottle').checked = isTrue(r.home_stopped_bottle);

        const saved = hhmm(r.drop_off_time);
        byId('pDropOff').value = saved || (att && att.checkin_time) || '';
        byId('dropOffHint').textContent = !saved && att && att.checkin_time ? '(เติมจากเวลาเช็คชื่อเข้า)' : '';

        byId('parentStamp').textContent = r.parent_updated_at ? 'บันทึกล่าสุด ' + thaiDateTime(r.parent_updated_at) : 'ยังไม่ได้บันทึก';
        byId('dotParent').style.display = r.parent_updated_at ? 'block' : 'none';
    }

    function collectParent() {
        return {
            parent_mood: getMood(), parent_message: byId('pMessage').value,
            drop_off_time: byId('pDropOff').value,
            home_morning_milk_ml: byId('hMorningMilk').value, home_morning_food: byId('hMorningFood').value,
            home_evening_milk_ml: byId('hEveningMilk').value, home_evening_food: byId('hEveningFood').value,
            home_sleep_hours: byId('hSleep').value, home_bedtime: byId('hBedtime').value, home_wake_time: byId('hWake').value,
            home_stopped_diaper: byId('hStopDiaper').checked, home_stopped_bottle: byId('hStopBottle').checked
        };
    }

    // ===== ร่างอัตโนมัติ (กันข้อมูลที่พิมพ์ค้างหาย) =====
    let dirty = false;
    let draftTimer = null;
    const draftKey = () => `nbDraft:${STUDENT_ID}:${byId('pDate').value}`;
    const FIELD_IDS = ['pMessage', 'pDropOff', 'hMorningMilk', 'hMorningFood', 'hEveningMilk', 'hEveningFood', 'hSleep', 'hBedtime', 'hWake'];
    const CHECK_IDS = ['hStopDiaper', 'hStopBottle'];

    function readDraft() {
        try { return JSON.parse(localStorage.getItem(draftKey()) || 'null'); } catch (e) { return null; }
    }
    function clearDraft() {
        try { localStorage.removeItem(draftKey()); } catch (e) { /* ใช้งานต่อได้แม้เก็บร่างไม่ได้ */ }
        dirty = false;
        byId('draftNote').style.display = 'none';
    }
    function scheduleDraft() {
        if (!canEdit) return;
        dirty = true;
        clearTimeout(draftTimer);
        draftTimer = setTimeout(() => {
            const d = { mood: getMood() };
            FIELD_IDS.forEach((id) => { d[id] = byId(id).value; });
            CHECK_IDS.forEach((id) => { d[id] = byId(id).checked; });
            try { localStorage.setItem(draftKey(), JSON.stringify(d)); } catch (e) { /* ไม่เป็นไร */ }
        }, 400);
    }
    function applyDraft(d) {
        setMood(d.mood || '');
        FIELD_IDS.forEach((id) => { if (d[id] !== undefined) byId(id).value = d[id]; });
        CHECK_IDS.forEach((id) => { if (d[id] !== undefined) byId(id).checked = !!d[id]; });
        NotebookWidgets.refresh(byId('parentFields'));
        byId('draftNote').style.display = 'block';
    }

    async function copyYesterday() {
        const d = new Date(byId('pDate').value + 'T00:00:00');
        d.setDate(d.getDate() - 1);
        const ds = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        try {
            const r = (await api('report', { student_id: STUDENT_ID, date: ds })).data.report;
            if (!r || !r.parent_updated_at) {
                toast('info', 'เมื่อวานยังไม่มีข้อมูลฝั่งผู้ปกครอง');
                return;
            }
            byId('hMorningMilk').value = r.home_morning_milk_ml ?? '';
            byId('hEveningMilk').value = r.home_evening_milk_ml ?? '';
            byId('hSleep').value = r.home_sleep_hours ?? '';
            byId('hBedtime').value = hhmm(r.home_bedtime);
            byId('hWake').value = hhmm(r.home_wake_time);
            byId('hStopDiaper').checked = isTrue(r.home_stopped_diaper);
            byId('hStopBottle').checked = isTrue(r.home_stopped_bottle);
            NotebookWidgets.refresh(byId('parentFields'));
            scheduleDraft();
            toast('success', 'คัดลอกข้อมูลของเมื่อวานแล้ว ตรวจสอบแล้วกดบันทึก');
        } catch (e) { showError(e); }
    }

    window.addEventListener('beforeunload', (e) => {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

    function applyEditState() {
        const date = byId('pDate').value;
        canEdit = date === todayStr();
        if (paneInit) setPane(canEdit ? 'parent' : 'teacher');
        byId('parentFields').disabled = !canEdit;
        byId('btnSave').style.display = canEdit ? '' : 'none';
        byId('copyRow').style.display = canEdit ? '' : 'none';
        byId('lockNote').style.display = canEdit ? 'none' : '';
        byId('dateTitle').textContent = thaiDate(date) + (canEdit ? ' (วันนี้)' : '');
    }

    async function loadReport() {
        applyEditState();
        try {
            const data = (await api('report', { student_id: STUDENT_ID, date: byId('pDate').value })).data;
            const c = data.child;

            const nick = (c.nickname || '').trim();
            byId('hNick').textContent = nick ? 'น้อง' + nick : [c.prefix_th, c.firstname_th].filter(Boolean).join(' ');
            byId('hFull').textContent = [c.prefix_th, c.firstname_th, c.lastname_th].filter(Boolean).join(' ') + (c.classroom ? ' · ห้อง ' + c.classroom : '');
            const initial = (c.firstname_th || nick || '-').charAt(0).toUpperCase();
            const av = byId('hAvatar');
            av.innerHTML = '';
            if (c.profile_image) {
                const img = document.createElement('img');
                img.src = c.profile_image; img.alt = 'รูปนักเรียน';
                img.onerror = () => { av.innerHTML = ''; av.textContent = initial; };
                av.appendChild(img);
            } else { av.textContent = initial; }

            menu = data.menu || {};
            if (!paneInit) { paneInit = true; setPane(canEdit ? 'parent' : 'teacher'); }
            renderTeacher(data.report);
            fillParent(data.report, data.attendance);
            NotebookWidgets.refresh(byId('parentFields'));
            dirty = false;
            byId('draftNote').style.display = 'none';
            if (canEdit) {
                const draft = readDraft();
                if (draft) applyDraft(draft);
            }
        } catch (e) {
            byId('teacherBody').innerHTML = `<div class="empty-teacher"><span class="emoji">⚠️</span>${esc(e.message)}</div>`;
        }
        loadHistory();
    }

    async function loadHistory() {
        try {
            const rows = (await api('history', { student_id: STUDENT_ID, days: 30 })).data;
            const cur = byId('pDate').value;
            if (!rows.length) {
                byId('histBody').innerHTML = '<div class="empty-teacher">ยังไม่มีสมุดย้อนหลัง</div>';
                return;
            }
            const tag = (yes, label) => `<span class="tag ${yes ? 'yes' : 'no'}">${label}${yes ? ' ✓' : ''}</span>`;
            byId('histBody').innerHTML = '<div class="hist-list">' + rows.map((r) => {
                const pm = moodOf(r.parent_mood), tm = moodOf(r.teacher_mood);
                return `<button type="button" class="hist-item ${r.report_date === cur ? 'active' : ''}" data-date="${esc(r.report_date)}">
                    <span class="d">${esc(thaiDate(r.report_date, false))}</span>
                    <span class="m">${pm ? pm.face : ''}</span>${tag(isTrue(r.parent_filled), 'บ้าน')}
                    <span class="m">${tm ? tm.face : ''}</span>${tag(isTrue(r.teacher_filled), 'ศูนย์')}
                </button>`;
            }).join('') + '</div>';
        } catch (e) {
            byId('histBody').innerHTML = '';
        }
    }

    async function saveParent() {
        if (!canEdit) return;
        const btn = byId('btnSave');
        const html = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
        try {
            await api('save_report', {}, { student_id: STUDENT_ID, date: byId('pDate').value, side: 'parent', ...collectParent() });
            clearDraft();
            toast('success', 'บันทึกสมุดเรียบร้อยแล้ว');
            await loadReport();
        } catch (e) {
            showError(e);
        } finally {
            btn.disabled = false;
            btn.innerHTML = html;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        buildMoodButtons();
        NotebookWidgets.enhance(byId('parentFields'));
        byId('pDate').value = todayStr();
        byId('pDate').addEventListener('change', loadReport);
        byId('datePrev').addEventListener('click', () => shiftDate(-1));
        byId('dateNext').addEventListener('click', () => shiftDate(1));
        byId('dateToday').addEventListener('click', () => { byId('pDate').value = todayStr(); loadReport(); });
        byId('btnPrint').addEventListener('click', () => {
            window.open(`../daily_notebook_print.php?date=${encodeURIComponent(byId('pDate').value)}`, '_blank');
        });
        byId('pnTabs').addEventListener('click', (e) => {
            const b = e.target.closest('.pn-tab');
            if (b) { setPane(b.dataset.pane); window.scrollTo({ top: 0, behavior: 'smooth' }); }
        });
        byId('btnSave').addEventListener('click', saveParent);
        byId('btnCopyYesterday').addEventListener('click', copyYesterday);
        byId('draftClear').addEventListener('click', (e) => { e.preventDefault(); clearDraft(); loadReport(); });
        byId('parentFields').addEventListener('input', scheduleDraft);
        byId('parentFields').addEventListener('click', (e) => { if (e.target.closest('.mood-btn')) scheduleDraft(); });
        byId('histBody').addEventListener('click', (e) => {
            const b = e.target.closest('button[data-date]');
            if (b) {
                byId('pDate').value = b.dataset.date;
                setPane(b.dataset.date === todayStr() ? 'parent' : 'teacher');
                loadReport();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
        loadReport();
    });
</script>
