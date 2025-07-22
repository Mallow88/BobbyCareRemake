<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer') {
    header("Location: ../index.php");
    exit();
}

$developer_id = $_SESSION['user_id'];

// ดึงงานทั้งหมดของผู้พัฒนาคนนี้
$stmt = $conn->prepare("
    SELECT t.*, sr.title, sr.description 
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    WHERE t.developer_user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$developer_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>📋 งานของฉัน - To Do List</title>
</head>
<body>
    <h1>🧑‍💻 To Do List ของฉัน</h1>
    <p><a href="dev_index.php">← กลับหน้าแรก</a></p>

    <?php
    $statuses = [
        'pending' => '📥 ยังไม่เริ่ม',
        'received' => '📦 รับงานแล้ว',
        'in_progress' => '🚧 กำลังดำเนินการ',
        'on_hold' => '⏸ พัก',
        'completed' => '✅ เสร็จสิ้น',
        'rejected' => '❌ ยกเลิก'
    ];

    foreach ($statuses as $status_key => $status_label):
    ?>
        <h2><?= $status_label ?></h2>
        <ul>
        <?php
        $has_item = false;
        foreach ($tasks as $task):
            if ($task['task_status'] === $status_key):
                $has_item = true;
        ?>
            <li>
                <strong><?= htmlspecialchars($task['title']) ?></strong><br>
                <?= nl2br(htmlspecialchars($task['description'])) ?><br>
                <small>สร้างเมื่อ: <?= $task['created_at'] ?></small>

                <?php if ($status_key !== 'completed' && $status_key !== 'rejected'): ?>
                    <form method="post" action="update_task_status.php" style="margin-top:5px;">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <select name="new_status">
                            <?php
                            foreach ($statuses as $key => $label) {
                                if ($key !== $status_key) {
                                    echo "<option value=\"$key\">$label</option>";
                                }
                            }
                            ?>
                        </select>
                        <button type="submit">อัปเดต</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php
            endif;
        endforeach;

        if (!$has_item) echo "<li>— ไม่มีรายการ —</li>";
        ?>
        </ul>
    <?php endforeach; ?>
</body>
</html>
