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
        
        -- Division Manager Info
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Info  
        aa.reason as assignor_reason,
        aa.estimated_hours,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        
        -- GM Info
        gma.reason as gm_reason,
        gma.budget_approved,
        gma.reviewed_at as gm_reviewed_at,
        gm.name as gm_name
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    JOIN gm_approvals gma ON sr.id = gma.service_request_id
    JOIN users gm ON gma.gm_user_id = gm.id
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

        .approval-summary {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .approval-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #f7fafc;
            border-left: 4px solid #48bb78;
        }

        .step-number {
            width: 30px;
            height: 30px;
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

        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 80px;
        }

        textarea:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .submit-btn {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
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
            box-shadow: 0 4px 12px rgba(128, 90, 213, 0.3);
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

        .priority-low { background: #c6f6d5; color: #2f855a; }
        .priority-medium { background: #fef5e7; color: #d69e2e; }
        .priority-high { background: #fed7d7; color: #c53030; }
        .priority-urgent { background: #e53e3e; color: white; }

        .budget-info {
            background: #e6fffa;
            border-radius: 8px;
            padding: 10px 15px;
            margin-top: 10px;
            border-left: 4px solid #38b2ac;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-gavel"></i> พิจารณาคำขอขั้นสุดท้าย</h1>
            <p>การอนุมัติขั้นสุดท้ายโดยผู้จัดการอาวุโส</p>
        </div>

        <div class="content-card">
            <a href="seniorindex.php" class="back-btn">
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
                    <div class="info-label">หัวข้อ:</div>
                    <div class="info-value"><?= htmlspecialchars($data['title']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">รายละเอียด:</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($data['description'])) ?></div>
                </div>

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

                <?php if ($data['estimated_hours']): ?>
                <div class="info-row">
                    <div class="info-label">ประมาณการ:</div>
                    <div class="info-value"><?= $data['estimated_hours'] ?> ชั่วโมง</div>
                </div>
                <?php endif; ?>

                <?php if ($data['budget_approved']): ?>
                <div class="info-row">
                    <div class="info-label">งบประมาณ:</div>
                    <div class="info-value">
                        <div class="budget-info">
                            <i class="fas fa-money-bill-wave"></i>
                            <?= number_format($data['budget_approved'], 2) ?> บาท
                        </div>
                    </div>
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

            <div class="approval-summary">
                <h3 style="margin-bottom: 20px; color: #2d3748;">
                    <i class="fas fa-route"></i> สรุปการอนุมัติที่ผ่านมา
                </h3>

                <div class="approval-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">ผู้จัดการฝ่าย - อนุมัติแล้ว</div>
                        <div class="step-details">
                            โดย: <?= htmlspecialchars($data['div_mgr_name']) ?>
                            <?php if ($data['div_mgr_reason']): ?>
                                <br>หมายเหตุ: <?= htmlspecialchars($data['div_mgr_reason']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="approval-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">ผู้จัดการแผนก - อนุมัติแล้ว</div>
                        <div class="step-details">
                            โดย: <?= htmlspecialchars($data['assignor_name']) ?>
                            <br>มอบหมายให้: <?= htmlspecialchars($data['dev_name'] . ' ' . $data['dev_lastname']) ?>
                            <?php if ($data['assignor_reason']): ?>
                                <br>หมายเหตุ: <?= htmlspecialchars($data['assignor_reason']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="approval-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">ผู้จัดการทั่วไป - อนุมัติแล้ว</div>
                        <div class="step-details">
                            โดย: <?= htmlspecialchars($data['gm_name']) ?>
                            <br>วันที่อนุมัติ: <?= date('d/m/Y H:i', strtotime($data['gm_reviewed_at'])) ?>
                            <?php if ($data['gm_reason']): ?>
                                <br>หมายเหตุ: <?= htmlspecialchars($data['gm_reason']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" class="approval-form">
                <h3 style="margin-bottom: 20px; color: #2d3748;">
                    <i class="fas fa-crown"></i> การพิจารณาขั้นสุดท้าย
                </h3>

                <div class="form-group">
                    <label>ผลการพิจารณา:</label>
                    <div class="radio-group">
                        <label class="radio-option approve-option">
                            <input type="radio" name="status" value="approved" required>
                            <i class="fas fa-check-circle"></i>
                            อนุมัติขั้นสุดท้าย
                        </label>
                        <label class="radio-option reject-option">
                            <input type="radio" name="status" value="rejected" required>
                            <i class="fas fa-times-circle"></i>
                            ไม่อนุมัติ
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">เหตุผล/ข้อเสนอแนะ:</label>
                    <textarea name="reason" id="reason" rows="3" placeholder="ระบุเหตุผลหรือข้อเสนอแนะ"></textarea>
                </div>

                <div class="form-group">
                    <label for="final_notes">หมายเหตุสำหรับผู้พัฒนา:</label>
                    <textarea name="final_notes" id="final_notes" rows="3" placeholder="ข้อแนะนำหรือคำแนะนำพิเศษสำหรับผู้พัฒนา"></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-stamp"></i>
                    ส่งผลการพิจารณาขั้นสุดท้าย
                </button>
            </form>
        </div>
    </div>

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