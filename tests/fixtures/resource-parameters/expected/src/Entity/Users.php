<?php

namespace BlueprintApi\Entity;

class Users extends \BlueprintApi\Core\Request
{
    /**
    * Retrieve User
    * 
    * @param number (optional)Database ID
    
    Additional description
    * @return Array|Exception array
    */
    function user(number $id = null) : array
    {
        $method = 'get';
        $path = "/users/{$id}";
        return self::request($method, $path);
    }
}

