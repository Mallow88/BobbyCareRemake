<?php
session_start();
require_once __DIR__ . '/../config/database.php'; // ให้แน่ใจว่าไฟล์นี้สร้าง $conn (หรือปรับชื่อให้ตรง)

// --- ปรับการเช็ค DB connection ชัดเจน ---
if (!isset($conn) && isset($pdo)) {
    // ถ้าไฟล์คืน $pdo แต่โค้ดใช้ $conn ให้ตั้งตัวแปรเทียบเท่า
    $conn = $pdo;
}

// ตรวจสอบ session และ role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'userservice') {
    header("Location: ../index.php");
    exit();
}

$userservice_id = $_SESSION['user_id'];

// เริ่มสร้าง query และ parameter
$conditions = ["sr.user_id = ?"];
$params = [$userservice_id]; // <-- แก้ตรงนี้เป็น $userservice_id

// ถ้ามีการกรอกคำค้นหา (search by title)
if (!empty($_GET['search'])) {
    $conditions[] = "(sr.title LIKE ? OR dn.document_number LIKE ?)";
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}

// ถ้ามีการกรองสถานะ
if (!empty($_GET['status'])) {
    $conditions[] = "sr.status = ?";
    $params[] = $_GET['status'];
}

// ถ้ามีการกรองความเร่งด่วน
if (!empty($_GET['priority'])) {
    $conditions[] = "sr.priority = ?";
    $params[] = $_GET['priority'];
}

// ถ้ามีการค้นหาเลขเอกสาร
if (!empty($_GET['document_number'])) {
    $conditions[] = "dn.document_number LIKE ?";
    $params[] = '%' . $_GET['document_number'] . '%';
}

// SQL ดึงข้อมูลคำขอทั้งหมด
$sql = "
SELECT 
    sr.*, 
    COUNT(DISTINCT ra.id) AS attachment_count,
    dn.document_number,
    dn.warehouse_number,
    dn.code_name,
    dn.year,
    dn.month,
    dn.running_number,
    dn.created_at AS document_created_at,
    s.name AS service_name,
    s.category AS service_category,
    t.id AS task_id,
    t.task_status,
    t.progress_percentage,
    t.started_at AS task_started_at,
    t.completed_at AS task_completed_at,
    t.developer_notes,
    ur.rating,
    ur.review_comment,
    ur.status AS review_status,
    ur.reviewed_at AS user_reviewed_at
FROM service_requests sr
LEFT JOIN request_attachments ra ON sr.id = ra.service_request_id
LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
LEFT JOIN services s ON sr.service_id = s.id
LEFT JOIN tasks t ON sr.id = t.service_request_id
LEFT JOIN user_reviews ur ON t.id = ur.task_id
WHERE " . implode(' AND ', $conditions) . "
GROUP BY sr.id
ORDER BY sr.created_at DESC
";

// Debugging: คุณสามารถ uncomment บรรทัดต่อไปนี้ ในช่วงทดสอบเพื่อดู SQL และ params
// echo "<pre>"; var_dump($sql, $params); echo "</pre>"; exit;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// เตรียม statement สำหรับ subtasks ล่วงหน้า
$sub_stmt = $conn->prepare("SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY step_order ASC");

// ดึง subtasks เพิ่มเติมถ้ามี task_id
foreach ($requests as &$request) {
    $task_id = $request['task_id'] ?? null;

    if ($task_id) {
        $sub_stmt->execute([$task_id]);
        $request['subtasks'] = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $request['subtasks'] = [];
    }
}
unset($request);
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BobbyCareDev-รายการคำขอบริการ</title>
    <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/timeline.css">
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <div class="container-lg my-5">
        <!-- Header -->
        <div class="header-card p-4 mb-4">
        </div>

        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">

            <div class="container">
                <!-- โลโก้ + ชื่อระบบ -->
                <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                    <img src="../img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
                    <span class="page-title"> สวัสดี, <?= htmlspecialchars($_SESSION['name']) ?>! </span>
                </a>

                <!-- ปุ่ม toggle สำหรับ mobile -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- เมนู -->
                <div class="collapse navbar-collapse" id="navbarContent">
                    <!-- ซ้าย: เมนูหลัก -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <!-- <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> หน้าหลัก</a>
                    </li> -->
                        <!-- <li class="nav-item">
                        <a class="nav-link" href="create.php"><i class="fas fa-tasks me-1"></i>สร้างคำขอบริการ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-chart-bar me-1"></i> รายการคำขอ</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="track_status.php"><i class="fas fa-chart-bar me-1"></i>ติดตามสถานะ</a>
                    </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="../profile.php"><i class="fas fa-chart-bar me-1"></i>โปรไฟล์</a>
                        </li>
                    </ul>
                    <!-- ขวา: ผู้ใช้งาน -->
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <!-- <li class="nav-item d-flex align-items-center text-dark me-3">
                        <i class="fas fa-user-circle me-2"></i>
                      
                    </li> -->
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>



        <!-- Filter Form -->
        <div class="glass-card p-4 mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาคำขอ..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- สถานะทั้งหมด --</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="div_mgr_review" <?= ($_GET['status'] ?? '') === 'div_mgr_review' ? 'selected' : '' ?>>รอผู้จัดการฝ่าย</option>
                        <option value="assignor_review" <?= ($_GET['status'] ?? '') === 'assignor_review' ? 'selected' : '' ?>>รอผู้จัดการแผนก</option>
                        <option value="gm_review" <?= ($_GET['status'] ?? '') === 'gm_review' ? 'selected' : '' ?>>รอผู้จัดการทั่วไป</option>
                        <option value="senior_gm_review" <?= ($_GET['status'] ?? '') === 'senior_gm_review' ? 'selected' : '' ?>>รอผู้จัดการอาวุโส</option>
                        <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                        <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
                        <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select">
                        <option value="">-- ความเร่งด่วนทั้งหมด --</option>
                        <option value="low" <?= ($_GET['priority'] ?? '') === 'low' ? 'selected' : '' ?>>ต่ำ</option>
                        <option value="medium" <?= ($_GET['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>ปานกลาง</option>
                        <option value="high" <?= ($_GET['priority'] ?? '') === 'high' ? 'selected' : '' ?>>สูง</option>
                        <option value="urgent" <?= ($_GET['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>เร่งด่วน</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="document_number" class="form-control" placeholder="ค้นหาเลขที่เอกสาร..." value="<?= htmlspecialchars($_GET['document_number'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-search me-2"></i>ค้นหา
                    </button>
                </div>
            </form>
        </div>

        <!-- Request List -->
        <div class="glass-card p-4">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3 class="fw-bold mb-3">ยังไม่มีคำขอบริการ Service </h3>


                </div>
            <?php else: ?>
                <div class="mb-4">
                    <h4 class="fw-bold">
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>
                        คำขอรายการ Service (<?= count($requests) ?> รายการ)
                    </h4>
                </div>

                <div class="row g-4">
                    <?php foreach ($requests as $req): ?>
                        <div class="col-12">
                            <div class="request-card">
                                <!-- Document Number -->
                                <?php if (!empty($req['document_number'])): ?>
                                    <div class="document-info">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="document-number">
                                                    <i class="fas fa-file-alt me-2"></i>
                                                    เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    สร้างเมื่อ: <?= $req['document_created_at'] ? date('d/m/Y H:i', strtotime($req['document_created_at'])) : 'ไม่ระบุ' ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    รหัสคลัง: <?= htmlspecialchars($req['warehouse_number'] ?? '') ?> |
                                                    ชื่อย่อเเผนก: <?= htmlspecialchars($req['code_name'] ?? '') ?> |
                                                    Running: <?= htmlspecialchars($req['running_number'] ?? '') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Service Info -->
                                <?php if ($req['service_name']): ?>
                                    <div class="service-info">
                                        <strong>
                                            <i class="fas fa-<?= $req['service_category'] === 'development' ? 'code' : 'tools' ?> me-2"></i>
                                            <?= htmlspecialchars($req['service_name']) ?>
                                        </strong>
                                        <span class="badge bg-info ms-2"><?= htmlspecialchars($req['service_category']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                        <div class="request-description">
                                            <?= nl2br(htmlspecialchars(substr($req['description'], 0, 200))) ?>
                                            <?= strlen($req['description']) > 200 ? '...' : '' ?>
                                        </div>
                                    </div>

                                    <div class="ms-3 text-end">
                                        <?php
                                        $status_labels = [
                                            'pending' => 'รอดำเนินการ',
                                            'div_mgr_review' => 'รอผู้จัดการฝ่ายพิจารณา',
                                            'assignor_review' => 'รอผู้จัดการแผนกพิจารณา',
                                            'gm_review' => 'รอผู้จัดการทั่วไปพิจารณา',
                                            'senior_gm_review' => 'รอผู้จัดการอาวุโสพิจารณา',
                                            'approved' => 'อนุมัติแล้ว',
                                            'rejected' => 'ไม่อนุมัติ',
                                            'in_progress' => 'กำลังดำเนินการ',
                                            'completed' => 'เสร็จสิ้น'
                                        ];
                                        $status_class = in_array($req['status'], ['approved', 'completed']) ? 'status-approved' : ($req['status'] === 'rejected' ? 'status-rejected' : 'status-pending');
                                        ?>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status_labels[$req['status']] ?? $req['status'] ?>
                                        </span>

                                        <?php if ($req['priority']): ?>
                                            <div class="mt-2">
                                                <?php
                                                $priority_labels = [
                                                    'low' => 'ต่ำ',
                                                    'medium' => 'ปานกลาง',
                                                    'high' => 'สูง',
                                                    'urgent' => 'เร่งด่วน'
                                                ];
                                                ?>
                                                <span class="priority-badge priority-<?= $req['priority'] ?>">
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    <?= $priority_labels[$req['priority']] ?? 'ปานกลาง' ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($req['attachment_count'] > 0): ?>
                                    <div class="attachment-info">
                                        <i class="fas fa-paperclip"></i>
                                        <?= $req['attachment_count'] ?> ไฟล์แนบ
                                    </div>
                                <?php endif; ?>

                                <div class="request-meta">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        ส่งคำขอ: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                        <?php if ($req['updated_at'] !== $req['created_at']): ?>
                                            <span class="ms-3">
                                                <i class="fas fa-edit me-1"></i>
                                                อัปเดต: <?= date('d/m/Y H:i', strtotime($req['updated_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="ms-3">
                                            <i class="fa-solid fa-hourglass-start me-1"></i>
                                            จะเสร็จสิ้นภายใน:
                                            <?php if (!empty($req['estimated_days'])): ?>
                                                <?= htmlspecialchars($req['estimated_days']) ?> วัน
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </span>


                                    </div>

                                    <!-- ปุ่มดูสถานะและแก้ไข -->
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary btn-sm" type="button"
                                            onclick="toggleStatus('status_<?= $req['id'] ?>')">
                                            <i class="fas fa-eye me-1"></i>ดูสถานะการอนุมัติ
                                        </button>

                                    </div>
                                </div>

                                <!-- ส่วนแสดงสถานะการอนุมัติ -->
                                <div class="collapse mt-4" id="status_<?= $req['id'] ?>">
                                    <div class="card card-body">
                                        <h5 class="fw-bold mb-4">
                                            <i class="fas fa-route me-2 text-primary"></i>ขั้นตอนการอนุมัติและดำเนินการ
                                        </h5>

                                        <div class="approval-timeline">
                                            <!-- ขั้นตอนที่ 5: การพัฒนา -->
                                            <?php if ($req['task_status']): ?>
                                                <div class="timeline-step <?=
                                                                            in_array($req['task_status'], ['completed', 'accepted']) ? 'completed' : (in_array($req['task_status'], ['received', 'in_progress', 'on_hold']) ? 'current' : 'pending')
                                                                            ?>">
                                                    <div class="step-number">5</div>
                                                    <div class="step-content">
                                                        <div class="step-title">การพัฒนา</div>
                                                        <div class="step-status">
                                                            <?php
                                                            $task_statuses = [
                                                                'pending' => '<i class="fas fa-clock text-warning"></i> รอรับงาน',
                                                                'received' => '<i class="fas fa-check text-success"></i> รับงานแล้ว',
                                                                'in_progress' => '<i class="fas fa-cog fa-spin text-primary"></i> กำลังดำเนินการ',
                                                                'on_hold' => '<i class="fas fa-pause text-warning"></i> พักงาน',
                                                                'completed' => '<i class="fas fa-check-double text-success"></i> เสร็จสิ้น',
                                                                'accepted' => '<i class="fas fa-star text-success"></i> ยอมรับงาน'
                                                            ];
                                                            echo $task_statuses[$req['task_status']] ?? '';
                                                            ?>
                                                        </div>
                                                        <?php if ($req['progress_percentage'] !== null): ?>
                                                            <div class="progress mt-2 mb-2" style="height: 10px;">
                                                                <div class="progress-bar bg-primary" style="width: <?= $req['progress_percentage'] ?>%"></div>
                                                            </div>
                                                            <div class="progress-text"><?= $req['progress_percentage'] ?>% เสร็จสิ้น</div>
                                                        <?php endif; ?>
                                                        <?php if ($req['task_started_at']): ?>
                                                            <div class="step-date">
                                                                <i class="fas fa-play me-1"></i>
                                                                เริ่มงาน: <?= date('d/m/Y H:i', strtotime($req['task_started_at'])) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($req['task_completed_at']): ?>
                                                            <div class="step-date">
                                                                <i class="fas fa-flag-checkered me-1"></i>
                                                                เสร็จงาน: <?= date('d/m/Y H:i', strtotime($req['task_completed_at'])) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($req['developer_notes']): ?>
                                                            <div class="step-notes">
                                                                <i class="fas fa-code me-1"></i>
                                                                หมายเหตุจาก Developer: <?= htmlspecialchars($req['developer_notes']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- ขั้นตอนที่ 6: การรีวิวของผู้ใช้ -->
                                            <?php if ($req['task_status'] === 'completed' && !$req['review_status']): ?>
                                                <div class="timeline-step current">
                                                    <div class="step-number">6</div>
                                                    <div class="step-content">
                                                        <div class="step-title">รีวิวและยอมรับงาน</div>
                                                        <div class="step-status">
                                                            <i class="fas fa-clock text-warning"></i> รอการรีวิวจากคุณ
                                                        </div>
                                                        <div class="mt-3">
                                                            <a href="review_service.php?request_id=<?= $req['id'] ?>" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-star me-1"></i>รีวิวงาน
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif ($req['review_status']): ?>
                                                <div class="timeline-step completed">
                                                    <div class="step-number">6</div>
                                                    <div class="step-content">
                                                        <div class="step-title">รีวิวและยอมรับงาน</div>
                                                        <div class="step-status">
                                                            <?php if ($req['review_status'] === 'accepted'): ?>
                                                                <i class="fas fa-star text-success"></i> ยอมรับงานแล้ว
                                                                <?php if ($req['rating']): ?>
                                                                    <div class="rating-stars mt-2">
                                                                        <?= str_repeat('⭐', $req['rating']) ?>
                                                                        <span class="text-muted ms-2">(<?= $req['rating'] ?>/5)</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php elseif ($req['review_status'] === 'revision_requested'): ?>
                                                                <i class="fas fa-redo text-warning"></i> ขอแก้ไข
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($req['user_reviewed_at']): ?>
                                                            <div class="step-date">
                                                                <i class="fas fa-calendar-check me-1"></i>
                                                                รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($req['user_reviewed_at'])) ?>
                                                            </div>
                                                            <!-- แสดงความคืบหน้า -->
                                                            <?php if ($req['progress_percentage'] !== null): ?>
                                                                <div class="progress-container mt-2">
                                                                    <div class="progress-bar">
                                                                        <div class="progress-fill" style="width: <?= $req['progress_percentage'] ?>%"></div>
                                                                    </div>
                                                                    <small class="progress-text"><?= $req['progress_percentage'] ?>% เสร็จสิ้น</small>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- แสดง Subtasks สำหรับงาน Development -->
                                                            <?php if ($req['service_category'] === 'development' && $req['current_step'] !== 'developer_self_created'): ?>
                                                                <div class="subtasks-preview mt-2">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                                        onclick="loadSubtasks(<?= $req['task_id'] ?>)">
                                                                        <i class="fas fa-tasks me-1"></i>ดู Subtasks
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <?php if ($req['review_comment']): ?>
                                                            <div class="step-notes">
                                                                <i class="fas fa-comment me-1"></i>
                                                                ความเห็น: <?= htmlspecialchars($req['review_comment']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
function toggleStatus(id) {
    const element = document.getElementById(id);
    if (!element) return;

    // Toggle class 'show' เพื่อเปิด/ปิด collapse
    element.classList.toggle('show');
}
</script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const collapseElements = document.querySelectorAll('.collapse');
            collapseElements.forEach(function(element) {
                element.addEventListener('shown.bs.collapse', function() {
                    this.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                });
            });
        });

        // อัปเดตฟังก์ชัน loadSubtasks เพื่อเก็บ task_id
        function loadSubtasks(taskId) {
            currentTaskId = taskId;

            const modal = document.getElementById('subtasksModal');
            const content = document.getElementById('subtasksContent');

            // แสดง loading
            content.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fs-2"></i><br>กำลังโหลด...</div>';

            // เปิด modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // โหลดข้อมูล subtasks
            fetch(`../developer/get_subtasks.php?task_id=${taskId}`)
                .then(response => response.text())
                .then(data => {
                    content.innerHTML = data;
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
        }

        // ฟังก์ชันอัปเดตสถานะ subtask
        function updateSubtaskStatus(subtaskId, status) {
            const formData = new FormData();
            formData.append('subtask_id', subtaskId);
            formData.append('status', status);

            fetch('../developer/update_subtask_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // รีโหลด subtasks
                        const taskId = document.querySelector('[onclick*="loadSubtasks"]').getAttribute('onclick').match(/\d+/)[0];
                        loadSubtasks(taskId);

                        // รีโหลดหน้าเพื่ออัปเดต progress หลัก
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                });
        }

        // ฟังก์ชันอัปเดตหมายเหตุ subtask
        function updateSubtaskNotes(subtaskId) {
            const notes = document.getElementById('notes_' + subtaskId).value;

            const formData = new FormData();
            formData.append('subtask_id', subtaskId);
            formData.append('notes', notes);

            fetch('../developer/update_subtask_notes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('เกิดข้อผิดพลาดในการบันทึกหมายเหตุ');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    </script>



    <!-- JavaScript toggle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.view-timeline-btn');
            const timelineContent = document.getElementById('timelineContent');

            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    const subtasks = JSON.parse(button.dataset.subtasks || '[]');
                    timelineContent.innerHTML = '';

                    if (subtasks.length === 0) {
                        timelineContent.innerHTML = '<li class="list-group-item text-muted">ไม่มีขั้นตอนงาน</li>';
                    } else {
                        // คำนวณเปอร์เซ็นต์รวม
                        let totalPercentage = 0;
                        subtasks.forEach(task => {
                            totalPercentage += Number(task.percentage) || 0;
                        });

                        // แสดงแต่ละขั้นตอน
                        subtasks.forEach((task, index) => {
                            const statusLabel = {
                                'pending': 'รอดำเนินการ',
                                'received': 'รับงานแล้ว',
                                'in_progress': 'กำลังดำเนินการ',
                                'on_hold': 'พักงานชั่วคราว',
                                'completed': 'เสร็จสิ้น',
                                'rejected': 'ถูกปฏิเสธ'
                            } [task.status] || task.status;

                            const item = `
                        <li class="list-group-item">
                            <div><strong>ขั้นตอน ${task.step_order}:</strong> ${task.step_name}</div>
                            <div>สถานะ: <span class="badge bg-${task.status === 'completed' ? 'success' : 'secondary'}">${statusLabel}</span></div>
                            ${task.step_description ? `<div><small class="text-muted">รายละเอียดขั้นตอน: ${task.step_description}</small></div>` : ''}
                            ${task.notes ? `<div><small class="text-muted">หมายเหตุ: ${task.notes}</small></div>` : ''}
                            ${task.started_at ? `<div><small class="text-muted">วันที่เริ่มขั้นตอน: ${task.started_at}</small></div>` : ''}
                            ${task.completed_at ? `<div><small class="text-muted">วันที่เสร็จ: ${task.completed_at}</small></div>` : ''}
                            ${task.percentage ? `<div><small class="text-muted">เปอร์เซ็นต์ขั้นตอน: ${task.percentage}%</small></div>` : ''}
                        </li>`;
                            timelineContent.innerHTML += item;
                        });

                        // แสดงเปอร์เซ็นต์รวมด้านล่าง (ถ้าต้องการ)
                        timelineContent.innerHTML += `
                    <li class="list-group-item">
                        <strong>รวมความคืบหน้า: ${totalPercentage}%</strong>
                        ${totalPercentage >= 100 ? '<span class="badge bg-success ms-2">งานเสร็จสมบูรณ์</span>' : ''}
                    </li>
                `;
                    }

                    const modal = new bootstrap.Modal(document.getElementById('timelineModal'));
                    modal.show();
                });
            });
        });
    </script>

</body>

</html>