<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'divmgr') {
  header("Location: ../index2.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$picture_url = $_SESSION['picture_url'] ?? null;

// ดึงรายการคำขอที่ยังไม่ได้รับการพิจารณาจาก div_mgr
$stmt = $conn->prepare("
    SELECT sr.*, u.name, u.lastname, u.employee_id, u.position, u.department, u.phone, u.email,
           s.name as service_name, s.category as service_category,
           dn.document_number,
           dn.created_at as document_created_at,
           (SELECT COUNT(*) FROM request_attachments WHERE service_request_id = sr.id) as attachment_count
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    LEFT JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id

    WHERE sr.status = 'pending'
    AND sr.assigned_div_mgr_id = ?
    AND (dma.id IS NULL OR dma.status = 'div_mgr_review')
    ORDER BY sr.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);



// บันทึกข้อมูลในฐานข้อมูล  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $request_id = $_POST['request_id'];
  $status = $_POST['status'];
  $document_number = trim($_POST['document_number'] ?? '');
  $reason = trim($_POST['reason'] ?? '');

  if ($status === 'rejected' && $reason === '') {
    $error = "กรุณาระบุเหตุผลเมื่อไม่อนุมัติ";
  } else {
    try {
      $conn->beginTransaction();

      // บันทึกการอนุมัติ
      $stmt = $conn->prepare("
                INSERT INTO div_mgr_approvals (service_request_id, div_mgr_user_id, status, reason, reviewed_at, document_number) 
                VALUES (?, ?, ?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    reason = VALUES(reason),
                    document_number = VALUES(document_number), 
                    reviewed_at = NOW()
            ");
      $stmt->execute([$request_id, $user_id, $status, $reason, $document_number]);

      // อัปเดตสถานะ
      $new_status = $status === 'approved' ? 'assignor_review' : 'rejected';
      $stmt = $conn->prepare("UPDATE service_requests SET status = ?, current_step = ? WHERE id = ?");
      $stmt->execute([$new_status, $status === 'approved' ? 'div_mgr_approved' : 'div_mgr_rejected', $request_id]);

      // บันทึก log
      $stmt = $conn->prepare("
                INSERT INTO document_status_logs (service_request_id, step_name, status, reviewer_id, reviewer_role, notes) 
                VALUES (?, 'div_mgr_review', ?, ?, 'divmgr', ?)
            ");
      $stmt->execute([$request_id, $status, $user_id, $reason]);

      $conn->commit();
      header("Location: index.php");
      exit();
    } catch (Exception $e) {
      $conn->rollBack();
      $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
  }
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
      border-left: 5px solid #667eea;
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

    .approval-form {
      background: #f8f9fa;
      border-radius: 15px;
      padding: 20px;
      margin-top: 20px;
    }

    .radio-group {
      display: flex;
      gap: 20px;
      margin-bottom: 15px;
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

    .form-control {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 12px 15px;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }

    .submit-btn {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      border: none;
      padding: 12px 30px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
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

      .user-info-grid {
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
            <li class="nav-item">
              <a data-bs-toggle="collapse" href="index2.php" class="collapsed" aria-expanded="false">
                <i class="fas fa-home"></i>
                <p>หน้าหลัก</p>
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
                    <span class="op-7">ผู้จัดการฝ่ายคุณคุณ:</span>
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




        <div class="container mt-5">


          <!-- Content -->
          <div class="glass-card p-4">
            <div class="d-flex align-items-center mb-4">
              <i class="fas fa-clipboard-list text-primary me-3 fs-3"></i>
              <h2 class="mb-0 fw-bold">รายการคำขอที่รอการพิจารณา</h2>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-3"></i>
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3 class="fw-bold mb-3">ไม่มีคำขอที่รอการพิจารณา</h3>
                <p class="fs-5">ขณะนี้ไม่มีคำขอใหม่ที่ต้องการการอนุมัติจากคุณ</p>
              </div>


            <?php else: ?>
              <?php foreach ($requests as $req): ?>
                <div class="request-card">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1">

                      <!-- ข้อมูลเลขที่เอกสาร -->
                      <?php if (!empty($req['document_number'])): ?>
                        <div class="text-muted mb-2">
                          <i class="fas fa-file-alt me-1"></i> เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?>
                        </div>
                        <!-- ส่งค่า document_number ไปใน form ด้วย -->
                        <input type="hidden" name="document_number" value="<?= htmlspecialchars($req['document_number']) ?>">
                      <?php endif; ?>


                      <!-- หัวข้อ -->
                      <div class="request-title">หัวข้อ : <?= htmlspecialchars($req['title']) ?></div>

                      <!-- ประเภทบริการ -->
                      <div class="d-flex gap-2 mb-2">
                        <?php if ($req['service_name']): ?>
                          <span class="service-badge service-<?= $req['service_category'] ?>">
                            <?php if ($req['service_category'] === 'development'): ?>
                              <i class="fas fa-code me-1"></i>
                            <?php else: ?>
                              <i class="fas fa-tools me-1"></i>
                            <?php endif; ?>
                            ประเภทคำขอ : <?= htmlspecialchars($req['service_name']) ?>
                          </span>
                        <?php endif; ?>
                      </div>


                    </div>
                    <!-- เอกสารสร้างเมื่อ -->
                     <div class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                วันที่ขอดำเนินเรื่อง: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
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
                        <div class="fw-bold"><?= htmlspecialchars($req['name'] . ' ' . $req['lastname']) ?></div>
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


                  <?php if ($req['service_category'] === 'development'): ?>


                    <h6 class="fw-bold text-info mb-3">
                      <i class="fas fa-code me-2"></i>ข้อมูล Development
                    </h6>
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
                  <?php endif; ?>
                  
                  <?php if ($req['expected_benefits']): ?>

                    <h6 class="fw-bold text-success mb-2">
                      <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                    </h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($req['expected_benefits'])) ?></p>

                  <?php endif; ?>



                  <?php if ($req['attachment_count'] > 0): ?>
                    <div class="mt-3">
                      <span class="badge bg-info">
                        <i class="fas fa-paperclip me-1"></i>
                        <?= $req['attachment_count'] ?> ไฟล์แนบ
                      </span>
                    </div>
                  <?php endif; ?>

                  <?php
                  // แสดงไฟล์แนบ
                  require_once __DIR__ . '/../includes/attachment_display.php';
                  displayAttachments($req['id']);
                  ?>

                  <form method="post" class="approval-form">
                    <?php if (!empty($req['document_number'])): ?>
                      <input type="hidden" name="document_number" value="<?= htmlspecialchars($req['document_number']) ?>">
                    <?php endif; ?>

                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">

                    
                    <h5 class="fw-bold mb-3">
                      <i class="fas fa-gavel me-2"></i>การพิจารณา
                    </h5>

                    <div class="mb-3">
                      <div class="radio-group">
                        <label class="radio-option approve-option">
                          <input type="radio" name="status" value="approved" required>
                          <i class="fas fa-check-circle me-2"></i>
                          อนุมัติ
                        </label>
                        <label class="radio-option reject-option">
                          <input type="radio" name="status" value="rejected" required>
                          <i class="fas fa-times-circle me-2"></i>
                          ไม่อนุมัติ
                        </label>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="reason_<?= $req['id'] ?>" class="form-label">เหตุผล/ข้อเสนอแนะ:</label>
                      <textarea
                        name="reason"
                        id="reason_<?= $req['id'] ?>"
                        class="form-control"
                        rows="3"
                        placeholder="ระบุเหตุผลหรือข้อเสนอแนะ (จำเป็นเมื่อไม่อนุมัติ)"></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                      <i class="fas fa-paper-plane me-2"></i>
                      ส่งผลการพิจารณา
                    </button>
                  </form>
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
</body>

</html>