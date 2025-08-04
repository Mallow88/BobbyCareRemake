<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขโปรไฟล์</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f7f9fc;
            font-family: 'Prompt', sans-serif;
            padding-top: 80px;
        }

        .card {
            border-radius: 1.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }

        label {
            font-weight: 500;
            margin-top: 1rem;
        }

        input.form-control {
            border-radius: 0.75rem;
        }

        button.btn-save {
            border-radius: 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-white">
                <h3 class="text-center mb-4"><i class="fa-solid fa-user-pen me-2"></i>แก้ไขโปรไฟล์</h3>
                <form action="update_profile.php" method="POST">
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">

                    <label>ชื่อ:</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>

                    <label>นามสกุล:</label>
                    <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($user['lastname']) ?>">

                    <label>รหัสพนักงาน:</label>
                    <input type="text" name="employee_id" class="form-control" value="<?= htmlspecialchars($user['employee_id']) ?>">

                    <label>ตำแหน่ง:</label>
                    <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($user['position']) ?>">

                    <label>หน่วยงาน:</label>
                    <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($user['department']) ?>">

                    <label>อีเมล:</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">

                    <label>เบอร์โทรศัพท์:</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-save">
                            <i class="fa-solid fa-floppy-disk me-1"></i> บันทึก
                        </button>
                        <a href="profile.php" class="btn btn-outline-secondary mt-2">
                            <i class="fa-solid fa-arrow-left me-1"></i> กลับไปโปรไฟล์
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
