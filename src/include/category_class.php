<?php
  include_once(dirname(__FILE__) ."/database_object.php");
  include_once(dirname(__FILE__) ."/data_access.php");

  class Category extends DatabaseObject
  {
    protected $DBTableName = DB_CATEGORY_TABLE;
    protected $ClassName = "Category";
    public $Data = array(
      "ID" => 0,
      "UserID" => 0,
      "Name" => "" 
    );
    private $User;

    public function GetUser()
    {
      if(!$this->User) $this->User = DataAccess::GetUserByID($this->UserID);
      return $this->User;
    }
  }


?>