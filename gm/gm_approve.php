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
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        aa.reason as assignor_reason,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
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

            $stmt = $conn->prepare("
                INSERT INTO gm_approvals (
                    service_request_id, gm_user_id, status, reason, budget_approved, reviewed_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$request_id, $gm_id, $status, $reason, $budget_approved]);

            $new_status = $status === 'approved' ? 'senior_gm_review' : 'rejected';
            $current_step = $status === 'approved' ? 'gm_approved' : 'gm_rejected';

            $stmt = $conn->prepare("UPDATE service_requests SET status = ?, current_step = ? WHERE id = ?");
            $stmt->execute([$new_status, $current_step, $request_id]);

            $stmt = $conn->prepare("
                INSERT INTO document_status_logs (
                    service_request_id, step_name, status, reviewer_id, reviewer_role, notes
                ) VALUES (?, 'gm_review', ?, ?, 'gmapprover', ?)
            ");
            $stmt->execute([$request_id, $status, $gm_id, $reason]);

            $conn->commit();

            
            header("Location: gmindex2.php");
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
    <title>GM อนุมัติคำขอ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/gm_approval.css">
</head>

<body>

    <div class="container py-4">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-clipboard-check me-2"></i> อนุมัติคำขอ</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <div class="row g-3">
                    <input type="hidden" name="requester_name" value="<?= htmlspecialchars($data['requester_name']) ?>">
                    <input type="hidden" name="requester_lastname" value="<?= htmlspecialchars($data['requester_lastname']) ?>">
                    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id'] ?? '-') ?>">
                    <input type="hidden" name="position" value="<?= htmlspecialchars($data['position'] ?? '-') ?>">
                    <input type="hidden" name="department" value="<?= htmlspecialchars($data['department'] ?? '-') ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($data['phone'] ?? '-') ?>">
                    <input type="hidden" name="title" value="<?= htmlspecialchars($data['title']) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($data['description']) ?>">
                    <?php if ($data['expected_benefits']): ?>
                        <input type="hidden" name="expected_benefits" value="<?= htmlspecialchars($data['expected_benefits']) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="dev_name" value="<?= htmlspecialchars($data['dev_name']) ?>">
                    <input type="hidden" name="dev_lastname" value="<?= htmlspecialchars($data['dev_lastname']) ?>">
                    <input type="hidden" name="priority_level" value="<?= htmlspecialchars($data['priority_level']) ?>">
                    <?php if ($data['estimated_days']): ?>
                        <input type="hidden" name="estimated_days" value="<?= $data['estimated_days'] ?>">
                    <?php endif; ?>
                    <input type="hidden" name="created_at" value="<?= htmlspecialchars($data['created_at']) ?>">

                </div>

                <hr>
              
                <form method="post" action="gm_approve.php?id=<?= $request_id ?>" class="approval-form">

                    <div class="col-12">
                        <label class="form-label">ผลการพิจารณา</label>
                        <div class="d-flex gap-3">
                            <div>
                                <input type="radio" id="approve" name="status" value="approved" required>
                                <label for="approve" class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> อนุมัติ</label>
                            </div>
                            <div>
                                <input type="radio" id="reject" name="status" value="rejected" required>
                                <label for="reject" class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i> ไม่อนุมัติ</label>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-md-6">
                        <label class="form-label">งบประมาณที่อนุมัติ (บาท)</label>
                        <input type="number" name="budget_approved" id="budget_approved" class="form-control" min="0" step="0.01" placeholder="ระบุงบประมาณ (ถ้ามี)">
                    </div> -->
                    <div class="col-12">
                        <label class="form-label">เหตุผล/ข้อเสนอแนะ</label>
                        <textarea name="reason" id="reason" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> ส่งผลการพิจารณา</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const reason = document.getElementById('reason');
                const budget = document.getElementById('budget_approved');
                if (this.value === 'rejected') {
                    reason.required = true;
                    budget.disabled = true;
                    budget.value = '';
                } else {
                    reason.required = false;
                    budget.disabled = false;
                }
            });
        });
    </script>
</body>

</html>