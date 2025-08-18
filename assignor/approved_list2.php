<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

$assignor_id = $_SESSION['user_id'];


$picture_url = $_SESSION['picture_url'] ?? null;


// รับพารามิเตอร์การฟิลเตอร์
$filter_type = $_GET['filter'] ?? 'all';
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_year = $_GET['year'] ?? date('Y');

// สร้าง WHERE clause ตามการฟิลเตอร์
$where_clause = "WHERE aa.assignor_user_id = ? AND aa.status IN ('approved', 'rejected')";
$params = [$assignor_id];

switch ($filter_type) {
    case 'day':
        $where_clause .= " AND DATE(aa.reviewed_at) = ?";
        $params[] = $filter_date;
        break;
    case 'month':
        $where_clause .= " AND DATE_FORMAT(aa.reviewed_at, '%Y-%m') = ?";
        $params[] = $filter_month;
        break;
    case 'year':
        $where_clause .= " AND YEAR(aa.reviewed_at) = ?";
        $params[] = $filter_year;
        break;
    case 'all':
    default:
        // ไม่มีเงื่อนไขเพิ่ม
        break;
}

// ดึงรายการที่อนุมัติแล้ว
$stmt = $conn->prepare("
    SELECT 
        aa.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        sr.priority,
        sr.current_step,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        requester.department as requester_department,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        s.name as service_name,
        s.category as service_category,
        t.task_status,
        t.progress_percentage,
        t.started_at as task_started_at,
        t.completed_at as task_completed_at,
        ur.rating,
        ur.review_comment,
        ur.status as review_status
    FROM assignor_approvals aa
    JOIN service_requests sr ON aa.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN tasks t ON sr.id = t.service_request_id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    $where_clause
    ORDER BY aa.reviewed_at DESC
");
$stmt->execute($params);
$approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณสถิติ
$total_approvals = count($approvals);
$approved_count = 0;
$rejected_count = 0;
$completed_count = 0;
$total_rating = 0;
$rating_count = 0;

foreach ($approvals as $approval) {
    if ($approval['status'] === 'approved') {
        $approved_count++;
    } elseif ($approval['status'] === 'rejected') {
        $rejected_count++;
    }
    
    if ($approval['task_status'] === 'accepted') {
        $completed_count++;
    }
    
    if ($approval['rating']) {
        $total_rating += $approval['rating'];
        $rating_count++;
    }
}

$approval_rate = $total_approvals > 0 ? round(($approved_count / $total_approvals) * 100, 1) : 0;
$completion_rate = $approved_count > 0 ? round(($completed_count / $approved_count) * 100, 1) : 0;
$average_rating = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>BobbyCareDev</title>
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="icon" href="/BobbyCareRemake/img/logo/bobby-icon.png" type="image/x-icon" />

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

    <style>
    

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .page-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.total .stat-number { color: #667eea; }
        .stat-card.approval .stat-number { color: #10b981; }
        .stat-card.completion .stat-number { color: #8b5cf6; }
        .stat-card.rating .stat-number { color: #f59e0b; }

        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: white;
            color: #6b7280;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active:hover {
            color: white;
        }

        .date-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .approval-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #10b981;
        }

        .approval-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .approval-card.rejected {
            border-left-color: #ef4444;
        }

        .approval-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .approval-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .approval-meta {
            color: #718096;
            font-size: 0.9rem;
        }

        .service-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: inline-block;
        }

        .service-development {
            background: #c6f6d5;
            color: #2f855a;
        }

        .service-service {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-approved {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-rejected {
            background: #fed7d7;
            color: #c53030;
        }

        .status-pending {
            background: #fef5e7;
            color: #d69e2e;
        }

        .task-progress {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }

        .progress-bar-container {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, #10b981, #059669);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
            transition: width 0.5s ease;
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: #d1d5db;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-buttons {
                justify-content: center;
            }

            .date-inputs {
                grid-template-columns: 1fr;
            }

            .approval-header {
                flex-direction: column;
                align-items: flex-start;
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
          <a href="index2.php" class="logo">
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
             <li class="nav-item active">
              <a href="index2.php">
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
            <li class="nav-item">
              <a href="view_requests2.php">
                <i class="fas fa-search"></i> <!-- ตรวจสอบคำขอ -->
                <p>ตรวจสอบคำขอ</p>
                <span class="badge badge-success"></span>
              </a>
            </li>

            <li class="nav-item active ">
              <a href="approved_list2.php">
                <i class="fas fa-check-circle"></i> <!-- รายการที่อนุมัติ -->
                <p>รายการที่อนุมัติ</p>
                <span class="badge badge-success"></span>
              </a>
            </li>

            <li class="nav-item ">
              <a href="view_completed_tasks2.php">
                <i class="fas fa-comments"></i> <!-- UserReviews -->
                <p>UserReviews</p>
                <span class="badge badge-success"></span>
              </a>
            </li>

            <li class="nav-item">
              <a href="assignor_dashboard2.php">
                <i class="fas fa-tachometer-alt"></i> <!-- Dashboard_DEV -->
                <p>Dashboard_DEV</p>
                <span class="badge badge-success"></span>
              </a>
            </li>

            <li class="nav-item">
              <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <!-- Logout -->
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
            <a href="../index.html" class="logo">
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
                    <span class="op-7">ผู้จัดการเเผนก:</span>
                    <span class="fw-bold"><?= htmlspecialchars($_SESSION['name']) ?></span>
                  </span>
                </a>
                <ul class="dropdown-menu dropdown-user animated fadeIn">
                  <div class="dropdown-user-scroll scrollbar-outer">
                    <li>
                      <div class="user-box">
                        <div class="avatar-lg">
                          <img src="<?= htmlspecialchars($picture_url) ?>" alt="image profile" class="avatar-img rounded" />
                        </div>
                        <div class="u-text">
                          <h4><?= htmlspecialchars($_SESSION['name']) ?> </h4>

                          <!-- <p class="text-muted"><?= htmlspecialchars($email) ?></p> -->
                          <a href="" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                        </div>
                      </div>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="#">My Profile</a>

                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="../logout.php">Logout</a>
                    </li>
                  </div>
                </ul>
              </li>


            </ul>
          </div>
        </nav>
        <!-- End Navbar -->
      </div>




      <div class="container">
<br>

  <div class="page-inner">
      

        <!-- สถิติ -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?= $total_approvals ?></div>
                <div class="stat-label">ทั้งหมด</div>
            </div>
            <div class="stat-card approval">
                <div class="stat-number"><?= $approval_rate ?>%</div>
                <div class="stat-label">อัตราการอนุมัติ</div>
            </div>
            <div class="stat-card completion">
                <div class="stat-number"><?= $completion_rate ?>%</div>
                <div class="stat-label">อัตราการเสร็จ</div>
            </div>
            <div class="stat-card rating">
                <div class="stat-number"><?= $average_rating ?></div>
                <div class="stat-label">คะแนนเฉลี่ย</div>
            </div>
        </div>

              <!-- ส่วนฟิลเตอร์ -->
      <div class="filter-section text-center p-4 bg-white shadow-sm rounded-3 mx-auto" style="max-width: 800px;">
    <h5 class="fw-bold mb-4">
        <i class="fas fa-filter me-2 text-primary"></i>ฟิลเตอร์ข้อมูล
    </h5>
             <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                <button class="filter-btn <?= $filter_type === 'all' ? 'active' : '' ?>" onclick="setFilter('all')">
                    <i class="fas fa-list me-1"></i>ทั้งหมด
                </button>
                <button class="filter-btn <?= $filter_type === 'day' ? 'active' : '' ?>" onclick="setFilter('day')">
                    <i class="fas fa-calendar-day me-1"></i>รายวัน
                </button>
                <button class="filter-btn <?= $filter_type === 'month' ? 'active' : '' ?>" onclick="setFilter('month')">
                    <i class="fas fa-calendar-alt me-1"></i>รายเดือน
                </button>
                <button class="filter-btn <?= $filter_type === 'year' ? 'active' : '' ?>" onclick="setFilter('year')">
                    <i class="fas fa-calendar me-1"></i>รายปี
                </button>
            </div>

            <div class="date-inputs">
                <div id="dayFilter" style="display: <?= $filter_type === 'day' ? 'block' : 'none' ?>">
                    <label class="form-label">เลือกวันที่:</label>
                    <input type="date" class="form-control" id="filterDate" value="<?= $filter_date ?>" onchange="applyFilter()">
                </div>
                <div id="monthFilter" style="display: <?= $filter_type === 'month' ? 'block' : 'none' ?>">
                    <label class="form-label">เลือกเดือน:</label>
                    <input type="month" class="form-control" id="filterMonth" value="<?= $filter_month ?>" onchange="applyFilter()">
                </div>
                <div id="yearFilter" style="display: <?= $filter_type === 'year' ? 'block' : 'none' ?>">
                    <label class="form-label">เลือกปี:</label>
                    <select class="form-control" id="filterYear" onchange="applyFilter()">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y + 543 ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- รายการอนุมัติ -->
        <div class="glass-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="mb-0 fw-bold">
                    <i class="fas fa-list-check text-success me-3"></i>
                    รายการที่พิจารณาแล้ว
                    <?php if ($filter_type !== 'all'): ?>
                        <small class="text-muted">
                            (<?php
                            switch ($filter_type) {
                                case 'day': echo 'วันที่ ' . date('d/m/Y', strtotime($filter_date)); break;
                                case 'month': echo 'เดือน ' . date('m/Y', strtotime($filter_month . '-01')); break;
                                case 'year': echo 'ปี ' . ($filter_year + 543); break;
                            }
                            ?>)
                        </small>
                    <?php endif; ?>
                </h2>
                <div class="text-muted">
                    <i class="fas fa-clipboard-list me-1"></i>
                    <?= $total_approvals ?> รายการ
                </div>
            </div>

            <?php if (empty($approvals)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3 class="fw-bold mb-3">ไม่มีรายการที่พิจารณาแล้ว</h3>
                    <p class="fs-5">
                        <?php if ($filter_type === 'all'): ?>
                            ยังไม่มีการพิจารณาคำขอ
                        <?php else: ?>
                            ไม่มีการพิจารณาในช่วงเวลาที่เลือก
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($approvals as $approval): ?>
                    <div class="approval-card <?= $approval['status'] === 'rejected' ? 'rejected' : '' ?>">
                        <div class="approval-header">
                            <div class="flex-grow-1">
                                <div class="approval-title"><?= htmlspecialchars($approval['title']) ?></div>
                                <div class="approval-meta">
                                    <i class="fas fa-user me-2"></i>
                                    ผู้ขอ: <?= htmlspecialchars($approval['requester_name'] . ' ' . $approval['requester_lastname']) ?>
                                    <?php if ($approval['requester_department']): ?>
                                        <span class="ms-2">
                                            <i class="fas fa-building me-1"></i>
                                            <?= htmlspecialchars($approval['requester_department']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($approval['dev_name']): ?>
                                        <span class="ms-2">
                                            <i class="fas fa-user-cog me-1"></i>
                                            มอบหมาย: <?= htmlspecialchars($approval['dev_name'] . ' ' . $approval['dev_lastname']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <?php if ($approval['service_name']): ?>
                                        <span class="service-badge service-<?= $approval['service_category'] ?>">
                                            <?php if ($approval['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($approval['service_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="status-badge status-<?= $approval['status'] ?>">
                                    <?php if ($approval['status'] === 'approved'): ?>
                                        <i class="fas fa-check me-1"></i>อนุมัติ
                                    <?php else: ?>
                                        <i class="fas fa-times me-1"></i>ไม่อนุมัติ
                                    <?php endif; ?>
                                </span>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($approval['reviewed_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- รายละเอียดคำขอ -->
                        <div class="bg-light p-3 rounded mb-3">
                            <strong>รายละเอียด:</strong><br>
                            <?= nl2br(htmlspecialchars($approval['description'])) ?>
                        </div>

                        <!-- เหตุผลการพิจารณา -->
                        <?php if ($approval['reason']): ?>
                            <div <?= $approval['status'] === 'approved' ? 'success' : 'danger' ?> 
                            bg-opacity-10 p-3 rounded border-start border-<?= $approval['status'] === 'approved' ? 'success' : 'danger' ?> 
                            border-4 mb-3">
                                <h6 class="fw-bold text-<?= $approval['status'] === 'approved' ? 'success' : 'danger' ?> mb-2">
                                    <i class="fas fa-comment me-2"></i>เหตุผล/ข้อเสนอแนะ
                                </h6>
                                <?= nl2br(htmlspecialchars($approval['reason'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        
                        <!-- รายละเอียดคำขอ -->
                        <div class="bg-light p-3 rounded mb-3">
                            <strong>งบประมาณ:</strong><br>
                            <?= nl2br(htmlspecialchars($approval['budget_approved'])) ?>

                        </div>

                        <!-- ข้อมูลการมอบหมาย -->
                        <?php if ($approval['status'] === 'approved' && $approval['dev_name']): ?>
                            <div >
                                <h6 class="fw-bold text-info mb-2">
                                    <i class="fas fa-user-cog me-2"></i>ข้อมูลการมอบหมาย
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>ผู้พัฒนา:</strong> <?= htmlspecialchars($approval['dev_name'] . ' ' . $approval['dev_lastname']) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>ประมาณการ:</strong> <?= $approval['estimated_days'] ?? 'ไม่ระบุ' ?> วัน
                                    </div>
                                    <div class="col-md-6">
                                        <strong>ความสำคัญ:</strong> 
                                        <?php
                                        $priority_labels = [
                                            'low' => 'ต่ำ',
                                            'medium' => 'ปานกลาง',
                                            'high' => 'สูง',
                                            'urgent' => 'เร่งด่วน'
                                        ];
                                        echo $priority_labels[$approval['priority_level']] ?? 'ปานกลาง';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- สถานะงาน -->
                        <?php if ($approval['task_status']): ?>
                            <div class="task-progress">
                                <h6 class="fw-bold mb-2">
                                    <i class="fas fa-tasks me-2"></i>สถานะการพัฒนา
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">
                                        <?php
                                        $task_status_labels = [
                                            'pending' => 'รอรับงาน',
                                            'received' => 'รับงานแล้ว',
                                            'in_progress' => 'กำลังดำเนินการ',
                                            'on_hold' => 'พักงาน',
                                            'completed' => 'เสร็จสิ้น',
                                            'accepted' => 'ยอมรับงาน'
                                        ];
                                        echo $task_status_labels[$approval['task_status']] ?? 'ไม่ทราบ';
                                        ?>
                                    </span>
                                    <span class="text-muted"><?= $approval['progress_percentage'] ?? 0 ?>%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width: <?= $approval['progress_percentage'] ?? 0 ?>%">
                                        <?= $approval['progress_percentage'] ?? 0 ?>%
                                    </div>
                                </div>
                                
                                <?php if ($approval['task_started_at']): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-play me-1"></i>
                                        เริ่มงาน: <?= date('d/m/Y H:i', strtotime($approval['task_started_at'])) ?>
                                        <?php if ($approval['task_completed_at']): ?>
                                            <span class="ms-3">
                                                <i class="fas fa-flag-checkered me-1"></i>
                                                เสร็จงาน: <?= date('d/m/Y H:i', strtotime($approval['task_completed_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- รีวิวจากผู้ใช้ -->
                        <?php if ($approval['rating']): ?>
                            <div class="bg-warning bg-opacity-10 p-3 rounded border-start border-warning border-4">
                                <h6 class="fw-bold text-warning mb-2">
                                    <i class="fas fa-star me-2"></i>รีวิวจากผู้ใช้
                                </h6>
                                <div class="rating-stars mb-2">
                                    <?= str_repeat('⭐', $approval['rating']) ?>
                                    <span class="ms-2 text-muted">(<?= $approval['rating'] ?>/5)</span>
                                </div>
                                <?php if ($approval['review_comment']): ?>
                                    <div class="bg-white p-2 rounded">
                                        <em>"<?= nl2br(htmlspecialchars($approval['review_comment'])) ?>"</em>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentFilter = '<?= $filter_type ?>';

        function setFilter(type) {
            currentFilter = type;
            
            // อัปเดต UI
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // แสดง/ซ่อน input ตามประเภท
            document.getElementById('dayFilter').style.display = type === 'day' ? 'block' : 'none';
            document.getElementById('monthFilter').style.display = type === 'month' ? 'block' : 'none';
            document.getElementById('yearFilter').style.display = type === 'year' ? 'block' : 'none';
            
            // ถ้าเลือก "ทั้งหมด" ให้ไปทันที
            if (type === 'all') {
                applyFilter();
            }
        }

        function applyFilter() {
            let url = new URL(window.location);
            url.searchParams.set('filter', currentFilter);
            
            switch (currentFilter) {
                case 'day':
                    const date = document.getElementById('filterDate').value;
                    if (date) url.searchParams.set('date', date);
                    break;
                case 'month':
                    const month = document.getElementById('filterMonth').value;
                    if (month) url.searchParams.set('month', month);
                    break;
                case 'year':
                    const year = document.getElementById('filterYear').value;
                    if (year) url.searchParams.set('year', year);
                    break;
                case 'all':
                    url.searchParams.delete('date');
                    url.searchParams.delete('month');
                    url.searchParams.delete('year');
                    break;
            }
            
            window.location.href = url.toString();
        }

        // Auto-refresh every 2 minutes
        // setInterval(function() {
        //     if (document.visibilityState === 'visible') {
        //         location.reload();
        //     }
        // }, 120000);
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