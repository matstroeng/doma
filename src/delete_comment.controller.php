<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  
  class DeleteCommentController
  {
    public function Execute()
    {

      $viewData = array();  
  
      $errors = array();

      $comment = new Comment();
      
      Helper::WriteToLog("Comment ID: ".$_GET["cid"]);
      if(($_GET["cid"])&&(is_numeric($_GET["cid"])))
      {
        $cid = $_GET["cid"];
      } else {
        die("No comment ID");
      }
      
      $comment->Load($cid);
      
      $userip = $_SERVER['REMOTE_ADDR'];
      
      $map = new Map();
      $map->Load($comment->MapID);
      
      if(($comment->UserIP == $userip)||($map->UserID == Helper::GetLoggedInUser()->ID))
      {
        $comment->Delete();
      } else {
        die("No rights to delete comment!");
      }
   
    

      $viewData["Errors"] = $errors;

      return $viewData;
    }
  }
?>
