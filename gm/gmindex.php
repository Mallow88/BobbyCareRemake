<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

// ดึงคำขอที่ผ่าน assignor + div_mgr แล้ว
$stmt = $conn->prepare("
    SELECT 
        al.id AS approval_log_id,
        al.status AS dept_mgr_status,
        al.reason AS dept_mgr_reason,
        al.created_at,
        sr.title, sr.description,
        requester.name AS requester_name, requester.lastname AS requester_lastname,
        dev.name AS developer_name, dev.lastname AS developer_lastname,
        assignor.name AS dept_mgr_name, assignor.lastname AS dept_mgr_lastname,
        dml.status AS div_mgr_approval_status,
        dml.reason AS div_mgr_approval_reason,
        divmgr.name AS div_mgr_name, divmgr.lastname AS div_mgr_lastname
    FROM approval_logs al
    JOIN service_requests sr ON al.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON al.assigned_to_user_id = dev.id
    LEFT JOIN users assignor ON al.assignor_id = assignor.id
    LEFT JOIN div_mgr_logs dml ON al.service_request_id = dml.service_request_id
    LEFT JOIN users divmgr ON dml.div_mgr_user_id = divmgr.id
    WHERE dml.status = 'approved'
    ORDER BY al.created_at DESC
");



$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>ผู้จัดการทั่วไป</title></head>
<body>
    <h2>✅ รายการคำขอผ่านการอนุมัติ (GM)</h2>
    <p><a href="approved_list.php">📄 รายการที่อนุมัติแล้ว</a></p>
     <p><a href="../logout.php">🚪 ออกจากระบบ</a></p>

   <table border="1" cellpadding="8">
   <thead>
    <tr>
        <th>ผู้ขอ</th>
        <th>หัวข้อ</th>
        <th>รายละเอียด</th>
        <th>ผู้พัฒนาที่ได้รับมอบหมาย</th>
        <th>ผู้จัดการแผนกอนุมัติ</th>
        <th>เหตุผลจากแผนก</th>
        <th>ผู้จัดการฝ่ายอนุมัติ</th>
        <th>เหตุผลจากฝ่าย</th>
        <th>เวลาที่ส่งอนุมัติมา</th>
        <th>ดำเนินการ</th>
    </tr>
</thead>


 <tbody> 
<?php foreach ($requests as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['requester_name'] . ' ' . $r['requester_lastname']) ?></td>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= nl2br(htmlspecialchars($r['description'])) ?></td>
        <td>
            <?= htmlspecialchars($r['developer_name'] . ' ' . $r['developer_lastname']) ?>
        </td>
        <td>
            <?= htmlspecialchars($r['dept_mgr_name'] . ' ' . $r['dept_mgr_lastname']) ?>
            <br>
            <small>
                <?= $r['dept_mgr_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?>
            </small>
        </td>

        <td><?= nl2br(htmlspecialchars($r['dept_mgr_reason'])) ?></td>

        <td>
            <?= htmlspecialchars($r['div_mgr_name'] . ' ' . $r['div_mgr_lastname']) ?>
            <br>
            <small>
                <?= $r['div_mgr_approval_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?>
            </small>
        </td>

        <td><?= nl2br(htmlspecialchars($r['div_mgr_approval_reason'])) ?></td>

        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><a href="gm_approve.php?id=<?= $r['approval_log_id'] ?>">✅ อนุมัติ</a></td>
    </tr>
<?php endforeach; ?>
</tbody>



</table>

</body>
</html>
