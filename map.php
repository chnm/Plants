<!DOCTYPE html>
<html>
<head>

<style type="text/css">
  html { height: 100% }
  body { height: 100%; margin: 0px; padding: 0px }
  #map-canvas { width:69%; height:100%; float:left; }
  #tool-window { width:29%; float:left; }
  #content-window { width:29%; float:left;}
</style>

<script type="text/javascript" src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
var kml;
var map;
var markers = [];

function initialize() {
    
    // Set the map.
    var latlng = new google.maps.LatLng(25, 0);
    var myOptions = {
        zoom: 2,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
    
    // Get the KML and set the XML object.
    $.post("http://localhost/Plants/kml.php", {searchId: <?php echo $_REQUEST['searchId']; ?>}, function (data) {
        kml = data;
        mapKml();
    });
}

// http://code.google.com/apis/maps/documentation/javascript/overlays.html#RemovingOverlays
function addMarker(point) {
    marker = new google.maps.Marker({
        position: point,
        map: map
    });
    markers.push(marker);
}
// Deletes all markers in the array by removing references to them
function deleteMarkers() {
    if (markers) {
        for (i in markers) {
            markers[i].setMap(null);
        }
        markers.length = 0;
    }
}

// http://articles.sitepoint.com/article/google-maps-api-jquery
function mapKml() {
    deleteMarkers();
    $(kml).find("Placemark").each(function () {
        var name = $(this).children("name").text();
        var description = $(this).children("description").text();
        var coordinates = $(this).find("coordinates").text().split(",");
        var point = new google.maps.LatLng(coordinates[1], coordinates[0]);
        addMarker(point);
    });
}

// http://api.jquery.com/category/traversing/tree-traversal/
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
    $.each(values.sort(), function (index, value) {
        // Adding onclick to $("<a>") selector works in Firefox but not in 
        // Chrome. Must write out the entire tag.
        $("#content-window").append($("<a href=\"#\" onclick=\"mapSpecimens('" + name + "', this)\">" + value + "</a><br />"));
    });
}

function mapSpecimens(name, element) {
    
    // Get the value from the passed element.
    var value = $(element).text();
    
    // Delete all the markers.
    deleteMarkers();
    
    // Could use ":has(value):contains('{value}')" to select only those 
    // Placemarks with the specified value, but Escaping meta-characters in 
    // selectors with \\ does not always work. See: http://stackoverflow.com/questions/739695/jquery-selector-value-escaping
    $(kml).find("Placemark:has(Data[name='" + name + "'])").each(function () {
        
        // Only map specimens with the specified value.
        if (value == $(this).find("Data[name='" + name + "']").children("value").text()) {
            var coordinates = $(this).find("coordinates").text().split(",");
            var point = new google.maps.LatLng(coordinates[1], coordinates[0]);
            addMarker(point);
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
        <button onclick="deleteMarkers();">Delete Markers</button>
    </div>
    <div id="content-window"></div>
    <div id='kml'></div>
</body>