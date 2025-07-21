<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'divmgr') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงรายการคำขอที่ยังไม่ถูกดำเนินการโดย div_mgr
$stmt = $conn->query("
    SELECT sr.*, u.name, u.lastname 
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    WHERE sr.id NOT IN (SELECT service_request_id FROM div_mgr_logs)
    ORDER BY sr.created_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ามีการ submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? null;

    if ($status === 'rejected' && trim($reason) === '') {
        $error = "กรุณาระบุเหตุผลเมื่อไม่อนุมัติ";
    } else {
        $stmt = $conn->prepare("INSERT INTO div_mgr_logs (service_request_id, status, reason, approved_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$request_id, $status, $reason, $user_id]);
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>อนุมัติคำขอ - ผู้จัดการฝ่าย</title>
</head>
<body>
        <h1>สวัสดี ผู้จัดการฝ่าย</h1>
    <h2>📋 รายการคำขอจากผู้ใช้งาน</h2>
    <p><a href="view_logs.php">📄 ดูประวัติการอนุมัติ</a></p>
     <p><a href="../logout.php">🚪 ออกจากระบบ</a></p>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (empty($requests)): ?>
        <p>ไม่มีคำขอที่รอการอนุมัติ</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ผู้ขอ</th>
                    <th>หัวข้อ</th>
                    <th>รายละเอียด</th>
                    <th>เวลาที่ส่ง</th>
                    <th>สถานะ</th>
                    <th>เหตุผล (ถ้ามี)</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['name'] . ' ' . $req['lastname']) ?></td>
                        <td><?= htmlspecialchars($req['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($req['description'])) ?></td>
                        <td><?= htmlspecialchars($req['created_at']) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <label>
                                    <input type="radio" name="status" value="approved" required> ✅ อนุมัติ
                                </label><br>
                                <label>
                                    <input type="radio" name="status" value="rejected" required> ❌ ไม่อนุมัติ
                                </label>
                        </td>
                        <td>
                                <textarea name="reason" rows="2" cols="30" placeholder="ระบุเหตุผลถ้าไม่อนุมัติ..."></textarea>
                        </td>
                        <td>
                                <button type="submit">บันทึกผล</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
