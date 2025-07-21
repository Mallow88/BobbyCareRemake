<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

// ดึงคำขอที่ผ่านการอนุมัติจาก div_mgr_logs แล้วเท่านั้น
$stmt = $conn->prepare("
    SELECT dml.*, sr.title, sr.description, sr.created_at, u.name, u.lastname
    FROM div_mgr_logs dml
    JOIN service_requests sr ON dml.service_request_id = sr.id
    JOIN users u ON sr.user_id = u.id
    WHERE dml.status = 'approved'
    ORDER BY dml.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>คำขอรอดำเนินการ (ผ่าน Div. Manager แล้ว)</title>
</head>
<body>
    <h1>📌 คำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่าย</h1>
    <p><a href="approval.php">ดูรายการที่อนุมัติโดยคุณแล้ว</a></p>
    <p><a href="../assignor/index.php">ย้อนกลับ</a></p>

    <?php if (empty($requests)): ?>
        <p>ไม่มีคำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่าย</p>
    <?php else: ?>
        <table border="1" cellpadding="8"> 
            <thead>
                <tr>
                    <th>ผู้ขอ</th>
                    <th>หัวข้อ</th>
                    <th>รายละเอียด</th>
                    <th>เวลาที่ส่ง</th>
                    <th>ผลการอนุมัติจาก Div. Manager</th>
                    <th>เหตุผล</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['name'] . ' ' . $req['lastname']) ?></td>
                        <td><?= htmlspecialchars($req['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($req['description'])) ?></td>
                        <td><?= $req['created_at'] ?></td>
                        <td>✅ อนุมัติ</td>
                        <td><?= nl2br(htmlspecialchars($req['reason'] ?? '-')) ?></td>
                        <td>
                            <a href="assign_status.php?id=<?= $req['service_request_id'] ?>">🛠 ดำเนินการ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
