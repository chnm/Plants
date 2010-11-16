<?php
require_once 'Plants/Geolocation/Interface.php';

/**
 * This class defines a PlaceFinder object.
 * 
 * @link http://developer.yahoo.com/geo/placefinder/guide/
 */
class Plants_Geolocation_PlaceFinder implements Plants_Geolocation_Interface
{
    const URL = 'http://where.yahooapis.com/geocode';
    
    private $_client;
    private $_responses = array();
    private $_totalCount;
    private $_latitude;
    private $_longitude;
    
    public function __construct()
    {
        require_once 'Zend/Http/Client.php';
        $this->_client = new Zend_Http_Client(self::URL);
        $this->_client->setConfig(array('keepalive' => true));
        $this->_client->setParameterGet('flags', 'J'); // JSON format
    }
    
    public function query($location, $country = null)
    {
        // There was a response with a country match.
        if ($this->_set($location, $country)) {
            return;
        }
        
        // There was at least one response, but no country matches. Set the 
        // coordinates from the first result.
        if ($this->_responses) {
            $this->_totalCount = $this->_responses[0]->ResultSet->Found;
            $this->_latitude = $this->_responses[0]->ResultSet->Results[0]->latitude;
            $this->_longitude = $this->_responses[0]->ResultSet->Results[0]->longitude;
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
    
    private function _set($location, $country)
    {
        // Reset the responses.
        $this->_responses = array();
        
        // Set the queries in priority order.
        $queries = array("$location $country", $location, $country);
        
        // Iterate the queries.
        foreach ($queries as $query) {
            
            // Make the request.
            $this->_client->setParameterGet('q', trim($query));
            $response = json_decode($this->_client->request()->getBody());
            
            // Continue to the next query if there are no results.
            if (!$response->ResultSet->Found) {
                continue;
            }
            
            // Set the responses.
            $this->_responses[] = $response;
            
            // Set the coordinates from the first result with a matching country.
            foreach ($response->ResultSet->Results as $result) {
                if (strstr($country, $result->country)) {
                    $this->_totalCount = $response->ResultSet->Found;
                    $this->_latitude = $result->latitude;
                    $this->_longitude = $result->longitude;
                    return true;
                }
            }
        }
        
        // No matching countries found for all queries.
        return false;
    }
}