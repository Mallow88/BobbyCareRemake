<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admins (username, password, name) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $name]);

    header("Location: login.php");
    exit();
}
?>

<form method="POST">
    ชื่อผู้ใช้: <input type="text" name="username" required><br>
    ชื่อจริง: <input type="text" name="name" required><br>
    รหัสผ่าน: <input type="password" name="password" required><br>
    <button type="submit">สมัครแอดมิน</button>
</form>

 <a href="login.php">เข้าสู่ระบบ</a>
