<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$subtask_id = $_POST['subtask_id'] ?? null;
$new_status = $_POST['status'] ?? null;
$developer_id = $_SESSION['user_id'];

if (!$subtask_id || !$new_status) {
    http_response_code(400);
    exit('Missing parameters');
}

// ตรวจสอบว่าเป็น subtask ของ developer นี้
$check_stmt = $conn->prepare("
    SELECT ts.*, t.developer_user_id, t.id as task_id, t.service_request_id
    FROM task_subtasks ts
    JOIN tasks t ON ts.task_id = t.id
    WHERE ts.id = ? AND t.developer_user_id = ?
");
$check_stmt->execute([$subtask_id, $developer_id]);
$subtask = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$subtask) {
    http_response_code(403);
    exit('Access denied');
}

// ตรวจสอบลำดับ step_order (ถ้าสถานะใหม่คือ in_progress ต้องให้ step ก่อนหน้าทำเสร็จหมดก่อน)
if ($new_status === 'in_progress' && $subtask['step_order'] > 1) {
    $prev_steps_stmt = $conn->prepare("
        SELECT COUNT(*) as incomplete_count 
        FROM task_subtasks 
        WHERE task_id = ? 
          AND step_order < ? 
          AND status != 'completed'
    ");
    $prev_steps_stmt->execute([$subtask['task_id'], $subtask['step_order']]);
    $prev_check = $prev_steps_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prev_check['incomplete_count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถเริ่มขั้นตอนนี้ได้ กรุณาทำขั้นตอนก่อนหน้าให้เสร็จ'
        ]);
        exit;
    }
}

try {
    $conn->beginTransaction();
    
    // อัปเดตสถานะ subtask
    $update_stmt = $conn->prepare("
        UPDATE task_subtasks 
        SET status = ?, 
            started_at = CASE WHEN ? = 'in_progress' AND started_at IS NULL THEN NOW() ELSE started_at END,
            completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
            updated_at = NOW()
        WHERE id = ?
    ");
    $update_stmt->execute([$new_status, $new_status, $new_status, $subtask_id]);
    
    // บันทึก log
    $log_stmt = $conn->prepare("
        INSERT INTO subtask_logs (subtask_id, old_status, new_status, changed_by, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $log_stmt->execute([$subtask_id, $subtask['status'], $new_status, $developer_id]);
    
    // คำนวณ progress รวม
    $progress_stmt = $conn->prepare("
        SELECT SUM(percentage) as total_progress
        FROM task_subtasks 
        WHERE task_id = ? AND status = 'completed'
    ");
    $progress_stmt->execute([$subtask['task_id']]);
    $progress_result = $progress_stmt->fetch(PDO::FETCH_ASSOC);
    $total_progress = $progress_result['total_progress'] ?? 0;
    
    // อัปเดต progress ในตาราง tasks
    $update_task = $conn->prepare("UPDATE tasks SET progress_percentage = ? WHERE id = ?");
    $update_task->execute([$total_progress, $subtask['task_id']]);
    
    // ถ้า progress = 100% ให้เปลี่ยนสถานะเป็น completed
    if ($total_progress >= 100) {
        $update_status = $conn->prepare("
            UPDATE tasks 
            SET task_status = 'completed', completed_at = NOW() 
            WHERE id = ? AND task_status != 'completed'
        ");
        $update_status->execute([$subtask['task_id']]);
        
        $update_sr = $conn->prepare("
            UPDATE service_requests 
            SET developer_status = 'completed' 
            WHERE id = ?
        ");
        $update_sr->execute([$subtask['service_request_id']]);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตสถานะเรียบร้อย',
        'total_progress' => $total_progress
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
