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
    $role = $_POST['role'] ?? 'user';

    if ($name && $lastname) {
        $stmt = $conn->prepare("
            INSERT INTO users 
                (line_id, name, lastname, email, position, phone, department, employee_id, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $line_id, $name, $lastname, $email, $position, $phone, $department, $employee_id, $role
        ]);

        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['name'] = $name;
        header("Location: ../dashboard2.php");
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
<title>BobbyCareDev</title>
<link rel="icon" href="/BobbyCareRemake/img/logo/bobby-icon.png" type="image/x-icon" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
    body {
        background: linear-gradient(135deg, #eef2f3, #d4dde4);
        font-family: 'Prompt', sans-serif;
    }
    .card {
        border: none;
        border-radius: 1.5rem;
        overflow: hidden;
    }
    .card-header {
        background: linear-gradient(to right, #0d6efd, #3b82f6);
        color: white;
        padding: 2rem 1.5rem;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .card-header h3 {
        margin: 0;
        font-weight: 600;
    }
    .form-control, .form-select {
        border-radius: 0.75rem;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
    }
    .btn-primary {
        border-radius: 0.75rem;
        padding: 0.6rem 2rem;
        font-weight: 500;
        background: linear-gradient(to right, #0d6efd, #3b82f6);
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13,110,253,0.4);
    }
    .form-label span {
        color: red;
    }
    /* ปรับช่องค้นหาให้ดูพรีเมียม */
    .input-group .form-control {
        border-radius: 0.75rem 0 0 0.75rem;
    }
    .input-group .btn {
        border-radius: 0 0.75rem 0.75rem 0;
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

      <!-- Search Template -->
    <div class="col-12 mb-3">
    <label class="form-label">ค้นหาจากรายชื่อ</label>
    <div class="input-group">
      <input type="text" id="searchTemplate" class="form-control" placeholder="พิมพ์ชื่อหรือรหัสพนักงาน">
      <button type="button" id="btnSearchTemplate" class="btn btn-secondary">
        <i class="bi bi-search"></i> ค้นหา
      </button>
    </div>
</div>

<!-- ฟอร์มลงทะเบียน (ซ่อนก่อน) -->
<div id="registerForm" style="display:none;">
    <form method="POST" class="row g-4 mt-4">
        <div class="col-12">
          <label class="form-label">LINE Name:</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['name']) ?>" disabled>
        </div>
        <div class="col-md-6">
          <label class="form-label">ชื่อจริง <span>*</span></label>
          <input type="text" class="form-control" name="name" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">นามสกุล <span>*</span></label>
          <input type="text" class="form-control" name="lastname" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">รหัสพนักงาน</label>
          <input type="text" class="form-control" name="employee_id">
        </div>
        <div class="col-md-6">
          <label class="form-label">ตำแหน่งงาน</label>
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
          <label class="form-label">หน่วยงาน</label>
          <input type="text" name="department" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">เบอร์โทรศัพท์</label>
          <input type="text" class="form-control" name="phone">
        </div>
        <div class="col-12">
          <label class="form-label">อีเมล</label>
          <input type="email" class="form-control" name="email">
        </div>
       <input type="hidden" name="role" value="user">


        <div class="col-12 text-end mt-4">
          <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
        </div>
    </form>
</div>

  </div>
</div>
<script>
document.getElementById('btnSearchTemplate').addEventListener('click', function() {
    let keyword = document.getElementById('searchTemplate').value.trim();
    if (keyword === '') {
        alert('กรุณากรอกชื่อหรือรหัสพนักงาน');
        return;
    }
    fetch('search_usertemplate.php?q=' + encodeURIComponent(keyword))
        .then(res => res.json())
        .then(data => {
            if (data && data.name) {
                // เติมข้อมูลลงฟอร์ม
                document.querySelector('[name="name"]').value = data.name;
                document.querySelector('[name="lastname"]').value = data.lastname;
                document.querySelector('[name="employee_id"]').value = data.employee_id;
                document.querySelector('[name="position"]').value = data.position;
                document.querySelector('[name="department"]').value = data.department;
                document.querySelector('[name="phone"]').value = data.phone;
                document.querySelector('[name="email"]').value = data.email;
                document.querySelector('[name="role"]').value = data.role;

                // แสดงฟอร์ม
                document.getElementById('registerForm').style.display = 'block';
            } else {
                alert('ไม่พบข้อมูลที่ค้นหา');
                document.getElementById('registerForm').style.display = 'none';
            }
        })
        .catch(err => console.error(err));
});
</script>

</body>
</html>
