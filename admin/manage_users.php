<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่าเป็น admin ที่ login อยู่
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลผู้ใช้ทั้งหมดจากตาราง users
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        .actions a {
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <h1>👥 จัดการผู้ใช้ทั้งหมด</h1>
    <p><a href="dashboard.php">← กลับแดชบอร์ด</a></p>
    <li><a href="logout.php">ออกจากระบบ</a></li>

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
                <th>สร้างโดย (admin_id)</th>
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
                    <td><?= htmlspecialchars($user['created_by_admin_id']) ?></td>
                    <td class="actions">
                        <a href="edit_user.php?id=<?= $user['id'] ?>">✏️ แก้ไข</a>
                        <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('คุณแน่ใจว่าต้องการลบ?')">🗑️ ลบ</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
