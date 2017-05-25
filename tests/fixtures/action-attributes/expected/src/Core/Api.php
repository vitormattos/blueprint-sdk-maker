<?php

namespace BlueprintSdk\Core;

class Api
{
    /**
     * Users
     * @var Users
     */
    public $Users;
    /**
     * Base url to API
     * @var string
     */
    protected $host;
    public function __construct()
    {
        $this->Users = new \BlueprintSdk\Entity\Users($this->host);
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

