<?php
class Point
{
  function __construct($x = 0, $y = 0) 
  {
     $this->x = $x;
     $this->y = $y;
  }  
  public $x;
  public $y;
  
  // converts from Point to LatLng
  public function deproject(LatLng $projectionOrigin)
  {
    /*
    if (LinearAlgebraUtil.DistancePointToPoint(coordinate, new PointD(0, 0)) < 0.0000001)
      return new LongLat(projectionOrigin.Longitude, projectionOrigin.Latitude);
    */
    $r = 6378200; // earth radius in metres
    $latLng = new LatLng();
    $rho = sqrt($this->x*$this->x + $this->y*$this->y);
    $c = asin($rho/$r);
    
    if(is_nan($c)) 
    {
      die($this->x .", ". $this->y ." is outside of projection!");
      return null; // outside of projection!
    }
    
    $lambda0 = $projectionOrigin->lng * M_PI / 180.0;
    $phi1 = $projectionOrigin->lat * M_PI / 180.0;
    $latLng->lat = asin(cos($c) * sin($phi1) + ($this->y * sin($c) * cos($phi1) / $rho)) / M_PI * 180.0;
    
    $latLng->lng = ($lambda0 + atan($this->x*sin($c) / ($rho * cos($phi1) * cos($c) - $this->y * sin($phi1) * sin($c)))) / M_PI * 180.0;
    return $latLng;
  }  
}  
?>
