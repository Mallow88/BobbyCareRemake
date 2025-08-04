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
// เพิ่มตัวแปรเพื่อบอกว่าอัปโหลดได้ด้วย
$can_upload = false;

// ตรวจสอบว่าเป็นเจ้าของคำขอหรือไม่
$owner_check = $conn->prepare("SELECT user_id FROM service_requests WHERE id = ?");
$owner_check->execute([$service_request_id]);
$request_owner = $owner_check->fetch(PDO::FETCH_ASSOC);

if ($request_owner && $request_owner['user_id'] == $user_id) {
    $has_access = true;
    $can_upload = true;
}

// ผู้อนุมัติทุกระดับสามารถดูได้
if (in_array($user_role, ['divmgr', 'assignor', 'gmapprover', 'seniorgm'])) {
    $has_access = true;
    // พวกนี้ดูได้ แต่ไม่ให้ upload
}

// Developer ที่ได้รับมอบหมายสามารถดูและอัปโหลดได้
if ($user_role === 'developer') {
    $task_check = $conn->prepare("
        SELECT t.id FROM tasks t
        WHERE t.service_request_id = ? AND t.developer_user_id = ?
    ");
    $task_check->execute([$service_request_id, $user_id]);
    if ($task_check->rowCount() > 0) {
        $has_access = true;
        $can_upload = true; // ✅ ให้สิทธิ์อัปโหลด
    }
}

if (!$has_access) {
    http_response_code(403);
    exit('Access denied');
}

// ✅ สามารถใช้ $can_upload ต่อในหน้า attachment_display.php ได้ (เช่นเพื่อแสดงปุ่มอัปโหลด)
require_once __DIR__ . '/attachment_display.php';
displayAttachments($service_request_id, $can_upload); // เพิ่ม parameter
