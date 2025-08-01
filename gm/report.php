<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

// ดึงข้อมูลสำหรับรายงาน
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$developer_id = $_GET['developer_id'] ?? 'all';
$status_filter = $_GET['status_filter'] ?? 'all';
$service_category = $_GET['service_category'] ?? 'all';
$priority_filter = $_GET['priority_filter'] ?? 'all';
$department_filter = $_GET['department_filter'] ?? 'all';

// ดึงรายชื่อ developers
$dev_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'developer' AND is_active = 1 ORDER BY name");
$dev_stmt->execute();
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อหน่วยงาน
$dept_stmt = $conn->prepare("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// สร้าง WHERE conditions
$where_conditions = ["sr.created_at BETWEEN ? AND ?"];
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

if ($developer_id !== 'all') {
    $where_conditions[] = "t.developer_user_id = ?";
    $params[] = $developer_id;
}

if ($service_category !== 'all') {
    $where_conditions[] = "s.category = ?";
    $params[] = $service_category;
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "sr.priority = ?";
    $params[] = $priority_filter;
}

if ($department_filter !== 'all') {
    $where_conditions[] = "requester.department = ?";
    $params[] = $department_filter;
}

if ($status_filter !== 'all') {
    if ($status_filter === 'pending_approval') {
        $where_conditions[] = "sr.status IN ('pending', 'div_mgr_review', 'assignor_review', 'gm_review', 'senior_gm_review')";
    } elseif ($status_filter === 'in_development') {
        $where_conditions[] = "t.task_status IN ('pending', 'received', 'in_progress', 'on_hold')";
    } elseif ($status_filter === 'completed') {
        $where_conditions[] = "t.task_status IN ('completed', 'accepted')";
    } elseif ($status_filter === 'rejected') {
        $where_conditions[] = "sr.status = 'rejected'";
    }
}

$where_clause = implode(' AND ', $where_conditions);

// ดึงข้อมูลคำขอทั้งหมด
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        requester.name as requester_name,
        requester.lastname as requester_lastname,
        requester.department as requester_department,
        requester.position as requester_position,
        requester.employee_id as requester_employee_id,
        s.name as service_name,
        s.category as service_category,
        dn.document_number,
        dn.warehouse_number,
        dn.code_name,
        dn.year,
        dn.month,
        dn.running_number,
        dn.created_at as document_created_at,
        
        -- Division Manager
        dma.status as div_mgr_status,
        dma.reviewed_at as div_mgr_reviewed_at,
        div_mgr.name as div_mgr_name,
        
        -- Assignor
        aa.status as assignor_status,
        aa.reviewed_at as assignor_reviewed_at,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        
        -- GM
        gma.status as gm_status,
        gma.reviewed_at as gm_reviewed_at,
        gma.budget_approved,
        gm.name as gm_name,
        
        -- Senior GM
        sgma.status as senior_gm_status,
        sgma.reviewed_at as senior_gm_reviewed_at,
        senior_gm.name as senior_gm_name,
        
        -- Task
        t.id as task_id,
        t.task_status,
        t.progress_percentage,
        t.started_at,
        t.completed_at,
        t.developer_notes,
        t.accepted_at,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        
        -- User Review
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.reviewed_at as user_reviewed_at,
        
        -- คำนวณเวลาที่ใช้
        CASE 
            WHEN t.started_at IS NOT NULL AND t.completed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at)
            WHEN t.started_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, t.started_at, NOW())
            ELSE 0
        END as hours_spent,
        
        -- คำนวณวันที่ใช้ในการอนุมัติ
        CASE 
            WHEN sgma.reviewed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(DAY, sr.created_at, sgma.reviewed_at)
            WHEN gma.reviewed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(DAY, sr.created_at, gma.reviewed_at)
            WHEN aa.reviewed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(DAY, sr.created_at, aa.reviewed_at)
            WHEN dma.reviewed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(DAY, sr.created_at, dma.reviewed_at)
            ELSE TIMESTAMPDIFF(DAY, sr.created_at, NOW())
        END as approval_days,
        
        -- คำนวณการส่งตรงเวลา
        CASE 
            WHEN t.completed_at IS NOT NULL AND aa.estimated_days IS NOT NULL
            THEN CASE 
                WHEN TIMESTAMPDIFF(DAY, t.started_at, t.completed_at) <= aa.estimated_days 
                THEN 'on_time' 
                ELSE 'overdue' 
            END
            ELSE 'unknown'
        END as delivery_status
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN document_numbers dn ON sr.document_number = dn.document_number
    LEFT JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    LEFT JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
    LEFT JOIN users gm ON gma.gm_user_id = gm.id
    LEFT JOIN senior_gm_approvals sgma ON sr.id = sgma.service_request_id
    LEFT JOIN users senior_gm ON sgma.senior_gm_user_id = senior_gm.id
    LEFT JOIN tasks t ON sr.id = t.service_request_id
    LEFT JOIN users dev ON t.developer_user_id = dev.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    WHERE $where_clause
    ORDER BY sr.created_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณสถิติ
$total_requests = count($requests);
$pending_approval = count(array_filter($requests, fn($r) => in_array($r['status'], ['pending', 'div_mgr_review', 'assignor_review', 'gm_review', 'senior_gm_review'])));
$approved = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$rejected = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
$in_development = count(array_filter($requests, fn($r) => in_array($r['task_status'], ['pending', 'received', 'in_progress', 'on_hold'])));
$completed = count(array_filter($requests, fn($r) => in_array($r['task_status'], ['completed', 'accepted'])));

// สถิติ Developer
$dev_performance = [];
foreach ($requests as $req) {
    if ($req['dev_name']) {
        $dev_key = $req['dev_name'] . ' ' . $req['dev_lastname'];
        if (!isset($dev_performance[$dev_key])) {
            $dev_performance[$dev_key] = [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'total_hours' => 0,
                'avg_rating' => 0,
                'total_ratings' => 0,
                'on_time' => 0,
                'overdue' => 0,
                'in_progress' => 0
            ];
        }
        
        $dev_performance[$dev_key]['total_tasks']++;
        $dev_performance[$dev_key]['total_hours'] += $req['hours_spent'];
        
        if (in_array($req['task_status'], ['completed', 'accepted'])) {
            $dev_performance[$dev_key]['completed_tasks']++;
        }
        
        if (in_array($req['task_status'], ['received', 'in_progress', 'on_hold'])) {
            $dev_performance[$dev_key]['in_progress']++;
        }
        
        if ($req['rating']) {
            $current_avg = $dev_performance[$dev_key]['avg_rating'];
            $current_count = $dev_performance[$dev_key]['total_ratings'];
            $dev_performance[$dev_key]['avg_rating'] = (($current_avg * $current_count) + $req['rating']) / ($current_count + 1);
            $dev_performance[$dev_key]['total_ratings']++;
        }
        
        // ตรวจสอบการส่งงานตรงเวลา
        if ($req['delivery_status'] === 'on_time') {
            $dev_performance[$dev_key]['on_time']++;
        } elseif ($req['delivery_status'] === 'overdue') {
            $dev_performance[$dev_key]['overdue']++;
        }
    }
}

// สถิติตามประเภทบริการ
$service_stats = [];
foreach ($requests as $req) {
    if ($req['service_category']) {
        if (!isset($service_stats[$req['service_category']])) {
            $service_stats[$req['service_category']] = 0;
        }
        $service_stats[$req['service_category']]++;
    }
}

// สถิติตามหน่วยงาน
$department_stats = [];
foreach ($requests as $req) {
    if ($req['requester_department']) {
        if (!isset($department_stats[$req['requester_department']])) {
            $department_stats[$req['requester_department']] = 0;
        }
        $department_stats[$req['requester_department']]++;
    }
}

// สถิติตามความสำคัญ
$priority_stats = [];
foreach ($requests as $req) {
    $priority = $req['priority_level'] ?? $req['priority'] ?? 'medium';
    if (!isset($priority_stats[$priority])) {
        $priority_stats[$priority] = 0;
    }
    $priority_stats[$priority]++;
}

// คำนวณเวลาเฉลี่ยในการอนุมัติ
$total_approval_days = array_sum(array_column($requests, 'approval_days'));
$avg_approval_days = $total_requests > 0 ? round($total_approval_days / $total_requests, 1) : 0;

// คำนวณเวลาเฉลี่ยในการพัฒนา
$completed_tasks = array_filter($requests, fn($r) => in_array($r['task_status'], ['completed', 'accepted']));
$total_dev_hours = array_sum(array_column($completed_tasks, 'hours_spent'));
$avg_dev_hours = count($completed_tasks) > 0 ? round($total_dev_hours / count($completed_tasks), 1) : 0;

// คำนวณคะแนนเฉลี่ย
$rated_tasks = array_filter($requests, fn($r) => $r['rating'] > 0);
$total_rating = array_sum(array_column($rated_tasks, 'rating'));
$avg_rating = count($rated_tasks) > 0 ? round($total_rating / count($rated_tasks), 1) : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานระบบจัดการคำขอบริการ - BobbyCareDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
            --border-color: #bdc3c7;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--primary-color);
            line-height: 1.6;
        }

        .report-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .report-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .report-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            text-align: center;
            margin-bottom: 1rem;
        }

        .report-meta {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            font-size: 0.95rem;
        }

        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .filters-title {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .stats-section {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.total .stat-number { color: var(--primary-color); }
        .stat-card.pending .stat-number { color: var(--warning-color); }
        .stat-card.approved .stat-number { color: var(--success-color); }
        .stat-card.rejected .stat-number { color: var(--danger-color); }
        .stat-card.development .stat-number { color: #9b59b6; }
        .stat-card.completed .stat-number { color: #16a085; }

        .section-title {
            color: var(--primary-color);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--accent-color);
            padding-left: 1rem;
        }

        .performance-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--success-color);
        }

        .performance-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .developer-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .performance-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }

        .perf-item {
            text-align: center;
            padding: 0.75rem;
            background: var(--light-bg);
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .perf-number {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .perf-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }

        .table-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .table-modern {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .table-modern thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .table-modern th {
            border: none;
            padding: 1rem 0.75rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            vertical-align: middle;
        }

        .table-modern td {
            border: none;
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

        .table-modern tbody tr:hover {
            background-color: #f8f9fa;
        }

        .document-number {
            font-family: 'Courier New', monospace;
            background: var(--light-bg);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid var(--border-color);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-approved { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .status-in_progress { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .status-completed { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        .service-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .service-development { background: #c6f6d5; color: #2f855a; border: 1px solid #9ae6b4; }
        .service-service { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low { background: #d1fae5; color: #065f46; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-high { background: #fed7d7; color: #991b1b; }
        .priority-urgent { background: #dc2626; color: white; }

        .rating-stars {
            color: #f59e0b;
            font-size: 0.9rem;
        }

        .summary-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .summary-item {
            text-align: center;
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .chart-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .progress-bar-custom {
            height: 25px;
            border-radius: 12px;
            background: #e9ecef;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-color), #2980b9);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        @media print {
            body { 
                background: white !important; 
                font-size: 12px;
            }
            .filters-section, .btn, .no-print { 
                display: none !important; 
            }
            .report-header {
                background: var(--primary-color) !important;
                -webkit-print-color-adjust: exact;
            }
            .table-modern thead {
                background: var(--primary-color) !important;
                -webkit-print-color-adjust: exact;
            }
            .stat-card, .performance-card, .table-section, .summary-section, .chart-section {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }
            .page-break {
                page-break-before: always;
            }
        }

           @media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
        text-align: center;
    }
    .container {
        padding: 1rem;
    }
            .performance-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="report-header">
        <div class="container">
            <h1 class="report-title">
                <i class="fas fa-chart-line me-3"></i>
                รายงานระบบจัดการคำขอบริการ
            </h1>
            <p class="report-subtitle">BobbyCareDev Service Request Management System Report</p>
            <div class="report-meta">
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-calendar me-2"></i>
                        <strong>ช่วงเวลา:</strong> <?= date('d/m/Y', strtotime($date_from)) ?> - <?= date('d/m/Y', strtotime($date_to)) ?>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-clock me-2"></i>
                        <strong>สร้างรายงาน:</strong> <?= date('d/m/Y H:i:s') ?>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-user me-2"></i>
                        <strong>ผู้สร้าง:</strong> <?= htmlspecialchars($_SESSION['name']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Filters -->
        <div class="filters-section no-print">
            <h3 class="filters-title">
                <i class="fas fa-filter me-2"></i>ตัวกรองข้อมูล
            </h3>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-calendar-alt me-1"></i>วันที่เริ่มต้น
                    </label>
                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-calendar-alt me-1"></i>วันที่สิ้นสุด
                    </label>
                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-user-cog me-1"></i>Developer
                    </label>
                    <select name="developer_id" class="form-select">
                        <option value="all" <?= $developer_id === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <?php foreach ($developers as $dev): ?>
                            <option value="<?= $dev['id'] ?>" <?= $developer_id == $dev['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-tasks me-1"></i>สถานะ
                    </label>
                    <select name="status_filter" class="form-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="pending_approval" <?= $status_filter === 'pending_approval' ? 'selected' : '' ?>>รอการอนุมัติ</option>
                        <option value="in_development" <?= $status_filter === 'in_development' ? 'selected' : '' ?>>กำลังพัฒนา</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-cogs me-1"></i>ประเภทบริการ
                    </label>
                    <select name="service_category" class="form-select">
                        <option value="all" <?= $service_category === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="development" <?= $service_category === 'development' ? 'selected' : '' ?>>Development</option>
                        <option value="service" <?= $service_category === 'service' ? 'selected' : '' ?>>Service</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-exclamation-circle me-1"></i>ความสำคัญ
                    </label>
                    <select name="priority_filter" class="form-select">
                        <option value="all" <?= $priority_filter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="low" <?= $priority_filter === 'low' ? 'selected' : '' ?>>ต่ำ</option>
                        <option value="medium" <?= $priority_filter === 'medium' ? 'selected' : '' ?>>ปานกลาง</option>
                        <option value="high" <?= $priority_filter === 'high' ? 'selected' : '' ?>>สูง</option>
                        <option value="urgent" <?= $priority_filter === 'urgent' ? 'selected' : '' ?>>เร่งด่วน</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-building me-1"></i>หน่วยงาน
                    </label>
                    <select name="department_filter" class="form-select">
                        <option value="all" <?= $department_filter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['department']) ?>" <?= $department_filter === $dept['department'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>สร้างรายงาน
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-success">
                        <i class="fas fa-print me-2"></i>พิมพ์
                    </button>
                </div>
            </form>
        </div>

        <!-- Executive Summary -->
        <div class="summary-section">
            <h3 class="section-title">
                <i class="fas fa-chart-bar me-2"></i>สรุปผลการดำเนินงาน
            </h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value"><?= $total_requests ?></div>
                    <div class="summary-label">คำขอทั้งหมด</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $avg_approval_days ?> วัน</div>
                    <div class="summary-label">เวลาอนุมัติเฉลี่ย</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $avg_dev_hours ?> ชม.</div>
                    <div class="summary-label">เวลาพัฒนาเฉลี่ย</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $avg_rating ?>/5</div>
                    <div class="summary-label">คะแนนเฉลี่ย</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= round(($completed / max($total_requests, 1)) * 100, 1) ?>%</div>
                    <div class="summary-label">อัตราความสำเร็จ</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= count($dev_performance) ?></div>
                    <div class="summary-label">Developer ที่ทำงาน</div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-section">
            <h3 class="section-title">
                <i class="fas fa-chart-pie me-2"></i>สถิติการดำเนินงาน
            </h3>
            <div class="row g-3 mb-4">
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="stat-card total">
                        <div class="stat-number"><?= $total_requests ?></div>
                        <div class="stat-label">คำขอทั้งหมด</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="stat-card pending">
                        <div class="stat-number"><?= $pending_approval ?></div>
                        <div class="stat-label">รอการอนุมัติ</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="stat-card approved">
                        <div class="stat-number"><?= $approved ?></div>
                        <div class="stat-label">อนุมัติแล้ว</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="stat-card rejected">
                        <div class="stat-number"><?= $rejected ?></div>
                        <div class="stat-label">ไม่อนุมัติ</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="stat-card development">
                        <div class="stat-number"><?= $in_development ?></div>
                        <div class="stat-label">กำลังพัฒนา</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="stat-card completed">
                        <div class="stat-number"><?= $completed ?></div>
                        <div class="stat-label">เสร็จสิ้น</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <!-- Service Statistics -->
            <div class="col-md-6">
                <div class="chart-section">
                    <h4 class="section-title">
                        <i class="fas fa-chart-donut me-2"></i>สถิติตามประเภทบริการ
                    </h4>
                    <?php foreach ($service_stats as $category => $count): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="service-badge service-<?= $category ?>">
                                    <?= $category === 'development' ? 'Development' : 'Service' ?>
                                </span>
                                <span class="fw-bold"><?= $count ?> รายการ (<?= round(($count / max($total_requests, 1)) * 100, 1) ?>%)</span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" style="width: <?= ($count / max($total_requests, 1)) * 100 ?>%">
                                    <?= round(($count / max($total_requests, 1)) * 100, 1) ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Priority Statistics -->
            <div class="col-md-6">
                <div class="chart-section">
                    <h4 class="section-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>สถิติตามความสำคัญ
                    </h4>
                    <?php 
                    $priority_labels = ['urgent' => 'เร่งด่วน', 'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ'];
                    foreach ($priority_stats as $priority => $count): 
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="priority-badge priority-<?= $priority ?>">
                                    <?= $priority_labels[$priority] ?? $priority ?>
                                </span>
                                <span class="fw-bold"><?= $count ?> รายการ (<?= round(($count / max($total_requests, 1)) * 100, 1) ?>%)</span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" style="width: <?= ($count / max($total_requests, 1)) * 100 ?>%">
                                    <?= round(($count / max($total_requests, 1)) * 100, 1) ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Developer Performance -->
        <?php if (!empty($dev_performance)): ?>
        <div class="page-break">
            <h3 class="section-title">
                <i class="fas fa-users-cog me-2"></i>ประสิทธิภาพการทำงานของ Developer
            </h3>
            <div class="row">
                <?php foreach ($dev_performance as $dev_name => $perf): ?>
                    <div class="col-lg-6 mb-3">
                        <div class="performance-card">
                            <div class="performance-header">
                                <div class="developer-name">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <?= htmlspecialchars($dev_name) ?>
                                </div>
                                <div class="text-end">
                                    <?php 
                                    $completion_rate = $perf['total_tasks'] > 0 ? round(($perf['completed_tasks'] / $perf['total_tasks']) * 100, 1) : 0;
                                    $on_time_rate = ($perf['on_time'] + $perf['overdue']) > 0 ? round(($perf['on_time'] / ($perf['on_time'] + $perf['overdue'])) * 100, 1) : 0;
                                    ?>
                                    <small class="text-muted">
                                        อัตราเสร็จ: <?= $completion_rate ?>% | ตรงเวลา: <?= $on_time_rate ?>%
                                    </small>
                                </div>
                            </div>
                            <div class="performance-stats">
                                <div class="perf-item">
                                    <div class="perf-number text-primary"><?= $perf['total_tasks'] ?></div>
                                    <div class="perf-label">งานทั้งหมด</div>
                                </div>
                                <div class="perf-item">
                                    <div class="perf-number text-warning"><?= $perf['in_progress'] ?></div>
                                    <div class="perf-label">กำลังทำ</div>
                                </div>
                                <div class="perf-item">
                                    <div class="perf-number text-success"><?= $perf['completed_tasks'] ?></div>
                                    <div class="perf-label">เสร็จแล้ว</div>
                                </div>
                                <div class="perf-item">
                                    <div class="perf-number text-info"><?= number_format($perf['total_hours'], 1) ?></div>
                                    <div class="perf-label">ชั่วโมง</div>
                                </div>
                                <div class="perf-item">
                                    <div class="perf-number text-warning"><?= $perf['avg_rating'] > 0 ? number_format($perf['avg_rating'], 1) : '-' ?></div>
                                    <div class="perf-label">คะแนนเฉลี่ย</div>
                                </div>
                                <div class="perf-item">
                                    <div class="perf-number text-success"><?= $perf['on_time'] ?></div>
                                    <div class="perf-label">ตรงเวลา</div>
                                </div>
                                <div class="perf-item">
                                    <div class="perf-number text-danger"><?= $perf['overdue'] ?></div>
                                    <div class="perf-label">เลยกำหนด</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Table -->
        <div class="table-section page-break">
            <h3 class="section-title">
                <i class="fas fa-table me-2"></i>รายละเอียดคำขอทั้งหมด
            </h3>
            
            <?php if (empty($requests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">ไม่มีข้อมูลในช่วงเวลาที่เลือก</h5>
                    <p class="text-muted">กรุณาปรับเปลี่ยนตัวกรองเพื่อดูข้อมูล</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <div class="table-modern">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">ลำดับ</th>
                                    <th style="width: 12%;">เลขที่เอกสาร</th>
                                    <th style="width: 15%;">หัวข้อ</th>
                                    <th style="width: 12%;">ผู้ขอ</th>
                                    <th style="width: 10%;">หน่วยงาน</th>
                                    <th style="width: 8%;">ประเภท</th>
                                    <th style="width: 10%;">Developer</th>
                                    <th style="width: 8%;">สถานะ</th>
                                    <th style="width: 6%;">ความสำคัญ</th>
                                    <th style="width: 6%;">เวลา(วัน)</th>
                                    <th style="width: 5%;">คะแนน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $index => $req): ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $index + 1 ?></td>
                                        <td>
                                            <?php if ($req['document_number']): ?>
                                                <span class="document-number"><?= htmlspecialchars($req['document_number']) ?></span>
                                                <br><small class="text-muted"><?= date('d/m/y', strtotime($req['document_created_at'])) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">ไม่มี</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($req['title']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(substr($req['description'], 0, 50)) ?>...</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($req['requester_employee_id'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($req['requester_department'] ?? 'ไม่ระบุ') ?></small>
                                        </td>
                                        <td>
                                            <?php if ($req['service_name']): ?>
                                                <span class="service-badge service-<?= $req['service_category'] ?>">
                                                    <?= htmlspecialchars($req['service_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">ไม่ระบุ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($req['dev_name']): ?>
                                                <div class="fw-bold"><?= htmlspecialchars($req['dev_name'] . ' ' . $req['dev_lastname']) ?></div>
                                                <?php if ($req['started_at']): ?>
                                                    <small class="text-muted">เริ่ม: <?= date('d/m/y', strtotime($req['started_at'])) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">ยังไม่มอบหมาย</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($req['task_status']): ?>
                                                <span class="status-badge status-<?= $req['task_status'] ?>">
                                                    <?php
                                                    $status_labels = [
                                                        'pending' => 'รอรับ',
                                                        'received' => 'รับแล้ว',
                                                        'in_progress' => 'กำลังทำ',
                                                        'on_hold' => 'พักงาน',
                                                        'completed' => 'เสร็จแล้ว',
                                                        'accepted' => 'ยอมรับแล้ว'
                                                    ];
                                                    echo $status_labels[$req['task_status']] ?? $req['task_status'];
                                                    ?>
                                                </span>
                                                <?php if ($req['progress_percentage']): ?>
                                                    <br><small class="text-muted"><?= $req['progress_percentage'] ?>%</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="status-badge status-<?= $req['status'] ?>">
                                                    <?php
                                                    $status_labels = [
                                                        'pending' => 'รอดำเนินการ',
                                                        'div_mgr_review' => 'ผู้จัดการฝ่าย',
                                                        'assignor_review' => 'ผู้จัดการแผนก',
                                                        'gm_review' => 'ผู้จัดการทั่วไป',
                                                        'senior_gm_review' => 'ผู้จัดการอาวุโส',
                                                        'approved' => 'อนุมัติแล้ว',
                                                        'rejected' => 'ไม่อนุมัติ'
                                                    ];
                                                    echo $status_labels[$req['status']] ?? $req['status'];
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $priority = $req['priority_level'] ?? $req['priority'] ?? 'medium';
                                            $priority_labels = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                            ?>
                                            <span class="priority-badge priority-<?= $priority ?>">
                                                <?= $priority_labels[$priority] ?? $priority ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($req['hours_spent'] > 0): ?>
                                                <div class="fw-bold"><?= number_format($req['hours_spent'] / 24, 1) ?></div>
                                                <small class="text-muted">พัฒนา</small>
                                            <?php endif; ?>
                                            <div class="text-muted"><?= $req['approval_days'] ?></div>
                                            <small class="text-muted">อนุมัติ</small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($req['rating']): ?>
                                                <div class="rating-stars">
                                                    <?= str_repeat('★', $req['rating']) ?>
                                                </div>
                                                <small class="text-muted"><?= $req['rating'] ?>/5</small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Footer -->
                <div class="mt-4 p-3 bg-light rounded">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <strong>รวมคำขอ:</strong> <?= $total_requests ?> รายการ
                        </div>
                        <div class="col-md-3">
                            <strong>เวลาอนุมัติเฉลี่ย:</strong> <?= $avg_approval_days ?> วัน
                        </div>
                        <div class="col-md-3">
                            <strong>เวลาพัฒนาเฉลี่ย:</strong> <?= $avg_dev_hours ?> ชั่วโมง
                        </div>
                        <div class="col-md-3">
                            <strong>คะแนนเฉลี่ย:</strong> <?= $avg_rating ?>/5 ดาว
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Report Footer -->
        <div class="text-center mt-4 mb-5 text-muted">
            <hr>
            <p class="mb-1">
                <strong>BobbyCareDev Service Request Management System</strong>
            </p>
            <p class="mb-0">
                รายงานนี้สร้างโดยอัตโนมัติเมื่อ <?= date('d/m/Y H:i:s') ?> | 
                ข้อมูล ณ วันที่ <?= date('d/m/Y') ?>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select, input[type="date"]').forEach(element => {
            element.addEventListener('change', function() {
                // Auto-submit after a short delay to allow multiple selections
                setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        });

        // Print optimization
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });

        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
    </script>
</body>
</html>