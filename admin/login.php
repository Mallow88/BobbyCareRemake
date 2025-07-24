<?php 
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log->execute([$admin['id'], 'Login']);

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>🛡️ Admin Login | Hacker Mode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');

        body {
            background-color: #000;
            font-family: 'Share Tech Mono', monospace;
            color: #0f0;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .matrix-bg::after {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('https://media.giphy.com/media/qN9Ues0Y8F1aU/giphy.gif') repeat;
            background-size: cover;
            opacity: 0.04;
            z-index: -1;
        }

        .login-card {
            background-color: rgba(0, 0, 0, 0.9);
            border: 2px solid #0f0;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 0 20px #0f0;
        }

        .login-card h2 {
            text-shadow: 0 0 8px #0f0;
        }

        .form-control {
            background-color: black;
            color: #0f0;
            border: 1px solid #0f0;
            transition: 0.2s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 10px #0f0;
            border-color: #0f0;
        }

        .btn-hacker {
            background-color: black;
            color: #0f0;
            border: 1px solid #0f0;
            transition: 0.2s ease-in-out;
        }

        .btn-hacker:hover {
            background-color: #0f0;
            color: black;
            box-shadow: 0 0 15px #0f0;
        }

        .alert-danger {
            background-color: rgba(255, 0, 0, 0.1);
            border-color: #f00;
            color: #f00;
        }

        .footer {
            margin-top: 20px;
            font-size: 0.8rem;
            opacity: 0.6;
        }
    </style>
</head>
<body>

<div class="matrix-bg"></div>

<div class="login-card text-center">
    <h2 class="mb-4"> <span class="hacker-glow">ADMIN ACCESS</span></h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger py-2">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3 text-start">
            <label for="username" class="form-label">USERNAME</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>

        <div class="mb-4 text-start">
            <label for="password" class="form-label">PASSWORD</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-hacker w-100">🔓 LOGIN</button>
    </form>

    <div class="footer mt-4 text-muted">
        © <?= date('Y') ?> Secure Terminal Interface
    </div>
</div>

</body>
</html>
