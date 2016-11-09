<?php
  include_once(dirname(__FILE__) ."/edit_map.controller.php");

  $controller = new EditMapController();
  $vd = $controller->Execute();
  $map = $vd["Map"];
?>
<?php print '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title><?php print __("PAGE_TITLE")?> :: <?php print $vd["Title"]; ?></title>
  <link rel="icon" type="image/png" href="gfx/favicon.png" />
  <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>
  <script type="text/javascript" src="js/edit_map.js?v=<?php print DOMA_VERSION; ?>"></script>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
</head>

<body id="editMapBody">
<div id="wrapper">
<?php Helper::CreateTopbar() ?>
<div id="content">
<form class="wide" method="post" action="<?php print $_SERVER["PHP_SELF"]; ?>?<?php print Helper::CreateQuerystring(getCurrentUser(), isset($vd["MapID"]) ? $vd["MapID"] : null); ?>" enctype="multipart/form-data">

<h1><?php print $vd["Title"]; ?></h1>

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
<label for="categoryID"><?php print __("CATEGORY")?></label>
<select name="categoryID" class="tooltipControl">
<?php
  foreach($vd["Categories"] as $c)
  {
    print '<option value="'. $c->ID .'"'. ($c->ID == $map->CategoryID ? ' selected="selected"' : '') .'>'. $c->Name .'</option>';
  }
?>
</select>
<div class="tooltip hidden"><?php print __("CATEGORY_TOOLTIP")?></div>
</div>

<div class="container">
<label for="date"><?php print __("DATE")?></label>
<?php
  if(date("H:i:s", Helper::StringToTime($map->Date, true)) == "00:00:00")
  {
    $map->Date = date(__("DATE_FORMAT"), Helper::StringToTime($map->Date, true));
  }
  else
  {
    $map->Date = date(__("DATE_FORMAT") ." H:i:s", Helper::StringToTime($map->Date, true));
  }
?>
<input type="text" class="text tooltipControl" name="date" id="date" value="<?php print hsc($map->Date)?>" />
<div class="tooltip hidden"><?php print __("MAP_DATE_FORMAT")?></div>
</div>

<div class="container">
<label for="name"><?php print __("NAME")?></label>
<input type="text" class="text" name="name" id="name" value="<?php print hsc($map->Name)?>" />
</div>

<?php if(__("SHOW_MAP_AREA_NAME")) { ?>
<div class="container">
<label for="relayLeg"><?php print __("MAP_AREA_NAME")?></label>
<input type="text" class="text" name="mapName" id="mapName" value="<?php print hsc($map->MapName)?>" />
</div>
<?php } ?>

<?php if(__("SHOW_ORGANISER")) { ?>
<div class="container">
<label for="organiser"><?php print __("ORGANISER")?></label>
<input type="text" class="text" name="organiser" id="organiser" value="<?php print hsc($map->Organiser)?>" />
</div>
<?php } ?>

<?php if(__("SHOW_COUNTRY")) { ?>
<div class="container">
<label for="country"><?php print __("COUNTRY")?></label>
<input type="text" class="text" name="country" id="country" value="<?php print hsc($map->Country)?>" />
</div>
<?php } ?>

<?php if(__("SHOW_DISCIPLINE")) { ?>
<div class="container">
<label for="discipline"><?php print __("DISCIPLINE")?></label>
<input type="text" class="text" name="discipline" id="discipline" value="<?php print hsc($map->Discipline)?>" />
</div>
<?php } ?>

<?php if(__("SHOW_RELAY_LEG")) { ?>
<div class="container">
<label for="relayLeg"><?php print __("RELAY_LEG_LONG")?></label>
<input type="text" class="text" name="relayLeg" id="relayLeg" value="<?php print hsc($map->RelayLeg)?>" />
</div>
<?php } ?>

<?php if(__("SHOW_RESULT_LIST_URL")) { ?>
<div class="container">
<label for="relayLeg"><?php print __("LINK_TO_RESULT_LIST")?></label>
<input type="text" class="text" name="resultListUrl" id="resultListUrl" value="<?php print hsc($map->ResultListUrl)?>" />
</div>
<?php } ?>

<?php if(__("SHOW_COMMENT")) { ?>
<div class="container">
<label for="comment"><?php print __("COMMENT")?></label>
<textarea name="comment" id="comment" rows="5" cols="60"><?php print hsc($map->Comment)?></textarea>
</div>
<?php } ?>

<div class="container">
<label for="mapImage"><?php print __("MAP_IMAGE_FILE")?></label>
<input type="file" id="mapImage" name="mapImage" class="tooltipControl" />
<div class="tooltip hidden"><?php print __("MAP_INFO"); if(isset($vd["MapID"])) print " ". __("LEAVE_EMPTY_TO_KEEP_EXISTING_MAP"); ?></div>
</div>

<div class="container">
<label for="blankMapImage"><?php print __("BLANK_MAP_IMAGE_FILE")?></label>
<input type="file" id="blankMapImage" name="blankMapImage" class="tooltipControl" />
<div class="tooltip hidden"><?php print __("BLANK_MAP_INFO"); if(isset($vd["MapID"])) print " ". __("LEAVE_EMPTY_TO_KEEP_EXISTING_MAP"); ?></div>
</div>

<div class="container">
<label for="thumbnailImage"><?php print __("THUMBNAIL_IMAGE_FILE")?></label>
<input type="file" id="thumbnailImage" name="thumbnailImage" class="tooltipControl" />
<div class="tooltip hidden"><?php printf(__("THUMBNAIL_INFO"), THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT); ?></div>
</div>

<div class="container">
<?php
  $protectedUntil = $map->ProtectedUntil;
  $protectedUntilTime = $protectedUntil != null ? Helper::StringToTime($protectedUntil, true) : null;
  if($protectedUntilTime != null) $protectedUntil = date(__("DATETIME_FORMAT"), $protectedUntilTime);
?>
<label for="protectedUntil"><?php print __("PROTECTED_UNTIL")?></label>
<input type="text" id="protectedUntil" class="text tooltipControl" name="protectedUntil" value="<?php print hsc($protectedUntil); ?>" />
<div class="tooltip hidden"><?php print __("PROTECTED_UNTIL_INFO")?></div>
</div>

<div class="buttons">
<input type="submit" class="submit" name="save" id="save" value="<?php print __("SAVE")?>" />
<?php if($vd["ConfirmDeletionButtonVisible"]) { ?>
<input type="submit" class="submit" name="deleteConfirmed" id="delete" value="<?php print __("CONFIRM_DELETION")?>" />
<?php } else { ?>
<input type="submit" class="submit" name="delete" id="delete" value="<?php print __("DELETE")?>" />
<?php } ?>
<input type="submit" class="submit" name="cancel" id="cancel" value="<?php print __("CANCEL")?>" />
</div>

</form>
</div>
</div>
</body>
</html>