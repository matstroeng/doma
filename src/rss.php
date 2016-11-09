<?php 
  include_once(dirname(__FILE__) ."/rss.controller.php");
  
  $controller = new RSSController();
  $vd = $controller->Execute();

  print '<?xml version="1.0" encoding="UTF-8"?>' ."\n";
?>
<rss version="2.0">
   <channel>
      <title><?php print $vd["Title"]?></title>
      <link><?php print Helper::GlobalPath("")?></link>
      <description><?php print $vd["Description"]?></description>
      <lastBuildDate><?php print $vd["LastCreatedTime"]?></lastBuildDate>
      <generator>DOMA <?php print DOMA_VERSION;?></generator>
      <image>
        <url>gfx/book.png</url>
        <title><?php print __("PAGE_TITLE")?></title>
        <link><?php print $vd["WebsiteUrl"]?></link>
      </image>
      <?php
        foreach($vd["Items"] as $item)
        {
          ?>
          <item>
             <title><?php print $item["Title"]?></title>
             <link><?php print $item["URL"]?></link>
             <description><?php print $item["Description"]?></description>
             <pubDate><?php print $item["PubDate"]?></pubDate>
          </item>
          <?php        
        }
      ?>
   </channel>
</rss>