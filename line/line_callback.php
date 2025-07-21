<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// แก้ให้ตรงกับ LINE Channel ของคุณ
$client_id = 'YOUR_LINE_CHANNEL_ID';
$client_secret = 'YOUR_LINE_CHANNEL_SECRET';
$redirect_uri = 'http://localhost/bobbycare_remake/line/line_callback.php';

// รับ code ที่ LINE ส่งกลับมา
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (!$code) {
    die("เกิดข้อผิดพลาด: ไม่พบ code จาก LINE");
}

// 1. แลก code เป็น access token
$token_url = 'https://api.line.me/oauth2/v2.1/token';

$data = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded",
        'content' => http_build_query($data),
    ],
];

$context = stream_context_create($options);
$response = file_get_contents($token_url, false, $context);
$result = json_decode($response, true);

$access_token = $result['access_token'] ?? null;

if (!$access_token) {
    die("ไม่สามารถรับ access token ได้");
}

// 2. ดึง profile ผู้ใช้
$profile_url = 'https://api.line.me/v2/profile';
$headers = [
    "Authorization: Bearer $access_token"
];

$opts = [
    "http" => [
        "method" => "GET",
        "header" => implode("\r\n", $headers),
    ],
];

$context = stream_context_create($opts);
$profile = json_decode(file_get_contents($profile_url, false, $context), true);

$line_id = $profile['userId'] ?? null;
$name = $profile['displayName'] ?? '';

if (!$line_id) {
    die("ไม่สามารถดึงข้อมูล LINE ID ได้");
}

// 3. ตรวจสอบว่าผู้ใช้นี้มีอยู่ในระบบหรือยัง
$stmt = $conn->prepare("SELECT * FROM users WHERE line_id = ?");
$stmt->execute([$line_id]);
$user = $stmt->fetch();

if ($user) {
    // ถ้ามีแล้ว → login เข้า session และไปหน้า dashboard
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    header("Location: ../dashboard.php");
    exit();
} else {
    // ถ้ายังไม่มี → ส่งไป register เพื่อกรอกชื่อ-นามสกุล
    $_SESSION['line_id'] = $line_id;
    $_SESSION['name'] = $name;
    header("Location: ../register/index.php");
    exit();
}
?>
