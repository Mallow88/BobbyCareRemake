<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['line_id'])) {
    // ถ้าเข้าตรง ๆ โดยไม่ login → กลับหน้าแรก
    header("Location: ../index.php");
    exit();
}

// เมื่อมีการกด submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $line_id = $_SESSION['line_id'];
    $name = $_POST['name'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($name && $lastname) {
        // บันทึกลงฐานข้อมูล
        $stmt = $conn->prepare("INSERT INTO users (line_id, name, lastname, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$line_id, $name, $lastname, $email]);

        // เก็บ session แล้วพาไป dashboard
        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['name'] = $name;
        header("Location: ../dashboard.php");
        exit();
    } else {
        $error = "กรุณากรอกชื่อและนามสกุล";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ลงทะเบียนเพิ่มเติม</title>
</head>
<body>
    <h2>ลงทะเบียนข้อมูลเพิ่มเติม</h2>

    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST">
        <p>LINE Name: <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></p>
        <p>
            <label>ชื่อจริง: <input type="text" name="name" required></label>
        </p>
        <p>
            <label>นามสกุล: <input type="text" name="lastname" required></label>
        </p>
        <p>
            <label>Email: <input type="email" name="email"></label>
        </p>
        <button type="submit">บันทึกข้อมูล</button>
    </form>
</body>
</html>
