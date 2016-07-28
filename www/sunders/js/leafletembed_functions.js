var ajaxRequest;
var map;
var osmTiles;
var plotList;
var plotLayers = [];
var footprintPolygon;

// Draw the map for the first time.
function initMap() {
  // Set up the AJAX request.
  ajaxRequest=getXmlHttpRequest();
  if (ajaxRequest==null) {
    alert("This browser does not support HTTP requests.");
    return;
  }

  // Set up the OSM tile layer with correct attribution
  var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  var dataAttrib = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap contributors</a>';
  var permalink = '<a href=#" onClick="permalink();return false;">Permalink</a>';
  var mapAttrib = dataAttrib + ' | ' + permalink;
  osmTiles = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 18, attribution: mapAttrib});

  // Set up the map.
  map = new L.Map('map');
  map.zoomControl.setPosition('topleft');
  map.attributionControl.setPosition('bottomleft');
  map.setView(new L.LatLng(initialLat, initialLon), initialZoom);
  map.addLayer(osmTiles);
  addPlots();
  map.on('moveend', onMapMoved);
}

// Return XMLHttpRequest.
function getXmlHttpRequest() {
  if (window.XMLHttpRequest) { return new XMLHttpRequest(); }
  if (window.ActiveXObject)  { return new ActiveXObject("Microsoft.XMLHTTP"); }
  return null;
}

// Create an URL for the current location and use this URL to reload the map.
function permalink() {
  var center = map.getCenter();
  var lat = Math.round(center.lat * 100000000) / 100000000;
  var lon = Math.round(center.lng * 100000000) / 100000000;
  var serverUrl = 'http://' + window.location.hostname + '/sunders/index.php';
  var newLoc = serverUrl + "?lat=" + lat + "&lon=" + lon + "&zoom=" + map.getZoom();
  window.location = newLoc;
}

// Add plots to map.
function addPlots() {
  var bounds = map.getBounds();
  var boundsSW = bounds.getSouthWest();
  var boundsNE = bounds.getNorthEast();
  var size = map.getSize();
  var url = 'camera.php?bbox=' + boundsSW.lng + ',' + boundsSW.lat + ',' + boundsNE.lng + ',' + boundsNE.lat + '&zoom=' + map.getZoom() + '&width=' + size.x + '&height=' + size.y;
  ajaxRequest.onreadystatechange = onStateChanged;
  ajaxRequest.open('GET', url, true);
  ajaxRequest.send(null);
}

// Remove all markers from map.
function removeMarkers() {
	for (i=0; i<plotLayers.length; i++) {
		map.removeLayer(plotLayers[i]);
	}
  if (footprintPolygon != null) {
    map.removeLayer(footprintPolygon);
  }
	plotLayers=[];
}

// Things to do when the map has been moved.
function onMapMoved(e) {
  addPlots();
}

// Things to do when a marker has been clicked.
function onClick(e) {
  e.target.openPopup();
}

// Things to do when the readystatechange event is fired.
function onStateChanged() {
  if (ajaxRequest.readyState == 4) { // 0 = UNSENT, 1 = OPENED, 2 = HEADERS_RECEIVED, 3 = LOADING, 4 = DONE
    if (ajaxRequest.status == 200) { // 200 = OK, 404 = Page not found
      plotList = eval("(" + ajaxRequest.responseText + ")");
      removeMarkers();
      for (i=0; i<plotList.length; i++) {
        var plotLatLng;
        var plotMarker = '';

        if (plotList[i].error) {
          alert(plotList[i].error);
        }
        // Add pink label with number of composite cameras.
        else if (plotList[i].multi == 'yes') {
          plotLatLng = new L.LatLng(plotList[i].lat, plotList[i].lon);
          plotMarker = getPlotMarkerMulti(plotList[i], plotLatLng);
        }
        // Add camera icon.
        else {
          try {
            plotLatLng = new L.LatLng(plotList[i].lat, plotList[i].lon);
            plotMarker = getPlotMarkerCamera(plotList[i], plotLatLng);

            // Get camera height to draw camera's field of view.
            var cameraHeight = getCameraHeight(plotList[i]);

            // Draw fixed camera's field of view and add it to map.
            var cameraType = plotList[i]['camera:type'];
            if (cameraType == 'fixed' || cameraType == 'static') {

              // Get camera direction to draw camera's field of view.
              var cameraDirection = getCameraDirection(plotList[i]);

              if (cameraDirection != null) {
                // Get camera angle to draw camera's field of view.
                var cameraAngle = getCameraAngle(plotList[i]);

                // Draw camera's field of view and add it to map.
                var plotFocus = drawCameraFocusFixed(plotLatLng, plotList[i], cameraDirection, cameraHeight, cameraAngle);
                map.addLayer(plotFocus);
                plotLayers.push(plotFocus);
              }
            }
            // Draw dome's field of view and add it to map.
            else if (cameraType == 'dome' ) {
                var plotFocus = drawCameraFocusDome(plotLatLng, cameraHeight);
                map.addLayer(plotFocus);
                plotLayers.push(plotFocus);
            }

            // Add camera details to camera marker.
            addCameraDetailsData(plotMarker, plotList[i]);
          } catch(e) {
          }
        }

        if (plotMarker != '') {
          map.addLayer(plotMarker);
          plotLayers.push(plotMarker);
        }
      }
    }
  }
}

// Get pink label with number of composite cameras.
function getPlotMarkerMulti(plot, plotLatLng) {
  if (plot.poly) {
    /* Build string with coordinates for every polygon corner,
        e.g. [{"lat":"42.9992737", "lon":"23.2704823"},{"lat":"42.9974195", "lon":"23.2713401"},{"lat":"42.9993203", "lon":"23.2706528"}] */
    var polygonCoordinates = '[';
    var separator = '';
    for (x in plot.poly) {
      polygonCoordinates = polygonCoordinates
             + separator
             + '{&quot;lat&quot;:&quot;' + plot.poly[x]['lat'] + '&quot;,'
             + ' &quot;lon&quot;:&quot;' + plot.poly[x]['lon'] + '&quot;}';
      separator = ",";
    }
    polygonCoordinates = polygonCoordinates + "]";
    countTxt = '<span onclick="drawFootprint(\'' + polygonCoordinates + '\')">' + plot.count + "</span>";
  } else {
    countTxt =  plot.count;
  }

  plotIcon = eval('compositeCamerasIcon');

  return(
    new L.Marker(plotLatLng, { icon: plotIcon }).bindLabel(countTxt, {
      noHide: true,
      clickable: true,
      className: 'composite-cameras-label'
    })
  );
}

/* Draw a footprint that represents the area composite cameras are located in.
    polygonCoordinates is a string like this:
      [{"lat":"42.9992737", "lon":"23.2704823"},{"lat":"42.9974195", "lon":"23.2713401"},{"lat":"42.9993203", "lon":"23.2706528"}] */
function drawFootprint(polygonCoordinates) {
  polygonCoordinates = JSON.parse(polygonCoordinates);
  var polygonPoints = [];
  for (x in eval(polygonCoordinates)) {
    polygonPoints.push(new L.LatLng(polygonCoordinates[x]['lat'], polygonCoordinates[x]['lon']));
  }
  if (footprintPolygon != null) {
    map.removeLayer(footprintPolygon);
  }
  footprintPolygon = L.polygon(polygonPoints, { color:'blue', weight : 1, fillOpacity:0.1 });
  footprintPolygon.addTo(map);
}

// Add camera icon.
function getPlotMarkerCamera(plot, plotLatLng) {
  // Get icon name for current camera or guard: fixed, dome, guard
  var iconName = 'fixed';
  if (plot['camera:type'] == 'dome') {
    iconName = 'dome';
  } else if (plot['surveillance:type'] == 'guard') {
    iconName = 'guard';
  }

  /* Add postfix for cameras and guards according to their location:
      Red (public outdoor areas), Blue (private outdoor areas), Green (indoor) */
  var type = plot['surveillance'];
  if (type == 'public') {
    iconName = iconName + 'Red';
  } else if (type == 'indoor' ) {
    iconName = iconName + 'Green';
  } else if (type == 'outdoor' ) {
    iconName = iconName + 'Blue';
  }

  // Get icon name for current traffic camera
  if (plot['surveillance:type'] == 'ALPR' || type == 'red_light' || type == 'level_crossing' || type == 'speed_camera') {
    iconName = 'traffic';
  }

  // Add prefix for cameras and guards marked with a 'fixme' key: todo_
  if (plot['fixme'] != null) {
    iconName = 'todo_' + iconName;
  }

  iconName = iconName + 'Icon';
  plotIcon = eval(iconName); // see leafletembed_icons.js
  return(new L.Marker(plotLatLng, {icon : plotIcon}));
}

// Get camera height.
function getCameraHeight(plot) {
  var cameraHeight = plot['height'];
  if (! isNumeric(cameraHeight)) {
    cameraHeight = 5;
  } else if (cameraHeight < 3) {
    cameraHeight = 3;
  } else if (cameraHeight > 12) {
    cameraHeight = 12;
  }
  return(cameraHeight);
}

// Get camera direction.
function getCameraDirection(plot) {
  var cameraDirection = plot['camera:direction'];
  if (cameraDirection == null) {
    cameraDirection = plot['direction'];
    if (cameraDirection == null) {
      cameraDirection = plot['surveillance:direction'];
    }
  }
  if (cameraDirection == 'N') {
    cameraDirection = 0;
  } else if (cameraDirection == 'NE') {
    cameraDirection = 45;
  } else if (cameraDirection == 'E') {
    cameraDirection = 90;
  } else if (cameraDirection == 'SE') {
    cameraDirection = 135;
  } else if (cameraDirection == 'S') {
    cameraDirection = 180;
  } else if (cameraDirection == 'SW') {
    cameraDirection = 225;
  } else if (cameraDirection == 'W') {
    cameraDirection = 270;
  } else if (cameraDirection == 'NW') {
    cameraDirection = 315;
  }
  if (cameraDirection != '' && isNumeric(cameraDirection)) {
    cameraDirection = 90 - cameraDirection;
    if (cameraDirection > 180) {
      cameraDirection -= 360
    } else if (cameraDirection < -180) {
      cameraDirection += 360;
    }
    cameraDirection = (cameraDirection * 207986.0) / 11916720;
  }
  return(cameraDirection);
}

// Get camera angle.
function getCameraAngle(plot) {
  var cameraAngle = plot['camera:angle'];
  if (cameraAngle != null && isNumeric(cameraAngle)) {
    if (cameraAngle < 0) {
      cameraAngle = -cameraAngle;
    }
    if (cameraAngle <= 15) {
      cameraAngle = 1;
    } else {
      cameraAngle = Math.cos(((cameraAngle - 15) * 207986.0) / 11916720);
    }
  } else {
    cameraAngle = 1;
  }
  return(cameraAngle);
}

// Draw fixed camera focus.
function drawCameraFocusFixed(plotLatLng, plot, cameraDirection, cameraHeight, cameraAngle) {
  var cameraLatLng = [plotLatLng];
  var coefLat = (1.0 / Math.cos(plot.lat * 3.14159 / 180));
  for (a=-5; a<=5; a+=2) {
    var plotLatLng = new L.LatLng(
      parseFloat(plot.lat) + 0.000063 * Math.sin(cameraDirection + a / 10) * cameraHeight * cameraAngle,
      parseFloat(plot.lon) + 0.000063 * Math.cos(cameraDirection + a / 10) * coefLat * cameraHeight * cameraAngle
    );
    cameraLatLng.push(plotLatLng);
  }
  return(new L.Polygon(cameraLatLng, { color:'red', weight:1, fillOpacity:0.1 }));
}

// Draw dome focus.
function drawCameraFocusDome(plotLatLng, cameraHeight) {
  return(new L.Circle(plotLatLng, 7 * cameraHeight, { color:'red', weight:1, fillOpacity:0.1 }));
}

// Add camera popup to camera marker.
function addCameraDetailsData(plotMarker, plot) {
  popupDataTable = '<table class="popup-content">'
    + '<tr><td>id</td><td><a href="https://www.openstreetmap.org/node/' + (plot.id) + '" target="_blank">' + (plot.id) + '</td></tr>'
    // + '<tr><td>user osm</td><td>' + (plot.userid) + '</td></tr>'
    + '<tr><td>latitude</td><td>' + (plot.lat) + '</td></tr>'
    + '<tr><td>longitude</td><td>' + (plot.lon) + '</td></tr>';
  for (x in plot) {
    if (plot[x] != '' && x != 'multi' && x != 'id' && x != 'userid' && x != 'lat' && x != 'lon') {
      popupDataTable = popupDataTable + '<tr><td>' + x + '</td><td>';
      var descr = plot[x];
      if (descr.substr(0, 4) == 'http') {
        var suffix = descr.slice(-3).toLowerCase();
        if (suffix == "jpg" || suffix == "gif" || suffix == "png") {
          popupDataTable = popupDataTable + '<a href="' + descr + '" target="_blank"><img alt="image" src="' + descr + '" width="200"/></a>';
        } else {
          popupDataTable = popupDataTable + '<a href="' + descr + '">Link</a>';
        }
      } else {
        popupDataTable = popupDataTable + plot[x];
      }
      popupDataTable = popupDataTable + '</td></tr>';
    }
  }
  popupDataTable = popupDataTable +'</table>';

  plotMarker.bindPopup(popupDataTable, {autoPan:false, maxWidth:400});

  plotMarker.on('click', onClick);
}

function isNumeric(s) {
  var intRegex = /^\d+$/;
  var floatRegex = /^((\d+(\.\d *)?)|((\d*\.)?\d+))$/;
  return ((intRegex.test(s) || floatRegex.test(s)));
}

initMap()
