<?php
  if(!mysql_connect(DB_HOST, DB_USERNAME, DB_PASSWORD)) die("Could not connect to the database. Make sure that the DB_HOST, DB_USERNAME and DB_PASSWORD settings in config.php are correct.");
  if(!mysql_select_db(DB_DATABASE_NAME)) die("Could not select database. Make sure that the DB_DATABASE_NAME setting in config.php is correct.");
  mysql_query("SET NAMES utf8");
  mysql_query("SET CHARACTER SET utf8");
?>