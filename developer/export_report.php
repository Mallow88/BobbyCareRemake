<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];
$export_type = $_GET['export'] ?? 'pdf';

// รับพารามิเตอร์การฟิลเตอร์
$filter_type = $_GET['filter'] ?? 'all';
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_year = $_GET['year'] ?? date('Y');

// สร้าง WHERE clause ตามการฟิลเตอร์
$where_clause = "WHERE t.developer_user_id = ? AND t.task_status IN ('completed', 'accepted')";
$params = [$developer_id];

switch ($filter_type) {
    case 'day':
        $where_clause .= " AND DATE(t.completed_at) = ?";
        $params[] = $filter_date;
        break;
    case 'month':
        $where_clause .= " AND DATE_FORMAT(t.completed_at, '%Y-%m') = ?";
        $params[] = $filter_month;
        break;
    case 'year':
        $where_clause .= " AND YEAR(t.completed_at) = ?";
        $params[] = $filter_year;
        break;
}

// ดึงข้อมูลงาน
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        requester.department as requester_department,
        aa.estimated_days,
        s.name as service_name,
        s.category as service_category,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        DATEDIFF(t.completed_at, t.started_at) as days_used,
        TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at) as hours_used
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    $where_clause
    ORDER BY t.completed_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล developer
$dev_stmt = $conn->prepare("SELECT name, lastname, department FROM users WHERE id = ?");
$dev_stmt->execute([$developer_id]);
$developer = $dev_stmt->fetch(PDO::FETCH_ASSOC);

if ($export_type === 'excel') {
    // ส่งออก Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="รายงานงานที่เสร็จแล้ว_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>ลำดับ</th>';
    echo '<th>หัวข้องาน</th>';
    echo '<th>ผู้ขอ</th>';
    echo '<th>หน่วยงาน</th>';
    echo '<th>ประเภทบริการ</th>';
    echo '<th>วันที่เริ่ม</th>';
    echo '<th>วันที่เสร็จ</th>';
    echo '<th>เวลาที่ใช้ (วัน)</th>';
    echo '<th>ประมาณการ (วัน)</th>';
    echo '<th>คะแนน</th>';
    echo '<th>สถานะรีวิว</th>';
    echo '</tr>';
    
    foreach ($tasks as $index => $task) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($task['title']) . '</td>';
        echo '<td>' . htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) . '</td>';
        echo '<td>' . htmlspecialchars($task['requester_department'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($task['service_name'] ?? '') . '</td>';
        echo '<td>' . ($task['started_at'] ? date('d/m/Y', strtotime($task['started_at'])) : '') . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($task['completed_at'])) . '</td>';
        echo '<td>' . ($task['days_used'] ?? '') . '</td>';
        echo '<td>' . ($task['estimated_days'] ?? '') . '</td>';
        echo '<td>' . ($task['rating'] ?? '') . '</td>';
        echo '<td>' . ($task['review_status'] === 'accepted' ? 'ยอมรับ' : ($task['review_status'] === 'revision_requested' ? 'ขอแก้ไข' : 'รอรีวิว')) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit();
}

// ส่งออก PDF (HTML)
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานงานที่เสร็จแล้ว</title>
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
        }
        .info-section {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
            margin: 10px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>รายงานงานที่เสร็จแล้ว</h1>
        <h3>Developer: <?= htmlspecialchars($developer['name'] . ' ' . $developer['lastname']) ?></h3>
        <p>หน่วยงาน: <?= htmlspecialchars($developer['department'] ?? 'ไม่ระบุ') ?></p>
        <p>วันที่สร้างรายงาน: <?= date('d/m/Y H:i') ?></p>
        <p>
            ช่วงเวลา: 
            <?php
            switch ($filter_type) {
                case 'day': echo 'วันที่ ' . date('d/m/Y', strtotime($filter_date)); break;
                case 'month': echo 'เดือน ' . date('m/Y', strtotime($filter_month . '-01')); break;
                case 'year': echo 'ปี ' . ($filter_year + 543); break;
                default: echo 'ทั้งหมด'; break;
            }
            ?>
        </p>
    </div>

   
    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>หัวข้องาน</th>
                <th>ผู้ขอ</th>
                <th>ประเภทบริการ</th>
                <th>วันที่เริ่ม</th>
                <th>วันที่เสร็จ</th>
                <th>เวลาที่ใช้</th>
                <th>คะแนน</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $index => $task): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($task['title']) ?></td>
                    <td><?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?></td>
                    <td><?= htmlspecialchars($task['service_name'] ?? 'ไม่ระบุ') ?></td>
                    <td><?= $task['started_at'] ? date('d/m/Y', strtotime($task['started_at'])) : 'ไม่ระบุ' ?></td>

                    <td><?= date('d/m/Y', strtotime($task['completed_at'])) ?></td>
                    <td><?= $task['days_used'] ?? 'ไม่ระบุ' ?> วัน</td>
                    <td><?= $task['rating'] ? $task['rating'] . '/5' : 'ไม่มี' ?></td>
                    <td>
                        <?php
                        if ($task['review_status'] === 'accepted') echo 'ยอมรับ';
                        elseif ($task['review_status'] === 'revision_requested') echo 'ขอแก้ไข';
                        else echo 'รอรีวิว';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

   <div class="no-print" style="margin-top: 30px; text-align: center;">
    <button onclick="window.print()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
        <i class="fas fa-print"></i> พิมพ์รายงาน
    </button>
    <button onclick="window.history.back()" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">
        <i class="fas fa-arrow-left"></i> ย้อนกลับ
    </button>
</div>

</body>
</html>