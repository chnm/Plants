<?php
// Require the configuration file.
require 'config.php';

// Query parameters.
$params = array(
    'text' => 'rose', 
    'resourceTypeId' => 397153,
    //'herbariumId' => 405650, 
    //'geographyId' => 6734,
);

// Ingest process.
require_once 'Plants/Process/Ingest.php';
$ingest = new Plants_Process_Ingest($db, JSTOR_USERNAME, JSTOR_PASSWORD);
$searchId = $ingest->ingest($params);

// Geolocation process.
require_once 'Plants/Process/Geolocate.php';
$geolocate = new Plants_Process_Geolocate($db);
$geolocate->geolocate($searchId);
