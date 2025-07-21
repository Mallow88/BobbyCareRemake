<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$name = $_POST['name'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

// ตรวจสอบค่าว่าง
if (empty($name)) {
    die("กรุณากรอกชื่อ");
}

// อัปเดตฐานข้อมูล
$stmt = $conn->prepare("UPDATE users SET name = ?, lastname = ?, email = ?, phone = ? WHERE id = ?");
$result = $stmt->execute([$name, $lastname, $email, $phone, $user_id]);

if ($result) {
    // อัปเดต session ด้วย
    $_SESSION['name'] = $name;
    $_SESSION['gmail'] = $email;
    header("Location: profile.php");
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
}
