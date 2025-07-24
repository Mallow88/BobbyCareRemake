<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

// ดึงรายชื่อ developers
$dev_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'developer' AND is_active = 1 ORDER BY name");
$dev_stmt->execute();
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลงานของ developers
$selected_dev = $_GET['dev_id'] ?? 'all';
$current_month = $_GET['month'] ?? date('n');
$current_year = $_GET['year'] ?? date('Y');

$dev_condition = $selected_dev !== 'all' ? "AND t.developer_user_id = ?" : "";
$params = $selected_dev !== 'all' ? [$selected_dev] : [];

$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.priority,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        dev.id as dev_id,
        requester.name as requester_name,
        requester.lastname as requester_lastname,
        ur.rating,
        ur.review_comment,
        ur.status as review_status
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users dev ON t.developer_user_id = dev.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    WHERE 1=1 $dev_condition
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มงานตาม developer
$dev_tasks = [];
$dev_status = [];
foreach ($tasks as $task) {
    $dev_id = $task['dev_id'];
    if (!isset($dev_tasks[$dev_id])) {
        $dev_tasks[$dev_id] = [];
        $dev_status[$dev_id] = [
            'name' => $task['dev_name'] . ' ' . $task['dev_lastname'],
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'total' => 0,
            'status' => 'ว่าง'
        ];
    }
    $dev_tasks[$dev_id][] = $task;
    $dev_status[$dev_id]['total']++;
    
    if (in_array($task['task_status'], ['pending', 'received'])) {
        $dev_status[$dev_id]['pending']++;
    } elseif (in_array($task['task_status'], ['in_progress', 'on_hold'])) {
        $dev_status[$dev_id]['in_progress']++;
    } elseif (in_array($task['task_status'], ['completed', 'accepted'])) {
        $dev_status[$dev_id]['completed']++;
    }
    
    // กำหนดสถานะ
    if ($dev_status[$dev_id]['in_progress'] > 0) {
        $dev_status[$dev_id]['status'] = 'ติดงาน';
    } elseif ($dev_status[$dev_id]['pending'] > 0) {
        $dev_status[$dev_id]['status'] = 'มีงานรอ';
    }
}

// สร้างข้อมูลปฏิทิน
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day);

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$thai_days = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

// จัดกลุ่มงานตามวันที่
$tasks_by_date = [];
foreach ($tasks as $task) {
    $date = date('Y-m-d', strtotime($task['started_at'] ?? $task['created_at']));
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
    <title>Developer Dashboard - GM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #a8b5ebff 0%, #ffffffff 100%); 
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

        .dev-status-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #6c757d;
        }

        .dev-status-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .dev-status-card.available {
            border-left-color: #28a745;
        }

        .dev-status-card.busy {
            border-left-color: #dc3545;
        }

        .dev-status-card.pending {
            border-left-color: #ffc107;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-busy {
            background: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
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
            padding: 10px 5px;
            vertical-align: top;
            height: 100px;
            width: 14.28%;
        }

        .calendar-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #495057;
            font-weight: 600;
            text-align: center;
            height: 40px;
        }

        .date-number {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .today {
            background: linear-gradient(135deg, #e6fffa, #b2f5ea) !important;
            border: 2px solid #38b2ac;
        }

        .task-item {
            background: #667eea;
            color: white;
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 0.6rem;
            margin-bottom: 2px;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .task-item.pending {
            background: #ffc107;
            color: #000;
        }

        .task-item.in_progress {
            background: #dc3545;
        }

        .task-item.completed {
            background: #28a745;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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

        .task-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .task-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            border-left: 4px solid #6c757d;
        }

        .task-card.pending {
            border-left-color: #ffc107;
        }

        .task-card.in_progress {
            border-left-color: #dc3545;
        }

        .task-card.completed {
            border-left-color: #28a745;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .calendar-table th,
            .calendar-table td {
                height: 80px;
                padding: 5px 2px;
            }

            .task-item {
                font-size: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-chart-line text-primary me-2"></i>
                <span class="page-title">GM Dashboard</span>
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
        <!-- Header -->
        <div class="glass-card p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="page-title mb-2">
                        <i class="fas fa-users-cog me-3"></i>Developer Dashboard
                    </h1>
                    <p class="text-muted mb-0 fs-5">ติดตามสถานะและงานของ Developer แต่ละคน</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                        <a href="gmindex.php" class="btn btn-gradient">
                            <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                        </a>
                        <a href="view_completed_tasks.php" class="btn btn-gradient">
                            <i class="fas fa-tasks me-2"></i>งานที่เสร็จ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="glass-card p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label for="devSelect" class="form-label fw-bold">เลือก Developer:</label>
                    <select class="form-select" id="devSelect" onchange="filterDeveloper()">
                        <option value="all" <?= $selected_dev === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <?php foreach ($developers as $dev): ?>
                            <option value="<?= $dev['id'] ?>" <?= $selected_dev == $dev['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">เดือน/ปี:</label>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="monthSelect" onchange="changeMonth()">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $current_month == $i ? 'selected' : '' ?>>
                                    <?= $thai_months[$i] ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-select" id="yearSelect" onchange="changeMonth()">
                            <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $current_year == $y ? 'selected' : '' ?>>
                                    <?= $y + 543 ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Developer Status -->
            <div class="col-lg-4">
                <div class="glass-card p-4 mb-4">
                    <h3 class="fw-bold mb-3">
                        <i class="fas fa-users text-primary me-2"></i>สถานะ Developer
                    </h3>
                    
                    <?php if ($selected_dev === 'all'): ?>
                        <?php foreach ($developers as $dev): ?>
                            <?php 
                            $status = $dev_status[$dev['id']] ?? [
                                'name' => $dev['name'] . ' ' . $dev['lastname'],
                                'pending' => 0,
                                'in_progress' => 0,
                                'completed' => 0,
                                'total' => 0,
                                'status' => 'ว่าง'
                            ];
                            $status_class = $status['status'] === 'ว่าง' ? 'available' : 
                                          ($status['status'] === 'ติดงาน' ? 'busy' : 'pending');
                            ?>
                            <div class="dev-status-card <?= $status_class ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($status['name']) ?></h6>
                                    <span class="status-badge status-<?= $status_class ?>">
                                        <?= $status['status'] ?>
                                    </span>
                                </div>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted">รอรับ</small>
                                        <div class="fw-bold text-warning"><?= $status['pending'] ?></div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">กำลังทำ</small>
                                        <div class="fw-bold text-danger"><?= $status['in_progress'] ?></div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">เสร็จแล้ว</small>
                                        <div class="fw-bold text-success"><?= $status['completed'] ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php 
                        $selected_dev_data = array_filter($developers, function($dev) use ($selected_dev) {
                            return $dev['id'] == $selected_dev;
                        });
                        $selected_dev_data = reset($selected_dev_data);
                        $status = $dev_status[$selected_dev] ?? [
                            'name' => $selected_dev_data['name'] . ' ' . $selected_dev_data['lastname'],
                            'pending' => 0,
                            'in_progress' => 0,
                            'completed' => 0,
                            'total' => 0,
                            'status' => 'ว่าง'
                        ];
                        ?>
                        <div class="dev-status-card <?= $status['status'] === 'ว่าง' ? 'available' : 'busy' ?>">
                            <h5 class="fw-bold mb-3"><?= htmlspecialchars($status['name']) ?></h5>
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="fw-bold text-warning fs-3"><?= $status['pending'] ?></div>
                                    <small class="text-muted">รอรับ</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-danger fs-3"><?= $status['in_progress'] ?></div>
                                    <small class="text-muted">กำลังทำ</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-success fs-3"><?= $status['completed'] ?></div>
                                    <small class="text-muted">เสร็จแล้ว</small>
                                </div>
                            </div>
                            
                            <h6 class="fw-bold mb-2">รายการงาน:</h6>
                            <div class="task-list">
                                <?php if (isset($dev_tasks[$selected_dev])): ?>
                                    <?php foreach ($dev_tasks[$selected_dev] as $task): ?>
                                        <div class="task-card <?= $task['task_status'] ?>">
                                            <div class="fw-bold"><?= htmlspecialchars($task['title']) ?></div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($task['requester_name']) ?> | 
                                                <?= date('d/m/Y', strtotime($task['created_at'])) ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>ไม่มีงาน</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar -->
            <div class="col-lg-8">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold mb-0">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            ปฏิทินงาน <?= $thai_months[$current_month] ?> <?= $current_year + 543 ?>
                        </h3>
                        <div class="d-flex gap-2">
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
                            <a href="?dev_id=<?= $selected_dev ?>&month=<?= $prev_month ?>&year=<?= $prev_year ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <a href="?dev_id=<?= $selected_dev ?>&month=<?= $next_month ?>&year=<?= $next_year ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table calendar-table mb-0">
                            <thead>
                                <tr>
                                    <?php foreach ($thai_days as $day): ?>
                                        <th class="text-center"><?= $day ?></th>
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
                                                    echo 'text-muted';
                                                } elseif ($current_date > $days_in_month) {
                                                    echo 'text-muted';
                                                } else {
                                                    $cell_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $current_date);
                                                    if ($cell_date == $today) {
                                                        echo 'today';
                                                    }
                                                }
                                            ?>">
                                                <?php if ($week == 0 && $day < $start_day): ?>
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
                                                             title="<?= htmlspecialchars($task['title']) ?> - <?= htmlspecialchars($task['dev_name']) ?>">
                                                            <?= htmlspecialchars(mb_substr($task['title'], 0, 10)) ?>
                                                        </div>
                                                    <?php 
                                                        endforeach;
                                                    endif;
                                                    $current_date++;
                                                    ?>
                                                <?php else: ?>
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

                    <!-- Legend -->
                    <div class="d-flex justify-content-center gap-4 mt-3">
                        <div class="d-flex align-items-center">
                            <div class="task-item pending me-2" style="width: 20px; height: 15px;"></div>
                            <small>รอรับ/รับแล้ว</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="task-item in_progress me-2" style="width: 20px; height: 15px;"></div>
                            <small>กำลังทำ</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="task-item completed me-2" style="width: 20px; height: 15px;"></div>
                            <small>เสร็จแล้ว</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterDeveloper() {
            const devId = document.getElementById('devSelect').value;
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;
            window.location.href = `?dev_id=${devId}&month=${month}&year=${year}`;
        }

        function changeMonth() {
            const devId = document.getElementById('devSelect').value;
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;
            window.location.href = `?dev_id=${devId}&month=${month}&year=${year}`;
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