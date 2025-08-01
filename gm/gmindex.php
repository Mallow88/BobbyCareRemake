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
        requester.employee_id,
        requester.position,
        requester.department,
        requester.phone,
        requester.email,
        dn.document_number,
        
        -- Division Manager Info
        dma.status as div_mgr_status,
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Info  
        aa.status as assignor_status,
        aa.reason as assignor_reason,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        
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
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
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
            border-left: 5px solid #9f7aea;
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

        .approval-timeline {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }

        .timeline-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            background: white;
            border-left: 4px solid #48bb78;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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
            background: #48bb78;
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

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn-approve {
            background: linear-gradient(135deg, #9f7aea, #805ad5);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(159, 122, 234, 0.3);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(159, 122, 234, 0.4);
            color: white;
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

        .request-meta {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 15px;
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
            padding: 10px 15px;
            margin: 10px 0;
            border-left: 3px solid #f59e0b;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .user-info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
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
                            <i class="fas fa-user-tie text-white fs-2"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">ผู้จัดการทั่วไป</h1>
                            <p class="text-muted mb-0 fs-5">พิจารณาและอนุมัติคำขอที่ผ่านการพิจารณาจากผู้จัดการแผนก</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                        <a href="approved_list.php" class="btn btn-gradient">
                            <i class="fas fa-history me-2"></i>รายการที่อนุมัติ
                        </a>
                        <a href="developer_dashboard.php" class="btn btn-gradient">
                            <i class="fas fa-chart-line me-2"></i>Developer Dashboard
                        </a>
                        <a href="view_completed_tasks.php" class="btn btn-gradient">
                            <i class="fas fa-star me-2"></i>งานที่เสร็จแล้ว
                        </a>
                        <a href="../logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="glass-card p-4">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-clipboard-check text-primary me-3 fs-3"></i>
                <h2 class="mb-0 fw-bold">รายการคำขอที่รอการพิจารณา</h2>
            </div>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3 class="fw-bold mb-3">ไม่มีคำขอที่รอการพิจารณา</h3>
                    <p class="fs-5">ขณะนี้ไม่มีคำขอที่ต้องการการอนุมัติจากคุณ</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">

                                       <!-- ข้อมูลเลขที่เอกสาร -->
                                <?php if (!empty($req['document_number'])): ?>
    <div class="text-muted mb-2">
        <i class="fas fa-file-alt me-1"></i> เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?>
    </div>
    <!-- ส่งค่า document_number ไปใน form ด้วย -->
    <input type="hidden" name="document_number" value="<?= htmlspecialchars($req['document_number']) ?>">
<?php endif; ?>


                                 <!-- หัวข้อ -->
                                <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>

                                <div class="d-flex gap-2 mb-2">
                                    <?php if ($req['service_name']): ?>
                                        <span class="service-badge service-<?= $req['service_category'] ?>">
                                            <?php if ($req['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($req['service_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="request-meta">
                                    <i class="fas fa-calendar me-1"></i>
                                    วันที่ขอดำเนินเรื่อง: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="priority-badge priority-<?= $req['priority_level'] ?>">
                                    <i class="fas fa-exclamation-circle me-1"></i>
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
                                <?php if ($req['estimated_days']): ?>
                                    <div class="estimate-info mt-2">
                                        <i class="fas fa-clock me-1"></i>
                                        ประมาณ <?= $req['estimated_days'] ?> วัน
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
                                    <div class="fw-bold"><?= htmlspecialchars($req['employee_id'] ?? 'ไม่ระบุ') ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon user">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <small class="text-muted">ชื่อ-นามสกุล</small>
                                    <div class="fw-bold"><?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon position">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div>
                                    <small class="text-muted">ตำแหน่ง</small>
                                    <div class="fw-bold"><?= htmlspecialchars($req['position'] ?? 'ไม่ระบุ') ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon department">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div>
                                    <small class="text-muted">หน่วยงาน</small>
                                    <div class="fw-bold"><?= htmlspecialchars($req['department'] ?? 'ไม่ระบุ') ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon phone">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <small class="text-muted">เบอร์โทร</small>
                                    <div class="fw-bold"><?= htmlspecialchars($req['phone'] ?? 'ไม่ระบุ') ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon email">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <small class="text-muted">อีเมล</small>
                                    <div class="fw-bold"><?= htmlspecialchars($req['email'] ?? 'ไม่ระบุ') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- รายละเอียดคำขอ -->
                       <?php if ($req['service_category'] === 'development'): ?>
    <div class="bg-info bg-opacity-10 p-3 rounded-3 mb-3 border-start border-info border-4">
        <h6 class="fw-bold text-info mb-3">
            <i class="fas fa-code me-2"></i>ข้อมูล Development
        </h6>
        <div class="row">
            <?php
                $fields = [
                    'program_purpose' => 'วัตถุประสงค์',
                    'target_users' => 'กลุ่มผู้ใช้งาน',
                    'main_functions' => 'ฟังก์ชันหลัก',
                    'data_requirements' => 'ข้อมูลที่ต้องใช้',
                    'current_program_name' => 'โปรแกรมที่มีปัญหา',
                    'problem_description' => 'รายละเอียดปัญหา',
                    'error_frequency' => 'ความถี่ของปัญหา',
                    'steps_to_reproduce' => 'ขั้นตอนการทำให้เกิดปัญหา',
                    'program_name_change' => 'โปรแกรมที่ต้องการเปลี่ยนข้อมูล',
                    'data_to_change' => 'ข้อมูลที่ต้องการเปลี่ยน',
                    'new_data_value' => 'ข้อมูลใหม่ที่ต้องการ',
                    'change_reason' => 'เหตุผลในการเปลี่ยนแปลง',
                    'program_name_function' => 'โปรแกรมที่ต้องการเพิ่มฟังก์ชั่น',
                    'new_functions' => 'ฟังก์ชั่นใหม่ที่ต้องการ',
                    'function_benefits' => 'ประโยชน์ของฟังก์ชั่นใหม่',
                    'integration_requirements' => 'ความต้องการเชื่อมต่อ',
                    'program_name_decorate' => 'โปรแกรมที่ต้องการตกแต่ง',
                    'decoration_type' => 'ประเภทการตกแต่ง',
                    'reference_examples' => 'ตัวอย่างอ้างอิง',
                    'current_workflow' => 'ขั้นตอนการทำงานเดิม',
                    'approach_ideas' => 'แนวทาง/ไอเดีย',
                    'related_programs' => 'โปรแกรมที่คาดว่าจะเกี่ยวข้อง',
                    'current_tools' => 'ปกติใช้โปรแกรมอะไรทำงานอยู่',
                    'system_impact' => 'ผลกระทบต่อระบบ',
                    'related_documents' => 'เอกสารการทำงานที่เกี่ยวข้อง',
                ];

                foreach ($fields as $key => $label):
                    if (!empty($req[$key])):
            ?>
            <div class="col-md-6 mb-3">
                <strong><?= $label ?>:</strong><br>
                <?= nl2br(htmlspecialchars($req[$key])) ?>
            </div>
            <?php
                    endif;
                endforeach;
            ?>
        </div>
    </div>
<?php endif; ?>
<?php if ($req['expected_benefits']): ?>
    <div class="bg-success bg-opacity-10 p-3 rounded-3 mb-3 border-start border-success border-4">
        <h6 class="fw-bold text-success mb-2">
            <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
        </h6>
        <p class="mb-0"><?= nl2br(htmlspecialchars($req['expected_benefits'])) ?></p>
    </div>
<?php endif; ?>

                        <?php
                        // แสดงไฟล์แนบ
                        require_once __DIR__ . '/../includes/attachment_display.php';
                        displayAttachments($req['id']);
                        ?>

                        <div class="approval-timeline">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-route me-2"></i>ขั้นตอนการอนุมัติที่ผ่านมา
                            </h5>

                            <!-- ผู้จัดการฝ่าย -->
                            <div class="timeline-step">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-content">
                                    <div class="step-title">1. ผู้จัดการฝ่าย - อนุมัติแล้ว</div>
                                    <div class="step-details">
                                        <strong>โดย:</strong> <?= htmlspecialchars($req['div_mgr_name']) ?>
                                        <?php if ($req['div_mgr_reason']): ?>
                                            <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($req['div_mgr_reason']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- ผู้จัดการแผนก -->
                            <div class="timeline-step">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-content">
                                    <div class="step-title">2. ผู้จัดการแผนก - อนุมัติแล้ว</div>
                                    <div class="step-details">
                                        <strong>โดย:</strong> <?= htmlspecialchars($req['assignor_name']) ?>
                                        <br><strong>มอบหมายให้:</strong> <?= htmlspecialchars($req['dev_name'] . ' ' . $req['dev_lastname']) ?>
                                        <?php if ($req['assignor_reason']): ?>
                                            <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($req['assignor_reason']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="gm_approve.php?id=<?= $req['id'] ?>" class="btn-approve">
                                <i class="fas fa-clipboard-check"></i>
                                พิจารณาคำขอ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>