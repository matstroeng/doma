<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  
  class AddCommentController
  {
    public function Execute()
    {
      
      $viewData = array();  
  
      $errors = array();

      $comment = new Comment();
      
      // no user specified - redirect to user list page
      if(isset($_POST["comment_text"])) 
      {
        $comment->Comment = stripslashes(strip_tags(urldecode($_POST["comment_text"])));
      } 
      else 
      {
        die("No comment text");
      }
      
      if(isset($_POST["user_name"]))
      {
        $comment->Name = stripslashes(strip_tags(urldecode($_POST["user_name"])));
      } 
      else 
      {
        die("No user name");
      }
      
      if(isset($_POST["map_id"]) && is_numeric($_POST["map_id"]))
      {
        $comment->MapID = $_POST["map_id"];
      } 
      else 
      {
        die("No valid map ID");
      }
      
      if(isset($_POST["user_email"])) $comment->Email = stripslashes(strip_tags($_POST["user_email"]));
      $comment->UserIP = $_SERVER['REMOTE_ADDR'];
      $comment->DateCreated = date("Y-m-d H:i:s");
      
      $comment->Save();
      
      $map = new Map();
      $map->Load($comment->MapID);
      
      if(__("EMAIL_VISITOR_COMMENTS") && $map->UserID != Helper::GetLoggedInUser()->ID)
      {
        $user = DataAccess::GetUserByID($map->UserID);
        
        $fromName = __("DOMA_ADMIN_EMAIL_NAME");
        $subject = __("NEW_COMMENT_EMAIL_SUBJECT");
        $mapAddress = Helper::GlobalPath("show_map.php?user=". $user->Username ."&map=". $map->ID ."&showComments=true");
        $body = sprintf(__("NEW_COMMENT_EMAIL_BODY"), $map->Name, $mapAddress, $comment->Name, $comment->Email, $comment->Comment);  
        $emailSentSuccessfully = Helper::SendEmail($fromName, $user->Email, $subject, $body);
        
        if(!$emailSentSuccessfully) $errors[] = __("EMAIL_ERROR");
      }

      $viewData["Errors"] = $errors;
      $viewData["Comment"] = $comment;
      $viewData["Map"] = $map;

      return $viewData;
    }
  }
?>
