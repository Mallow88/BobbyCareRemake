<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่า login แล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// ถ้ามีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title !== '' && $description !== '') {
        $stmt = $conn->prepare("INSERT INTO service_requests (user_id, title, description, status, assigned_to_admin_id) VALUES (?, ?, ?, 'pending', NULL)");
        $stmt->execute([$user_id, $title, $description]);

        header("Location: index.php");
        exit();
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>สร้างคำขอบริการใหม่</title>
</head>
<body>
    <h1>➕ สร้างคำขอบริการใหม่</h1>

    <p><a href="index.php">← ย้อนกลับ</a></p>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <p>
            <label>หัวข้อ:<br>
                <input type="text" name="title" required style="width: 300px;">
            </label>
        </p>
        <p>
            <label>รายละเอียด:<br>
                <textarea name="description" rows="5" cols="50" required></textarea>
            </label>
        </p>
        <p>
            <button type="submit">📤 ส่งคำขอ</button>
        </p>
    </form>
</body>
</html>
