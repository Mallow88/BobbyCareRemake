<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

$gm_id = $_SESSION['user_id'];

// ดึงงานที่เสร็จแล้วและมีการรีวิว (ที่ GM เคยอนุมัติ)
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.revision_notes,
        ur.reviewed_at,
        gma.budget_approved,
        assignor.name as assignor_name
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    JOIN users dev ON t.developer_user_id = dev.id
    JOIN gm_approvals gma ON sr.id = gma.service_request_id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    WHERE gma.gm_user_id = ? 
    AND gma.status = 'approved'
    AND t.task_status IN ('completed', 'accepted', 'revision_requested')
    AND ur.id IS NOT NULL
    ORDER BY ur.reviewed_at DESC
");
$stmt->execute([$gm_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานที่เสร็จแล้ว - ผู้จัดการทั่วไป</title>
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

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .task-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #9f7aea;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .task-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .task-meta {
            color: #718096;
            font-size: 0.9rem;
        }

        .review-section {
            background: #f0fff4;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #48bb78;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .stars {
            color: #f6ad55;
            font-size: 1.2rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-accepted {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-revision {
            background: #fef5e7;
            color: #d69e2e;
        }

        .budget-info {
            background: #e6fffa;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
            border-left: 3px solid #38b2ac;
        }

        .revision-notes {
            background: #fef5e7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border-left: 3px solid #f6ad55;
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
    .page-title {
        font-size: 2rem;
        text-align: center;
    }
    .container {
        padding: 1rem;
    }

            .header h1 {
                font-size: 2rem;
            }

            .task-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> งานที่เสร็จแล้ว</h1>
            <p>ติดตามผลงานที่อนุมัติและรีวิวจากผู้ใช้</p>
            
            <div class="nav-buttons">
                <a href="gmindex.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                </a>
                <a href="approved_list.php" class="nav-btn secondary">
                    <i class="fas fa-list"></i> รายการที่อนุมัติ
                </a>
            </div>
        </div>

        <div class="content-card">
            <h2><i class="fas fa-star"></i> งานที่ได้รับการรีวิวแล้ว</h2>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>ยังไม่มีงานที่เสร็จแล้ว</h3>
                    <p>งานที่อนุมัติและได้รับการรีวิวจะแสดงที่นี่</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div>
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-meta">
                                    <i class="fas fa-user"></i>
                                    ผู้ขอ: <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    <span style="margin-left: 20px;">
                                        <i class="fas fa-user-cog"></i>
                                        ผู้พัฒนา: <?= htmlspecialchars($task['dev_name'] . ' ' . $task['dev_lastname']) ?>
                                    </span>
                                    <span style="margin-left: 20px;">
                                        <i class="fas fa-user-tie"></i>
                                        ผู้จัดการแผนก: <?= htmlspecialchars($task['assignor_name']) ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <?php if ($task['review_status'] === 'accepted'): ?>
                                    <span class="status-badge status-accepted">ยอมรับงาน</span>
                                <?php elseif ($task['review_status'] === 'revision_requested'): ?>
                                    <span class="status-badge status-revision">ขอแก้ไข</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="background: #f7fafc; border-radius: 8px; padding: 15px; margin: 15px 0;">
                            <strong>รายละเอียดงาน:</strong><br>
                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                        </div>

                        <?php if ($task['budget_approved']): ?>
                        <div class="budget-info">
                            <strong><i class="fas fa-money-bill-wave"></i> งบประมาณที่อนุมัติ:</strong>
                            <?= number_format($task['budget_approved'], 2) ?> บาท
                        </div>
                        <?php endif; ?>

                        <?php if ($task['developer_notes']): ?>
                        <div style="background: #e6fffa; border-radius: 8px; padding: 15px; margin: 15px 0; border-left: 3px solid #38b2ac;">
                            <strong><i class="fas fa-sticky-note"></i> หมายเหตุจากผู้พัฒนา:</strong><br>
                            <?= nl2br(htmlspecialchars($task['developer_notes'])) ?>
                        </div>
                        <?php endif; ?>

                        <div class="review-section">
                            <h4 style="margin-bottom: 15px; color: #2d3748;">
                                <i class="fas fa-star"></i> รีวิวจากผู้ใช้
                            </h4>

                            <div class="rating-display">
                                <span class="stars"><?= str_repeat('⭐', $task['rating']) ?></span>
                                <span style="font-weight: 600;"><?= $task['rating'] ?>/5 ดาว</span>
                                <span style="color: #718096; font-size: 0.9rem;">
                                    รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($task['reviewed_at'])) ?>
                                </span>
                            </div>

                            <?php if ($task['review_comment']): ?>
                            <div style="margin: 10px 0;">
                                <strong>ความเห็น:</strong><br>
                                <div style="background: white; padding: 12px; border-radius: 6px; margin-top: 5px; font-style: italic;">
                                    "<?= nl2br(htmlspecialchars($task['review_comment'])) ?>"
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($task['revision_notes']): ?>
                            <div class="revision-notes">
                                <strong><i class="fas fa-edit"></i> รายละเอียดที่ต้องแก้ไข:</strong><br>
                                <?= nl2br(htmlspecialchars($task['revision_notes'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; font-size: 0.9rem; color: #718096;">
                            <div>
                                <i class="fas fa-calendar"></i>
                                เริ่มงาน: <?= $task['started_at'] ? date('d/m/Y H:i', strtotime($task['started_at'])) : 'ไม่ระบุ' ?>
                            </div>
                            <div>
                                <i class="fas fa-check-circle"></i>
                                เสร็จงาน: <?= date('d/m/Y H:i', strtotime($task['completed_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>