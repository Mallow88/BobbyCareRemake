<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

// ดึงคำขอที่ผ่านการอนุมัติจาก div_mgr แล้ว
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        dma.status as div_mgr_status,
        dma.reason as div_mgr_reason,
        dma.reviewed_at as div_mgr_reviewed_at,
        requester.name as requester_name,
        requester.lastname as requester_lastname,
        div_mgr.name as div_mgr_name
    FROM service_requests sr
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    WHERE dma.status = 'approved' 
    AND (aa.id IS NULL OR aa.status = 'pending')
    ORDER BY dma.reviewed_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อ developers
$dev_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'developer' AND is_active = 1");
$dev_stmt->execute();
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำขอรอดำเนินการ - ผู้จัดการแผนก</title>
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
            max-width: 1200px;
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

        .request-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #48bb78;
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

        .approval-info {
            background: #c6f6d5;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #48bb78;
        }

        .approval-form {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }

        select, textarea, input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
        }

        select:focus, textarea:focus, input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .approve-option {
            border: 2px solid #48bb78;
            color: #2f855a;
        }

        .reject-option {
            border: 2px solid #f56565;
            color: #c53030;
        }

        .submit-btn {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:hover {
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
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-cog"></i> ผู้จัดการแผนก</h1>
            <p>พิจารณาคำขอและมอบหมายงานให้ผู้พัฒนา</p>
            
            <div class="nav-buttons">
                <a href="index.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                </a>
                <a href="approval.php" class="nav-btn secondary">
                    <i class="fas fa-history"></i> ประวัติการอนุมัติ
                </a>
            </div>
        </div>

        <div class="content-card">
            <h2><i class="fas fa-clipboard-check"></i> คำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่าย</h2>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>ไม่มีคำขอที่รอการพิจารณา</h3>
                    <p>ขณะนี้ไม่มีคำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่าย</p>
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
                        </div>

                        <div style="background: #f7fafc; border-radius: 8px; padding: 15px; margin: 15px 0;">
                            <strong>รายละเอียดคำขอ:</strong><br>
                            <?= nl2br(htmlspecialchars($req['description'])) ?>
                        </div>

                        <?php
                        // แสดงไฟล์แนบ
                        require_once __DIR__ . '/../includes/attachment_display.php';
                        displayAttachments($req['id']);
                        ?>

                        <div class="approval-info">
                            <strong><i class="fas fa-check-circle"></i> อนุมัติโดยผู้จัดการฝ่าย</strong><br>
                            <small>
                                โดย: <?= htmlspecialchars($req['div_mgr_name']) ?> 
                                เมื่อ: <?= date('d/m/Y H:i', strtotime($req['div_mgr_reviewed_at'])) ?>
                            </small>
                            <?php if ($req['div_mgr_reason']): ?>
                                <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($req['div_mgr_reason']) ?>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="assign_status.php" class="approval-form">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="developer_<?= $req['id'] ?>">มอบหมายให้ผู้พัฒนา:</label>
                                    <select name="assigned_developer_id" id="developer_<?= $req['id'] ?>" required>
                                        <option value="">-- เลือกผู้พัฒนา --</option>
                                        <?php foreach ($developers as $dev): ?>
                                            <option value="<?= $dev['id'] ?>">
                                                <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="priority_<?= $req['id'] ?>">ระดับความสำคัญ:</label>
                                    <select name="priority_level" id="priority_<?= $req['id'] ?>">
                                        <option value="low">ต่ำ</option>
                                        <option value="medium" selected>ปานกลาง</option>
                                        <option value="high">สูง</option>
                                        <option value="urgent">เร่งด่วน</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="estimated_hours_<?= $req['id'] ?>">ประมาณการเวลา (ชั่วโมง):</label>
                                <input type="number" name="estimated_hours" id="estimated_hours_<?= $req['id'] ?>" min="1" max="1000">
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
                                    placeholder="ระบุเหตุผลหรือข้อเสนอแนะ"
                                    rows="3"
                                ></textarea>
                            </div>

                            <button type="submit" class="submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                ส่งผลการพิจารณา
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // จัดการฟอร์มตามการเลือก
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('form');
                const developerSelect = form.querySelector('select[name="assigned_developer_id"]');
                const prioritySelect = form.querySelector('select[name="priority_level"]');
                const hoursInput = form.querySelector('input[name="estimated_hours"]');
                const textarea = form.querySelector('textarea');
                
                if (this.value === 'approved') {
                    developerSelect.required = true;
                    developerSelect.disabled = false;
                    prioritySelect.disabled = false;
                    hoursInput.disabled = false;
                    textarea.placeholder = 'ข้อเสนอแนะหรือคำแนะนำสำหรับผู้พัฒนา';
                } else {
                    developerSelect.required = false;
                    developerSelect.disabled = true;
                    prioritySelect.disabled = true;
                    hoursInput.disabled = true;
                    textarea.required = true;
                    textarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
                }
            });
        });
    </script>
</body>
</html>