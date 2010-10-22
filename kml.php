<?php
require 'config.php';

// Content type must be text/xml to render properly in browsers.
header ('Content-Type: text/xml');

// Write KML process.
require_once 'Plants/Process/Kml.php';
$kml = new Process_Kml($db);
$kml->write(1);
