<?php
session_start();
require_once __DIR__ . '/../config/database.php'; // มีตัวแปร $pdo

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// เขียน query แบบ PDO ใช้ ? แทนพารามิเตอร์
$sql = "SELECT id, task_status, progress_percentage, started_at, completed_at 
        FROM tasks 
        WHERE developer_user_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

// ส่งพารามิเตอร์เข้า execute() เลย
$stmt->execute([$developer_id]);

// ดึงข้อมูล
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>รายการงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>รายการงานของฉัน</h2>

    <?php if (empty($tasks)): ?>
        <div class="alert alert-info">ยังไม่มีงานที่มอบหมายให้คุณ</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>รหัสงาน</th>
                    <th>สถานะ</th>
                    <th>ความคืบหน้า</th>
                    <th>เริ่มงาน</th>
                    <th>เสร็จงาน</th>
                    <th>ดูรายละเอียด</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['id']) ?></td>
                    <td><?= htmlspecialchars($task['task_status']) ?></td>
                    <td><?= htmlspecialchars($task['progress_percentage']) ?>%</td>
                    <td><?= $task['started_at'] ?? '-' ?></td>
                    <td><?= $task['completed_at'] ?? '-' ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm btn-view-timeline" data-task-id="<?= $task['id'] ?>">ดูไทม์ไลน์</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal และ jQuery, Bootstrap เหมือนเดิม -->

<!-- ... (โค้ด modal และสคริปต์ AJAX เหมือนตัวอย่างก่อนหน้า) -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
    $('.btn-view-timeline').on('click', function() {
        let taskId = $(this).data('task-id');
        $('#timelineContent').html('กำลังโหลด...');
        $('#timelineModal').modal('show');

        $.ajax({
            url: 'task_timeline.php',
            method: 'GET',
            data: { task_id: taskId },
            success: function(data) {
                $('#timelineContent').html(data);
            },
            error: function() {
                $('#timelineContent').html('<div class="alert alert-danger">โหลดข้อมูลไม่สำเร็จ</div>');
            }
        });
    });
});
</script>

<!-- Modal HTML (เหมือนเดิม) -->
<div class="modal fade" id="timelineModal" tabindex="-1" aria-labelledby="timelineModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="timelineModalLabel">ไทม์ไลน์งาน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="timelineContent" class="p-3">
            กำลังโหลด...
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
