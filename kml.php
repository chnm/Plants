<?php
// http://code.google.com/apis/kml/documentation/kml_tut.html#placemarks
/*
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Placemark>
    <name>Simple placemark</name>
    <description>Attached to the ground. Intelligently places itself 
       at the height of the underlying terrain.</description>
    <Point>
      <coordinates>-122.0822035425683,37.42228990140251,0</coordinates>
    </Point>
  </Placemark>
</kml>
*/
require 'config.php';

$searchId = 1;
$servicePriority = array(2,3,1); // PlaceFinder(2),GeoNames(3),Nominitum(1)

// Fetch all resources for this search.
$sql = 'SELECT * 
        FROM searches_resources sr 
        JOIN resources r 
        ON sr.resource_id = r.id 
        WHERE search_id = ?';
$resources = $db->fetchAll($sql, $searchId);

// Begin building the array containing valid geolocations for this search.
$geolocations = array();

// Iterate the resources.
foreach ($resources as $resource) {
    
    // Iterate the geolocation services in priority order.
    foreach ($servicePriority as $serviceId) {
        
        // Fetch the geolocation for this resource/service.
        $sql = 'SELECT * 
                FROM geolocations  
                WHERE resource_id = ? 
                AND geolocation_service_id = ? 
                AND latitude IS NOT NULL 
                AND longitude IS NOT NULL 
                LIMIT 1';
        $geolocation = $db->fetchRow($sql, array($resource['id'], $serviceId));
        
        // Stop iterating services is If the geolocation exists.
        if ($geolocation) {
            break;
        }
    }
    
    // If a geolocation was found, add it to the geolocations for this search.
    if ($geolocation) {
        $geolocations[] = array($resource['title'], 
                                $resource['herbarium'], 
                                $resource['locality'], 
                                $resource['country'], 
                                $geolocation['geolocation_service_id'], 
                                $geolocation['latitude'], 
                                $geolocation['longitude']);
    }
}
print_r($geolocations);