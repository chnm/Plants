<?php
/**
 * This class defines an object that geolocates resources ingested from the 
 * JSTOR Plant Science database. It can query multiple multiple geolocation 
 * service APIs.
 * 
 * @link http://plants.jstor.org/
 */
class Plants_Process_Geolocate
{
    const CLASS_PREFIX = 'Plants_Geolocation_';
    
    private $_db;
    private $_stopWords = array();
    private $_stopWordsDelimited = '
southwest of
northwest of
southeast of
northeast of
south of
north of
east of
west of
N
S
E
W
NE
NW
SE
SW
from
to
above
below
vicinity of
the headwaters of
on rocky banks
on rocks
hillside
slope of
toward
summit of
the summit
summit camp
trail to summit
below summit
turnoff to summit
vicinity of summit
slopes and summit of
vicinity
approach from
approach to
highest point of
cloud forest
rain forest
virgin forest
montane rain forest
montane forest
elfin woods
forested
along
connecting with
along trail
along road
along rd
suroeste de
noroeste de
sureste de
noreste de
sur de
norte de
este de
oeste de
mogote de
encima
abajo
In the forest of
Dans les ravins chez les
Near
Way to
De/ à
Route de
Environs de
Entre/ et
Between/and
and viccinity
du chemin de fer
dans la foret aux environs de
Croît dans les forêts de
Croît dans les gorges des montagnes des environs de
près des ravins du
Dans les sables du
Montagnes arides du
Bords du
Forêts fertiles du
Basfonds inondés près du
Sur les bords de
Pays des
Cercle de/entre/de
Vallée de
Main road from
Bassin du
Plains of
Croit sur les rochers, près des eaux couvantes du pays des
in
to
from base of';
    
    /**
     * Construct the geolocate object.
     * 
     * @param Zend_Db_Adapter_Abstract $db
     */
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->_db = $db;
        $this->_setStopWords();
    }
    
    /**
     * Geolocate the resources in the provided search.
     * 
     * @param int $searchId
     */
    public function geolocate($searchId)
    {
        // Find all geolocation services
        $sql = 'SELECT * FROM geolocation_services';
        $geolocationServices = $this->_db->fetchAll($sql);
        
        // Iterate the geolocation services.
        $services = array();
        foreach ($geolocationServices as $geolocationService) {
            
            // Set the class name, require the classes and instantiate them.
            $className = self::CLASS_PREFIX . $geolocationService['class_suffix'];
            require_once str_replace('_', DIRECTORY_SEPARATOR, $className . '.php');
            
            // Geolocation service classes must implement 
            // Plants_Geolocation_Interface. Ignore ones that do not.
            $class = new $className;
            if ($class instanceof Plants_Geolocation_Interface) {
                $services[$geolocationService['id']] = $class;
            }
        }
        
        // Find all resources in this search.
        $sql = 'SELECT r.id, r.locality, r.country_name 
                FROM resources r 
                JOIN searches_resources sr 
                ON r.id = sr.resource_id
                WHERE sr.search_id = ? 
                AND (
                    r.locality IS NOT NULL 
                    OR r.country_name IS NOT NULL 
                )';
        $resources = $this->_db->fetchAll($sql, $searchId);
        
        // Require Zend_Db_Expr for SQL expressions.
        require_once 'Zend/Db/Expr.php';
        
        // Iterate all resources in this search.
        foreach ($resources as $resource) {
            
            $locality = $this->_filterLocality($resource['locality']);
            
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
                    $service->query($locality, $resource['country_name']);
                } catch (Exception $e) {
                    continue;
                }
                
                // Set the geolocation array.
                $geolocation = array('resource_id'            => $resource['id'], 
                                     'geolocation_service_id' => $serviceId, 
                                     'inserted'               => new Zend_Db_Expr('NOW()'));
                
                // Set the latitude and longitude if they exist. If there 
                // are no coordinates, set latitude and longitude to NULL. 
                // This will prevent future queries to this geolocation 
                // service for this resource.
                if ($service->getTotalCount()) {
                    $geolocation['latitude'] = $service->getLatitude();
                    $geolocation['longitude'] = $service->getLongitude();
                    $geolocation['request_uri'] = $service->getRequestUri();
                }
                
                $this->_db->insert('geolocations', $geolocation);
            }
            
            // Record the highest ranking service with coordinates to the 
            // resources_geolocations table.
            $sql = 'SELECT g.id 
                    FROM geolocations g 
                    JOIN geolocation_services gs 
                    ON g.geolocation_service_id = gs.id 
                    WHERE g.resource_id = ? 
                    AND g.latitude IS NOT NULL 
                    AND g.longitude IS NOT NULL 
                    ORDER BY gs.rank 
                    LIMIT 1';
            $geolocationId = $this->_db->fetchOne($sql, $resource['id']);
            
            // Save the resource/geolocation relationship if a valid geolocation 
            // is found.
            if ($geolocationId) {
                
                // Check to see if a resource/geolocation relationship already 
                // exists for the resource.
                $sql = 'SELECT id 
                        FROM resources_geolocations 
                        WHERE resource_id = ?';
                $resourceGeolocationExists = $this->_db->fetchOne($sql, $resource['id']);
                
                // Update the geolocation ID if a resource/geolocation 
                // relationship already exists. This way more geolocation 
                // services can be added that rank higher than an existing 
                // relationship.
                if ($resourceGeolocationExists) {
                    $this->_db->update('resources_geolocations', 
                                       array('geolocation_id' => $geolocationId), 
                                       'resource_id = ' . $resource['id']);
                
                // Insert a new resource/geolocation relationship.
                } else {
                    $this->_db->insert('resources_geolocations', 
                                       array('resource_id' => $resource['id'], 
                                             'geolocation_id' => $geolocationId));
                }
            }
        }
    }
    
    /**
     * Set the stop words to be parsable.
     */
    private function _setStopWords()
    {
        // Explode the stop words by newline.
        $stopWords = explode("\n", $this->_stopWordsDelimited);
        // Trim every element. Having problems trimming some whitespace 
        // characters.
        array_walk($stopWords, 'trim');
        // Filter out empty elements.
        $stopWords = array_filter($stopWords);
        // Order array by string length, longest first.
        usort($stopWords, array($this, 'cmpStopWords'));
        $this->_stopWords = $stopWords;
    }
    
    /**
     * String comparisom callback that orders an array by string length, longest 
     * first.
     * 
     * @param string $a
     * @param string $b
     * @return int
     */
    private function cmpStopWords($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        return mb_strlen($a, 'utf8') > mb_strlen($b, 'utf8') ? -1 : 1;
    }
    
    /**
     * Filter stopwords, punctuation, and unneeded spaces from locality string.
     * 
     * @param string $locality The locality to be filtered.
     * @return string The filtered locality.
     */
    private function _filterLocality($locality)
    {
        $locality = trim($locality);
        
        // Remove stop words.
        foreach ($this->_stopWords as $stopWord) {
            $locality = preg_replace('/\b' . preg_quote($stopWord, '/') . '\b/u', '', $locality);
        }
        
        // Remove unneeded spaces.
        $locality = preg_replace('/\s\s+/', ' ', $locality);
        
        return $locality;
    }
}