$(document).ready(function() 
{
  $(".toggleComment").click(function() 
  {
    toggleComment($(this).parent().parent());
  });

  $(".comment div").click(function() 
  {
    toggleComment($(this).parent().parent());
  });

  $("#categoryID").change(function() { submitForm(); });
  $("#year").change(function() { submitForm()});
  $("#displayMode").change(function() { submitForm(); });
  $(".showOverviewMap,.hideOverviewMap").click(function() 
  { 
    toggleSingleOverviewMap($(this).closest(".map"));
    return false;
 });

});

function toggleComment(baseElement)
{
  $(".longComment", baseElement).toggleClass('hidden');
  $(".shortComment", baseElement).toggleClass('hidden');
}

function submitForm()
{
  $("form").submit();
}

function toggleSingleOverviewMap(mapContainer)
{
  var div = $(".listOverviewMapLink", mapContainer);
  var id = $("input[type='hidden']", div).val();
  var googleMapsContainer = $(".googleMapsContainer", mapContainer);
  var mapExists = $(".singleOverviewMap", googleMapsContainer).length > 0;
  
  if(mapExists)
  {
    googleMapsContainer.toggle();
  }
  else
  {
    googleMapsContainer.show();
    var mapDiv = $('<div class="singleOverviewMap"/>');
    googleMapsContainer.append(mapDiv);
    var loadingIcon = $('<div class="loadingIcon"/>');
    mapDiv.append(loadingIcon);
    $.getJSON(
      'ajax_server.php', 
      { action: 'getMapCornerPositionsAndRouteCoordinates', id: id }, 
      function(data)
      { 
        loadingIcon.remove();
        mapDiv.overviewMap({ data: [data] });
      });
  }
  $(".showOverviewMap", mapContainer).toggle();
  $(".hideOverviewMap", mapContainer).toggle();
}
