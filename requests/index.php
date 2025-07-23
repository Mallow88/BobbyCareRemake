<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่า login แล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงรายการ service request พร้อมไฟล์แนบ
$stmt = $conn->prepare("
    SELECT sr.*, 
           COUNT(ra.id) as attachment_count
    FROM service_requests sr
    LEFT JOIN request_attachments ra ON sr.id = ra.service_request_id
    WHERE sr.user_id = ?
    GROUP BY sr.id
    ORDER BY sr.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการคำขอบริการ - BobbyCareDev</title>
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

        .btn-outline-gradient {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
        }

        .request-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .request-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .request-description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 15px;
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

        .status-approved {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .status-rejected {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #c53030;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low { background: #c6f6d5; color: #2f855a; }
        .priority-medium { background: #fef5e7; color: #d69e2e; }
        .priority-high { background: #fed7d7; color: #c53030; }
        .priority-urgent { background: #e53e3e; color: white; }

        .attachment-info {
            background: #f7fafc;
            border-radius: 10px;
            padding: 10px 15px;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .request-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 15px;
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

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .request-meta {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Header -->
        <div class="header-card p-5 mb-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-list-alt text-white fs-2"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">รายการคำขอบริการ</h1>
                            <p class="text-muted mb-0 fs-5">จัดการและติดตามคำขอบริการของคุณ</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                        <a href="../dashboard.php" class="btn btn-outline-gradient">
                            <i class="fas fa-home me-2"></i>หน้าหลัก
                        </a>
                        <a href="create.php" class="btn btn-gradient">
                            <i class="fas fa-plus me-2"></i>สร้างคำขอใหม่
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="glass-card p-4 mb-4">
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="track_status.php" class="btn btn-gradient">
                    <i class="fas fa-chart-line me-2"></i>ติดตามสถานะ
                </a>
                <a href="create.php" class="btn btn-outline-gradient">
                    <i class="fas fa-plus-circle me-2"></i>สร้างคำขอใหม่
                </a>
            </div>
        </div>

        <!-- Requests List -->
        <div class="glass-card p-4">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3 class="fw-bold mb-3">ยังไม่มีคำขอบริการ</h3>
                    <p class="fs-5 mb-4">เริ่มต้นด้วยการสร้างคำขอบริการใหม่</p>
                    <a href="create.php" class="btn btn-gradient btn-lg">
                        <i class="fas fa-plus me-2"></i>สร้างคำขอแรก
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h3 class="fw-bold mb-0">
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>
                        คำขอทั้งหมด (<?= count($requests) ?> รายการ)
                    </h3>
                </div>

                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                <div class="request-description">
                                    <?= nl2br(htmlspecialchars(substr($req['description'], 0, 200))) ?>
                                    <?= strlen($req['description']) > 200 ? '...' : '' ?>
                                </div>
                            </div>
                            <div class="ms-3">
                                <?php
                                $status_labels = [
                                    'pending' => 'รอดำเนินการ',
                                    'div_mgr_review' => 'ผู้จัดการฝ่ายพิจารณา',
                                    'assignor_review' => 'ผู้จัดการแผนกพิจารณา',
                                    'gm_review' => 'ผู้จัดการทั่วไปพิจารณา',
                                    'senior_gm_review' => 'ผู้จัดการอาวุโสพิจารณา',
                                    'approved' => 'อนุมัติแล้ว',
                                    'rejected' => 'ไม่อนุมัติ',
                                    'in_progress' => 'กำลังดำเนินการ',
                                    'completed' => 'เสร็จสิ้น'
                                ];
                                $status_class = in_array($req['status'], ['approved', 'completed']) ? 'status-approved' : 
                                               ($req['status'] === 'rejected' ? 'status-rejected' : 'status-pending');
                                ?>
                                <span class="status-badge <?= $status_class ?>">
                                    <?= $status_labels[$req['status']] ?? $req['status'] ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($req['priority']): ?>
                        <div class="mb-3">
                            <?php
                            $priority_labels = [
                                'low' => 'ต่ำ',
                                'medium' => 'ปานกลาง',
                                'high' => 'สูง',
                                'urgent' => 'เร่งด่วน'
                            ];
                            ?>
                            <span class="priority-badge priority-<?= $req['priority'] ?>">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <?= $priority_labels[$req['priority']] ?? 'ปานกลาง' ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if ($req['attachment_count'] > 0): ?>
                        <div class="attachment-info">
                            <i class="fas fa-paperclip"></i>
                            <span><?= $req['attachment_count'] ?> ไฟล์แนบ</span>
                        </div>
                        <?php endif; ?>

                        <div class="request-meta">
                            <div class="text-muted">
                                <i class="fas fa-calendar me-2"></i>
                                สร้างเมื่อ: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                <?php if ($req['updated_at'] !== $req['created_at']): ?>
                                    <span class="ms-3">
                                        <i class="fas fa-edit me-2"></i>
                                        อัปเดต: <?= date('d/m/Y H:i', strtotime($req['updated_at'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="track_status.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>ดูสถานะ
                                </a>
                                <?php if (in_array($req['status'], ['pending', 'rejected'])): ?>
                                <a href="edit.php?id=<?= $req['id'] ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-edit me-1"></i>แก้ไข
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>