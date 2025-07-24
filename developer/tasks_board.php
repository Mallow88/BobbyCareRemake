<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// สร้างงานใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $service_id = $_POST['service_id'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $estimated_days = $_POST['estimated_days'] ?? 1;
    $deadline = $_POST['deadline'] ?? null;

    if ($title && $description && $service_id) {
        try {
            $conn->beginTransaction();

            // สร้าง service request สำหรับงานส่วนตัว
            $stmt = $conn->prepare("
                INSERT INTO service_requests (
                    user_id, title, description, service_id, priority, 
                    estimated_days, deadline, status, current_step, developer_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', 'developer_self_created', 'received')
            ");
            $stmt->execute([$developer_id, $title, $description, $service_id, $priority, $estimated_days, $deadline]);
            $request_id = $conn->lastInsertId();

            // สร้าง task
            $stmt = $conn->prepare("
                INSERT INTO tasks (
                    service_request_id, developer_user_id, task_status, 
                    progress_percentage, started_at, accepted_at, estimated_completion
                ) VALUES (?, ?, 'received', 10, NOW(), NOW(), ?)
            ");
            $completion_date = $deadline ?: date('Y-m-d', strtotime('+' . $estimated_days . ' days'));
            $stmt->execute([$request_id, $developer_id, $completion_date]);

            $conn->commit();
            $success = "สร้างงานใหม่เรียบร้อยแล้ว";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// อัปเดตสถานะงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];
    $progress = $_POST['progress'];
    $notes = $_POST['notes'] ?? '';

    try {
        $conn->beginTransaction();

        // อัปเดตสถานะใน tasks
        $stmt = $conn->prepare("UPDATE tasks SET task_status = ?, progress_percentage = ?, developer_notes = ?, updated_at = NOW() WHERE id = ? AND developer_user_id = ?");
        $stmt->execute([$new_status, $progress, $notes, $task_id, $developer_id]);

        // อัปเดตสถานะใน service_requests
        $stmt = $conn->prepare("UPDATE service_requests SET developer_status = ? WHERE id = (SELECT service_request_id FROM tasks WHERE id = ?)");
        $stmt->execute([$new_status, $task_id]);

        // บันทึก log
        $stmt = $conn->prepare("INSERT INTO task_status_logs (task_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$task_id, '', $new_status, $developer_id, $notes]);

        // ถ้าเป็นการส่งงาน ให้อัปเดต completed_at
        if ($new_status === 'completed') {
            $stmt = $conn->prepare("UPDATE tasks SET completed_at = NOW() WHERE id = ?");
            $stmt->execute([$task_id]);
        }

        $conn->commit();
        header("Location: tasks_board.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ลบงานส่วนตัว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];
    
    try {
        $conn->beginTransaction();
        
        // ตรวจสอบว่าเป็นงานส่วนตัวหรือไม่
        $check_stmt = $conn->prepare("
            SELECT sr.current_step FROM tasks t
            JOIN service_requests sr ON t.service_request_id = sr.id
            WHERE t.id = ? AND t.developer_user_id = ?
        ");
        $check_stmt->execute([$task_id, $developer_id]);
        $task_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task_data && $task_data['current_step'] === 'developer_self_created') {
            // ลบ task และ service_request
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND developer_user_id = ?");
            $stmt->execute([$task_id, $developer_id]);
            
            $stmt = $conn->prepare("DELETE FROM service_requests WHERE id = (SELECT service_request_id FROM tasks WHERE id = ?)");
            $stmt->execute([$task_id]);
            
            $success = "ลบงานเรียบร้อยแล้ว";
        } else {
            $error = "ไม่สามารถลบงานที่ได้รับมอบหมายได้";
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ดึงงานทั้งหมดของ developer
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        sr.current_step,
        sr.priority,
        sr.deadline,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.revision_notes,
        ur.reviewed_at as user_reviewed_at,
        aa.estimated_days,
        s.name as service_name,
        s.category as service_category
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN services s ON sr.service_id = s.id
    WHERE t.developer_user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$developer_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการ services ประเภท service
$services_stmt = $conn->prepare("SELECT * FROM services WHERE category = 'service' AND is_active = 1 ORDER BY name");
$services_stmt->execute();
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มงานตามสถานะ
$tasks_by_status = [
    'pending' => [],
    'received' => [],
    'in_progress' => [],
    'on_hold' => [],
    'completed' => []
];

foreach ($tasks as $task) {
    $status = $task['task_status'];
    if (isset($tasks_by_status[$status])) {
        $tasks_by_status[$status][] = $task;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บอร์ดงาน - BobbyCareDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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

        .kanban-board {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .kanban-column {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            min-height: 500px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .column-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .column-title {
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .task-count {
            background: #6c757d;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .task-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            cursor: move;
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
            position: relative;
        }

        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .task-card.self-created {
            border-left-color: #9f7aea;
            background: linear-gradient(135deg, #faf5ff, #f3e8ff);
        }

        .task-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .task-description {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 15px;
        }

        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .task-requester {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-buttons {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .status-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .status-btn:hover {
            transform: scale(1.05);
        }

        .self-created-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #9f7aea, #805ad5);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .delete-btn {
            position: absolute;
            top: 10px;
            right: 80px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .delete-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        /* Column specific styles */
        .pending {
            border-left-color: #f59e0b;
        }
        .pending .column-title {
            color: #f59e0b;
        }
        .pending .task-count {
            background: #f59e0b;
        }

        .received {
            border-left-color: #3b82f6;
        }
        .received .column-title {
            color: #3b82f6;
        }
        .received .task-count {
            background: #3b82f6;
        }

        .in_progress {
            border-left-color: #8b5cf6;
        }
        .in_progress .column-title {
            color: #8b5cf6;
        }
        .in_progress .task-count {
            background: #8b5cf6;
        }

        .on_hold {
            border-left-color: #ef4444;
        }
        .on_hold .column-title {
            color: #ef4444;
        }
        .on_hold .task-count {
            background: #ef4444;
        }

        .completed {
            border-left-color: #10b981;
        }
        .completed .column-title {
            color: #10b981;
        }
        .completed .task-count {
            background: #10b981;
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

        .empty-column {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .empty-column i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
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

        .sortable-ghost {
            opacity: 0.4;
        }
        
        .sortable-chosen {
            transform: rotate(2deg);
        }
        
        .sortable-drag {
            transform: rotate(2deg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
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

        .priority-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 5px;
        }

        .priority-low { background: #c6f6d5; color: #2f855a; }
        .priority-medium { background: #fef5e7; color: #d69e2e; }
        .priority-high { background: #fed7d7; color: #c53030; }
        .priority-urgent { background: #e53e3e; color: white; }

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
            .kanban-board {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-tasks text-primary me-2"></i>
                <span class="page-title">Task Board</span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['name']) ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5 pt-5">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="header-card p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="page-title mb-2">
                                <i class="fas fa-clipboard-list me-3"></i>บอร์ดงาน
                            </h1>
                            <p class="text-muted mb-0 fs-5">จัดการงานด้วยระบบ Kanban</p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                                    <i class="fas fa-plus me-2"></i>สร้างงานใหม่
                                </button>
                                <a href="dev_index.php" class="btn btn-gradient">
                                    <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                                </a>
                                <a href="calendar.php" class="btn btn-gradient">
                                    <i class="fas fa-calendar-alt me-2"></i>ปฏิทิน
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Kanban Board -->
        <div class="kanban-board">
            <!-- รอรับ -->
            <div class="kanban-column pending">
                <div class="column-header">
                    <div class="column-title">
                        <i class="fas fa-hourglass-half"></i>
                        <span>รอรับ</span>
                    </div>
                    <span class="task-count"><?= count($tasks_by_status['pending']) ?></span>
                </div>
                <div class="tasks-container" data-status="pending">
                    <?php if (empty($tasks_by_status['pending'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-hourglass-half"></i>
                            <p>ไม่มีงานที่รอรับ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['pending'] as $task): ?>
                            <div class="task-card pending <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <span class="self-created-badge">งานส่วนตัว</span>
                                    <button class="delete-btn" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                
                                <?php if ($task['service_name']): ?>
                                    <span class="service-badge service-<?= $task['service_category'] ?>">
                                        <?php if ($task['service_category'] === 'development'): ?>
                                            <i class="fas fa-code me-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-tools me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($task['service_name']) ?>
                                    </span>
                                    <?php if ($task['priority']): ?>
                                        <span class="priority-badge priority-<?= $task['priority'] ?>">
                                            <?php
                                            $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                            echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php if ($task['estimated_days']): ?>
                                <div class="mb-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>
                                        ประมาณ <?= $task['estimated_days'] ?> วัน
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="badge bg-danger ms-1">
                                            <i class="fas fa-calendar-times me-1"></i>
                                            ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <div class="task-requester">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div class="task-date"><?= date('d/m/Y', strtotime($task['request_date'])) ?></div>
                                </div>
                                <div class="status-buttons">
                                    <button class="status-btn btn-primary" onclick="updateStatus(<?= $task['id'] ?>, 'received')">รับงาน</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- รับแล้ว -->
            <div class="kanban-column received">
                <div class="column-header">
                    <div class="column-title">
                        <i class="fas fa-check-circle"></i>
                        <span>รับแล้ว</span>
                    </div>
                    <span class="task-count"><?= count($tasks_by_status['received']) ?></span>
                </div>
                <div class="tasks-container" data-status="received">
                    <?php if (empty($tasks_by_status['received'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-check-circle"></i>
                            <p>ไม่มีงานที่รับแล้ว</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['received'] as $task): ?>
                            <div class="task-card received <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <span class="self-created-badge">งานส่วนตัว</span>
                                    <button class="delete-btn" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                
                                <?php if ($task['service_name']): ?>
                                    <span class="service-badge service-<?= $task['service_category'] ?>">
                                        <?php if ($task['service_category'] === 'development'): ?>
                                            <i class="fas fa-code me-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-tools me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($task['service_name']) ?>
                                    </span>
                                    <?php if ($task['priority']): ?>
                                        <span class="priority-badge priority-<?= $task['priority'] ?>">
                                            <?php
                                            $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                            echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php if ($task['estimated_days']): ?>
                                <div class="mb-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>
                                        ประมาณ <?= $task['estimated_days'] ?> วัน
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="badge bg-danger ms-1">
                                            <i class="fas fa-calendar-times me-1"></i>
                                            ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <div class="task-requester">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div class="task-date"><?= date('d/m/Y', strtotime($task['request_date'])) ?></div>
                                </div>
                                <div class="status-buttons">
                                    <button class="status-btn btn-success" onclick="updateStatus(<?= $task['id'] ?>, 'in_progress')">เริ่มทำ</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- กำลังดำเนินการ -->
            <div class="kanban-column in_progress">
                <div class="column-header">
                    <div class="column-title">
                        <i class="fas fa-cog fa-spin"></i>
                        <span>กำลังดำเนินการ</span>
                    </div>
                    <span class="task-count"><?= count($tasks_by_status['in_progress']) ?></span>
                </div>
                <div class="tasks-container" data-status="in_progress">
                    <?php if (empty($tasks_by_status['in_progress'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-cog"></i>
                            <p>ไม่มีงานที่กำลังทำ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['in_progress'] as $task): ?>
                            <div class="task-card in_progress <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <span class="self-created-badge">งานส่วนตัว</span>
                                    <button class="delete-btn" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                
                                <?php if ($task['service_name']): ?>
                                    <span class="service-badge service-<?= $task['service_category'] ?>">
                                        <?php if ($task['service_category'] === 'development'): ?>
                                            <i class="fas fa-code me-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-tools me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($task['service_name']) ?>
                                    </span>
                                    <?php if ($task['priority']): ?>
                                        <span class="priority-badge priority-<?= $task['priority'] ?>">
                                            <?php
                                            $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                            echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php if ($task['estimated_days']): ?>
                                <div class="mb-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>
                                        ประมาณ <?= $task['estimated_days'] ?> วัน
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="badge bg-danger ms-1">
                                            <i class="fas fa-calendar-times me-1"></i>
                                            ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <div class="task-requester">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div class="task-date"><?= date('d/m/Y', strtotime($task['request_date'])) ?></div>
                                </div>
                                <div class="status-buttons">
                                    <button class="status-btn btn-warning" onclick="updateStatus(<?= $task['id'] ?>, 'on_hold')">พักงาน</button>
                                    <button class="status-btn btn-success" onclick="showCompleteModal(<?= $task['id'] ?>)">ส่งงาน</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- พักงาน -->
            <div class="kanban-column on_hold">
                <div class="column-header">
                    <div class="column-title">
                        <i class="fas fa-pause-circle"></i>
                        <span>พักงาน</span>
                    </div>
                    <span class="task-count"><?= count($tasks_by_status['on_hold']) ?></span>
                </div>
                <div class="tasks-container" data-status="on_hold">
                    <?php if (empty($tasks_by_status['on_hold'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-pause-circle"></i>
                            <p>ไม่มีงานที่พัก</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['on_hold'] as $task): ?>
                            <div class="task-card on_hold <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <span class="self-created-badge">งานส่วนตัว</span>
                                    <button class="delete-btn" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                
                                <?php if ($task['service_name']): ?>
                                    <span class="service-badge service-<?= $task['service_category'] ?>">
                                        <?php if ($task['service_category'] === 'development'): ?>
                                            <i class="fas fa-code me-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-tools me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($task['service_name']) ?>
                                    </span>
                                    <?php if ($task['priority']): ?>
                                        <span class="priority-badge priority-<?= $task['priority'] ?>">
                                            <?php
                                            $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                            echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php if ($task['estimated_days']): ?>
                                <div class="mb-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>
                                        ประมาณ <?= $task['estimated_days'] ?> วัน
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="badge bg-danger ms-1">
                                            <i class="fas fa-calendar-times me-1"></i>
                                            ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <div class="task-requester">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div class="task-date"><?= date('d/m/Y', strtotime($task['request_date'])) ?></div>
                                </div>
                                <div class="status-buttons">
                                    <button class="status-btn btn-success" onclick="updateStatus(<?= $task['id'] ?>, 'in_progress')">ทำต่อ</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- เสร็จแล้ว -->
            <div class="kanban-column completed">
                <div class="column-header">
                    <div class="column-title">
                        <i class="fas fa-check-double"></i>
                        <span>เสร็จแล้ว</span>
                    </div>
                    <span class="task-count"><?= count($tasks_by_status['completed']) ?></span>
                </div>
                <div class="tasks-container" data-status="completed">
                    <?php if (empty($tasks_by_status['completed'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-check-double"></i>
                            <p>ยังไม่มีงานที่เสร็จ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['completed'] as $task): ?>
                            <div class="task-card completed <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <span class="self-created-badge">งานส่วนตัว</span>
                                <?php endif; ?>
                                
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                
                                <?php if ($task['service_name']): ?>
                                    <span class="service-badge service-<?= $task['service_category'] ?>">
                                        <?php if ($task['service_category'] === 'development'): ?>
                                            <i class="fas fa-code me-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-tools me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($task['service_name']) ?>
                                    </span>
                                    <?php if ($task['priority']): ?>
                                        <span class="priority-badge priority-<?= $task['priority'] ?>">
                                            <?php
                                            $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                            echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($task['review_status']): ?>
                                <div class="review-section">
                                    <div class="fw-bold text-success mb-2">
                                        <i class="fas fa-star"></i> รีวิวจากผู้ใช้
                                    </div>
                                    <?php if ($task['rating']): ?>
                                    <div class="mb-2">
                                        <span class="rating-stars"><?= str_repeat('⭐', $task['rating']) ?></span>
                                        <span class="ms-2">(<?= $task['rating'] ?>/5)</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($task['review_comment']): ?>
                                    <div class="small text-muted mb-2">
                                        "<?= htmlspecialchars($task['review_comment']) ?>"
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($task['user_reviewed_at']): ?>
                                    <div class="small text-muted mb-2">
                                        <i class="fas fa-clock"></i> รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($task['user_reviewed_at'])) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="small">
                                        สถานะ: 
                                        <?php if ($task['review_status'] === 'accepted'): ?>
                                            <span class="text-success">✅ ยอมรับงาน</span>
                                        <?php elseif ($task['review_status'] === 'revision_requested'): ?>
                                            <span class="text-warning">🔄 ขอแก้ไข</span>
                                        <?php else: ?>
                                            <span class="text-info">⏳ รอรีวิว</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($task['revision_notes']): ?>
                                    <div class="mt-2 p-2 bg-warning bg-opacity-10 rounded">
                                        <strong>ต้องแก้ไข:</strong> <?= htmlspecialchars($task['revision_notes']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <div class="task-requester">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div class="task-date"><?= date('d/m/Y', strtotime($task['request_date'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal รายละเอียดงาน -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
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
                                <p id="detailTitle" class="mb-0"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">รายละเอียด:</h6>
                                <div id="detailDescription" class="bg-light p-3 rounded"></div>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">ผู้ร้องขอ:</h6>
                                <p id="detailRequester" class="mb-0"></p>
                            </div>
                            <div id="additionalInfo"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">ไฟล์แนบ:</h6>
                                <div id="attachmentsList">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">กำลังโหลด...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สร้างงานใหม่ -->
    <div class="modal fade" id="createTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>สร้างงานใหม่
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="create_task" value="1">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label fw-bold">หัวข้องาน:</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           placeholder="ระบุหัวข้องาน">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label fw-bold">รายละเอียด:</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required
                                              placeholder="อธิบายรายละเอียดงาน"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="service_id" class="form-label fw-bold">ประเภทบริการ:</label>
                                    <select class="form-select" id="service_id" name="service_id" required>
                                        <option value="">-- เลือกประเภทบริการ --</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?= $service['id'] ?>">
                                                <?= htmlspecialchars($service['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label fw-bold">ความสำคัญ:</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">ต่ำ</option>
                                        <option value="medium" selected>ปานกลาง</option>
                                        <option value="high">สูง</option>
                                        <option value="urgent">เร่งด่วน</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="estimated_days" class="form-label fw-bold">ประมาณการ (วัน):</label>
                                    <input type="number" class="form-control" id="estimated_days" name="estimated_days" 
                                           min="1" max="365" value="1">
                                </div>

                                <div class="mb-3">
                                    <label for="deadline" class="form-label fw-bold">กำหนดเสร็จ:</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-gradient">
                            <i class="fas fa-plus me-2"></i>สร้างงาน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับการส่งงาน -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2"></i>ส่งงานที่เสร็จแล้ว
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="completionNotes" class="form-label fw-bold">หมายเหตุการส่งงาน:</label>
                        <textarea class="form-control" id="completionNotes" rows="4" 
                                  placeholder="อธิบายงานที่ทำเสร็จ, ปัญหาที่พบ, หรือข้อแนะนำ..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success" onclick="submitComplete()">
                        <i class="fas fa-paper-plane me-2"></i>ส่งงาน
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form สำหรับอัปเดตสถานะ -->
    <form id="statusForm" method="post" style="display: none;">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="task_id" id="taskId">
        <input type="hidden" name="new_status" id="newStatus">
        <input type="hidden" name="progress" id="taskProgress">
        <input type="hidden" name="notes" id="taskNotes">
    </form>

    <!-- Form สำหรับลบงาน -->
    <form id="deleteForm" method="post" style="display: none;">
        <input type="hidden" name="delete_task" value="1">
        <input type="hidden" name="task_id" id="deleteTaskId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentTaskId = null;
        
        function updateStatus(taskId, newStatus) {
            let progress = 0;
            switch(newStatus) {
                case 'received': progress = 10; break;
                case 'in_progress': progress = 50; break;
                case 'on_hold': progress = 30; break;
                case 'completed': progress = 100; break;
            }
            
            document.getElementById('taskId').value = taskId;
            document.getElementById('newStatus').value = newStatus;
            document.getElementById('taskProgress').value = progress;
            document.getElementById('taskNotes').value = '';
            document.getElementById('statusForm').submit();
        }
        
        function showCompleteModal(taskId) {
            currentTaskId = taskId;
            const modal = new bootstrap.Modal(document.getElementById('completeModal'));
            modal.show();
        }
        
        function submitComplete() {
            const notes = document.getElementById('completionNotes').value;
            if (notes.trim() === '') {
                alert('กรุณาใส่หมายเหตุการส่งงาน');
                return;
            }
            
            document.getElementById('taskId').value = currentTaskId;
            document.getElementById('newStatus').value = 'completed';
            document.getElementById('taskProgress').value = 100;
            document.getElementById('taskNotes').value = notes;
            document.getElementById('statusForm').submit();
        }

        function deleteTask(taskId) {
            if (confirm('ยืนยันการลบงาน? (เฉพาะงานส่วนตัวเท่านั้น)')) {
                document.getElementById('deleteTaskId').value = taskId;
                document.getElementById('deleteForm').submit();
            }
        }

        // เปิดใช้งาน drag & drop
        document.addEventListener('DOMContentLoaded', function() {
            const containers = document.querySelectorAll('.tasks-container');
            
            containers.forEach(container => {
                new Sortable(container, {
                    group: 'tasks',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        const taskId = evt.item.dataset.taskId;
                        const newStatus = evt.to.dataset.status;
                        
                        // อัปเดตสถานะอัตโนมัติ
                        updateStatus(taskId, newStatus);
                    }
                });
            });

            // ตั้งค่าวันที่ขั้นต่ำเป็นวันนี้
            document.getElementById('deadline').min = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>