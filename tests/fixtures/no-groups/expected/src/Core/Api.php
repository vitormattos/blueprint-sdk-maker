<?php

namespace BlueprintApi\Core;

class Api
{
    /**
     * Base url to API
     * @var string
     */
    protected $host;
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

