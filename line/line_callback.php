<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// แก้ให้ตรงกับ LINE Channel ของคุณ
$client_id = '2006057645';
$client_secret = '685fc53d7769bac865285d91f7b467f3';
$redirect_uri = 'http://localhost/BobbyCareRemake/line/line_callback.php';

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
$_SESSION['line_id'] = $profile['userId'];
$_SESSION['display_name'] = $profile['displayName'];
$_SESSION['picture_url'] = $profile['pictureUrl']; // ถ้ามี 



if (!$line_id) {
    die("ไม่สามารถดึงข้อมูล LINE ID ได้");
}

// 3. ตรวจสอบว่าผู้ใช้นี้มีอยู่ในระบบหรือยัง
$stmt = $conn->prepare("SELECT * FROM users WHERE line_id = ?");
$stmt->execute([$line_id]);
$user = $stmt->fetch();

if ($user) {
    // ถ้ามีแล้ว → login เข้า session และไปหน้าเฉพาะตาม role
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role']; // ต้องมี role จากฐานข้อมูล

$role = trim(strtolower($user['role']));

switch ($role) {
    case 'assignor':
        header("Location: ../assignor/index2.php");
        break;
    case 'divmgr':
        header("Location: ../div_mgr/index2.php");
        break;
    case 'gmapprover': 
        header("Location: ../gm/gmindex2.php");
        break;
    case 'seniorgm': 
        header("Location: ../seniorgm/seniorindex2.php");
        break;
    case 'developer': 
        header("Location: ../developer/dev_index.php");
        break;
    case 'userservice': 
        header("Location: ../userservice/index.php");
        break;
    default:
        header("Location: ../dashboard2.php");
        break;
}




    exit();
} else {
    // ถ้ายังไม่มี → ส่งไป register เพื่อกรอกชื่อ-นามสกุล
    $_SESSION['line_id'] = $line_id;
    $_SESSION['name'] = $name;
    header("Location: ../register/index.php");
    exit();
}

?>
