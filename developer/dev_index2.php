<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// รับคำร้องที่อนุมัติครบแล้วและ assigned ถึง developer นี้
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        t.id as task_id,
        t.task_status,
        t.accepted_at,
        aa.estimated_days,
        aa.priority_level,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        assignor.name as assignor_name
    FROM service_requests sr
    JOIN tasks t ON sr.id = t.service_request_id
    JOIN users requester ON sr.user_id = requester.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    WHERE t.developer_user_id = ?
      AND sr.status = 'approved'
      AND t.task_status = 'pending'
    ORDER BY sr.created_at DESC
");
$stmt->execute([$developer_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รับงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request_id'])) {
    $task_id = $_POST['accept_request_id'];
    $service_request_id = $_POST['service_request_id'];

    try {
        $conn->beginTransaction();

        // อัปเดตสถานะใน tasks
        $update = $conn->prepare("UPDATE tasks SET task_status = 'received', progress_percentage = 10, started_at = NOW(), accepted_at = NOW() WHERE id = ? AND developer_user_id = ?");
        $update->execute([$task_id, $developer_id]);

        // อัปเดตสถานะใน service_requests
        $update_sr = $conn->prepare("UPDATE service_requests SET developer_status = 'received' WHERE id = ?");
        $update_sr->execute([$service_request_id]);

        // บันทึก log
        $log_stmt = $conn->prepare("INSERT INTO task_status_logs (task_id, old_status, new_status, changed_by, notes) VALUES (?, 'pending', 'received', ?, 'งานได้รับการยอมรับโดยผู้พัฒนา')");
        $log_stmt->execute([$task_id, $developer_id]);

        $conn->commit();
        header("Location: tasks_board.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
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
          <a href="developer_dashboard2.php" class="logo">
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
              <a data-bs-toggle="collapse" href="#dashboard" class="collapsed" aria-expanded="false">
                <i class="fas fa-home"></i>
                 <p>หน้าหลัก</p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse" id="dashboard">
                                <ul class="nav nav-collapse">
                                    <li>
                                        <a href="gmindex2.php">
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

                        <li class="nav-item ">
                            <a href="approved_list2.php">
                                <i class="fas fa-check-circle"></i> <!-- รายการที่อนุมัติ -->
                                <p>รายการที่อนุมัติเเล้ว</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="view_completed_tasks2.php">
                                <i class="fas fa-comments"></i> <!-- รีวิวจากผู้ใช้ -->
                                <p>User Reviews</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item active">
                            <a href="developer_dashboard2.php">
                                <i class="fas fa-tachometer-alt"></i> <!-- Dashboard -->
                                <p>Dashboard_DEV</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="report2.php">
                                <i class="fas fa-file-alt"></i> <!-- Report -->
                                <p>Report</p>
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
                    <span class="op-7">DEV:</span>
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




      
        <!-- Main Content -->
        <div class="glass-card p-4 animate-fade-in">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-inbox text-primary me-3 fs-3"></i>
                <h2 class="mb-0 fw-bold">งานที่รอรับ</h2>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3 class="fw-bold mb-3">ไม่มีงานที่รอรับ</h3>
                    <p class="fs-5">ขณะนี้ไม่มีงานใหม่ที่รอการรับ กลับมาตรวจสอบใหม่ภายหลัง</p>
                    <a href="tasks_board.php" class="btn btn-gradient mt-3">
                        <i class="fas fa-tasks me-2"></i>ไปยังบอร์ดงาน
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <div class="table-modern">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-clipboard-list me-2"></i>งาน</th>
                                    <th><i class="fas fa-user me-2"></i>ผู้ร้องขอ</th>
                                    <th><i class="fas fa-calendar me-2"></i>วันที่ร้องขอ</th>
                                    <th><i class="fas fa-info-circle me-2"></i>สถานะ</th>
                                    <th><i class="fas fa-cogs me-2"></i>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <h6 class="fw-bold mb-2 text-primary"><?= htmlspecialchars($req['title']) ?></h6>
                                                <p class="text-muted mb-0 small"><?= nl2br(htmlspecialchars(substr($req['description'], 0, 100))) ?><?= strlen($req['description']) > 100 ? '...' : '' ?></p>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted">
                                                <i class="fas fa-clock me-2"></i>
                                                <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                                <?php if ($req['assignor_name']): ?>
                                                    <br><small class="text-primary">
                                                        <i class="fas fa-user-tie me-1"></i>
                                                        มอบหมายโดย: <?= htmlspecialchars($req['assignor_name']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-hourglass-half me-1"></i>รอรับงาน
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="accept_request_id" value="<?= $req['task_id'] ?>">
                                                <input type="hidden" name="service_request_id" value="<?= $req['id'] ?>">
                                                <button type="submit" class="btn btn-success-gradient pulse-animation"
                                                    onclick="return confirm('ยืนยันการรับงาน?')">
                                                    <i class="fas fa-check me-2"></i>รับงาน
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
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