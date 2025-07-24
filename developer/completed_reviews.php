<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// ดึงงานที่เสร็จแล้วและมีการรีวิว
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.revision_notes,
        ur.reviewed_at as user_reviewed_at
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    JOIN user_reviews ur ON t.id = ur.task_id
    WHERE t.developer_user_id = ?
    AND t.task_status IN ('completed', 'accepted', 'revision_requested')
    ORDER BY ur.reviewed_at DESC
");
$stmt->execute([$developer_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณสถิติ
$total_reviews = count($reviews);
$total_rating = 0;
$accepted_count = 0;
$revision_count = 0;

foreach ($reviews as $review) {
    $total_rating += $review['rating'];
    if ($review['review_status'] === 'accepted') {
        $accepted_count++;
    } elseif ($review['review_status'] === 'revision_requested') {
        $revision_count++;
    }
}

$average_rating = $total_reviews > 0 ? round($total_rating / $total_reviews, 1) : 0;
$acceptance_rate = $total_reviews > 0 ? round(($accepted_count / $total_reviews) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีวิวงาน - BobbyCareDev</title>
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

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .page-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2.5rem;
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

        .review-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .review-status {
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

        .revision-notes {
            background: #fef5e7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #f6ad55;
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

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-star text-primary me-2"></i>
                <span class="page-title">Reviews</span>
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
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 60px; height: 60px;">
                            <i class="fas fa-star text-white fs-3"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">รีวิวงานจากผู้ใช้</h1>
                            <p class="text-muted mb-0 fs-5">ดูความคิดเห็นและคะแนนจากผู้ใช้งาน</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                        <a href="dev_index.php" class="btn btn-gradient">
                            <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                        </a>
                        <a href="tasks_board.php" class="btn btn-gradient">
                            <i class="fas fa-tasks me-2"></i>บอร์ดงาน
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid animate-fade-in">
            <div class="stat-card">
                <div class="stat-number text-primary"><?= $total_reviews ?></div>
                <div class="stat-label">รีวิวทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?= $average_rating ?></div>
                <div class="stat-label">คะแนนเฉลี่ย</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?= $acceptance_rate ?>%</div>
                <div class="stat-label">อัตราการยอมรับ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger"><?= $revision_count ?></div>
                <div class="stat-label">ขอแก้ไข</div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="glass-card p-4 animate-fade-in">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-comments text-primary me-3 fs-3"></i>
                <h2 class="mb-0 fw-bold">รายการรีวิว</h2>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    <i class="fas fa-star"></i>
                    <h3 class="fw-bold mb-3">ยังไม่มีรีวิว</h3>
                    <p class="fs-5">เมื่อผู้ใช้รีวิวงานของคุณ จะแสดงที่นี่</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="fw-bold text-primary mb-2"><?= htmlspecialchars($review['title']) ?></h5>
                                <p class="text-muted mb-3">
                                    <i class="fas fa-user me-2"></i>
                                    <?= htmlspecialchars($review['requester_name'] . ' ' . $review['requester_lastname']) ?>
                                    <span class="ms-3">
                                        <i class="fas fa-clock me-2"></i>
                                        รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($review['user_reviewed_at'])) ?>
                                    </span>
                                    <?php if ($review['assignor_name']): ?>
                                        <span class="ms-3">
                                            <i class="fas fa-user-tie me-2"></i>
                                            มอบหมายโดย: <?= htmlspecialchars($review['assignor_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($review['estimated_days']): ?>
                                <div class="mb-3">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>
                                        ประมาณการ: <?= $review['estimated_days'] ?> วัน
                                    </span>
                                    <?php 
                                    // คำนวณวันที่ควรเสร็จและวันที่เสร็จจริง
                                    if ($review['accepted_at'] && $review['completed_at']) {
                                        $expected_completion = date('Y-m-d', strtotime($review['accepted_at'] . ' + ' . $review['estimated_days'] . ' days'));
                                        $actual_completion = date('Y-m-d', strtotime($review['completed_at']));
                                        
                                        if ($actual_completion <= $expected_completion) {
                                            echo '<span class="badge bg-success ms-2">';
                                            echo '<i class="fas fa-check me-1"></i>';
                                            echo 'เสร็จตามกำหนด';
                                        } else {
                                            $days_late = (strtotime($actual_completion) - strtotime($expected_completion)) / (60 * 60 * 24);
                                            echo '<span class="badge bg-danger ms-2">';
                                            echo '<i class="fas fa-exclamation me-1"></i>';
                                            echo 'เสร็จช้า ' . $days_late . ' วัน';
                                        }
                                        echo '</span>';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>

                                <div class="rating-stars mb-3">
                                    <?= str_repeat('⭐', $review['rating']) ?>
                                    <span class="ms-2 text-muted">(<?= $review['rating'] ?>/5)</span>
                                </div>

                                <?php if ($review['review_comment']): ?>
                                <div class="bg-light p-3 rounded mb-3">
                                    <strong>ความเห็น:</strong><br>
                                    <em>"<?= nl2br(htmlspecialchars($review['review_comment'])) ?>"</em>
                                </div>
                                <?php endif; ?>

                                <?php if ($review['revision_notes']): ?>
                                <div class="revision-notes">
                                    <strong><i class="fas fa-edit"></i> รายละเอียดที่ต้องแก้ไข:</strong><br>
                                    <?= nl2br(htmlspecialchars($review['revision_notes'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <?php if ($review['review_status'] === 'accepted'): ?>
                                    <span class="review-status status-accepted">
                                        <i class="fas fa-check me-1"></i>ยอมรับงาน
                                    </span>
                                <?php elseif ($review['review_status'] === 'revision_requested'): ?>
                                    <span class="review-status status-revision">
                                        <i class="fas fa-redo me-1"></i>ขอแก้ไข
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
            document.querySelectorAll('.review-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>