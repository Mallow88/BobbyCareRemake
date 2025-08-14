<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
  header("Location: ../index.php");
  exit();
}

/* ==== ดึงรายชื่อ Developer ==== */
$dev_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'developer' AND is_active = 1 ORDER BY name");
$dev_stmt->execute();
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

$picture_url = $_SESSION['picture_url'] ?? null;

/* ==== รับค่าตัวกรองจาก GET ==== */
$selected_dev = $_GET['dev_id'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$status       = $_GET['status']   ?? 'all';     // pending, received, in_progress, on_hold, completed, accepted
$priority     = $_GET['priority'] ?? 'all';     // urgent, high, medium, low
$deadlineFlag = $_GET['due']      ?? 'all';     // overdue, due_soon, on_time
// ให้ค่าเริ่มต้นเป็น "all" เพื่อไม่ล็อกเดือน/ปีโดยไม่ตั้งใจ
$current_month = $_GET['month'] ?? 'all';       // 1..12 หรือ all
$current_year  = $_GET['year']  ?? 'all';       // YYYY หรือ all

$current_day = $_GET['day'] ?? 'all'; // ค่าเริ่มต้น all = ไม่กรอง
$type         = $_GET['type'] ?? 'all';



$devData = [];

foreach ($developers as $dev) {
  // เงื่อนไข SQL เริ่มต้น
  $where = ["t.developer_user_id = ?"];
  $params = [$dev['id']];

  // ฟิลเตอร์ Developer
  if ($selected_dev !== 'all') {
    $where[] = "t.developer_user_id = ?";
    $params[] = $selected_dev;
  }

  // ประเภทงาน
  if ($type !== 'all') {
    $where[] = "s.category = ?";
    $params[] = $type;
  }

  // สถานะงาน
  if ($status !== 'all') {
    $where[] = "t.task_status = ?";
    $params[] = $status;
  }

  // สถานะกำหนดส่ง
  if ($deadlineFlag === 'overdue') {
    $where[] = "sr.deadline < CURDATE()";
  } elseif ($deadlineFlag === 'due_soon') {
    $where[] = "DATEDIFF(sr.deadline, CURDATE()) <= 2 AND sr.deadline >= CURDATE()";
  } elseif ($deadlineFlag === 'on_time') {
    $where[] = "sr.deadline >= CURDATE()";
  }

  // วัน / เดือน / ปี
  if ($current_day !== 'all') {
    $where[] = "DAY(t.created_at) = ?";
    $params[] = $current_day;
  }
  if ($current_month !== 'all') {
    $where[] = "MONTH(t.created_at) = ?";
    $params[] = $current_month;
  }
  if ($current_year !== 'all') {
    $where[] = "YEAR(t.created_at) = ?";
    $params[] = $current_year;
  }

  // ประกอบ WHERE
  $whereSQL = implode(' AND ', $where);

  // Query
  $stmt = $conn->prepare("
        SELECT 
            t.task_status,
            s.category
        FROM tasks t
        JOIN service_requests sr ON t.service_request_id = sr.id
        LEFT JOIN services s ON sr.service_id = s.id
        WHERE {$whereSQL}
    ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // นับสถานะ
  $pending = $inProgress = $completed = 0;
  $serviceCount = $devCount = 0;

  foreach ($rows as $r) {
    if (in_array($r['task_status'], ['pending', 'received'])) $pending++;
    elseif (in_array($r['task_status'], ['in_progress', 'on_hold'])) $inProgress++;
    elseif (in_array($r['task_status'], ['completed', 'accepted'])) $completed++;

    // นับประเภท
    if (!empty($r['category'])) {
      if (mb_strtolower($r['category']) === 'service' || $r['category'] === 'งานบริการ') {
        $serviceCount++;
      } elseif (mb_strtolower($r['category']) === 'development' || $r['category'] === 'งานพัฒนา') {
        $devCount++;
      }
    }
  }

  // push devData (ถ้าไม่มีงานค่าก็เป็นศูนย์)
  $devData[] = [
    'id'   => $dev['id'],
    'name' => $dev['name'] . ' ' . $dev['lastname'],
    'data' => [$pending, $inProgress, $completed, $serviceCount, $devCount]
  ];
}


$monthlyData = [
  'total' => array_fill(1, 12, 0),
  'dev'   => array_fill(1, 12, 0),
  'service' => array_fill(1, 12, 0)
];

$stmt = $conn->prepare("
    SELECT 
        MONTH(sr.created_at) AS month,
        s.category
    FROM service_requests sr
    LEFT JOIN services s ON sr.service_id = s.id
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
  $m = (int)$r['month'];
  $monthlyData['total'][$m]++;

  if (mb_strtolower($r['category']) === 'development' || $r['category'] === 'งานพัฒนา') {
    $monthlyData['dev'][$m]++;
  } elseif (mb_strtolower($r['category']) === 'service' || $r['category'] === 'งานบริการ') {
    $monthlyData['service'][$m]++;
  }
}




// เตรียม array เก็บข้อมูล 12 เดือน
$allCounts = array_fill(1, 12, 0);
$devCounts = array_fill(1, 12, 0);
$serviceCounts = array_fill(1, 12, 0);

$sql = "
    SELECT 
        MONTH(t.created_at) AS month,
        s.category,
        COUNT(*) AS total
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    LEFT JOIN services s ON sr.service_id = s.id
    WHERE 1
";

$params = [];

if ($current_year !== 'all') {
  $sql .= " AND YEAR(t.created_at) = ?";
  $params[] = $current_year;
}

$sql .= " GROUP BY MONTH(t.created_at), s.category";
$stmt = $conn->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $month = (int)$row['month'];
  $allCounts[$month] += $row['total'];

  if (mb_strtolower($row['category']) === 'development' || $row['category'] === 'งานพัฒนา') {
    $devCounts[$month] += $row['total'];
  } elseif (mb_strtolower($row['category']) === 'service' || $row['category'] === 'งานบริการ') {
    $serviceCounts[$month] += $row['total'];
  }
}

$barData = [
  'all' => array_values($allCounts),
  'development' => array_values($devCounts),
  'service' => array_values($serviceCounts),
];





// function เเสดงกอรงตามวันเดือนปี
function formatDateFilter($day, $month, $year)
{
  if ($year === 'all' && $month === 'all' && $day === 'all') {
    return "ทั้งหมด";
  }
  // กรณีมีปี กับ เดือนอย่างน้อย
  if ($year !== 'all' && $month !== 'all' && $day !== 'all') {
    // สร้างวันที่
    $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
    $date = DateTime::createFromFormat('Y-m-d', $dateStr);
    if ($date) {
      // แสดงวันที่ภาษาไทย (ถ้าต้องการ)
      setlocale(LC_TIME, 'th_TH.UTF-8'); // หรือใช้ locale ที่เครื่องรองรับ
      // แสดงวันที่เป็น เช่น "11 สิงหาคม 2025"
      return strftime('%e %B %Y', $date->getTimestamp());
    }
    // fallback ถ้าวันไม่ถูกต้อง
    return "$day/$month/$year";
  }

  // กรณีเลือกปีกับเดือนอย่างเดียว
  if ($year !== 'all' && $month !== 'all') {
    $dateStr = sprintf("%04d-%02d-01", $year, $month);
    $date = DateTime::createFromFormat('Y-m-d', $dateStr);
    if ($date) {
      setlocale(LC_TIME, 'th_TH.UTF-8');
      return strftime('%B %Y', $date->getTimestamp());
    }
    return "$month/$year";
  }

  // กรณีเลือกปีอย่างเดียว
  if ($year !== 'all') {
    return "ปี $year";
  }

  // กรณีเลือกเดือนอย่างเดียว
  if ($month !== 'all') {
    $months = [
      1 => 'มกราคม',
      2 => 'กุมภาพันธ์',
      3 => 'มีนาคม',
      4 => 'เมษายน',
      5 => 'พฤษภาคม',
      6 => 'มิถุนายน',
      7 => 'กรกฎาคม',
      8 => 'สิงหาคม',
      9 => 'กันยายน',
      10 => 'ตุลาคม',
      11 => 'พฤศจิกายน',
      12 => 'ธันวาคม'
    ];
    $m = (int)$month;
    $monthName = $months[$m] ?? $month;
    return "เดือน $monthName";
  }

  // กรณีเลือกวันอย่างเดียว (น้อยมากที่จะใช้)
  if ($day !== 'all') {
    return "วันที่ $day";
  }

  // ค่าเริ่มต้น
  return "เลือกวันเดือนปี";
}



$type = $_GET['type'] ?? 'all'; // ประเภทงาน all, service, developer หรือ category อื่น ๆ
$type_opts = [
  'all' => 'ทุกประเภท',
  'service' => 'งานบริการ',
  'development' => 'งานพัฒนา',
];

/* ==== ประกอบ WHERE แบบไดนามิก ==== */
$where  = ["1=1"];
$params = [];


if (!empty($_GET['code_name'])) {
  $where[]  = 'dn.code_name = ?';
  $params[] = $_GET['code_name'];
}

// กรองตาม วัน
if ($current_day !== 'all') {
  $where[]  = "DAY(sr.created_at) = ?";
  $params[] = (int)$current_day;
}


// กรองตาม Developer ประเภทงาน all, service, developer
if ($type !== 'all') {
  // ถ้าใช้ category จาก services.category
  $where[] = "s.category = ?";
  $params[] = $type;
}


// กรองตาม Developer
if ($selected_dev !== 'all') {
  $where[]  = "t.developer_user_id = ?";
  $params[] = $selected_dev;
}

// กรองสถานะงาน
if ($status !== 'all') {
  $where[]  = "t.task_status = ?";
  $params[] = $status;
}

// กรองความสำคัญ
if ($priority !== 'all') {
  $where[]  = "sr.priority = ?";
  $params[] = $priority;
}

// กรองเดือน/ปี (อิงวันที่สร้างคำขอ)
if ($current_month !== 'all') {
  $where[]  = "MONTH(sr.created_at) = ?";
  $params[] = (int)$current_month;
}
if ($current_year !== 'all') {
  $where[]  = "YEAR(sr.created_at) = ?";
  $params[] = (int)$current_year;
}



// ค้นหาคีย์เวิร์ด (หัวข้อ/รายละเอียด/เลขเอกสาร/ผู้ร้อง/บริการ)
if ($search !== '') {
  $where[] = "(
      sr.title LIKE ?
      OR sr.description LIKE ?
      OR COALESCE(dn.document_number,'') LIKE ?
      OR CONCAT(requester.name,' ',requester.lastname) LIKE ?
      OR COALESCE(s.name,'') LIKE ?
      OR COALESCE(s.category,'') LIKE ?
    )";
  $like = "%{$search}%";
  array_push($params, $like, $like, $like, $like, $like, $like);
}

// กรองสถานะกำหนดส่ง (overdue/due_soon/on_time)
// ต้องใส่ expression เดิมลงใน WHERE (ใช้นามแฝงไม่ได้)
if ($deadlineFlag !== 'all') {
  $where[] = "(
      CASE 
        WHEN sr.deadline IS NOT NULL 
             AND sr.deadline < CURDATE() 
             AND t.task_status NOT IN ('completed','accepted') THEN 'overdue'
        WHEN sr.deadline IS NOT NULL 
             AND DATEDIFF(sr.deadline, CURDATE()) <= 2 
             AND t.task_status NOT IN ('completed','accepted') THEN 'due_soon'
        ELSE 'on_time'
      END
    ) = ?";
  $params[] = $deadlineFlag;
}

/* ==== คำสั่ง SQL หลัก (ลบ comma เกินจากโค้ดเดิมให้แล้ว) ==== */
$sql = "
  SELECT 
    t.*,
    sr.title,
    sr.description,
    sr.priority,
    sr.estimated_days,
    sr.deadline,
    sr.created_at AS request_created_at,
    dev.name AS dev_name,
    dev.lastname AS dev_lastname,
    dev.id AS dev_id,
    requester.name AS requester_name,
    requester.lastname AS requester_lastname,
    requester.department AS requester_department,
    s.name AS service_name,
    s.category AS service_category,
    ur.rating,
    ur.review_comment,
    ur.status AS review_status,
    ur.reviewed_at AS user_reviewed_at,
    dn.document_number,
    dn.code_name AS document_code_name,

    aa.assignor_user_id,
    assignor.name AS assignor_name,
    assignor.lastname AS assignor_lastname,
    -- เวลาที่ใช้
    CASE 
      WHEN t.started_at IS NOT NULL AND t.completed_at IS NOT NULL 
        THEN TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at)
      WHEN t.started_at IS NOT NULL 
        THEN TIMESTAMPDIFF(HOUR, t.started_at, NOW())
      ELSE 0
    END AS hours_spent,
    -- สถานะความล่าช้า
    CASE 
      WHEN sr.deadline IS NOT NULL AND sr.deadline < CURDATE() AND t.task_status NOT IN ('completed', 'accepted')
        THEN 'overdue'
      WHEN sr.deadline IS NOT NULL AND DATEDIFF(sr.deadline, CURDATE()) <= 2 AND t.task_status NOT IN ('completed', 'accepted')
        THEN 'due_soon'
      ELSE 'on_time'
    END AS deadline_status
  FROM tasks t
  JOIN service_requests sr ON t.service_request_id = sr.id
  JOIN users dev ON t.developer_user_id = dev.id
  JOIN users requester ON sr.user_id = requester.id
  LEFT JOIN services s ON sr.service_id = s.id
  LEFT JOIN user_reviews ur ON t.id = ur.task_id
  LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
  LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id
  LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
  WHERE " . implode(' AND ', $where) . "
  ORDER BY 
    CASE t.task_status 
      WHEN 'pending' THEN 1
      WHEN 'received' THEN 2
      WHEN 'in_progress' THEN 3
      WHEN 'on_hold' THEN 4
      WHEN 'completed' THEN 5
      WHEN 'accepted' THEN 6
      ELSE 7
    END,
    CASE sr.priority
      WHEN 'urgent' THEN 1
      WHEN 'high' THEN 2
      WHEN 'medium' THEN 3
      WHEN 'low' THEN 4
      ELSE 5
    END,
    t.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
// รายการคำขอทั้งหมด
$countSrStmt = $conn->query("SELECT COUNT(*) AS total_sr FROM service_requests");
$totalServiceRequests = $countSrStmt->fetchColumn();
// รายการเอกสารทั้งหมด


// ดึงจำนวนเอกสารทั้งหมด
$countStmt = $conn->query("SELECT COUNT(*) AS total_documents FROM document_numbers");
$countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalDocuments = $countResult['total_documents'] ?? 0;

// แจกแจงตาม code_name
$codeNameStmt = $conn->query("
    SELECT code_name, COUNT(*) AS total
    FROM document_numbers
    GROUP BY code_name
    ORDER BY total DESC
");
$codeNameCounts = $codeNameStmt->fetchAll(PDO::FETCH_ASSOC);

$codeNames = $conn->query("
    SELECT DISTINCT code_name
    FROM document_numbers
    ORDER BY code_name
")->fetchAll(PDO::FETCH_COLUMN);






/* ==== จัดกลุ่ม/สถิติเดิม (โค้ดของคุณ) ==== */
$dev_tasks = [];
$dev_stats = [];
foreach ($tasks as $task) {
  $dev_id = $task['dev_id'];
  if (!isset($dev_tasks[$dev_id])) {
    $dev_tasks[$dev_id] = [];
    $dev_stats[$dev_id] = [
      'name' => $task['dev_name'] . ' ' . $task['dev_lastname'],
      'pending' => 0,
      'in_progress' => 0,
      'completed' => 0,
      'overdue' => 0,
      'total' => 0,
      'total_hours' => 0,
      'avg_rating' => 0,
      'total_ratings' => 0,
      'status' => 'ว่าง'
    ];
  }
  $dev_tasks[$dev_id][] = $task;
  $dev_stats[$dev_id]['total']++;
  $dev_stats[$dev_id]['total_hours'] += $task['hours_spent'];

  if (in_array($task['task_status'], ['pending', 'received'])) {
    $dev_stats[$dev_id]['pending']++;
  } elseif (in_array($task['task_status'], ['in_progress', 'on_hold'])) {
    $dev_stats[$dev_id]['in_progress']++;
  } elseif (in_array($task['task_status'], ['completed', 'accepted'])) {
    $dev_stats[$dev_id]['completed']++;
  }

  if ($task['deadline_status'] === 'overdue') {
    $dev_stats[$dev_id]['overdue']++;
  }

  if ($task['rating'] !== null && $task['rating'] !== '') {
    $dev_stats[$dev_id]['avg_rating'] = (($dev_stats[$dev_id]['avg_rating'] * $dev_stats[$dev_id]['total_ratings']) + $task['rating']) / ($dev_stats[$dev_id]['total_ratings'] + 1);
    $dev_stats[$dev_id]['total_ratings']++;
  }

  if ($dev_stats[$dev_id]['overdue'] > 0) {
    $dev_stats[$dev_id]['status'] = 'เลยกำหนด';
  } elseif ($dev_stats[$dev_id]['in_progress'] > 0) {
    $dev_stats[$dev_id]['status'] = 'ติดงาน';
  } elseif ($dev_stats[$dev_id]['pending'] > 0) {
    $dev_stats[$dev_id]['status'] = 'มีงานรอ';
  }
}

/* สร้างลิสต์ตัวเลือกไว้ใช้ในฟอร์ม */
$status_opts = [
  'all' => 'ทุกสถานะ',
  'pending' => 'รอรับ',
  'received' => 'รับแล้ว',
  'in_progress' => 'กำลังทำ',
  'on_hold' => 'พัก',
  'completed' => 'เสร็จ',
  'accepted' => 'ปิดงาน',
];
$priority_opts = [
  'all' => 'ทุกความสำคัญ',
  'urgent' => 'ด่วนมาก',
  'high' => 'สูง',
  'medium' => 'ปานกลาง',
  'low' => 'ต่ำ',
];
$due_opts = [
  'all' => 'ทั้งหมด',
  'overdue' => 'เลยกำหนด',
  'due_soon' => 'ใกล้ครบกำหนด (≤2วัน)',
  'on_time' => 'ตามกำหนด',
];
$year_now = (int)date('Y');
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
          <h3 class="fw-bold mb-3">Dashboard</h3>

          <form method="GET" id="searchForm" class="card mb-3">
            <div class="card-body py-3">

              <!-- แถวบน -->
              <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                  <label for="devSelect" class="form-label fw-bold small">
                    <i class="fas fa-user-cog me-1 text-primary"></i>Developer
                  </label>
                  <select class="form-select form-select-sm" id="devSelect" name="dev_id" onchange="this.form.submit()">
                    <option value="all" <?= $selected_dev === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    <?php foreach ($developers as $dev): ?>
                      <option value="<?= $dev['id'] ?>" <?= $selected_dev == $dev['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-6 col-md-3">
                  <label class="form-label fw-bold small">ประเภทงาน</label>
                  <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($type_opts as $k => $v): ?>
                      <option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-6 col-md-3">
                  <label class="form-label fw-bold small">สถานะงาน</label>
                  <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($status_opts as $k => $v): ?>
                      <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-6 col-md-3">
                  <label class="form-label fw-bold small">แผนกคลังสินค้า</label>
                  <select name="code_name" id="code_name" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- แสดงทั้งหมด --</option>
                    <?php foreach ($codeNames as $name): ?>
                      <option value="<?= htmlspecialchars($name) ?>" <?= ($_GET['code_name'] ?? '') === $name ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- แถวล่าง -->
              <div class="row g-2 align-items-end mt-1">
                <div class="col-6 col-md-2">
                  <label class="form-label fw-bold small">กำหนดส่ง</label>
                  <select name="due" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($due_opts as $k => $v): ?>
                      <option value="<?= $k ?>" <?= $deadlineFlag === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-6 col-md-2">
                  <label class="form-label fw-bold small">วัน</label>
                  <select name="day" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $current_day === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                      <option value="<?= $d ?>" <?= (string)$current_day === (string)$d ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endfor; ?>
                  </select>
                </div>

                <div class="col-6 col-md-2">
                  <label class="form-label fw-bold small">เดือน</label>
                  <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $current_month === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                      <option value="<?= $m ?>" <?= (string)$current_month === (string)$m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endfor; ?>
                  </select>
                </div>

                <div class="col-6 col-md-2">
                  <label class="form-label fw-bold small">ปี</label>
                  <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $current_year === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    <?php for ($y = $year_now - 2; $y <= $year_now + 1; $y++): ?>
                      <option value="<?= $y ?>" <?= (string)$current_year === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                  </select>
                </div>

                <div class="col-6 col-md-2 d-grid">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i> ค้นหา
                  </button>
                </div>

                <div class="col-6 col-md-2 d-grid">
                  <a href="?dev_id=all" class="btn btn-outline-secondary btn-sm">ล้างตัวกรอง</a>
                </div>
              </div>

            </div>
          </form>






          <div class="row">


            <div class="row g-3 text-center">
              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100">
                  <div class="card-body p-3">
                    <div class="text-end text-success">
                      <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                    </div>

                    <div class="h1 m-0"><?= count($tasks) ?></div>
                    <div class="text-muted mb-0">งานทั้งหมด</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100">
                  <div class="card-body p-3">
                    <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                    <div class="h1 m-0"><?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'pending')) ?></div>
                    <div class="text-muted mb-0">รอรับงาน</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100">
                  <div class="card-body p-3">
                    <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                    <div class="h1 m-0"><?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'received')) ?></div>
                    <div class="text-muted mb-0">รับงาน</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100">
                  <div class="card-body p-3">
                    <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                    <div class="h1 m-0"><?= count(array_filter($tasks, fn($t) => in_array($t['task_status'], ['in_progress', 'on_hold']))) ?></div>
                    <div class="text-muted mb-0">กำลังทำ</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100">
                  <div class="card-body p-3">
                    <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                    <div class="h1 m-0"><?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'completed')) ?></div>
                    <div class="text-muted mb-0">เสร็จสิ้น</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100">
                  <div class="card-body p-3">
                    <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                    <div class="h1 m-0"><?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'accepted')) ?></div>
                    <div class="text-muted mb-0">ปิดงาน</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100">
                  <div class="card-body p-3">
                    <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                    <div class="h1 m-0"><?= count(array_filter($tasks, fn($t) => $t['deadline_status'] === 'overdue')) ?></div>
                    <div class="text-muted mb-0">เลยกำหนด</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <br>




          <div class="row">
            <div class="col-sm-6 col-lg-3">
              <div class="card p-3">
                <div class="d-flex align-items-center">
                  <span class="stamp stamp-md bg-secondary me-3">
                    <i class="fa fa-file-alt"></i>
                  </span>
                  <div>
                    <h5 class="mb-1">
                      <b><a href="#"><?= number_format($totalServiceRequests) ?> <small>รายการ</small></a></b>
                    </h5>
                    <small class="text-muted">รายการเอกสารที่ขอเข้าทั้งหมด</small>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-lg-3">
              <div class="card p-3">
                <div class="d-flex align-items-center">
                  <span class="stamp stamp-md bg-success me-3">
                    <i class="fa fa-warehouse"></i>
                  </span>
                  <div>
                    <h5 class="mb-1">
                      <b><?= number_format($totalDocuments) ?></b>
                    </h5>
                    <small class="text-muted">จำนวนเแผนกทั้งหหมดที่ใช้บริการ</small>
                  </div>
                </div>
              </div>
            </div>





            <div class="col-sm-6 col-lg-3">
              <div class="card p-3">
                <div class="d-flex align-items-center">
                  <span class="stamp stamp-md bg-danger me-3">
                    <i class="fa fa-users"></i>
                  </span>
                  <div>
                    <h5 class="mb-1">
                      <b><a href="#">1,352 <small>Members</small></a></b>
                    </h5>
                    <small class="text-muted">163 registered today</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="card p-3">
                <div class="d-flex align-items-center">
                  <span class="stamp stamp-md bg-warning me-3">
                    <i class="fa fa-comment-alt"></i>
                  </span>
                  <div>
                    <h5 class="mb-1">
                      <b><a href="#">132 <small>Comments</small></a></b>
                    </h5>
                    <small class="text-muted">16 waiting</small>
                  </div>
                </div>
              </div>
            </div>
          </div>





          <div class="row">


            <?php foreach ($devData as $i => $dev): ?>
              <div class="col-sm-6 col-md-4 col-lg-4 mb-3">
                <div class="card h-100">
                  <div class="card-header">
                    <div class="card-title mb-0"><?= htmlspecialchars($dev['name']) ?></div>
                  </div>
                  <div class="card-body">
                    <div class="chart-container" style="height:250px; position:relative; overflow:visible;">
                      <canvas id="devChart<?= $i ?>"></canvas>
                    </div>
                    <div class="small text-muted mt-2" id="devEmpty<?= $i ?>" style="display:none">ไม่มีงาน</div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>


            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    รายงานประจำเดือน
                    <?php if ($current_year !== 'all'): ?>
                      (ปี <?= htmlspecialchars($current_year) ?>)
                    <?php else: ?>
                      (ทุกปี)
                    <?php endif; ?>
                  </div>
                </div>
                <div class="card-body">
                  <div class="chart-container">
                    <canvas id="multipleBarChart"></canvas>
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


  <script>
    const labels = ['รอรับงาน', 'กำลังทำ', 'เสร็จสิ้น', 'งานบริการ', 'งานพัฒนา'];
    const backgroundColors = ['#f3545d', '#fbfd8e', '#02ff63', '#177dff', '#77d4ffff'];
    const devData = <?= json_encode($devData, JSON_UNESCAPED_UNICODE) ?>;

    devData.forEach((dev, i) => {
      const total = dev.data.reduce((a, b) => a + b, 0);
      if (total === 0) {
        document.getElementById('devEmpty' + i).style.display = 'block';
        return;
      }

      const ctx = document.getElementById('devChart' + i).getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data: dev.data,
            backgroundColor: backgroundColors,
            borderColor: '#fff',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                padding: 10,
                generateLabels: function(chart) {
                  const data = chart.data;
                  return data.labels.map((label, index) => {
                    const value = data.datasets[0].data[index];
                    return {
                      text: `${label} (${value})`,
                      fillStyle: data.datasets[0].backgroundColor[index],
                      strokeStyle: '#fff',
                      lineWidth: 2,
                      hidden: chart.getDataVisibility(index) === false
                    };
                  });
                }
              },
              // ✅ เปิดให้คลิก toggle ได้
              onClick: (e, legendItem, legend) => {
                const index = legendItem.index;
                const ci = legend.chart;
                ci.toggleDataVisibility(index);
                ci.update();
              }
            },
            datalabels: {
              color: '#000',
              font: {
                weight: 'bold',
                size: 12
              },
              formatter: (value, context) => {
                const ci = context.chart;
                const index = context.dataIndex;
                return ci.getDataVisibility(index) && value > 0 ? value : '';
              }
            }
          },
          animation: {
            duration: 300
          }
        },
        plugins: [ChartDataLabels]
      });
    });
  </script>
  <script>
    var barData = <?= json_encode($barData) ?>;

    var myMultipleBarChart = new Chart(document.getElementById('multipleBarChart'), {
      type: "bar",
      data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
            label: "งานทั้งหมด",
            backgroundColor: "#59d05d",
            borderColor: "#59d05d",
            data: barData.all
          },
          {
            label: "งานพัฒนา Development",
            backgroundColor: "#77d4ffff",
            borderColor: "#77d4ffff",
            data: barData.development
          },
          {
            label: "งานบริการ Service",
            backgroundColor: "#177dff",
            borderColor: "#177dff",
            data: barData.service
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
          position: "bottom"
        },
        title: {
          display: true,
          text: "รายการงานต่อเดือน"
        },
        tooltips: {
          mode: "index",
          intersect: false
        },
        scales: {
          xAxes: [{
            stacked: true
          }],
          yAxes: [{
            stacked: true
          }]
        }
      }
    });
  </script>

</body>

</html>