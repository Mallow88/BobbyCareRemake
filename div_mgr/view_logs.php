<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'divmgr') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงประวัติการอนุมัติของผู้จัดการฝ่าย
$stmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.title,
        sr.description,
        sr.created_at,
        sr.status as current_status,
        u.name as requester_name,
        u.lastname as requester_lastname,
        u.employee_id,
        u.department,
        s.name as service_name,
        s.category as service_category,
        dn.document_number,
        dn.warehouse_number,
        dn.code_name,
        dn.year,
        dn.month,
        dn.running_number,
        dn.created_at as document_created_at,
        dma.status as approval_status,
        dma.reason as approval_reason,
        dma.priority,
        dma.estimated_days,
        dma.reviewed_at,
        (SELECT COUNT(*) FROM request_attachments WHERE service_request_id = sr.id) as attachment_count
    FROM div_mgr_approvals dma
    JOIN service_requests sr ON dma.service_request_id = sr.id
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN document_numbers dn ON sr.document_number = dn.document_number
    WHERE dma.div_mgr_user_id = ?
    ORDER BY dma.reviewed_at DESC
");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// สถิติการอนุมัติ
$total_reviews = count($logs);
$approved_count = 0;
$rejected_count = 0;

foreach ($logs as $log) {
    if ($log['approval_status'] === 'approved') {
        $approved_count++;
    } elseif ($log['approval_status'] === 'rejected') {
        $rejected_count++;
    }
}

$approval_rate = $total_reviews > 0 ? round(($approved_count / $total_reviews) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการอนุมัติ - ผู้จัดการฝ่าย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .stat-card.approved .stat-number { color: #10b981; }
        .stat-card.rejected .stat-number { color: #ef4444; }
        .stat-card.rate .stat-number { color: #f59e0b; }

        .log-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .log-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .log-card.approved {
            border-left-color: #10b981;
        }

        .log-card.rejected {
            border-left-color: #ef4444;
        }

        .log-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .document-number {
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            color: #2d3748;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            border: 2px solid #e2e8f0;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 8px;
        }

        .priority-low { background: #c6f6d5; color: #2f855a; }
        .priority-medium { background: #fef5e7; color: #d69e2e; }
        .priority-high { background: #fed7d7; color: #c53030; }
        .priority-urgent { background: #e53e3e; color: white; }

        .service-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-right: 8px;
        }

        .service-development {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .service-service {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            color: #1e40af;
        }

        .log-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .log-date {
            color: #718096;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requester-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin: 10px 0;
        }

        .reason-box {
            background: #f0f8ff;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .log-meta {
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
                            <i class="fas fa-history text-white fs-2"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">ประวัติการอนุมัติ</h1>
                            <p class="text-muted mb-0 fs-5">ดูประวัติการพิจารณาคำขอบริการทั้งหมด</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                        <a href="index.php" class="btn btn-gradient">
                            <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                        </a>
                        <a href="../logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?= $total_reviews ?></div>
                <div class="stat-label">ทั้งหมด</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number"><?= $approved_count ?></div>
                <div class="stat-label">อนุมัติ</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-number"><?= $rejected_count ?></div>
                <div class="stat-label">ไม่อนุมัติ</div>
            </div>
            <div class="stat-card rate">
                <div class="stat-number"><?= $approval_rate ?>%</div>
                <div class="stat-label">อัตราการอนุมัติ</div>
            </div>
        </div>

        <!-- Content -->
        <div class="glass-card p-4">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-clipboard-list text-primary me-3 fs-3"></i>
                <h2 class="mb-0 fw-bold">ประวัติการพิจารณา (<?= count($logs) ?> รายการ)</h2>
            </div>

            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3 class="fw-bold mb-3">ยังไม่มีประวัติการอนุมัติ</h3>
                    <p class="fs-5">เมื่อคุณพิจารณาคำขอ ประวัติจะแสดงที่นี่</p>
                </div>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-card <?= $log['approval_status'] ?>">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <!-- เลขที่เอกสาร -->
                                <?php if (!empty($log['document_number'])): ?>
                                    <div class="mb-2">
                                        <span class="document-number">
                                            <i class="fas fa-file-alt me-2"></i>
                                            เลขที่: <?= htmlspecialchars($log['document_number']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- หัวข้อ -->
                                <div class="log-title"><?= htmlspecialchars($log['title']) ?></div>
                                
                                <!-- ประเภทบริการ -->
                                <div class="d-flex gap-2 mb-2">
                                    <?php if ($log['service_name']): ?>
                                        <span class="service-badge service-<?= $log['service_category'] ?>">
                                            <?php if ($log['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($log['service_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <span class="status-badge status-<?= $log['approval_status'] ?>">
                                    <?php if ($log['approval_status'] === 'approved'): ?>
                                        <i class="fas fa-check me-1"></i>อนุมัติ
                                    <?php else: ?>
                                        <i class="fas fa-times me-1"></i>ไม่อนุมัติ
                                    <?php endif; ?>
                                </span>
                                <?php if ($log['priority']): ?>
                                    <span class="priority-badge priority-<?= $log['priority'] ?>">
                                        <?php
                                        $priorities = [
                                            'low' => 'ต่ำ',
                                            'medium' => 'ปานกลาง',
                                            'high' => 'สูง',
                                            'urgent' => 'เร่งด่วน'
                                        ];
                                        echo $priorities[$log['priority']] ?? 'ปานกลาง';
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ข้อมูลผู้ขอ -->
                        <div class="requester-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-user me-2"></i>ผู้ขอ:</strong>
                                    <?= htmlspecialchars($log['requester_name'] . ' ' . $log['requester_lastname']) ?>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-id-card me-2"></i>รหัสพนักงาน:</strong>
                                    <?= htmlspecialchars($log['employee_id'] ?? 'ไม่ระบุ') ?>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-building me-2"></i>หน่วยงาน:</strong>
                                    <?= htmlspecialchars($log['department'] ?? 'ไม่ระบุ') ?>
                                </div>
                                <?php if ($log['estimated_days']): ?>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-clock me-2"></i>ประมาณการ:</strong>
                                    <?= $log['estimated_days'] ?> วัน
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- รายละเอียดคำขอ -->
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <h6 class="fw-bold text-primary mb-2">
                                <i class="fas fa-align-left me-2"></i>รายละเอียดคำขอ
                            </h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($log['description'])) ?></p>
                        </div>

                        <!-- เหตุผล/ข้อเสนอแนะ -->
                        <?php if ($log['approval_reason']): ?>
                        <div class="reason-box">
                            <h6 class="fw-bold text-primary mb-2">
                                <i class="fas fa-comment me-2"></i>เหตุผล/ข้อเสนอแนะ
                            </h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($log['approval_reason'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- ไฟล์แนบ -->
                        <?php if ($log['attachment_count'] > 0): ?>
                        <div class="mt-3">
                            <span class="badge bg-info">
                                <i class="fas fa-paperclip me-1"></i>
                                <?= $log['attachment_count'] ?> ไฟล์แนบ
                            </span>
                        </div>
                        <?php endif; ?>

                        <div class="log-meta">
                            <div class="log-date">
                                <i class="fas fa-calendar"></i>
                                <span>ส่งคำขอ: <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></span>
                                <span class="ms-3">
                                    <i class="fas fa-check-circle"></i>
                                    พิจารณา: <?= date('d/m/Y H:i', strtotime($log['reviewed_at'])) ?>
                                </span>
                            </div>
                            <div>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-info-circle me-1"></i>
                                    สถานะปัจจุบัน: <?= $log['current_status'] ?>
                                </span>
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