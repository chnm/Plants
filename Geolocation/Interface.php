<?php
interface Plants_Geolocation_Interface
{
    public function query($location, $limit);
    public function getResponse();
    public function getTotalCount();
    public function getLatitude($index);
    public function getLongitude($index);
    public function getLocation($index);
}