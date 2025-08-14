<?php
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ไม่พบรหัสแผนก");
}

// ลบข้อมูล
$stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
$stmt->execute([$id]);

header("Location: departments_list.php?msg=deleted");
exit();
