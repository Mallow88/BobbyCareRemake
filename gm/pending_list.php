<?php
session_start();
require_once __DIR__ . '/../config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// ===== สร้างเงื่อนไข =====
// ไม่กรองตาม user_id แล้ว → จะเห็นทุกคำขอ
$conditions = ["s.category = 'development'"];
$params = [];


if (!empty($_GET['search'])) {
    $conditions[] = "(sr.title LIKE ? OR dn.document_number LIKE ?)";
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['status'])) {
    $conditions[] = "sr.status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['priority'])) {
    $conditions[] = "sr.priority = ?";
    $params[] = $_GET['priority'];
}
if (!empty($_GET['document_number'])) {
    $conditions[] = "dn.document_number LIKE ?";
    $params[] = '%' . $_GET['document_number'] . '%';
}


// ===== Query หลัก =====
// ===== Query หลัก =====
$sql = "
SELECT 
    sr.id,
    sr.title,
    sr.description,
    sr.priority,
    sr.status,
    sr.created_at,

    dn.document_number,
    s.name AS service_name,
    s.category AS service_category,

    -- นับไฟล์แนบ
    (SELECT COUNT(*) FROM request_attachments ra WHERE ra.service_request_id = sr.id) AS attachment_count,

    -- Division Manager
    dma.status AS div_mgr_status,
    dma.reason AS div_mgr_reason,
    dma.reviewed_at AS div_mgr_reviewed_at,
    div_mgr.name AS div_mgr_name,
    div_mgr.lastname AS div_mgr_lastname,

    -- Assignor
    aa.status AS assignor_status,
    aa.reason AS assignor_reason,
    aa.estimated_days,
    aa.priority_level,
    aa.reviewed_at AS assignor_reviewed_at,
    assignor.name AS assignor_name,
    assignor.lastname AS assignor_lastname,

    -- GM
    gma.status AS gm_status,
    gma.reason AS gm_reason,
    gma.budget_approved,
    gma.reviewed_at AS gm_reviewed_at,
    gm.name AS gm_name,
    gm.lastname AS gm_lastname,

    -- Senior GM
    sgma.status AS senior_gm_status,
    sgma.reason AS senior_gm_reason,
    sgma.final_notes AS senior_gm_final_notes,
    sgma.reviewed_at AS senior_gm_reviewed_at,
    senior_gm.name AS senior_gm_name,
    senior_gm.lastname AS senior_gm_lastname,

    -- Developer (จาก assignor)
    dev.name as dev_name,
    dev.lastname as dev_lastname,

    -- Task ล่าสุด
    t.task_id,
    t.task_status,
    t.progress_percentage,
    t.started_at AS task_started_at,
    t.completed_at AS task_completed_at,
    t.developer_notes,

    -- Review ล่าสุด
    (SELECT ur.rating 
        FROM user_reviews ur 
        WHERE ur.task_id = t.task_id
        ORDER BY ur.reviewed_at DESC LIMIT 1
    ) AS rating,
    (SELECT ur.review_comment 
        FROM user_reviews ur 
        WHERE ur.task_id = t.task_id
        ORDER BY ur.reviewed_at DESC LIMIT 1
    ) AS review_comment,
    (SELECT ur.status 
        FROM user_reviews ur 
        WHERE ur.task_id = t.task_id
        ORDER BY ur.reviewed_at DESC LIMIT 1
    ) AS review_status,
    (SELECT ur.reviewed_at 
        FROM user_reviews ur 
        WHERE ur.task_id = t.task_id
        ORDER BY ur.reviewed_at DESC LIMIT 1
    ) AS user_reviewed_at

FROM service_requests sr
LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
LEFT JOIN services s ON sr.service_id = s.id

LEFT JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
LEFT JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id

LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id

LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
LEFT JOIN users gm ON gma.gm_user_id = gm.id

LEFT JOIN senior_gm_approvals sgma ON sr.id = sgma.service_request_id
LEFT JOIN users senior_gm ON sgma.senior_gm_user_id = senior_gm.id

-- ดึง task ล่าสุด
LEFT JOIN (
    SELECT 
        t1.id AS task_id,
        t1.task_status,
        t1.progress_percentage,
        t1.started_at,
        t1.completed_at,
        t1.developer_notes,
        t1.service_request_id
    FROM tasks t1
    INNER JOIN (
        SELECT service_request_id, MAX(id) AS latest_id
        FROM tasks
        GROUP BY service_request_id
    ) t2 ON t1.id = t2.latest_id
) t ON sr.id = t.service_request_id

LEFT JOIN users dev ON aa.assigned_developer_id = dev.id

WHERE s.category = 'development'
  AND (sgma.status IS NULL OR sgma.status != 'approved')

ORDER BY sr.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กรองซ้ำ
$unique = [];
$filtered = [];
foreach ($requests as $req) {
    if (!in_array($req['id'], $unique)) {
        $unique[] = $req['id'];
        $filtered[] = $req;
    }
}
$requests = $filtered;

$status_map = [
    'approved' => 'อนุมัติ',
    'pending'  => 'รอดำเนินการ',
    'rejected' => 'ไม่อนุมัติ',
    'completed' => 'เสร็จสิ้น',
    'in_progress' => 'กำลังดำเนินการ'
];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>BobbyCare</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .request-card {
            border-radius: 16px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .badge {
            font-size: 0.75rem;
            padding: 6px 10px;
            border-radius: 12px;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            top: 0;
            left: 18px;
            width: 3px;
            height: 100%;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-icon {
            position: absolute;
            left: -3px;
            top: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #fff;
            font-weight: bold;
        }

        .timeline-content {
            background: #fff;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #eee;
        }

        .card-title {
            font-size: 1rem;
            font-weight: bold;
            color: #0d6efd;
        }

        .small-text {
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <div class="row g-4">
        <?php $i = 1;
        foreach ($requests as $req): ?>
            <?php if ($req['service_category'] !== 'development') continue; ?> <!-- ข้ามถ้าไม่ใช่ development -->

            <div class="col-md-6 col-lg-4">
                <div class="card request-card h-100">
                    <div class="card-body">

                        <!-- ลำดับ -->
                        <div class="text-muted small mb-2">
                            <i class="fas fa-hashtag"></i> เอกสารที่ <?= $i++ ?>
                        </div>


                        <!-- Document Number -->
                        <?php if (!empty($req['document_number'])): ?>
                            <h5 class="fw-bold text-primary mb-2">
                                <i class="fas fa-file-alt me-2"></i>
                                <?= htmlspecialchars($req['document_number']) ?>
                            </h5>
                        <?php endif; ?>

                        <?php if (!empty($req['dev_name'])): ?>
                            <h5 class="fw-bold text-primary mb-2">
                                <i class=""></i>
                              ผู้พัฒนา : <?= htmlspecialchars($req['dev_name'] . ' ' . $req['dev_lastname']) ?>
                                    
                            </h5>
                        <?php endif; ?>

                        <!-- Service -->
                        <div class="mb-2">
                            <span class="badge bg-info"><?= htmlspecialchars($req['service_category']) ?></span>
                            <strong class="ms-2"><?= htmlspecialchars($req['service_name']) ?></strong>
                        </div>

                        <!-- Title -->
                        <div class="mb-2"><strong>หัวข้อ:</strong> <?= htmlspecialchars($req['title']) ?></div>

                        <!-- Description -->
                        <p class="text-muted small">
                            <?= nl2br(htmlspecialchars(substr($req['description'], 0, 5050))) ?>
                            <?= strlen($req['description']) > 150 ? '...' : '' ?>
                        </p>

                        <!-- Priority + Status -->
                        <div class="d-flex justify-content-between align-items-center my-2">
                            <!-- Priority badge (ชิดซ้าย) -->
                            <div>
                                <span class="badge px-3 py-2 rounded-pill 
            bg-<?= $req['priority'] == 'urgent' ? 'danger' : ($req['priority'] == 'high' ? 'warning' : ($req['priority'] == 'medium' ? 'info' : 'secondary')) ?>">
                                    <?= ucfirst($req['priority']) ?>
                                </span>
                            </div>

                            <!-- Status badge (ชิดขวา) -->
                            <div>
                                <span class="badge px-3 py-2 rounded-pill 
            bg-<?= in_array($req['status'], ['approved', 'completed']) ? 'success' : ($req['status'] == 'rejected' ? 'danger' : 'secondary') ?>">
                                    <?= ucfirst($req['status']) ?>
                                </span>
                            </div>
                        </div>
     ส่งคำขอเมื่อ: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                        <!-- Timeline -->
                        <div class="d-grid">
                            <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="collapse"
                                data-bs-target="#status_<?= $req['id'] ?>">
                                <i class="fas fa-eye me-1"></i> ดูรายละเอียด
                            </button>
                        </div>

                        <div class="collapse mt-3" id="status_<?= $req['id'] ?>">
                            <h6 class="fw-bold mb-3"><i class="fas fa-route me-2 text-primary"></i> ขั้นตอนการอนุมัติ</h6>

                            <ul class="timeline list-unstyled">
                                <!-- ผู้จัดการฝ่าย -->
                                <li class="timeline-item">
                                    <span class="<?= $req['div_mgr_status'] == 'approved' ? 'success' : ($req['div_mgr_status'] == 'rejected' ? 'danger' : ($req['div_mgr_status'] == 'pending' ? 'warning' : 'secondary')) ?>">

                                    </span>
                                    <div class="timeline-content">
                                        <strong>ผู้จัดการฝ่าย</strong><br>
                                        <small class="text-muted">
                                            สถานะ: <?= $status_map[$req['div_mgr_status']] ?? '-' ?><br>
                                            <?php if ($req['div_mgr_reviewed_at']): ?>
                                                <?= date('d/m/Y H:i', strtotime($req['div_mgr_reviewed_at'])) ?>
                                            <?php endif; ?>
                                        </small>
                                        <?php if ($req['div_mgr_name']): ?>
                                            <div><i class="fas fa-user me-1"></i>คุณ: <?= $req['div_mgr_name'] . ' ' . $req['div_mgr_lastname'] ?></div>
                                        <?php endif; ?>
                                        <?php if ($req['div_mgr_reason']): ?>
                                            <div><i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars($req['div_mgr_reason']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </li>

                                <!-- ผู้จัดการแผนก -->
                                <li class="timeline-item">
                                    <span class="<?= $req['assignor_status'] == 'approved' ? 'success' : ($req['assignor_status'] == 'rejected' ? 'danger' : ($req['assignor_status'] == 'pending' ? 'warning' : 'secondary')) ?>">

                                    </span>
                                    <div class="timeline-content">
                                        <strong>ผู้จัดการแผนก</strong><br>
                                        <small class="text-muted">
                                            สถานะ: <?= $status_map[$req['assignor_status']] ?? '-' ?><br>
                                            <?php if ($req['assignor_reviewed_at']): ?>
                                                <?= date('d/m/Y H:i', strtotime($req['assignor_reviewed_at'])) ?>
                                            <?php endif; ?>
                                        </small>

                                        <?php if ($req['assignor_name']): ?>
                                            <div><i class="fas fa-user me-1">คุณ:</i><?= $req['assignor_name'] . ' ' . $req['assignor_lastname'] ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['dev_name'])): ?>
                                            <div>
                                                <i class="fas fa-user me-1"></i> มอบหมาย: <?= htmlspecialchars($req['dev_name'] . ' ' . $req['dev_lastname']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($req['assignor_reason']): ?>
                                            <div><i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars($req['assignor_reason']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </li>

                                <!-- ผู้จัดการทั่วไป -->
                                <li class="timeline-item">
                                    <span class="<?= $req['gm_status'] == 'approved' ? 'success' : ($req['gm_status'] == 'rejected' ? 'danger' : ($req['gm_status'] == 'pending' ? 'warning' : 'secondary')) ?>">

                                    </span>
                                    <div class="timeline-content">
                                        <strong>ผู้จัดการทั่วไป</strong><br>

                                        <small class="text-muted">
                                            สถานะ: <?= $status_map[$req['gm_status']] ?? '-' ?><br>
                                            <?php if ($req['gm_reviewed_at']): ?>
                                                <?= date('d/m/Y H:i', strtotime($req['gm_reviewed_at'])) ?>
                                            <?php endif; ?>
                                        </small>


                                        <?php if ($req['gm_name']): ?>
                                            <div><i class="fas fa-user me-1"></i><?= $req['gm_name'] . ' ' . $req['gm_lastname'] ?></div>
                                        <?php endif; ?>
                                        <?php if ($req['gm_reason']): ?>
                                            <div><i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars($req['gm_reason']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </li>

                                <!-- ผู้จัดการอาวุโส -->
                                <li class="timeline-item">
                                    <span class="<?= $req['senior_gm_status'] == 'approved' ? 'success' : ($req['senior_gm_status'] == 'rejected' ? 'danger' : ($req['senior_gm_status'] == 'pending' ? 'warning' : 'secondary')) ?>">

                                    </span>
                                    <div class="timeline-content">
                                        <strong>ผู้จัดการอาวุโส</strong><br>
                                        <small class="text-muted">สถานะ: <?= $req['senior_gm_status'] ?? '-' ?></small>

                                        <small class="text-muted">
                                            สถานะ: <?= $status_map[$req['senior_gm_status']] ?? '-' ?><br>
                                            <?php if ($req['senior_gm_reviewed_at']): ?>
                                                <?= date('d/m/Y H:i', strtotime($req['senior_gm_reviewed_at'])) ?>
                                            <?php endif; ?>
                                        </small>
                                        <?php if ($req['senior_gm_name']): ?>
                                            <div><i class="fas fa-user me-1"></i><?= $req['senior_gm_name'] . ' ' . $req['senior_gm_lastname'] ?></div>
                                        <?php endif; ?>
                                        <?php if ($req['senior_gm_reason']): ?>
                                            <div><i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars($req['senior_gm_reason']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </li>


                            </ul>
                        </div>


                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    </div>

    <script src="../assets/js/core/bootstrap.bundle.min.js"></script>
</body>

</html>