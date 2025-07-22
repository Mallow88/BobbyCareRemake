<?php 
session_start();
require_once __DIR__ . '/config/database.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลจากฐานข้อมูล
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้ในระบบ");
}

// แสดงข้อมูล
$line_id = $user['line_id'];
$display_name = $_SESSION['display_name'] ?? '';
$email = $user['email'] ?? '';
$name = $user['name'] ?? '';
$lastname = $user['lastname'] ?? '';
$phone = $user['phone'] ?? '';
$picture_url = $_SESSION['picture_url'] ?? null;
?>
<?php
// ...
if ($user['role'] === 'admin') {
    echo "<p>คุณคือแอดมิน 🛡️</p>";
} elseif ($user['role'] === 'staff') {
    echo "<p>คุณคือเจ้าหน้าที่ 👷‍♂️</p>";
} else {
    echo "<p>คุณคือผู้ใช้งานทั่วไป 👤</p>";
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ผู้ใช้</title>
</head>
<body>
    <h1>สวัสดี, <?php echo htmlspecialchars($display_name); ?></h1>

    <?php if ($picture_url): ?>
        <img src="<?php echo htmlspecialchars($picture_url); ?>" alt="Profile Picture" width="150">
    <?php endif; ?>

    <p><strong>LINE ID:</strong> <?php echo htmlspecialchars($line_id); ?></p>
    <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($name); ?></p>
    <p><strong>นามสกุล:</strong> <?php echo htmlspecialchars($lastname); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
    <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($phone); ?></p>

    <p><a href="edit_profile.php">🔧 แก้ไขโปรไฟล์</a></p>
    <p><a href="logout.php">🚪 ออกจากระบบ</a></p>
    <p><a href="dashboard.php">🚪 ย้อยกลับ</a></p>
</body>
</html>
