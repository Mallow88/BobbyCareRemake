<?php
session_start();
require_once __DIR__ . '/../config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
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
  $thaiMonths = [
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

  if ($year === 'all' && $month === 'all' && $day === 'all') {
    return "ทั้งหมด";
  }

  // ถ้าเลือก ปี+เดือน+วัน
  if ($year !== 'all' && $month !== 'all' && $day !== 'all') {
    $thYear = $year + 543;
    return "$day " . ($thaiMonths[(int)$month] ?? $month) . " $thYear";
  }

  // ถ้าเลือก ปี+เดือน
  if ($year !== 'all' && $month !== 'all') {
    $thYear = $year + 543;
    return ($thaiMonths[(int)$month] ?? $month) . " $thYear";
  }

  // ถ้าเลือกปีอย่างเดียว
  if ($year !== 'all') {
    return "ปี " . ($year + 543);
  }

  // ถ้าเลือกเดือนอย่างเดียว
  if ($month !== 'all') {
    return "เดือน " . ($thaiMonths[(int)$month] ?? $month);
  }

  if ($day !== 'all') {
    return "วันที่ $day";
  }

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


// จำนวนคำขอที่ยังอยู่ในขั้นตอนอนุมัติ (ยังไม่ approved )
$pendingStmt = $conn->query("
    SELECT COUNT(*) 
    FROM service_requests 
    WHERE status NOT IN ('approved' )
");
$totalPendingApprovals = $pendingStmt->fetchColumn();



// จำนวนคำขอที่ยังอยู่ในขั้นตอนอนุมัติ ( rejected / completed)
$pendingStmtCom = $conn->query("
    SELECT COUNT(*) 
    FROM service_requests 
    WHERE status NOT IN ('rejected','completed' )
");
$totalPendingcompleted = $pendingStmtCom->fetchColumn();



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







// ====== ถ้ามีการเรียกแบบ popup (modal)  ======
if (isset($_GET['popup'])) {
  $popup_status = $_GET['status'] ?? null;

  $filtered = $tasks;

  if ($popup_status === 'pending') {
    $filtered = array_filter($tasks, fn($t) => $t['task_status'] === 'pending');
  } elseif ($popup_status === 'received') {
    $filtered = array_filter($tasks, fn($t) => $t['task_status'] === 'received');
  } elseif ($popup_status === 'in_progress') {
    $filtered = array_filter($tasks, fn($t) => in_array($t['task_status'], ['in_progress', 'on_hold']));
  } elseif ($popup_status === 'completed') {
    $filtered = array_filter($tasks, fn($t) => $t['task_status'] === 'completed');
  } elseif ($popup_status === 'accepted') {
    $filtered = array_filter($tasks, fn($t) => $t['task_status'] === 'accepted');
  } elseif ($popup_status === 'overdue') {
    $filtered = array_filter($tasks, fn($t) => $t['deadline_status'] === 'overdue');
  } elseif ($popup_status === 'all') {
    $filtered = $tasks; // ไม่กรองเลย
  }



  $limit = 35; // จำนวนต่อหน้า
  $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
  $start = ($page - 1) * $limit;

  // จำนวนข้อมูลทั้งหมด
  $total_records = count($filtered);
  $total_pages   = ceil($total_records / $limit);

  // Slice array ให้เหลือเฉพาะหน้า
  $pagedData = array_slice($filtered, $start, $limit);

  if (empty($pagedData)) {
    echo "<p class='text-muted text-center py-3'>
            <i class='fas fa-inbox me-2'></i> ไม่พบงานในสถานะนี้
          </p>";
  } else {
    echo '
    <div class="card shadow-lg border-0 rounded-4">
       
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0" style="min-width: 1100px;">
                    <thead class="table-primary text-dark">
                        <tr>
                            <th scope="col">ลำดับ</th>
                            <th scope="col">เลขที่เอกสาร</th>
                            <th scope="col">หัวข้อ</th>
                            <th scope="col">ผู้พัฒนา</th>
                            <th scope="col">ประเภทงาน</th>
                            <th scope="col">ผู้ขอบริการ</th>
                            <th scope="col">แผนก</th>
                            <th scope="col">สถานะ</th>
                            <th scope="col">กำหนดส่ง</th>
                            <th scope="col">วันที่ขอบริการ</th>
                        </tr>
                    </thead>
                    <tbody>';

    $i = $start + 1;
    foreach ($pagedData as $t) {
      // กำหนดสี Badge ของสถานะ
      $statusClass = 'secondary';
      if ($t['task_status'] === 'pending') {
        $statusClass = 'warning';
      } elseif ($t['task_status'] === 'received') {
        $statusClass = 'info';
      } elseif ($t['task_status'] === 'in_progress ') {
        $statusClass = 'primary';
      } elseif ($t['task_status'] === 'completed') {
        $statusClass = 'success';
      } elseif ($t['task_status'] === 'accepted') {
        $statusClass = 'success';
      } elseif ($t['task_status'] === 'overdue ') {
        $statusClass = 'danger';
      }


      $categoryLabel = '-';
      $categoryClass = 'secondary';
      if (!empty($t['service_category'])) {
        if ($t['service_category'] === 'development') {
          $categoryLabel = 'งานพัฒนา';
          $categoryClass = 'success';
        } elseif ($t['service_category'] === 'service') {
          $categoryLabel = 'งานบริการ';
          $categoryClass = 'info';
        }
      }



      echo "
            <tr>
                <td>{$i}</td>
                <td>" . ($t['document_number'] ?? '-') . "</td>
                <td class='text-start'>" . htmlspecialchars($t['title']) . "</td>
                <td>{$t['dev_name']} {$t['dev_lastname']}</td>
                <td><span class='badge bg-{$categoryClass} px-3 py-2'>{$categoryLabel}</span></td>
    <td>{$t['requester_name']} {$t['requester_lastname']} </td>
     <td>{$t['requester_department']}</td>
                <td><span class='badge bg-{$statusClass} px-3 py-2'>{$t['task_status']}</span></td>
                <td>" . ($t['deadline'] ?? '-') . "</td>
                  <td>" . ($t['created_at'] ?? '-') . "</td>
            </tr>";
      $i++;
    }

    echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';

    // --------------------
    // Pagination UI
    // --------------------
    if ($total_pages > 1) {
      echo '<nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination justify-content-center">';

      // ปุ่มก่อนหน้า
      if ($page > 1) {
        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '">ก่อนหน้า</a></li>';
      }

      // เลขหน้า
      for ($p = 1; $p <= $total_pages; $p++) {
        $active = ($p == $page) ? 'active' : '';
        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $p . '">' . $p . '</a></li>';
      }

      // ปุ่มถัดไป
      if ($page < $total_pages) {
        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '">ถัดไป</a></li>';
      }

      echo '   </ul>
              </nav>';
    }
  }

  exit;
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

  
  <style>
    .custom-modal {
      max-width: 70% !important;
      /* ให้ modal กินพื้นที่ 90% ของจอ */
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

            <li class="nav-item active">
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
                    <span class="op-7">ผู้จัดการแผนก:</span>
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
                    <?php
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
                    foreach ($months as $num => $name):
                    ?>
                      <option value="<?= $num ?>" <?= (string)$current_month === (string)$num ? 'selected' : '' ?>>
                        <?= $name ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>


                <div class="col-6 col-md-2">
                  <label class="form-label fw-bold small">ปี</label>
                  <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $current_year === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    <?php for ($y = $year_now - 2; $y <= $year_now + 1; $y++):
                      $displayYear = $y + 543; // แสดงเป็น พ.ศ.
                    ?>
                      <option value="<?= $y ?>" <?= (string)$current_year === (string)$y ? 'selected' : '' ?>>
                        <?= $displayYear ?>
                      </option>
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
                <div class="card h-100 monitor-card clickable shadow-sm border-0" data-type="all">
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                    <!-- จำนวนงาน -->
                    <div>
                      <!-- วันที่ -->
                      <div class="fw-bold fs-5 mt-2 text-dark">
                        <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                      </div>
                      <!-- สถานะ -->
                      <div class="badge bg-warning text-dark px-3 py-2 fs-6 mb-2">
                        งานทั้งหมด
                      </div>

                      <div class="h1 fw-bold text-primary mb-1">
                        <?= count($tasks) ?>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100 monitor-card clickable shadow-sm border-0" data-type="pending">
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                    <!-- จำนวนงาน -->
                    <div>
                      <!-- วันที่ -->
                      <div class="fw-bold fs-5 mt-2 text-dark">
                        <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                      </div>
                      <!-- สถานะ -->
                      <div class="badge bg-warning text-dark px-3 py-2 fs-6 mb-2">
                        รอรับงาน
                      </div>

                      <div class="h1 fw-bold text-primary mb-1">
                        <?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'pending')) ?>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100 monitor-card clickable shadow-sm border-0" data-type="received">
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                    <!-- จำนวนงาน -->
                    <div>
                      <!-- วันที่ -->
                      <div class="fw-bold fs-5 mt-2 text-dark">
                        <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                      </div>
                      <!-- สถานะ -->
                      <div class="badge bg-warning text-dark px-3 py-2 fs-6 mb-2">
                        รับงาน
                      </div>

                      <div class="h1 fw-bold text-primary mb-1">
                        <?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'received')) ?>
                      </div>

                    </div>
                  </div>
                </div>
              </div>






              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100 monitor-card clickable shadow-sm border-0" data-type="in_progress">
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                    <!-- จำนวนงาน -->
                    <div>
                      <!-- วันที่ -->
                      <div class="fw-bold fs-5 mt-2 text-dark">
                        <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                      </div>
                      <!-- สถานะ -->
                      <div class="badge bg-warning text-dark px-3 py-2 fs-6 mb-2">
                        กำลังทำ
                      </div>

                      <div class="h1 fw-bold text-primary mb-1">
                        <?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'in_progress')) ?>
                      </div>

                    </div>
                  </div>
                </div>
              </div>



              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100 monitor-card clickable shadow-sm border-0" data-type="completed">
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                    <!-- จำนวนงาน -->
                    <div>
                      <!-- วันที่ -->
                      <div class="fw-bold fs-5 mt-2 text-dark">
                        <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                      </div>
                      <!-- สถานะ -->
                      <div class="badge bg-warning text-dark px-3 py-2 fs-6 mb-2">
                        เสร็จสิ้น
                      </div>

                      <div class="h1 fw-bold text-primary mb-1">
                        <?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'completed')) ?>
                      </div>

                    </div>
                  </div>
                </div>
              </div>




              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100 monitor-card clickable shadow-sm border-0" data-type="accepted">
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                    <!-- จำนวนงาน -->
                    <div>
                      <!-- วันที่ -->
                      <div class="fw-bold fs-5 mt-2 text-dark">
                        <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                      </div>
                      <!-- สถานะ -->
                      <div class="badge bg-warning text-dark px-3 py-2 fs-6 mb-2">
                        ปิดงาน
                      </div>

                      <div class="h1 fw-bold text-primary mb-1">
                        <?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'accepted')) ?>
                      </div>

                    </div>
                  </div>
                </div>
              </div>




              <div class="col-6 col-sm-4 col-md-3 col-lg">
                <div class="card h-100 monitor-card clickable shadow-sm border-0" data-type="overdue">
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                    <!-- จำนวนงาน -->
                    <div>
                      <!-- วันที่ -->
                      <div class="fw-bold fs-5 mt-2 text-dark">
                        <?= htmlspecialchars(formatDateFilter($current_day, $current_month, $current_year)) ?>
                      </div>
                      <!-- สถานะ -->
                      <div class="badge bg-warning text-dark px-3 py-2 fs-6 mb-2">
                        เลยกำหนด
                      </div>

                      <div class="h1 fw-bold text-primary mb-1">
                        <?= count(array_filter($tasks, fn($t) => $t['task_status'] === 'overdue')) ?>
                      </div>

                    </div>
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

                      <span class="fw-bold text-dark fs-3"><?= number_format($totalServiceRequests) ?></span>
                    </h5>
                    <small class="text-muted"><strong>รายการเอกสารที่ขอเข้าทั้งหมด</strong></small>
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

                      <span class="fw-bold text-dark fs-3"><?= number_format($totalDocuments) ?></span>
                    </h5>
                    <small class="text-muted"><strong>จำนวนเแผนกทั้งหหมดที่ใช้บริการ</strong></small>
                  </div>
                </div>
              </div>
            </div>



            <div class="col-sm-6 col-lg-3">
              <div class="card p-3">
                <div class="d-flex align-items-center">
                  <span class="stamp stamp-md bg-warning me-3">
                    <i class="fa fa-hourglass-half"></i>
                  </span>
                  <div>
                    <h5 class="mb-1">
                      <span class="fw-bold text-dark fs-3"><?= number_format($totalPendingApprovals) ?></span>
                    </h5>
                    <small class="text-muted"><strong>จำนวนรายการที่ยังอยู่ในขั้นตอนอนุมัติ</strong></small>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-lg-3">
              <div class="card p-3">
                <div class="d-flex align-items-center">
                  <span class="stamp stamp-md bg-success me-3">
                    <i class="fa fa-check-circle"></i>
                  </span>
                  <div>
                    <h5 class="mb-1">
                      <span class="fw-bold text-success fs-3"><?= count($tasks) ?></span>
                    </h5>
                    <small class="text-muted"><strong>จำนวนรายการที่ผ่านขั้นตอนอนุมัติแล้ว</strong></small>
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
                    <canvas id="multipleBarChart3"></canvas>
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




  <div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog modal-xl custom-modal">

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">รายละเอียดงาน</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="taskList">กำลังโหลด...</div>
        </div>
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
    document.querySelectorAll('.monitor-card').forEach(card => {
      card.addEventListener('click', function() {
        let type = this.getAttribute('data-type');

        // เปิด modal
        let modal = new bootstrap.Modal(document.getElementById('taskModal'));
        modal.show();

        // โหลดงานจากไฟล์ PHP (ส่ง type และ filter ที่เลือกอยู่ไปด้วย)
        let params = new URLSearchParams(window.location.search);
        params.set("popup", "1");
        params.set("status", type);

        fetch("assignor_dashboard2.php?" + params.toString())
          .then(res => res.text())
          .then(html => {
            document.getElementById('taskList').innerHTML = html;
          });
      });
    });
  </script>


<script> 
    const labels = ['รอรับงาน', 'กำลังทำ', 'เสร็จสิ้น'];
    const backgroundColors = ['#f3545d', '#fbfd8e', '#02ff63'];
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
            data: dev.data.slice(0, 3), 
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
              position: 'bottom'
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
          },
          onClick: (evt, elements) => {
            if (elements.length > 0) {
              const index = elements[0].index;
              const statusMap = ['pending', 'in_progress', 'completed']; // ✅ เหลือ 3 อันตรงกับ labels
              const status = statusMap[index];

              let modal = new bootstrap.Modal(document.getElementById('taskModal'));
              modal.show();

              let params = new URLSearchParams(window.location.search);
              params.set("popup", "1");
              params.set("status", status);
              params.set("dev_id", dev.id);

              fetch("assignor_dashboard2.php?" + params.toString())
                .then(res => res.text())
                .then(html => {
                  document.getElementById('taskList').innerHTML = html;
                });
            }
          }
        },
        plugins: [ChartDataLabels]
      });
    });
</script>




  <script>
    var barData = <?= json_encode($barData) ?>;

    var myComboChart3 = new Chart(document.getElementById('multipleBarChart3'), {
      type: "bar", // ชนิดหลัก แต่ datasets สามารถกำหนดต่างได้
      data: {
        labels: ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"],
        datasets: [{
            label: "งานทั้งหมด",
            type: "line", // กำหนดเป็นเส้น
            borderColor: "#59d05d",
            backgroundColor: "rgba(89, 208, 93, 0.2)",
            data: barData.all,
            fill: false,
            tension: 0.3,
            yAxisID: "y"
          },
          {
            label: "งานพัฒนา Development",
            type: "bar", // กำหนดเป็นแท่ง
            backgroundColor: "#77d4ff",
            borderColor: "#77d4ff",
            data: barData.development,
            yAxisID: "y"
          },
          {
            label: "งานบริการ Service",
            type: "bar",
            backgroundColor: "#177dff",
            borderColor: "#177dff",
            data: barData.service,
            yAxisID: "y"
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom"
          },
          title: {
            display: true,
            text: " "
          }
        },
        interaction: {
          mode: "index",
          intersect: false
        },
        scales: {
          x: {
            stacked: false
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "จำนวนงาน"
            }
          }
        }
      }
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