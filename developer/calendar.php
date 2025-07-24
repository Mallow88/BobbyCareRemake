<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// ดึงงานทั้งหมดของ developer
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    WHERE t.developer_user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$developer_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รับเดือนและปีปัจจุบัน
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// สร้างปฏิทิน
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // วันแรกของเดือนเป็นวันอะไร (0=อาทิตย์)

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$thai_days = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

// จัดกลุ่มงานตามวันที่
$tasks_by_date = [];
foreach ($tasks as $task) {
    $date = date('Y-m-d', strtotime($task['accepted_at'] ?? $task['created_at']));
    if (!isset($tasks_by_date[$date])) {
        $tasks_by_date[$date] = [];
    }
    $tasks_by_date[$date][] = $task;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินงาน - BobbyCareDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .page-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2.5rem;
        }

        .calendar-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .calendar-table th,
        .calendar-table td {
            border: 1px solid #e9ecef;
            padding: 15px 8px;
            vertical-align: top;
            height: 120px;
            width: 14.28%;
        }

        .calendar-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #495057;
            font-weight: 600;
            text-align: center;
            height: 50px;
            font-size: 0.9rem;
        }

        .calendar-table td {
            position: relative;
            background: white;
            transition: all 0.3s ease;
        }

        .calendar-table td:hover {
            background: #f8f9fa;
            transform: scale(1.02);
        }

        .date-number {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .today {
            background: linear-gradient(135deg, #e6fffa, #b2f5ea) !important;
            border: 2px solid #38b2ac;
            font-weight: 700;
        }

        .other-month {
            color: #cbd5e0;
            background: #f7fafc;
        }

        .task-item {
            background: #667eea;
            color: white;
            padding: 3px 6px;
            border-radius: 6px;
            font-size: 0.7rem;
            margin-bottom: 3px;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .task-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .task-item.pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .task-item.received {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .task-item.in_progress {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .task-item.on_hold {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .task-item.completed {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.pending .stat-number {
            color: #f59e0b;
        }

        .stat-card.received .stat-number {
            color: #3b82f6;
        }

        .stat-card.in_progress .stat-number {
            color: #8b5cf6;
        }

        .stat-card.on_hold .stat-number {
            color: #ef4444;
        }

        .stat-card.completed .stat-number {
            color: #10b981;
        }

        .legend {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .month-navigation {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .month-nav-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .month-nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .month-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            text-align: center;
            min-width: 250px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        @media (max-width: 768px) {
            .calendar-table th,
            .calendar-table td {
                height: 80px;
                padding: 8px 4px;
            }

            .task-item {
                font-size: 0.6rem;
                padding: 2px 4px;
            }

            .month-title {
                font-size: 1.5rem;
                min-width: auto;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                <span class="page-title">Calendar</span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['name']) ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <!-- Header Section -->
        <div class="header-card p-5 mb-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="page-title mb-2">
                        <i class="fas fa-calendar-alt me-3"></i>ปฏิทินงาน
                    </h1>
                    <p class="text-muted mb-0 fs-5">ติดตามและจัดการงานของคุณในรูปแบบปฏิทิน</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                        <a href="dev_index.php" class="btn btn-gradient">
                            <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                        </a>
                        <a href="tasks_board.php" class="btn btn-gradient">
                            <i class="fas fa-tasks me-2"></i>บอร์ดงาน
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- สถิติงาน -->
        <div class="stats-grid">
            <?php
            $status_counts = [
                'pending' => 0,
                'received' => 0,
                'in_progress' => 0,
                'on_hold' => 0,
                'completed' => 0
            ];

            foreach ($tasks as $task) {
                if (isset($status_counts[$task['task_status']])) {
                    $status_counts[$task['task_status']]++;
                }
            }

            $status_labels = [
                'pending' => 'รอรับ',
                'received' => 'รับแล้ว',
                'in_progress' => 'กำลังทำ',
                'on_hold' => 'พักงาน',
                'completed' => 'เสร็จแล้ว'
            ];
            ?>

            <?php foreach ($status_counts as $status => $count): ?>
                <div class="stat-card <?= $status ?>">
                    <div class="stat-number"><?= $count ?></div>
                    <div class="stat-label"><?= $status_labels[$status] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ปฏิทิน -->
        <div class="glass-card p-4">
            <!-- ส่วนหัวปฏิทิน -->
            <div class="month-navigation">
                <?php
                $prev_month = $current_month - 1;
                $prev_year = $current_year;
                if ($prev_month < 1) {
                    $prev_month = 12;
                    $prev_year--;
                }

                $next_month = $current_month + 1;
                $next_year = $current_year;
                if ($next_month > 12) {
                    $next_month = 1;
                    $next_year++;
                }
                ?>
                <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="month-nav-btn">
                    <i class="fas fa-chevron-left"></i> เดือนก่อน
                </a>

                <div class="month-title">
                    <?= $thai_months[$current_month] ?> <?= $current_year + 543 ?>
                </div>

                <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="month-nav-btn">
                    เดือนถัดไป <i class="fas fa-chevron-right"></i>
                </a>
            </div>

            <!-- ตารางปฏิทิน -->
            <div class="table-responsive">
                <table class="table calendar-table mb-0">
                    <thead>
                        <tr>
                            <?php foreach ($thai_days as $day): ?>
                                <th><?= $day ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $current_date = 1;
                        $today = date('Y-m-d');
                        
                        for ($week = 0; $week < 6; $week++):
                            if ($current_date > $days_in_month) break;
                        ?>
                            <tr>
                                <?php for ($day = 0; $day < 7; $day++): ?>
                                    <td class="<?php
                                        if ($week == 0 && $day < $start_day) {
                                            echo 'other-month';
                                        } elseif ($current_date > $days_in_month) {
                                            echo 'other-month';
                                        } else {
                                            $cell_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $current_date);
                                            if ($cell_date == $today) {
                                                echo 'today';
                                            }
                                        }
                                    ?>">
                                        <?php if ($week == 0 && $day < $start_day): ?>
                                            <!-- วันของเดือนก่อน -->
                                            <?php
                                            $prev_month_days = date('t', mktime(0, 0, 0, $current_month - 1, 1, $current_year));
                                            echo $prev_month_days - ($start_day - $day - 1);
                                            ?>
                                        <?php elseif ($current_date <= $days_in_month): ?>
                                            <div class="date-number"><?= $current_date ?></div>
                                            <?php
                                            $cell_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $current_date);
                                            if (isset($tasks_by_date[$cell_date])):
                                                foreach ($tasks_by_date[$cell_date] as $task):
                                            ?>
                                                <div class="task-item <?= $task['task_status'] ?>" 
                                                     onclick="showTaskDetail(<?= htmlspecialchars(json_encode($task)) ?>)">
                                                    <?= htmlspecialchars(mb_substr($task['title'], 0, 15)) ?>
                                                </div>
                                            <?php 
                                                endforeach;
                                            endif;
                                            $current_date++;
                                            ?>
                                        <?php else: ?>
                                            <!-- วันของเดือนถัดไป -->
                                            <?= $current_date - $days_in_month ?>
                                            <?php $current_date++; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- คำอธิบายสี -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: linear-gradient(135deg, #f59e0b, #d97706);"></div>
                    <span>รอรับ</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"></div>
                    <span>รับแล้ว</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"></div>
                    <span>กำลังทำ</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: linear-gradient(135deg, #ef4444, #dc2626);"></div>
                    <span>พักงาน</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: linear-gradient(135deg, #10b981, #059669);"></div>
                    <span>เสร็จแล้ว</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดงาน -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>รายละเอียดงาน
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 class="fw-bold">หัวข้องาน:</h6>
                        <p id="modalTitle" class="mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">รายละเอียด:</h6>
                        <p id="modalDescription" class="mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">ผู้ร้องขอ:</h6>
                        <p id="modalRequester" class="mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">สถานะ:</h6>
                        <p id="modalStatus" class="mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">วันที่รับงาน:</h6>
                        <p id="modalDate" class="mb-0"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showTaskDetail(task) {
            const statusLabels = {
                'pending': 'รอรับ',
                'received': 'รับแล้ว',
                'in_progress': 'กำลังทำ',
                'on_hold': 'พักงาน',
                'completed': 'เสร็จแล้ว'
            };

            document.getElementById('modalTitle').textContent = task.title;
            document.getElementById('modalDescription').textContent = task.description;
            document.getElementById('modalRequester').textContent = task.requester_name + ' ' + task.requester_lastname;
            document.getElementById('modalStatus').textContent = statusLabels[task.task_status];
            
            let dateText = new Date(task.accepted_at || task.created_at).toLocaleDateString('th-TH');
            if (task.estimated_days && task.accepted_at) {
                const startDate = new Date(task.accepted_at);
                const endDate = new Date(startDate.getTime() + (task.estimated_days - 1) * 24 * 60 * 60 * 1000);
                dateText += ' (ประมาณ ' + task.estimated_days + ' วัน, ควรเสร็จ: ' + endDate.toLocaleDateString('th-TH') + ')';
            }
            document.getElementById('modalDate').textContent = dateText;

            const modal = new bootstrap.Modal(document.getElementById('taskModal'));
            modal.show();
        }

        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>