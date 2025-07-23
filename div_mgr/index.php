<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'divmgr') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงรายการคำขอที่ยังไม่ได้รับการพิจารณาจาก div_mgr
$stmt = $conn->prepare("
    SELECT sr.*, u.name, u.lastname 
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    WHERE dma.id IS NULL OR dma.status = 'pending'
    ORDER BY sr.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ามีการ submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? '';

    if ($status === 'rejected' && trim($reason) === '') {
        $error = "กรุณาระบุเหตุผลเมื่อไม่อนุมัติ";
    } else {
        // บันทึกการอนุมัติ
        $stmt = $conn->prepare("
            INSERT INTO div_mgr_approvals (service_request_id, div_mgr_user_id, status, reason, reviewed_at) 
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            status = VALUES(status), 
            reason = VALUES(reason), 
            reviewed_at = NOW()
        ");
        $stmt->execute([$request_id, $user_id, $status, $reason]);

        // อัปเดตสถานะใน service_requests
        $new_status = $status === 'approved' ? 'assignor_review' : 'rejected';
        $stmt = $conn->prepare("UPDATE service_requests SET status = ?, current_step = ? WHERE id = ?");
        $stmt->execute([$new_status, $status === 'approved' ? 'div_mgr_approved' : 'div_mgr_rejected', $request_id]);

        // บันทึก log
        $stmt = $conn->prepare("
            INSERT INTO document_status_logs (service_request_id, step_name, status, reviewer_id, reviewer_role, notes) 
            VALUES (?, 'div_mgr_review', ?, ?, 'divmgr', ?)
        ");
        $stmt->execute([$request_id, $status, $user_id, $reason]);

        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติคำขอ - ผู้จัดการฝ่าย</title>
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

        .nav-btn.danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
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

        .request-meta {
            color: #718096;
            font-size: 0.9rem;
        }

        .request-description {
            background: #f7fafc;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            line-height: 1.6;
        }

        .approval-form {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
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

        .radio-option:hover {
            background: rgba(66, 153, 225, 0.1);
        }

        .radio-option input[type="radio"] {
            margin: 0;
        }

        .approve-option {
            border: 2px solid #48bb78;
            color: #2f855a;
        }

        .reject-option {
            border: 2px solid #f56565;
            color: #c53030;
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

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .radio-group {
                flex-direction: column;
                gap: 10px;
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
            <h1><i class="fas fa-user-tie"></i> ผู้จัดการฝ่าย</h1>
            <p>พิจารณาและอนุมัติคำขอบริการจากผู้ใช้งาน</p>
            
            <div class="nav-buttons">
                <a href="view_logs.php" class="nav-btn">
                    <i class="fas fa-history"></i> ประวัติการอนุมัติ
                </a>
                <a href="../logout.php" class="nav-btn danger">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
        </div>

        <div class="content-card">
            <h2><i class="fas fa-clipboard-list"></i> รายการคำขอที่รอการพิจารณา</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>ไม่มีคำขอที่รอการพิจารณา</h3>
                    <p>ขณะนี้ไม่มีคำขอใหม่ที่ต้องการการอนุมัติจากคุณ</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                <div class="request-meta">
                                    <i class="fas fa-user"></i>
                                    ผู้ขอ: <?= htmlspecialchars($req['name'] . ' ' . $req['lastname']) ?>
                                    <span style="margin-left: 20px;">
                                        <i class="fas fa-calendar"></i>
                                        วันที่ส่ง: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="request-description">
                            <strong>รายละเอียดคำขอ:</strong><br>
                            <?= nl2br(htmlspecialchars($req['description'])) ?>
                        </div>

                        <?php
                        // แสดงไฟล์แนบ
                        require_once __DIR__ . '/../includes/attachment_display.php';
                        displayAttachments($req['id']);
                        ?>

                        <form method="post" class="approval-form">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            
                            <div class="form-group">
                                <label>การพิจารณา:</label>
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
                                <label for="reason_<?= $req['id'] ?>">เหตุผล/ข้อเสนอแนะ:</label>
                                <textarea 
                                    name="reason" 
                                    id="reason_<?= $req['id'] ?>" 
                                    placeholder="ระบุเหตุผลหรือข้อเสนอแนะ (จำเป็นเมื่อไม่อนุมัติ)"
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
        // แสดง/ซ่อน textarea เหตุผลตามการเลือก
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('form');
                const textarea = form.querySelector('textarea');
                const label = form.querySelector('label[for^="reason"]');
                
                if (this.value === 'rejected') {
                    textarea.required = true;
                    label.innerHTML = 'เหตุผลการไม่อนุมัติ: <span style="color: red;">*</span>';
                    textarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
                } else {
                    textarea.required = false;
                    label.innerHTML = 'เหตุผล/ข้อเสนอแนะ:';
                    textarea.placeholder = 'ระบุเหตุผลหรือข้อเสนอแนะ (ไม่บังคับ)';
                }
            });
        });
    </script>
</body>
</html>