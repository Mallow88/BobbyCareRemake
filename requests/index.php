<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่า login แล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงรายการ service request พร้อมชื่อแอดมิน (ถ้ามี)
$stmt = $conn->prepare("
    SELECT sr.*, a.name AS admin_name
    FROM service_requests sr
    LEFT JOIN admins a ON sr.assigned_to_admin_id = a.id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายการคำขอบริการ</title>
</head>
<body>
    <h1>📄 รายการคำขอบริการของคุณ</h1>

    <p><a href="../dashboard.php">← กลับแดชบอร์ด</a></p>
    <p><a href="create.php">➕ สร้างคำขอบริการใหม่</a></p>

    <?php if (count($requests) === 0): ?>
        <p>ยังไม่มีคำขอบริการ</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>หัวข้อ</th>
                    <th>รายละเอียด</th>
                    <th>สถานะ</th>
                    <th>แอดมินที่รับผิดชอบ</th>
                    <th>สร้างเมื่อ</th>
                    <th>อัปเดตล่าสุด</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($req['description'])) ?></td>
                        <td><?= htmlspecialchars($req['status']) ?></td>
                        <td><?= htmlspecialchars($req['admin_name'] ?? '-') ?></td>
                        <td><?= $req['created_at'] ?></td>
                        <td><?= $req['updated_at'] ?></td>
                        <td>
                            <a href="edit.php?id=<?= $req['id'] ?>">แก้ไข</a> |
                            <a href="delete.php?id=<?= $req['id'] ?>" onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบ?');">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
