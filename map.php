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
    
    // Get the KML, set the XML object, and map all Placemarks.
    $.post("http://localhost/Plants/kml.php", {searchId: <?php echo $_REQUEST['searchId']; ?>}, function (data) {
        kml = data;
        mapKml();
    });
}

/**
 * Adds a marker to the map.
 * 
 * @link http://api.jquery.com/category/traversing/tree-traversal/
 * @param Element KML Placemark element.
 */
function addMarker(placemark) {
    var name = $(placemark).children("name").text();
    var description = $(placemark).children("description").text();
    var coordinates = $(placemark).find("coordinates").text().split(",");
    var marker = new google.maps.Marker({
        map: map, 
        position: new google.maps.LatLng(coordinates[1], coordinates[0]), 
        title: name
    });
    markers.push(marker);
}

/**
 * Deletes all markers from the map by removing references to them.
 * 
 * @link http://code.google.com/apis/maps/documentation/javascript/overlays.html#RemovingOverlays
 */
function deleteMarkers() {
    if (markers) {
        for (i in markers) {
            markers[i].setMap(null);
        }
        markers.length = 0;
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