<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['line_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $line_id = $_SESSION['line_id'];
    $name = $_POST['name'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $position = $_POST['position'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department = $_POST['department'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';

    if ($name && $lastname) {
        $stmt = $conn->prepare("
            INSERT INTO users 
                (line_id, name, lastname, email, position, phone, department, employee_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $line_id, $name, $lastname, $email, $position, $phone, $department, $employee_id
        ]);

        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['name'] = $name;
        header("Location: ../dashboard.php");
        exit();
    } else {
        $error = "กรุณากรอกชื่อและนามสกุล";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ลงทะเบียนข้อมูลเพิ่มเติม</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(to right, #eef2f3, #d4dde4);
    }

    .card {
      border: none;
      border-radius: 1.5rem;
    }

    .card-header {
      background: linear-gradient(to right, #0d6efd, #3b82f6);
      color: white;
      border-radius: 1.5rem 1.5rem 0 0;
      padding: 2rem 1.5rem;
      text-align: center;
    }

    .form-control, .form-select {
      border-radius: 0.75rem;
    }

    .btn-primary {
      border-radius: 0.75rem;
      padding: 0.6rem 2rem;
      font-weight: 500;
    }

    .form-label span {
      color: red;
    }
  </style>
</head>
<body>

<div class="container mt-5 mb-5">
  <div class="card shadow-lg">
    <div class="card-header">
      <h3 class="mb-0"><i class="bi bi-person-plus-fill"></i> ลงทะเบียนข้อมูลเพิ่มเติม</h3>
    </div>

    <div class="card-body p-4">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="row g-4">
        <div class="col-12">
          <label class="form-label">LINE Name:</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['name']) ?>" disabled>
        </div>

        <div class="col-md-6">
          <label for="name" class="form-label">ชื่อจริง <span>*</span></label>
          <input type="text" class="form-control" name="name" required>
        </div>

        <div class="col-md-6">
          <label for="lastname" class="form-label">นามสกุล <span>*</span></label>
          <input type="text" class="form-control" name="lastname" required>
        </div>

        <div class="col-md-6">
          <label for="employee_id" class="form-label">รหัสพนักงาน</label>
          <input type="text" class="form-control" name="employee_id">
        </div>

        <div class="col-md-6">
          <label for="position" class="form-label">ตำแหน่งงาน</label>
          <select name="position" class="form-select">
            <option value="">-- กรุณาเลือกตำแหน่ง --</option>
            <option value="พนักงาน">พนักงาน</option>
            <option value="เจ้าหน้าที่">เจ้าหน้าที่</option>
            <option value="เจ้าหน้าที่อาวุโส">เจ้าหน้าที่อาวุโส</option>
            <option value="ผู้ช่วยหัวหน้าหน่วย">ผู้ช่วยหัวหน้าหน่วย</option>
            <option value="หัวหน้าหน่วย">หัวหน้าหน่วย</option>
            <option value="ผู้จัดการแผนก">ผู้จัดการแผนก</option>
            <option value="ผู้จัดการฝ่าย">ผู้จัดการฝ่าย</option>
          </select>
        </div>

        <div class="col-md-6">
          <label for="department" class="form-label">หน่วยงาน</label>
          <select name="department" class="form-select">
            <option value="">-- กรุณาเลือกหน่วยงาน --</option>
            <optgroup label="RDC">
              <option value="แผนก Break Case">แผนก Break Case</option>
              <option value="แผนก Full Case">แผนก Full Case</option>
              <option value="แผนก MIS">แผนก MIS</option>
              <option value="แผนก O2O">แผนก O2O</option>
              <option value="แผนกข้อมูลจ่าย">แผนกข้อมูลจ่าย</option>
              <option value="แผนกข้อมูลรับ">แผนกข้อมูลรับ</option>
              <option value="แผนกควบคุมสินค้า">แผนกควบคุมสินค้า</option>
              <option value="แผนกความปลอดภัย">แผนกความปลอดภัย</option>
              <option value="แผนกจัดเก็บและเติม">แผนกจัดเก็บและเติม</option>
              <option value="แผนกจัดส่งขาเข้า">แผนกจัดส่งขาเข้า</option>
              <option value="แผนกจัดส่งขาออก">แผนกจัดส่งขาออก</option>
              <option value="แผนกธุรการ">แผนกธุรการ</option>
              <option value="แผนกบริหารจัดการ Riders">แผนกบริหารจัดการ Riders</option>
              <option value="แผนกบริหารจัดการสินค้าฝากส่งและพัสดุ">แผนกบริหารจัดการสินค้าฝากส่งและพัสดุ</option>
              <option value="แผนกบำรุงรักษา">แผนกบำรุงรักษา</option>
              <option value="แผนกพัฒนาระบบงาน">แผนกพัฒนาระบบงาน</option>
              <option value="แผนกพัฒนาองค์กร">แผนกพัฒนาองค์กร</option>
              <option value="แผนกรับคืนสินค้า">แผนกรับคืนสินค้า</option>
              <option value="แผนกรับสินค้า">แผนกรับสินค้า</option>
              <option value="แผนกวางแผนจัดส่ง">แผนกวางแผนจัดส่ง</option>
              <option value="แผนกส่งมอบสินค้า">แผนกส่งมอบสินค้า</option>
              <option value="แผนกสินค้าควบคุม">แผนกสินค้าควบคุม</option>
              <option value="แผนกสินค้าพิเศษ">แผนกสินค้าพิเศษ</option>
            </optgroup>

            <optgroup label="CDC">
              <option value="แผนก MIS">แผนก MIS</option>
              <option value="แผนกข้อมูลจ่าย">แผนกข้อมูลจ่าย</option>
              <option value="แผนกข้อมูลรับ">แผนกข้อมูลรับ</option>
              <option value="แผนกควบคุมคุณภาพ">แผนกควบคุมคุณภาพ</option>
              <option value="แผนกความปลอดภัย">แผนกความปลอดภัย</option>
              <option value="แผนกจัดส่งขาเข้า">แผนกจัดส่งขาเข้า</option>
              <option value="แผนกจัดส่งขาออก">แผนกจัดส่งขาออก</option>
              <option value="แผนกบำรุงรักษา">แผนกบำรุงรักษา</option>
              <option value="แผนกรับสินค้า">แผนกรับสินค้า</option>
              <option value="แผนกส่งมอบสินค้า">แผนกส่งมอบสินค้า</option>
            </optgroup>

            <optgroup label="BDC">
              <option value="แผนกข้อมูลเบเกอรี่">แผนกข้อมูลเบเกอรี่</option>
              <option value="แผนกควบคุมคุณภาพ">แผนกควบคุมคุณภาพ</option>
              <option value="แผนกจัดส่งเบเกอรี่">แผนกจัดส่งเบเกอรี่</option>
              <option value="แผนกจัดสินค้า">แผนกจัดสินค้า</option>
              <option value="แผนกรับคืนสินค้าสินค้าเบเกอรี่">แผนกรับคืนสินค้าสินค้าเบเกอรี่</option>
              <option value="แผนกรับและส่งมอบเบเกอรี่">แผนกรับและส่งมอบเบเกอรี่</option>
            </optgroup>

            <optgroup label="ฝ่าย (นครสวรรค์)">
              <option value="ฝ่ายจัดสินค้า (RDC.นครสวรรค์)">ฝ่ายจัดสินค้า (RDC.นครสวรรค์)</option>
              <option value="ฝ่ายควบคุมสินค้าคงคลัง (RDC.นครสวรรค์)">ฝ่ายควบคุมสินค้าคงคลัง</option>
              <option value="ฝ่ายกระจายสินค้า (CDC.นครสวรรค์)">ฝ่ายกระจายสินค้า (CDC.นครสวรรค์)</option>
              <option value="ฝ่ายกระจายสินค้า (BDC.นครสวรรค์)">ฝ่ายกระจายสินค้า (BDC.นครสวรรค์)</option>
              <option value="ฝ่ายจัดเก็บและเติม (RDC.นครสวรรค์)">ฝ่ายจัดเก็บและเติม</option>
              <option value="ฝ่ายจัดส่งสินค้า (Complex นครสวรรค์)">ฝ่ายจัดส่งสินค้า</option>
              <option value="ฝ่ายบริหารทั่วไป (Complex นครสวรรค์)">ฝ่ายบริหารทั่วไป</option>
              <option value="ฝ่ายวิศวกรรม (Complex นครสวรรค์)">ฝ่ายวิศวกรรม</option>
              <option value="ฝ่ายรับและส่งมอบ (RDC.นครสวรรค์)">ฝ่ายรับและส่งมอบ</option>
            </optgroup>

            <option value="อื่นๆ">อื่นๆ</option>
          </select>
        </div>

        <div class="col-md-6">
          <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
          <input type="text" class="form-control" name="phone">
        </div>

        <div class="col-12">
          <label for="email" class="form-label">อีเมล</label>
          <input type="email" class="form-control" name="email">
        </div>

        <div class="col-12 text-end mt-4">
          <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap Icons (optional) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>
