<?php

class AuthController {
    public function loginPage() {
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function processLogin() {
        // ตรวจสอบ username และ password (หรือ token จาก SSO)
        if ($_POST['username'] === 'admin' && $_POST['password'] === '1234') {
            $_SESSION['role'] = 'admin';
            header('Location: /dashboard/admin');
        } else {
            echo "Invalid credentials!";
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /');
    }
}
