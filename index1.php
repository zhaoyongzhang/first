<?php
require 'wechat.class.php';
$wechat = new Wechat();
//认证
//是否存在echostr
if($_GET["echostr"]){
  $wechat->valid();
}else{
  //消息管理
  $wechat->responseMsg();
  file_put_contents('test', '111');
}

