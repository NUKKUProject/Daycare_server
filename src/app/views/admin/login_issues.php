<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once('../../../config/database.php');

$pdo = getDatabaseConnection();
$stmt = $pdo->query("
    SELECT li.*,
           (SELECT COUNT(*) FROM login_issue_messages WHERE issue_id = li.id) AS msg_count,
           (SELECT COUNT(*) FROM login_issue_messages WHERE issue_id = li.id AND sender_role = 'parent') AS parent_msg_count
    FROM login_issues li
    ORDER BY (li.status = 'pending') DESC, li.created_at DESC
");
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <h4 class="mb-0 fw-bold"><i class="bi bi-headset me-2 text-primary"></i>รายการแจ้งปัญหาการเข้าสู่ระบบ</h4>
        <span class="badge bg-warning text-dark fs-6">
            <?= count(array_filter($issues, fn($r) => $r['status'] === 'pending')) ?> รอดำเนินการ
        </span>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="issuesTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>วันที่แจ้ง</th>
                            <th>รหัสนักเรียน</th>
                            <th>เลขบัตรประชาชน</th>
                            <th>ชื่อ-สกุล</th>
                            <th>รายละเอียด</th>
                            <th class="text-center">สนทนา</th>
                            <th class="text-center">สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n = 1; foreach ($issues as $issue): ?>
                        <tr class="<?= $issue['status'] === 'pending' ? 'table-warning bg-opacity-25' : '' ?>">
                            <td class="ps-3 text-muted"><?= $n++ ?></td>
                            <td style="white-space:nowrap;font-size:0.85rem;"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($issue['created_at']))) ?></td>
                            <td><?= htmlspecialchars($issue['student_id'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($issue['national_id'] ?? '-') ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($issue['student_name']) ?></td>
                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.88rem;color:#475467;">
                                <?= htmlspecialchars($issue['description']) ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary position-relative"
                                        onclick="openChat(<?= (int)$issue['id'] ?>, '<?= htmlspecialchars(addslashes($issue['student_name'])) ?>', '<?= htmlspecialchars($issue['status']) ?>', '<?= htmlspecialchars($issue['student_id'] ?? '') ?>', '<?= htmlspecialchars($issue['national_id'] ?? '') ?>')">
                                    <i class="bi bi-chat-dots"></i> สนทนา
                                    <?php if ($issue['parent_msg_count'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">
                                        <?= (int)$issue['parent_msg_count'] ?>
                                    </span>
                                    <?php endif; ?>
                                </button>
                            </td>
                            <td class="text-center">
                                <?php if ($issue['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">รอดำเนินการ</span>
                                <?php else: ?>
                                    <span class="badge bg-success">แก้ไขแล้ว</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($issue['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success" onclick="resolveIssue(<?= (int)$issue['id'] ?>)">
                                    <i class="bi bi-check-lg"></i> ปิดเคส
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</main>

<!-- Chat Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas" style="width:420px;">
    <div class="offcanvas-header text-white py-3" id="chatOffcanvasHeader" style="background:linear-gradient(135deg,#1E4F6F,#26648E);">
        <div class="flex-grow-1">
            <div class="fw-bold fs-6" id="chatName">-</div>
            <div class="mt-1 d-flex gap-2 align-items-center" style="font-size:0.8rem;">
                <span id="chatStudentId" style="opacity:0.85;"></span>
                <span id="chatNationalId" style="opacity:0.85;"></span>
                <span id="chatStatusBadge"></span>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button id="resolveBtn" class="btn btn-sm btn-light" onclick="resolveIssue(window._chatIssueId)">
                <i class="bi bi-check-lg me-1"></i>ปิดเคส
            </button>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
    </div>

    <div id="chatMessages" class="flex-grow-1 overflow-y-auto p-3 d-flex flex-direction-column gap-3"
         style="height:calc(100vh - 200px);display:flex;flex-direction:column;gap:0.6rem;background:#f8fafc;">
        <div class="text-center text-muted small mt-auto">กำลังโหลด...</div>
    </div>

    <div id="chatInputArea" class="border-top p-3 bg-white">
        <div class="d-flex gap-2 align-items-end">
            <textarea id="adminChatInput" class="form-control form-control-sm" rows="2"
                      placeholder="พิมพ์ข้อความตอบกลับ..." maxlength="1000"
                      style="resize:none;border-radius:10px;"></textarea>
            <button id="adminSendBtn" class="btn btn-primary px-3" onclick="adminSendMessage()" style="border-radius:10px;white-space:nowrap;">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
        <div class="text-muted mt-1" style="font-size:0.72rem;"><i class="bi bi-info-circle me-1"></i>Enter ส่ง / Shift+Enter ขึ้นบรรทัดใหม่</div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#issuesTable').DataTable({
        order: [],
        language: {
            search: 'ค้นหา:',
            lengthMenu: 'แสดง _MENU_ รายการ',
            info: 'แสดง _START_-_END_ จาก _TOTAL_ รายการ',
            infoEmpty: 'ไม่มีรายการ',
            zeroRecords: 'ไม่พบข้อมูล',
            paginate: { first: 'หน้าแรก', last: 'หน้าสุดท้าย', next: 'ถัดไป', previous: 'ก่อนหน้า' }
        }
    });
});

window._chatIssueId = null;
const chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));

function openChat(id, name, status, studentId, nationalId) {
    window._chatIssueId = id;
    document.getElementById('chatName').textContent = name;
    document.getElementById('chatStudentId').textContent = studentId ? 'รหัส: ' + studentId : '';
    document.getElementById('chatNationalId').textContent = nationalId ? '| บัตร: ' + nationalId : '';
    document.getElementById('chatStatusBadge').innerHTML = status === 'pending'
        ? '<span class="badge bg-warning text-dark">รอดำเนินการ</span>'
        : '<span class="badge bg-success">แก้ไขแล้ว</span>';

    const resolveBtn = document.getElementById('resolveBtn');
    const inputArea  = document.getElementById('chatInputArea');
    if (status === 'resolved') {
        resolveBtn.style.display = 'none';
        inputArea.style.display  = 'none';
    } else {
        resolveBtn.style.display = '';
        inputArea.style.display  = '';
    }

    document.getElementById('chatMessages').innerHTML = '<div class="text-center text-muted small" style="margin:auto;">กำลังโหลด...</div>';
    chatOffcanvas.show();
    loadAdminMessages(id);

    document.getElementById('adminChatInput').addEventListener('keydown', function handler(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); adminSendMessage(); }
    });
}

async function loadAdminMessages(issueId) {
    const area = document.getElementById('chatMessages');
    const res = await fetch('../../include/process/get_issue_messages_admin.php', {
        method: 'POST',
        body: (() => { const d = new FormData(); d.append('issue_id', issueId); return d; })()
    }).then(r => r.json()).catch(() => null);

    if (!res || !res.success) {
        area.innerHTML = '<div class="text-center text-danger small" style="margin:auto;">โหลดไม่สำเร็จ</div>';
        return;
    }
    if (!res.data || res.data.length === 0) {
        area.innerHTML = '<div class="text-center text-muted small" style="margin:auto;">ยังไม่มีข้อความ</div>';
        return;
    }
    area.innerHTML = res.data.map(renderAdminBubble).join('');
    area.scrollTop = area.scrollHeight;
}

function renderAdminBubble(m) {
    const isAdmin = m.sender_role === 'admin';
    return `<div style="display:flex;flex-direction:column;align-items:${isAdmin ? 'flex-end' : 'flex-start'};margin-bottom:0.4rem;">
        <div style="font-size:0.7rem;color:${isAdmin ? '#1E4F6F' : '#a16207'};margin-bottom:2px;">${isAdmin ? '<i class="bi bi-shield-check me-1"></i>ผู้ดูแลระบบ' : '<i class="bi bi-person me-1"></i>ผู้ปกครอง'}</div>
        <div style="background:${isAdmin ? '#dbeafe' : '#fef9c3'};border-radius:${isAdmin ? '12px 12px 2px 12px' : '12px 12px 12px 2px'};padding:0.5rem 0.75rem;max-width:85%;font-size:0.87rem;color:#1f2d3a;white-space:pre-wrap;word-break:break-word;">${escHtml(m.message)}</div>
        <div style="font-size:0.7rem;color:#98a2b3;margin-top:2px;">${m.created_at}</div>
    </div>`;
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function adminSendMessage() {
    const input = document.getElementById('adminChatInput');
    const text  = input.value.trim();
    if (!text || !window._chatIssueId) return;
    const btn = document.getElementById('adminSendBtn');
    btn.disabled = true;

    const data = new FormData();
    data.append('issue_id', window._chatIssueId);
    data.append('message', text);
    const res = await fetch('../../include/process/admin_send_issue_message.php', { method:'POST', body:data })
        .then(r => r.json()).catch(() => null);

    btn.disabled = false;
    if (!res || !res.success) {
        Swal.fire({ icon:'error', title: res?.message || 'เกิดข้อผิดพลาด', timer:2000, showConfirmButton:false });
        return;
    }
    input.value = '';
    const area = document.getElementById('chatMessages');
    const emptyEl = area.querySelector('[style*="ยังไม่มีข้อความ"]');
    if (emptyEl) emptyEl.remove();
    area.insertAdjacentHTML('beforeend', renderAdminBubble({ sender_role:'admin', message:text, created_at:'เมื่อกี้' }));
    area.scrollTop = area.scrollHeight;
    input.focus();
}

function resolveIssue(id) {
    Swal.fire({
        title: 'ปิดเคสนี้?',
        text: 'ผู้ปกครองจะไม่สามารถส่งข้อความเพิ่มได้อีก',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-lg me-1"></i>ปิดเคส',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#198754'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('../../include/process/resolve_login_issue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        }).then(r => r.json()).then(data => {
            if (data.status === 'success') location.reload();
            else Swal.fire({ icon:'error', title: data.message || 'เกิดข้อผิดพลาด' });
        }).catch(() => Swal.fire({ icon:'error', title:'เกิดข้อผิดพลาด' }));
    });
}
</script>
