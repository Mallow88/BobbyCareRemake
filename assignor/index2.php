<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
  header("Location: ../index.php");
  exit();
}

$assignor_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// ดึงสถิติรวม
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN aa.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN aa.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
        SUM(CASE WHEN aa.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
    FROM assignor_approvals aa
    WHERE aa.assignor_user_id = ?
");
$stats_stmt->execute([$assignor_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// ดึงงานที่รอพิจารณา
$pending_stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM service_requests sr
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    WHERE dma.status = 'approved' 
    AND (aa.id IS NULL OR aa.status = 'pending')
");
$pending_stmt->execute();
$pending_count = $pending_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// ดึงงานล่าสุดที่อนุมัติ
$recent_stmt = $conn->prepare("
    SELECT 
        sr.title,
        sr.created_at,
        aa.reviewed_at,
        aa.status,
        requester.name as requester_name,
        requester.lastname as requester_lastname,
        dev.name as dev_name,
        dev.lastname as dev_lastname
    FROM assignor_approvals aa
    JOIN service_requests sr ON aa.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    WHERE aa.assignor_user_id = ?
    ORDER BY aa.reviewed_at DESC
    LIMIT 5
");
$recent_stmt->execute([$assignor_id]);
$recent_approvals = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>BobbyCareDev-Dashboard</title>
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
            <li class="nav-item active ">
              <a data-bs-toggle="collapse" href="#dashboard" class="collapsed" aria-expanded="false">
                <i class="fas fa-home"></i>
                <p>Dashboard</p>
                <span class="caret"></span>
              </a>
              <div class="collapse" id="dashboard">
                <ul class="nav nav-collapse">
                  <li>
                    <a href="index2.php">
                      <span class="sub-item">หน้าหลัก</span>
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
              <a href="view_requests2.php">
                <i class="fas fa-search"></i> <!-- ตรวจสอบคำขอ -->
                <p>ตรวจสอบคำขอ</p>
                <span class="badge badge-success"></span>
              </a>
            </li>

            <li class="nav-item">
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
              <a href="assignor_dashboard.php2">
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
                    <span class="op-7">ผู้จัดการทั่วไป:</span>
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









        <div class="container">
          <div class="page-inner">
            <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
              <div>
                <h3 class="fw-bold mb-3">สถิติคำขอของคุณ</h3>

              </div>

            </div>
            <div class="row">
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                          <i class="fas fa-file-alt"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">ทั้งหมด</p>
                          <h4 class="card-title"><?= $stats['total_requests'] ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-info bubble-shadow-small">
                          <i class="fas fa-hourglass-half"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">รอพิจารณา</p>
                          <h4 class="card-title">
                            <a href="view_requests2.php" class="text-decoration-none"><?= $pending_count ?></a>
                          </h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-success bubble-shadow-small">
                          <i class="fas fa-check-circle"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">อนุมัติแล้ว</p>
                          <h4 class="card-title"><?= $stats['approved_requests'] ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-secondary bubble-shadow-small">
                          <i class="fas fa-times-circle"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">ไม่อนุมัติ</p>
                          <h4 class="card-title"><?= $stats['rejected_requests'] ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>


            <div class="row">
              <div class="col-md-4">
                <div class="card card-round">
                  <!-- เนื้อหาฝั่งซ้าย -->
                </div>
              </div>

              <div class="col- 8 md-5">
                <div class="card card-round">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0">รายการอนุมัติล่าสุด</div>
                    <a href="requests/index.php" class="btn btn-outline-primary btn-sm">
                      ดูทั้งหมด
                    </a>
                  </div>

                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <?php if (empty($recent_approvals)): ?>
                        <p class="text-muted m-3">ยังไม่มีการอนุมัติ</p>
                      <?php else: ?>
                        <table class="table align-items-center mb-0">
                          <thead class="thead-light">
                            <tr>
                              <th scope="col">หัวข้อ</th>
                              <th scope="col" class="text-end">วันและเวลา</th>
                              <th scope="col" class="text-end">สถานะ</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($recent_approvals as $approval): ?>
                              <?php
                              $status_class = $approval['status'] === 'approved' ? 'status-approved' : ($approval['status'] === 'rejected' ? 'status-rejected' : 'status-pending');

                              $status_text = $approval['status'] === 'approved' ? 'อนุมัติ' : ($approval['status'] === 'rejected' ? 'ไม่อนุมัติ' : 'รอพิจารณา');
                              ?>
                              <tr>
                                <td>
                                  <h6 class="fw-bold mb-0">
                                    <?= htmlspecialchars(substr($approval['title'], 0, 40)) ?>
                                    <?= strlen($approval['title']) > 40 ? '...' : '' ?>
                                  </h6>
                                </td>
                                <td class="text-end">
                                  <?= date('d/m/Y H:i', strtotime($approval['created_at'])) ?>
                                </td>
                                <td class="text-end">
                                  <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

            </div>










          </div>
        </div>
      </div>

      <footer class="footer">
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
      </footer>
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

</body>

</html>