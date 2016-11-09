<?php
class LatLng
{
  function __construct($lat = 0, $lng = 0) 
  {
     $this->lat = $lat;
     $this->lng = $lng;
  }  
  public $lat;
  public $lng;
  
  // converts from LatLng to Point
  public function project(LatLng $projectionOrigin)
  {
    $rho = 6378200; // earth radius in metres
    $lambda0 = $projectionOrigin->lng * M_PI / 180.0;
    $phi0 = $projectionOrigin->lat * M_PI / 180.0;

    $lambda = $this->lng * M_PI / 180.0;
    $phi = $this->lat * M_PI / 180.0;
    return new Point($rho * cos($phi) * sin($lambda - $lambda0),
                     $rho * (cos($phi0) * sin($phi) - sin($phi0) * cos($phi) * cos($lambda - $lambda0)));
  }  
}
?>
