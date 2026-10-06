<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/function/child_functions.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
?>

<style>
    .nb-header {
        background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%);
        padding: 1.75rem 2rem;
        border-radius: 15px;
        color: #fff;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
    }
    .nb-header h2 { margin: 0; font-size: 1.5rem; font-weight: 800; }
    .nb-header .sub { opacity: .8; font-size: .9rem; margin-top: 4px; }
    .nb-header .btn-menu {
        background: rgba(255, 255, 255, .16); color: #fff; border: 1px solid rgba(255, 255, 255, .35);
        border-radius: 12px; font-weight: 700; padding: .5rem 1.1rem;
    }
    .nb-header .btn-menu:hover { background: rgba(255, 255, 255, .28); color: #fff; }

    .nb-card { background: #fff; border-radius: 15px; box-shadow: 0 2px 15px rgba(0, 0, 0, .05); padding: 1.25rem; margin-bottom: 1.25rem; }

    .nb-card .form-label { font-size: .8rem; font-weight: 700; color: #475569; margin-bottom: .3rem; }
    .nb-card .form-control, .nb-card .form-select { height: 42px; border-radius: 10px; border: 2px solid #e2e8f0; }
    .nb-card .form-control:focus, .nb-card .form-select:focus { border-color: #1e4db7; box-shadow: 0 0 0 4px rgba(30, 77, 183, .1); }

    .date-nav { display: flex; gap: .4rem; align-items: stretch; }
    .date-nav input { flex: 1 1 auto; min-width: 0; }
    .date-nav .btn {
        height: 42px; border-radius: 10px; border: 2px solid #e2e8f0; background: #fff; color: #475569;
        font-weight: 600; white-space: nowrap; padding: 0 .8rem; display: inline-flex; align-items: center; justify-content: center;
    }
    .date-nav .btn:hover { border-color: #1e4db7; color: #1e4db7; }

    .menu-strip {
        display: flex; flex-wrap: wrap; gap: .5rem .9rem; align-items: center;
        background: #fff7e0; border: 1px solid #fcd34d; border-radius: 12px; padding: .7rem 1rem; margin-bottom: 1.25rem;
        font-size: .9rem; color: #7a4a00;
    }
    .menu-strip b { color: #92400e; }
    .menu-strip .empty { color: #b45309; }

    .sum-row { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .sum-pill { background: #eff3ff; color: #1e4db7; border: 1px solid #c7d7f8; border-radius: 20px; padding: 3px 14px; font-weight: 700; font-size: .85rem; }

    .nb-scroll { max-height: 600px; overflow: auto; -webkit-overflow-scrolling: touch; }
    .nb-table { margin: 0; min-width: 760px; white-space: nowrap; border-collapse: separate; border-spacing: 0; }
    .nb-table thead th {
        position: sticky; top: 0; z-index: 2; background: #eff3ff; color: #0f2460; font-size: .82rem;
        border-bottom: 2px solid #c7d7f8; text-align: center;
    }
    .nb-table td { vertical-align: middle; text-align: center; font-size: .9rem; }
    .nb-table td.name-col { text-align: left; }

    .nickname-text {
        display: inline-block; background: #1e4db7; color: #fff; font-weight: 700; padding: 2px 12px;
        border-radius: 20px; white-space: nowrap;
    }

    .fill-chip { display: inline-flex; align-items: center; gap: 4px; border-radius: 20px; padding: 2px 10px; font-size: .78rem; font-weight: 700; }
    .fill-chip.yes { background: #dcfce7; color: #15803d; }
    .fill-chip.no { background: #f1f5f9; color: #94a3b8; }
    .mood-emoji { font-size: 1.3rem; margin-right: 4px; font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; }

    @media (max-width: 768px) {
        .nb-table th.nick-col, .nb-table td.nick-col { position: sticky; left: 0; }
        .nb-table td.nick-col { z-index: 1; background-color: #fff; }
        .nb-table th.nick-col { z-index: 3; background-color: #eff3ff; }
        .nb-table th.nick-col::after, .nb-table td.nick-col::after {
            content: ""; position: absolute; top: 0; bottom: 0; right: -6px; width: 6px; pointer-events: none;
            background: linear-gradient(to right, rgba(15, 36, 96, .12), transparent);
        }
        .nb-table th.nick-col::before, .nb-table td.nick-col::before {
            content: ""; position: absolute; top: 0; bottom: 0; left: -6px; width: 6px; pointer-events: none;
            background: linear-gradient(to left, rgba(15, 36, 96, .12), transparent);
        }
    }

    .state-box { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .state-box .emoji { font-size: 2.4rem; display: block; margin-bottom: .4rem; }

    /* ===== Modal ===== */
    #noteModal .modal-content { border: none; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 70px rgba(10, 30, 80, .2); }
    #noteModal .modal-header { background: linear-gradient(135deg, #0f2460 0%, #1a3a8f 60%, #1e4db7 100%); border: none; padding: 1.25rem 1.5rem; }
    #noteModal .modal-title { color: #fff; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 12px; }
    #noteModal .title-icon { width: 40px; height: 40px; border-radius: 12px; background: rgba(255, 255, 255, .16); display: flex; align-items: center; justify-content: center; }
    #noteModal .title-sub { display: block; font-weight: 400; font-size: .78rem; color: rgba(255, 255, 255, .65); }
    #noteModal .btn-close { filter: brightness(0) invert(1); opacity: .7; }
    #noteModal .modal-body { background: #f0f4f8; padding: 1.25rem; }
    #noteModal .modal-footer { background: #f0f4f8; border-top: 1px solid #e2e8f0; padding: .9rem 1.25rem; gap: .6rem; }

    .child-card {
        background: #fff; border-radius: 16px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;
        margin-bottom: 1rem; box-shadow: 0 4px 16px rgba(15, 36, 96, .1); border-left: 5px solid #1e4db7;
    }
    .child-card .avatar {
        width: 80px; height: 80px; border-radius: 16px; background: linear-gradient(135deg, #0f2460, #1e4db7);
        display: flex; align-items: center; justify-content: center; overflow: hidden; color: #fff; font-size: 1.6rem; font-weight: 700; flex-shrink: 0;
    }
    .child-card .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .child-card .nick { font-size: clamp(1.4rem, 6vw, 1.9rem); font-weight: 800; color: #0f2460; line-height: 1.15; white-space: nowrap; }
    .child-card .full { font-size: .88rem; color: #64748b; }
    .child-card .pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
    .child-card .pill { background: #eff3ff; color: #1e4db7; border: 1px solid #c7d7f8; border-radius: 20px; padding: 1px 10px; font-size: .74rem; font-weight: 600; }

    .side-tabs .nav-link { border-radius: 25px; font-weight: 700; color: #475569; }
    .side-tabs .nav-link.active { background: linear-gradient(135deg, #0f2460, #1e4db7); color: #fff; }
    .side-tabs .filled-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; margin-left: 6px; }

    .nb-sec { background: #fff; border-radius: 16px; padding: 1.1rem 1.25rem; margin-bottom: .9rem; box-shadow: 0 2px 10px rgba(15, 36, 96, .07); }
    .nb-sec-title { font-weight: 800; color: #0f2460; font-size: .95rem; margin-bottom: .75rem; display: flex; align-items: center; gap: 8px; }
    .nb-label { display: block; font-weight: 700; color: #334155; font-size: .85rem; margin-bottom: .3rem; }
    .nb-input { height: 44px; border-radius: 12px; border: 2px solid #e2e8f0; background: #f8faff; font-size: .95rem; box-shadow: none; }
    textarea.nb-input { height: auto; }
    .nb-input:focus { border-color: #1e4db7; background: #fff; box-shadow: 0 0 0 4px rgba(30, 77, 183, .1); outline: none; }
    .unit { position: relative; }
    .unit .nb-input { padding-right: 3.2rem; }
    .unit::after { content: attr(data-unit); position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; font-weight: 700; pointer-events: none; }

    .mood-group { display: grid; grid-template-columns: repeat(5, 1fr); gap: .5rem; }
    .mood-btn {
        border: 2px solid #e2e8f0; background: #f8faff; border-radius: 14px; padding: .5rem .2rem; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; gap: 2px; transition: all .15s ease;
    }
    .mood-btn .face { font-size: 1.9rem; line-height: 1.1; font-family: "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", sans-serif; }
    .mood-btn .txt { font-size: .72rem; font-weight: 700; color: #64748b; text-align: center; }
    .mood-btn:hover { border-color: #93b4f0; }
    .mood-btn.active { border-color: #1e4db7; background: #eff3ff; box-shadow: 0 4px 14px rgba(30, 77, 183, .18); }
    .mood-btn.active .txt { color: #0f2460; }

    .meal-hint { font-size: .8rem; margin-bottom: .3rem; color: #b45309; background: #fff7e0; border-radius: 8px; padding: 2px 10px; display: inline-block; }
    .meal-hint.none { color: #94a3b8; background: #f1f5f9; }

    .check-pill { position: relative; margin: 0; cursor: pointer; }
    .check-pill input { position: absolute; opacity: 0; pointer-events: none; }
    .check-pill span {
        display: inline-block; padding: .45rem 1rem; border-radius: 20px; border: 2px solid #e2e8f0; background: #fff;
        color: #475569; font-weight: 600; font-size: .85rem; user-select: none;
    }
    .check-pill input:checked + span { border-color: #1e4db7; background: linear-gradient(135deg, #0f2460, #1e4db7); color: #fff; }

    .btn-save-note {
        border-radius: 12px; padding: .6rem 1.5rem; font-weight: 800; border: none; color: #fff;
        background: linear-gradient(135deg, #0f2460, #1e4db7); box-shadow: 0 4px 16px rgba(15, 36, 96, .3);
    }
    .btn-save-note:hover { color: #fff; background: linear-gradient(135deg, #0a1a4f, #1a43a8); }
    .btn-save-note:disabled { opacity: .7; color: #fff; }
    .btn-soft { border-radius: 12px; padding: .6rem 1.2rem; font-weight: 700; border: 2px solid #e2e8f0; background: #fff; color: #64748b; }
    .btn-soft:hover { background: #f1f5f9; color: #334155; }

    @media (max-width: 575.98px) {
        #noteModal .modal-content { border-radius: 0; }
        #noteModal .modal-body { padding: .9rem; }
        .child-card { padding: .8rem; gap: .75rem; }
        .child-card .avatar { width: 64px; height: 64px; }
        .mood-group { gap: .3rem; }
        .mood-btn .face { font-size: 1.6rem; }
        #noteModal .modal-footer .btn { flex: 1 1 100%; }
    }
</style>

<main class="main-content">
    <div class="container-fluid mt-4">
        <div class="nb-header">
            <div>
                <h2><i class="bi bi-journal-text me-2"></i>สมุดสื่อสารประจำวัน</h2>
                <div class="sub">ข้อมูลจากผู้ปกครอง (ที่บ้าน) และบันทึกของครู (ที่ศูนย์) ของเด็กแต่ละคน</div>
            </div>
            <a href="daily_menu.php" class="btn btn-menu"><i class="bi bi-egg-fried me-1"></i> เมนูอาหารรายวัน</a>
        </div>

        <div id="migrationAlert" class="alert alert-warning" style="display:none;"></div>

        <div class="nb-card">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="fGroup">กลุ่มเรียน</label>
                    <select id="fGroup" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <?php
                        foreach (get_childgroup() as $group) {
                            if (!empty($group['child_group'])) {
                                $g = htmlspecialchars($group['child_group']);
                                echo "<option value='{$g}'>{$g}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="fRoom">ห้องเรียน</label>
                    <select id="fRoom" class="form-select"><option value="">ทั้งหมด</option></select>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="fDate">วันที่</label>
                    <div class="date-nav">
                        <button type="button" class="btn" id="datePrev" title="วันก่อนหน้า"><i class="fas fa-chevron-left"></i></button>
                        <input type="date" class="form-control" id="fDate">
                        <button type="button" class="btn" id="dateNext" title="วันถัดไป"><i class="fas fa-chevron-right"></i></button>
                        <button type="button" class="btn" id="dateToday">วันนี้</button>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="fSearch">ค้นหา</label>
                    <input type="text" class="form-control" id="fSearch" placeholder="ชื่อเล่น, ชื่อ-นามสกุล, รหัส" autocomplete="off">
                </div>
            </div>
        </div>

        <div id="menuStrip"></div>
        <div id="sumRow" class="sum-row"></div>

        <div class="nb-card p-0 overflow-hidden">
            <div id="listArea"><div class="state-box"><span class="emoji">📒</span>เลือกกลุ่มหรือห้องเรียนเพื่อดูรายชื่อเด็ก</div></div>
        </div>
    </div>

    <!-- Modal สมุดของเด็ก 1 คน -->
    <div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="title-icon"><i class="bi bi-journal-text"></i></span>
                        <span>สมุดสื่อสารประจำวัน<small class="title-sub" id="mDate">-</small></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="child-card">
                        <div class="avatar" id="mAvatar">-</div>
                        <div class="min-w-0">
                            <div class="nick" id="mNick" style="display:none;"></div>
                            <div class="full" id="mFull">-</div>
                            <div class="pills">
                                <span class="pill"><i class="bi bi-person-badge me-1"></i><span id="mSid">-</span></span>
                                <span class="pill"><i class="bi bi-door-open me-1"></i><span id="mRoom">-</span></span>
                                <span class="pill" id="mAtt">-</span>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-pills side-tabs mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#paneTeacher" type="button">🏫 ที่ศูนย์ (ครู)<span class="filled-dot" id="dotTeacher" style="display:none;"></span></button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#paneParent" type="button">🏠 ที่บ้าน (ผู้ปกครอง)<span class="filled-dot" id="dotParent" style="display:none;"></span></button></li>
                    </ul>

                    <div class="tab-content">
                        <!-- ฝั่งครู -->
                        <div class="tab-pane fade show active" id="paneTeacher">
                            <div class="nb-sec">
                                <div class="nb-sec-title">อารมณ์ของเด็กที่ศูนย์</div>
                                <div class="mood-group" data-mood-group="teacher"></div>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">🥛 นม</div>
                                <div class="row g-3">
                                    <div class="col-6"><label class="nb-label" for="cMorningMilk">ช่วงเช้า</label><div class="unit" data-unit="มล."><input type="number" class="form-control nb-input" id="cMorningMilk" min="0" inputmode="numeric"></div></div>
                                    <div class="col-6"><label class="nb-label" for="cAfternoonMilk">ช่วงบ่าย</label><div class="unit" data-unit="มล."><input type="number" class="form-control nb-input" id="cAfternoonMilk" min="0" inputmode="numeric"></div></div>
                                </div>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">🍽️ การรับประทานอาหาร <small class="text-muted fw-normal">(ระบุปริมาณที่เด็กทาน)</small></div>
                                <div class="mb-3">
                                    <label class="nb-label" for="cMorningSnack">อาหารว่างเช้า</label>
                                    <div class="meal-hint" id="hintMorning"></div>
                                    <input type="text" class="form-control nb-input" id="cMorningSnack" maxlength="200" placeholder="เช่น หมด / ครึ่งหนึ่ง / 3 ช้อน">
                                </div>
                                <div class="mb-3">
                                    <label class="nb-label" for="cLunch">อาหารกลางวัน</label>
                                    <div class="meal-hint" id="hintLunch"></div>
                                    <input type="text" class="form-control nb-input" id="cLunch" maxlength="200" placeholder="เช่น หมด / ครึ่งหนึ่ง / 3 ช้อน">
                                </div>
                                <div>
                                    <label class="nb-label" for="cAfternoonSnack">อาหารว่างบ่าย</label>
                                    <div class="meal-hint" id="hintAfternoon"></div>
                                    <input type="text" class="form-control nb-input" id="cAfternoonSnack" maxlength="200" placeholder="เช่น หมด / ครึ่งหนึ่ง / 3 ช้อน">
                                </div>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">😴 นอนและการขับถ่าย</div>
                                <div class="row g-3">
                                    <div class="col-12 col-sm-4"><label class="nb-label" for="cNap">นอนกลางวัน</label><div class="unit" data-unit="ชม."><input type="number" class="form-control nb-input" id="cNap" min="0" max="24" step="0.5" inputmode="decimal"></div></div>
                                    <div class="col-6 col-sm-4"><label class="nb-label" for="cUrine">ปัสสาวะ</label><div class="unit" data-unit="ครั้ง"><input type="number" class="form-control nb-input" id="cUrine" min="0" inputmode="numeric"></div></div>
                                    <div class="col-6 col-sm-4"><label class="nb-label" for="cStool">อุจจาระ</label><div class="unit" data-unit="ครั้ง"><input type="number" class="form-control nb-input" id="cStool" min="0" inputmode="numeric"></div></div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <label class="check-pill"><input type="checkbox" id="cStopDiaper"><span>เลิกใส่แพมเพิร์ส</span></label>
                                    <label class="check-pill"><input type="checkbox" id="cStopBottle"><span>เลิกดื่มนมขวด</span></label>
                                </div>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">🎨 กิจกรรมและการมีส่วนร่วม</div>
                                <textarea class="form-control nb-input" id="tActivities" rows="2"></textarea>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">💬 ข้อความถึงผู้ปกครอง</div>
                                <textarea class="form-control nb-input" id="tMessage" rows="3" placeholder="ข้อมูลจากคุณครู"></textarea>
                            </div>
                        </div>

                        <!-- ฝั่งผู้ปกครอง -->
                        <div class="tab-pane fade" id="paneParent">
                            <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i> ส่วนนี้ผู้ปกครองกรอกเองได้ ครู/admin กรอกแทนได้ กรณีผู้ปกครองส่งเป็นกระดาษ</div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">อารมณ์ของเด็กที่บ้าน</div>
                                <div class="mood-group" data-mood-group="parent"></div>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">🚗 การส่งเด็ก</div>
                                <label class="nb-label" for="pDropOff">ส่งเด็กเวลา <small class="text-muted fw-normal" id="dropOffHint"></small></label>
                                <input type="time" class="form-control nb-input" id="pDropOff" style="max-width:200px;">
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">🌅 ช่วงเช้าก่อนมาศูนย์</div>
                                <div class="row g-3">
                                    <div class="col-12 col-sm-5"><label class="nb-label" for="hMorningMilk">ดื่มนม</label><div class="unit" data-unit="มล."><input type="number" class="form-control nb-input" id="hMorningMilk" min="0" inputmode="numeric"></div></div>
                                    <div class="col-12 col-sm-7"><label class="nb-label" for="hMorningFood">อาหารเช้า (ปริมาณ/คุณภาพ)</label><input type="text" class="form-control nb-input" id="hMorningFood" maxlength="500"></div>
                                </div>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">🌙 ช่วงเย็นและกลางคืน</div>
                                <div class="row g-3">
                                    <div class="col-12 col-sm-5"><label class="nb-label" for="hEveningMilk">ดื่มนม</label><div class="unit" data-unit="มล."><input type="number" class="form-control nb-input" id="hEveningMilk" min="0" inputmode="numeric"></div></div>
                                    <div class="col-12 col-sm-7"><label class="nb-label" for="hEveningFood">อาหารเย็น (ปริมาณ/คุณภาพ)</label><input type="text" class="form-control nb-input" id="hEveningFood" maxlength="500"></div>
                                    <div class="col-12 col-sm-4"><label class="nb-label" for="hSleep">กลางคืนนอนหลับ</label><div class="unit" data-unit="ชม."><input type="number" class="form-control nb-input" id="hSleep" min="0" max="24" step="0.5" inputmode="decimal"></div></div>
                                    <div class="col-6 col-sm-4"><label class="nb-label" for="hBedtime">เข้านอนเวลา</label><input type="time" class="form-control nb-input" id="hBedtime"></div>
                                    <div class="col-6 col-sm-4"><label class="nb-label" for="hWake">ตื่นนอนเวลา</label><input type="time" class="form-control nb-input" id="hWake"></div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <label class="check-pill"><input type="checkbox" id="hStopDiaper"><span>เลิกใส่แพมเพิร์ส</span></label>
                                    <label class="check-pill"><input type="checkbox" id="hStopBottle"><span>เลิกดื่มนมขวด</span></label>
                                </div>
                            </div>

                            <div class="nb-sec">
                                <div class="nb-sec-title">💬 สื่อสารจากผู้ปกครอง</div>
                                <textarea class="form-control nb-input" id="pMessage" rows="3" placeholder="ข้อความจากผู้ปกครองถึงครู"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>ปิด</button>
                    <button type="button" class="btn btn-save-note" id="btnSave"><i class="bi bi-check-circle me-1"></i>บันทึก</button>
                    <button type="button" class="btn btn-save-note" id="btnSaveNext"><i class="bi bi-arrow-right-circle me-1"></i>บันทึกและคนถัดไป</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const API = '../../include/function/daily_notebook_api.php';
    const MOODS = [
        { key: 'happy', face: '😊', text: 'มีความสุข' },
        { key: 'scared', face: '😨', text: 'กลัว' },
        { key: 'cry', face: '😢', text: 'ร้องไห้' },
        { key: 'angry', face: '😠', text: 'โกรธ/หงุดหงิด' },
        { key: 'normal', face: '😐', text: 'ปกติ' }
    ];
    const ATT = {
        present: ['มาเรียน', 'bg-success'], late: ['มาสาย', 'bg-warning text-dark'],
        absent: ['ไม่มาเรียน', 'bg-danger'], leave: ['ลา', 'bg-primary']
    };

    let roster = [];
    let menus = {};
    let current = null;   // { sid, index }
    let noteModal = null;

    const byId = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
    const moodOf = (key) => MOODS.find((m) => m.key === key);

    async function api(action, params = {}, body = null) {
        const url = `${API}?action=${action}&` + new URLSearchParams(params).toString();
        const res = await fetch(body ? API : url, body ? {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action, ...body })
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

    const toast = (icon, title) => Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 1800, timerProgressBar: true });
    const showError = (e) => Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message, confirmButtonText: 'ตกลง' });

    function todayStr() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    function formatThaiDate(s) {
        const m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return '-';
        return new Date(+m[1], +m[2] - 1, +m[3]).toLocaleDateString('th-TH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function shiftDate(days) {
        const base = byId('fDate').value ? new Date(byId('fDate').value + 'T00:00:00') : new Date();
        base.setDate(base.getDate() + days);
        byId('fDate').value = `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}-${String(base.getDate()).padStart(2, '0')}`;
        loadRoster();
    }

    const fullName = (c) => [c.prefix_th, c.firstname_th, c.lastname_th].filter(Boolean).join(' ');

    // ===== รายชื่อ =====
    async function loadClassrooms() {
        const group = byId('fGroup').value;
        const sel = byId('fRoom');
        sel.innerHTML = '<option value="">ทั้งหมด</option>';
        if (!group) return;
        try {
            const res = await fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(group)}`);
            const data = await res.json();
            (Array.isArray(data) ? data : []).forEach((c) => {
                const o = document.createElement('option');
                o.value = c.classroom_name; o.textContent = c.classroom_name;
                sel.appendChild(o);
            });
        } catch (e) { console.error(e); }
    }

    async function loadRoster() {
        const group = byId('fGroup').value, room = byId('fRoom').value;
        if (!group && !room) {
            roster = []; menus = {};
            byId('menuStrip').innerHTML = '';
            byId('sumRow').innerHTML = '';
            byId('listArea').innerHTML = '<div class="state-box"><span class="emoji">📒</span>เลือกกลุ่มหรือห้องเรียนเพื่อดูรายชื่อเด็ก</div>';
            return;
        }
        byId('listArea').innerHTML = '<div class="state-box"><div class="spinner-border text-primary"></div><div class="mt-2">กำลังโหลด...</div></div>';
        try {
            const data = (await api('roster', { date: byId('fDate').value, child_group: group, classroom: room })).data;
            roster = data.children || [];
            menus = data.menus || {};
            renderRoster();
        } catch (e) {
            roster = [];
            byId('listArea').innerHTML = `<div class="state-box"><span class="emoji">⚠️</span>${esc(e.message)}</div>`;
        }
    }

    function filteredRoster() {
        const q = byId('fSearch').value.trim().toLowerCase();
        if (!q) return roster;
        return roster.filter((c) => (`${c.nickname || ''} ${fullName(c)} ${c.studentid}`).toLowerCase().includes(q));
    }

    function renderMenuStrip() {
        const room = byId('fRoom').value;
        const box = byId('menuStrip');
        if (!room) { box.innerHTML = ''; return; }
        const m = menus[room] || {};
        const parts = [['morning_snack', 'ว่างเช้า'], ['lunch', 'กลางวัน'], ['afternoon_snack', 'ว่างบ่าย']]
            .map(([k, l]) => `<span><b>${l}:</b> ${m[k] ? esc(m[k]) : '<span class="empty">ยังไม่กรอก</span>'}</span>`).join('');
        box.innerHTML = `<div class="menu-strip"><b>🍽️ เมนูวันนี้ ห้อง ${esc(room)}</b>${parts}
            <a href="daily_menu.php" class="ms-auto fw-bold">แก้เมนู</a></div>`;
    }

    function renderRoster() {
        renderMenuStrip();
        const list = filteredRoster();
        const total = roster.length;
        const p = roster.filter((c) => c.parent_filled).length;
        const t = roster.filter((c) => c.teacher_filled).length;
        byId('sumRow').innerHTML = `
            <span class="sum-pill">เด็กทั้งหมด ${total} คน</span>
            <span class="sum-pill">ผู้ปกครองกรอกแล้ว ${p}/${total}</span>
            <span class="sum-pill">ครูกรอกแล้ว ${t}/${total}</span>`;

        if (list.length === 0) {
            byId('listArea').innerHTML = '<div class="state-box"><span class="emoji">📭</span>ไม่พบรายชื่อเด็ก</div>';
            return;
        }
        const chip = (yes, label) => `<span class="fill-chip ${yes ? 'yes' : 'no'}">${yes ? '✓' : '–'} ${label}</span>`;
        const mood = (k) => { const m = moodOf(k); return m ? `<span class="mood-emoji" title="${esc(m.text)}">${m.face}</span>` : ''; };

        byId('listArea').innerHTML = `<div class="nb-scroll"><table class="table table-hover nb-table">
            <thead><tr>
                <th>รหัส</th><th class="nick-col">ชื่อเล่น</th><th class="text-start">ชื่อ-นามสกุล</th><th>ห้อง</th><th>การมาเรียน</th>
                <th>ที่บ้าน (ผู้ปกครอง)</th><th>ที่ศูนย์ (ครู)</th><th></th>
            </tr></thead>
            <tbody>${list.map((c) => {
                const att = ATT[c.att_status];
                return `<tr>
                    <td>${esc(c.studentid)}</td>
                    <td class="nick-col">${c.nickname ? `<span class="nickname-text">${esc(c.nickname)}</span>` : '-'}</td>
                    <td class="name-col">${esc(fullName(c))}</td>
                    <td>${esc(c.classroom)}</td>
                    <td>${att ? `<span class="badge ${att[1]}">${att[0]}</span>` : '<span class="badge bg-secondary">ยังไม่บันทึก</span>'}</td>
                    <td>${mood(c.parent_mood)}${chip(c.parent_filled, c.parent_filled ? 'กรอกแล้ว' : 'ยังไม่กรอก')}</td>
                    <td>${mood(c.teacher_mood)}${chip(c.teacher_filled, c.teacher_filled ? 'กรอกแล้ว' : 'ยังไม่กรอก')}</td>
                    <td><button type="button" class="btn btn-primary btn-sm" data-open="${esc(c.studentid)}"><i class="bi bi-journal-text me-1"></i>เปิดสมุด</button></td>
                </tr>`;
            }).join('')}</tbody></table></div>`;
    }

    // ===== Modal =====
    function buildMoodButtons() {
        document.querySelectorAll('[data-mood-group]').forEach((box) => {
            box.innerHTML = MOODS.map((m) => `<button type="button" class="mood-btn" data-mood="${m.key}" title="${esc(m.text)}">
                <span class="face">${m.face}</span><span class="txt">${esc(m.text)}</span></button>`).join('');
            box.addEventListener('click', (e) => {
                const b = e.target.closest('.mood-btn');
                if (!b) return;
                const was = b.classList.contains('active');
                box.querySelectorAll('.mood-btn').forEach((x) => x.classList.remove('active'));
                if (!was) b.classList.add('active');   // กดซ้ำ = ไม่เลือก
            });
        });
    }

    const getMood = (side) => document.querySelector(`[data-mood-group="${side}"] .mood-btn.active`)?.dataset.mood || '';
    function setMood(side, key) {
        document.querySelectorAll(`[data-mood-group="${side}"] .mood-btn`).forEach((b) => b.classList.toggle('active', b.dataset.mood === key));
    }

    const hhmm = (v) => (v ? String(v).slice(0, 5) : '');

    function fillForm(rep, menu, att) {
        const r = rep || {};
        // ฝั่งครู
        setMood('teacher', r.teacher_mood || '');
        byId('tMessage').value = r.teacher_message || '';
        byId('cMorningMilk').value = r.center_morning_milk_ml ?? '';
        byId('cMorningSnack').value = r.center_morning_snack_amount || '';
        byId('cLunch').value = r.center_lunch_amount || '';
        byId('cAfternoonMilk').value = r.center_afternoon_milk_ml ?? '';
        byId('cAfternoonSnack').value = r.center_afternoon_snack_amount || '';
        byId('cNap').value = r.center_nap_hours ?? '';
        byId('cUrine').value = r.center_urine_count ?? '';
        byId('cStool').value = r.center_stool_count ?? '';
        byId('cStopDiaper').checked = r.center_stopped_diaper === true || r.center_stopped_diaper === 't';
        byId('cStopBottle').checked = r.center_stopped_bottle === true || r.center_stopped_bottle === 't';
        byId('tActivities').value = r.activities || '';

        // ฝั่งผู้ปกครอง
        setMood('parent', r.parent_mood || '');
        byId('pMessage').value = r.parent_message || '';
        byId('hMorningMilk').value = r.home_morning_milk_ml ?? '';
        byId('hMorningFood').value = r.home_morning_food || '';
        byId('hEveningMilk').value = r.home_evening_milk_ml ?? '';
        byId('hEveningFood').value = r.home_evening_food || '';
        byId('hSleep').value = r.home_sleep_hours ?? '';
        byId('hBedtime').value = hhmm(r.home_bedtime);
        byId('hWake').value = hhmm(r.home_wake_time);
        byId('hStopDiaper').checked = r.home_stopped_diaper === true || r.home_stopped_diaper === 't';
        byId('hStopBottle').checked = r.home_stopped_bottle === true || r.home_stopped_bottle === 't';

        // เวลาส่งเด็ก: ถ้ายังไม่เคยกรอก ใช้เวลาเช็คชื่อเข้าเป็นค่าเริ่มต้น
        const savedDrop = hhmm(r.drop_off_time);
        byId('pDropOff').value = savedDrop || (att && att.checkin_time) || '';
        byId('dropOffHint').textContent = !savedDrop && att && att.checkin_time ? '(เติมจากเวลาเช็คชื่อเข้า)' : '';

        // เมนูอาหารของห้อง
        const hint = (id, text) => {
            const el = byId(id);
            el.className = 'meal-hint' + (text ? '' : ' none');
            el.textContent = text ? `เมนู: ${text}` : 'ยังไม่ได้กรอกเมนู';
        };
        hint('hintMorning', menu.morning_snack);
        hint('hintLunch', menu.lunch);
        hint('hintAfternoon', menu.afternoon_snack);

        byId('dotTeacher').style.display = r.teacher_updated_at ? 'inline-block' : 'none';
        byId('dotParent').style.display = r.parent_updated_at ? 'inline-block' : 'none';
    }

    async function openChild(sid, tab = 'teacher') {
        try {
            const data = (await api('report', { student_id: sid, date: byId('fDate').value })).data;
            const c = data.child;
            const index = filteredRoster().findIndex((x) => x.studentid === sid);
            current = { sid, index };

            byId('mDate').textContent = formatThaiDate(byId('fDate').value);
            const nick = (c.nickname || '').trim();
            byId('mNick').textContent = nick ? 'น้อง' + nick : '';
            byId('mNick').style.display = nick ? '' : 'none';
            byId('mFull').textContent = fullName(c) || '-';
            byId('mSid').textContent = c.studentid;
            byId('mRoom').textContent = c.classroom || '-';
            const att = data.attendance && ATT[data.attendance.status];
            byId('mAtt').innerHTML = att ? `มาเรียน: <b>${att[0]}</b>${data.attendance.checkin_time ? ' ' + data.attendance.checkin_time + ' น.' : ''}` : 'ยังไม่บันทึกการมาเรียน';

            const initial = (c.firstname_th || nick || '-').charAt(0).toUpperCase();
            const av = byId('mAvatar');
            av.innerHTML = '';
            if (c.profile_image) {
                const img = document.createElement('img');
                img.src = c.profile_image; img.alt = 'รูปนักเรียน';
                img.onerror = () => { av.innerHTML = ''; av.textContent = initial; };
                av.appendChild(img);
            } else { av.textContent = initial; }

            fillForm(data.report, data.menu || {}, data.attendance);

            // เปิดแท็บที่ต้องการ
            const tabBtn = document.querySelector(`.side-tabs [data-bs-target="#pane${tab === 'parent' ? 'Parent' : 'Teacher'}"]`);
            if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();

            noteModal.show();
        } catch (e) { showError(e); }
    }

    const num = (id) => byId(id).value;

    function collectPayload(side) {
        if (side === 'parent') {
            return {
                parent_mood: getMood('parent'), parent_message: byId('pMessage').value,
                drop_off_time: num('pDropOff'),
                home_morning_milk_ml: num('hMorningMilk'), home_morning_food: byId('hMorningFood').value,
                home_evening_milk_ml: num('hEveningMilk'), home_evening_food: byId('hEveningFood').value,
                home_sleep_hours: num('hSleep'), home_bedtime: num('hBedtime'), home_wake_time: num('hWake'),
                home_stopped_diaper: byId('hStopDiaper').checked, home_stopped_bottle: byId('hStopBottle').checked
            };
        }
        return {
            teacher_mood: getMood('teacher'), teacher_message: byId('tMessage').value,
            center_morning_milk_ml: num('cMorningMilk'), center_morning_snack_amount: byId('cMorningSnack').value,
            center_lunch_amount: byId('cLunch').value,
            center_afternoon_milk_ml: num('cAfternoonMilk'), center_afternoon_snack_amount: byId('cAfternoonSnack').value,
            center_nap_hours: num('cNap'), center_urine_count: num('cUrine'), center_stool_count: num('cStool'),
            center_stopped_diaper: byId('cStopDiaper').checked, center_stopped_bottle: byId('cStopBottle').checked,
            activities: byId('tActivities').value
        };
    }

    async function saveCurrent(goNext) {
        if (!current) return;
        const activePane = document.querySelector('#noteModal .tab-pane.active');
        const side = activePane && activePane.id === 'paneParent' ? 'parent' : 'teacher';
        const btns = [byId('btnSave'), byId('btnSaveNext')];
        btns.forEach((b) => { b.disabled = true; });
        try {
            await api('save_report', {}, { student_id: current.sid, date: byId('fDate').value, side, ...collectPayload(side) });
            toast('success', side === 'parent' ? 'บันทึกข้อมูลผู้ปกครองแล้ว' : 'บันทึกข้อมูลของครูแล้ว');

            const list = filteredRoster();
            const next = goNext ? list[current.index + 1] : null;
            await loadRoster();

            if (goNext) {
                if (next) { openChild(next.studentid, side); }
                else { noteModal.hide(); toast('info', 'เป็นคนสุดท้ายของรายการแล้ว'); }
            } else {
                noteModal.hide();
            }
        } catch (e) {
            showError(e);
        } finally {
            btns.forEach((b) => { b.disabled = false; });
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        noteModal = new bootstrap.Modal(byId('noteModal'), { backdrop: 'static' });
        buildMoodButtons();

        byId('fDate').value = todayStr();
        byId('fGroup').addEventListener('change', async () => { await loadClassrooms(); loadRoster(); });
        byId('fRoom').addEventListener('change', loadRoster);
        byId('fDate').addEventListener('change', loadRoster);
        byId('fSearch').addEventListener('input', () => { if (roster.length) renderRoster(); });
        byId('datePrev').addEventListener('click', () => shiftDate(-1));
        byId('dateNext').addEventListener('click', () => shiftDate(1));
        byId('dateToday').addEventListener('click', () => { byId('fDate').value = todayStr(); loadRoster(); });

        byId('listArea').addEventListener('click', (e) => {
            const b = e.target.closest('button[data-open]');
            if (b) openChild(b.dataset.open);
        });
        byId('btnSave').addEventListener('click', () => saveCurrent(false));
        byId('btnSaveNext').addEventListener('click', () => saveCurrent(true));
    });
</script>
