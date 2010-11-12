<?php
// Require the configuration file.
require 'config.php';

// Fetch the search.
$sql = 'SELECT * FROM searches WHERE id = ?';
// $argv[2] is: $ php process.php -s {search ID}
$search = $db->fetchRow($sql, $argv[2]);

// Set the status to 'In Process'.
$db->update('searches', 
            array('status' => 'In Process'), 
            'id = ' . $search['id']);

try {
    
    process($db, $search['id']);
    
// Handle "Unable to read response, or response is empty" errors.
} catch (Zend_Http_Client_Exception $e) {
    
    // Attempt the process another two times then error out. There must be a 
    // better way to do this.
    try {
        process($db, $search['id']);
    } catch (Zend_Http_Client_Exception $e) {
        try {
            process($db, $search['id']);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
// Something went wrong.
} catch (Exception $e) {
    
    // log errors here.
    
    $db->update('searches', 
                array('status' => 'Error'), 
                'id = ' . $search['id']);
}

function process($db, $searchId)
{
    // Ingest process.
    require_once 'Plants/Process/Ingest.php';
    $ingest = new Plants_Process_Ingest($db, JSTOR_USERNAME, JSTOR_PASSWORD);
    $ingest->ingest($searchId);
    
    // Geolocation process.
    require_once 'Plants/Process/Geolocate.php';
    $geolocate = new Plants_Process_Geolocate($db);
    $geolocate->geolocate($searchId);
    
    // Set the status to 'Completed'.
    $db->update('searches', 
                array('status' => 'Completed', 
                      'process_end' => new Zend_Db_Expr('NOW()')), 
               'id = ' . $searchId);
}
