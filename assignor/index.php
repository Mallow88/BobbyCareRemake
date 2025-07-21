<?php
session_start();

// ตรวจสอบว่า login แล้ว และมี role เป็น assignor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

$assignor_name = $_SESSION['name'] ?? '';

// เรียกข้อมูล service requests ได้ที่นี่ ถ้าต้องการ
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดผู้จัดการแผนก</title>
</head>
<body>
    <h1>👨‍💼 ยินดีต้อนรับผู้จัดการแผนก: <?= htmlspecialchars($assignor_name) ?></h1>

    <p><a href="../logout.php">🚪 ออกจากระบบ</a></p>

    <h2>📋 เมนู</h2>
    <ul>
        <li><a href="view_requests.php">📄 ตรวจสอบคำขอบริการ</a></li>
        <!-- เพิ่มเมนูอื่นๆ ได้ตามต้องการ -->
    </ul>
</body>
</html>
