<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงรายการคำขอของผู้ใช้พร้อมสถานะการอนุมัติ
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        -- Division Manager Status
        dma.status as div_mgr_status,
        dma.reason as div_mgr_reason,
        dma.reviewed_at as div_mgr_reviewed_at,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Status  
        aa.status as assignor_status,
        aa.reason as assignor_reason,
        aa.reviewed_at as assignor_reviewed_at,
        assignor.name as assignor_name,
        dev.name as assigned_dev_name,
        dev.lastname as assigned_dev_lastname,
        
        -- GM Status
        gma.status as gm_status,
        gma.reason as gm_reason,
        gma.reviewed_at as gm_reviewed_at,
        gm.name as gm_name,
        
        -- Senior GM Status
        sgma.status as senior_gm_status,
        sgma.reason as senior_gm_reason,
        sgma.reviewed_at as senior_gm_reviewed_at,
        senior_gm.name as senior_gm_name,
        
        -- Task Status
        t.task_status,
        t.progress_percentage,
        t.developer_notes,
        t.started_at as task_started_at,
        t.completed_at as task_completed_at,
        
        -- User Review Status
        ur.status as review_status,
        ur.rating,
        ur.review_comment,
        ur.reviewed_at as user_reviewed_at
        
    FROM service_requests sr
    LEFT JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    LEFT JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id  
    LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
    LEFT JOIN users gm ON gma.gm_user_id = gm.id
    LEFT JOIN senior_gm_approvals sgma ON sr.id = sgma.service_request_id
    LEFT JOIN users senior_gm ON sgma.senior_gm_user_id = senior_gm.id
    LEFT JOIN tasks t ON sr.id = t.service_request_id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge pending">รอดำเนินการ</span>',
        'div_mgr_review' => '<span class="badge in-review">ผู้จัดการฝ่ายพิจารณา</span>',
        'assignor_review' => '<span class="badge in-review">ผู้จัดการแผนกพิจารณา</span>',
        'gm_review' => '<span class="badge in-review">ผู้จัดการทั่วไปพิจารณา</span>',
        'senior_gm_review' => '<span class="badge in-review">ผู้จัดการอาวุโสพิจารณา</span>',
        'approved' => '<span class="badge approved">อนุมัติแล้ว</span>',
        'rejected' => '<span class="badge rejected">ไม่อนุมัติ</span>',
        'developer_assigned' => '<span class="badge assigned">มอบหมายงานแล้ว</span>',
        'in_progress' => '<span class="badge in-progress">กำลังดำเนินการ</span>',
        'completed' => '<span class="badge completed">เสร็จสิ้น</span>'
    ];
    return $badges[$status] ?? '<span class="badge unknown">ไม่ทราบสถานะ</span>';
}

function getApprovalStatus($status) {
    if ($status === 'approved') return '<i class="fas fa-check-circle text-success"></i> อนุมัติ';
    if ($status === 'rejected') return '<i class="fas fa-times-circle text-danger"></i> ไม่อนุมัติ';
    if ($status === 'pending') return '<i class="fas fa-clock text-warning"></i> รอพิจารณา';
    return '<i class="fas fa-minus text-muted"></i> ยังไม่ถึงขั้นตอน';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามสถานะเอกสาร - BobbyCareDev</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #a8b5ebff 0%, #ffffffff 100%);
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

        .requests-container {
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
            border-left: 5px solid #4299e1;
            
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

        .request-date {
            color: #718096;
            font-size: 0.9rem;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.pending {
            background: #fed7d7;
            color: #c53030;
        }

        .badge.in-review {
            background: #fef5e7;
            color: #d69e2e;
        }

        .badge.approved {
            background: #c6f6d5;
            color: #2f855a;
        }

        .badge.rejected {
            background: #fed7d7;
            color: #c53030;
        }

        .badge.assigned {
            background: #bee3f8;
            color: #2b6cb0;
        }

        .badge.in-progress {
            background: #d6bcfa;
            color: #6b46c1;
        }

        .badge.completed {
            background: #c6f6d5;
            color: #2f855a;
        }

        .progress-timeline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .timeline-step {
            background: #f7fafc;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            position: relative;
        }

        .timeline-step.completed {
            background: #c6f6d5;
            border: 2px solid #48bb78;
        }

        .timeline-step.current {
            background: #fef5e7;
            border: 2px solid #d69e2e;
        }

        .timeline-step.rejected {
            background: #fed7d7;
            border: 2px solid #f56565;
        }

        .step-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .step-status {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .step-date {
            font-size: 0.8rem;
            color: #718096;
        }

        .step-reviewer {
            font-size: 0.8rem;
            color: #4a5568;
            margin-top: 5px;
        }

        .task-progress {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            background: linear-gradient(90deg, #48bb78, #38a169);
            height: 100%;
            transition: width 0.3s ease;
        }

        .text-success { color: #48bb78; }
        .text-danger { color: #f56565; }
        .text-warning { color: #d69e2e; }
        .text-muted { color: #a0aec0; }

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

            .progress-timeline {
                grid-template-columns: 1fr;
            }

            .request-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-list"></i> ติดตามสถานะเอกสาร</h1>
            <p>ตรวจสอบความคืบหน้าของคำขอบริการของคุณ</p>
            
            <div class="nav-buttons">
                <a href="../dashboard.php" class="nav-btn">
                    <i class="fas fa-home"></i> หน้าหลัก
                </a>
                <a href="index.php" class="nav-btn secondary">
                    <i class="fas fa-list"></i> รายการคำขอ
                </a>
                <a href="create.php" class="nav-btn secondary">
                    <i class="fas fa-plus"></i> สร้างคำขอใหม่
                </a>
            </div>
        </div>
          <!-- ส่วนกรองข้อมูล -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> ค้นหา</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="ค้นหาชื่อหรือรายละเอียด..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group">
                    <label for="status"><i class="fas fa-filter"></i> สถานะ</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">ทุกสถานะ</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="div_mgr_review" <?= $status_filter === 'div_mgr_review' ? 'selected' : '' ?>>ผู้จัดการฝ่ายพิจารณา</option>
                        <option value="assignor_review" <?= $status_filter === 'assignor_review' ? 'selected' : '' ?>>ผู้จัดการแผนกพิจารณา</option>
                        <option value="gm_review" <?= $status_filter === 'gm_review' ? 'selected' : '' ?>>ผู้จัดการทั่วไปพิจารณา</option>
                        <option value="senior_gm_review" <?= $status_filter === 'senior_gm_review' ? 'selected' : '' ?>>ผู้จัดการอาวุโสพิจารณา</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
                        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> ช่วงเวลา</label>
                    <select id="date" name="date" class="form-control">
                        <option value="">ทุกช่วงเวลา</option>
                        <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>วันนี้</option>
                        <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>7 วันที่ผ่านมา</option>
                        <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>30 วันที่ผ่านมา</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                </div>
                
                <div class="form-group">
                    <a href="track_status.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> ล้างตัวกรอง
                    </a>
                </div>
            </form>
        </div>

        <div class="requests-container">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>ยังไม่มีคำขอบริการ</h3>
                    <p>เริ่มต้นด้วยการสร้างคำขอบริการใหม่</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                <div class="request-date">
                                    <i class="fas fa-calendar"></i>
                                    ส่งคำขอเมื่อ: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                </div>
                            </div>
                            <div>
                                <?= getStatusBadge($req['status']) ?>
                            </div>
                        </div>

                        <div class="progress-timeline">
                            <!-- ขั้นตอนที่ 1: ผู้จัดการฝ่าย -->
                            <div class="timeline-step <?= 
                                $req['div_mgr_status'] === 'approved' ? 'completed' : 
                                ($req['div_mgr_status'] === 'rejected' ? 'rejected' : 
                                ($req['div_mgr_status'] === 'pending' ? 'current' : '')) 
                            ?>">
                                <div class="step-title">1. ผู้จัดการฝ่าย</div>
                                <div class="step-status"><?= getApprovalStatus($req['div_mgr_status']) ?></div>
                                <?php if ($req['div_mgr_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['div_mgr_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['div_mgr_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['div_mgr_name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- ขั้นตอนที่ 2: ผู้จัดการแผนก -->
                            <div class="timeline-step <?= 
                                $req['assignor_status'] === 'approved' ? 'completed' : 
                                ($req['assignor_status'] === 'rejected' ? 'rejected' : 
                                ($req['assignor_status'] === 'pending' && $req['div_mgr_status'] === 'approved' ? 'current' : '')) 
                            ?>">
                                <div class="step-title">2. ผู้จัดการแผนก</div>
                                <div class="step-status"><?= getApprovalStatus($req['assignor_status']) ?></div>
                                <?php if ($req['assignor_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['assignor_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['assignor_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['assignor_name']) ?></div>
                                <?php endif; ?>
                                <?php if ($req['assigned_dev_name']): ?>
                                    <div class="step-reviewer">
                                        <i class="fas fa-user-cog"></i> 
                                        ผู้พัฒนาระบบงาน: <?= htmlspecialchars($req['assigned_dev_name'] . ' ' . $req['assigned_dev_lastname']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- ขั้นตอนที่ 3: ผู้จัดการทั่วไป -->
                            <div class="timeline-step <?= 
                                $req['gm_status'] === 'approved' ? 'completed' : 
                                ($req['gm_status'] === 'rejected' ? 'rejected' : 
                                ($req['gm_status'] === 'pending' && $req['assignor_status'] === 'approved' ? 'current' : '')) 
                            ?>">
                                <div class="step-title">3. ผู้จัดการทั่วไป</div>
                                <div class="step-status"><?= getApprovalStatus($req['gm_status']) ?></div>
                                <?php if ($req['gm_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['gm_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['gm_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['gm_name']) ?></div>
                                <?php endif; ?>
                                  <?php if ($req['assigned_dev_name']): ?>
                                    <div class="step-reviewer">
                                        <i class="fas fa-user-cog"></i> 
                                        ผู้พัฒนาระบบงาน: <?= htmlspecialchars($req['assigned_dev_name'] . ' ' . $req['assigned_dev_lastname']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- ขั้นตอนที่ 4: ผู้จัดการอาวุโส -->
                            <div class="timeline-step <?= 
                                $req['senior_gm_status'] === 'approved' ? 'completed' : 
                                ($req['senior_gm_status'] === 'rejected' ? 'rejected' : 
                                ($req['senior_gm_status'] === 'pending' && $req['gm_status'] === 'approved' ? 'current' : '')) 
                            ?>">
                                <div class="step-title">4. ผู้จัดการอาวุโส</div>
                                <div class="step-status"><?= getApprovalStatus($req['senior_gm_status']) ?></div>
                                <?php if ($req['senior_gm_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['senior_gm_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['senior_gm_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['senior_gm_name']) ?></div>
                                <?php endif; ?>
                                  <?php if ($req['assigned_dev_name']): ?>
                                    <div class="step-reviewer">
                                        <i class="fas fa-user-cog"></i> 
                                        ผู้พัฒนาระบบงาน: <?= htmlspecialchars($req['assigned_dev_name'] . ' ' . $req['assigned_dev_lastname']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- ขั้นตอนที่ 5: การพัฒนา -->
                            <?php if ($req['task_status']): ?>
                            <div class="timeline-step <?= 
                                in_array($req['task_status'], ['completed', 'accepted']) ? 'completed' : 
                                (in_array($req['task_status'], ['received', 'in_progress', 'on_hold']) ? 'current' : '') 
                            ?>">
                                <div class="step-title">5. การพัฒนา</div>
                                <div class="step-status">
                                    <?php
                                    $task_statuses = [
                                        'pending' => '<i class="fas fa-clock text-warning"></i> รอรับงาน',
                                        'received' => '<i class="fas fa-check text-success"></i> รับงานแล้ว',
                                        'in_progress' => '<i class="fas fa-cog fa-spin text-primary"></i> กำลังดำเนินการ',
                                        'on_hold' => '<i class="fas fa-pause text-warning"></i> พักงาน',
                                        'completed' => '<i class="fas fa-check-double text-success"></i> เสร็จสิ้น - รอรีวิว',
                                        'accepted' => '<i class="fas fa-star text-success"></i> ยอมรับงาน'
                                    ];
                                    echo $task_statuses[$req['task_status']] ?? '';
                                    ?>
                                </div>
                                <?php if ($req['task_started_at']): ?>
                                    <div class="step-date">เริ่ม: <?= date('d/m/Y H:i', strtotime($req['task_started_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['task_completed_at']): ?>
                                    <div class="step-date">เสร็จ: <?= date('d/m/Y H:i', strtotime($req['task_completed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['developer_notes']): ?>
                                    <div class="step-reviewer">หมายเหตุ: <?= htmlspecialchars($req['developer_notes']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- ขั้นตอนที่ 6: การรีวิวของผู้ใช้ -->
                            <?php if ($req['task_status'] === 'completed'): ?>
                            <div class="timeline-step current">
                                <div class="step-title">6. รีวิวและยอมรับงาน</div>
                                <div class="step-status">
                                    <i class="fas fa-clock text-warning"></i> รอการรีวิวจากคุณ
                                </div>
                                <div style="margin-top: 10px;">
                                    <a href="review_task.php?request_id=<?= $req['id'] ?>" 
                                       class="btn btn-primary" style="background: #4299e1; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-star"></i> รีวิวงาน
                                    </a>
                                </div>
                            </div>
                            <?php elseif ($req['review_status']): ?>
                            <div class="timeline-step completed">
                                <div class="step-title">6. รีวิวและยอมรับงาน</div>
                                <div class="step-status">
                                    <?php if ($req['review_status'] === 'accepted'): ?>
                                        <i class="fas fa-star text-success"></i> ยอมรับงานแล้ว
                                        <?php if ($req['rating']): ?>
                                            <br>คะแนน: <?= str_repeat('⭐', $req['rating']) ?>
                                        <?php endif; ?>
                                    <?php elseif ($req['review_status'] === 'revision_requested'): ?>
                                        <i class="fas fa-redo text-warning"></i> ขอแก้ไข
                                    <?php endif; ?>
                                </div>
                                <?php if ($req['user_reviewed_at']): ?>
                                    <div class="step-date">รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($req['user_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['review_comment']): ?>
                                    <div class="step-reviewer">ความเห็น: <?= htmlspecialchars($req['review_comment']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                       <div class="border rounded p-2 bg-light" style="max-width: 300px; max-height: 200px; overflow: auto;">
    <?php
    require_once __DIR__ . '/../includes/attachment_display.php';
    displayAttachments($req['id']);
    ?>
</div>


                        <!-- แสดงความคืบหน้าของงาน -->
                        <?php if ($req['task_status'] && $req['progress_percentage'] !== null): ?>
                        <div class="task-progress">
                            <h4><i class="fas fa-tasks"></i> ความคืบหน้าการพัฒนา</h4>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $req['progress_percentage'] ?>%"></div>
                            </div>
                            <div style="text-align: center; margin-top: 10px;">
                                <strong><?= $req['progress_percentage'] ?>%</strong> เสร็จสิ้น
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- แสดงเหตุผลการไม่อนุมัติ (ถ้ามี) -->
                        <?php 
                        $rejections = [];
                        if ($req['div_mgr_status'] === 'rejected' && $req['div_mgr_reason']) {
                            $rejections[] = "ผู้จัดการฝ่าย: " . $req['div_mgr_reason'];
                        }
                        if ($req['assignor_status'] === 'rejected' && $req['assignor_reason']) {
                            $rejections[] = "ผู้จัดการแผนก: " . $req['assignor_reason'];
                        }
                        if ($req['gm_status'] === 'rejected' && $req['gm_reason']) {
                            $rejections[] = "ผู้จัดการทั่วไป: " . $req['gm_reason'];
                        }
                        if ($req['senior_gm_status'] === 'rejected' && $req['senior_gm_reason']) {
                            $rejections[] = "ผู้จัดการอาวุโส: " . $req['senior_gm_reason'];
                        }
                        ?>
                        
                        <?php if (!empty($rejections)): ?>
                        <div style="background: #fed7d7; border-radius: 8px; padding: 15px; margin-top: 15px;">
                            <h4 style="color: #c53030; margin-bottom: 10px;">
                                <i class="fas fa-exclamation-triangle"></i> เหตุผลการไม่อนุมัติ
                            </h4>
                            <?php foreach ($rejections as $rejection): ?>
                                <p style="color: #c53030; margin-bottom: 5px;">• <?= htmlspecialchars($rejection) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>