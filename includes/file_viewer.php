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

// ตรวจสอบสิทธิ์การเข้าถึง
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$has_access = false;

// เจ้าของคำขอสามารถดูได้
if ($file['user_id'] == $user_id) {
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

// กำหนด Content-Type
$mime_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed'
];

$extension = strtolower($file['file_type']);
$content_type = $mime_types[$extension] ?? 'application/octet-stream';

// ส่งไฟล์
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . $file['original_filename'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=3600');

readfile($file_path);
exit();
?>