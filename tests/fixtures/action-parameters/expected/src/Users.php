<?php

namespace BlueprintApi;

class Users extends Request
{
    /**
     * Retrieve User
     * 
     * @param stringUsername
     * @param
     * @param boolean
     * @param number
     * @return Array|Exception array
     */
    function user(string $id, $country, boolean $active, number $votes) : array
    {
        $method = 'get';
        $path = "/users/{$id}{$country}{$active}{$votes}";
        if ($active == 'true' || $active == '1') {
            $active = true;
        } else {
            $active = false;
        }
        return self::request($method, $path);
    }
}

