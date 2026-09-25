<?php
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../include/function/token_helper.php';
require_once __DIR__ . '/../../../include/function/template_helper.php';

$pdo = getDatabaseConnection();

$children_ids = isset($_GET['children_ids']) ? array_values(array_filter(array_unique(explode(',', $_GET['children_ids'])), 'strlen')) : [];
$children_id = $_GET['children_id'] ?? null;
$template_id = $_GET['template_id'] ?? null;
$group = $_GET['group'] ?? null;
$classroom = $_GET['classroom'] ?? null;
$academicYear = $_GET['academic_year'] ?? null;
$withQR = isset($_GET['with_qr']);

// รูปในฐานข้อมูลบางรายการเป็นพาธแบบ relative จากหน้า view_child.php
// แต่หน้า print_preview.php อยู่ลึกกว่า 1 ระดับ จึงต้องปรับพาธให้สัมพันธ์กับหน้านี้
function getPrintImagePath(?string $imagePath): ?string {
    if (empty($imagePath)) {
        return null;
    }

    $imagePath = trim($imagePath);
    if (strpos($imagePath, 'data:') === 0 || preg_match('#^https?://#i', $imagePath)) {
        return $imagePath;
    }

    $path = parse_url($imagePath, PHP_URL_PATH) ?: $imagePath;
    if (preg_match('#/public/uploads/(profiles|parents)/#', str_replace('\\', '/', $path), $matches)) {
        $fileName = basename(str_replace('\\', '/', $path));
        return '../../../../public/uploads/' . $matches[1] . '/' . rawurlencode($fileName);
    }

    return $imagePath;
}

function getPrintProfileImagePath(?string $imagePath): ?string {
    return getPrintImagePath($imagePath);
}

if (empty($children_ids) && $group) {
    $sql = "SELECT id FROM children WHERE status = 'กำลังศึกษา'";
    $params = [];
    $groupMap = ['big'=>'เด็กโต','medium'=>'เด็กกลาง','prep'=>'เตรียมอนุบาล'];
    $groupName = $groupMap[$group] ?? $group;
    if ($groupName && $groupName !== 'all') {
        $sql .= " AND child_group = :grp";
        $params['grp'] = $groupName;
    }
    if ($classroom && $classroom !== 'all') {
        $sql .= " AND classroom = :room";
        $params['room'] = $classroom;
    }
    if ($academicYear && $academicYear !== 'all') {
        $sql .= " AND academic_year = :ay";
        $params['ay'] = (int)$academicYear;
    }
    if ($withQR) {
        $sql .= " AND id IN (SELECT children_id FROM student_qr_tokens WHERE is_active = TRUE AND (expires_at IS NULL OR expires_at > NOW()))";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $children_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
} elseif (empty($children_ids) && $children_id) {
    $children_ids = [$children_id];
}

if (empty($children_ids)) {
    die('No students selected');
}

$template = null;
if ($template_id) {
    $templates = getTemplates();
    foreach ($templates as $t) {
        if ($t['id'] == $template_id) { $template = $t; break; }
    }
}
if (!$template) {
    $template = getDefaultTemplate();
}
if (!$template) {
    $template = ['header_color' => '#1E3A8A', 'name' => 'บัตรมาตรฐาน', 'layout_config' => json_encode(['show_nickname'=>true,'show_classroom'=>true,'show_blood'=>false,'show_studentid'=>true,'show_dob'=>true,'show_congenital'=>true,'show_allergic_food'=>true,'show_allergic_medicine'=>true,'show_expiry'=>true,'show_photo'=>true,'show_back'=>true])];
}

$layout = json_decode($template['layout_config'], true) ?: [];
$schoolLines = explode("\n", $layout['school_name'] ?? 'โรงเรียนอนุบาล');
$schoolFirst = trim($schoolLines[0]);
$schoolRest = array_map('trim', array_slice($schoolLines, 1));

$childrenData = [];
foreach ($children_ids as $cid) {
    $child = getChildDataForPrint($pdo, $cid);
    if (!$child) continue;
    $child['profile_image'] = getPrintProfileImagePath($child['profile_image'] ?? null);
    foreach (['father_image', 'mother_image', 'relative_image'] as $guardianImageField) {
        $child[$guardianImageField] = getPrintImagePath($child[$guardianImageField] ?? null);
    }
    $token = getActiveToken($pdo, $cid);
    if (!$token) continue;
    $child['token'] = $token['token'];
    $child['expires_at'] = $token['expires_at'];
    $childrenData[] = $child;
}

if (empty($childrenData)) {
    die('No data to print');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>พิมพ์บัตร QR Code</title>
<link rel="stylesheet" href="../assets/print.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
<style>
    /* ใช้ fallback font ในเครื่อง เพื่อลด third-party resource ที่ไม่มี SRI */
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Sarabun','Kanit',sans-serif; background:#f3f4f6; }
    .no-print { padding:1rem; text-align:center; }
    .no-print button { padding:0.5rem 1.5rem; border-radius:0.5rem; border:none; background:#1E3A8A; color:#fff; cursor:pointer; font-family:'Kanit',sans-serif; font-size:1rem; margin:0 0.25rem; }
    .no-print button:hover { background:#2563EB; }
    .no-print .btn-close-preview { background:#6B7280; }
    .no-print .btn-close-preview:hover { background:#4B5563; }

    /* === PAGE SETUP: A4 แนวตั้ง (Portrait) === */
    @page {
      size: A4 portrait;
      margin: 5mm;
    }

    /* === A4 LAYOUT — same on screen + print === */
    .print-wrapper {
      width: 100%;
      max-width: 180mm;
      margin: 0 auto;
      padding: 0 0 6mm;
    }

    .print-sheet {
      width: 100%;
      max-width: 180mm;
      min-height: 225mm;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(2, 85.6mm);
      gap: 3mm;
      justify-content: center;
      align-content: start;
      page-break-after: always;
      break-after: page;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .print-sheet:last-child { page-break-after: auto; break-after: auto; }

    .print-sheet.mirror-back { direction: rtl; }
    .print-sheet.mirror-back > .card-preview { direction: ltr; }


    .count-badge { position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);background:#1E3A8A;color:#fff;padding:0.35rem 1rem;border-radius:999px;font-size:0.85rem;font-family:'Kanit',sans-serif;z-index:10; }

    .card-preview {
      box-shadow: none !important;
      border: 1px dashed #aaa;
    }

    /* === PRINT: A4 แนวตั้ง สะอาด ไม่มีองค์ประกอบควบคุม === */
    @media print {
      body { background:none; }
      .print-wrapper { padding:0; max-width:none; }
      .print-sheet { max-width:none; min-height:0; }
      .no-print, .count-badge { display:none !important; }
    }
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()"><i class="bi bi-printer"></i> พิมพ์ (<?= count($childrenData) ?> ใบ)</button>
    <label style="display:inline-flex;align-items:center;gap:0.4rem;margin:0 0.5rem;font-family:'Kanit',sans-serif;font-size:0.9rem;">
        <input type="checkbox" id="mirrorBackCheckbox"> กลับด้านหลังซ้าย–ขวา
    </label>
    <button class="btn-close-preview" onclick="window.close()">ปิด</button>
</div>

<div class="print-wrapper" data-header-color="<?= htmlspecialchars($template['header_color'] ?? '#1E3A8A') ?>">
<?php
$cardsPerSheet = 10;
$showBack = !array_key_exists('show_back', $layout) || $layout['show_back'] !== false;
$childSheets = array_chunk($childrenData, $cardsPerSheet);
foreach ($childSheets as $sheetChildren):
    foreach (['front', 'back'] as $sheetSide):
        if ($sheetSide === 'back' && !$showBack) continue;
    ?>
    <div class="print-sheet" data-card-side="<?= $sheetSide ?>">
    <?php foreach ($sheetChildren as $child):
        $layoutConfig = $layout;
        $headerColor = $template['header_color'] ?? '#1E3A8A';
        $childData = $child;
        $cardChild = null;
        $showQR = $sheetSide === 'front';
        $cardSide = $sheetSide;
        include __DIR__ . '/partials/card_template.php';
    endforeach; ?>
    </div>
    <?php endforeach;
endforeach; ?>
</div>

<div class="count-badge no-print">ทั้งหมด <?= count($childrenData) ?> ใบ</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mirrorBackCheckbox = document.getElementById('mirrorBackCheckbox');
    var savedMirrorBack = localStorage.getItem('qrCardMirrorBack') === '1';
    if (mirrorBackCheckbox) {
        mirrorBackCheckbox.checked = savedMirrorBack;
        mirrorBackCheckbox.addEventListener('change', function() {
            localStorage.setItem('qrCardMirrorBack', this.checked ? '1' : '0');
            document.querySelectorAll('.print-sheet[data-card-side="back"]').forEach(function(sheet) {
                sheet.classList.toggle('mirror-back', this.checked);
            }, this);
        });
        if (savedMirrorBack) {
            document.querySelectorAll('.print-sheet[data-card-side="back"]').forEach(function(sheet) {
                sheet.classList.add('mirror-back');
            });
        }
    }
    var headerColor = document.querySelector('.print-wrapper').getAttribute('data-header-color') || '#1E3A8A';
    document.querySelectorAll('.qr-print').forEach(function(el) {
        new QRCode(el, {
            text: el.dataset.token,
            width: 72, height: 72,
            colorDark: headerColor, colorLight: '#FFFFFF',
            correctLevel: QRCode.CorrectLevel.M
        });
    });
});
</script>
</body>
</html>
