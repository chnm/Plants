<?php
class Process_Kml extends XMLWriter
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
     * @todo This method of getting geolocations based on rank is sub-optimal. 
     *       Tried optimizing the SQL to one statement using IF, CASE, UNION, 
     *       and GROUP BY with no success.
     * @param int $searchId The search ID.
     * @param int $limit The maximum amount of results.
     */
    public function write($searchId, $limit = 200)
    {
        // Fetch all resources for this search. Must limit total count to reduce 
        // memory load, which is considerable on large datasets.
        $sql = 'SELECT * 
                FROM resources r
                JOIN searches_resources sr 
                ON r.id = sr.resource_id 
                WHERE sr.search_id = ? 
                LIMIT ? ';
        $resources = $this->_db->fetchAll($sql, array($searchId, $limit));
        
        // Begin building the array containing valid geolocations for this search.
        $geolocations = array();

        // Iterate the resources.
        foreach ($resources as $resource) {
            
            // Iterate the geolocation services in rank order.
            foreach ($this->_services as $serviceId) {
                
                // Fetch the geolocation for this resource/service.
                $sql = 'SELECT * 
                        FROM geolocations  
                        WHERE resource_id = ? 
                        AND geolocation_service_id = ? 
                        AND latitude IS NOT NULL 
                        AND longitude IS NOT NULL';
                $geolocation = $this->_db->fetchRow($sql, array($resource['id'], $serviceId));
                
                // Stop iterating services is If the geolocation exists.
                if ($geolocation) {
                    break;
                }
            }
            
            // Continue to the next resource if no geolocation was found.
            if (!$geolocation) {
                continue;
            }
            
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
                                    'latitude'        => $geolocation['latitude'], 
                                    'longitude'       => $geolocation['longitude'], 
                                    'url'             => $url);
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