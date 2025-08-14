<?php
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ไม่พบรหัสโปรแกรม");
}

// ลบข้อมูล
$stmt = $conn->prepare("DELETE FROM programs WHERE id = ?");
$stmt->execute([$id]);

header("Location: programs_list.php?msg=deleted");
exit();
