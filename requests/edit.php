<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = $_GET['id'] ?? null;

if (!$request_id) {
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลคำขอ
$stmt = $conn->prepare("
    SELECT sr.*, s.name as service_name, s.category as service_category
    FROM service_requests sr
    LEFT JOIN services s ON sr.service_id = s.id
    WHERE sr.id = ? AND sr.user_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    $_SESSION['error'] = "ไม่พบคำขอที่ระบุ หรือคุณไม่มีสิทธิ์แก้ไข";
    header("Location: index.php");
    exit();
}

// ตรวจสอบว่าสามารถแก้ไขได้หรือไม่ (เฉพาะสถานะ pending หรือ div_mgr_review)
if (!in_array($request['status'], ['pending', 'div_mgr_review'])) {
    $_SESSION['error'] = "ไม่สามารถแก้ไขคำขอที่ผ่านการอนุมัติแล้ว";
    header("Location: index.php");
    exit();
}

// ดึงข้อมูล services
$services_stmt = $conn->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY category, name");
$services_stmt->execute();
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล departments
$dept_stmt = $conn->prepare("SELECT * FROM departments WHERE is_active = 1 ORDER BY warehouse_number, code_name");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล programs
$prog_stmt = $conn->prepare("SELECT * FROM programs ORDER BY name");
$prog_stmt->execute();
$programs = $prog_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลผู้จัดการฝ่าย
$divmgr_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'divmgr' AND is_active = 1 ORDER BY name");
$divmgr_stmt->execute();
$div_managers = $divmgr_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงไฟล์แนบ
$files_stmt = $conn->prepare("SELECT * FROM request_attachments WHERE service_request_id = ? ORDER BY uploaded_at");
$files_stmt->execute([$request_id]);
$attachments = $files_stmt->fetchAll(PDO::FETCH_ASSOC);

// ประมวลผลการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $service_id = $_POST['service_id'] ?? null;
    $work_category = $_POST['work_category'] ?? null;
    $assigned_div_mgr_id = $_POST['assigned_div_mgr_id'] ?? null;
    $expected_benefits = trim($_POST['expected_benefits'] ?? '');
    
    // ฟิลด์ทั่วไป
    $current_workflow = trim($_POST['current_workflow'] ?? '');
    $approach_ideas = trim($_POST['approach_ideas'] ?? '');
    $related_programs = trim($_POST['related_programs'] ?? '');
    $current_tools = trim($_POST['current_tools'] ?? '');
    $system_impact = trim($_POST['system_impact'] ?? '');
    $related_documents = trim($_POST['related_documents'] ?? '');
    
    // ฟิลด์สำหรับโปรแกรมใหม่
    $program_purpose = trim($_POST['program_purpose'] ?? '');
    $target_users = trim($_POST['target_users'] ?? '');
    $main_functions = trim($_POST['main_functions'] ?? '');
    $data_requirements = trim($_POST['data_requirements'] ?? '');
    
    // ฟิลด์สำหรับแก้ปัญหา
    $current_program_name = trim($_POST['current_program_name'] ?? '');
    $problem_description = trim($_POST['problem_description'] ?? '');
    $error_frequency = trim($_POST['error_frequency'] ?? '');
    $steps_to_reproduce = trim($_POST['steps_to_reproduce'] ?? '');
    
    // ฟิลด์สำหรับเปลี่ยนข้อมูล
    $program_name_change = trim($_POST['program_name_change'] ?? '');
    $data_to_change = trim($_POST['data_to_change'] ?? '');
    $new_data_value = trim($_POST['new_data_value'] ?? '');
    $change_reason = trim($_POST['change_reason'] ?? '');
    
    // ฟิลด์สำหรับเพิ่มฟังก์ชั่น
    $program_name_function = trim($_POST['program_name_function'] ?? '');
    $new_functions = trim($_POST['new_functions'] ?? '');
    $function_benefits = trim($_POST['function_benefits'] ?? '');
    $integration_requirements = trim($_POST['integration_requirements'] ?? '');
    
    // ฟิลด์สำหรับตกแต่ง
    $program_name_decorate = trim($_POST['program_name_decorate'] ?? '');
    $decoration_type = trim($_POST['decoration_type'] ?? '');
    $reference_examples = trim($_POST['reference_examples'] ?? '');
    
    // Validation
    if (empty($title)) {
        $error = "กรุณากรอกหัวข้อคำขอ";
    } elseif (empty($service_id)) {
        $error = "กรุณาเลือกประเภทบริการ";
    } elseif (empty($assigned_div_mgr_id)) {
        $error = "กรุณาเลือกผู้จัดการฝ่าย";
    } else {
        try {
            $conn->beginTransaction();
            
            // อัปเดตข้อมูลคำขอ
            $update_stmt = $conn->prepare("
                UPDATE service_requests SET 
                    title = ?, service_id = ?, work_category = ?, assigned_div_mgr_id = ?,
                    expected_benefits = ?, current_workflow = ?, approach_ideas = ?, 
                    related_programs = ?, current_tools = ?, system_impact = ?, 
                    related_documents = ?, program_purpose = ?, target_users = ?, 
                    main_functions = ?, data_requirements = ?, current_program_name = ?, 
                    problem_description = ?, error_frequency = ?, steps_to_reproduce = ?, 
                    program_name_change = ?, data_to_change = ?, new_data_value = ?, 
                    change_reason = ?, program_name_function = ?, new_functions = ?, 
                    function_benefits = ?, integration_requirements = ?, 
                    program_name_decorate = ?, decoration_type = ?, reference_examples = ?,
                    updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $update_stmt->execute([
                $title, $service_id, $work_category, $assigned_div_mgr_id,
                $expected_benefits, $current_workflow, $approach_ideas, 
                $related_programs, $current_tools, $system_impact, 
                $related_documents, $program_purpose, $target_users, 
                $main_functions, $data_requirements, $current_program_name, 
                $problem_description, $error_frequency, $steps_to_reproduce, 
                $program_name_change, $data_to_change, $new_data_value, 
                $change_reason, $program_name_function, $new_functions, 
                $function_benefits, $integration_requirements, 
                $program_name_decorate, $decoration_type, $reference_examples,
                $request_id, $user_id
            ]);
            
            // จัดการไฟล์แนบใหม่
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar'];
                $max_file_size = 10 * 1024 * 1024; // 10MB
                
                foreach ($_FILES['attachments']['name'] as $key => $filename) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_size = $_FILES['attachments']['size'][$key];
                        $file_tmp = $_FILES['attachments']['tmp_name'][$key];
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (!in_array($file_ext, $allowed_types)) {
                            throw new Exception("ไฟล์ $filename ไม่ใช่ประเภทที่อนุญาต");
                        }
                        
                        if ($file_size > $max_file_size) {
                            throw new Exception("ไฟล์ $filename มีขนาดใหญ่เกิน 10MB");
                        }
                        
                        $stored_filename = $request_id . '_' . time() . '_' . $key . '.' . $file_ext;
                        $file_path = $upload_dir . $stored_filename;
                        
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            $file_stmt = $conn->prepare("
                                INSERT INTO request_attachments 
                                (service_request_id, original_filename, stored_filename, file_size, file_type) 
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $file_stmt->execute([$request_id, $filename, $stored_filename, $file_size, $file_ext]);
                        }
                    }
                }
            }
            
            $conn->commit();
            $_SESSION['success'] = "แก้ไขคำขอเรียบร้อยแล้ว";
            header("Location: index.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ลบไฟล์แนบ
if (isset($_GET['delete_file'])) {
    $file_id = $_GET['delete_file'];
    
    try {
        // ดึงข้อมูลไฟล์
        $file_stmt = $conn->prepare("SELECT * FROM request_attachments WHERE id = ? AND service_request_id = ?");
        $file_stmt->execute([$file_id, $request_id]);
        $file = $file_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            // ลบไฟล์จากระบบ
            $file_path = __DIR__ . '/../uploads/' . $file['stored_filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // ลบข้อมูลจากฐานข้อมูล
            $delete_stmt = $conn->prepare("DELETE FROM request_attachments WHERE id = ?");
            $delete_stmt->execute([$file_id]);
            
            $_SESSION['success'] = "ลบไฟล์เรียบร้อยแล้ว";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบไฟล์: " . $e->getMessage();
    }
    
    header("Location: edit.php?id=" . $request_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BobbyCareDev - แก้ไขคำขอบริการ</title>
    <link rel="icon" type="image/png" href="/BobbyCareRemake/img/logo/bobby-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .form-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #667eea;
        }

        .section-title {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .required {
            color: #e53e3e;
        }

        .service-fields {
            display: none;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 15px;
            border-left: 4px solid #10b981;
        }

        .service-fields.show {
            display: block;
        }

        .file-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: between;
            align-items: center;
            border-left: 4px solid #0d6efd;
        }

        .file-info {
            flex-grow: 1;
        }

        .file-name {
            font-weight: 600;
            color: #2d3748;
        }

        .file-size {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .btn-delete-file {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .btn-delete-file:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .status-div_mgr_review {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            color: #1e40af;
        }

        .warning-box {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #f59e0b;
        }

        .info-box {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #3b82f6;
        }

        .file-upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .file-upload-area.dragover {
            border-color: #667eea;
            background: #e6f3ff;
            transform: scale(1.02);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Header -->

         <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">

        <div class="container">
            <!-- โลโก้ + ชื่อระบบ -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="../dashboard.php">
                <img src="../img/logo/bobby-full.png" alt="Logo" height="32" class="me-2">
                <span class="page-title"> สวัสดี, <?= htmlspecialchars($_SESSION['name']) ?>! </span>
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
                        <a class="nav-link" href="create.php"><i class="fas fa-tasks me-1"></i>สร้างคำขอบริการ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-chart-bar me-1"></i> รายการคำขอ</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="track_status.php"><i class="fas fa-chart-bar me-1"></i>ติดตามสถานะ</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="../profile.php"><i class="fas fa-chart-bar me-1"></i>โปรไฟล์</a>
                    </li>
                </ul>
                <!-- ขวา: ผู้ใช้งาน -->
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <!-- <li class="nav-item d-flex align-items-center text-dark me-3">
                        <i class="fas fa-user-circle me-2"></i>
                      
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
        <div class="header-card p-5 mb-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 60px; height: 60px;">
                            <i class="fas fa-edit text-white fs-3"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">แก้ไขคำขอบริการ</h1>
                          
                        </div>
                    </div>
                </div>
               
            </div>
        </div>

        <!-- แสดงข้อผิดพลาดหรือความสำเร็จ -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- ข้อมูลคำขอปัจจุบัน -->
        <div class="info-box">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-info-circle me-2"></i>ข้อมูลคำขอปัจจุบัน
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>หัวข้อ:</strong> <?= htmlspecialchars($request['title']) ?></p>
                    <p><strong>ประเภทบริการ:</strong> <?= htmlspecialchars($request['service_name'] ?? 'ไม่ระบุ') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>สถานะ:</strong> 
                        <span class="status-badge status-<?= $request['status'] ?>">
                            <?= $request['status'] === 'pending' ? 'รอดำเนินการ' : 'ผู้จัดการฝ่ายพิจารณา' ?>
                        </span>
                    </p>
                    <p><strong>วันที่สร้าง:</strong> <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <!-- คำเตือน -->
        <div class="warning-box">
            <h5 class="fw-bold mb-2">
                <i class="fas fa-exclamation-triangle me-2"></i>ข้อควรระวัง
            </h5>
            <ul class="mb-0">
                <li>สามารถแก้ไขได้เฉพาะคำขอที่ยังไม่ผ่านการอนุมัติ</li>
                <li>การแก้ไขจะส่งผลต่อการพิจารณาของผู้อนุมัติ</li>
                <li>ไฟล์แนบใหม่จะเพิ่มเข้าไป ไฟล์เก่าจะยังคงอยู่</li>
                <li>ตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก</li>
            </ul>
        </div>

        <form method="post" enctype="multipart/form-data" id="editForm">
            <!-- ข้อมูลพื้นฐาน -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-clipboard-list text-primary"></i>
                    ข้อมูลพื้นฐาน
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="title" class="form-label">หัวข้อคำขอ <span class="required">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?= htmlspecialchars($request['title']) ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="service_id" class="form-label">ประเภทบริการ <span class="required">*</span></label>
                        <select class="form-select" id="service_id" name="service_id" required>
                            <option value="">-- เลือกประเภทบริการ --</option>
                            <?php
                            $current_category = '';
                            foreach ($services as $service):
                                if ($current_category !== $service['category']):
                                    if ($current_category !== '') echo '</optgroup>';
                                    $category_name = $service['category'] === 'development' ? 'งานพัฒนา (Development)' : 'งานบริการ (Service)';
                                    echo '<optgroup label="' . $category_name . '">';
                                    $current_category = $service['category'];
                                endif;
                            ?>
                                <option value="<?= $service['id'] ?>" 
                                        data-category="<?= $service['category'] ?>"
                                        <?= $request['service_id'] == $service['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($service['name']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_category !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="work_category" class="form-label">หัวข้องานคลัง</label>
                        <select class="form-select" id="work_category" name="work_category">
                            <option value="">-- เลือกหัวข้องานคลัง --</option>
                            <?php
                            $current_warehouse = '';
                            foreach ($departments as $dept):
                                if ($current_warehouse !== $dept['warehouse_number']):
                                    if ($current_warehouse !== '') echo '</optgroup>';
                                    $warehouse_name = $dept['warehouse_number'] === '01' ? 'RDC' : 
                                                     ($dept['warehouse_number'] === '02' ? 'CDC' : 'BDC');
                                    echo '<optgroup label="' . $warehouse_name . '">';
                                    $current_warehouse = $dept['warehouse_number'];
                                endif;
                                $option_value = $dept['warehouse_number'] . '-' . $dept['code_name'];
                            ?>
                                <option value="<?= $option_value ?>" 
                                        <?= $request['work_category'] === $option_value ? 'selected' : '' ?>>
                                    <?= $dept['code_name'] ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_warehouse !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="assigned_div_mgr_id" class="form-label">ผู้จัดการฝ่าย <span class="required">*</span></label>
                        <select class="form-select" id="assigned_div_mgr_id" name="assigned_div_mgr_id" required>
                            <option value="">-- เลือกผู้จัดการฝ่าย --</option>
                            <?php foreach ($div_managers as $mgr): ?>
                                <option value="<?= $mgr['id'] ?>" 
                                        <?= $request['assigned_div_mgr_id'] == $mgr['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mgr['name'] . ' ' . $mgr['lastname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="expected_benefits" class="form-label">ประโยชน์ที่คาดว่าจะได้รับ</label>
                        <textarea class="form-control" id="expected_benefits" name="expected_benefits" rows="3" 
                                  placeholder="อธิบายประโยชน์ที่คาดว่าจะได้รับจากการใช้บริการนี้"><?= htmlspecialchars($request['expected_benefits']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ฟิลด์สำหรับโปรแกรมใหม่ -->
            <div class="service-fields" id="new-program-fields">
                <div class="section-title">
                    <i class="fas fa-plus-circle text-success"></i>
                    ข้อมูลสำหรับโปรแกรมใหม่
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="program_purpose" class="form-label">วัตถุประสงค์ของโปรแกรม</label>
                        <textarea class="form-control" id="program_purpose" name="program_purpose" rows="3" 
                                  placeholder="อธิบายวัตถุประสงค์และเป้าหมายของโปรแกรม"><?= htmlspecialchars($request['program_purpose']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="target_users" class="form-label">กลุ่มผู้ใช้งาน</label>
                        <textarea class="form-control" id="target_users" name="target_users" rows="3" 
                                  placeholder="ระบุกลุ่มผู้ใช้งานหลัก"><?= htmlspecialchars($request['target_users']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="main_functions" class="form-label">ฟังก์ชันหลักที่ต้องการ</label>
                        <textarea class="form-control" id="main_functions" name="main_functions" rows="3" 
                                  placeholder="อธิบายฟังก์ชันหลักที่ต้องการ"><?= htmlspecialchars($request['main_functions']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="data_requirements" class="form-label">ข้อมูลที่ต้องใช้</label>
                        <textarea class="form-control" id="data_requirements" name="data_requirements" rows="3" 
                                  placeholder="ระบุข้อมูลที่ต้องใช้ในโปรแกรม"><?= htmlspecialchars($request['data_requirements']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ฟิลด์สำหรับแก้ปัญหา -->
            <div class="service-fields" id="fix-problem-fields">
                <div class="section-title">
                    <i class="fas fa-bug text-danger"></i>
                    ข้อมูลสำหรับแก้ปัญหา
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="current_program_name" class="form-label">ชื่อโปรแกรมที่มีปัญหา</label>
                        <input type="text" class="form-control" id="current_program_name" name="current_program_name" 
                               value="<?= htmlspecialchars($request['current_program_name']) ?>" 
                               placeholder="ระบุชื่อโปรแกรมที่มีปัญหา">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="error_frequency" class="form-label">ความถี่ของปัญหา</label>
                        <select class="form-select" id="error_frequency" name="error_frequency">
                            <option value="">-- เลือกความถี่ --</option>
                            <option value="ทุกครั้ง" <?= $request['error_frequency'] === 'ทุกครั้ง' ? 'selected' : '' ?>>ทุกครั้ง</option>
                            <option value="บ่อยครั้ง" <?= $request['error_frequency'] === 'บ่อยครั้ง' ? 'selected' : '' ?>>บ่อยครั้ง</option>
                            <option value="บางครั้ง" <?= $request['error_frequency'] === 'บางครั้ง' ? 'selected' : '' ?>>บางครั้ง</option>
                            <option value="นานๆครั้ง" <?= $request['error_frequency'] === 'นานๆครั้ง' ? 'selected' : '' ?>>นานๆครั้ง</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="problem_description" class="form-label">รายละเอียดปัญหา</label>
                        <textarea class="form-control" id="problem_description" name="problem_description" rows="4" 
                                  placeholder="อธิบายปัญหาที่เกิดขึ้นอย่างละเอียด"><?= htmlspecialchars($request['problem_description']) ?></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="steps_to_reproduce" class="form-label">ขั้นตอนการทำให้เกิดปัญหา</label>
                        <textarea class="form-control" id="steps_to_reproduce" name="steps_to_reproduce" rows="4" 
                                  placeholder="อธิบายขั้นตอนการทำให้เกิดปัญหา"><?= htmlspecialchars($request['steps_to_reproduce']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ฟิลด์สำหรับเปลี่ยนข้อมูล -->
            <div class="service-fields" id="change-data-fields">
                <div class="section-title">
                    <i class="fas fa-exchange-alt text-info"></i>
                    ข้อมูลสำหรับเปลี่ยนข้อมูล
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="program_name_change" class="form-label">ชื่อโปรแกรมที่ต้องการเปลี่ยนข้อมูล</label>
                        <input type="text" class="form-control" id="program_name_change" name="program_name_change" 
                               value="<?= htmlspecialchars($request['program_name_change']) ?>" 
                               placeholder="ระบุชื่อโปรแกรม">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="change_reason" class="form-label">เหตุผลในการเปลี่ยนแปลง</label>
                        <textarea class="form-control" id="change_reason" name="change_reason" rows="3" 
                                  placeholder="อธิบายเหตุผลที่ต้องเปลี่ยนข้อมูล"><?= htmlspecialchars($request['change_reason']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="data_to_change" class="form-label">ข้อมูลที่ต้องการเปลี่ยน</label>
                        <textarea class="form-control" id="data_to_change" name="data_to_change" rows="4" 
                                  placeholder="ระบุข้อมูลปัจจุบันที่ต้องการเปลี่ยน"><?= htmlspecialchars($request['data_to_change']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="new_data_value" class="form-label">ข้อมูลใหม่ที่ต้องการ</label>
                        <textarea class="form-control" id="new_data_value" name="new_data_value" rows="4" 
                                  placeholder="ระบุข้อมูลใหม่ที่ต้องการ"><?= htmlspecialchars($request['new_data_value']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ฟิลด์สำหรับเพิ่มฟังก์ชั่น -->
            <div class="service-fields" id="add-function-fields">
                <div class="section-title">
                    <i class="fas fa-plus text-warning"></i>
                    ข้อมูลสำหรับเพิ่มฟังก์ชั่น
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="program_name_function" class="form-label">ชื่อโปรแกรมที่ต้องการเพิ่มฟังก์ชั่น</label>
                        <input type="text" class="form-control" id="program_name_function" name="program_name_function" 
                               value="<?= htmlspecialchars($request['program_name_function']) ?>" 
                               placeholder="ระบุชื่อโปรแกรม">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="function_benefits" class="form-label">ประโยชน์ของฟังก์ชั่นใหม่</label>
                        <textarea class="form-control" id="function_benefits" name="function_benefits" rows="3" 
                                  placeholder="อธิบายประโยชน์ที่จะได้รับ"><?= htmlspecialchars($request['function_benefits']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="new_functions" class="form-label">ฟังก์ชั่นใหม่ที่ต้องการ</label>
                        <textarea class="form-control" id="new_functions" name="new_functions" rows="4" 
                                  placeholder="อธิบายฟังก์ชั่นใหม่ที่ต้องการเพิ่ม"><?= htmlspecialchars($request['new_functions']) ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="integration_requirements" class="form-label">ความต้องการเชื่อมต่อ</label>
                        <textarea class="form-control" id="integration_requirements" name="integration_requirements" rows="4" 
                                  placeholder="ระบุความต้องการเชื่อมต่อกับระบบอื่น"><?= htmlspecialchars($request['integration_requirements']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ฟิลด์สำหรับตกแต่ง -->
            <div class="service-fields" id="decoration-fields">
                <div class="section-title">
                    <i class="fas fa-palette text-secondary"></i>
                    ข้อมูลสำหรับตกแต่ง
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="program_name_decorate" class="form-label">ชื่อโปรแกรมที่ต้องการตกแต่ง</label>
                        <input type="text" class="form-control" id="program_name_decorate" name="program_name_decorate" 
                               value="<?= htmlspecialchars($request['program_name_decorate']) ?>" 
                               placeholder="ระบุชื่อโปรแกรม">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="decoration_type" class="form-label">ประเภทการตกแต่ง</label>
                        <textarea class="form-control" id="decoration_type" name="decoration_type" rows="3" 
                                  placeholder="อธิบายประเภทการตกแต่งที่ต้องการ"><?= htmlspecialchars($request['decoration_type']) ?></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="reference_examples" class="form-label">ตัวอย่างอ้างอิง</label>
                        <textarea class="form-control" id="reference_examples" name="reference_examples" rows="4" 
                                  placeholder="ระบุตัวอย่างหรือแนวทางที่ต้องการให้เป็นแบบอย่าง"><?= htmlspecialchars($request['reference_examples']) ?></textarea>
                    </div>
                </div>
            </div>


            <!-- ไฟล์แนบปัจจุบัน -->
            <?php if (!empty($attachments)): ?>
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-paperclip text-info"></i>
                    ไฟล์แนบปัจจุบัน (<?= count($attachments) ?> ไฟล์)
                </div>
                <?php foreach ($attachments as $file): ?>
                    <div class="file-item">
                        <div class="file-info">
                            <div class="file-name">
                                <i class="fas fa-file me-2"></i>
                                <?= htmlspecialchars($file['original_filename']) ?>
                            </div>
                            <div class="file-size">
                                ขนาด: <?= number_format($file['file_size'] / 1024, 2) ?> KB
                                | อัปโหลดเมื่อ: <?= date('d/m/Y H:i', strtotime($file['uploaded_at'])) ?>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="../includes/file_viewer.php?id=<?= $file['id'] ?>" target="_blank" 
                               class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-eye"></i> ดู
                            </a>
                            <a href="edit.php?id=<?= $request_id ?>&delete_file=<?= $file['id'] ?>" 
                               class="btn btn-delete-file"
                               onclick="return confirm('ยืนยันการลบไฟล์นี้?')">
                                <i class="fas fa-trash"></i> ลบ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- เพิ่มไฟล์แนบใหม่ -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-cloud-upload-alt text-success"></i>
                    เพิ่มไฟล์แนบใหม่
                </div>
                <div class="file-upload-area" id="fileUploadArea">
                    <i class="fas fa-cloud-upload-alt fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h5>
                    <p class="text-muted mb-3">รองรับไฟล์: PDF, DOC, DOCX, JPG, PNG, GIF, TXT, ZIP, RAR</p>
                    <p class="text-muted">ขนาดไฟล์สูงสุด: 10MB ต่อไฟล์</p>
                    <input type="file" class="form-control" id="attachments" name="attachments[]" 
                           multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar" style="display: none;">
                    <button type="button" class="btn btn-outline-primary mt-3" onclick="document.getElementById('attachments').click()">
                        <i class="fas fa-plus me-2"></i>เลือกไฟล์
                    </button>
                </div>
                <div id="selectedFiles" class="mt-3"></div>
            </div>

            <!-- ปุ่มบันทึก -->
            <div class="form-section text-center">
                <button type="submit" class="btn btn-gradient btn-lg me-3">
                    <i class="fas fa-save me-2"></i>บันทึกการแก้ไข
                </button>
                <a href="index.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>ยกเลิก
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // แสดง/ซ่อนฟิลด์ตามประเภทบริการ
        function toggleServiceFields() {
            const serviceSelect = document.getElementById('service_id');
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const category = selectedOption.dataset.category;
            const serviceName = selectedOption.text;
            
            // ซ่อนฟิลด์ทั้งหมดก่อน
            document.querySelectorAll('.service-fields').forEach(field => {
                field.classList.remove('show');
            });
            
            // แสดงฟิลด์ตามประเภท
            if (category === 'development') {
                if (serviceName.includes('โปรแกรมใหม่')) {
                    document.getElementById('new-program-fields').classList.add('show');
                } else if (serviceName.includes('แก้ปัญหา')) {
                    document.getElementById('fix-problem-fields').classList.add('show');
                } else if (serviceName.includes('เปลี่ยนข้อมูล')) {
                    document.getElementById('change-data-fields').classList.add('show');
                } else if (serviceName.includes('เพิ่มฟังก์ชั่น')) {
                    document.getElementById('add-function-fields').classList.add('show');
                } else if (serviceName.includes('ตกแต่ง')) {
                    document.getElementById('decoration-fields').classList.add('show');
                }
            }
        }

        // เรียกใช้เมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            toggleServiceFields();
            
            // เมื่อเปลี่ยนประเภทบริการ
            document.getElementById('service_id').addEventListener('change', toggleServiceFields);
        });

        // จัดการการอัปโหลดไฟล์
        const fileInput = document.getElementById('attachments');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const selectedFilesDiv = document.getElementById('selectedFiles');

        // Drag and drop
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            fileInput.files = files;
            displaySelectedFiles();
        });

        // คลิกเพื่อเลือกไฟล์
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // เมื่อเลือกไฟล์
        fileInput.addEventListener('change', displaySelectedFiles);

        function displaySelectedFiles() {
            const files = fileInput.files;
            selectedFilesDiv.innerHTML = '';
            
            if (files.length > 0) {
                selectedFilesDiv.innerHTML = '<h6 class="fw-bold mb-3">ไฟล์ที่เลือก:</h6>';
                
                Array.from(files).forEach((file, index) => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'file-item';
                    fileDiv.innerHTML = `
                        <div class="file-info">
                            <div class="file-name">
                                <i class="fas fa-file me-2"></i>
                                ${file.name}
                            </div>
                            <div class="file-size">
                                ขนาด: ${(file.size / 1024).toFixed(2)} KB
                            </div>
                        </div>
                        <button type="button" class="btn btn-delete-file" onclick="removeFile(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    selectedFilesDiv.appendChild(fileDiv);
                });
            }
        }

        function removeFile(index) {
            const dt = new DataTransfer();
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            fileInput.files = dt.files;
            displaySelectedFiles();
        }

        // Validation ก่อนส่งฟอร์ม
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const serviceId = document.getElementById('service_id').value;
            const divMgrId = document.getElementById('assigned_div_mgr_id').value;
            
            if (!title) {
                e.preventDefault();
                alert('กรุณากรอกหัวข้อคำขอ');
                return;
            }
            
            if (!serviceId) {
                e.preventDefault();
                alert('กรุณาเลือกประเภทบริการ');
                return;
            }
            
            if (!divMgrId) {
                e.preventDefault();
                alert('กรุณาเลือกผู้จัดการฝ่าย');
                return;
            }
            
            // ยืนยันการแก้ไข
            if (!confirm('ยืนยันการแก้ไขคำขอนี้?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>