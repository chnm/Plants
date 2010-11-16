<?php
/**
 * This interface describes the methods the geolocation classes must implement.
 */
interface Plants_Geolocation_Interface
{
    /**
     * Query the geolocation service API.
     * 
     * @param string $location The string to geolocate.
     * @param int $limit The maximum number of geolocation results.
     */
    public function query($location, $country = null);
    
    /**
     * Get the total count of the query results.
     * 
     * @return int The total count.
     */
    public function getTotalCount();
    
    /**
     * Get the latitude of a specific query result.
     * 
     * @return string The latitude. Don't return float due to lack of precision.
     */
    public function getLatitude();
    
    /**
     * Get the longitude of a specific query result.
     * 
     * @return string The longitude. Don't return float due to lack of precision.
     */
    public function getLongitude();
}