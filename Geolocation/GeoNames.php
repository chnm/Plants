<?php
require_once 'Plants/Geolocation/Interface.php';
class Plants_Geolocation_GeoNames implements Plants_Geolocation_Interface
{
    const NAME = 'GeoNames';
    const URL = 'http://ws.geonames.org/search';
    
    private $_response;
    private $_result;
    
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        require_once 'Zend/Http/Client.php';
        $this->_client = new Zend_Http_Client(self::URL);
        $this->_client->setConfig(array('keepalive' => true, 'timeout' => 100, 
                                        'storeresponse' => false));
        $this->_client->setParameterGet('type', 'json');
    }
    
    public function query($location, $limit)
    {
        $this->_client->setParameterGet('maxRows', $limit);
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
        return $this->_result->totalResultsCount;
    }
    
    public function getLatitude($index)
    {
        return $this->_result->geonames[$index]->lat;
    }
    
    public function getLongitude($index)
    {
        return $this->_result->geonames[$index]->lng;
    }
    
    public function getLocation($index)
    {
        $result = $this->_result->geonames[$index];
        $name = array(trim($result->name), 
                      trim($result->adminName1), 
                      trim($result->countryName));
        $name = array_filter($name);
        return implode(' ', $name);
    }
}