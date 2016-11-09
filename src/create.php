<?php
  include_once(dirname(__FILE__) ."/create.controller.php");

  $controller = new CreateController();
  $vd = $controller->Execute();  
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title><?php print _SITE_TITLE?></title>
  <link rel="icon" type="image/png" href="gfx/favicon.png" />
  <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
</head>

<body id="createBody">
<div id="wrapper">
<div id="content">
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>">

<?php if(count($vd["Errors"]) == 0) { ?>
<h1><?php print __("SITE_SUCCESSFULLY_CREATED_TITLE")?></h1>
<p><?php print __("SITE_SUCCESSFULLY_CREATED_MESSAGE")?></p>
<p><a href="users.php"><?php print __("GOTO_START_PAGE")?></a></p>

<?php } else { ?>
<h1><?php print __("ERRORS_WHEN_CREATING_SITE_TITLE")?></h1>
<p><?php print __("ERRORS_WHEN_CREATING_SITE_MESSAGE")?></p>

<ul class="error">
<?php
  foreach($vd["Errors"] as $e)
  {
    print "<li>$e</li>";
  }
?>
</ul>

<p><a href="create.php"><?php print __("ERRORS_TRY_AGAIN")?></a></p>
<?php } ?>

</form>
</div>
</div>
</body>

</html>