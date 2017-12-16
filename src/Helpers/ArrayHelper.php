<?php 

namespace Wizz\ApiClientHelpers\Helpers;

class ArrayHelper 
{
    public static function array_sign($array, $prepend = '', $sign = '+', $ignore_array = false) {
        $results = [];
        
        foreach ($array as $key => $value) 
        {
            if (is_array($value) && $ignore_array) {
                $results = array_merge($results, self::array_sign($value, $prepend.$key.$sign));
            } else {
                $results[$prepend.$key] = $value;
            }
        }
        return $results;
    }

}