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

// ดึงข้อมูลไฟล์แนบ + ผู้สร้าง service request
$stmt = $conn->prepare("
    SELECT ra.*, sr.user_id AS request_owner_id, sr.id AS service_request_id, sr.title AS request_title
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
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$has_access = false;

// ✅ 1. เจ้าของคำขอ
if ($file['request_owner_id'] == $user_id) {
    $has_access = true;
}

// ✅ 2. ผู้มี role พิเศษ
if (in_array($user_role, ['divmgr', 'assignor', 'gmapprover', 'seniorgm'])) {
    $has_access = true;
}

// ✅ 3. Developer ที่ได้รับมอบหมายกับคำขอนี้
if ($user_role === 'developer') {
    $task_check = $conn->prepare("
        SELECT id FROM tasks
        WHERE service_request_id = ? AND developer_user_id = ?
    ");
    $task_check->execute([$file['service_request_id'], $user_id]);
    if ($task_check->rowCount() > 0) {
        $has_access = true;
    }
}

if (!$has_access) {
    die("คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้");
}

// ตรวจสอบว่าไฟล์มีอยู่จริง
$file_path = __DIR__ . '/../uploads/' . $file['stored_filename'];

if (!file_exists($file_path)) {
    die("ไม่พบไฟล์ในระบบ");
}

// ส่งไฟล์ออกเพื่อดาวน์โหลด
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['original_filename']) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');

readfile($file_path);
exit();
?>
