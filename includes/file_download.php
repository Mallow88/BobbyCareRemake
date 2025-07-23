<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$file_id = $_GET['id'] ?? null;
if (!$file_id) {
    die("ไม่พบไฟล์ที่ระบุ");
}

// ดึงข้อมูลไฟล์
$stmt = $conn->prepare("
    SELECT ra.*, sr.user_id, sr.title as request_title
    FROM request_attachments ra
    JOIN service_requests sr ON ra.service_request_id = sr.id
    WHERE ra.id = ?
");
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("ไม่พบไฟล์");
}

// ตรวจสอบสิทธิ์การเข้าถึง (เหมือนกับ file_viewer.php)
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$has_access = false;

if ($file['user_id'] == $user_id) {
    $has_access = true;
}

if (in_array($user_role, ['divmgr', 'assignor', 'gmapprover', 'seniorgm'])) {
    $has_access = true;
}

if ($user_role === 'developer') {
    $task_check = $conn->prepare("
        SELECT t.id FROM tasks t
        JOIN service_requests sr ON t.service_request_id = sr.id
        WHERE sr.id = ? AND t.developer_user_id = ?
    ");
    $task_check->execute([$file['service_request_id'], $user_id]);
    if ($task_check->rowCount() > 0) {
        $has_access = true;
    }
}

if (!$has_access) {
    die("คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้");
}

$file_path = __DIR__ . '/../uploads/' . $file['stored_filename'];

if (!file_exists($file_path)) {
    die("ไม่พบไฟล์ในระบบ");
}

// ส่งไฟล์สำหรับดาวน์โหลด
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');

readfile($file_path);
exit();
?>