<?php
$client_id = "YOUR_LINE_CHANNEL_ID";
$redirect_uri = urlencode("http://localhost/yourproject/line_callback.php");
$state = uniqid(); // กัน CSRF
$scope = "profile openid email";

$line_login_url = "https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&state={$state}&scope={$scope}";
?>

<a href="<?= $line_login_url ?>">
  <img src="https://scdn.line-apps.com/n/line_login/btn/en.png" alt="Login with LINE">
</a>
