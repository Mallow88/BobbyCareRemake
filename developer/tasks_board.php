<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// จัดการ subtask actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_subtask'])) {
        $subtask_id = $_POST['subtask_id'];
        $new_status = $_POST['new_status'];
        $notes = trim($_POST['notes'] ?? '');
        
        try {
            $conn->beginTransaction();
            
            // อัปเดตสถานะ subtask
            $update_subtask = $conn->prepare("
                UPDATE task_subtasks 
                SET status = ?, notes = ?, 
                    started_at = CASE WHEN ? = 'in_progress' AND started_at IS NULL THEN NOW() ELSE started_at END,
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE NULL END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $update_subtask->execute([$new_status, $notes, $new_status, $new_status, $subtask_id]);
            
            // บันทึก log
            $log_stmt = $conn->prepare("
                INSERT INTO subtask_logs (subtask_id, old_status, new_status, changed_by, notes) 
                VALUES (?, (SELECT status FROM task_subtasks WHERE id = ? LIMIT 1), ?, ?, ?)
            ");
            $log_stmt->execute([$subtask_id, $subtask_id, $new_status, $developer_id, $notes]);
            
            // คำนวณ progress รวม
            $task_id_stmt = $conn->prepare("SELECT task_id FROM task_subtasks WHERE id = ?");
            $task_id_stmt->execute([$subtask_id]);
            $task_id = $task_id_stmt->fetchColumn();
            
            $progress_stmt = $conn->prepare("
                SELECT SUM(CASE WHEN status = 'completed' THEN percentage ELSE 0 END) as total_progress
                FROM task_subtasks 
                WHERE task_id = ?
            ");
            $progress_stmt->execute([$task_id]);
            $total_progress = $progress_stmt->fetchColumn() ?? 0;
            
            // อัปเดต progress ในตาราง tasks
            $update_task = $conn->prepare("UPDATE tasks SET progress_percentage = ? WHERE id = ?");
            $update_task->execute([$total_progress, $task_id]);
            
            // ถ้า progress = 100% ให้เปลี่ยนสถานะเป็น completed
            if ($total_progress >= 100) {
                $complete_task = $conn->prepare("
                    UPDATE tasks 
                    SET task_status = 'completed', completed_at = NOW() 
                    WHERE id = ? AND task_status != 'completed'
                ");
                $complete_task->execute([$task_id]);
                
                $update_sr = $conn->prepare("UPDATE service_requests SET developer_status = 'completed' WHERE id = (SELECT service_request_id FROM tasks WHERE id = ?)");
                $update_sr->execute([$task_id]);
            }
            
            $conn->commit();
            header("Location: tasks_board.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

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
    <link rel="stylesheet" href="css/tasks_board.css">
    <style>
        .task-card.priority-urgent {
            border-left: 4px solid #dc2626 !important;
            box-shadow: 0 0 15px rgba(220, 38, 38, 0.3);
            order: 1;
        }

        .task-card.priority-high {
            border-left: 4px solid #ef4444 !important;
            order: 2;
        }

        .task-card.priority-medium {
            border-left: 4px solid #f59e0b !important;
            order: 3;
        }

        .task-card.priority-low {
            border-left: 4px solid #10b981 !important;
            opacity: 0.9;
            order: 4;
        }

        .tasks-container {
            display: flex;
            flex-direction: column;
        }

        .task-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }

        .detail-btn {
            background: rgba(102, 126, 234, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.7;
        }

        .detail-btn:hover {
            opacity: 1;
            transform: scale(1.1);
            background: rgba(102, 126, 234, 1);
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
                                
                                <div class="task-actions">
                                    <button class="detail-btn" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
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
                                    <?php if ($task['service_name']): ?>
                                    <div class="service-badge service-<?= $task['service_category'] ?>">
                                        <?php if ($task['service_category'] === 'development'): ?>
                                            <i class="fas fa-code"></i>
                                        <?php else: ?>
                                            <i class="fas fa-tools"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($task['service_name']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="priority-badge priority-<?= $task['priority_level'] ?? 'medium' ?>">
                                        <?= ucfirst($task['priority_level'] ?? 'medium') ?>
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
                                
                                <div class="task-actions">
                                    <button class="detail-btn" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
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
                                    
                                    <!-- ปุ่มดู Subtasks สำหรับงาน Development -->
                                    <?php if ($task['service_category'] === 'development' && $task['current_step'] !== 'developer_self_created'): ?>
                                        <button class="status-btn btn-subtask" onclick="showSubtasks(<?= $task['id'] ?>)">
                                            <i class="fas fa-tasks"></i> Subtasks
                                        </button>
                                    <?php endif; ?>
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
                                
                                <div class="task-actions">
                                    <button class="detail-btn" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
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
                                    
                                    <!-- ปุ่มดู Subtasks สำหรับงาน Development -->
                                    <?php if ($task['service_category'] === 'development' && $task['current_step'] !== 'developer_self_created'): ?>
                                        <button class="status-btn btn-subtask" onclick="showSubtasks(<?= $task['id'] ?>)">
                                            <i class="fas fa-tasks"></i> Subtasks
                                        </button>
                                    <?php endif; ?>
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
                                
                                <div class="task-actions">
                                    <button class="detail-btn" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
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
                                
                                <div class="task-actions">
                                    <button class="detail-btn" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
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
                        <i class="fas fa-clipboard-list me-2"></i>รายละเอียดงาน
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">
                                    <i class="fas fa-heading me-2"></i>หัวข้องาน
                                </h6>
                                <div id="detailTitle" class="bg-light p-3 rounded fw-bold"></div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">
                                    <i class="fas fa-align-left me-2"></i>รายละเอียดงาน
                                </h6>
                                <div id="detailDescription" class="bg-light p-3 rounded"></div>
                            </div>
                            
                            <div class="mb-3" id="detailBenefits" style="display: none;">
                                <h6 class="fw-bold text-success">
                                    <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                                </h6>
                                <div id="detailBenefitsContent" class="bg-success bg-opacity-10 p-3 rounded border-start border-success border-4"></div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">
                                    <i class="fas fa-user me-2"></i>ข้อมูลผู้ร้องขอ
                                </h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">ชื่อ-นามสกุล</small>
                                            <div id="detailRequester" class="fw-bold"></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">รหัสพนักงาน</small>
                                            <div id="detailEmployeeId" class="fw-bold"></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">ตำแหน่ง</small>
                                            <div id="detailPosition" class="fw-bold"></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">หน่วยงาน</small>
                                            <div id="detailDepartment" class="fw-bold"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">
                                    <i class="fas fa-cogs me-2"></i>ข้อมูลงาน
                                </h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="mb-2">
                                        <small class="text-muted">ประเภทบริการ</small>
                                        <div id="detailService" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">หัวข้องานคลัง</small>
                                        <div id="detailWorkCategory" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">ความสำคัญ</small>
                                        <div id="detailPriority" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">เวลาแทน</small>
                                        <div id="detailEstimatedDays" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">ผู้มอบหมาย</small>
                                        <div id="detailAssignor" class="fw-bold"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">
                                    <i class="fas fa-chart-line me-2"></i>ความคืบหน้า
                                </h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="mb-2">
                                        <small class="text-muted">สถานะ</small>
                                        <div id="detailStatus" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">ความคืบหน้า</small>
                                        <div class="progress mb-1">
                                            <div id="detailProgress" class="progress-bar bg-primary" role="progressbar"></div>
                                        </div>
                                        <small id="detailProgressText" class="text-muted"></small>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">วันที่รับงาน</small>
                                        <div id="detailAcceptedAt" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">วันที่ควรเสร็จ</small>
                                        <div id="detailExpectedCompletion" class="fw-bold"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary">
                                    <i class="fas fa-paperclip me-2"></i>ไฟล์แนบ
                                </h6>
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

    <!-- Modal สำหรับ Subtasks -->
    <div class="modal fade" id="subtaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks me-2"></i>รายละเอียดขั้นตอนการพัฒนา
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="subtaskContent">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fs-1 text-primary"></i>
                            <p class="mt-3">กำลังโหลดข้อมูล...</p>
                        </div>
                    </div>
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
                                    <label for="estimated_days" class="form-label fw-bold">เวลาแทน:</label>
                                    <input type="number" class="form-control" id="estimated_days" name="estimated_days" 
                                           min="1" max="365" value="1" placeholder="จำนวนวันที่คาดว่าจะใช้">
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
        let currentCompleteTaskId = null;
        
        // ดึงข้อมูลรายละเอียดงาน
        function showTaskDetail(taskId) {
            // ค้นหาข้อมูลงานจาก tasks array
            const tasks = <?= json_encode($tasks) ?>;
            const task = tasks.find(t => t.id == taskId);
            
            if (!task) {
                alert('ไม่พบข้อมูลงาน');
                return;
            }
            
            // แสดงข้อมูลในป๊อปอัพ
            document.getElementById('detailTitle').textContent = task.title;
            document.getElementById('detailDescription').innerHTML = task.description.replace(/\n/g, '<br>');
            
            // ข้อมูลผู้ร้องขอ
            document.getElementById('detailRequester').textContent = task.requester_name + ' ' + task.requester_lastname;
            document.getElementById('detailEmployeeId').textContent = task.employee_id || 'ไม่ระบุ';
            document.getElementById('detailPosition').textContent = task.position || 'ไม่ระบุ';
            document.getElementById('detailDepartment').textContent = task.department || 'ไม่ระบุ';
            
            // ข้อมูลงาน
            document.getElementById('detailService').textContent = task.service_name || 'ไม่ระบุ';
            document.getElementById('detailWorkCategory').textContent = task.work_category || 'ไม่ระบุ';
            
            const priorityLabels = {
                'urgent': 'เร่งด่วน',
                'high': 'สูง', 
                'medium': 'ปานกลาง',
                'low': 'ต่ำ'
            };
            document.getElementById('detailPriority').textContent = priorityLabels[task.priority] || 'ปานกลาง';
            document.getElementById('detailEstimatedDays').textContent = (task.estimated_days || 1) + ' วัน';
            document.getElementById('detailAssignor').textContent = task.assignor_name || 'ไม่ระบุ';
            
            // สถานะและความคืบหน้า
            const statusLabels = {
                'pending': 'รอรับ',
                'received': 'รับแล้ว',
                'in_progress': 'กำลังทำ',
                'on_hold': 'พักงาน',
                'completed': 'เสร็จแล้ว'
            };
            document.getElementById('detailStatus').textContent = statusLabels[task.task_status] || 'ไม่ทราบ';
            
            const progress = task.progress_percentage || 0;
            const progressBar = document.getElementById('detailProgress');
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            document.getElementById('detailProgressText').textContent = 'อัปเดตล่าสุด: ' + new Date(task.updated_at).toLocaleDateString('th-TH');
            
            // วันที่
            document.getElementById('detailAcceptedAt').textContent = task.accepted_at ? 
                new Date(task.accepted_at).toLocaleDateString('th-TH') : 'ยังไม่รับงาน';
                
            // คำนวณวันที่ควรเสร็จ
            if (task.accepted_at && task.estimated_days) {
                const acceptedDate = new Date(task.accepted_at);
                const expectedDate = new Date(acceptedDate.getTime() + (task.estimated_days * 24 * 60 * 60 * 1000));
                document.getElementById('detailExpectedCompletion').textContent = expectedDate.toLocaleDateString('th-TH');
            } else {
                document.getElementById('detailExpectedCompletion').textContent = 'ไม่สามารถคำนวณได้';
            }
            
            // แสดงประโยชน์ที่คาดว่าจะได้รับ (ถ้ามี)
            if (task.expected_benefits) {
                document.getElementById('detailBenefits').style.display = 'block';
                document.getElementById('detailBenefitsContent').innerHTML = task.expected_benefits.replace(/\n/g, '<br>');
            } else {
                document.getElementById('detailBenefits').style.display = 'none';
            }
            
            // โหลดไฟล์แนบ
            loadAttachments(task.service_request_id);
            
            // เปิด Modal
            const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            modal.show();
        }
        
        // โหลดไฟล์แนบ
        function loadAttachments(serviceRequestId) {
            const attachmentsList = document.getElementById('attachmentsList');
            
            fetch(`../includes/get_attachments.php?service_request_id=${serviceRequestId}`)
                .then(response => response.text())
                .then(html => {
                    if (html.trim() === '') {
                        attachmentsList.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-inbox fa-2x mb-2"></i><p>ไม่มีไฟล์แนบ</p></div>';
                    } else {
                        attachmentsList.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error loading attachments:', error);
                    attachmentsList.innerHTML = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>เกิดข้อผิดพลาดในการโหลดไฟล์แนบ</p></div>';
                });
        }
        
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

        function showSubtasks(taskId) {
            const modal = new bootstrap.Modal(document.getElementById('subtaskModal'));
            
            // โหลดข้อมูล subtasks
            fetch(`get_subtasks.php?task_id=${taskId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('subtaskContent').innerHTML = html;
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('subtaskContent').innerHTML = 
                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                    modal.show();
                });
        }
        
        function updateSubtaskStatus(subtaskId, newStatus) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const subtaskInput = document.createElement('input');
            subtaskInput.name = 'subtask_id';
            subtaskInput.value = subtaskId;
            
            const statusInput = document.createElement('input');
            statusInput.name = 'new_status';
            statusInput.value = newStatus;
            
            const actionInput = document.createElement('input');
            actionInput.name = 'update_subtask';
            actionInput.value = '1';
            
            // เพิ่ม notes ถ้ามี
            const notesTextarea = document.querySelector(`#notes_${subtaskId}`);
            if (notesTextarea) {
                const notesInput = document.createElement('input');
                notesInput.name = 'notes';
                notesInput.value = notesTextarea.value;
                form.appendChild(notesInput);
            }
            
            form.appendChild(subtaskInput);
            form.appendChild(statusInput);
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
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
            
            // เรียงงานตามความสำคัญในแต่ละคอลัมน์
            sortTasksByPriority();
        });
        
        // เรียงงานตามความสำคัญ
        function sortTasksByPriority() {
            const containers = document.querySelectorAll('.tasks-container');
            
            containers.forEach(container => {
                const tasks = Array.from(container.querySelectorAll('.task-card'));
                
                tasks.sort((a, b) => {
                    const priorityOrder = {
                        'priority-urgent': 1,
                        'priority-high': 2,
                        'priority-medium': 3,
                        'priority-low': 4
                    };
                    
                    let aPriority = 5;
                    let bPriority = 5;
                    
                    for (let className of a.classList) {
                        if (priorityOrder[className]) {
                            aPriority = priorityOrder[className];
                            break;
                        }
                    }
                    
                    for (let className of b.classList) {
                        if (priorityOrder[className]) {
                            bPriority = priorityOrder[className];
                            break;
                        }
                    }
                    
                    return aPriority - bPriority;
                });
                
                // เรียงใหม่ใน DOM
                tasks.forEach(task => container.appendChild(task));
            });
        }
    </script>
    
    <style>
        .btn-complete { background: #10b981; }
        .btn-complete:hover { background: #059669; }
        
        .btn-subtask { 
            background: #8b5cf6; 
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            margin-top: 5px;
            width: 100%;
        }
        .btn-subtask:hover { 
            background: #7c3aed; 
            color: white;
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
        
        .subtask-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .subtask-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .subtask-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .subtask-item.pending {
            border-left-color: #f59e0b;
        }
        
        .subtask-item.in_progress {
            border-left-color: #3b82f6;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        }
        
        .subtask-item.completed {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }
        
        .subtask-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .subtask-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .subtask-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .subtask-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .subtask-percentage {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 50px;
            text-align: center;
        }
        
        .subtask-status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-in_progress {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .subtask-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .subtask-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-start {
            background: #3b82f6;
            color: white;
        }
        
        .btn-start:hover {
            background: #2563eb;
        }
        
        .btn-finish {
            background: #10b981;
            color: white;
        }
        
        .btn-finish:hover {
            background: #059669;
        }
        
        .subtask-notes {
            margin-top: 10px;
        }
        
        .subtask-notes textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 60px;
        }
        
        .subtask-dates {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 10px;
        }
        
        .overall-progress {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .overall-progress h6 {
            margin-bottom: 10px;
            color: #4a5568;
        }
        
        .progress-bar-container {
            background: #e2e8f0;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-bar-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .kanban-column {
                min-height: auto;
            }
        }
    </style>
</body>
</html>