<?php
  include_once(dirname(__FILE__) ."/helper.php");

  class DataAccess
  {
    public static function GetAllMaps($userID = 0, $requestingUserID = 0)
    {
      $userID = mysql_real_escape_string($userID);
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $where[] = "U.Visible=1";
      if($userID) $where[] = "M.UserID='$userID'";
      $where[] = "(M.ProtectedUntil IS NULL OR M.ProtectedUntil<='$now' OR U.ID='$requestingUserID')";

      $sql = "SELECT M.*, M.ID AS MapID, M.Name AS Map_Name, C.*, C.Name AS CategoryName FROM `". DB_MAP_TABLE ."` M ".
             "LEFT JOIN `". DB_CATEGORY_TABLE ."` C ON C.ID=M.CategoryID ".
             "LEFT JOIN `". DB_USER_TABLE ."` U ON U.ID=M.UserID ".
             (count($where) > 0 ? "WHERE ". join(" AND ", $where) ." " : "").
             "ORDER BY M.Date DESC, M.ID DESC";
      return self::GetMapsUsersAndCategoriesFromSql($sql);
    }

    public static function GetAllMapIds()
    {
      $sql = "SELECT ID FROM `". DB_MAP_TABLE ."`";
      $rs = self::Query($sql);
      $ids = array();
      while($r = mysql_fetch_assoc($rs))
      {
        $ids[] = $r["ID"];
      }
      return $ids;
    }
    
    public static function GetMaps($userID = 0, $startDate = 0, $endDate = 0, $categoryID = 0, $filter = null, $count = 0, $orderBy = "date", $requestingUserID = 0)
    {
      $userID = mysql_real_escape_string($userID);
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $startDateString = mysql_real_escape_string(date("Y-m-d", $startDate));
      $endDateString = mysql_real_escape_string(date("Y-m-d", $endDate));
      $categoryID = mysql_real_escape_string($categoryID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $filter = mysql_real_escape_string($filter);
      $count = (int)$count;

      switch($orderBy)
      {
        case "lastChangedTime": $ob = "M.LastChangedTime DESC"; break;
        case "createdTime": $ob = "M.CreatedTime DESC"; break;
        case "ID": $ob = "M.ID DESC"; break;
        default: $ob = "M.Date DESC"; break;
      }

      $where[] = "U.Visible=1";
      if($userID) $where[] = "M.UserID='$userID'";
      if($startDate) $where[] = "DATE(M.Date)>='$startDateString'";
      if($endDate) $where[] = "DATE(M.Date)<='$endDateString'";
      if($categoryID) $where[] = "M.CategoryID='$categoryID'";
      if($filter != null && $filter != "") $where[] = "(M.Name LIKE '%$filter%' OR M.Comment LIKE '%$filter%' OR M.Organiser LIKE '%$filter%' OR M.Country LIKE '%$filter%' OR M.Discipline LIKE '%$filter%' OR M.MapName LIKE '%$filter%')";
      $where[] = "(M.ProtectedUntil IS NULL OR M.ProtectedUntil<='$now' OR M.UserID='$requestingUserID')";

      $sql = "SELECT M.*, M.ID AS MapID, M.Name AS Map_Name, C.*, C.Name AS CategoryName, U.* FROM `". DB_MAP_TABLE ."` M ".
             "LEFT JOIN `". DB_CATEGORY_TABLE ."` C ON C.ID=M.CategoryID ".
             "LEFT JOIN `". DB_USER_TABLE ."` U ON U.ID=M.UserID ".
             (count($where) > 0 ? "WHERE ". join(" AND ", $where) ." " : "").
             "ORDER BY $ob, M.ID DESC".
             ($count ? " LIMIT 0, $count" : "");
      return self::GetMapsUsersAndCategoriesFromSql($sql);
    }

    public static function GetCloseMaps($latitude, $longitude, $startTime, $endTime, $maxDistance, $orderBy = "closeness", $requestingUserID = 0)
    {
      $startTimeString = mysql_real_escape_string(date("Y-m-d", $startTime));
      $endTimeString = mysql_real_escape_string(date("Y-m-d", $endTime));
      $maxDistance = mysql_real_escape_string($maxDistance);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $requestingUserID = mysql_real_escape_string($requestingUserID);

      switch($orderBy)
      {
        case "closeness": $ob = "Closeness ASC"; break;
        default: $ob = "M.Date ASC"; break;
      }

      $pi180 = M_PI/180;
      $latR = $latitude*$pi180;
      $lonR = $longitude*$pi180;
      $closenessSql = "ACOS(SIN(M.MapCenterLatitude*$pi180) * SIN($latR) + COS(M.MapCenterLatitude*$pi180) * COS($latR) * COS($lonR-M.MapCenterLongitude*$pi180)) * 6378200";

      $where[] = "IsGeocoded=1";
      $where[] = "$closenessSql < '$maxDistance'";
      $where[] = "(M.ProtectedUntil IS NULL OR M.ProtectedUntil<='$now' OR M.UserID='$requestingUserID')";
      if($startTime) $where[] = "M.SessionEndTime>='$startTimeString'";
      if($endTime) $where[] = "M.SessionStartTime<='$endTimeString'";

      $sql = "SELECT M.*, M.ID AS MapID, M.Name AS Map_Name, C.*, C.Name AS CategoryName, U.*, $closenessSql AS Closeness FROM `". DB_MAP_TABLE ."` M ".
             "LEFT JOIN `". DB_CATEGORY_TABLE ."` C ON C.ID=M.CategoryID ".
             "LEFT JOIN `". DB_USER_TABLE ."` U ON U.ID=M.UserID ".
             "WHERE ". join(" AND ", $where). " ".
             "ORDER BY $ob, M.ID DESC";
      return self::GetMapsUsersAndCategoriesFromSql($sql);
    }

    private static function GetMapsUsersAndCategoriesFromSql($sql)
    {
      $rs = self::Query($sql);
      $maps = array();
      while($r = mysql_fetch_assoc($rs))
      {
        $map = new Map();
        $r["ID"] = $r["MapID"];
        $r["Name"] = $r["Map_Name"];
        $map->LoadFromArray($r);

        $category = new Category();
        $r["ID"] = $r["CategoryID"];
        $r["Name"] = $r["CategoryName"];
        $category->LoadFromArray($r);
        $map->SetCategory($category);

        $user = new User();
        $r["ID"] = $r["UserID"];
        $user->LoadFromArray($r);
        $map->SetUser($user);

        $maps[$map->ID] = $map;
      }
      return $maps;
    }

    public static function GetMapByID($id, $requestingUserID = 0)
    {
      $id = mysql_real_escape_string($id);
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $sql = "SELECT M.*, M.ID AS MapID, M.Name AS MapName, C.*, C.Name AS CategoryName FROM `". DB_MAP_TABLE ."` M ".
             "LEFT JOIN `". DB_CATEGORY_TABLE ."` C ON C.ID=M.CategoryID ".
             "WHERE M.ID='$id' AND ".
             "(M.ProtectedUntil IS NULL OR M.ProtectedUntil<='$now' OR M.UserID='$requestingUserID')";
      $rs = self::Query($sql);

      if($r = mysql_fetch_assoc($rs))
      {
        $map = new Map();
        $r["ID"] = $r["MapID"];
        $map->LoadFromArray($r);

        $category = new Category();
        $r["ID"] = $r["CategoryID"];
        $r["Name"] = $r["CategoryName"];
        $category->LoadFromArray($r);
        $map->SetCategory($category);
        return $map;
      }
      return null;
    }

    public static function GetYearsByUserIDAndCategoryID($userID, $categoryID, $requestingUserID = 0)
    {
      $userID = mysql_real_escape_string($userID);
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $categoryID = mysql_real_escape_string($categoryID);
      $sql = "SELECT DISTINCT YEAR(Date) AS Year FROM `". DB_MAP_TABLE ."` ".
             "WHERE UserID='$userID' AND ". 
             "(ProtectedUntil IS NULL OR ProtectedUntil<='$now' OR UserID='$requestingUserID')".
             ($categoryID ? "AND CategoryID='$categoryID'" : "").
             "ORDER BY Date ASC";
      $rs = self::Query($sql);

      $years = array();
      while($r = mysql_fetch_assoc($rs))
      {
        $years[] = $r["Year"];
      }
      return $years;
    }

    public static function GetYearsByUserID($userID, $requestingUserID = 0)
    {
      return self::GetYearsByUserIDAndCategoryID($userID, 0, $requestingUserID);
    }

    public static function GetLastChangedTime($userID = 0, $requestingUserID = 0)
    {
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $userID = mysql_real_escape_string($userID);
      $sql = "SELECT MAX(LastChangedTime) AS LastChangedTime FROM `". DB_MAP_TABLE ."` ".
             "WHERE (ProtectedUntil IS NULL OR ProtectedUntil<='$now' OR UserID='$requestingUserID')".
             ($userID ? " AND UserID='$userID'" : "");
      $r = mysql_fetch_assoc(self::Query($sql));
      return Helper::StringToTime($r["LastChangedTime"], true);
    }

    public static function GetLastCreatedTime($userID = 0, $requestingUserID = 0)
    {
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $userID = mysql_real_escape_string($userID);
      $sql = "SELECT MAX(CreatedTime) AS LastCreatedTime FROM `". DB_MAP_TABLE ."` ".
             "WHERE (ProtectedUntil IS NULL OR ProtectedUntil<='$now' OR UserID='$requestingUserID')".
             ($userID ? " AND UserID='$userID'" : "");
      $r = mysql_fetch_assoc(self::Query($sql));
      return Helper::StringToTime($r["LastCreatedTime"], true);
    }

    public static function GetPreviousMap($userID, $mapID, $requestingUserID = 0)
    {
      $mapID = mysql_real_escape_string($mapID);
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $userID = mysql_real_escape_string($userID);
      $sql = "SELECT * FROM `". DB_MAP_TABLE ."` WHERE (Date<(SELECT Date FROM `". DB_MAP_TABLE ."` WHERE ID='$mapID') OR (Date=(SELECT Date FROM `". DB_MAP_TABLE ."` WHERE ID='$mapID') AND ID<'$mapID')) AND ".
             "UserID='$userID' AND ".
             "(ProtectedUntil IS NULL OR ProtectedUntil<='$now' OR UserID='$requestingUserID') ".
             "ORDER BY Date DESC, ID DESC";
      if($r = mysql_fetch_assoc(self::Query($sql)))
      {
        $map = new Map();
        $map->LoadFromArray($r);
        return $map;
      }
      return null;
    }

    public static function GetNextMap($userID, $mapID, $requestingUserID = 0)
    {
      $mapID = mysql_real_escape_string($mapID);
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $userID = mysql_real_escape_string($userID);
      $sql = "SELECT * FROM `". DB_MAP_TABLE ."` WHERE (Date>(SELECT Date FROM `". DB_MAP_TABLE ."` WHERE ID='$mapID') OR (Date=(SELECT Date FROM `". DB_MAP_TABLE ."` WHERE ID='$mapID') AND ID>'$mapID')) AND ".
             "UserID='$userID' AND ".
             "(ProtectedUntil IS NULL OR ProtectedUntil<='$now' OR UserID='$requestingUserID') ".
             "ORDER BY Date ASC, ID ASC";
      $r = mysql_fetch_assoc(self::Query($sql));
      if($r = mysql_fetch_assoc(self::Query($sql)))
      {
        $map = new Map();
        $map->LoadFromArray($r);
        return $map;
      }
      return null;
    }

    public static function DeleteMap($map)
    {
      $uploadDir = Helper::LocalPath(MAP_IMAGE_PATH ."/");
      self::DeleteMapImage($map);
      self::DeleteThumbnailImage($map);
      $map->Delete();
    }

    public static function SaveMapAndThumbnailImage($map, $mapImageFileName, $blankMapImageFileName, $thumbnailImageFileName, &$error, &$thumbnailCreatedSuccessfully)
    {
      $inputMapImageFileName = $mapImageFileName;
      $inputBlankMapImageFileName = $blankMapImageFileName;
      $inputThumbnailImageFileName = $thumbnailImageFileName;
      $isNewMap = !($map->ID);
      $uploadDir = Helper::LocalPath(MAP_IMAGE_PATH ."/");
      $thumbnailCreatedSuccessfully = true;
      $map->Save();
      $id = $map->ID;

      $baseFileName = $id;

      if($inputMapImageFileName)
      {
        // map image
        $extension = Helper::GetExtension($inputMapImageFileName);
        $uploadFileName = $uploadDir . $baseFileName . "." . $extension;
        self::DeleteMapImage($map);

        @chmod($uploadDir, 0777);
        copy($inputMapImageFileName, $uploadFileName);
        @chmod($uploadFileName, 0777);

        $map->MapImage = "$baseFileName.$extension";
        
        if(!$inputThumbnailImageFileName)
        {
          // auto-create thumbnail
          self::DeleteThumbnailImage($map);
          $thumbnailImageName = Helper::CreateThumbnail(
            Helper::LocalPath(MAP_IMAGE_PATH ."/$baseFileName.$extension"),
            Helper::LocalPath(MAP_IMAGE_PATH. "/$baseFileName.thumbnail"),
            THUMBNAIL_WIDTH,
            THUMBNAIL_HEIGHT,
            THUMBNAIL_SCALE,
            $thumbnailCreatedSuccessfully);
          $map->ThumbnailImage = basename($thumbnailImageName);
        }

        $map->AddGeocoding();
      }

      if($inputBlankMapImageFileName)
      {
        // blank map image
        $extension = Helper::GetExtension($inputBlankMapImageFileName);
        $uploadFileName = $uploadDir . $baseFileName . ".blank." . $extension;
        self::DeleteBlankMapImage($map);

        @chmod($uploadDir, 0777);
        copy($inputBlankMapImageFileName, $uploadFileName);
        @chmod($uploadFileName, 0777);

        $map->BlankMapImage = "$baseFileName.blank.$extension";
        if(!$inputThumbnailImageFileName && !$thumbnailImageName)
        {
          // autc-create thumbnail
          self::DeleteThumbnailImage($map);
          $thumbnailImageName = Helper::CreateThumbnail(
            Helper::LocalPath(MAP_IMAGE_PATH ."/$baseFileName.blank.$extension"),
            Helper::LocalPath(MAP_IMAGE_PATH. "/$baseFileName.thumbnail"),
            THUMBNAIL_WIDTH,
            THUMBNAIL_HEIGHT,
            THUMBNAIL_SCALE,
            $thumbnailCreatedSuccessfully);
          $map->ThumbnailImage = basename($thumbnailImageName);
        }

        if(!$map->IsGeocoded)
        {
          // add geocoding if it didn't exist in map image
          $map->AddGeocoding();
        }
      }


      if($inputThumbnailImageFileName)
      {
        // custom thumbnail image
        $extension = Helper::GetExtension($inputThumbnailImageFileName);
        $uploadFileName = $uploadDir . $baseFileName . ".thumbnail." . $extension;
        self::DeleteThumbnailImage($map);

        @chmod($uploadDir, 0777);
        copy($inputThumbnailImageFileName, $uploadFileName);
        @chmod($uploadFileName, 0777);
        $map->ThumbnailImage = "$baseFileName.thumbnail.$extension";
      }

      $map->LastChangedTime = gmdate("Y-m-d H:i:s");
      if($isNewMap) $map->CreatedTime = gmdate("Y-m-d H:i:s");

      $map->Save();
      
      self::UnprotectMapIfNeeded($map);
      self::ProtectMapIfNeeded($map);
      
      return true;

      if($isNewMap)
      {
        $user = self::GetUserByID($map->UserID);
        //todo: border gps coords
        $data = "user=". urlencode($user->Username) .
                "&map=". $map->ID.
                ($gpsData ? "&longitude=". $gpsData["Longitude"] ."&latitude=". $gpsData["Latitude"] : "");
        Helper::LogUsage("addMap", $data);
      }
    }
    
    public static function ProtectMapIfNeeded($map)
    {
      if($map->ProtectedUntil != null && 
         $map->ProtectedUntil > gmdate("Y-m-d H:i:s") &&
         strpos($map->MapImage, "_") === false)
      {
        // taking a tiny tiny risk here by not checking if file actually exists
        $randomString = Helper::CreateRandomString(32);  
        $newMapImage = Helper::GetProtectedFileName($map->MapImage, $randomString);
        $newThumbnailImage = Helper::GetProtectedFileName($map->ThumbnailImage, $randomString);
        $newBlankMapImage = Helper::GetProtectedFileName($map->BlankMapImage, $randomString);
        self::RenameMapImageFiles($map, $newMapImage, $newThumbnailImage, $newBlankMapImage);
        $map->Save();
      }
    }    
    
    public static function UnprotectMapIfNeeded($map)
    {
      if($map->ProtectedUntil != null && 
         $map->ProtectedUntil <= gmdate("Y-m-d H:i:s") &&
         strpos($map->MapImage, "_") !== false)
      {
        $newMapImage = Helper::GetUnprotectedFileName($map->MapImage);
        $newThumbnailImage = Helper::GetUnprotectedFileName($map->ThumbnailImage);
        $newBlankMapImage = Helper::GetUnprotectedFileName($map->BlankMapImage);
        self::RenameMapImageFiles($map, $newMapImage, $newThumbnailImage, $newBlankMapImage);
        $map->Save();
      }
    }
    
    private static function RenameMapImageFiles($map, $newMapImage, $newThumbnailImage, $newBlankMapImage)
    {
      $uploadDir = Helper::LocalPath(MAP_IMAGE_PATH ."/");
      @chmod($uploadDir, 0777);
      if($map->MapImage != null) 
      {
        @chmod($uploadDir . $map->MapImage, 0777);
        @chmod($uploadDir . $newMapImage, 0777);
        if(@rename($uploadDir . $map->MapImage, $uploadDir . $newMapImage)) $map->MapImage = $newMapImage; 
      }
      if($map->ThumbnailImage != null) 
      {
        @chmod($uploadDir . $map->ThumbnailImage, 0777);
        @chmod($uploadDir . $newThumbnailImage, 0777);
        if(@rename($uploadDir . $map->ThumbnailImage, $uploadDir . $newThumbnailImage)) $map->ThumbnailImage = $newThumbnailImage;
      }
      if($map->BlankMapImage != null) 
      {
        @chmod($uploadDir . $map->BlankMapImage, 0777);
        @chmod($uploadDir . $newBlankMapImage, 0777);
        if(@rename($uploadDir . $map->BlankMapImage, $uploadDir . $newBlankMapImage)) $map->BlankMapImage = $newBlankMapImage;
      }
    }
    
    public static function DeleteMapImage($map)
    {
      $uploadDir = Helper::LocalPath(MAP_IMAGE_PATH ."/");
      if($map->MapImage) @unlink($uploadDir . $map->MapImage);
    }

    public static function DeleteBlankMapImage($map)
    {
      $uploadDir = Helper::LocalPath(MAP_IMAGE_PATH ."/");
      if($map->BlankMapImage) @unlink($uploadDir . $map->BlankMapImage);
    }

    public static function DeleteThumbnailImage($map)
    {
      $uploadDir = Helper::LocalPath(MAP_IMAGE_PATH ."/");
      if($map->ThumbnailImage) @unlink($uploadDir . $map->ThumbnailImage);
    }

    public static function IncreaseMapViews($map)
    {
      $id = mysql_real_escape_string($map->ID);
      $sql = "UPDATE `". DB_MAP_TABLE ."` SET Views=Views+1 WHERE ID='$id'";
      self::Query($sql);
    }

    public static function GetUserByID($id)
    {
      $id = mysql_real_escape_string($id);
      $sql = "SELECT * FROM `". DB_USER_TABLE ."` WHERE ID='$id'";
      $rs = self::Query($sql);

      if($r = mysql_fetch_assoc($rs))
      {
        $user = new User();
        $user->LoadFromArray($r);
        return $user;
      }
      else
      {
        return null;
      }
    }

    public static function GetSingleUserID()
    {
      $sql = "SELECT ID FROM `". DB_USER_TABLE ."` WHERE Visible=1";
      $rs = self::Query($sql);

      if(mysql_num_rows($rs) == 1)
      {
        $r = mysql_fetch_assoc($rs);
        return $r["ID"];
      }
      return null;
    }

    public static function GetUserByUsernameAndPassword($username, $password)
    {
      $username = mysql_real_escape_string($username);
      $password = mysql_real_escape_string(md5($password));
      $sql = "SELECT * FROM `". DB_USER_TABLE ."` WHERE Username='$username' AND Password='$password' AND Visible=1";
      $rs = self::Query($sql);

      if($r = mysql_fetch_assoc($rs))
      {
        $user = new User();
        $user->LoadFromArray($r);
        return $user;
      }
      else
      {
        return null;
      }
    }

    public static function GetUserByUsername($username)
    {
      $username = mysql_real_escape_string($username);
      $sql = "SELECT * FROM `". DB_USER_TABLE ."` WHERE Username='$username'";
      $rs = self::Query($sql);

      if($r = mysql_fetch_assoc($rs))
      {
        $user = new User();
        $user->LoadFromArray($r);
        return $user;
      }
      else
      {
        return null;
      }
    }

    public static function UsernameExists($username, $excludeUserID)
    {
      $username = mysql_real_escape_string(strtolower($username));
      if(!$excludeUserID) $excludeUserID = 0;
      $excludeUserID = mysql_real_escape_string($excludeUserID);
      $sql = "SELECT * FROM `". DB_USER_TABLE ."` WHERE LCASE(Username)='$username' AND NOT(ID='$excludeUserID')";
      $rs = self::Query($sql);

      return (mysql_num_rows($rs) > 0);
    }

    public static function GetUserSettings($userID)
    {
      $userID = mysql_real_escape_string($userID);
      $ret = array();
      $sql = "SELECT `Key`, `Value` FROM `". DB_USER_SETTING_TABLE ."` WHERE UserID='$userID'";
      $rs = self::Query($sql);
      $user = self::GetUserByID($userID);

      while($r = mysql_fetch_assoc($rs))
      {
        $r["Value"] = str_replace("%userEmail%", $user->Email, $r["Value"]);
        $ret[$r["Key"]] = $r["Value"];
      }
      return $ret;
    }

    public static function GetAllUsers($visibleOnly)
    {
      $sql = "SELECT U.*, COUNT(M.ID) AS NoOfMaps ".
             "FROM `". DB_USER_TABLE ."` U ".
             "LEFT JOIN `". DB_MAP_TABLE ."` M ON U.ID=M.UserID ".
             ($visibleOnly ? "WHERE U.Visible=1 " : "").
             "GROUP BY U.ID ".
             "ORDER BY U.LastName, U.FirstName, U.ID";

      $rs = self::Query($sql);

      $users = array();
      while($r = mysql_fetch_assoc($rs))
      {
        $user = new User();
        $user->LoadFromArray($r);
        $user->NoOfMaps = $r["NoOfMaps"];
        $users[$user->ID] = $user;
      }

      return $users;
    }

    public static function DeleteUserByID($id)
    {
      // delete all map images
      $maps = self::GetAllMaps($id, $id);
      foreach($maps as $m)
      {
        self::DeleteMapImage($m);
        self::DeleteThumbnailImage($m);
      }
      $id = mysql_real_escape_string($id);
      $sql = "DELETE FROM `". DB_MAP_TABLE ."` WHERE UserID='$id'";
      self::Query($sql);
      $sql = "DELETE FROM `". DB_USER_SETTING_TABLE ."` WHERE UserID='$id'";
      self::Query($sql);
      $sql = "DELETE FROM `". DB_USER_TABLE ."` WHERE ID='$id'";
      self::Query($sql);
    }

    public static function GetLastMapsForUsers($param = "date", $requestingUserID = 0)
    {
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      switch($param)
      {
        case "lastChangedTime": $field = "LastChangedTime"; break;
        case "createdTime": $field = "CreatedTime"; break;
        default: $field = "Date"; break;
      }

      $ret = array();
      $sql = "SELECT * FROM `". DB_MAP_TABLE ."` a ".
             "INNER JOIN `". DB_MAP_TABLE ."` b ".
             "ON a.ID=b.ID ".
             "WHERE a.`$field`=(SELECT MAX(`$field`) FROM `". DB_MAP_TABLE ."` WHERE UserID=b.UserID AND (ProtectedUntil IS NULL OR ProtectedUntil<='$now' OR UserID='$requestingUserID'))";
      $rs = self::Query($sql);
      while($r = mysql_fetch_assoc($rs))
      {
        $map = new Map();
        $map->LoadFromArray($r);
        $ret[$map->UserID] = $map;
      }
      return $ret;
    }

    public static function SaveUser($user, $categories, $defaultCategoryIndex, $userSettings)
    {
      $newUser = (!$user->ID);
      $user->Save();

      self::SaveUserCategories($user->ID, $categories);
      $categoriesIndexed = array_values($categories);
      $defaultCategoryID = $defaultCategoryIndex == -1 ? 0 : $categoriesIndexed[$defaultCategoryIndex]->ID;
      $user->DefaultCategoryID = $defaultCategoryID;
      $user->Save();

      self::SaveUserSettings($user->ID, $userSettings);
      if($newUser) Helper::LogUsage("createUser", "user=". urlencode($user->Username));
    }

    public static function SaveUserCategories($userID, &$categories)
    {
      // 1. get all existing categories for this user
      $existingCategories = self::GetCategoriesByUserID($userID);
      $existingCategoryIDs = array();
      // 2. Extract the ids
      foreach($existingCategories as $ec)
      {
        $existingCategoryIDs[$ec->ID] = $ec->ID;
      }

      // 3. Save all categories in $categories
      $count = 0;
      foreach($categories as &$c)
      {
        $c->UserID = $userID; // update with user id
        $c->Save();
        unset($existingCategoryIDs[$c->ID]);
      }

      // 4. Delete existing categories that are not found in $categories
      foreach($existingCategoryIDs as $ecID)
      {
        self::DeleteCategoryByID($ecID);
      }
    }

    public static function SaveUserSettings($userID, $settings)
    {
      $userID = mysql_real_escape_string($userID);
      // first delete all settings for this user
      $sql = "DELETE FROM `". DB_USER_SETTING_TABLE ."` WHERE UserID='$userID'";
      self::Query($sql);
      // then insert new settings
      foreach($settings as $key => $value)
      {
        $key = mysql_real_escape_string($key);
        $value = mysql_real_escape_string($value);
        $sql = "INSERT INTO `". DB_USER_SETTING_TABLE ."` (`UserID`, `Key`, `Value`) ".
               "VALUES ('$userID', '$key', '$value')";
        self::Query($sql);
      }
    }

    public static function GetCategoryByID($id)
    {
      $id = mysql_real_escape_string($id);
      $sql = "SELECT * FROM `". DB_CATEGORY_TABLE ."` WHERE ID='$id'";
      $rs = self::Query($sql);

      if($r = mysql_fetch_assoc($rs))
      {
        $category = new Category();
        $category->LoadFromArray($r);
        return $category;
      }
      else
      {
        return null;
      }
    }

    public static function GetCategoriesByUserID($userID = 0)
    {
      $userID = mysql_real_escape_string($userID);
      $sql = "SELECT * FROM `". DB_CATEGORY_TABLE ."` ".
             ($userID ? "WHERE UserID='$userID' " : "").
             "ORDER BY ID";
      $rs = self::Query($sql);

      $categories = array();
      while($r = mysql_fetch_assoc($rs))
      {
        $category = new Category();
        $category->LoadFromArray($r);
        $categories[$category->ID] = $category;
      }
      return $categories;
    }

    public static function GetCategoriesByUserIDAndYear($userID, $year, $requestingUserID = 0)
    {
      if($year == 0) return self::GetCategoriesByUserID($userID);
      $userID = mysql_real_escape_string($userID);
      $year = mysql_real_escape_string($year);
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));

      $sql = "SELECT * FROM `". DB_CATEGORY_TABLE ."` ".
             "WHERE ID IN(SELECT DISTINCT(CategoryID) FROM `". DB_MAP_TABLE ."` WHERE UserID='$userID' AND YEAR(Date)='$year' AND (ProtectedUntil IS NULL OR ProtectedUntil<='$now' OR UserID='$requestingUserID')) ".
             "ORDER BY ID";
      $rs = self::Query($sql);

      $categories = array();
      while($r = mysql_fetch_assoc($rs))
      {
        $category = new Category();
        $category->LoadFromArray($r);
        $categories[$category->ID] = $category;
      }
      return $categories;
    }

    // returns false and doesn't delete when there are maps in the category
    public static function DeleteCategoryByID($id)
    {
      $id = mysql_real_escape_string($id);
      if(self::NoOfMapsInCategory($id) == 0)
      {
        $sql = "DELETE FROM `". DB_CATEGORY_TABLE ."` WHERE ID='$id'";
        self::Query($sql);
        return true;
      }
      return false;
    }

    public static function NoOfMapsInCategory($id)
    {
      if(!$id) return 0;
      $id = mysql_real_escape_string($id);
      $sql = "SELECT COUNT(*) AS NoOfMaps FROM `". DB_MAP_TABLE ."` ".
             "WHERE CategoryID='$id'";
      $r = mysql_fetch_assoc(self::Query($sql));

      return $r["NoOfMaps"];
    }

    public static function DeleteAllUsers()
    {
      self::Query('DELETE FROM `'. DB_MAP_TABLE ."`");
      self::Query('DELETE FROM `'. DB_USER_TABLE ."`");
      self::Query('DELETE FROM `'. DB_USER_SETTING_TABLE ."`");
      self::Query('DELETE FROM `'. DB_CATEGORY_TABLE ."`");
      Helper::DeleteFiles(MAP_IMAGE_PATH, "*.*");
    }

    public static function GetSetting($key, $defaultValue)
    {
      $key = mysql_real_escape_string($key);
      $sql = "SELECT `Value` FROM `". DB_SETTING_TABLE ."` WHERE `Key`='$key'";
      $rs = self::Query($sql);
      if($r = @mysql_fetch_assoc($rs))
      {
        return $r["Value"];
      }
      return $defaultValue;
    }

    public static function SetSetting($key, $value)
    {
      $key = mysql_real_escape_string($key);
      $value = mysql_real_escape_string($value);
      $sql = "REPLACE INTO `". DB_SETTING_TABLE ."` (`Key`, `Value`) VALUES ('$key', '$value')";
      self::Query($sql);
    }

    private static function Query($sql)
    {
      $result = @mysql_query($sql);
      Helper::WriteToLog($sql);
      if(mysql_error()) Helper::WriteToLog("MYSQL ERROR: ". mysql_error());
      return $result;
    }
    
    public static function GetCommentsByMapId($mapId)
    {
      $mapId = mysql_real_escape_string($mapId);
      $sql = "SELECT C.* ".
             "FROM `". DB_COMMENT_TABLE ."` C ".
             "WHERE C.MapID='$mapId' ".
             "ORDER BY C.DateCreated";

      $rs = self::Query($sql);

      $comments = array();
      while($r = mysql_fetch_assoc($rs))
      {
        $comment = new Comment();
        $comment->LoadFromArray($r);
        $comments[$comment->ID] = $comment;
      }

      return $comments;
    }
    public static function GetLastComments($numberOfComments, $requestingUserID = 0)
    {
      $numberOfComments = (int)$numberOfComments;
      $requestingUserID = mysql_real_escape_string($requestingUserID);
      $now = mysql_real_escape_string(gmdate("Y-m-d H:i:s"));
      $sql = "select distinct m.ID, m.UserID, m.Name, ".
      "(select concat(FirstName,' ',LastName) from `". DB_USER_TABLE ."` where id=m.userid) as user_flname, ".
      "(select UserName from `". DB_USER_TABLE ."` where id=m.userid) as user_name, ".
      "(select count(*) from `". DB_COMMENT_TABLE ."` where mapid=m.id) as comments_count, ".
      "(select name from `". DB_COMMENT_TABLE ."` where mapid=m.id order by datecreated desc limit 0,1) as comment_name, ".
      "(select datecreated from `". DB_COMMENT_TABLE ."` where mapid=m.id order by datecreated desc limit 0,1) as comment_date ".
      "from `". DB_MAP_TABLE ."` as m ".
      "inner join `". DB_COMMENT_TABLE ."` as c on m.id=c.mapid ".
      "WHERE (m.ProtectedUntil IS NULL OR m.ProtectedUntil<='$now' OR m.UserID='$requestingUserID') ".
      "order by comment_date desc ".
      "limit 0,$numberOfComments";

      $rs = self::Query($sql);

      $last_comments = array();
      
      while($r = mysql_fetch_assoc($rs))
      {
        $last_comment = array();
        $last_comment["ID"]=$r["ID"];
        $last_comment["UserID"]=$r["UserID"];
        $last_comment["UserName"]=$r["user_name"];
        $last_comment["UserFLName"]=$r["user_flname"];
        $last_comment["Name"]=$r["Name"];
        $last_comment["CommentsCount"]=$r["comments_count"];
        $last_comment["CommentName"]=$r["comment_name"];
        $last_comment["CommentDate"]=$r["comment_date"];
        $last_comments[$last_comment["ID"]] = $last_comment;
      }

      return $last_comments;
    }
  }
?>
