<?php
  include_once(dirname(__FILE__) ."/add_comment.controller.php");
  
  $controller = new AddCommentController();
  $vd = $controller->Execute();
  $comment = $vd["Comment"];
  $map = $vd["Map"];
  
  include(dirname(__FILE__) ."/show_comment.php");
?>
  
