<?php
function verifySSO($token) {
    $api_url = "https://university-sso.com/verify"; // ตัวอย่าง URL API
    $ch = curl_init($api_url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token' => $token]));
    
    $response = curl_exec($ch);
    
    return json_decode($response, true);
}

// ตัวอย่างการใช้งาน
$token = $_POST['token']; // รับ token จากหน้า login
$user_data = verifySSO($token);

if ($user_data && isset($user_data['username'])) {
    echo "เข้าสู่ระบบสำเร็จ: " . $user_data['username'];
} else {
    echo "SSO Login Failed";
}
?>
