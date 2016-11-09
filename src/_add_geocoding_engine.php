<?php
  include_once(dirname(__FILE__) ."/config.php");
  include_once(dirname(__FILE__) ."/include/definitions.php");

  if(($_POST["id"])&&(is_numeric($_POST["id"])))
  {
    $map = new Map();
    $map->Load($_POST["id"]);
    if(!$map->IsGeocoded)
    {
      $map->AddGeocoding();
      if($map->IsGeocoded)
      {
        $map->Save();
        Helper::WriteToLog("Added geocoding data to database for map with id ". $_POST["id"] .".");
        print "1";
      }
      else
      {
        Helper::WriteToLog("Failed to add geocoding data to database for map with id ". $_POST["id"] .". Probably no QuickRoute jpeg file.");
        print "2";
      }
    }
    else
    {
      print "3";
    }
  }
?>