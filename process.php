<?php
/* BOOTSTRAP */

ini_set('display_errors', 1);

require 'config.php';

set_include_path(get_include_path() 
               . PATH_SEPARATOR . PATH_ZEND 
               . PATH_SEPARATOR . PATH_PLANTS);

// Set the database object.
require_once 'Zend/Db.php';
$db = Zend_Db::factory('Mysqli', array(
    'host'     => DB_HOST, 
    'username' => DB_USERNAME, 
    'password' => DB_PASSWORD, 
    'dbname'   => DB_DBNAME, 
    'charset'  => 'utf8', // must include utf8 charset
));

/* PROCESSES */

// Ingest process.
require_once 'Plants/Process/Ingest.php';
$ingest = new Plants_Process_Ingest($db, JSTOR_USERNAME, JSTOR_PASSWORD);
$searchId = $ingest->ingest($params);

// Geolocation process.
require_once 'Plants/Process/Geolocate.php';
$geolocate = new Plants_Process_Geolocate($db, $searchId);
$geolocate->geolocate();
