<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// ===== รับค่า filter =====
$current_year  = isset($_GET['year'])  && is_numeric($_GET['year'])  ? (int)$_GET['year']  : 'all';
$current_month = isset($_GET['month']) && is_numeric($_GET['month']) ? (int)$_GET['month'] : 'all';
$current_day   = isset($_GET['day'])   && is_numeric($_GET['day'])   ? (int)$_GET['day']   : 'all';

// ====== ถ้าเป็น popup (กดจาก calendar) ======
if (isset($_GET['popup'])) {
    $where  = ["t.developer_user_id = ?"];
    $params = [$developer_id];
// วันที่ที่เลือกจาก calendar
if ($current_year !== 'all' && $current_month !== 'all' && $current_day !== 'all') {
    $selected_date = sprintf("%04d-%02d-%02d", $current_year, $current_month, $current_day);

    // งานจะแสดงตั้งแต่วันสร้าง → วัน deadline (ไม่ตัดออกหลังเสร็จ)
    $where[] = "DATE(?) BETWEEN DATE(sr.created_at) AND DATE(sr.deadline)";
    $params[] = $selected_date;
}


    // ===== SQL หลัก =====
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
          WHEN sr.deadline IS NOT NULL AND sr.deadline < CURDATE() AND t.task_status NOT IN ('completed','accepted')
            THEN 'overdue'
          WHEN sr.deadline IS NOT NULL AND DATEDIFF(sr.deadline, CURDATE()) <= 2 AND t.task_status NOT IN ('completed','accepted')
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
    ";

    // ===== เงื่อนไขการกรอง =====
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " 
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

 // ===== แสดงผลเป็นตาราง =====
if ($tasks) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover align-middle">';
    echo '<thead class="table-primary text-center">
            <tr>
                <th>#</th>
                <th>หัวข้อ</th>
                <th>ผู้พัฒนา</th>
                <th>ประเภทงาน</th>
                <th>ผู้ขอบริการ</th>
                <th>แผนก</th>
                <th>สถานะ</th>
                <th>กำหนดส่ง</th>
                <th>วันที่ขอ</th>
            </tr>
          </thead><tbody>';

    $i = 1;
   foreach ($tasks as $t) {
    // กำหนดส่งจริง
    $due_date = !empty($t['deadline']) 
        ? date('d/m/Y', strtotime($t['deadline'])) 
        : '-';

   // Badge สถานะงาน
switch ($t['task_status']) {
    case 'pending':
        $status_badge = '<span class="badge bg-secondary">รอดำเนินการ</span>';
        break;
    case 'received':
        $status_badge = '<span class="badge bg-info text-dark">รับงานแล้ว</span>';
        break;
    case 'in_progress':
        $status_badge = '<span class="badge bg-warning text-dark">กำลังทำ</span>';
        break;
    case 'on_hold':
        $status_badge = '<span class="badge bg-dark">พักงาน</span>';
        break;
    case 'completed':
        $status_badge = '<span class="badge bg-success">เสร็จสิ้น</span>';
        break;
    case 'accepted':
        $status_badge = '<span class="badge bg-primary">ผู้ใช้รับงานแล้ว</span>';
        break;
    default:
        $status_badge = '<span class="badge bg-light text-dark">' . $t['task_status'] . '</span>';
        break;
}

// Badge deadline
switch ($t['deadline_status']) {
    case 'overdue':
        $deadline_badge = '<span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> เลยกำหนด</span>';
        break;
    case 'due_soon':
        $deadline_badge = '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> ใกล้ครบกำหนด</span>';
        break;
    default:
        $deadline_badge = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> ปกติ</span>';
        break;
}


    echo "<tr>
            <td class='text-center'>{$i}</td>
            <td>" . htmlspecialchars($t['title']) . "</td>
            <td>" . htmlspecialchars($t['dev_name'] . ' ' . $t['dev_lastname']) . "</td>
            <td>" . htmlspecialchars($t['service_name']) . "</td>
            <td>" . htmlspecialchars($t['requester_name'] . ' ' . $t['requester_lastname']) . "</td>
            <td>" . htmlspecialchars($t['requester_department']) . "</td>
            <td class='text-center'>{$status_badge}</td>
            <td class='text-center'>{$due_date}<br>{$deadline_badge}</td>
            <td class='text-center'>" . date('d/m/Y', strtotime($t['request_created_at'])) . "</td>
          </tr>";
    $i++;
}
    echo '</tbody></table></div>';
} else {
    echo '<div class="alert alert-info text-center p-4">
            <i class="fas fa-info-circle fa-2x d-block mb-2"></i> 
            ไม่มีงานในวันนี้
          </div>';
}


    exit;
}
