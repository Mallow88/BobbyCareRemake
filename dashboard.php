<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];

// ดึงสถิติคำขอของผู้ใช้
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status IN ('approved', 'completed') THEN 1 ELSE 0 END) as approved_requests,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
    FROM service_requests 
    WHERE user_id = ?
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// ดึงคำขอล่าสุด
$recent_stmt = $conn->prepare("
    SELECT * FROM service_requests 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_stmt->execute([$user_id]);
$recent_requests = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BobbyCareDev-Dashboard</title>
      <link rel="icon" type="image/png" href="img/logo/bobby-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/nav.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ffffff 0%, #341355 100%);
            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            box-shadow: var(--card-shadow);
        }

        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .page-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            font-size: 1.1rem;
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.total .stat-number {
            color: #667eea;
        }

        .stat-card.pending .stat-number {
            color: #f59e0b;
        }

        .stat-card.approved .stat-number {
            color: #10b981;
        }

        .stat-card.rejected .stat-number {
            color: #ef4444;
        }

        .recent-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #e2e8f0;
        }

        .recent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-left-color: #667eea;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .welcome-section {
            background: linear-gradient(135deg, #8d9de2ff, #b792dbff);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            color: inherit;
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }

        .action-icon.create {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .action-icon.track {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .action-icon.list {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
                text-align: center;
            }

            .container {
                padding: 1rem;
            }



            .stat-number {
                font-size: 2rem;
            }
        }

        .dropdown-menu {
            z-index: 1055;
            /* สูงพอจะอยู่เหนือ element ส่วนใหญ่ */
            position: absolute;
        }

        /* จัดความสูงสูงสุด + scroll ถ้าเมนูเยอะ */
        .dropdown-menu-custom {
            max-height: 300px;
            overflow-y: auto;
            z-index: 2000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            margin-top: 5px;
        }

        /* เมนูแต่ละรายการให้ดูทันสมัย */
        .dropdown-item {
            padding: 10px 20px;
            transition: background-color 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f0f0f0;
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
                <li class="nav-item">
                    <a class="nav-link" href="dashboard2.php"><i class="fas fa-user me-1"></i>ทดสอบ</a>
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

    <div class="container mt-5">
      




        <!-- Quick Actions -->
        <!-- <div class="quick-actions">
            <a href="requests/create.php" class="action-card">
                <div class="action-icon create">
                    <i class="fas fa-plus"></i>
                </div>
                <h5 class="fw-bold">สร้างคำขอใหม่</h5>
                <p class="text-muted mb-0">เริ่มต้นคำขอบริการใหม่</p>
            </a>

            <a href="requests/index.php" class="action-card">
                <div class="action-icon list">
                    <i class="fas fa-list-alt"></i>
                </div>
                <h5 class="fw-bold">รายการคำขอ</h5>
                <p class="text-muted mb-0">จัดการคำขอทั้งหมด</p>
            </a>

            <a href="requests/track_status.php" class="action-card">
                <div class="action-icon track">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h5 class="fw-bold">ติดตามสถานะ</h5>
                <p class="text-muted mb-0">ดูความคืบหน้าคำขอ</p>
            </a>
        </div> -->

        <div class="row">
            <!-- Statistics -->
            <div class="col-lg-20">
                <div class="glass-card p-4 mb-4">
                    <h3 class="fw-bold mb-4">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>สถิติคำขอของคุณ
                    </h3>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card total">
                                <div class="stat-number"><?= $stats['total_requests'] ?></div>
                                <div class="stat-label">ทั้งหมด</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card pending">
                                <div class="stat-number"><?= $stats['pending_requests'] ?></div>
                                <div class="stat-label">รอดำเนินการ</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card approved">
                                <div class="stat-number"><?= $stats['approved_requests'] ?></div>
                                <div class="stat-label">อนุมัติแล้ว</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card rejected">
                                <div class="stat-number"><?= $stats['rejected_requests'] ?></div>
                                <div class="stat-label">ไม่อนุมัติ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           
            
        </div>

         <!-- Recent Requests -->
            <div class="col-lg-20">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">
                            <i class="fas fa-clock me-2 text-primary"></i>คำขอล่าสุด
                        </h4>
                        <a href="requests/index.php" class="btn btn-outline-primary btn-sm">
                            ดูทั้งหมด
                        </a>
                    </div>

                    <?php if (empty($recent_requests)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox text-muted fs-1 mb-3"></i>
                            <p class="text-muted">ยังไม่มีคำขอ</p>
                            <a href="requests/create.php" class="btn btn-gradient btn-sm">
                                สร้างคำขอแรก
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_requests as $request): ?>
                            <div class="recent-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars(substr($request['title'], 0, 30)) ?><?= strlen($request['title']) > 30 ? '...' : '' ?></h6>
                                    <?php
                                    $status_class = in_array($request['status'], ['approved', 'completed']) ? 'status-approved' : ($request['status'] === 'rejected' ? 'status-rejected' : 'status-pending');
                                    ?>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= $request['status'] ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>