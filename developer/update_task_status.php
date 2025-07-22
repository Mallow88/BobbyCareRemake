<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['new_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE tasks SET task_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $task_id]);
}

header("Location: subtasks.php");
exit();
