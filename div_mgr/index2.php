<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'divmgr') {
  header("Location: ../index.php");
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



// ฟังก์ชันส่งข้อความเข้า LINE Official Account

function sendLinePushFlex($toUserId, $req)
{
  $access_token = "hAfRJZ7KyjncT3I2IB6UhHqU/DmP1qPxW2PbeDE7KtUUveyiSKgLvJxrahWyrFUmlrta4MAnw8V3QRr5b7LwoKYh4hv1ATfX8yrJOMFQ+zdQxm3rScAAGNaJTEN1mJxHN93jHbqLoK8dQ080ja5BFAdB04t89/1O/w1cDnyilFU="; // ใส่ Channel access token (long-lived)

  $url = "https://api.line.me/v2/bot/message/push";

  $bubble = [
    "type" => "bubble",
    "size" => "mega",
    "header" => [
      "type" => "box",
      "layout" => "vertical",
      "contents" => [
        [
          "type" => "text",
          "text" => "📑 เอกสารใหม่",
          "weight" => "bold",
          "size" => "lg",
          "align" => "center",
          "color" => "#ffffffff"
        ],
        [
          "type" => "text",
          "text" => $req['document_number'] ?? "-",
          "size" => "md",
          "align" => "center",
          "color" => "#FFFFFF",
          "margin" => "md"
        ]
      ],
      "backgroundColor" => "#5677fc",
      "paddingAll" => "20px"
    ],
    "body" => [
      "type" => "box",
      "layout" => "vertical",
      "spacing" => "md",
      "contents" => [
        ["type" => "text", "text" => "📌 เรื่อง: {$req['title']}", "wrap" => true, "weight" => "bold", "size" => "sm", "color" => "#333333"],
        ["type" => "text", "text" => "📝 {$req['description']}", "wrap" => true, "size" => "sm", "color" => "#666666"],
        ["type" => "text", "text" => "✨ ประโยชน์: {$req['expected_benefits']}", "wrap" => true, "size" => "sm", "color" => "#32CD32"],
        ["type" => "separator", "margin" => "md"],
        ["type" => "text", "text" => "ผู้ขอบริการ : {$req['name']} {$req['lastname']}", "size" => "sm", "color" => "#000000"],
        ["type" => "text", "text" => "🆔 {$req['employee_id']} | 🏢 {$req['department']}", "size" => "sm", "color" => "#444444"]
      ]
    ],
    "footer" => [
      "type" => "box",
      "layout" => "vertical",
      "contents" => [
        [
          "type" => "button",
          "style" => "primary",
          "color" => "#d0d9ff",
          "action" => [
            "type" => "uri",
            "label" => "🔎 ดูรายละเอียด",
            "uri" => "http://yourdomain/index2.php?id={$req['request_id']}"
          ]
        ]
      ],
      "backgroundColor" => "#5677fc"
    ]
  ];

  $flexMessage = [
    "type" => "flex",
    "altText" => "📑 มีคำขอเอกสารใหม่",
    "contents" => $bubble
  ];

  $data = [
    "to" => $toUserId,
    "messages" => [$flexMessage]
  ];

  $post = json_encode($data, JSON_UNESCAPED_UNICODE);
  $headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
  ];

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  curl_close($ch);

  return $result;
}




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


      // === ส่งแจ้งเตือน LINE Official Account ไปยัง Assignor + Developer ===

      // ดึงข้อมูล service request + user ที่สร้าง
      $req_stmt = $conn->prepare("
    SELECT sr.title, sr.description, sr.expected_benefits, dn.document_number,
           u.name, u.lastname, u.employee_id, u.department
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    WHERE sr.id = ?
");
      $req_stmt->execute([$request_id]);
      $req = $req_stmt->fetch(PDO::FETCH_ASSOC);

      if ($req) {
        $title = $req['title'] ?? '-';
        $description = $req['description'] ?? '-';
        $expected_benefits = $req['expected_benefits'] ?? '-';
        $document_number = $req['document_number'] ?? '-';
        $user_name = $req['name'] ?? '';
        $user_lastname = $req['lastname'] ?? '';
        $employee_id = $req['employee_id'] ?? '';
        $department = $req['department'] ?? '';

        $payload = [
          'document_number'   => $document_number,
          'title'             => $title,
          'description'       => $description,
          'expected_benefits' => $expected_benefits,
          'name'              => $user_name,
          'lastname'          => $user_lastname,
          'employee_id'       => $employee_id,
          'department'        => $department,
          'request_id'        => $request_id
        ];

        // --- ส่งหา Assignor ---
        $assignor_stmt = $conn->prepare("SELECT line_id FROM users WHERE role = 'assignor' AND is_active = 1");
        $assignor_stmt->execute();
        $assignors = $assignor_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($assignors as $assignor) {
          if (!empty($assignor['line_id'])) {
            sendLinePushFlex($assignor['line_id'], $payload);
          }
        }

        // --- ส่งหา Developer ---
        $dev_stmt = $conn->prepare("SELECT line_id FROM users WHERE role = 'developer' AND is_active = 1");
        $dev_stmt->execute();
        $developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($developers as $dev) {
          if (!empty($dev['line_id'])) {
            sendLinePushFlex($dev['line_id'], $payload);
          }
        }
      }

      header("Location: index2.php");
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
                    <span class="op-7">ผู้จัดการฝ่ายคุณ:</span>
                    <span class="fw-bold"><?= htmlspecialchars($_SESSION['name']) ?></span>
                  </span>
                </a>
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
                  <div class="">
                    <!-- บรรทัดแรก: เลขที่เอกสาร + วันที่ -->
                    <div class="d-flex justify-content-between mb-2 flex-wrap">

                      <div>
                        <?php if (!empty($req['service_name'])): ?>
                          <div class="text-dark fw-bold fs-4">
                            <?php if ($req['service_category'] === 'development'): ?>
                              <i class="fas fa-code me-1"></i>
                            <?php else: ?>
                              <i class="fas fa-tools me-1"></i>
                            <?php endif; ?>
                            ประเภทคำขอ: <?= htmlspecialchars($req['service_name']) ?>
                          </div>
                        <?php endif; ?>

                        <span class="me-3 fw-bold text-dark fs-5">
                          <i class="fas fa-file-alt me-1"></i>
                          เลขที่: <?= htmlspecialchars($req['document_number'] ?? '-') ?>
                        </span>
                      </div>

                    </div>
                  </div>


                  <h5 class="fw-bold text-info mb-3">
                    <i class="fas fa-user me-2"></i> ข้อมูลผู้ขอ
                  </h5>

                  <!-- ข้อมูลผู้ขอ -->
                  <div class="row g-3">
                    <div class="col-6">
                      <small class="text-muted">รหัสพนักงาน</small>
                      <div class="fw-bold fs-5"><?= htmlspecialchars($req['employee_id'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">ชื่อ-นามสกุล</small>
                      <div class="fw-bold fs-5"><?= htmlspecialchars($req['name'] . ' ' . $req['lastname']) ?></div>
                    </div>

                    <div class="col-6">
                      <small class="text-muted">ตำแหน่ง</small>
                      <div class="fw-bold fs-5"><?= htmlspecialchars($req['position'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">หน่วยงาน</small>
                      <div class="fw-bold fs-5"><?= htmlspecialchars($req['department'] ?? 'ไม่ระบุ') ?></div>
                    </div>

                    <div class="col-6">
                      <small class="text-muted">เบอร์โทร</small>
                      <div class="fw-bold fs-5"><?= htmlspecialchars($req['phone'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">อีเมล</small>
                      <div class="fw-bold fs-5"><?= htmlspecialchars($req['email'] ?? 'ไม่ระบุ') ?></div>
                    </div>
                  </div>
                  <br>


                  <?php if ($req['service_category'] === 'development'): ?>

                    <!-- หัวข้อ -->
                    <h4 class="fw-bold text-dark mb-3">
                      <i class="fas fa-code text-primary me-2"></i>
                      หัวข้อ: <?= htmlspecialchars($req['title'] ?? '-') ?>
                    </h4>

                    <!-- รายละเอียดฟิลด์ -->
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
                            <div class="p-3 border rounded bg-light h-100">
                              <strong class="text-secondary"><?= $label ?>:</strong><br>
                              <span class="fw-bold"><?= nl2br(htmlspecialchars($req[$key])) ?></span>
                            </div>
                          </div>
                      <?php
                        endif;
                      endforeach;
                      ?>
                    </div>
                  <?php endif; ?>

                  <!-- ประโยชน์ที่คาดว่าจะได้รับ -->
                  <?php if ($req['expected_benefits']): ?>
                    <div class="mt-3 p-3 border-start border-4 border-success bg-light rounded">
                      <h5 class="fw-bold text-success mb-2">
                        <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                      </h5>
                      <p class="mb-0"><?= nl2br(htmlspecialchars($req['expected_benefits'])) ?></p>
                    </div>
                  <?php endif; ?>

                  <!-- วันที่ -->
                  <div class="mt-3 text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    วันที่ขอดำเนินเรื่อง:
                    <span class="fw-bold"><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></span>
                  </div>


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

  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

  <script>
    document.querySelectorAll(".approval-form").forEach(form => {
      form.addEventListener("submit", function(e) {
        e.preventDefault(); // กันไม่ให้ฟอร์ม submit ทันที

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
            form.submit(); // ส่งฟอร์มจริงเมื่อกด ตกลง
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