<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seniorgm') {
    header("Location: ../index.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        gm.*, 
        al.service_request_id,
        sr.title, sr.description, sr.created_at AS request_created,
        requester.name AS requester_name, requester.lastname AS requester_lastname,
        dev.name AS developer_name, dev.lastname AS developer_lastname,

        assignor.name AS assignor_name, assignor.lastname AS assignor_lastname,
        al.status AS assignor_status,
        al.reason AS assignor_reason,

        dml.status AS div_mgr_status,
        dml.reason AS div_mgr_reason,
        divmgr.name AS div_mgr_name, divmgr.lastname AS div_mgr_lastname,

        gm_approver.name AS gm_name, gm_approver.lastname AS gm_lastname
    FROM gm_approval_logs gm
    JOIN approval_logs al ON gm.approval_log_id = al.id
    JOIN service_requests sr ON al.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON al.assigned_to_user_id = dev.id
    LEFT JOIN users assignor ON al.assignor_id = assignor.id
    LEFT JOIN div_mgr_logs dml ON sr.id = dml.service_request_id
    LEFT JOIN users divmgr ON dml.div_mgr_user_id = divmgr.id
    LEFT JOIN users gm_approver ON gm.gm_user_id = gm_approver.id
    ORDER BY gm.created_at DESC
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ผู้จัดการทั่วไปอาวุโส</title>
</head>
<body>
    <h2>📋 รายการที่ GM อนุมัติแล้ว (Senior GM)</h2>
    <p><a href="../logout.php">🚪 ออกจากระบบ</a></p>
    <p><a href="senior_list.php">📜 ดูรายการที่พิจารณาแล้ว</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ผู้ร้องขอ</th>
                <th>หัวข้อ</th>
                <th>รายละเอียด</th>
                <th>ผู้พัฒนา</th>
                <th>ผู้จัดการแผนก</th>
                <th>เหตุผลแผนก</th>
                <th>ผู้จัดการฝ่าย</th>
                <th>เหตุผลฝ่าย</th>
                <th>GM พิจารณา</th>
                <th>เหตุผล GM</th>
                <th>วันที่ร้องขอ</th>
                <th>วันที่ GM พิจารณา</th>
                <th>ดำเนินการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['requester_name'] . ' ' . $r['requester_lastname']) ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= nl2br(htmlspecialchars($r['description'])) ?></td>
                <td><?= $r['developer_name'] ? htmlspecialchars($r['developer_name'] . ' ' . $r['developer_lastname']) : '-' ?></td>

                <!-- ผู้จัดการแผนก -->
                <td>
                    <?= htmlspecialchars($r['assignor_name'] . ' ' . $r['assignor_lastname']) ?>
                    <br><small><?= $r['assignor_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?></small>
                </td>
                <td><?= nl2br(htmlspecialchars($r['assignor_reason'])) ?: '-' ?></td>

                <!-- ผู้จัดการฝ่าย -->
                <td>
                    <?= htmlspecialchars($r['div_mgr_name'] . ' ' . $r['div_mgr_lastname']) ?>
                    <br><small><?= $r['div_mgr_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?></small>
                </td>
                <td><?= nl2br(htmlspecialchars($r['div_mgr_reason'])) ?: '-' ?></td>

                <!-- GM -->
                <td><?= $r['status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?><br><small>โดย <?= htmlspecialchars($r['gm_name'] . ' ' . $r['gm_lastname']) ?></small></td>
                <td><?= nl2br(htmlspecialchars($r['reason'])) ?: '-' ?></td>

                <td><?= htmlspecialchars($r['request_created']) ?></td>
                <td><?= htmlspecialchars($r['created_at']) ?></td>

                <!-- ลิงก์ดำเนินการ -->
                <td>
                    <a href="senior_approve.php?id=<?= $r['id'] ?>">📝 พิจารณา</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
