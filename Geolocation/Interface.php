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
    public function query($location, $limit);
    
    /**
     * Get the request URI for the query.
     * 
     * @return string The request URI included in the request line of the HTTP 
     * request.
     */
    public function getRequestUri();
    
    /**
     * Get the HTTP response for the query.
     * 
     * @return string The entire HTTP response.
     */
    public function getResponse();
    
    /**
     * Get the total count of the query results.
     * 
     * @return int The total count.
     */
    public function getTotalCount();
    
    /**
     * Get the latitude of a specific query result.
     * 
     * @param int The index of the query result from which to get the latitude.
     * @return string The latitude. Don't return float due to lack of precision.
     */
    public function getLatitude($index);
    
    /**
     * Get the longitude of a specific query result.
     * 
     * @param int The index of the query result from which to get the longitude.
     * @return string The longitude. Don't return float due to lack of precision.
     */
    public function getLongitude($index);
    
    /**
     * Get the location of a specific query result.
     * 
     * @param int The index of the query result from which to get the location.
     * @return string The parsed location, concatenated if necessary.
     */
    public function getLocation($index);
}