<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

$approval_id = $_GET['id'] ?? null;
if (!$approval_id) {
    echo "ไม่พบคำขอที่ระบุ"; exit();
}

// ตรวจสอบว่ามีการอนุมัติจาก GM ไปแล้วหรือยัง
$check = $conn->prepare("SELECT * FROM gm_approval_logs WHERE approval_log_id = ?");
$check->execute([$approval_id]);
if ($check->rowCount() > 0) {
    echo "คำขอนี้ได้รับการพิจารณาโดย GM แล้ว"; exit();
}

// ดึงข้อมูลหลัก + ข้อมูลจาก approval_logs และ div_mgr_logs
$stmt = $conn->prepare("
    SELECT al.*, sr.title, sr.description,
           u.name AS requester_name, u.lastname AS requester_lastname,
           dev.name AS dev_name, dev.lastname AS dev_lastname,
           dml.status AS div_mgr_approval_status,
           dml.reason AS div_mgr_approval_reason
    FROM approval_logs al
    JOIN service_requests sr ON al.service_request_id = sr.id
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN users dev ON al.assigned_to_user_id = dev.id
    LEFT JOIN div_mgr_logs dml ON al.service_request_id = dml.service_request_id
    WHERE al.id = ?
");
$stmt->execute([$approval_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "ไม่พบคำขอ"; exit();
}

// หากส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $reason = trim($_POST['reason'] ?? '');
    $gm_id = $_SESSION['user_id'];

    if ($status === 'rejected' && $reason === '') {
        $error = "กรุณาระบุเหตุผลที่ไม่อนุมัติ";
    } else {
        $ins = $conn->prepare("
            INSERT INTO gm_approval_logs (
                approval_log_id, status, reason, gm_user_id,
                dept_mgr_status, dept_mgr_reason,
                div_mgr_approval_status, div_mgr_approval_reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $approval_id,
            $status,
            $reason,
            $gm_id,
            $data['status'],             // dept_mgr_status
            $data['reason'],             // dept_mgr_reason
            $data['div_mgr_approval_status'],
            $data['div_mgr_approval_reason']
        ]);
        header("Location: gmindex.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>อนุมัติคำขอ (GM)</title>
</head>
<body>
    <h2>📋 อนุมัติคำขอโดย GM</h2>
    <p><strong>ผู้ขอ:</strong> <?= htmlspecialchars($data['requester_name'] . ' ' . $data['requester_lastname']) ?></p>
    <p><strong>หัวข้อ:</strong> <?= htmlspecialchars($data['title']) ?></p>
    <p><strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($data['description'])) ?></p>
    <p><strong>ผู้พัฒนาที่ได้รับมอบหมาย:</strong> 
        <?= $data['dev_name'] ? htmlspecialchars($data['dev_name'] . ' ' . $data['dev_lastname']) : '-' ?>
    </p>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="post">
        <label><input type="radio" name="status" value="approved" required> ✅ อนุมัติ</label><br>
        <label><input type="radio" name="status" value="rejected"> ❌ ไม่อนุมัติ</label><br><br>

        <textarea name="reason" rows="4" cols="50" placeholder="เหตุผล (ถ้ามี)"></textarea><br><br>
        <button type="submit">ยืนยัน</button>
    </form>
    <p><a href="gmindex.php">← กลับ</a></p>
</body>
</html>
