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
    die("ไม่พบข้อมูลผู้ใช้ในระบบ");
}

$line_id = $user['line_id'];
$display_name = $_SESSION['display_name'] ?? '';
$email = $user['email'] ?? '';
$name = $user['name'] ?? '';
$lastname = $user['lastname'] ?? '';
$phone = $user['phone'] ?? '';
$picture_url = $_SESSION['picture_url'] ?? null;
$employee_id = $user['employee_id'] ?? 'ไม่ระบุ';
$position = $user['position'] ?? 'ไม่ระบุ';
$department = $user['department'] ?? 'ไม่ระบุ';

$role_text = match($user['role']) {
    'admin' => '🛡️ ผู้ดูแลระบบ',
    'staff' => '👷‍♂️ เจ้าหน้าที่',
    'user' => '🛡️ ผู้จัดการเเผนก',
    'staff' => '👷‍♂️ เจ้าหน้าที่',
    default => '👤 ผู้ใช้งานทั่วไป'
};
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>BobbyCareDev-โปรไฟล์ผู้ใช้</title>
    <link rel="icon" type="image/png" href="img/logo/bobby-icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/timeline.css">
    <link rel="stylesheet" href="requests/css/index.css">
    <link rel="stylesheet" href="css/nav.css">
    <style>
        body {
            background: #fdfdfd;
            font-family: 'Prompt', sans-serif;
        }

        .profile-card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            background: #ffffff;
            padding: 2rem;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            font-weight: 500;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .btn-soft {
            border-radius: 1rem;
        }

        .role-badge {
            background: #e6f0ff;
            color: #3561c3;
            border-radius: 2rem;
            padding: 0.4rem 1rem;
            font-weight: 500;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>


         <nav class="custom-navbar navbar navbar-expand-lg shadow-sm">
    <div class="container custom-navbar-container">
        <!-- โลโก้ + ชื่อระบบ (ฝั่งซ้าย) -->
        <a class="navbar-brand d-flex align-items-center custom-navbar-brand" href="dashboard.php">
            <img src="img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
            <!-- ชื่อระบบ หรือ โลโก้อย่างเดียว ฝั่งซ้าย -->
        </a>

        <!-- ปุ่ม toggle สำหรับ mobile -->
        <button class="navbar-toggler custom-navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- เมนู -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- ซ้าย: เมนูหลัก -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 custom-navbar-menu">
                <li class="nav-item">
                    <a class="nav-link" href="requests/create.php"><i class="fas fa-tasks me-1"></i> สร้างคำขอบริการ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="requests/index.php"><i class="fas fa-chart-bar me-1"></i> รายการคำขอ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="requests/track_status.php"><i class="fas fa-chart-bar me-1"></i> ติดตามสถานะ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> โปรไฟล์</a>
                </li>
            </ul>

            <!-- ขวา: ชื่อผู้ใช้ + ออกจากระบบ -->
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-center">
                <li class="nav-item d-flex align-items-center me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <span class="custom-navbar-title">คุณ: <?= htmlspecialchars($_SESSION['name']) ?>!</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

   <div class="container py-5 mt-2 pt-5" >

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="profile-card text-center">
                    <h2 class="mb-3">👋 สวัสดี, <?= htmlspecialchars($display_name) ?></h2>
                    
                    <?php if ($picture_url): ?>
                        <img src="<?= htmlspecialchars($picture_url) ?>" alt="Profile Picture" class="profile-picture mb-3">
                    <?php endif; ?>

                    <div class="role-badge mb-4"><?= $role_text ?></div>

                    <div class="text-start px-3">
                        <p><span class="info-label"><i class="fab fa-line me-2 text-success"></i>LINE ID:</span> <span class="info-value"><?= htmlspecialchars($line_id) ?></span></p>
                        <p><span class="info-label"><i class="fa fa-user me-2"></i>ชื่อ-สกุล:</span> <span class="info-value"><?= htmlspecialchars($name) ?> <?= htmlspecialchars($lastname) ?></span></p>
                        <p><span class="info-label"><i class="fa fa-id-card me-2"></i>รหัสพนักงาน:</span> <span class="info-value"><?= htmlspecialchars($employee_id) ?></span></p>
                        <p><span class="info-label"><i class="fa fa-briefcase me-2"></i>ตำแหน่ง:</span> <span class="info-value"><?= htmlspecialchars($position) ?></span></p>
                        <p><span class="info-label"><i class="fa fa-building me-2"></i>หน่วยงาน:</span> <span class="info-value"><?= htmlspecialchars($department) ?></span></p>
                        <p><span class="info-label"><i class="fa fa-envelope me-2"></i>Email:</span> <span class="info-value"><?= htmlspecialchars($email) ?></span></p>
                        <p><span class="info-label"><i class="fa fa-phone me-2"></i>เบอร์โทร:</span> <span class="info-value"><?= htmlspecialchars($phone) ?></span></p>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <a href="edit_profile.php" class="btn btn-warning btn-soft"><i class="fas fa-user-edit me-1"></i> แก้ไขโปรไฟล์</a>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-soft"><i class="fas fa-arrow-left me-1"></i> ย้อนกลับ</a>
                        <a href="logout.php" class="btn btn-danger btn-soft"><i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
