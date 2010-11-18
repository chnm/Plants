<?php
require_once 'Plants/Geolocation/Interface.php';

/**
 * This class defines a Nominatim object.
 * 
 * @link http://wiki.openstreetmap.org/wiki/Nominatim
 */
class Plants_Geolocation_Nominatim implements Plants_Geolocation_Interface
{
    const URL = 'http://nominatim.openstreetmap.org/search';
    
    private $_client;
    private $_totalCount;
    private $_latitude;
    private $_longitude;
    private $_requestUri;
    
    public function __construct()
    {
        require_once 'Zend/Http/Client.php';
        $this->_client = new Zend_Http_Client(self::URL);
        $this->_client->setConfig(array('keepalive' => true));
        $this->_client->setParameterGet('format', 'json');
        $this->_client->setParameterGet('addressdetails', '1');
    }
    
    public function query($location, $country)
    {
        
        // Set the queries in priority order.
        $queries = array("$location $country", $location, $country);
        
        // Iterate the queries.
        foreach ($queries as $query) {
            
            // Make the request.
            $this->_client->setParameterGet('q', $query);
            $response = json_decode($this->_client->request()->getBody());
            
            // Continue to the next query if there are no results.
            if (!count($response)) {
                continue;
            }
            
            // Set the first response with results.
            if (!isset($firstResponse)) {
                $firstResponse = $response;
                $firstRequest = $this->_client->getLastRequest();
            }
            
            // Set the coordinates from the first result with a matching country.
            foreach ($response as $result) {
                if (isset($result->address->country) && false !== @strpos($country, $result->address->country)) {
                    $this->_totalCount = count($response);
                    $this->_latitude = $result->lat;
                    $this->_longitude = $result->lon;
                    preg_match('/GET (.+) /', $this->_client->getLastRequest(), $matches);
                    $this->_requestUri = $matches[1];
                    return;
                }
            }
        }
        
        // There was at least one response, but no country matches. Set the 
        // coordinates from the first result.
        if (isset($firstResponse)) {
            $this->_totalCount = count($firstResponse);
            $this->_latitude = $firstResponse[0]->lat;
            $this->_longitude = $firstResponse[0]->lon;
            preg_match('/GET (.+) /', $firstRequest, $matches);
            $this->_requestUri = $matches[1];
            return;
        }
        
        // There were no results for all queries.
        $this->_totalCount = 0;
    }
    
    public function getTotalCount()
    {
        return $this->_totalCount;
    }
    
    public function getLatitude()
    {
        return $this->_latitude;
    }
    
    public function getLongitude()
    {
        return $this->_longitude;
    }
    
    public function getRequestUri()
    {
        return $this->_requestUri;
    }
}