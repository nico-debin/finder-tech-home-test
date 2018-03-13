<?php namespace App\Libraries\Server;

interface Server
{
    /**
     * Gets the response status code of the last request
     *
     * @return int Status code.
     */
    public function getHttpStatusCode();

    /**
     * Performs an HTTP request to an API endpoint
     *
     * @param string $method HTTP verb such as GET, POST
     * @param string $endpoint API endpoint
     * @param array $params API endpoint parameters
     * @return ServerResponse
     * @throws \Exception
     */
    public function request($method, $endpoint, array $params = []);

    /**
     * HTTP GET request to an API endpoint
     *
     * @param string $endpoint API endpoint
     * @param array $params API endpoint parameters
     * @return ServerResponse
     */
    public function get($endpoint, array $params);

}