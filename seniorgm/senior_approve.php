<?php  
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seniorgm') {
    header("Location: ../index.php");
    exit();
}

$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    echo "ไม่พบคำขอที่ระบุ"; exit();
}

// ตรวจสอบว่ามีการพิจารณาแล้ว
$check = $conn->prepare("SELECT * FROM senior_gm_approvals WHERE service_request_id = ?");
$check->execute([$request_id]);
if ($check->rowCount() > 0) {
    echo "คำขอนี้ได้รับการพิจารณาโดย Senior GM แล้ว";
    exit();
}

// ดึงข้อมูลคำขอ
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        requester.name AS requester_name, 
        requester.lastname AS requester_lastname,
        requester.employee_id,
        requester.position,
        requester.department,
        requester.phone,
        requester.email,
        
        -- Division Manager Info
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Info  
        aa.reason as assignor_reason,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        
        -- GM Info
        gma.reason as gm_reason,
        gma.budget_approved,
        gma.reviewed_at as gm_reviewed_at,
        gm.name as gm_name,
        
        -- Service Info
        s.name as service_name,
        s.category as service_category
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    JOIN gm_approvals gma ON sr.id = gma.service_request_id
    JOIN users gm ON gma.gm_user_id = gm.id
    LEFT JOIN services s ON sr.service_id = s.id
    WHERE sr.id = ?
");
$stmt->execute([$request_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "ไม่พบคำขอ"; exit();
}

// เมื่อ submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $reason = trim($_POST['reason'] ?? '');
    $final_notes = trim($_POST['final_notes'] ?? '');
    $senior_gm_id = $_SESSION['user_id'];

    if ($status === 'rejected' && $reason === '') {
        $error = "กรุณาระบุเหตุผลที่ไม่อนุมัติ";
    } else {
        try {
            $conn->beginTransaction();

            // บันทึกการอนุมัติ Senior GM
            $stmt = $conn->prepare("
                INSERT INTO senior_gm_approvals (
                    service_request_id, senior_gm_user_id, status, reason, final_notes, reviewed_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$request_id, $senior_gm_id, $status, $reason, $final_notes]);

            // อัปเดตสถานะใน service_requests
            if ($status === 'approved') {
                // สร้าง task สำหรับ developer
                $dev_stmt = $conn->prepare("SELECT assigned_developer_id FROM assignor_approvals WHERE service_request_id = ?");
                $dev_stmt->execute([$request_id]);
                $dev_result = $dev_stmt->fetch(PDO::FETCH_ASSOC);
                $dev_id = $dev_result['assigned_developer_id'] ?? null;
                
                if ($dev_id) {
                    $task_stmt = $conn->prepare("
                        INSERT INTO tasks (service_request_id, developer_user_id, task_status, progress_percentage, created_at) 
                        VALUES (?, ?, 'pending', 0, NOW())
                    ");
                    $task_stmt->execute([$request_id, $dev_id]);
                    
                    // อัปเดต developer_status ใน service_requests
                    $update_dev_status = $conn->prepare("UPDATE service_requests SET developer_status = 'pending' WHERE id = ?");
                    $update_dev_status->execute([$request_id]);
                }
                
                $new_status = 'approved';
                $current_step = 'senior_gm_approved';
            } else {
                $new_status = 'rejected';
                $current_step = 'senior_gm_rejected';
            }
            
            $stmt = $conn->prepare("UPDATE service_requests SET status = ?, current_step = ? WHERE id = ?");
            $stmt->execute([$new_status, $current_step, $request_id]);

            // บันทึก log
            $stmt = $conn->prepare("
                INSERT INTO document_status_logs (
                    service_request_id, step_name, status, reviewer_id, reviewer_role, notes
                ) VALUES (?, 'senior_gm_review', ?, ?, 'seniorgm', ?)
            ");
            $stmt->execute([$request_id, $status, $senior_gm_id, $reason . ' ' . $final_notes]);

            $conn->commit();
            header("Location: seniorindex.php");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิจารณาคำขอ (Senior GM) - BobbyCareDev</title>
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

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .info-icon.employee { background: #667eea; }
        .info-icon.user { background: #10b981; }
        .info-icon.position { background: #f59e0b; }
        .info-icon.department { background: #8b5cf6; }
        .info-icon.phone { background: #ef4444; }
        .info-icon.email { background: #06b6d4; }

        .service-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .service-development {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .service-service {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            color: #1e40af;
        }

        .category-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-rdc { background: #dbeafe; color: #1e40af; }
        .category-cdc { background: #d1fae5; color: #065f46; }
        .category-bdc { background: #fef3c7; color: #92400e; }

        .priority-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low { background: #c6f6d5; color: #2f855a; }
        .priority-medium { background: #fef5e7; color: #d69e2e; }
        .priority-high { background: #fed7d7; color: #c53030; }
        .priority-urgent { background: #e53e3e; color: white; }

        .approval-summary {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .approval-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            background: white;
            border-left: 4px solid #48bb78;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #48bb78;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .step-details {
            font-size: 0.9rem;
            color: #718096;
            line-height: 1.4;
        }

        .approval-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 1.1rem;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-weight: 600;
        }

        .approve-option {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .approve-option:hover {
            border-color: #48bb78;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }

        .reject-option {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #c53030;
        }

        .reject-option:hover {
            border-color: #f56565;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 101, 101, 0.3);
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .submit-btn {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            box-shadow: 0 8px 25px rgba(128, 90, 213, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(128, 90, 213, 0.4);
        }

        .back-btn {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.4);
            color: white;
        }

        .error-message {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #c53030;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            border-left: 4px solid #ef4444;
        }

        .budget-info {
            background: linear-gradient(135deg, #e6fffa, #b2f5ea);
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 10px;
            border-left: 4px solid #38b2ac;
            display: inline-block;
        }

        .description-box {
            background: #f7fafc;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }

        .benefits-box {
            background: linear-gradient(135deg, #f0fff4, #e6fffa);
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }

        .estimate-info {
            background: #fef5e7;
            border-radius: 8px;
            padding: 8px 12px;
            margin: 8px 0;
            border-left: 3px solid #f59e0b;
            display: inline-block;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .user-info-grid {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
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
                            <i class="fas fa-crown text-white fs-2"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">พิจารณาคำขอขั้นสุดท้าย</h1>
                            <p class="text-muted mb-0 fs-5">การอนุมัติขั้นสุดท้ายโดยผู้จัดการอาวุโส</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="seniorindex.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> กลับรายการ
                    </a>
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- ข้อมูลคำขอ -->
            <div class="glass-card p-4 mb-4">
                <h3 class="fw-bold mb-3">
                    <i class="fas fa-info-circle text-primary me-2"></i>ข้อมูลคำขอ
                </h3>

                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1">
                        <div class="request-title"><?= htmlspecialchars($data['title']) ?></div>
                        <div class="d-flex gap-2 mb-2">
                            <?php if ($data['service_name']): ?>
                                <span class="service-badge service-<?= $data['service_category'] ?>">
                                    <?php if ($data['service_category'] === 'development'): ?>
                                        <i class="fas fa-code me-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-tools me-1"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($data['service_name']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($data['work_category']): ?>
                                <span class="category-badge category-<?= strtolower($data['work_category']) ?>">
                                    <i class="fas fa-building me-1"></i>
                                    <?= htmlspecialchars($data['work_category']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="priority-badge priority-<?= $data['priority_level'] ?>">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <?php
                            $priorities = [
                                'low' => 'ต่ำ',
                                'medium' => 'ปานกลาง', 
                                'high' => 'สูง',
                                'urgent' => 'เร่งด่วน'
                            ];
                            echo $priorities[$data['priority_level']] ?? 'ปานกลาง';
                            ?>
                        </span>
                        <?php if ($data['estimated_days']): ?>
                            <div class="estimate-info mt-2">
                                <i class="fas fa-clock me-1"></i>
                                ประมาณ <?= $data['estimated_days'] ?> วัน
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ข้อมูลผู้ขอ -->
                <div class="user-info-grid">
                    <div class="info-item">
                        <div class="info-icon employee">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div>
                            <small class="text-muted">รหัสพนักงาน</small>
                            <div class="fw-bold"><?= htmlspecialchars($data['employee_id'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon user">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <small class="text-muted">ชื่อ-นามสกุล</small>
                            <div class="fw-bold"><?= htmlspecialchars($data['requester_name'] . ' ' . $data['requester_lastname']) ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon position">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div>
                            <small class="text-muted">ตำแหน่ง</small>
                            <div class="fw-bold"><?= htmlspecialchars($data['position'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon department">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <small class="text-muted">หน่วยงาน</small>
                            <div class="fw-bold"><?= htmlspecialchars($data['department'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon phone">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <small class="text-muted">เบอร์โทร</small>
                            <div class="fw-bold"><?= htmlspecialchars($data['phone'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <small class="text-muted">อีเมล</small>
                            <div class="fw-bold"><?= htmlspecialchars($data['email'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                    </div>
                </div>

                <!-- รายละเอียดคำขอ -->
                <div class="description-box">
                    <h6 class="fw-bold text-primary mb-2">
                        <i class="fas fa-align-left me-2"></i>รายละเอียดคำขอ
                    </h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($data['description'])) ?></p>
                </div>

                <!-- ประโยชน์ที่คาดว่าจะได้รับ -->
                <?php if ($data['expected_benefits']): ?>
                <div class="benefits-box">
                    <h6 class="fw-bold text-success mb-2">
                        <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                    </h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($data['expected_benefits'])) ?></p>
                </div>
                <?php endif; ?>

                <?php
                // แสดงไฟล์แนบ
                require_once __DIR__ . '/../includes/attachment_display.php';
                displayAttachments($request_id);
                ?>
            </div>

            <div class="approval-summary">
                <h3 class="fw-bold mb-3">
                    <i class="fas fa-route text-primary me-2"></i>สรุปการอนุมัติที่ผ่านมา
                </h3>

                <div class="approval-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">ผู้จัดการฝ่าย - อนุมัติแล้ว</div>
                        <div class="step-details">
                            <strong>โดย:</strong> <?= htmlspecialchars($data['div_mgr_name']) ?>
                            <?php if ($data['div_mgr_reason']): ?>
                                <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($data['div_mgr_reason']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="approval-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">ผู้จัดการแผนก - อนุมัติแล้ว</div>
                        <div class="step-details">
                            <strong>โดย:</strong> <?= htmlspecialchars($data['assignor_name']) ?>
                            <br><strong>มอบหมายให้:</strong> <?= htmlspecialchars($data['dev_name'] . ' ' . $data['dev_lastname']) ?>
                            <?php if ($data['assignor_reason']): ?>
                                <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($data['assignor_reason']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="approval-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">ผู้จัดการทั่วไป - อนุมัติแล้ว</div>
                        <div class="step-details">
                            <strong>โดย:</strong> <?= htmlspecialchars($data['gm_name']) ?>
                            <br><strong>วันที่อนุมัติ:</strong> <?= date('d/m/Y H:i', strtotime($data['gm_reviewed_at'])) ?>
                            <?php if ($data['gm_reason']): ?>
                                <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($data['gm_reason']) ?>
                            <?php endif; ?>
                            <?php if ($data['budget_approved']): ?>
                                <div class="budget-info mt-2">
                                    <i class="fas fa-money-bill-wave me-1"></i>
                                    งบประมาณที่อนุมัติ: <?= number_format($data['budget_approved'], 2) ?> บาท
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" class="approval-form">
                <h3 class="fw-bold mb-3">
                    <i class="fas fa-crown text-primary me-2"></i>การพิจารณาขั้นสุดท้าย
                </h3>

                <div class="form-group">
                    <label>ผลการพิจารณา:</label>
                    <div class="radio-group">
                        <label class="radio-option approve-option">
                            <input type="radio" name="status" value="approved" required>
                            <i class="fas fa-check-circle me-2"></i>
                            อนุมัติขั้นสุดท้าย
                        </label>
                        <label class="radio-option reject-option">
                            <input type="radio" name="status" value="rejected" required>
                            <i class="fas fa-times-circle me-2"></i>
                            ไม่อนุมัติ
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">เหตุผล/ข้อเสนอแนะ:</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="ระบุเหตุผลหรือข้อเสนอแนะ"></textarea>
                </div>

                <div class="form-group">
                    <label for="final_notes">หมายเหตุสำหรับผู้พัฒนา:</label>
                    <textarea name="final_notes" id="final_notes" class="form-control" rows="3" placeholder="ข้อแนะนำหรือคำแนะนำพิเศษสำหรับผู้พัฒนา"></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-stamp"></i>
                    ส่งผลการพิจารณาขั้นสุดท้าย
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // จัดการฟอร์มตามการเลือก
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const reasonTextarea = document.getElementById('reason');
                const finalNotesTextarea = document.getElementById('final_notes');
                
                if (this.value === 'rejected') {
                    reasonTextarea.required = true;
                    reasonTextarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
                    finalNotesTextarea.disabled = true;
                    finalNotesTextarea.value = '';
                } else {
                    reasonTextarea.required = false;
                    reasonTextarea.placeholder = 'ระบุเหตุผลหรือข้อเสนอแนะ (ไม่บังคับ)';
                    finalNotesTextarea.disabled = false;
                }
            });
        });
    </script>
</body>
</html>