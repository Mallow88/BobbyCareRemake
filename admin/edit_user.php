<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    die("ไม่พบ ID ผู้ใช้");
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("ไม่พบผู้ใช้ในระบบ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';

    $update = $conn->prepare("UPDATE users SET name = ?, lastname = ?, email = ?, phone = ?, role = ? WHERE id = ?");
    $update->execute([$name, $lastname, $email, $phone, $role, $user_id]);

    $admin_id = $_SESSION['admin_id'];
    $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)")
         ->execute([$admin_id, "แก้ไขผู้ใช้ ID $user_id"]);

    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>🛠️ แก้ไขผู้ใช้ | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');

        body {
            background-color: #000;
            color: #e4e4e4ff;
            font-family: 'Share Tech Mono', monospace;
            padding: 50px;
        }

        .matrix-bg::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('https://media.giphy.com/media/qN9Ues0Y8F1aU/giphy.gif') repeat;
            background-size: cover;
            opacity: 0.03;
            z-index: -1;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 10px #dcddddff;
        }

        label {
            margin-top: 10px;
        }

        input, select {
            background-color: #000;
            color: #ebebebff;
            border: 1px solid #d3d6d5ff;
            border-radius: 5px;
            padding: 6px;
            width: 100%;
        }

        input:focus, select:focus {
            outline: none;
            box-shadow: 0 0 10px #ddddddff;
        }

        .btn-save {
            background-color: transparent;
            border: 1px solid #dfdfdfff;
            color: #ddddddff;
            padding: 8px 20px;
            margin-top: 20px;
            border-radius: 5px;
        }

        .btn-save:hover {
            background-color: #d6dad8ff;
            color: black;
        }

        a {
            color: #d1d1d1ff;
            margin-left: 15px;
        }

        a:hover {
            background-color: #e7e7e7ff;
            color: black;
            padding: 4px 8px;
            border-radius: 5px;
            transition: 0.2s;
            text-decoration: none;
        }

        .form-box {
            max-width: 600px;
            margin: 0 auto;
            background-color: rgba(0,0,0,0.7);
            padding: 30px;
            border: 1px solid #e2e2e2ff;
            border-radius: 10px;
            box-shadow: 0 0 15px #e0e0e0ff;
        }
    </style>
</head>
<body>
    <div class="matrix-bg"></div>

    <div class="form-box">
        <h1>🛠️ แก้ไขข้อมูลผู้ใช้: <?= htmlspecialchars($user['name']) ?></h1>
        <form method="post">
            <div class="mb-3">
                <label>ชื่อ:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label>นามสกุล:</label>
                <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required>
            </div>

            <div class="mb-3">
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
            </div>

            <div class="mb-3">
                <label>เบอร์โทร:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
            </div>

            <div class="mb-3">
                <label>สิทธิ์ (role):</label>
                <select name="role">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>user</option>
                    <option value="assignor" <?= $user['role'] === 'assignor' ? 'selected' : '' ?>>assignor</option>
                    <option value="divmgr" <?= $user['role'] === 'divmgr' ? 'selected' : '' ?>>divmgr</option>
                    <option value="gmapprover" <?= $user['role'] === 'gmapprover' ? 'selected' : '' ?>>gmapprover</option>
                    <option value="seniorgm" <?= $user['role'] === 'seniorgm' ? 'selected' : '' ?>>seniorgm</option>
                    <option value="developer" <?= $user['role'] === 'developer' ? 'selected' : '' ?>>developer</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                </select>
            </div>

            <button type="submit" class="btn-save">💾 บันทึก</button>
            <a href="manage_users.php">← ยกเลิก</a>
        </form>
    </div>
</body>
</html>
