<!DOCTYPE html>
<html>
<head>

<style type="text/css">
  html { height: 100% }
  body { height: 100%; margin: 0px; padding: 0px }
  #map-canvas { width:69%; height:100%; float:left; }
  #tool-window { width:29%; float:left; }
  #content-window { width:29%; float:left;}
  .info-window h1 { font-size: 14px; }
  .info-window p { font-size: 12px; }
  .info-window table, td { font-size: 12px; border: 1px solid gray; }
</style>

<script type="text/javascript" src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
var kml;
var map;
var markers = [];
var infoWindows = [];
var iconColors = ["blue", "brown", "darkgreen", "green", "orange", "paleblue", 
                  "pink", "purple", "red", "yellow"]

function initialize() {
    
    // Set the map.
    var latlng = new google.maps.LatLng(25, 0);
    var myOptions = {
        zoom: 2,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
    
    // Get the KML, set the XML object, and map all Placemarks.
    $.post("http://localhost/Plants/kml.php", {searchId: <?php echo $_REQUEST['searchId']; ?>}, function (data) {
        kml = data;
        mapKml();
    });
}

/**
 * Adds a marker to the map.
 * 
 * @link http://code.google.com/apis/maps/documentation/javascript/overlays.html
 * @param Element KML Placemark element.
 */
function addMarker(placemark) {
    
    // Map the marker.
    var name = $(placemark).children("name").text();
    var description = $(placemark).children("description").text();
    var coordinates = $(placemark).find("coordinates").text();
    var coordinatesArray = coordinates.split(",");
    
    // Scatter specimens with identical coordinates by randomizing their 
    // latitudes and longitudes.
    for (i in markers) {
        var existingLatitude = markers[i].getPosition().lat();
        var existingLongitude = markers[i].getPosition().lng();
        if (coordinatesArray[1] == existingLatitude 
         && coordinatesArray[0] == existingLongitude) {
             // Generate random numbers between -0.0001 and 0.0001.
             // See: http://www.kadimi.com/en/negative-random-87
             coordinatesArray[1] = existingLatitude + (((Math.random() * 2) + -1)/1000);
             coordinatesArray[0] = existingLongitude + ((Math.random() * 2) + -1)/1000;
         }
    }
    
    var marker = new google.maps.Marker({
        map: map, 
        position: new google.maps.LatLng(coordinatesArray[1], coordinatesArray[0]), 
        title: name
    });
    
    // Build info window content string.
    var contentString = '<div class="info-window">'
                      + '<h1>' + name + '</h1>'
                      + '<p><a href="' + description + '" target="_blank">View Specimen on JSTOR</a></p>'
                      + '<table>';
    $(placemark).find("Data").each(function (){
        var displayName = $(this).children("displayName").text();
        var value = $(this).children("value").text();
        contentString += '<tr><td>' + displayName + '</td><td>' + value + '</td></tr>';
    });
    contentString += '<tr><td>Coordinates</td><td>' + coordinates + '</td></tr>'
                   + '</table></div>';
    var infoWindow = new google.maps.InfoWindow({
        content: contentString, 
        maxWidth: 400
    });
    
    // Push the marker and info window to their respective arrays.
    var markersLength = markers.push(marker);
    infoWindows.push(infoWindow);
    
    // Set the info window click event to the marker.
    new google.maps.event.addListener(marker, 'click', function() {
        closeInfoWindows();
        infoWindows[markersLength - 1].open(map, marker);
    });
}

/**
 * Deletes all markers from the map by removing references to them.
 * 
 * @link http://code.google.com/apis/maps/documentation/javascript/overlays.html#RemovingOverlays
 */
function deleteMarkers() {
    for (i in markers) {
        markers[i].setMap(null);
    }
    closeInfoWindows();
    markers = [];
    infoWindows = [];
}

/**
 * Closes all open info windows.
 */
function closeInfoWindows()
{
    for (i in infoWindows) {
        infoWindows[i].close();
    }
}

/**
 * Map all KML Placemarks on the map.
 * 
 * @link http://articles.sitepoint.com/article/google-maps-api-jquery
 */
function mapKml() {
    deleteMarkers();
    $(kml).find("Placemark").each(function () {
        addMarker(this);
    });
}

/**
 * Unused proof of concept function that renders all KML Placemark data.
 * 
 * @link http://api.jquery.com/category/traversing/tree-traversal/
 */
function parseKml() {
    $(kml).find("Placemark").each(function () {
        var name = $(this).children("name").text();
        var description = $(this).children("description").text();
        $("#kml").append($("<a>", {href: description, text: name}));
        $("#kml").append('<table>');
        $(this).find("Data").each(function () {
            var dataName = $(this).attr("name");
            var dataDisplayName = $(this).children("displayName").text();
            var dataValue = $(this).children("value").text()
            $("#kml").append('<tr><td>' + dataDisplayName + '</td><td>' + dataValue + '</td></tr>');
        })
        $("#kml").append('</table>');
    });
}

/**
 * Get distinct values for the specified KML Data name.
 * 
 * @param string The KML Data name to search.
 */
function getSpecimens(name) {
    
    // Empty the content window.
    $("#content-window").empty();
    
    // Get all values for the specified Data name.
    var values = [];
    $(kml).find("Data[name='" + name + "']").each(function () {
        
        var value = $(this).children('value').text();
        
        // Remove duplicate values
        if ($.inArray(value, values) == -1 && value.length > 0) {
            values.push(value);
        }
    });
    
    // Append all values to the content window.
    
    var select = "<select onchange=\"mapSpecimens('" + name + "', this)\"><option>Choose one...</option>";
    $.each(values.sort(), function (index, value) {
        select += "<option>" + value + "</option>";
    });
    select += "</select>";
    $("#content-window").append(select);
}

/**
 * Place a marker on the map for every specimen that matches the search.
 * 
 * @param string The KML Data name to search.
 * @param HTMLSelectElement The HTTP select element containing the search string.
 */
function mapSpecimens(name, element) {
    deleteMarkers();
    var value = element.value;
    
    // Could use ":has(value):contains('{value}')" to select only those 
    // Placemarks with the specified value, but Escaping meta-characters in 
    // selectors with \\ does not always work.
    // See: http://stackoverflow.com/questions/739695/jquery-selector-value-escaping
    $(kml).find("Placemark:has(Data[name='" + name + "'])").each(function () {
        
        // Only map specimens with the specified value.
        if (value == $(this).find("Data[name='" + name + "']").children("value").text()) {
            addMarker(this);
        }
    });
}
</script>
</head>
<body onload="initialize()">
    <div id="map-canvas"></div>
    <div id="tool-window">
        <button onclick="alert(kml);">Check for KML</button>
        <button onclick="parseKml();">Parse KML</button>
        <button onclick="mapKml();">Map KML</button>
        <button onclick="getSpecimens('herbarium');">Get Herbariums</button>
        <button onclick="getSpecimens('collection_year');">Get Collection Years</button>
        <button onclick="getSpecimens('country');">Get Countries</button>
        <button onclick="getSpecimens('collector');">Get Collectors</button>
        <button onclick="deleteMarkers();">Delete Markers</button>
    </div>
    <div id="content-window"></div>
    <div id='kml'></div>
</body>