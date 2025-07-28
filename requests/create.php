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
                       <a href="create2.php" class="btn btn-outline-gradient">
                        <i class="fas fa-arrow-left me-2"></i>ทดสอบ
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
                            ข้อมูลเพิ่มเติมสำหรับงาน Development
                        </div>
                        <div class="development-grid">
                            <div>
                                <label for="current_workflow" class="form-label">
                                    <i class="fas fa-list-ol me-2"></i>ขั้นตอนการทำงานเดิม
                                </label>
                                <textarea class="form-control" id="current_workflow" name="current_workflow" rows="3"
                                          placeholder="อธิบายขั้นตอนการทำงานปัจจุบัน เช่น วิธีการทำงาน กระบวนการที่ใช้อยู่"></textarea>
                            </div>
                            <div>
                                <label for="approach_ideas" class="form-label">
                                    <i class="fas fa-lightbulb me-2"></i>แนวทาง/ไอเดีย
                                </label>
                                <textarea class="form-control" id="approach_ideas" name="approach_ideas" rows="3"
                                          placeholder="แนวทางหรือไอเดียที่คิดว่าควรจะพัฒนา เช่น วิธีการใหม่ที่ต้องการ"></textarea>
                            </div>
                            <div>
                                <label for="related_programs" class="form-label">
                                    <i class="fas fa-desktop me-2"></i>โปรแกรมที่คาดว่าจะเกี่ยวข้อง
                                </label>
                                <textarea class="form-control" id="related_programs" name="related_programs" rows="2"
                                          placeholder="โปรแกรมหรือระบบที่คาดว่าจะต้องใช้ในการพัฒนา"></textarea>
                            </div>
                            <div>
                                <label for="current_tools" class="form-label">
                                    <i class="fas fa-tools me-2"></i>ปกติใช้โปรแกรมอะไรทำงานอยู่
                                </label>
                                <textarea class="form-control" id="current_tools" name="current_tools" rows="2"
                                          placeholder="โปรแกรมหรือเครื่องมือที่ใช้ทำงานปัจจุบัน"></textarea>
                            </div>
                            <div>
                                <label for="system_impact" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-2"></i>ผลกระทบต่อระบบ
                                </label>
                                <textarea class="form-control" id="system_impact" name="system_impact" rows="3"
                                          placeholder="อธิบายผลกระทบที่อาจเกิดขึ้นหากต้องปิดระบบหรือ Server"></textarea>
                            </div>
                            <!-- <div>
                                <label for="related_documents" class="form-label">
                                    <i class="fas fa-file-alt me-2"></i>เอกสารที่เกี่ยวข้อง
                                </label>
                                <textarea class="form-control" id="related_documents" name="related_documents" rows="2"
                                          placeholder="เอกสาร คู่มือ หรือข้อมูลที่เกี่ยวข้องกับงานนี้"></textarea>
                            </div> -->
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
            
            if (category === 'development') {
                developmentFields.style.display = 'block';
                // เพิ่ม required สำหรับฟิลด์สำคัญ
                document.getElementById('current_workflow').required = true;
                document.getElementById('approach_ideas').required = true;
            } else {
                developmentFields.style.display = 'none';
                // ลบ required
                document.getElementById('current_workflow').required = false;
                document.getElementById('approach_ideas').required = false;
                
                // เคลียร์ค่า
                document.getElementById('current_workflow').value = '';
                document.getElementById('approach_ideas').value = '';
                document.getElementById('related_programs').value = '';
                document.getElementById('current_tools').value = '';
                document.getElementById('system_impact').value = '';
                document.getElementById('related_documents').value = '';
            }
        }
    </script>
</body>
</html>