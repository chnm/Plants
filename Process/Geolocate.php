<?php
class Plants_Process_Geolocate
{
    private $_db;
    
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->_db = $db;
    }
    
    public function geolocate($searchId)
    {
        // Find all geolocation services
        $sql = 'SELECT * FROM geolocation_services';
        $geolocationServices = $this->_db->fetchAll($sql);
        
        // Require the geolocation service classes and instantiate them.
        $services = array();
        foreach ($geolocationServices as $geolocationService) {
            require_once str_replace('_', DIRECTORY_SEPARATOR, 
                                     $geolocationService['class']) . '.php';
            
            // Geolocation service classes must implement 
            // Plants_Geolocation_Interface. Ignore ones that do not.
            $class = new $geolocationService['class']($this->_db);
            if ($class instanceof Plants_Geolocation_Interface) {
                $services[$geolocationService['id']] = $class;
            }
        }
        
        // Find all resources in this search.
        $sql = 'SELECT r.id, r.locality, r.country 
                FROM resources r 
                JOIN searches_resources sr 
                ON r.id = sr.resource_id
                WHERE sr.search_id = ? 
                AND (
                    r.locality IS NOT NULL 
                    OR r.country IS NOT NULL 
                )';
        $resources = $this->_db->fetchAll($sql, $searchId);
        
        // Require Zend_Db_Expr for SQL expressions.
        require_once 'Zend/Db/Expr.php';
        
        // Iterate all resources in this search.
        foreach ($resources as $resource) {
            
            // Set the query string.
            $queryParts = array();
            if ($resource['locality']) {
                $queryParts[] = $resource['locality'];
            }
            if ($resource['country']) {
                $queryParts[] = $resource['country'];
            }
            $query = implode(', ', $queryParts);
            
            // Iterate all geolocation services for each resource.
            foreach ($services as $serviceId => $service) {
                
                // Find whether the geolocation exists for the resource using 
                // the specified geolocation service.
                $sql = 'SELECT g.id
                        FROM geolocations g 
                        WHERE g.resource_id = ? 
                        AND g.geolocation_service_id = ?';
                $geolocationExists = $this->_db->fetchOne($sql, array($resource['id'], 
                                                                      $serviceId));
                
                // Do not geolocate if it already exists for this resource.
                if ($geolocationExists) {
                    continue;
                }
                
                // Getting "Unable to read response, or response is empty" 
                // errors here. Ignore and continue. Hopefully it will work 
                // next time this resource is geolocated.
                try {
                    $service->query($query, 1);
                } catch (Exception $e) {
                    continue;
                }
                
                // Set the geolocation array.
                $geolocation = array('resource_id'            => $resource['id'], 
                                     'geolocation_service_id' => $serviceId, 
                                     'query'                  => $query, 
                                     'response'               => $service->getResponse(), 
                                     'inserted'               => new Zend_Db_Expr('NOW()'));
                
                // Set the latitude and longitude if they exist. If there 
                // are no coordinates, set latitude and longitude to NULL. 
                // This will prevent future queries to this geolocation 
                // service for this resource.
                if ($service->getTotalCount()) {
                    $geolocation['latitude'] = $service->getLatitude(0);
                    $geolocation['longitude'] = $service->getLongitude(0);
                }
                
                $this->_db->insert('geolocations', $geolocation);
            }
        }
    }
}