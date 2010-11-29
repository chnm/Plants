<?php
// Require the configuration file.
require 'config.php';

try {
    // Fetch the search.
    $sql = 'SELECT * FROM searches WHERE id = ?';
    // $argv[2] is: $ php process.php -s {search ID}
    $search = $db->fetchRow($sql, $argv[2]);
    // Set the status to 'In Process'.
    $db->update('searches', 
                array('status' => 'In Process'), 
                'id = ' . $search['id']);
    
    $attempt= 1;
    while (true) {
        try {
            // Ingest process.
            require_once 'Plants/Process/Ingest.php';
            $ingest = new Plants_Process_Ingest($db, JSTOR_USERNAME, JSTOR_PASSWORD);
            $ingest->ingest($search['id']);
            
            // Geolocation process.
            require_once 'Plants/Process/Geolocate.php';
            $geolocate = new Plants_Process_Geolocate($db);
            $geolocate->geolocate($search['id']);
            
            // Set the status to 'Completed'.
            $db->update('searches', 
                        array('status' => 'Completed', 
                              'process_end' => new Zend_Db_Expr('NOW()')), 
                       'id = ' . $search['id']);
            break;
        // Attempt the process three times for "Unable to read response, or 
        // response is empty" errors. 
        } catch (Zend_Http_Client_Exception $e) {
            $attempt++;
            if (3 >= $attempt) {
                continue;
            }
            // Throw Zend_Http_Client_Exception after three attempts.
            throw $e;
        }
    }
} catch (Exception $e) {
    // Log errors.
    $fp = @fopen(FILEPATH_ERRORLOG, 'a');
    @fwrite($fp, "\n$e\n");
    
    if (!$db->isConnected()) {
        $db = getDb();
    }
    
    $db->update('searches', 
                array('status' => 'Error'), 
                'id = ' . $search['id']);
}
