<?php
require_once __DIR__ . '/../config/database.php';

// ดึงข้อมูล programs
$stmt = $conn->prepare("SELECT * FROM programs ORDER BY id ASC");
$stmt->execute();
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจจับข้อความแจ้งเตือน
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ข้อมูลโปรแกรม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #121212;
            color: #fff;
        }
        h2 {
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
        <h2 class="mb-0"><i class="bi bi-grid"></i> ข้อมูลโปรแกรม (Programs)</h2>
        <button onclick="history.back()" class="btn btn-back">
            <i class="bi bi-arrow-left"></i> ย้อนกลับ
        </button>
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
                        <th>ชื่อโปรแกรม</th>
                        <th>สถานะ</th>
                        <th>วันที่สร้าง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programs)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">ไม่มีข้อมูลโปรแกรม</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($programs as $prog): ?>
                            <tr>
                                <td><?= htmlspecialchars($prog['id']) ?></td>
                                <td><?= htmlspecialchars($prog['name']) ?></td>
                                <td>
                                    <?php if ($prog['is_active'] == 1): ?>
                                        <span class="badge badge-success">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">ปิดใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($prog['created_at']) ?></td>
                                <td>
                                    <a href="edit_program.php?id=<?= $prog['id'] ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-square"></i> แก้ไข
                                    </a>
                                    <a href="delete_program.php?id=<?= $prog['id'] ?>" 
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
