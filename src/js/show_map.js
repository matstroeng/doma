$(document).ready(function() 
{

  $("#zoomIn").click(function() 
  {
    ZoomIn();
  });
  
  $("#zoomOut").click(function() 
  {
    ZoomOut();
  });

  $("#showSecondImage,#hideSecondImage,#mapImage").click(function() 
  {
    ToggleImage();
  });
  
  $('#hidePostedComments').click(function() {
    $("#postedComments").hide();
    $("#commentBox").hide();
    $('#hidePostedComments').toggle();
    $('#showPostedComments').toggle();
    reloadGM();
    return false;
  });	

  $('#showPostedComments').click(function() {
    $("#postedComments").show();
    $("#commentBox").show();
    //$("#commentMark").focus();
    $("#commentBox").children("a#submitComment").show();    
    $('#showPostedComments').toggle();
    $('#hidePostedComments').toggle();
    reloadGM();
    return false;
  });	
  
  $("abbr.timeago").timeago();
  
  //SubmitComment
  $('a.comment').click(function() {
    var id =  $("#id").val();	
    var comment_text = $("#commentMark").val();
    var user_name = $("#user_name").val();
    var user_email = $("#user_email").val();
    var map_user = $("#map_user").val();
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    var passed = false;
    if((comment_text == "")||(user_name == ""))
    {
      alert($("#missingCommentText").val());
    }
    else
    {
      passed = true;
    }
    if((user_email != "")&&(!emailReg.test(user_email)))
    {
      alert($("#invalidEmailText").val());
      passed = false;
    }
    if(passed)
    {
      $.post(
        "add_comment.php", 
        { comment_text: comment_text, map_id: id, user_name: user_name, user_email: user_email, user: map_user },
        function(response)
        {
          $('#postedComments').append($(response).fadeIn('slow'));
          $("abbr.timeago").timeago();
          $("#comments_count").text( $("#postedComments > div").size());
          $("#commentMark").val("");
        }
      );
    }
    
  });
  
  	//deleteComment
		$('a.c_delete').live("click", function(e){
			if(confirm($("#commentDeleteConfirmationText").val())==false)
			return false;
			e.preventDefault();
			var parent  = $('a.c_delete').parent();
			var c_id =  $(this).attr('id').replace('CID-','');	
			$.ajax({
				type: 'get',
				url: 'delete_comment.php?cid='+ c_id,
				data: '',
				beforeSend: function(){
				},
				success: function(){
					$('#commentPanel-'+c_id).fadeOut(200,function(){
						$('#commentPanel-'+c_id).remove();
					});
          $("#comments_count").text( $("#comments_count").text() - 1);
				}
			});
		});
    
    reloadGM();
  
});

var zoom = 1;

function ZoomIn()
{
  zoom *= 1.25;
  $("#mapImage").get(0).width = zoom * $("#imageWidth").val();
  $("#mapImage").get(0).height = zoom * $("#imageHeight").val();
}

function ZoomOut()
{
  zoom /= 1.25;
  $("#mapImage").get(0).width = zoom * $("#imageWidth").val();
  $("#mapImage").get(0).height = zoom * $("#imageHeight").val();
}

function ToggleImage()
{
  var mapImage = $("#mapImage").get(0).src;
  var hiddenMapImageControl = $("#hiddenMapImage");
  
  if(hiddenMapImageControl.length > 0)
  {
    var hiddenMapImage = hiddenMapImageControl.get(0).src;
    $("#mapImage").get(0).src = hiddenMapImage;
    $("#hiddenMapImage").get(0).src = mapImage;
    $("#showSecondImage").toggle();
    $("#hideSecondImage").toggle();
  }
}

function toggleOverviewMap(mapContainer)
{
  var id = $("#id").val();
  var mapExists = $(".overviewMap", mapContainer).length > 0;
  
  if(mapExists)
  {
    $(".overviewMap").toggle();
  }
  else
  {
    var mapDiv = $('<div class="overviewMap"/>');
    var loadingIcon = $('<div class="loadingIcon"/>');
    mapDiv.append(loadingIcon);
    mapContainer.append(mapDiv);
    $.getJSON(
      'ajax_server.php', 
      { action: 'getMapCornerPositionsAndRouteCoordinates', id: id }, 
      function(data)
      { 
        loadingIcon.remove();
        mapDiv.overviewMap({ data: [data] });
      });
  }
  $("#showOverviewMap").toggle();
  $("#hideOverviewMap").toggle();
}

function reloadGM()
{
  if($("#gmap").size())
  {
    $("#gmap").html("<a href='"+$("#gmap_url").val()+"' target='_blank'><img src='http://maps.googleapis.com/maps/api/staticmap?center="+$("#gmap_coordinates").val()+"&amp;zoom=6&amp;size=174x"+$("#wrapper").height()+"&amp;maptype=terrain&amp;markers=color:red%7C"+$("#gmap_coordinates").val()+"&amp;sensor=false&amp;language="+$("#gmap_lang").val()+"'></a>");
  }
}

$(function() {
  $("#showOverviewMap,#hideOverviewMap").click(function() {
    toggleOverviewMap($("#overviewMapContainer"));
    return false;
  });
});

