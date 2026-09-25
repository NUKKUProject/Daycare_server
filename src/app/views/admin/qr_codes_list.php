<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once __DIR__ . '/../../include/function/token_helper.php';
require_once __DIR__ . '/../../include/function/template_helper.php';
require_once __DIR__ . '/../../include/function/academic_year_functions.php';

$pdo = getDatabaseConnection();
$currentMainTab = $_GET['main_tab'] ?? 'list';
$currentTab = $_GET['tab'] ?? 'all';
$currentView = $_GET['view'] ?? 'card';

$groupMap = ['big'=>'เด็กโต','medium'=>'เด็กกลาง','prep'=>'เตรียมอนุบาล'];
$groupFilter = $groupMap[$currentTab] ?? null;

$academicYears = getAcademicYears();
$selectedYear = $_GET['academic_year'] ?? '';
$academicYearFilter = $selectedYear ? (int)$selectedYear : null;

$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage = 50;
$totalCount = 0;
$students = getStudentsWithTokenStatus($pdo, null, null, null, $groupFilter, $academicYearFilter, $currentPage, $perPage, $totalCount);
$totalPages = max(1, (int)ceil($totalCount / $perPage));

$stats = getStatsCounts($pdo, $groupFilter, $academicYearFilter);
$totalChildren = $stats['total'];
$totalWithToken = $stats['active'];
$totalWithoutToken = $stats['no_token'];
$totalExpired = $stats['expired'];

$templates = getTemplates();
$defaultTemplate = getDefaultTemplate();
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --qr-primary: #2563EB;
  --qr-dark: #1E3A8A;
  --qr-bg: #F3F4F6;
  --qr-surface: #FFFFFF;
  --qr-border: #E5E7EB;
  --qr-text: #111827;
  --qr-text-secondary: #6B7280;
  --qr-success: #15803D;
  --qr-success-bg: #DCFCE7;
  --qr-warning: #A16207;
  --qr-warning-bg: #FEF9C3;
  --qr-error: #B91C1C;
  --qr-error-bg: #FEE2E2;
}
body { background: var(--qr-bg); font-family:'Sarabun','Kanit',sans-serif; }
.page-header-qr {
  background: var(--qr-primary);
  padding:1.5rem 2rem; border-radius:0 0 1rem 1rem; color:#fff; margin-bottom:1.5rem;
  display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;
}
.page-header-qr h1 { font-size:1.5rem; font-weight:600; margin:0; }
.page-header-qr .breadcrumb-qr { font-size:0.85rem; opacity:0.8; }
.page-header-qr .breadcrumb-qr a { color:#fff; text-decoration:none; }
.stats-grid {
  display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem;
}
.stat-card-qr {
  background:#fff; border-radius:0.75rem; padding:1.25rem; display:flex; align-items:center; gap:1rem;
  box-shadow:0 1px 3px rgba(0,0,0,0.06); border:1px solid var(--qr-border);
  transition:transform 0.2s, box-shadow 0.2s;
}
.stat-card-qr:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.stat-icon-qr { width:44px;height:44px;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
.stat-icon-qr.blue { background:#EFF6FF;color:var(--qr-primary); }
.stat-icon-qr.green { background:var(--qr-success-bg);color:var(--qr-success); }
.stat-icon-qr.yellow { background:var(--qr-warning-bg);color:var(--qr-warning); }
.stat-icon-qr.indigo { background:#EEF2FF;color:#4F46E5; }
.stat-val-qr { font-size:1.75rem;font-weight:700;line-height:1.2;color:var(--qr-text); }
.stat-lbl-qr { font-size:0.8rem;color:var(--qr-text-secondary); }
.nav-tabs-qr {
  display:flex;gap:0.25rem;border-bottom:1px solid var(--qr-border);margin-bottom:1.5rem;
}
.nav-tabs-qr .tab-link-qr {
  padding:0.7rem 1.25rem; font-weight:500; font-size:0.9rem; color:var(--qr-text-secondary);
  border:none; background:none; cursor:pointer; border-bottom:3px solid transparent;
  transition:all 0.2s; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;
}
.nav-tabs-qr .tab-link-qr:hover { color:var(--qr-primary); background:#EFF6FF; }
.nav-tabs-qr .tab-link-qr.active { color:var(--qr-primary); border-bottom-color:var(--qr-primary); }
.qr-control-bar {
  background:#fff; border-radius:0.75rem; padding:0.75rem 1.25rem; margin-bottom:1.5rem;
  border:1px solid var(--qr-border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;
}
.qr-filter-tabs { display:flex;gap:0.25rem;list-style:none;padding:0;margin:0;flex-wrap:wrap; }
.qr-filter-tabs a {
  display:inline-flex;align-items:center;gap:0.35rem;padding:0.4rem 0.9rem;border-radius:50rem;
  font-size:0.85rem;font-weight:500;color:var(--qr-text-secondary);text-decoration:none;transition:all 0.2s;
}
.qr-filter-tabs a:hover { background:var(--qr-bg);color:var(--qr-primary); }
.qr-filter-tabs a.active { background:var(--qr-primary);color:#fff; }
.filter-badge { display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.2);border-radius:999px;padding:0 0.5rem;font-size:0.7rem;min-width:1.3rem; }
.qr-filter-tabs a:not(.active) .filter-badge { background:var(--qr-bg);color:var(--qr-text-secondary); }
.search-wrap-qr { position:relative;min-width:140px; }
.search-wrap-qr input { padding-left:2rem;border-radius:50rem;border:1px solid var(--qr-border);font-size:0.85rem;padding:0.4rem 1rem 0.4rem 2rem;width:100%; }
.search-wrap-qr .search-icon-qr { position:absolute;left:0.65rem;top:50%;transform:translateY(-50%);color:var(--qr-text-secondary); }
.child-card-qr {
  background:#fff; border-radius:0.75rem; border:1px solid var(--qr-border); overflow:hidden;
  transition:transform 0.2s, box-shadow 0.2s;
}
.child-card-qr:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.08); }
.child-card-qr .card-header {
  background:var(--qr-primary); padding:0.75rem 1rem; display:flex; align-items:center; gap:0.75rem;
}
.child-card-qr .card-avatar { width:44px;height:44px;border-radius:50%;overflow:hidden;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;flex-shrink:0; }
.child-card-qr .card-avatar img { width:100%;height:100%;object-fit:cover;display:block; }
.child-card-qr .card-name { color:#fff;font-weight:600;font-size:0.9rem;line-height:1.2; }
.child-card-qr .card-meta { color:rgba(255,255,255,0.7);font-size:0.75rem; }
.child-card-qr .card-body { padding:1rem;text-align:center; }
.child-card-qr .qr-placeholder { width:120px;height:120px;margin:0 auto;display:flex;align-items:center;justify-content:center; }
.child-card-qr .qr-placeholder canvas { width:120px;height:120px; }
.child-card-qr .token-hash { font-family:monospace;font-size:0.7rem;color:var(--qr-text-secondary);margin-top:0.5rem;word-break:break-all; }
.child-card-qr .card-footer { padding:0.5rem 1rem;display:flex;gap:0.35rem;border-top:1px solid var(--qr-border);justify-content:center;flex-wrap:wrap; }
.child-card-qr .card-footer .btn-sm { font-size:0.75rem;padding:0.2rem 0.6rem; }
.data-table-qr { width:100%;border-collapse:collapse;font-size:0.85rem; }
.data-table-qr th { background:var(--qr-bg);padding:0.7rem 0.75rem;text-align:left;font-weight:600;color:var(--qr-text-secondary);border-bottom:2px solid var(--qr-border); }
.data-table-qr td { padding:0.6rem 0.75rem;border-bottom:1px solid var(--qr-border);vertical-align:middle; }
.data-table-qr tr:hover td { background:#FAFBFC; }
.badge-token-active { display:inline-flex;align-items:center;gap:3px;background:var(--qr-success-bg);color:var(--qr-success);padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:600; }
.badge-token-missing { display:inline-flex;align-items:center;gap:3px;background:var(--qr-error-bg);color:var(--qr-error);padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:600; }
.tpl-edit-layout { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start; }
.tpl-edit-form { background:#fff;border-radius:0.75rem;border:1px solid var(--qr-border);padding:1.5rem; }
.tpl-preview-panel { background:#fff;border-radius:0.75rem;border:1px solid var(--qr-border);padding:1.5rem;position:sticky;top:1rem; }
.tpl-preview-inner { display:flex;flex-direction:column;align-items:center; }
.tpl-preview-side-label { align-self:flex-start;font-size:0.75rem;color:var(--qr-text-secondary);margin:0.65rem 0 0.35rem; }


#tplCardPreview .card-blood { font-size:inherit; }
.tpl-badge-default { display:inline-flex;align-items:center;gap:4px;background:#DBEAFE;color:var(--qr-primary);padding:2px 10px;border-radius:999px;font-size:0.75rem;font-weight:600; }
.tpl-nav-arrows { display:flex;align-items:center;gap:0.25rem; }
@media (max-width:768px) { .tpl-edit-layout { grid-template-columns:1fr; } }
.color-swatch { display:inline-block;width:28px;height:28px;border-radius:50%;cursor:pointer;border:3px solid transparent;transition:all 0.15s; }
.color-swatch:hover { transform:scale(1.15); }
.color-swatch.active { border-color:var(--qr-text); }
@media (max-width:992px) { .stats-grid { grid-template-columns:repeat(3,1fr); } }
@media (max-width:768px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:576px) { .stats-grid { grid-template-columns:1fr; } }
@media (max-width:768px) {
  .page-header-qr { flex-direction:column;align-items:stretch;text-align:center;padding:1rem; }
  .page-header-qr .d-flex { justify-content:center; }
  .qr-control-bar { flex-direction:column;align-items:stretch; }
  .qr-control-bar .d-flex { flex-wrap:wrap;justify-content:center; }
  .child-card-qr .card-footer .btn-sm { padding:0.3rem 0.8rem;font-size:0.8rem; }
  .modal-footer { flex-direction:column;gap:0.5rem; }
  .modal-footer .btn { width:100%; }
}
@media print { .no-print { display:none !important; } }
</style>

<main class="main-content">
  <div class="page-header-qr">
    <div>
      <h1><i class="bi bi-qr-code me-2"></i>จัดการ QR Code</h1>
      <div class="breadcrumb-qr"><a href="#">หน้าหลัก</a> / จัดการ QR Code</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-light btn-sm" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:#fff;" onclick="showBulkGenerateModal()">
        <i class="bi bi-plus-circle me-1"></i>สร้างจำนวนมาก
      </button>
      <button class="btn btn-light btn-sm" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:#fff;" onclick="showBulkPrintModal()">
        <i class="bi bi-printer me-1"></i>พิมพ์จำนวนมาก
      </button>
    </div>
  </div>

  <div class="container-fluid px-4">
    <div class="stats-grid">
      <div class="stat-card-qr">
        <div class="stat-icon-qr blue"><i class="bi bi-people"></i></div>
        <div><div class="stat-val-qr"><?= $totalChildren ?></div><div class="stat-lbl-qr">เด็กทั้งหมด</div></div>
      </div>
      <div class="stat-card-qr">
        <div class="stat-icon-qr green"><i class="bi bi-check-circle"></i></div>
        <div><div class="stat-val-qr"><?= $totalWithToken + $totalExpired ?></div><div class="stat-lbl-qr">มี QR Code</div></div>
      </div>
      <div class="stat-card-qr">
        <div class="stat-icon-qr yellow"><i class="bi bi-exclamation-circle"></i></div>
        <div><div class="stat-val-qr"><?= $totalWithoutToken ?></div><div class="stat-lbl-qr">ยังไม่มี QR Code</div></div>
      </div>
      <div class="stat-card-qr">
        <div class="stat-icon-qr indigo"><i class="bi bi-printer"></i></div>
        <div><div class="stat-val-qr"><?= $totalWithToken ?></div><div class="stat-lbl-qr">พร้อมพิมพ์</div></div>
      </div>
      <div class="stat-card-qr">
        <div class="stat-icon-qr" style="background:#FEF3C7;color:#D97706;"><i class="bi bi-clock-history"></i></div>
        <div><div class="stat-val-qr"><?= $totalExpired ?></div><div class="stat-lbl-qr">หมดอายุ</div></div>
      </div>
    </div>

    <div class="nav-tabs-qr">
      <a class="tab-link-qr <?= $currentMainTab === 'list' ? 'active' : '' ?>" href="?main_tab=list&tab=<?= $currentTab ?>&view=<?= $currentView ?>&academic_year=<?= $selectedYear ?>"><i class="bi bi-grid-3x3-gap"></i>รายการทั้งหมด</a>
      <a class="tab-link-qr <?= $currentMainTab === 'template' ? 'active' : '' ?>" href="?main_tab=template"><i class="bi bi-palette"></i>จัดการเทมเพลต</a>
    </div>

<?php if ($currentMainTab === 'list'): ?>

    <div class="qr-control-bar">
      <div class="qr-filter-tabs">
        <a href="?main_tab=list&tab=all&view=<?= $currentView ?>&academic_year=<?= $selectedYear ?>" class="<?= $currentTab === 'all' ? 'active' : '' ?>">ทั้งหมด <span class="filter-badge"><?= $totalChildren ?></span></a>
        <?php
        $groupTabCounts = getGroupTabCounts($pdo, $academicYearFilter);
        $groups = ['เด็กโต','เด็กกลาง','เตรียมอนุบาล'];
        $groupKeys = ['big','medium','prep'];
        foreach ($groups as $i => $gname):
          $cnt = $groupTabCounts[$gname] ?? 0;
        ?>
        <a href="?main_tab=list&tab=<?= $groupKeys[$i] ?>&view=<?= $currentView ?>&academic_year=<?= $selectedYear ?>" class="<?= $currentTab === $groupKeys[$i] ? 'active' : '' ?>"><?= $gname ?> <span class="filter-badge"><?= $cnt ?></span></a>
        <?php endforeach; ?>
      </div>
      <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width:auto;min-width:130px;" onchange="filterByAcademicYear(this.value)">
          <option value="" <?= $selectedYear === '' ? 'selected' : '' ?>>ปีการศึกษาทั้งหมด</option>
          <?php foreach ($academicYears as $ay): ?>
          <option value="<?= htmlspecialchars($ay['name']) ?>" <?= (string)$ay['name'] === $selectedYear ? 'selected' : '' ?>>
            ปีการศึกษา <?= htmlspecialchars($ay['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div class="search-wrap-qr">
          <i class="bi bi-search search-icon-qr"></i>
          <input type="text" id="qrSearchInput" placeholder="ค้นหา..." oninput="filterQRCards(this.value)">
        </div>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary <?= $currentView === 'card' ? 'active' : '' ?>" onclick="switchQRView('card')" title="การ์ด"><i class="bi bi-grid-3x3-gap"></i></button>
          <button class="btn btn-outline-secondary <?= $currentView === 'table' ? 'active' : '' ?>" onclick="switchQRView('table')" title="ตาราง"><i class="bi bi-table"></i></button>
        </div>
      </div>
    </div>

    <div id="qrCardView" class="row g-3 <?= $currentView === 'card' ? '' : 'd-none' ?>">
      <?php foreach ($students as $s): ?>
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 qr-card-col" data-search="<?= htmlspecialchars(mb_strtolower($s['firstname_th'].' '.$s['lastname_th'].' '.$s['studentid'].' '.$s['nickname'])) ?>">
        <div class="child-card-qr">
          <div class="card-header">
            <div class="card-avatar"><?php if (!empty($s['profile_image'])): ?><img src="<?= htmlspecialchars($s['profile_image']) ?>" alt=""><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?></div>
            <div>
              <div class="card-name"><?= htmlspecialchars($s['prefix_th'] . $s['firstname_th'] . ' ' . $s['lastname_th']) ?></div>
              <div class="card-meta"><?= htmlspecialchars($s['studentid']) ?> • ห้อง<?= htmlspecialchars($s['classroom']) ?></div>
            </div>
          </div>
          <div class="card-body">
            <?php if ($s['has_active_token'] && !$s['is_expired'] && $s['active_token']): ?>
            <div class="qr-placeholder" id="qrc-<?= $s['id'] ?>" data-token="<?= htmlspecialchars(json_encode(['student_id' => $s['studentid']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="token-hash"><?= htmlspecialchars(mb_substr($s['active_token'], 0, 20)) ?>...</div>
            <?php elseif ($s['is_expired']): ?>
            <div style="padding:1.5rem;color:#D97706;">
              <i class="bi bi-clock-history" style="font-size:1.9rem;display:block;margin-bottom:0.5rem;opacity:0.6;"></i>
              <span style="font-size:0.85rem;font-weight:600;">หมดอายุ</span>
              <div style="font-size:0.7rem;margin-top:0.25rem;"><?= htmlspecialchars(date('d/m/Y', strtotime($s['token_expires_at']))) ?></div>
            </div>
            <?php else: ?>
            <div style="padding:1.5rem;color:var(--qr-text-secondary);">
              <i class="bi bi-qr-code-scan" style="font-size:2.5rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
              <span style="font-size:0.85rem;">ยังไม่มี QR Code</span>
            </div>
            <?php endif; ?>
          </div>
          <div class="card-footer">
            <?php if ($s['has_active_token'] && !$s['is_expired']): ?>
            <button class="btn btn-outline-primary btn-sm" onclick="printSingleCard(<?= $s['id'] ?>)"><i class="bi bi-printer"></i></button>
            <button class="btn btn-outline-danger btn-sm" onclick="regenerateSingle(<?= $s['id'] ?>, '<?= htmlspecialchars($s['firstname_th'] . ' ' . $s['lastname_th'], ENT_QUOTES) ?>')"><i class="bi bi-arrow-repeat"></i></button>
            <?php else: ?>
            <button class="btn btn-primary btn-sm" onclick="generateSingle(<?= $s['id'] ?>)"><i class="bi bi-plus-circle"></i> สร้าง</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($students)): ?>
      <div class="col-12 text-center py-5" style="color:var(--qr-text-secondary);">ไม่พบข้อมูลนักเรียน</div>
      <?php endif; ?>
      <div id="qrNoResults" class="col-12 text-center py-5 d-none" style="color:var(--qr-text-secondary);"><i class="bi bi-search" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>ไม่พบผลการค้นหา</div>
    </div>

    <div id="qrTableView" class="<?= $currentView === 'table' ? '' : 'd-none' ?>">
      <div style="background:#fff;border-radius:0.75rem;border:1px solid var(--qr-border);overflow-x:auto;">
        <table class="data-table-qr" id="qrDataTable" style="min-width:700px;">
          <thead>
            <tr>
              <th style="width:40px;"><input type="checkbox" id="selectAllQR" onchange="toggleSelectAll(this)"></th>
              <th>รหัสนักเรียน</th>
              <th>ชื่อ-นามสกุล</th>
              <th>กลุ่ม / ห้อง</th>
              <th>สถานะ QR Code</th>
              <th style="width:100px;">วันหมดอายุ</th>
              <th style="width:140px;">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): ?>
            <tr data-id="<?= $s['id'] ?>">
              <td><input type="checkbox" class="qr-select-row" value="<?= $s['id'] ?>"></td>
              <td style="font-family:monospace;font-size:0.8rem;"><?= htmlspecialchars($s['studentid']) ?></td>
              <td><?= htmlspecialchars($s['prefix_th'] . $s['firstname_th'] . ' ' . $s['lastname_th']) ?></td>
              <td><?= htmlspecialchars($s['child_group'] . ' / ' . $s['classroom']) ?></td>
              <td>
                <?php if ($s['has_active_token'] && !$s['is_expired']): ?>
                <span class="badge-token-active"><i class="bi bi-check-circle-fill"></i>พร้อมใช้งาน</span>
                <?php elseif ($s['is_expired']): ?>
                <span style="display:inline-flex;align-items:center;gap:3px;background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:600;"><i class="bi bi-clock-history"></i>หมดอายุ</span>
                <?php else: ?>
                <span class="badge-token-missing"><i class="bi bi-x-circle-fill"></i>ยังไม่มี</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.8rem;white-space:nowrap;">
                <?php if ($s['has_active_token'] && $s['token_expires_at']): ?>
                  <?= htmlspecialchars(date('d/m/Y', strtotime($s['token_expires_at']))) ?>
                <?php elseif ($s['has_active_token'] && !$s['token_expires_at']): ?>
                  <span style="color:var(--qr-text-secondary);">ไม่หมดอายุ</span>
                <?php else: ?>
                  <span style="color:var(--qr-text-secondary);">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($s['has_active_token'] && !$s['is_expired']): ?>
                <button class="btn btn-outline-primary btn-sm" onclick="printSingleCard(<?= $s['id'] ?>)"><i class="bi bi-printer"></i></button>
                <button class="btn btn-outline-danger btn-sm" onclick="regenerateSingle(<?= $s['id'] ?>, '<?= htmlspecialchars($s['firstname_th'] . ' ' . $s['lastname_th'], ENT_QUOTES) ?>')"><i class="bi bi-arrow-repeat"></i></button>
                <?php else: ?>
                <button class="btn btn-primary btn-sm" onclick="generateSingle(<?= $s['id'] ?>)"><i class="bi bi-plus-circle me-1"></i>สร้าง</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-2 d-flex gap-2">
        <button class="btn btn-primary btn-sm" onclick="bulkPrintSelected()" disabled id="bulkPrintBtn"><i class="bi bi-printer me-1"></i>พิมพ์ที่เลือก</button>
        <button class="btn btn-outline-danger btn-sm" onclick="bulkRegenerateSelected()" disabled id="bulkRegenBtn"><i class="bi bi-arrow-repeat me-1"></i>ออกบัตรใหม่ที่เลือก</button>
      </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <?php
    $pageUrl = "?main_tab=list&tab=" . urlencode($currentTab) . "&view=" . urlencode($currentView) . "&academic_year=" . urlencode($selectedYear);
    ?>
    <nav aria-label="Page navigation" class="mt-3 mb-3">
      <ul class="pagination justify-content-center mb-0">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $pageUrl ?>&p=<?= $currentPage - 1 ?>" tabindex="-1" aria-disabled="<?= $currentPage <= 1 ? 'true' : 'false' ?>">ก่อนหน้า</a>
        </li>
        <?php
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        if ($startPage > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= $pageUrl ?>&p=1">1</a></li>
        <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
          <a class="page-link" href="<?= $pageUrl ?>&p=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($endPage < $totalPages): ?>
        <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= $pageUrl ?>&p=<?= $totalPages ?>"><?= $totalPages ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $pageUrl ?>&p=<?= $currentPage + 1 ?>">ถัดไป</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>

    <!-- Bulk Generate Modal -->
    <div class="modal fade" id="bulkGenerateModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;border:none;">
          <div class="modal-header" style="background:var(--qr-primary);color:#fff;border:none;">
            <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>สร้าง QR Code จำนวนมาก</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-medium">กลุ่มเรียน</label>
              <select class="form-select" id="bulkGroup">
                <option value="all">ทั้งหมด</option>
                <option value="เด็กโต">เด็กโต</option>
                <option value="เด็กกลาง">เด็กกลาง</option>
                <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
              </select>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="bulkWithoutQR" checked>
              <label class="form-check-label" for="bulkWithoutQR">สร้างเฉพาะเด็กที่ยังไม่มี QR Code</label>
            </div>
          </div>
          <div class="modal-footer" style="border-top:1px solid var(--qr-border);">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="bulkGenerate()"><i class="bi bi-plus-circle me-1"></i>สร้าง</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Print Modal -->
    <div class="modal fade" id="bulkPrintModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;border:none;">
          <div class="modal-header" style="background:var(--qr-primary);color:#fff;border:none;">
            <h5 class="modal-title"><i class="bi bi-printer me-2"></i>พิมพ์ QR Code จำนวนมาก</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-medium">กลุ่มเรียน</label>
              <select class="form-select" id="printGroup" onchange="loadPrintClassrooms(this.value)">
                <option value="all">ทั้งหมด</option>
                <option value="เด็กโต">เด็กโต</option>
                <option value="เด็กกลาง">เด็กกลาง</option>
                <option value="เตรียมอนุบาล">เตรียมอนุบาล</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">ห้องเรียน</label>
              <select class="form-select" id="printClassroom">
                <option value="all">ทั้งหมด</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">ปีการศึกษา</label>
              <select class="form-select" id="printAcademicYear">
                <option value="all">ทั้งหมด</option>
                <?php foreach ($academicYears as $ay): ?>
                <option value="<?= htmlspecialchars($ay['name']) ?>">ปีการศึกษา <?= htmlspecialchars($ay['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">เทมเพลต</label>
              <select class="form-select" id="printTemplate">
                <?php foreach ($templates as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($t['is_default'] || $t['id'] === ($defaultTemplate['id'] ?? null)) ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="printWithQR" checked>
              <label class="form-check-label" for="printWithQR">เฉพาะเด็กที่มี QR Code</label>
            </div>
          </div>
          <div class="modal-footer" style="border-top:1px solid var(--qr-border);">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="bulkPrint()"><i class="bi bi-printer me-1"></i>พิมพ์</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Regenerate Confirm Modal -->
    <div class="modal fade" id="regenConfirmModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;border:none;">
          <div class="modal-header" style="background:linear-gradient(135deg,#B91C1C,#DC2626);color:#fff;border:none;">
            <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>ยืนยันออกบัตรใหม่</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p id="regenModalBody" style="color:var(--qr-text-secondary);">การออกบัตรใหม่จะทำให้ QR Code เก่าไม่สามารถใช้งานได้อีก</p>
            <div class="mb-3">
              <label class="form-label fw-medium">เหตุผล</label>
              <select class="form-select" id="regenReason">
                <option value="regenerate">ออกบัตรใหม่ทั่วไป</option>
                <option value="lost_card">บัตรสูญหาย</option>
                <option value="damaged">บัตรชำรุด</option>
              </select>
            </div>
          </div>
          <div class="modal-footer" style="border-top:1px solid var(--qr-border);">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-danger" id="confirmRegenBtn"><i class="bi bi-arrow-repeat me-1"></i>ยืนยัน</button>
          </div>
        </div>
      </div>
    </div>

<?php elseif ($currentMainTab === 'template'): ?>

    <?php
    $sampleChild = $pdo->query("
        SELECT c.id, c.prefix_th, c.firstname_th, c.lastname_th, c.nickname,
               c.classroom, c.child_group, c.blood_type, c.studentid, c.birthday,
               c.congenital_disease,
               COALESCE(
                   (SELECT string_agg(NULLIF(BTRIM(fa.food_name), ''), ', ' ORDER BY fa.id)
                    FROM food_allergies fa WHERE fa.student_id = c.studentid),
                   NULLIF(BTRIM(c.allergic_food), '')
               ) AS allergic_food,
               COALESCE(
                   (SELECT string_agg(NULLIF(BTRIM(da.drug_name), ''), ', ' ORDER BY da.id)
                    FROM drug_allergies da WHERE da.student_id = c.studentid),
                   NULLIF(BTRIM(c.allergic_medicine), '')
               ) AS allergic_medicine,
               c.profile_image,
               c.father_first_name, c.father_last_name,
               c.mother_first_name, c.mother_last_name,
               c.relative_first_name, c.relative_last_name,
               c.father_phone, c.father_phone_backup,
               c.mother_phone, c.mother_phone_backup,
               c.relative_phone, c.relative_phone_backup,
               c.father_image, c.mother_image, c.relative_image,
               c.emergency_contact, c.emergency_phone, c.emergency_relation,
               t.token, t.expires_at
        FROM children c
        LEFT JOIN LATERAL (
            SELECT token, expires_at FROM student_qr_tokens
            WHERE children_id = c.id AND is_active = TRUE
              AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC LIMIT 1
        ) t ON true
        WHERE c.status = 'กำลังศึกษา' AND t.token IS NOT NULL
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    $sampleToken = $sampleChild['token'] ?? ('QR-' . time() . '-PREVIEW');
    $defaultId = $defaultTemplate['id'] ?? ($templates[0]['id'] ?? null);
    $firstId = $templates[0]['id'] ?? null;
    $defaultTpl = null;
    foreach ($templates as $t) {
        if ($t['id'] === $defaultId) { $defaultTpl = $t; break; }
    }
    if (!$defaultTpl) $defaultTpl = $templates[0] ?? null;
    $curCfg = $defaultTpl ? (json_decode($defaultTpl['layout_config'], true) ?: []) : [];
    ?>

    <div class="tpl-edit-layout mb-4">
      <div class="tpl-edit-form">
        <input type="hidden" id="tplEditId" value="<?= $defaultTpl['id'] ?? '' ?>">
        <div class="mb-3">
          <label class="form-label fw-medium">ชื่อเทมเพลต</label>
          <input type="text" class="form-control" id="tplName" placeholder="เช่น บัตรมาตรฐาน" value="<?= htmlspecialchars($defaultTpl['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-medium">ชื่อโรงเรียน</label>
          <textarea class="form-control" id="tplSchoolName" placeholder="เช่น โรงเรียนอนุบาล" rows="2" oninput="updatePreview()"><?= htmlspecialchars($curCfg['school_name'] ?? 'โรงเรียนอนุบาล') ?></textarea>
          <div class="mt-1 d-flex align-items-center gap-2">
            <label style="font-size:0.75rem;white-space:nowrap;">บรรทัด 1:</label>
            <input type="number" class="form-control form-control-sm" style="width:60px;" id="fontSchoolname" value="<?= $curCfg['font_schoolname'] ?? '2.2' ?>" min="1" max="5" step="0.1" oninput="updatePreview()">
            <span style="font-size:0.7rem;">mm</span>
            <label style="font-size:0.75rem;white-space:nowrap;">บรรทัดถัดไป:</label>
            <input type="number" class="form-control form-control-sm" style="width:60px;" id="fontSchoolname2" value="<?= $curCfg['font_schoolname2'] ?? '1.8' ?>" min="1" max="5" step="0.1" oninput="updatePreview()">
            <span style="font-size:0.7rem;">mm</span>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-medium">ชื่อนักเรียน</label>
          <div class="d-flex align-items-center gap-2">
            <label style="font-size:0.75rem;white-space:nowrap;">ขนาด:</label>
            <input type="number" class="form-control form-control-sm" style="width:70px;" id="fontName" value="<?= $curCfg['font_name'] ?? '3' ?>" min="1" max="5" step="0.1" oninput="updatePreview()">
            <span style="font-size:0.7rem;">mm</span>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-medium">สีหัวข้อ</label>
          <div class="d-flex gap-2 flex-wrap" id="colorSwatches">
            <?php
            $colors = ['#1E3A8A','#2563EB','#7C3AED','#BE123C','#B91C1C','#15803D','#A16207','#0F766E','#6B7280','#111827'];
            $curColor = $defaultTpl['header_color'] ?? '#1E3A8A';
            foreach ($colors as $c):
            ?>
            <span class="color-swatch <?= $c === $curColor ? 'active' : '' ?>" style="background:<?= $c ?>;" data-color="<?= $c ?>" onclick="selectColor(this);updatePreview()"></span>
            <?php endforeach; ?>
            <input type="color" id="tplCustomColor" style="width:28px;height:28px;border-radius:50%;padding:0;border:2px solid var(--qr-border);cursor:pointer;" value="<?= $curColor ?>" onchange="applyCustomColor(this);updatePreview()">
          </div>
          <input type="hidden" id="tplHeaderColor" value="<?= $curColor ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-medium">ข้อมูลที่แสดงบนบัตร</label>
          <div class="row g-2">
            <?php
            $fieldOpts = [
              'show_nickname' => 'ชื่อเล่น',
              'show_classroom' => 'ห้องเรียน',
              'show_blood' => 'กรุ๊ปเลือด',
              'show_studentid' => 'รหัสนักเรียน',
              'show_dob' => 'วันเดือนปีเกิด',
              'show_congenital' => 'โรคประจำตัว',
              'show_allergic_food' => 'แพ้อาหาร',
              'show_allergic_medicine' => 'แพ้ยา',
              'show_expiry' => 'วันหมดอายุ',
            ];
            $fontDefaults = [
              'show_nickname' => '2.2', 'show_classroom' => '2.2', 'show_blood' => '2.2',
              'show_studentid' => '2', 'show_dob' => '1.8', 'show_congenital' => '1.7',
              'show_allergic_food' => '1.7', 'show_allergic_medicine' => '1.7', 'show_expiry' => '2.2'
            ];
            foreach ($fieldOpts as $k => $v):
              $defaultChecked = in_array($k, ['show_nickname','show_classroom','show_studentid','show_dob','show_congenital','show_allergic_food','show_allergic_medicine','show_expiry'], true);
              $checked = isset($curCfg[$k]) ? $curCfg[$k] : $defaultChecked;
            ?>
            <div class="col-md-6">
              <div class="d-flex align-items-center gap-1">
                <input class="form-check-input tpl-field" type="checkbox" value="<?= $k ?>" id="field_<?= $k ?>" <?= $checked ? 'checked' : '' ?> onchange="updatePreview()">
                <label class="form-check-label" for="field_<?= $k ?>" style="flex:1;font-size:0.85rem;"><?= $v ?></label>
                <input type="number" class="form-control form-control-sm" style="width:55px;flex-shrink:0;" id="font_<?= $k ?>" value="<?= $curCfg['font_'.$k] ?? $fontDefaults[$k] ?>" min="1" max="5" step="0.1" oninput="updatePreview()">
                <span style="font-size:0.65rem;flex-shrink:0;">mm</span>
              </div>
            </div>
            <?php endforeach; ?>
            <div class="col-12">
              <div class="d-flex align-items-center gap-2 mt-1">
                <input class="form-check-input tpl-field" type="checkbox" value="show_photo" id="field_show_photo" <?= ($curCfg['show_photo'] ?? true) ? 'checked' : '' ?> onchange="updatePreview()">
                <label class="form-check-label" for="field_show_photo" style="font-size:0.85rem;cursor:pointer;">รูปภาพนักเรียน</label>
              </div>
            </div>
          </div>
        </div>
        <div class="mb-3 border-top pt-3">
          <label class="form-label fw-medium"><i class="bi bi-credit-card-2-front me-1"></i>หลังบัตร</label>
          <div class="small text-muted mb-2">แสดงชื่อ รูปถ่าย ความสัมพันธ์ และเบอร์โทรของผู้รับส่งที่มีข้อมูลในระบบ</div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <input class="form-check-input tpl-field" type="checkbox" value="show_back" id="field_show_back" <?= ($curCfg['show_back'] ?? true) ? 'checked' : '' ?> onchange="updatePreview()">
            <label class="form-check-label" for="field_show_back" style="font-size:0.85rem;">พิมพ์หลังบัตร</label>
          </div>
          <div class="mb-2">
            <label class="form-label small mb-1">หัวข้อหลังบัตร</label>
            <input type="text" class="form-control form-control-sm" id="tplBackTitle" value="<?= htmlspecialchars(in_array($curCfg['back_title'] ?? '', ['ข้อมูลสำคัญ', 'ข้อมูลผู้รับส่ง'], true) || empty($curCfg['back_title']) ? 'ข้อมูลผู้รับส่ง (ผู้ปกครอง)' : $curCfg['back_title']) ?>" oninput="updatePreview()">
            <div class="d-flex align-items-center gap-2 mt-1">
              <label style="font-size:0.75rem;white-space:nowrap;">ขนาด:</label>
              <input type="number" class="form-control form-control-sm" style="width:70px;" id="fontBackTitle" value="<?= $curCfg['font_back_title'] ?? '2.8' ?>" min="1" max="5" step="0.1" oninput="updatePreview()">
              <span style="font-size:0.7rem;">mm</span>
            </div>
          </div>
          <div class="mt-2">
            <label class="form-label small mb-1">ขนาดข้อมูลผู้รับส่ง</label>
            <div class="d-flex align-items-center gap-2">
              <input type="number" class="form-control form-control-sm" style="width:70px;" id="fontBackGuardian" value="<?= $curCfg['font_back_guardian'] ?? '1.6' ?>" min="1" max="3" step="0.1" oninput="updatePreview()">
              <span style="font-size:0.7rem;">mm</span>
            </div>
            <label class="form-label small mb-1 mt-2">ขนาดรูปผู้รับส่ง</label>
            <div class="d-flex align-items-center gap-2">
              <input type="number" class="form-control form-control-sm" style="width:70px;" id="sizeBackGuardianPhoto" value="<?= $curCfg['size_back_guardian_photo'] ?? '7' ?>" min="4" max="15" step="0.5" oninput="updatePreview()">
              <span style="font-size:0.7rem;">mm</span>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" onclick="saveTemplate()"><i class="bi bi-save me-1"></i>บันทึก</button>
        </div>
      </div>

      <div class="tpl-preview-panel">
        <div style="font-size:0.9rem;font-weight:600;color:var(--qr-text);margin-bottom:0.75rem;display:flex;align-items:center;gap:0.5rem;">
          <i class="bi bi-card-image" style="color:var(--qr-primary);"></i>ตัวอย่างบัตร
        </div>
        <div class="tpl-preview-inner">
          <?php
          $cardChild = $sampleChild;
          $cardLayout = $curCfg;
          $cardHeaderColor = $curColor;
          $cardId = 'tplCardPreview';
          $qrContainerId = 'tplPreviewQR';
          $showQR = true;
          $cardSide = 'front';
          include __DIR__ . '/qr_tabs/partials/card_template.php';
          echo '<div class="tpl-preview-side-label" id="tplBackLabel">ด้านหลัง</div>';
          $cardId = 'tplBackPreview';
          $qrContainerId = null;
          $showQR = false;
          $cardSide = 'back';
          $cardStyle = ($curCfg['show_back'] ?? true) ? '' : 'display:none;';
          include __DIR__ . '/qr_tabs/partials/card_template.php';
          ?>
          <div style="text-align:center;margin-top:0.5rem;font-size:0.75rem;color:var(--qr-text-secondary);">
            ขนาดจริง: 85.6 × 54 มม.
          </div>
        </div>
      </div>
    </div>

<?php endif; ?>

  </div>
</main>

<div class="toast-container-qr" id="toastContainerQR" style="position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;"></div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU" crossorigin="anonymous"></script>
<script>
let regenQueue = [];
let isBulkRegen = false;

function showToast(msg, type) {
  const icons = {success:'bi-check-circle-fill text-success',error:'bi-x-circle-fill text-danger',warning:'bi-exclamation-circle-fill text-warning'};
  const icon = icons[type]||icons.success;
  const tc = document.getElementById('toastContainerQR');
  const item = document.createElement('div');
  item.style.cssText = 'background:#fff;border-radius:0.75rem;padding:0.85rem 1.25rem;box-shadow:0 1rem 2.5rem rgba(0,0,0,0.12);display:flex;align-items:center;gap:0.75rem;animation:slideInRight 0.3s ease;border-left:4px solid #2563EB;min-width:280px;font-size:0.9rem;font-family:Sarabun,sans-serif;';
  if (type==='success') item.style.borderLeftColor='#15803D';
  else if (type==='error') item.style.borderLeftColor='#B91C1C';
  item.innerHTML = `<i class="bi ${icon}"></i><span style="color:#111827;">${msg}</span><button style="margin-left:auto;border:none;background:none;cursor:pointer;color:#6B7280;" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>`;
  tc.appendChild(item);
  setTimeout(()=>{item.style.animation='slideOutRight 0.3s ease forwards';setTimeout(()=>item.remove(),300);},3500);
}

document.addEventListener('DOMContentLoaded', function() {
  const qrElements = document.querySelectorAll('[data-token]');
  let qrIndex = 0;
  const BATCH_SIZE = 10;

  function generateNextBatch() {
    const end = Math.min(qrIndex + BATCH_SIZE, qrElements.length);
    for (let i = qrIndex; i < end; i++) {
      const el = qrElements[i];
      if (el.id && el.id.startsWith('qrc-')) {
        new QRCode(el, {
          text: el.dataset.token,
          width: 120, height: 120,
          colorDark: '#1E3A8A', colorLight: '#FFFFFF',
          correctLevel: QRCode.CorrectLevel.M
        });
      }
    }
    qrIndex = end;
    if (qrIndex < qrElements.length) {
      requestAnimationFrame(generateNextBatch);
    } else {
      console.log('QR codes generated for', qrElements.length, 'cards');
    }
  }

  generateNextBatch();

  document.querySelectorAll('.qr-select-row').forEach(cb => {
    cb.addEventListener('change', updateBulkButtons);
  });
});

function filterQRCards(val) {
  const q = val.toLowerCase().trim();
  const cards = document.querySelectorAll('.qr-card-col');
  let visible = 0;
  cards.forEach(c => {
    const match = c.dataset.search.includes(q);
    c.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  document.getElementById('qrNoResults').classList.toggle('d-none', visible > 0 || cards.length === 0);
}

function switchQRView(view) {
  const url = new URL(window.location.href);
  url.searchParams.set('view', view);
  window.location.href = url.toString();
}

function filterByAcademicYear(year) {
  const url = new URL(window.location.href);
  if (year) {
    url.searchParams.set('academic_year', year);
  } else {
    url.searchParams.delete('academic_year');
  }
  url.searchParams.set('p', '1');
  window.location.href = url.toString();
}

function generateSingle(id) {
  fetch('../../include/function/api_generate_token.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({children_id: id})
  }).then(r=>r.json()).then(d => {
    if (d.success) { showToast('สร้าง QR Code เรียบร้อย','success'); setTimeout(()=>location.reload(),500); }
    else showToast(d.message||'เกิดข้อผิดพลาด','error');
  }).catch(()=>showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ','error'));
}

function regenerateSingle(id, name) {
  document.getElementById('regenModalBody').textContent = `ต้องการออกบัตรใหม่ให้ "${name}"? QR Code เก่าจะไม่สามารถใช้งานได้อีก`;
  document.getElementById('confirmRegenBtn').onclick = function() {
    const reason = document.getElementById('regenReason').value;
    fetch('../../include/function/api_regenerate_token.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({children_id: id, reason: reason})
    }).then(r=>r.json()).then(d => {
      if (d.success) {
        bootstrap.Modal.getInstance(document.getElementById('regenConfirmModal'))?.hide();
        showToast('ออกบัตรใหม่เรียบร้อย','success');
        setTimeout(()=>location.reload(),500);
      } else showToast(d.message||'เกิดข้อผิดพลาด','error');
    }).catch(()=>showToast('เกิดข้อผิดพลาด','error'));
  };
  new bootstrap.Modal(document.getElementById('regenConfirmModal')).show();
}

function printSingleCard(id) {
  const templateParam = currentTemplateId ? '&template_id=' + encodeURIComponent(currentTemplateId) : '';
  window.open('../admin/qr_tabs/print_preview.php?children_id=' + encodeURIComponent(id) + templateParam, '_blank');
}

function showBulkGenerateModal() {
  new bootstrap.Modal(document.getElementById('bulkGenerateModal')).show();
}

function showBulkPrintModal() {
  new bootstrap.Modal(document.getElementById('bulkPrintModal')).show();
}

function loadPrintClassrooms(group) {
  const sel = document.getElementById('printClassroom');
  sel.innerHTML = '<option value="all">ทั้งหมด</option>';
  if (group === 'all') return;
  fetch('../../include/function/get_classrooms.php?child_group=' + encodeURIComponent(group))
    .then(r=>r.json()).then(rooms => {
      rooms.forEach(r => { const o=document.createElement('option'); o.value=r.classroom_name; o.textContent=r.classroom_name; sel.appendChild(o); });
    }).catch(()=>{});
}

function bulkGenerate() {
  const group = document.getElementById('bulkGroup').value;
  const onlyMissing = document.getElementById('bulkWithoutQR').checked;
  const btn = document.querySelector('#bulkGenerateModal .btn-primary');
  btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังสร้าง...';
  fetch('../../include/function/api_generate_token.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({group: group === 'all' ? null : group, only_missing: onlyMissing})
  }).then(r=>r.json()).then(d => {
    btn.disabled = false; btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>สร้าง';
    bootstrap.Modal.getInstance(document.getElementById('bulkGenerateModal'))?.hide();
    if (d.success) { showToast(`สร้าง QR Code จำนวน ${d.count || 0} รายการเรียบร้อย`,'success'); setTimeout(()=>location.reload(),800); }
    else showToast(d.message||'เกิดข้อผิดพลาด','error');
  }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-plus-circle me-1"></i>สร้าง'; showToast('เกิดข้อผิดพลาด','error'); });
}

function bulkPrint() {
  const group = document.getElementById('printGroup').value;
  const classroom = document.getElementById('printClassroom').value;
  const templateId = document.getElementById('printTemplate').value;
  const withQR = document.getElementById('printWithQR').checked;
  const academicYear = document.getElementById('printAcademicYear').value;
  let url = `../admin/qr_tabs/print_preview.php?group=${group}&classroom=${classroom}&template_id=${templateId}&academic_year=${academicYear}`;
  if (withQR) url += '&with_qr=1';
  window.open(url, '_blank');
  bootstrap.Modal.getInstance(document.getElementById('bulkPrintModal'))?.hide();
}

function toggleSelectAll(master) {
  document.querySelectorAll('.qr-select-row').forEach(cb => cb.checked = master.checked);
  updateBulkButtons();
}

function updateBulkButtons() {
  const checked = document.querySelectorAll('.qr-select-row:checked').length;
  document.getElementById('bulkPrintBtn').disabled = checked === 0;
  document.getElementById('bulkRegenBtn').disabled = checked === 0;
}

function bulkPrintSelected() {
  const ids = Array.from(document.querySelectorAll('.qr-select-row:checked')).map(cb => cb.value);
  if (ids.length === 0) return;
  const templateParam = currentTemplateId ? '&template_id=' + encodeURIComponent(currentTemplateId) : '';
  window.open('../admin/qr_tabs/print_preview.php?children_ids=' + ids.map(encodeURIComponent).join(',') + templateParam, '_blank');
}

function bulkRegenerateSelected() {
  const ids = Array.from(document.querySelectorAll('.qr-select-row:checked')).map(cb => cb.value);
  if (ids.length === 0) return;
  document.getElementById('regenModalBody').textContent = `ต้องการออกบัตรใหม่ ${ids.length} คน?`;
  document.getElementById('confirmRegenBtn').onclick = function() {
    const reason = document.getElementById('regenReason').value;
    let done = 0, err = 0;
    ids.forEach(id => {
      fetch('../../include/function/api_regenerate_token.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({children_id: id, reason: reason})
      }).then(r=>r.json()).then(d => {
        if (d.success) done++; else err++;
        if (done + err === ids.length) {
          bootstrap.Modal.getInstance(document.getElementById('regenConfirmModal'))?.hide();
          showToast(`ออกบัตรใหม่ ${done} คน สำเร็จ${err ? ' (ล้มเหลว '+err+' คน)' : ''}`, err ? 'warning' : 'success');
          setTimeout(()=>location.reload(),500);
        }
      }).catch(()=>{ err++; if (done+err===ids.length) { bootstrap.Modal.getInstance(document.getElementById('regenConfirmModal'))?.hide(); showToast('เกิดข้อผิดพลาด','error'); } });
    });
  };
  new bootstrap.Modal(document.getElementById('regenConfirmModal')).show();
}

let currentTemplateId = <?= $defaultTpl['id'] ?? 'null' ?>;
let qrPreviewInstance = null;
const templateIds = <?= json_encode(array_values(array_map(fn($t) => (int)$t['id'], $templates))) ?>;

function selectColor(el) {
  document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('tplHeaderColor').value = el.dataset.color;
}

function applyCustomColor(input) {
  document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
  document.getElementById('tplHeaderColor').value = input.value;
}

function selectTemplate(id) {
  if (!id) return;
  currentTemplateId = id;
  fetch('../../include/function/api_get_template.php?id=' + id)
    .then(r => r.json()).then(t => {
      if (!t) return;
      document.getElementById('tplEditId').value = t.id;
      document.getElementById('tplName').value = t.name || '';
      document.getElementById('tplHeaderColor').value = t.header_color || '#1E3A8A';
      const templateSelector = document.getElementById('templateSelector');
      if (templateSelector) templateSelector.value = id;
      document.querySelectorAll('.color-swatch').forEach(s => s.classList.toggle('active', s.dataset.color === (t.header_color || '#1E3A8A')));
      if (t.layout_config) {
        document.querySelectorAll('.tpl-field').forEach(cb => {
          const defaultOn = ['show_dob','show_congenital','show_allergic_food','show_allergic_medicine','show_back'].includes(cb.value);
          cb.checked = defaultOn ? t.layout_config[cb.value] !== false : t.layout_config[cb.value] === true;
        });
        document.getElementById('tplSchoolName').value = t.layout_config.school_name || 'โรงเรียนอนุบาล';
        document.getElementById('fontSchoolname').value = t.layout_config.font_schoolname ?? '2.2';
        document.getElementById('fontSchoolname2').value = t.layout_config.font_schoolname2 ?? '1.8';
        document.getElementById('fontName').value = t.layout_config.font_name ?? '3';
        document.getElementById('tplBackTitle').value = (t.layout_config.back_title && !['ข้อมูลสำคัญ', 'ข้อมูลผู้รับส่ง'].includes(t.layout_config.back_title)) ? t.layout_config.back_title : 'ข้อมูลผู้รับส่ง (ผู้ปกครอง)';
        document.getElementById('fontBackTitle').value = t.layout_config.font_back_title ?? '2.8';
        document.getElementById('fontBackGuardian').value = t.layout_config.font_back_guardian ?? '1.6';
        document.getElementById('sizeBackGuardianPhoto').value = t.layout_config.size_back_guardian_photo ?? '7';
        const fontDefs = {show_nickname:'2.2',show_classroom:'2.2',show_blood:'2.2',show_studentid:'2',show_dob:'1.8',show_congenital:'1.7',show_allergic_food:'1.7',show_allergic_medicine:'1.7',show_expiry:'2.2'};
        document.querySelectorAll('.tpl-field').forEach(cb => {
          const el = document.getElementById('font_' + cb.value);
          if (el) el.value = t.layout_config['font_' + cb.value] ?? fontDefs[cb.value] ?? '2.2';
        });
      }
      const setDefaultBtn = document.getElementById('setDefaultBtn');
      const defaultBadge = document.getElementById('defaultBadge');
      if (setDefaultBtn) setDefaultBtn.disabled = t.is_default;
      if (defaultBadge) defaultBadge.classList.toggle('d-none', !t.is_default);
      updatePreview();
    }).catch(() => {});
}

function updatePreview() {
  if (!document.getElementById('tplCardPreview')) return;
  const headerColor = document.getElementById('tplHeaderColor').value;
  const schoolName = document.getElementById('tplSchoolName').value.trim() || 'โรงเรียนอนุบาล';
  const showNickname = document.getElementById('field_show_nickname').checked;
  const showClassroom = document.getElementById('field_show_classroom').checked;
  const showBlood = document.getElementById('field_show_blood').checked;
  const showStudentid = document.getElementById('field_show_studentid').checked;
  const showDob = document.getElementById('field_show_dob').checked;
  const showExpiry = document.getElementById('field_show_expiry').checked;
  const showBack = document.getElementById('field_show_back').checked;
  const healthFields = [
    ['pvCongenital', 'field_show_congenital', 'font_show_congenital'],
    ['pvAllergicFood', 'field_show_allergic_food', 'font_show_allergic_food'],
    ['pvAllergicMedicine', 'field_show_allergic_medicine', 'font_show_allergic_medicine']
  ];
  document.querySelector('#tplCardPreview .card-subheader').style.background = headerColor;
  const schoolLines = schoolName.split('\n');
  const line1 = document.getElementById('pvSchoolLine1');
  const lineRest = document.getElementById('pvSchoolRest');
  line1.textContent = schoolLines[0] || 'โรงเรียนอนุบาล';
  line1.style.fontSize = document.getElementById('fontSchoolname').value + 'mm';
  line1.style.color = headerColor;
  if (schoolLines.length > 1) {
    lineRest.textContent = schoolLines.slice(1).join('\n');
    lineRest.style.display = '';
    lineRest.style.fontSize = document.getElementById('fontSchoolname2').value + 'mm';
    lineRest.style.color = headerColor;
  } else {
    lineRest.style.display = 'none';
  }
  document.querySelector('.card-name-text').style.fontSize = document.getElementById('fontName').value + 'mm';
  document.getElementById('pvPhotoWrap').style.display = document.getElementById('field_show_photo').checked ? '' : 'none';
  document.getElementById('pvNickname').style.display = showNickname ? '' : 'none';
  document.getElementById('pvNickname').style.fontSize = document.getElementById('font_show_nickname').value + 'mm';
  document.getElementById('pvClassroom').style.display = showClassroom ? '' : 'none';
  document.getElementById('pvClassroom').style.fontSize = document.getElementById('font_show_classroom').value + 'mm';
  document.getElementById('pvBlood').style.display = showBlood ? '' : 'none';
  document.getElementById('pvBlood').style.fontSize = document.getElementById('font_show_blood').value + 'mm';
  document.getElementById('pvDob').style.display = showDob ? '' : 'none';
  document.getElementById('pvDob').style.fontSize = document.getElementById('font_show_dob').value + 'mm';
  document.getElementById('pvStudentid').style.display = showStudentid ? '' : 'none';
  document.getElementById('pvStudentid').style.fontSize = document.getElementById('font_show_studentid').value + 'mm';
  document.getElementById('pvExpiry').style.display = showExpiry ? '' : 'none';
  document.getElementById('pvExpiry').style.fontSize = document.getElementById('font_show_expiry').value + 'mm';
  healthFields.forEach(([previewId, fieldId, fontId]) => {
    const preview = document.getElementById(previewId);
    if (preview) {
      preview.style.display = document.getElementById(fieldId).checked ? '' : 'none';
      preview.style.fontSize = document.getElementById(fontId).value + 'mm';
    }
  });
  const backPreview = document.getElementById('tplBackPreview');
  const backLabel = document.getElementById('tplBackLabel');
  if (backPreview) backPreview.style.display = showBack ? '' : 'none';
  if (backLabel) backLabel.style.display = showBack ? '' : 'none';
  const backTitle = document.getElementById('pvBackTitle');
  if (backTitle) {
    backTitle.textContent = document.getElementById('tplBackTitle').value || 'ข้อมูลผู้รับส่ง (ผู้ปกครอง)';
    backTitle.style.fontSize = document.getElementById('fontBackTitle').value + 'mm';
    backTitle.style.color = headerColor;
  }
  document.querySelectorAll('#tplBackPreview .card-guardian-info').forEach(info => {
    info.style.fontSize = document.getElementById('fontBackGuardian').value + 'mm';
  });
  const guardianPhotoSize = Math.max(4, Math.min(15, parseFloat(document.getElementById('sizeBackGuardianPhoto').value) || 7));
  document.querySelectorAll('#tplBackPreview .card-guardian-photo').forEach(photo => {
    photo.style.width = guardianPhotoSize + 'mm';
    photo.style.height = guardianPhotoSize + 'mm';
    const icon = photo.querySelector('i');
    if (icon) icon.style.fontSize = Math.max(3, guardianPhotoSize * 0.57) + 'mm';
  });
  const backInner = document.querySelector('#tplBackPreview .card-back-inner');
  if (backInner) {
    backInner.style.borderColor = headerColor;
    backInner.style.setProperty('--card-accent', headerColor);
  }
  document.querySelector('.card-name-text').style.color = headerColor;
  document.getElementById('pvStudentid').style.backgroundColor = headerColor;
  var qrEl = document.getElementById('tplPreviewQR');
  if (qrEl && qrPreviewInstance) {
    qrPreviewInstance.clear();
    qrEl.innerHTML = '';
    qrPreviewInstance = new QRCode(qrEl, {
      text: qrEl.dataset.token,
      width: 72, height: 72,
      colorDark: headerColor, colorLight: '#FFFFFF',
      correctLevel: QRCode.CorrectLevel.M
    });
  }
}

function saveTemplate() {
  const id = document.getElementById('tplEditId').value;
  const name = document.getElementById('tplName').value.trim();
  if (!name) { showToast('กรุณากรอกชื่อเทมเพลต','error'); return; }
  const headerColor = document.getElementById('tplHeaderColor').value;
  const schoolName = document.getElementById('tplSchoolName').value.trim() || 'โรงเรียนอนุบาล';
  const layoutConfig = {school_name: schoolName};
  document.querySelectorAll('.tpl-field').forEach(cb => layoutConfig[cb.value] = cb.checked);
  layoutConfig.font_schoolname = parseFloat(document.getElementById('fontSchoolname').value) || 2.2;
  layoutConfig.font_schoolname2 = parseFloat(document.getElementById('fontSchoolname2').value) || 1.8;
  layoutConfig.font_name = parseFloat(document.getElementById('fontName').value) || 3;
  document.querySelectorAll('.tpl-field').forEach(cb => {
    const el = document.getElementById('font_' + cb.value);
    if (el) layoutConfig['font_' + cb.value] = parseFloat(el.value) || 2.2;
  });
  if (!layoutConfig.font_show_studentid) layoutConfig.font_show_studentid = 2;
  layoutConfig.back_title = document.getElementById('tplBackTitle').value.trim() || 'ข้อมูลผู้รับส่ง (ผู้ปกครอง)';
  layoutConfig.font_back_title = parseFloat(document.getElementById('fontBackTitle').value) || 2.8;
  layoutConfig.font_back_guardian = parseFloat(document.getElementById('fontBackGuardian').value) || 1.6;
  layoutConfig.size_back_guardian_photo = Math.max(4, Math.min(15, parseFloat(document.getElementById('sizeBackGuardianPhoto').value) || 7));
  fetch('../../include/function/api_save_template.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id: id || null, name, header_color: headerColor, layout_config: layoutConfig})
  }).then(r=>r.json()).then(d => {
    if (d.success) {
      showToast('บันทึกเทมเพลตเรียบร้อย','success');
      setTimeout(() => location.href = '?main_tab=template&edit_id=' + (d.id || id), 500);
    } else showToast(d.message||'เกิดข้อผิดพลาด','error');
  }).catch(() => showToast('เกิดข้อผิดพลาด','error'));
}

function setDefaultTemplate(id) {
  fetch('../../include/function/api_set_default_template.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({template_id: id})
  }).then(r=>r.json()).then(d => {
    if (d.success) { showToast('ตั้งค่าเริ่มต้นเรียบร้อย','success'); setTimeout(()=>location.reload(),500); }
    else showToast(d.message||'เกิดข้อผิดพลาด','error');
  }).catch(()=>showToast('เกิดข้อผิดพลาด','error'));
}

function createNewTemplate() {
  document.getElementById('tplEditId').value = '';
  document.getElementById('tplName').value = '';
  document.getElementById('tplSchoolName').value = 'โรงเรียนอนุบาล';
  document.getElementById('tplHeaderColor').value = '#1E3A8A';
  document.getElementById('fontSchoolname').value = '2.2';
  document.getElementById('fontSchoolname2').value = '1.8';
  document.getElementById('fontName').value = '3';
  document.getElementById('tplBackTitle').value = 'ข้อมูลผู้รับส่ง (ผู้ปกครอง)';
  document.getElementById('fontBackTitle').value = '2.8';
  document.getElementById('fontBackGuardian').value = '1.6';
  document.getElementById('sizeBackGuardianPhoto').value = '7';
  const fontDefs = {show_nickname:'2.2',show_classroom:'2.2',show_blood:'2.2',show_studentid:'2',show_dob:'1.8',show_congenital:'1.7',show_allergic_food:'1.7',show_allergic_medicine:'1.7',show_expiry:'2.2'};
  document.querySelectorAll('.tpl-field').forEach(cb => {
    const el = document.getElementById('font_' + cb.value);
    if (el) el.value = fontDefs[cb.value] ?? '2.2';
  });
  document.querySelectorAll('.color-swatch').forEach(s => s.classList.toggle('active', s.dataset.color === '#1E3A8A'));
  document.querySelectorAll('.tpl-field').forEach(cb => cb.checked = ['show_nickname','show_classroom','show_studentid','show_dob','show_congenital','show_allergic_food','show_allergic_medicine','show_expiry','show_photo','show_back'].includes(cb.value));
  const setDefaultBtn = document.getElementById('setDefaultBtn');
  const defaultBadge = document.getElementById('defaultBadge');
  if (setDefaultBtn) setDefaultBtn.disabled = false;
  if (defaultBadge) defaultBadge.classList.add('d-none');
  currentTemplateId = null;
  updatePreview();
}

function prevTemplate() {
  const idx = templateIds.indexOf(currentTemplateId);
  if (idx > 0) selectTemplate(templateIds[idx - 1]);
}

function nextTemplate() {
  const idx = templateIds.indexOf(currentTemplateId);
  if (idx < templateIds.length - 1) selectTemplate(templateIds[idx + 1]);
}

document.addEventListener('DOMContentLoaded', function() {
  const qrEl = document.getElementById('tplPreviewQR');
  if (qrEl) {
    qrPreviewInstance = new QRCode(qrEl, {
      text: '<?= json_encode(['student_id' => $sampleChild['studentid'] ?? 'PREVIEW'], JSON_UNESCAPED_UNICODE) ?>',
      width: 72, height: 72,
      colorDark: '<?= $curColor ?? '#1E3A8A' ?>', colorLight: '#FFFFFF',
      correctLevel: QRCode.CorrectLevel.M
    });
  }
  const urlParams = new URLSearchParams(window.location.search);
  const editId = urlParams.get('edit_id');
  if (editId) selectTemplate(parseInt(editId));
});
</script>
