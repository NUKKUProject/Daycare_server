<?php 
// โยนหน้าเว็บไปยังไฟล์ index.php
// header("Location: ./app/views/login.php");
// exit();



?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กำลังปรับปรุงเว็บไซต์ - Under Construction</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            color: white;
            padding: 2rem;
            max-width: 600px;
            position: relative;
            z-index: 2;
        }

        .construction-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        .title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .progress-container {
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
            padding: 8px;
            margin: 2rem 0;
            backdrop-filter: blur(10px);
        }

        .progress-bar {
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
            height: 20px;
            border-radius: 20px;
            width: 0%;
            animation: loading 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
            animation: shimmer 2s infinite;
        }

        .countdown {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .countdown-title {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .time-unit {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 1rem;
            min-width: 80px;
            backdrop-filter: blur(5px);
        }

        .time-number {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }

        .time-label {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }

        .contact-info {
            margin-top: 2rem;
            font-size: 1rem;
            opacity: 0.9;
        }

        .contact-info a {
            color: #ffd700;
            text-decoration: none;
            font-weight: 600;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) { width: 80px; height: 80px; top: 20%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 60px; height: 60px; top: 60%; left: 80%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 100px; height: 100px; top: 80%; left: 20%; animation-delay: 4s; }
        .shape:nth-child(4) { width: 40px; height: 40px; top: 30%; left: 70%; animation-delay: 1s; }
        .shape:nth-child(5) { width: 120px; height: 120px; top: 10%; left: 60%; animation-delay: 3s; }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes loading {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        @media (max-width: 768px) {
            .title { font-size: 2rem; }
            .subtitle { font-size: 1rem; }
            .construction-icon { font-size: 3rem; }
            .countdown-timer { gap: 0.5rem; }
            .time-unit { min-width: 60px; padding: 0.75rem; }
            .time-number { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="construction-icon">🚧</div>
        
        <h1 class="title">กำลังปรับปรุงเว็บไซต์</h1>
        
        <p class="subtitle">
            เรากำลังทำการปรับปรุงเว็บไซต์เพื่อให้คุณได้รับประสบการณ์ที่ดีขึ้น<br>
            ขออภัยในความไม่สะดวก เราจะกลับมาเร็วๆ นี้!
        </p>

        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>


    </div>

    <script>
        // Countdown Timer Script (Optional - for dynamic countdown)
        function updateCountdown() {
            // Set target date (5 days from now for example)
            const targetDate = new Date();
            targetDate.setDate(targetDate.getDate() + 5);
            targetDate.setHours(targetDate.getHours() + 12);
            targetDate.setMinutes(targetDate.getMinutes() + 30);
            
            const now = new Date().getTime();
            const distance = targetDate.getTime() - now;
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("days").innerText = days.toString().padStart(2, '0');
            document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
            document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
            document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');
            
            if (distance < 0) {
                document.querySelector(".countdown").innerHTML = "<h2>เว็บไซต์พร้อมใช้งานแล้ว!</h2>";
            }
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call
    </script>
</body>
</html>