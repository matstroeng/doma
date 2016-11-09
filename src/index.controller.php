<?php
  include_once(dirname(__FILE__) ."/include/main.php");

  class IndexController
  {
    public function Execute()
    {
      $viewData = array();
      // no user specified - redirect to user list page
      
      if(!getCurrentUser()) 
      {
        $singleUserID = DataAccess::GetSingleUserID();
        if(!$singleUserID) Helper::Redirect("users.php");
        Helper::SetUser(DataAccess::GetUserByID($singleUserID));
      }
      
      // user is hidden - redirect to user list page
      if(!getCurrentUser()->Visible) Helper::Redirect("users.php");
      
      $searchCriteria = Session::GetSearchCriteria(getCurrentUser()->ID);
      
      if(!isset($searchCriteria))
      {
        // default search criteria  
        $searchCriteria = array(
            "selectedYear" => date("Y"),
            "selectedCategoryID" => getCurrentUser()->DefaultCategoryID,
            "filter" => null
        );
      }
      
      $viewData["Errors"] = array();

      if(isset($_GET["error"]) && $_GET["error"] == "thumbnailCreationFailure")
      {
        // calculate max image size for auto-generation of thumbnail
        $memoryLimit = ini_get("memory_limit");
        if(stripos($memoryLimit, "M")) $memoryLimit = ((int)str_replace("M", "", $memoryLimit)) * 1024 * 1024;
        $memoryLimit -= memory_get_usage();
        $size = round(sqrt($memoryLimit / 4) / 100) * 100; 
        $viewData["Errors"][] = sprintf(__("THUMBNAIL_CREATION_FAILURE"), $size. "x". $size);
      }
      
      // get all categories
      $allCategoriesItem = new Category();
      $allCategoriesItem->ID = 0;
      $allCategoriesItem->Name = __("ALL_CATEGORIES");
      $categories = DataAccess::GetCategoriesByUserID(getCurrentUser()->ID);
      $viewData["Categories"] = $categories;
      $viewData["CategoriesWithText"] = array_merge(array(0 => $allCategoriesItem), $categories);

      // get all years
      $years = DataAccess::GetYearsByUserID(getCurrentUser()->ID, Helper::GetLoggedInUserID());
      $years = array_reverse($years);
      $viewData["YearsWithText"][0] = array("value" => 0, "text" => __("ALL_YEARS"));
      foreach($years as $year)
      {
        $viewData["YearsWithText"][$year] = array("value" => $year, "text" => $year);
      }
      if(!in_array($searchCriteria["selectedYear"], array_keys($viewData["YearsWithText"])) && count($years) > 0)
      {
        $searchCriteria["selectedYear"] = $years[0];
      } 
      $categoryIds = array_keys($categories);
      if($searchCriteria["selectedCategoryID"] != 0 && !in_array($searchCriteria["selectedCategoryID"], $categoryIds) && count($categories) > 0)
      {
        $searchCriteria["selectedCategoryID"] = $categoryIds[0];
      } 

      if(isset($_GET["year"])) $searchCriteria["selectedYear"] = $_GET["year"];
      if(isset($_GET["categoryID"])) $searchCriteria["selectedCategoryID"] = $_GET["categoryID"];
      if(isset($_GET["filter"])) $searchCriteria["filter"] = $_GET["filter"];
      if(isset($_GET["displayMode"])) 
      {
        $viewData["DisplayMode"] = $_GET["displayMode"];
      }
      else
      {
        $viewData["DisplayMode"] = "list";
      }

      $startDate = ($searchCriteria["selectedYear"] == 0 ? 0 : Helper::StringToTime($searchCriteria["selectedYear"] ."-01-01", true));
      $endDate = ($searchCriteria["selectedYear"] == 0 ? 0 : Helper::StringToTime($searchCriteria["selectedYear"]. "-12-31", true));
      $viewData["SearchCriteria"] = $searchCriteria;
      
      // get map data
      $viewData["Maps"] = DataAccess::GetMaps(getCurrentUser()->ID, $startDate, $endDate, $searchCriteria["selectedCategoryID"], $searchCriteria["filter"], 0, "date", Helper::GetLoggedInUserID());  
      $viewData["GeocodedMapsExist"] = false;
      
      foreach($viewData["Maps"] as $map)
      {
        $mapInfo = array();
        $mapInfo["URL"] = ($map->MapImage ? 'show_map.php?'. Helper::CreateQuerystring(getCurrentUser(), $map->ID) : "");
        $mapInfo["Name"] = $map->Name .' ('. date(__("DATE_FORMAT"), Helper::StringToTime($map->Date, true)) .')';
        $mapInfo["MapThumbnailHtml"] = Helper::EncapsulateLink('<img src="'. Helper::GetThumbnailImage($map) .'" alt="'. $mapInfo["Name"] .'" height="'. THUMBNAIL_HEIGHT .'" width="'. THUMBNAIL_WIDTH .'" />', $mapInfo["URL"]);

        $atoms = array();
        if(__("SHOW_MAP_AREA_NAME") && $map->MapName) $atoms[] = $map->MapName;
        if(__("SHOW_ORGANISER") && $map->Organiser) $atoms[] = $map->Organiser;
        if(__("SHOW_COUNTRY") && $map->Country) $atoms[] = $map->Country;
        $mapInfo["MapAreaOrganiserCountry"] = join(", ", $atoms);
        
        if($map->Comment)
        {
          $maxLength = 130;
          $strippedComment = strip_tags($map->Comment);
          $mapInfo["IsExpandableComment"] = !($strippedComment == $map->Comment && strlen($map->Comment) <= $maxLength);
          if($mapInfo["IsExpandableComment"])
          {
            $mapInfo["ContractedComment"] = substr($strippedComment, 0, $maxLength) ."...";
          }
        }
        $viewData["MapInfo"][$map->ID] = $mapInfo;
        
        if(($viewData["DisplayMode"] == "overviewMap")&&($map->IsGeocoded))
        {
          $viewData["OverviewMapData"][] = Helper::GetOverviewMapData($map, false, true, false, $categories, $searchCriteria["selectedCategoryID"]);
        }
        if($map->IsGeocoded) $viewData["GeocodedMapsExist"] = true;
      }
      if(!$viewData["GeocodedMapsExist"]) $viewData["DisplayMode"] = "list";
      
      Session::SetSearchCriteria(getCurrentUser()->ID, $searchCriteria);
      
      return $viewData;
    }
  }
  
?>
