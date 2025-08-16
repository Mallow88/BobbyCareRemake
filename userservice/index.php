<?php
session_start();
require_once __DIR__ . '/../config/database.php'; // ต้องมี $pdo ในไฟล์นี้

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'userservice') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$picture_url = $_SESSION['picture_url'] ?? null;


// เริ่มสร้าง query และ parameter
$conditions = ["sr.user_id = ?"];
$params = [$user_id];

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
    dev.name AS dev_name,
    dev.lastname AS dev_lastname,

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
    
    -- Task
    t.id AS task_id,
    t.task_status,
    t.progress_percentage,
    t.started_at AS task_started_at,
    t.completed_at AS task_completed_at,
    t.developer_notes,

    -- Review
    ur.rating,
    ur.review_comment,
    ur.status AS review_status,
    ur.reviewed_at AS user_reviewed_at

FROM service_requests sr
LEFT JOIN request_attachments ra ON sr.id = ra.service_request_id
LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
LEFT JOIN services s ON sr.service_id = s.id

LEFT JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
LEFT JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id

LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id
LEFT JOIN users dev ON aa.assigned_developer_id = dev.id

LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
LEFT JOIN users gm ON gma.gm_user_id = gm.id

LEFT JOIN senior_gm_approvals sgma ON sr.id = sgma.service_request_id
LEFT JOIN users senior_gm ON sgma.senior_gm_user_id = senior_gm.id

LEFT JOIN tasks t ON sr.id = t.service_request_id
LEFT JOIN user_reviews ur ON t.id = ur.task_id

LEFT JOIN task_subtasks ts ON t.id = ts.task_id

WHERE " . implode(' AND ', $conditions) . "

GROUP BY sr.id
ORDER BY sr.created_at DESC
";

// ดึงข้อมูล request ทั้งหมด
// $stmt = $conn ->prepare($sql);
// $stmt->execute($params);
// $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// // ดึง subtasks เพิ่มเติมถ้ามี task_id
// foreach ($requests as &$request) {
//     $task_id = $request['task_id'];

//     if ($task_id) {
//         $stmt = $conn->prepare("SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY step_order ASC");
//         $stmt->execute([$task_id]);
//         $request['subtasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
//     } else {
//         $request['subtasks'] = []; // ไม่มี task_id
//     }
// }
// ดึงข้อมูล request ทั้งหมด
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// เตรียม statement สำหรับ subtasks ล่วงหน้า
$sub_stmt = $conn->prepare("SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY step_order ASC");

// ดึง subtasks เพิ่มเติมถ้ามี task_id
foreach ($requests as &$request) {
  $task_id = $request['task_id'];

  if ($task_id) {
    $sub_stmt->execute([$task_id]);
    $request['subtasks'] = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $request['subtasks'] = [];
  }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>BobbyCareRemake</title>
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="icon" href="../img/logo/bobby-icon.png" type="image/x-icon" />

  <!-- Fonts and icons -->
  <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
  <script>
    WebFont.load({
      google: {
        families: ["Public Sans:300,400,500,600,700"]
      },
      custom: {
        families: [
          "Font Awesome 5 Solid",
          "Font Awesome 5 Regular",
          "Font Awesome 5 Brands",
          "simple-line-icons",
        ],
        urls: ["../assets/css/fonts.min.css"],
      },
      active: function() {
        sessionStorage.fonts = true;
      },
    });
  </script>

  <!-- CSS Files -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/plugins.min.css" />
  <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />

  <!-- CSS Just for demo purpose, don't include it in your project -->
  <link rel="stylesheet" href="../assets/css/demo.css" />
  <link rel="stylesheet" href="css/timeline.css">
  <link rel="stylesheet" href="css/index.css">
</head>
<body>
  <div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar" data-background-color="dark">
      <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
          <a href="index.php" class="logo">
            <img src="../img/logo/bobby-full.png" alt="navbar brand" class="navbar-brand" height="30" />
          </a>
          <div class="nav-toggle">
            <button class="btn btn-toggle toggle-sidebar">
              <i class="gg-menu-right"></i>
            </button>
            <button class="btn btn-toggle sidenav-toggler">
              <i class="gg-menu-left"></i>
            </button>
          </div>
          <button class="topbar-toggler more">
            <i class="gg-more-vertical-alt"></i>
          </button>
        </div>
        <!-- End Logo Header -->
      </div>
      <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
          <ul class="nav nav-secondary">
            <li class="nav-item">
              <a data-bs-toggle="collapse" href="index.php" class="collapsed" aria-expanded="false">
                <i class="fas fa-home"></i>
                <p>หน้าหลัก</p>
                <span class="caret"></span>
              </a>
              <div class="collapse" id="dashboard">
                <ul class="nav nav-collapse">
                  <li>
                    <a href="index.php">
                      <span class="sub-item">หน้าหลัก 1</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-section">
              <span class="sidebar-mini-icon">
                <i class="fa fa-ellipsis-h"></i>
              </span>
              <h4 class="text-section">Components</h4>
            </li>
            <li class="nav-item">
              <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <p>Logout</p>
                <span class="badge badge-success"></span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- End Sidebar -->

    <div class="main-panel">
      <div class="main-header">
        <div class="main-header-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="../dashboard2.php" class="logo">
              <img src="../img/logo/bobby-full.png" alt="navbar brand" class="navbar-brand" height="20" />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>

        <!-- Navbar Header -->
        <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
          <div class="container-fluid">
            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">

              <!-- โปรไฟล์ -->
              <li class="nav-item topbar-user dropdown hidden-caret">
                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                  <div class="avatar-sm">
                    <img src="<?= htmlspecialchars($picture_url) ?>" alt="..." class="avatar-img rounded-circle" />
                  </div>
                  <span class="profile-username">
                    <span class="op-7">คุณ:</span>
                    <span class="fw-bold"><?= htmlspecialchars($_SESSION['name']) ?></span>
                  </span>
                </a>

              </li>


            </ul>
          </div>
        </nav>
        <!-- End Navbar -->
      </div>

      <div class="container">


        <!-- Filter Form -->
        <div class="glass-card p-4 mb-4">
          <form method="GET" class="row g-3">
           
            <div class="col-md-10">
              <input type="text" name="document_number" class="form-control" placeholder="ค้นหาเฉพาะเลขที่เอกสาร..." value="<?= htmlspecialchars($_GET['document_number'] ?? '') ?>">
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
              <h3 class="fw-bold mb-3">ยังไม่มีคำขอบริการ</h3>
              
            </div>
          <?php else: ?>
            <div class="mb-4">
              <h4 class="fw-bold">
                <i class="fas fa-clipboard-list me-2 text-primary"></i>
                คำขอทั้งหมด (<?= count($requests) ?> รายการ)
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
                           
                              <i class="fas fa-file-alt me-2"></i>
                              เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?>
                           
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>

                    <!-- Service Info -->
                    <?php if ($req['service_name']): ?>
                      <div class="service-info">
                        <strong>
                          <i class="fas fa-<?= $req['service_category'] === 'development' ? 'code' : 'tools' ?> me-2"></i>
                          ประเภทคำขอ : <?= htmlspecialchars($req['service_name']) ?>
                        </strong>
                        <span class="badge bg-info ms-2"><?= htmlspecialchars($req['service_category']) ?></span>
                      </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <div class="flex-grow-1">
                        <div class="request-title">หัวข้อ : <?= htmlspecialchars($req['title']) ?></div>
                        <div class="request-description"> รายละเอียด :
                          <?= nl2br(htmlspecialchars(substr($req['description'], 0, 2000))) ?>
                          <?= strlen($req['description']) > 2000 ? '...' : '' ?>
                        </div>
                      </div>

                      <div class="ms-3 text-end">
                        <?php
                        $status_labels = [
                          'approved' => 'เสร็จเรียบร้อย',
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

                      </div>
                      <!-- ปุ่มดูสถานะและแก้ไข -->
                      <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" type="button"
                          onclick="toggleStatus('status_<?= $req['id'] ?>')">
                          <i class="fas fa-eye me-1"></i>ดูสถานะ
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
    </div>
  </div>
  </div>
  </div>




  </div>
  <!--   Core JS Files   -->
  <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <!-- Chart JS -->
  <script src="../assets/js/plugin/chart.js/chart.min.js"></script>
  <!-- jQuery Scrollbar -->
  <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
  <!-- Kaiadmin JS -->
  <script src="../assets/js/kaiadmin.min.js"></script>
  <!-- Kaiadmin DEMO methods, don't include it in your project! -->
  <script src="../assets/js/setting-demo2.js"></script>



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