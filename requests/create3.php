<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูล services
$services_stmt = $conn->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY category, name");
$services_stmt->execute();
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการผู้จัดการฝ่าย
$divmgr_stmt = $conn->prepare("SELECT id, name, lastname FROM users WHERE role = 'divmgr' AND is_active = 1 ORDER BY name");
$divmgr_stmt->execute();
$div_managers = $divmgr_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล departments
$dept_stmt = $conn->prepare("SELECT * FROM departments WHERE is_active = 1 ORDER BY warehouse_number, code_name");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล programs
$programs_stmt = $conn->prepare("SELECT * FROM programs ORDER BY name");
$programs_stmt->execute();
$programs = $programs_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// ฟังก์ชันสร้างเลขที่เอกสาร
function generateDocumentNumber($conn, $warehouse_number, $code_name)
{
  try {
    $conn->beginTransaction();

    $current_year = date('y');  // 2 หลัก เช่น 25
    $current_month = date('n'); // 1-12 (ถ้าอยากได้เลขเต็ม 2 หลัก ใช้ 'm')

    // ดึงเลขรันนิ่งล่าสุดแยกตาม warehouse, code_name, ปี, เดือน
    $stmt = $conn->prepare("
            SELECT COALESCE(MAX(running_number), 0) as max_running 
            FROM document_numbers
            WHERE warehouse_number = ? AND code_name = ? AND year = ? AND month = ?
        ");
    $stmt->execute([$warehouse_number, $code_name, $current_year, $current_month]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $next_running = ($result['max_running'] ?? 0) + 1;
    $running_str = str_pad($next_running, 3, '0', STR_PAD_LEFT);

    // สร้างเลขที่เอกสาร
    $document_number = $warehouse_number . '-' . $code_name . '-' . $current_year . '-' . $current_month . '-' . $running_str;

    // บันทึกเลขที่เอกสาร
    $insert_stmt = $conn->prepare("
            INSERT INTO document_numbers 
            (warehouse_number, code_name, year, month, running_number, document_number) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
    $insert_stmt->execute([$warehouse_number, $code_name, $current_year, $current_month, $next_running, $document_number]);

    $document_id = $conn->lastInsertId();
    $conn->commit();

    return ['document_number' => $document_number, 'document_id' => $document_id];
  } catch (Exception $e) {
    $conn->rollBack();
    error_log("Error generating document number: " . $e->getMessage());
    throw $e;
  }
}

// ประมวลผลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    error_log("=== CREATE FORM SUBMISSION START ===");

    // รับข้อมูลพื้นฐาน
    $service_id = $_POST['service_id'] ?? null;
    $work_category = $_POST['work_category'] ?? null;
    $title = $_POST['title'] ?? '';

    error_log("Basic data - Service ID: $service_id, Work Category: $work_category, Title: $title");
    $assigned_div_mgr_id = !empty($_POST['assigned_div_mgr_id']) ? (int)$_POST['assigned_div_mgr_id'] : null;

    // Validation พื้นฐาน
    if (!$service_id) {
      throw new Exception("กรุณาเลือกประเภทบริการ");
    }

    if (!$work_category) {
      throw new Exception("กรุณาเลือกหัวข้องานคลัง");
    }

    if (!$assigned_div_mgr_id) {
      throw new Exception("กรุณาเลือกผู้จัดการฝ่าย");
    }

    if (empty(trim($title))) {
      throw new Exception("กรุณากรอกหัวข้อคำขอ");
    }

    // ดึงข้อมูล service
    $service_stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $service_stmt->execute([$service_id]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
      throw new Exception("ไม่พบประเภทบริการที่เลือก");
    }

    error_log("Service found: " . $service['name'] . " (" . $service['category'] . ")");

    // แยก warehouse และ code จาก work_category
    $work_parts = explode('-', $work_category);
    if (count($work_parts) !== 2) {
      throw new Exception("รูปแบบหัวข้องานคลังไม่ถูกต้อง");
    }

    $warehouse_number = $work_parts[0];
    $code_name = $work_parts[1];

    error_log("Warehouse: $warehouse_number, Code: $code_name");

    // สร้างเลขที่เอกสาร
    $doc_result = generateDocumentNumber($conn, $warehouse_number, $code_name);
    $document_number = $doc_result['document_number'];
    $document_id = $doc_result['document_id'];

    error_log("Generated document number: $document_number");

    // // เลือก div manager แรก (หรือสามารถให้ผู้ใช้เลือกได้)
    // $div_mgr_id = null;
    // if (!empty($div_managers)) {
    //     $div_mgr_id = $div_managers[0]['id'];
    // }

    $conn->beginTransaction();

    // เตรียมข้อมูลสำหรับบันทึก
    $insert_data = [
      'user_id' => $user_id,
      'title' => trim($title),
      'service_id' => $service_id,
      'work_category' => $work_category,
      'assigned_div_mgr_id' => $assigned_div_mgr_id,
      // 'assigned_div_mgr_id' => $div_mgr_id,
      'status' => 'pending',  //สถานะส่งให้ผู้จัดการฝ่าย 
      'current_step' => 'user_submitted'
    ];

    // รับข้อมูลเพิ่มเติมตามประเภทบริการ
    if ($service['category'] === 'development') {
      // ข้อมูลทั่วไป
      $insert_data['current_workflow'] = $_POST['current_workflow'] ?? null;
      $insert_data['approach_ideas'] = $_POST['approach_ideas'] ?? null;
      $insert_data['related_programs'] = $_POST['related_programs'] ?? null;
      $insert_data['current_tools'] = $_POST['current_tools'] ?? null;
      $insert_data['system_impact'] = $_POST['system_impact'] ?? null;
      $insert_data['related_documents'] = $_POST['related_documents'] ?? null;
      $insert_data['expected_benefits'] = $_POST['expected_benefits'] ?? null;

      // ข้อมูลตามประเภทบริการ
      switch ($service['name']) {
        case 'โปรแกรมใหม่':
          $insert_data['program_purpose'] = $_POST['program_purpose'] ?? null;
          $insert_data['target_users'] = $_POST['target_users'] ?? null;
          $insert_data['main_functions'] = $_POST['main_functions'] ?? null;
          $insert_data['data_requirements'] = $_POST['data_requirements'] ?? null;
          $insert_data['expected_benefits'] = $_POST['expected_benefits'] ?? null;
          break;

        case 'โปรแกรมเดิม (แก้ปัญหา)':
          $insert_data['current_program_name'] = $_POST['current_program_name'] ?? null;
          $insert_data['problem_description'] = $_POST['problem_description'] ?? null;
          $insert_data['error_frequency'] = $_POST['error_frequency'] ?? null;
          $insert_data['steps_to_reproduce'] = $_POST['steps_to_reproduce'] ?? null;
          $insert_data['expected_benefits'] = $_POST['expected_benefits'] ?? null;
          break;

        case 'โปรแกรมเดิม (เปลี่ยนข้อมูล)':
          $insert_data['program_name_change'] = $_POST['program_name_change'] ?? null;
          $insert_data['data_to_change'] = $_POST['data_to_change'] ?? null;
          $insert_data['new_data_value'] = $_POST['new_data_value'] ?? null;
          $insert_data['change_reason'] = $_POST['change_reason'] ?? null;
          $insert_data['expected_benefits'] = $_POST['expected_benefits'] ?? null;
          break;

        case 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)':
          $insert_data['program_name_function'] = $_POST['program_name_function'] ?? null;
          $insert_data['new_functions'] = $_POST['new_functions'] ?? null;
          $insert_data['function_benefits'] = $_POST['function_benefits'] ?? null;
          $insert_data['integration_requirements'] = $_POST['integration_requirements'] ?? null;
          break;

        case 'โปรแกรมเดิม (ตกแต่ง)':
          $insert_data['program_name_decorate'] = $_POST['program_name_decorate'] ?? null;
          $decoration_types = $_POST['decoration_type'] ?? [];
          $insert_data['decoration_type'] = is_array($decoration_types) ? implode(',', $decoration_types) : null;
          $insert_data['reference_examples'] = $_POST['reference_examples'] ?? null;
          $insert_data['expected_benefits'] = $_POST['expected_benefits'] ?? null;
          break;
      }
    }

    // สร้าง description จากข้อมูลที่กรอก
    $description_parts = [];
    $description_parts[] = "ประเภทบริการ: " . $service['name'];
    $description_parts[] = "หัวข้องานคลัง: " . $work_category;

    if ($service['category'] === 'development') {
      switch ($service['name']) {
        case 'โปรแกรมใหม่':
          if ($insert_data['program_purpose']) $description_parts[] = "วัตถุประสงค์: " . $insert_data['program_purpose'];
          if ($insert_data['target_users']) $description_parts[] = "กลุ่มผู้ใช้: " . $insert_data['target_users'];
          if ($insert_data['main_functions']) $description_parts[] = "ฟังก์ชันหลัก: " . $insert_data['main_functions'];
          break;
        case 'โปรแกรมเดิม (แก้ปัญหา)':
          if ($insert_data['current_program_name']) $description_parts[] = "โปรแกรม: " . $insert_data['current_program_name'];
          if ($insert_data['problem_description']) $description_parts[] = "ปัญหา: " . $insert_data['problem_description'];
          break;
        case 'โปรแกรมเดิม (เปลี่ยนข้อมูล)':
          if ($insert_data['program_name_change']) $description_parts[] = "โปรแกรม: " . $insert_data['program_name_change'];
          if ($insert_data['data_to_change']) $description_parts[] = "ข้อมูลที่ต้องเปลี่ยน: " . $insert_data['data_to_change'];
          break;
        case 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)':
          if ($insert_data['program_name_function']) $description_parts[] = "โปรแกรม: " . $insert_data['program_name_function'];
          if ($insert_data['new_functions']) $description_parts[] = "ฟังก์ชั่นใหม่: " . $insert_data['new_functions'];
          break;
        case 'โปรแกรมเดิม (ตกแต่ง)':
          if ($insert_data['program_name_decorate']) $description_parts[] = "โปรแกรม: " . $insert_data['program_name_decorate'];
          if ($insert_data['decoration_type']) $description_parts[] = "ประเภทการตกแต่ง: " . $insert_data['decoration_type'];
          break;
      }
    }

    $insert_data['description'] = implode("\n", $description_parts);

    error_log("Insert data prepared: " . print_r($insert_data, true));

    // สร้าง SQL query
    $columns = array_keys($insert_data);
    $placeholders = array_fill(0, count($columns), '?');

    $sql = "INSERT INTO service_requests (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $values = array_values($insert_data);

    error_log("SQL: $sql");
    error_log("Values: " . print_r($values, true));

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($values);

    if (!$result) {
      throw new Exception("ไม่สามารถบันทึกคำขอได้: " . implode(', ', $stmt->errorInfo()));
    }

    $request_id = $conn->lastInsertId();
    error_log("Service request created with ID: $request_id");

    // อัปเดต service_request_id ในตาราง document_numbers
    $update_doc = $conn->prepare("UPDATE document_numbers SET service_request_id = ? WHERE id = ?");
    $update_doc->execute([$request_id, $document_id]);

    // จัดการไฟล์แนบ
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
      $upload_dir = __DIR__ . '/../uploads/';
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }

      $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar'];
      $max_file_size = 10 * 1024 * 1024; // 10MB

      foreach ($_FILES['attachments']['name'] as $key => $filename) {
        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
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
          }
        }
      }
    }
    $conn->commit();
    // $_SESSION['success'] = "สร้างคำขอบริการสำเร็จ! เลขที่เอกสาร: $document_number";
    header("Location: index.php");
    exit();
  } catch (Exception $e) {
    if ($conn->inTransaction()) {
      $conn->rollBack();
    }
    error_log("Create request error: " . $e->getMessage());
    $error = $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">

  <title>BobbyCareDev-สร้างคำขอบริการ</title>
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
  <style>
    .glass-card {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      box-shadow: var(--card-shadow);
      margin-bottom: 20px;
    }

    .header-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      margin-bottom: 30px;
    }

    .page-title {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
      font-size: 2.2rem;
    }

    .form-grid {
      display: grid;
      gap: 20px;
    }

    .form-section {
      background: white;
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .form-section:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
    }

    .section-title {
      color: #2d3748;
      font-weight: 700;
      font-size: 1.3rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f1f5f9;
    }

    .section-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.1rem;
    }

    .form-control,
    .form-select {
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #f8fafc;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
      background: white;
    }

    .form-label {
      font-weight: 600;
      color: #4a5568;
      margin-bottom: 8px;
      font-size: 1rem;
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

    /* Grid Layouts */
    .user-info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }

    .priority-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
      gap: 12px;
      margin-top: 10px;
    }

    .service-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-top: 10px;
    }

    .work-category-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 10px;
    }

    .development-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    /* Radio Button Styles */
    .radio-option {
      position: relative;
    }

    .radio-option input[type="radio"] {
      display: none;
    }

    .radio-label {
      display: block;
      padding: 12px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 600;
      background: #f8fafc;
    }

    .radio-option input[type="radio"]:checked+.radio-label {
      border-color: #667eea;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .priority-urgent {
      border-color: #dc2626 !important;
    }

    .priority-high {
      border-color: #ea580c !important;
    }

    .priority-medium {
      border-color: #d97706 !important;
    }

    .priority-low {
      border-color: #16a34a !important;
    }

    .service-development {
      border-color: #16a34a !important;
    }

    .service-service {
      border-color: #2563eb !important;
    }

    .work-category-rdc {
      border-color: #2563eb !important;
    }

    .work-category-cdc {
      border-color: #16a34a !important;
    }

    .work-category-bdc {
      border-color: #d97706 !important;
    }

    /* File Upload */
    .file-upload-area {
      border: 3px dashed #cbd5e0;
      border-radius: 20px;
      padding: 30px;
      text-align: center;
      transition: all 0.3s ease;
      background: #f8fafc;
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

    .file-icon.pdf {
      background: #dc2626;
    }

    .file-icon.image {
      background: #16a34a;
    }

    .file-icon.document {
      background: #2563eb;
    }

    .file-icon.archive {
      background: #d97706;
    }

    .file-icon.other {
      background: #6b7280;
    }

    /* Development Fields */
    .development-fields {
      animation: slideDown 0.3s ease-out;
    }

    .development-fields .form-section {
      border-left: 4px solid #16a34a;
      background: linear-gradient(135deg, #f0fdf4, #f7fafc);
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Alert Styles */
    .alert {
      border-radius: 15px;
      border: none;
      padding: 20px;
      font-weight: 500;
      margin-bottom: 25px;
    }

    .alert-success {
      background: linear-gradient(135deg, #dcfce7, #bbf7d0);
      color: #166534;
    }

    .alert-danger {
      background: linear-gradient(135deg, #fef2f2, #fecaca);
      color: #991b1b;
    }

    /* User Info Display */
    .user-info-item {
      background: #f8fafc;
      padding: 12px 16px;
      border-radius: 10px;
      border-left: 4px solid #667eea;
    }

    .user-info-label {
      font-weight: 600;
      color: #4a5568;
      font-size: 0.9rem;
      margin-bottom: 4px;
    }

    .user-info-value {
      color: #2d3748;
      font-size: 1rem;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .page-title {
        font-size: 1.8rem;
      }

      .priority-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .service-grid {
        grid-template-columns: 1fr;
      }

      .work-category-grid {
        grid-template-columns: 1fr;
      }

      .development-grid {
        grid-template-columns: 1fr;
      }

      .user-info-grid {
        grid-template-columns: 1fr;
      }

      .form-section {
        padding: 20px;
      }

      .section-title {
        font-size: 1.1rem;
      }

      .file-upload-area {
        padding: 20px;
      }
    }

    @media (max-width: 576px) {
      body {
        padding: 10px 0;
      }

      .header-card {
        margin-bottom: 20px;
      }

      .form-section {
        padding: 15px;
      }

      .priority-grid {
        grid-template-columns: 1fr;
      }

      .btn-gradient {
        width: 100%;
        padding: 18px;
      }
    }
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
          <a class="nav-link text-white " href="../dashboard2.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">dashboard</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white active bg-gradient-primary" href="create3.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">add_task</i>
            </div>
            <span class="nav-link-text ms-1">สร้างคำขอบริการ</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="index2.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">assignment</i>
            </div>
            <span class="nav-link-text ms-1">รายการคำขอ</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="track_status.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">track_changes</i>
            </div>
            <span class="nav-link-text ms-1">ติดตามสถานะ</span>
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

          <h6 class="font-weight-bolder mb-0">สร้างคำขอบริการ</h6>
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
            <li class="nav-item dropdown pe-2 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-bell cursor-pointer"></i>
              </a>
              <ul class="dropdown-menu  dropdown-menu-end  px-2 py-3 me-sm-n4" aria-labelledby="dropdownMenuButton">
                <li class="mb-2">
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="my-auto">
                        <img src="../assets/img/team-2.jpg" class="avatar avatar-sm  me-3 ">
                      </div>
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          <span class="font-weight-bold">New message</span> from Laur
                        </h6>
                        <p class="text-xs text-secondary mb-0">
                          <i class="fa fa-clock me-1"></i>
                          13 minutes ago
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
                <li class="mb-2">
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="my-auto">
                        <img src="../assets/img/small-logos/logo-spotify.svg" class="avatar avatar-sm bg-gradient-dark  me-3 ">
                      </div>
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          <span class="font-weight-bold">New album</span> by Travis Scott
                        </h6>
                        <p class="text-xs text-secondary mb-0">
                          <i class="fa fa-clock me-1"></i>
                          1 day
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="avatar avatar-sm bg-gradient-secondary  me-3  my-auto">
                        <svg width="12px" height="12px" viewBox="0 0 43 36" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                          <title>credit-card</title>
                          <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g transform="translate(-2169.000000, -745.000000)" fill="#FFFFFF" fill-rule="nonzero">
                              <g transform="translate(1716.000000, 291.000000)">
                                <g transform="translate(453.000000, 454.000000)">
                                  <path class="color-background" d="M43,10.7482083 L43,3.58333333 C43,1.60354167 41.3964583,0 39.4166667,0 L3.58333333,0 C1.60354167,0 0,1.60354167 0,3.58333333 L0,10.7482083 L43,10.7482083 Z" opacity="0.593633743"></path>
                                  <path class="color-background" d="M0,16.125 L0,32.25 C0,34.2297917 1.60354167,35.8333333 3.58333333,35.8333333 L39.4166667,35.8333333 C41.3964583,35.8333333 43,34.2297917 43,32.25 L43,16.125 L0,16.125 Z M19.7083333,26.875 L7.16666667,26.875 L7.16666667,23.2916667 L19.7083333,23.2916667 L19.7083333,26.875 Z M35.8333333,26.875 L28.6666667,26.875 L28.6666667,23.2916667 L35.8333333,23.2916667 L35.8333333,26.875 Z"></path>
                                </g>
                              </g>
                            </g>
                          </g>
                        </svg>
                      </div>
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          Payment successfully completed
                        </h6>
                        <p class="text-xs text-secondary mb-0">
                          <i class="fa fa-clock me-1"></i>
                          2 days
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>





    <!-- End Navbar -->
    <div class="container-fluid py-4">

      <!-- Form -->
      <div class="glass-card p-4">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-3"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="createRequestForm">
          <!-- ข้อมูลพื้นฐาน -->
          <div class="form-section">
            <div class="section-title">

              <span>ข้อมูลพื้นฐาน</span>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="service_id" class="form-label">
                  <i class="fas fa-cogs me-2"></i>ประเภทบริการ <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="service_id" name="service_id" required onchange="handleServiceChange()">
                  <option value="">-- เลือกประเภทบริการ --</option>
                  <?php
                  // กรองเอาเฉพาะ services ที่ category = 'development'
                  $current_category = '';
                  foreach ($services as $service):
                    if ($service['category'] !== 'development') continue;  // ข้ามถ้าไม่ใช่ development

                    if ($current_category !== $service['category']):
                      if ($current_category !== '') echo '</optgroup>';
                      // ตั้งชื่อกลุ่มแค่ Development เท่านั้น
                      echo '<optgroup label="งาน Development">';
                      $current_category = $service['category'];
                    endif;
                  ?>
                    <option value="<?= $service['id'] ?>" data-category="<?= $service['category'] ?>" data-name="<?= htmlspecialchars($service['name']) ?>">
                      <?= htmlspecialchars($service['name']) ?>
                    </option>
                  <?php endforeach; ?>
                  <?php if ($current_category !== '') echo '</optgroup>'; ?>
                </select>
              </div>

              <div class="col-md-6 mb-3">

                <label for="work_category" class="form-label">
                  <i class="fas fa-warehouse me-1"></i>รหัสเเผนกคลัง

                </label>


                <select class="form-select" id="work_category" name="work_category" required>
                  <option value="">-- เลือกรหัสเเผนกคลัง --</option>
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

              </div>

              <div class="col-12 mb-3">
                <label for="title" class="form-label">
                  <i class="fas fa-heading me-2"></i>หัวข้อคำขอ <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="title" name="title" required
                  placeholder="ระบุหัวข้อคำขอบริการ">

                <!-- กล่องข้อความแจ้งเตือน ซ่อนเริ่มต้น -->
                <div id="title-error" class="text-danger mt-1" style="display: none;">
                  ห้ามกรอกอักขระพิเศษ: / * - +
                </div>
              </div>

              <!-- เลือกผู้จัดการฝ่าย -->
              <div class="row">
                <div class="col-12 mb-3">
                  <label for="assigned_div_mgr_id" class="form-label">
                    <i class="fas fa-user-tie me-2"></i>เลือกผู้จัดการฝ่าย <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="assigned_div_mgr_id" name="assigned_div_mgr_id" required>
                    <option value="">-- เลือกผู้จัดการฝ่าย --</option>
                    <?php foreach ($div_managers as $manager): ?>
                      <option value="<?= $manager['id'] ?>">
                        <?= htmlspecialchars($manager['name'] . ' ' . $manager['lastname']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

            </div>
          </div>



          <!-- ฟิลด์เพิ่มเติมสำหรับงาน Development -->
          <div id="developmentFields" class="development-fields">
            <div class="form-section">
              <div class="section-title">

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
                    placeholder="ระบุกลุ่มผู้ใช้งานหลัก เช่น พนักงาน, ผู้จัดการ, ลูกค้า"></textarea>
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
                <div>
                  <label for="expected_benefits" class="form-label">
                    <i class="fas fa-chart-line me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                  </label>
                  <textarea class="form-control" id="expected_benefits" name="expected_benefits" rows="2"
                    placeholder="ระบุประโยชน์หรือผลลัพธ์ที่คาดว่าจะได้รับจากการดำเนินการตามคำขอนี้"></textarea>
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
                    placeholder="ระบุขั้นตอนการใช้งานที่ทำให้เกิดปัญหา"></textarea>
                </div>
                <div>
                  <label for="expected_benefits" class="form-label">
                    <i class="fas fa-chart-line me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                  </label>
                  <textarea class="form-control" id="expected_benefits" name="expected_benefits" rows="2"
                    placeholder="ระบุประโยชน์หรือผลลัพธ์ที่คาดว่าจะได้รับจากการดำเนินการตามคำขอนี้"></textarea>
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
                <div>
                  <label for="expected_benefits" class="form-label">
                    <i class="fas fa-chart-line me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                  </label>
                  <textarea class="form-control" id="expected_benefits" name="expected_benefits" rows="2"
                    placeholder="ระบุประโยชน์หรือผลลัพธ์ที่คาดว่าจะได้รับจากการดำเนินการตามคำขอนี้"></textarea>
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
                          เปลี่ยน ICON
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
                <div>
                  <label for="expected_benefits" class="form-label">
                    <i class="fas fa-chart-line me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                  </label>
                  <textarea class="form-control" id="expected_benefits" name="expected_benefits" rows="2"
                    placeholder="ระบุประโยชน์หรือผลลัพธ์ที่คาดว่าจะได้รับจากการดำเนินการตามคำขอนี้"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- ไฟล์แนบ -->
          <div class="form-section">
            <div class="section-title">

              <span>ไฟล์แนบเอกสารที่เกี่ยวข้อกับการทำงาน (SD) หรือสไลด์การทำเสนอ</span>
            </div>

            <div class="mb-3">
              <label for="attachments" class="form-label">
                <i class="fas fa-upload me-2"></i>เลือกไฟล์แนบ
              </label>
              <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar">
              <div class="form-text">
                ประเภทไฟล์ที่รองรับ: PDF, DOC, DOCX, JPG, PNG, GIF, TXT, ZIP, RAR (ขนาดไม่เกิน 10MB ต่อไฟล์)
              </div>
            </div>

            <!-- ปุ่มส่ง -->
            <div class="text-center">
              <button type="submit" class="btn btn-gradient btn-lg">
                <i class="fas fa-paper-plane me-2"></i>ส่งคำขอบริการ
              </button>
            </div>
          </div>


        </form>
      </div>
    </div>




    <footer class="footer py-4  ">
      <div class="container-fluid">
        <div class="row align-items-center justify-content-lg-between">
          <div class="col-lg-6 mb-lg-0 mb-4">
            <div class="copyright text-center text-sm text-muted text-lg-start">
              © <script>
                document.write(new Date().getFullYear())
              </script>,
              made with <i class="fa fa-heart"></i> by
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function handleServiceChange() {
    console.log('handleServiceChange called');

    const serviceSelect = document.getElementById('service_id');
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    const category = selectedOption.getAttribute('data-category');
    const serviceName = selectedOption.getAttribute('data-name');

    console.log('Selected service:', serviceName);
    console.log('Category:', category);

    // ซ่อนฟิลด์ development ทั้งหมด
    const developmentFields = document.getElementById('developmentFields');
    const allDevFields = [
      'newProgramFields',
      'fixProblemFields',
      'changeDataFields',
      'addFunctionFields',
      'decorateFields'
    ];

    // ล้าง required และซ่อนฟิลด์ทั้งหมด
    allDevFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        field.style.display = 'none';
        // ลบ required จากฟิลด์ในกลุ่มนี้
        field.querySelectorAll('input, select, textarea').forEach(input => {
          input.removeAttribute('required');
        });
      }
    });

    if (category === 'development') {
      console.log('Showing development fields');
      developmentFields.style.display = 'block';

      // แสดงฟิลด์ตามประเภทบริการ
      let targetFieldId = '';
      let requiredFields = [];

      switch (serviceName) {
        case 'โปรแกรมใหม่':
          targetFieldId = 'newProgramFields';
          requiredFields = ['program_purpose', 'target_users', 'main_functions', 'data_requirements'];
          break;
        case 'โปรแกรมเดิม (แก้ปัญหา)':
          targetFieldId = 'fixProblemFields';
          requiredFields = ['current_program_name', 'problem_description', 'error_frequency'];
          break;
        case 'โปรแกรมเดิม (เปลี่ยนข้อมูล)':
          targetFieldId = 'changeDataFields';
          requiredFields = ['program_name_change', 'data_to_change', 'new_data_value', 'change_reason'];
          break;
        case 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)':
          targetFieldId = 'addFunctionFields';
          requiredFields = ['program_name_function', 'new_functions', 'function_benefits'];
          break;
        case 'โปรแกรมเดิม (ตกแต่ง)':
          targetFieldId = 'decorateFields';
          requiredFields = ['program_name_decorate'];
          break;
      }

      if (targetFieldId) {
        const targetField = document.getElementById(targetFieldId);
        if (targetField) {
          targetField.style.display = 'grid';

          // เพิ่ม required ให้ฟิลด์ที่จำเป็น
          requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
              field.setAttribute('required', 'required');
            }
          });

          console.log('Showing field:', targetFieldId);
          console.log('Required fields:', requiredFields);
        }
      }

      // อัปเดตชื่อหัวข้อ
      const titleElement = document.getElementById('developmentTitle');
      if (titleElement) {
        titleElement.textContent = `ข้อมูลเพิ่มเติมสำหรับ ${serviceName}`;
      }
    } else {
      console.log('Hiding development fields');
      developmentFields.style.display = 'none';
    }
  }

  document.getElementById('createRequestForm').addEventListener('submit', function(e) {
    console.log('Form submission started');

    const serviceId = document.getElementById('service_id').value;
    const workCategory = document.getElementById('work_category').value;
    const title = document.getElementById('title').value.trim();

    const errorDiv = document.getElementById('title-error');
    const forbiddenPattern = /[\/\*\-\+]/;

    // รายการ input IDs ที่จะตรวจ
    const fieldsToCheck = [
      'program_purpose',
      'target_users',
      'main_functions',
      'data_requirements',
      'current_workflow',
      'related_programs',
      'expected_benefits',
      'problem_description',
      'steps_to_reproduce',
      'data_to_change',
      'new_data_value',
      'change_reason',
      'expected_benefits',
      'new_functions',
      'function_benefits',
      'integration_requirements',
      'reference_examples',
      'expected_benefits',
      'expected_benefits'

    ];

    console.log('Service ID:', serviceId);
    console.log('Work Category:', workCategory);
    console.log('Title:', title);

    if (!serviceId) {
      e.preventDefault();
      alert('กรุณาเลือกประเภทบริการ');
      return false;
    }

    if (!workCategory) {
      e.preventDefault();
      alert('กรุณาเลือกหัวข้องานคลัง');
      return false;
    }

    if (!title) {
      e.preventDefault();
      alert('กรุณากรอกหัวข้อคำขอ');
      return false;
    }

    if (forbiddenPattern.test(title)) {
      errorDiv.style.display = 'block';
      e.preventDefault();
      return false;
    } else {
      errorDiv.style.display = 'none';
    }

    for (let fieldId of fieldsToCheck) {
      const field = document.getElementById(fieldId);
      if (field) {
        const value = field.value.trim();
        if (forbiddenPattern.test(value)) {
          e.preventDefault();
          alert(`ช่อง "${fieldId}" ห้ามมีอักขระพิเศษ เช่น / * - +`);
          field.focus();
          return false;
        }
      }
    }

    console.log('Form validation passed');
    return true;
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

</html>