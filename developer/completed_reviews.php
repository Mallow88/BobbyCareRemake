<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// ================== Filter ประเภทงาน ==================
$type = $_GET['type'] ?? 'all';
$where = [];
$params = [];

if ($type !== 'all') {
    $where[] = "s.category = ?";
    $params[] = $type;
}


// ================== Query หลัก ==================
$sql = "
       SELECT 
        t.*,
        sr.title,
        sr.description,
        s.category,   -- ใช้จาก services แทน
        sr.created_at as request_date,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.revision_notes,
        ur.reviewed_at,
        gma.budget_approved,
        dn.document_number,
        assignor.name as assignor_name
    FROM user_reviews ur
    JOIN tasks t ON ur.task_id = t.id
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN services s ON sr.service_id = s.id       -- 👈 join เพิ่ม
    JOIN users requester ON sr.user_id = requester.id
    JOIN users dev ON t.developer_user_id = dev.id
    LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id

";

// เพิ่มเงื่อนไขถ้ามี filter
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY ur.reviewed_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== คำนวณสถิติ ==================
$total_reviews = count($tasks);
$total_rating = 0;
$accepted_count = 0;
$revision_count = 0;

foreach ($tasks as $review) {
    $total_rating += $review['rating'];
    if ($review['review_status'] === 'accepted') {
        $accepted_count++;
    } elseif ($review['review_status'] === 'revision_requested') {
        $revision_count++;
    }
}

$average_rating = $total_reviews > 0 ? round($total_rating / $total_reviews, 1) : 0;
$acceptance_rate = $total_reviews > 0 ? round(($accepted_count / $total_reviews) * 100, 1) : 0;
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
            background: #f8fafc;
            font-family: "Public Sans", sans-serif;
        }

        /* ส่วนหัวสถิติ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
        }

        .stat-box:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* งานแต่ละรายการ */
        .task-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .task-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
        }

        .task-meta {
            font-size: 0.9rem;
            color: #4a5568;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-accepted {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-revision {
            background: #fefcbf;
            color: #b7791f;
        }

        /* ส่วนรีวิว */
        .review-box {
            margin-top: 10px;
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
        }

        .rating-stars {
            color: #f6ad55;
            font-size: 1.2rem;
        }

        .comment-box {
            background: white;
            border-radius: 8px;
            padding: 12px;
            font-style: italic;
            margin-top: 10px;
            border-left: 3px solid #3182ce;
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
                    <a href="tasks_board.php" class="logo">
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
                            <a href="tasks_board.php">
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
                            <a href="completed_reviews.php">
                                <i class="fas fa-comments"></i> <!-- รีวิวจากผู้ใช้ -->
                                <p>งานที่รีวิวเเล้ว</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item ">
                            <a href="export_report.php">
                                <i class="fas fa-tachometer-alt"></i> <!-- Dashboard -->
                                <p>Dashboard_DEV</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item ">
                            <a href="calendar2.php">
                                <i class="fas fa-check-circle"></i> <!-- รายการที่อนุมัติ -->
                                <p>ปฏิทิน</p>
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
                        <a href="tasks_board.php" class="logo">
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
                                        <span class="op-7">Development :</span>
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




            <div class="container py-5">


                <div class="page-inner">

                    <!-- สรุปสถิติ -->
                    <div class="stats-grid mb-5">
                        <div class="stat-box">
                            <div class="stat-number text-primary"><?= $total_reviews ?></div>
                            <div class="stat-label">รีวิวทั้งหมด</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number text-warning"><?= $average_rating ?></div>
                            <div class="stat-label">คะแนนเฉลี่ย</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number text-success"><?= $acceptance_rate ?>%</div>
                            <div class="stat-label">อัตราการยอมรับ</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number text-danger"><?= $revision_count ?></div>
                            <div class="stat-label">ขอแก้ไข</div>
                        </div>
                    </div>

                    <!-- ฟิลเตอร์ประเภทงาน -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <a href="?type=<?= ($type === 'development') ? 'all' : 'development' ?>" class="text-decoration-none">
                                <div class="card h-100 shadow-sm border-0 <?= ($type === 'development') ? 'bg-primary text-white' : '' ?>">
                                    <div class="card-body text-center">
                                        <i class="fas fa-laptop-code fa-2x mb-2"></i>
                                        <h5 class="card-title mb-0">งานพัฒนา</h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="?type=<?= ($type === 'service') ? 'all' : 'service' ?>" class="text-decoration-none">
                                <div class="card h-100 shadow-sm border-0 <?= ($type === 'service') ? 'bg-success text-white' : '' ?>">
                                    <div class="card-body text-center">
                                        <i class="fas fa-laptop-code fa-2x mb-2"></i>
                                        <h5 class="card-title mb-0">งานบริการ</h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>




                    <!-- รายการรีวิว -->
                    <h2 class="mb-4"><i class="fas fa-star text-warning"></i> งานที่ได้รับการรีวิวแล้ว</h2>

                    <?php if (empty($tasks)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <h4>ยังไม่มีงานที่เสร็จแล้ว</h4>
                            <p>งานที่อนุมัติและได้รับการรีวิวจะแสดงที่นี่</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-item">
                                <div class="task-header">
                                    <div>
                                        <div class="task-title"><?= htmlspecialchars($task['document_number']) ?> หัวข้องาน : <?= htmlspecialchars($task['title']) ?></div>
                                        <div class="task-meta">
                                            <i class="fas fa-user"></i> ผู้ขอ: <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                            &nbsp; | &nbsp;
                                            <i class="fas fa-user-cog"></i> ผู้พัฒนา: <?= htmlspecialchars($task['dev_name'] . ' ' . $task['dev_lastname']) ?>
                                        </div>
                                    </div>
                                    <div>
                                          <div class="task-meta mt-3">
                                        <?php if ($task['review_status'] === 'accepted'): ?>
                                            <span class="status-badge status-accepted">ยอมรับงาน</span>
                                        <?php elseif ($task['review_status'] === 'revision_requested'): ?>
                                            <span class="status-badge status-revision">ขอแก้ไข</span>
                                        <?php endif; ?>
                                         </div>

                                        <div class="task-meta mt-3">
                                            <span class="badge <?= ($task['category'] === 'development') ? 'bg-primary' : (($task['category'] === 'service') ? 'bg-success' : 'bg-secondary') ?> p-2">
                                                <i class="fas fa-briefcase"></i>
                                                <?= ($task['category'] === 'development') ? 'งานพัฒนา' : (($task['category'] === 'service') ? 'งานบริการ' : 'ไม่ระบุ') ?>
                                            </span>
                                        </div>
                                    </div>

                                </div>

                                <div class="review-box">
                                    <div class="rating-stars"><?= str_repeat('⭐', $task['rating']) ?></div>

                                    <div><strong><?= $task['rating'] ?>/5 ดาว</strong> | รีวิวเมื่อ <?= date('d/m/Y H:i', strtotime($task['reviewed_at'])) ?></div>

                                    <?php if ($task['review_comment']): ?>
                                        <div class="comment-box"><strong> รีวิวจากผู้ใช้บริการ :</strong>
                                            “<?= nl2br(htmlspecialchars($task['review_comment'])) ?>” <br>
                                            <strong>รายละเอียดงาน : </strong><br>
                                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($task['revision_notes']): ?>
                                        <div class="comment-box" style="border-left-color:#dd6b20;">
                                            <strong><i class="fas fa-edit"></i> รายละเอียดที่ต้องแก้ไข:</strong><br>
                                            <?= nl2br(htmlspecialchars($task['revision_notes'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($task['developer_notes']): ?>
                                        <div style="background: #e6fffa; border-radius: 8px; padding: 15px; margin: 15px 0; border-left: 3px solid #38b2ac;">
                                            <strong><i class="fas fa-sticky-note"></i> หมายเหตุจากผู้พัฒนา:</strong><br>
                                            <?= nl2br(htmlspecialchars($task['developer_notes'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="task-meta mt-3">
                                    <i class="fas fa-check-circle"></i> เสร็จงาน: <?= date('d/m/Y H:i', strtotime($task['completed_at'])) ?>
                                </div>





                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

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

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                document.querySelectorAll('[data-bs-target^="#gmApprovalSection"]').forEach(button => {
                    button.addEventListener("click", function() {
                        const targetId = this.getAttribute("data-bs-target");
                        const container = document.querySelector(targetId + " .gm-approval-content");
                        const requestId = container.getAttribute("data-request-id");

                        if (!container.dataset.loaded) {
                            fetch("gm_approve.php?id=" + requestId)
                                .then(response => response.text())
                                .then(html => {
                                    container.innerHTML = html;
                                    container.dataset.loaded = "true";
                                })
                                .catch(err => {
                                    container.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
                                });
                        }
                    });
                });
            });
        </script>


</body>

</html>