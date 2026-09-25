<!-- ฟอร์มเพิ่มข้อมูลเด็ก – แบ่งเป็นขั้นตอน (Wizard) -->
<div class="modal-body child-profile-add">
    <form id="addChildForm" method="post" action="../../include/process/process_add_child.php" enctype="multipart/form-data" class="child-form-modal">
        <?php include __DIR__ . '/child_profile_add_fields.php'; ?>
        <fieldset disabled class="d-none" aria-hidden="true">
        <p class="text-muted"><span class="required-asterisk">*</span> หมายถึงจำเป็นต้องกรอก</p>
ช
        <!-- STEP 1: โปรไฟล์ -->
        <div class="step" id="step-1">
            <h5 class="step-title"><i class="bi bi-person-badge"></i> รูปโปรไฟล์</h5>
            <div class="row align-items-center mb-4">
                <div class="col-md-4 text-center">
                    <div class="profile-image-container mb-3">
                        <img id="preview_image" src="../../../public/assets/images/avatar.png"
                             class="profile-preview rounded-circle" alt="Preview image">
                        <div class="profile-image-overlay">
                            <i class="bi bi-camera"></i>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removeImageBtn" style="display:none;">ลบรูป</button>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">อัพโหลดรูปโปรไฟล์:</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" onchange="handleImageSelect(this)">
                        <input type="hidden" name="profile_image_data" id="profile_image_data">
                        <div class="form-text"><i class="bi bi-info-circle"></i> รองรับไฟล์ภาพ (jpg, jpeg, png) ขนาดไม่เกิน 5MB</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: ข้อมูลการเรียน -->
        <div class="step" id="step-2" style="display:none;">
            <h5 class="step-title"><i class="bi bi-book"></i> ข้อมูลการเรียน</h5>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">รหัสนักเรียน:<span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="student_id" id="student_id" placeholder="เช่น 12345" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="academic_year" class="form-label mb-0">ปีการศึกษา:<span class="required-asterisk">*</span></label>
                        <button type="button" class="btn btn-sm btn-primary" onclick="openAcademicYearManager()"><i class="bi bi-gear-fill"></i> จัดการปีการศึกษา</button>
                    </div>
                    <select name="academic_year" id="academic_year" class="form-select" required>
                        <option value="" disabled selected>กรุณาเลือกปีการศึกษา</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="child_group" class="form-label">กลุ่มเด็ก:<span class="required-asterisk">*</span></label>
                        <select name="child_group" id="child_group" class="form-select" onchange="loadClassrooms()" required>
                            <option value="" disabled selected>กรุณาเลือกกลุ่มเด็ก</option>
                            <option value="เด็กกลาง">เด็กกลาง</option>
                            <option value="เด็กโต">เด็กโต</option>
                            <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="classroom" class="form-label mb-0">ห้องเรียน:<span class="required-asterisk">*</span></label>
                        <button type="button" class="btn btn-sm btn-primary" onclick="openClassroomManager()"><i class="bi bi-gear-fill"></i> จัดการห้องเรียน</button>
                    </div>
                    <select name="classroom" id="classroom" class="form-select" required disabled aria-describedby="classroomHelp">
                        <option value="" disabled selected>กรุณาเลือกห้องเรียน</option>
                    </select>
                    <div id="classroomHelp" class="form-text">กรุณาเลือกกลุ่มเด็กก่อน</div>
                </div>
            </div>
        </div>

        <!-- STEP 3: ข้อมูลส่วนตัว & ผู้ปกครอง -->
        <div class="step" id="step-3" style="display:none;">
            <h5 class="step-title"><i class="bi bi-person-circle"></i> ข้อมูลส่วนตัว</h5>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="id_card" class="form-label">หมายเลขบัตรประชาชน (ถ้ามี):</label>
                        <input type="text" class="form-control" name="id_card" placeholder="กรุณากรอกหมายเลขบัตรประชาชน">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="nickname" class="form-label">ชื่อเล่น:<span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="nickname" placeholder="กรุณากรอกชื่อเล่น" required>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="prefix_th" class="form-label">คำนำหน้าชื่อ (ไทย):</label>
                    <select name="prefix_th" class="form-select" required>
                        <option value="">เลือกคำนำหน้าชื่อ</option>
                        <option value="เด็กชาย">เด็กชาย</option>
                        <option value="เด็กหญิง">เด็กหญิง</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="firstname_th" class="form-label">ชื่อ (ไทย):<span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" name="firstname_th" placeholder="กรุณากรอกชื่อ" required>
                </div>
                <div class="col-md-4">
                    <label for="lastname_th" class="form-label">นามสกุล (ไทย):<span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" name="lastname_th" placeholder="กรุณากรอกนามสกุล" required>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="prefix_en" class="form-label">คำนำหน้าชื่อ (อังกฤษ):</label>
                    <select name="prefix_en" class="form-select">
                        <option value="">Select prefix</option>
                        <option value="Mr.">Mr.</option>
                        <option value="Ms.">Ms.</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="firstname_en" class="form-label">ชื่อ (อังกฤษ):</label>
                    <input type="text" class="form-control" name="firstname_en" placeholder="กรุณากรอกชื่อ">
                </div>
                <div class="col-md-4">
                    <label for="lastname_en" class="form-label">นามสกุล (อังกฤษ):</label>
                    <input type="text" class="form-control" name="lastname_en" placeholder="กรุณากรอกนามสกุล">
                </div>
            </div>

            <h5 class="step-title"><i class="bi bi-person-vcard"></i> ข้อมูลส่วนตัวเพิ่มเติม</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="birthday" class="form-label">วันเกิด:</label>
                    <input type="date" class="form-control" name="birthday" id="birthday">
                </div>
                <div class="col-md-2">
                    <label for="sex" class="form-label">เพศ:</label>
                    <select name="sex" id="sex" class="form-select">
                        <option value="">เลือกเพศ</option>
                        <option value="ชาย">ชาย</option><option value="หญิง">หญิง</option><option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="blood_type" class="form-label">กรุ๊ปเลือด:</label>
                    <select name="blood_type" id="blood_type" class="form-select">
                        <option value="">เลือกกรุ๊ปเลือด</option>
                        <option value="A">A</option><option value="B">B</option><option value="O">O</option><option value="AB">AB</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">ส่วนสูง (ซม.)</label><input type="number" step="0.1" class="form-control" name="height"></div>
                <div class="col-md-2"><label class="form-label">น้ำหนัก (กก.)</label><input type="number" step="0.1" class="form-control" name="weight"></div>
                <div class="col-md-3"><label class="form-label">เชื้อชาติ</label><input type="text" class="form-control" name="race"></div>
                <div class="col-md-3"><label class="form-label">สัญชาติ</label><input type="text" class="form-control" name="nationality"></div>
                <div class="col-md-3"><label class="form-label">ศาสนา</label><input type="text" class="form-control" name="religion"></div>
                <div class="col-md-6"><label class="form-label">โรคประจำตัว</label><input type="text" class="form-control" name="congenital_disease" placeholder="ถ้าไม่มีให้เว้นว่าง"></div>
            </div>

            <h5 class="step-title"><i class="bi bi-people"></i> ข้อมูลผู้ปกครอง</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="father_first_name" class="form-label">ชื่อบิดา:</label>
                        <input type="text" class="form-control" name="father_first_name" placeholder="กรุณากรอกชื่อบิดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="father_last_name" class="form-label">นามสกุลบิดา:</label>
                        <input type="text" class="form-control" name="father_last_name" placeholder="กรุณากรอกนามสกุลบิดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="father_phone" class="form-label">เบอร์โทรบิดา:</label>
                        <input type="text" class="form-control" name="father_phone" placeholder="กรุณากรอกเบอร์โทรบิดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="father_phone_backup" class="form-label">เบอร์โทรสำรองบิดา:</label>
                        <input type="text" class="form-control" name="father_phone_backup" placeholder="กรุณากรอกเบอร์โทรสำรองบิดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="father_image" class="form-label">รูปบิดา:</label>
                    <input type="file" class="form-control" name="father_image" id="father_image" accept="image/*">
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="mother_first_name" class="form-label">ชื่อมารดา:</label>
                        <input type="text" class="form-control" name="mother_first_name" placeholder="กรุณากรอกชื่อมารดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="mother_last_name" class="form-label">นามสกุลมารดา:</label>
                        <input type="text" class="form-control" name="mother_last_name" placeholder="กรุณากรอกนามสกุลมารดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="mother_phone" class="form-label">เบอร์โทรมารดา:</label>
                        <input type="text" class="form-control" name="mother_phone" placeholder="กรุณากรอกเบอร์โทรมารดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="mother_phone_backup" class="form-label">เบอร์โทรสำรองมารดา:</label>
                        <input type="text" class="form-control" name="mother_phone_backup" placeholder="กรุณากรอกเบอร์โทรสำรองมารดา">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="mother_image" class="form-label">รูปมารดา:</label>
                    <input type="file" class="form-control" name="mother_image" id="mother_image" accept="image/*">
                </div>
            </div>

            <h5 class="step-title mt-4"><i class="bi bi-person-heart"></i> ข้อมูลผู้ปกครอง/ผู้ดูแล</h5>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">ชื่อ</label><input type="text" class="form-control" name="relative_first_name"></div>
                <div class="col-md-3"><label class="form-label">นามสกุล</label><input type="text" class="form-control" name="relative_last_name"></div>
                <div class="col-md-3"><label class="form-label">เบอร์โทร</label><input type="tel" class="form-control" name="relative_phone"></div>
                <div class="col-md-3"><label class="form-label">เบอร์โทรสำรอง</label><input type="tel" class="form-control" name="relative_phone_backup"></div>
                <div class="col-md-6"><label class="form-label">รูปผู้ปกครอง/ผู้ดูแล</label><input type="file" class="form-control" name="relative_image" accept="image/*"></div>
            </div>

            <h5 class="step-title mt-4"><i class="bi bi-house-door"></i> ที่อยู่และผู้ติดต่อฉุกเฉิน</h5>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">ที่อยู่</label><textarea class="form-control" name="address" rows="2"></textarea></div>
                <div class="col-md-3"><label class="form-label">ตำบล/แขวง</label><input type="text" class="form-control" name="district"></div>
                <div class="col-md-3"><label class="form-label">อำเภอ/เขต</label><input type="text" class="form-control" name="amphoe"></div>
                <div class="col-md-3"><label class="form-label">จังหวัด</label><input type="text" class="form-control" name="province"></div>
                <div class="col-md-3"><label class="form-label">รหัสไปรษณีย์</label><input type="text" class="form-control" name="zipcode" maxlength="5"></div>
                <div class="col-md-4"><label class="form-label">ชื่อผู้ติดต่อฉุกเฉิน</label><input type="text" class="form-control" name="emergency_contact"></div>
                <div class="col-md-4"><label class="form-label">เบอร์โทรฉุกเฉิน</label><input type="tel" class="form-control" name="emergency_phone"></div>
                <div class="col-md-4"><label class="form-label">ความสัมพันธ์</label><input type="text" class="form-control" name="emergency_relation"></div>
            </div>

            <h5 class="step-title mt-4"><i class="bi bi-heart-pulse"></i> ข้อมูลสุขภาพและการแพ้</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="allergy-card border border-danger rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong class="text-danger"><i class="bi bi-capsule-pill me-1"></i>การแพ้ยา</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="addDrugAllergyRow()"><i class="bi bi-plus-circle me-1"></i>เพิ่มรายการ</button>
                        </div>
                        <div id="addDrugAllergyRows" class="d-flex flex-column gap-3"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="allergy-card border border-warning rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong class="text-warning-emphasis"><i class="bi bi-egg-fried me-1"></i>การแพ้อาหาร</strong>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="addFoodAllergyRow()"><i class="bi bi-plus-circle me-1"></i>เพิ่มรายการ</button>
                        </div>
                        <div id="addFoodAllergyRows" class="d-flex flex-column gap-3"></div>
                    </div>
                </div>
            </div>
        </div>

        </fieldset>

        <!-- Navigation Buttons -->
        <div class="modal-footer d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary" id="prevBtn" style="display:none;">ย้อนกลับ</button>
            <button type="button" class="btn btn-primary" id="nextBtn">ต่อไป</button>
            <button type="submit" class="btn btn-success btn-submit d-none" id="submitBtn">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display:none;" id="submitSpinner"></span>
                <i class="bi bi-check-circle me-2"></i> บันทึกข้อมูล
            </button>
        </div>
    </form>
</div>

<!-- Modal จัดการห้องเรียน -->
<div class="modal fade" id="classroomManagerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการห้องเรียน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- ฟอร์มเพิ่มห้องเรียน -->
                <form id="addClassroomForm" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <select id="new_classroom_group" class="form-select" required>
                                <option value="">เลือกกลุ่ม</option>
                                <option value="เด็กกลาง">เด็กกลาง</option>
                                <option value="เด็กโต">เด็กโต</option>
                                <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="new_classroom_name" class="form-control" 
                                   placeholder="ชื่อห้องเรียน" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">เพิ่ม</button>
                        </div>
                    </div>
                </form>

                <!-- ตารางแสดงห้องเรียน -->
                <div class="table-responsive">
                    <table class="table" id="classroomTable">
                        <thead>
                            <tr>
                                <th>กลุ่ม</th>
                                <th>ห้องเรียน</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal จัดการปีการศึกษา -->
<div class="modal fade" id="academicYearManagerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการปีการศึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- ฟอร์มเพิ่มปีการศึกษา -->
                <form id="addAcademicYearForm" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-10">
                            <input type="text" id="new_academic_year" class="form-control" 
                                   placeholder="ปีการศึกษา (พ.ศ.)" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">เพิ่ม</button>
                        </div>
                    </div>
                </form>

                <!-- ตารางแสดงปีการศึกษา -->
                <div class="table-responsive">
                    <table class="table" id="academicYearTable">
                        <thead>
                            <tr>
                                <th>ปีการศึกษา</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.child-profile-add {
    background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    padding: 1.5rem;
}

.child-add-modal-header {
    background: linear-gradient(135deg, #0f3d5e 0%, #2563a6 55%, #3b82f6 100%);
    color: #fff;
    border-radius: 16px 16px 0 0;
    border: 0;
    padding: 1.15rem 1.5rem;
}

.child-add-modal-header .modal-title {
    font-weight: 700;
    letter-spacing: 0.1px;
}

.child-add-modal-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.85;
}

.child-profile-add .step {
    display: block !important;
    background: #fff;
    border: 1px solid #e5edf6;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 24px rgba(30, 64, 175, 0.06);
}

.child-profile-add .step-title {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    color: #1e3a8a;
    font-size: 1.05rem;
    font-weight: 700;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #dbeafe;
}

.child-profile-add .step-title i {
    color: #2563eb;
    font-size: 1.2rem;
}

.child-profile-add .required-asterisk {
    color: #ef4444;
    font-weight: 800;
}

.child-profile-add .profile-image-container {
    width: 170px !important;
    height: 170px !important;
    border: 5px solid #dbeafe;
    box-shadow: 0 8px 22px rgba(37, 99, 235, 0.15);
}

.child-profile-add .profile-preview {
    border: 0;
}

.child-profile-add .form-text {
    color: #64748b;
}

.child-profile-add .form-label {
    color: #475569;
    font-weight: 600;
}

.child-profile-add .form-control,
.child-profile-add .form-select {
    border-color: #cbd5e1;
    border-radius: 10px;
}

.child-profile-add .form-control:focus,
.child-profile-add .form-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.child-profile-add .form-control,
.child-profile-add .form-select {
    min-height: 42px;
    background-color: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.child-profile-add textarea.form-control {
    min-height: auto;
    resize: vertical;
}

.child-profile-add .form-control:hover,
.child-profile-add .form-select:hover {
    border-color: #93c5fd;
}

.child-profile-add .allergy-card {
    background: rgba(255, 255, 255, 0.86);
    border-width: 1px !important;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
}

.child-profile-add .allergy-card > .d-flex {
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.child-profile-add .add-drug-allergy-row,
.child-profile-add .add-food-allergy-row {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.child-profile-add .add-drug-allergy-row strong,
.child-profile-add .add-food-allergy-row strong {
    color: #334155;
    font-size: 0.85rem;
}

.child-profile-add .form-check {
    padding: 0.55rem 0.75rem 0.55rem 2rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.child-profile-add .form-check:hover {
    background: #eff6ff;
    border-color: #93c5fd;
}

.child-profile-add .modal-footer .btn {
    border-radius: 10px;
    font-weight: 600;
    padding: 0.65rem 1.15rem;
}

.child-profile-add > form > .modal-footer {
    position: sticky;
    bottom: 0;
    z-index: 2;
    justify-content: flex-end !important;
    background: rgba(255, 255, 255, 0.96);
    border-top: 1px solid #e2e8f0;
    padding: 1rem 0 0;
}

@media (max-width: 768px) {
    .child-profile-add { padding: 1rem; }
    .child-profile-add .step { padding: 1rem; border-radius: 12px; }
    .child-profile-add .profile-image-container { width: 135px; height: 135px; }
}

.child-profile-add #prevBtn,
.child-profile-add #nextBtn {
    display: none !important;
}

.child-profile-add #submitBtn {
    display: inline-block !important;
    background: #198754;
    border-color: #198754;
    color: #fff;
    box-shadow: 0 4px 10px rgba(25, 135, 84, 0.2);
}

.child-profile-add #submitBtn:hover,
.child-profile-add #submitBtn:focus {
    background: #146c43;
    border-color: #146c43;
    color: #fff;
}

/* ===== Responsive add-child modal ===== */
#addChildModal .child-add-modal-dialog {
    width: calc(100% - 2rem);
    max-width: 1180px;
    margin: 1rem auto;
}

#addChildModal .child-add-modal-content {
    height: calc(100vh - 2rem);
    max-height: calc(100vh - 2rem);
    height: calc(100dvh - 2rem);
    max-height: calc(100dvh - 2rem);
    border: 0;
    border-radius: 16px;
    overflow: hidden;
}

#addChildModal .modal-body.child-profile-add {
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: clamp(0.75rem, 2vw, 1.5rem);
}

#addChildModal .child-profile-add > form,
#addChildModal .child-profile-add-card {
    width: 100%;
    max-width: 100%;
}

#addChildModal .child-profile-add-card {
    overflow: visible;
}

#addChildModal .content-card-header,
#addChildModal .content-card-body {
    padding: clamp(1rem, 2vw, 1.5rem);
}

#addChildModal #preview_image {
    width: min(100%, 200px) !important;
    height: auto !important;
    aspect-ratio: 1 / 1;
}

#addChildModal .parent-card-header {
    min-width: 0;
}

#addChildModal .parent-photo-editor {
    min-width: 80px;
    max-width: 100%;
}

#addChildModal .parent-photo-editor .form-control {
    min-width: 0;
}

#addChildModal .allergy-card-title {
    flex-wrap: wrap;
    row-gap: 0.5rem;
}

#addChildModal .allergy-card-title .btn {
    margin-left: auto;
    white-space: nowrap;
}

#addChildModal .child-profile-add > form > .modal-footer {
    margin: 0 -1.5rem;
    padding: 1rem 1.5rem 0;
}

/* iPad / tablet: give each field enough room to read and tap comfortably */
@media (min-width: 768px) and (max-width: 1199.98px) {
    #addChildModal .child-add-modal-dialog {
        width: calc(100% - 1rem);
        max-width: none;
        margin: 0.5rem auto;
    }

    #addChildModal .child-add-modal-content {
        height: calc(100vh - 1rem);
        max-height: calc(100vh - 1rem);
        height: calc(100dvh - 1rem);
        max-height: calc(100dvh - 1rem);
    }

    #addChildModal .content-card-body {
        padding: 1.25rem;
    }

    #addChildModal .child-personal-grid > [class*="col-md-"],
    #addChildModal .child-address-grid > [class*="col-md-"],
    #addChildModal .child-emergency-grid > [class*="col-md-"] {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }

    #addChildModal .child-parents-grid > .col-md-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    #addChildModal .child-profile-photo {
        padding-right: 0.5rem;
    }

    #addChildModal .child-basic-info-grid > .col-md-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (min-width: 768px) and (max-width: 899.98px) {
    #addChildModal .child-profile-layout > .child-profile-photo,
    #addChildModal .child-profile-layout > .child-profile-fields {
        flex: 0 0 100%;
        max-width: 100%;
    }

    #addChildModal .child-profile-photo {
        padding-right: calc(var(--bs-gutter-x) * 0.5);
    }

    #addChildModal .child-profile-photo #profile_image {
        max-width: 420px;
        margin-left: auto;
        margin-right: auto;
    }

    #addChildModal .child-allergy-grid > .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

@media (min-width: 768px) and (max-width: 899.98px) {
    #addChildModal .child-personal-grid > [class*="col-md-"],
    #addChildModal .child-address-grid > [class*="col-md-"],
    #addChildModal .child-emergency-grid > [class*="col-md-"] {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 767.98px) {
    #addChildModal .child-add-modal-dialog {
        width: 100%;
        max-width: none;
        min-height: 100dvh;
        margin: 0;
    }

    #addChildModal .child-add-modal-content {
        height: 100vh;
        max-height: 100vh;
        height: 100dvh;
        max-height: 100dvh;
        border-radius: 0;
    }

    #addChildModal .child-add-modal-header {
        flex: 0 0 auto;
        padding: 0.9rem 1rem;
        border-radius: 0;
    }

    #addChildModal .child-add-modal-header .modal-title {
        font-size: 1rem;
    }

    #addChildModal .modal-body.child-profile-add {
        padding: 0.75rem;
    }

    #addChildModal .content-card {
        border-radius: 12px;
        box-shadow: none;
        border: 1px solid var(--gray-100);
    }

    #addChildModal .content-card-header {
        align-items: flex-start;
        padding: 0.9rem;
    }

    #addChildModal .content-card-body {
        padding: 0.9rem;
    }

    #addChildModal .section-divider {
        gap: 0.5rem;
        margin: 1.25rem 0 1rem;
    }

    #addChildModal .section-divider-title {
        font-size: 0.75rem;
        letter-spacing: 0.35px;
    }

    #addChildModal .parent-card-header {
        align-items: flex-start;
        padding: 0.75rem;
    }

    #addChildModal .parent-card-body {
        padding: 0.75rem;
    }

    #addChildModal .allergy-card {
        padding: 0.85rem;
    }

    #addChildModal .allergy-card-title .btn {
        width: 100%;
        margin-left: 0;
    }

    #addChildModal .child-profile-add > form > .modal-footer {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
        padding: 0.75rem 0.75rem 0;
    }

    #addChildModal #submitBtn {
        width: auto;
        min-width: 170px;
    }
}

@media (max-width: 420px) {
    #addChildModal .content-card-header {
        display: block;
    }

    #addChildModal .content-card-header > .text-muted {
        display: block;
        margin-top: 0.5rem;
    }

    #addChildModal .section-divider-title {
        white-space: normal;
    }

    #addChildModal .parent-card-header {
        flex-wrap: wrap;
    }

    #addChildModal .parent-photo-editor {
        width: 100%;
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr);
        gap: 0.65rem;
        align-items: center;
    }

    #addChildModal .parent-photo-editor .parent-avatar {
        width: 56px !important;
        height: 56px !important;
    }

    #addChildModal .parent-photo-editor .form-control {
        margin-top: 0 !important;
    }
}

.profile-image-container {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto;
    cursor: pointer;
}

.profile-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 3px solid #e0e0e0;
}

.profile-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    border-radius: 50%;
}

.profile-image-overlay i {
    color: white;
    font-size: 2rem;
}

.profile-image-container:hover .profile-image-overlay {
    opacity: 1;
}

/* เมื่อ drag ไฟล์เข้ามา */
.profile-image-container.dragover {
    border: 2px dashed #4CAF50;
}

.yearpicker-container {
    position: absolute;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 10000;
    max-height: 200px;
    overflow-y: auto;
    padding: 10px;
}

.yearpicker-items {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 5px;
}

.yearpicker-item {
    padding: 5px 10px;
    text-align: center;
    cursor: pointer;
    border-radius: 3px;
}

.yearpicker-item:hover {
    background-color: #f0f0f0;
}

.yearpicker-item.selected {
    background-color: #007bff;
    color: white;
}
</style>

<script>
function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // ตรวจสอบประเภทไฟล์
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'ไฟล์ไม่ถูกต้อง',
                text: 'กรุณาเลือกไฟล์ภาพ (jpg, jpeg, png) เท่านั้น'
            });
            input.value = '';
            return;
        }
        
        // ตรวจสอบขนาดไฟล์
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'ไฟล์มีขนาดใหญ่เกินไป',
                text: 'ขนาดไฟล์ต้องไม่เกิน 5MB'
            });
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            // เก็บข้อมูล base64
            document.getElementById('profile_image_data').value = e.target.result;
            
            // แสดงตัวอย่างรูป
            const preview = document.getElementById('preview_image');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// เพิ่มการตรวจสอบก่อนส่งฟอร์ม
document.getElementById('addChildForm').onsubmit = function(e) {
    const imageData = document.getElementById('profile_image_data').value;
    if (imageData) {
        // ตรวจสอบขนาดข้อมูล base64
        const base64Size = Math.ceil((imageData.length * 3) / 4);
        if (base64Size > 5 * 1024 * 1024) {
            alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
            e.preventDefault();
            return false;
        }
    }
    return true;
};

// เพิ่ม event listener สำหรับการคลิกที่รูป
document.querySelector('.profile-image-container').addEventListener('click', function() {
    document.getElementById('profile_image').click();
});

// เพิ่ม drag and drop support
const container = document.querySelector('.profile-image-container');

container.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

container.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

container.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    
    const input = document.getElementById('profile_image');
    input.files = e.dataTransfer.files;
    handleImageSelect(input);
});

// ฟังก์ชันโหลดห้องเรียนตามกลุ่มที่เลือก
function loadClassrooms() {
    const childGroup = document.getElementById('child_group').value;
    const classroomSelect = document.getElementById('classroom');
    
    if (!childGroup) {
        classroomSelect.innerHTML = '<option value="" disabled selected>กรุณาเลือกห้องเรียน</option>';
        return;
    }

    // เรียก API เพื่อดึงข้อมูลห้องเรียน
    fetch(`../../include/function/get_classrooms.php?child_group=${encodeURIComponent(childGroup)}`)
        .then(response => response.json())
        .then(data => {
            classroomSelect.innerHTML = '<option value="" disabled selected>กรุณาเลือกห้องเรียน</option>';
            if (Array.isArray(data)) {
                data.forEach(classroom => {
                    const option = document.createElement('option');
                    option.value = classroom.classroom_name;
                    option.textContent = classroom.classroom_name;
                    classroomSelect.appendChild(option);
                });
            }
            classroomSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            classroomSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
        });
}

// ฟังก์ชันเปิด Modal จัดการห้องเรียน
function openClassroomManager() {
    loadClassroomTable();
    const modal = new bootstrap.Modal(document.getElementById('classroomManagerModal'));
    modal.show();
}

// ฟังก์ชันโหลดตารางห้องเรียน
function loadClassroomTable() {
    fetch('../../include/function/get_classrooms.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#classroomTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(classroom => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${classroom.child_group}</td>
                    <td>${classroom.classroom_name}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editClassroom('${classroom.classroom_name}', '${classroom.child_group}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClassroom('${classroom.classroom_name}', '${classroom.child_group}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => console.error('Error:', error));
}

// เพิ่ม Event Listener สำหรับฟอร์มเพิ่มห้องเรียน
document.getElementById('addClassroomForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const data = {
        action: 'add',
        child_group: document.getElementById('new_classroom_group').value,
        classroom_name: document.getElementById('new_classroom_name').value
    };

    fetch('../../include/function/classroom_functions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire('สำเร็จ', result.message, 'success');
            loadClassroomTable();
            if (document.getElementById('child_group').value === data.child_group) {
                loadClassrooms();
            }
            this.reset();
        } else {
            Swal.fire('ข้อผิดพลาด', result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเพิ่มห้องเรียน', 'error');
    });
});

function editClassroom(classroomName, childGroup) {
    document.activeElement?.blur();

    const modal = document.getElementById('addChildModal');
    const instance = bootstrap.Modal.getInstance(modal);
    if (instance) {
        instance.hide();
    }

    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

    setTimeout(() => {
        Swal.fire({
            title: 'แก้ไขข้อมูลห้องเรียน',
            html: `
                <div class="mb-3">
                    <label for="edit_child_group" class="form-label">กลุ่มเด็ก:</label>
                    <select id="edit_child_group" class="form-control form-select">
                        <option value="เด็กกลาง" ${childGroup === 'เด็กกลาง' ? 'selected' : ''}>เด็กกลาง</option>
                        <option value="เด็กโต" ${childGroup === 'เด็กโต' ? 'selected' : ''}>เด็กโต</option>
                        <option value="เตรียมอนุบาล" ${childGroup === 'เตรียมอนุบาล' ? 'selected' : ''}>เตรียมอนุบาล</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_classroom_name" class="form-label">ชื่อห้องเรียน:</label>
                    <input type="text" id="edit_classroom_name" class="form-control">
                </div>
            `,
            didOpen: () => {
                document.getElementById('edit_classroom_name').value = classroomName;
            },
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',

            preConfirm: () => {
                const newClassroomName = document.getElementById('edit_classroom_name').value.trim();
                const newChildGroup = document.getElementById('edit_child_group').value;

                if (!newClassroomName) {
                    Swal.showValidationMessage('กรุณากรอกชื่อห้องเรียน');
                    return false;
                }

                return {
                    action: 'edit',  // สำคัญ! ต้องส่ง action ให้ PHP รู้ว่าทำอะไร
                    old_classroom_name: classroomName,
                    old_child_group: childGroup,
                    classroom_name: newClassroomName,
                    child_group: newChildGroup
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../../include/function/classroom_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('สำเร็จ', data.message, 'success');
                        loadClassroomTable();
                        const currentGroup = document.getElementById('child_group')?.value;
                        if (currentGroup === childGroup || currentGroup === result.value.child_group) {
                            loadClassrooms();
                        }
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                });
            }
        });
    }, 300);
}




// ฟังก์ชันลบห้องเรียน
function deleteClassroom(classroomName, childGroup) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: `คุณต้องการลบห้องเรียน ${classroomName} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../include/function/classroom_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    classroom_name: classroomName,
                    child_group: childGroup
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    loadClassroomTable();
                    // รีเฟรชรายการห้องเรียนในฟอร์มหลัก
                    if (document.getElementById('child_group').value === childGroup) {
                        loadClassrooms();
                    }
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

// เพิ่ม Event Listener สำหรับฟอร์มเพิ่มห้องเรียน
document.getElementById('addChildForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // แสดง loading
    Swal.fire({
        title: 'กำลังบันทึกข้อมูล...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: data.message,
                showConfirmButton: true
            }).then(() => {
                // กลับไปหน้า children_history
                window.location.href = '../student/children_history.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message,
                showConfirmButton: true
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
            showConfirmButton: true
        });
    });
});

$(document).ready(function() {
    // สร้าง Year Picker
    $('.yearpicker').each(function() {
        const input = $(this);
        const container = $('<div class="yearpicker-container" style="display:none;"></div>');
        const itemsContainer = $('<div class="yearpicker-items"></div>');
        
        // สร้างปีย้อนหลัง 10 ปี และล่วงหน้า 5 ปี
        const currentYear = new Date().getFullYear() + 543;
        for(let year = currentYear + 5; year >= currentYear - 10; year--) {
            const item = $(`<div class="yearpicker-item" data-year="${year}">${year}</div>`);
            item.click(function() {
                input.val(year);
                container.hide();
                // ไฮไลท์ปีที่เลือก
                itemsContainer.find('.yearpicker-item').removeClass('selected');
                $(this).addClass('selected');
            });
            itemsContainer.append(item);
        }
        
        container.append(itemsContainer);
        input.after(container);
        
        // แสดง/ซ่อน picker เมื่อคลิกที่ input
        input.click(function(e) {
            e.stopPropagation();
            $('.yearpicker-container').not(container).hide();
            container.toggle();
            
            // ไฮไลท์ปีที่เลือกปัจจุบัน
            const selectedYear = input.val();
            itemsContainer.find('.yearpicker-item').removeClass('selected');
            itemsContainer.find(`[data-year="${selectedYear}"]`).addClass('selected');
            
            // เลื่อนไปที่ปีที่เลือก
            const selectedItem = container.find('.selected');
            if(selectedItem.length) {
                container.scrollTop(selectedItem.position().top - container.height()/2);
            }
        });
        
        // ซ่อน picker เมื่อคลิกที่อื่น
        $(document).click(function(e) {
            if(!$(e.target).closest('.yearpicker-container').length) {
                container.hide();
            }
        });
    });
});

// ฟังก์ชันโหลดปีการศึกษา
function loadAcademicYears() {
    fetch('../../include/function/get_academic_years.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('academic_year');
            select.innerHTML = '<option value="" disabled selected>กรุณาเลือกปีการศึกษา</option>';
            
            if (data.success && Array.isArray(data.years)) {
                data.years.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="" disabled selected>ไม่พบข้อมูลปีการศึกษา</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const select = document.getElementById('academic_year');
            select.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
        });
}

// ฟังก์ชันเปิด Modal จัดการปีการศึกษา
function openAcademicYearManager() {
    loadAcademicYearTable();
    const modal = new bootstrap.Modal(document.getElementById('academicYearManagerModal'));
    modal.show();
}

// ฟังก์ชันโหลดตารางปีการศึกษา
function loadAcademicYearTable() {
    fetch('../../include/function/academic_year_functions.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#academicYearTable tbody');
            tbody.innerHTML = '';
            
            if (Array.isArray(data)) {
                data.forEach(year => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${year.name}</td>
                        <td>
                            <span class="badge ${year.is_active ? 'bg-success' : 'bg-secondary'}">
                                ${year.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editAcademicYear(${year.id}, '${year.name}', ${year.is_active})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteAcademicYear(${year.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center">ไม่พบข้อมูลปีการศึกษา</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const tbody = document.querySelector('#academicYearTable tbody');
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
        });
}

// ฟังก์ชันจัดรูปแบบวันที่
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// เพิ่ม Event Listener สำหรับฟอร์มเพิ่มปีการศึกษา
document.getElementById('addAcademicYearForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const data = {
        action: 'add',
        name: document.getElementById('new_academic_year').value
    };

    fetch('../../include/function/academic_year_functions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire('สำเร็จ', result.message, 'success');
            loadAcademicYearTable();
            loadAcademicYears();
            this.reset();
        } else {
            Swal.fire('ข้อผิดพลาด', result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเพิ่มปีการศึกษา', 'error');
    });
});

// ฟังก์ชันแก้ไขปีการศึกษา
function editAcademicYear(id, name, isActive) {
    Swal.fire({
        title: 'แก้ไขข้อมูลปีการศึกษา',
        html: `
            <div class="mb-3">
                <label for="edit_academic_year" class="form-label">ปีการศึกษา:</label>
                <input type="text" id="edit_academic_year" class="form-control" value="${name}">
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" id="edit_is_active" class="form-check-input" ${isActive ? 'checked' : ''}>
                    <label class="form-check-label" for="edit_is_active">เปิดใช้งาน</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            return {
                action: 'edit',
                id: id,
                name: document.getElementById('edit_academic_year').value,
                is_active: document.getElementById('edit_is_active').checked
            };
        }
    })
    .then((result) => {
        if (result.isConfirmed) {
            fetch('../../include/function/academic_year_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    loadAcademicYearTable();
                    loadAcademicYears();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

// ฟังก์ชันลบปีการศึกษา
function deleteAcademicYear(id) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบปีการศึกษานี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../include/function/academic_year_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    loadAcademicYearTable();
                    loadAcademicYears();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

// โหลดปีการศึกษาเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    loadAcademicYears();
});
</script>

<script>
// ---------- Wizard navigation ----------
const steps = [document.getElementById('step-1'), document.getElementById('step-2'), document.getElementById('step-3')];
let currentStep = 0;

function showStep(index) {
    steps.forEach((el, i) => {
        el.style.display = i === index ? 'block' : 'none';
    });
    // Button visibility
    document.getElementById('prevBtn').style.display = index === 0 ? 'none' : 'inline-block';
    if (index === steps.length - 1) {
        document.getElementById('nextBtn').style.display = 'none';
        document.getElementById('submitBtn').classList.remove('d-none');
    } else {
        document.getElementById('nextBtn').style.display = 'inline-block';
        document.getElementById('submitBtn').classList.add('d-none');
    }
}

document.getElementById('nextBtn').addEventListener('click', () => {
    if (currentStep < steps.length - 1) {
        currentStep++;
        showStep(currentStep);
    }
});

document.getElementById('prevBtn').addEventListener('click', () => {
    if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
    }
});

// Initialize first step on page load
document.addEventListener('DOMContentLoaded', () => {
    showStep(currentStep);
    addDrugAllergyRow();
    addFoodAllergyRow();
});

let addDrugIndex = 0;
let addFoodIndex = 0;

function addDrugAllergyRow() {
    const container = document.getElementById('addDrugAllergyRows');
    if (!container) return;
    const index = addDrugIndex++;
    const row = document.createElement('div');
    row.className = 'border rounded p-3 bg-light add-drug-allergy-row';
    row.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>รายการที่ ${container.children.length + 1}</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="this.closest('.add-drug-allergy-row').remove()">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-capsule me-2"></i>ชื่อยาที่แพ้</label>
                <input type="text" class="form-control" name="drug_items[${index}][drug_name]" placeholder="ระบุชื่อยาที่แพ้">
            </div>
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-check-circle me-2"></i>วิธีที่ทราบว่าแพ้</label>
                <select class="form-select" name="drug_items[${index}][detection_method]">
                    <option value="">เลือกวิธีที่ทราบ</option>
                    <option value="symptoms_after_use">มีอาการแพ้หลังจากใช้ยา</option>
                    <option value="skin_testing">การทดสอบทางผิวหนัง</option>
                    <option value="blood_test">ทดสอบโดยการเจาะเลือด</option>
                    <option value="repeat_use">ทดสอบโดยการใช้ยาซ้ำ</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>อาการที่เกิดขึ้น</label>
                <select class="form-select" name="drug_items[${index}][symptoms]">
                    <option value="">เลือกอาการ</option>
                    <option value="type1">ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก</option>
                    <option value="type2">ผื่นลมพิษ, การบวมในชั้นใต้ผิวหนังและเยื่อเมือก และหายใจลำบาก</option>
                    <option value="type3">ผื่นแดงลักษณะเป็นผื่นราบ และผื่นนูน กระจายอย่างสมมาตร</option>
                    <option value="type4">ผิวแดงทั่วตัวและผื่นตุ่มหนองขนาดเล็กจำนวนมาก</option>
                    <option value="type5">ผื่นที่เกิดขึ้นสามารถพบได้หลายแบบ</option>
                    <option value="type6">ผื่นตุ่มน้ำ มีผิวหนังกำพร้าตายและหลุดลอก</option>
                </select>
            </div>
            <div class="col-12">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="drug_items[${index}][has_allergy_card]" value="true">
                    <label class="form-check-label fw-bold"><i class="bi bi-card-checklist me-2"></i>มีบัตรแพ้ยา</label>
                </div>
            </div>
        </div>`;
    container.appendChild(row);
}

function addFoodAllergyRow() {
    const container = document.getElementById('addFoodAllergyRows');
    if (!container) return;
    const index = addFoodIndex++;
    const row = document.createElement('div');
    row.className = 'border rounded p-3 bg-light add-food-allergy-row';
    row.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>รายการที่ ${container.children.length + 1}</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="this.closest('.add-food-allergy-row').remove()">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-egg me-2"></i>ชื่ออาหารที่แพ้</label>
                <input type="text" class="form-control" name="food_items[${index}][food_name]" placeholder="ระบุชื่ออาหารที่แพ้">
            </div>
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-check-circle me-2"></i>วิธีที่ทราบว่าแพ้</label>
                <select class="form-select" name="food_items[${index}][detection_method]">
                    <option value="">เลือกวิธีที่ทราบ</option>
                    <option value="symptoms_after_eat">มีอาการแพ้หลังรับประทานอาหาร</option>
                    <option value="repeat_eat">ทดสอบโดยการรับประทานอาหารซ้ำ</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-droplet me-2"></i>อาการทางเดินอาหาร</label>
                <div class="row g-2">
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][digestive_symptoms][]" value="bloody_stool"><label class="form-check-label">ถ่ายเป็นมูกเลือดเป็น ๆ หาย ๆ</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][digestive_symptoms][]" value="vomiting"><label class="form-check-label">อาเจียน</label></div></div>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-droplet-half me-2"></i>อาการทางผิวหนัง</label>
                <div class="row g-2">
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="urticaria"><label class="form-check-label">ผื่นลมพิษทั่วตัว</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="eye_swelling"><label class="form-check-label">ตาบวม</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="mouth_rash"><label class="form-check-label">มีผื่นรอบปาก</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][skin_symptoms][]" value="atopic_dermatitis"><label class="form-check-label">ผื่นภูมิแพ้ผิวหนัง</label></div></div>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold"><i class="bi bi-wind me-2"></i>อาการทางเดินหายใจ</label>
                <div class="row g-2">
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="wheezing"><label class="form-check-label">หายใจมีเสียงวี้ด</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="runny_nose"><label class="form-check-label">น้ำมูกไหล</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="nasal_congestion"><label class="form-check-label">คัดจมูก</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="food_items[${index}][respiratory_symptoms][]" value="breathing_difficulty"><label class="form-check-label">หายใจลำบาก</label></div></div>
                </div>
            </div>
        </div>`;
    container.appendChild(row);
}
</script>
