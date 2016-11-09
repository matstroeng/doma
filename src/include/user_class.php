<?php
  include_once(dirname(__FILE__) ."/database_object.php");

  class User extends DatabaseObject
  {
    protected $DBTableName = DB_USER_TABLE;
    protected $ClassName = "User";
    public $Data = array(
      "ID" => 0, 
      "Username" => "", 
      "Password" => "", 
      "FirstName" => "", 
      "LastName" => "", 
      "Email" => "", 
      "Visible" => 1,
      "DefaultCategoryID" => 0
    );
    public $NoOfMaps = 0;
    private $DefaultCategory;
    private $Categories;
    
    public function GetCategories()
    {
      if(!$this->Categories) $this->Categories = DataAccess::GetCategoriesByUserID($this->ID);
      return $this->Categories;
    }    
  }


?>