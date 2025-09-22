// ตรวจสอบ Session Timeout ทุกๆ 1 นาที
const checkSessionTimeout = () => {
    fetch('/app/include/auth/check_session.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'session_timeout') {
            Swal.fire({
                title: 'Session หมดอายุ',
                text: 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง',
                icon: 'warning',
                confirmButtonText: 'เข้าสู่ระบบ'
            }).then((result) => {
                window.location.href = '/app/views/login.php';
            });
        }
    })
    .catch(error => {
        console.error('Error checking session:', error);
    });
};

// เริ่มตรวจสอบทุก 1 นาที
setInterval(checkSessionTimeout, 60000);

// ตรวจสอบ AJAX response สำหรับ session timeout
$(document).ajaxComplete(function(event, xhr, settings) {
    try {
        const response = JSON.parse(xhr.responseText);
        if (response.status === 'session_timeout') {
            Swal.fire({
                title: 'Session หมดอายุ',
                text: 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง',
                icon: 'warning',
                confirmButtonText: 'เข้าสู่ระบบ'
            }).then((result) => {
                window.location.href = '/app/views/login.php';
            });
        }
    } catch (e) {
        // ไม่ใช่ JSON response
    }
}); 