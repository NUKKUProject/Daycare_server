<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once('../../../config/database.php');

$pdo = getDatabaseConnection();
$stmt = $pdo->query("SELECT * FROM login_issues ORDER BY (status = 'open') DESC, created_at DESC");
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
    <div class="container-fluid">
        <h1 class="mt-4">รายการแจ้งปัญหาการเข้าสู่ระบบ</h1>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                เรื่องที่ผู้ใช้แจ้งเข้ามา
            </div>
            <div class="card-body table-responsive">
                <table id="datatablesSimple" class="table table-bordered table-striped">
                    <thead>
                        <tr class="table-primary">
                            <th>ลำดับ</th>
                            <th>วันที่แจ้ง</th>
                            <th>รหัสนักเรียน</th>
                            <th>ชื่อ-สกุลผู้เรียน</th>
                            <th>รายละเอียด</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1;
                        foreach ($issues as $issue): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td style="white-space: nowrap;"><?= htmlspecialchars($issue['created_at']) ?></td>
                                <td><?= htmlspecialchars($issue['student_id'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($issue['student_name']) ?></td>
                                <td style="white-space: pre-wrap; min-width: 250px;"><?= htmlspecialchars($issue['description']) ?></td>
                                <td>
                                    <?php if ($issue['status'] === 'open'): ?>
                                        <span class="badge bg-warning text-dark">รอดำเนินการ</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">แก้ไขแล้ว</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($issue['status'] === 'open'): ?>
                                        <button class="btn btn-success btn-sm" onclick="resolveIssue(<?= (int)$issue['id'] ?>)">ปิดเคส</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        $('#datatablesSimple').DataTable({
            order: []
        });
    });

    function resolveIssue(id) {
        Swal.fire({
            title: 'ปิดเคสนี้?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ปิดเคส',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch('../../include/process/resolve_login_issue.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: data.message || 'เกิดข้อผิดพลาด'
                        });
                    }
                })
                .catch(() => Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด'
                }));
        });
    }
</script>
