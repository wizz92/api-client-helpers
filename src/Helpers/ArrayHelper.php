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

    public static function clear_string_from_shit($string, $shit = '.pdf.pdf', $replacement = '.pdf')
    {
        if (stripos($string, $shit)) 
        {
            $string = str_replace($shit, $replacement, $string);
            $string = clear_string_from_shit($string, $shit, $replacement);
        }
        return $string;
    }

}