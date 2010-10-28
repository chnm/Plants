<?php
/**
 * This class describes an object that writes Keyhole Markup Language (KML) for 
 * geolocated resources ingested from the JSTOR Plant Science database.
 * 
 * @link http://plants.jstor.org/
 * @link http://code.google.com/apis/kml/documentation/
 */
class Plants_Process_Kml extends XMLWriter
{
    private $_db;
    private $_services;
    private $_extendedData = array('locality'        => 'Locality', 
                                   'country'         => 'Country', 
                                   'herbarium'       => 'Herbarium', 
                                   'collector'       => 'Collector', 
                                   'collection_year' => 'Collection Year', 
                                   'collection_date' => 'Collection Date', 
                                   'url'             => 'URL');

    
    /**
     * Construct the KML object.
     * 
     * @param Zend_Db_Adapter_Abstract $db
     */
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->_db = $db;
        
        // Get all geolocation services by rank. [rank] => [id]
        $sql = 'SELECT rank, id 
                FROM geolocation_services 
                ORDER BY rank';
        $this->_services = $this->_db->fetchPairs($sql);
        
    }
    
    /**
     * Writes KML during runtime.
     * 
     * @param int $searchId The search ID from which to generate the KML.
     * @param int $limit The maximum amount of results.
     * @param bool $xmlContentType Whether to include a text/xml content type
     * header prior to XML output.
     */
    public function write($searchId, $limit = 200, $xmlContentType = false)
    {
        /* GET GEOLOCATION DATA */
        
        // Fetch all resources for this search that have coordinates.
        $sql = 'SELECT r.*, g.latitude, g.longitude 
                FROM searches_resources sr 
                JOIN resources r 
                ON sr.resource_id = r.id 
                JOIN resources_geolocations rg 
                ON r.id = rg.resource_id 
                JOIN geolocations g 
                ON rg.geolocation_id = g.id 
                WHERE sr.search_id = ? 
                LIMIT ?';
        $resources = $this->_db->fetchAll($sql, array($searchId, $limit));
        
        // Begin building the array containing valid geolocations for this search.
        $geolocations = array();

        // Iterate the resources.
        foreach ($resources as $resource) {
            
            // Build the URL to JSTOR.
            $pattern = '/^.+\.([^.]+)\.([^.]+)$/e';
            $replacement = "'http://plants.jstor.org/'.strtolower('$1').'/'.strtolower('$2')";
            $url = preg_replace($pattern, $replacement, $resource['doi']);
            
            // Add the geolocation to this search.
            $geolocations[] = array('name'            => $resource['title'], 
                                    'herbarium'       => $resource['herbarium'], 
                                    'collector'       => $resource['collector'], 
                                    'locality'        => $resource['locality'], 
                                    'country'         => $resource['country'], 
                                    'collection_year' => $resource['collection_year'], 
                                    'collection_date' => $resource['collection_date'], 
                                    'latitude'        => $resource['latitude'], 
                                    'longitude'       => $resource['longitude'], 
                                    'url'             => $url);
        }
        
        /* WRITE KML */
        
        // Content type must be text/xml to render properly in browsers.
        if ($xmlContentType) {
            header ('Content-Type: text/xml');
        }
        
        // Google Maps parses out HTML from all elements. The only exception to 
        // this, it seems, is in a description element that contains a solitary 
        // URL and no other text. This URL is parsed as a hyperlink. Also it 
        // seems that Google Maps sets the point limit to 200, no matter how 
        // many Placemarks there are in the uploaded KML.
        
        $this->openURI('php://output'); 
        $this->startDocument('1.0', 'UTF-8');
        $this->setIndent(false); // Do not indent to greatly reduce filesize.
        $this->startElement('kml');
        $this->writeAttribute('xmlns', 'http://www.opengis.net/kml/2.2');
            $this->startElement('Document');
                // Iterate the Placemark elements
                foreach ($geolocations as $geolocation) {
                $this->startElement('Placemark');
                    $this->writeElement('name', $geolocation['name']);
                    // Google Maps does not parse ExtendedData if both the 
                    // description and ExtendedData elements are present.
                    $this->writeElement('description', $geolocation['url']);
                    $this->startElement('ExtendedData');
                        // Iterate the Data elements.
                        foreach ($this->_extendedData as $name => $displayName) {
                        $this->startElement('Data');
                        $this->writeAttribute('name', $name);
                            $this->writeElement('displayName', $displayName);
                            $this->writeElement('value', $geolocation[$name]);
                        $this->endElement(); // end Data
                        }
                    $this->endElement(); // end ExtendedData
                    $this->startElement('Point');
                        $this->writeElement('coordinates', 
                                            $geolocation['longitude'] . ',' . $geolocation['latitude']);
                    $this->endElement(); // end Point
                $this->endElement(); // end Placemark
                }
            $this->endElement(); // end Document
        $this->endElement();
        $this->endDocument(); 
        $this->flush();
    }
}