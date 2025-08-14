<?php
// เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config/database.php';

// ดึงข้อมูล departments
$stmt = $conn->prepare("SELECT * FROM departments ORDER BY warehouse_number, code_name");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจจับข้อความแจ้งเตือน
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ข้อมูลแผนก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #121212;
            color: #fff;
        }
        .card {
            background-color: #1e1e1e;
            border: none;
        }
        .table-dark th {
            background-color: #292929 !important;
            color: #f1f1f1;
        }
        .table-dark td {
            color: #e0e0e0;
        }
        .btn-warning {
            background-color: #ffb300;
            border: none;
        }
        .btn-warning:hover {
            background-color: #ffa000;
        }
        .btn-danger {
            background-color: #d32f2f;
            border: none;
        }
        .btn-danger:hover {
            background-color: #c62828;
        }
        .badge-success {
            background-color: #388e3c;
        }
        .badge-secondary {
            background-color: #757575;
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
        <h2 class="mb-0">
            <i class="bi bi-building"></i> ข้อมูลแผนก (Departments)
        </h2>
       <a href="dashboard.php" class="btn btn-secondary">
    <i class="bi bi-arrow-left"></i> ย้อนกลับ
</a>

    </div>

    <?php if ($msg === 'updated'): ?>
        <div class="alert alert-success">✅ แก้ไขข้อมูลเรียบร้อย</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-danger">🗑️ ลบข้อมูลเรียบร้อย</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>หมายเลขคลัง</th>
                        <th>รหัสย่อแผนก</th>
                        <th>รหัส/ชื่อแผนกเต็ม</th>
                        <th>สถานะ</th>
                        <th>วันที่สร้าง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">ไม่มีข้อมูลแผนก</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?= htmlspecialchars($dept['id']) ?></td>
                                <td><?= htmlspecialchars($dept['warehouse_number']) ?></td>
                                <td><?= htmlspecialchars($dept['code_name']) ?></td>
                                <td><?= htmlspecialchars($dept['department_code']) ?></td>
                                <td>
                                    <?php if ($dept['is_active'] == 1): ?>
                                        <span class="badge badge-success">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">ปิดใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($dept['created_at']) ?></td>
                                <td>
                                    <a href="edit_department.php?id=<?= $dept['id'] ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-square"></i> แก้ไข
                                    </a>
                                    <a href="delete_department.php?id=<?= $dept['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?')">
                                        <i class="bi bi-trash"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
