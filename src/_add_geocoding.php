<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  $ids = DataAccess::GetAllMapIds();
  $numberOfSimultaneousRequests = isset($_GET["numberOfSimultaneousRequests"]) ? $_GET["numberOfSimultaneousRequests"] : 5;
?>

<?php print '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Add geocoding to DOMA database</title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <link rel="icon" type="image/png" href="gfx/favicon.png" />
  <link rel="stylesheet" href="style.css?v=<?php print DOMA_VERSION; ?>" type="text/css" />  
  <script src="js/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>
  <script src="js/common.js?v=<?php print DOMA_VERSION; ?>" type="text/javascript"></script>
  <script type="text/javascript">
    
    var numberOfSimultaneousRequests = <?php print $numberOfSimultaneousRequests; ?>;
    
    $(document).ready(function() 
    {
      $('a.upgrade').click(function() 
      {
        if(!confirm('Do you want to add map geocoding to database?\n\n(This action will not affect your current core data. It will just add/update gps data from maps to db.)'))
        {
          return false;  
        }
        $(this).hide();
        $("#result").show();
        for(var i=0; i < numberOfSimultaneousRequests; i++)
        {
          geocodeNextMap();  
        }
      });
    });
    
    function geocodeNextMap()
    {
      var idElement = $("#ids input:first").remove();
      if(idElement.length > 0)
      {
        $.post("_add_geocoding_engine.php", { id: idElement.val() })
          .done(function(data) 
          { 
            if(data == "1") $('#numberOfGeocodedMaps span').text(parseInt($('#numberOfGeocodedMaps span').text())+1);
            else if(data == "2") $('#numberOfFailedMaps span').text(parseInt($('#numberOfFailedMaps span').text())+1);
            else if(data == "3") $('#numberOfAlreadyGeocodedMaps span').text(parseInt($('#numberOfAlreadyGeocodedMaps span').text())+1);
            else $('#numberOfFailedMaps span').text(parseInt($('#numberOfFailedMaps span').text())+1);
          })
          .fail(function() 
          { 
            $('#numberOfFailedMaps span').text(parseInt($('#numberOfFailedMaps span').text())+1);
          })
          .always(function() 
          {             
            $('#numberOfProcessedMaps span').text(parseInt($('#numberOfProcessedMaps span').text())+1);
            geocodeNextMap();
          });        
      }
      else
      {
       $("#finished").show();
      }
    }
    
  </script>
</head>
<body>
<div id="wrapper">
 <div id="topbar">
  <div class="left">
     Admin tool: Add geocoding to DOMA database
  </div>
  <div class="right">
  <a href="users.php">Back to DOMA homepage</a>
  </div>
  <div class="clear"></div>
</div>

<div id="content">
<form>

<div id="ids">
  <?php
    foreach($ids as $id)
    {
      print '<input type="hidden" value="'. $id .'"/>';
    }
  ?>
</div>

<p>This tool will add geocoding to the existing maps in the database that contain QuickRoute jpeg extension data. These include map image files exported from QuickRoute 2.3 and later. Geocoding will make it possible to show map locations on overview maps, and also show information about distances, heart rates and times. Click the link below to start the tool. Processing time is dependent on the number of maps in the database and might take several minutes.</p>

<p>This tool only needs to be run once. New maps that are added to the database will be automatically geocoded if they contain QuickRoute jpeg extension data.</p>

<br/><br/>

<p>Total number of maps in database: <?php print count($ids); ?></p>

<div id="result" class="hidden">
  <p id="numberOfProcessedMaps">Number of processed maps: <span class="required">0</span></p>

  <p id="numberOfGeocodedMaps">Number of maps successfully geocoded: <span class="required">0</span></p>

  <p id="numberOfAlreadyGeocodedMaps">Number of maps already geocoded: <span class="required">0</span></p>

  <p id="numberOfFailedMaps">Number of maps where geocoding failed (probably due to missing QuickRoute jpeg extension data in the map image file): <span class="required">0</span></p>

</div>

<p id="finished" class="hidden"><strong>Finished processing maps!</strong></p>

<p><a href="#" class="upgrade">GEOCODE MAPS IN DATABASE</a></p>

</form>
</div>
</div>
</body>
</html>