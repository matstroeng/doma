<?php
  $dbCon = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD);
  if(!$GLOBALS["dbCon"]) die("Could not connect to the database. Make sure that the DB_HOST, DB_USERNAME and DB_PASSWORD settings in config.php are correct.");
  if(!mysqli_select_db($GLOBALS["dbCon"], DB_DATABASE_NAME)) die("Could not select database. Make sure that the DB_DATABASE_NAME setting in config.php is correct.");
  mysqli_query($GLOBALS["dbCon"], "SET NAMES utf8");
  mysqli_query($GLOBALS["dbCon"], "SET CHARACTER SET utf8");
?>