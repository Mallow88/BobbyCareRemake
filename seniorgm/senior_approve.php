<?php  
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seniorgm') {
    header("Location: ../index.php");
    exit();
}

$gm_approval_id = $_GET['id'] ?? null;
if (!$gm_approval_id) {
    echo "ไม่พบคำขอที่ระบุ"; exit();
}

// ตรวจสอบว่ามีการพิจารณาแล้ว
$check = $conn->prepare("SELECT * FROM senior_approval_logs WHERE gm_approval_log_id = ?");
$check->execute([$gm_approval_id]);
if ($check->rowCount() > 0) {
    echo "คำขอนี้ได้รับการพิจารณาโดย Senior GM แล้ว";
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        gm.*, 
        sr.title, sr.description,
        requester.name AS requester_name, requester.lastname AS requester_lastname,
        dev.name AS dev_name, dev.lastname AS dev_lastname,
        assignor.name AS assignor_name, assignor.lastname AS assignor_lastname,
        al.status AS assignor_status, al.reason AS assignor_reason,
        divmgr.name AS div_mgr_name, divmgr.lastname AS div_mgr_lastname,
        dml.status AS div_mgr_status, dml.reason AS div_mgr_reason
    FROM gm_approval_logs gm
    JOIN approval_logs al ON gm.approval_log_id = al.id
    JOIN service_requests sr ON al.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON al.assigned_to_user_id = dev.id
    LEFT JOIN users assignor ON al.assignor_id = assignor.id
    LEFT JOIN div_mgr_logs dml ON sr.id = dml.service_request_id
    LEFT JOIN users divmgr ON dml.div_mgr_user_id = divmgr.id
    WHERE gm.id = ?
");
$stmt->execute([$gm_approval_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "ไม่พบคำขอ"; exit();
}

// เมื่อ submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $reason = trim($_POST['reason'] ?? '');
    $senior_gm_id = $_SESSION['user_id'];

    if ($status === 'rejected' && $reason === '') {
        $error = "กรุณาระบุเหตุผลที่ไม่อนุมัติ";
    } else {
        $insert = $conn->prepare("
            INSERT INTO senior_approval_logs (gm_approval_log_id, status, reason, senior_gm_user_id) 
            VALUES (?, ?, ?, ?)
        ");
        $insert->execute([$gm_approval_id, $status, $reason, $senior_gm_id]);
        header("Location: seniorindex.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>พิจารณาคำขอ (Senior GM)</title>
</head>
<body>
    <h2>📋 พิจารณาคำขอโดย Senior GM</h2>

    <p><strong>ผู้ร้องขอ:</strong> <?= htmlspecialchars($data['requester_name'] . ' ' . $data['requester_lastname']) ?></p>
    <p><strong>หัวข้อ:</strong> <?= htmlspecialchars($data['title']) ?></p>
    <p><strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($data['description'])) ?></p>
    <p><strong>ผู้พัฒนาที่ได้รับมอบหมาย:</strong> <?= $data['dev_name'] ? htmlspecialchars($data['dev_name'] . ' ' . $data['dev_lastname']) : '-' ?></p>

    <p><strong>ผู้จัดการแผนก:</strong> <?= htmlspecialchars($data['assignor_name'] . ' ' . $data['assignor_lastname']) ?> 
        (<?= $data['assignor_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?>)<br>
        <small><?= nl2br(htmlspecialchars($data['assignor_reason'])) ?></small>
    </p>

    <p><strong>ผู้จัดการฝ่าย:</strong> <?= htmlspecialchars($data['div_mgr_name'] . ' ' . $data['div_mgr_lastname']) ?> 
        (<?= $data['div_mgr_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?>)<br>
        <small><?= nl2br(htmlspecialchars($data['div_mgr_reason'])) ?></small>
    </p>

    <p><strong>GM:</strong> 
        (<?= $data['status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?>)<br>
        <small><?= nl2br(htmlspecialchars($data['reason'])) ?></small>
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

    <p><a href="seniorindex.php">← กลับ</a></p>
</body>
</html>
