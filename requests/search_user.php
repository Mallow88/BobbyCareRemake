<?php
require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, employee_id, name, lastname, department, position 
    FROM users 
    WHERE employee_id LIKE ? OR name LIKE ? OR lastname LIKE ?
    LIMIT 10
");
$search = "%$q%";
$stmt->execute([$search, $search, $search]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
