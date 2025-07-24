<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

$assignor_id = $_SESSION['user_id'];

// ดึงรายการคำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่ายแล้ว
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
        dma.reviewed_at as div_mgr_reviewed_at,
        div_mgr.name as div_mgr_name,
        
        -- Service Info
        s.name as service_name,
        s.category as service_category
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    WHERE dma.status = 'approved' 
    AND (aa.id IS NULL OR aa.status = 'pending')
    ORDER BY dma.reviewed_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อ developers
$dev_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'developer' AND is_active = 1 ORDER BY name");
$dev_stmt->execute();
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการ services ประเภท development
$services_stmt = $conn->prepare("SELECT * FROM services WHERE category = 'development' AND is_active = 1 ORDER BY name");
$services_stmt->execute();
$development_services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้จัดการแผนก - BobbyCareDev</title>
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

        .request-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #10b981;
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

        .approval-info {
            background: #d1fae5;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }

        .approval-form {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
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
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .approve-option {
            background: #d1fae5;
            color: #065f46;
        }

        .approve-option:hover {
            border-color: #10b981;
        }

        .reject-option {
            background: #fee2e2;
            color: #991b1b;
        }

        .reject-option:hover {
            border-color: #ef4444;
        }

        .submit-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
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
            
            .user-info-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
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
                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-user-cog text-white fs-2"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">ผู้จัดการแผนก</h1>
                            <p class="text-muted mb-0 fs-5">พิจารณาคำขอและมอบหมายงานให้ผู้พัฒนา</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end justify-content-start flex-wrap">
                        <a href="index.php" class="btn btn-gradient">
                            <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                        </a>
                        <a href="view_completed_tasks.php" class="btn btn-gradient">
                            <i class="fas fa-star me-2"></i>งานที่เสร็จแล้ว
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="glass-card p-4">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-clipboard-check text-success me-3 fs-3"></i>
                <h2 class="mb-0 fw-bold">คำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่าย</h2>
            </div>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3 class="fw-bold mb-3">ไม่มีคำขอที่รอการพิจารณา</h3>
                    <p class="fs-5">ขณะนี้ไม่มีคำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่าย</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                <div class="d-flex gap-2 mb-2">
                                    <?php if ($req['service_name']): ?>
                                        <span class="service-badge service-<?= $req['service_category'] ?>">
                                            <i class="fas fa-code me-1"></i>
                                            <?= htmlspecialchars($req['service_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($req['work_category']): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-building me-1"></i>
                                            <?= htmlspecialchars($req['work_category']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
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
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <h6 class="fw-bold text-primary mb-2">
                                <i class="fas fa-align-left me-2"></i>รายละเอียดคำขอ
                            </h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($req['description'])) ?></p>
                        </div>

                        <!-- ประโยชน์ที่คาดว่าจะได้รับ -->
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

                        <div class="approval-info">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-check-circle me-2"></i>อนุมัติโดยผู้จัดการฝ่าย
                            </h6>
                            <p class="mb-1">
                                <strong>โดย:</strong> <?= htmlspecialchars($req['div_mgr_name']) ?> 
                                <strong class="ms-3">เมื่อ:</strong> <?= date('d/m/Y H:i', strtotime($req['div_mgr_reviewed_at'])) ?>
                            </p>
                            <?php if ($req['div_mgr_reason']): ?>
                                <p class="mb-0"><strong>หมายเหตุ:</strong> <?= htmlspecialchars($req['div_mgr_reason']) ?></p>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="assign_status.php" class="approval-form">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-tasks me-2"></i>มอบหมายงานและพิจารณา
                            </h5>
                            
                            <!-- เลือก Service ประเภท Development -->
                            <div class="form-group">
                                <label for="service_<?= $req['id'] ?>">
                                    <i class="fas fa-cogs me-2"></i>เลือกประเภทงาน Development:
                                </label>
                                <select name="development_service_id" id="service_<?= $req['id'] ?>" class="form-select" required>
                                    <option value="">-- เลือกประเภทงาน Development --</option>
                                    <?php foreach ($development_services as $service): ?>
                                        <option value="<?= $service['id'] ?>">
                                            <?= htmlspecialchars($service['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">เลือกประเภทงานพัฒนาที่เหมาะสมกับคำขอนี้</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="developer_<?= $req['id'] ?>">
                                        <i class="fas fa-user-cog me-2"></i>มอบหมายให้ผู้พัฒนา:
                                    </label>
                                    <select name="assigned_developer_id" id="developer_<?= $req['id'] ?>" class="form-select" required>
                                        <option value="">-- เลือกผู้พัฒนา --</option>
                                        <?php foreach ($developers as $dev): ?>
                                            <option value="<?= $dev['id'] ?>">
                                                <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="priority_<?= $req['id'] ?>">
                                        <i class="fas fa-exclamation-circle me-2"></i>ระดับความสำคัญ:
                                    </label>
                                    <select name="priority_level" id="priority_<?= $req['id'] ?>" class="form-select">
                                        <option value="low">ต่ำ</option>
                                        <option value="medium" selected>ปานกลาง</option>
                                        <option value="high">สูง</option>
                                        <option value="urgent">เร่งด่วน</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="estimated_days_<?= $req['id'] ?>">
                                    <i class="fas fa-calendar-alt me-2"></i>ประมาณการเวลา (วัน):
                                </label>
                                <input type="number" name="estimated_days" id="estimated_days_<?= $req['id'] ?>" class="form-control" min="1" max="365" placeholder="จำนวนวันที่คาดว่าจะใช้">
                            </div>

                            <div class="form-group">
                                <label>การพิจารณา:</label>
                                <div class="radio-group">
                                    <label class="radio-option approve-option">
                                        <input type="radio" name="status" value="approved" required>
                                        <i class="fas fa-check-circle"></i>
                                        อนุมัติและมอบหมายงาน
                                    </label>
                                    <label class="radio-option reject-option">
                                        <input type="radio" name="status" value="rejected" required>
                                        <i class="fas fa-times-circle"></i>
                                        ไม่อนุมัติ
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reason_<?= $req['id'] ?>">เหตุผล/ข้อเสนอแนะ:</label>
                                <textarea 
                                    name="reason" 
                                    id="reason_<?= $req['id'] ?>" 
                                    class="form-control"
                                    placeholder="ระบุเหตุผลหรือข้อเสนอแนะ"
                                    rows="3"
                                ></textarea>
                            </div>

                            <button type="submit" class="submit-btn">
                                <i class="fas fa-paper-plane me-2"></i>
                                ส่งผลการพิจารณา
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // จัดการฟอร์มตามการเลือก
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('form');
                const developerSelect = form.querySelector('select[name="assigned_developer_id"]');
                const prioritySelect = form.querySelector('select[name="priority_level"]');
                const daysInput = form.querySelector('input[name="estimated_days"]');
                const textarea = form.querySelector('textarea');
                
                if (this.value === 'approved') {
                    form.querySelector('select[name="development_service_id"]').required = true;
                    form.querySelector('select[name="development_service_id"]').disabled = false;
                    developerSelect.required = true;
                    developerSelect.disabled = false;
                    prioritySelect.disabled = false;
                    daysInput.disabled = false;
                    textarea.placeholder = 'ข้อเสนอแนะหรือคำแนะนำสำหรับผู้พัฒนา';
                } else {
                    form.querySelector('select[name="development_service_id"]').required = false;
                    form.querySelector('select[name="development_service_id"]').disabled = true;
                    developerSelect.required = false;
                    developerSelect.disabled = true;
                    prioritySelect.disabled = true;
                    daysInput.disabled = true;
                    textarea.required = true;
                    textarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
                }
            });
        });
    </script>
</body>
</html>