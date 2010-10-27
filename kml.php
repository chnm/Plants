<?php
require 'config.php';

// Write KML process.
require_once 'Plants/Process/Kml.php';
$kml = new Plants_Process_Kml($db);
$kml->write($_REQUEST['searchId'], 500, true);
