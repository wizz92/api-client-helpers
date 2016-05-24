<?php 


function array_sign($array, $prepend = '', $sign = '+')
{
    $results = [];

    foreach ($array as $key => $value) 
    {
        if (is_array($value)) {
            $results = array_merge($results, array_sign($value, $prepend.$key.$sign));
        } else {
            $results[$prepend.$key] = $value;
        }
    }

    return $results;
}


function contains($str, array $arr)
{
    foreach($arr as $a) 
    {
        if (stripos($a, $str) !== false) return true;
    }
    return false;
}