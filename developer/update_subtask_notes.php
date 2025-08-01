<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$subtask_id = $_POST['subtask_id'] ?? null;
$notes = $_POST['notes'] ?? '';
$developer_id = $_SESSION['user_id'];

if (!$subtask_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// ตรวจสอบว่าเป็น subtask ของ developer นี้
$check_stmt = $conn->prepare("
    SELECT ts.id
    FROM task_subtasks ts
    JOIN tasks t ON ts.task_id = t.id
    WHERE ts.id = ? AND t.developer_user_id = ?
");
$check_stmt->execute([$subtask_id, $developer_id]);

if (!$check_stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $update_stmt = $conn->prepare("
        UPDATE task_subtasks 
        SET notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $update_stmt->execute([$notes, $subtask_id]);
    
    echo json_encode(['success' => true, 'message' => 'บันทึกหมายเหตุเรียบร้อย']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>