<?php
session_start();

// ถ้า login แล้ว ให้ redirect ไปหน้า dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BobbyCareDev</title>
    <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">

    <!-- Bootstrap 5.3.7 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Fira+Code:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            --secondary-gradient: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            --accent-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            --success-gradient: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            --tech-blue: #00d4ff;
            --tech-purple: #8b5cf6;
            --tech-green: #00ff88;
            --tech-orange: #ff6b35;
            --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 20px 60px rgba(0, 0, 0, 0.15);
            --glow-blue: 0 0 20px rgba(0, 212, 255, 0.3);
            --glow-purple: 0 0 20px rgba(139, 92, 246, 0.3);
        }

        * {
            font-family: 'Kanit', sans-serif;
        }

        .code-font {
            font-family: 'Fira Code', monospace;
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(0, 255, 136, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Matrix rain effect */
        .matrix-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            opacity: 0.1;
        }

        .matrix-column {
            position: absolute;
            top: -100%;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            color: var(--tech-green);
            animation: matrix-fall linear infinite;
            white-space: nowrap;
        }

        @keyframes matrix-fall {
            to {
                transform: translateY(100vh);
            }
        }

        .main-container {
            position: relative;
            z-index: 1;
        }

        .welcome-card {
            background: rgba(15, 15, 35, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 24px;
            box-shadow: var(--shadow-soft), var(--glow-blue);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            position: relative;
            color: white;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--secondary-gradient);
        }

        .welcome-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover), var(--glow-blue);
            border-color: rgba(0, 212, 255, 0.4);
        }

        .logo-container {
            width: 120px;
            height: 120px;
            background: var(--secondary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: var(--glow-blue);
            position: relative;
            overflow: hidden;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }

        .logo-icon {
            font-size: 3rem;
            color: white;
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-align: center;
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.5);
        }

        .welcome-subtitle {
            color: #a0a9c0;
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 3rem;
            text-align: center;
            line-height: 1.6;
        }

        .tech-badge {
            display: inline-block;
            background: rgba(0, 212, 255, 0.1);
            color: var(--tech-blue);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin: 0.25rem;
            border: 1px solid rgba(0, 212, 255, 0.3);
            font-family: 'Fira Code', monospace;
        }

        .login-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 300px;
            margin: 0 auto;
        }

        .btn-line {
            background: #00C300;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 16px;
            font-weight: 500;
            font-size: 1.1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-line::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-line:hover::before {
            left: 100%;
        }

        .btn-line:hover {
            background: linear-gradient(135deg, #00cc6a 0%, #00ff88 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 136, 0.4);
            color: white;
        }

        .btn-admin {
            background: var(--accent-gradient);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 16px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-admin:hover::before {
            left: 100%;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 107, 107, 0.4);
            color: white;
        }

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.2);
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
            border-radius: 20px;
            transform: rotate(45deg);
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
            border-radius: 50%;
            background: rgba(139, 92, 246, 0.1);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .shape:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
            border-radius: 8px;
            background: rgba(0, 255, 136, 0.1);
            border-color: rgba(0, 255, 136, 0.2);
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .feature-icons {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 212, 255, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--tech-blue);
            font-size: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-icon:hover {
            background: var(--secondary-gradient);
            color: white;
            transform: translateY(-4px);
            box-shadow: var(--glow-blue);
        }

        .feature-icon:nth-child(2) {
            background: rgba(139, 92, 246, 0.1);
            border-color: rgba(139, 92, 246, 0.2);
            color: var(--tech-purple);
        }

        .feature-icon:nth-child(2):hover {
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
            box-shadow: var(--glow-purple);
        }

        .feature-icon:nth-child(3) {
            background: rgba(0, 255, 136, 0.1);
            border-color: rgba(0, 255, 136, 0.2);
            color: var(--tech-green);
        }

        .feature-icon:nth-child(3):hover {
            background: var(--success-gradient);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .feature-icon:nth-child(4) {
            background: rgba(255, 107, 53, 0.1);
            border-color: rgba(255, 107, 53, 0.2);
            color: var(--tech-orange);
        }

        .feature-icon:nth-child(4):hover {
            background: var(--accent-gradient);
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
        }

        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2rem;
            }

            .welcome-card {
                margin: 1rem;
                border-radius: 20px;
            }

            .feature-icons {
                gap: 1rem;
            }

            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }

            .tech-badge {
                font-size: 0.75rem;
                padding: 0.2rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="matrix-bg" id="matrixBg"></div>

    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container-fluid main-container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="welcome-card p-5">
                    <div class="logo-container">

                        <img src="img/logo/bobby-icon.png" alt="Logo" width="210" height="118">


                    </div>

                    <h1 class="welcome-title">
                        ยินดีต้อนรับสู่ระบบ <span class="code-font">BobbyCare</span>
                    </h1>

                    <div class="welcome-subtitle text-center">
                        <p>ระบบดูแลเเละบริการ serviceI</p>
                        <div class="mt-3">
                            <span class="tech-badge">AI-Powered</span>
                            <span class="tech-badge">Cloud-Based</span>
                            <span class="tech-badge">service</span>
                            <span class="tech-badge">Secure</span>
                        </div>
                    </div>


                    <div class="login-buttons">
                        <a href="line/login.php" class="btn-line">
                            <i class="fab fa-line"></i>
                            เข้าสู่ระบบด้วย LINE
                        </a>

                        <a href="track_documents.php" class="btn-admin">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span class="code-font">ค้นหาเอกสาร</span>
                        </a>

                        <a href="admin/login.php" class="btn-admin">
                            <i class="fas fa-user-shield"></i>
                            <span class="code-font">Admin Panel</span>
                        </a>
                    </div>

                    <div class="feature-icons">
                        <div class="feature-icon" title="AI Technology">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="feature-icon" title="Cloud Computing">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <div class="feature-icon" title="IoT Integration">
                            <i class="fas fa-wifi"></i>
                        </div>
                        <div class="feature-icon" title="Data Analytics">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3.7 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Matrix rain effect
        function createMatrixRain() {
            const matrixBg = document.getElementById('matrixBg');
            const characters = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';

            for (let i = 0; i < 15; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = Math.random() * 100 + '%';
                column.style.animationDuration = (Math.random() * 3 + 2) + 's';
                column.style.animationDelay = Math.random() * 2 + 's';

                let text = '';
                for (let j = 0; j < 20; j++) {
                    text += characters.charAt(Math.floor(Math.random() * characters.length)) + '<br>';
                }
                column.innerHTML = text;

                matrixBg.appendChild(column);
            }
        }

        // เพิ่มเอฟเฟกต์ smooth scroll และ interaction
        document.addEventListener('DOMContentLoaded', function() {
            // สร้าง Matrix rain effect
            createMatrixRain();

            // เพิ่มเอฟเฟกต์ hover สำหรับ feature icons
            const featureIcons = document.querySelectorAll('.feature-icon');
            featureIcons.forEach(icon => {
                icon.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.1)';
                });

                icon.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // เพิ่มเอฟเฟกต์ loading สำหรับปุ่ม
            const buttons = document.querySelectorAll('.btn-line, .btn-admin');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // เพิ่ม ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');

                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // เพิ่มเอฟเฟกต์ typing สำหรับ tech badges
            const techBadges = document.querySelectorAll('.tech-badge');
            techBadges.forEach((badge, index) => {
                badge.style.opacity = '0';
                badge.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    badge.style.transition = 'all 0.5s ease';
                    badge.style.opacity = '1';
                    badge.style.transform = 'translateY(0)';
                }, index * 200 + 1000);
            });
        });
    </script>

    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</body>

</html>