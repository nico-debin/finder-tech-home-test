<?php namespace App\Libraries\Server;

interface ServerResponse
{
    /**
     * Gets the response status code of the last request
     *
     * @return int Status code.
     */
    public function getStatusCode();

    /**
     * Gets the response in an JSON format
     *
     * @return array of Json responses
     */
    public function json();

    /**
     * Gets the response in an Object format
     *
     * @return array of Object responses
     */
    public function object();

    /**
     * Gets the response in an Array format
     *
     * @return array of array responses
     */
    public function array();
}