<?php
  include_once(dirname(__FILE__) ."/send_new_password.controller.php");

  $controller = new SendNewPasswordController();
  $vd = $controller->Execute();
?>
<?php print '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title><?php print (__("PAGE_TITLE") ." :: ". __("SEND_NEW_PASSWORD_TITLE"))?></title>
  <link rel="icon" type="image/png" href="gfx/favicon.png" />
  <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
</head>

<body id="sendNewPasswordBody">
<div id="wrapper">
<?php Helper::CreateTopbar() ?>
<div id="content">
<form class="wide" method="post" action="<?php print $_SERVER["PHP_SELF"]?>?<?php print Helper::CreateQuerystring(getCurrentUser())?>">

<h1><?php print __("SEND_NEW_PASSWORD_TITLE")?></h1>

<?php if(count($vd["Errors"]) > 0) { ?>
<ul class="error">
<?php
  foreach($vd["Errors"] as $e)
  {
    print "<li>$e</li>";
  }
?>
</ul>
<?php } ?>

<p><?php print sprintf(__("SEND_NEW_PASSWORD_INFO"), getCurrentUser()->Email)?></p>

<div class="container">
<input type="submit" class="submit" name="send" value="<?php print __("SEND_NEW_PASSWORD")?>" />
<input type="submit" class="submit" name="cancel" value="<?php print __("CANCEL")?>" />
</div>

</form>
</div>
</div>
</body>
</html>