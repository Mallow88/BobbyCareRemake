<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];



// ฟังก์ชันสร้างเลขที่เอกสาร
function generateDocumentNumber($conn, $warehouse_number, $code_name)
{
    try {
        $current_year = date('y');
        $current_month = date('n');

        $stmt = $conn->prepare("
            SELECT COALESCE(MAX(running_number), 0) as max_running 
            FROM document_numbers
            WHERE warehouse_number = ? AND code_name = ? AND year = ? AND month = ?
        ");
        $stmt->execute([$warehouse_number, $code_name, $current_year, $current_month]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $next_running = ($result['max_running'] ?? 0) + 1;
        $running_str = str_pad($next_running, 3, '0', STR_PAD_LEFT);

        $document_number = $warehouse_number . '-' . $code_name . '-' . $current_year . '-' . $current_month . '-' . $running_str;

        $insert_stmt = $conn->prepare("
            INSERT INTO document_numbers 
            (warehouse_number, code_name, year, month, running_number, document_number) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insert_stmt->execute([$warehouse_number, $code_name, $current_year, $current_month, $next_running, $document_number]);

        $document_id = $conn->lastInsertId();

        return ['document_number' => $document_number, 'document_id' => $document_id];
    } catch (Exception $e) {
        error_log("Error generating document number: " . $e->getMessage());
        throw $e;
    }
}

// การประมวลผล POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $work_category = $_POST['work_category'] ?? null;
    $service_id = $_POST['service_id'] ?? null;
    
    try {
        $conn->beginTransaction();

        // ตรวจสอบ category
        if (empty($work_category)) {
            throw new Exception("กรุณาเลือกหัวข้องานคลัง");
        }

        // ดึงข้อมูล service
        $service_stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
        $service_stmt->execute([$service_id]);
        $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$service) {
            throw new Exception("ไม่พบประเภทบริการที่เลือก");
        }

        // แยก warehouse_number และ code_name
        $work_parts = explode('-', $work_category);
        if (count($work_parts) !== 2) {
            throw new Exception("รูปแบบหัวข้องานคลังไม่ถูกต้อง");
        }
        $warehouse_number = $work_parts[0];
        $code_name = $work_parts[1];

        // ✅ สร้างเลขที่เอกสาร
        $doc_result = generateDocumentNumber($conn, $warehouse_number, $code_name);
        $document_number = $doc_result['document_number'];
        $document_id = $doc_result['document_id'];

        // ✅ สร้าง service request (สมมุติสร้างแล้วได้ $request_id)
        // $request_id = ... (สร้างงานใหม่และได้ ID กลับมา)

        
        // ✅ อัปเดตให้ผูกเลขเอกสารกับ service request
        $update_doc = $conn->prepare("UPDATE document_numbers SET service_request_id = ? WHERE id = ?");
        $update_doc->execute([$request_id, $document_id]);

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("เกิดข้อผิดพลาด: " . $e->getMessage());
        // redirect หรือแสดง error message ตามต้องการ
    }
}

// ดึงข้อมูล departments
$dept_stmt = $conn->prepare("SELECT * FROM departments WHERE is_active = 1 ORDER BY warehouse_number, code_name");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่ม departments ตาม warehouse
$dept_by_warehouse = [];
foreach ($departments as $dept) {
    $warehouse_names = [
        '01' => 'RDC',
        '02' => 'CDC',
        '03' => 'BDC'
    ];
    $warehouse_name = $warehouse_names[$dept['warehouse_number']] ?? $dept['warehouse_number'];
    $dept_by_warehouse[$warehouse_name][] = $dept;
}






// จัดการ subtask actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_subtask'])) {
        $subtask_id = $_POST['subtask_id'];
        $new_status = $_POST['new_status'];
        $notes = trim($_POST['notes'] ?? '');

        try {
            $conn->beginTransaction();

            // อัปเดตสถานะ subtask
            $update_subtask = $conn->prepare("
                UPDATE task_subtasks 
                SET status = ?, notes = ?, 
                    started_at = CASE WHEN ? = 'in_progress' AND started_at IS NULL THEN NOW() ELSE started_at END,
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE NULL END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $update_subtask->execute([$new_status, $notes, $new_status, $new_status, $subtask_id]);

            // บันทึก log
            $log_stmt = $conn->prepare("
                INSERT INTO subtask_logs (subtask_id, old_status, new_status, changed_by, notes) 
                VALUES (?, (SELECT status FROM task_subtasks WHERE id = ? LIMIT 1), ?, ?, ?)
            ");
            $log_stmt->execute([$subtask_id, $subtask_id, $new_status, $developer_id, $notes]);

            // คำนวณ progress รวม
            $task_id_stmt = $conn->prepare("SELECT task_id FROM task_subtasks WHERE id = ?");
            $task_id_stmt->execute([$subtask_id]);
            $task_id = $task_id_stmt->fetchColumn();

            $progress_stmt = $conn->prepare("
                SELECT SUM(CASE WHEN status = 'completed' THEN percentage ELSE 0 END) as total_progress
                FROM task_subtasks 
                WHERE task_id = ?
            ");
            $progress_stmt->execute([$task_id]);
            $total_progress = $progress_stmt->fetchColumn() ?? 0;

            // อัปเดต progress ในตาราง tasks
            $update_task = $conn->prepare("UPDATE tasks SET progress_percentage = ? WHERE id = ?");
            $update_task->execute([$total_progress, $task_id]);

            // ถ้า progress = 100% ให้เปลี่ยนสถานะเป็น completed
            if ($total_progress >= 100) {
                $complete_task = $conn->prepare("
                    UPDATE tasks 
                    SET task_status = 'completed', completed_at = NOW() 
                    WHERE id = ? AND task_status != 'completed'
                ");
                $complete_task->execute([$task_id]);

                $update_sr = $conn->prepare("UPDATE service_requests SET developer_status = 'completed' WHERE id = (SELECT service_request_id FROM tasks WHERE id = ?)");
                $update_sr->execute([$task_id]);
            }

            $conn->commit();
            header("Location: tasks_board.php");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}


// สร้างงานใหม่ 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $user_id = $_POST['user_id']; // คนที่ร้องขอ
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $service_id = $_POST['service_id'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $estimated_days = $_POST['estimated_days'] ?? 1;
    $deadline = $_POST['deadline'] ?? null;

    if ($title && $description && $service_id) {
        try {
            $conn->beginTransaction();

            // สร้าง service request status ตั้งเป็น 'approved' ทันที (แสดงว่าไม่ต้องรออนุมัติ)
            //current_step = developer_self_created แสดงว่ามาจากการสร้างเอง
            //user_id = developer_id
            $stmt = $conn->prepare("
                INSERT INTO service_requests (
                    user_id, title, description, service_id, priority, 
                    estimated_days, deadline, status, current_step, developer_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', 'developer_self_created', 'received')
            ");
            $stmt->execute([$user_id, $title, $description, $service_id, $priority, $estimated_days, $deadline]);
            $request_id = $conn->lastInsertId();





// ✅ แยก work_category เพื่อสร้างเลขเอกสาร
$work_category = $_POST['work_category'] ?? null;
if (!$work_category) {
    throw new Exception("กรุณาเลือกหัวข้องานคลัง");
}

$work_parts = explode('-', $work_category);
if (count($work_parts) !== 2) {
    throw new Exception("รูปแบบหัวข้องานคลังไม่ถูกต้อง");
}

$warehouse_number = $work_parts[0];
$code_name = $work_parts[1];

// ✅ สร้างเลขที่เอกสาร
$doc_result = generateDocumentNumber($conn, $warehouse_number, $code_name);
$document_id = $doc_result['document_id'];

// ✅ ผูก service_request_id กับเลขเอกสาร
$update_doc = $conn->prepare("UPDATE document_numbers SET service_request_id = ? WHERE id = ?");
$update_doc->execute([$request_id, $document_id]);











            // สร้าง task task_status = 'received' — dev รับงานแล้ว สามารถเเก้เป็น completed ได้
            $stmt = $conn->prepare("
                INSERT INTO tasks (
                    service_request_id, developer_user_id, task_status, 
                    progress_percentage, started_at, accepted_at, estimated_completion
                ) VALUES (?, ?, 'received', 10, NOW(), NOW(), ?)
            ");
            $completion_date = $deadline ?: date('Y-m-d', strtotime('+' . $estimated_days . ' days'));
            $stmt->execute([$request_id, $developer_id, $completion_date]);

            // ✅ จัดการไฟล์แนบ (หลังจากมี $request_id แล้วเท่านั้น)
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar'];
                $max_file_size = 10 * 1024 * 1024; // 10MB

                foreach ($_FILES['attachments']['name'] as $key => $filename) {
                    $error_code = $_FILES['attachments']['error'][$key];

                    if ($error_code === UPLOAD_ERR_OK) {
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        if (!in_array($file_extension, $allowed_types)) {
                            throw new Exception("ไฟล์ $filename ไม่ใช่ประเภทที่อนุญาต");
                        }

                        if ($_FILES['attachments']['size'][$key] > $max_file_size) {
                            throw new Exception("ไฟล์ $filename มีขนาดใหญ่เกินไป");
                        }

                        $stored_filename = $request_id . '_' . time() . '_' . $key . '.' . $file_extension;
                        $upload_path = $upload_dir . $stored_filename;

                        if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $upload_path)) {
                            $file_stmt = $conn->prepare("
                            INSERT INTO request_attachments (service_request_id, original_filename, stored_filename, file_size, file_type) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                            $file_stmt->execute([
                                $request_id,
                                $filename,
                                $stored_filename,
                                $_FILES['attachments']['size'][$key],
                                $file_extension
                            ]);
                        } else {
                            throw new Exception("ไม่สามารถย้ายไฟล์ $filename ไปยังโฟลเดอร์ปลายทางได้");
                        }
                    } elseif ($error_code !== UPLOAD_ERR_NO_FILE) {
                        // กรณี error ในการอัปโหลดไฟล์อื่นๆ เช่น ไฟล์ใหญ่เกินไป, upload หยุดกลางคัน
                        throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์ $filename รหัสข้อผิดพลาด: $error_code");
                    }
                }
            }
            $conn->commit();
            header("Location: tasks_board.php");
            exit(); // <--- ออกจากสคริปต์เลยหลัง redirect
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}


// อัปเดตสถานะงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];
    $progress = $_POST['progress'];
    $notes = $_POST['notes'] ?? '';

    try {
        $conn->beginTransaction();

        // อัปเดตสถานะใน tasks
        $stmt = $conn->prepare("UPDATE tasks SET task_status = ?, progress_percentage = ?, developer_notes = ?, updated_at = NOW() WHERE id = ? AND developer_user_id = ?");
        $stmt->execute([$new_status, $progress, $notes, $task_id, $developer_id]);

        // อัปเดตสถานะใน service_requests
        $stmt = $conn->prepare("UPDATE service_requests SET developer_status = ? WHERE id = (SELECT service_request_id FROM tasks WHERE id = ?)");
        $stmt->execute([$new_status, $task_id]);

        // บันทึก log
        $stmt = $conn->prepare("INSERT INTO task_status_logs (task_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$task_id, '', $new_status, $developer_id, $notes]);

        // ถ้าเป็นการส่งงาน ให้อัปเดต completed_at
        if ($new_status === 'completed') {
            $stmt = $conn->prepare("UPDATE tasks SET completed_at = NOW() WHERE id = ?");
            $stmt->execute([$task_id]);
        }

        $conn->commit();
        header("Location: tasks_board.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ลบงานส่วนตัว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];

    try {
        $conn->beginTransaction();

        // ตรวจสอบว่าเป็นงานส่วนตัวหรือไม่
        $check_stmt = $conn->prepare("
            SELECT sr.current_step FROM tasks t
            JOIN service_requests sr ON t.service_request_id = sr.id
            WHERE t.id = ? AND t.developer_user_id = ?
        ");
        $check_stmt->execute([$task_id, $developer_id]);
        $task_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($task_data && $task_data['current_step'] === 'developer_self_created') {
            // ลบ task และ service_request
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND developer_user_id = ?");
            $stmt->execute([$task_id, $developer_id]);

            $stmt = $conn->prepare("DELETE FROM service_requests WHERE id = (SELECT service_request_id FROM tasks WHERE id = ?)");
            $stmt->execute([$task_id]);

            // $success = "ลบงานเรียบร้อยแล้ว";
        } else {
            $error = "ไม่สามารถลบงานที่ได้รับมอบหมายได้";
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ดึงงานทั้งหมดของ developer
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.created_at as request_date,
        sr.current_step,
        sr.priority,
        sr.deadline,
        requester.name AS requester_name,
        requester.lastname AS requester_lastname,
        requester.employee_id,
        requester.position,
        requester.department,
        ur.rating,
        ur.review_comment,
        ur.status as review_status,
        ur.revision_notes,
        ur.reviewed_at as user_reviewed_at,
        aa.estimated_days,
        s.name as service_name,
        s.category as service_category,
        dn.document_number AS document_number
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users requester ON sr.user_id = requester.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    WHERE t.developer_user_id = ?
    ORDER BY t.created_at DESC
");



$stmt->execute([$developer_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการ services ประเภท service
$services_stmt = $conn->prepare("SELECT * FROM services WHERE category = 'service' AND is_active = 1 ORDER BY name");
$services_stmt->execute();
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

$users_stmt = $conn->prepare("
    SELECT id, name, lastname 
    FROM users 
    WHERE role IN ('userservice', 'user') 
    ORDER BY name
");
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);



// จัดกลุ่มงานตามสถานะ
$tasks_by_status = [
    'pending' => [],
    'received' => [],
    'in_progress' => [],
    'on_hold' => [],
    'completed' => []
];

foreach ($tasks as $task) {
    $status = $task['task_status'];
    if (isset($tasks_by_status[$status])) {
        $tasks_by_status[$status][] = $task;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BobbyCareDev</title>
    <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="css/tasks_board.css">
    <style>

        :root {
            --primary-gradient: linear-gradient(135deg, #ffffff 0%, #341355 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        .task-card.priority-urgent {
            border-left: 4px solid #dc2626 !important;
            box-shadow: 0 0 15px rgba(220, 38, 38, 0.3);
            order: 1;
        }

        .task-card.priority-high {
            border-left: 4px solid #ef4444 !important;
            order: 2;
        }

        .task-card.priority-medium {
            border-left: 4px solid #f59e0b !important;
            order: 3;
        }

        .task-card.priority-low {
            border-left: 4px solid #10b981 !important;
            opacity: 0.9;
            order: 4;
        }

        .tasks-container {
            display: flex;
            flex-direction: column;
        }

        .task-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }

        .detail-btn {
            background: rgba(102, 126, 234, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.7;
        }

        .detail-btn:hover {
            opacity: 1;
            transform: scale(1.1);
            background: rgba(102, 126, 234, 1);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">

        <div class="container">
            <!-- โลโก้ + ชื่อระบบ -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dev_index.php">
                <img src="../img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
                <span class="page-title">Tasks-Board</span>
            </a>


            <!-- ปุ่ม toggle สำหรับ mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- เมนู -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- ซ้าย: เมนูหลัก -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> หน้าหลัก</a>
                    </li> -->
                    <li class="nav-item">

                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calendar.php"><i class="fas fa-tasks me-1"></i> ปฏิทิน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="completed_reviews.php"><i class="fas fa-chart-bar me-1"></i> งานที่รีวิว</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="export_report.php"><i class="fas fa-chart-bar me-1"></i>Report</a>
                    </li>
                </ul>

                <!-- ขวา: ผู้ใช้งาน -->
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <!-- <li class="nav-item d-flex align-items-center text-dark me-3">
                        <i class="fas fa-user-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['name']) ?>
                    </li>
                     -->
                    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                        <i class="fas fa-plus me-2"></i>สร้างงานใหม่
                    </button>

                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5 pt-5">
        <?php if (!empty($error)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Kanban Board -->
        <div class="kanban-board">
            <!-- รอรับ -->
            <!-- คอลัมน์: รอรับ -->
            <div class="kanban-column pending">
                <!-- หัวคอลัมน์ -->
                <div class="column-header d-flex justify-content-between align-items-center">
                    <div class="column-title">
                        <i class="fas fa-hourglass-half me-1"></i>
                        <span>รอรับ</span>
                    </div>
                    <span class="task-count badge bg-secondary"><?= count($tasks_by_status['pending']) ?></span>
                </div>

                <div class="tasks-container" data-status="pending">
                    <?php if (empty($tasks_by_status['pending'])): ?>
                        <div class="empty-column text-center text-muted py-4">
                            <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                            <p class="mb-0">ไม่มีงานที่รอรับ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['pending'] as $task): ?>
                            <div class="task-card pending mb-3 p-3 border rounded <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">

                                <!-- Badge + ปุ่มลบ สำหรับ self-created -->
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-secondary">Service</span>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- ชื่อเรื่อง -->
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($task['title']) ?></h6>

                                <!-- Badge ประเภทงาน / ความสำคัญ -->
                                <?php if ($task['service_name']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-info text-dark me-1">
                                            <?php if ($task['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($task['service_name']) ?>
                                        </span>
                                        <?php if ($task['priority']): ?>
                                            <span class="badge bg-<?= $task['priority'] === 'urgent' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                                <?php
                                                $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                                echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- คำอธิบาย -->
                                <p class="small text-muted mb-2">
                                    <?= nl2br(htmlspecialchars(substr($task['description'], 0, 1000))) ?>
                                    <?= strlen($task['description']) > 1000 ? '...' : '' ?>
                                </p>

                                <!-- ระยะเวลา -->
                                <?php if ($task['estimated_days']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i>
                                            ประมาณ <?= $task['estimated_days'] ?> วัน
                                        </span>
                                        <?php if ($task['deadline']): ?>
                                            <span class="badge bg-danger ms-1">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- ผู้ขอ / วันที่ -->
                                <div class="d-flex justify-content-between text-muted small mt-2">
                                    <div>
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?= date('d/m/Y', strtotime($task['request_date'])) ?>
                                    </div>
                                </div>

                                <!-- ปุ่มดูรายละเอียด + รับงาน -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <button class="btn btn-sm btn-primary" onclick="updateStatus(<?= $task['id'] ?>, 'received')">
                                        รับงาน
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- คอลัมน์: รับแล้ว -->
            <div class="kanban-column received">
                <!-- หัวคอลัมน์ -->
                <div class="column-header d-flex justify-content-between align-items-center">
                    <div class="column-title">
                        <i class="fas fa-check-circle me-1"></i>
                        <span>รับแล้ว</span>
                    </div>
                    <span class="task-count badge bg-primary"><?= count($tasks_by_status['received']) ?></span>
                </div>

                <!-- พื้นที่แสดงการ์ดงาน -->
                <div class="tasks-container" data-status="received">
                    <?php if (empty($tasks_by_status['received'])): ?>
                        <div class="empty-column text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">ไม่มีงานที่รับแล้ว</p>
                        </div>
                    <?php else: ?>


                        <?php foreach ($tasks_by_status['received'] as $task): ?>
                            <div class="task-card received mb-3 p-3 border rounded <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">

                                <!-- Badge + ปุ่มลบ สำหรับ self-created -->
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-secondary">Service</span>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- ชื่อเรื่อง -->
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($task['title']) ?></h6>

                                <!-- Badge ประเภทงาน / ความสำคัญ -->
                                <?php if ($task['service_name']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-info text-dark me-1">
                                            <?php if ($task['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($task['service_name']) ?>
                                        </span>
                                        <?php if ($task['priority']): ?>
                                            <span class="badge bg-<?= $task['priority'] === 'urgent' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                                <?php
                                                $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                                echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- คำอธิบาย -->
                                <p class="small text-muted mb-2">
                                    <?= nl2br(htmlspecialchars(substr($task['description'], 0, 1000))) ?>
                                    <?= strlen($task['description']) > 1000 ? '...' : '' ?>
                                </p>

                                <!-- ระยะเวลา -->
                                <?php if ($task['estimated_days']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i>
                                            ประมาณ <?= $task['estimated_days'] ?> วัน
                                        </span>
                                        <?php if ($task['deadline']): ?>
                                            <span class="badge bg-danger ms-1">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- ผู้ขอ / วันที่ -->
                                <div class="d-flex justify-content-between text-muted small mt-2">
                                    <div>
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?= date('d/m/Y', strtotime($task['request_date'])) ?>
                                    </div>
                                </div>

                                <!-- ปุ่มดำเนินการ -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $task['id'] ?>, 'in_progress')">
                                        เริ่มทำ
                                    </button>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($task['service_category'] === 'development' && $task['current_step'] !== 'developer_self_created'): ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="showSubtasks(<?= $task['id'] ?>)">
                                                <i class="fas fa-tasks"></i> Subtasks
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- กำลังดำเนินการ -->
            <!-- คอลัมน์: กำลังดำเนินการ -->
            <div class="kanban-column in_progress">
                <!-- หัวคอลัมน์ -->
                <div class="column-header d-flex justify-content-between align-items-center">
                    <div class="column-title">
                        <i class="fas fa-cog fa-spin me-1"></i>
                        <span>กำลังดำเนินการ</span>
                    </div>
                    <span class="task-count badge bg-warning text-dark"><?= count($tasks_by_status['in_progress']) ?></span>
                </div>

                <div class="tasks-container" data-status="in_progress">
                    <?php if (empty($tasks_by_status['in_progress'])): ?>
                        <div class="empty-column text-center text-muted py-4">
                            <i class="fas fa-cog fa-2x mb-2"></i>
                            <p class="mb-0">ไม่มีงานที่กำลังทำ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['in_progress'] as $task): ?>
                            <div class="task-card in_progress mb-3 p-3 border rounded <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">

                                <!-- Badge + ปุ่มลบ สำหรับ self-created -->
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-secondary">Service</span>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- ชื่อเรื่อง -->
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($task['title']) ?></h6>

                                <!-- Badge ประเภทงาน / ความสำคัญ -->
                                <?php if ($task['service_name']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-info text-dark me-1">
                                            <?php if ($task['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($task['service_name']) ?>
                                        </span>
                                        <?php if ($task['priority']): ?>
                                            <span class="badge bg-<?= $task['priority'] === 'urgent' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                                <?php
                                                $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                                echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- คำอธิบาย -->
                                <p class="small text-muted mb-2">
                                    <?= nl2br(htmlspecialchars(substr($task['description'], 0, 1000))) ?>
                                    <?= strlen($task['description']) > 1000 ? '...' : '' ?>
                                </p>

                                <!-- ระยะเวลา -->
                                <?php if ($task['estimated_days']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i>
                                            ประมาณ <?= $task['estimated_days'] ?> วัน
                                        </span>
                                        <?php if ($task['deadline']): ?>
                                            <span class="badge bg-danger ms-1">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- ผู้ขอ / วันที่ -->
                                <div class="d-flex justify-content-between text-muted small mt-2">
                                    <div>
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?= date('d/m/Y', strtotime($task['request_date'])) ?>
                                    </div>
                                </div>

                                <!-- ปุ่มจัดการสถานะ -->
                                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-1">
                                    <div class="d-flex gap-1 flex-grow-1">
                                        <button class="btn btn-sm btn-warning flex-grow-1" onclick="updateStatus(<?= $task['id'] ?>, 'on_hold')">
                                            พักงาน
                                        </button>
                                        <button class="btn btn-sm btn-success flex-grow-1" onclick="showCompleteModal(<?= $task['id'] ?>)">
                                            ส่งงาน
                                        </button>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($task['service_category'] === 'development' && $task['current_step'] !== 'developer_self_created'): ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="showSubtasks(<?= $task['id'] ?>)">
                                                <i class="fas fa-tasks"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- คอลัมน์: พักงาน -->
            <div class="kanban-column on_hold">
                <!-- หัวคอลัมน์ -->
                <div class="column-header d-flex justify-content-between align-items-center">
                    <div class="column-title">
                        <i class="fas fa-pause-circle me-1"></i>
                        <span>พักงาน</span>
                    </div>
                    <span class="task-count badge bg-secondary"><?= count($tasks_by_status['on_hold']) ?></span>
                </div>

                <div class="tasks-container" data-status="on_hold">
                    <?php if (empty($tasks_by_status['on_hold'])): ?>
                        <div class="empty-column text-center text-muted py-4">
                            <i class="fas fa-pause-circle fa-2x mb-2"></i>
                            <p class="mb-0">ไม่มีงานที่พัก</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['on_hold'] as $task): ?>
                            <div class="task-card on_hold mb-3 p-3 border rounded <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">

                                <!-- Badge + ปุ่มลบ สำหรับ self-created -->
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-secondary">Service</span>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $task['id'] ?>)" title="ลบงาน">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- ชื่อเรื่อง -->
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($task['title']) ?></h6>

                                <!-- Badge ประเภทงาน / ความสำคัญ -->
                                <?php if ($task['service_name']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-info text-dark me-1">
                                            <?php if ($task['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($task['service_name']) ?>
                                        </span>
                                        <?php if ($task['priority']): ?>
                                            <span class="badge bg-<?= $task['priority'] === 'urgent' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                                <?php
                                                $priorities = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                                                echo $priorities[$task['priority']] ?? 'ปานกลาง';
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- คำอธิบาย -->
                                <p class="small text-muted mb-2">
                                    <?= nl2br(htmlspecialchars(substr($task['description'], 0, 1000))) ?>
                                    <?= strlen($task['description']) > 1000 ? '...' : '' ?>
                                </p>

                                <!-- ระยะเวลา -->
                                <?php if ($task['estimated_days']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i>
                                            ประมาณ <?= $task['estimated_days'] ?> วัน
                                        </span>
                                        <?php if ($task['deadline']): ?>
                                            <span class="badge bg-danger ms-1">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                ภายใน: <?= date('d/m/Y', strtotime($task['deadline'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- ผู้ขอ / วันที่ -->
                                <div class="d-flex justify-content-between text-muted small mt-2">
                                    <div>
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?= date('d/m/Y', strtotime($task['request_date'])) ?>
                                    </div>
                                </div>

                                <!-- ปุ่มทำต่อ / รายละเอียด -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $task['id'] ?>, 'in_progress')">
                                        ทำต่อ
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- เสร็จแล้ว -->
            <!-- คอลัมน์งานที่เสร็จแล้ว -->
            <div class="kanban-column completed">
                <!-- หัวคอลัมน์ -->
                <div class="column-header d-flex justify-content-between align-items-center bg-success text-white px-3 py-2 rounded-top">
                    <div class="column-title d-flex align-items-center">
                        <i class="fas fa-check-double me-2"></i>
                        <span class="fw-bold">เสร็จแล้ว</span>
                    </div>
                    <span class="task-count badge bg-light text-dark"><?= count($tasks_by_status['completed']) ?></span>
                </div>

                <!-- โซนการ์ด -->
                <div class="tasks-container bg-white border border-top-0 rounded-bottom p-2" data-status="completed" style="min-height: 150px;">
                    <?php if (empty($tasks_by_status['completed'])): ?>
                        <div class="empty-column text-center text-muted py-4">
                            <i class="fas fa-check-double fa-2x mb-2"></i>
                            <p class="mb-0">ยังไม่มีงานที่เสร็จ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks_by_status['completed'] as $task): ?>
                            <div class="task-card border rounded mb-3 p-3 shadow-sm bg-light completed <?= $task['current_step'] === 'developer_self_created' ? 'self-created' : '' ?>" data-task-id="<?= $task['id'] ?>">

                                <!-- แสดง badge "Service" ถ้าเป็นงานที่ dev สร้างเอง -->
                                <?php if ($task['current_step'] === 'developer_self_created'): ?>
                                    <span class="badge bg-info text-dark mb-2">Service</span>
                                <?php endif; ?>

                                <!-- ชื่อเรื่อง -->
                                <div class="task-title fw-bold mb-2"><?= htmlspecialchars($task['title']) ?></div>

                                <!-- ปุ่มแอคชัน -->
                                <div class="task-actions position-absolute top-0 end-0 mt-2 me-2">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="showTaskDetail(<?= $task['id'] ?>)" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>

                                <!-- หมวดหมู่บริการ + ความสำคัญ -->
                                <?php if ($task['service_name']): ?>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="badge bg-secondary">
                                            <?php if ($task['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($task['service_name']) ?>
                                        </span>
                                        <?php if ($task['priority']): ?>
                                            <span class="badge bg-<?= $task['priority'] === 'urgent' ? 'danger' : ($task['priority'] === 'high' ? 'warning text-dark' : ($task['priority'] === 'medium' ? 'primary' : 'secondary')) ?>">
                                                <?= ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'][$task['priority']] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- โซนรีวิว -->
                                <?php if ($task['review_status']): ?>
                                    <div class="review-section mt-2 p-2 bg-white border rounded small">
                                        <div class="fw-bold text-success mb-1">
                                            <i class="fas fa-star me-1"></i> รีวิวจากผู้ใช้
                                        </div>

                                        <?php if ($task['rating']): ?>
                                            <div>
                                                <span class="rating-stars"><?= str_repeat('⭐', $task['rating']) ?></span>
                                                <span class="ms-2">(<?= $task['rating'] ?>/5)</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($task['review_comment']): ?>
                                            <div class="text-muted fst-italic my-1">"<?= htmlspecialchars($task['review_comment']) ?>"</div>
                                        <?php endif; ?>

                                        <?php if ($task['user_reviewed_at']): ?>
                                            <div class="text-muted mb-1">
                                                <i class="fas fa-clock me-1"></i> รีวิวเมื่อ: <?= date('d/m/Y H:i', strtotime($task['user_reviewed_at'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            สถานะ:
                                            <?php if ($task['review_status'] === 'accepted'): ?>
                                                <span class="text-success">✅ ยอมรับงาน</span>
                                            <?php elseif ($task['review_status'] === 'revision_requested'): ?>
                                                <span class="text-warning">🔄 ขอแก้ไข</span>
                                            <?php else: ?>
                                                <span class="text-info">⏳ รอรีวิว</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($task['revision_notes']): ?>
                                            <div class="mt-2 p-2 bg-warning bg-opacity-25 rounded">
                                                <strong>ต้องแก้ไข:</strong> <?= htmlspecialchars($task['revision_notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- ผู้ร้องขอ + วันที่ -->
                                <div class="task-meta mt-3 d-flex justify-content-between text-muted small">
                                    <div class="task-requester">
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($task['requester_name'] . ' ' . $task['requester_lastname']) ?>
                                    </div>
                                    <div class="task-date">
                                        <i class="fas fa-calendar-day me-1"></i>
                                        <?= date('d/m/Y', strtotime($task['request_date'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>




        </div>
    </div>

    <!-- Modal รายละเอียดงาน -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-list me-2"></i>รายละเอียดงาน
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>


                <div class="modal-body">


                    <div class="row g-4">
                        <!-- ซ้าย -->
                        <div class="col-md-7">
                            <!-- หัวข้องาน -->
                            <section>
                                <h6 class="text-uppercase text-primary fw-bold mb-2">
                                    <i class="fas fa-heading me-2"></i>หัวข้องาน
                                </h6>
                                <div id="detailTitle" class="bg-light p-3 rounded border-start border-primary border-4 fw-bold"></div>
                            </section>

                            <!-- รายละเอียดงาน -->
                            <section class="mt-4">
                                <h6 class="text-uppercase text-primary fw-bold mb-2">
                                    <i class="fas fa-align-left me-2"></i>รายละเอียดงาน
                                </h6>
                                <div id="detailDescription" class="bg-light p-3 rounded"></div>
                            </section>

                            <!-- ประโยชน์ -->
                            <section id="detailBenefits" class="mt-4" style="display: none;">
                                <h6 class="text-uppercase text-success fw-bold mb-2">
                                    <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                                </h6>
                                <div id="detailBenefitsContent" class="bg-success bg-opacity-10 p-3 rounded border-start border-success border-4"></div>
                            </section>

                            <!-- ผู้ร้องขอ -->
                            <section class="mt-4">
                                <h6 class="text-uppercase text-primary fw-bold mb-2">
                                    <i class="fas fa-user me-2"></i>ข้อมูลผู้ร้องขอ
                                </h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <small class="text-muted">ชื่อ-นามสกุล</small>
                                            <div id="detailRequester" class="fw-bold"></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">รหัสพนักงาน</small>
                                            <div id="detailEmployeeId" class="fw-bold"></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">ตำแหน่ง</small>
                                            <div id="detailPosition" class="fw-bold"></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">หน่วยงาน</small>
                                            <div id="detailDepartment" class="fw-bold"></div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- ขวา -->
                        <div class="col-md-5">
                            <!-- ข้อมูลงาน -->
                            <section>
                                <h6 class="text-uppercase text-primary fw-bold mb-2">
                                    <i class="fas fa-cogs me-2"></i>ข้อมูลงาน
                                </h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="mb-3">
                                        <small class="text-muted">ประเภทบริการ</small>
                                        <div id="detailService" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">หัวข้องานคลัง</small>
                                        <div id="detailWorkCategory" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">ความสำคัญ</small>
                                        <div id="detailPriority" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">กำหนดเสร็จ (วันและเวลา)</small>
                                        <div id="detailEstimatedDays" class="fw-bold"></div>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted">ผู้มอบหมาย</small>
                                        <div id="detailAssignor" class="fw-bold"></div>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <!-- ไฟล์แนบ -->
                        <section class="mt-4">
                            <h6 class="text-uppercase text-primary fw-bold mb-2">
                                <i class="fas fa-paperclip me-2"></i>ไฟล์แนบ
                            </h6>
                            <div id="attachmentsList" class="bg-light p-3 rounded">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">กำลังโหลด...</span>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <!-- ความคืบหน้า -->
                        <section class="mt-4">
                            <h6 class="text-uppercase text-primary fw-bold mb-2">
                                <i class="fas fa-chart-line me-2"></i>ความคืบหน้า
                            </h6>
                            <div class="bg-light p-3 rounded">
                                <div class="mb-3">
                                    <small class="text-muted">สถานะ</small>
                                    <div id="detailStatus" class="fw-bold"></div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">ความคืบหน้า</small>
                                    <div class="progress">
                                        <div id="detailProgress" class="progress-bar bg-primary" role="progressbar"></div>
                                    </div>
                                    <small id="detailProgressText" class="text-muted"></small>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">วันที่รับงาน</small>
                                    <div id="detailAcceptedAt" class="fw-bold"></div>
                                </div>
                                <div>
                                    <small class="text-muted">วันที่ควรเสร็จ</small>
                                    <div id="detailExpectedCompletion" class="fw-bold"></div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>




                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับ Subtasks -->

    <div class="modal fade" id="subtaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks me-2"></i>รายละเอียดขั้นตอนการพัฒนา
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="subtaskContent">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fs-1 text-primary"></i>
                            <p class="mt-3">กำลังโหลดข้อมูล...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สร้างงานใหม่ -->
    <div class="modal fade" id="createTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>สร้างงานใหม่
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form method="post" enctype="multipart/form-data">

                    <div class="modal-body">
                        <input type="hidden" name="create_task" value="1">

                        <div class="row">
                            <div class="col-md-8">


                                <div class="mb-3">
                                    <label for="title" class="form-label fw-bold">หัวข้องาน:</label>
                                    <input type="text" class="form-control" id="title" name="title" required
                                        placeholder="ระบุหัวข้องาน">
                                </div>

                                <div class="mb-3">
                                    <label for="user_id" class="form-label fw-bold">เลือก USER :</label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="">-- เลือกผู้ใช้บริการ --</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= htmlspecialchars($user['id']) ?>">
                                                <?= htmlspecialchars($user['name'] . ' ' . $user['lastname']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>


                                <div class="mb-3">
                                    <label for="description" class="form-label fw-bold">รายละเอียด:</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required
                                        placeholder="อธิบายรายละเอียดงาน"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="service_id" class="form-label fw-bold">ประเภทบริการ:</label>
                                    <select class="form-select" id="service_id" name="service_id" required>
                                        <option value="">-- เลือกประเภทบริการ --</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?= $service['id'] ?>">
                                                <?= htmlspecialchars($service['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label fw-bold">ความสำคัญ:</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">ต่ำ</option>
                                        <option value="medium" selected>ปานกลาง</option>
                                        <option value="high">สูง</option>
                                        <option value="urgent">เร่งด่วน</option>
                                    </select>
                                </div>

                                  <select class="form-select" id="work_category" name="work_category" required>
                                <option value="">-- เลือกหัวข้องานคลัง --</option>
                                <?php foreach ($dept_by_warehouse as $warehouse => $depts): ?>
                                    <optgroup label="<?= $warehouse ?>">
                                        <?php foreach ($depts as $dept): ?>
                                            <option value="<?= $dept['warehouse_number'] ?>-<?= $dept['code_name'] ?>">
                                                <?= $dept['code_name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>

                                <div class="mb-3">
                                    <label for="estimated_days" class="form-label fw-bold">วัน:</label>
                                    <input type="number" class="form-control" id="estimated_days" name="estimated_days"
                                        min="1" max="365" value="1" placeholder="จำนวนวันที่คาดว่าจะใช้">
                                </div>

                                <div class="mb-3">
                                    <label for="deadline" class="form-label fw-bold">กำหนดเสร็จ (วันและเวลา):</label>
                                    <input type="datetime-local" class="form-control" id="deadline" name="deadline">
                                </div>


                                <div class="mb-3">
                                    <label for="attachments" class="form-label">
                                        <i class="fas fa-upload me-2"></i>เลือกไฟล์แนบ
                                    </label>
                                    <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar">
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-gradient">
                            <i class="fas fa-plus me-2"></i>สร้างงาน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับการส่งงาน -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2"></i>ส่งงานที่เสร็จแล้ว
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="completionNotes" class="form-label fw-bold">หมายเหตุการส่งงาน:</label>
                        <textarea class="form-control" id="completionNotes" rows="4"
                            placeholder="อธิบายงานที่ทำเสร็จ, ปัญหาที่พบ, หรือข้อแนะนำ..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success" onclick="submitComplete()">
                        <i class="fas fa-paper-plane me-2"></i>ส่งงาน
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form สำหรับอัปเดตสถานะ -->
    <form id="statusForm" method="post" style="display: none;">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="task_id" id="taskId">
        <input type="hidden" name="new_status" id="newStatus">
        <input type="hidden" name="progress" id="taskProgress">
        <input type="hidden" name="notes" id="taskNotes">
    </form>

    <!-- Form สำหรับลบงาน -->
    <form id="deleteForm" method="post" style="display: none;">
        <input type="hidden" name="delete_task" value="1">
        <input type="hidden" name="task_id" id="deleteTaskId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentTaskId = null;
        let currentCompleteTaskId = null;

        // ดึงข้อมูลรายละเอียดงาน
        function showTaskDetail(taskId) {
            // ค้นหาข้อมูลงานจาก tasks array
            const tasks = <?= json_encode($tasks) ?>;
            const task = tasks.find(t => t.id == taskId);

            if (!task) {
                alert('ไม่พบข้อมูลงาน');
                return;
            }

            // แสดงข้อมูลในป๊อปอัพ
            document.getElementById('detailTitle').textContent = task.title;
            document.getElementById('detailDescription').innerHTML = task.description.replace(/\n/g, '<br>');

            // ข้อมูลผู้ร้องขอ
            document.getElementById('detailRequester').textContent = task.requester_name + ' ' + task.requester_lastname;
            document.getElementById('detailEmployeeId').textContent = task.employee_id || 'ไม่ระบุ';
            document.getElementById('detailPosition').textContent = task.position || 'ไม่ระบุ';
            document.getElementById('detailDepartment').textContent = task.department || 'ไม่ระบุ';

            // ข้อมูลงาน
            document.getElementById('detailService').textContent = task.service_name || 'ไม่ระบุ';
            document.getElementById('detailWorkCategory').textContent = task.document_number;

            // document.getElementById('document_number').textContent = task.document_number || 'ไม่ระบุ';


            const priorityLabels = {
                'urgent': 'เร่งด่วน',
                'high': 'สูง',
                'medium': 'ปานกลาง',
                'low': 'ต่ำ'
            };
            document.getElementById('detailPriority').textContent = priorityLabels[task.priority] || 'ปานกลาง';
            document.getElementById('detailEstimatedDays').textContent = (task.estimated_days + 'วัน,ภายใน ' + task.deadline + ' วัน');
            document.getElementById('detailAssignor').textContent = task.assignor_name || 'ไม่ระบุ';

            // สถานะและความคืบหน้า
            const statusLabels = {
                'pending': 'รอรับ',
                'received': 'รับแล้ว',
                'in_progress': 'กำลังทำ',
                'on_hold': 'พักงาน',
                'completed': 'เสร็จแล้ว'
            };
            document.getElementById('detailStatus').textContent = statusLabels[task.task_status] || 'ไม่ทราบ';

            const progress = task.progress_percentage || 0;
            const progressBar = document.getElementById('detailProgress');
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            document.getElementById('detailProgressText').textContent = 'อัปเดตล่าสุด: ' + new Date(task.updated_at).toLocaleDateString('th-TH');

            // วันที่
            document.getElementById('detailAcceptedAt').textContent = task.accepted_at ?
                new Date(task.accepted_at).toLocaleDateString('th-TH') : 'ยังไม่รับงาน';

            // คำนวณวันที่ควรเสร็จ
            if (task.accepted_at && task.estimated_days) {
                const acceptedDate = new Date(task.accepted_at);
                const expectedDate = new Date(acceptedDate.getTime() + (task.estimated_days * 24 * 60 * 60 * 1000));
                document.getElementById('detailExpectedCompletion').textContent = expectedDate.toLocaleDateString('th-TH');
            } else {
                document.getElementById('detailExpectedCompletion').textContent = 'ไม่สามารถคำนวณได้';
            }

            // แสดงประโยชน์ที่คาดว่าจะได้รับ (ถ้ามี)
            if (task.expected_benefits) {
                document.getElementById('detailBenefits').style.display = 'block';
                document.getElementById('detailBenefitsContent').innerHTML = task.expected_benefits.replace(/\n/g, '<br>');
            } else {
                document.getElementById('detailBenefits').style.display = 'none';
            }

            // โหลดไฟล์แนบ
            loadAttachments(task.service_request_id);

            // เปิด Modal
            const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            modal.show();
        }

        // โหลดไฟล์แนบ
        function loadAttachments(serviceRequestId) {
            const attachmentsList = document.getElementById('attachmentsList');

            fetch(`../includes/get_attachments.php?service_request_id=${serviceRequestId}`)
                .then(response => response.text())
                .then(html => {
                    if (html.trim() === '') {
                        attachmentsList.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-inbox fa-2x mb-2"></i><p>ไม่มีไฟล์แนบ</p></div>';
                    } else {
                        attachmentsList.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error loading attachments:', error);
                    attachmentsList.innerHTML = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>เกิดข้อผิดพลาดในการโหลดไฟล์แนบ</p></div>';
                });
        }

        function updateStatus(taskId, newStatus) {
            let progress = 0;
            switch (newStatus) {
                case 'received':
                    progress = 10;
                    break;
                case 'in_progress':
                    progress = 50;
                    break;
                case 'on_hold':
                    progress = 30;
                    break;
                case 'completed':
                    progress = 100;
                    break;
            }

            document.getElementById('taskId').value = taskId;
            document.getElementById('newStatus').value = newStatus;
            document.getElementById('taskProgress').value = progress;
            document.getElementById('taskNotes').value = '';
            document.getElementById('statusForm').submit();
        }

        function showCompleteModal(taskId) {
            currentTaskId = taskId;
            const modal = new bootstrap.Modal(document.getElementById('completeModal'));
            modal.show();
        }

        function submitComplete() {
            const notes = document.getElementById('completionNotes').value;
            if (notes.trim() === '') {
                alert('กรุณาใส่หมายเหตุการส่งงาน');
                return;
            }

            document.getElementById('taskId').value = currentTaskId;
            document.getElementById('newStatus').value = 'completed';
            document.getElementById('taskProgress').value = 100;
            document.getElementById('taskNotes').value = notes;
            document.getElementById('statusForm').submit();
        }

        function deleteTask(taskId) {
            if (confirm('ยืนยันการลบงาน? (เฉพาะงานส่วนตัวเท่านั้น)')) {
                document.getElementById('deleteTaskId').value = taskId;
                document.getElementById('deleteForm').submit();
            }
        }

        function showSubtasks(taskId) {
            const modal = new bootstrap.Modal(document.getElementById('subtaskModal'));

            // โหลดข้อมูล subtasks
            fetch(`get_subtasks.php?task_id=${taskId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('subtaskContent').innerHTML = html;
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('subtaskContent').innerHTML =
                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                    modal.show();
                });
        }

        function updateSubtaskStatus(subtaskId, newStatus) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const subtaskInput = document.createElement('input');
            subtaskInput.name = 'subtask_id';
            subtaskInput.value = subtaskId;

            const statusInput = document.createElement('input');
            statusInput.name = 'new_status';
            statusInput.value = newStatus;

            const actionInput = document.createElement('input');
            actionInput.name = 'update_subtask';
            actionInput.value = '1';

            // เพิ่ม notes ถ้ามี
            const notesTextarea = document.querySelector(`#notes_${subtaskId}`);
            if (notesTextarea) {
                const notesInput = document.createElement('input');
                notesInput.name = 'notes';
                notesInput.value = notesTextarea.value;
                form.appendChild(notesInput);
            }

            form.appendChild(subtaskInput);
            form.appendChild(statusInput);
            form.appendChild(actionInput);

            document.body.appendChild(form);
            form.submit();
        }

        // เปิดใช้งาน drag & drop
        document.addEventListener('DOMContentLoaded', function() {
            const containers = document.querySelectorAll('.tasks-container');

            containers.forEach(container => {
                new Sortable(container, {
                    group: 'tasks',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        const taskId = evt.item.dataset.taskId;
                        const newStatus = evt.to.dataset.status;

                        // อัปเดตสถานะอัตโนมัติ
                        updateStatus(taskId, newStatus);
                    }
                });
            });

            // ตั้งค่าวันที่ขั้นต่ำเป็นวันนี้
            document.getElementById('deadline').min = new Date().toISOString().split('T')[0];

            // เรียงงานตามความสำคัญในแต่ละคอลัมน์
            sortTasksByPriority();
        });

        // เรียงงานตามความสำคัญ
        function sortTasksByPriority() {
            const containers = document.querySelectorAll('.tasks-container');

            containers.forEach(container => {
                const tasks = Array.from(container.querySelectorAll('.task-card'));

                tasks.sort((a, b) => {
                    const priorityOrder = {
                        'priority-urgent': 1,
                        'priority-high': 2,
                        'priority-medium': 3,
                        'priority-low': 4
                    };

                    let aPriority = 5;
                    let bPriority = 5;

                    for (let className of a.classList) {
                        if (priorityOrder[className]) {
                            aPriority = priorityOrder[className];
                            break;
                        }
                    }

                    for (let className of b.classList) {
                        if (priorityOrder[className]) {
                            bPriority = priorityOrder[className];
                            break;
                        }
                    }

                    return aPriority - bPriority;
                });

                // เรียงใหม่ใน DOM
                tasks.forEach(task => container.appendChild(task));
            });
        }
    </script>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const deadlineInput = document.getElementById('deadline');
            const now = new Date();

            // แปลงเป็นรูปแบบ 'YYYY-MM-DDTHH:MM'
            const formattedNow = now.toISOString().slice(0, 16);

            // กำหนดค่าขั้นต่ำของ datetime-local
            deadlineInput.min = formattedNow;

            // ตัวเลือก: ตั้งค่า default เป็นพรุ่งนี้
            now.setDate(now.getDate() + 1);
            const defaultVal = now.toISOString().slice(0, 16);
            deadlineInput.value = defaultVal;
        });
    </script>
    
<script>
    $(document).ready(function() {
        $('#user_id').select2({
            placeholder: "เลือกผู้ใช้บริการ",
            allowClear: true
        });
    });
</script>


    <style>
        .btn-complete {
            background: #10b981;
        }

        .btn-complete:hover {
            background: #059669;
        }

        .btn-subtask {
            background: #8b5cf6;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            margin-top: 5px;
            width: 100%;
        }

        .btn-subtask:hover {
            background: #7c3aed;
            color: white;
        }

        .service-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: inline-block;
        }

        .service-development {
            background: #c6f6d5;
            color: #2f855a;
        }

        .service-service {
            background: #dbeafe;
            color: #2a3c77ff;
        }

        .subtask-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .subtask-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .subtask-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .subtask-item.pending {
            border-left-color: #f59e0b;
        }

        .subtask-item.in_progress {
            border-left-color: #3b82f6;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        }

        .subtask-item.completed {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }

        .subtask-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }

        .subtask-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .subtask-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .subtask-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .subtask-percentage {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 50px;
            text-align: center;
        }

        .subtask-status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-in_progress {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .subtask-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .subtask-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-start {
            background: #3b82f6;
            color: white;
        }

        .btn-start:hover {
            background: #2563eb;
        }

        .btn-finish {
            background: #10b981;
            color: white;
        }

        .btn-finish:hover {
            background: #059669;
        }

        .subtask-notes {
            margin-top: 10px;
        }

        .subtask-notes textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 60px;
        }

        .subtask-dates {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 10px;
        }

        .overall-progress {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .overall-progress h6 {
            margin-bottom: 10px;
            color: #4a5568;
        }

        .progress-bar-container {
            background: #e2e8f0;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .kanban-column {
                min-height: auto;
            }
        }
    </style>
</body>

</html>