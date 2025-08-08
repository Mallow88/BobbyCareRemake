<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    echo "ไม่พบคำขอที่ระบุ";
    exit();
}

// ตรวจสอบว่ามีการอนุมัติจาก GM ไปแล้วหรือยัง
$check = $conn->prepare("SELECT * FROM gm_approvals WHERE service_request_id = ?");
$check->execute([$request_id]);
if ($check->rowCount() > 0) {
    echo "คำขอนี้ได้รับการพิจารณาโดย GM แล้ว";
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
    WHERE sr.id = ?
");
$stmt->execute([$request_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "ไม่พบคำขอ";
    exit();
}

// หากส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $reason = trim($_POST['reason'] ?? '');
    $budget_approved = $_POST['budget_approved'] ?? null;
    $gm_id = $_SESSION['user_id'];

    if ($status === 'rejected' && $reason === '') {
        $error = "กรุณาระบุเหตุผลที่ไม่อนุมัติ";
    } else {
        try {
            $conn->beginTransaction();

            // บันทึกการอนุมัติ GM
            $stmt = $conn->prepare("
                INSERT INTO gm_approvals (
                    service_request_id, gm_user_id, status, reason, budget_approved, reviewed_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$request_id, $gm_id, $status, $reason, $budget_approved]);

            // อัปเดตสถานะใน service_requests
            $new_status = $status === 'approved' ? 'senior_gm_review' : 'rejected';
            $current_step = $status === 'approved' ? 'gm_approved' : 'gm_rejected';

            $stmt = $conn->prepare("UPDATE service_requests SET status = ?, current_step = ? WHERE id = ?");
            $stmt->execute([$new_status, $current_step, $request_id]);

            // บันทึก log
            $stmt = $conn->prepare("
                INSERT INTO document_status_logs (
                    service_request_id, step_name, status, reviewer_id, reviewer_role, notes
                ) VALUES (?, 'gm_review', ?, ?, 'gmapprover', ?)
            ");
            $stmt->execute([$request_id, $status, $gm_id, $reason]);

            $conn->commit();
            header("Location: gmindex.php");
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
    <title>BobbyCareDev-อนุมัติคำขอ</title>
    <link rel="stylesheet" href="../css/nav.css">
    <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">
    <!-- Bootstrap CSS -->
         <link rel="stylesheet" href="css/approved-title.css">
    <link rel="stylesheet" href="css/developer_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffffffff 0%, #341355 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1000px;
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

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .request-info {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: flex-start;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 120px;
            margin-right: 15px;
        }

        .info-value {
            color: #2d3748;
            flex: 1;
        }

        .approval-form {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .approve-option {
            background: #c6f6d5;
            color: #2f855a;
        }

        .approve-option:hover {
            border-color: #48bb78;
        }

        .reject-option {
            background: #fed7d7;
            color: #c53030;
        }

        .reject-option:hover {
            border-color: #f56565;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .submit-btn {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }

        .back-btn {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3);
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .priority-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low {
            background: #c6f6d5;
            color: #2f855a;
        }

        .priority-medium {
            background: #fef5e7;
            color: #d69e2e;
        }

        .priority-high {
            background: #fed7d7;
            color: #c53030;
        }

        .priority-urgent {
            background: #e53e3e;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> อนุมัติคำขอ (GM)</h1>
            <p>พิจารณาและอนุมัติคำขอบริการ</p>
        </div>

        <div class="content-card">
            <a href="gmindex.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> กลับรายการ
            </a>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="request-info">
                <h3 style="margin-bottom: 20px; color: #2d3748;">
                    <i class="fas fa-info-circle"></i> ข้อมูลคำขอ
                </h3>

                <div class="info-row">
                    <div class="info-label">ผู้ขอ:</div>
                    <div class="info-value"><?= htmlspecialchars($data['requester_name'] . ' ' . $data['requester_lastname']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">รหัสพนักงาน:</div>
                    <div class="info-value"><?= htmlspecialchars($data['employee_id'] ?? 'ไม่ระบุ') ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">ตำแหน่ง:</div>
                    <div class="info-value"><?= htmlspecialchars($data['position'] ?? 'ไม่ระบุ') ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">หน่วยงาน:</div>
                    <div class="info-value"><?= htmlspecialchars($data['department'] ?? 'ไม่ระบุ') ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">เบอร์โทร:</div>
                    <div class="info-value"><?= htmlspecialchars($data['phone'] ?? 'ไม่ระบุ') ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">หัวข้อ:</div>
                    <div class="info-value"><?= htmlspecialchars($data['title']) ?></div>
                </div>

                <?php if ($data['service_name']): ?>
                    <div class="info-row">
                        <div class="info-label">ประเภทบริการ:</div>
                        <div class="info-value">
                            <span class="priority-badge priority-<?= $data['service_category'] === 'development' ? 'high' : 'medium' ?>">
                                <?php if ($data['service_category'] === 'development'): ?>
                                    <i class="fas fa-code me-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-tools me-1"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($data['service_name']) ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($data['work_category']): ?>
                    <div class="info-row">
                        <div class="info-label">หัวข้องานคลัง:</div>
                        <div class="info-value">
                            <span class="priority-badge priority-medium">
                                <i class="fas fa-building me-1"></i>
                                <?= htmlspecialchars($data['work_category']) ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-row">
                    <div class="info-label">รายละเอียด:</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($data['description'])) ?></div>
                </div>

                <?php if ($data['expected_benefits']): ?>
                    <div class="info-row">
                        <div class="info-label">ประโยชน์ที่คาดหวัง:</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($data['expected_benefits'])) ?></div>
                    </div>
                <?php endif; ?>

                <div class="info-row">
                    <div class="info-label">ผู้พัฒนา:</div>
                    <div class="info-value"><?= htmlspecialchars($data['dev_name'] . ' ' . $data['dev_lastname']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">ความสำคัญ:</div>
                    <div class="info-value">
                        <span class="priority-badge priority-<?= $data['priority_level'] ?>">
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
                    </div>
                </div>
                <?php if ($data['estimated_days']): ?>
                    <div class="estimate-info mt-2">
                        <i class="fas fa-clock me-1"></i>
                        ประมาณ <?= $data['estimated_days'] ?> วัน
                    </div>
                <?php endif; ?>

                <div class="info-row">
                    <div class="info-label">วันที่ส่ง:</div>
                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($data['created_at'])) ?></div>
                </div>
            </div>

            <?php
            // แสดงไฟล์แนบ
            require_once __DIR__ . '/../includes/attachment_display.php';
            displayAttachments($request_id);
            ?>

            <form method="post" class="approval-form">
                <h3 style="margin-bottom: 20px; color: #2d3748;">
                    <i class="fas fa-gavel"></i> การพิจารณา
                </h3>

                <div class="form-group">
                    <label>ผลการพิจารณา:</label>
                    <div class="radio-group">
                        <label class="radio-option approve-option">
                            <input type="radio" name="status" value="approved" required>
                            <i class="fas fa-check-circle"></i>
                            อนุมัติ
                        </label>
                        <label class="radio-option reject-option">
                            <input type="radio" name="status" value="rejected" required>
                            <i class="fas fa-times-circle"></i>
                            ไม่อนุมัติ
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="budget_approved">งบประมาณที่อนุมัติ (บาท):</label>
                    <input type="number" name="budget_approved" id="budget_approved" min="0" step="0.01" placeholder="ระบุงบประมาณ (ถ้ามี)">
                </div>

                <div class="form-group">
                    <label for="reason">เหตุผล/ข้อเสนอแนะ:</label>
                    <textarea name="reason" id="reason" rows="4" placeholder="ระบุเหตุผลหรือข้อเสนอแนะ"></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    ส่งผลการพิจารณา
                </button>
            </form>
        </div>
    </div>

    <script>
        // จัดการฟอร์มตามการเลือก
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const reasonTextarea = document.getElementById('reason');
                const budgetInput = document.getElementById('budget_approved');

                if (this.value === 'rejected') {
                    reasonTextarea.required = true;
                    reasonTextarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
                    budgetInput.disabled = true;
                    budgetInput.value = '';
                } else {
                    reasonTextarea.required = false;
                    reasonTextarea.placeholder = 'ระบุเหตุผลหรือข้อเสนอแนะ (ไม่บังคับ)';
                    budgetInput.disabled = false;
                }
            });
        });
    </script>
</body>

</html>