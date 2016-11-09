<?php
  require_once(dirname(__FILE__) ."/database_object.php");
  require_once(dirname(__FILE__) ."/data_access.php");
  require_once(dirname(__FILE__) ."/../entities/GeocodedMap.php");
  require_once(dirname(__FILE__) ."/../include/quickroute_jpeg_extension_data.php");
  require_once(dirname(__FILE__) ."/../lib/Matrix.php");
  require_once(dirname(__FILE__) ."/../entities/LatLng.php");
  require_once(dirname(__FILE__) ."/../entities/Point.php");

  class Map extends DatabaseObject
  {
    protected $DBTableName = DB_MAP_TABLE;
    protected $ClassName = "Map";
    public $Data = array(
      "ID" => 0,
      "UserID" => 0,
      "CategoryID" => 0,
      "Date" => 0,
      "Name" => "",
      "Organiser" => "",
      "Country" => "",
      "Discipline" => "",
      "RelayLeg" => "",
      "MapName" => "",
      "ResultListUrl" => "",
      "MapImage" => "",
      "BlankMapImage" => "",
      "ThumbnailImage" => "",
      "Comment" => "",
      "Views" => 0,
      "LastChangedTime" => null,
      "CreatedTime" => null,
      "IsGeocoded" => 0,
      "MapCenterLatitude" => null,
      "MapCenterLongitude" => null,
      "MapCorners" => null,
      "SessionStartTime" => null,
      "SessionEndTime" => null,
      "Distance" => null,
      "StraightLineDistance" => null,
      "ElapsedTime" => null,
      "ProtectedUntil" => null,
      "RerunID" => 0,
      "RerunTries" => 0
    );
    private $User;
    private $Category;
    private $QuickRouteJpegExtensionData;
    private $QuickRouteJpegExtensionDataNotPresent;
    private $Exif;
    private $ExifNotPresent;


    public function CreateResultListUrl()
    {
      if($this->ResultListUrl != "")
      {
        if(strtolower(substr($this->ResultListUrl, 0, 4)) != "http")
        {
          return "http://". $this->ResultListUrl;
        }
        return $this->ResultListUrl;
      }
      return "";
    }

    public function GetUser()
    {
      if(!$this->User) $this->User = DataAccess::GetUserByID($this->UserID);
      return $this->User;
    }

    public function SetUser($user)
    {
      $this->User = $user;
    }

    public function GetCategory()
    {
      if(!$this->Category) $this->Category = DataAccess::GetCategoryByID($this->CategoryID);
      return $this->Category;
    }

    public function SetCategory($category)
    {
      $this->Category = $category;
    }

    public function GetMapCornerArray()
    {
      if($this->IsGeocoded)
      {
        //$ed = $this->GetQuickRouteJpegExtensionData();
        $arr = explode(",", $this->MapCorners);
        return array("SW" => array("Longitude" => $arr[0], "Latitude" => $arr[1]),
                     "NW" => array("Longitude" => $arr[2], "Latitude" => $arr[3]),
                     "NE" => array("Longitude" => $arr[4], "Latitude" => $arr[5]),
                     "SE" => array("Longitude" => $arr[6], "Latitude" => $arr[7]));
      }
      return null;
    }

    public function GetQuickRouteJpegExtensionData($calculate = true, $forceFetch = false)
    {
      if(!$this->IsGeocoded && !$forceFetch) $this->QuickRouteJpegExtensionDataNotPresent = true;
      if($this->QuickRouteJpegExtensionDataNotPresent) return null;
      
      // is there a cached value?
      if($this->QuickRouteJpegExtensionData != null) return $this->QuickRouteJpegExtensionData; // yes, use it
      // no cached value, get it
      $this->QuickRouteJpegExtensionData = new QuickRouteJpegExtensionData(Helper::LocalPath(MAP_IMAGE_PATH ."/" . $this->MapImage));
      if(isset($this->QuickRouteJpegExtensionData) && $this->QuickRouteJpegExtensionData->IsValid) 
      {
        if($calculate) $this->QuickRouteJpegExtensionData->Calculate();
        return $this->QuickRouteJpegExtensionData;
      }
      else
      {
        // this should not happen
        $this->QuickRouteJpegExtensionDataNotPresent = true;
        return null;
      }
    }

    public function GetExifData()
    {
      if(!$this->Exif && !$this->ExifNotPresent)
      {
        $this->Exif = @exif_read_data(Helper::GetMapImage($map), 0, true);
        $this->ExifNotPresent = ($this->Exif == null);
      }
      return $this->Exif;
    }

    public function GetExifGpsData()
    {
      $exif = $this->GetExifData();
      if($exif["GPS"])
      {
        $coord = $exif["GPS"]["GPSLongitude"];
        $ds = explode("/", $coord[0]);
        $ms = explode("/", $coord[1]);
        $ss = explode("/", $coord[2]);
        $lon = $ds[0] / $ds[1] +
               $ms[0] / $ms[1] / 60 +
               $ss[0] / $ss[1] / 3600;
        if($exif["GPS"]["GPSLongitudeRef"] == "W") $lon = -$lon;

        $coord = $exif["GPS"]["GPSLatitude"];
        $ds = explode("/", $coord[0]);
        $ms = explode("/", $coord[1]);
        $ss = explode("/", $coord[2]);
        $lat = $ds[0] / $ds[1] +
               $ms[0] / $ms[1] / 60 +
               $ss[0] / $ss[1] / 3600;
        if($exif["GPS"]["GPSLatitudeRef"] == "S") $lat = -$lat;

        return array("Longitude" => $lon, "Latitude" => $lat);
      }
      return null;
    }

    public function AddGeocoding()
    {
      $ed = $this->GetQuickRouteJpegExtensionData(true, true);
      if(isset($ed) && $ed->IsValid)
      {
        $this->MapCenterLatitude = (
          min($ed->MapCornerPositions["SW"]->Latitude, $ed->MapCornerPositions["SE"]->Latitude) +
          max($ed->MapCornerPositions["NW"]->Latitude, $ed->MapCornerPositions["NE"]->Latitude)) / 2;
        $this->MapCenterLongitude = (
          min($ed->MapCornerPositions["SW"]->Longitude, $ed->MapCornerPositions["NW"]->Longitude) +
          max($ed->MapCornerPositions["SE"]->Longitude, $ed->MapCornerPositions["NE"]->Longitude)) / 2;
        $this->MapCorners =
          $ed->MapCornerPositions["SW"]->Longitude .",".
          $ed->MapCornerPositions["SW"]->Latitude .",".
          $ed->MapCornerPositions["NW"]->Longitude .",".
          $ed->MapCornerPositions["NW"]->Latitude .",".
          $ed->MapCornerPositions["NE"]->Longitude .",".
          $ed->MapCornerPositions["NE"]->Latitude .",".
          $ed->MapCornerPositions["SE"]->Longitude .",".
          $ed->MapCornerPositions["SE"]->Latitude;
        $this->SessionStartTime = gmdate("Y-m-d H:i:s", $ed->Sessions[0]->GetStartTime());
        $this->SessionEndTime = gmdate("Y-m-d H:i:s", $ed->Sessions[0]->GetEndTime());
        $this->Distance = $ed->Sessions[0]->Route->Distance;
        $this->StraightLineDistance = $ed->Sessions[0]->StraightLineDistance;
        $this->ElapsedTime = $ed->Sessions[0]->Route->ElapsedTime;
        $this->IsGeocoded = 1;
      }
      else
      {
        $this->MapCenterLatitude = null;
        $this->MapCenterLongitude = null;
        $this->MapCorners = null;
        $this->SessionStartTime = null;
        $this->SessionEndTime = null;
        $this->Distance = null;
        $this->StraightLineDistance = null;
        $this->ElapsedTime = null;
        $this->IsGeocoded = 0;
      }
    }


    public function CreateKmlString($localMapImagePath, $globalMapImagePath, $format = "kmz")
    {
      if(!$this->IsGeocoded) return null;

      $ed = $this->GetQuickRouteJpegExtensionData();
      
      $size = $this->GetMapImageSize();
      $latLngs = array(
        new LatLng($ed->ImageCornerPositions["NW"]->Latitude, $ed->ImageCornerPositions["NW"]->Longitude),
        new LatLng($ed->ImageCornerPositions["SE"]->Latitude, $ed->ImageCornerPositions["SE"]->Longitude));
      $points = array(
        new Point(0, 0),
        new Point($size["Width"]-1, $size["Height"]-1));
      $geocodedMap = new GeocodedMap();
      $geocodedMap->createFromCoordinatePairs($latLngs, $points, $localMapImagePath ."/". $this->MapImage, $globalMapImagePath ."/". $this->MapImage);
      $title = $this->GetUser()->FirstName ." ". $this->GetUser()->LastName .": " . $this->Name .' ('. date(__("DATE_FORMAT"), Helper::StringToTime($this->Date, true)) .")";
      return $geocodedMap->saveToString($title, $format);
    }
    
    public function GetMapImageSize()
    {
      $size = getimagesize(Helper::LocalPath(MAP_IMAGE_PATH ."/" . $this->MapImage));
      return array("Width" => $size[0], "Height" => $size[1]);
    }
    
    public function GetDistanceToLongLat($longitude, $latitude)
    {
      if(!$this->IsGeocoded) return null;

      $pi180 = M_PI/180;
      $latR = $latitude*$pi180;
      $lonR = $longitude*$pi180;
      return acos(sin($this->MapCenterLatitude*$pi180) * sin($latR) +
                  cos($this->MapCenterLatitude*$pi180) * cos($latR) * cos($lonR-$this->MapCenterLongitude*$pi180)) *
             6378200;
    }

  }


?>
