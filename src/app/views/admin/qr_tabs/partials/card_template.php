<?php
$cardChild = $cardChild ?? $child ?? [];
$cardLayout = $cardLayout ?? $layoutConfig ?? [];
$cardHeaderColor = $cardHeaderColor ?? $headerColor ?? '#1E3A8A';
$cardClass = $cardClass ?? 'card-preview';
$cardId = $cardId ?? null;
$cardStyle = $cardStyle ?? '';
$showQR = $showQR ?? true;
$qrContainerId = $qrContainerId ?? null;
$qrClass = $qrClass ?? 'qr-print';
$photoWrapStyle = $photoWrapStyle ?? '';
$cardSide = $cardSide ?? 'front';

$schoolName = $cardLayout['school_name'] ?? 'โรงเรียนอนุบาล';
$schoolLines = explode("\n", $schoolName);
$firstLine = trim($schoolLines[0]);
$restLines = array_map('trim', array_slice($schoolLines, 1));

$showPhoto = $cardLayout['show_photo'] ?? true;
$showNickname = $cardLayout['show_nickname'] ?? true;
$showClassroom = $cardLayout['show_classroom'] ?? true;
$showBlood = $cardLayout['show_blood'] ?? false;
$showStudentid = $cardLayout['show_studentid'] ?? true;
$showDob = $cardLayout['show_dob'] ?? true;
$showCongenital = $cardLayout['show_congenital'] ?? true;
$showAllergicFood = $cardLayout['show_allergic_food'] ?? true;
$showAllergicMedicine = $cardLayout['show_allergic_medicine'] ?? true;
$showExpiry = $cardLayout['show_expiry'] ?? true;

$nameFont = $cardLayout['font_name'] ?? '3';
$schoolFont1 = $cardLayout['font_schoolname'] ?? '2.2';
$schoolFont2 = $cardLayout['font_schoolname2'] ?? '1.8';
$fontNickname = $cardLayout['font_show_nickname'] ?? '2.2';
$fontClassroom = $cardLayout['font_show_classroom'] ?? '2.2';
$fontBlood = $cardLayout['font_show_blood'] ?? '2.2';
$fontStudentid = $cardLayout['font_show_studentid'] ?? '2';
$fontDob = $cardLayout['font_show_dob'] ?? '1.8';
$fontCongenital = $cardLayout['font_show_congenital'] ?? '1.7';
$fontAllergicFood = $cardLayout['font_show_allergic_food'] ?? '1.7';
$fontAllergicMedicine = $cardLayout['font_show_allergic_medicine'] ?? '1.7';
$fontExpiry = $cardLayout['font_show_expiry'] ?? '2.2';
$backTitle = $cardLayout['back_title'] ?? 'ข้อมูลผู้รับส่ง (ผู้ปกครอง)';
if (in_array($backTitle, ['ข้อมูลสำคัญ', 'ข้อมูลผู้รับส่ง'], true)) $backTitle = 'ข้อมูลผู้รับส่ง (ผู้ปกครอง)';
$fontBackTitle = $cardLayout['font_back_title'] ?? '2.8';
$fontBackGuardian = $cardLayout['font_back_guardian'] ?? '1.6';
$sizeBackGuardianPhoto = max(4, min(15, (float)($cardLayout['size_back_guardian_photo'] ?? 7)));

$childName = trim((string)($cardChild['prefix_th'] ?? '') . (string)($cardChild['firstname_th'] ?? '') . ' ' . (string)($cardChild['lastname_th'] ?? 'นักเรียน'));
$childNickname = $cardChild['nickname'] ?? '-';
$childClassroom = $cardChild['classroom'] ?? '-';
$childBlood = $cardChild['blood_type'] ?? '-';
$birthdayTimestamp = !empty($cardChild['birthday']) ? strtotime((string)$cardChild['birthday']) : false;
$childBirthday = $birthdayTimestamp ? date('d/m/Y', $birthdayTimestamp) : '-';
$childCongenital = trim((string)($cardChild['congenital_disease'] ?? '')) ?: '-';
$formatCardList = static function ($value): string {
    $items = is_array($value)
        ? $value
        : preg_split('/\s*(?:,|\r\n|\r|\n)\s*/u', trim((string)$value), -1, PREG_SPLIT_NO_EMPTY);
    $items = array_values(array_unique(array_filter(array_map(static fn($item): string => trim((string)$item), $items), static fn(string $item): bool => $item !== '')));
    return $items ? implode(', ', $items) : '-';
};
$childAllergicFood = $formatCardList($cardChild['allergic_food'] ?? '');
$childAllergicMedicine = $formatCardList($cardChild['allergic_medicine'] ?? '');
$childStudentId = $cardChild['studentid'] ?? '';
$childExpiry = !empty($cardChild['expires_at']) ? date('d/m/Y', strtotime($cardChild['expires_at'])) : '-';
$childFather = trim(($cardChild['father_first_name'] ?? '') . ' ' . ($cardChild['father_last_name'] ?? ''));
$childMother = trim(($cardChild['mother_first_name'] ?? '') . ' ' . ($cardChild['mother_last_name'] ?? ''));
if (!$childFather) $childFather = '-';
if (!$childMother) $childMother = '-';

$guardianEntries = [];
$guardianSources = [
    ['first' => 'father_first_name', 'last' => 'father_last_name', 'phone' => 'father_phone', 'phone_backup' => 'father_phone_backup', 'image' => 'father_image', 'type' => 'บิดา', 'relation' => 'บิดา'],
    ['first' => 'mother_first_name', 'last' => 'mother_last_name', 'phone' => 'mother_phone', 'phone_backup' => 'mother_phone_backup', 'image' => 'mother_image', 'type' => 'มารดา', 'relation' => 'มารดา'],
    ['first' => 'relative_first_name', 'last' => 'relative_last_name', 'phone' => 'relative_phone', 'phone_backup' => 'relative_phone_backup', 'image' => 'relative_image', 'type' => 'ผู้ปกครอง/ผู้ดูแล', 'relation' => trim((string)($cardChild['emergency_relation'] ?? '')) ?: 'ผู้ปกครอง/ผู้ดูแล']
];
foreach ($guardianSources as $source) {
    $guardianName = trim((string)($cardChild[$source['first']] ?? '') . ' ' . (string)($cardChild[$source['last']] ?? ''));
    $guardianPhones = array_values(array_filter([
        trim((string)($cardChild[$source['phone']] ?? '')),
        trim((string)($cardChild[$source['phone_backup']] ?? ''))
    ], static fn(string $phone): bool => $phone !== ''));
    $guardianEntries[] = [
        'type' => $source['type'],
        'name' => $guardianName ?: '-',
        'phone' => $guardianPhones ? implode(', ', $guardianPhones) : '-',
        'image' => $cardChild[$source['image']] ?? '',
        'relation' => $source['relation'],
        'no_photo' => false
    ];
}
$emergencyName = trim((string)($cardChild['emergency_contact'] ?? ''));
$emergencyPhone = trim((string)($cardChild['emergency_phone'] ?? ''));
$emergencyRelation = trim((string)($cardChild['emergency_relation'] ?? ''));
$guardianEntries[] = [
    'type' => 'ผู้ติดต่อฉุกเฉิน',
    'name' => $emergencyName ?: '-',
    'phone' => $emergencyPhone ?: '-',
    'image' => '',
    'relation' => $emergencyRelation ?: '-',
    'no_photo' => true
];

$cardIdAttr = $cardId ? ' id="' . $cardId . '"' : '';
$qrIdAttr = $qrContainerId ? ' id="' . $qrContainerId . '"' : '';
$photoDisplay = ($cardLayout['show_photo'] ?? true) ? '' : 'none';
$finalPhotoStyle = $photoWrapStyle ?: 'display:' . $photoDisplay;
if (empty($GLOBALS['_card_css_loaded'])): $GLOBALS['_card_css_loaded'] = true; ?>
<style>
.card-preview { width:85.6mm;height:54mm;box-sizing:border-box;padding:2.5mm;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);margin:0 auto;position:relative;font-size:11px;display:flex;flex-direction:column;background:#FFFCEB; }
.card-preview { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Cg opacity='.12'%3E%3Ccircle cx='15' cy='13' r='5' fill='%23F9A8D4'/%3E%3Ccircle cx='33' cy='13' r='5' fill='%23F9A8D4'/%3E%3Ccircle cx='24' cy='24' r='11' fill='%23FBBF24'/%3E%3Ccircle cx='20' cy='23' r='1.5' fill='%23334155'/%3E%3Ccircle cx='28' cy='23' r='1.5' fill='%23334155'/%3E%3Cellipse cx='24' cy='29' rx='4' ry='3' fill='%23FEF3C7'/%3E%3Ccircle cx='24' cy='28' r='1.2' fill='%2392400E'/%3E%3C/g%3E%3C/svg%3E");background-size:30mm 30mm;background-repeat:repeat; }
.card-cartoon { position:absolute;width:13mm;height:13mm;opacity:0.18;pointer-events:none;z-index:0; }
.card-cartoon-middle { left:57mm;top:22mm;width:13mm;height:13mm;transform:rotate(-15deg); }
.card-preview > .card-school-row,.card-preview > .card-subheader,.card-preview > .card-body-area { position:relative;z-index:1; }
.card-back-inner > .card-back-title,.card-back-inner > .card-guardian-list { position:relative;z-index:1; }
.card-school-row { min-height:5.5mm;padding:0.5mm 0.8mm;background-color:transparent;position:relative;display:flex;align-items:center;gap:1mm; }
.card-school-row .school-name { font-size:2.5mm;font-weight:700;color:#111827;line-height:1.25;white-space:pre-wrap;word-break:break-word;display:block; }
.card-subheader { height:5.2mm;min-height:5.2mm;width:60mm;display:flex;align-items:center;justify-content:center;font-size:2.2mm;font-weight:600;letter-spacing:0.4px;color:#fff;margin-top:0.4mm; }
.card-body-area { flex:1;min-height:0;background-color:transparent;padding-top:2.2mm;display:flex;gap:1.5mm;align-items:flex-start; }
.card-body-main { flex:1;display:flex;flex-direction:column;gap:0.3mm;overflow:hidden;min-width:0; }
.card-body-main .info-grid { display:flex;flex-direction:column;gap:0.2mm;margin-bottom:0.2mm; }
.card-body-main .info-grid .card-detail-text { flex:0 0 auto;min-width:0;width:100%; }
.card-primary-row { display:flex;flex-direction:column;gap:0.2mm;width:100%; }
.card-body-main .info-grid .card-primary-row .card-detail-text { flex:0 0 auto;min-width:0;width:100%; }
.card-body-main .info-grid .card-primary-row #pvNickname { white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.card-body-main .info-grid .card-primary-row #pvDob { white-space:normal;overflow:visible;text-overflow:clip;overflow-wrap:anywhere; }
.card-photo-wrap { width:11mm;height:11mm;aspect-ratio:1/1;border-radius:50%;background:#E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;margin-bottom:0.5mm; }
.card-photo-wrap img { width:100%;height:100%;object-fit:cover; }
.card-photo-wrap .photo-fallback { font-size:6.5mm;color:#9CA3AF; }
.qr-print { width:18mm;height:18mm;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.qr-print canvas { width:18mm !important;height:18mm !important; }
.card-name-text { font-weight:700;font-size:3mm;color:#1E3A8A;line-height:1.2;margin-bottom:0.3mm; }
.card-detail-text { font-size:2.2mm;color:#6B7280;line-height:1.2; }
.card-detail-text b { color:#111827; }
.card-blood { color:#B91C1C;font-weight:700;font-size:2.5mm; }
.card-badge-studentid { font-family:monospace;color:#fff;padding:0.4mm 2.5mm;border-radius:2mm;display:inline-block;align-self:flex-start;font-weight:600;margin-top:0.5mm; }
.card-back-preview { width:85.6mm;height:54mm;box-sizing:border-box;justify-content:center;align-items:center;text-align:center;padding:2.5mm;background-color:#FFFCEB; }
.card-back-inner { --card-accent:#1E3A8A;width:100%;height:100%;border:0.5mm solid #E5E7EB;border-radius:3mm;padding:2.5mm;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.2mm;background-color:#FFFCEB;background-image:inherit;background-size:inherit;background-repeat:inherit; }
.card-back-title { font-weight:700;line-height:1.2;white-space:pre-wrap;word-break:break-word; }
.card-guardian-list { width:100%;display:flex;flex-direction:column;gap:1.2mm; }
.card-guardian-row { display:flex;align-items:center;gap:1.5mm;width:100%;text-align:left;min-height:7mm; }
.card-guardian-row-no-photo { justify-content:center;text-align:center; }
.card-guardian-row-no-photo .card-guardian-info { text-align:center; }
.card-guardian-photo { width:7mm;height:7mm;border-radius:50%;background:#E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;color:#9CA3AF; }
.card-guardian-photo img { width:100%;height:100%;object-fit:cover; }
.card-guardian-photo i { font-size:4mm; }
.card-guardian-info { min-width:0;flex:1;line-height:1.15;color:#374151; }
.card-guardian-name { font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.card-guardian-type { color:var(--card-accent); }
.card-guardian-meta { color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.card-guardian-meta-label { color:#64748B;font-weight:600; }
.card-guardian-empty { color:#6B7280;text-align:center; }
</style>
<?php endif; ?>
<?php if ($cardSide === 'back'): ?>
<div class="<?= $cardClass ?> card-back-preview"<?= $cardIdAttr ?><?= $cardStyle ? ' style="' . $cardStyle . '"' : '' ?> data-card-side="back">
  <div class="card-back-inner" style="--card-accent:<?= htmlspecialchars($cardHeaderColor) ?>;border-color:<?= htmlspecialchars($cardHeaderColor) ?>;">
    <svg class="card-cartoon card-cartoon-middle" viewBox="0 0 100 100" aria-hidden="true">
      <circle cx="25" cy="25" r="16" fill="#93C5FD"/><circle cx="75" cy="25" r="16" fill="#93C5FD"/>
      <circle cx="50" cy="53" r="35" fill="#FBBF24"/><circle cx="38" cy="50" r="4" fill="#334155"/><circle cx="62" cy="50" r="4" fill="#334155"/>
      <ellipse cx="50" cy="65" rx="14" ry="10" fill="#FEF3C7"/><circle cx="50" cy="62" r="4" fill="#92400E"/>
      <path d="M43 69 Q50 76 57 69" fill="none" stroke="#92400E" stroke-width="3" stroke-linecap="round"/>
    </svg>
    <div class="card-back-title" id="pvBackTitle" style="font-size:<?= $fontBackTitle ?>mm;color:<?= htmlspecialchars($cardHeaderColor) ?>;"><?= htmlspecialchars($backTitle) ?></div>
    <div class="card-guardian-list" id="pvGuardianList">
      <?php if ($guardianEntries): foreach ($guardianEntries as $guardian): ?>
      <div class="card-guardian-row<?= !empty($guardian['no_photo']) ? ' card-guardian-row-no-photo' : '' ?>">
        <?php if (empty($guardian['no_photo'])): ?>
          <div class="card-guardian-photo" style="width:<?= $sizeBackGuardianPhoto ?>mm;height:<?= $sizeBackGuardianPhoto ?>mm;">
            <?php if (!empty($guardian['image'])): ?><img src="<?= htmlspecialchars($guardian['image']) ?>" alt="">
          <?php elseif (empty($guardian['no_photo'])): ?><i class="bi bi-person-circle" style="font-size:<?= max(3, $sizeBackGuardianPhoto * 0.57) ?>mm;"></i><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="card-guardian-info" style="font-size:<?= $fontBackGuardian ?>mm;">
          <div class="card-guardian-name"><span class="card-guardian-type"><?= htmlspecialchars($guardian['type']) ?>:</span> <?= htmlspecialchars($guardian['name']) ?></div>
          <div class="card-guardian-meta"><span class="card-guardian-meta-label">ความสัมพันธ์:</span> <?= htmlspecialchars($guardian['relation']) ?></div>
          <div class="card-guardian-meta"><span class="card-guardian-meta-label">โทร:</span> <?= htmlspecialchars($guardian['phone']) ?></div>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div class="card-guardian-empty" style="font-size:<?= $fontBackGuardian ?>mm;">ยังไม่มีข้อมูลผู้รับส่ง</div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php else: ?>
<div class="<?= $cardClass ?>"<?= $cardIdAttr ?><?= $cardStyle ? ' style="' . $cardStyle . '"' : '' ?>>
  <div class="card-school-row">
    <div style="flex:1;display:flex;flex-direction:column;">
      <div class="school-name" id="pvSchoolLine1" style="font-size:<?= $schoolFont1 ?>mm;color:<?= htmlspecialchars($cardHeaderColor) ?>"><?= htmlspecialchars($firstLine ?: 'โรงเรียนอนุบาล') ?></div>
      <div class="school-name" id="pvSchoolRest" style="font-size:<?= $schoolFont2 ?>mm;font-weight:400;opacity:0.85;color:<?= htmlspecialchars($cardHeaderColor) ?>;<?= !empty($restLines) ? '' : 'display:none;' ?>"><?= htmlspecialchars(implode("\n", $restLines)) ?></div>
    </div>
    <?php if ($showQR): ?>
    <div<?= $qrIdAttr ?> class="<?= $qrClass ?>" data-token="<?= htmlspecialchars(json_encode(['student_id' => $cardChild['studentid']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>" style="position:absolute;top:0.5mm;right:1mm;z-index:2;color"></div>
    <?php endif; ?>
  </div>
  <div class="card-subheader" style="background:<?= htmlspecialchars($cardHeaderColor) ?>;font-size:3mm;">
    บัตรประจำตัวผู้เรียน
  </div>
  <svg class="card-cartoon card-cartoon-middle" viewBox="0 0 100 100" aria-hidden="true">
    <circle cx="25" cy="25" r="16" fill="#93C5FD"/><circle cx="75" cy="25" r="16" fill="#93C5FD"/>
    <circle cx="50" cy="53" r="35" fill="#FBBF24"/><circle cx="38" cy="50" r="4" fill="#334155"/><circle cx="62" cy="50" r="4" fill="#334155"/>
    <ellipse cx="50" cy="65" rx="14" ry="10" fill="#FEF3C7"/><circle cx="50" cy="62" r="4" fill="#92400E"/>
    <path d="M43 69 Q50 76 57 69" fill="none" stroke="#92400E" stroke-width="3" stroke-linecap="round"/>
  </svg>
  <div class="card-body-area">
    <div class="card-photo-wrap" id="pvPhotoWrap" style="width:100px;height:100px;border-radius:0;<?= $finalPhotoStyle ?>">
      <?php if (!empty($cardChild['profile_image'])): ?>
      <img src="<?= htmlspecialchars($cardChild['profile_image']) ?>" alt="" style="width:100px;height:100px;object-fit:cover;">
      <?php else: ?>
      <span class="photo-fallback" style="font-size:48px;"><i class="bi bi-person-circle"></i></span>
      <?php endif; ?>
    </div>
    <div class="card-body-main" style="align-self:stretch">
      <div class="card-name-text" style="font-size:<?= $nameFont ?>mm;color:<?= htmlspecialchars($cardHeaderColor) ?>"><?= htmlspecialchars($childName) ?></div>
      <div class="info-grid">
        <div class="card-primary-row">
          <div class="card-detail-text" id="pvNickname" style="display:<?= $showNickname ? '' : 'none' ?>;font-size:<?= $fontNickname ?>mm;">ชื่อเล่น: <b><?= htmlspecialchars($childNickname) ?></b></div>
          <div class="card-detail-text" id="pvDob" style="display:<?= $showDob ? '' : 'none' ?>;font-size:<?= $fontDob ?>mm;">ว/ด/ป เกิด: <b><?= htmlspecialchars($childBirthday) ?></b></div>
        </div>
        <div class="card-detail-text card-health-text" id="pvCongenital" style="display:<?= $showCongenital ? '' : 'none' ?>;font-size:<?= $fontCongenital ?>mm;">โรคประจำตัว: <b><?= htmlspecialchars($childCongenital) ?></b></div>
        <div class="card-detail-text card-health-text" id="pvAllergicFood" style="display:<?= $showAllergicFood ? '' : 'none' ?>;font-size:<?= $fontAllergicFood ?>mm;">แพ้อาหาร: <b><?= htmlspecialchars($childAllergicFood) ?></b></div>
        <div class="card-detail-text card-health-text" id="pvAllergicMedicine" style="display:<?= $showAllergicMedicine ? '' : 'none' ?>;font-size:<?= $fontAllergicMedicine ?>mm;">แพ้ยา: <b><?= htmlspecialchars($childAllergicMedicine) ?></b></div>
        <div class="card-detail-text" id="pvClassroom" style="display:<?= $showClassroom ? '' : 'none' ?>;font-size:<?= $fontClassroom ?>mm;">ห้อง: <b><?= htmlspecialchars($childClassroom) ?></b></div>
        <div class="card-detail-text" id="pvBlood" style="display:<?= $showBlood ? '' : 'none' ?>;font-size:<?= $fontBlood ?>mm;">กรุ๊ปเลือด: <span class="card-blood"><?= htmlspecialchars($childBlood) ?></span></div>
      </div>
      <div class="card-badge-studentid" id="pvStudentid" style="display:<?= $showStudentid ? '' : 'none' ?>;font-size:<?= $fontStudentid ?>mm;background-color:<?= htmlspecialchars($cardHeaderColor) ?>;"><?= htmlspecialchars($childStudentId) ?></div>
      <div class="card-detail-text" id="pvExpiry" style="display:<?= $showExpiry ? '' : 'none' ?>;margin-top:auto;text-align:right;font-size:<?= $fontExpiry ?>mm;">หมดอายุ: <b><?= htmlspecialchars($childExpiry) ?></b></div>
    </div>
  </div>
</div>
<?php endif; ?>
