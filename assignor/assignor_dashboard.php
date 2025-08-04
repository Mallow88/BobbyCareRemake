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
    <title>Developer Dashboard - GM</title>
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
    <!-- Navigation -->
     <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">

        <div class="container">
            <!-- โลโก้ + ชื่อระบบ -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <img src="../img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
                <span class="page-title"> ผู้จัดการแผนก, <?= htmlspecialchars($_SESSION['name']) ?>! </span>
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
                </ul>
                <!-- ขวา: ผู้ใช้งาน -->
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <!-- <li class="nav-item d-flex align-items-center text-dark me-3">
                        <i class="fas fa-user-circle me-2"></i>
                      
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<br><br><br>
    <div class="container mt-5 pt-5">
       
        <!-- Filter -->
       <div class="glass-card p-4 mb-3">
    <div class="row g-4 align-items-center">
        <!-- Developer Selector -->
        <div class="col-lg-4 col-md-6">
            <label for="devSelect" class="form-label fw-bold">
                <i class="fas fa-user-cog me-2 text-primary"></i>เลือก Developer:
            </label>
            <select class="form-select" id="devSelect" onchange="filterDeveloper()">
                <option value="all" <?= $selected_dev === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                <?php foreach ($developers as $dev): ?>
                    <option value="<?= $dev['id'] ?>" <?= $selected_dev == $dev['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Stats Section -->
        <div class="col-lg-8 col-md-6">
            <label class="form-label fw-bold">
                <i class="fas fa-chart-bar me-2 text-success"></i>สถิติรวม:
            </label>
            <div class="row row-cols-2 row-cols-md-3 g-2">
                <div class="col">
                    <div class="border rounded text-center p-2 bg-light">
                        <div class="fw-bold text-primary fs-5"><?= count($tasks) ?></div>
                        <div class="small">งานทั้งหมด</div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded text-center p-2 bg-light">
                        <div class="fw-bold text-warning fs-5"><?= count(array_filter($tasks, fn($t) => in_array($t['task_status'], ['pending', 'received']))) ?></div>
                        <div class="small">รอดำเนินการ</div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded text-center p-2 bg-light">
                        <div class="fw-bold text-info fs-5"><?= count(array_filter($tasks, fn($t) => in_array($t['task_status'], ['in_progress', 'on_hold']))) ?></div>
                        <div class="small">กำลังทำ</div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded text-center p-2 bg-light">
                        <div class="fw-bold text-success fs-5"><?= count(array_filter($tasks, fn($t) => in_array($t['task_status'], ['completed', 'accepted']))) ?></div>
                        <div class="small">เสร็จแล้ว</div>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded text-center p-2 bg-light">
                        <div class="fw-bold text-danger fs-5"><?= count(array_filter($tasks, fn($t) => $t['deadline_status'] === 'overdue')) ?></div>
                        <div class="small">เลยกำหนด</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


        <div class="row">
            <!-- Developer Status -->
            <div class="col-lg-3">
                <div class="glass-card p-4 mb-4">
                    <h3 class="fw-bold mb-3">
                        <i class="fas fa-users text-primary me-2"></i>สถานะ Developer
                    </h3>
                    
                    <?php if ($selected_dev === 'all'): ?>
                        <?php foreach ($developers as $dev): ?>
                            <?php 
                            $stats = $dev_stats[$dev['id']] ?? [
                                'name' => $dev['name'] . ' ' . $dev['lastname'],
                                'pending' => 0,
                                'in_progress' => 0,
                                'completed' => 0,
                                'overdue' => 0,
                                'total' => 0,
                                'total_hours' => 0,
                                'avg_rating' => 0,
                                'status' => 'ว่าง'
                            ];
                            $status_class = $stats['overdue'] > 0 ? 'overdue' : 
                                          ($stats['status'] === 'ว่าง' ? 'available' : 
                                          ($stats['status'] === 'ติดงาน' ? 'busy' : 'pending'));
                            ?>
                            <div class="dev-status-card <?= $status_class ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($stats['name']) ?></h6>
                                    <span class="status-badge status-<?= $status_class ?>">
                                        <?= $stats['status'] ?>
                                        <?php if ($stats['overdue'] > 0): ?>
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-number text-warning"><?= $stats['pending'] ?></div>
                                        <div class="stat-label">รอรับ</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number text-info"><?= $stats['in_progress'] ?></div>
                                        <div class="stat-label">กำลังทำ</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number text-success"><?= $stats['completed'] ?></div>
                                        <div class="stat-label">เสร็จแล้ว</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number text-danger"><?= $stats['overdue'] ?></div>
                                        <div class="stat-label">เลยกำหนด</div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>รวม: <?= number_format($stats['total_hours'], 1) ?> ชม.
                                        <?php if ($stats['avg_rating'] > 0): ?>
                                            | <i class="fas fa-star text-warning me-1"></i><?= number_format($stats['avg_rating'], 1) ?>/5
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php 
                        $selected_dev_data = array_filter($developers, function($dev) use ($selected_dev) {
                            return $dev['id'] == $selected_dev;
                        });
                        $selected_dev_data = reset($selected_dev_data);
                        $stats = $dev_stats[$selected_dev] ?? [
                            'name' => $selected_dev_data['name'] . ' ' . $selected_dev_data['lastname'],
                            'pending' => 0,
                            'in_progress' => 0,
                            'completed' => 0,
                            'overdue' => 0,
                            'total' => 0,
                            'total_hours' => 0,
                            'avg_rating' => 0,
                            'status' => 'ว่าง'
                        ];
                        ?>
                        <div class="dev-status-card <?= $stats['overdue'] > 0 ? 'overdue' : ($stats['status'] === 'ว่าง' ? 'available' : 'busy') ?>">
                            <h5 class="fw-bold mb-3"><?= htmlspecialchars($stats['name']) ?></h5>
                            <div class="stats-grid mb-3">
                                <div class="stat-item">
                                    <div class="stat-number text-warning"><?= $stats['pending'] ?></div>
                                    <div class="stat-label">รอรับ</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number text-info"><?= $stats['in_progress'] ?></div>
                                    <div class="stat-label">กำลังทำ</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number text-success"><?= $stats['completed'] ?></div>
                                    <div class="stat-label">เสร็จแล้ว</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number text-danger"><?= $stats['overdue'] ?></div>
                                    <div class="stat-label">เลยกำหนด</div>
                                </div>
                            </div>
                            
                            <h6 class="fw-bold mb-2">รายการงาน:</h6>
                            <div class="task-list">
                                <?php if (isset($dev_tasks[$selected_dev])): ?>
                                    <?php foreach ($dev_tasks[$selected_dev] as $task): ?>
                                        <div class="task-card <?= $task['task_status'] ?> <?= $task['deadline_status'] === 'overdue' ? 'overdue' : '' ?>">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold"><?= htmlspecialchars($task['title']) ?></div>
                                                    <div class="small text-muted">
                                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                                        <?php if ($task['requester_department']): ?>
                                                            | <?= htmlspecialchars($task['requester_department']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($task['service_name']): ?>
                                                        <span class="service-badge service-<?= $task['service_category'] ?>">
                                                            <?= htmlspecialchars($task['service_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <span class="priority-badge priority-<?= $task['priority'] ?>">
                                                        <?= strtoupper($task['priority']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="task-meta">
                                                <div>
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('d/m/Y', strtotime($task['request_created_at'])) ?>
                                                    <?php if ($task['started_at']): ?>
                                                        | <i class="fas fa-play me-1"></i>
                                                        <?= date('d/m H:i', strtotime($task['started_at'])) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <?php if ($task['deadline']): ?>
                                                        <i class="fas fa-flag me-1 <?= $task['deadline_status'] === 'overdue' ? 'deadline-warning' : ($task['deadline_status'] === 'due_soon' ? 'deadline-soon' : '') ?>"></i>
                                                        <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                                    <?php endif; ?>
                                                    <?php if ($task['hours_spent'] > 0): ?>
                                                        | <i class="fas fa-clock me-1"></i>
                                                        <?= number_format($task['hours_spent'], 1) ?>ชม.
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if ($task['progress_percentage'] > 0): ?>
                                                <div class="progress mt-2" style="height: 6px;">
                                                    <div class="progress-bar bg-info" style="width: <?= $task['progress_percentage'] ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= $task['progress_percentage'] ?>% เสร็จสิ้น</small>
                                            <?php endif; ?>
                                            
                                            <?php if ($task['rating']): ?>
                                                <div class="mt-2">
                                                    <span class="rating-stars">
                                                        <?= str_repeat('★', $task['rating']) ?>
                                                    </span>
                                                    <small class="text-muted ms-1">(<?= $task['rating'] ?>/5)</small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($task['developer_notes']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-sticky-note me-1"></i>
                                                        <?= htmlspecialchars(substr($task['developer_notes'], 0, 100)) ?>
                                                        <?= strlen($task['developer_notes']) > 100 ? '...' : '' ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Hidden data for JavaScript -->
                                            <div style="display: none;" 
                                                 data-task-id="<?= $task['id'] ?>"
                                                 data-created-at="<?= $task['request_created_at'] ?>"
                                                 data-status="<?= $task['task_status'] ?>"
                                                 data-deadline-status="<?= $task['deadline_status'] ?>">
                                            </div>
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
                                <option value="overdue">เลยกำหนด</option>
                            </select>
                            
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
                                        <th>งาน</th>
                                        <th>Developer</th>
                                        <th>สถานะ</th>
                                        <th>ความสำคัญ</th>
                                        <th>เวลา</th>
                                        <th>กำหนดส่ง</th>
                                        <th>ความคืบหน้า</th>
                                        <th>คะแนน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr class="<?= $task['deadline_status'] === 'overdue' ? 'table-danger' : ($task['deadline_status'] === 'due_soon' ? 'table-warning' : '') ?>">
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($task['title']) ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                                    <?php if ($task['service_name']): ?>
                                                        <br><span class="service-badge service-<?= $task['service_category'] ?>">
                                                            <?= htmlspecialchars($task['service_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($task['dev_name'] . ' ' . $task['dev_lastname']) ?></div>
                                                <?php if ($task['assignor_name']): ?>
                                                    <small class="text-muted">
                                                        มอบหมายโดย: <?= htmlspecialchars($task['assignor_name']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($task['task_status']) {
                                                        case 'pending': echo 'warning'; break;
                                                        case 'received': echo 'info'; break;
                                                        case 'in_progress': echo 'primary'; break;
                                                        case 'on_hold': echo 'secondary'; break;
                                                        case 'completed': echo 'success'; break;
                                                        case 'accepted': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php
                                                    $status_labels = [
                                                        'pending' => 'รอรับ',
                                                        'received' => 'รับแล้ว',
                                                        'in_progress' => 'กำลังทำ',
                                                        'on_hold' => 'พักงาน',
                                                        'completed' => 'เสร็จแล้ว',
                                                        'accepted' => 'ยอมรับแล้ว'
                                                    ];
                                                    echo $status_labels[$task['task_status']] ?? $task['task_status'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="priority-badge priority-<?= $task['priority'] ?>">
                                                    <?= strtoupper($task['priority']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($task['hours_spent'] > 0): ?>
                                                    <div class="fw-bold"><?= number_format($task['hours_spent'], 1) ?> ชม.</div>
                                                <?php endif; ?>
                                                <?php if ($task['estimated_days']): ?>
                                                    <small class="text-muted">ประมาณ: <?= $task['estimated_days'] ?> วัน</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($task['deadline']): ?>
                                                    <div class="<?= $task['deadline_status'] === 'overdue' ? 'deadline-warning' : ($task['deadline_status'] === 'due_soon' ? 'deadline-soon' : '') ?>">
                                                        <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                                        <?php if ($task['deadline_status'] === 'overdue'): ?>
                                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                                        <?php elseif ($task['deadline_status'] === 'due_soon'): ?>
                                                            <i class="fas fa-clock ms-1"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">ไม่กำหนด</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($task['progress_percentage'] > 0): ?>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-info" style="width: <?= $task['progress_percentage'] ?>%"></div>
                                                    </div>
                                                    <small><?= $task['progress_percentage'] ?>%</small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($task['rating']): ?>
                                                    <div class="rating-stars">
                                                        <?= str_repeat('★', $task['rating']) ?>
                                                    </div>
                                                    <small class="text-muted"><?= $task['rating'] ?>/5</small>
                                                <?php else: ?>
                                                    <span class="text-muted">ยังไม่รีวิว</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

             <!-- Mini Calendar -->
            <!-- <div class="col-lg-3">
                <div class="glass-card p-4 mb-4">
                    <h3 class="fw-bold mb-3">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>ปฏิทินงาน
                    </h3> -->
                    
                    <!-- Calendar Navigation -->
                    <!-- <div class="calendar-nav mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="changeMonth(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h6 class="mb-0" id="currentMonth"><?= $thai_months[date('n')] ?> <?= date('Y') + 543 ?></h6>
                            <button class="btn btn-sm btn-outline-primary" onclick="changeMonth(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div> -->
                    
                    <!-- Mini Calendar Grid -->
                    <!-- <div class="mini-calendar">
                        <div class="calendar-header">
                            <div class="day-header">อา</div>
                            <div class="day-header">จ</div>
                            <div class="day-header">อ</div>
                            <div class="day-header">พ</div>
                            <div class="day-header">พฤ</div>
                            <div class="day-header">ศ</div>
                            <div class="day-header">ส</div>
                        </div>
                        <div class="calendar-body" id="calendarBody"> -->
                            <!-- Calendar days will be generated by JavaScript -->
                        <!-- </div>
                    </div>
                     -->
                    <!-- Calendar Legend -->
                    <!-- <div class="calendar-legend mt-3">
                        <div class="legend-item">
                            <span class="legend-dot pending"></span>
                            <small>รอรับ</small>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot in-progress"></span>
                            <small>กำลังทำ</small>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot completed"></span>
                            <small>เสร็จแล้ว</small>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot overdue"></span>
                            <small>เลยกำหนด</small>
                        </div>
                    </div>
                </div>
            </div> -->

            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calendar variables
        let currentCalendarMonth = new Date().getMonth();
        let currentCalendarYear = new Date().getFullYear();
        const thaiMonths = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        
        // Task data for calendar (this would come from PHP in real implementation)
        const taskData = <?= json_encode($tasks) ?>;
        
        function filterDeveloper() {
            const devId = document.getElementById('devSelect').value;
            window.location.href = `?dev_id=${devId}`;
        }

        function refreshData() {
            location.reload();
        }
        
        function filterTasks() {
            const dateFilter = document.getElementById('dateFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            // Get all task cards
            const taskCards = document.querySelectorAll('.task-card');
            
            taskCards.forEach(card => {
                let showCard = true;
                
                // Date filter
                if (dateFilter !== 'all') {
                    const taskDate = new Date(card.dataset.createdAt);
                    const now = new Date();
                    
                    switch(dateFilter) {
                        case 'today':
                            showCard = taskDate.toDateString() === now.toDateString();
                            break;
                        case 'week':
                            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                            showCard = taskDate >= weekAgo;
                            break;
                        case 'month':
                            showCard = taskDate.getMonth() === now.getMonth() && 
                                      taskDate.getFullYear() === now.getFullYear();
                            break;
                        case 'year':
                            showCard = taskDate.getFullYear() === now.getFullYear();
                            break;
                    }
                }
                
                // Status filter
                if (statusFilter !== 'all' && showCard) {
                    const taskStatus = card.dataset.status;
                    const deadlineStatus = card.dataset.deadlineStatus;
                    
                    switch(statusFilter) {
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
        
        function changeMonth(direction) {
            currentCalendarMonth += direction;
            
            if (currentCalendarMonth > 11) {
                currentCalendarMonth = 0;
                currentCalendarYear++;
            } else if (currentCalendarMonth < 0) {
                currentCalendarMonth = 11;
                currentCalendarYear--;
            }
            
            updateCalendar();
        }
        
        function updateCalendar() {
            // Update month display
            document.getElementById('currentMonth').textContent = 
                thaiMonths[currentCalendarMonth] + ' ' + (currentCalendarYear + 543);
            
            // Generate calendar
            generateCalendar();
        }
        
        function generateCalendar() {
            const calendarBody = document.getElementById('calendarBody');
            const firstDay = new Date(currentCalendarYear, currentCalendarMonth, 1);
            const lastDay = new Date(currentCalendarYear, currentCalendarMonth + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            let html = '';
            let currentDate = new Date(startDate);
            
            // Generate 6 weeks
            for (let week = 0; week < 6; week++) {
                for (let day = 0; day < 7; day++) {
                    const isCurrentMonth = currentDate.getMonth() === currentCalendarMonth;
                    const isToday = currentDate.toDateString() === new Date().toDateString();
                    const dateStr = currentDate.toISOString().split('T')[0];
                    
                    // Check for tasks on this date
                    const tasksOnDate = taskData.filter(task => {
                        const taskDate = new Date(task.started_at || task.created_at);
                        return taskDate.toDateString() === currentDate.toDateString();
                    });
                    
                    let dayClass = 'calendar-day';
                    if (isToday) dayClass += ' today';
                    if (!isCurrentMonth) dayClass += ' other-month';
                    if (tasksOnDate.length > 0) dayClass += ' has-task';
                    
                    // Check for overdue tasks
                    const hasOverdue = tasksOnDate.some(task => task.deadline_status === 'overdue');
                    if (hasOverdue) dayClass += ' has-overdue';
                    
                    let indicator = '';
                    if (tasksOnDate.length > 0) {
                        const primaryTask = tasksOnDate[0];
                        let indicatorClass = 'task-indicator ';
                        
                        if (primaryTask.deadline_status === 'overdue') {
                            indicatorClass += 'overdue';
                        } else if (['completed', 'accepted'].includes(primaryTask.task_status)) {
                            indicatorClass += 'completed';
                        } else if (['in_progress', 'on_hold'].includes(primaryTask.task_status)) {
                            indicatorClass += 'in-progress';
                        } else {
                            indicatorClass += 'pending';
                        }
                        
                        indicator = `<span class="${indicatorClass}"></span>`;
                    }
                    
                    html += `
                        <div class="${dayClass}" onclick="showTasksForDate('${dateStr}')">
                            ${currentDate.getDate()}
                            ${indicator}
                        </div>
                    `;
                    
                    currentDate.setDate(currentDate.getDate() + 1);
                }
            }
            
            calendarBody.innerHTML = html;
        }
        
        function showTasksForDate(dateStr) {
            const tasksOnDate = taskData.filter(task => {
                const taskDate = new Date(task.started_at || task.created_at);
                return taskDate.toISOString().split('T')[0] === dateStr;
            });
            
            if (tasksOnDate.length > 0) {
                let message = `งานในวันที่ ${new Date(dateStr).toLocaleDateString('th-TH')}:\n\n`;
                tasksOnDate.forEach((task, index) => {
                    message += `${index + 1}. ${task.title}\n`;
                    message += `   สถานะ: ${getStatusLabel(task.task_status)}\n`;
                    message += `   Developer: ${task.dev_name} ${task.dev_lastname}\n\n`;
                    message += `   Developer: ${task.dev_name} ${task.dev_lastname}\n\n`;
                });
                alert(message);
            } else {
                alert(`ไม่มีงานในวันที่ ${new Date(dateStr).toLocaleDateString('th-TH')}`);
            }
        }
        
        function getStatusLabel(status) {
            const labels = {
                'pending': 'รอรับ',
                'received': 'รับแล้ว',
                'in_progress': 'กำลังทำ',
                'on_hold': 'พักงาน',
                'completed': 'เสร็จแล้ว',
                'accepted': 'ยอมรับแล้ว'
            };
            return labels[status] || status;
        }
        
        // Initialize calendar on page load
        document.addEventListener('DOMContentLoaded', function() {
            generateCalendar();
            
            // Add data attributes to task cards for filtering
            const taskCards = document.querySelectorAll('.task-card');
            taskCards.forEach(card => {
                // This would be populated from PHP data
                const taskId = card.querySelector('[data-task-id]')?.dataset.taskId;
                if (taskId) {
                    const task = taskData.find(t => t.id == taskId);
                    if (task) {
                        card.dataset.createdAt = task.request_created_at;
                        card.dataset.status = task.task_status;
                        card.dataset.deadlineStatus = task.deadline_status;
                    }
                }
            });
        });

        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>