<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h1>สวัสดีคุณแอดมิน <?php echo $_SESSION['admin_name']; ?></h1>
<ul>
    <li><a href="manage_users.php">จัดการผู้ใช้</a></li>
    <li><a href="logs.php">ดูประวัติการใช้งาน</a></li>
    <li><a href="logout.php">ออกจากระบบ</a></li>
</ul>
