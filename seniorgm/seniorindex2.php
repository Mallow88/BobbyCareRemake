<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seniorgm') {
    header("Location: ../index.php");
    exit();
}
$picture_url = $_SESSION['picture_url'] ?? null;
// ดึงคำขอที่ผ่าน GM แล้ว
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        requester.name AS requester_name, 
        requester.lastname AS requester_lastname,
        requester.employee_id,
        requester.position,
        requester.department,
        requester.phone,
        requester.email,
        
        -- Division Manager Info
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Info  
        aa.budget_approved AS assignor_budget_approved,
        aa.reason as assignor_reason,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        
        -- GM Info
        gma.reason as gm_reason,
        gma.budget_approved AS gm_budget_approved,
        gma.reviewed_at as gm_reviewed_at,
        gm.name as gm_name,
        
        -- Service Info
        s.name as service_name,
        s.category as service_category
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    JOIN gm_approvals gma ON sr.id = gma.service_request_id
    JOIN users gm ON gma.gm_user_id = gm.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN senior_gm_approvals sgma ON sr.id = sgma.service_request_id
    WHERE dma.status = 'approved' 
    AND aa.status = 'approved'
    AND gma.status = 'approved'
    AND (sgma.id IS NULL OR sgma.status = 'pending')
    ORDER BY gma.reviewed_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <style>
        :root {
            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            box-shadow: var(--card-shadow);
        }

        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
        }

        .request-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #805ad5;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .request-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .info-icon.employee {
            background: #667eea;
        }

        .info-icon.user {
            background: #10b981;
        }

        .info-icon.position {
            background: #f59e0b;
        }

        .info-icon.department {
            background: #8b5cf6;
        }

        .info-icon.phone {
            background: #ef4444;
        }

        .info-icon.email {
            background: #06b6d4;
        }

        .service-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .service-development {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .service-service {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            color: #1e40af;
        }

        .category-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-rdc {
            background: #dbeafe;
            color: #1e40af;
        }

        .category-cdc {
            background: #d1fae5;
            color: #065f46;
        }

        .category-bdc {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low {
            background: #c6f6d5;
            color: #2f855a;
        }

        .priority-medium {
            background: #fef5e7;
            color: #d69e2e;
        }

        .priority-high {
            background: #fed7d7;
            color: #c53030;
        }

        .priority-urgent {
            background: #e53e3e;
            color: white;
        }

        .approval-timeline {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }

        .timeline-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            background: white;
            border-left: 4px solid #48bb78;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            background: #48bb78;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .step-details {
            font-size: 0.9rem;
            color: #718096;
            line-height: 1.4;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn-approve {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(128, 90, 213, 0.3);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(128, 90, 213, 0.4);
            color: white;
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

        .request-meta {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .description-box {
            background: #f7fafc;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }

        .benefits-box {
            background: linear-gradient(135deg, #f0fff4, #e6fffa);
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }

        .estimate-info {
            background: #fef5e7;
            border-radius: 8px;
            padding: 8px 12px;
            margin: 8px 0;
            border-left: 3px solid #f59e0b;
            display: inline-block;
            font-size: 0.9rem;
        }

        .budget-info {
            background: #e6fffa;
            border-radius: 8px;
            padding: 10px 15px;
            margin: 10px 0;
            border-left: 3px solid #38b2ac;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .user-info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
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
                    <a href="seniorindex2.php" class="logo">
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
                            <a href="seniorindex2.php">
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

                        <!-- <li class="nav-item ">
              <a href="create2.php">
                <i class="fas fa-plus-circle"></i>
                <p>ประวัติการอนุมัติ</p>
                <span class="badge badge-success"></span>
              </a>
            </li> -->



                        <!-- <li class="nav-item">
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
                        <a href="index2.php" class="logo">
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
                                        <span class="op-7">ผู้จัดการทั่วไปอาวุโสคุณ:</span>
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
                    <!-- Content -->
                    <div class="glass-card p-4">
                        <div class="d-flex align-items-center mb-4">
                            <i class="fas fa-clipboard-check text-primary me-3 fs-3"></i>
                            <h2 class="mb-0 fw-bold">รายการคำขอที่รอการพิจารณา</h2>
                        </div>

                        <?php if (empty($requests)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3 class="fw-bold mb-3">ไม่มีคำขอที่รอการพิจารณา</h3>
                                <p class="fs-5">ขณะนี้ไม่มีคำขอที่ต้องการการอนุมัติจากคุณ</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <div class="request-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>

                                            <div class="d-flex gap-2 mb-2">

                                                <?php if ($req['service_name']): ?>
                                                    <span class="service-badge service-<?= $req['service_category'] ?>">
                                                        <?php if ($req['service_category'] === 'development'): ?>
                                                            <i class="fas fa-code me-1"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-tools me-1"></i>
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($req['service_name']) ?>
                                                    </span>
                                                <?php endif; ?>



                                            </div>
                                            <div class="request-meta">
                                                <i class="fas fa-calendar me-1"></i>
                                                วันที่ส่ง: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <span class="priority-badge priority-<?= $req['priority_level'] ?>">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                <?php
                                                $priorities = [
                                                    'low' => 'ต่ำ',
                                                    'medium' => 'ปานกลาง',
                                                    'high' => 'สูง',
                                                    'urgent' => 'เร่งด่วน'
                                                ];
                                                echo $priorities[$req['priority_level']] ?? 'ปานกลาง';
                                                ?>
                                            </span>

                                            <?php if ($req['estimated_days']): ?>
                                                <div class="estimate-info mt-2">
                                                    <i class="fas fa-clock me-1"></i>
                                                    ประมาณ <?= $req['estimated_days'] ?> วัน
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                    </div>

                                    <!-- ข้อมูลผู้ขอ -->
                                    <div class="user-info-grid">
                                        <div class="info-item">
                                            <div class="info-icon employee">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">รหัสพนักงาน</small>
                                                <div class="fw-bold"><?= htmlspecialchars($req['employee_id'] ?? 'ไม่ระบุ') ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-icon user">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">ชื่อ-นามสกุล</small>
                                                <div class="fw-bold"><?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-icon position">
                                                <i class="fas fa-briefcase"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">ตำแหน่ง</small>
                                                <div class="fw-bold"><?= htmlspecialchars($req['position'] ?? 'ไม่ระบุ') ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-icon department">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">หน่วยงาน</small>
                                                <div class="fw-bold"><?= htmlspecialchars($req['department'] ?? 'ไม่ระบุ') ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-icon phone">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">เบอร์โทร</small>
                                                <div class="fw-bold"><?= htmlspecialchars($req['phone'] ?? 'ไม่ระบุ') ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-icon email">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">อีเมล</small>
                                                <div class="fw-bold"><?= htmlspecialchars($req['email'] ?? 'ไม่ระบุ') ?></div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="action-buttons">
                                        <a href="senior_approve2.php?id=<?= $req['id'] ?>" class="btn-approve">
                                            <i class="fas fa-gavel"></i>
                                            รายละเอียดเพิ่มเติม
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
        // แสดง/ซ่อน textarea เหตุผลตามการเลือก
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('form');
                const textarea = form.querySelector('textarea');
                const label = form.querySelector('label[for^="reason"]');

                if (this.value === 'rejected') {
                    textarea.required = true;
                    label.innerHTML = 'เหตุผลการไม่อนุมัติ: <span style="color: red;">*</span>';
                    textarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
                } else {
                    textarea.required = false;
                    label.innerHTML = 'เหตุผล/ข้อเสนอแนะ:';
                    textarea.placeholder = 'ระบุเหตุผลหรือข้อเสนอแนะ (ไม่บังคับ)';
                }
            });
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