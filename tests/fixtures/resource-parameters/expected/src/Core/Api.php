<?php

namespace BlueprintApi\Core;

class Api
{
    /**
     * Users
     * @var \BlueprintApi\Entity\Users
     */
    public $Users;
    /**
     * Base url to API
     * @var string
     */
    protected $host;
    public function __construct()
    {
        $this->Users = new \BlueprintApi\Entity\Users($this->host);
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

