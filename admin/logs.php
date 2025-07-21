<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT admin_logs.*, admins.name 
    FROM admin_logs 
    JOIN admins ON admin_logs.admin_id = admins.id 
    ORDER BY admin_logs.created_at DESC
");
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<h1>ประวัติการใช้งานของแอดมิน</h1>
<table border="1">
    <tr>
        <th>เวลา</th>
        <th>แอดมิน</th>
        <th>การกระทำ</th>
    </tr>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= $log['created_at'] ?></td>
        <td><?= htmlspecialchars($log['name']) ?></td>
        <td><?= htmlspecialchars($log['action']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
