<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  
  class LoginController
  {
    public function Execute()
    {
      $viewData = array();  
  
      $errors = array();

      // no user specified - redirect to user list page
      if(!getCurrentUser()) Helper::Redirect("users.php");

      // user is hidden - redirect to user list page
      if(!getCurrentUser()->Visible) Helper::Redirect("users.php");

      if(isset($_POST["cancel"]))
      {
        Helper::Redirect("index.php?". Helper::CreateQuerystring(getCurrentUser()));
      }

      if(isset($_GET["action"]) && $_GET["action"] == "logout")
      {
        $location = "index.php?". Helper::CreateQuerystring(getCurrentUser());
        Helper::LogoutUser();
        Helper::Redirect($location);
      }

      if(isset($_POST["login"]))
      {
        $currentUserID = getCurrentUser()->ID;
        if(Helper::LoginUser(stripslashes($_POST["username"]), stripslashes($_POST["password"])))
        {
          if(getCurrentUser()->ID == $currentUserID) Helper::Redirect("index.php?". Helper::CreateQuerystring(getCurrentUser()));
        }
        $errors[] = __("INVALID_USERNAME_OR_PASSWORD");
      }

      if(isset($_POST["forgotPassword"]))
      {
        Helper::Redirect("send_new_password.php?". Helper::CreateQuerystring(getCurrentUser()));
      }
      
      $viewData["Errors"] = $errors;
      return $viewData;
    }
  }
?>
