<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    echo "ไม่พบคำขอบริการที่ระบุ";
    exit();
}

// ตรวจสอบว่ามีการอนุมัติคำขอนี้ไปแล้วหรือยัง
$stmt = $conn->prepare("SELECT * FROM approval_logs WHERE service_request_id = ?");
$stmt->execute([$request_id]);
$existingApproval = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลคำขอบริการ
$stmt = $conn->prepare("SELECT sr.*, u.name AS requester_name, u.lastname AS requester_lastname 
                        FROM service_requests sr 
                        JOIN users u ON sr.user_id = u.id 
                        WHERE sr.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo "ไม่พบคำขอบริการนี้";
    exit();
}

// ดึงรายชื่อ developer
$dev_stmt = $conn->query("SELECT id, name, lastname FROM users WHERE role = 'developer'");
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ามีการส่งแบบฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingApproval) {
    $assignor_id = $_SESSION['user_id'];

    // ดึงผลการอนุมัติจาก div_mgr_logs
    $divStmt = $conn->prepare("SELECT status, reason FROM div_mgr_logs WHERE service_request_id = ?");
    $divStmt->execute([$request_id]);
    $divApproval = $divStmt->fetch(PDO::FETCH_ASSOC);
    $div_status = $divApproval['status'] ?? null;
    $div_reason = $divApproval['reason'] ?? null;

    if (isset($_POST['approve'])) {
        $developer_id = $_POST['developer_id'] ?? null;

        if ($developer_id) {
            // เพิ่มเข้า approval_logs พร้อมผลจาก div_mgr
            $stmt = $conn->prepare("INSERT INTO approval_logs 
                (service_request_id, assigned_to_user_id, status, reason, 
                 div_mgr_approval_status, div_mgr_approval_reason, approved_by_assignor_id)
                VALUES (?, ?, 'approved', NULL, ?, ?, ?)");
            $stmt->execute([
                $request_id,
                $developer_id,
                $div_status,
                $div_reason,
                $assignor_id
            ]);
            header("Location: view_requests.php");
            exit();
        } else {
            $error = "กรุณาเลือกผู้พัฒนา";
        }

    } elseif (isset($_POST['reject'])) {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason !== '') {
            // เพิ่มเข้า approval_logs พร้อมผลจาก div_mgr
            $stmt = $conn->prepare("INSERT INTO approval_logs 
                (service_request_id, status, reason,
                 div_mgr_approval_status, div_mgr_approval_reason, approved_by_assignor_id)
                VALUES (?, 'rejected', ?, ?, ?, ?)");
            $stmt->execute([
                $request_id,
                $reason,
                $div_status,
                $div_reason,
                $assignor_id
            ]);
            header("Location: view_requests.php");
            exit();
        } else {
            $error = "กรุณาระบุเหตุผลที่ไม่อนุมัติ";
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>จัดการคำขอบริการ</title>
</head>
<body>
    <h2>📋 จัดการคำขอบริการ</h2>
    <p><a href="view_requests.php">← กลับ</a></p>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <p><strong>ผู้ร้องขอ:</strong> <?= htmlspecialchars($request['requester_name'] . ' ' . $request['requester_lastname']) ?></p>
    <p><strong>หัวข้อ:</strong> <?= htmlspecialchars($request['title']) ?></p>
    <p><strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($request['description'])) ?></p>

    <?php if (!$existingApproval): ?>
        <form method="post">
            <p>
    <label>เลือกผู้พัฒนา (developer):</label><br>
    <select name="developer_id" required>
        <option value="">-- เลือก --</option>
        <?php foreach ($developers as $dev): ?>
            <option value="<?= $dev['id'] ?>">
                <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</p>

            <button type="submit" name="approve">✅ อนุมัติและมอบหมาย</button>
        </form>

        <hr>

        <form method="post">
            <p>
                <label>เหตุผลที่ไม่อนุมัติ:</label><br>
                <textarea name="reason" rows="4" cols="50" placeholder="ระบุเหตุผลที่ปฏิเสธ..." required></textarea>
            </p>
            <button type="submit" name="reject" style="color:red;">❌ ไม่อนุมัติ</button>
        </form>

    <?php else: ?>
        <h3>📌 คำขอนี้ได้รับการพิจารณาแล้ว</h3>
        <p><strong>ผลการอนุมัติ:</strong>
            <?= $existingApproval['status'] === 'approved' ? '✅ อนุมัติแล้ว' : '❌ ไม่อนุมัติ' ?>
        </p>
        <?php if ($existingApproval['status'] === 'rejected'): ?>
            <p><strong>เหตุผล:</strong> <?= nl2br(htmlspecialchars($existingApproval['reason'])) ?></p>
        <?php elseif ($existingApproval['status'] === 'approved' && $existingApproval['assigned_to_user_id']): ?>
            <?php
            $dev_stmt = $conn->prepare("SELECT name, lastname FROM users WHERE id = ?");
            $dev_stmt->execute([$existingApproval['assigned_to_user_id']]);
            $assigned_dev = $dev_stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <?php if ($assigned_dev): ?>
                <p><strong>ผู้พัฒนาที่ได้รับมอบหมาย:</strong> 
                    <?= htmlspecialchars($assigned_dev['name'] . ' ' . $assigned_dev['lastname']) ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
