<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
  header("Location: ../index.php");
  exit();
}

$assignor_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// ดึงรายการคำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่ายแล้ว
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
        dn.document_number,
        
        -- Division Manager Info
        dma.reason as div_mgr_reason,
        dma.reviewed_at as div_mgr_reviewed_at,
        div_mgr.name as div_mgr_name,
        
        -- Service Info
        s.name as service_name,
        s.category as service_category
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    WHERE dma.status = 'approved' 
    AND (aa.id IS NULL OR aa.status = 'pending')
    ORDER BY dma.reviewed_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อ developers
$dev_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'developer' AND is_active = 1 ORDER BY name");
$dev_stmt->execute();
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการ services ประเภท development
$services_stmt = $conn->prepare("SELECT * FROM services WHERE category = 'development' AND is_active = 1 ORDER BY name");
$services_stmt->execute();
$development_services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
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

    .request-card {
      background: white;
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      border-left: 5px solid #10b981;

      max-width: 1200px;
      /* กำหนดความกว้างสูงสุด */
      margin-left: auto;
      margin-right: auto;
      /* จัดให้อยู่ตรงกลาง */

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
      grid-template-columns: repeat(3, 1fr);
      /* แถวละ 3 */
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

    .approval-info {
      background: #d1fae5;
      border-radius: 12px;
      padding: 15px;
      margin: 15px 0;
      border-left: 4px solid #10b981;
    }

    .approval-form {
      background: #f8f9fa;
      border-radius: 15px;
      padding: 25px;
      margin-top: 20px;
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #4a5568;
    }

    .form-control,
    .form-select {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 12px 15px;
      transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }

    .radio-group {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .radio-option {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }

    .approve-option {
      background: #d1fae5;
      color: #065f46;
    }

    .approve-option:hover {
      border-color: #10b981;
    }

    .reject-option {
      background: #fee2e2;
      color: #991b1b;
    }

    .reject-option:hover {
      border-color: #ef4444;
    }

    .submit-btn {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      border: none;
      padding: 15px 30px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
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
        text-align: center;
      }

      .container {
        padding: 1rem;
      }

      .user-info-grid {
        grid-template-columns: 1fr;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .radio-group {
        flex-direction: column;
        gap: 10px;
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
            <li class="nav-item active">
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




        <div class="container">



          <!-- Content -->
          <div class="glass-card p-4">
            <div class="d-flex align-items-center mb-4">
              <i class="fas fa-clipboard-check text-success me-3 fs-3"></i>
              <h2 class="mb-0 fw-bold">คำขอที่รอการอนุมัติอยู่</h2>
            </div>

            <?php if (empty($requests)): ?>
              <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3 class="fw-bold mb-3">ไม่มีคำขอที่รอการพิจารณา</h3>
                <p class="fs-5">ขณะนี้ไม่มีคำขอที่ผ่านการอนุมัติจากผู้จัดการฝ่าย</p>
              </div>
            <?php else: ?>
              <?php foreach ($requests as $req): ?>

                <div class="request-card">
                  <div class="">
                    <!-- บรรทัดแรก: เลขที่เอกสาร + วันที่ -->
                    <div class="d-flex justify-content-between text-muted mb-2 flex-wrap">

                      <div>
                        <?php if (!empty($req['service_name'])): ?>
                          <div class="text-secondary">
                            <?php if ($req['service_category'] === 'development'): ?>
                              <i class="fas fa-code me-1"></i>
                            <?php else: ?>
                              <i class="fas fa-tools me-1"></i>
                            <?php endif; ?>
                            <strong>ประเภทคำขอ: <?= htmlspecialchars($req['service_name']) ?></strong>
                          </div>
                        <?php endif; ?>


                        <span class="me-3">
                          <i class="fas fa-file-alt me-1"></i>
                          เลขที่: <?= htmlspecialchars($req['document_number'] ?? '-') ?>
                        </span>
                      </div>


                    </div>


                  </div>

                  <h6 class="fw-bold text-info mb-3">
                    <i class=""></i>ข้อมูลผู้ขอ
                  </h6>
                  <!-- ข้อมูลผู้ขอ -->
                  <div class="row g-3">
                    <div class="col-6">
                      <small class="text-muted">รหัสพนักงาน</small>
                      <div class="fw-bold"><?= htmlspecialchars($req['employee_id'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">ชื่อ-นามสกุล</small>
                      <div class="fw-bold"><?= htmlspecialchars($req['requester_name'] . ' ' . $req['requester_lastname']) ?></div>
                    </div>

                    <div class="col-6">
                      <small class="text-muted">ตำแหน่ง</small>
                      <div class="fw-bold"><?= htmlspecialchars($req['position'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">หน่วยงาน</small>
                      <div class="fw-bold"><?= htmlspecialchars($req['department'] ?? 'ไม่ระบุ') ?></div>
                    </div>

                    <div class="col-6">
                      <small class="text-muted">เบอร์โทร</small>
                      <div class="fw-bold"><?= htmlspecialchars($req['phone'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">อีเมล</small>
                      <div class="fw-bold"><?= htmlspecialchars($req['email'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                  </div>
                  <br>



                  <?php if ($req['service_category'] === 'development'): ?>
                    <div>
                      <!-- <i class="fas fa-code me-2"></i>ข้อมูล Development
                      </h6> -->
                      <h5 class="fw-bold text-dark mb-2">
                        หัวข้อ: <?= htmlspecialchars($req['title'] ?? '-') ?>
                      </h5>

                      <div class="row">
                        <?php
                        $fields = [
                          'program_purpose' => 'วัตถุประสงค์',
                          'target_users' => 'กลุ่มผู้ใช้งาน',
                          'main_functions' => 'ฟังก์ชันหลัก',
                          'data_requirements' => 'ข้อมูลที่ต้องใช้',
                          'current_program_name' => 'โปรแกรมที่มีปัญหา',
                          'problem_description' => 'รายละเอียดปัญหา',
                          'error_frequency' => 'ความถี่ของปัญหา',
                          'steps_to_reproduce' => 'ขั้นตอนการทำให้เกิดปัญหา',
                          'program_name_change' => 'โปรแกรมที่ต้องการเปลี่ยนข้อมูล',
                          'data_to_change' => 'ข้อมูลที่ต้องการเปลี่ยน',
                          'new_data_value' => 'ข้อมูลใหม่ที่ต้องการ',
                          'change_reason' => 'เหตุผลในการเปลี่ยนแปลง',
                          'program_name_function' => 'โปรแกรมที่ต้องการเพิ่มฟังก์ชั่น',
                          'new_functions' => 'ฟังก์ชั่นใหม่ที่ต้องการ',
                          'function_benefits' => 'ประโยชน์ของฟังก์ชั่นใหม่',
                          'integration_requirements' => 'ความต้องการเชื่อมต่อ',
                          'program_name_decorate' => 'โปรแกรมที่ต้องการตกแต่ง',
                          'decoration_type' => 'ประเภทการตกแต่ง',
                          'reference_examples' => 'ตัวอย่างอ้างอิง',
                          'current_workflow' => 'ขั้นตอนการทำงานเดิม',
                          'approach_ideas' => 'แนวทาง/ไอเดีย',
                          'related_programs' => 'โปรแกรมที่คาดว่าจะเกี่ยวข้อง',
                          'current_tools' => 'ปกติใช้โปรแกรมอะไรทำงานอยู่',
                          'system_impact' => 'ผลกระทบต่อระบบ',
                          'related_documents' => 'เอกสารการทำงานที่เกี่ยวข้อง',
                        ];

                        foreach ($fields as $key => $label):
                          if (!empty($req[$key])):
                        ?>
                            <div class="col-md-6 mb-3">
                              <strong><?= $label ?>:</strong><br>
                              <?= nl2br(htmlspecialchars($req[$key])) ?>
                            </div>
                        <?php
                          endif;
                        endforeach;
                        ?>
                      </div>
                    </div>
                  <?php endif; ?>
                  <?php if ($req['expected_benefits']): ?>

                    <h6 class="fw-bold text-success mb-2">
                      <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                      <p class="mb-0"><?= nl2br(htmlspecialchars($req['expected_benefits'])) ?></p>
                    </h6>
                  <?php endif; ?>
                  <div>
                    <i class="fas fa-calendar me-1"></i>
                    วันที่ขอดำเนินเรื่อง: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                  </div>

                  <?php
                  // แสดงไฟล์แนบ
                  require_once __DIR__ . '/../includes/attachment_display.php';
                  displayAttachments($req['id']);
                  ?>

                  <div class="approval-info">
                    <h6 class="fw-bold mb-2">
                      <i class="fas fa-check-circle me-2"></i>อนุมัติโดยผู้จัดการฝ่าย
                    </h6>
                    <p class="mb-1">
                      <strong>โดยคุณ:</strong> <?= htmlspecialchars($req['div_mgr_name']) ?>
                      <strong class="ms-3">เมื่อ:</strong> <?= date('d/m/Y H:i', strtotime($req['div_mgr_reviewed_at'])) ?>
                    </p>
                    <?php if ($req['div_mgr_reason']): ?>
                      <p class="mb-0"><strong>หมายเหตุ:</strong> <?= htmlspecialchars($req['div_mgr_reason']) ?></p>
                    <?php endif; ?>
                  </div>


                  <form method="post" action="assign_status.php"
                    class=" ">

                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">


                    <div class="row g-4">
                      <!-- มอบหมายผู้พัฒนา -->
                      <div class="col-md-6">
                        <label for="developer_<?= $req['id'] ?>" class="form-label fw-semibold">
                          <i class="fas fa-user-cog me-2 text-primary"></i>มอบหมายให้ผู้พัฒนา
                        </label>
                        <select name="assigned_developer_id" id="developer_<?= $req['id'] ?>"
                          class="form-select shadow-sm" required>
                          <option value="">-- เลือกผู้พัฒนา --</option>
                          <?php foreach ($developers as $dev): ?>
                            <option value="<?= $dev['id'] ?>">
                              <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <!-- ระดับความสำคัญ -->
                      <div class="col-md-6">
                        <label for="priority_<?= $req['id'] ?>" class="form-label fw-semibold">
                          <i class="fas fa-exclamation-circle me-2 text-danger"></i>ระดับความสำคัญ
                        </label>
                        <select name="priority_level" id="priority_<?= $req['id'] ?>"
                          class="form-select shadow-sm">
                          <option value="low">ต่ำ</option>
                          <option value="medium" selected>ปานกลาง</option>
                          <option value="high">สูง</option>
                          <option value="urgent">เร่งด่วน</option>
                        </select>
                      </div>

                      <!-- งบประมาณ -->
                      <div class="col-md-6">
                        <label for="budget_approved_<?= $req['id'] ?>" class="form-label fw-semibold">
                          <i class="fas fa-coins me-2 text-warning"></i>งบประมาณ (ถ้ามี)
                        </label>
                        <div class="input-group shadow-sm">
                          <span class="input-group-text bg-light"><i class="fas fa-coins text-warning"></i></span>
                          <input type="number" name="budget_approved"
                            id="budget_approved_<?= $req['id'] ?>"
                            class="form-control" step="0.01" min="0"
                            placeholder="ระบุจำนวนงบประมาณ (ถ้ามี)">
                        </div>
                      </div>

                      <!-- เวลาที่ใช้ -->
                      <div class="col-md-6">
                        <label for="estimated_days_<?= $req['id'] ?>" class="form-label fw-semibold">
                          <i class="fas fa-calendar-alt me-2 text-info"></i>ประมาณการเวลา (วัน)
                        </label>
                        <input type="number" name="estimated_days"
                          id="estimated_days_<?= $req['id'] ?>"
                          class="form-control shadow-sm" min="1" max="365"
                          placeholder="จำนวนวันที่คาดว่าจะใช้">
                      </div>

                      <!-- กำหนดเสร็จ -->
                      <div class="col-md-6">
                        <label for="deadline_<?= $req['id'] ?>" class="form-label fw-semibold">
                          <i class="fas fa-clock me-2 text-primary"></i>กำหนดเสร็จ
                        </label>
                        <div class="input-group shadow-sm">
                          <span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-primary"></i></span>
                          <input type="datetime-local" name="deadline"
                            id="deadline_<?= $req['id'] ?>"
                            class="form-control">
                        </div>
                        <div class="form-text text-muted">เลือกวันและเวลาที่ต้องการให้เสร็จงาน</div>
                      </div>

                      <!-- การพิจารณา -->
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">การพิจารณา:</label>
                        <div class="d-flex flex-column gap-2">
                          <label class="form-check-label p-2 border rounded shadow-sm">
                            <input type="radio" name="status" value="approved" class="form-check-input me-2" required>
                            <i class="fas fa-check-circle text-success me-1"></i> อนุมัติและมอบหมายงาน
                          </label>
                          <label class="form-check-label p-2 border rounded shadow-sm">
                            <input type="radio" name="status" value="rejected" class="form-check-input me-2" required>
                            <i class="fas fa-times-circle text-danger me-1"></i> ไม่อนุมัติ
                          </label>
                        </div>
                      </div>

                      <!-- เหตุผล -->
                      <div class="col-12">
                        <label for="reason_<?= $req['id'] ?>" class="form-label fw-semibold">
                          <i class="fas fa-comment-dots me-2 text-secondary"></i>เหตุผล/ข้อเสนอแนะ
                        </label>
                        <textarea name="reason" id="reason_<?= $req['id'] ?>"
                          class="form-control shadow-sm" rows="3"
                          placeholder="ระบุเหตุผลหรือข้อเสนอแนะ"></textarea>
                      </div>

                      <!-- ปุ่มส่ง -->
                      <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm rounded-pill">
                          <i class="fas fa-paper-plane me-2"></i> ส่งผลการพิจารณา
                        </button>
                      </div>

                    </div>
                  </form>


                </div>
              <?php endforeach; ?>
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
 <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
 

<script>
  // จัดการฟอร์มตามการเลือก approved/rejected
  document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const form = this.closest('form');
      const developerSelect = form.querySelector('select[name="assigned_developer_id"]');
      const prioritySelect = form.querySelector('select[name="priority_level"]');
      const daysInput = form.querySelector('input[name="estimated_days"]');
      const textarea = form.querySelector('textarea[name="reason"]');
      const devServiceSelect = form.querySelector('select[name="development_service_id"]');

      if (this.value === 'approved') {
        if (devServiceSelect) {
          devServiceSelect.required = true;
          devServiceSelect.disabled = false;
        }
        developerSelect.required = true;
        developerSelect.disabled = false;
        prioritySelect.disabled = false;
        daysInput.disabled = false;
        textarea.required = false;
        textarea.placeholder = 'ข้อเสนอแนะหรือคำแนะนำสำหรับผู้พัฒนา';
      } else { // rejected
        if (devServiceSelect) {
          devServiceSelect.required = false;
          devServiceSelect.disabled = true;
        }
        developerSelect.required = false;
        developerSelect.disabled = true;
        prioritySelect.disabled = true;
        daysInput.disabled = true;
        textarea.required = true;
        textarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
      }
    });
  });

  // SweetAlert ก่อน submit ฟอร์ม
  document.querySelectorAll("form[action='assign_status.php']").forEach(form => {
    form.addEventListener("submit", function(e) {
      e.preventDefault(); // กันไม่ให้ submit ทันที

      swal("Good job!", "ขอบคุณที่ใช้บริการ BobbyCare", {
        icon: "success",
        buttons: {
          confirm: {
            text: "ตกลง",
            className: "btn btn-success",
          },
        },
      }).then((willSubmit) => {
        if (willSubmit) {
          form.submit(); // submit จริงเมื่อกดตกลง
        }
      });
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