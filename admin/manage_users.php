<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>👥 จัดการผู้ใช้ | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');

        body {
            background-color: #000;
            color: #ffffffff;
            font-family: 'Share Tech Mono', monospace;
            min-height: 100vh;
            padding: 40px;
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

        h1 {
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 10px #ffffffff;
        }

        a {
            color: #dbdbdbff;
            text-decoration: none;
        }

        a:hover {
            color: #000;
            background-color: #e0ece7ff;
            padding: 2px 6px;
            border-radius: 5px;
            transition: 0.2s;
        }

        .btn-back, .btn-logout {
            margin-bottom: 20px;
            display: inline-block;
            padding: 6px 12px;
            color: #e6e6e6ff;
            border: 1px solid #e7e7e7ff;
            border-radius: 6px;
            background-color: transparent;
            text-decoration: none;
        }

        .btn-back:hover, .btn-logout:hover {
            background-color: #e6e6e6ff;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 15px #ffffffff;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #dbdbdbff;
            padding: 8px 12px;
            text-align: center;
        }

        th {
            background-color: #001a00;
            color: #f7f7f7ff;
        }

        tr:nth-child(even) {
            background-color: #001100;
        }

        .actions a {
            margin: 0 5px;
        }
    </style>
</head>
<body>

<div class="matrix-bg"></div>

<h1>👥 จัดการผู้ใช้ทั้งหมด</h1>

<div class="mb-3 text-center">
    <a href="dashboard.php" class="btn-back">← กลับแดชบอร์ด</a>
    <a href="logout.php" class="btn-logout">🚪 ออกจากระบบ</a>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>LINE ID</th>
            <th>ชื่อ</th>
            <th>นามสกุล</th>
            <th>Email</th>
            <th>เบอร์</th>
            <th>Role</th>
            <th>วันที่สมัคร</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['line_id']) ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['lastname']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td class="actions">
                    <a href="edit_user.php?id=<?= $user['id'] ?>">✏️</a>
                    <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('คุณแน่ใจว่าต้องการลบ?')">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
