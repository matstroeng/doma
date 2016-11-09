<div class="commentPanel" id="commentPanel-<?php print $comment->ID; ?>">
  <span class="commentName">
    <?php if($comment->Email != "") 
      {
      ?><a href="mailto:<?php print $comment->Email; ?>"><?php print $comment->Name; ?></a>
      <?php
      } else {
      print $comment->Name;
      }
      ?></span>:
  <span>
    <?php print Helper::ClickableLink(nl2br(stripslashes($comment->Comment))); ?>
  </span>
  <div class="postedTime">
    <abbr class="timeago" title="<?php print $comment->DateCreated?>"></abbr>
    <?php
      $userip = $_SERVER['REMOTE_ADDR'];
      if(($comment->UserIP == $userip)||($map->UserID == Helper::GetLoggedInUser()->ID)) { ?>
        &nbsp;&nbsp;<a href="#" id="CID-<?php print $comment->ID; ?>" class="c_delete"><?php print __("DELETE"); ?></a>
    <?php } ?>
  </div>
</div>
  
