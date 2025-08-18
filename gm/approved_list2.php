<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}

$gm_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// รับพารามิเตอร์การฟิลเตอร์และ pagination
$filter_type = $_GET['filter'] ?? 'all';
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_year = $_GET['year'] ?? date('Y');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// สร้าง WHERE clause ตามการฟิลเตอร์
$where_clause = "WHERE gma.gm_user_id = ? AND gma.status IN ('approved', 'rejected')";
$params = [$gm_id];

switch ($filter_type) {
    case 'day':
        $where_clause .= " AND DATE(gma.reviewed_at) = ?";
        $params[] = $filter_date;
        break;
    case 'month':
        $where_clause .= " AND DATE_FORMAT(gma.reviewed_at, '%Y-%m') = ?";
        $params[] = $filter_month;
        break;
    case 'year':
        $where_clause .= " AND YEAR(gma.reviewed_at) = ?";
        $params[] = $filter_year;
        break;
}

// นับจำนวนรายการทั้งหมด
$count_stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM gm_approvals gma
    JOIN service_requests sr ON gma.service_request_id = sr.id
    $where_clause
");
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, ceil($total_records / $limit));

function apprv_qs($extra = []) {
    $params = $_GET;
    foreach ($extra as $k => $v) { $params[$k] = $v; }
    return htmlspecialchars(http_build_query($params));
}
// ดึงรายการที่อนุมัติแล้ว
$stmt = $conn->prepare("
    SELECT 
        gma.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        sr.priority,
        sr.current_step,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        requester.department as requester_department,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        assignor.lastname as assignor_lastname,
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
    FROM gm_approvals gma
    JOIN service_requests sr ON gma.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN tasks t ON sr.id = t.service_request_id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    $where_clause
    ORDER BY gma.reviewed_at DESC
    LIMIT $limit OFFSET $offset
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

$approval_rate = $total_records > 0 ? round(($approved_count / $total_records) * 100, 1) : 0;
$completion_rate = $approved_count > 0 ? round(($completed_count / $approved_count) * 100, 1) : 0;
$average_rating = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>BobbyCareDev-Report</title>
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
   .apprv-container { font-family: "Segoe UI", sans-serif; }
.apprv-header h2 { color: #333; }
.apprv-stat-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    padding: 15px;
    text-align: center;
}
.apprv-stat-value { font-size: 1.6rem; font-weight: bold; color: #0d6efd; }
.apprv-stat-label { font-size: 0.9rem; color: #666; }

.apprv-btn {
    border: none; padding: 8px 14px; border-radius: 20px;
    background: #f1f3f5; color: #333; font-size: 0.9rem;
    transition: all 0.2s ease;
}
.apprv-btn.active, .apprv-btn:hover { background: #0d6efd; color: #fff; }

.apprv-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.07);
}
.apprv-card-header {
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 8px;
}
.apprv-meta { font-size: 0.85rem; color: #777; }

.apprv-status-badge {
    padding: 5px 10px; border-radius: 20px;
    font-size: 0.8rem; font-weight: bold;
}
.apprv-status-badge.approved { background: #d1e7dd; color: #0f5132; }
.apprv-status-badge.rejected { background: #f8d7da; color: #842029; }

.apprv-empty {
    background: #fafafa; border-radius: 12px; box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
}

@media (max-width: 576px) {
    .apprv-card-header { flex-direction: column; align-items: flex-start; gap: 5px; }
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
                         <li class="nav-item ">
                            <a href="gmindex2.php">
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

                        <li class="nav-item active ">
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

                        <li class="nav-item">
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


              <div class="page-inner">
<div class="apprv-container container-fluid py-4">



  <!-- Header + Stats (ตามของคุณ) -->
  <div class="apprv-header text-center mb-4">
 
  </div>

    <!-- Header -->
    <div class="apprv-header text-center mb-5">
        <h2 class="fw-bold mb-3"><i class="fas fa-check-circle me-2 text-success"></i>รายการที่อนุมัติแล้ว</h2>
        <div class="apprv-stats row g-3 justify-content-center">
            <div class="col-6 col-md-4 col-lg-3">
                <div class="apprv-stat-card">
                    <div class="apprv-stat-value"><?= $total_records ?></div>
                    <div class="apprv-stat-label">ทั้งหมด</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="apprv-stat-card">
                    <div class="apprv-stat-value"><?= $approval_rate ?>%</div>
                    <div class="apprv-stat-label">อัตราการอนุมัติ</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="apprv-stat-card">
                    <div class="apprv-stat-value"><?= $completion_rate ?>%</div>
                    <div class="apprv-stat-label">อัตราการเสร็จ</div>
                </div>
            </div>
        </div>
    </div>

  <!-- แถบสรุปหน้า -->
 <div class="apprv-page-summary d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="m-0">รายการที่พิจารณาแล้ว</h4>
    </div>
    <div class="text-muted">
        หน้า <?= $page ?> จาก <?= $total_pages ?> (<?= $total_records ?> รายการ)
        <span> • แสดง <?= $limit ?>/หน้า</span>
    </div>
</div>


  <!-- List -->
  <?php if (empty($approvals)): ?>
    <div class="apprv-empty text-center p-5">
      <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
      <h5>ไม่มีรายการที่พิจารณาแล้ว</h5>
      <p><?= $filter_type === 'all' ? 'ยังไม่มีการพิจารณาคำขอ' : 'ไม่มีการพิจารณาในช่วงเวลาที่เลือก' ?></p>
    </div>
  <?php else: ?>
    <?php foreach ($approvals as $approval): ?>
      <div class="apprv-card <?= $approval['status'] === 'rejected' ? 'apprv-rejected' : '' ?>">
        <div class="apprv-card-header">
          <div>
            <h5 class="mb-1"><?= htmlspecialchars($approval['title']) ?></h5>
            <div class="apprv-meta text-muted small">
              ผู้ขอคุณ: <?= htmlspecialchars($approval['requester_name'] . ' ' . $approval['requester_lastname']) ?>
              <?php if ($approval['requester_department']): ?>
                | <?= htmlspecialchars($approval['requester_department']) ?>
              <?php endif; ?>
              <?php if ($approval['assignor_name']): ?>
                | ผู้จัดการเเผนก: <?= htmlspecialchars($approval['assignor_name'] . ' ' . $approval['assignor_lastname']) ?>
              <?php endif; ?>
              <?php if ($approval['dev_name']): ?>
                | มอบหมายให้ผู้พัฒนา: <?= htmlspecialchars($approval['dev_name'] . ' ' . $approval['dev_lastname']) ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="apprv-status">
            <span class="apprv-status-badge <?= $approval['status'] ?>">
              <?= $approval['status'] === 'approved' ? 'อนุมัติ' : 'ไม่อนุมัติ' ?>
            </span>
            <div class="apprv-reviewed-at small text-muted mt-1">
              <?= date('d/m/Y H:i', strtotime($approval['reviewed_at'])) ?>
            </div>
          </div>
        </div>

        <?php if ($approval['service_name']): ?>
          <div class="apprv-service">
            <span class="apprv-service-badge"><?= htmlspecialchars($approval['service_name']) ?></span>
          </div>
        <?php endif; ?>

        <div class="apprv-desc">
          <strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($approval['description'])) ?>
        </div>

        <?php if (!empty($approval['reason'])): ?>
          <div class="apprv-reason"><strong>เหตุผล:</strong> <?= nl2br(htmlspecialchars($approval['reason'])) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <?php
      $window = 2; // จำนวนเพจรอบๆ หน้า current
      $start = max(1, $page - $window);
      $end   = min($total_pages, $page + $window);
    ?>
    <nav class="apprv-pagination" aria-label="Pagination">
        <!-- First -->
        <a class="apprv-page-link <?= $page == 1 ? 'disabled' : '' ?>"
           href="<?= $page == 1 ? '#' : '?' . apprv_qs(['page' => 1]) ?>">«</a>

        <!-- Prev -->
        <a class="apprv-page-link <?= $page == 1 ? 'disabled' : '' ?>"
           href="<?= $page == 1 ? '#' : '?' . apprv_qs(['page' => $page - 1]) ?>">‹</a>

        <?php if ($start > 1): ?>
            <a class="apprv-page-link" href="?<?= apprv_qs(['page' => 1]) ?>">1</a>
            <?php if ($start > 2): ?><span class="apprv-ellipsis">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a class="apprv-page-link <?= $i == $page ? 'active' : '' ?>"
               href="?<?= apprv_qs(['page' => $i]) ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><span class="apprv-ellipsis">…</span><?php endif; ?>
            <a class="apprv-page-link" href="?<?= apprv_qs(['page' => $total_pages]) ?>"><?= $total_pages ?></a>
        <?php endif; ?>

        <!-- Next -->
        <a class="apprv-page-link <?= $page == $total_pages ? 'disabled' : '' ?>"
           href="<?= $page == $total_pages ? '#' : '?' . apprv_qs(['page' => $page + 1]) ?>">›</a>

        <!-- Last -->
        <a class="apprv-page-link <?= $page == $total_pages ? 'disabled' : '' ?>"
           href="<?= $page == $total_pages ? '#' : '?' . apprv_qs(['page' => $total_pages]) ?>">»</a>
    </nav>
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
            url.searchParams.delete('page'); // รีเซ็ตหน้า

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
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 120000);
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