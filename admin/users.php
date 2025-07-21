<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ผู้ใช้งานทั้งหมด</title>
</head>
<body>
    <h2>📋 รายชื่อผู้ใช้ทั้งหมด</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>LINE ID</th>
            <th>ชื่อ</th>
            <th>Email</th>
            <th>Role</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['id']) ?></td>
            <td><?= htmlspecialchars($u['line_id']) ?></td>
            <td><?= htmlspecialchars($u['name'] . ' ' . $u['lastname']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['role']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p><a href="dashboard.php">← กลับแดชบอร์ดแอดมิน</a></p>
</body>
</html>
