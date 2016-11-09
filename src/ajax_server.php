<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  include_once(dirname(__FILE__) ."/include/json.php");

  switch($_GET["action"])
  {
    case "getMapCornerPositionsAndRouteCoordinates":
      $id = $_GET["id"];
      $r = getMapCornerPositionsAndRouteCoordinates($id);
      print json_encode($r);
      break;
    case "saveRerunID":
      if(USE_3DRERUN == "1") 
      {
        if(isset($_GET["mapid"])&&is_numeric($_GET["mapid"]))
        {
          $mapid = $_GET["mapid"];
          $map = new Map();
          $map->Load($mapid);
          if($map->ID!=0)
          {
            if($map->RerunID==0 || is_null($map->RerunID))
            {
              if(isset($_GET["fail"])&&$_GET["fail"]==1)
              {
                if($map->IsGeocoded)
                {
                  $map->RerunTries = $map->RerunTries + 1;
                }
                else
                {
                  $map->RerunTries = RERUN_MAX_TRIES;
                }
                $map->Save();
              }
              else
              {
                if(isset($_GET["rerunid"])&&is_numeric($_GET["rerunid"]))
                {
                  $rerunid = $_GET["rerunid"];
                  $map->RerunID = $rerunid;
                  if($rerunid==0)
                  {
                    $map->RerunTries = RERUN_MAX_TRIES; //map is not geocoded - avoid to send request again
                  }
                  else
                  {
                    $map->RerunTries = 0;
                  }
                  $map->Save();
                }
              }
            }
          }
        }
      }
      break;
    case "saveLastRerunCheck":
      if(USE_3DRERUN == "1" && DataAccess::GetSetting("LAST_WORLDOFO_CHECK_DOMA_TIME", "0")+RERUN_FREQUENCY*3600<time())
      {
        DataAccess::SetSetting("LAST_WORLDOFO_CHECK_DOMA_TIME", time());
      }
      break;
  }
  
  function getMapCornerPositionsAndRouteCoordinates($id)
  {
    $map = new Map();
    $map->Load($id);
    
    $user = DataAccess::GetUserByID($map->UserID);
    
    $categories = DataAccess::GetCategoriesByUserID($user->ID);
    return Helper::GetOverviewMapData($map, true, false, false, $categories);
  }
?>
