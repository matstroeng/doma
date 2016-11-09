$(document).ready(function() 
{
  $(".thumbnailHoverLink").mouseover(function() 
  {
    var x = $(".hoverThumbnail", $(this).parent()).removeClass('hidden');
  });

  $(".thumbnailHoverLink").mouseout(function() 
  {
    $(".hoverThumbnail", $(this).parent()).addClass('hidden');
  });

  $("#showOverviewMap,#hideOverviewMap").click(function() {
    toggleOverviewMap();
    return false;
  });

});

function toggleOverviewMap()
{
  var mapExists = $("#overviewMap").length > 0;
  
  if(mapExists)
  {
    $("#overviewMap").toggle();
  }
  else
  {
    var overviewMap = $('<div id="overviewMap"/>');
    $("#overviewMapContainer").append(overviewMap);
    overviewMap.overviewMap({ data: overviewMapData });
  }
  $("#showOverviewMap").toggle();
  $("#hideOverviewMap").toggle();
}
