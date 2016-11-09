$(document).ready(function() 
{
  $(".tooltipControl").focus(function() 
  {
    $(".tooltip", $(this).parent()).removeClass("hidden");
  });

  $(".tooltipControl").blur(function() 
  {
    $(".tooltip", $(this).parent()).addClass("hidden");
  });
});