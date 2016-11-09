<?php
  include_once(dirname(__FILE__) ."/include/main.php");

  class AdminLoginController
  {
    public function Execute()
    {
      $viewData = array();  

      if(isset($_POST["cancel"]))
      {
        Helper::Redirect("users.php");
      }

      if(isset($_GET["action"]) && $_GET["action"] == "logout")
      {
        Helper::LogoutAdmin();
        Helper::Redirect("users.php");
      }

      $errors = array(); 
        
      if(isset($_POST["login"]))
      {
        if(Helper::LoginAdmin(stripslashes($_POST["username"]), stripslashes($_POST["password"])))
        {
          Helper::Redirect("users.php");
        }
        $errors[] = __("INVALID_USERNAME_OR_PASSWORD");
      }
      
      $viewData["Errors"] = $errors;
      return $viewData;
    }
  }  
?>
