<?php
session_start();
require_once __DIR__ . '/../config/database.php'; // ต้องมี $pdo ในไฟล์นี้

// ตรวจสอบว่า login แล้ว
if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit();
}

$user_id = $_SESSION['user_id'];

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
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">


  <title>BobbyCareDev-รายการคำขอ</title>
  <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">

  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.0.0" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --bc-primary: #4f46e5;
      --bc-primary-2: #6366f1;
      --bc-success: #10b981;
      --bc-danger: #ef4444;
      --bc-warning: #f59e0b;
      --bc-info: #0ea5e9;
      --bc-muted: #6b7280;
      --bc-bg: #f8fafc;
      --bc-card: #ffffff;
      --bc-border: #e5e7eb;
    }

    /* Container tweaks */
    .requests-section .card {
      border: none;
      box-shadow: 0 6px 20px rgba(2, 6, 23, .06);
    }

    .requests-section .card-header {
      background: linear-gradient(90deg, #e9227e, #e9227e);
      color: #fff;
      border-radius: .75rem .75rem 0 0;
    }

    .requests-section .card-header h6 {
      font-weight: 700;
      letter-spacing: .3px;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: var(--bc-muted);
    }

    .empty-state i {
      font-size: 2.5rem;
      margin-bottom: .75rem;
      opacity: .8;
    }

    .btn-gradient {
      background: linear-gradient(135deg, var(--bc-primary), var(--bc-info));
      color: #fff;
      border: none;
    }

    .btn-gradient:hover {
      filter: brightness(1.05);
      color: #fff;
    }

    /* Request card */
    .request-card {
      background: var(--bc-card);
      border: 1px solid var(--bc-border);
      border-radius: 1rem;
      padding: 1rem;
      transition: transform .15s ease, box-shadow .15s ease;
    }

    .request-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(2, 6, 23, .08);
    }

    .request-card+.request-card {
      margin-top: 1rem;
    }

    /* Document info bar */
    .document-info {
      background: #f3f4f6;
      border: 1px dashed var(--bc-border);
      border-radius: .75rem;
      padding: .75rem .9rem;
      margin-bottom: .75rem;
    }

    .document-number {
      font-weight: 700;
      letter-spacing: .3px;
    }

    /* Service info */
    .service-info {
      display: flex;
      align-items: center;
      gap: .5rem;
      margin-bottom: .25rem;
    }

    .service-info strong {
      font-weight: 700;
    }

    .badge.rounded {
      border-radius: 999px;
    }

    /* Title & description */
    .request-title {
      font-weight: 700;
      font-size: 1.05rem;
      line-height: 1.35;
      margin-bottom: .25rem;
    }

    .request-description {
      color: var(--bc-muted);
      font-size: .95rem;
    }

    /* Badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      font-weight: 600;
      padding: .35rem .65rem;
      border-radius: .75rem;
      font-size: .85rem;
    }

    .status-approved {
      background: rgba(16, 185, 129, .12);
      color: var(--bc-success);
      border: 1px solid rgba(16, 185, 129, .35);
    }

    .status-rejected {
      background: rgba(239, 68, 68, .12);
      color: var(--bc-danger);
      border: 1px solid rgba(239, 68, 68, .35);
    }

    .status-pending {
      background: rgba(99, 102, 241, .12);
      color: var(--bc-primary);
      border: 1px solid rgba(99, 102, 241, .35);
    }

    .priority-badge {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .25rem .55rem;
      border-radius: .65rem;
      font-size: .8rem;
      font-weight: 700;
    }

    .priority-low {
      background: #e5f9f0;
      color: #047857;
    }

    .priority-medium {
      background: #eff6ff;
      color: #1d4ed8;
    }

    .priority-high {
      background: #fff7ed;
      color: #c2410c;
    }

    .priority-urgent {
      background: #fef2f2;
      color: #b91c1c;
    }

    .attachment-info {
      color: var(--bc-muted);
      font-size: .9rem;
      margin-top: .5rem;
    }

    .attachment-info i {
      margin-right: .35rem;
    }

    .request-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: .75rem;
      padding-top: .75rem;
      border-top: 1px dashed var(--bc-border);
      margin-top: .75rem;
    }

    .request-meta .text-muted {
      font-size: .9rem;
    }

    /* Timeline v2 */
    .approval-timeline {
      position: relative;
      padding-left: .25rem;
    }

    .approval-timeline .timeline-track {
      position: absolute;
      left: 13px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: linear-gradient(180deg, var(--bc-primary), var(--bc-info));
      opacity: .2;
    }

    .timeline-step {
      position: relative;
      display: grid;
      grid-template-columns: 28px 1fr;
      gap: .75rem;
      padding: .9rem 0;
    }

    .timeline-step+.timeline-step {
      border-top: 1px dashed var(--bc-border);
    }

    .timeline-dot {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: .85rem;
      color: #fff;
      background: var(--bc-muted);
      align-self: start;
      margin-top: .15rem;
      box-shadow: 0 1px 0 rgba(0, 0, 0, .04);
    }

    .timeline-step.completed .timeline-dot {
      background: var(--bc-success);
    }

    .timeline-step.rejected .timeline-dot {
      background: var(--bc-danger);
    }

    .timeline-step.current .timeline-dot {
      background: var(--bc-primary);
    }

    .step-body {
      min-width: 0;
    }

    .step-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
    }

    .step-title {
      font-weight: 800;
      margin: 0;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .25rem .6rem;
      font-size: .8rem;
      font-weight: 700;
      border-radius: 999px;
      border: 1px solid transparent;
      white-space: nowrap;
    }

    .chip-success {
      background: rgba(16, 185, 129, .12);
      color: var(--bc-success);
      border-color: rgba(16, 185, 129, .35);
    }

    .chip-danger {
      background: rgba(239, 68, 68, .12);
      color: var(--bc-danger);
      border-color: rgba(239, 68, 68, .35);
    }

    .chip-warn {
      background: rgba(245, 158, 11, .12);
      color: var(--bc-warning);
      border-color: rgba(245, 158, 11, .35);
    }

    .chip-muted {
      background: rgba(99, 102, 241, .1);
      color: var(--bc-primary);
      border-color: rgba(99, 102, 241, .25);
    }

    .step-meta {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .35rem .75rem;
      margin-top: .4rem;
    }

    .meta-item {
      color: var(--bc-muted);
      font-size: .9rem;
      display: flex;
      align-items: center;
      gap: .4rem;
    }

    @media (max-width: 767.98px) {
      .step-head {
        flex-direction: column;
        align-items: flex-start;
      }

      .step-meta {
        grid-template-columns: 1fr;
      }
    }

    /* Progress */
    / .progress {
      height: 8px;
      border-radius: 999px;
    }

    .progress-text {
      color: var(--bc-muted);
      font-size: .85rem;
    }

    .progress-container {
      width: 100%;
    }

    .progress-bar {
      width: 100%;
      height: 8px;
      background: #eef2ff;
      border-radius: 999px;
      position: relative;
      overflow: hidden;
    }

    .progress-fill {
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      background: linear-gradient(90deg, var(--bc-primary), var(--bc-info));
    }

    /* Utilities */
    .glass-card {
      background: rgba(255, 255, 255, .7);
      backdrop-filter: blur(4px);
      border: 1px solid var(--bc-border);
      border-radius: 1rem;
    }

    /* Responsive */
    @media (max-width: 767.98px) {
      .request-meta {
        flex-direction: column;
        align-items: flex-start;
      }

      .request-card {
        padding: .9rem;
      }

      .request-title {
        font-size: 1rem;
      }

      .document-info {
        font-size: .9rem;
      }
    }
 


    /* ===== BCRQ Timeline (conflict-proof) ===== */
    .bcrq-collapse {

    }

    .bcrq-timeline {
      position: relative;
      padding-left: .25rem;
    }

    .bcrq-track {
      position: absolute;
      left: 13px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: linear-gradient(180deg, var(--bc-primary), var(--bc-info));
      opacity: .2;
    }

    .bcrq-step {
      position: relative;
      display: grid;
      grid-template-columns: 28px 1fr;
      gap: .75rem;
      padding: .9rem 0;
    }

    .bcrq-step+.bcrq-step {
      border-top: 1px dashed var(--bc-border);
    }

    .bcrq-dot {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: .85rem;
      color: #fff;
      background: var(--bc-muted);
      align-self: start;
      margin-top: .15rem;
      box-shadow: 0 1px 0 rgba(0, 0, 0, .04);
    }

    .bcrq-step.completed .bcrq-dot {
      background: var(--bc-success);
    }

    .bcrq-step.rejected .bcrq-dot {
      background: var(--bc-danger);
    }

    .bcrq-step.current .bcrq-dot {
      background: var(--bc-primary);
    }

    .bcrq-body {
      min-width: 0;
    }

    .bcrq-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
    }

    .bcrq-title {
      font-weight: 800;
      margin: 0;
      line-height: 1.3;
      white-space: normal;
      word-break: break-word;
    }

    .bcrq-chip {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .25rem .6rem;
      font-size: .8rem;
      line-height: 1;
      font-weight: 700;
      border-radius: 999px;
      border: 1px solid transparent;
      white-space: nowrap;
    }

    .bcrq-chip--success {
      background: rgba(16, 185, 129, .12);
      color: var(--bc-success);
      border-color: rgba(16, 185, 129, .35);
    }

    .bcrq-chip--danger {
      background: rgba(239, 68, 68, .12);
      color: var(--bc-danger);
      border-color: rgba(239, 68, 68, .35);
    }

    .bcrq-chip--warn {
      background: rgba(245, 158, 11, .12);
      color: var(--bc-warning);
      border-color: rgba(245, 158, 11, .35);
    }

    .bcrq-chip--muted {
      background: rgba(99, 102, 241, .1);
      color: var(--bc-primary);
      border-color: rgba(99, 102, 241, .25);
    }

    .bcrq-meta {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .35rem .75rem;
      margin-top: .4rem;
    }

    .bcrq-meta__item {
      color: var(--bc-muted);
      font-size: .9rem;
      display: flex;
      align-items: center;
      gap: .4rem;
      word-break: break-word;
    }

    .bcrq-progress-container {
      width: 100%;
    }

    .bcrq-progress-text {
      color: var(--bc-muted);
      font-size: .85rem;
    }

    .bcrq-progress-fill {
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      background: linear-gradient(90deg, var(--bc-primary), var(--bc-info));
    }

    @media (max-width: 767.98px) {
      .bcrq-head {
        flex-direction: column;
        align-items: flex-start;
      }

      .bcrq-meta {
        grid-template-columns: 1fr;
      }

      .bcrq-step {
        grid-template-columns: 24px 1fr;
        gap: .5rem;
      }

      .bcrq-dot {
        width: 24px;
        height: 24px;
        font-size: .8rem;
      }
    }
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-0" target="_blank">
        <img src="../img/logo/bobby-full.png" class="navbar-brand-img h-100" alt="main_logo">
      </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto  max-height-vh-100" id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link text-white " href="../dashboard2.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">dashboard</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="create3.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">add_task</i>
            </div>
            <span class="nav-link-text ms-1">สร้างคำขอบริการ</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white active bg-gradient-primary" href="index2.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">assignment</i>
            </div>
            <span class="nav-link-text ms-1">รายการคำขอ</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="track_status.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">track_changes</i>
            </div>
            <span class="nav-link-text ms-1">ติดตามสถานะ</span>
          </a>
        </li>

        <li class="nav-item mt-3">
          <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Account pages</h6>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../profile.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">person</i>
            </div>
            <span class="nav-link-text ms-1">Profile</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../logout.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">login</i>
            </div>
            <span class="nav-link-text ms-1">Logout</span>
          </a>
        </li>
      </ul>
    </div>
    <div class="sidenav-footer position-absolute w-100 bottom-0 ">

    </div>
  </aside>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
          <h6 class="font-weight-bolder mb-0">รายการคำขอ</h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">

          <ul class="navbar-nav  justify-content-end">
            <li class="nav-item d-flex align-items-center">
            </li>
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner">
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                </div>
              </a>
            </li>
            <li class="nav-item px-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0">
                <i class="fa fa-cog fixed-plugin-button-nav cursor-pointer"></i>
              </a>
            </li>

          </ul>
        </div>
      </div>
    </nav>
    <!-- End Navbar -->

    <!-- ===================== Requests List (Left Column) ===================== -->
    <div class="container-lg my-5">
      <div class="row requests-section">
        <div class="col-md-12 mt-4">
          <div class="card">
            <div class="card-header pb-0 px-3">
              <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>คำขอทั้งหมด</h6>
            </div>

            <div class="card-body pt-4 p-3">
              <?php if (empty($requests)): ?>
                <div class="empty-state glass-card">
                  <i class="fas fa-inbox"></i>
                  <h3 class="fw-bold mb-2">ยังไม่มีคำขอบริการ</h3>
                  <p class="mb-4">เริ่มต้นด้วยการสร้างคำขอบริการใหม่</p>
                  <a href="create.php" class="btn btn-gradient btn-lg">
                    <i class="fas fa-plus me-2"></i>สร้างคำขอแรก
                  </a>
                </div>
              <?php else: ?>

                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="fw-bold mb-0">
                    คำขอทั้งหมด <span class="badge bg-light text-dark ms-2"><?= count($requests) ?> รายการ</span>
                  </h5>
                </div>

                <div class="row g-3">
                  <?php foreach ($requests as $req): ?>
                    <div class="col-12">
                      <div class="request-card">

                        <!-- Document Number -->
                        <?php if (!empty($req['document_number'])): ?>
                          <div class="document-info">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                              <div>
                                <div class="document-number">
                                  <i class="fas fa-file-alt me-2"></i>
                                  เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?>
                                </div>

                              </div>
                              <div class="text-end">
                                <small class="text-muted">
                                  รหัสคลัง: <?= htmlspecialchars($req['warehouse_number'] ?? '') ?> |
                                  ชื่อย่อแผนก: <?= htmlspecialchars($req['code_name'] ?? '') ?> |
                                  Running: <?= htmlspecialchars($req['running_number'] ?? '') ?>
                                </small>
                              </div>
                            </div>
                          </div>
                        <?php endif; ?>

                        <!-- Service Info -->
                        <?php if (!empty($req['service_name'])): ?>
                          <div class="service-info">
                            <strong>
                              <i class="fas fa-<?= ($req['service_category'] ?? '') === 'development' ? 'code' : 'tools' ?> me-2"></i>
                              ประเภทบริการ : <?= htmlspecialchars($req['service_name']) ?>
                            </strong>
                            <?php if (!empty($req['service_category'])): ?>
                              <span class="badge rounded bg-info-subtle text-dark"><?= htmlspecialchars($req['service_category']) ?></span>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>

                        <!-- Title / Desc + Status, Priority -->
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                          <div class="flex-grow-1">
                            <div class="request-title"> หัวข้อ: <?= htmlspecialchars($req['title']) ?></div>
                            <div class="request-description">
                              <?= nl2br(htmlspecialchars(substr($req['description'], 0, 500))) ?><?= (strlen($req['description'] ?? '') > 500 ? '...' : '') ?>
                            </div>
                          </div>

                          <div class="text-end" style="min-width: 180px;">
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
                            $status_class = in_array(($req['status'] ?? ''), ['approved', 'completed'])
                              ? 'status-approved' : (($req['status'] ?? '') === 'rejected' ? 'status-rejected' : 'status-pending');
                            ?>
                            <span class="status-badge <?= $status_class ?>">
                              <?= $status_labels[$req['status'] ?? 'pending'] ?? ($req['status'] ?? 'pending') ?>
                            </span>

                            <?php if (!empty($req['priority'])): ?>
                              <?php $priority_labels = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน']; ?>
                              <div class="mt-2">
                                <span class="priority-badge priority-<?= htmlspecialchars($req['priority']) ?>">
                                  <i class="fas fa-exclamation-circle"></i>
                                  <?= $priority_labels[$req['priority']] ?? 'ปานกลาง' ?>
                                </span>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>

                        <!-- Attachments -->
                        <?php if (!empty($req['attachment_count']) && (int)$req['attachment_count'] > 0): ?>
                          <div class="attachment-info">
                            <i class="fas fa-paperclip"></i>
                            <?= (int)$req['attachment_count'] ?> ไฟล์แนบ
                          </div>
                        <?php endif; ?>

                        <!-- Meta + Actions -->
                        <div class="request-meta">
                          <div class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            ส่งคำขอ: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                            <?php if (!empty($req['updated_at']) && $req['updated_at'] !== $req['created_at']): ?>
                              <span class="ms-3">
                                <i class="fas fa-edit me-1"></i>อัปเดต: <?= date('d/m/Y H:i', strtotime($req['updated_at'])) ?>
                              </span>
                            <?php endif; ?>
                            <span class="ms-3">
                              <i class="fa-solid fa-hourglass-start me-1"></i>จะเสร็จสิ้นภายใน:
                              <?= !empty($req['estimated_days']) ? htmlspecialchars($req['estimated_days']) . ' วัน' : '-' ?>
                            </span>
                          </div>
                          <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-primary btn-sm" type="button"
                              onclick="toggleStatus('status_<?= $req['id'] ?>')">
                              <i class="fas fa-eye me-1"></i>ดูสถานะการอนุมัติ
                            </button>
                            <?php if (in_array(($req['status'] ?? ''), ['pending', 'rejected'])): ?>
                              <a href="edit.php?id=<?= $req['id'] ?>" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-edit me-1"></i>แก้ไข
                              </a>
                            <?php endif; ?>
                          </div>
                        </div>

                        <!-- Approval Timeline (Collapse) -->
                        <div class="bcrq-collapse collapse mt-3" id="status_<?= $req['id'] ?>">
                          <div class="card card-body border-0" style="background:#f9fafb;">
                            <h6 class="fw-bold mb-3"><i class="fas fa-route me-2 text-primary"></i>ขั้นตอนการอนุมัติและดำเนินการ</h6>

                            <div class="bcrq-timeline">
                              <div class="bcrq-track"></div>

                              <!-- Step 1: Division Manager -->
                              <div class="bcrq-step <?= ($req['div_mgr_status'] ?? '') === 'approved' ? 'completed' : ((($req['div_mgr_status'] ?? '') === 'rejected') ? 'rejected' : ((($req['div_mgr_status'] ?? '') === 'pending') ? 'current' : 'pending')) ?>">
                                <div class="bcrq-dot">1</div>
                                <div class="bcrq-body">
                                  <div class="bcrq-head">
                                    <div class="bcrq-title">ผู้จัดการฝ่าย</div>
                                    <div>
                                      <?php if (($req['div_mgr_status'] ?? '') === 'approved'): ?>
                                        <span class="bcrq-chip bcrq-chip--success"><i class="fas fa-check-circle"></i> อนุมัติแล้ว</span>
                                      <?php elseif (($req['div_mgr_status'] ?? '') === 'rejected'): ?>
                                        <span class="bcrq-chip bcrq-chip--danger"><i class="fas fa-times-circle"></i> ไม่อนุมัติ</span>
                                      <?php elseif (($req['div_mgr_status'] ?? '') === 'pending'): ?>
                                        <span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-clock"></i> รอพิจารณา</span>
                                      <?php else: ?>
                                        <span class="bcrq-chip bcrq-chip--muted"><i class="fas fa-hourglass"></i> รอส่งเรื่อง</span>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                  <div class="bcrq-meta">
                                    <?php if (!empty($req['div_mgr_reviewed_at'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-calendar-check"></i>เวลาอนุมัติ : <?= date('d/m/Y H:i', strtotime($req['div_mgr_reviewed_at'])) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['div_mgr_name'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-user"></i>อนุมัติโดยคุณ : <?= htmlspecialchars(($req['div_mgr_name'] ?? '') . ' ' . ($req['div_mgr_lastname'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['div_mgr_reason'])): ?>
                                      <div class="bcrq-meta__item" style="grid-column: 1/-1;"><i class="fas fa-sticky-note"></i><?= htmlspecialchars($req['div_mgr_reason']) ?></div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>

                              <!-- Step 2: Assignor -->
                              <div class="bcrq-step <?= ($req['assignor_status'] ?? '') === 'approved' ? 'completed' : ((($req['assignor_status'] ?? '') === 'rejected') ? 'rejected' : ((($req['assignor_status'] ?? '') === 'pending' && ($req['div_mgr_status'] ?? '') === 'approved') ? 'current' : 'pending')) ?>">
                                <div class="bcrq-dot">2</div>
                                <div class="bcrq-body">
                                  <div class="bcrq-head">
                                    <div class="bcrq-title">ผู้จัดการแผนก</div>
                                    <div>
                                      <?php if (($req['assignor_status'] ?? '') === 'approved'): ?>
                                        <span class="bcrq-chip bcrq-chip--success"><i class="fas fa-check-circle"></i> อนุมัติแล้ว</span>
                                      <?php elseif (($req['assignor_status'] ?? '') === 'rejected'): ?>
                                        <span class="bcrq-chip bcrq-chip--danger"><i class="fas fa-times-circle"></i> ไม่อนุมัติ</span>
                                      <?php elseif (($req['assignor_status'] ?? '') === 'pending' && ($req['div_mgr_status'] ?? '') === 'approved'): ?>
                                        <span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-clock"></i> รอพิจารณา</span>
                                      <?php else: ?>
                                        <span class="bcrq-chip bcrq-chip--muted"><i class="fas fa-minus"></i> ยังไม่ถึงขั้นตอน</span>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                  <div class="bcrq-meta">
                                    <?php if (!empty($req['assignor_reviewed_at'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-calendar-check"></i>เวลาอนุมัติ : <?= date('d/m/Y H:i', strtotime($req['assignor_reviewed_at'])) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['assignor_name'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-user"></i>อนุมัติโดยคุณ : <?= htmlspecialchars(($req['assignor_name'] ?? '') . ' ' . ($req['assignor_lastname'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['dev_name'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-user-cog"></i>ผู้พัฒนาระบบงาน: <?= htmlspecialchars(($req['dev_name'] ?? '') . ' ' . ($req['dev_lastname'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['estimated_days'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-clock"></i>ประมาณการ: <?= htmlspecialchars($req['estimated_days']) ?> วัน</div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['assignor_reason'])): ?>
                                      <div class="bcrq-meta__item" style="grid-column: 1/-1;"><i class="fas fa-sticky-note"></i><?= htmlspecialchars($req['assignor_reason']) ?></div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>

                              <!-- Step 3: GM -->
                              <div class="bcrq-step <?= ($req['gm_status'] ?? '') === 'approved' ? 'completed' : ((($req['gm_status'] ?? '') === 'rejected') ? 'rejected' : ((($req['gm_status'] ?? '') === 'pending' && ($req['assignor_status'] ?? '') === 'approved') ? 'current' : 'pending')) ?>">
                                <div class="bcrq-dot">3</div>
                                <div class="bcrq-body">
                                  <div class="bcrq-head">
                                    <div class="bcrq-title">ผู้จัดการทั่วไป</div>
                                    <div>
                                      <?php if (($req['gm_status'] ?? '') === 'approved'): ?>
                                        <span class="bcrq-chip bcrq-chip--success"><i class="fas fa-check-circle"></i> อนุมัติแล้ว</span>
                                      <?php elseif (($req['gm_status'] ?? '') === 'rejected'): ?>
                                        <span class="bcrq-chip bcrq-chip--danger"><i class="fas fa-times-circle"></i> ไม่อนุมัติ</span>
                                      <?php elseif (($req['gm_status'] ?? '') === 'pending' && ($req['assignor_status'] ?? '') === 'approved'): ?>
                                        <span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-clock"></i> รอพิจารณา</span>
                                      <?php else: ?>
                                        <span class="bcrq-chip bcrq-chip--muted"><i class="fas fa-minus"></i> ยังไม่ถึงขั้นตอน</span>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                  <div class="bcrq-meta">
                                    <?php if (!empty($req['gm_reviewed_at'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-calendar-check"></i>เวลาอนุมัติ : <?= date('d/m/Y H:i', strtotime($req['gm_reviewed_at'])) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['gm_name'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-user"></i>อนุมัติโดยคุณ : <?= htmlspecialchars(($req['gm_name'] ?? '') . ' ' . ($req['gm_lastname'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['dev_name'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-user-cog"></i>ผู้พัฒนาระบบงาน: <?= htmlspecialchars(($req['dev_name'] ?? '') . ' ' . ($req['dev_lastname'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['budget_approved'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-money-bill-wave"></i>งบประมาณ: <?= number_format((float)$req['budget_approved'], 2) ?> บาท</div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['gm_reason'])): ?>
                                      <div class="bcrq-meta__item" style="grid-column: 1/-1;"><i class="fas fa-sticky-note"></i><?= htmlspecialchars($req['gm_reason']) ?></div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>

                              <!-- Step 4: Senior GM -->
                              <div class="bcrq-step <?= ($req['senior_gm_status'] ?? '') === 'approved' ? 'completed' : ((($req['senior_gm_status'] ?? '') === 'rejected') ? 'rejected' : ((($req['senior_gm_status'] ?? '') === 'pending' && ($req['gm_status'] ?? '') === 'approved') ? 'current' : 'pending')) ?>">
                                <div class="bcrq-dot">4</div>
                                <div class="bcrq-body">
                                  <div class="bcrq-head">
                                    <div class="bcrq-title">ผู้จัดการอาวุโส</div>
                                    <div>
                                      <?php if (($req['senior_gm_status'] ?? '') === 'approved'): ?>
                                        <span class="bcrq-chip bcrq-chip--success"><i class="fas fa-check-circle"></i> อนุมัติแล้ว</span>
                                      <?php elseif (($req['senior_gm_status'] ?? '') === 'rejected'): ?>
                                        <span class="bcrq-chip bcrq-chip--danger"><i class="fas fa-times-circle"></i> ไม่อนุมัติ</span>
                                      <?php elseif (($req['senior_gm_status'] ?? '') === 'pending' && ($req['gm_status'] ?? '') === 'approved'): ?>
                                        <span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-clock"></i> รอพิจารณา</span>
                                      <?php else: ?>
                                        <span class="bcrq-chip bcrq-chip--muted"><i class="fas fa-minus"></i> ยังไม่ถึงขั้นตอน</span>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                  <div class="bcrq-meta">
                                    <?php if (!empty($req['senior_gm_reviewed_at'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-calendar-check"></i>เวลาอนุมัติ : <?= date('d/m/Y H:i', strtotime($req['senior_gm_reviewed_at'])) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['senior_gm_name'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-user"></i>อนุมัติโดยคุณ : <?= htmlspecialchars(($req['senior_gm_name'] ?? '') . ' ' . ($req['senior_gm_lastname'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['dev_name'])): ?>
                                      <div class="bcrq-meta__item"><i class="fas fa-user-cog"></i>ผู้พัฒนาระบบงาน: <?= htmlspecialchars(($req['dev_name'] ?? '') . ' ' . ($req['dev_lastname'] ?? '')) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['senior_gm_reason'])): ?>
                                      <div class="bcrq-meta__item" style="grid-column: 1/-1;"><i class="fas fa-sticky-note"></i><?= htmlspecialchars($req['senior_gm_reason']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['senior_gm_final_notes'])): ?>
                                      <div class="bcrq-meta__item" style="grid-column: 1/-1;"><i class="fas fa-comment-dots"></i>หมายเหตุสำหรับ Developer: <?= htmlspecialchars($req['senior_gm_final_notes']) ?></div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>

                              <!-- Step 5: Development -->
                              <?php if (!empty($req['task_status'])): ?>
                                <div class="bcrq-step <?= in_array(($req['task_status'] ?? ''), ['completed', 'accepted']) ? 'completed' : (in_array(($req['task_status'] ?? ''), ['received', 'in_progress', 'on_hold']) ? 'current' : 'pending') ?>">
                                  <div class="bcrq-dot">5</div>
                                  <div class="bcrq-body">
                                    <div class="bcrq-head">
                                      <div class="bcrq-title">การพัฒนา</div>
                                      <div>
                                        <?php
                                        $task_statuses = [
                                          'pending' => '<span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-clock"></i> รอรับงาน</span>',
                                          'received' => '<span class="bcrq-chip bcrq-chip--success"><i class="fas fa-check"></i> รับงานแล้ว</span>',
                                          'in_progress' => '<span class="bcrq-chip bcrq-chip--muted"><i class="fas fa-cog fa-spin"></i> กำลังดำเนินการ</span>',
                                          'on_hold' => '<span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-pause"></i> พักงาน</span>',
                                          'completed' => '<span class="bcrq-chip bcrq-chip--success"><i class="fas fa-check-double"></i> เสร็จสิ้น</span>',
                                          'accepted' => '<span class="bcrq-chip bcrq-chip--success"><i class="fas fa-star"></i> ยอมรับงาน</span>'
                                        ];
                                        echo $task_statuses[$req['task_status']] ?? '';
                                        ?>
                                      </div>
                                    </div>
                                    <div class="bcrq-meta">
                                      <?php if (isset($req['progress_percentage'])): ?>
                                        <div class="bcrq-meta__item" style="grid-column: 1/-1;">
                                          <div class="progress mt-1 mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= (int)$req['progress_percentage'] ?>%" aria-valuenow="<?= (int)$req['progress_percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                          </div>
                                          <small class="bcrq-progress-text"><?= (int)$req['progress_percentage'] ?>% เสร็จสิ้น</small>
                                        </div>
                                      <?php endif; ?>
                                      <?php if (!empty($req['task_started_at'])): ?>
                                        <div class="bcrq-meta__item"><i class="fas fa-play"></i>เริ่มงาน: <?= date('d/m/Y H:i', strtotime($req['task_started_at'])) ?></div>
                                      <?php endif; ?>
                                      <?php if (!empty($req['task_completed_at'])): ?>
                                        <div class="bcrq-meta__item"><i class="fas fa-flag-checkered"></i>เสร็จงาน: <?= date('d/m/Y H:i', strtotime($req['task_completed_at'])) ?></div>
                                      <?php endif; ?>
                                      <?php if (!empty($req['developer_notes'])): ?>
                                        <div class="bcrq-meta__item" style="grid-column:1/-1;"><i class="fas fa-code"></i>หมายเหตุจาก Developer: <?= htmlspecialchars($req['developer_notes']) ?></div>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                              <?php endif; ?>

                              <!-- Step 6: User Review -->
                              <?php if (($req['task_status'] ?? '') === 'completed' && empty($req['review_status'])): ?>
                                <div class="bcrq-step current">
                                  <div class="bcrq-dot">6</div>
                                  <div class="bcrq-body">
                                    <div class="bcrq-head">
                                      <div class="bcrq-title">รีวิวและยอมรับงาน</div>
                                      <span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-clock"></i> รอการรีวิวจากคุณ</span>
                                    </div>
                                    <div class="bcrq-meta">
                                      <div class="bcrq-meta__item" style="grid-column:1/-1;">
                                        <a href="review_task.php?request_id=<?= $req['id'] ?>" class="btn btn-primary btn-sm">
                                          <i class="fas fa-star me-1"></i>รีวิวงาน
                                        </a>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              <?php elseif (!empty($req['review_status'])): ?>
                                <div class="bcrq-step completed">
                                  <div class="bcrq-dot">6</div>
                                  <div class="bcrq-body">
                                    <div class="bcrq-head">
                                      <div class="bcrq-title">รีวิวและยอมรับงาน</div>
                                      <div>
                                        <?php if (($req['review_status'] ?? '') === 'accepted'): ?>
                                          <span class="bcrq-chip bcrq-chip--success"><i class="fas fa-star"></i> ยอมรับงานแล้ว</span>
                                        <?php elseif (($req['review_status'] ?? '') === 'revision_requested'): ?>
                                          <span class="bcrq-chip bcrq-chip--warn"><i class="fas fa-redo"></i> ขอแก้ไข</span>
                                        <?php endif; ?>
                                      </div>
                                    </div>
                                    <div class="bcrq-meta">
                                      <?php if (!empty($req['user_reviewed_at'])): ?>
                                        <div class="bcrq-meta__item"><i class="fas fa-calendar-check"></i>รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($req['user_reviewed_at'])) ?></div>
                                        <?php if (isset($req['progress_percentage'])): ?>
                                          <div class="bcrq-meta__item" style="grid-column:1/-1;">
                                            <div class="bcrq-progress-container mt-1">
                                              <div class="progress-bar">
                                                <div class="bcrq-progress-fill" style="width: <?= (int)$req['progress_percentage'] ?>%"></div>
                                              </div>
                                              <small class="bcrq-progress-text"><?= (int)$req['progress_percentage'] ?>% เสร็จสิ้น</small>
                                            </div>
                                          </div>
                                        <?php endif; ?>
                                        <?php if (($req['service_category'] ?? '') === 'development' && ($req['current_step'] ?? '') !== 'developer_self_created'): ?>
                                          <div class="bcrq-meta__item" style="grid-column:1/-1;">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSubtasks(<?= (int)$req['task_id'] ?>)">
                                              <i class="fas fa-tasks me-1"></i>ดู Subtasks
                                            </button>
                                          </div>
                                        <?php endif; ?>
                                      <?php endif; ?>
                                      <?php if (!empty($req['review_comment'])): ?>
                                        <div class="bcrq-meta__item" style="grid-column:1/-1;"><i class="fas fa-comment"></i>ความเห็น: <?= htmlspecialchars($req['review_comment']) ?></div>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                              <?php endif; ?>

                            </div>
                          </div>
                        </div>
                        <!-- Timeline Button -->
                        <div class="text-end mt-3">
                          <button class="btn btn-sm btn-primary view-timeline-btn"
                            data-request-id="<?= (int)$req['id'] ?>"
                            data-subtasks='<?= json_encode($req['subtasks'] ?? []) ?>'>
                            <i class="fas fa-stream me-1"></i> ดูไทม์ไลน์
                          </button>
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

      <!-- Timeline Modal -->
      <div class="modal fade" id="timelineModal" tabindex="-1" aria-labelledby="timelineModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="timelineModalLabel">ไทม์ไลน์งาน</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
              <ul id="timelineContent" class="list-group list-group-flush"></ul>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
          </div>
        </div>
      </div>

      <footer class="footer py-4  ">
        <div class="container-fluid">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
              <div class="copyright text-center text-sm text-muted text-lg-start">
                © <script>
                  document.write(new Date().getFullYear())
                </script>,
                made with </i> by
                <a class="font-weight-bold" target="_blank">เเผนกพัฒนาระบบงาน</a>
                for BobbyCareRemake.
              </div>
            </div>

          </div>
        </div>
      </footer>
    </div>
  </main>
  <div class="fixed-plugin">
    <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
      <i class="material-icons py-2">settings</i>
    </a>
    <div class="card shadow-lg">
      <div class="card-header pb-0 pt-3">
        <div class="float-start">
          <h5 class="mt-3 mb-0">Material UI Configurator</h5>
          <p>See our dashboard options.</p>
        </div>
        <div class="float-end mt-4">
          <button class="btn btn-link text-dark p-0 fixed-plugin-close-button">
            <i class="material-icons">clear</i>
          </button>
        </div>
        <!-- End Toggle Button -->
      </div>
      <hr class="horizontal dark my-1">
      <div class="card-body pt-sm-3 pt-0">
        <!-- Sidebar Backgrounds -->
        <div>
          <h6 class="mb-0">Sidebar Colors</h6>
        </div>
        <a href="javascript:void(0)" class="switch-trigger background-color">
          <div class="badge-colors my-2 text-start">
            <span class="badge filter bg-gradient-primary active" data-color="primary" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-dark" data-color="dark" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-info" data-color="info" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-success" data-color="success" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-warning" data-color="warning" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-danger" data-color="danger" onclick="sidebarColor(this)"></span>
          </div>
        </a>
        <!-- Sidenav Type -->
        <div class="mt-3">
          <h6 class="mb-0">Sidenav Type</h6>
          <p class="text-sm">Choose between 2 different sidenav types.</p>
        </div>
        <div class="d-flex">
          <button class="btn bg-gradient-dark px-3 mb-2 active" data-class="bg-gradient-dark" onclick="sidebarType(this)">Dark</button>
          <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-transparent" onclick="sidebarType(this)">Transparent</button>
          <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-white" onclick="sidebarType(this)">White</button>
        </div>
        <p class="text-sm d-xl-none d-block mt-2">You can change the sidenav type just on desktop view.</p>
        <!-- Navbar Fixed -->
        <div class="mt-3 d-flex">
          <h6 class="mb-0">Navbar Fixed</h6>
          <div class="form-check form-switch ps-0 ms-auto my-auto">
            <input class="form-check-input mt-1 ms-auto" type="checkbox" id="navbarFixed" onclick="navbarFixed(this)">
          </div>
        </div>
        <hr class="horizontal dark my-3">
        <div class="mt-2 d-flex">
          <h6 class="mb-0">Light / Dark</h6>
          <div class="form-check form-switch ps-0 ms-auto my-auto">
            <input class="form-check-input mt-1 ms-auto" type="checkbox" id="dark-version" onclick="darkMode(this)">
          </div>
        </div>
        <hr class="horizontal dark my-sm-4">
        <a class="btn btn-outline-dark w-100" href="">View documentation</a>

      </div>
    </div>
  </div>
  <!--   Core JS Files   -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>

  <!-- ===================== Scripts ===================== -->
  <script>
    // Close other collapses & toggle the selected one
    function toggleStatus(targetId) {
      var target = document.getElementById(targetId);
      if (!target) return;
      // Close others
      document.querySelectorAll('.approval-collapse.show').forEach(function(el) {
        if (el.id !== targetId) {
          var inst = bootstrap.Collapse.getOrCreateInstance(el, {
            toggle: false
          });
          inst.hide();
        }
      });
      // Toggle current
      var instance = bootstrap.Collapse.getOrCreateInstance(target, {
        toggle: false
      });
      if (target.classList.contains('show')) instance.hide();
      else instance.show();
    }

    // Optional: hook timeline buttons (you can replace with your modal logic)
    document.querySelectorAll('.view-timeline-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-request-id');
        // TODO: open your timeline modal / navigate
        console.log('Open timeline for request', id);
      });
    });
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


  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.0.0"></script>
</body>

</html>