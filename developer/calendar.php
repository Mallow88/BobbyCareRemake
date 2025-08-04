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
        sr.priority,
        sr.deadline,
        sr.current_step,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        aa.estimated_days,
        s.name as service_name,
        s.category as service_category
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN services s ON sr.service_id = s.id
    WHERE t.developer_user_id = ?
    ORDER BY 
        CASE sr.priority 
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
            ELSE 5
        END,
        t.created_at DESC
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
            position: relative;
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
        
        .task-item.task-start {
            border-radius: 6px 0 0 6px;
            margin-right: 0;
        }
        
        .task-item.task-middle {
            border-radius: 0;
            margin: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3));
            color: #4a5568;
            font-size: 0.6rem;
            padding: 1px 3px;
        }
        
        .task-item.task-end {
            border-radius: 0 6px 6px 0;
            margin-left: 0;
        }
        
        .task-item.note-task {
            background: linear-gradient(135deg, #9f7aea, #805ad5);
            border-left: 3px solid #6b46c1;
        }

        /* Priority levels - เรียงลำดับตามความสำคัญ */
        .task-item.priority-urgent {
            z-index: 4;
            border: 2px solid #dc2626;
            animation: pulse-urgent 2s infinite;
        }

        .task-item.priority-high {
            z-index: 3;
            border: 1px solid #ef4444;
        }

        .task-item.priority-medium {
            z-index: 2;
        }

        .task-item.priority-low {
            z-index: 1;
            opacity: 0.8;
        }

        @keyframes pulse-urgent {
            0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }

        .priority-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .priority-urgent .priority-badge {
            background: #dc2626;
            color: white;
        }

        .priority-high .priority-badge {
            background: #ef4444;
            color: white;
        }

        .priority-medium .priority-badge {
            background: #f59e0b;
            color: white;
        }

        .priority-low .priority-badge {
            background: #10b981;
            color: white;
        }
        
        .add-note-btn {
            background: rgba(102, 126, 234, 0.1);
            border: 1px dashed #667eea;
            color: #667eea;
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 0.6rem;
            margin-top: 2px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .add-note-btn:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: scale(1.05);
        }

        .note-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 20px;
            height: 20px;
            background: rgba(102, 126, 234, 0.8);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.7;
        }
        
        .note-btn:hover {
            opacity: 1;
            transform: scale(1.1);
            background: rgba(102, 126, 234, 1);
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

        .service-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: inline-block;
        }

        .service-development {
            background: #c6f6d5;
            color: #2f855a;
        }

        .service-service {
            background: #dbeafe;
            color: #1e40af;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
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
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">

        <div class="container">
            <!-- โลโก้ + ชื่อระบบ -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dev_index.php">
                <img src="../img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
                <span class="page-title">Calendar</span>
            </a>


            <!-- ปุ่ม toggle สำหรับ mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- เมนู -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- ซ้าย: เมนูหลัก -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> หน้าหลัก</a>
                    </li> -->
                     <li class="nav-item">
                        <a class="nav-link" href="tasks_board.php">
                            <i class="fas fa-clipboard-list me-1"></i>
                            บอร์ดงาน
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calendar.php"><i class="fas fa-tasks me-1"></i> ปฏิทิน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="completed_reviews.php"><i class="fas fa-chart-bar me-1"></i> งานที่รีวิว</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="export_report.php"><i class="fas fa-chart-bar me-1"></i>Report</a>
                    </li>
                </ul>

                <!-- ขวา: ผู้ใช้งาน -->
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item d-flex align-items-center text-dark me-3">
                        <i class="fas fa-user-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['name']) ?>
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

            foreach ($status_counts as $status => $count):
            ?>
                <div class="stat-card <?= $status ?>">
                    <div class="stat-number"><?= $count ?></div>
                    <div class="stat-label"><?= $status_labels[$status] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

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
                                        
                                        // เรียงงานตามความสำคัญ
                                        $cell_tasks = [];
                                        foreach ($tasks as $task) {
                                            $start_date = date('Y-m-d', strtotime($task['started_at'] ?? $task['created_at']));
                                            $estimated_days = $task['estimated_days'] ?? 1;
                                            $end_date = date('Y-m-d', strtotime($start_date . ' + ' . ($estimated_days - 1) . ' days'));
                                            
                                            if ($cell_date >= $start_date && $cell_date <= $end_date) {
                                                $cell_tasks[] = $task;
                                            }
                                        }
                                        
                                        // เรียงตามความสำคัญ
                                        usort($cell_tasks, function($a, $b) {
                                            $priority_order = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
                                            $a_priority = $priority_order[$a['priority']] ?? 5;
                                            $b_priority = $priority_order[$b['priority']] ?? 5;
                                            return $a_priority - $b_priority;
                                        });
                                        
                                        // แสดงงานที่เรียงแล้ว
                                        foreach ($cell_tasks as $task):
                                            $start_date = date('Y-m-d', strtotime($task['started_at'] ?? $task['created_at']));
                                            $estimated_days = $task['estimated_days'] ?? 1;
                                            $end_date = date('Y-m-d', strtotime($start_date . ' + ' . ($estimated_days - 1) . ' days'));
                                            
                                            $is_start = ($cell_date === $start_date);
                                            $is_end = ($cell_date === $end_date);
                                            $is_middle = (!$is_start && !$is_end);
                                        ?>
                                            <div class="task-item <?= $task['task_status'] ?> priority-<?= $task['priority'] ?> <?= $is_start ? 'task-start' : ($is_end ? 'task-end' : 'task-middle') ?>" 
                                                 onclick="showTaskDetail(<?= htmlspecialchars(json_encode($task)) ?>)"
                                                 title="<?= htmlspecialchars($task['title']) ?> (<?= $estimated_days ?> วัน)">
                                                <div class="priority-badge">
                                                    <?php
                                                    switch($task['priority']) {
                                                        case 'urgent': echo '!'; break;
                                                        case 'high': echo '↑'; break;
                                                        case 'medium': echo '→'; break;
                                                        case 'low': echo '↓'; break;
                                                        default: echo '•'; break;
                                                    }
                                                    ?>
                                                </div>
                                                <?php if ($is_start): ?>
                                                    <i class="fas fa-play me-1"></i>เริ่ม: <?= htmlspecialchars(mb_substr($task['title'], 0, 10)) ?>
                                                <?php elseif ($is_end): ?>
                                                    <i class="fas fa-flag-checkered me-1"></i>เสร็จ: <?= htmlspecialchars(mb_substr($task['title'], 0, 10)) ?>
                                                <?php else: ?>
                                                    <div style="text-align: center; font-weight: bold;">▬▬▬</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php
                                        // แสดงงานที่สร้างในวันนี้ (Note)
                                        if (isset($tasks_by_date[$cell_date])):
                                            foreach ($tasks_by_date[$cell_date] as $note_task):
                                                if ($note_task['current_step'] === 'developer_self_created'):
                                        ?>
                                                    <div class="task-item note-task priority-<?= $note_task['priority'] ?>" 
                                                         onclick="showTaskDetail(<?= htmlspecialchars(json_encode($note_task)) ?>)"
                                                         title="งานส่วนตัว: <?= htmlspecialchars($note_task['title']) ?>">
                                                        <div class="priority-badge">
                                                            <?php
                                                            switch($note_task['priority']) {
                                                                case 'urgent': echo '!'; break;
                                                                case 'high': echo '↑'; break;
                                                                case 'medium': echo '→'; break;
                                                                case 'low': echo '↓'; break;
                                                                default: echo '•'; break;
                                                            }
                                                            ?>
                                                        </div>
                                                        <i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars(mb_substr($note_task['title'], 0, 12)) ?>
                                                    </div>
                                        <?php 
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                        
                                        <!-- ปุ่มสร้าง Note ในวันนี้ -->
                                        <div class="add-note-btn" onclick="createNoteForDate('<?= $cell_date ?>')" title="สร้างงานในวันนี้">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                        <?php
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

        <!-- คำอธิบายความสำคัญ -->
        <div class="legend mt-3">
            <div class="legend-item">
                <span style="color: #dc2626; font-weight: bold;">!</span>
                <span>เร่งด่วน</span>
            </div>
            <div class="legend-item">
                <span style="color: #ef4444; font-weight: bold;">↑</span>
                <span>สูง</span>
            </div>
            <div class="legend-item">
                <span style="color: #f59e0b; font-weight: bold;">→</span>
                <span>ปานกลาง</span>
            </div>
            <div class="legend-item">
                <span style="color: #10b981; font-weight: bold;">↓</span>
                <span>ต่ำ</span>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดงาน -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>รายละเอียดงาน
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">หัวข้องาน:</h6>
                                <p id="modalTitle" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">รายละเอียด:</h6>
                                <div id="modalDescription" class="bg-light p-3 rounded"></div>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">ผู้ร้องขอ:</h6>
                                <p id="modalRequester" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">สถานะ:</h6>
                                <p id="modalStatus" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">ความสำคัญ:</h6>
                                <p id="modalPriority" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">เวลาแทน:</h6>
                                <p id="modalEstimatedTime" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">วันที่รับงาน:</h6>
                                <p id="modalDate" class="mb-0"></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">ประเภทบริการ:</h6>
                                <p id="modalService" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">กำหนดเสร็จ:</h6>
                                <p id="modalDeadline" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">ความคืบหน้า:</h6>
                                <div class="progress mb-2">
                                    <div id="modalProgress" class="progress-bar" role="progressbar"></div>
                                </div>
                                <small id="modalProgressText" class="text-muted"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <a id="modalViewBoard" href="#" class="btn btn-gradient">
                        <i class="fas fa-external-link-alt me-2"></i>ดูในบอร์ดงาน
                    </a>
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

            const priorityLabels = {
                'urgent': 'เร่งด่วน',
                'high': 'สูง',
                'medium': 'ปานกลาง',
                'low': 'ต่ำ'
            };

            document.getElementById('modalTitle').textContent = task.title;
            document.getElementById('modalDescription').innerHTML = task.description.replace(/\n/g, '<br>');
            document.getElementById('modalRequester').textContent = task.requester_name + ' ' + task.requester_lastname;
            document.getElementById('modalStatus').textContent = statusLabels[task.task_status];
            document.getElementById('modalPriority').textContent = priorityLabels[task.priority] || 'ปานกลาง';
            
            // แสดงเวลาแทนแทนประมาณการ (วัน)
            let timeText = 'ไม่ระบุ';
            if (task.estimated_days) {
                const hours = task.estimated_days * 8; // แปลงวันเป็นชั่วโมง (8 ชั่วโมงต่อวัน)
                timeText = hours + ' ชั่วโมง (' + task.estimated_days + ' วัน)';
            }
            document.getElementById('modalEstimatedTime').textContent = timeText;
            
            let dateText = new Date(task.accepted_at || task.created_at).toLocaleDateString('th-TH');
            document.getElementById('modalDate').textContent = dateText;

            // แสดงประเภทบริการ
            document.getElementById('modalService').textContent = task.service_name || 'ไม่ระบุ';

            // แสดงกำหนดเสร็จ
            document.getElementById('modalDeadline').textContent = task.deadline ? 
                new Date(task.deadline).toLocaleDateString('th-TH') : 'ไม่กำหนด';

            // แสดงความคืบหน้า
            const progress = task.progress_percentage || 0;
            const progressBar = document.getElementById('modalProgress');
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            
            // เปลี่ยนสีตามสถานะ
            progressBar.className = 'progress-bar';
            if (progress >= 100) {
                progressBar.classList.add('bg-success');
            } else if (progress >= 50) {
                progressBar.classList.add('bg-info');
            } else if (progress >= 25) {
                progressBar.classList.add('bg-warning');
            } else {
                progressBar.classList.add('bg-danger');
            }

            document.getElementById('modalProgressText').textContent = 
                'อัปเดตล่าสุด: ' + new Date(task.updated_at).toLocaleDateString('th-TH');

            // ลิงก์ไปยังบอร์ดงาน
            document.getElementById('modalViewBoard').href = 'tasks_board.php#task-' + task.id;
 
            const modal = new bootstrap.Modal(document.getElementById('taskModal'));
            modal.show();
        }
        
        function createNoteForDate(date) {
            const thaiDate = new Date(date).toLocaleDateString('th-TH');
            // เปิดหน้าบอร์ดงานพร้อมกำหนดวันที่ (ไม่ต้องยืนยัน)
            const url = `tasks_board.php?create_date=${date}`;
            window.location.href = url;
        }
        
        function createNote(date) {
            window.location.href = `tasks_board.php?create_date=${date}`;
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