<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$subtask_id = $_POST['subtask_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if (!$subtask_id || !in_array($new_status, ['pending', 'in_progress', 'completed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// ดึง subtask ปัจจุบัน
$stmt = $conn->prepare("SELECT * FROM task_subtasks WHERE id = ?");
$stmt->execute([$subtask_id]);
$subtask = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subtask) {
    echo json_encode(['success' => false, 'message' => 'Subtask not found']);
    exit;
}

// อัปเดตสถานะ subtask
$update = $conn->prepare("
    UPDATE task_subtasks 
    SET status = ?, 
        started_at = IF(? = 'in_progress' AND started_at IS NULL, NOW(), started_at),
        completed_at = IF(? = 'completed', NOW(), completed_at)
    WHERE id = ?
");
$update->execute([$new_status, $new_status, $new_status, $subtask_id]);

// ถ้าเสร็จขั้นนี้แล้ว → สร้างขั้นถัดไป
if ($new_status === 'completed') {
    echo "Subtask marked as completed\n";
    $next_order = $subtask['step_order'] + 1;

    // ตรวจว่ามีขั้นถัดไปหรือยัง
    $check = $conn->prepare("SELECT COUNT(*) FROM task_subtasks WHERE task_id = ? AND step_order = ?");
    $check->execute([$subtask['task_id'], $next_order]);
    $exists = $check->fetchColumn();
    echo "Next step exists? " . $exists . "\n";

    if (!$exists) {
        // ดึง service_name
        $service_stmt = $conn->prepare("
            SELECT sr.service_id, s.name AS service_name
            FROM tasks t
            JOIN service_requests sr ON t.service_request_id = sr.id
            JOIN services s ON sr.service_id = s.id
            WHERE t.id = ?
        ");
        $service_stmt->execute([$subtask['task_id']]);
        $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
        echo "Service name: " . $service['service_name'] . "\n";

        // ดึง template ขั้นถัดไป
        $template_stmt = $conn->prepare("
            SELECT * FROM subtask_templates 
            WHERE service_type = ? AND step_order = ?
        ");
        $template_stmt->execute([$service['service_name'], $next_order]);
        $template = $template_stmt->fetch(PDO::FETCH_ASSOC);
        echo "Template step found? " . ($template ? 'yes' : 'no') . "\n";

        if ($template) {
            echo "Inserting next step: " . $template['step_name'] . "\n";
            // สร้าง subtask ขั้นถัดไป
            $insert = $conn->prepare("
                INSERT INTO task_subtasks 
                (task_id, step_order, step_name, step_description, percentage, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $insert->execute([
                $subtask['task_id'],
                $template['step_order'],
                $template['step_name'],
                $template['step_description'],
                $template['percentage']
            ]);
        }
    }
}



// คำนวณความคืบหน้าใหม่
$sum_stmt = $conn->prepare("
    SELECT SUM(percentage) FROM task_subtasks 
    WHERE task_id = ? AND status = 'completed'
");
$sum_stmt->execute([$subtask['task_id']]);
$total_progress = $sum_stmt->fetchColumn() ?? 0;

$update_task = $conn->prepare("UPDATE tasks SET progress_percentage = ? WHERE id = ?");
$update_task->execute([$total_progress, $subtask['task_id']]);

// ถ้าครบ 100% → ปิดงาน
if ($total_progress >= 100) {
    $conn->prepare("UPDATE tasks SET task_status = 'completed', completed_at = NOW() WHERE id = ?")
         ->execute([$subtask['task_id']]);

    $conn->prepare("
        UPDATE service_requests 
        SET developer_status = 'completed'
        WHERE id = (SELECT service_request_id FROM tasks WHERE id = ?)
    ")->execute([$subtask['task_id']]);
}

echo json_encode(['success' => true]);
