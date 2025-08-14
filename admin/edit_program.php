<?php
require_once __DIR__ . '/../config/database.php';

// รับค่า id จาก URL
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ไม่พบรหัสโปรแกรม");
}

// ดึงข้อมูลเดิม
$stmt = $conn->prepare("SELECT * FROM programs WHERE id = ?");
$stmt->execute([$id]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    die("ไม่พบข้อมูลโปรแกรม");
}

// อัพเดทข้อมูลเมื่อกดบันทึก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $update = $conn->prepare("
        UPDATE programs 
        SET name = ?, is_active = ?
        WHERE id = ?
    ");
    $update->execute([$name, $is_active, $id]);

    header("Location: programs_list.php?msg=updated");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลโปรแกรม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #121212;
            color: #fff;
        }
        h2 {
            color: #ffffff;
        }
        .card {
            background-color: #9c9c9cff;
            border: none;
        }
        .form-control, .form-check-input {
            background-color: #c4c4c4ff;
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
        <h2 class="mb-0"><i class="bi bi-pencil-square"></i> แก้ไขข้อมูลโปรแกรม</h2>
        <button onclick="history.back()" class="btn btn-back">
            <i class="bi bi-arrow-left"></i> ย้อนกลับ
        </button>
    </div>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">ชื่อโปรแกรม</label>
            <input type="text" name="name" class="form-control" 
                   value="<?= htmlspecialchars($program['name']) ?>" required>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                   <?= $program['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">ใช้งาน</label>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="programs_list.php" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
</body>
</html>
