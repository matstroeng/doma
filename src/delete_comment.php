<?php
  include_once(dirname(__FILE__) ."/delete_comment.controller.php");
  
  $controller = new DeleteCommentController();
  $vd = $controller->Execute();

?>
  
