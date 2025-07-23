<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_requests.php");
    exit();
}

$assignor_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'];
$status = $_POST['status'];
$reason = $_POST['reason'] ?? '';
$assigned_developer_id = $_POST['assigned_developer_id'] ?? null;
$priority_level = $_POST['priority_level'] ?? 'medium';
$estimated_hours = $_POST['estimated_hours'] ?? null;

// ตรวจสอบข้อมูล
if ($status === 'rejected' && trim($reason) === '') {
    $_SESSION['error'] = "กรุณาระบุเหตุผลเมื่อไม่อนุมัติ";
    header("Location: view_requests.php");
    exit();
}

if ($status === 'approved' && !$assigned_developer_id) {
    $_SESSION['error'] = "กรุณาเลือกผู้พัฒนาที่จะมอบหมายงาน";
    header("Location: view_requests.php");
    exit();
}

try {
    $conn->beginTransaction();

    // บันทึกการอนุมัติ
    $stmt = $conn->prepare("
        INSERT INTO assignor_approvals (
            service_request_id, assignor_user_id, assigned_developer_id, 
            status, reason, estimated_hours, priority_level, reviewed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        assigned_developer_id = VALUES(assigned_developer_id),
        status = VALUES(status), 
        reason = VALUES(reason), 
        estimated_hours = VALUES(estimated_hours),
        priority_level = VALUES(priority_level),
        reviewed_at = NOW()
    ");
    $stmt->execute([
        $request_id, $assignor_id, $assigned_developer_id, 
        $status, $reason, $estimated_hours, $priority_level
    ]);

    // อัปเดตสถานะใน service_requests
    $new_status = $status === 'approved' ? 'gm_review' : 'rejected';
    $current_step = $status === 'approved' ? 'assignor_approved' : 'assignor_rejected';
    
    $stmt = $conn->prepare("
        UPDATE service_requests 
        SET status = ?, current_step = ?, priority = ?, estimated_hours = ? 
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $current_step, $priority_level, $estimated_hours, $request_id]);

    // บันทึก log
    $stmt = $conn->prepare("
        INSERT INTO document_status_logs (
            service_request_id, step_name, status, reviewer_id, reviewer_role, notes
        ) VALUES (?, 'assignor_review', ?, ?, 'assignor', ?)
    ");
    $stmt->execute([$request_id, $status, $assignor_id, $reason]);

    $conn->commit();
    $_SESSION['success'] = "บันทึกผลการพิจารณาเรียบร้อยแล้ว";

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

header("Location: view_requests.php");
exit();
?>