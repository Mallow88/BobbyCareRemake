<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่าล็อกอินเป็นแอดมิน
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    die("ไม่พบ ID ผู้ใช้");
}

// รับข้อมูลผู้ใช้
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("ไม่พบผู้ใช้ในระบบ");
}

// เมื่อมีการส่งฟอร์มแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';

    $update = $conn->prepare("UPDATE users SET name = ?, lastname = ?, email = ?, phone = ?, role = ? WHERE id = ?");
    $update->execute([$name, $lastname, $email, $phone, $role, $user_id]);

    // log admin action
    $admin_id = $_SESSION['admin_id'];
    $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)")
         ->execute([$admin_id, "แก้ไขผู้ใช้ ID $user_id"]);

    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>แก้ไขผู้ใช้</title>
</head>
<body>
    <h1>แก้ไขข้อมูลผู้ใช้: <?= htmlspecialchars($user['name']) ?></h1>
    <form method="post">
        <p>ชื่อ: <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>"></p>
        <p>นามสกุล: <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>"></p>
        <p>Email: <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></p>
        <p>เบอร์โทร: <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>"></p>
        <p>สิทธิ์ (role):
    <select name="role">
        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>user</option>
        <option value="assignor" <?= $user['role'] === 'assignor' ? 'selected' : '' ?>>assignor</option>
        <option value="divmgr" <?= $user['role'] === 'divmgr' ? 'selected' : '' ?>>divmgr</option>
       <option value="gmapprover" <?= $user['role'] === 'gmapprover' ? 'selected' : '' ?>>gmapprover</option>
       <option value="seniorgm" <?= $user['role'] === 'seniorgm' ? 'selected' : '' ?>>seniorgm</option>
       <option value="developer" <?= $user['role'] === 'developer' ? 'selected' : '' ?>>developer</option>
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
    </select>
</p>
smapprover
        <button type="submit">💾 บันทึก</button>
        <a href="manage_users.php">← ยกเลิก</a>
    </form>
</body>
</html>
