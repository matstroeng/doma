<?php
  // Some often-used functions that needs short names are wrapped and placed in global scope

  function __($key, $htmlSpecialChars = false)
  {
    return Helper::__($key, $htmlSpecialChars);
  }

  function hsc($string)
  {
    return Helper::Hsc($string);
  }

  function getCurrentUser()
  {
    return Helper::GetUser();
  }

  class Helper
  {
    public static function __($key, $htmlSpecialChars = false)
    {
      $ls = Session::GetLanguageStrings();
      $value = $ls[$key];
      if($htmlSpecialChars) return hsc($value);
      if (!isset($value)) $value = "#".$key."#";
      return $value;
    }

    public static function Hsc($string)
    {
      return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
    }

    // creates language strings for a certain user
    public static function GetLanguageStrings($userID = 0)
    {
      // 1. application-wide strings
      $cs = self::GetCustomizableStrings();
      $settings = array_merge($cs["settings"], self::GetNonCustomizableStrings());

      // 2. user-specific settings
      $userSettings = array();
      if($userID) $userSettings = DataAccess::GetUserSettings($userID);
      foreach($userSettings as $key => $value)
      {
        $settings[$key] = $value;
      }
      return $settings;
    }

    public static function GetCustomizableStrings()
    {
      $settings = array();
      $descriptions = array();
      $languageFileName = self::LocalPath("languages/". Session::GetLanguageCode() .".xml");
      $xml = simplexml_load_file($languageFileName);
      $count = count($xml->customizable->string);
      for($i = 0; $i < $count; $i++)
      {
        $attrs = $xml->customizable->string[$i]->attributes();
        $key = $attrs["key"];
        $description = $attrs["description"];
        $value = $xml->customizable->string[$i];
        $settings["$key"] = trim("$value");
        $descriptions["$key"] = trim("$description");
      }
      return array("settings" => $settings, "descriptions" => $descriptions);
    }

    private static function GetNonCustomizableStrings()
    {
      $settings = array();
      $languageFileName = self::LocalPath("languages/". Session::GetLanguageCode() .".xml");
      $xml = simplexml_load_file($languageFileName);
      $count = count($xml->nonCustomizable->string);
      for($i = 0; $i < $count; $i++)
      {
        $attrs = $xml->nonCustomizable->string[$i]->attributes();
        $key = $attrs["key"];
        $value = $xml->nonCustomizable->string[$i];
        $value = trim("$value");
        $value = str_replace("%adminEmail%", ADMIN_EMAIL, $value);
        $settings["$key"] = $value;
      }
      return $settings;
    }

    public static function CreateQuerystring($user, $mapID = 0)
    {
      $qs = "user=". urlencode($user->Username);
      if($mapID) $qs .= "&amp;map=". $mapID;
      return $qs;
    }

    public static function Redirect($url)
    {
      header("Location: $url");
      die();
    }

    /**
    * Creates a url relative to the server root, e g /subdir/index.php.
    *
    */
    public static function ServerPath($path)
    {
      if(substr($path, 0, 1) == "/") $path = substr($path, 1);
      return PROJECT_DIRECTORY . $path;
    }

    /**
    * Creates a rooted path on the local machine e g c:\inetpub\wwwroot\subdir/index.php.
    *
    */
    public static function LocalPath($path)
    {
      if(substr($path, 0, 1) == "/") $path = substr($path, 1);
      return ROOT_PATH . $path;
    }

    /**
    * Creates a full url, e g http://www.mymaparchive.com/subdir/index.php.
    *
    */
    public static function GlobalPath($path)
    {
      if(substr($path, 0, 1) == "/") $path = substr($path, 1);
      return BASE_URL . $path;
    }

    public static function LoginAdmin($username, $password)
    {
      if(stripslashes($username) == ADMIN_USERNAME && stripslashes($password) == ADMIN_PASSWORD)
      {
        Session::SetIsLoggedInAdmin(true);
        return true;
      }
      return false;
    }

    public static function IsLoggedInAdmin()
    {
       return Session::GetIsLoggedInAdmin(true);
    }

    public static function LogoutAdmin()
    {
       Session::SetIsLoggedInAdmin(null);
    }

    public static function LoginUser($username, $password)
    {
      $user = DataAccess::GetUserByUsernameAndPassword($username, $password);
      if($user)
      {
        Session::SetLoggedInUser($user);
        self::SetUser($user);
        return true;
      }
      return false;
    }

    public static function LoginUserByUsername($username)
    {
      $user = DataAccess::GetUserByUsername($username);
      if($user)
      {
        Session::SetLoggedInUser($user);
        self::SetUser($user);
        return true;
      }
      return false;
    }


    public static function IsLoggedInUser()
    {
      $user = self::GetLoggedInUser();
      return isset($user);
    }

    public static function LogoutUser()
    {
      Session::SetLoggedInUser(null);
    }

    public static function GetLoggedInUser()
    {
      return Session::GetLoggedInUser();
    }

    public static function GetLoggedInUserID()
    {
      $user = self::GetLoggedInUser();
      if(!isset($user)) return 0;
      return $user->ID;
    }

    // the user as specified by $_GET["user"] / $_POST["user"]
    public static function GetUser()
    {
      return Session::GetDisplayedUser();
    }

    // the user as specified by $_GET["user"] / $_POST["user"]
    public static function SetUser($user)
    {
      if(isset($_GET["lang"]))
      {
        if(strrpos("|" . LANGUAGES_AVAILABLE ."|", ";". $_GET["lang"] ."|") !== false) Session::SetLanguageCode($_GET["lang"]);
      }
      else
      {
        if(Session::GetLanguageCode() == null)
        {
          Session::SetLanguageCode(defined('LANGUAGE_CODE') ? LANGUAGE_CODE : self::GetVersion2DefaultLanguageCode());
        }
      }

      $languageFileName = self::LocalPath("languages/". Session::GetLanguageCode() .".xml");
      $languageFileNameAndDate = $languageFileName ."_". filemtime($languageFileName);

      // some caching logic for language strings
      $previousUser = self::GetUser();
      $loadStrings = ($previousUser || $user || Session::GetLanguageFile() != $languageFileNameAndDate);

      if(!Session::GetLanguageStrings()) $loadStrings = true;

      Session::SetDisplayedUser($user);
      if($loadStrings)
      {
        Session::SetLanguageStrings(Helper::GetLanguageStrings($user ? $user->ID : 0));
        Session::SetLanguageFile($languageFileNameAndDate);
      }
    }

    private static function GetVersion2DefaultLanguageCode()
    {
      // handle DOMA 2 config where the setting had a different name
      return str_replace(array("no_NB", "ee", "cz", "dk", "de_AT"),
                         array("nb", "et", "cs", "da", "de"),
                         str_replace(".xml", "", LANGUAGE_FILE));
    }

    public static function GetThumbnailImage(Map $map)
    {
      return self::ServerPath(MAP_IMAGE_PATH ."/". $map->ThumbnailImage);
    }

    public static function GetMapImage(Map $map)
    {
      return self::ServerPath(MAP_IMAGE_PATH ."/". $map->MapImage);
    }

    public static function GetBlankMapImage(Map $map)
    {
      return self::ServerPath(MAP_IMAGE_PATH ."/". $map->BlankMapImage);
    }

    public static function DatabaseVersionIsValid()
    {
      $databaseVersion = Session::GetDatabaseVersion();
      if($databaseVersion == null ||
         version_compare($databaseVersion, DOMA_VERSION) < 0 /* make extra check if not valid to avoid stale data */)
      {
        $databaseVersion = DataAccess::GetSetting("DATABASE_VERSION", "0.0");
        Session::SetDatabaseVersion($databaseVersion);
      }
      return version_compare($databaseVersion, DOMA_VERSION) >= 0;
    }

    public static function EncapsulateLink($linkText, $url)
    {
      if($url == "")
      {
        return $linkText;
      }
      else
      {
        return '<a href="'. $url .'">'. $linkText .'</a>';
      }
    }

    public static function DateToLongString($d)
    {
      $dayNames = explode(";", __("DAY_NAMES"));
      $monthNames = explode(";", __("MONTH_NAMES"));
      return $dayNames[date("w", $d)] ." ".
             date("j", $d) ." ".
             $monthNames[date("n", $d) - 1] ." ".
             date("Y", $d);
    }

    public static function StringToTime($string, $utc)
    {
      return strtotime($string . ($utc ? " UTC" : ""));
    }

    public static function LocalizedStringToTime($string, $utc)
    {
      return strtotime(self::ToIso8601DateTime($string) . ($utc ? " UTC" : ""));
    }

    private static function ParseDateTime($dateTimeString)
    {
      if(function_exists("date_parse_from_format"))
      {
        $value = date_parse_from_format(__("DATETIME_FORMAT") .":s", $dateTimeString);
        return mktime($value["hour"], $value["minute"], $value["second"], $value["month"], $value["day"], $value["year"]);
      }
      // fall back to custom function
      $dateTimeString = str_replace(array(".", "/", ":", " "), "-", $dateTimeString);
      $format = str_replace(array(".", "/", ":", " "), "-", __("DATETIME_FORMAT") .":s");
      $dateTimeAtoms = @explode("-", $dateTimeString);
      $formatAtoms = @explode("-", $format);

      $value = array("Y" => 0, "m" => 0, "d" => 0, "H" => 0, "i" => 0, "s" => 0);

      for($i=0; $i<count($formatAtoms); $i++)
      {
        if(isset($dateTimeAtoms[$i])) $value[$formatAtoms[$i]] = $dateTimeAtoms[$i];
      }

      return mktime($value["H"], $value["i"], $value["s"], $value["m"], $value["d"], $value["Y"]);
    }

    private static function ToIso8601DateTime($dateTimeString)
    {
      return date("Y-m-d H:i:s", self::ParseDateTime($dateTimeString));
    }

    private static function ImageIsResizable($fileName)
    {
      if(IMAGE_RESIZING_METHOD == "2") return true;
      try
      {
        $contents = file_get_contents(self::GlobalPath("include/image_is_resizable.php?filename=". $fileName));
        return ($contents == "1");
      }
      catch(Exception $e)
      {
        return false;
      }
    }

    public static function ImageCreateFromGeneral($fileName)
    {
      switch(strtolower(self::GetExtension($fileName)))
      {
        case "png":
          $image = ImageCreateFromPng($fileName);
          break;
        case "gif":
          $image = ImageCreateFromGif($fileName);
          break;
        default:
          $image = ImageCreateFromJpeg($fileName);
          break;
      }
      return $image;
    }

    public static function GetExtension($fileName)
    {
      $pathinfo = pathinfo($fileName);
      return $pathinfo["extension"];
    }

    public static function GetFilenameWithoutExtension($fileName)
    {
      $extension = self::GetExtension($fileName);
      if($extension) return basename($fileName, ".". self::GetExtension($fileName));
      basename($fileName);
    }

    public static function CreateThumbnail($sourceFileName, $targetFileNameWithoutExtension, $targetWidth, $targetHeight, $targetZoom, &$thumbnailCreatedSuccessfully)
    {
      if(self::ImageIsResizable($sourceFileName))
      {
        $sourceImage = self::ImageCreateFromGeneral($sourceFileName);
        $targetFileName = $targetFileNameWithoutExtension .".". self::GetExtension($sourceFileName);

        $sourceWidth = ImageSX($sourceImage);
        $sourceHeight = ImageSY($sourceImage);

        $targetImage = ImageCreateTrueColor($targetWidth, $targetHeight);

        if($targetZoom * $sourceWidth < $targetWidth) $targetZoom = $targetWidth / $sourceWidth;
        if($targetZoom * $sourceHeight < $targetHeight) $targetZoom = $targetHeight / $sourceHeight;

        $sourceClippedWidth = $targetWidth / $targetZoom;
        $sourceClippedHeight = $targetHeight / $targetZoom;
        $sourceCenterX = $sourceWidth / 2;
        $sourceCenterY = $sourceHeight / 2;

        $sourceX = $sourceCenterX - $sourceClippedWidth / 2;
        $sourceY = $sourceCenterY - $sourceClippedHeight / 2;

        ImageCopyResampled(
          $targetImage,
          $sourceImage,
          0,
          0,
          $sourceX,
          $sourceY,
          $targetWidth,
          $targetHeight,
          $sourceClippedWidth,
          $sourceClippedHeight);

        ImageDestroy($sourceImage);

        @ImageJpeg($targetImage, $targetFileName);
        ImageDestroy($targetImage);
        $thumbnailCreatedSuccessfully = true;
      }
      else
      {
        // make thumbnail displaying standard 64x64 image icon
        $sourceImage = ImageCreateFromPng("gfx/imageFileIcon.png");

        $sourceWidth = ImageSX($sourceImage);
        $sourceHeight = ImageSY($sourceImage);

        $targetImage = ImageCreateTrueColor($targetWidth, $targetHeight);
        $targetFileName = $targetFileNameWithoutExtension .".png";

        $white = imagecolorallocate($targetImage, 255, 255, 255);
        ImageFilledRectangle($targetImage, 0, 0, $targetWidth - 1, $targetHeight - 1, $white);
        imagecolordeallocate($targetImage, $white);

        $targetCenterX = $targetWidth / 2;
        $targetCenterY = $targetHeight / 2;

        $targetX = $targetCenterX - $sourceWidth / 2;
        $targetY = $targetCenterY - $sourceHeight / 2;

        ImageCopy($targetImage,$sourceImage, $targetX, $targetY, 0, 0, $sourceWidth, $sourceHeight);

        ImageDestroy($sourceImage);

        @ImagePng($targetImage, $targetFileName);
        ImageDestroy($targetImage);
        $thumbnailCreatedSuccessfully = false;
      }
      return $targetFileName;
    }

    public static function CreateTopbar()
    {
      $isLoggedIn = (Helper::IsLoggedInUser() && Helper::GetLoggedInUser()->ID == getCurrentUser()->ID);
      ?>
      <div id="topbar">
        <div class="left">
          <a href="index.php?<?php print Helper::CreateQuerystring(getCurrentUser())?>"><?php printf(__("DOMA_FOR_X"), getCurrentUser()->FirstName ." ". getCurrentUser()->LastName); ?></a>
          <span class="separator">|</span>
          <?php if(!$isLoggedIn) { ?>
            <a href="login.php?<?php print Helper::CreateQuerystring(getCurrentUser())?>"><?php print __("LOGIN")?></a>
          <?php } else { ?>
            <a href="edit_map.php?<?php print Helper::CreateQuerystring(getCurrentUser())?>"><?php print __("ADD_MAP"); ?></a>
            <span class="separator">|</span>
            <a href="edit_user.php?<?php print Helper::CreateQuerystring(getCurrentUser())?>"><?php print __("USER_PROFILE"); ?></a>
            <span class="separator">|</span>
            <a href="login.php?<?php print Helper::CreateQuerystring(getCurrentUser())?>&amp;action=logout"><?php print __("LOGOUT"); ?></a>
          <?php } ?>
        </div>
        <div class="right">
          <a href="users.php"><?php print __("ALL_USERS"); ?></a>
          <span class="separator">|</span>
          <?php
          if(SHOW_LANGUAGES_IN_TOPBAR=="1")
          {
            Helper::ShowLanguages();?>
            <span class="separator">|</span>
          <?php } ?>
          <a href="http://www.matstroeng.se/doma/?version=<?php print DOMA_VERSION; ?>"><?php printf(__("DOMA_VERSION_X"), DOMA_VERSION); ?></a>
        </div>
        <div class="clear"></div>
      </div>
      <?php
    }

    public static function CreateUserListTopbar()
    {
      $isLoggedIn = Helper::IsLoggedInAdmin();
      ?>
      <div id="topbar">
        <div class="left">
          <a href="users.php"><?php print _SITE_TITLE; ?></a>
          <span class="separator">|</span>
          <?php if(!$isLoggedIn) { ?>
            <a href="admin_login.php"><?php print __("ADMIN_LOGIN"); ?></a>
          <?php } else { ?>
            <a href="edit_user.php?mode=admin"><?php print __("ADD_USER"); ?></a>
            <span class="separator">|</span>
            <a href="admin_login.php?action=logout"><?php print __("ADMIN_LOGOUT"); ?></a>
          <?php } ?>
        </div>
        <div class="right">
          <?php
          if(SHOW_LANGUAGES_IN_TOPBAR=="1")
          {
            Helper::ShowLanguages();?>
            <span class="separator">|</span>
          <?php } ?>
          <a href="http://www.matstroeng.se/doma/?version=<?php print DOMA_VERSION?>"><?php printf(__("DOMA_VERSION_X"), DOMA_VERSION); ?></a>
        </div>
        <div class="clear"></div>
      </div>
      <?php
    }

    public static function LogUsage($action, $data)
    {
      @file(DOMA_SERVER ."?url=". urlencode(self::GlobalPath("")) ."&action=". urlencode($action) ."&data=". urlencode($data));
    }

    public static function SendEmail($fromName, $toEmail, $subject, $body)
    {
      if(ADMIN_EMAIL == "email@yourdomain.com") return false; // the address is the default one, don't send
      $header = "From: ". utf8_decode($fromName) . " <" . ADMIN_EMAIL . ">\r\n";
      ini_set('sendmail_from', ADMIN_EMAIL);
      $result = @mail($toEmail, utf8_decode($subject), utf8_decode($body), $header);
      return $result;
    }

    public static function IsValidEmailAddress($emailAddress)
    {
      return preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $emailAddress);
    }

    public static function CreatePassword($length)
    {
      $password = "";
      $chars = "abcdefghijkmnpqrstuvwxyz23456789";
      for($i=0; $i<$length; $i++)
      {
        $password .= substr($chars, rand(0, strlen($chars)-1), 1);
      }
      return $password;
    }

    public static function WriteToLog($message)
    {
      if(defined("LOG"))
      {
        $microtime = explode(" ", microtime());
        $timeString = date("Y-m-d H:i:s") . substr($microtime[0], 1, 4);
        $fp = fopen(self::LocalPath(LOG_FILE_NAME), "a");
        fwrite($fp, $timeString ." ". $message ."\n");
        fclose($fp);
      }
    }

    public static function ClearLog()
    {
      if(defined("LOG"))
      {
        unlink(LOG_FILE_NAME);
      }
    }

    public static function DeleteFiles($path, $pattern)
    {
      if(substr($path, strlen($path) - 1, 1) != "/") $path = $path ."/";
      $dirs = glob($path ."*");
      $files = glob($path . $pattern);

      if(is_array($files))
      {
        foreach($files as $file)
        {
          if(is_file($file))
          {
            unlink($file);
          }
        }
      }
      if(is_array($dirs))
      {
        foreach($dirs as $dir)
        {
          if(is_dir($dir))
          {
            $dir = basename($dir) . "/";
            self::DeleteFiles($path . $dir, $pattern);
          }
        }
      }
    }

    public static function SaveTemporaryFileFromUploadedFile($uploadedFile)
    {
      $temporaryDirectory = Helper::LocalPath(TEMP_FILE_PATH ."/");
      $fileName = null;
      $error = null;
      if($uploadedFile['name'])
      {
        $extension = Helper::GetExtension($uploadedFile['name']);
        $fileName = $temporaryDirectory . rand(0, 1000000000) .".". $extension;
        if(!move_uploaded_file($uploadedFile['tmp_name'], $fileName))
        {
          $error = "couldNotCopyUploadedFile";
        }
      }
      return array("fileName" => $fileName, "error" => $error);
    }

    public static function SaveTemporaryFileFromFileData($fileData, $extension)
    {
      $temporaryDirectory = Helper::LocalPath(TEMP_FILE_PATH ."/");
      $fileName = $temporaryDirectory . rand(0, 1000000000) .".". $extension;
      $fp = fopen($fileName, "w");
      fwrite($fp, $fileData);
      fclose($fp);
      return array("fileName" => $fileName, "error" => $error);
    }

    public static function ShowLanguages()
    {
      $langs = explode("|", LANGUAGES_AVAILABLE);
      if(is_array($langs))
      {
        print __("LANGUAGE").": ";
        print '<span id="currentLanguage">';
        $langcode = Session::GetLanguageCode();
        print self::CreateLanguageImageAndText($langcode);
        print '<span id="languages">';
        foreach ($langs as $lang)
        {
          self::CreateLanguageLink($lang);
        }
        print '</span>';
        print '</span>';
      }
    }

    private static function CreateLanguageLink($lang)
    {
      $get = $_GET;
      list($languageName, $languageCode) = explode(";", $lang);
      $get['lang'] = $languageCode;
      $queryString = http_build_query($get);
      print '<a href="?'. $queryString .'">'. self::CreateLanguageImageAndText($languageCode, $languageName) ."</a>";
    }

    private static function CreateLanguageImageAndText($languageCode, $languageName = null)
    {
      if($languageName == null)
      {
        $items = @explode("|", LANGUAGES_AVAILABLE);
        foreach($items as $item)
        {
          list($ln, $lc) = explode(";", $item);
          if($lc == $languageCode)
          {
            $languageName = $ln;
            break;
          }
        }
      }

      return '<img src="gfx/flag/'. $languageCode. '.png" alt="'. hsc($languageName). '" title="'. hsc($languageName) .'">'.
              $languageName;
    }

    public static function ConvertToTime($value, $format)
    {
      $leading = "";
      if($format=="MM:SS")
      {
        if(is_numeric($value))
        {
          if($value%60<10) $leading = "0";
          return intval($value/60).":".$leading.$value%60;
        }
      }
      if($format=="HH:MM:SS")
      {
        if(is_numeric($value))
        {
          if($value%60<10) $leading = "0";
          if((intval($value/60)%60)<10) $leading1 = "0";
          return intval($value/3600) .":". $leading1 . intval($value/60)%60 .":". $leading . $value%60 ; //result as HH:MM:SS
        }
      }
    }

    public static function ClickableLink($text = '')
    {
      $text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:", $text);
      $ret = ' ' . $text;
      $ret = preg_replace("#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret);

      $ret = preg_replace("#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret);
      $ret = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);
      $ret = substr($ret, 1);
      return $ret;
    }

    public static function MapIsProtected(Map $map)
    {
      return !($map->ProtectedUntil == null || $map->ProtectedUntil <= gmdate("Y-m-d H:i:s") || $map->UserID == self::GetLoggedInUserID());
    }

    public static function GetProtectedFileName($unprotectedFileName, $randomString)
    {
      if($unprotectedFileName == null || strlen($unprotectedFileName) == 0) return $unprotectedFileName;
      $atoms = explode(".", $unprotectedFileName);
      $resultAtoms = array();
      for($i=0; $i<count($atoms); $i++)
      {
        $resultAtoms[] = $atoms[$i] . ($i == 0 ? "_". $randomString : "");
      }
      return implode(".", $resultAtoms);
    }

    public static function GetUnprotectedFileName($unprotectedFileName)
    {
      if($unprotectedFileName == null || strlen($unprotectedFileName) == 0) return $unprotectedFileName;
      $atoms = explode(".", $unprotectedFileName);
      $resultAtoms = array();
      foreach($atoms as $atom)
      {
        $pos = strpos($atom, "_");
        if($pos !== false)
        {
          $resultAtoms[] = substr($atom, 0, $pos);
        }
        else
        {
          $resultAtoms[] = $atom;
        }
      }
      return implode(".", $resultAtoms);
    }

    public static function CreateRandomString($length, $characters = "0123456789abcdef")
    {
      $numberOfCharacters = strlen($characters);
      $string = "";
      for ($i = 0; $i < $length; $i++)
      {
        $string .= $characters[mt_rand(0, $numberOfCharacters-1)];
      }
      return $string;
    }

    public static function GetOverviewMapData(Map $map, $includeRouteCoordinates, $includeTooltipMarkup, $includePersonName, $categories, $selectedCategoryId = 0)
    {
      if(!$map->IsGeocoded) return null;
      $corners = $map->GetMapCornerArray();
      $data = array();
      $data["MapId"] = $map->ID;
      $data["MapCenter"] = new QRLongLat($map->MapCenterLongitude, $map->MapCenterLatitude);
      $data["Corners"][] = new QRLongLat($corners["SW"]["Longitude"], $corners["SW"]["Latitude"]);
      $data["Corners"][] = new QRLongLat($corners["NW"]["Longitude"], $corners["NW"]["Latitude"]);
      $data["Corners"][] = new QRLongLat($corners["NE"]["Longitude"], $corners["NE"]["Latitude"]);
      $data["Corners"][] = new QRLongLat($corners["SE"]["Longitude"], $corners["SE"]["Latitude"]);
      $data["BorderColor"] = '#ff0000';
      $data["BorderWidth"] = 2;
      $data["BorderOpacity"] = 0.8;
      $data["FillColor"] = '#ff0000';
      $data["FillOpacity"] = 0.3;
      $data["RouteColor"] = '#ff0000';
      $data["RouteWidth"] = 3;
      $data["RouteOpacity"] = 1;
      $data["SelectedBorderColor"] = '#0000ff';
      $data["SelectedFillColor"] = '#0000ff';
      if($includeRouteCoordinates)
      {
        $ed = $map->GetQuickRouteJpegExtensionData(false);
        $data["RouteCoordinates"] = $ed->Sessions[0]->Route->GetWaypointPositionsAsArray(5, 6);
      }

      $info = "";
      $hscNameAndDate = "";
      $disciplineAndRelayLeg = "";

      if(__("SHOW_MAP_AREA_NAME") || __("SHOW_ORGANISER") || __("SHOW_COUNTRY"))
      {
        $atoms = array();
        if(__("SHOW_MAP_AREA_NAME") && $map->MapName) $atoms[] = $map->MapName;
        if(__("SHOW_ORGANISER") && $map->Organiser) $atoms[] = $map->Organiser;
        if(__("SHOW_COUNTRY") && $map->Country) $atoms[] = $map->Country;
        $mapAreaOrganiserCountry = @implode(", ", $atoms);
        $info .= "<br/>". hsc($mapAreaOrganiserCountry);
      }

      if($selectedCategoryId == 0) $info .= "<br/>". __("CATEGORY") .": ". $categories[$map->CategoryID]->Name;

      if(__("SHOW_DISCIPLINE"))
      {
         $info .= "<br/>" . hsc($map->Discipline);
         if(__("SHOW_RELAY_LEG") && $map->RelayLeg) $disciplineAndRelayLeg .= ', '. __("RELAY_LEG_LOWERCASE") .' '. hsc($map->RelayLeg);
      }

      if($includeTooltipMarkup)
      {
        $hscNameAndDate = hsc($includePersonName ? $map->GetUser()->FirstName ." ". $map->GetUser()->LastName .", " : "").
          hsc($map->Name .' ('. date(__("DATE_FORMAT"), self::StringToTime($map->Date, true)) .')');
        $data["TooltipMarkup"] =
          '<div>'.
            '<img src="'. self::GetThumbnailImage($map) .'" alt="'. $hscNameAndDate .'" '.
                 'height="'. THUMBNAIL_HEIGHT .'" width="'. THUMBNAIL_WIDTH .'" />'.
          '</div>'.
          '<div>'.
            $hscNameAndDate.
            $info.
          '</div>';

        $data["Url"] = $map->MapImage ? 'show_map.php?'. self::CreateQuerystring($map->GetUser(), $map->ID) : "";
      }
      return $data;
    }
     
    public static function GoogleAnalytics()
    {
      if(USE_GA == "1")
      {
        ?>
        <script type="text/javascript">

          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', '<?php print GA_TRACKER; ?>']);
          _gaq.push(['_trackPageview']);

          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();

        </script>
        <?php
      }
    }
    public static function GetMapsForRerunRequest()
    {
      if(USE_3DRERUN == "1")
      {
        $maps = DataAccess::GetAllMaps();
        $ret = array();
        foreach($maps as $map)
        {
          if((is_null($map->RerunID) || $map->RerunID==0) && $map->RerunTries < RERUN_MAX_TRIES && $map->IsGeocoded)
          {
            $user = new User();
            $user->Load($map->UserID);
            $ret[]=$map->ID.";".$user->Username; 
          }
        }
        if(count($ret)>0)
        {
          return implode(",",$ret);
        }
        else
        {
          return null;
        }
    
      }
    }
    
  }

?>