<?php
require_once __DIR__ . '/../config/database.php';

$keyword = $_GET['q'] ?? '';
$data = [];

if ($keyword) {
    $stmt = $conn->prepare("
        SELECT * FROM usertemplate 
        WHERE name LIKE ? OR lastname LIKE ? OR employee_id LIKE ? 
        LIMIT 1
    ");
    $like = "%$keyword%";
    $stmt->execute([$like, $like, $like]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($data);
