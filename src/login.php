<?php
  include_once(dirname(__FILE__) ."/login.controller.php");

  $controller = new LoginController();
  $vd = $controller->Execute();
?>
<?php print '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title><?php print (__("PAGE_TITLE") ." :: ". __("LOGIN"))?></title>
  <link rel="icon" type="image/png" href="gfx/favicon.png" />
  <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
</head>

<body id="loginBody">
<div id="wrapper">
<?php Helper::CreateTopbar() ?>
<div id="content">
<form class="wide" method="post" action="<?php print $_SERVER["PHP_SELF"]?>?<?php print Helper::CreateQuerystring(getCurrentUser())?>">

<h1><?php print __("LOGIN")?></h1>

<?php if(isset($_GET["action"]) && $_GET["action"] == "newPasswordSent") print '<p>'. sprintf(__("NEW_PASSWORD_SENT"), getCurrentUser()->Email) .'</p>'; ?>

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
<input type="text" class="text" name="username" id="username" value="" />
</div>

<div class="container">
<label for="password"><?php print __("PASSWORD")?></label>
<input type="password" class="password" name="password" id="password" value="" />
</div>

<div class="buttons">
<input type="submit" class="submit" name="login" value="<?php print __("LOGIN")?>" />
<?php if(getCurrentUser()->Email) { ?> <input type="submit" class="submit" name="forgotPassword" value="<?php print __("FORGOT_PASSWORD")?>" /> <?php } ?>
<input type="submit" class="submit" name="cancel" value="<?php print __("CANCEL")?>" />
</div>

</form>
</div>
</div>
</body>
</html>
