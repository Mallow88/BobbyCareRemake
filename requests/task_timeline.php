<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "ไม่อนุญาตให้เข้าถึง";
    exit();
}

$developer_id = $_SESSION['user_id'];

$task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
if ($task_id <= 0) {
    echo "<div class='alert alert-danger'>ข้อมูลงานไม่ถูกต้อง</div>";
    exit();
}

// ตรวจสอบว่างานนี้เป็นของ developer นี้หรือไม่
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND developer_user_id = ?");
$stmt->execute([$task_id, $developer_id]);
$task = $stmt->fetch();

if (!$task) {
    echo "<div class='alert alert-danger'>ไม่พบงานนี้ หรือคุณไม่มีสิทธิ์ดู</div>";
    exit();
}

// ดึง subtasks
$sub_stmt = $conn->prepare("SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY step_order ASC");
$sub_stmt->execute([$task_id]);
$subtasks = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .timeline {
        list-style: none;
        padding-left: 0;
    }
    .timeline-item {
        margin-bottom: 1rem;
        padding-left: 1rem;
        border-left: 3px solid #0d6efd;
        position: relative;
    }
    .timeline-item::before {
        content: "";
        width: 12px;
        height: 12px;
        background: #0d6efd;
        border-radius: 50%;
        position: absolute;
        left: -7px;
        top: 0.4rem;
    }
    .subtask-status {
        font-weight: bold;
        text-transform: capitalize;
    }
    .badge-status-pending {
        background-color: gray;
    }
    .badge-status-in_progress {
        background-color: orange;
        color: black;
    }
    .badge-status-completed {
        background-color: green;
    }
</style>

<h5>ไทม์ไลน์ของงานของคุณ #<?= htmlspecialchars($task['id']) ?></h5>
<p><strong>สถานะงาน:</strong> <?= htmlspecialchars($task['task_status']) ?></p>
<p><strong>เปอร์เซ็นต์ความคืบหน้า:</strong> <?= $task['progress_percentage'] ?>%</p>
<p><strong>เริ่มงาน:</strong> <?= $task['started_at'] ?? '-' ?></p>
<p><strong>เสร็จงาน:</strong> <?= $task['completed_at'] ?? '-' ?></p>
<p><strong>หมายเหตุผู้พัฒนา:</strong> <?= nl2br(htmlspecialchars($task['developer_notes'] ?? '-')) ?></p>

<h6>ขั้นตอนย่อย</h6>
<?php if (!$subtasks): ?>
    <p class="text-muted">ไม่มีขั้นตอนย่อย</p>
<?php else: ?>
    <ul class="timeline">
        <?php foreach ($subtasks as $sub): ?>
            <li class="timeline-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong><?= htmlspecialchars($sub['step_order']) ?>. <?= htmlspecialchars($sub['step_name']) ?></strong><br />
                        <small class="text-muted"><?= htmlspecialchars($sub['step_description'] ?? '') ?></small>
                    </div>
                    <div>
                        <span class="badge 
                            <?= $sub['status'] === 'completed' ? 'badge-status-completed' : 
                                ($sub['status'] === 'in_progress' ? 'badge-status-in_progress' : 'badge-status-pending') ?>
                            subtask-status">
                            <?= htmlspecialchars($sub['status']) ?>
                        </span>
                    </div>
                </div>
                <small>เริ่ม: <?= $sub['started_at'] ?? '-' ?> | เสร็จ: <?= $sub['completed_at'] ?? '-' ?></small><br />
                <?php if (!empty($sub['notes'])): ?>
                    <small class="text-muted">หมายเหตุ: <?= htmlspecialchars($sub['notes']) ?></small>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
