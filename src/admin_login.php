<?php
  include_once(dirname(__FILE__) ."/admin_login.controller.php");
  
  $controller = new AdminLoginController();
  $vd = $controller->Execute();
?>
<?php print '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title><?php print (_SITE_TITLE ." :: ". __("ADMIN_LOGIN"))?></title>
  <link rel="icon" type="image/png" href="gfx/favicon.png" />
  <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
</head>

<body id="adminLoginBody">
<div id="wrapper">
<?php Helper::CreateUserListTopbar(); ?>
<div id="content">
<form class="wide" method="post" action="<?php print $_SERVER["PHP_SELF"]?>">

<h1><?php print __("ADMIN_LOGIN")?></h1>

<p><?php print __("ADMIN_LOGIN_INFO")?></p>

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

<div class="container">
<label for="username"><?php print __("USERNAME")?></label>
<input type="text" class="text" id="username" name="username" value="" />
</div>

<div class="container">
<label for="password"><?php print __("PASSWORD")?></label>
<input type="password" class="password" name="password" id="password" value="" />
</div>

<div class="buttons">
<input type="submit" class="submit" name="login" value="<?php print __("LOGIN")?>" />
<input type="submit" class="submit" name="cancel" value="<?php print __("CANCEL")?>" />
</div>

</form>
</div>
</div>
</body>
</html>