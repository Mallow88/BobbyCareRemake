<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

$assignor_id = $_SESSION['user_id'];

// ดึงสถิติรวม
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN aa.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN aa.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
        SUM(CASE WHEN aa.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
    FROM assignor_approvals aa
    WHERE aa.assignor_user_id = ?
");
$stats_stmt->execute([$assignor_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// ดึงงานที่รอพิจารณา
$pending_stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM service_requests sr
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    WHERE dma.status = 'approved' 
    AND (aa.id IS NULL OR aa.status = 'pending')
");
$pending_stmt->execute();
$pending_count = $pending_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// ดึงงานล่าสุดที่อนุมัติ
$recent_stmt = $conn->prepare("
    SELECT 
        sr.title,
        sr.created_at,
        aa.reviewed_at,
        aa.status,
        requester.name as requester_name,
        requester.lastname as requester_lastname,
        dev.name as dev_name,
        dev.lastname as dev_lastname
    FROM assignor_approvals aa
    JOIN service_requests sr ON aa.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    WHERE aa.assignor_user_id = ?
    ORDER BY aa.reviewed_at DESC
    LIMIT 5
");
$recent_stmt->execute([$assignor_id]);
$recent_approvals = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้จัดการแผนก - BobbyCareDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            padding: 12px 24px;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
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
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.total .stat-number { color: #667eea; }
        .stat-card.pending .stat-number { color: #f59e0b; }
        .stat-card.approved .stat-number { color: #10b981; }
        .stat-card.rejected .stat-number { color: #ef4444; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .action-icon.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .action-icon.approved { background: linear-gradient(135deg, #10b981, #059669); }
        .action-icon.completed { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

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

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        .welcome-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .welcome-section {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">




      <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">

        <div class="container">
            <!-- โลโก้ + ชื่อระบบ -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <img src="../img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
                <span class="page-title"> ผู้จัดการแผนก, <?= htmlspecialchars($_SESSION['name']) ?>! </span>
            </a>

            <!-- ปุ่ม toggle สำหรับ mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- เมนู -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- ซ้าย: เมนูหลัก -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> หน้าหลัก</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="view_requests.php"><i class="fas fa-tasks me-1"></i>ตรวจสอบคำขอ
                    </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approved_list.php"><i class="fas fa-chart-bar me-1"></i> รายการที่อนุมัติ</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="view_completed_tasks.php"><i class="fas fa-chart-bar me-1"></i>UserReviews</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="assignor_dashboard.php"><i class="fas fa-chart-bar me-1"></i>Dashboard_DEV</a>
                    </li>
                </ul>
                <!-- ขวา: ผู้ใช้งาน -->
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <!-- <li class="nav-item d-flex align-items-center text-dark me-3">
                        <i class="fas fa-user-circle me-2"></i>
                      
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

        <!-- Welcome Section -->
        <div class="welcome-section">
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?= $stats['total_requests'] ?></div>
                <div class="stat-label">คำขอทั้งหมด</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?= $pending_count ?></div>
                <div class="stat-label">รอพิจารณา</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number"><?= $stats['approved_requests'] ?></div>
                <div class="stat-label">อนุมัติแล้ว</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-number"><?= $stats['rejected_requests'] ?></div>
                <div class="stat-label">ไม่อนุมัติ</div>
            </div>
        </div>


        <div class="row">
            <!-- Recent Approvals -->
            <div class="col-lg-12">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>การอนุมัติล่าสุด
                        </h4>
                        <a href="approved_list.php" class="btn btn-outline-primary btn-sm">
                            ดูทั้งหมด
                        </a>
                    </div>

                    <?php if (empty($recent_approvals)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox text-muted fs-1 mb-3"></i>
                            <p class="text-muted">ยังไม่มีการอนุมัติ</p>
                            <a href="view_requests.php" class="btn btn-gradient btn-sm">
                                ไปตรวจสอบคำขอ
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_approvals as $approval): ?>
                            <div class="recent-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars(substr($approval['title'], 0, 40)) ?><?= strlen($approval['title']) > 40 ? '...' : '' ?></h6>
                                    <?php
                                    $status_class = $approval['status'] === 'approved' ? 'status-approved' : 
                                                   ($approval['status'] === 'rejected' ? 'status-rejected' : 'status-pending');
                                    ?>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= $approval['status'] === 'approved' ? 'อนุมัติ' : 
                                           ($approval['status'] === 'rejected' ? 'ไม่อนุมัติ' : 'รอพิจารณา') ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($approval['requester_name'] . ' ' . $approval['requester_lastname']) ?>
                                        <?php if ($approval['dev_name']): ?>
                                            <span class="ms-2">
                                                <i class="fas fa-arrow-right me-1"></i>
                                                <?= htmlspecialchars($approval['dev_name'] . ' ' . $approval['dev_lastname']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($approval['reviewed_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>