<div class="content-card child-profile-add-card">
    <div class="content-card-body">
        <div class="section-divider">
            <span class="section-divider-title"><i class="bi bi-person me-1"></i>ข้อมูลพื้นฐาน</span>
            <div class="section-divider-line"></div>
        </div>

        <div class="row g-3 align-items-start child-profile-layout">
            <div class="col-md-2 text-center child-profile-photo">
                <img id="preview_image" src="../../../public/assets/images/avatar.png"
                     class="rounded-3 mb-2" style="width:100%;max-width:200px;height:200px;object-fit:cover;border:2px solid var(--gray-200);" alt="Profile preview">
                <input type="file" class="form-control form-control-sm mt-2" name="profile_image" id="profile_image" accept="image/*" onchange="handleImageSelect(this)">
                <small class="d-block text-muted mt-1">JPG, PNG, GIF, WEBP ไม่เกิน 5MB</small>
                <input type="hidden" name="profile_image_data" id="profile_image_data">
            </div>

            <div class="col-md-10 child-profile-fields">
                <div class="row g-3 child-basic-info-grid">
                    <div class="col-md-3">
                        <label class="form-label">รหัสนักเรียน <span class="required-asterisk">*</span></label>
                        <input type="text" class="form-control" name="student_id" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ปีการศึกษา <span class="required-asterisk">*</span></label>
                        <select class="form-select" name="academic_year" id="academic_year" required>
                            <option value="" disabled selected>กรุณาเลือกปีการศึกษา</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">กลุ่มเด็ก <span class="required-asterisk">*</span></label>
                        <select class="form-select" name="child_group" id="child_group" onchange="loadClassrooms()" required>
                            <option value="" disabled selected>กรุณาเลือกกลุ่มเด็ก</option>
                            <option value="เด็กกลาง">เด็กกลาง</option>
                            <option value="เด็กโต">เด็กโต</option>
                            <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ห้องเรียน <span class="required-asterisk">*</span></label>
                        <select class="form-select" name="classroom" id="classroom" required disabled>
                            <option value="" disabled selected>กรุณาเลือกห้องเรียน</option>
                        </select>
                    </div>

                    <div class="col-md-2"><label class="form-label">คำนำหน้า (ไทย)</label><input type="text" class="form-control" name="prefix_th"></div>
                    <div class="col-md-4"><label class="form-label">ชื่อ (ไทย) <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="firstname_th" required></div>
                    <div class="col-md-4"><label class="form-label">นามสกุล (ไทย) <span class="required-asterisk">*</span></label><input type="text" class="form-control" name="lastname_th" required></div>
                    <div class="col-md-2"><label class="form-label">ชื่อเล่น</label><input type="text" class="form-control" name="nickname"></div>

                    <div class="col-md-2"><label class="form-label">คำนำหน้า (EN)</label><input type="text" class="form-control" name="prefix_en"></div>
                    <div class="col-md-4"><label class="form-label">First Name</label><input type="text" class="form-control" name="firstname_en"></div>
                    <div class="col-md-4"><label class="form-label">Last Name</label><input type="text" class="form-control" name="lastname_en"></div>
                    <div class="col-md-2"><label class="form-label">วันเกิด</label><input type="date" class="form-control" name="birthday"></div>
                </div>
            </div>
        </div>

        <div class="section-divider">
            <span class="section-divider-title"><i class="bi bi-person-vcard me-1"></i>ข้อมูลส่วนตัว</span>
            <div class="section-divider-line"></div>
        </div>
        <div class="row g-3 child-personal-grid">
            <div class="col-md-3"><label class="form-label">เลขประจำตัวประชาชน</label><input type="text" class="form-control" name="id_card" maxlength="13"></div>
            <div class="col-md-2"><label class="form-label">เพศ</label><select class="form-select" name="sex"><option value="">เลือกเพศ</option><option value="ชาย">ชาย</option><option value="หญิง">หญิง</option><option value="อื่นๆ">อื่นๆ</option></select></div>
            <div class="col-md-2"><label class="form-label">เชื้อชาติ</label><input type="text" class="form-control" name="race"></div>
            <div class="col-md-2"><label class="form-label">สัญชาติ</label><input type="text" class="form-control" name="nationality"></div>
            <div class="col-md-2"><label class="form-label">ศาสนา</label><input type="text" class="form-control" name="religion"></div>
            <div class="col-md-2"><label class="form-label">กรุ๊ปเลือด</label><select class="form-select" name="blood_type"><option value="">เลือกกรุ๊ปเลือด</option><option value="A">A</option><option value="B">B</option><option value="O">O</option><option value="AB">AB</option></select></div>
            <div class="col-md-2"><label class="form-label">ส่วนสูง</label><div class="input-group"><input type="number" step="0.1" class="form-control" name="height"><span class="input-group-text">ซม.</span></div></div>
            <div class="col-md-2"><label class="form-label">น้ำหนัก</label><div class="input-group"><input type="number" step="0.1" class="form-control" name="weight"><span class="input-group-text">กก.</span></div></div>
            <div class="col-md-4"><label class="form-label">โรคประจำตัว</label><input type="text" class="form-control" name="congenital_disease"></div>
        </div>

        <div class="section-divider">
            <span class="section-divider-title"><i class="bi bi-people me-1"></i>ข้อมูลผู้ปกครอง</span>
            <div class="section-divider-line"></div>
        </div>
        <div class="row g-3 child-parents-grid">
            <?php foreach ([['father', 'บิดา', 'Father'], ['mother', 'มารดา', 'Mother'], ['relative', 'ผู้ปกครอง/ผู้ดูแล', 'Guardian']] as [$type, $label, $english]): ?>
            <div class="col-md-4">
                <div class="parent-card h-100">
                    <div class="parent-card-header">
                        <div class="parent-photo-editor text-center">
                            <img src="../../../public/assets/images/avatar.png" alt="<?= $english ?>" class="parent-avatar" style="width:80px;height:80px;">
                            <input type="file" class="form-control form-control-sm mt-2" name="<?= $type ?>_image" accept="image/*">
                        </div>
                        <div><div class="parent-card-title"><i class="bi bi-person me-1"></i><?= $label ?></div><div class="parent-card-subtitle"><?= $english ?></div></div>
                    </div>
                    <div class="parent-card-body">
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label">ชื่อ</label><input type="text" class="form-control form-control-sm" name="<?= $type ?>_first_name"></div>
                            <div class="col-6"><label class="form-label">นามสกุล</label><input type="text" class="form-control form-control-sm" name="<?= $type ?>_last_name"></div>
                            <div class="col-6"><label class="form-label">เบอร์หลัก</label><input type="tel" class="form-control form-control-sm" name="<?= $type ?>_phone"></div>
                            <div class="col-6"><label class="form-label">เบอร์สำรอง</label><input type="tel" class="form-control form-control-sm" name="<?= $type ?>_phone_backup"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="section-divider">
            <span class="section-divider-title"><i class="bi bi-house-door me-1"></i>ที่อยู่</span>
            <div class="section-divider-line"></div>
        </div>
        <div class="row g-3 child-address-grid">
            <div class="col-12"><label class="form-label">ที่อยู่</label><textarea class="form-control" name="address" rows="2"></textarea></div>
            <div class="col-md-3"><label class="form-label">ตำบล/แขวง</label><input type="text" class="form-control" name="district"></div>
            <div class="col-md-3"><label class="form-label">อำเภอ/เขต</label><input type="text" class="form-control" name="amphoe"></div>
            <div class="col-md-3"><label class="form-label">จังหวัด</label><input type="text" class="form-control" name="province"></div>
            <div class="col-md-3"><label class="form-label">รหัสไปรษณีย์</label><input type="text" class="form-control" name="zipcode" maxlength="5"></div>
        </div>

        <div class="section-divider">
            <span class="section-divider-title"><i class="bi bi-telephone-fill me-1"></i>ผู้ติดต่อฉุกเฉิน</span>
            <div class="section-divider-line"></div>
        </div>
        <div class="row g-3 child-emergency-grid">
            <div class="col-md-4"><label class="form-label">ชื่อผู้ติดต่อ</label><input type="text" class="form-control" name="emergency_contact"></div>
            <div class="col-md-4"><label class="form-label">เบอร์โทร</label><input type="tel" class="form-control" name="emergency_phone"></div>
            <div class="col-md-4"><label class="form-label">ความสัมพันธ์</label><input type="text" class="form-control" name="emergency_relation"></div>
        </div>

        <div class="section-divider">
            <span class="section-divider-title"><i class="bi bi-heart-pulse me-1"></i>ข้อมูลสุขภาพและการแพ้</span>
            <div class="section-divider-line"></div>
        </div>
        <div class="row g-3 child-allergy-grid">
            <div class="col-md-6"><div class="allergy-card allergy-card-drug h-100"><div class="allergy-card-title" style="color:#b91c1c;"><i class="bi bi-capsule-pill"></i> การแพ้ยา <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="addDrugAllergyRow()"><i class="bi bi-plus-circle me-1"></i>เพิ่มรายการ</button></div><div id="addDrugAllergyRows" class="d-flex flex-column gap-3"></div></div></div>
            <div class="col-md-6"><div class="allergy-card allergy-card-food h-100"><div class="allergy-card-title" style="color:#92400e;"><i class="bi bi-egg-fried"></i> การแพ้อาหาร <button type="button" class="btn btn-sm btn-outline-warning ms-auto" onclick="addFoodAllergyRow()"><i class="bi bi-plus-circle me-1"></i>เพิ่มรายการ</button></div><div id="addFoodAllergyRows" class="d-flex flex-column gap-3"></div></div></div>
        </div>
    </div>
</div>
