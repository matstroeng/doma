<?php
require_once(dirname(__FILE__) ."/LatLng.php");
require_once(dirname(__FILE__) ."/../util/LinearAlgebraUtil.php");

class KmlDocument
{
  public $title;  
  public $north;  
  public $south;  
  public $east;  
  public $west;  
  public $rotation; // in radians
  public $imageFileName;

  public function loadFromFile($fileName)
  {
    $this->loadFromString(file_get_contents($fileName));
  }

  public function loadFromString($string)
  {
    $doc = new DOMDocument();
    $doc->loadXML($string);
    $xpath = new DOMXPath($doc);
    if($xmlns = self::getXmlns($doc))
    {
      $ns = "ns:";
      $xpath->registerNamespace("ns", $xmlns);
    }

    $groundOverlays = $xpath->query("//". $ns ."GroundOverlay[1]");
    
    if($groundOverlays->length > 0)
    {
      $nodes = $xpath->query("//". $ns ."GroundOverlay[1]/". $ns ."Icon/". $ns ."href");
      $this->imageFileName = $nodes->item(0)->nodeValue;
      $nodes = $xpath->query("//". $ns ."GroundOverlay[1]/". $ns ."LatLonBox");
      $this->north = 0;
      $this->south = 0;
      $this->east = 0;
      $this->west = 0;
      $this->rotation = 0;
      foreach($nodes->item(0)->childNodes as $node)
      {
        switch($node->nodeName)
        {
          case "north": $this->north = (double)$node->nodeValue; break;
          case "south": $this->south = (double)$node->nodeValue; break;
          case "east": $this->east = (double)$node->nodeValue; break;
          case "west": $this->west = (double)$node->nodeValue; break;
          case "rotation": $this->rotation = (double)$node->nodeValue / 180.0 * M_PI; break;
        }
      }
      return true;
    }
    return false;
  }
  
  public function saveToFile($fileName)
  {
    $fp = fopen($fileName, "w+");  
    fwrite($fp, $this->saveToString());
    fclose($fp);
  }

  public function saveToString()
  {
    return 
      '<?xml version="1.0" encoding="utf-8"?>' ."\r\n".
      '<kml xmlns="http://www.opengis.net/kml/2.2">' ."\r\n".
      '  <Document>' ."\r\n".
      ($this->title != null ? '  <name>'. self::xmlEntities($this->title) .'</name>' : '') ."\r\n".
      '    <Folder>' ."\r\n".
      '      <GroundOverlay>' ."\r\n".
      '        <Icon>' ."\r\n".
      '          <href>'. $this->imageFileName .'</href>' ."\r\n".
      '        </Icon>' ."\r\n".
      '        <LatLonBox>' ."\r\n".
      '          <north>'. $this->north .'</north>' ."\r\n".
      '          <south>'. $this->south .'</south>' ."\r\n".
      '          <east>'. $this->east .'</east>' ."\r\n".
      '          <west>'. $this->west .'</west>' ."\r\n".
      '          <rotation>'. ($this->rotation / M_PI * 180.0) .'</rotation>' ."\r\n". // rotation is in degrees
      '        </LatLonBox>' ."\r\n".
      '      </GroundOverlay>' ."\r\n".
      '    </Folder>' ."\r\n".
      '  </Document>' ."\r\n".
      '</kml>' ."\r\n";
      
  }
  
  public function getImageCornerLatLngs()
  {
    $corners = array();
    $rotation = -$this->rotation;
    
    $corners["ne"] = new LatLng($this->north, $this->east);
    $corners["nw"] = new LatLng($this->north, $this->west);
    $corners["sw"] = new LatLng($this->south, $this->west);
    $corners["se"] = new LatLng($this->south, $this->east);
    
    $projectionOrigin = new LatLng(($this->north + $this->south) / 2, ($this->east + $this->west) / 2);
    
    $projectedMapCenter = $projectionOrigin->project($projectionOrigin);
    
    $projectedCorners = array();
    $projectedCorners["ne"] = $corners["ne"]->project($projectionOrigin);
    $projectedCorners["nw"] = $corners["nw"]->project($projectionOrigin);
    $projectedCorners["sw"] = $corners["sw"]->project($projectionOrigin);
    $projectedCorners["se"] = $corners["se"]->project($projectionOrigin);
    
    $projectedRotatedCorners = array();
    $projectedRotatedCorners["ne"] = LinearAlgebraUtil::rotate($projectedCorners["ne"], $projectedMapCenter, $rotation);
    $projectedRotatedCorners["nw"] = LinearAlgebraUtil::rotate($projectedCorners["nw"], $projectedMapCenter, $rotation);
    $projectedRotatedCorners["sw"] = LinearAlgebraUtil::rotate($projectedCorners["sw"], $projectedMapCenter, $rotation);
    $projectedRotatedCorners["se"] = LinearAlgebraUtil::rotate($projectedCorners["se"], $projectedMapCenter, $rotation);
    
    $projectedRotatedCorners = array();
    $projectedRotatedCorners["ne"] = LinearAlgebraUtil::rotate($projectedCorners["ne"], $projectedMapCenter, $rotation);
    $projectedRotatedCorners["nw"] = LinearAlgebraUtil::rotate($projectedCorners["nw"], $projectedMapCenter, $rotation);
    $projectedRotatedCorners["sw"] = LinearAlgebraUtil::rotate($projectedCorners["sw"], $projectedMapCenter, $rotation);
    $projectedRotatedCorners["se"] = LinearAlgebraUtil::rotate($projectedCorners["se"], $projectedMapCenter, $rotation);
    
    $rotatedCorners = array();
    $rotatedCorners["nw"] = $projectedRotatedCorners["nw"]->deproject($projectionOrigin);
    $rotatedCorners["ne"] = $projectedRotatedCorners["ne"]->deproject($projectionOrigin);
    $rotatedCorners["sw"] = $projectedRotatedCorners["sw"]->deproject($projectionOrigin);
    $rotatedCorners["se"] = $projectedRotatedCorners["se"]->deproject($projectionOrigin);
    
    return $rotatedCorners;
  }
  
  private static function getXmlns($doc)
  {
    return $doc->documentElement->getAttribute("xmlns");
  }  
  
  private static function xmlEntities($string) 
  {
    return str_replace(array("<", ">", "\"", "'", "&"), array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), $string);
  }
}
?>
