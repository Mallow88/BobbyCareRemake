<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>🛡️ Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');

        body {
            margin: 0;
            background-color: #000;
            color: #d8d8d8ff;
            font-family: 'Share Tech Mono', monospace;
            height: 100vh;
            overflow-x: hidden;
        }

        .matrix-bg::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('https://media.giphy.com/media/qN9Ues0Y8F1aU/giphy.gif') repeat;
            background-size: cover;
            opacity: 0.03;
            z-index: -1;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 80px auto;
            padding: 40px;
            border: 2px solid #c9c9c9ff;
            border-radius: 20px;
            background: rgba(0, 0, 0, 0.9);
            box-shadow: 0 0 20px #e7e7e7ff;
        }

        h1 {
            text-align: center;
            color: #e2e2e2ff;
            text-shadow: 0 0 5px #d1d1d1ff, 0 0 10px #00ff88;
        }

        .btn-hacker {
            display: block;
            width: 100%;
            background-color: black;
            color: #cececeff;
            border: 1px solid #d8d8d8ff;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: 0.2s ease-in-out;
            text-align: center;
            text-decoration: none;
        }

        .btn-hacker:hover {
            background-color: #c4c4c4ff;
            color: #000;
            box-shadow: 0 0 12px #00ff88;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            color: #e2e2e2ff;
            font-size: 0.9rem;
            opacity: 0.6;
        }
    </style>
</head>
<body>

<div class="matrix-bg"></div>

<div class="dashboard-container">
    <h1> ยินดีต้อนรับ, แอดมิน <?php echo htmlspecialchars($_SESSION['admin_name']); ?> </h1>
    <hr style="border-color: #d4d4d4ff;">

    <a href="manage_users.php" class="btn-hacker">👥 จัดการผู้ใช้</a>
    <a href="logs.php" class="btn-hacker">📜 ดูประวัติการใช้งาน</a>
    <a href="logout.php" class="btn-hacker">🚪 ออกจากระบบ</a>

    <div class="footer">
        <p>© <?= date("Y") ?> Secure Terminal Admin | Version 1.0</p>
    </div>
</div>

</body>
</html>
