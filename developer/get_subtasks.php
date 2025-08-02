<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    http_response_code(403);
    exit('Unauthorized');
}

$task_id = $_GET['task_id'] ?? null;
if (!$task_id) {
    http_response_code(400);
    exit('Missing task_id');
}

$developer_id = $_SESSION['user_id'];

// ตรวจสอบว่าเป็นงานของ developer นี้
$task_check = $conn->prepare("
    SELECT t.*, sr.title, s.name as service_name, sr.id as service_request_id
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    LEFT JOIN services s ON sr.service_id = s.id
    WHERE t.id = ? AND t.developer_user_id = ?
");
$task_check->execute([$task_id, $developer_id]);
$task = $task_check->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    http_response_code(403);
    exit('Access denied');
}

// ดึง subtask ปัจจุบัน
$subtasks_stmt = $conn->prepare("
    SELECT * FROM task_subtasks 
    WHERE task_id = ? 
    ORDER BY step_order
");
$subtasks_stmt->execute([$task_id]);
$subtasks = $subtasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ายังไม่มี subtask ใดเลย → สร้างขั้นแรก
if (empty($subtasks) && $task['service_name']) {
    $template_stmt = $conn->prepare("
        SELECT * FROM subtask_templates 
        WHERE service_type = ? AND is_active = 1 
        ORDER BY step_order
    ");
    $template_stmt->execute([$task['service_name']]);
    $templates = $template_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($templates)) {
        $first_step = $templates[0];
        $insert_stmt = $conn->prepare("
            INSERT INTO task_subtasks (task_id, step_order, step_name, step_description, percentage, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $insert_stmt->execute([
            $task_id,
            $first_step['step_order'],
            $first_step['step_name'],
            $first_step['step_description'],
            $first_step['percentage']
        ]);
    }

    // โหลดใหม่
    $subtasks_stmt->execute([$task_id]);
    $subtasks = $subtasks_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ถ้ามีขั้นเสร็จแล้ว → สร้างขั้นถัดไปจาก template
if (!empty($subtasks) && $task['service_name']) {
    $last_completed_order = 0;
    foreach ($subtasks as $s) {
        if ($s['status'] === 'completed' && $s['step_order'] > $last_completed_order) {
            $last_completed_order = $s['step_order'];
        }
    }

    $in_progress = false;
    foreach ($subtasks as $s) {
        if ($s['status'] === 'pending' || $s['status'] === 'in_progress') {
            $in_progress = true;
            break;
        }
    }

    if (!$in_progress) {
        $next_order = $last_completed_order + 1;

        $next_template = $conn->prepare("
            SELECT * FROM subtask_templates 
            WHERE service_type = ? AND is_active = 1 AND step_order = ? 
            LIMIT 1
        ");
        $next_template->execute([$task['service_name'], $next_order]);
        $next = $next_template->fetch(PDO::FETCH_ASSOC);

        if ($next) {
            $insert_stmt = $conn->prepare("
                INSERT INTO task_subtasks (task_id, step_order, step_name, step_description, percentage, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $insert_stmt->execute([
                $task_id,
                $next['step_order'],
                $next['step_name'],
                $next['step_description'],
                $next['percentage']
            ]);

            // โหลดใหม่
            $subtasks_stmt->execute([$task_id]);
            $subtasks = $subtasks_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// คำนวณ progress
$total_progress = 0;
$completed_steps = 0;
foreach ($subtasks as $subtask) {
    if ($subtask['status'] === 'completed') {
        $total_progress += $subtask['percentage'];
        $completed_steps++;
    }
}

// อัปเดต progress ใน tasks
$update_progress = $conn->prepare("UPDATE tasks SET progress_percentage = ? WHERE id = ?");
$update_progress->execute([$total_progress, $task_id]);

if ($total_progress >= 100) {
    $conn->prepare("UPDATE tasks SET task_status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$task_id]);
    $conn->prepare("UPDATE service_requests SET developer_status = 'completed' WHERE id = ?")->execute([$task['service_request_id']]);
}
?>

<!-- HTML แสดงความคืบหน้าและขั้นตอน -->
<div class="overall-progress">
    <h6><i class="fas fa-chart-line me-2"></i>ความคืบหน้ารวม</h6>
    <div class="progress-bar-container">
        <div class="progress-bar-fill" style="width: <?= $total_progress ?>%"><?= $total_progress ?>%</div>
    </div>
    <small class="text-muted">เสร็จแล้ว <?= $completed_steps ?> จาก <?= count($subtasks) ?> ขั้นตอน</small>
</div>

<div class="task-info mb-3">
    <h6 class="fw-bold text-primary">
        <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($task['title']) ?>
    </h6>
    <p class="text-muted mb-0">
        <i class="fas fa-code me-2"></i><?= htmlspecialchars($task['service_name']) ?>
    </p>
</div>

<ul class="subtask-list">
    <?php foreach ($subtasks as $subtask): ?>
    <li class="subtask-item <?= $subtask['status'] ?>">
        <div class="subtask-header">
            <div class="flex-grow-1">
                <div class="subtask-title"><?= $subtask['step_order'] ?>. <?= htmlspecialchars($subtask['step_name']) ?></div>
                <div class="subtask-description"><?= htmlspecialchars($subtask['step_description']) ?></div>
            </div>
        </div>

        <div class="subtask-progress">
            <div class="subtask-percentage"><?= $subtask['percentage'] ?>%</div>
            <span class="subtask-status-badge status-<?= $subtask['status'] ?>">
                <?= ['pending' => 'รอดำเนินการ', 'in_progress' => 'กำลังทำ', 'completed' => 'เสร็จแล้ว'][$subtask['status']] ?>
            </span>
        </div>

        <?php if ($subtask['status'] !== 'completed'): ?>
        <div class="subtask-actions">
            <?php if ($subtask['status'] === 'pending'): ?>
                <button class="subtask-btn btn-start" onclick="updateSubtaskStatus(<?= $subtask['id'] ?>, 'in_progress')">
                    <i class="fas fa-play me-1"></i>เริ่มทำ
                </button>
            <?php elseif ($subtask['status'] === 'in_progress'): ?>
                <button class="subtask-btn btn-finish" onclick="updateSubtaskStatus(<?= $subtask['id'] ?>, 'completed')">
                    <i class="fas fa-check me-1"></i>เสร็จแล้ว
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="subtask-notes">
            <textarea id="notes_<?= $subtask['id'] ?>" 
                      placeholder="หมายเหตุสำหรับขั้นตอนนี้..." 
                      class="form-control"
                      onblur="updateSubtaskNotes(<?= $subtask['id'] ?>)">
                <?= htmlspecialchars($subtask['notes'] ?? '') ?>
            </textarea>
        </div>

        <?php if ($subtask['started_at'] || $subtask['completed_at']): ?>
        <div class="subtask-dates">
            <?php if ($subtask['started_at']): ?>
                <i class="fas fa-play me-1"></i> เริ่ม: <?= date('d/m/Y H:i', strtotime($subtask['started_at'])) ?>
            <?php endif; ?>
            <?php if ($subtask['completed_at']): ?>
                <span class="ms-3"><i class="fas fa-check me-1"></i> เสร็จ: <?= date('d/m/Y H:i', strtotime($subtask['completed_at'])) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </li>
    <?php endforeach; ?>
</ul>

<script>
function updateSubtaskStatus(subtaskId, status) {
    const formData = new FormData();
    formData.append('subtask_id', subtaskId);
    formData.append('status', status);

    fetch('update_subtask_status.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert("เกิดข้อผิดพลาด: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("ไม่สามารถอัปเดตสถานะได้");
    });
}

function updateSubtaskNotes(subtaskId) {
    const textarea = document.getElementById("notes_" + subtaskId);
    const formData = new FormData();
    formData.append('subtask_id', subtaskId);
    formData.append('notes', textarea.value);

    fetch('update_subtask_notes.php', {
        method: 'POST',
        body: formData
    });
}
</script>


<style>
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
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #e2e8f0;
    transition: all 0.3s ease;
}

.subtask-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.subtask-item.pending {
    border-left-color: #f59e0b;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
}

.subtask-item.in_progress {
    border-left-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
}

.subtask-item.completed {
    border-left-color: #10b981;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
}

.subtask-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.subtask-title {
    font-weight: 600;
    color: #2d3748;
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.subtask-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
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
}

.subtask-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
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
    color: #2563eb;
}

.status-completed {
    background: #dcfce7;
    color: #16a34a;
}

.subtask-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.subtask-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-start {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn-start:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.btn-finish {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-finish:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-disabled {
    background: linear-gradient(135deg, #9ca3af, #6b7280);
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-disabled:hover {
    transform: none;
    box-shadow: none;
}

.subtask-notes textarea {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 10px;
    font-size: 0.9rem;
    resize: vertical;
    min-height: 60px;
    transition: all 0.3s ease;
}

.subtask-notes textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
}

.subtask-dates {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e9ecef;
}

.progress-bar-container {
    background: #e9ecef;
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-bar-fill {
    background: linear-gradient(90deg, #10b981, #059669);
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
    transition: width 0.5s ease;
}

.task-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #667eea;
}
</style>