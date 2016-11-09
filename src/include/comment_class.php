<?php
  include_once(dirname(__FILE__) ."/database_object.php");
  include_once(dirname(__FILE__) ."/data_access.php");

  class Comment extends DatabaseObject
  {
    protected $DBTableName = DB_COMMENT_TABLE;
    protected $ClassName = "Comment";
    public $Data = array(
      "ID" => 0,
      "MapID" => 0,
      "Name" => "",
      "Email" => "",
      "Comment" => "",
      "DateCreated" => 0,
      "UserIP" => ""
    );
  }


?>