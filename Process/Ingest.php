<?php
class Plants_Process_Ingest
{
    const URL           = 'http://plants.jstor.org/';
    const PATH_SEARCH   = 'search';
    const PATH_LOGIN    = 'action/doLogin';
    const PATH_DOWNLOAD = 'action/batchDoisDataDownload';
    const PATH_CITATION = 'action/showCitation';
    
    private $_db;
    private $_username;
    private $_password;
    
    // [local name] => [JSTOR name, GET query]
    private $_parameterFields = array(
        'collector'      => 'Collector_fld', 
        'date'           => 'Date_fld', 
        'geographyId'    => 'st', 
        'herbariumId'    => 'st', 
        'resourceTypeId' => 't', 
        'text'           => 'searchText', 
    );
    
    // [local name] => [JSTOR name, XPath].
    private $_metadataFields = array(
        'collection'          => 'Collection', 
        'collection_altitude' => 'Collection altitude', 
        'collection_date'     => 'Collection date', 
        'collector'           => 'Collector', 
        'country'             => 'Country', 
        'data_last_modified'  => 'Data last modified', 
        'herbarium'           => 'Herbarium', 
        'identifications'     => 'Identifications', 
        'locality'            => 'Locality', 
        'notes'               => 'Notes', 
        'resource_type'       => 'Resource Type', 
    );
    
    public function __construct(Zend_Db_Adapter_Abstract $db, $username, $password)
    {
        // http://www.php.net/manual/en/domdocument.loadhtml.php#95463
        libxml_use_internal_errors(true);
        
        $this->_db = $db;
        $this->_username = $username;
        $this->_password = $password;
        
        // Set the HTTP client.
        require_once 'Zend/Http/Client.php';
        $this->_client = new Zend_Http_Client;
        $this->_client->setConfig(array('keepalive' => true, 
                                        'timeout' => 100, 
                                        'storeresponse' => false)); // attempt to reduce memory load
        $this->_client->setCookieJar();
    }
    
    public function ingest(array $params, $returnTotalCount = false)
    {
        /* SEARCH JSTOR */
        
        // Set the search parameters.
        $queryPrefix = array();
        foreach ($this->_parameterFields as $key => $value) {
            if (isset($params[$key])) {
                // "st" parameters are special cases.
                if ('st' == $value) {
                    $queryPrefix[] = 'st=' . urlencode($params[$key]);
                } else {
                    $parameters[$value] = $params[$key];
                }
            }
        }
        
        // Zend_Http_Client does not allow duplicate GET keys, following HTTP 
        // specification. JSTOR uses duplicate keys for certain search fields. 
        // Need to build a URL query prefix to compensate.
        $queryPrefix = '?' . implode('&', $queryPrefix);
        
        // Perform the search but don't parse the results. JSTOR must store the 
        // previous search in a cookie.
        $this->_client->setUri(self::URL . self::PATH_SEARCH . $queryPrefix);
        $this->_client->setParameterGet($parameters);
        $response = $this->_client->request();
        // Store the search query for later.
        $searchQuery = $this->_client->getUri()->getQuery();
        $this->_client->resetParameters();
        
        // Return total count if desired.
        if ($returnTotalCount) {
            $doc = new DOMDocument();
            $doc->loadHTML($response->getBody());
            $xpath = new DOMXpath($doc);
            // Firefox adds tbody so don't include it in the XPath.
            $results = $xpath->query('/html/body/table/tr[2]/td[3]/article/nav');
            preg_match('/^\s+([\d,]+) Results/', $results->item(0)->nodeValue, $matches);
            return (int) str_replace(',', '', $matches[1]);
        }
        
        /* LOGIN TO JSTOR*/
        
        // Login to JSTOR to access the XLS download action.
        $this->_client->setUri(self::URL . self::PATH_LOGIN);
        $this->_client->setParameterPost(array('login' => $this->_username, 
                                               'password' => $this->_password, 
                                               'submit' => 'true'));
        $this->_client->request('POST');
        $this->_client->resetParameters();
        
        /* DOWNLOAD XLS */
        
        // Download the XLS file containing every DOI in the result set of the 
        // previous search.
        $this->_client->setUri(self::URL . self::PATH_DOWNLOAD);
        $this->_client->setParameterGet('downtr', 'downloadAllDois');
        $response = $this->_client->request();
        $this->_client->resetParameters();
        
        // Must create a temporary XLS file for PHPExcel, since the library 
        // doesn't seem to have a loadString method.
        $tempFile = tempnam(sys_get_temp_dir(), 'PHPExcel');
        $fileHandle = fopen($tempFile, 'w');
        fwrite($fileHandle, $response->getBody());
        
        /* PARSE XLS */
        
        // Parse the XLS file and set the JSTOR DOIs of the previous search.
        // See: http://phpexcel.codeplex.com/
        require_once 'PHPExcel/IOFactory.php';
        $sheet = PHPExcel_IOFactory::load($tempFile)->getActiveSheet();
        fclose($fileHandle);
        $dois = array();
        foreach ($sheet->getCellCollection() as $cellName) {
            $dois[] = $sheet->getCell($cellName)->getValue();
        }
        
        /* INGEST RESOURCES */
        
        // Require Zend_Db_Expr for SQL expressions.
        require_once 'Zend/Db/Expr.php';
        
        // Save this search to the database.
        $this->_db->insert('searches', array('query' => $searchQuery, 
                                             'inserted' => new Zend_Db_Expr('NOW()')));
        $searchId = $this->_db->lastInsertId();
        
        // Iterate the resource DOIs.
        foreach ($dois as $doi) {
            
            // Do not ingest the resource if it already exists in the database.
            $sql = 'SELECT id FROM resources WHERE doi = ?';
            $id = $this->_db->fetchOne($sql, $doi);
            if ($id) {
                $this->_db->insert('searches_resources', array('search_id' => $searchId, 
                                                               'resource_id' => $id));
                continue;
            }
            
            // Begin building this resource's metadata array, including DOI and 
            // inserted timestamp.
            $resource = array('doi' => $doi, 
                              'inserted' => new Zend_Db_Expr('NOW()'));
            
            // Request the resource metadata.
            $this->_client->setUri(self::URL . self::PATH_CITATION);
            $this->_client->setParameterGet('doi', $doi);
            $response = $this->_client->request();
            
            $doc = new DOMDocument();
            // Must convert HTML entities to UTF-8 before loading into DOM. 
            // Otherwise DOMXpath munges UTF-8 metadata values.
            // See: http://stackoverflow.com/questions/1154528/how-to-force-xpath-to-use-utf8/3304473#3304473
            $doc->loadHTML(mb_convert_encoding($response->getBody(), 
                                               'HTML-ENTITIES', 
                                               'UTF-8'));
            $xpath = new DOMXpath($doc);
            
            // Set resource title.
            $div = $xpath->query('//div[@class="document-heading"]');
            $resource['title'] = strip_tags($div->item(0)->nodeValue);
            
            // Set resource metadata.
            $tds = $xpath->query('//td[@class="name"]');
            foreach ($tds as $td) {
                if (in_array($td->nodeValue, $this->_metadataFields)) {
                    // $results[local name] = [JSTOR value]
                    $resource[array_search($td->nodeValue, $this->_metadataFields)] = strip_tags($td->nextSibling->nodeValue);
                }
            }
            
            // Extract the collection year from the collection date.
            if (isset($resource['collection_date'])) {
                preg_match('/\d{4}/', $resource['collection_date'], $yearMatches);
                if (count($yearMatches)) {
                    $resource['collection_year'] = $yearMatches[0];
                }
            }
            
            // Save the resource to the database.
            $this->_db->insert('resources', $resource);
            
            // Save the search/resource relationship to the database.
            $this->_db->insert('searches_resources', array('search_id' => $searchId, 
                                                           'resource_id' => $this->_db->lastInsertId()));;
        }
        
        return $searchId;
    }
}