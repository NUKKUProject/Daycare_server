<?php
include __DIR__ . '/../../include/auth/auth.php';
checkUserRole(['admin']);
include __DIR__ . '/../partials/Header.php';
include __DIR__ . '/../../include/auth/auth_navbar.php';
include __DIR__ . '/../../include/auth/auth_dashboard.php';
require_once('../../../config/database.php');

$pdo = getDatabaseConnection();

?>

<main class="main-content">
    <div class="container-fluid">
        <h1 class="mt-4">จัดการบัญชีรออนุมัติ</h1>
        <!-- ส่วนตารางข้อมูล -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                รายการบัญชีที่รอการอนุมัติ
            </div>
            <div class="card-body table-responsive">

                <table id="datatablesSimple" class="table table-bordered table-striped">
                    <thead>
                        <tr class="table-primary">
                            <th style="width: 100px;">ลำดับ</th>
                            <th style="white-space: nowrap; width: 350px;">ชื่อ-นามสกุล</th>
                            <th style="width: 350px;">Username</th>
                            <th style="width: 150px;">บทบาท</th>
                            <th style="width: 150px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT id, username, role ,name FROM users WHERE role = 'wait'");
                        $stmt->execute();
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $counter = 1;
                        foreach ($users as $user):
                        ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td style="white-space: nowrap;"><?php echo htmlspecialchars($user['name']) ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td><button class="btn btn-warning" onclick="btnEdit(<?php echo $user['id'] ?>, '<?php echo $user['username'] ?>','<?php echo $user['name'] ?>')">edit</button></td>
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
            columnDefs: [{
                targets: -1,
                className: 'dt-body-center'
            }]
        });
    });

    function btnEdit(id, username, name) {
        Swal.fire({
            title: 'เลือกบทบาท',
            html: `    
                <p>Username: <strong class="text-primary">${username}</strong></p>
                <p>ชื่อ-สกุล: <strong class="text-primary">${name}</strong></p>
               <select id="roleSelect" class="form-select">
                   <option value="" disabled selected>Select a role</option>
                   <option value="admin">Admin</option>
                   <option value="teacher">คุณครู</option>
                   <option value="doctor">แพทย์</option>
               </select>
              `,

            inputPlaceholder: 'Select a role',
            showCancelButton: true,
            preConfirm: () => {
                const role = document.getElementById('roleSelect').value;
                if (!role) {
                    Swal.showValidationMessage('กรุณาเลือกบทบาท');
                }
                return role;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const selectedRole = result.value;
                // ส่งข้อมูลไปยังเซิร์ฟเวอร์
                fetch('./process/appove_account.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id,
                            role: selectedRole
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'User role updated successfully!',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                            setTimeout(() => location.reload(), 2100);
                        } else {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: data.message || 'An error occurred.',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'An error occurred while updating the user role.',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    });
            }
        });
    }
</script>