<?php
require_once('auth.php');

header('Content-Type: application/json');

// ตรวจสอบว่าเป็น AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // ฟังก์ชัน isLoggedIn จะเรียก checkSessionTimeout ให้โดยอัตโนมัติ
    if (!isLoggedIn()) {
        echo json_encode([
            'status' => 'session_timeout',
            'message' => 'Your session has expired. Please login again.'
        ]);
        exit;
    }
    
    echo json_encode([
        'status' => 'active',
        'message' => 'Session is active'
    ]);
} 