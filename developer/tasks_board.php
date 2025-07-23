<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

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

// ดึงงานทั้งหมดของ developer
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.revision_notes,
        ur.reviewed_at as user_reviewed_at
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    WHERE t.developer_user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$developer_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        }

        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
            transform: rotate(5deg);
        }
        
        .sortable-drag {
            transform: rotate(5deg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .attachment-info-small {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 8px 10px;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
        }

        .review-section {
            background: linear-gradient(135deg, #f0fff4, #e6fffa);
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 1.2rem;
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
                            <div class="task-card pending" data-task-id="<?= $task['id'] ?>">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php
                                // แสดงไฟล์แนบแบบย่อ
                                $attach_stmt = $conn->prepare("SELECT COUNT(*) as count FROM request_attachments WHERE service_request_id = ?");
                                $attach_stmt->execute([$task['service_request_id']]);
                                $attach_count = $attach_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                if ($attach_count > 0):
                                ?>
                                <div class="attachment-info-small mb-2">
                                    <i class="fas fa-paperclip text-primary"></i>
                                    <span class="small text-muted"><?= $attach_count ?> ไฟล์แนบ</span>
                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="showAttachments(<?= $task['service_request_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
                            <div class="task-card received" data-task-id="<?= $task['id'] ?>">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php
                                // แสดงไฟล์แนบแบบย่อ
                                $attach_stmt = $conn->prepare("SELECT COUNT(*) as count FROM request_attachments WHERE service_request_id = ?");
                                $attach_stmt->execute([$task['service_request_id']]);
                                $attach_count = $attach_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                if ($attach_count > 0):
                                ?>
                                <div class="attachment-info-small mb-2">
                                    <i class="fas fa-paperclip text-primary"></i>
                                    <span class="small text-muted"><?= $attach_count ?> ไฟล์แนบ</span>
                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="showAttachments(<?= $task['service_request_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
                            <div class="task-card in_progress" data-task-id="<?= $task['id'] ?>">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php
                                // แสดงไฟล์แนบแบบย่อ
                                $attach_stmt = $conn->prepare("SELECT COUNT(*) as count FROM request_attachments WHERE service_request_id = ?");
                                $attach_stmt->execute([$task['service_request_id']]);
                                $attach_count = $attach_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                if ($attach_count > 0):
                                ?>
                                <div class="attachment-info-small mb-2">
                                    <i class="fas fa-paperclip text-primary"></i>
                                    <span class="small text-muted"><?= $attach_count ?> ไฟล์แนบ</span>
                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="showAttachments(<?= $task['service_request_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
                            <div class="task-card on_hold" data-task-id="<?= $task['id'] ?>">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
                                <?php
                                // แสดงไฟล์แนบแบบย่อ
                                $attach_stmt = $conn->prepare("SELECT COUNT(*) as count FROM request_attachments WHERE service_request_id = ?");
                                $attach_stmt->execute([$task['service_request_id']]);
                                $attach_count = $attach_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                if ($attach_count > 0):
                                ?>
                                <div class="attachment-info-small mb-2">
                                    <i class="fas fa-paperclip text-primary"></i>
                                    <span class="small text-muted"><?= $attach_count ?> ไฟล์แนบ</span>
                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="showAttachments(<?= $task['service_request_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
                            <div class="task-card completed" data-task-id="<?= $task['id'] ?>">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-description"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                
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

    <!-- Modal สำหรับแสดงไฟล์แนบ -->
    <div class="modal fade" id="attachmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paperclip me-2"></i>ไฟล์แนบ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="attachmentContent">
                    <!-- ไฟล์แนบจะแสดงที่นี่ -->
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentTaskId = null;
        
        function updateStatus(taskId, newStatus) {
            if (confirm('ยืนยันการเปลี่ยนสถานะงาน?')) {
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

        function showAttachments(serviceRequestId) {
            // โหลดไฟล์แนบผ่าน AJAX
            fetch(`../includes/get_attachments.php?service_request_id=${serviceRequestId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('attachmentContent').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('attachmentModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดไฟล์แนบ');
                });
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
                        
                        if (confirm('ยืนยันการเปลี่ยนสถานะงาน?')) {
                            updateStatus(taskId, newStatus);
                        } else {
                            // ถ้าไม่ยืนยัน ให้ย้ายกลับที่เดิม
                            evt.from.appendChild(evt.item);
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
        });
    </script>
</body>
</html>