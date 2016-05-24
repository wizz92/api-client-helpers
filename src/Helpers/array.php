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

function contains_string($str, array $arr)
{
    foreach($arr as $a) 
    {
        if (stripos('q'.$str, $a) !== false) return true;
    }
    return false;
}

function clear_string_from_shit($string, $shit = '.pdf.pdf', $replacement = '.pdf')
{
    if (stripos($string, $shit)) 
    {
        $string = str_replace($shit, $replacement, $string);
        $string = clear_string_from_shit($string, $shit, $replacement);
    }
    return $string;
}