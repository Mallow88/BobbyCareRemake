<?php  
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seniorgm') {
    header("Location: ../index.php");
    exit();
}

// ดึงข้อมูลที่พิจารณาแล้ว
$stmt = $conn->prepare("
    SELECT 
        sal.*, 
        gm.status AS gm_status, gm.reason AS gm_reason, gm.created_at AS gm_decision_time,
        sr.title, sr.description, sr.created_at AS request_created,
        requester.name AS requester_name, requester.lastname AS requester_lastname,
        dev.name AS dev_name, dev.lastname AS dev_lastname,
        senior.name AS senior_name, senior.lastname AS senior_lastname,
        assignor.name AS assignor_name, assignor.lastname AS assignor_lastname,
        al.status AS assignor_status, al.reason AS assignor_reason,
        divmgr.name AS div_mgr_name, divmgr.lastname AS div_mgr_lastname,
        dml.status AS div_mgr_status, dml.reason AS div_mgr_reason
    FROM senior_approval_logs sal
    JOIN gm_approval_logs gm ON sal.gm_approval_log_id = gm.id
    JOIN approval_logs al ON gm.approval_log_id = al.id
    JOIN service_requests sr ON al.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON al.assigned_to_user_id = dev.id
    LEFT JOIN users senior ON sal.senior_gm_user_id = senior.id
    LEFT JOIN users assignor ON al.assignor_id = assignor.id
    LEFT JOIN div_mgr_logs dml ON sr.id = dml.service_request_id
    LEFT JOIN users divmgr ON dml.div_mgr_user_id = divmgr.id
    ORDER BY sal.created_at DESC
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันแปลงสถานะเป็นคำไทย
function translateStatus($status) {
    return $status === 'approved' ? '✅ อนุมัติ' : '❌ ไม่อนุมัติ';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการที่ Senior GM พิจารณาแล้ว</title>
</head>
<body>

<nav class="custom-navbar navbar navbar-expand-lg shadow-sm">
    <div class="container custom-navbar-container">
        <!-- โลโก้ + ชื่อระบบ (ฝั่งซ้าย) -->
        <a class="navbar-brand d-flex align-items-center custom-navbar-brand" href="gmindex.php">
            <img src="../img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
            <!-- ชื่อระบบ หรือ โลโก้อย่างเดียว ฝั่งซ้าย -->
        </a>

        <!-- ปุ่ม toggle สำหรับ mobile -->
        <button class="navbar-toggler custom-navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- เมนู -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- ซ้าย: เมนูหลัก -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 custom-navbar-menu">
                 <li class="nav-item">
                       <!-- <li class="nav-item">
                        <a class="nav-link" href="view_requests.php"><i class="fas fa-tasks me-1"></i> ตรวจสอบคำขอ</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="approved_list.php"><i class="fas fa-check-circle me-1"></i> รายการที่อนุมัติ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_completed_tasks.php"><i class="fas fa-star me-1"></i> User Reviews</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="developer_dashboard.php"><i class="fas fa-chart-line me-1"></i> Dashboard_DEV</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="report.php"><i class="fas fa-chart-line me-1"></i> Report</a>
                    </li>
            </ul>

            <!-- ขวา: ชื่อผู้ใช้ + ออกจากระบบ -->
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-center">
                <li class="nav-item d-flex align-items-center me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <span class="custom-navbar-title">ผู้จัดการทั่วไปคุณ: <?= htmlspecialchars($_SESSION['name']) ?>!</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <h2>📋 รายการที่ Senior GM พิจารณาแล้ว</h2>
    <p><a href="seniorindex.php">← กลับหน้าแรก</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ผู้ร้องขอ</th>
                <th>หัวข้อ</th>
                <th>รายละเอียด</th>
                <th>ผู้พัฒนา</th>
                <th>ผู้จัดการแผนก</th>
                <th>ผลแผนก</th>
                <th>เหตุผลแผนก</th>
                <th>ผู้จัดการฝ่าย</th>
                <th>ผลฝ่าย</th>
                <th>เหตุผลฝ่าย</th>
                <th>ผลจาก GM</th>
                <th>เหตุผล GM</th>
                <th>ผลจาก Senior GM</th>
                <th>เหตุผล</th>
                <th>โดย</th>
                <th>วันที่ร้องขอ</th>
                <th>วันที่ GM พิจารณา</th>
                <th>วันที่ Senior GM พิจารณา</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['requester_name'] . ' ' . $log['requester_lastname']) ?></td>
                <td><?= htmlspecialchars($log['title']) ?></td>
                <td><?= nl2br(htmlspecialchars($log['description'])) ?></td>
                <td><?= $log['dev_name'] ? htmlspecialchars($log['dev_name'] . ' ' . $log['dev_lastname']) : '-' ?></td>
                
                <td><?= htmlspecialchars($log['assignor_name'] . ' ' . $log['assignor_lastname']) ?></td>
                <td><?= translateStatus($log['assignor_status']) ?></td>
                <td><?= nl2br(htmlspecialchars($log['assignor_reason'])) ?></td>

                <td><?= htmlspecialchars($log['div_mgr_name'] . ' ' . $log['div_mgr_lastname']) ?></td>
                <td><?= translateStatus($log['div_mgr_status']) ?></td>
                <td><?= nl2br(htmlspecialchars($log['div_mgr_reason'])) ?></td>

                <td><?= translateStatus($log['gm_status']) ?></td>
                <td><?= nl2br(htmlspecialchars($log['gm_reason'])) ?></td>

                <td><?= translateStatus($log['status']) ?></td>
                <td><?= nl2br(htmlspecialchars($log['reason'])) ?></td>
                <td><?= htmlspecialchars($log['senior_name'] . ' ' . $log['senior_lastname']) ?></td>
                <td><?= htmlspecialchars($log['request_created']) ?></td>
                <td><?= htmlspecialchars($log['gm_decision_time']) ?></td>
                <td><?= htmlspecialchars($log['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
