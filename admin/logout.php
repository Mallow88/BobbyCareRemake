<?php
session_start();
session_destroy();
header("Location: ../index.php"); // ← ย้อนออกจากโฟลเดอร์ admin
exit();
