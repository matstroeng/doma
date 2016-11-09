(function($) {
  $.fn.overviewMap = function(options)
  {
    $(this).each(function() {
      var mapElement = $(this);
      var mapBounds = new google.maps.LatLngBounds();
      var markers = [];
      var borderPolygons = [];
      var routePolylines = [];
      var mapOptions = 
      {
        panControl: false,
        zoomControl: true,
        scaleControl: true,
        overviewMapControl: true,
        scrollwheel: true,
        mapTypeId: google.maps.MapTypeId.TERRAIN
      };
      var map = new google.maps.Map(mapElement.get(0), mapOptions);
      google.maps.event.addListener(map, 'zoom_changed', function() { zoomChanged(); });
      var lastZoom = -1;
      var zoomLimit = 10;
      var lastShownTooltipMapId = 0;
      
      // get bounds of maps
      for(var i in options.data)
      {
        var data = options.data[i];
        
        // the map borders for large scale overview map
        var vertices =         
        [
          new google.maps.LatLng(data.Corners[0].Latitude, data.Corners[0].Longitude),
          new google.maps.LatLng(data.Corners[1].Latitude, data.Corners[1].Longitude),
          new google.maps.LatLng(data.Corners[2].Latitude, data.Corners[2].Longitude),
          new google.maps.LatLng(data.Corners[3].Latitude, data.Corners[3].Longitude),
          new google.maps.LatLng(data.Corners[0].Latitude, data.Corners[0].Longitude)
        ];

        var borderPolygon = new google.maps.Polygon({
          paths: vertices,
          strokeColor: data.BorderColor, 
          strokeWeight: data.BorderWidth,
          strokeOpacity: data.BorderOpacity,
          fillColor: data.FillColor, 
          fillOpacity: data.FillOpacity
        });
        borderPolygon.Data = data;
        borderPolygons.push(borderPolygon);
        
        // the map as an icon for small scale overview map
        var icon = new google.maps.MarkerImage("gfx/control_flag.png", new google.maps.Size(16, 16), new google.maps.Point(0, 0), new google.maps.Point(8, 8));
        var position = new google.maps.LatLng(data.MapCenter.Latitude, data.MapCenter.Longitude);
        var marker = new google.maps.Marker({ icon: icon, position: position });
        marker.Data = data;
        markers.push(marker);

        // tooltips
        if(data.TooltipMarkup != null)
        {
          function showTooltip(e) 
          {
            if(e.Data.MapId != lastShownTooltipMapId)
            {
              Tooltip.show(e.Data.TooltipMarkup);
              lastShownTooltipMapId = e.Data.MapId;
            }        
          }
          google.maps.event.addListener(marker, 'mousemove', function() { showTooltip(this); });
          google.maps.event.addListener(marker, 'click', function() {
            window.location = this.Data.Url.replace('&amp;', '&')
          });
          google.maps.event.addListener(marker, 'mouseover', function() 
          { 
            var icon = this.getIcon();
            this.setIcon(new google.maps.MarkerImage("gfx/control_flag_highlighted.png", icon.size, icon.origin, icon.anchor));
          });
          google.maps.event.addListener(marker, 'mouseout', function()
          { 
            var icon = this.getIcon();
            this.setIcon(new google.maps.MarkerImage("gfx/control_flag.png", icon.size, icon.origin, icon.anchor));
          });

          google.maps.event.addListener(borderPolygon, 'mousemove', function() { showTooltip(this); });
          google.maps.event.addListener(borderPolygon, 'click', function() {
            window.location = this.Data.Url.replace('&amp;', '&');
          });
          google.maps.event.addListener(borderPolygon, 'mouseover', function() 
          { 
            this.setOptions({ strokeColor: this.Data.SelectedBorderColor, fillColor: this.Data.SelectedFillColor });
          });
          google.maps.event.addListener(borderPolygon, 'mouseout', function()
          { 
            this.setOptions({ strokeColor: this.Data.BorderColor, fillColor: this.Data.FillColor });
          });

          google.maps.event.addListener(map, 'mousemove', function() {
            if(lastShownTooltipMapId != 0)
            {
              Tooltip.hide();
              lastShownTooltipMapId = 0;
            }
          });
        }

        // the route lines (if data.RouteCoordinates is present)
        if(data.RouteCoordinates != null)
        {
          for(var i in data.RouteCoordinates)
          {
            var points = new Array(data.RouteCoordinates[i].length);
            for(var j in data.RouteCoordinates[i])
            {
              var vertex = data.RouteCoordinates[i][j];
              points[j] = new google.maps.LatLng(vertex[1], vertex[0]);
            }
            var polyline = new google.maps.Polyline({ path: points, strokeColor: data.RouteColor, strokeWeight: data.RouteWidth, strokeOpacity: data.RouteOpacity });
            routePolylines.push(polyline);
          }
        }
        
        // make sure all maps fits in overview map
        mapBounds.extend(vertices[0]);
        mapBounds.extend(vertices[1]);
        mapBounds.extend(vertices[2]);
        mapBounds.extend(vertices[3]);
      }
      
      map.fitBounds(mapBounds);

      function zoomChanged()
      {
        var zoom = map.getZoom();
        
        if(zoom < zoomLimit && (lastZoom >= zoomLimit || lastZoom == -1))
        {
          for(var i in borderPolygons)
          {
            borderPolygons[i].setMap(null);
          }
          for(var i in routePolylines)
          {
            routePolylines[i].setMap(null);
          }
          for(var i in markers)
          {
            markers[i].setMap(map);
          }
        }
        if(zoom >= zoomLimit && (lastZoom < zoomLimit || lastZoom == -1))
        {
          for(var i in markers)
          {
            markers[i].setMap(null);
          }
          for(var i in borderPolygons)
          {
            borderPolygons[i].setMap(map);
          }
          for(var i in routePolylines)
          {
            routePolylines[i].setMap(map);
          }
        }
        lastZoom = zoom;
      }    
    });
  };
})(jQuery);

var Tooltip=function(){
    var id = 'tt';
    var top = 12;
    var left = 12;
    var maxw = 600;
    var speed = 15;
    var timer = 20;
    var endalpha = 85;
    var alpha = 0;
    var tt,h;
    var ie = document.all ? true : false;
    return{
        show:function(v,w){
            if(tt == null){
                tt = document.createElement('div');
                tt.setAttribute('id',id);
                document.body.appendChild(tt);
                tt.style.opacity = 0;
                tt.style.filter = 'alpha(opacity=0)';
                document.onmousemove = this.pos;
            }
            tt.style.display = 'block';
            tt.innerHTML = v;
            tt.style.width = w ? w + 'px' : 'auto';
            if(!w && ie){
                tt.style.width = tt.offsetWidth;
            }
            if(tt.offsetWidth > maxw){tt.style.width = maxw + 'px'}
            h = parseInt(tt.offsetHeight) + top;
            clearInterval(tt.timer);
            tt.timer = setInterval(function(){Tooltip.fade(1)},timer);
        },
        pos:function(e){
            var u = ie ? event.clientY + document.documentElement.scrollTop : e.pageY;
            var l = ie ? event.clientX + document.documentElement.scrollLeft : e.pageX;
            tt.style.top = (u - h) + 'px';
            tt.style.left = (l + left) + 'px';
        },
        fade:function(d){
            var a = alpha;
            if((a != endalpha && d == 1) || (a != 0 && d == -1)){
                var i = speed;
                if(endalpha - a < speed && d == 1){
                    i = endalpha - a;
                }else if(alpha < speed && d == -1){
                    i = a;
                }
                alpha = a + (i * d);
                tt.style.opacity = alpha * .01;
                tt.style.filter = 'alpha(opacity=' + alpha + ')';
            }else{
                clearInterval(tt.timer);
                if(d == -1){tt.style.display = 'none'}
            }
        },
        hide:function(){
          if(tt != null)
          {
            clearInterval(tt.timer);
            tt.timer = setInterval(function(){Tooltip.fade(-1)},timer);
          }          
        }
    };
}();