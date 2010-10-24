<?php
require_once 'Plants/Geolocation/Interface.php';
class Plants_Geolocation_Nominatim implements Plants_Geolocation_Interface
{
    const URL = 'http://nominatim.openstreetmap.org/search';
    
    private $_client;
    private $_result;
    
    public function __construct()
    {
        require_once 'Zend/Http/Client.php';
        $this->_client = new Zend_Http_Client(self::URL);
        $this->_client->setConfig(array('keepalive' => true, 'timeout' => 100));
        $this->_client->setParameterGet('format', 'json');
    }
    
    public function query($location, $limit)
    {
        $this->_client->setParameterGet('limit', $limit);
        $this->_client->setParameterGet('q', $location);
        $response = $this->_client->request();
        $this->_result = json_decode($response->getBody());
    }
    
    public function getRequestUri()
    {
        preg_match('/GET (.+) /', $this->_client->getLastRequest(), $matches);
        return $matches[1];
    }
    
    public function getResponse()
    {
        return $this->_client->getLastResponse()->getBody();
    }
    
    public function getTotalCount()
    {
        return count($this->_result);
    }
    
    public function getLatitude($index)
    {
        return $this->_result[$index]->lat;
    }
    
    public function getLongitude($index)
    {
        return $this->_result[$index]->lon;
    }
    
    public function getLocation($index)
    {
        return $this->_result[$index]->display_name;
    }
}