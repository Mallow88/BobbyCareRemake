<?php
session_start();

// ถ้า login แล้ว ให้ redirect ไปหน้า dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to BobbyCare</title>
</head>
<body>
    <h1>ยินดีต้อนรับสู่ระบบ BobbyCare</h1>
    <p>กรุณาเข้าสู่ระบบด้วยบัญชี LINE ของคุณ</p>
     <a href="admin/register.php">เข้าadmin</a>

    <a href="line/login.php">
        <img src="https://scdn.line-apps.com/n/line_login/btn/en.png" alt="Login with LINE">
    </a>
    
</body>
</html>
