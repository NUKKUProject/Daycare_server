<?php
/**
 * Edit Child Page
 * Provides a form to edit an existing child's information.
 */

include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin', 'teacher']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
require_once __DIR__ . '/../../include/function/pages_referen.php';
require_once __DIR__ . '/../../include/function/child_functions.php';

$studentid = $_GET['studentid'] ?? null;
if (!$studentid) {
    echo "<div class='alert alert-danger'>รหัสนักเรียนไม่ถูกต้อง</div>";
    exit;
}

$child = getChildById($studentid);
if (!$child) {
    echo "<div class='alert alert-warning'>ไม่พบข้อมูลเด็ก</div>";
    exit;
}
?>

<main class="container my-4">
    <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลเด็ก</h2>
    <form action="../../include/process/edit_child.php" method="POST" enctype="multipart/form-data"
          id="editChildForm" class="needs-validation" novalidate>
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($child['studentid']) ?>">
        <!-- Add form fields here (similar to add child form) -->
        <div class="mb-3">
            <label class="form-label">ชื่อ-สกุล (ภาษาไทย)</label>
            <div class="row g-2">
                <div class="col-md-2"><input type="text" class="form-control" name="prefix_th" value="<?= htmlspecialchars($child['prefix_th']) ?>" required></div>
                <div class="col-md-5"><input type="text" class="form-control" name="firstname_th" value="<?= htmlspecialchars($child['firstname_th']) ?>" required></div>
                <div class="col-md-5"><input type="text" class="form-control" name="lastname_th" value="<?= htmlspecialchars($child['lastname_th']) ?>" required></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>บันทึก</button>
    </form>
</main>

<?php include __DIR__ . '/../partials/Footer.php'; ?>