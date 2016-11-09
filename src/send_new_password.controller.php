<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  
  class SendNewPasswordController
  {
    public function Execute()
    {
      $viewData = array();  

      $errors = array();

      // no user specified - redirect to user list page
      if(!getCurrentUser()) Helper::Redirect("users.php");

      // user is hidden - redirect to user list page
      if(!getCurrentUser()->Visible) Helper::Redirect("users.php");
      
      // no email address for user is not specified
      if(!getCurrentUser()->Email) Helper::Redirect("users.php");

      if($_POST["cancel"])
      {
        Helper::Redirect("login.php?". Helper::CreateQuerystring(getCurrentUser()));
      }

      if($_POST["send"])
      {
        $password = Helper::CreatePassword(6);
        $user = getCurrentUser();
        $user->Password = md5($password);
        $user->Save();
        
        $fromName = __("DOMA_ADMIN_EMAIL_NAME");
        $subject = __("NEW_PASSWORD_EMAIL_SUBJECT");
        $baseAddress = Helper::GlobalPath("");
        $userAddress = Helper::GlobalPath("index.php?user=". $user->Username);
        $body = sprintf(__("NEW_PASSWORD_EMAIL_BODY"), $user->FirstName, $baseAddress, $userAddress, $user->Username, $password);  
        $emailSentSuccessfully = Helper::SendEmail($fromName, $user->Email, $subject, $body);
        
        if($emailSentSuccessfully) Helper::Redirect("login.php?". Helper::CreateQuerystring(getCurrentUser()) ."&action=newPasswordSent");
        
        $errors[] = __("EMAIL_ERROR");
      }
      
      $viewData["Errors"] = $errors;
      return $viewData;
    }
  }  
?>
