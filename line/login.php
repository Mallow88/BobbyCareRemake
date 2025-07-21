<?php
// login.php → สร้าง URL เพื่อส่งผู้ใช้ไป login กับ LINE
$client_id = '2006057645';
$redirect_uri = urlencode('http://localhost/BobbyCareRemake/line/line_callback.php');
$state = bin2hex(random_bytes(16)); // ป้องกัน CSRF
$scope = 'profile openid email';

$line_login_url = "https://access.line.me/oauth2/v2.1/authorize"
    . "?response_type=code"
    . "&client_id={$client_id}"
    . "&redirect_uri={$redirect_uri}"
    . "&state={$state}"
    . "&scope={$scope}";

header("Location: $line_login_url");
exit();
