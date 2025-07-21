<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'divmgr') {
    header("Location: ../index.php");
    exit();
}

// ดึงข้อมูลจาก div_mgr_logs
$stmt = $conn->query("
    SELECT d.*, sr.title, sr.description, u.name AS requester_name, u.lastname AS requester_lastname, du.name AS approver_name, du.lastname AS approver_lastname
    FROM div_mgr_logs d
    JOIN service_requests sr ON d.service_request_id = sr.id
    JOIN users u ON sr.user_id = u.id
    JOIN users du ON d.approved_by = du.id
    ORDER BY d.created_at DESC
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ประวัติการอนุมัติ - ผู้จัดการฝ่าย</title>
</head>
<body>
    <h2>📄 ประวัติการอนุมัติ / ไม่อนุมัติ</h2>
    <p><a href="index.php">← กลับหน้ารายการคำขอ</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>หัวข้อ</th>
                <th>รายละเอียด</th>
                <th>ผู้ขอ</th>
                <th>สถานะ</th>
                <th>เหตุผล</th>
                <th>โดย</th>
                <th>เวลา</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['title']) ?></td>
                    <td><?= nl2br(htmlspecialchars($log['description'])) ?></td>
                    <td><?= htmlspecialchars($log['requester_name'] . ' ' . $log['requester_lastname']) ?></td>
                    <td><?= $log['status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?></td>
                    <td><?= $log['reason'] ? nl2br(htmlspecialchars($log['reason'])) : '-' ?></td>
                    <td><?= htmlspecialchars($log['approver_name'] . ' ' . $log['approver_lastname']) ?></td>
                    <td><?= $log['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
