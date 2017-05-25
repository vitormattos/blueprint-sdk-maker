<?php

namespace BlueprintApi\Core;

class Api
{
    /**
     * FORMAT
     * @var 1A
     */
    protected $format = '1A';
    /**
     * HOST
     * @var https://api.example.com
     */
    protected $host = 'https://api.example.com';
    public function __construct()
    {
    }
    /**
     * Define the base URL to API
     * @param string $host
     **/
    function setHost($host = null)
    {
        $this->host = $host;
    }
}

