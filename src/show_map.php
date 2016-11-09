<?php
  include_once(dirname(__FILE__) ."/show_map.controller.php");
  include_once("./include/quickroute_jpeg_extension_data.php");
  
  $controller = new ShowMapController();
  $vd = $controller->Execute();
  $map = $vd["Map"];  
  $QR = $map->GetQuickRouteJpegExtensionData();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php print __("PAGE_TITLE")?> :: <?php print strip_tags($vd["Name"])?></title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />
  <link rel="icon" type="image/png" href="gfx/favicon.png" />
  <link rel="alternate" type="application/rss+xml" title="RSS" href="rss.php?<?php print Helper::CreateQuerystring(getCurrentUser())?>" />
  <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>  
  <script type="text/javascript" src="js/show_map.js?v=<?php print DOMA_VERSION; ?>"></script>
  <script type="text/javascript" src="js/jquery/jquery.timeago.js"></script>
  <?php 
    $lang = Session::GetLanguageCode();
    if($lang != "" && $lang != "en")
    {
      ?>
      <script type="text/javascript" src="js/jquery/jquery.timeago.<?php print $lang; ?>.js"></script>
      <?php
    }
  ?>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
  <?php if(isset($vd["OverviewMapData"])) { ?>
    <script src="http://maps.googleapis.com/maps/api/js?sensor=false&amp;language=<?php print $lang; ?>" type="text/javascript"></script>
    <script src="js/overview_map.js" type="text/javascript"></script>
    <script type="text/javascript">
      <!--
      $(function() { 
        var overviewMapData = <?php print json_encode($vd["OverviewMapData"]); ?>;        
        $("#overviewMap").overviewMap({ data: overviewMapData });
      });
      -->
    </script>
  <?php } ?> 
  <?php if(isset($vd["ProcessRerun"])) {?>
    <?php if($vd["RerunMaps"]!="") {?>
      <script type="text/javascript" src="js/rerun.js?v=<?php print DOMA_VERSION; ?>"></script>
    <?php } else { ?>
      <script type="text/javascript">
        $.get("ajax_server.php?action=saveLastRerunCheck");
      </script>
    <?php }?>
  <?php }?>
  

</head>
<body id="showMapBody">
<center>
<div id="top_menu">
<div id="wrapper" <?php if($vd["Map"]->IsGeocoded) {print 'style="float:left"';} else {print 'style="float:center"';}?>>
<?php Helper::CreateTopbar() ?>

<div id="navigation">
  <div class="left">
  <?php if(isset($vd["SecondMapImageName"])) {?>
    <a href="#" id="showSecondImage" title="<?php print __("TOGGLE_IMAGE_TOOLTIP")?>"><?php print __("SHOW_ROUTE_ON_MAP")?></a>
    <a href="#" id="hideSecondImage" title="<?php print __("TOGGLE_IMAGE_TOOLTIP")?>"><?php print __("HIDE_ROUTE_ON_MAP")?></a>
    <span class="separator">|</span>
  <?php }?>
  <?php if(isset($QR) && $QR->IsValid) { ?>
    <a id="showOverviewMap" href="#"><?php print __("SHOW_OVERVIEW_MAP"); ?></a>
    <a id="hideOverviewMap" href="#"><?php print __("HIDE_OVERVIEW_MAP"); ?></a>
    <span class="separator">|</span>
    <a href="export_kml.php?id=<?php print $map->ID; ?>&amp;format=kml" title="<?php print __("KMZ_TOOLTIP"); ?>"><?php print __("KMZ"); ?></a>
    <span class="separator">|</span>
  <?php } ?>
  <a href="<?php print $vd["BackUrl"]?>"><?php print __("BACK")?></a>
  </div>
  <div class="right">
  <?php if($vd["Previous"]) { ?><a href="show_map.php?<?php print Helper::CreateQuerystring(getCurrentUser(), $vd["Previous"]->ID)?>"><?php print "&lt;&lt; ". $vd["PreviousName"]; ?></a><?php } ?>
  <?php if($vd["Next"]) { ?><span class="separator">|</span><a href="show_map.php?<?php print Helper::CreateQuerystring(getCurrentUser(), $vd["Next"]->ID)?>"><?php print $vd["NextName"] ." &gt;&gt;"; ?></a>
  
  <?php } ?>
  </div>
  <div class="clear"></div>
</div>

<div id="content">
<form id="frm" method="post" action="<?php print $_SERVER['PHP_SELF']; ?>">
<?php if(isset($vd["ProcessRerun"]) && $vd["RerunMaps"]!="") {?>
  <input id="rerun_maps" type="hidden" value="<?php print $vd["RerunMaps"]; ?>" />
  <input id="base_url" type="hidden" value="<?php print BASE_URL; ?>" />
  <input id="rerun_apikey" type="hidden" value="<?php print RERUN_APIKEY; ?>" />
  <input id="rerun_apiurl" type="hidden" value="<?php print RERUN_APIURL; ?>" />  
  <input id="total_rerun_maps" type="hidden" value="<?php print $vd["TotalRerunMaps"]; ?>" />
  <input id="processed_rerun_maps" type="hidden" value="0" />
<?php }?>
<div id="mapInfo">
<div id="name"><?php print $vd["Name"]?></div>

<div id="zoomButtonDiv">
  <div id="zoomIn" class="zoomButton"></div>
  <div id="zoomOut" class="zoomButton"></div>
</div>

<div id="propertyContainer">
<?php
  print '<div class="property"><span class="caption">'. __("CATEGORY") .":</span> ". $map->GetCategory()->Name .'</div>'; 
  if(__("SHOW_MAP_AREA_NAME") && $map->MapName != "") print '<div class="property"><span class="caption">'. __("MAP_AREA_NAME") .':</span> '. $map->MapName .'</div>';
  if(__("SHOW_ORGANISER") && $map->Organiser != "") print '<div class="property"><span class="caption">'. __("ORGANISER") .':</span> '. $map->Organiser .'</div>';
  if(__("SHOW_COUNTRY") && $map->Country != "") print '<div class="property"><span class="caption">'. __("COUNTRY") .':</span> '. $map->Country .'</div>';
  if(__("SHOW_DISCIPLINE") && $map->Discipline != "") print '<div class="property"><span class="caption">'. __("DISCIPLINE") .':</span> '. $map->Discipline .'</div>';
  if(__("SHOW_RELAY_LEG") && $map->RelayLeg != "") print '<div class="property"><span class="caption">'. __("RELAY_LEG") .':</span> '. $map->RelayLeg .'</div>';
  if(__("SHOW_RESULT_LIST_URL") && $map->ResultListUrl != "") print '<div class="property"><span class="caption"><a href="'. hsc($map->CreateResultListUrl()) .'" target="_blank">'. __("RESULTS") .'</a></span></div>';

if(isset($QR) && $QR->IsValid)
{
	$waypoints = $QR->Sessions[0]->Route->Segments[0]->Waypoints;
	$c1 = 0;
	$c2 = 0;
	$max1 = 0;
	$val = count($waypoints);
	for ($i = 0; $i < $val; $i++) {
		$c1 += $waypoints[$i]->HeartRate;
		$c2 += 1;
		if ($waypoints[$i]->HeartRate > $max1) 
		{
			$max1 = $waypoints[$i]->HeartRate;
		}
	}
	
  if((__("SHOW_DISTANCE"))||(__("SHOW_ELAPSEDTIME"))) 
	{
		if(__("SHOW_DISTANCE") && $map->Distance != "") print '<div class="property"><span class="caption">'. __("DISTANCE") .':</span> '. round(($map->Distance)/1000,2) .' km</div>';
		if(__("SHOW_ELAPSEDTIME") && $map->ElapsedTime != "") print '<div class="property"><span class="caption">'. __("ELAPSEDTIME") .':</span> '. Helper::ConvertToTime($map->ElapsedTime,"MM:SS").'</div>';
	}
	
  if (($c1 != 0)&&((__("SHOW_MAXHR"))||(__("SHOW_AVGHR")))) 
  {
		if(__("SHOW_AVGHR")) print '<div class="property"><span class="caption">'. __("AVGHR") .':</span> '. round($c1/$c2,0).'</div>';
		if(__("SHOW_MAXHR")) print '<div class="property"><span class="caption">'. __("MAXHR") .':</span> '. round($max1,0).'</div>';
	}
    if($map->RerunID && $map->RerunID != 0 && USE_3DRERUN == "1") print '<div class="property"><a href="http://3drerun.worldofo.com/?id='.$map->RerunID.'&type=info" target="_blank">'. __("3DRERUN") .'</a></div>';

}
?>
</div>
<?php
  if(__("SHOW_COMMENT") && $map->Comment != "") print '<div id="comment">'. nl2br($map->Comment) .'</div>';

  if(__("SHOW_VISITOR_COMMENTS"))
  {
?>
<div class="clear"></div>
<a id="showPostedComments"<?php if($vd["ShowComments"]) print ' class="hidden"'; ?> href="#"><?php print __("SHOW_COMMENTS"); ?></a>
<a id="hidePostedComments"<?php if(!$vd["ShowComments"]) print ' class="hidden"'; ?> href="#"><?php print __("HIDE_COMMENTS"); ?></a>
(<span id="comments_count"><?php print count($vd["Comments"]); ?></span>)
</div>

<div id="postedComments"<?php if(!$vd["ShowComments"]) print ' class="hidden"'; ?>">
  <?php 
    foreach($vd["Comments"] as $comment) 
    {
      include(dirname(__FILE__) ."/show_comment.php");
    }
  ?>
</div>
  
<div class="commentBox<?php if(!$vd["ShowComments"]) print " hidden"; ?>" id="commentBox">
  <div id="commentBoxHeader"><?php print __("POST_COMMENTS") ?></a></div>
  <div id="userDetails">
    <input type="hidden" id="map_user" value="<?php print getCurrentUser()->Username ?>">
    <label for="user_name"><?php print __("NAME") ?>:</label>
    <input type="text" id="user_name"<?php if(Helper::IsLoggedInUser()) print " value='" . hsc(Helper::GetLoggedInUser()->FirstName. " " .Helper::GetLoggedInUser()->LastName) ."'"; ?> /> 
    <label id="userEmailLabel" for="user_email"><?php print __("EMAIL") ?>:</label>
    <input type="text" id="user_email"<?php if(Helper::IsLoggedInUser()) print "value='". hsc(Helper::GetLoggedInUser()->Email) ."'"; ?> />
  </div>
  <textarea id="commentMark" name="commentMark"></textarea>
  <a id="submitComment" href="#" class="small button comment"><?php print __("SAVE") ?></a>
  <input type="hidden" id="missingCommentText" value="<?php print hsc(__("MISSING_COMMENT")); ?>"/>
  <input type="hidden" id="invalidEmailText" value="<?php print hsc(__("INVALID_EMAIL")); ?>"/>
  <input type="hidden" id="commentDeleteConfirmationText" value="<?php print hsc(__("COMMENT_DELETE_CONFIRMATION")); ?>"/>
  </div>

<?php 
}
?>



<div class="clear"></div>

</form>
</div>
</div>
<?php
if($map->IsGeocoded)
{
  $coordinates = $map->MapCenterLatitude .",". $map->MapCenterLongitude;
  print '<input id="gmap_coordinates" type="hidden" value="'.$coordinates.'" />';
  print '<input id="gmap_url" type="hidden" value="'.$vd["GoogleMapsUrl"].'" />';
  print '<input id="gmap_lang" type="hidden" value="'.Session::GetLanguageCode().'" />';
  print '<div id="gmap">';
  print '</div>';
}
?>
</div>
<div class="clear">&nbsp;</div>

<div id="overviewMapContainer"></div>

<div>
  <img id="mapImage" src="<?php print $vd["FirstMapImageName"]; ?>" alt="<?php print hsc(strip_tags($vd["Name"]))?>"<?php if(isset($vd["SecondMapImageName"])) print ' title="'. __("TOGGLE_IMAGE_CLICK") .'" class="toggleable"'; ?>/>
  <?php if(isset($vd["SecondMapImageName"])) { ?>
  <img id="hiddenMapImage" src="<?php print $vd["SecondMapImageName"]; ?>" alt="<?php print hsc(strip_tags($vd["Name"]))?>" <?php if($vd["SecondMapImageName"]) {?>title="<?php print __("TOGGLE_IMAGE_CLICK")?>"<?php }?>/>
  <?php } ?>
  <input type="hidden" id="id" value="<?php print $map->ID; ?>" />
  <input type="hidden" id="imageWidth" value="<?php print $vd["ImageWidth"] ?>" />
  <input type="hidden" id="imageHeight" value="<?php print $vd["ImageHeight"] ?>" />
</div>
</center>
<?php Helper::GoogleAnalytics() ?>
</body>
</html>