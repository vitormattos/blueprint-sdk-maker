<?php

namespace Blueprint;

class Api
{
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

