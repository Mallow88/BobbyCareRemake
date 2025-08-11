<?php  
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

// ดึงข้อมูลจาก approval_logs + service_requests + ผู้ร้องขอ + ผู้พัฒนา + div_mgr_logs
$stmt = $conn->prepare("
    SELECT 
        al.*, 
        sr.title, sr.description, sr.created_at AS request_created_at,
        requester.name AS requester_name, requester.lastname AS requester_lastname,
        dev.name AS developer_name, dev.lastname AS developer_lastname
    FROM approval_logs al
    JOIN service_requests sr ON al.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON al.assigned_to_user_id = dev.id
    WHERE al.approved_by_assignor_id = ?
    ORDER BY al.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายการที่อนุมัติแล้ว</title>
</head>
<body>
    <h1>✅ รายการที่อนุมัติ / ไม่อนุมัติแล้ว</h1>
    <p><a href="view_requests.php">← กลับไปยังคำขอรอดำเนินการ</a></p>

    <table border="1" cellpadding="8"> 
        <thead>
            <tr>
                <th>ผู้ขอ</th>
                <th>หัวข้อ</th>
                <th>รายละเอียด</th>
                <th>ผลการอนุมัติ (ฝ่าย)</th>
                <th>เหตุผล (ฝ่าย)</th>
                <th>ผลการอนุมัติ (ตนเอง)</th>
                <th>ผู้พัฒนา</th>
                <th>เหตุผล (ตนเอง)</th>
                <th>เวลาที่อนุมัติ</th>
                <th>แก้ไข</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['requester_name'] . ' ' . $log['requester_lastname']) ?></td>
                    <td><?= htmlspecialchars($log['title']) ?></td>
                    <td><?= nl2br(htmlspecialchars($log['description'])) ?></td>

                    <!-- ผลจาก div_mgr -->
                    <td>
                        <?= $log['div_mgr_approval_status'] === 'approved' ? '✅ อนุมัติ' : 
                            ($log['div_mgr_approval_status'] === 'rejected' ? '❌ ไม่อนุมัติ' : '-') ?>
                    </td>
                    <td>
                        <?= $log['div_mgr_approval_reason'] ? nl2br(htmlspecialchars($log['div_mgr_approval_reason'])) : '-' ?>
                    </td>

                    <!-- ผลจาก assignor -->
                    <td>
                        <?= $log['status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?>
                    </td>
                    <td>
                        <?= $log['developer_name'] ? htmlspecialchars($log['developer_name'] . ' ' . $log['developer_lastname']) : '-' ?>
                    </td>
                    <td>
                        <?= $log['reason'] ? nl2br(htmlspecialchars($log['reason'])) : '-' ?>
                    </td>
                    <td><?= $log['created_at'] ?></td>
                    <td><a href="edit_approval.php?id=<?= $log['id'] ?>">✏️ แก้ไข</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
