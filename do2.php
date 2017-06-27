<meta charset="utf-8">
<?php
// header('Content-Type:text/html;charset=utf-8');
require './wechat.class.php';
$wechat = new Wechat();
$wechat->getUserInfo($_GET['openid']);