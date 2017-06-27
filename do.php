<meta charset="utf-8">
<?php
// header('Content-Type:text/html;charset=utf-8');
require './wechat.class.php';
$wechat = new Wechat();
// $wechat->getAccessToken1();
// $wechat->getTicket(666);
// $wechat->getQRCode();
$wechat->createMenu();
// $wechat->showMenu();
// $wechat->delMenu();
// $wechat->getUserList();
// $wechat->getUserInfo();
// $wechat->uploadMedia();
// $wechat->getMedia();