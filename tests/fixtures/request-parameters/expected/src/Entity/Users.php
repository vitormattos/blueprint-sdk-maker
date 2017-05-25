<?php

namespace BlueprintSdk\Entity;

class Users extends \BlueprintSdk\Core\Request
{
    /**
     * List Users
     * 
     * @param number (optional)The maximum number of users to return.
     * @return Array|Exception array
     */
    function users(number $limit = null) : array
    {
        $method = 'get';
        $path = "/users{$limit}";
        if (!is_null($limit)) {
            $params['limit'] = $limit;
        }
        $path .= '?' . http_build_query($params);
        return self::request($method, $path);
    }
}

