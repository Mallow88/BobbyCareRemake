<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

// ดึงรายการที่ได้รับการอนุมัติ/ไม่อนุมัติจาก GM แล้ว
$stmt = $conn->prepare("
    SELECT 
        gm.*, 
        al.service_request_id,
        sr.title, sr.description, sr.created_at AS request_created,
        requester.name AS requester_name, requester.lastname AS requester_lastname,
        dev.name AS dev_name, dev.lastname AS dev_lastname
    FROM gm_approval_logs gm
    JOIN approval_logs al ON gm.approval_log_id = al.id
    JOIN service_requests sr ON sr.id = al.service_request_id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON al.assigned_to_user_id = dev.id
    ORDER BY gm.created_at DESC
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการที่ GM พิจารณาแล้ว</title>
</head>
<body>
    <h1>📋 รายการที่ GM พิจารณาแล้ว</h1>
    <p><a href="gmindex.php">← กลับ</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ผู้ร้องขอ</th>
                <th>หัวข้อ</th>
                <th>รายละเอียด</th>
                <th>ผู้พัฒนา</th>
                <th>สถานะ GM</th>
                <th>เหตุผล GM</th>
                <th>สถานะแผนก</th>
                <th>เหตุผลแผนก</th>
                <th>สถานะฝ่าย</th>
                <th>เหตุผลฝ่าย</th>
                <th>เวลาที่ร้องขอ</th>
                <th>เวลาที่ GM พิจารณา</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['requester_name'] . ' ' . $log['requester_lastname']) ?></td>
                    <td><?= htmlspecialchars($log['title']) ?></td>
                    <td><?= nl2br(htmlspecialchars($log['description'])) ?></td>
                    <td><?= $log['dev_name'] ? htmlspecialchars($log['dev_name'] . ' ' . $log['dev_lastname']) : '-' ?></td>
                    <td><?= $log['status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?></td>
                    <td><?= $log['reason'] ? nl2br(htmlspecialchars($log['reason'])) : '-' ?></td>
                    
                    <td><?= $log['dept_mgr_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?></td>
                    <td><?= $log['dept_mgr_reason'] ? nl2br(htmlspecialchars($log['dept_mgr_reason'])) : '-' ?></td>

                    <td><?= $log['div_mgr_approval_status'] === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ' ?></td>
                    <td><?= $log['div_mgr_approval_reason'] ? nl2br(htmlspecialchars($log['div_mgr_approval_reason'])) : '-' ?></td>

                    <td><?= $log['request_created'] ?></td>
                    <td><?= $log['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
