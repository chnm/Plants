<?php
// Require the configuration file.
require 'config.php';

// JSTOR Search URL.
$searchUrl = 'http://plants.jstor.org/search?searchText=field&st=397153&st=6808&st=6836&t=397221';

// Ingest process.
require_once 'Plants/Process/Ingest.php';
$ingest = new Plants_Process_Ingest($db, JSTOR_USERNAME, JSTOR_PASSWORD);
$searchId = $ingest->ingest($searchUrl);

// Geolocation process.
require_once 'Plants/Process/Geolocate.php';
$geolocate = new Plants_Process_Geolocate($db);
$geolocate->geolocate($searchId);
