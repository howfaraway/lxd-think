<!doctype html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-capable" content="yes">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title></title>
{css href="__RESOURCE__admin/css/index.css" /}
</head>
<body>
<div class="msgpage">
  <div class="msgbox">
    <div class="pic"></div>
    <div class="msg">
        <div class="con">
          <?php echo(strip_tags($msg));?>
        </div>
        <?php if (isset($url)){ ?>
        <div class="scon">页面如不能自动跳转，选择手动操作...</div>
        <div class="button">
          <?php
          if (is_array($url)){
              foreach($url as $k => $v){
          ?>
          <a href="<?php echo $v['url'];?>" class="ap-btn"><?php echo $v['msg'];?></a>
              <?php } ?>
          <script type="text/javascript"> window.setTimeout("javascript:location.href='<?php echo $url[0]['url'];?>'", <?php echo $wait * 1000;?>); </script>
          <?php } else { if ($url != ''){ ?>
          <a href="<?php echo $url;?>" class="ap-btn">返回上一页</a>
          <script type="text/javascript"> window.setTimeout("javascript:location.href='<?php echo $url;?>'", <?php echo $wait * 1000;?>); </script>
          <?php } else { ?>
          <a href="javascript:history.back()" class="ap-btn">返回上一页</a>
          <script type="text/javascript"> window.setTimeout("javascript:history.back()", <?php echo $wait * 1000;?>); </script>
          <?php } } ?>
        </div>
        <?php } ?>
    </div>
    <div class="powerby"></div>
  </div>
</div>
</body>
</html>
