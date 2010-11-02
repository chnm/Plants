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
var ccIcons = [
    "blue_MarkerA.png", "brown_MarkerA.png", "darkgreen_MarkerA.png", 
    "green_MarkerA.png", "orange_MarkerA.png", "paleblue_MarkerA.png", 
    "pink_MarkerA.png", "purple_MarkerA.png", "red_MarkerA.png", 
    "yellow_MarkerA.png", 
    "blue_MarkerB.png", "brown_MarkerB.png", "darkgreen_MarkerB.png", 
    "green_MarkerB.png", "orange_MarkerB.png", "paleblue_MarkerB.png", 
    "pink_MarkerB.png", "purple_MarkerB.png", "red_MarkerB.png", 
    "yellow_MarkerB.png", 
    "blue_MarkerC.png", "brown_MarkerC.png", "darkgreen_MarkerC.png", 
    "green_MarkerC.png", "orange_MarkerC.png", "paleblue_MarkerC.png", 
    "pink_MarkerC.png", "purple_MarkerC.png", "red_MarkerC.png", 
    "yellow_MarkerC.png", 
    "blue_MarkerD.png", "brown_MarkerD.png", "darkgreen_MarkerD.png", 
    "green_MarkerD.png", "orange_MarkerD.png", "paleblue_MarkerD.png", 
    "pink_MarkerD.png", "purple_MarkerD.png", "red_MarkerD.png", 
    "yellow_MarkerD.png", 
    "blue_MarkerE.png", "brown_MarkerE.png", "darkgreen_MarkerE.png", 
    "green_MarkerE.png", "orange_MarkerE.png", "paleblue_MarkerE.png", 
    "pink_MarkerE.png", "purple_MarkerE.png", "red_MarkerE.png", 
    "yellow_MarkerE.png"
]

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
             // Generate random numbers between −0.001 and 0.001.
             // See: http://www.kadimi.com/en/negative-random-87
             coordinatesArray[1] = existingLatitude + ((Math.random() * 2) + -1)/100;
             coordinatesArray[0] = existingLongitude + ((Math.random() * 2) + -1)/100;
         }
    }
    
    
    
    var marker = new google.maps.Marker({
        map: map, 
        position: new google.maps.LatLng(coordinatesArray[1], coordinatesArray[0]), 
        title: name
    });
    
    var infoWindow = new google.maps.InfoWindow({
        content: placemark, 
        maxWidth: 400
    });
    
    // Push the marker and info window to their respective arrays.
    var markersLength = markers.push(marker);
    infoWindows.push(infoWindow);
    
    // Set the info window click event to the marker.
    new google.maps.event.addListener(marker, 'click', function() {
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
        closeInfoWindows();
        infoWindows[markersLength - 1].setContent(contentString);
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

/**
 * Color code the specimen markers according to herbarium.
 */
function colorCodeHerbariums() {
    $("#cc-herbariums").empty();
    var ccHerbariums = [];
    
    // Iterate the markers.
    for (i in markers) {
        
        // Get the herbarium.
        var ccHerbarium = $(infoWindows[i].getContent()).find("Data[name='herbarium']").children("value").text();
        
        // Build an array of unique herbariums.
        var ccHerbariumKey = $.inArray(ccHerbarium, ccHerbariums);
        if (ccHerbariumKey == -1) {
            ccHerbariumKey = ccHerbariums.push(ccHerbarium) - 1;
        }
        
        // Set the marker to its herbarium's corresponding color coded icon.
        markers[i].setIcon("icons/" + ccIcons[ccHerbariumKey]);
    }
    
    // Display the herbarium color table.
    var ccHerbariumTable = '<table>';
    for (i in ccHerbariums) {
        ccHerbariumTable += '<tr><td><img src="icons/' + ccIcons[i] + '" /></td><td>' + ccHerbariums[i] + '</td></tr>';
    }
    ccHerbariumTable += '</table>';
    $("#cc-herbariums").append(ccHerbariumTable);
}
</script>
</head>
<body onload="initialize()">
    <div id="map-canvas"></div>
    <div id="tool-window">
        <button onclick="alert(kml);">Check for KML</button>
        <button onclick="parseKml();">Parse KML</button>
        <button onclick="mapKml();">Map KML</button>
        <button onclick="colorCodeHerbariums();">Color Code Herbariums</button>
        <button onclick="getSpecimens('herbarium');">Get Herbariums</button>
        <button onclick="getSpecimens('collection_year');">Get Collection Years</button>
        <button onclick="getSpecimens('country');">Get Countries</button>
        <button onclick="getSpecimens('collector');">Get Collectors</button>
        <button onclick="deleteMarkers();">Delete Markers</button>
    </div>
    <div id="content-window"></div>
    <div id="cc-herbariums"></div>
    <div id='kml'></div>
</body>