<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

$log_id = $_GET['id'] ?? null;
if (!$log_id) {
    echo "ไม่พบข้อมูลการอนุมัติ";
    exit();
}

// ดึงข้อมูล approval log
$stmt = $conn->prepare("
    SELECT al.*, sr.title, sr.description, u.name AS developer_name, u.lastname AS developer_lastname
    FROM approval_logs al
    JOIN service_requests sr ON al.service_request_id = sr.id
    LEFT JOIN users u ON al.assigned_to_user_id = u.id
    WHERE al.id = ?
");
$stmt->execute([$log_id]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) {
    echo "ไม่พบข้อมูลที่ต้องการแก้ไข";
    exit();
}

// ดึง developer ทั้งหมด
$dev_stmt = $conn->query("SELECT id, name, lastname FROM users WHERE role = 'developer'");
$developers = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ามีการ submit ฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'] ?? '';
    $developer_id = $_POST['developer_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');

    if (!in_array($new_status, ['approved', 'rejected'])) {
        $error = "สถานะไม่ถูกต้อง";
    } elseif ($new_status === 'approved' && !$developer_id) {
        $error = "กรุณาเลือกผู้พัฒนาเมื่ออนุมัติ";
    } elseif ($new_status === 'rejected' && $reason === '') {
        $error = "กรุณาระบุเหตุผลเมื่อไม่อนุมัติ";
    } else {
        $stmt = $conn->prepare("UPDATE approval_logs 
                                SET status = ?, assigned_to_user_id = ?, reason = ?
                                WHERE id = ?");
        $stmt->execute([
            $new_status,
            $new_status === 'approved' ? $developer_id : null,
            $new_status === 'rejected' ? $reason : null,
            $log_id
        ]);
        header("Location: approval.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>✏️ แก้ไขผลการอนุมัติ</title>
</head>
<body>
    <h2>✏️ แก้ไขผลการอนุมัติคำขอบริการ</h2>
    <p><a href="approval.php">← กลับ</a></p>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <p><strong>หัวข้อ:</strong> <?= htmlspecialchars($log['title']) ?></p>
    <p><strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($log['description'])) ?></p>

    <form method="post">
        <p>
            <label>สถานะ:</label><br>
            <select name="status" required>
                <option value="approved" <?= $log['status'] === 'approved' ? 'selected' : '' ?>>✅ อนุมัติ</option>
                <option value="rejected" <?= $log['status'] === 'rejected' ? 'selected' : '' ?>>❌ ไม่อนุมัติ</option>
            </select>
        </p>

        <div id="developer-section" style="<?= $log['status'] === 'approved' ? '' : 'display:none;' ?>">
            <label>เลือกผู้พัฒนา:</label><br>
            <select name="developer_id">
                <option value="">-- เลือก --</option>
                <?php foreach ($developers as $dev): ?>
                    <option value="<?= $dev['id'] ?>" <?= $log['assigned_to_user_id'] == $dev['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dev['name'] . ' ' . $dev['lastname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="reason-section" style="<?= $log['status'] === 'rejected' ? '' : 'display:none;' ?>">
            <label>เหตุผล:</label><br>
            <textarea name="reason" rows="4" cols="50"><?= htmlspecialchars($log['reason']) ?></textarea>
        </div>

        <br>
        <button type="submit">💾 บันทึกการแก้ไข</button>
    </form>

    <script>
        const statusSelect = document.querySelector("select[name='status']");
        const devSection = document.getElementById("developer-section");
        const reasonSection = document.getElementById("reason-section");

        statusSelect.addEventListener("change", () => {
            if (statusSelect.value === 'approved') {
                devSection.style.display = '';
                reasonSection.style.display = 'none';
            } else {
                devSection.style.display = 'none';
                reasonSection.style.display = '';
            }
        });
    </script>
</body>
</html>
