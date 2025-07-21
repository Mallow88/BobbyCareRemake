<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่าเป็นแอดมิน
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    die("ไม่พบ ID ผู้ใช้");
}

// ลบผู้ใช้
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// log การลบ
$admin_id = $_SESSION['admin_id'];
$conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)")
     ->execute([$admin_id, "ลบผู้ใช้ ID $user_id"]);

header("Location: manage_users.php");
exit();
