<?php
  include_once(dirname(__FILE__) ."/include/main.php");

  class UsersController
  {
    public function Execute()
    {
      $viewData = array();

      $errors = array();

      if(Helper::IsLoggedInAdmin() && isset($_GET["loginAsUser"]))
      {
        // login as a certain user and redirect to his page
        if(Helper::LoginUserByUsername($_GET["loginAsUser"]))
        {
          Helper::Redirect("index.php?". Helper::CreateQuerystring(getCurrentUser()));
        }
      }
      
      $viewData["Users"] = DataAccess::GetAllUsers(!Helper::IsLoggedInAdmin());
      
      $viewData["LastMapForEachUser"] = DataAccess::GetLastMapsForUsers("date");
      
      // last x maps
      $numberOfMaps = isset($_GET["lastMaps"]) && is_numeric($_GET["lastMaps"]) 
        ? (int)$_GET["lastMaps"] 
        : (isset($_GET["lastMaps"]) && $_GET["lastMaps"] == "all" ? 999999 : 10);
      $viewData["LastMaps"] = DataAccess::GetMaps(0, 0, 0, 0, null, $numberOfMaps, "createdTime", Helper::GetLoggedInUserID());
      
      // last x comments
      $numberOfComments = isset($_GET["lastComments"]) && is_numeric($_GET["lastComments"]) 
        ? (int)$_GET["lastComments"] 
        : (isset($_GET["lastComments"]) && $_GET["lastComments"] == "all" ? 999999 : 10);
      $viewData["LastComments"] = DataAccess::GetLastComments($numberOfComments, Helper::GetLoggedInUserID());
      
      $viewData["OverviewMapData"] = null;
      $categories = DataAccess::GetCategoriesByUserID();
      foreach($viewData["LastMaps"] as $map)
      {
        $data = Helper::GetOverviewMapData($map, false, true, true, $categories);
        if($data != null) $viewData["OverviewMapData"][] = $data;
      }

      if(isset($_GET["error"]) && $_GET["error"] == "email") $errors[] = sprintf(__("ADMIN_EMAIL_ERROR"), ADMIN_EMAIL);
      
      $viewData["Errors"] = $errors;
      
      return $viewData;
    }
  }      
 
?>
