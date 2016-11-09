$(document).ready(function() 
{
  getRerun();
  
  function getRerun() 
  {
    var apiurl = $("#rerun_apiurl").val().replace("{0}",$("#rerun_apikey").val());
    if($("#rerun_maps").val().length>0)
    {
      var maps = $("#rerun_maps").val().split(",");
      for (var i=0; i<maps.length; i++) 
      {
        var map = maps[i].split(";");
        var url = encodeURIComponent($("#base_url").val()+'show_map.php?user='+map[1]+'&map='+map[0]);
        $.ajax({
          dataType: 'json',
          crossDomain: true,
          url: apiurl.replace("{1}",url)+'&callback=?',
          success: function(data) {
            var ind = data.link.lastIndexOf("=");
            var mapid = data.link.substring(ind+1);
            if(data.status=="OK")
            {
              $.get("ajax_server.php?action=saveRerunID&mapid="+mapid+"&rerunid="+data.id3drerun);
            }
            else
            {
              $.get("ajax_server.php?action=saveRerunID&mapid="+mapid+"&fail=1");
            }
            $("#processed_rerun_maps").val(parseInt($("#processed_rerun_maps").val())+1);
            if(parseInt($("#processed_rerun_maps").val())==parseInt($("#total_rerun_maps").val()))
            {
              $.get("ajax_server.php?action=saveLastRerunCheck");
            }
          }
        });
      }
    }
  }
});