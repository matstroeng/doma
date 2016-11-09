<?php
include_once(dirname(__FILE__) ."/include/main.php");

class EnterPublicCreationCodeController
{
  public function Execute()
  {
    $viewData = array();  

    $errors = array();
    
    if(!PUBLIC_USER_CREATION_CODE) Helper::Redirect("users.php");
    
    if(Session::GetPublicCreationCodeEntered()) Helper::Redirect("edit_user.php");

    if(isset($_POST["proceed"]))
    {
      if($_POST["publicCreationCode"] == PUBLIC_USER_CREATION_CODE)
      {
        Session::SetPublicCreationCodeEntered(true);
        Helper::Redirect("edit_user.php");
      }
      $errors[] = __("INVALID_CODE");
    }

    if(isset($_POST["cancel"]))
    {
      Helper::Redirect("users.php");
    }  

    $viewData["Errors"] = $errors;
    return $viewData;
  }
}
  
?>
