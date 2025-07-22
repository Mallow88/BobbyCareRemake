<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// รับคำร้องที่อนุมัติครบแล้วและ assigned ถึง developer นี้
$stmt = $conn->prepare("
    SELECT 
        sr.*, sr.id AS request_id,
        sa.status AS senior_status,
        al.assigned_to_user_id,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname
    FROM senior_approval_logs sa
    JOIN gm_approval_logs gm ON sa.gm_approval_log_id = gm.id
    JOIN approval_logs al ON gm.approval_log_id = al.id
    JOIN service_requests sr ON al.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    WHERE sa.status = 'approved'
      AND al.assigned_to_user_id = ?
      AND sr.status != 'accepted'
    ORDER BY sr.created_at DESC
");
$stmt->execute([$developer_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รับงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request_id'])) {
    $request_id = $_POST['accept_request_id'];

    // อัปเดตสถานะในตาราง service_requests
    $update = $conn->prepare("UPDATE service_requests SET status = 'received' WHERE id = ?");
    $update->execute([$request_id]);

    // เพิ่มข้อมูลใน tasks
    $insert = $conn->prepare("
        INSERT INTO tasks (service_request_id, developer_user_id, accepted_at)  
        VALUES (?, ?, NOW())
    ");
    $insert->execute([$request_id, $developer_id]);

    header("Location: dev_index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>งานของผู้พัฒนา</title>
</head>
<body>
    <h1>📋 งานที่ได้รับมอบหมาย</h1>
     <p><a href="subtasks.php">📜 ตารางงาน</a></p>
      <p><a href="../logout.php">🚪 ออกจากระบบ</a></p>

    <?php if (empty($requests)): ?>
        <p>ไม่มีงานที่รอรับ</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>หัวข้อ</th>
                    <th>รายละเอียด</th>
                    <th>ผู้ร้องขอ</th>
                    <th>วันที่ร้องขอ</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($req['description'])) ?></td>
                        <td><?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?></td>
                        <td><?= $req['created_at'] ?></td>
                        <td><?= htmlspecialchars($req['status']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="accept_request_id" value="<?= $req['request_id'] ?>">
                                <button type="submit" onclick="return confirm('ยืนยันการรับงาน?')">✅ รับงาน</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
