<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// รับค่าการค้นหาและกรอง
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// สร้าง WHERE clause สำหรับการกรอง
$where_conditions = ["sr.user_id = ?"];
$params = [$user_id];

if (!empty($search)) {
    $where_conditions[] = "(sr.title LIKE ? OR sr.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "sr.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(sr.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "sr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "sr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = implode(' AND ', $where_conditions);

// ดึงรายการคำขอของผู้ใช้พร้อมสถานะการอนุมัติ
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        -- Division Manager Status
        dma.status as div_mgr_status,
        dma.reason as div_mgr_reason,
        dma.reviewed_at as div_mgr_reviewed_at,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Status  
        aa.status as assignor_status,
        aa.reason as assignor_reason,
        aa.reviewed_at as assignor_reviewed_at,
        assignor.name as assignor_name,
        dev.name as assigned_dev_name,
        dev.lastname as assigned_dev_lastname,
        
        -- GM Status
        gma.status as gm_status,
        gma.reason as gm_reason,
        gma.reviewed_at as gm_reviewed_at,
        gm.name as gm_name,
        
        -- Senior GM Status
        sgma.status as senior_gm_status,
        sgma.reason as senior_gm_reason,
        sgma.reviewed_at as senior_gm_reviewed_at,
        senior_gm.name as senior_gm_name,
        
        -- Task Status
        t.task_status,
        t.progress_percentage,
        t.developer_notes,
        t.started_at as task_started_at,
        t.completed_at as task_completed_at,
        
        -- User Review Status
        ur.status as review_status,
        ur.rating,
        ur.review_comment,
        ur.reviewed_at as user_reviewed_at
        
    FROM service_requests sr
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
    WHERE $where_clause
    ORDER BY sr.created_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge pending">รอดำเนินการ</span>',
        'div_mgr_review' => '<span class="badge in-review">รอผู้จัดการฝ่ายพิจารณา</span>',
        'assignor_review' => '<span class="badge in-review">รอผู้จัดการแผนกพิจารณา</span>',
        'gm_review' => '<span class="badge in-review">รอผู้จัดการทั่วไปพิจารณา</span>',
        'senior_gm_review' => '<span class="badge in-review">รอผู้จัดการอาวุโสพิจารณา</span>',
        'approved' => '<span class="badge approved">อนุมัติแล้ว</span>',
        'rejected' => '<span class="badge rejected">ไม่อนุมัติ</span>',
        'developer_assigned' => '<span class="badge assigned">มอบหมายงานแล้ว</span>',
        'in_progress' => '<span class="badge in-progress">กำลังดำเนินการ</span>',
        'completed' => '<span class="badge completed">เสร็จสิ้น</span>'
    ];
    return $badges[$status] ?? '<span class="badge unknown">ไม่ทราบสถานะ</span>';
}

function getApprovalStatus($status) {
    if ($status === 'approved') return '<i class="fas fa-check-circle text-success"></i> อนุมัติ';
    if ($status === 'rejected') return '<i class="fas fa-times-circle text-danger"></i> ไม่อนุมัติ';
    if ($status === 'pending') return '<i class="fas fa-clock text-warning"></i> รอพิจารณา';
    return '<i class="fas fa-minus text-muted"></i> ยังไม่ถึงขั้นตอน';
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
      google: { families: ["Public Sans:300,400,500,600,700"] },
      custom: {
        families: [
          "Font Awesome 5 Solid",
          "Font Awesome 5 Regular",
          "Font Awesome 5 Brands",
          "simple-line-icons",
        ],
        urls: ["../assets/css/fonts.min.css"],
      },
      active: function () {
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
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
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

        /* Filter Section */
        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-control {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 153, 225, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-1px);
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #4299e1;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #718096;
            font-weight: 500;
        }

        .requests-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* Grid Layout for Cards */
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }

        .request-card {
            background: white;
            border-radius: 12px;
             padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #4299e1;
            transition: all 0.3s ease;
            height: fit-content;
        }

        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            gap: 10px;
        }

        .request-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
            line-height: 1.3;
        }

        .request-date {
            color: #718096;
            font-size: 0.8rem;
        }

        .badge {
           padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .badge.pending {
            background: #fed7d7;
            color: #c53030;
        }

        .badge.in-review {
            background: #fef5e7;
            color: #d69e2e;
        }

        .badge.approved {
            background: #c6f6d5;
            color: #2f855a;
        }

        .badge.rejected {
            background: #fed7d7;
            color: #c53030;
        }

        .badge.assigned {
            background: #bee3f8;
            color: #2b6cb0;
        }

        .badge.in-progress {
            background: #d6bcfa;
            color: #6b46c1;
        }

        .badge.completed {
            background: #c6f6d5;
            color: #2f855a;
        }

        .progress-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            overflow-x: auto;
            gap: 10px;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 60px;
            text-align: center;
            position: relative;
            flex-shrink: 0;
        }

        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -15px;
            width: 20px;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }

        .timeline-step.completed:not(:last-child)::after {
            background: #48bb78;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-bottom: 5px;
            position: relative;
            z-index: 2;
            background: #e2e8f0;
            color: #718096;
        }

        .timeline-step.completed {
        }

        .timeline-step.completed .step-icon {
            background: #48bb78;
            color: white;
        }

        .timeline-step.current {
        }

        .timeline-step.current .step-icon {
            background: #d69e2e;
            color: white;
            animation: pulse 2s infinite;
        }

        .timeline-step.rejected {
        }

        .timeline-step.rejected .step-icon {
            background: #f56565;
            color: white;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .step-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.7rem;
            margin-bottom: 3px;
            text-align: center;
            line-height: 1.2;
        }

        .step-status {
            font-size: 0.9rem;
            color: #718096;
        }

        .step-date {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 2px;
        }

        .step-reviewer {
             font-size: 0.8rem;
            color: #4a5568;
            margin-top: 5px;
        }

        .task-progress {
          background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .task-progress h4 {
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

       .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            background: linear-gradient(90deg, #48bb78, #38a169);
            height: 100%;
            transition: width 0.3s ease;
        }

        .compact-attachments {
            max-height: 100px;
            overflow-y: auto;
        }

        .text-success { color: #48bb78; }
        .text-danger { color: #f56565; }
        .text-warning { color: #d69e2e; }
        .text-muted { color: #a0aec0; }

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
    .page-title {
        font-size: 2rem;
        text-align: center;
    }
    .container {
        padding: 1rem;
    }



            .header h1 {
                font-size: 2rem;
            }

            .filter-form {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .requests-grid {
                grid-template-columns: 1fr;
            }

            .progress-timeline {
                padding: 10px;
                gap: 5px;
            }

            .timeline-step {
                min-width: 50px;
            }

            .step-icon {
                width: 25px;
                height: 25px;
                font-size: 0.7rem;
            }

            .step-title {
                font-size: 0.6rem;
            }

            .timeline-step:not(:last-child)::after {
                right: -10px;
                width: 15px;
            }
        }
        
    </style>
</head>

<body>

  <div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar" data-background-color="dark">
      <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
          <a href="../dashboard2.php" class="logo">
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
             <li class="nav-item ">
                            <a href="../dashboard2.php">
                                <i class="fas fa-home"></i>
                                <p>หน้าหลัก</p>
                            </a>
                        </li>
           <li class="nav-section">
              <span class="sidebar-mini-icon">
                <i class="fa fa-ellipsis-h"></i>
              </span>
              <h4 class="text-section">Components</h4>
            </li>

            <li class="nav-item ">
              <a href="create2.php">
                <i class="fas fa-plus-circle"></i>
                <p>สร้างคำขอใหม่</p>
                <span class="badge badge-success"></span>
              </a>
            </li>

            <li class="nav-item ">
              <a href="index2.php">
                <i class="fas fa-list"></i>
                <p>รายการคำขอ</p>
                <span class="badge badge-success"></span>
              </a>
            </li>

            <li class="nav-item active">
              <a href="track_status2.php">
                <i class="fas fa-spinner"></i>
                <p>ติดตามสถานะ</p>
                <span class="badge badge-success"></span>
              </a>
            </li>
<!-- 
            <li class="nav-item">
              <a href="../profile.php">
                <i class="fas fa-user"></i>
                <p>โปรไฟล์</p>
                <span class="badge badge-success"></span>
              </a>
            </li> -->

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

                <!-- <ul class="dropdown-menu dropdown-user animated fadeIn">
                  <div class="dropdown-user-scroll scrollbar-outer">
                    <li>
                      <div class="user-box">
                        <div class="avatar-lg">
                          <img src="../assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" />
                        </div>
                        <div class="u-text">
                          <h4>Hizrian</h4>
                          <p class="text-muted">hello@example.com</p>
                          <a href="profile.html" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                        </div>
                      </div>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="#">My Profile</a>
                    
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="#">Logout</a>
                    </li>
                  </div>
                </ul> -->
              </li>


            </ul>
          </div>
        </nav>
        <!-- End Navbar -->
      </div>



      
      <div class="container">
        



   <div class="page-inner">
          <!-- ส่วนกรองข้อมูล -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> ค้นหา</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="ค้นหาชื่อหรือรายละเอียด..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group">
                    <label for="status"><i class="fas fa-filter"></i> สถานะ</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">ทุกสถานะ</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="div_mgr_review" <?= $status_filter === 'div_mgr_review' ? 'selected' : '' ?>>ผู้จัดการฝ่ายพิจารณา</option>
                        <option value="assignor_review" <?= $status_filter === 'assignor_review' ? 'selected' : '' ?>>ผู้จัดการแผนกพิจารณา</option>
                        <option value="gm_review" <?= $status_filter === 'gm_review' ? 'selected' : '' ?>>ผู้จัดการทั่วไปพิจารณา</option>
                        <option value="senior_gm_review" <?= $status_filter === 'senior_gm_review' ? 'selected' : '' ?>>ผู้จัดการอาวุโสพิจารณา</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
                        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> ช่วงเวลา</label>
                    <select id="date" name="date" class="form-control">
                        <option value="">ทุกช่วงเวลา</option>
                        <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>วันนี้</option>
                        <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>7 วันที่ผ่านมา</option>
                        <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>30 วันที่ผ่านมา</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                </div>
                
                <div class="form-group">
                    <a href="track_status.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> ล้างตัวกรอง
                    </a>
                </div>
            </form>
        </div>



        <!-- สถิติสรุป -->
        <?php
        $total_requests = count($requests);
        $pending_requests = count(array_filter($requests, fn($r) => in_array($r['status'], ['pending', 'div_mgr_review', 'assignor_review', 'gm_review', 'senior_gm_review'])));
        $approved_requests = count(array_filter($requests, fn($r) => $r['status'] === 'approved' || $r['senior_gm_status'] === 'approved'));
        $completed_requests = count(array_filter($requests, fn($r) => $r['status'] === 'completed' || $r['task_status'] === 'accepted'));
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_requests ?></div>
                <div class="stat-label"><i class="fas fa-list"></i> คำขอทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $pending_requests ?></div>
                <div class="stat-label"><i class="fas fa-clock"></i> รอดำเนินการ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $approved_requests ?></div>
                <div class="stat-label"><i class="fas fa-check"></i> อนุมัติแล้ว</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $completed_requests ?></div>
                <div class="stat-label"><i class="fas fa-star"></i> เสร็จสิ้น</div>
            </div>
        </div>

        <div class="requests-container">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>ยังไม่มีคำขอบริการ</h3>
                    <p>เริ่มต้นด้วยการสร้างคำขอบริการใหม่</p>
                </div>
            <?php else: ?>
                <div class="requests-grid">
                    <?php foreach ($requests as $req): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div>
                                    <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                    <div class="request-date">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                    </div>
                                </div>
                                <div>
                                    <?= getStatusBadge($req['status']) ?>
                                </div>
                            </div>

                            <div class="progress-timeline">
                                <!-- ขั้นตอนที่ 1: ผู้จัดการฝ่าย -->
                                <div class="timeline-step <?= 
                                    $req['div_mgr_status'] === 'approved' ? 'completed' : 
                                    ($req['div_mgr_status'] === 'rejected' ? 'rejected' : 
                                    ($req['div_mgr_status'] === 'pending' ? 'current' : '')) 
                                ?>">
                                    <div class="step-icon">
                                        <?php if ($req['div_mgr_status'] === 'approved'): ?>
                                            <i class="fas fa-check"></i>
                                        <?php elseif ($req['div_mgr_status'] === 'rejected'): ?>
                                            <i class="fas fa-times"></i>
                                        <?php elseif ($req['div_mgr_status'] === 'pending'): ?>
                                            <i class="fas fa-clock"></i>
                                        <?php else: ?>
                                            1
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-title">ผู้จัดการฝ่าย</div>
                                     <?php if ($req['div_mgr_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['div_mgr_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['div_mgr_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['div_mgr_name']) ?></div>
                                <?php endif; ?>
                                    
                                </div>

                                <!-- ขั้นตอนที่ 2: ผู้จัดการแผนก -->
                                <div class="timeline-step <?= 
                                    $req['assignor_status'] === 'approved' ? 'completed' : 
                                    ($req['assignor_status'] === 'rejected' ? 'rejected' : 
                                    ($req['assignor_status'] === 'pending' && $req['div_mgr_status'] === 'approved' ? 'current' : '')) 
                                ?>">
                                    <div class="step-icon">
                                        <?php if ($req['assignor_status'] === 'approved'): ?>
                                            <i class="fas fa-check"></i>
                                        <?php elseif ($req['assignor_status'] === 'rejected'): ?>
                                            <i class="fas fa-times"></i>
                                        <?php elseif ($req['assignor_status'] === 'pending' && $req['div_mgr_status'] === 'approved'): ?>
                                            <i class="fas fa-clock"></i>
                                        <?php else: ?>
                                            2
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-title">ผู้จัดการแผนก</div>
                                    <?php if ($req['assignor_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['assignor_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['assignor_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['assignor_name']) ?></div>
                                <?php endif; ?>
                                <?php if ($req['assigned_dev_name']): ?>
                                    <div class="step-reviewer">
                                        <i class="fas fa-user-cog"></i> 
                                        ผู้พัฒนาระบบงาน: <?= htmlspecialchars($req['assigned_dev_name'] . ' ' . $req['assigned_dev_lastname']) ?>
                                    </div>
                                <?php endif; ?>
                                </div>

                                <!-- ขั้นตอนที่ 3: ผู้จัดการทั่วไป -->
                                <div class="timeline-step <?= 
                                    $req['gm_status'] === 'approved' ? 'completed' : 
                                    ($req['gm_status'] === 'rejected' ? 'rejected' : 
                                    ($req['gm_status'] === 'pending' && $req['assignor_status'] === 'approved' ? 'current' : '')) 
                                ?>">
                                    <div class="step-icon">
                                        <?php if ($req['gm_status'] === 'approved'): ?>
                                            <i class="fas fa-check"></i>
                                        <?php elseif ($req['gm_status'] === 'rejected'): ?>
                                            <i class="fas fa-times"></i>
                                        <?php elseif ($req['gm_status'] === 'pending' && $req['assignor_status'] === 'approved'): ?>
                                            <i class="fas fa-clock"></i>
                                        <?php else: ?>
                                            3
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-title">ผู้จัดการทั่วไป</div>
                                   <?php if ($req['gm_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['gm_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['gm_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['gm_name']) ?></div>
                                <?php endif; ?>
                                  <?php if ($req['assigned_dev_name']): ?>
                                    <div class="step-reviewer">
                                        <i class="fas fa-user-cog"></i> 
                                        ผู้พัฒนาระบบงาน: <?= htmlspecialchars($req['assigned_dev_name'] . ' ' . $req['assigned_dev_lastname']) ?>
                                    </div>
                                <?php endif; ?>
                                </div>

                                <!-- ขั้นตอนที่ 4: ผู้จัดการอาวุโส -->
                                <div class="timeline-step <?= 
                                    $req['senior_gm_status'] === 'approved' ? 'completed' : 
                                    ($req['senior_gm_status'] === 'rejected' ? 'rejected' : 
                                    ($req['senior_gm_status'] === 'pending' && $req['gm_status'] === 'approved' ? 'current' : '')) 
                                ?>">
                                    <div class="step-icon">
                                        <?php if ($req['senior_gm_status'] === 'approved'): ?>
                                            <i class="fas fa-check"></i>
                                        <?php elseif ($req['senior_gm_status'] === 'rejected'): ?>
                                            <i class="fas fa-times"></i>
                                        <?php elseif ($req['senior_gm_status'] === 'pending' && $req['gm_status'] === 'approved'): ?>
                                            <i class="fas fa-clock"></i>
                                        <?php else: ?>
                                            4
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-title">ผู้จัดการอาวุโส</div>
                                     <?php if ($req['senior_gm_reviewed_at']): ?>
                                    <div class="step-date"><?= date('d/m/Y H:i', strtotime($req['senior_gm_reviewed_at'])) ?></div>
                                <?php endif; ?>
                                <?php if ($req['senior_gm_name']): ?>
                                    <div class="step-reviewer">โดย: <?= htmlspecialchars($req['senior_gm_name']) ?></div>
                                <?php endif; ?>
                                  <?php if ($req['assigned_dev_name']): ?>
                                    <div class="step-reviewer">
                                        <i class="fas fa-user-cog"></i> 
                                        ผู้พัฒนาระบบงาน: <?= htmlspecialchars($req['assigned_dev_name'] . ' ' . $req['assigned_dev_lastname']) ?>
                                    </div>
                                <?php endif; ?>
                                </div>

                                <!-- ขั้นตอนที่ 5: การพัฒนา -->
                                <?php if ($req['task_status']): ?>
                                <div class="timeline-step <?= 
                                    in_array($req['task_status'], ['completed', 'accepted']) ? 'completed' : 
                                    (in_array($req['task_status'], ['received', 'in_progress', 'on_hold']) ? 'current' : '') 
                                ?>">
                                    <div class="step-icon">
                                        <?php if (in_array($req['task_status'], ['completed', 'accepted'])): ?>
                                            <i class="fas fa-check"></i>
                                        <?php elseif (in_array($req['task_status'], ['received', 'in_progress', 'on_hold'])): ?>
                                            <i class="fas fa-cog"></i>
                                        <?php else: ?>
                                            5
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-title">พัฒนา</div>
                                    <?php if ($req['task_started_at']): ?>
                                        <div class="step-date"><?= date('d/m', strtotime($req['task_started_at'])) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- ขั้นตอนที่ 6: การรีวิวของผู้ใช้ -->
                                <?php if ($req['task_status'] === 'completed'): ?>
                                <div class="timeline-step current">
                                    <div class="step-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="step-title">รีวิว</div>
                                </div>
                                <?php elseif ($req['review_status']): ?>
                                <div class="timeline-step completed">
                                    <div class="step-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="step-title">รีวิว</div>
                                    <?php if ($req['user_reviewed_at']): ?>
                                        <div class="step-date"><?= date('d/m', strtotime($req['user_reviewed_at'])) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- ข้อมูลเพิ่มเติม -->
                            <?php if ($req['assigned_dev_name']): ?>
                            <div style="background: #f0f8ff; padding: 8px; border-radius: 6px; margin: 10px 0; font-size: 0.8rem;">
                                <i class="fas fa-user-cog text-primary"></i> 
                                <strong>ผู้พัฒนา:</strong> <?= htmlspecialchars($req['assigned_dev_name'] . ' ' . $req['assigned_dev_lastname']) ?>
                            </div>
                            <?php endif; ?>

                            <!-- แสดงความคืบหน้าของงาน -->
                            <?php if ($req['task_status'] && $req['progress_percentage'] !== null): ?>
                            <div class="task-progress">
                                <h4><i class="fas fa-tasks"></i> ความคืบหน้า <?= $req['progress_percentage'] ?>%</h4>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $req['progress_percentage'] ?>%"></div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- ปุ่มรีวิวงาน -->
                            <?php if ($req['task_status'] === 'completed'): ?>
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="review_task.php?request_id=<?= $req['id'] ?>" 
                                   class="btn btn-primary" style="background: #4299e1; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-star"></i> รีวิวงาน
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- ไฟล์แนบ -->
                            <div class="compact-attachments" style="margin-top: 10px;">
                                <?php
                                require_once __DIR__ . '/../includes/attachment_display.php';
                                displayAttachments($req['id']);
                                ?>
                            </div>

                            <!-- แสดงเหตุผลการไม่อนุมัติ (ถ้ามี) -->
                            <?php 
                            $rejections = [];
                            if ($req['div_mgr_status'] === 'rejected' && $req['div_mgr_reason']) {
                                $rejections[] = "ผู้จัดการฝ่าย: " . $req['div_mgr_reason'];
                            }
                            if ($req['assignor_status'] === 'rejected' && $req['assignor_reason']) {
                                $rejections[] = "ผู้จัดการแผนก: " . $req['assignor_reason'];
                            }
                            if ($req['gm_status'] === 'rejected' && $req['gm_reason']) {
                                $rejections[] = "ผู้จัดการทั่วไป: " . $req['gm_reason'];
                            }
                            if ($req['senior_gm_status'] === 'rejected' && $req['senior_gm_reason']) {
                                $rejections[] = "ผู้จัดการอาวุโส: " . $req['senior_gm_reason'];
                            }
                            ?>
                            
                            <?php if (!empty($rejections)): ?>
                            <div style="background: #fed7d7; border-radius: 8px; padding: 10px; margin-top: 10px;">
                                <h4 style="color: #c53030; margin-bottom: 5px; font-size: 0.9rem;">
                                    <i class="fas fa-exclamation-triangle"></i> เหตุผลการไม่อนุมัติ
                                </h4>
                                <?php foreach ($rejections as $rejection): ?>
                                    <p style="color: #c53030; margin-bottom: 3px; font-size: 0.8rem;">• <?= htmlspecialchars($rejection) ?></p>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                </div>
            <?php endif; ?>
        </div>
    </div>


      
        </div>
      </div>

      <!-- <footer class="footer">
        <div class="container-fluid d-flex justify-content-between">
          <nav class="pull-left">

          </nav>
          <div class="copyright">
            © 2025, made with by เเผนกพัฒนาระบบงาน for BobbyCareRemake.
            <i class="fa fa-heart heart text-danger"></i>

          </div>
          <div>

          </div>
        </div>
      </footer> -->
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
        // แสดง/ซ่อนฟิลด์ตามประเภทบริการ
        function toggleServiceFields() {
            const serviceSelect = document.getElementById('service_id');
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const category = selectedOption.dataset.category;
            const serviceName = selectedOption.text;
            
            // ซ่อนฟิลด์ทั้งหมดก่อน
            document.querySelectorAll('.service-fields').forEach(field => {
                field.classList.remove('show');
            });
            
            // แสดงฟิลด์ตามประเภท
            if (category === 'development') {
                if (serviceName.includes('โปรแกรมใหม่')) {
                    document.getElementById('new-program-fields').classList.add('show');
                } else if (serviceName.includes('แก้ปัญหา')) {
                    document.getElementById('fix-problem-fields').classList.add('show');
                } else if (serviceName.includes('เปลี่ยนข้อมูล')) {
                    document.getElementById('change-data-fields').classList.add('show');
                } else if (serviceName.includes('เพิ่มฟังก์ชั่น')) {
                    document.getElementById('add-function-fields').classList.add('show');
                } else if (serviceName.includes('ตกแต่ง')) {
                    document.getElementById('decoration-fields').classList.add('show');
                }
            }
        }

        // เรียกใช้เมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            toggleServiceFields();
            
            // เมื่อเปลี่ยนประเภทบริการ
            document.getElementById('service_id').addEventListener('change', toggleServiceFields);
        });

        // จัดการการอัปโหลดไฟล์
        const fileInput = document.getElementById('attachments');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const selectedFilesDiv = document.getElementById('selectedFiles');

        // Drag and drop
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            fileInput.files = files;
            displaySelectedFiles();
        });

        // คลิกเพื่อเลือกไฟล์
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // เมื่อเลือกไฟล์
        fileInput.addEventListener('change', displaySelectedFiles);

        function displaySelectedFiles() {
            const files = fileInput.files;
            selectedFilesDiv.innerHTML = '';
            
            if (files.length > 0) {
                selectedFilesDiv.innerHTML = '<h6 class="fw-bold mb-3">ไฟล์ที่เลือก:</h6>';
                
                Array.from(files).forEach((file, index) => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'file-item';
                    fileDiv.innerHTML = `
                        <div class="file-info">
                            <div class="file-name">
                                <i class="fas fa-file me-2"></i>
                                ${file.name}
                            </div>
                            <div class="file-size">
                                ขนาด: ${(file.size / 1024).toFixed(2)} KB
                            </div>
                        </div>
                        <button type="button" class="btn btn-delete-file" onclick="removeFile(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    selectedFilesDiv.appendChild(fileDiv);
                });
            }
        }

        function removeFile(index) {
            const dt = new DataTransfer();
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            fileInput.files = dt.files;
            displaySelectedFiles();
        }

        // Validation ก่อนส่งฟอร์ม
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const serviceId = document.getElementById('service_id').value;
            const divMgrId = document.getElementById('assigned_div_mgr_id').value;
            
            if (!title) {
                e.preventDefault();
                alert('กรุณากรอกหัวข้อคำขอ');
                return;
            }
            
            if (!serviceId) {
                e.preventDefault();
                alert('กรุณาเลือกประเภทบริการ');
                return;
            }
            
            if (!divMgrId) {
                e.preventDefault();
                alert('กรุณาเลือกผู้จัดการฝ่าย');
                return;
            }
            
            // ยืนยันการแก้ไข
            if (!confirm('ยืนยันการแก้ไขคำขอนี้?')) {
                e.preventDefault();
            }
        });
    </script>

    
    <style>
        /* overlay ครอบทั้งหน้าตอนเมนูเปิด */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .25);
            z-index: 998;
            /* ให้อยู่ใต้ sidebar นิดเดียว */
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }
    </style>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        (function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            // ปุ่มที่ใช้เปิด/ปิดเมนู (ตามโค้ดคุณมีทั้งสองคลาส)
            const toggleBtns = document.querySelectorAll('.toggle-sidebar, .sidenav-toggler');

            // คลาสที่มักถูกเติมเมื่อ "เมนูเปิด" (เติมเพิ่มได้ถ้าโปรเจ็กต์คุณใช้ชื่ออื่น)
            const OPEN_CLASSES = ['nav_open', 'toggled', 'show', 'active'];

            // helper: เช็คว่าเมนูถือว่า "เปิด" อยู่ไหม
            function isSidebarOpen() {
                if (!sidebar) return false;
                // ถ้าบอดี้หรือไซด์บาร์มีคลาสในรายการนี้ตัวใดตัวหนึ่ง ให้ถือว่าเปิด
                const openOnBody = OPEN_CLASSES.some(c => document.body.classList.contains(c) || document.documentElement.classList.contains(c));
                const openOnSidebar = OPEN_CLASSES.some(c => sidebar.classList.contains(c));
                return openOnBody || openOnSidebar;
            }

            // helper: สั่งปิดเมนูแบบไม่ผูกกับไส้ในธีมมากนัก
            function closeSidebar() {
                // เอาคลาสเปิดออกจาก body/html และ sidebar (กันเหนียว)
                OPEN_CLASSES.forEach(c => {
                    document.body.classList.remove(c);
                    document.documentElement.classList.remove(c);
                    sidebar && sidebar.classList.remove(c);
                });
                overlay?.classList.remove('show');
            }

            // เมื่อกดปุ่ม toggle: ถ้าเปิดแล้วให้โชว์ overlay / ถ้าปิดก็ซ่อน
            toggleBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // หน่วงนิดให้ธีมสลับคลาสเสร็จก่อน
                    setTimeout(() => {
                        if (isSidebarOpen()) {
                            overlay?.classList.add('show');
                        } else {
                            overlay?.classList.remove('show');
                        }
                    }, 10);
                });
            });

            // คลิกที่ overlay = ปิดเมนู
            overlay?.addEventListener('click', () => {
                closeSidebar();
            });

            // คลิกที่ใดก็ได้บนหน้า: ถ้านอก sidebar + นอกปุ่ม toggle และขณะ mobile → ปิดเมนู
            document.addEventListener('click', (e) => {
                // จำกัดเฉพาะจอเล็ก (คุณจะปรับ breakpoint เองก็ได้)
                if (window.innerWidth > 991) return;

                const clickedInsideSidebar = e.target.closest('.sidebar');
                const clickedToggle = e.target.closest('.toggle-sidebar, .sidenav-toggler');

                if (!clickedInsideSidebar && !clickedToggle && isSidebarOpen()) {
                    closeSidebar();
                }
            });

            // ปิดเมนูอัตโนมัติเมื่อ resize จากจอเล็กไปจอใหญ่ (กันค้าง)
            window.addEventListener('resize', () => {
                if (window.innerWidth > 991) closeSidebar();
            });
        })();
    </script>
</body>

</html>