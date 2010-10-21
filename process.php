<?php
// Require the configuration file.
require 'config.php';

// Ingest process.
require_once 'Plants/Process/Ingest.php';
$ingest = new Plants_Process_Ingest($db, JSTOR_USERNAME, JSTOR_PASSWORD);
$searchId = $ingest->ingest($params);

// Geolocation process.
require_once 'Plants/Process/Geolocate.php';
$geolocate = new Plants_Process_Geolocate($db, $searchId);
$geolocate->geolocate();
