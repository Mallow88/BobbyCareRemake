<?php
session_start();
require_once __DIR__ . '/../config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
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
        sr.estimated_days,
        sr.deadline,
        sr.created_at as request_created_at,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        dev.id as dev_id,
        requester.name as requester_name,
        requester.lastname as requester_lastname,
        requester.department as requester_department,
        s.name as service_name,
        s.category as service_category,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.reviewed_at as user_reviewed_at,
        aa.assignor_user_id,
        assignor.name as assignor_name,
        assignor.lastname as assignor_lastname,
        -- คำนวณเวลาที่ใช้
        CASE 
            WHEN t.started_at IS NOT NULL AND t.completed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at)
            WHEN t.started_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, t.started_at, NOW())
            ELSE 0
        END as hours_spent,
        -- สถานะความล่าช้า
        CASE 
            WHEN sr.deadline IS NOT NULL AND sr.deadline < CURDATE() AND t.task_status NOT IN ('completed', 'accepted')
            THEN 'overdue'
            WHEN sr.deadline IS NOT NULL AND DATEDIFF(sr.deadline, CURDATE()) <= 2 AND t.task_status NOT IN ('completed', 'accepted')
            THEN 'due_soon'
            ELSE 'on_time'
        END as deadline_status
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users dev ON t.developer_user_id = dev.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id
    WHERE 1=1 $dev_condition
    ORDER BY 
        CASE t.task_status 
            WHEN 'pending' THEN 1
            WHEN 'received' THEN 2
            WHEN 'in_progress' THEN 3
            WHEN 'on_hold' THEN 4
            WHEN 'completed' THEN 5
            WHEN 'accepted' THEN 6
            ELSE 7
        END,
        CASE sr.priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
            ELSE 5
        END,
        t.created_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มงานตาม developer
$dev_tasks = [];
$dev_stats = [];
foreach ($tasks as $task) {
    $dev_id = $task['dev_id'];
    if (!isset($dev_tasks[$dev_id])) {
        $dev_tasks[$dev_id] = [];
        $dev_stats[$dev_id] = [
            'name' => $task['dev_name'] . ' ' . $task['dev_lastname'],
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'total' => 0,
            'total_hours' => 0,
            'avg_rating' => 0,
            'total_ratings' => 0,
            'status' => 'ว่าง'
        ];
    }
    $dev_tasks[$dev_id][] = $task;
    $dev_stats[$dev_id]['total']++;
    $dev_stats[$dev_id]['total_hours'] += $task['hours_spent'];
    
    // นับสถานะ
    if (in_array($task['task_status'], ['pending', 'received'])) {
        $dev_stats[$dev_id]['pending']++;
    } elseif (in_array($task['task_status'], ['in_progress', 'on_hold'])) {
        $dev_stats[$dev_id]['in_progress']++;
    } elseif (in_array($task['task_status'], ['completed', 'accepted'])) {
        $dev_stats[$dev_id]['completed']++;
    }
    
    // นับงานที่เลยกำหนด
    if ($task['deadline_status'] === 'overdue') {
        $dev_stats[$dev_id]['overdue']++;
    }
    
    // คำนวณคะแนนเฉลี่ย
    if ($task['rating']) {
        $dev_stats[$dev_id]['avg_rating'] = (($dev_stats[$dev_id]['avg_rating'] * $dev_stats[$dev_id]['total_ratings']) + $task['rating']) / ($dev_stats[$dev_id]['total_ratings'] + 1);
        $dev_stats[$dev_id]['total_ratings']++;
    }
    
    // กำหนดสถานะ
    if ($dev_stats[$dev_id]['overdue'] > 0) {
        $dev_stats[$dev_id]['status'] = 'เลยกำหนด';
    } elseif ($dev_stats[$dev_id]['in_progress'] > 0) {
        $dev_stats[$dev_id]['status'] = 'ติดงาน';
    } elseif ($dev_stats[$dev_id]['pending'] > 0) {
        $dev_stats[$dev_id]['status'] = 'มีงานรอ';
    }
}

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>BobbyCareDev-Dashboard</title>
       <link rel="stylesheet" href="../css/nav.css">
    <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ffffffff 0%, #341355 100%);
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

        .dev-status-card.overdue {
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
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

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .task-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #6c757d;
            transition: all 0.3s ease;
        }

        .task-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .task-card.pending {
            border-left-color: #ffc107;
            background: #fff9e6;
        }

        .task-card.received {
            border-left-color: #17a2b8;
            background: #e6f7ff;
        }

        .task-card.in_progress {
            border-left-color: #6f42c1;
            background: #f3e8ff;
        }

        .task-card.on_hold {
            border-left-color: #fd7e14;
            background: #fff2e6;
        }

        .task-card.completed {
            border-left-color: #28a745;
            background: #e6f7e6;
        }

        .task-card.accepted {
            border-left-color: #20c997;
            background: #e6fff9;
        }

        .task-card.overdue {
            border-left-color: #dc3545;
            background: #ffe6e6;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }

        .priority-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-urgent {
            background: #dc3545;
            color: white;
            animation: blink 1s infinite;
        }

        .priority-high {
            background: #fd7e14;
            color: white;
        }

        .priority-medium {
            background: #ffc107;
            color: #000;
        }

        .priority-low {
            background: #28a745;
            color: white;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }

        .service-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
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
            max-height: 500px;
            overflow-y: auto;
        }

        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .deadline-warning {
            color: #dc3545;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        .deadline-soon {
            color: #fd7e14;
            font-weight: 600;
        }

        .rating-stars {
            color: #ffc107;
        }

        /* Mini Calendar Styles */
        .mini-calendar {
            font-size: 0.8rem;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            margin-bottom: 5px;
        }

        .day-header {
            text-align: center;
            font-weight: 600;
            color: #6c757d;
            padding: 5px 2px;
            font-size: 0.7rem;
        }

        .calendar-body {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            font-size: 0.7rem;
        }

        .calendar-day:hover {
            background: #e9ecef;
        }

        .calendar-day.today {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        .calendar-day.has-task {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }

        .calendar-day.has-overdue {
            background: #f8d7da;
            border: 1px solid #dc3545;
            animation: pulse 2s infinite;
        }

        .calendar-day.other-month {
            color: #adb5bd;
        }

        .task-indicator {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .task-indicator.pending { background: #ffc107; }
        .task-indicator.in-progress { background: #0d6efd; }
        .task-indicator.completed { background: #198754; }
        .task-indicator.overdue { background: #dc3545; }

        .calendar-legend {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .legend-dot.pending { background: #ffc107; }
        .legend-dot.in-progress { background: #0d6efd; }
        .legend-dot.completed { background: #198754; }
        .legend-dot.overdue { background: #dc3545; }

        .calendar-nav {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
          @media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
        text-align: center;
    }
    .container {
        padding: 1rem;
    }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .col-lg-3, .col-lg-6 {
                margin-bottom: 20px;
            }
            
            .calendar-legend {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
 <nav class="custom-navbar navbar navbar-expand-lg shadow-sm">
    <div class="container custom-navbar-container">
        <!-- โลโก้ + ชื่อระบบ (ฝั่งซ้าย) -->
        <a class="navbar-brand d-flex align-items-center custom-navbar-brand" href="index.php">
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
                        <a class="nav-link" href="view_requests.php"><i class="fas fa-tasks me-1"></i>ตรวจสอบคำขอ
                    </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approved_list.php"><i class="fas fa-chart-bar me-1"></i> รายการที่อนุมัติ</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="view_completed_tasks.php"><i class="fas fa-chart-bar me-1"></i>UserReviews</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="assignor_dashboard.php"><i class="fas fa-chart-bar me-1"></i>Dashboard_DEV</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="report.php"><i class="fas fa-chart-bar me-1"></i>Report</a>
                    </li>
            </ul>

            <!-- ขวา: ชื่อผู้ใช้ + ออกจากระบบ -->
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-center">
                <li class="nav-item d-flex align-items-center me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <span class="custom-navbar-title">ผู้จัดการเเผนกคุณ: <?= htmlspecialchars($_SESSION['name']) ?>!</span>
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
   
    <div class="container  pt-5">

        <div class="row">
            <!-- Developer Status -->
            <div class="col-lg-3">
                <div class="glass-card p-4 mb-4">
                    <!-- ตำแหน่ง Filter ด้านซ้าย -->

                    <!-- Filter & Stats Sidebar (Responsive Vertical Layout) -->
                    <div class="glass-card p-3 mb-4" style="max-width: 100%;">

                        <!-- Developer Selector -->
                        <div class="mb-3">
                            <label for="devSelect" class="form-label fw-bold small">
                                <i class="fas fa-user-cog me-2 text-primary"></i>เลือก Developer:
                            </label>
                            <select class="form-select form-select-sm" id="devSelect" onchange="filterDeveloper()">
                                <option value="all" <?= $selected_dev === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                                <?php foreach ($developers as $dev): ?>
                                    <option value="<?= $dev['id'] ?>" <?= $selected_dev == $dev['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Stats -->
                        <div>
                            <label class="form-label fw-bold small mb-2">
                                <i class="fas fa-chart-bar me-2 text-success"></i>สถิติรวม:
                            </label>

                            <div class="d-grid gap-2">
                                <!-- งานทั้งหมด -->
                                <button class="stat-card text-center bg-light p-2 rounded shadow-sm border-0 w-100"
                                    onclick="setStatusFilter('all')">
                                    <div class="fw-bold text-primary fs-6"><?= count($tasks) ?></div>
                                    <div class="small">งานทั้งหมด</div>
                                </button>
                                <button class="stat-card text-center bg-light p-2 rounded shadow-sm border-0 w-100"
                                    onclick="setStatusFilter('pending')">
                                    <div class="fw-bold text-warning fs-6"><?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'pending')) ?></div>
                                    <div class="small">รอดำเนินการ</div>
                                </button>
                                <button class="stat-card text-center bg-light p-2 rounded shadow-sm border-0 w-100"
                                    onclick="setStatusFilter('in_progress')">
                                    <div class="fw-bold text-info fs-6"><?= count(array_filter($tasks, fn($t) => in_array($t['task_status'], ['in_progress', 'on_hold']))) ?></div>
                                    <div class="small">กำลังทำ</div>
                                </button>
                                <button class="stat-card text-center bg-light p-2 rounded shadow-sm border-0 w-100"
                                    onclick="setStatusFilter('completed')">
                                    <div class="fw-bold text-success fs-6"><?= count(array_filter($tasks, fn($t) => in_array($t['task_status'], ['completed',]))) ?></div>
                                    <div class="small">เสร็จแล้ว</div>
                                </button>
                                <button class="stat-card text-center bg-light p-2 rounded shadow-sm border-0 w-100"
                                    onclick="setStatusFilter('accepted')">
                                    <div class="fw-bold text-danger fs-6"><?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'accepted')) ?></div>
                                    <div class="small">ปิดงาน</div>
                                </button>
                                <button class="stat-card text-center bg-light p-2 rounded shadow-sm border-0 w-100"
                                    onclick="setStatusFilter('overdue')">
                                    <div class="fw-bold text-danger fs-6"><?= count(array_filter($tasks, fn($t) => $t['deadline_status'] === 'overdue')) ?></div>
                                    <div class="small">เลยกำหนด</div>
                                </button>

                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Details -->
            <div class="col-lg-9">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold mb-0">
                            <i class="fas fa-tasks text-primary me-2"></i>
                            รายละเอียดงาน
                        </h3>
                        <div class="d-flex gap-2 flex-wrap">
                            <!-- Filters -->
                            <select class="form-select form-select-sm" id="dateFilter" onchange="filterTasks()" style="width: auto;">
                                <option value="all">ทั้งหมด</option>
                                <option value="today">วันนี้</option>
                                <option value="week">สัปดาห์นี้</option>
                                <option value="month">เดือนนี้</option>
                                <option value="year">ปีนี้</option>
                            </select>
                            <select class="form-select form-select-sm" id="statusFilter" onchange="filterTasks()" style="width: auto;">
                                <option value="all">ทุกสถานะ</option>
                                <option value="pending">รอรับ</option>
                                <option value="in_progress">กำลังทำ</option>
                                <option value="completed">เสร็จแล้ว</option>
                                <option value="accepted">ปิดงาน</option>
                                <option value="overdue">เลยกำหนด</option>
                            </select>
                            <input type="text" class="form-control form-control-sm" id="searchInput" onkeyup="filterTasks()" placeholder="ค้นหางาน..." style="width: 200px;">

                            <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                                <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                            </button>
                        </div>
                    </div>
                    <?php if (empty($tasks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">ไม่มีงานในระบบ</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                         <th>ลำดับ</th>
                                        <th>เลขที่เอกสาร</th>
                                        <th>งาน</th>
                                        <th>ประเภท</th>
                                        <th>Developer</th>
                                        <th>สถานะ</th>
                                        <th>ความสำคัญ</th>
                                        <th>รายละเอียด</th>
                                        <th>วันที่ขอบริการ</th>
                                        <th>ประมาณการเสร็จ</th>
                                        <th>กำหนดส่ง</th>
                                        <th>ความคืบหน้า</th>
                                        <th>คะแนน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>

                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div id="pagination-info">Showing 1 to 5 of 10 items</div>
                        <div>
                            <button id="prev-btn" class="btn btn-sm btn-outline-primary" disabled>Previous</button>
                            <button id="next-btn" class="btn btn-sm btn-outline-primary">Next</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // โหลดงานจาก PHP
            const taskData = <?= json_encode($tasks) ?>;

            // กรอง developer
            function filterDeveloper() {
                const devId = document.getElementById('devSelect').value;
                window.location.href = `?dev_id=${devId}`;
            }

            // รีโหลดหน้า
            function refreshData() {
                location.reload();
            }

            // ฟังก์ชันกรองงานตามสถานะเท่านั้น 
            function filterTasksByStatus() {
                const statusFilter = document.getElementById('statusFilter').value;
                const taskCards = document.querySelectorAll('.task-card');

                taskCards.forEach(card => {
                    let showCard = true;

                    if (statusFilter !== 'all') {
                        const taskStatus = card.dataset.status;
                        const deadlineStatus = card.dataset.deadlineStatus;

                        switch (statusFilter) {
                            case 'pending':
                                showCard = ['pending', 'received'].includes(taskStatus);
                                break;
                            case 'in_progress':
                                showCard = ['in_progress', 'on_hold'].includes(taskStatus);
                                break;
                            case 'completed':
                                showCard = ['completed', 'accepted'].includes(taskStatus);
                                break;
                            case 'overdue':
                                showCard = deadlineStatus === 'overdue';
                                break;
                        }
                    }

                    card.style.display = showCard ? 'block' : 'none';
                });
            }

            // เมื่อโหลดหน้าให้ใส่ข้อมูลลง dataset
            document.addEventListener('DOMContentLoaded', function() {
                const taskCards = document.querySelectorAll('.task-card');
                taskCards.forEach(card => {
                    const taskId = card.querySelector('[data-task-id]')?.dataset.taskId;
                    if (taskId) {
                        const task = taskData.find(t => t.id == taskId);
                        if (task) {
                            card.dataset.status = task.task_status;
                            card.dataset.deadlineStatus = task.deadline_status;
                        }
                    }
                });

                // เริ่มด้วยการกรองทั้งหมด
                filterDevTasks('all');
            });

            // แสดงรายการแบบตาราง (อีกรูปแบบ)
            function renderTasks(tasks) {
                const tbody = document.getElementById("taskBody");
                tbody.innerHTML = "";

                if (tasks.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">ไม่พบงาน</td></tr>`;
                    return;
                }

                for (const task of tasks) {
                    const row = `
                <tr>
                    <td>${task.title ?? '(ไม่มีชื่อ)'}</td>
                    <td>${task.task_status}</td>
                    <td>${task.due_date ?? '-'}</td>
                </tr>
            `;
                    tbody.insertAdjacentHTML("beforeend", row);
                }
            }

            // ฟิลเตอร์งาน (ใช้กับตาราง)
            function filterDevTasks(filterType) {
                let filtered = [];

                if (filterType === 'all') {
                    filtered = taskData;
                } else if (filterType === 'pending') {
                    // รวม 'pending' และ 'received'
                    filtered = taskData.filter(t => ['pending', 'received'].includes(t.task_status));
                } else if (filterType === 'overdue') {
                    filtered = taskData.filter(t => t.deadline_status === 'overdue');
                } else if (filterType === 'in_progress') {
                    filtered = taskData.filter(t => t.task_status === 'in_progress');
                } else if (filterType === 'completed') {
                    filtered = taskData.filter(t => t.task_status === 'completed');
                } else if (filterType === 'accepted') {
                    filtered = taskData.filter(t => t.task_status === 'accepted');
                } else {
                    // กรณีสถานะอื่นๆที่ไม่รู้จัก ให้กรองออกหมด
                    filtered = [];
                }

                renderTasks(filtered);
            }


            function setStatusFilter(value) {
                const statusSelect = document.getElementById("statusFilter");
                if (statusSelect) {
                    statusSelect.value = value;
                    filterTasks(); // เรียกของเดิม
                }
            }

            //ช่องค้นหา
            function filterTasks() {
                const dateFilter = document.getElementById('dateFilter').value;
                const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
                const searchInput = document.getElementById('searchInput').value.toLowerCase();

                const rows = document.querySelectorAll("table tbody tr");
                const today = new Date();

                rows.forEach(row => {
                    const statusCell = row.querySelector('.status-cell');
                    const dateCell = row.querySelector('.date-cell');

                    const rowStatus = statusCell ? statusCell.dataset.status.toLowerCase().trim() : "";
                    const rowDateText = dateCell ? dateCell.innerText.trim() : "";
                    const rowText = row.innerText.toLowerCase();

                    // กรองสถานะ
                    const matchStatus = (statusFilter === "all") || rowStatus === statusFilter;

                    // กรองคำค้นหา
                    const matchSearch = rowText.includes(searchInput);

                    // กรองวันที่
                    let matchDate = true;
                    if (dateFilter !== "all" && rowDateText) {
                        const rowDate = new Date(rowDateText);
                        switch (dateFilter) {
                            case "today":
                                matchDate = rowDate.toDateString() === today.toDateString();
                                break;
                            case "week":
                                const startOfWeek = new Date(today);
                                startOfWeek.setDate(today.getDate() - today.getDay());
                                const endOfWeek = new Date(startOfWeek);
                                endOfWeek.setDate(startOfWeek.getDate() + 6);
                                matchDate = rowDate >= startOfWeek && rowDate <= endOfWeek;
                                break;
                            case "month":
                                matchDate = rowDate.getMonth() === today.getMonth() &&
                                    rowDate.getFullYear() === today.getFullYear();
                                break;
                            case "year":
                                matchDate = rowDate.getFullYear() === today.getFullYear();
                                break;
                        }
                    }

                    // เงื่อนไขรวม
                    if (matchStatus && matchSearch && matchDate) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            }
        </script>


        <script>
            // สมมติ tasks มาจาก PHP
            const tasks = <?= json_encode($tasks) ?>;
            const itemsPerPage = 5;
            let currentPage = 1;

            function renderTablePage(page) {
                const tbody = document.querySelector('table tbody');
                tbody.innerHTML = '';

                const startIndex = (page - 1) * itemsPerPage;
                const endIndex = Math.min(startIndex + itemsPerPage, tasks.length);

                for (let i = startIndex; i < endIndex; i++) {
                    const task = tasks[i];
                    // สร้าง <tr>... ตามโครงสร้างของ PHP (แปลงข้อมูล task เป็น html row)
                    const tr = document.createElement('tr');
                    // ตัวอย่างทำแค่บางช่องนะ ต้องแปลงเหมือน PHP ของคุณ
                    tr.className = task.deadline_status === 'overdue' ? 'table-danger' : (task.deadline_status === 'due_soon' ? 'table-warning' : '');

                    tr.innerHTML = `

            <td><div class="fw-bold">${i + 1}</div></td>


     <td><div class="fw-bold">${task.document_number}</div></td>



        <td>
          <div class="fw-bold">${task.title}</div>
          <div> <span class="service-badge service-${task.service_category}">${task.service_name}</span></div>
          <small class="text-muted">ผู้ขอ: ${task.requester_name} ${task.requester_lastname}</small>
          
        </td>

       <td>
  ${task.service_name
    ? `
       <div><span class="service-badge service-${task.service_category}">${task.service_category.toUpperCase()}</span></div>`
    : ''}
</td>


        

        <td>
          <div class="fw-bold">${task.dev_name} ${task.dev_lastname}</div>
          ${task.assignor_name ? `<small class="text-muted">มอบหมายโดย: ${task.assignor_name}</small>` : ''}
        </td>

        <td class="status-cell" data-status="${task.task_status}">
  <span class="badge bg-${{
    'pending':'warning',
    'received':'info',
    'in_progress':'primary',
    'on_hold':'secondary',
    'completed':'success',
    'accepted':'success'
  }[task.task_status] || 'secondary'}">
    ${{
      'pending':'รอรับ',
      'received':'รับแล้ว',
      'in_progress':'กำลังทำ',
      'on_hold':'พักงาน',
      'completed':'เสร็จแล้ว',
      'accepted':'ปิดงานเเล้ว'
    }[task.task_status] || task.task_status}
  </span>
</td>


        <td><span class="priority-badge priority-${task.priority}">${task.priority.toUpperCase()}</span></td>

        <td style="white-space: normal; word-break: break-word;">
  <span class="priority-badge priority-${task.description}">
    ${task.description.toUpperCase()}
  </span>
</td>

        <td><span class="priority-badge priority-${task.created_at}">${task.created_at.toUpperCase()}</span></td>

        <td>
          ${task.hours_spent > 0 ? `<div class="fw-bold">${task.hours_spent.toFixed(1)} ชม.</div>` : ''}
          ${task.estimated_days ? `<small class="text-muted">ประมาณ: ${task.estimated_days} วัน</small>` : ''}
        </td>

      <td class="date-cell" data-date="${task.deadline || ''}">
  ${task.deadline ? `<div class="${task.deadline_status === 'overdue' ? 'deadline-warning' : (task.deadline_status === 'due_soon' ? 'deadline-soon' : '')}">
    ${new Date(task.deadline).toLocaleDateString('th-TH')} 
    ${task.deadline_status === 'overdue' ? '<i class="fas fa-exclamation-triangle ms-1"></i>' : task.deadline_status === 'due_soon' ? '<i class="fas fa-clock ms-1"></i>' : ''}
  </div>` : '<span class="text-muted">ไม่กำหนด</span>'}
</td>


        <td>
          ${task.progress_percentage > 0 ? `<div class="progress" style="height: 8px;">
            <div class="progress-bar bg-info" style="width: ${task.progress_percentage}%;"></div>
          </div><small>${task.progress_percentage}%</small>` : '<span class="text-muted">-</span>'}
        </td>

        <td>
          ${task.rating ? `<div class="rating-stars">${'★'.repeat(task.rating)}</div><small class="text-muted">${task.rating}/5</small>` : '<span class="text-muted">ยังไม่รีวิว</span>'}
        </td>

      `;

                    tbody.appendChild(tr);
                }

                document.getElementById('pagination-info').innerText = `Showing ${startIndex + 1} to ${endIndex} of ${tasks.length} items`;

                // ปิดปุ่ม prev ถ้าอยู่หน้าแรก, ปิดปุ่ม next ถ้าอยู่หน้าสุดท้าย
                document.getElementById('prev-btn').disabled = page === 1;
                document.getElementById('next-btn').disabled = endIndex === tasks.length;
            }

            document.getElementById('prev-btn').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTablePage(currentPage);
                }
            });

            document.getElementById('next-btn').addEventListener('click', () => {
                if (currentPage * itemsPerPage < tasks.length) {
                    currentPage++;
                    renderTablePage(currentPage);
                }
            });

            // แสดงหน้าแรกตอนโหลด
            renderTablePage(currentPage);
        </script>


</body>

</html>