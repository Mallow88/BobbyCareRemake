<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$service_request_id = $_GET['service_request_id'] ?? null;
if (!$service_request_id) {
    http_response_code(400);
    exit('Missing service_request_id');
}

// ตรวจสอบสิทธิ์การเข้าถึง
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$has_access = false;

// ตรวจสอบว่าเป็นเจ้าของคำขอหรือไม่
$owner_check = $conn->prepare("SELECT user_id FROM service_requests WHERE id = ?");
$owner_check->execute([$service_request_id]);
$request_owner = $owner_check->fetch(PDO::FETCH_ASSOC);

if ($request_owner && $request_owner['user_id'] == $user_id) {
    $has_access = true;
}

// ผู้อนุมัติทุกระดับสามารถดูได้
if (in_array($user_role, ['divmgr', 'assignor', 'gmapprover', 'seniorgm'])) {
    $has_access = true;
}

// Developer ที่ได้รับมอบหมายสามารถดูได้
if ($user_role === 'developer') {
    $task_check = $conn->prepare("
        SELECT t.id FROM tasks t
        WHERE t.service_request_id = ? AND t.developer_user_id = ?
    ");
    $task_check->execute([$service_request_id, $user_id]);
    if ($task_check->rowCount() > 0) {
        $has_access = true;
    }
}

if (!$has_access) {
    http_response_code(403);
    exit('Access denied');
}

// แสดงไฟล์แนบ
require_once __DIR__ . '/attachment_display.php';
displayAttachments($service_request_id);
?>