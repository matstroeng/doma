<?php
  error_reporting(E_ALL & ~E_NOTICE);
  
  include_once(dirname(__FILE__) ."/../config.php");
  include_once(dirname(__FILE__) ."/definitions.php");

  // set character encoding
  header('Content-Type: text/html; charset=utf-8');

  // load session
  session_start();
  
  // create database if it does not exist
  if(!Helper::DatabaseVersionIsValid()) Helper::Redirect("create.php?redirectUrl=". urlencode($_SERVER["REQUEST_URI"]));
  
  // extract current user from querystring
  if(isset($_GET["user"]))
  {
    $currentUser = getCurrentUser();
    if(!$currentUser || 
       $currentUser->Username != $_GET["user"] || 
       !Session::GetLanguageStrings() || 
       (isset($_GET["lang"]) && Session::GetLanguageCode() != $_GET["lang"]))
    {
      Helper::SetUser(DataAccess::GetUserByUsername($_GET["user"]));
    }
  }
  else
  {
    Helper::SetUser(null);
  }
?>