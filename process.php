<?php
// Require the configuration file.
require 'config.php';

$searchId = 1;

// Ingest process.
require_once 'Plants/Process/Ingest.php';
$ingest = new Plants_Process_Ingest($db, JSTOR_USERNAME, JSTOR_PASSWORD);
$ingest->ingest($searchId);

// Geolocation process.
require_once 'Plants/Process/Geolocate.php';
$geolocate = new Plants_Process_Geolocate($db);
$geolocate->geolocate($searchId);
