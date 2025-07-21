<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>แก้ไขโปรไฟล์</title>
</head>
<body>
    <h2>แก้ไขโปรไฟล์</h2>

    <form action="update_profile.php" method="POST">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <label>ชื่อ:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required><br><br>

        <label>นามสกุล:</label><br>
        <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>"><br><br>

        <label>อีเมล:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"><br><br>

        <label>เบอร์โทรศัพท์:</label><br>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>"><br><br>

        <button type="submit">💾 บันทึก</button>
    </form>

    <p><a href="profile.php">🔙 กลับไปโปรไฟล์</a></p>
</body>
</html>
