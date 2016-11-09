<?php
  include_once(dirname(__FILE__) ."/helper.php");
  $filename = $_GET["filename"];
  $result = 0;
  if(file_exists($filename))
  {
    $image = Helper::ImageCreateFromGeneral($filename);
    if(is_resource($image))
    {
      ImageDestroy($image);
      $result = 1;
    }
  }
  print $result;
?>