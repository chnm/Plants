<!DOCTYPE html>
<html>
<head>

<style type="text/css">
  html { height: 100% }
  body { height: 100%; margin: 0px; padding: 0px }
  #map_canvas { height: 100% }
</style>

<script type="text/javascript" src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
var kml;
var map;
var markers = [];

function initialize() {
    
    // Set the map.
    var latlng = new google.maps.LatLng(0, 0);
    var myOptions = {
        zoom: 2,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById("map"), myOptions);
    
    // Get the KML and set the XML object.
    $.post("http://localhost/Plants/kml.php", {searchId: 1}, function (data) {
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

// Identical herbariums are named differently for every resource. May have to 
// parse out formal name of herbarium during ingest.
function getHerbariums() {
    // http://www.hunlock.com/blogs/Mastering_Javascript_Arrays
    var herbariums = [];
    // http://api.jquery.com/category/selectors/
    $(kml).find("Data[name='herbarium']").each(function () {
        var value = $(this).children('value').text();
        if ($.inArray(value, herbariums) == -1) {
            herbariums.push(value);
        }
    });
    alert(herbariums);
}

function getCollectionYears() {
    var collectionYears = [];
    $(kml).find("Data[name='collection_year']").each(function () {
        var value = $(this).children('value').text();
        if ($.inArray(value, collectionYears) == -1 && value.length > 0) {
            collectionYears.push(value);
        }
    });
    $.each(collectionYears.sort(), function (index, value) {
        // Adding onclick to $("<a>") selector works in Firefox, but not in 
        // Chrome. Must write out the entire tag.
        $("#content-window").append($("<a href=\"#\" onclick=\"mapCollectionYears(" + value + ")\">" + value + "</a>"));
        $("#content-window").append($("<br />"));
    });
}

function mapCollectionYears(value) {
    deleteMarkers();
    $(kml).find("Placemark:has(Data[name='collection_year']):has(value):contains(" + value + ")").each(function () {
        var coordinates = $(this).find("coordinates").text().split(",");
        var point = new google.maps.LatLng(coordinates[1], coordinates[0]);
        addMarker(point);
    });
}
</script>
</head>
<body onload="initialize()">
    <div id="map" style="width:79%; height:100%; float:left"></div>
    <div id="content-window" style="width:19%; height:100%; float:left">
        <button onclick="alert(kml);">Check for KML</button>
        <button onclick="parseKml();">Parse KML</button>
        <button onclick="mapKml();">Map KML</button>
        <button onclick="getHerbariums();">Get Herbariums</button>
        <button onclick="getCollectionYears();">Get Collection Years</button>
        <button onclick="deleteMarkers();">Delete Markers</button>
    </div>
    <div id='kml'></div>
</body>