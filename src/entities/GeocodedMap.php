<?php
require_once(dirname(__FILE__) ."/../lib/Matrix.php");
require_once(dirname(__FILE__) ."/../lib/Zipfile.php");
require_once(dirname(__FILE__) ."/../lib/SimpleUnzip.php");
require_once(dirname(__FILE__) ."/../util/LinearAlgebraUtil.php");
require_once(dirname(__FILE__) ."/../util/StringUtil.php");
require_once(dirname(__FILE__) ."/KmlDocument.php");
require_once(dirname(__FILE__) ."/LatLng.php");
require_once(dirname(__FILE__) ."/Point.php");

class GeocodedMap
{
  /**
   *  The projection origin (the center coordinate of the map)
   */ 
  public $projectionOrigin;
  
  /**
   *  The 3x3 transformation matrix that translates from projected coordinates to map image pixel coordinates
   */ 
  public $transformationMatrix;

  /**
   *  The transformation matrix that translates from map image pixel coordinates to projected coordinates
   */ 
  public $inverseTransformationMatrix;

  /**
   *  The image file name on local disk.
   */ 
  public $imageFileName;

  /**
   *  The image url when exporting to a kml/kmz file. If null, $this->imageFileName name will be used.
   */ 
  public $imageUrl;
  
  /**
   *  The image file data. Can be null. In this case refer to $this->imageFileName.
   */ 
  public $imageFileData;
  
  /**
   * Loads a geocoded map from a kml/kmz file
   * @param string name of kml/kmz file
   * @access public
   */  
  public function loadFromFile($fileName)
  {
    // first determine file format from extension
    $fileFormat = strtolower(StringUtil::getExtension($fileName));
    if($fileFormat != "kml") $fileFormat = "kmz";
    
    if($fileFormat == "kml")
    {
      $kml = new KmlDocument();
      $kml->loadFromFile($fileName);
      $this->imageFileData = file_get_contents($kml->imageFileName);
    }
    else // kmz
    {
      $unzip = new SimpleUnzip($fileName);
      $kml = null;
      // find kml file
      foreach($unzip->Entries as $entry)
      {
        if(strtolower(StringUtil::getExtension($entry->Name)) == "kml")
        {
          $kml = new KmlDocument();
          $kml->loadFromString($entry->Data);
          break; 
        }
      }
      if($kml == null) return false;

      // find image file
      $this->imageFileName = null;
      $this->imageFileData = null;
      foreach($unzip->Entries as $entry)
      {
        $entryFileName = ($entry->Path != "" ? $entry->Path . "/" : "") . $entry->Name;
        if($entryFileName == $kml->imageFileName)
        {
          $this->imageFileData = $entry->Data;
          break; 
        }
      }
    }

    if($this->imageFileData == null) return false;    
    
    // get size of image
    $pixelImageCorners = $this->getPixelImageCorners();

    // calculate projection origin
    $this->projectionOrigin = new LatLng(($kml->north + $kml->south) / 2, ($kml->east + $kml->west) / 2);

    // get image corners from kml file
    $imageCornerLatLngs = $kml->getImageCornerLatLngs();
    
    // project them on flat surface
    $projectedImageCorners = array();
    $projectedImageCorners["nw"] = $imageCornerLatLngs["nw"]->project($this->projectionOrigin);
    $projectedImageCorners["se"] = $imageCornerLatLngs["se"]->project($this->projectionOrigin);
    
    // calculate transformation matrix
    $this->transformationMatrix = self::calculateTransformationMatrixFromTwoCoordinatePairs(
      $projectedImageCorners["nw"], $pixelImageCorners["nw"], 
      $projectedImageCorners["se"], $pixelImageCorners["se"]);
    // calculate inverse transformation matrix
    $this->inverseTransformationMatrix = LinearAlgebraUtil::inverse($this->transformationMatrix);
      
    return true;
  }

  /**
   * Saves the geocoded map to a kmz file
   * @param string name of kmz file
   * @access public
   */  
  public function saveToFile($fileName, $title, $fileFormat = "kmz")
  {
    $fp = fopen($fileName, "w+");  
    fwrite($fp, $this->saveToString($title, $fileFormat));
    fclose($fp);    
  }
  
  /**
   * Returns the geocoded map as a kmz string
   * @access public
   */  
  public function saveToString($title, $fileFormat = "kmz")
  {
    $box = $this->getKmlLatLonBox($this->getPixelImageCorners(), $this->projectionOrigin, $this->inverseTransformationMatrix);

    $kml = new KmlDocument();
    $kml->title = $title;
    $kml->north = $box->north;
    $kml->south = $box->south;
    $kml->west = $box->west;
    $kml->east = $box->east;
    $kml->rotation = $box->rotation;
    
    if(strtolower($fileFormat) == "kml")
    {
      $kml->imageFileName = $this->imageUrl != null ? $this->imageUrl : $this->imageFileName;
      return $kml->saveToString();
    }
    else // kmz
    {
      $kml->imageFileName = $this->getImageFileNameInKml();
      $kmlString = $kml->saveToString();
      
      $zip = new Zipfile();
      $zip->addString($kmlString, "doc.kml");
      $zip->addFile($this->imageFileName, $this->getImageFileNameInKml());

      return $zip->saveToString();
    }
  }

  /*
  IN
  - n LatLngs (from Google map)
  - n Points (from omap)
  - omap image file
  
  OUT
  - kmz file
  */
  
  // TODO: extend to more than two coordinate pairs
  // use algorithm in QuickRoute.BusinessEntities.SessionCollection.CalculateAverageTransformationMatrix
  // started implementing in CalculateAverageTransformationMatrix below
  public function createFromCoordinatePairs($latLngs, $points, $imageFileName, $imageUrl = null) 
  {
    if(count($latLngs) != 2 || count($points) != 2) die("Wrong number of latlngs / points");
    
    $this->imageFileName = $imageFileName;
    $this->imageUrl = $imageUrl;

    // FIRST PASS
    // calculate projection origin as an average of $latLngs
    $latSum = 0;
    $lngSum = 0;
    for($i=0; $i<count($latLngs); $i++)
    {
      $latSum += $latLngs[$i]->lat;
      $lngSum += $latLngs[$i]->lng;
    }
    $this->projectionOrigin = new LatLng($latSum / count($latLngs), $lngSum / count($latLngs));
    $this->calculateTransformationMatrix($latLngs, $points, $this->projectionOrigin);
    
    $box = $this->getKmlLatLonBox($this->getPixelImageCorners(), $this->projectionOrigin, $this->inverseTransformationMatrix);
    
    // SECOND PASS
    // calculate projection origin as center of the box in previous pass
    $this->projectionOrigin = new LatLng(($box->north + $box->south)/2, ($box->east + $box->west)/2);
    $this->calculateTransformationMatrix($latLngs, $points, $this->projectionOrigin);
  }

  public function CalculateAverageTransformationMatrix($latLngs, $points)
  {
    $m = count($latLngs);
    if ($m == 0) return null;
    $n = 4;
    $XtX = array(array(0,0,0,0),array(0,0,0,0),array(0,0,0,0),array(0,0,0,0)); // n x n
    $Xty = array(array(0),array(0),array(0),array(0)); // n x 1
    $numberOfUnknowns = $m;

    for ($i = 0; $i < $m; $i++)
    {
      $p = $latLngs[$i]->project($this->projectionOrigin); // projected point on earth (metres)
      $q = $points[$i]; // point on map image (pixels)

      $XtX[0][0] = $XtX[0][0] + ($p->x * $p->x + $p->y * $p->y);
      $XtX[0][2] = $XtX[0][2] + $p->x;
      $XtX[0][3] = $XtX[0][3] - $p->y;
      $XtX[1][1] = $XtX[1][1] + ($p->x * $p->x + $p->y * $p->y);
      $XtX[1][2] = $XtX[1][2] + $p->y;
      $XtX[1][3] = $XtX[1][3] + $p->x;
      $XtX[2][0] = $XtX[2][0] + $p->x;
      $XtX[2][1] = $XtX[2][1] + $p->y;
      $XtX[2][2] = $XtX[2][2] + 1;
      $XtX[3][0] = $XtX[3][0] - $p->y;
      $XtX[3][1] = $XtX[3][1] + $p->x;
      $XtX[3][3] = $XtX[3][3] + 1;

      $Xty[0][0] = $Xty[0][0] + ($q->x * $p->x - $q->y * $p->y);
      $Xty[1][0] = $Xty[1][0] + ($q->x * $p->y + $q->y * $p->x);
      $Xty[2][0] = $Xty[2][0] + $q->x;
      $Xty[3][0] = $Xty[3][0] + $q->y;
    }

    $B = $XtX->qrd()->solve($Xty);

    $T = array(); // 3 x 3

    $T[0][0] = $B[0][0];
    $T[0][1] = $B[1][0];
    $T[0][2] = $B[2][0];
    $T[1][0] = $B[1][0];
    $T[1][1] = -$B[0][0];
    $T[1][2] = $B[3][0];
    $T[2][0] = 0;
    $T[2][1] = 0;
    $T[2][2] = 1;
    return $T;
  }  

  public function getImageSize()
  {
    if ($this->imageFileName != null)
    {
      $size = getimagesize($this->imageFileName);
      $size = array("width" => $size[0], "height" => $size[1]);
    }
    else if($this->imageFileData != null)
    {
      $image = imagecreatefromstring($this->imageFileData);
      $size = array("width" => imagesx($image), "height" => imagesy($image));
      imagedestroy($image);
    }
    else
    {
      $size = null; 
    }
    return $size;
  }
  
  private function calculateTransformationMatrix($latLngs, $points, $projectionOrigin)
  {
    // calculate projected points
    $projectedPoints = array();
    for($i=0; $i<count($latLngs); $i++)
    {
      $projectedPoints[$i] = $latLngs[$i]->project($projectionOrigin);
    }
    
    // calculate transformation matrix
    $this->transformationMatrix = self::calculateTransformationMatrixFromTwoCoordinatePairs($projectedPoints[0], $points[0], $projectedPoints[1], $points[1]);

    // calculate inverse transformation matrix
    $this->inverseTransformationMatrix = LinearAlgebraUtil::inverse($this->transformationMatrix);
  }

  
  private function getKmlLatLonBox($pixelImageCorners, $projectionOrigin, $inverseTransformationMatrix)
  {
    // translate corner pixels of map image to projected points
    $projectedCorners = array();
    $projectedCorners["nw"] = LinearAlgebraUtil::transformPoint($pixelImageCorners["nw"], $inverseTransformationMatrix);
    $projectedCorners["ne"] = LinearAlgebraUtil::transformPoint($pixelImageCorners["ne"], $inverseTransformationMatrix);
    $projectedCorners["sw"] = LinearAlgebraUtil::transformPoint($pixelImageCorners["sw"], $inverseTransformationMatrix);
    $projectedCorners["se"] = LinearAlgebraUtil::transformPoint($pixelImageCorners["se"], $inverseTransformationMatrix);
    
    // get rotation
    $dx = $projectedCorners["nw"]->x - $projectedCorners["sw"]->x;
    $dy = $projectedCorners["nw"]->y - $projectedCorners["sw"]->y;

    $mapRotation = $dy == 0 ? 0 : -atan($dx/$dy); // in radians
    
    $corners = array();
    $corners["nw"] = $projectedCorners["nw"]->deproject($projectionOrigin);
    $corners["ne"] = $projectedCorners["ne"]->deproject($projectionOrigin);
    $corners["sw"] = $projectedCorners["sw"]->deproject($projectionOrigin);
    $corners["se"] = $projectedCorners["se"]->deproject($projectionOrigin);
    
    $mapCenter = new LatLng($corners["nw"]->lat / 4 + $corners["ne"]->lat / 4 + $corners["sw"]->lat / 4 + $corners["se"]->lat / 4, 
                            $corners["nw"]->lng / 4 + $corners["ne"]->lng / 4 + $corners["sw"]->lng / 4 + $corners["se"]->lng / 4);

    $projectedMapCenter = $mapCenter->project($projectionOrigin);
    
    $projectedRotatedCorners = array();
    $projectedRotatedCorners["nw"] = LinearAlgebraUtil::rotate($projectedCorners["nw"], $projectedMapCenter, $mapRotation);
    $projectedRotatedCorners["ne"] = LinearAlgebraUtil::rotate($projectedCorners["ne"], $projectedMapCenter, $mapRotation);
    $projectedRotatedCorners["sw"] = LinearAlgebraUtil::rotate($projectedCorners["sw"], $projectedMapCenter, $mapRotation);
    $projectedRotatedCorners["se"] = LinearAlgebraUtil::rotate($projectedCorners["se"], $projectedMapCenter, $mapRotation);
    
    $rotatedCorners = array();
    $rotatedCorners["nw"] = $projectedRotatedCorners["nw"]->deproject($projectionOrigin);
    $rotatedCorners["ne"] = $projectedRotatedCorners["ne"]->deproject($projectionOrigin);
    $rotatedCorners["sw"] = $projectedRotatedCorners["sw"]->deproject($projectionOrigin);
    $rotatedCorners["se"] = $projectedRotatedCorners["se"]->deproject($projectionOrigin);
    
    // get latlng bounding box
    $box = new KmlLatLonBox();
    $box->north = max($rotatedCorners["nw"]->lat, $rotatedCorners["ne"]->lat, $rotatedCorners["sw"]->lat, $rotatedCorners["se"]->lat);
    $box->south = min($rotatedCorners["nw"]->lat, $rotatedCorners["ne"]->lat, $rotatedCorners["sw"]->lat, $rotatedCorners["se"]->lat);
    $box->east = max($rotatedCorners["nw"]->lng, $rotatedCorners["ne"]->lng, $rotatedCorners["sw"]->lng, $rotatedCorners["se"]->lng);
    $box->west = min($rotatedCorners["nw"]->lng, $rotatedCorners["ne"]->lng, $rotatedCorners["sw"]->lng, $rotatedCorners["se"]->lng);
    $box->rotation = $mapRotation; // still in radians, will get converted to degrees later on

    return $box;
  }
  
  private function getImageFileNameInKml()
  {
    return "map." . StringUtil::getExtension($this->imageFileName);
  }

  private function getPixelImageCorners()
  {
    $size = $this->getImageSize();
    $width = $size["width"];
    $height = $size["height"]; 
        
    $pixelImageCorners = array();
    $pixelImageCorners["nw"] = new Point(0, 0);
    $pixelImageCorners["ne"] = new Point($width-1, 0);
    $pixelImageCorners["sw"] = new Point(0, $height-1);
    $pixelImageCorners["se"] = new Point($width-1, $height-1);    
    
    return $pixelImageCorners;
  }

  // p: projected points on earth
  // q: pixel locations on map image
  public static function calculateTransformationMatrixFromTwoCoordinatePairs(Point $p0, Point $q0, Point $p1, Point $q1)
  {
    // note that we need to mirror q y pixel values in x axis
    $angleDifferece = LinearAlgebraUtil::getAngle(new Point($p1->x - $p0->x, $p1->y - $p0->y), new Point($q1->x - $q0->x, -$q1->y + $q0->y));
    $lengthQ = LinearAlgebraUtil::distance($q0, $q1);
    $lengthP = LinearAlgebraUtil::distance($p0, $p1);
    $scaleFactor = $lengthP == 0 ? 0 : $lengthQ / $lengthP;
    $cos = cos($angleDifferece);
    $sin = sin($angleDifferece);
    
    // translation to origo in metric space
    $a = array();
    $a[0][0] = 1;
    $a[0][1] = 0;
    $a[0][2] = -$p0->x;
    $a[1][0] = 0;
    $a[1][1] = 1;
    $a[1][2] = -$p0->y;
    $a[2][0] = 0;
    $a[2][1] = 0;
    $a[2][2] = 1;

    // rotation
    $b = array();
    $b[0][0] = $cos;
    $b[0][1] = -$sin;
    $b[0][2] = 0;
    $b[1][0] = $sin;
    $b[1][1] = $cos;
    $b[1][2] = 0;
    $b[2][0] = 0;
    $b[2][1] = 0;
    $b[2][2] = 1;

    // scaling, note that we need to mirror y scale around x axis
    $c = array();
    $c[0][0] = $scaleFactor;
    $c[0][1] = 0;
    $c[0][2] = 0;
    $c[1][0] = 0;
    $c[1][1] = -$scaleFactor;
    $c[1][2] = 0;
    $c[2][0] = 0;
    $c[2][1] = 0;
    $c[2][2] = 1;

    // translation from origo to pixel space
    $d = array();
    $d[0][0] = 1;
    $d[0][1] = 0;
    $d[0][2] = $q0->x;
    $d[1][0] = 0;
    $d[1][1] = 1;
    $d[1][2] = $q0->y;
    $d[2][0] = 0;
    $d[2][1] = 0;
    $d[2][2] = 1;

    return LinearAlgebraUtil::multiply(LinearAlgebraUtil::multiply(LinearAlgebraUtil::multiply($d, $c), $b), $a);
  }  
}

class KmlLatLonBox
{
  public $north;
  public $south;
  public $east;
  public $west;
  public $rotation;
}

?>
