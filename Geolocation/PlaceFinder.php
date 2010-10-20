<?php
require_once 'Plants/Geolocation/Interface.php';
class Plants_Geolocation_PlaceFinder implements Plants_Geolocation_Interface
{
    const NAME = 'Yahoo! PlaceFinder';
    const URL = 'http://where.yahooapis.com/geocode';
    
    private $_response;
    private $_result;
    
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        require_once 'Zend/Http/Client.php';
        $this->_client = new Zend_Http_Client(self::URL);
        $this->_client->setConfig(array('keepalive' => true, 'timeout' => 100, 
                                        'storeresponse' => false));
        $this->_client->setParameterGet('flags', 'J'); // JSON format
    }
    
    public function query($location, $limit)
    {
        // The "count" control parameter does not work as of 2010/10/20. 
        // PlaceFinder returns all results.
        $this->_client->setParameterGet('count', $limit);
        $this->_client->setParameterGet('q', $location);
        $response = $this->_client->request();
        $this->_response = $response->getBody();
        $this->_result = json_decode($response->getBody());
    }
    
    public function getResponse()
    {
        return $this->_response;
    }
    
    public function getTotalCount()
    {
        return $this->_result->ResultSet->Found;
    }
    
    public function getLatitude($index)
    {
        return $this->_result->ResultSet->Results[$index]->latitude;
    }
    
    public function getLongitude($index)
    {
        return $this->_result->ResultSet->Results[$index]->longitude;
    }
    
    public function getLocation($index)
    {
        $result = $this->_result->ResultSet->Results[$index];
        $name = array(trim($result->neighborhood), 
                      trim($result->city), 
                      trim($result->county), 
                      trim($result->state), 
                      trim($result->country));
        $name = array_filter($name);
        return implode(' ', $name);
    }
}