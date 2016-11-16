<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  
  $id = $_GET["id"];
  
  $map = new Map();
  $map->Load($id);

  $data = $map->CreateJsonString(Helper::LocalPath(MAP_IMAGE_PATH), Helper::GlobalPath(MAP_IMAGE_PATH));

  header('Content-Type: application/json');
  
  print $data;
?>