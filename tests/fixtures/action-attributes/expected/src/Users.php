<?php

namespace BlueprintApi;

class Users extends Request
{
    /**
     * List all users
     * 
     * @return Array|Exception array
     */
    function user() : array
    {
        $method = 'get';
        $path = "/users";
        return self::request($method, $path);
    }
}

