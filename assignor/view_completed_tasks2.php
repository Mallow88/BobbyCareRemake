<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

$assignor_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// ดึงงานที่เสร็จแล้วและมีการรีวิว
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
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
        aa.assignor_user_id
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    JOIN users dev ON t.developer_user_id = dev.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    WHERE aa.assignor_user_id = ? 
    AND t.task_status IN ('completed', 'accepted', 'revision_requested')
    AND ur.id IS NOT NULL
    ORDER BY ur.reviewed_at DESC
");
$stmt->execute([$assignor_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .task-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #48bb78;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .task-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .task-meta {
            color: #718096;
            font-size: 0.9rem;
        }

        .review-section {
            background: #f0fff4;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #48bb78;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .stars {
            color: #f6ad55;
            font-size: 1.2rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-accepted {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-revision {
            background: #fef5e7;
            color: #d69e2e;
        }

        .status-pending {
            background: #e2e8f0;
            color: #4a5568;
        }

        .revision-notes {
            background: #fef5e7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border-left: 3px solid #f6ad55;
        }

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

            .task-header {
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



      <div class="content-card">
            <h2><i class="fas fa-star"></i> งานที่ได้รับการรีวิวแล้ว</h2>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>ยังไม่มีงานที่เสร็จแล้ว</h3>
                    <p>งานที่มอบหมายและได้รับการรีวิวจะแสดงที่นี่</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div>
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-meta">
                                    <i class="fas fa-user"></i>
                                    ผู้ขอ: <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    <span style="margin-left: 20px;">
                                        <i class="fas fa-user-cog"></i>
                                        ผู้พัฒนา: <?= htmlspecialchars($task['dev_name'] . ' ' . $task['dev_lastname']) ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <?php if ($task['review_status'] === 'accepted'): ?>
                                    <span class="status-badge status-accepted">ยอมรับงาน</span>
                                <?php elseif ($task['review_status'] === 'revision_requested'): ?>
                                    <span class="status-badge status-revision">ขอแก้ไข</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">รอรีวิว</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="background: #f7fafc; border-radius: 8px; padding: 15px; margin: 15px 0;">
                            <strong>รายละเอียดงาน:</strong><br>
                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                        </div>

                        <?php if ($task['developer_notes']): ?>
                        <div style="background: #e6fffa; border-radius: 8px; padding: 15px; margin: 15px 0; border-left: 3px solid #38b2ac;">
                            <strong><i class="fas fa-sticky-note"></i> หมายเหตุจากผู้พัฒนา:</strong><br>
                            <?= nl2br(htmlspecialchars($task['developer_notes'])) ?>
                        </div>
                        <?php endif; ?>

                        <div class="review-section">
                            <h4 style="margin-bottom: 15px; color: #2d3748;">
                                <i class="fas fa-star"></i> รีวิวจากผู้ใช้
                            </h4>

                            <div class="rating-display">
                                <span class="stars"><?= str_repeat('⭐', $task['rating']) ?></span>
                                <span style="font-weight: 600;"><?= $task['rating'] ?>/5 ดาว</span>
                                <span style="color: #718096; font-size: 0.9rem;">
                                    รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($task['reviewed_at'])) ?>
                                </span>
                            </div>

                            <?php if ($task['review_comment']): ?>
                            <div style="margin: 10px 0;">
                                <strong>ความเห็น:</strong><br>
                                <div style="background: white; padding: 12px; border-radius: 6px; margin-top: 5px; font-style: italic;">
                                    "<?= nl2br(htmlspecialchars($task['review_comment'])) ?>"
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($task['revision_notes']): ?>
                            <div class="revision-notes">
                                <strong><i class="fas fa-edit"></i> รายละเอียดที่ต้องแก้ไข:</strong><br>
                                <?= nl2br(htmlspecialchars($task['revision_notes'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; font-size: 0.9rem; color: #718096;">
                            <div>
                                <i class="fas fa-calendar"></i>
                                เริ่มงาน: <?= $task['started_at'] ? date('d/m/Y H:i', strtotime($task['started_at'])) : 'ไม่ระบุ' ?>
                            </div>
                            <div>
                                <i class="fas fa-check-circle"></i>
                                เสร็จงาน: <?= date('d/m/Y H:i', strtotime($task['completed_at'])) ?>
                            </div>
                        </div>
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
        // จัดการฟอร์มตามการเลือก
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('form');
                const developerSelect = form.querySelector('select[name="assigned_developer_id"]');
                const prioritySelect = form.querySelector('select[name="priority_level"]');
                const daysInput = form.querySelector('input[name="estimated_days"]');
                const textarea = form.querySelector('textarea');

                if (this.value === 'approved') {
                    form.querySelector('select[name="development_service_id"]').required = true;
                    form.querySelector('select[name="development_service_id"]').disabled = false;
                    developerSelect.required = true;
                    developerSelect.disabled = false;
                    prioritySelect.disabled = false;
                    daysInput.disabled = false;
                    textarea.placeholder = 'ข้อเสนอแนะหรือคำแนะนำสำหรับผู้พัฒนา';
                } else {
                    form.querySelector('select[name="development_service_id"]').required = false;
                    form.querySelector('select[name="development_service_id"]').disabled = true;
                    developerSelect.required = false;
                    developerSelect.disabled = true;
                    prioritySelect.disabled = true;
                    daysInput.disabled = true;
                    textarea.required = true;
                    textarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
                }
            });
        });
    </script>

</body>

</html>