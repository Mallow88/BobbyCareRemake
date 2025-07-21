<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h2>สวัสดี, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <p>คุณเข้าสู่ระบบด้วยบัญชี LINE เรียบร้อยแล้ว</p>
    <a href="logout.php">ออกจากระบบ</a>
</body>
</html>
