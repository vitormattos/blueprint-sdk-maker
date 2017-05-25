<?php

namespace BlueprintApi;

class Api
{
    /**
     * Users
     * @var Users
     */
    public $Users;
    public function __construct()
    {
        $this->Users = new Users();
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

