<?php 
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่า login แล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// ดึงข้อมูลผู้ใช้
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// ดึงรายชื่อผู้จัดการฝ่าย
$div_mgr_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'divmgr' AND is_active = 1 ORDER BY name");
$div_mgr_stmt->execute();
$div_managers = $div_mgr_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการ services
$services_stmt = $conn->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY category DESC, name");
$services_stmt->execute();
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการโปรแกรม
$programs_stmt = $conn->prepare("SELECT * FROM programs ORDER BY name");
$programs_stmt->execute();
$programs = $programs_stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ามีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $work_category = $_POST['work_category'] ?? null;
    $expected_benefits = trim($_POST['expected_benefits'] ?? '');
    $assigned_div_mgr_id = $_POST['assigned_div_mgr_id'] ?? null;
    $service_id = $_POST['service_id'] ?? null;
    
    // ฟิลด์เพิ่มเติมสำหรับงาน Development
    $current_workflow = trim($_POST['current_workflow'] ?? '');
    $approach_ideas = trim($_POST['approach_ideas'] ?? '');
    $related_programs = trim($_POST['related_programs'] ?? '');
    $current_tools = trim($_POST['current_tools'] ?? '');
    $system_impact = trim($_POST['system_impact'] ?? '');
    $related_documents = trim($_POST['related_documents'] ?? '');

    if ($title !== '' && $description !== '' && $work_category && $expected_benefits && $assigned_div_mgr_id && $service_id) {
        try {
            $conn->beginTransaction();

            // สร้าง service request
            $stmt = $conn->prepare("
                INSERT INTO service_requests (
                    user_id, title, description, priority, work_category, expected_benefits, 
                    assigned_div_mgr_id, service_id, status, current_workflow, approach_ideas, 
                    related_programs, current_tools, system_impact, related_documents
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'div_mgr_review', ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $title, $description, $priority, $work_category, $expected_benefits, 
                $assigned_div_mgr_id, $service_id, $current_workflow, $approach_ideas, 
                $related_programs, $current_tools, $system_impact, $related_documents
            ]);
            $request_id = $conn->lastInsertId();

            // จัดการไฟล์ที่อัปโหลด
            if (!empty($_FILES['attachments']['name'][0])) {
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                foreach ($_FILES['attachments']['name'] as $key => $filename) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'txt', 'zip', 'rar'];
                        
                        if (in_array($file_extension, $allowed_extensions)) {
                            $file_size = $_FILES['attachments']['size'][$key];
                            if ($file_size <= 10 * 1024 * 1024) { // 10MB limit
                                $new_filename = $request_id . '_' . time() . '_' . $key . '.' . $file_extension;
                                $upload_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $upload_path)) {
                                    // บันทึกข้อมูลไฟล์ในฐานข้อมูล
                                    $file_stmt = $conn->prepare("INSERT INTO request_attachments (service_request_id, original_filename, stored_filename, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
                                    $file_stmt->execute([$request_id, $filename, $new_filename, $file_size, $file_extension]);
                                }
                            }
                        }
                    }
                }
            }

            $conn->commit();
            $success = "สร้างคำขอบริการเรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างคำขอบริการใหม่ - BobbyCareDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="css/create.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-card p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center">
                        <div class="section-icon me-3">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">สร้างคำขอบริการใหม่</h1>
                            <p class="text-muted mb-0">กรอกรายละเอียดและแนบไฟล์เพื่อส่งคำขอ</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="index.php" class="btn btn-outline-gradient">
                        <i class="fas fa-arrow-left me-2"></i>กลับรายการ
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-3 fs-4"></i>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" enctype="multipart/form-data" id="requestForm">
            <div class="form-grid">
                <!-- ข้อมูลผู้ขอ -->
                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        ข้อมูลผู้ขอ
                    </div>
                    <div class="user-info-grid">
                        <div class="user-info-item">
                            <div class="user-info-label">รหัสพนักงาน</div>
                            <div class="user-info-value"><?= htmlspecialchars($user_data['employee_id'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                        <div class="user-info-item">
                            <div class="user-info-label">ชื่อ-นามสกุล</div>
                            <div class="user-info-value"><?= htmlspecialchars($user_data['name'] . ' ' . $user_data['lastname']) ?></div>
                        </div>
                        <div class="user-info-item">
                            <div class="user-info-label">ตำแหน่ง</div>
                            <div class="user-info-value"><?= htmlspecialchars($user_data['position'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                        <div class="user-info-item">
                            <div class="user-info-label">หน่วยงาน</div>
                            <div class="user-info-value"><?= htmlspecialchars($user_data['department'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                        <div class="user-info-item">
                            <div class="user-info-label">เบอร์โทรศัพท์</div>
                            <div class="user-info-value"><?= htmlspecialchars($user_data['phone'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                        <div class="user-info-item">
                            <div class="user-info-label">อีเมล</div>
                            <div class="user-info-value"><?= htmlspecialchars($user_data['email'] ?? 'ไม่ระบุ') ?></div>
                        </div>
                    </div>
                </div>

  <!-- ประเภทบริการ -->
                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        ประเภทบริการ
                    </div>
                    <div class="service-grid">
                        <?php foreach ($services as $service): ?>
                            <?php if ($service['category'] !== 'development') continue; ?>
                            <div class="radio-option">
                                <input type="radio" id="service_<?= $service['id'] ?>" name="service_id" value="<?= $service['id'] ?>" required>
                                <label for="service_<?= $service['id'] ?>" class="radio-label service-<?= $service['category'] ?>" onclick="toggleDevelopmentFields(<?= $service['id'] ?>, '<?= $service['category'] ?>')">
                                    <i class="fas fa-code me-2"></i>
                                    <?= htmlspecialchars($service['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                 <!-- ฟิลด์เพิ่มเติมสำหรับงาน Development -->
                <div id="developmentFields" class="development-fields" style="display: none;">
                    <div class="form-section">
                        <div class="section-title">
                            <div class="section-icon">
                                <i class="fas fa-code"></i>
                            </div>
                            <span id="developmentTitle">ข้อมูลเพิ่มเติมสำหรับงาน Development</span>
                        </div>
                        
                        <!-- ฟิลด์สำหรับโปรแกรมใหม่ -->
                        <div id="newProgramFields" class="development-grid" style="display: none;">
                            <div>
                                <label for="program_purpose" class="form-label">
                                    <i class="fas fa-bullseye me-2"></i>วัตถุประสงค์ของโปรแกรม <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="program_purpose" name="program_purpose" rows="3"
                                          placeholder="อธิบายวัตถุประสงค์และเป้าหมายของโปรแกรมที่ต้องการพัฒนา"></textarea>
                            </div>
                            <div>
                                <label for="target_users" class="form-label">
                                    <i class="fas fa-users me-2"></i>กลุ่มผู้ใช้งาน <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="target_users" name="target_users" rows="2"
                                          placeholder="ระบุกลุ่มผู้ใช้งานหลัก เช่น พนักงานฝ่ายขาย, ผู้จัดการ, ลูกค้า"></textarea>
                            </div>
                            <div>
                                <label for="main_functions" class="form-label">
                                    <i class="fas fa-list me-2"></i>ฟังก์ชันหลักที่ต้องการ <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="main_functions" name="main_functions" rows="4"
                                          placeholder="ระบุฟังก์ชันหลักที่ต้องการ เช่น การบันทึกข้อมูล, การออกรายงาน, การคำนวณ"></textarea>
                            </div>
                            <div>
                                <label for="data_requirements" class="form-label">
                                    <i class="fas fa-database me-2"></i>ข้อมูลที่ต้องใช้ <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="data_requirements" name="data_requirements" rows="3"
                                          placeholder="ระบุข้อมูลที่ต้องใช้ในระบบ เช่น ข้อมูลลูกค้า, ข้อมูลสินค้า, ข้อมูลการขาย"></textarea>
                            </div>
                            <div>
                                <label for="current_workflow" class="form-label">
                                    <i class="fas fa-list-ol me-2"></i>ขั้นตอนการทำงานเดิม
                                </label>
                                <textarea class="form-control" id="current_workflow" name="current_workflow" rows="3"
                                          placeholder="อธิบายขั้นตอนการทำงานปัจจุบัน เช่น วิธีการทำงาน กระบวนการที่ใช้อยู่"></textarea>
                            </div>
                             <div>
                                <label for="related_programs" class="form-label">
                                    <i class="fas fa-desktop me-2"></i>โปรแกรมที่คาดว่าจะเกี่ยวข้อง
                                </label>
                                <textarea class="form-control" id="related_programs" name="related_programs" rows="2"
                                          placeholder="โปรแกรมหรือระบบที่คาดว่าจะต้องใช้ในการพัฒนา"></textarea>
                            </div>
                        </div>

                        <!-- ฟิลด์สำหรับโปรแกรมเดิม (แก้ปัญหา) -->
                        <div id="fixProblemFields" class="development-grid" style="display: none;">
                            <div>
                                <label for="current_program_name" class="form-label">
                                    <i class="fas fa-desktop me-2"></i>ชื่อโปรแกรมที่มีปัญหา <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="current_program_name" name="current_program_name">
                                    <option value="">-- เลือกโปรแกรม --</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?= htmlspecialchars($program['name']) ?>">
                                            <?= htmlspecialchars($program['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="problem_description" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-2"></i>รายละเอียดปัญหา <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="problem_description" name="problem_description" rows="4"
                                          placeholder="อธิบายปัญหาที่เกิดขึ้นอย่างละเอียด เช่น error message, พฤติกรรมที่ผิดปกติ"></textarea>
                            </div>
                            <div>
                                <label for="error_frequency" class="form-label">
                                    <i class="fas fa-clock me-2"></i>ความถี่ของปัญหา <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="error_frequency" name="error_frequency">
                                    <option value="">-- เลือกความถี่ --</option>
                                    <option value="always">เกิดขึ้นทุกครั้ง</option>
                                    <option value="often">เกิดขึ้นบ่อย</option>
                                    <option value="sometimes">เกิดขึ้นบางครั้ง</option>
                                    <option value="rarely">เกิดขึ้นนานๆ ครั้ง</option>
                                </select>
                            </div>
                            <div>
                                <label for="steps_to_reproduce" class="form-label">
                                    <i class="fas fa-redo me-2"></i>ขั้นตอนการทำให้เกิดปัญหา
                                </label>
                                <textarea class="form-control" id="steps_to_reproduce" name="steps_to_reproduce" rows="3"
                                          placeholder="ระบุขั้นตอนการใช้งานที่ทำให้เกิดปัญหา (ถ้ามี)"></textarea>
                            </div>
                        </div>

                        <!-- ฟิลด์สำหรับโปรแกรมเดิม (เปลี่ยนข้อมูล) -->
                        <div id="changeDataFields" class="development-grid" style="display: none;">
                            <div>
                                <label for="program_name_change" class="form-label">
                                    <i class="fas fa-desktop me-2"></i>ชื่อโปรแกรมที่ต้องการเปลี่ยนข้อมูล <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="program_name_change" name="program_name_change">
                                    <option value="">-- เลือกโปรแกรม --</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?= htmlspecialchars($program['name']) ?>">
                                            <?= htmlspecialchars($program['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="data_to_change" class="form-label">
                                    <i class="fas fa-edit me-2"></i>ข้อมูลที่ต้องการเปลี่ยน <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="data_to_change" name="data_to_change" rows="3"
                                          placeholder="ระบุข้อมูลที่ต้องการเปลี่ยนแปลง เช่น ข้อความ, ตัวเลข, รายการ"></textarea>
                            </div>
                            <div>
                                <label for="new_data_value" class="form-label">
                                    <i class="fas fa-arrow-right me-2"></i>ข้อมูลใหม่ที่ต้องการ <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="new_data_value" name="new_data_value" rows="3"
                                          placeholder="ระบุข้อมูลใหม่ที่ต้องการให้แสดงแทน"></textarea>
                            </div>
                            <div>
                                <label for="change_reason" class="form-label">
                                    <i class="fas fa-question-circle me-2"></i>เหตุผลในการเปลี่ยนแปลง <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="change_reason" name="change_reason" rows="2"
                                          placeholder="อธิบายเหตุผลที่ต้องการเปลี่ยนแปลงข้อมูล"></textarea>
                            </div>
                        </div>

                        <!-- ฟิลด์สำหรับโปรแกรมเดิม (เพิ่มฟังก์ชั่น) -->
                        <div id="addFunctionFields" class="development-grid" style="display: none;">
                            <div>
                                <label for="program_name_function" class="form-label">
                                    <i class="fas fa-desktop me-2"></i>ชื่อโปรแกรมที่ต้องการเพิ่มฟังก์ชั่น <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="program_name_function" name="program_name_function">
                                    <option value="">-- เลือกโปรแกรม --</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?= htmlspecialchars($program['name']) ?>">
                                            <?= htmlspecialchars($program['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="new_functions" class="form-label">
                                    <i class="fas fa-plus-circle me-2"></i>ฟังก์ชั่นใหม่ที่ต้องการ <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="new_functions" name="new_functions" rows="4"
                                          placeholder="อธิบายฟังก์ชั่นใหม่ที่ต้องการเพิ่ม เช่น การออกรายงาน, การคำนวณ, การส่งอีเมล"></textarea>
                            </div>
                            <div>
                                <label for="function_benefits" class="form-label">
                                    <i class="fas fa-chart-line me-2"></i>ประโยชน์ของฟังก์ชั่นใหม่ <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="function_benefits" name="function_benefits" rows="3"
                                          placeholder="อธิบายประโยชน์ที่จะได้รับจากฟังก์ชั่นใหม่"></textarea>
                            </div>
                            <div>
                                <label for="integration_requirements" class="form-label">
                                    <i class="fas fa-link me-2"></i>ความต้องการเชื่อมต่อ
                                </label>
                                <textarea class="form-control" id="integration_requirements" name="integration_requirements" rows="2"
                                          placeholder="ต้องการเชื่อมต่อกับระบบอื่นหรือไม่ (ถ้ามี)"></textarea>
                            </div>
                        </div>

                        <!-- ฟิลด์สำหรับโปรแกรมเดิม (ตกแต่ง) -->
                        <div id="decorateFields" class="development-grid" style="display: none;">
                            <div>
                                <label for="program_name_decorate" class="form-label">
                                    <i class="fas fa-desktop me-2"></i>ชื่อโปรแกรมที่ต้องการตกแต่ง <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="program_name_decorate" name="program_name_decorate">
                                    <option value="">-- เลือกโปรแกรม --</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?= htmlspecialchars($program['name']) ?>">
                                            <?= htmlspecialchars($program['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="decoration_type" class="form-label">
                                    <i class="fas fa-palette me-2"></i>ประเภทการตกแต่ง <span class="text-danger">*</span>
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="ui_design" name="decoration_type[]" value="ui_design">
                                            <label class="form-check-label" for="ui_design">
                                                ปรับปรุงหน้าตา UI
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="color_scheme" name="decoration_type[]" value="color_scheme">
                                            <label class="form-check-label" for="color_scheme">
                                                เปลี่ยนสีธีม
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="layout_improve" name="decoration_type[]" value="layout_improve">
                                            <label class="form-check-label" for="layout_improve">
                                                ปรับปรุงการจัดวาง
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="icon" name="decoration_type[]" value="icon">
                                            <label class="form-check-label" for="icon">
                                                เปลี่ยนICON
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="reference_examples" class="form-label">
                                    <i class="fas fa-images me-2"></i>ตัวอย่างอ้างอิงหรือโปรแกรมที่ใกล้เคียง
                                </label>
                                <textarea class="form-control" id="reference_examples" name="reference_examples" rows="2"
                                          placeholder="มีเว็บไซต์หรือโปรแกรมที่ชอบให้อ้างอิงหรือไม่ (ถ้ามี)"></textarea>
                            </div>
    
                        </div>




                        <!-- ฟิลด์ทั่วไปสำหรับทุกประเภท Development -->
                        <!-- <div class="development-grid mt-4">
                            <div>
                                <label for="current_workflow" class="form-label">
                                    <i class="fas fa-list-ol me-2"></i>ขั้นตอนการทำงานเดิม
                                </label>
                                <textarea class="form-control" id="current_workflow" name="current_workflow" rows="3"
                                          placeholder="อธิบายขั้นตอนการทำงานปัจจุบัน เช่น วิธีการทำงาน กระบวนการที่ใช้อยู่"></textarea>
                            </div>
                            <div>
                                <label for="related_programs" class="form-label">
                                    <i class="fas fa-desktop me-2"></i>โปรแกรมที่คาดว่าจะเกี่ยวข้อง
                                </label>
                                <textarea class="form-control" id="related_programs" name="related_programs" rows="2"
                                          placeholder="โปรแกรมหรือระบบที่คาดว่าจะต้องใช้ในการพัฒนา"></textarea>
                            </div>
                           
                        </div> -->
                    </div>
                </div>


                <!-- ข้อมูลคำขอ -->
                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        ข้อมูลคำขอ
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-2"></i>หัวข้อคำขอ <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   placeholder="ระบุหัวข้อคำขอบริการ">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-2"></i>รายละเอียด <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                      placeholder="อธิบายรายละเอียดคำขอบริการ เช่น ปัญหาที่พบ ความต้องการ หรือข้อกำหนดพิเศษ"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="expected_benefits" class="form-label">
                                <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="expected_benefits" name="expected_benefits" rows="3" required
                                      placeholder="ระบุประโยชน์หรือผลลัพธ์ที่คาดว่าจะได้รับจากการดำเนินการตามคำขอนี้"></textarea>
                        </div>
                    </div>
                </div>

            

                <!-- การตั้งค่า -->
                <div class="form-section">
                 
                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <label class="form-label">
                                <i class="fas fa-building me-2"></i>หัวข้องานคลัง <span class="text-danger">*</span>
                            </label>
                            <div class="work-category-grid">
                                <div class="radio-option">
                                    <input type="radio" name="work_category" value="RDC" id="rdc" required>
                                    <label for="rdc" class="radio-label work-category-rdc">
                                        <i class="fas fa-database me-2"></i>RDC
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="work_category" value="CDC" id="cdc" required>
                                    <label for="cdc" class="radio-label work-category-cdc">
                                        <i class="fas fa-warehouse me-2"></i>CDC
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="work_category" value="BDC" id="bdc" required>
                                    <label for="bdc" class="radio-label work-category-bdc">
                                        <i class="fas fa-truck me-2"></i>BDC
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-4">
                            <label for="assigned_div_mgr_id" class="form-label">
                                <i class="fas fa-user-tie me-2"></i>ผู้กลั่นกรอง <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="assigned_div_mgr_id" name="assigned_div_mgr_id" required>
                                <option value="">-- เลือกผู้จัดการฝ่าย --</option>
                                <?php foreach ($div_managers as $mgr): ?>
                                    <option value="<?= $mgr['id'] ?>">
                                        <?= htmlspecialchars($mgr['name'] . ' ' . $mgr['lastname']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- <div class="col-lg-4 mb-4">
                            <label class="form-label">
                                <i class="fas fa-exclamation-circle me-2"></i>ระดับความสำคัญ
                            </label>
                            <div class="priority-grid">
                                <div class="radio-option">
                                    <input type="radio" id="priority_low" name="priority" value="low">
                                    <label for="priority_low" class="radio-label priority-low">
                                        <i class="fas fa-circle mb-1"></i><br>ต่ำ
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="priority_medium" name="priority" value="medium" checked>
                                    <label for="priority_medium" class="radio-label priority-medium">
                                        <i class="fas fa-circle mb-1"></i><br>ปานกลาง
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="priority_high" name="priority" value="high">
                                    <label for="priority_high" class="radio-label priority-high">
                                        <i class="fas fa-circle mb-1"></i><br>สูง
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="priority_urgent" name="priority" value="urgent">
                                    <label for="priority_urgent" class="radio-label priority-urgent">
                                        <i class="fas fa-circle mb-1"></i><br>เร่งด่วน
                                    </label>
                                </div>
                            </div>
                        </div> -->

                         <div class="form-section">
                         <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-paperclip"></i>
                        </div>
                        แนบไฟล์หรือไสล์นำเสนอเเละเอกสารการทำงานที่เกียวข้อง (sd)
                           </div>
                             <div class="file-upload-area" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h5>
                        <p class="text-muted mb-0">รองรับไฟล์: PDF, รูปภาพ, เอกสาร, ไฟล์บีบอัด (สูงสุด 10MB ต่อไฟล์)</p>
                        <input type="file" id="fileInput" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.txt,.zip,.rar" style="display: none;">
                               </div>
                              <div class="file-list" id="fileList"></div>
                          </div>

                    </div>

                </div>

               

                <!-- ปุ่มส่ง -->
                <div class="form-section text-center">
                    <button type="submit" class="btn btn-gradient btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>ส่งคำขอบริการ
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload functionality
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        let selectedFiles = [];

        // Click to select files
        fileUploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            files.forEach(file => {
                if (file.size <= 10 * 1024 * 1024) { // 10MB limit
                    selectedFiles.push(file);
                    displayFile(file);
                } else {
                    alert(`ไฟล์ ${file.name} มีขนาดใหญ่เกิน 10MB`);
                }
            });
            updateFileInput();
        }

        function displayFile(file) {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            const fileExtension = file.name.split('.').pop().toLowerCase();
            let iconClass = 'other';
            let iconName = 'fas fa-file';
            
            if (['pdf'].includes(fileExtension)) {
                iconClass = 'pdf';
                iconName = 'fas fa-file-pdf';
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                iconClass = 'image';
                iconName = 'fas fa-file-image';
            } else if (['doc', 'docx', 'txt'].includes(fileExtension)) {
                iconClass = 'document';
                iconName = 'fas fa-file-word';
            } else if (['zip', 'rar'].includes(fileExtension)) {
                iconClass = 'archive';
                iconName = 'fas fa-file-archive';
            }

            fileItem.innerHTML = `
                <div class="file-info">
                    <div class="file-icon ${iconClass}">
                        <i class="${iconName}"></i>
                    </div>
                    <div>
                        <div class="fw-bold">${file.name}</div>
                        <small class="text-muted">${formatFileSize(file.size)}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFile('${file.name}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            fileList.appendChild(fileItem);
        }

        function removeFile(fileName) {
            selectedFiles = selectedFiles.filter(file => file.name !== fileName);
            updateFileInput();
            displayFileList();
        }

        function displayFileList() {
            fileList.innerHTML = '';
            selectedFiles.forEach(file => displayFile(file));
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form validation
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const workCategory = document.querySelector('input[name="work_category"]:checked');
            const expectedBenefits = document.getElementById('expected_benefits').value.trim();
            const divMgr = document.getElementById('assigned_div_mgr_id').value;
            const serviceId = document.querySelector('input[name="service_id"]:checked');
            
            if (!title || !description || !workCategory || !expectedBenefits || !divMgr || !serviceId) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน (หัวข้อ, รายละเอียด, ประเภทบริการ, หัวข้องานคลัง, ประโยชน์ที่คาดว่าจะได้รับ, และผู้กลั่นกรอง)');
            }
        });
        
        // ฟังก์ชันแสดง/ซ่อนฟิลด์ Development
        function toggleDevelopmentFields(serviceId, category) {
            const developmentFields = document.getElementById('developmentFields');
            const developmentTitle = document.getElementById('developmentTitle');
            
            // ซ่อนฟิลด์ทั้งหมดก่อน
            const allFields = ['newProgramFields', 'fixProblemFields', 'changeDataFields', 'addFunctionFields', 'decorateFields'];
            allFields.forEach(fieldId => {
                document.getElementById(fieldId).style.display = 'none';
            });
            
            if (category === 'development') {
                developmentFields.style.display = 'block';
                
                // แสดงฟิลด์ตามประเภทที่เลือก
                switch(parseInt(serviceId)) {
                    case 1: // โปรแกรมใหม่
                        developmentTitle.textContent = 'ข้อมูลเพิ่มเติมสำหรับโปรแกรมใหม่';
                        document.getElementById('newProgramFields').style.display = 'block';
                        setRequiredFields(['program_purpose', 'target_users', 'main_functions', 'data_requirements']);
                        break;
                    case 2: // โปรแกรมเดิม (แก้ปัญหา)
                        developmentTitle.textContent = 'ข้อมูลเพิ่มเติมสำหรับการแก้ปัญหาโปรแกรม';
                        document.getElementById('fixProblemFields').style.display = 'block';
                        setRequiredFields(['current_program_name', 'problem_description', 'error_frequency']);
                        break;
                    case 3: // โปรแกรมเดิม (เปลี่ยนข้อมูล)
                        developmentTitle.textContent = 'ข้อมูลเพิ่มเติมสำหรับการเปลี่ยนข้อมูลโปรแกรม';
                        document.getElementById('changeDataFields').style.display = 'block';
                        setRequiredFields(['program_name_change', 'data_to_change', 'new_data_value', 'change_reason']);
                        break;
                    case 4: // โปรแกรมเดิม (เพิ่มฟังก์ชั่น)
                        developmentTitle.textContent = 'ข้อมูลเพิ่มเติมสำหรับการเพิ่มฟังก์ชั่นโปรแกรม';
                        document.getElementById('addFunctionFields').style.display = 'block';
                        setRequiredFields(['program_name_function', 'new_functions', 'function_benefits']);
                        break;
                    case 5: // โปรแกรมเดิม (ตกแต่ง)
                        developmentTitle.textContent = 'ข้อมูลเพิ่มเติมสำหรับการตกแต่งโปรแกรม';
                        document.getElementById('decorateFields').style.display = 'block';
                        setRequiredFields(['program_name_decorate', 'decoration_details']);
                        break;
                }
            } else {
                developmentFields.style.display = 'none';
                clearAllDevelopmentFields();
            }
        }
        
        function setRequiredFields(fieldIds) {
            // ลบ required จากฟิลด์ทั้งหมดก่อน
            const allDevelopmentFields = [
                'program_purpose', 'target_users', 'main_functions', 'data_requirements',
                'current_program_name', 'problem_description', 'error_frequency',
                'program_name_change', 'data_to_change', 'new_data_value', 'change_reason',
                'program_name_function', 'new_functions', 'function_benefits',
                'program_name_decorate', 'decoration_details'
            ];
            
            allDevelopmentFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.required = false;
                }
            });
            
            // เพิ่ม required สำหรับฟิลด์ที่ระบุ
            fieldIds.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.required = true;
                }
            });
        }
        
        function clearAllDevelopmentFields() {
            const allInputs = document.querySelectorAll('#developmentFields input, #developmentFields textarea, #developmentFields select');
            allInputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
                input.required = false;
            });
        }
    </script>
</body>
</html>