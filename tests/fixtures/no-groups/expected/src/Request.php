<?php

namespace Blueprint;

class Request
{
    /**
     * FORMAT
     * @var 1A
     */
    protected $format = 'string';
    /**
     * HOST
     * @var https://api.example.com
     */
    protected $host = 'string';
    /**
     * Send an request
     *
     * @param string $method The method of HTTP request
     * @param string $url The path of endpoint to request
     * @return array|Exception
     */
    protected function request(string $method, string $url) : array
    {
        $client = new \GuzzleHttp\Client(['base_uri' => $this->hots]);
        try {
            $res = $client->request($method, $url);
        } catch (Exception $e) {
            $res = json_decode($e->getResponse()->getBody()->getContents());
        }
        return json_decode($res->getBody()->getContents(), true);
    }
}

