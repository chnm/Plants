<?php
// Testing runtime KML generation. Will have to limit total count to reduce 
// memory load, which is considerable on large datasets.

require 'config.php';

// Set the search ID from which to generate the KML.
$searchId = 1;

// This method of getting geolocations based on rank is sub-optimal. Tried 
// optimizing the SQL to one statement using IF, CASE, UNION, and GROUP BY with 
// no success.

// Get all geolocation services by rank. [rank] => [id]
$sql = 'SELECT rank, id 
        FROM geolocation_services 
        ORDER BY rank';
$services = $db->fetchPairs($sql);

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
    
    // Iterate the geolocation services in rank order.
    foreach ($services as $serviceId) {
        
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
        
        // Build the URL to JSTOR.
        $pattern = '/^.+\.([^.]+)\.([^.]+)$/e';
        $replacement = "'http://plants.jstor.org/'.strtolower('$1').'/'.strtolower('$2')";
        $url = preg_replace($pattern, $replacement, $resource['doi']);
        
        $geolocations[] = array('name'            => $resource['title'], 
                                'herbarium'       => $resource['herbarium'], 
                                'collector'       => $resource['collector'], 
                                'locality'        => $resource['locality'], 
                                'country'         => $resource['country'], 
                                'collection_year' => $resource['collection_year'], 
                                'collection_date' => $resource['collection_date'], 
                                'latitude'        => $geolocation['latitude'], 
                                'longitude'       => $geolocation['longitude'], 
                                'url'             => $url);
    }
}
//print_r($geolocations);
//exit;

// http://code.google.com/apis/kml/documentation/kml_tut.html#placemarks
/*
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Placemark>
    <name>Simple placemark</name>
    <ExtendedData>                       
      <Data name="string">
        <displayName>...</displayName>    <!-- string -->
        <value>...</value>                <!-- string -->
      </Data>
    </ExtendedData>  
    <Point>
      <coordinates>-122.0822035425683,37.42228990140251,0</coordinates>
    </Point>
  </Placemark>
</kml>
*/
$extendedData = array('locality'        => 'Locality', 
                      'country'         => 'Country', 
                      'herbarium'       => 'Herbarium', 
                      'collector'       => 'Collector', 
                      'collection_year' => 'Collection Year', 
                      'collection_date' => 'Collection Date', 
                      'url'             => 'URL');

// Google Maps parses out HTML from all elements. The only exception to this, it 
// seems, is in a description element that contains a solitary URL and no other 
// text. This URL is parsed as a hyperlink. Also it seems that Google Maps sets 
// the point limit to 200, no matter how many Placemarks there are in the 
// uploaded KML.

header ('Content-Type: text/xml'); 
$writer = new XMLWriter();
$writer->openURI('php://output'); 
$writer->startDocument('1.0', 'UTF-8');
$writer->setIndent(false); // Do not indent to greatly reduce filesize.
$writer->startElement('kml');
$writer->writeAttribute('xmlns', 'http://www.opengis.net/kml/2.2');
    $writer->startElement('Document');
        // Iterate the Placemark elements
        foreach ($geolocations as $geolocation) {
        $writer->startElement('Placemark');
            $writer->writeElement('name', $geolocation['name']);
            // Google Maps does not parse ExtendedData if both the description 
            // and ExtendedData elements are present.
            $writer->writeElement('description', $geolocation['url']);
            $writer->startElement('ExtendedData');
                // Iterate the Data elements.
                foreach ($extendedData as $name => $displayName) {
                $writer->startElement('Data');
                $writer->writeAttribute('name', $name);
                    $writer->writeElement('displayName', $displayName);
                    $writer->writeElement('value', $geolocation[$name]);
                $writer->endElement(); // end Data
                }
            $writer->endElement(); // end ExtendedData
            $writer->startElement('Point');
                $writer->writeElement('coordinates', $geolocation['longitude'] . ',' . $geolocation['latitude']);
            $writer->endElement(); // end Point
        $writer->endElement(); // end Placemark
        }
    $writer->endElement(); // end Document
$writer->endElement();
$writer->endDocument(); 
$writer->flush();