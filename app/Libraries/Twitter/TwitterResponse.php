<?php

namespace App\Libraries\Twitter;

use App\Libraries\Server\ServerResponse;
use Psr\Http\Message\ResponseInterface;

class TwitterResponse implements ServerResponse
{

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function json()
    {
        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function object()
    {
        return \GuzzleHttp\json_decode($this->json());
    }

    /**
     * {@inheritdoc}
     */
    public function array()
    {
        return \GuzzleHttp\json_decode($this->json(), true);
    }
}