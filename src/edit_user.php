<?php
  include_once(dirname(__FILE__) ."/edit_user.controller.php");
  
  $controller = new EditUserController();
  $vd = $controller->Execute();
  
?>
<?php print '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title><?php print _SITE_TITLE ." :: ". $vd["Title"]; ?></title>
<link rel="icon" type="image/png" href="gfx/favicon.png" />
  <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>
  <script type="text/javascript" src="js/edit_user.js?v=<?php print DOMA_VERSION; ?>"></script>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
</head>

<body id="editUserBody">
<div id="wrapper">
<?php 
  if($vd["IsAdmin"] || $vd["IsNewUser"])
  {
    Helper::CreateUserListTopbar(); 
  }
  else
  {
    Helper::CreateTopbar(); 
  }

?>
<div id="content">
<form class="wide" method="post" action="<?php print $vd["FormActionURL"]; ?>">

<h1><?php print $vd["Title"]?></h1>

<p><?php print $vd["Info"]?></p>

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

<h2><?php print __("PERSONAL_DETAILS_TITLE")?></h2>

<p><?php print ($vd["IsAdmin"] ? __("ADMIN_PERSONAL_DETAILS_INFO") : __("PERSONAL_DETAILS_INFO"))?></p>

<div class="container">
<input type="hidden" name="id" value="<?php print $vd["User"]->ID?>" />
<label for="username"><?php print __("USERNAME")?></label>
<input type="text" class="text tooltipControl" name="username" value="<?php print hsc($vd["User"]->Username)?>" /> <span class="required">*</span>
<div class="tooltip hidden"><?php print ($vd["IsAdmin"] ? __("ADMIN_USERNAME_DESCRIPTION") : __("USERNAME_DESCRIPTION"))?></div>
</div>

<div class="container">
<label for="password"><?php print __("PASSWORD")?></label>
<input type="password" class="password tooltipControl" name="password" value="<?php if(isset($password)) print hsc($password); ?>" /><?php if($vd["IsNewUser"]) print ' <span class="required">*</span>'; ?>
<div class="tooltip hidden"><?php print ($vd["IsAdmin"] ? ($vd["User"]->ID ? __("ADMIN_PASSWORD_DESCRIPTION_EXISTING_USER") : __("ADMIN_PASSWORD_DESCRIPTION_NEW_USER")) : __("PASSWORD_DESCRIPTION"))?></div>
</div>

<div class="container">
<label for="firstName"><?php print __("FIRST_NAME")?></label>
<input type="text" class="text tooltipControl" name="firstName" value="<?php print hsc($vd["User"]->FirstName)?>" /> <span class="required">*</span>
<div class="tooltip hidden"><?php print ($vd["IsAdmin"] ? __("ADMIN_FIRST_NAME_DESCRIPTION") : __("FIRST_NAME_DESCRIPTION"))?></div>
</div>

<div class="container">
<label for="lastName"><?php print __("LAST_NAME")?></label>
<input type="text" class="text tooltipControl" name="lastName" value="<?php print hsc($vd["User"]->LastName)?>" /> <span class="required">*</span>
<div class="tooltip hidden"><?php print ($vd["IsAdmin"] ? __("ADMIN_LAST_NAME_DESCRIPTION") : __("LAST_NAME_DESCRIPTION"))?></div>
</div>

<div class="container">
<label for="email"><?php print __("EMAIL")?></label>
<input type="text" class="text tooltipControl" name="email" value="<?php print hsc($vd["User"]->Email)?>" /> <span class="required">*</span>
<div class="tooltip hidden"><?php print ($vd["IsAdmin"] ? __("ADMIN_EMAIL_DESCRIPTION") : __("EMAIL_DESCRIPTION"))?></div>
</div>

<?php if($vd["IsAdmin"] && $vd["IsNewUser"]) { ?>
<div class="container">
<label for="sendEmail"><?php print __("SEND_CONFIRMATION_EMAIL")?></label>
<input type="checkbox" class="checkbox" name="sendEmail" id="sendEmail"<?php if($vd["SendEmail"]) print ' checked="checked"'; ?> /> <?php print __("SEND_CONFIRMATION_EMAIL_DESCRIPTION")?>
</div>
<?php } ?>

<?php if($vd["IsAdmin"]) { ?>
<div class="container">
<label for="visible"><?php print __("VISIBLE")?></label>
<input type="checkbox" class="checkbox" name="visible" id="visible"<?php if($vd["User"]->Visible) print ' checked="checked"'; ?> /> <?php print __("ADMIN_VISIBLE_DESCRIPTION")?>
</div>

<?php } ?>

<h2><?php print __("MAP_CATEGORIES_TITLE")?></h2>

<p><?php print ($vd["IsAdmin"] ? __("ADMIN_MAP_CATEGORIES_INFO") : __("MAP_CATEGORIES_INFO"))?></p>

<div class="container">
<label for="categoryContainer"><?php print __("MAP_CATEGORIES")?></label>

<div id="categoryContainer">
  <div class="category">
    <span id="allCategoriesText"><?php print __("ALL_CATEGORIES"); ?></span>
    <input type="radio" class="radio" name="defaultCategory" id="categoryDefault_0" value="0"<?php if($vd["DefaultCategory"] == 0) print ' checked="checked"'; ?> />
    <label for="categoryDefault_0"><?php print __("DEFAULT_CATEGORY")?></label>        
<?php
  foreach($vd["CategoryData"] as $d)
  {
    ?>
      <div class="category">
        <input type="text" class="text" name="<?php print $d["nameId"]?>" id="<?php print $d["nameId"]?>" value="<?php print hsc($d["category"]->Name)?>" />
        <input type="radio" class="radio" name="defaultCategory" value="<?php print $d["defaultValue"]?>" id="<?php print $d["defaultId"]?>"<?php if($vd["DefaultCategory"] == $d["defaultValue"]) print ' checked="checked"'; ?> />
        <label for="<?php print $d["defaultId"]?>"><?php print __("DEFAULT_CATEGORY")?></label>        
        <input type="submit" class="submit" name="<?php print $d["deleteId"]?>" id="<?php print $d["deleteId"]?>" value="<?php print __("DELETE_CATEGORY")?>" />
      </div>
    <?php
  }
  
  ?>  <div class="clear">
    <input type="submit" class="submit" name="addCategory" id="addCategory" value="<?php print __("ADD_CATEGORY")?>" />
  </div>  

</div>
</div>

<h2><?php print __("TEXTS_AND_SETTINGS_TITLE")?></h2>

<p><?php print ($vd["IsAdmin"] ? __("ADMIN_TEXTS_AND_SETTINGS_INFO") : __("TEXTS_AND_SETTINGS_INFO"))?></p>

<?php
  foreach($vd["CustomizableSettings"]["settings"] as $key => $value)
  {
    $customizedValue = stripslashes($_POST["CV_$key"]);
    $description = __($vd["CustomizableSettings"]["descriptions"][$key]);
    ?>
      <div class="container">
      <label for="CV_<?php print $key?>"><?php print hsc($key)?></label>
      <?php if(strlen($value) < 60) { ?>
      <input type="text" class="text tooltipControl" name="CV_<?php print $key?>" value="<?php print $customizedValue?>" />
      <?php } else { ?>
      <textarea class="tooltipControl" name="CV_<?php print $key?>" rows="3" cols="50" style="height: <?php print (2+2*ceil(strlen($customizedValue) / 80))?>em;"><?php print hsc($customizedValue)?></textarea>
      <?php } ?>
      <div class="tooltip hidden"><?php print $description?></div>
      </div>
    <?php
  }
?>

<div class="buttons">
<input type="submit" class="submit" name="save" id="save" value="<?php print __("SAVE")?>" />
<?php if($vd["IsAdmin"] && !$vd["DeleteButtonClicked"] && !$vd["IsNewUser"]) { ?>
<input type="submit" class="submit" name="delete" id="delete" value="<?php print __("DELETE")?>" />
<?php } elseif($vd["IsAdmin"] && $vd["DeleteButtonClicked"]) { ?>
<input type="submit" class="submit" name="deleteConfirmed" id="deleteConfirmed" value="<?php print __("CONFIRM_DELETION")?>" />
<?php } ?>
<input type="submit" class="submit" name="cancel" id="cancel" value="<?php print __("CANCEL")?>" />
<input type="hidden" name="noOfCategoriesAdded" value="<?php print $vd["NoOfCategoriesAdded"]?>" />
</div>

</form>
</div>
</div>
</body>
</html>