<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'divmgr') {
  header("Location: ../index.php");
  exit();
}

$user_id = $_SESSION['user_id'];

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
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">

  <title>BobbyCareDev</title>
  <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.0.0" rel="stylesheet" />

  <!-- ===================== Pending Requests (Auryx UI v2) ===================== -->
 <style>
  /* AURYX v2 — super-unique prefix to avoid collisions */
  .auryx { --ax-bg:#f7f9fc; --ax-card:#fff; --ax-border:#e7e9ee; --ax-ink:#0f172a; --ax-muted:#6b7280; --ax-primary:#5b5bd6; --ax-primary-2:#7a7af2; --ax-info:#0ea5e9; --ax-success:#10b981; --ax-danger:#ef4444; --ax-warn:#f59e0b; }

  .auryx .auryx-shell { background: var(--ax-card); border:1px solid var(--ax-border); border-radius: 1rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .06); padding: 1.25rem; }
  .auryx .auryx-shell + .auryx-shell { margin-top: 1rem; }

  .auryx-empty { text-align:center; color:var(--ax-muted); padding: 2.2rem 1rem; }
  .auryx-empty i { font-size:2.6rem; opacity:.85; margin-bottom:.5rem; }

  /* Card */
  .auryx-card { background: var(--ax-card); border:1px solid var(--ax-border); border-radius: .9rem; padding:1rem; transition: box-shadow .2s ease, transform .2s ease; }
  .auryx-card + .auryx-card { margin-top:.9rem; }
  .auryx-card:hover { box-shadow: 0 12px 28px rgba(15, 23, 42, .08); transform: translateY(-1px); }

  .auryx-head { display:flex; justify-content:space-between; gap:1rem; margin-bottom:.25rem; }
  .auryx-title { font-size:1.05rem; font-weight:800; margin:0; line-height:1.35; word-break:break-word; }
  .auryx-doc { color:var(--ax-muted); font-size:.9rem; }
  .auryx-sub { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-top:.25rem; }

  .auryx-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.22rem .55rem; font-weight:700; font-size:.78rem; line-height:1; border-radius:.6rem; border:1px solid transparent; }
  .auryx-badge.dev { background:#eef2ff; color:#4338ca; border-color:#e0e7ff; }
  .auryx-badge.ops { background:#eff6ff; color:#1d4ed8; border-color:#dbeafe; }

  .auryx-stamp { color:var(--ax-muted); font-size:.9rem; white-space:nowrap; }

  /* Meta chips (compact instead of big boxes) */
  .auryx-meta { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.4rem; }
  .auryx-meta .meta { display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .5rem; background:var(--ax-bg); border:1px solid var(--ax-border); border-radius:.6rem; font-size:.85rem; }
  .auryx-meta .meta i { font-size:.9rem; color:#5b5bd6; }
  .auryx-meta .label { color:var(--ax-muted); }
  .auryx-meta .val { font-weight:700; }

  /* Detail box (collapsed by default to shorten page) */
  .auryx-detail { background:#f9fafb; border-radius:.75rem; border-left:4px solid var(--ax-info); padding:.9rem; margin-top:.6rem; }
  .auryx-detail h6 { font-weight:800; color:var(--ax-info); margin-bottom:.5rem; }

  /* Attachments — compact */
  .auryx-attach { margin-top:.35rem; }
  .auryx-attach--compact ul { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:.4rem; }
  .auryx-attach--compact li { margin:0; }
  .auryx-attach--compact a, .auryx-attach--compact .attachment-link { display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .5rem; border:1px solid var(--ax-border); border-radius:.5rem; font-size:.82rem; text-decoration:none; }
  .auryx-attach--compact img { max-width:28px; height:28px; object-fit:cover; border-radius:.35rem; border:1px solid var(--ax-border); }

  /* Approve form */
  .auryx-approve { margin-top:.75rem; border-top:1px dashed var(--ax-border); padding-top:.8rem; }
  .auryx-approve h5 { font-weight:800; margin-bottom:.55rem; }
  .auryx-options { display:flex; gap:.6rem; flex-wrap:wrap; }
  .auryx-opt { position:relative; display:inline-flex; align-items:center; gap:.5rem; padding:.45rem .8rem; border-radius:.7rem; border:1px solid var(--ax-border); font-weight:800; cursor:pointer; user-select:none; }
  .auryx-opt input { position:absolute; inset:0; opacity:0; cursor:pointer; }
  .auryx-opt.approve { background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.35); color:var(--ax-success); }
  .auryx-opt.reject  { background:rgba(239,68,68,.08); border-color:rgba(239,68,68,.35); color:var(--ax-danger); }
  .auryx-textarea { resize:vertical; }
  .auryx-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.5rem 1rem; border-radius:.65rem; background:linear-gradient(135deg, var(--ax-primary), var(--ax-primary-2)); color:#fff; border:none; font-weight:800; }
  .auryx-btn:hover { filter:brightness(1.05); color:#fff; }

  /* Load more */
  .auryx-load { text-align:center; margin-top:.9rem; }

  /* Responsive */
  @media (max-width: 575.98px) {
    .auryx-head { flex-direction:column; align-items:flex-start; }
  }
  @media (prefers-reduced-motion: reduce) { .auryx-card, .auryx-shell { transition:none; } }

    .auryx-opt input { display: none; }
.auryx-opt { padding: 8px 12px; border: 2px solid #ccc; border-radius: 6px; cursor: pointer; }
.auryx-opt input:checked + i, 
.auryx-opt input:checked + i + span { color: #fff; }
.auryx-opt.approve input:checked + i { background: #28a745; }
.auryx-opt.reject input:checked + i { background: #dc3545; }

  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-0" target="_blank">
        <img src="../img/logo/bobby-full.png" class="navbar-brand-img h-100" alt="main_logo">
      </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto  max-height-vh-100" id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item">
           <a class="nav-link text-white active bg-gradient-primary " href="index2.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">dashboard</i>
            </div>
            <span class="nav-link-text ms-1">หน้าหลัก</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="view_logs.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">table_view</i>
            </div>
            <span class="nav-link-text ms-1">ประวัติการอนุมัติ</span>
          </a>
        </li>


        <li class="nav-item mt-3">
          <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Account pages</h6>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../profile.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">person</i>
            </div>
            <span class="nav-link-text ms-1">Profile</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../logout.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">login</i>
            </div>
            <span class="nav-link-text ms-1">Logout</span>
          </a>
        </li>
      </ul>
    </div>
    <div class="sidenav-footer position-absolute w-100 bottom-0 ">
     
    </div>
  </aside>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">index</li>
          </ol>
          <h6 class="font-weight-bolder mb-0">หน้าหลัก</h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">

          <ul class="navbar-nav  justify-content-end">
            <li class="nav-item d-flex align-items-center">
            </li>
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner">
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                </div>
              </a>
            </li>
            <li class="nav-item px-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0">
                <i class="fa fa-cog fixed-plugin-button-nav cursor-pointer"></i>
              </a>
            </li>

          </ul>
        </div>
      </div>
    </nav>
    <!-- End Navbar -->



    <div class="container mt-5">

      <div class="auryx">
        <div class="d-flex align-items-center mb-3">

          <h2 class="ms-2 fw-bold mb-0">รายการคำขอที่รอการพิจารณา</h2>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-3"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
          <div class="auryx-shell auryx-empty">
            <i class="fas fa-inbox"></i>
            <h4 class="fw-bold mt-2">ไม่มีคำขอที่รอการพิจารณา</h4>
            <div>ขณะนี้ไม่มีคำขอใหม่ที่ต้องการการอนุมัติจากคุณ</div>
          </div>
        <?php else: ?>

          <div id="auryxList">
            <?php foreach ($requests as $idx => $req): ?>
              <article class="auryx-card" data-auryx-card data-idx="<?= (int)$idx ?>" style="<?= $idx >= 5 ? 'display:none' : '' ?>">
                <div class="auryx-head">
                  <div class="flex-grow-1">
                    <?php if (!empty($req['document_number'])): ?>
                      <div class="auryx-doc"><i class="fas fa-file-alt me-1"></i>เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?></div>
                      <input type="hidden" name="document_number" value=" <?= htmlspecialchars($req['document_number']) ?>">
                    <?php endif; ?>
                    <h3 class="auryx-title">หัวข้อ : <?= htmlspecialchars($req['title']) ?></h3>
                    <div class="auryx-sub">
                      <?php if (!empty($req['service_name'])): ?>
                        <span class="auryx-badge <?= (($req['service_category'] ?? '') === 'development') ? 'dev' : 'ops' ?>">
                          <?php if (($req['service_category'] ?? '') === 'development'): ?>
                            <i class="fas fa-code"></i>
                          <?php else: ?>
                            <i class="fas fa-tools"></i>
                          <?php endif; ?>
                          <?= htmlspecialchars($req['service_name']) ?>
                        </span>
                      <?php endif; ?>
                      <?php if (!empty($req['attachment_count'])): ?>
                        <span class="badge bg-info"><i class="fas fa-paperclip me-1"></i><?= (int)$req['attachment_count'] ?> ไฟล์แนบ</span>
                      <?php endif; ?>
                      <button class="btn btn-sm btn-outline-secondary ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#auryx_d_<?= (int)$req['id'] ?>">
                        <i class="fas fa-chevron-down me-1"></i> รายละเอียด
                      </button>
                    </div>
                  </div>
                  <div class="auryx-stamp"><i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></div>
                </div>

                <!-- meta chips -->
                <div class="auryx-meta">
                  <span class="meta"><i class="fas fa-id-card"></i><span class="label">รหัส:</span> <span class="val"><?= htmlspecialchars($req['employee_id'] ?? 'ไม่ระบุ') ?></span></span>
                  <span class="meta"><i class="fas fa-user"></i><span class="label">ชื่อ:</span> <span class="val"><?= htmlspecialchars($req['name'] . ' ' . $req['lastname']) ?></span></span>
                  <span class="meta"><i class="fas fa-briefcase"></i><span class="label">ตำแหน่ง:</span> <span class="val"><?= htmlspecialchars($req['position'] ?? 'ไม่ระบุ') ?></span></span>
                  <span class="meta"><i class="fas fa-building"></i><span class="label">หน่วยงาน:</span> <span class="val"><?= htmlspecialchars($req['department'] ?? 'ไม่ระบุ') ?></span></span>
                  <span class="meta"><i class="fas fa-phone"></i><span class="label">โทร:</span> <span class="val"><?= htmlspecialchars($req['phone'] ?? 'ไม่ระบุ') ?></span></span>
                  <span class="meta"><i class="fas fa-envelope"></i><span class="label">อีเมล:</span> <span class="val"><?= htmlspecialchars($req['email'] ?? 'ไม่ระบุ') ?></span></span>
                </div>

                <!-- collapsed details to shorten page -->
                <div class="collapse" id="auryx_d_<?= (int)$req['id'] ?>">
                  <?php if (($req['service_category'] ?? '') === 'development'): ?>
                    <div class="auryx-detail mt-3">
                      <h6><i class="fas fa-code me-2"></i>ข้อมูล Development</h6>
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
                        foreach ($fields as $key => $label): if (!empty($req[$key])): ?>
                            <div class="col-md-6 mb-3"><strong><?= $label ?>:</strong><br><?= nl2br(htmlspecialchars($req[$key])) ?></div>
                        <?php endif;
                        endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($req['expected_benefits'])): ?>
                    <div class="auryx-detail mt-3" style="border-left-color:var(--ax-success);">
                      <h6 class="text-success"><i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ</h6>
                      <p class="mb-0"><?= nl2br(htmlspecialchars($req['expected_benefits'])) ?></p>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($req['attachment_count'])): ?>
                    <div class="auryx-attach auryx-attach--compact">
                      <?php require_once __DIR__ . '/../includes/attachment_display.php';
                      displayAttachments($req['id']); ?>
                    </div>
                  <?php endif; ?>
                </div>

                <form method="post" class="auryx-approve" onsubmit="return auryxValidate(this)">
                  <?php if (!empty($req['document_number'])): ?>
                    <input type="hidden" name="document_number" value="<?= htmlspecialchars($req['document_number']) ?>">
                  <?php endif; ?>
                  <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">

                  <h5><i class="fas fa-gavel me-2"></i>กดเลือกการพิจารณาก่อน</h5>
                  <div class="auryx-options mb-3">
                    <label class="auryx-opt approve">
                      <input type="radio" name="status" value="approved" required>
                      <i class="fas fa-check-circle"></i> อนุมัติ
                    </label>
                    <label class="auryx-opt reject">
                      <input type="radio" name="status" value="rejected" required>
                      <i class="fas fa-times-circle"></i> ไม่อนุมัติ
                    </label>
                  </div>


                  <div class="mb-3">
                    <label for="reason_<?= (int)$req['id'] ?>" class="form-label">เหตุผล/ข้อเสนอแนะ:</label>
                    <textarea name="reason" id="reason_<?= (int)$req['id'] ?>" class="form-control auryx-textarea" rows="3" placeholder="ระบุเหตุผลหรือข้อเสนอแนะ (จำเป็นเมื่อไม่อนุมัติ)"></textarea>
                  </div>

                  <button type="submit" class="auryx-btn"><i class="fas fa-paper-plane"></i> ส่งผลการพิจารณา</button>
                </form>
              </article>
            <?php endforeach; ?>
          </div>

          <?php if (count($requests) > 5): ?>
            <div class="auryx-load">
              <button id="auryxLoadMore" class="btn btn-outline-secondary btn-sm">
                โหลดเพิ่มอีก <span id="auryxRemain"><?= (int)(count($requests) - 5) ?></span>
              </button>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>


      <footer class="footer py-4  ">
        <div class="container-fluid">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
              <div class="copyright text-center text-sm text-muted text-lg-start">
                © <script>
                  document.write(new Date().getFullYear())
                </script>,
                made with </i> by
                <a class="font-weight-bold" target="_blank">เเผนกพัฒนาระบบงาน</a>
                for BobbyCareRemake.
              </div>
            </div>

          </div>
        </div>
      </footer>
    </div>
  </main>
  <div class="fixed-plugin">
    <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
      <i class="material-icons py-2">settings</i>
    </a>
    <div class="card shadow-lg">
      <div class="card-header pb-0 pt-3">
        <div class="float-start">
          <h5 class="mt-3 mb-0">Material UI Configurator</h5>
          <p>See our dashboard options.</p>
        </div>
        <div class="float-end mt-4">
          <button class="btn btn-link text-dark p-0 fixed-plugin-close-button">
            <i class="material-icons">clear</i>
          </button>
        </div>
        <!-- End Toggle Button -->
      </div>
      <hr class="horizontal dark my-1">
      <div class="card-body pt-sm-3 pt-0">
        <!-- Sidebar Backgrounds -->
        <div>
          <h6 class="mb-0">Sidebar Colors</h6>
        </div>
        <a href="javascript:void(0)" class="switch-trigger background-color">
          <div class="badge-colors my-2 text-start">
            <span class="badge filter bg-gradient-primary active" data-color="primary" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-dark" data-color="dark" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-info" data-color="info" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-success" data-color="success" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-warning" data-color="warning" onclick="sidebarColor(this)"></span>
            <span class="badge filter bg-gradient-danger" data-color="danger" onclick="sidebarColor(this)"></span>
          </div>
        </a>
        <!-- Sidenav Type -->
        <div class="mt-3">
          <h6 class="mb-0">Sidenav Type</h6>
          <p class="text-sm">Choose between 2 different sidenav types.</p>
        </div>
        <div class="d-flex">
          <button class="btn bg-gradient-dark px-3 mb-2 active" data-class="bg-gradient-dark" onclick="sidebarType(this)">Dark</button>
          <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-transparent" onclick="sidebarType(this)">Transparent</button>
          <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-white" onclick="sidebarType(this)">White</button>
        </div>
        <p class="text-sm d-xl-none d-block mt-2">You can change the sidenav type just on desktop view.</p>
        <!-- Navbar Fixed -->
        <div class="mt-3 d-flex">
          <h6 class="mb-0">Navbar Fixed</h6>
          <div class="form-check form-switch ps-0 ms-auto my-auto">
            <input class="form-check-input mt-1 ms-auto" type="checkbox" id="navbarFixed" onclick="navbarFixed(this)">
          </div>
        </div>
        <hr class="horizontal dark my-3">
        <div class="mt-2 d-flex">
          <h6 class="mb-0">Light / Dark</h6>
          <div class="form-check form-switch ps-0 ms-auto my-auto">
            <input class="form-check-input mt-1 ms-auto" type="checkbox" id="dark-version" onclick="darkMode(this)">
          </div>
        </div>
        <hr class="horizontal dark my-sm-4">
        <a class="btn btn-outline-dark w-100" href="">View documentation</a>

      </div>
    </div>
  </div>
  <!--   Core JS Files   -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>
  <script>
    var ctx = document.getElementById("chart-bars").getContext("2d");

    new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["M", "T", "W", "T", "F", "S", "S"],
        datasets: [{
          label: "Sales",
          tension: 0.4,
          borderWidth: 0,
          borderRadius: 4,
          borderSkipped: false,
          backgroundColor: "rgba(255, 255, 255, .8)",
          data: [50, 20, 10, 22, 50, 10, 40],
          maxBarThickness: 6
        }, ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              suggestedMin: 0,
              suggestedMax: 500,
              beginAtZero: true,
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
              color: "#fff"
            },
          },
          x: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });


    var ctx2 = document.getElementById("chart-line").getContext("2d");

    new Chart(ctx2, {
      type: "line",
      data: {
        labels: ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
          label: "Mobile apps",
          tension: 0,
          borderWidth: 0,
          pointRadius: 5,
          pointBackgroundColor: "rgba(255, 255, 255, .8)",
          pointBorderColor: "transparent",
          borderColor: "rgba(255, 255, 255, .8)",
          borderColor: "rgba(255, 255, 255, .8)",
          borderWidth: 4,
          backgroundColor: "transparent",
          fill: true,
          data: [50, 40, 300, 320, 500, 350, 200, 230, 500],
          maxBarThickness: 6

        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
          x: {
            grid: {
              drawBorder: false,
              display: false,
              drawOnChartArea: false,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });

    var ctx3 = document.getElementById("chart-line-tasks").getContext("2d");

    new Chart(ctx3, {
      type: "line",
      data: {
        labels: ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
          label: "Mobile apps",
          tension: 0,
          borderWidth: 0,
          pointRadius: 5,
          pointBackgroundColor: "rgba(255, 255, 255, .8)",
          pointBorderColor: "transparent",
          borderColor: "rgba(255, 255, 255, .8)",
          borderWidth: 4,
          backgroundColor: "transparent",
          fill: true,
          data: [50, 40, 300, 220, 500, 250, 400, 230, 500],
          maxBarThickness: 6

        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              padding: 10,
              color: '#f8f9fa',
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
          x: {
            grid: {
              drawBorder: false,
              display: false,
              drawOnChartArea: false,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });
  </script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.0.0"></script>
</body>


<script>
  // Auryx: require reason when rejecting
  function auryxValidate(form) {
    var sel = form.querySelector('input[name="status"]:checked');
    var reason = form.querySelector('textarea[name="reason"]');
    if (!sel) return false;
    if (sel.value === 'rejected' && reason.value.trim().length < 3) {
      reason.classList.add('is-invalid');
      reason.focus();
      return false;
    }
    reason.classList.remove('is-invalid');
    return true;
  }

  // Auryx: Load more (batch of 5)
  (function() {
    var list = document.getElementById('auryxList');
    if (!list) return;
    var cards = Array.from(list.querySelectorAll('[data-auryx-card]'));
    var btn = document.getElementById('auryxLoadMore');
    var remainEl = document.getElementById('auryxRemain');
    var BATCH = 5;

    function updateRemain() {
      var shown = cards.filter(c => c.style.display !== 'none').length;
      var remain = Math.max(cards.length - shown, 0);
      if (remainEl) remainEl.textContent = remain;
      if (btn && remain <= 0) btn.remove();
    }
    if (btn) btn.addEventListener('click', function() {
      var shown = cards.filter(c => c.style.display !== 'none').length;
      cards.slice(shown, shown + BATCH).forEach(c => c.style.display = '');
      updateRemain();
    });
    updateRemain();
  })();
</script>
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



</html>