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

// ดึงรายการ services ประเภท development
$services_stmt = $conn->prepare("SELECT * FROM services WHERE category = 'development' AND is_active = 1 ORDER BY name");
$services_stmt->execute();
$development_services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);


// ดึงข้อมูลผู้ใช้
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// ดึงรายชื่อผู้จัดการฝ่าย
$div_mgr_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'divmgr' AND is_active = 1 ORDER BY name");
$div_mgr_stmt->execute();
$div_managers = $div_mgr_stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ามีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $work_category = $_POST['work_category'] ?? null;
    $expected_benefits = trim($_POST['expected_benefits'] ?? '');
    $assigned_div_mgr_id = $_POST['assigned_div_mgr_id'] ?? null;

    if ($title !== '' && $description !== '' && $work_category && $expected_benefits && $assigned_div_mgr_id) {
        try {
            $conn->beginTransaction();

            // สร้าง service request
            $stmt = $conn->prepare("INSERT INTO service_requests (user_id, title, description, priority, work_category, expected_benefits, assigned_div_mgr_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'div_mgr_review')");
            $stmt->execute([$user_id, $title, $description, $priority, $work_category, $expected_benefits, $assigned_div_mgr_id]);
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

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            font-size: 1.1rem;
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
        }

        .file-upload-area {
            border: 3px dashed #cbd5e0;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .file-upload-area.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.02);
        }

        .file-list {
            margin-top: 20px;
        }

        .file-item {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .file-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .file-icon.pdf { background: #e53e3e; }
        .file-icon.image { background: #38a169; }
        .file-icon.document { background: #3182ce; }
        .file-icon.archive { background: #d69e2e; }
        .file-icon.other { background: #718096; }

        .priority-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .priority-option {
            position: relative;
        }

        .priority-option input[type="radio"] {
            display: none;
        }

        .priority-label {
            display: block;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .priority-option input[type="radio"]:checked + .priority-label {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .priority-low { color: #38a169; }
        .priority-medium { color: #d69e2e; }
        .priority-high { color: #e53e3e; }
        .priority-urgent { color: #9f1239; }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #1a202c;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #1a202c;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .priority-selector {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Header -->
        <div class="header-card p-5 mb-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-plus-circle text-white fs-2"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-2">สร้างคำขอบริการใหม่</h1>
                            <p class="text-muted mb-0 fs-5">กรอกรายละเอียดและแนบไฟล์เพื่อส่งคำขอ</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="index.php" class="btn btn-outline-gradient">
                        <i class="fas fa-arrow-left me-2"></i>กลับรายการ
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="glass-card p-5">
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

            <form method="post" enctype="multipart/form-data" id="requestForm">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- ข้อมูลผู้ขอ -->
                        <div class="glass-card p-4 mb-4">
                            <h4 class="fw-bold mb-3">
                                <i class="fas fa-user me-2"></i>ข้อมูลผู้ขอ
                            </h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>รหัสพนักงาน:</strong> <?= htmlspecialchars($user_data['employee_id'] ?? 'ไม่ระบุ') ?></p>
                                    <p><strong>ชื่อ-นามสกุล:</strong> <?= htmlspecialchars($user_data['name'] . ' ' . $user_data['lastname']) ?></p>
                                    <p><strong>ตำแหน่ง:</strong> <?= htmlspecialchars($user_data['position'] ?? 'ไม่ระบุ') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>หน่วยงาน:</strong> <?= htmlspecialchars($user_data['department'] ?? 'ไม่ระบุ') ?></p>
                                    <p><strong>เบอร์โทรศัพท์:</strong> <?= htmlspecialchars($user_data['phone'] ?? 'ไม่ระบุ') ?></p>
                                    <p><strong>อีเมล:</strong> <?= htmlspecialchars($user_data['email'] ?? 'ไม่ระบุ') ?></p>
                                </div>
                            </div>
                        </div>



    <!-- ------------------------------------------------------------------------- -->
     <div class="container mt-5">
    <div class="form-group">
        <label for="devServiceSelect">
            <i class="fas fa-cogs me-2"></i>เลือกประเภทงาน Development:
        </label>
        <select class="form-select" id="devServiceSelect">
            <option value="">-- เลือกประเภทงาน --</option>
            <option value="new_program" data-modal="#modalNewProgram">โปรแกรมใหม่</option>
            <option value="existing_add_feature" data-modal="#modalAddFeature">เพิ่มฟังก์ชันในระบบเดิม</option>
            <option value="other">อื่นๆ</option>
        </select>
        <small class="text-muted">เลือกหัวข้อเพื่อเปิดฟอร์มเฉพาะ</small>
    </div>
</div>
    <!-- ------------------------------------------------------------------------- -->



                        <div class="mb-4">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-2"></i>หัวข้อคำขอ
                            </label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   placeholder="ระบุหัวข้อคำขอบริการ">
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-2"></i>รายละเอียด
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="6" required
                                      placeholder="อธิบายรายละเอียดคำขอบริการ เช่น ปัญหาที่พบ ความต้องการ หรือข้อกำหนดพิเศษ"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="expected_benefits" class="form-label">
                                <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                            </label>
                            <textarea class="form-control" id="expected_benefits" name="expected_benefits" rows="3" required
                                      placeholder="ระบุประโยชน์หรือผลลัพธ์ที่คาดว่าจะได้รับจากการดำเนินการตามคำขอนี้"></textarea>
                        </div>

                    
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-building me-2"></i>หัวข้องานคลัง
                            </label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="work_category" value="RDC" id="rdc" required>
                                    <label class="form-check-label fw-bold text-primary" for="rdc">
                                        <i class="fas fa-database me-2"></i>RDC (Regional Distribution Center)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="work_category" value="CDC" id="cdc" required>
                                    <label class="form-check-label fw-bold text-success" for="cdc">
                                        <i class="fas fa-warehouse me-2"></i>CDC (Central Distribution Center)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="work_category" value="BDC" id="bdc" required>
                                    <label class="form-check-label fw-bold text-warning" for="bdc">
                                        <i class="fas fa-truck me-2"></i>BDC (Branch Distribution Center)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="assigned_div_mgr_id" class="form-label">
                                <i class="fas fa-user-tie me-2"></i>ผู้กลั่นกรอง (ผู้จัดการฝ่าย)
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

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-exclamation-circle me-2"></i>ระดับความสำคัญ
                            </label>
                            <div class="priority-selector">
                                <div class="priority-option">
                                    <input type="radio" id="priority_low" name="priority" value="low">
                                    <label for="priority_low" class="priority-label priority-low">
                                        <i class="fas fa-circle mb-2"></i><br>ต่ำ
                                    </label>
                                </div>
                                <div class="priority-option">
                                    <input type="radio" id="priority_medium" name="priority" value="medium" checked>
                                    <label for="priority_medium" class="priority-label priority-medium">
                                        <i class="fas fa-circle mb-2"></i><br>ปานกลาง
                                    </label>
                                </div>
                                <div class="priority-option">
                                    <input type="radio" id="priority_high" name="priority" value="high">
                                    <label for="priority_high" class="priority-label priority-high">
                                        <i class="fas fa-circle mb-2"></i><br>สูง
                                    </label>
                                </div>
                                <div class="priority-option">
                                    <input type="radio" id="priority_urgent" name="priority" value="urgent">
                                    <label for="priority_urgent" class="priority-label priority-urgent">
                                        <i class="fas fa-circle mb-2"></i><br>เร่งด่วน
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-paperclip me-2"></i>แนบไฟล์ (ไม่บังคับ)
                    </label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h5>
                        <p class="text-muted mb-0">รองรับไฟล์: PDF, รูปภาพ, เอกสาร, ไฟล์บีบอัด (สูงสุด 10MB ต่อไฟล์)</p>
                        <input type="file" id="fileInput" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.txt,.zip,.rar" style="display: none;">
                    </div>
                    <div class="file-list" id="fileList"></div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-gradient btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>ส่งคำขอบริการ
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- ------------------------------------------------------------------------- -->
 <!-- Modal: โปรแกรมใหม่ -->
<div class="modal fade" id="modalNewProgram" tabindex="-1" aria-labelledby="modalNewProgramLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalNewProgramLabel">📦 ขอพัฒนาระบบใหม่</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label class="form-label">ขั้นตอนการทำงานเดิม</label>
            <textarea class="form-control" rows="3" placeholder="อธิบาย flow การทำงานแบบ manual หรือเดิมที่ใช้"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">แนวทาง/ไอเดีย (เช่น จากกรอกมือ → คีย์โปรแกรม)</label>
            <textarea class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">โปรแกรมที่คาดว่าจะเกี่ยวข้อง</label>
            <input type="text" class="form-control">
          </div>
          
            <div class="mb-3">
            <label class="form-label">ปกติใช้โปรเเกรมอะไรทำงานอยู่</label>
          <textarea class="form-control" rows="2"></textarea>
          </div>
            <div class="mb-3">
            <label class="form-label">ถ้ากรณีต้องระบบหรือปิด Server จะกระทบต่อกระบวนการอะไรบ้างในตอนนี้</label>
             <textarea class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">เอกสารการทำงานที่เกี่ยวข้อง ( SD )</label>
            <input type="file" class="form-control">
          </div>

        </form>
      </div>
    </div>
  </div>
</div>
<!-- Modal: เพิ่มฟังก์ชัน -->
<div class="modal fade" id="modalAddFeature" tabindex="-1" aria-labelledby="modalAddFeatureLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalAddFeatureLabel">🔧 เพิ่มฟังก์ชันระบบเดิม</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label class="form-label">ชื่อโปรแกรมเดิม</label>
            <input type="text" class="form-control" placeholder="เช่น ระบบเบิกของ">
          </div>
          <div class="mb-3">
            <label class="form-label">สิ่งที่ต้องการให้เพิ่ม</label>
            <textarea class="form-control" rows="2" placeholder="เช่น เพิ่มปุ่ม export, เพิ่มช่องใหม่"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">เหตุผลและประโยชน์ที่คาดหวัง</label>
            <textarea class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">แนบภาพหรือเอกสารประกอบ (ถ้ามี)</label>
            <input type="file" class="form-control">
          </div>
        </form>
      </div>
    </div>
  </div>
</div>



    <!-- ------------------------------------------------------------------------- -->

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
        } requests/create.php

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
            
            if (!title || !description || !workCategory || !expectedBenefits || !divMgr) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน (หัวข้อ, รายละเอียด, หัวข้องานคลัง, ประโยชน์ที่คาดว่าจะได้รับ, และผู้กลั่นกรอง)');
            }
        });



    </script>
        <!-- ------------------------------------------------------------------------- -->
    <script>
document.addEventListener("DOMContentLoaded", function () {
    const select = document.getElementById("devServiceSelect");
    select.addEventListener("change", function () {
        const selected = select.options[select.selectedIndex];
        const modalSelector = selected.getAttribute("data-modal");

        if (modalSelector) {
            const modal = new bootstrap.Modal(document.querySelector(modalSelector));
            modal.show();
        }
    });
});
</script>
    <!-- ------------------------------------------------------------------------- -->
</body>
</html>