<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// รับคำร้องที่อนุมัติครบแล้วและ assigned ถึง developer นี้
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        t.id as task_id,
        t.task_status,
        t.accepted_at,
        aa.estimated_days,
        aa.priority_level,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        assignor.name as assignor_name
    FROM service_requests sr
    JOIN tasks t ON sr.id = t.service_request_id
    JOIN users requester ON sr.user_id = requester.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    WHERE t.developer_user_id = ?
      AND sr.status = 'approved'
      AND t.task_status = 'pending'
    ORDER BY sr.created_at DESC
");
$stmt->execute([$developer_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รับงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request_id'])) {
    $task_id = $_POST['accept_request_id'];
    $service_request_id = $_POST['service_request_id'];

    try {
        $conn->beginTransaction();

        // อัปเดตสถานะใน tasks
        $update = $conn->prepare("UPDATE tasks SET task_status = 'received', progress_percentage = 10, started_at = NOW(), accepted_at = NOW() WHERE id = ? AND developer_user_id = ?");
        $update->execute([$task_id, $developer_id]);
        
        // อัปเดตสถานะใน service_requests
        $update_sr = $conn->prepare("UPDATE service_requests SET developer_status = 'received' WHERE id = ?");
        $update_sr->execute([$service_request_id]);
        
        // บันทึก log
        $log_stmt = $conn->prepare("INSERT INTO task_status_logs (task_id, old_status, new_status, changed_by, notes) VALUES (?, 'pending', 'received', ?, 'งานได้รับการยอมรับโดยผู้พัฒนา')");
        $log_stmt->execute([$task_id, $developer_id]);

        $conn->commit();
        header("Location: tasks_board.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานของผู้พัฒนา - BobbyCareDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-success-gradient {
            background: linear-gradient(135deg, #48bb78, #38a169);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .btn-success-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
            color: white;
        }

        .btn-danger-gradient {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
        }

        .btn-danger-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 101, 101, 0.4);
            color: white;
        }

        .task-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #c53030;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: #d1d5db;
            opacity: 0.7;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2.5rem;
        }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .icon-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .table-modern {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .table-modern thead {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        .table-modern th {
            border: none;
            padding: 20px 15px;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table-modern td {
            border: none;
            padding: 20px 15px;
            vertical-align: middle;
        }

        .table-modern tbody tr {
            transition: all 0.3s ease;
        }

        .table-modern tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-code text-primary me-2"></i>
                <span class="page-title">Developer Portal</span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['name']) ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <!-- Header Section -->
        <div class="header-card p-5 mb-5 animate-fade-in">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box icon-primary me-4">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">งานของผู้พัฒนา</h1>
                            <p class="text-muted mb-0 fs-5">จัดการและติดตามงานพัฒนาของคุณ</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="stats-card">
                        <div class="stats-number"><?= count($requests) ?></div>
                        <div>งานที่รอรับ</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="glass-card p-4">
                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <a href="tasks_board.php" class="btn btn-gradient">
                            <i class="fas fa-tasks me-2"></i>บอร์ดงาน
                        </a>
                        <a href="completed_reviews.php" class="btn btn-gradient">
                            <i class="fas fa-star me-2"></i>รีวิวงาน
                        </a>
                        <a href="calendar.php" class="btn btn-gradient">
                            <i class="fas fa-calendar-alt me-2"></i>ปฏิทินงาน
                        </a>
                        <a href="../logout.php" class="btn btn-danger-gradient">
                            <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="glass-card p-4 animate-fade-in">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-inbox text-primary me-3 fs-3"></i>
                <h2 class="mb-0 fw-bold">งานที่รอรับ</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3 class="fw-bold mb-3">ไม่มีงานที่รอรับ</h3>
                    <p class="fs-5">ขณะนี้ไม่มีงานใหม่ที่รอการรับ กลับมาตรวจสอบใหม่ภายหลัง</p>
                    <a href="tasks_board.php" class="btn btn-gradient mt-3">
                        <i class="fas fa-tasks me-2"></i>ไปยังบอร์ดงาน
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <div class="table-modern">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-clipboard-list me-2"></i>งาน</th>
                                    <th><i class="fas fa-user me-2"></i>ผู้ร้องขอ</th>
                                    <th><i class="fas fa-calendar me-2"></i>วันที่ร้องขอ</th>
                                    <th><i class="fas fa-info-circle me-2"></i>สถานะ</th>
                                    <th><i class="fas fa-cogs me-2"></i>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <h6 class="fw-bold mb-2 text-primary"><?= htmlspecialchars($req['title']) ?></h6>
                                                <p class="text-muted mb-0 small"><?= nl2br(htmlspecialchars(substr($req['description'], 0, 100))) ?><?= strlen($req['description']) > 100 ? '...' : '' ?></p>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted">
                                                <i class="fas fa-clock me-2"></i>
                                                <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                                <?php if ($req['assignor_name']): ?>
                                                    <br><small class="text-primary">
                                                        <i class="fas fa-user-tie me-1"></i>
                                                        มอบหมายโดย: <?= htmlspecialchars($req['assignor_name']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-hourglass-half me-1"></i>รอรับงาน
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="accept_request_id" value="<?= $req['task_id'] ?>">
                                                <input type="hidden" name="service_request_id" value="<?= $req['id'] ?>">
                                                <button type="submit" class="btn btn-success-gradient pulse-animation" 
                                                        onclick="return confirm('ยืนยันการรับงาน?')">
                                                    <i class="fas fa-check me-2"></i>รับงาน
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all cards
            document.querySelectorAll('.glass-card, .task-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });

            // Add click animation to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    let ripple = document.createElement('span');
                    ripple.classList.add('ripple');
                    this.appendChild(ripple);

                    let x = e.clientX - e.target.offsetLeft;
                    let y = e.clientY - e.target.offsetTop;

                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    </script>

    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</body>
</html>