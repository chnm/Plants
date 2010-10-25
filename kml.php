<?php
require 'config.php';

// Write KML process.
require_once 'Plants/Process/Kml.php';
$kml = new Process_Kml($db);
$kml->write(1, 1000, true);
