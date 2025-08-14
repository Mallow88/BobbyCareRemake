<?php
require_once __DIR__ . '/../config/database.php';

// รับค่า id จาก URL
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ไม่พบรหัสแผนก");
}

// ดึงข้อมูลเดิม
$stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$id]);
$dept = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dept) {
    die("ไม่พบข้อมูลแผนก");
}

// อัพเดทข้อมูลเมื่อกดบันทึก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $warehouse_number = $_POST['warehouse_number'];
    $code_name = $_POST['code_name'];
    $department_code = $_POST['department_code'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $update = $conn->prepare("
        UPDATE departments 
        SET warehouse_number = ?, code_name = ?, department_code = ?, is_active = ?
        WHERE id = ?
    ");
    $update->execute([$warehouse_number, $code_name, $department_code, $is_active, $id]);

    header("Location: departments_list.php?msg=updated");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลแผนก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #121212;
            color: #fff;
        }
        h2 {
            color: #ffffff; /* หัวข้อสีขาว */
        }
        .card {
            background-color: #7c7c7cff;
            border: none;
        }
        .form-control, .form-check-input {
            background-color: #afafafff;
            border: 1px solid #444;
            color: #fff;
        }
        .form-control:focus {
            background-color: #333;
            border-color: #666;
            box-shadow: none;
            color: #fff;
        }
        .btn-primary {
            background-color: #1976d2;
            border: none;
        }
        .btn-primary:hover {
            background-color: #1565c0;
        }
        .btn-secondary {
            background-color: #424242;
            border: none;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #616161;
        }
        .btn-back {
            background-color: #424242;
            color: white;
            border: none;
        }
        .btn-back:hover {
            background-color: #616161;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="bi bi-pencil-square"></i> แก้ไขข้อมูลแผนก</h2>
        <button onclick="history.back()" class="btn btn-back">
            <i class="bi bi-arrow-left"></i> ย้อนกลับ
        </button>
    </div>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">หมายเลขคลัง</label>
            <input type="text" name="warehouse_number" class="form-control" 
                   value="<?= htmlspecialchars($dept['warehouse_number']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">รหัสย่อแผนก</label>
            <input type="text" name="code_name" class="form-control" 
                   value="<?= htmlspecialchars($dept['code_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">รหัส/ชื่อแผนกเต็ม</label>
            <input type="text" name="department_code" class="form-control" 
                   value="<?= htmlspecialchars($dept['department_code']) ?>">
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                   <?= $dept['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">ใช้งาน</label>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="departments_list.php" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
</body>
</html>
