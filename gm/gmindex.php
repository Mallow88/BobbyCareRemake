<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

// ดึงคำขอที่ผ่าน assignor แล้ว
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        requester.name AS requester_name, 
        requester.lastname AS requester_lastname,
        
        -- Division Manager Info
        dma.status as div_mgr_status,
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Info  
        aa.status as assignor_status,
        aa.reason as assignor_reason,
        aa.estimated_hours,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
    WHERE dma.status = 'approved' 
    AND aa.status = 'approved'
    AND (gma.id IS NULL OR gma.status = 'pending')
    ORDER BY aa.reviewed_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้จัดการทั่วไป - BobbyCareDev</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .header h1 {
            color: #4a5568;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 153, 225, 0.4);
        }

        .nav-btn.secondary {
            background: linear-gradient(135deg, #48bb78, #38a169);
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .nav-btn.danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .request-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #9f7aea;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .request-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .request-meta {
            color: #718096;
            font-size: 0.9rem;
        }

        .priority-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low { background: #c6f6d5; color: #2f855a; }
        .priority-medium { background: #fef5e7; color: #d69e2e; }
        .priority-high { background: #fed7d7; color: #c53030; }
        .priority-urgent { background: #e53e3e; color: white; }

        .approval-timeline {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .timeline-step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            background: white;
        }

        .timeline-step.approved {
            border-left: 4px solid #48bb78;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
        }

        .step-icon.approved {
            background: #48bb78;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            color: #2d3748;
        }

        .step-details {
            font-size: 0.9rem;
            color: #718096;
            margin-top: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-approve {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-approve:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .request-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-tie"></i> ผู้จัดการทั่วไป</h1>
            <p>พิจารณาและอนุมัติคำขอที่ผ่านการพิจารณาจากผู้จัดการแผนก</p>
            
            <div class="nav-buttons">
                <a href="approved_list.php" class="nav-btn secondary">
                    <i class="fas fa-history"></i> รายการที่อนุมัติแล้ว
                </a>
                <a href="developer_dashboard.php" class="nav-btn secondary">
                    <i class="fas fa-chart-line"></i> Developer Dashboard
                </a>
             
                <a href="view_completed_tasks.php" class="nav-btn secondary">
                    <i class="fas fa-star"></i> งานที่เสร็จแล้ว
                </a>
                <a href="../logout.php" class="nav-btn danger">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
        </div>

        <div class="content-card">
            <h2><i class="fas fa-clipboard-check"></i> รายการคำขอที่รอการพิจารณา</h2>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>ไม่มีคำขอที่รอการพิจารณา</h3>
                    <p>ขณะนี้ไม่มีคำขอที่ต้องการการอนุมัติจากคุณ</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                <div class="request-meta">
                                    <i class="fas fa-user"></i>
                                    ผู้ขอ: <?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?>
                                    <span style="margin-left: 20px;">
                                        <i class="fas fa-calendar"></i>
                                        วันที่ส่ง: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="priority-badge priority-<?= $req['priority_level'] ?>">
                                    <?php
                                    $priorities = [
                                        'low' => 'ต่ำ',
                                        'medium' => 'ปานกลาง', 
                                        'high' => 'สูง',
                                        'urgent' => 'เร่งด่วน'
                                    ];
                                    echo $priorities[$req['priority_level']] ?? 'ปานกลาง';
                                    ?>
                                </span>
                                <?php if ($req['estimated_hours']): ?>
                                    <span style="margin-left: 10px; font-size: 0.9rem; color: #718096;">
                                        <i class="fas fa-clock"></i> <?= $req['estimated_hours'] ?> ชม.
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="background: #f7fafc; border-radius: 8px; padding: 15px; margin: 15px 0;">
                            <strong>รายละเอียดคำขอ:</strong><br>
                            <?= nl2br(htmlspecialchars($req['description'])) ?>
                        </div>

                        <div class="approval-timeline">
                            <h4 style="margin-bottom: 15px; color: #4a5568;">
                                <i class="fas fa-route"></i> ขั้นตอนการอนุมัติ
                            </h4>

                            <!-- ผู้จัดการฝ่าย -->
                            <div class="timeline-step approved">
                                <div class="step-icon approved">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-content">
                                    <div class="step-title">1. ผู้จัดการฝ่าย - อนุมัติแล้ว</div>
                                    <div class="step-details">
                                        โดย: <?= htmlspecialchars($req['div_mgr_name']) ?>
                                        <?php if ($req['div_mgr_reason']): ?>
                                            <br>หมายเหตุ: <?= htmlspecialchars($req['div_mgr_reason']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- ผู้จัดการแผนก -->
                            <div class="timeline-step approved">
                                <div class="step-icon approved">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-content">
                                    <div class="step-title">2. ผู้จัดการแผนก - อนุมัติแล้ว</div>
                                    <div class="step-details">
                                        โดย: <?= htmlspecialchars($req['assignor_name']) ?>
                                        <br>มอบหมายให้: <?= htmlspecialchars($req['dev_name'] . ' ' . $req['dev_lastname']) ?>
                                        <?php if ($req['assignor_reason']): ?>
                                            <br>หมายเหตุ: <?= htmlspecialchars($req['assignor_reason']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="gm_approve.php?id=<?= $req['id'] ?>" class="btn btn-approve">
                                <i class="fas fa-clipboard-check"></i>
                                พิจารณาคำขอ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>