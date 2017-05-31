<?php

namespace BlueprintApi\Core;

class Request
{
    public function __construct($host = null)
    {
        if ($host) {
            $this->host = $host;
        }
    }
    /**
     * Send an request
     *
     * @param string $method The method of HTTP request
     * @param string $url The path of endpoint to request
     * @return array|Exception
     */
    protected function request(string $method, string $url) : array
    {
        $client = new \GuzzleHttp\Client(['base_uri' => $this->host]);
        try {
            $res = $client->request($method, $url);
        } catch (Exception $e) {
            $res = json_decode($e->getResponse()->getBody()->getContents(), true);
        }
        return json_decode($res->getBody()->getContents(), true);
    }
}

